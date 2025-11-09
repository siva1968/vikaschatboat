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
     * Prepare enquiry data for MCB API
     * 
     * @param array $enquiry - Enquiry data from database
     * @return array - Formatted data for MCB API
     */
    private function prepare_mcb_data($enquiry) {
        try {
            // Map grade to MCB class ID
            $class_id = $this->map_grade_to_class_id($enquiry['grade']);
            
            // Map board to MCB board ID
            $board_id = $this->map_board_to_board_id($enquiry['board']);
            
            // Map lead source to MCB lead source ID
            $lead_source = isset($enquiry['source']) ? $enquiry['source'] : 'unknown';
            $source_id = $this->map_lead_source($lead_source);
            
            // Extract marketing parameters from utm_data
            $utm_data = !empty($enquiry['utm_data']) ? json_decode($enquiry['utm_data'], true) : array();
            $click_id_data = !empty($enquiry['click_id_data']) ? json_decode($enquiry['click_id_data'], true) : array();
            
            // Prepare MCB payload with marketing parameters
            $mcb_data = array(
                'OrgID' => $this->mcb_settings['organization_id'],
                'BranchID' => $this->mcb_settings['branch_id'],
                'StudentName' => sanitize_text_field($enquiry['student_name']),
                'ClassID' => $class_id,
                'BoardID' => $board_id,
                'ParentName' => sanitize_text_field($enquiry['parent_name'] ?? $enquiry['student_name']),
                'ParentMobileNo' => sanitize_text_field($enquiry['phone']),
                'ParentEmailID' => sanitize_email($enquiry['email']),
                'AcademicYear' => isset($enquiry['academic_year']) ? sanitize_text_field($enquiry['academic_year']) : date('Y') . '-' . (date('Y') + 1),
                'EnquiryID' => $enquiry['enquiry_number'],
                'LeadSourceID' => $source_id,
                'Remarks' => isset($enquiry['notes']) ? sanitize_textarea_field($enquiry['notes']) : '',
                'Phone' => sanitize_text_field($enquiry['phone']),
                'Email' => sanitize_email($enquiry['email']),
                'Gender' => isset($enquiry['gender']) ? sanitize_text_field($enquiry['gender']) : '',
                'DateOfBirth' => isset($enquiry['date_of_birth']) ? sanitize_text_field($enquiry['date_of_birth']) : '',
                
                // Marketing Parameters - UTM Tracking
                'UTMSource' => sanitize_text_field($utm_data['utm_source'] ?? ''),
                'UTMMedium' => sanitize_text_field($utm_data['utm_medium'] ?? ''),
                'UTMCampaign' => sanitize_text_field($utm_data['utm_campaign'] ?? ''),
                'UTMContent' => sanitize_text_field($utm_data['utm_content'] ?? ''),
                'UTMTerm' => sanitize_text_field($utm_data['utm_term'] ?? ''),
                
                // Click IDs - Google & Facebook Tracking
                'GClickID' => sanitize_text_field($enquiry['gclid'] ?? $click_id_data['gclid'] ?? ''),
                'FBClickID' => sanitize_text_field($enquiry['fbclid'] ?? $click_id_data['fbclid'] ?? ''),
                
                // Additional tracking
                'IPAddress' => sanitize_text_field($enquiry['ip_address'] ?? ''),
                'LeadSource' => sanitize_text_field($lead_source),
                'CapturedFrom' => 'EduBot Chatbot'
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
    private function map_grade_to_class_id($grade) {
        $grade = strtolower(trim($grade));
        
        // Grade to Class ID mapping
        $mapping = array(
            'nursery' => '1',
            'pp1' => '2',
            'pp2' => '3',
            'lkg' => '4',
            'ukg' => '5',
            'grade 1' => '6',
            'class 1' => '6',
            '1' => '6',
            'grade 2' => '7',
            'class 2' => '7',
            '2' => '7',
            'grade 3' => '8',
            'class 3' => '8',
            '3' => '8',
            'grade 4' => '9',
            'class 4' => '9',
            '4' => '9',
            'grade 5' => '10',
            'class 5' => '10',
            '5' => '10',
            'grade 6' => '11',
            'class 6' => '11',
            '6' => '11',
            'grade 7' => '12',
            'class 7' => '12',
            '7' => '12',
            'grade 8' => '13',
            'class 8' => '13',
            '8' => '13',
            'grade 9' => '14',
            'class 9' => '14',
            '9' => '14',
            'grade 10' => '15',
            'class 10' => '15',
            '10' => '15',
            'grade 11' => '16',
            'class 11' => '16',
            '11' => '16',
            'grade 12' => '17',
            'class 12' => '17',
            '12' => '17'
        );
        
        return isset($mapping[$grade]) ? $mapping[$grade] : '10'; // Default to Grade 5
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
     * Map lead source to MCB lead source ID
     * 
     * @param string $source - Lead source name
     * @return string - MCB Lead Source ID
     */
    private function map_lead_source($source) {
        $source = strtolower(trim($source));
        $mcb_settings = get_option('edubot_mcb_settings', array());
        
        if (!isset($mcb_settings['lead_source_mapping'])) {
            return '280'; // Default to organic
        }
        
        $mapping = $mcb_settings['lead_source_mapping'];
        return isset($mapping[$source]) ? $mapping[$source] : $mapping['default'];
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
