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
     * Check if debug mode is enabled
     */
    public function is_debug_enabled() {
        return !empty($this->mcb_settings['debug_mode']);
    }
    
    /**
     * Sync an enquiry to MCB
     * 
     * @param int $enquiry_id - Application ID to sync (from the applications table)
     * @return array - Sync result with status and message
     */
    public function sync_enquiry($enquiry_id) {
        global $wpdb;
        
        // ═════════════════════════════════════════════════════════════════
        error_log('╔════════════════════════════════════════════════════════╗');
        error_log('║     MCB SYNC ENQUIRY - MASTER LOG START              ║');
        error_log('╚════════════════════════════════════════════════════════╝');
        error_log('[SYNC-001] sync_enquiry() called with enquiry_id: ' . $enquiry_id . ' (type: ' . gettype($enquiry_id) . ')');
        error_log('[SYNC-002] Timestamp: ' . current_time('mysql') . ' | Version: ' . EDUBOT_PRO_VERSION);
        
        // Check if MCB sync is enabled
        error_log('[SYNC-003] Checking if MCB sync is enabled...');
        if (!$this->is_sync_enabled()) {
            error_log('[SYNC-004] ❌ MCB sync is DISABLED');
            error_log('[SYNC-005] enabled: ' . ($this->mcb_settings['enabled'] ? 'YES' : 'NO'));
            error_log('[SYNC-006] sync_enabled: ' . ($this->mcb_settings['sync_enabled'] ? 'YES' : 'NO'));
            error_log('╔════════════════════════════════════════════════════════╗');
            error_log('║              SYNC FAILED - MCB DISABLED               ║');
            error_log('╚════════════════════════════════════════════════════════╝');
            return array(
                'success' => false,
                'message' => 'MCB sync is not enabled',
                'error' => 'MCB_DISABLED'
            );
        }
        error_log('[SYNC-007] ✅ MCB sync is ENABLED');
        
        // ═════════════════════════════════════════════════════════════════
        // STEP 1: Get Application Data
        error_log('[SYNC-008] ━━━ STEP 1: Fetching Application Data ━━━');
        error_log('[SYNC-009] Query: SELECT * FROM wp_edubot_applications WHERE id = ' . $enquiry_id);
        
        $application = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}edubot_applications WHERE id = %d", $enquiry_id),
            ARRAY_A
        );
        
        if (!$application) {
            error_log('[SYNC-010] ❌ Application NOT FOUND in database');
            error_log('[SYNC-011] Last SQL: ' . $wpdb->last_query);
            error_log('[SYNC-012] SQL Error: ' . ($wpdb->last_error ?: 'None'));
            error_log('╔════════════════════════════════════════════════════════╗');
            error_log('║          SYNC FAILED - APPLICATION NOT FOUND          ║');
            error_log('╚════════════════════════════════════════════════════════╝');
            return array(
                'success' => false,
                'message' => 'Application not found',
                'error' => 'APPLICATION_NOT_FOUND'
            );
        }
        
        error_log('[SYNC-013] ✅ Application found');
        error_log('[SYNC-014] Application ID: ' . $application['id']);
        error_log('[SYNC-015] Application Number: ' . $application['application_number']);
        error_log('[SYNC-016] Student Name: ' . ($application['student_name'] ?? 'N/A'));
        error_log('[SYNC-017] Email: ' . ($application['email'] ?? 'N/A'));
        error_log('[SYNC-018] Phone: ' . ($application['phone'] ?? 'N/A'));
        
        // ═════════════════════════════════════════════════════════════════
        // STEP 2: Get Enquiry Data
        error_log('[SYNC-019] ━━━ STEP 2: Fetching Enquiry Data ━━━');
        error_log('[SYNC-020] Looking up enquiry_number: ' . $application['application_number']);
        
        $enquiry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}edubot_enquiries WHERE enquiry_number = %s",
                $application['application_number']
            ),
            ARRAY_A
        );
        
        if (!$enquiry) {
            error_log('[SYNC-021] ❌ Enquiry NOT FOUND');
            error_log('[SYNC-022] Last SQL: ' . $wpdb->last_query);
            error_log('[SYNC-023] SQL Error: ' . ($wpdb->last_error ?: 'None'));
            error_log('╔════════════════════════════════════════════════════════╗');
            error_log('║          SYNC FAILED - ENQUIRY NOT FOUND              ║');
            error_log('╚════════════════════════════════════════════════════════╝');
            return array(
                'success' => false,
                'message' => 'Enquiry not found',
                'error' => 'ENQUIRY_NOT_FOUND'
            );
        }
        
        error_log('[SYNC-024] ✅ Enquiry found');
        error_log('[SYNC-025] Enquiry ID: ' . $enquiry['id']);
        error_log('[SYNC-026] Enquiry Number: ' . $enquiry['enquiry_number']);
        error_log('[SYNC-027] Grade: ' . ($enquiry['grade'] ?? 'N/A'));
        error_log('[SYNC-028] Board: ' . ($enquiry['board'] ?? 'N/A'));
        
        // ═════════════════════════════════════════════════════════════════
        // STEP 3: Prepare MCB Data
        error_log('[SYNC-029] ━━━ STEP 3: Preparing MCB Data ━━━');
        
        $mcb_data = $this->prepare_mcb_data($enquiry);
        
        if (!$mcb_data) {
            error_log('[SYNC-030] ❌ MCB data preparation failed');
            error_log('╔════════════════════════════════════════════════════════╗');
            error_log('║        SYNC FAILED - DATA PREPARATION ERROR           ║');
            error_log('╚════════════════════════════════════════════════════════╝');
            return array(
                'success' => false,
                'message' => 'Failed to prepare MCB data',
                'error' => 'DATA_PREPARATION_FAILED'
            );
        }
        
        error_log('[SYNC-031] ✅ MCB data prepared successfully');
        error_log('[SYNC-032] MCB Data Keys: ' . implode(', ', array_keys($mcb_data)));
        error_log('[SYNC-033] StudentName: ' . ($mcb_data['StudentName'] ?? 'N/A'));
        error_log('[SYNC-034] FatherEmailID: ' . ($mcb_data['FatherEmailID'] ?? 'N/A'));
        error_log('[SYNC-035] ClassID: ' . ($mcb_data['ClassID'] ?? 'N/A'));
        
        // ═════════════════════════════════════════════════════════════════
        // STEP 4: Call MCB API
        error_log('[SYNC-036] ━━━ STEP 4: Calling MCB API ━━━');
        error_log('[SYNC-037] API URL: ' . ($this->mcb_settings['api_url'] ?? 'NOT SET'));
        error_log('[SYNC-038] Request payload size: ' . strlen(wp_json_encode($mcb_data)) . ' bytes');
        error_log('[SYNC-039] Sending request...');
        
        $api_response = $this->call_mcb_api($mcb_data);
        
        error_log('[SYNC-040] ✅ API call completed');
        
        // ═════════════════════════════════════════════════════════════════
        // STEP 5: Process Response
        error_log('[SYNC-041] ━━━ STEP 5: Processing API Response ━━━');
        
        $sync_result = $this->process_api_response($api_response, $enquiry_id, $mcb_data);
        
        if ($sync_result['success']) {
            error_log('[SYNC-042] ✅✅✅ SYNC SUCCESSFUL ✅✅✅');
            error_log('[SYNC-043] MCB Enquiry ID: ' . $sync_result['mcb_enquiry_id']);
            error_log('[SYNC-044] MCB Query Code: ' . $sync_result['mcb_query_code']);
        } else {
            error_log('[SYNC-045] ❌❌❌ SYNC FAILED ❌❌❌');
            error_log('[SYNC-046] Error: ' . $sync_result['message']);
        }
        
        error_log('╔════════════════════════════════════════════════════════╗');
        error_log('║           MCB SYNC ENQUIRY - END OF LOG               ║');
        error_log('╚════════════════════════════════════════════════════════╝');
        
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
        
        error_log('═════════════════════════════════════════════════════');
        error_log('═══ preview_mcb_data() CALLED (v' . EDUBOT_PRO_VERSION . ') ═══');
        error_log('═════════════════════════════════════════════════════');
        error_log('  Input enquiry_id: ' . $enquiry_id . ' (type: ' . gettype($enquiry_id) . ')');
        
        if (!$this->is_sync_enabled()) {
            error_log('❌ MCB sync is NOT enabled');
            return array(
                'success' => false,
                'message' => 'MCB sync is not enabled',
                'mcb_data' => null
            );
        }
        
        error_log('✅ MCB sync is enabled');
        
        // $enquiry_id is actually the enquiry_number/application_number (passed from button)
        // The button passes enquiry_number which matches application_number
        // First find the application by its application_number
        error_log('✓ Looking for application with application_number = ' . $enquiry_id);
        
        $application = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}edubot_applications WHERE application_number = %s", $enquiry_id),
            ARRAY_A
        );
        
        if (!$application) {
            error_log('❌ Application NOT FOUND for application_number=' . $enquiry_id);
            error_log('  Last query: ' . $wpdb->last_query);
            error_log('  Last error: ' . ($wpdb->last_error ?? 'None'));
            return array(
                'success' => false,
                'message' => 'Application not found for this enquiry number',
                'mcb_data' => null
            );
        }
        
        error_log('✅ Application FOUND: id=' . $application['id']);
        error_log('  Application number: ' . $application['application_number']);
        
        // Now get the enquiry data using the application_number
        error_log('✓ Querying enquiries table for enquiry_number = "' . $application['application_number'] . '"');
        error_log('  Query: SELECT * FROM ' . $wpdb->prefix . 'edubot_enquiries WHERE enquiry_number = "' . $application['application_number'] . '"');
        
        $enquiry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}edubot_enquiries WHERE enquiry_number = %s",
                $application['application_number']
            ),
            ARRAY_A
        );
        
        if (!$enquiry) {
            error_log('❌ Enquiry NOT FOUND for enquiry_number=' . $application['application_number']);
            error_log('  Last query: ' . $wpdb->last_query);
            error_log('  Last error: ' . ($wpdb->last_error ?? 'None'));
            return array(
                'success' => false,
                'message' => 'Enquiry not found for this application',
                'mcb_data' => null
            );
        }
        
        error_log('✅ Enquiry FOUND: id=' . $enquiry['id'] . ', number=' . $enquiry['enquiry_number']);
        
        // Merge application data with enquiry data
        error_log('✓ Merging application data into enquiry data');
        if ($application) {
            $enquiry['utm_data'] = $application['utm_data'];
            $enquiry['gclid'] = $application['gclid'];
            $enquiry['fbclid'] = $application['fbclid'];
            $enquiry['click_id_data'] = $application['click_id_data'];
            error_log('  ✅ Data merged');
        }
        
        // Prepare data for MCB
        error_log('✓ Calling prepare_mcb_data()...');
        $mcb_data = $this->prepare_mcb_data($enquiry);
        error_log('  Result: ' . ($mcb_data ? 'SUCCESS' : 'FAILED'));
        
        if (!$mcb_data) {
            error_log('❌ MCB data preparation failed');
            return array(
                'success' => false,
                'message' => 'Failed to prepare MCB data',
                'mcb_data' => null
            );
        }
        
        // Extract marketing data from enquiry utm_data for display
        $utm_data = !empty($enquiry['utm_data']) ? json_decode($enquiry['utm_data'], true) : array();
        
        error_log('✅ preview_mcb_data() RETURNING SUCCESS');
        error_log('  Enquiry number: ' . $enquiry['enquiry_number']);
        error_log('  MCB Data keys: ' . implode(', ', array_keys($mcb_data ?? array())));
        
        // Return preview without sending
        return array(
            'success' => true,
            'message' => 'MCB data prepared successfully',
            'enquiry_number' => $enquiry['enquiry_number'],
            'enquiry_source_data' => array(
                'student_name' => $enquiry['student_name'] ?? 'Not provided',
                'parent_name' => $enquiry['parent_name'] ?? 'Not provided',
                'email' => $enquiry['email'] ?? 'Not provided',
                'phone' => $enquiry['phone'] ?? 'Not provided',
                'date_of_birth' => $enquiry['date_of_birth'] ?? 'Not provided',
                'grade' => $enquiry['grade'] ?? 'Not provided',
                'board' => $enquiry['board'] ?? 'Not provided',
                'academic_year' => $enquiry['academic_year'] ?? 'Not provided',
                'source' => $enquiry['source'] ?? 'chatbot'
            ),
            'mcb_settings' => array(
                'organization_id' => $this->mcb_settings['organization_id'] ?? 'Not configured',
                'branch_id' => $this->mcb_settings['branch_id'] ?? 'Not configured',
                'sync_enabled' => $this->is_sync_enabled()
            ),
            'mcb_data' => $mcb_data,
            'marketing_data' => array(
                'utm_source' => $utm_data['utm_source'] ?? '',
                'utm_medium' => $utm_data['utm_medium'] ?? '',
                'utm_campaign' => $utm_data['utm_campaign'] ?? '',
                'gclid' => $utm_data['gclid'] ?? '',
                'fbclid' => $utm_data['fbclid'] ?? ''
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
    /**
     * Call MCB API endpoint
     * 
     * @param array $data - Data to send to MCB
     * @return array - API response
     */
    private function call_mcb_api($data) {
        error_log('[API-001] ╔═══════════════════════════════════════════╗');
        error_log('[API-002] ║     CALLING MCB API ENDPOINT            ║');
        error_log('[API-003] ╚═══════════════════════════════════════════╝');
        
        // LOG COMPLETE MCB PAYLOAD BEFORE SENDING
        error_log('[API-PRE-001] ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        error_log('[API-PRE-002] 📦 COMPLETE MCB PAYLOAD (will be sent to API):');
        error_log('[API-PRE-003] ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        error_log('[API-PRE-004] ' . wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        error_log('[API-PRE-005] ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $api_url = $this->mcb_settings['api_url'] ?? '';
        $timeout = intval($this->mcb_settings['timeout'] ?? 65);
        
        error_log('[API-004] URL: ' . $api_url);
        error_log('[API-005] Timeout: ' . $timeout . ' seconds');
        error_log('[API-006] Test Mode: ' . ($this->mcb_settings['test_mode'] ? 'ENABLED' : 'DISABLED'));
        
        // If test mode enabled, skip actual API call
        if (!empty($this->mcb_settings['test_mode'])) {
            error_log('[API-007] ⚠️  TEST MODE - Skipping actual API call');
            error_log('[API-008] Request payload would be:');
            error_log('[API-009] ' . wp_json_encode($data, JSON_PRETTY_PRINT));
            return array(
                'test_mode' => true,
                'body' => json_encode(array('Result' => 'Success', 'Message' => 'Test Mode - No API Call'))
            );
        }
        
        error_log('[API-010] Preparing request...');
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode($data),
            'timeout' => $timeout,
            'sslverify' => true
        );
        
        error_log('[API-011] Headers: ' . wp_json_encode($args['headers']));
        error_log('[API-012] Body size: ' . strlen($args['body']) . ' bytes');
        error_log('[API-013] Executing wp_remote_post()...');
        
        // Execute API call with error handling
        $response = wp_remote_post($api_url, $args);
        
        error_log('[API-014] ✅ wp_remote_post() returned');
        
        // Log response details
        if (is_wp_error($response)) {
            error_log('[API-015] ❌ WP_ERROR detected');
            error_log('[API-016] Error Code: ' . $response->get_error_code());
            error_log('[API-017] Error Message: ' . $response->get_error_message());
            $error_data = $response->get_error_data();
            if (!empty($error_data)) {
                error_log('[API-018] Error Data: ' . wp_json_encode($error_data));
            }
        } else {
            $http_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $headers = wp_remote_retrieve_headers($response);
            
            error_log('[API-019] HTTP Status Code: ' . $http_code);
            error_log('[API-020] Response Headers: ' . wp_json_encode($headers->getAll()));
            error_log('[API-021] Response Body Size: ' . strlen($response_body) . ' bytes');
            error_log('[API-022] Response Body (first 500 chars): ' . substr($response_body, 0, 500));
        }
        
        error_log('[API-023] ╔═══════════════════════════════════════════╗');
        error_log('[API-024] ║        MCB API CALL - END               ║');
        error_log('[API-025] ╚═══════════════════════════════════════════╝');
        
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
        
        error_log('[RESP-001] ╔═══════════════════════════════════════════╗');
        error_log('[RESP-002] ║   PROCESSING MCB API RESPONSE           ║');
        error_log('[RESP-003] ╚═══════════════════════════════════════════╝');
        
        $success = false;
        $error_message = null;
        $response_data = null;
        $mcb_enquiry_id = null;
        $mcb_query_code = null;
        
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // Check for HTTP errors / Network issues
        error_log('[RESP-004] Checking for WP_Error...');
        if (is_wp_error($api_response)) {
            error_log('[RESP-005] ❌ WP_Error found - Network or system level error');
            $error_code = $api_response->get_error_code();
            $error_msg = $api_response->get_error_message();
            $error_data = $api_response->get_error_data();
            
            error_log('[RESP-006] WP_Error Code: ' . $error_code);
            error_log('[RESP-007] WP_Error Message: ' . $error_msg);
            if (!empty($error_data)) {
                error_log('[RESP-008] WP_Error Data: ' . wp_json_encode($error_data));
            }
            
            $error_message = 'Network Error: ' . $error_msg . ' (' . $error_code . ')';
            $sync_status = 'failed';
        } else {
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // Check HTTP Status Code
            error_log('[RESP-009] ✅ Valid response object received');
            
            $http_code = wp_remote_retrieve_response_code($api_response);
            $response_body = wp_remote_retrieve_body($api_response);
            
            error_log('[RESP-010] HTTP Status Code: ' . $http_code);
            error_log('[RESP-011] Response Body Length: ' . strlen($response_body) . ' bytes');
            error_log('[RESP-012] Response Body: ' . $response_body);
            
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // Parse JSON Response
            if ($http_code === 200) {
                error_log('[RESP-013] ✅ HTTP 200 OK');
                
                $response_data = json_decode($response_body, true);
                $json_error = json_last_error();
                
                if ($json_error !== JSON_ERROR_NONE) {
                    error_log('[RESP-014] ❌ JSON Decode Error: ' . json_last_error_msg());
                    error_log('[RESP-015] Response was not valid JSON');
                    $error_message = 'Invalid JSON Response: ' . json_last_error_msg();
                    $sync_status = 'failed';
                } else {
                    error_log('[RESP-016] ✅ JSON decoded successfully');
                    error_log('[RESP-017] Response Data Keys: ' . implode(', ', array_keys($response_data)));
                    
                    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                    // Check MCB Result Field
                    error_log('[RESP-018] Checking MCB Result field...');
                    error_log('[RESP-019] Result: ' . ($response_data['Result'] ?? 'NOT SET'));
                    error_log('[RESP-020] Message: ' . ($response_data['Message'] ?? 'NOT SET'));
                    error_log('[RESP-021] QueryCode: ' . ($response_data['QueryCode'] ?? 'NOT SET'));
                    error_log('[RESP-022] EnquiryID: ' . ($response_data['EnquiryID'] ?? 'NOT SET'));
                    
                    if (!empty($response_data['Result']) && $response_data['Result'] === 'Success') {
                        error_log('[RESP-023] ✅✅ MCB RESULT = SUCCESS ✅✅');
                        $success = true;
                        $sync_status = 'synced';
                        $mcb_enquiry_id = $response_data['QueryCode'] ?? $response_data['EnquiryID'] ?? null;
                        $mcb_query_code = $response_data['QueryCode'] ?? null;
                        error_log('[RESP-024] MCB Enquiry ID/QueryCode: ' . $mcb_enquiry_id);
                    } else {
                        error_log('[RESP-025] ❌ MCB Result was not Success');
                        error_log('[RESP-026] MCB Result Value: ' . ($response_data['Result'] ?? 'NULL'));
                        
                        // Capture exact MCB error message
                        $error_message = $response_data['Message'] ?? $response_data['message'] ?? 'Unknown error from MCB API';
                        error_log('[RESP-027] ❌❌ MCB ERROR MESSAGE: ' . $error_message . ' ❌❌');
                        
                        // Log additional error details if present
                        if (!empty($response_data['ErrorDetails'])) {
                            error_log('[RESP-028] MCB ErrorDetails: ' . wp_json_encode($response_data['ErrorDetails']));
                        }
                        if (!empty($response_data['ErrorCode'])) {
                            error_log('[RESP-029] MCB ErrorCode: ' . $response_data['ErrorCode']);
                        }
                        if (!empty($response_data['Details'])) {
                            error_log('[RESP-030] MCB Details: ' . wp_json_encode($response_data['Details']));
                        }
                        
                        // CRITICAL: Set status to FAILED when MCB returns error
                        $sync_status = 'failed';
                        error_log('[RESP-031-CRITICAL] ⚠️  Status set to: FAILED (because MCB returned error)');
                    }
                }
            } else {
                // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                // Non-200 HTTP Status
                error_log('[RESP-031] ❌ HTTP Error: ' . $http_code);
                error_log('[RESP-032] Response Body: ' . $response_body);
                
                $error_message = "HTTP Error $http_code: " . substr($response_body, 0, 200);
                $sync_status = 'failed';
            }
        }
        
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // Log to Database
        error_log('[RESP-033] Logging sync attempt to database...');
        
        $insert_result = $wpdb->insert(
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
        
        if (!$insert_result) {
            error_log('[RESP-034] ❌ Database insert failed: ' . $wpdb->last_error);
        } else {
            error_log('[RESP-035] ✅ Logged to wp_edubot_mcb_sync_log (ID: ' . $wpdb->insert_id . ')');
        }
        
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // Update Enquiry Status
        error_log('[RESP-036] Updating enquiry MCB status...');
        
        $update_result = $wpdb->update(
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
        
        if ($update_result === false) {
            error_log('[RESP-037] ❌ Database update failed: ' . $wpdb->last_error);
        } else {
            error_log('[RESP-038] ✅ Updated enquiry status to: ' . $sync_status);
        }
        
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // Final Result
        error_log('[RESP-039] ╔═══════════════════════════════════════════╗');
        if ($success) {
            error_log('[RESP-040] ║    ✅✅ SYNC SUCCESSFUL ✅✅          ║');
        } else {
            error_log('[RESP-041] ║    ❌❌ SYNC FAILED ❌❌              ║');
        }
        error_log('[RESP-042] ╚═══════════════════════════════════════════╝');
        
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
