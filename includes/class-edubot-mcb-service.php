<?php
/**
 * MCB (MyClassBoard) Sync Service
 * 
 * Handles synchronization of enquiries to MyClassBoard CRM system
 * Manages API calls, logging, retry logic, and error handling
 * 
 * @package EduBot_Pro
 * @subpackage MCB_Integration
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EduBot_MCB_Service {
    
    private static $instance = null;
    private $mcb_settings = null;
    private $logger = null;
    
    /**
     * Singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_settings();
    }
    
    /**
     * Load MCB settings from WordPress options
     */
    private function load_settings() {
        $this->mcb_settings = get_option('edubot_mcb_settings', array());
    }
    
    /**
     * Check if MCB sync is enabled
     */
    public function is_sync_enabled() {
        return !empty($this->mcb_settings['sync_enabled']) && 
               !empty($this->mcb_settings['enabled']);
    }
    
    /**
     * Check if auto-sync is enabled
     */
    public function is_auto_sync_enabled() {
        return !empty($this->mcb_settings['auto_sync']);
    }
    
    /**
     * Sync an enquiry to MCB
     * 
     * @param int $enquiry_id - Enquiry ID to sync
     * @return array - Sync result with status and message
     */
    public function sync_enquiry($enquiry_id) {
        global $wpdb;
        
        if (!$this->is_sync_enabled()) {
            return array(
                'success' => false,
                'message' => 'MCB sync is not enabled',
                'error' => 'MCB_DISABLED'
            );
        }
        
        // Get enquiry data
        $enquiry = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}edubot_enquiries WHERE id = %d", $enquiry_id),
            ARRAY_A
        );
        
        if (!$enquiry) {
            return array(
                'success' => false,
                'message' => 'Enquiry not found',
                'error' => 'ENQUIRY_NOT_FOUND'
            );
        }
        
        // Prepare data for MCB
        $mcb_data = $this->prepare_mcb_data($enquiry);
        
        if (!$mcb_data) {
            return array(
                'success' => false,
                'message' => 'Failed to prepare MCB data',
                'error' => 'DATA_PREPARATION_FAILED'
            );
        }
        
        // Call MCB API
        $api_response = $this->call_mcb_api($mcb_data);
        
        // Process response and log
        $sync_result = $this->process_api_response($api_response, $enquiry_id, $mcb_data);
        
        return $sync_result;
    }
    
    /**
     * Preview MCB sync data WITHOUT submitting to API
     * 
     * @param int $enquiry_id - Enquiry ID to preview
     * @return array - MCB data that would be sent
     */
    public function preview_mcb_data($enquiry_id) {
        global $wpdb;
        
        if (!$this->is_sync_enabled()) {
            return array(
                'success' => false,
                'message' => 'MCB sync is not enabled',
                'mcb_data' => null
            );
        }
        
        // Get enquiry data
        $enquiry = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}edubot_enquiries WHERE id = %d", $enquiry_id),
            ARRAY_A
        );
        
        if (!$enquiry) {
            return array(
                'success' => false,
                'message' => 'Enquiry not found',
                'mcb_data' => null
            );
        }
        
        // Get application data (for marketing data)
        $application = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}edubot_applications WHERE application_number = %s",
                $enquiry['enquiry_number']
            ),
            ARRAY_A
        );
        
        // Merge application data if found
        if ($application) {
            $enquiry['utm_data'] = $application['utm_data'];
            $enquiry['gclid'] = $application['gclid'];
            $enquiry['fbclid'] = $application['fbclid'];
            $enquiry['click_id_data'] = $application['click_id_data'];
        }
        
        // Prepare data for MCB
        $mcb_data = $this->prepare_mcb_data($enquiry);
        
        if (!$mcb_data) {
            return array(
                'success' => false,
                'message' => 'Failed to prepare MCB data',
                'mcb_data' => null
            );
        }
        
        // Return preview without sending
        return array(
            'success' => true,
            'message' => 'MCB data prepared successfully',
            'enquiry_number' => $enquiry['enquiry_number'],
            'mcb_data' => $mcb_data,
            'marketing_data' => array(
                'utm_source' => $mcb_data['UTMSource'] ?? '',
                'utm_medium' => $mcb_data['UTMMedium'] ?? '',
                'utm_campaign' => $mcb_data['UTMCampaign'] ?? '',
                'gclid' => $mcb_data['GClickID'] ?? '',
                'fbclid' => $mcb_data['FBClickID'] ?? ''
            )
        );
    }
    
    /**
     * Prepare enquiry data for MCB API
     * 
     * @param array $enquiry - Enquiry data from database
     * @return array - Formatted data for MCB API
     */
    private function prepare_mcb_data($enquiry) {
        try {
            // Map board to MCB board ID first
            $board = isset($enquiry['board']) ? $enquiry['board'] : 'CBSE';
            $board_id = $this->map_board_to_board_id($board);
            
            // Map grade to MCB class ID (board-dependent)
            $grade = isset($enquiry['grade']) ? $enquiry['grade'] : '';
            $class_id = $this->map_grade_to_class_id($grade, $board);
            
            // Map academic year to MCB academic year ID
            $academic_year = isset($enquiry['academic_year']) ? $enquiry['academic_year'] : date('Y') . '-' . (date('Y') + 1);
            $academic_year_id = $this->map_academic_year_to_id($academic_year);
            
            // Extract marketing parameters from utm_data
            $utm_data = !empty($enquiry['utm_data']) ? json_decode($enquiry['utm_data'], true) : array();
            $click_id_data = !empty($enquiry['click_id_data']) ? json_decode($enquiry['click_id_data'], true) : array();
            
            // Priority: Use UTM source if available, otherwise use enquiry source field
            $utm_source = $utm_data['utm_source'] ?? '';
            $lead_source = $utm_source ?: (isset($enquiry['source']) ? $enquiry['source'] : '');
            
            // Map lead source to MCB lead source ID (with UTM source priority)
            $source_id = $this->map_lead_source($lead_source);
            
            // Build remarks with EnquiryID prefix (as per MCB API requirement)
            // Include "Chat" prefix to distinguish from website/Ninjaform enquiries
            $remarks = isset($enquiry['notes']) ? sanitize_textarea_field($enquiry['notes']) : '';
            $remarks_with_enquiry = 'Chat EnquiryID: ' . $enquiry['enquiry_number'];
            if (!empty($remarks)) {
                $remarks_with_enquiry .= ' | ' . $remarks;
            }
            
            // Prepare MCB payload - ONLY fields that MCB API accepts
            // Based on: https://corp.myclassboard.com/api/EnquiryService/SaveEnquiryDetails
            $mcb_data = array(
                'OrganisationID' => $this->mcb_settings['organization_id'],
                'BranchID' => $this->mcb_settings['branch_id'],
                'StudentName' => sanitize_text_field($enquiry['student_name'] ?? 'NA'),
                'ClassID' => $class_id,
                'AcademicYearID' => $academic_year_id,
                'QueryContactSourceID' => $source_id,
                'FatherName' => !empty($enquiry['parent_name']) ? sanitize_text_field($enquiry['parent_name']) : 'NA',
                'FatherMobile' => !empty($enquiry['phone']) ? sanitize_text_field($enquiry['phone']) : 'NA',
                'FatherEmailID' => !empty($enquiry['email']) ? sanitize_email($enquiry['email']) : 'NA',
                'MotherName' => 'NA',
                'MotherMobile' => !empty($enquiry['phone']) ? sanitize_text_field($enquiry['phone']) : 'NA',
                'DOB' => !empty($enquiry['date_of_birth']) ? sanitize_text_field($enquiry['date_of_birth']) : 'NA',
                'Address1' => 'NA',
                'Remarks' => $remarks_with_enquiry
            );
            
            return $mcb_data;
            
        } catch (Exception $e) {
            error_log('MCB Data Preparation Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Map grade to MCB class ID
     * 
     * @param string $grade - Grade/Class name
     * @return string - MCB Class ID
     */
    private function map_grade_to_class_id($grade, $board = 'CBSE') {
        $grade = strtolower(trim($grade));
        $board = strtoupper(trim($board));
        
        // MCB System Grade/Class ID mapping - BOARD DEPENDENT
        // Different boards have different class IDs for the same grade
        
        if ($board === 'CBSE') {
            // CBSE Board Mapping
            $mapping = array(
                'i' => '943',
                'ii' => '944',
                'iii' => '945',
                'iv' => '946',
                'v' => '947',
                'vi' => '903',
                'vii' => '904',
                'viii' => '894',
                'ix' => '895',
                'x' => '896',
                'xi' => '943',  // Class XI CBSE (same as I CBSE for higher secondary)
                'xii' => '944', // Class XII CBSE (same as II CBSE for higher secondary)
                'grade 1' => '943',
                'class 1' => '943',
                '1' => '943',
                'grade 2' => '944',
                'class 2' => '944',
                '2' => '944',
                'grade 3' => '945',
                'class 3' => '945',
                '3' => '945',
                'grade 4' => '946',
                'class 4' => '946',
                '4' => '946',
                'grade 5' => '947',
                'class 5' => '947',
                '5' => '947',
                'grade 6' => '903',
                'class 6' => '903',
                '6' => '903',
                'grade 7' => '904',
                'class 7' => '904',
                '7' => '904',
                'grade 8' => '894',
                'class 8' => '894',
                '8' => '894',
                'grade 9' => '895',
                'class 9' => '895',
                '9' => '895',
                'grade 10' => '896',
                'class 10' => '896',
                '10' => '896',
                'grade 11' => '943',
                'class 11' => '943',
                '11' => '943',
                'grade 12' => '944',
                'class 12' => '944',
                '12' => '944'
            );
        } elseif ($board === 'CAIE' || $board === 'CAMBRIDGE') {
            // CAIE/Cambridge Board Mapping
            $mapping = array(
                'pre nursery' => '787',
                'nursery' => '273',
                'pp1' => '274',
                'pp2' => '275',
                'grade 1' => '276',
                'class 1' => '276',
                '1' => '276',
                'grade 2' => '277',
                'class 2' => '277',
                '2' => '277',
                'grade 3' => '278',
                'class 3' => '278',
                '3' => '278',
                'grade 4' => '279',
                'class 4' => '279',
                '4' => '279',
                'grade 5' => '280',
                'class 5' => '280',
                '5' => '280',
                'grade 6' => '281',
                'class 6' => '281',
                '6' => '281',
                'grade 7' => '282',
                'class 7' => '282',
                '7' => '282',
                'grade 8' => '283',
                'class 8' => '283',
                '8' => '283',
                'grade 9' => '315',
                'class 9' => '315',
                '9' => '315',
                'grade 10' => '631',
                'class 10' => '631',
                '10' => '631',
                'grade 11' => '910',
                'grade 11 mpc' => '910',
                'grade 11 mbipc' => '911',
                'grade 11 bipc' => '912',
                'grade 11 comm' => '913',
                'class 11' => '910',
                '11' => '910',
                'grade 12' => '914',
                'grade 12 mpc' => '914',
                'grade 12 mbipc' => '915',
                'grade 12 bipc' => '916',
                'grade 12 comm' => '917',
                'class 12' => '914',
                '12' => '914'
            );
        } else {
            // Default mapping (fallback to CAIE)
            $mapping = array(
                'pre nursery' => '787',
                'nursery' => '273',
                'pp1' => '274',
                'pp2' => '275',
                'grade 1' => '276',
                'class 1' => '276',
                '1' => '276',
                'grade 2' => '277',
                'class 2' => '277',
                '2' => '277',
                'grade 3' => '278',
                'class 3' => '278',
                '3' => '278',
                'grade 4' => '279',
                'class 4' => '279',
                '4' => '279',
                'grade 5' => '280',
                'class 5' => '280',
                '5' => '280',
                'grade 6' => '281',
                'class 6' => '281',
                '6' => '281',
                'grade 7' => '282',
                'class 7' => '282',
                '7' => '282',
                'grade 8' => '283',
                'class 8' => '283',
                '8' => '283',
                'grade 9' => '315',
                'class 9' => '315',
                '9' => '315',
                'grade 10' => '631',
                'class 10' => '631',
                '10' => '631',
                'grade 11' => '910',
                'class 11' => '910',
                '11' => '910',
                'grade 12' => '914',
                'class 12' => '914',
                '12' => '914'
            );
        }
        
        return isset($mapping[$grade]) ? $mapping[$grade] : '280'; // Default to Grade 5
    }
    
    /**
     * Map board to MCB board ID
     * 
     * @param string $board - Board name
     * @return string - MCB Board ID
     */
    private function map_board_to_board_id($board) {
        $board = strtoupper(trim($board));
        
        $mapping = array(
            'CBSE' => '1',
            'ICSE' => '2',
            'CAIE' => '3',
            'CAMBRIDGE' => '3',
            'STATE' => '4',
            'IGCSE' => '5'
        );
        
        return isset($mapping[$board]) ? $mapping[$board] : '1'; // Default to CBSE
    }
    
    /**
     * Map academic year to MCB academic year ID
     * 
     * @param string $academic_year - Academic year (e.g., "2025-26")
     * @return string - MCB Academic Year ID
     */
    private function map_academic_year_to_id($academic_year) {
        $academic_year = strtoupper(trim($academic_year));
        
        // MCB Academic Year ID mapping
        $mapping = array(
            '2020-21' => '11',
            '2021-22' => '12',
            '2022-23' => '13',
            '2023-24' => '14',
            '2024-25' => '15',
            '2025-26' => '16',
            '2026-27' => '17',
            '2027-28' => '18'
        );
        
        return isset($mapping[$academic_year]) ? $mapping[$academic_year] : '16'; // Default to 2025-26
    }
    
    /**
     * Map lead source to MCB lead source ID
     * 
     * @param string $source - Lead source name
     * @return string - MCB Lead Source ID
     */
    private function map_lead_source($source) {
        $source = strtolower(trim($source));
        
        // MCB Lead Source ID mapping - from MyClassBoard system
        $mapping = array(
            'news paper' => '84',
            'hoardings' => '85',
            'existing parent' => '232',
            'others' => '233',
            'events' => '234',
            'walkin' => '250',
            'website' => '231',
            'facebook' => '272',
            'facebook lead' => '271',
            'google display' => '270',
            'google search' => '269',
            'instagram' => '268',
            'ebook' => '274',
            'linkedin' => '267',
            'chat bot' => '273',
            'google call ads' => '275',
            'leaflets' => '86',
            'organic' => '280',
            'friends' => '92',
            'youtube' => '446',
            'news letter' => '447',
            'word of mouth' => '448',
            'email' => '286',
            'how did you hear about us?' => '280'
        );
        
        // If source is empty or not found, default to Organic (280)
        if (empty($source) || !isset($mapping[$source])) {
            return '280'; // Default to Organic
        }
        
        return $mapping[$source];
    }
    
    /**
     * Call MCB API endpoint
     * 
     * @param array $data - Data to send to MCB
     * @return array - API response
     */
    private function call_mcb_api($data) {
        $api_url = $this->mcb_settings['api_url'];
        $timeout = intval($this->mcb_settings['timeout'] ?? 65);
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode($data),
            'timeout' => $timeout,
            'sslverify' => true
        );
        
        $response = wp_remote_post($api_url, $args);
        
        return $response;
    }
    
    /**
     * Process API response and log sync attempt
     * 
     * @param array $api_response - Response from MCB API
     * @param int $enquiry_id - Enquiry ID
     * @param array $request_data - Data sent to API
     * @return array - Sync result
     */
    private function process_api_response($api_response, $enquiry_id, $request_data) {
        global $wpdb;
        
        $success = false;
        $error_message = null;
        $response_data = null;
        $mcb_enquiry_id = null;
        $mcb_query_code = null;
        
        // Check for HTTP errors
        if (is_wp_error($api_response)) {
            $error_message = $api_response->get_error_message();
            $sync_status = 'failed';
        } else {
            $http_code = wp_remote_retrieve_response_code($api_response);
            $response_body = wp_remote_retrieve_body($api_response);
            
            if ($http_code === 200) {
                $response_data = json_decode($response_body, true);
                
                // Check MCB response for success
                if (!empty($response_data['Result']) && $response_data['Result'] === 'Success') {
                    $success = true;
                    $sync_status = 'synced';
                    $mcb_enquiry_id = $response_data['QueryCode'] ?? $response_data['EnquiryID'] ?? null;
                    $mcb_query_code = $response_data['QueryCode'] ?? null;
                } else {
                    $error_message = $response_data['Message'] ?? 'Unknown error from MCB API';
                    $sync_status = 'failed';
                }
            } else {
                $error_message = "HTTP Error: $http_code - $response_body";
                $sync_status = 'failed';
            }
        }
        
        // Log sync attempt to wp_edubot_mcb_sync_log
        $wpdb->insert(
            "{$wpdb->prefix}edubot_mcb_sync_log",
            array(
                'enquiry_id' => $enquiry_id,
                'request_data' => wp_json_encode($request_data),
                'response_data' => wp_json_encode($response_data),
                'success' => $success ? 1 : 0,
                'error_message' => $error_message,
                'retry_count' => 0,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s')
        );
        
        // Update enquiry MCB status
        $wpdb->update(
            "{$wpdb->prefix}edubot_enquiries",
            array(
                'mcb_sync_status' => $sync_status,
                'mcb_enquiry_id' => $mcb_enquiry_id,
                'mcb_query_code' => $mcb_query_code
            ),
            array('id' => $enquiry_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        return array(
            'success' => $success,
            'status' => $sync_status,
            'message' => $success ? 'Enquiry synced to MCB successfully' : $error_message,
            'mcb_enquiry_id' => $mcb_enquiry_id,
            'mcb_query_code' => $mcb_query_code
        );
    }
    
    /**
     * Sync all pending enquiries
     * 
     * @return array - Results of all sync attempts
     */
    public function sync_pending_enquiries() {
        global $wpdb;
        
        if (!$this->is_sync_enabled()) {
            return array('success' => false, 'message' => 'MCB sync disabled');
        }
        
        // Get pending enquiries
        $pending = $wpdb->get_results(
            "SELECT id FROM {$wpdb->prefix}edubot_enquiries 
             WHERE mcb_sync_status IS NULL OR mcb_sync_status = 'pending'
             ORDER BY created_at DESC
             LIMIT 50"
        );
        
        $results = array('total' => count($pending), 'synced' => 0, 'failed' => 0);
        
        foreach ($pending as $enquiry) {
            $result = $this->sync_enquiry($enquiry->id);
            if ($result['success']) {
                $results['synced']++;
            } else {
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Retry failed sync with backoff
     * 
     * @param int $sync_log_id - Sync log ID to retry
     * @param int $retry_count - Current retry count
     * @return array - Retry result
     */
    public function retry_failed_sync($sync_log_id, $retry_count = 0) {
        global $wpdb;
        
        $max_retries = intval($this->mcb_settings['retry_attempts'] ?? 3);
        
        if ($retry_count >= $max_retries) {
            return array(
                'success' => false,
                'message' => "Max retries ($max_retries) exceeded"
            );
        }
        
        // Get sync log entry
        $sync_log = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}edubot_mcb_sync_log WHERE id = %d", $sync_log_id),
            ARRAY_A
        );
        
        if (!$sync_log) {
            return array('success' => false, 'message' => 'Sync log not found');
        }
        
        // Retry the sync
        $result = $this->sync_enquiry($sync_log['enquiry_id']);
        
        // Increment retry count
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}edubot_mcb_sync_log SET retry_count = %d, updated_at = %s WHERE id = %d",
                $retry_count + 1,
                current_time('mysql'),
                $sync_log_id
            )
        );
        
        return $result;
    }
}
?>
