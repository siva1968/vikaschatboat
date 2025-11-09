<?php
/**
 * MyClassBoard Integration Class
 * 
 * Handles synchronization between EduBot Pro enquiries and MyClassBoard CRM
 * Converts EduBot enquiry data to MyClassBoard API format and syncs bidirectionally
 * 
 * @package EduBot_Pro
 * @subpackage Integrations
 * @version 1.0.0
 */

class EduBot_MyClassBoard_Integration {

    /**
     * Settings key for MCB configuration
     */
    const SETTINGS_KEY = 'edubot_mcb_settings';

    /**
     * API endpoint
     */
    const MCB_API_ENDPOINT = 'https://corp.myclassboard.com/api/EnquiryService/SaveEnquiryDetails';

    /**
     * Sync log table
     */
    private $sync_log_table;

    /**
     * Default organization ID
     */
    private $default_org_id = '21';

    /**
     * Default branch ID
     */
    private $default_branch_id = '113';

    /**
     * Initialize integration
     */
    public function __construct() {
        global $wpdb;
        $this->sync_log_table = $wpdb->prefix . 'edubot_mcb_sync_log';
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Sync enquiry to MCB when created
        add_action( 'edubot_enquiry_created', array( $this, 'sync_enquiry_to_mcb' ), 10, 2 );
        
        // AJAX endpoint for manual sync
        add_action( 'wp_ajax_edubot_mcb_sync_enquiry', array( $this, 'ajax_sync_enquiry' ) );
        add_action( 'wp_ajax_nopriv_edubot_mcb_sync_enquiry', array( $this, 'ajax_sync_enquiry' ) );
    }

    /**
     * Get MCB settings from database
     * 
     * @return array MCB configuration
     */
    public function get_settings() {
        global $wpdb;

        $settings = get_option( self::SETTINGS_KEY );

        if ( ! $settings ) {
            // Try from database table if exists
            $db_settings = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}edubot_mcb_settings WHERE site_id = %d LIMIT 1",
                    get_current_blog_id()
                ),
                ARRAY_A
            );

            if ( $db_settings ) {
                $settings = json_decode( $db_settings['config_data'], true );
            }
        }

        return wp_parse_args(
            $settings,
            $this->get_default_settings()
        );
    }

    /**
     * Get default MCB settings
     * 
     * @return array Default settings
     */
    public function get_default_settings() {
        return array(
            'enabled'               => false,
            'api_key'               => '',
            'access_token'          => '',
            'api_url'               => self::MCB_API_ENDPOINT,
            'organization_id'       => $this->default_org_id,
            'branch_id'             => $this->default_branch_id,
            'sync_enabled'          => false,
            'sync_new_enquiries'    => true,
            'sync_updates'          => false,
            'auto_sync'             => true,
            'test_mode'             => false,
            'timeout'               => 65,
            'retry_attempts'        => 3,
            'lead_source_mapping'   => $this->get_default_lead_source_mapping(),
        );
    }

    /**
     * Get default lead source mapping - COMPLETE MCB SOURCES
     * 
     * All 29 lead source mappings including:
     * - 7 Digital/Chatbot sources
     * - 5 Social Media sources
     * - 4 Referral sources
     * - 2 Event sources
     * - 3 Traditional sources
     * - 1 Content source
     * - 2 Other sources
     * 
     * @return array Lead source mapping with MCB IDs
     */
    private function get_default_lead_source_mapping() {
        return array(
            // ====== DIGITAL/CHATBOT (7) ======
            'chatbot'              => '273',  // Chat Bot
            'whatsapp'             => '273',  // WhatsApp (Chat Bot)
            'website'              => '231',  // Website
            'email'                => '286',  // Email
            
            // ====== SEARCH & DISPLAY (3) ======
            'google_search'        => '269',  // Google Search
            'google_display'       => '270',  // Google Display
            'google_call_ads'      => '275',  // Google Call Ads
            
            // ====== SOCIAL MEDIA (5) ======
            'facebook'             => '272',  // Facebook
            'facebook_lead'        => '271',  // Facebook Lead
            'instagram'            => '268',  // Instagram
            'linkedin'             => '267',  // LinkedIn
            'youtube'              => '446',  // YouTube
            
            // ====== REFERRAL (4) ======
            'referral'             => '92',   // Friends/Referral
            'friends'              => '92',   // Friends (Alias)
            'existing_parent'      => '232',  // Existing Parent
            'word_of_mouth'        => '448',  // Word of Mouth
            
            // ====== EVENTS & WALK-IN (2) ======
            'events'               => '234',  // Events
            'walkin'               => '250',  // Walk In
            
            // ====== CONTENT (2) ======
            'ebook'                => '274',  // E-book
            'newsletter'           => '447',  // News Letter
            
            // ====== TRADITIONAL (3) ======
            'newspaper'            => '84',   // News Paper
            'hoardings'            => '85',   // Hoardings
            'leaflets'             => '86',   // Leaflets
            
            // ====== OTHER (2) ======
            'organic'              => '280',  // Organic
            'others'               => '233',  // Others
            
            // ====== FALLBACK ======
            'unknown'              => '280',  // Default to Organic
            'default'              => '280',  // Default to Organic
        );
    }

    /**
     * Update MCB settings
     * 
     * Preserves all existing data while updating new values
     * 
     * @param array $settings New settings to update
     * @return bool Success status
     */
    public function update_settings( $settings ) {
        // Get current settings from database
        $current = get_option( self::SETTINGS_KEY );
        if ( ! is_array( $current ) ) {
            $current = $this->get_default_settings();
        }

        // CRITICAL: Merge new settings with existing settings
        // This ensures fields not in new settings are preserved
        $merged = array_merge( $current, $settings );

        // Sanitize all settings
        $sanitized = array(
            'enabled'               => (bool) ( $merged['enabled'] ?? false ),
            'api_key'               => sanitize_text_field( $merged['api_key'] ?? '' ),
            'access_token'          => sanitize_text_field( $merged['access_token'] ?? '' ),
            'api_url'               => esc_url_raw( $merged['api_url'] ?? self::MCB_API_ENDPOINT ),
            'organization_id'       => sanitize_text_field( $merged['organization_id'] ?? '21' ),
            'branch_id'             => sanitize_text_field( $merged['branch_id'] ?? '113' ),
            'sync_enabled'          => (bool) ( $merged['sync_enabled'] ?? false ),
            'sync_new_enquiries'    => (bool) ( $merged['sync_new_enquiries'] ?? true ),
            'sync_updates'          => (bool) ( $merged['sync_updates'] ?? false ),
            'auto_sync'             => (bool) ( $merged['auto_sync'] ?? true ),
            'test_mode'             => (bool) ( $merged['test_mode'] ?? false ),
            'timeout'               => intval( $merged['timeout'] ?? 65 ),
            'retry_attempts'        => intval( $merged['retry_attempts'] ?? 3 ),
            'lead_source_mapping'   => $this->sanitize_lead_source_mapping( 
                $merged['lead_source_mapping'] ?? array() 
            ),
        );

        return update_option( self::SETTINGS_KEY, $sanitized );
    }

    /**
     * Sanitize lead source mapping array
     * 
     * Ensures all values are strings and not empty
     * 
     * @param array $mapping Lead source mapping
     * @return array Sanitized mapping
     */
    private function sanitize_lead_source_mapping( $mapping ) {
        if ( ! is_array( $mapping ) ) {
            return $this->get_default_lead_source_mapping();
        }

        $sanitized = array();
        
        foreach ( $mapping as $key => $value ) {
            $key = sanitize_key( $key );
            $value = sanitize_text_field( $value );
            
            // Only include non-empty values
            if ( ! empty( $key ) && ! empty( $value ) ) {
                $sanitized[ $key ] = $value;
            }
        }

        // If result is empty, use defaults
        if ( empty( $sanitized ) ) {
            return $this->get_default_lead_source_mapping();
        }

        return $sanitized;
    }

    /**
     * Map EduBot enquiry to MyClassBoard format
     * 
     * @param array $enquiry EduBot enquiry data
     * @return array MCB formatted data
     */
    public function map_enquiry_to_mcb( $enquiry ) {
        $settings = $this->get_settings();

        // Get lead source mapping
        $lead_source_map = $settings['lead_source_mapping'];
        $source = isset( $enquiry['source'] ) ? $enquiry['source'] : 'chatbot';
        $lead_source_id = isset( $lead_source_map[ $source ] ) 
            ? $lead_source_map[ $source ] 
            : $lead_source_map['organic'];

        // Map enquiry data to MCB format
        $mcb_data = array(
            'OrganisationID'        => $settings['organization_id'],
            'BranchID'              => $settings['branch_id'],
            'AcademicYearID'        => $this->get_academic_year_id( $enquiry['academic_year'] ?? '2026-27' ),
            'StudentName'           => $enquiry['student_name'] ?? 'Not Provided',
            'FatherName'            => $enquiry['parent_name'] ?? '',
            'FatherMobile'          => $enquiry['phone'] ?? '',
            'FatherEmailID'         => $enquiry['email'] ?? '',
            'MotherName'            => $enquiry['mother_name'] ?? '',
            'MotherMobile'          => $enquiry['mother_phone'] ?? '',
            'DOB'                   => $enquiry['date_of_birth'] ?? '01-06-2025',
            'Address1'              => $enquiry['address'] ?? '',
            'QueryContactSourceID'  => $lead_source_id,
            'Class'                 => $this->map_grade_to_class( $enquiry['grade'] ?? '' ),
            'ClassID'               => $this->map_grade_to_class_id( $enquiry['grade'] ?? '' ),
            'LeadSource'            => $lead_source_id,
            'Remarks'               => isset( $enquiry['enquiry_number'] ) ? 'ENQ:' . $enquiry['enquiry_number'] : 'EduBot Sync',
        );

        return $mcb_data;
    }

    /**
     * Get academic year ID from year string
     * 
     * @param string $year Academic year
     * @return int Academic year ID
     */
    private function get_academic_year_id( $year ) {
        $year_mapping = array(
           
            '2024-25' => 15,
            '2025-26' => 16,
            '2026-27' => 17,
            '2027-28' => 18,
            '2028-29' => 19,
            '2029-30' => 20,
            '2030-31' => 21,
            '2031-32' => 22,
        );

        return isset( $year_mapping[ $year ] ) ? $year_mapping[ $year ] : 17;
    }

    /**
     * Map grade to MCB class name
     * 
     * @param string $grade Grade
     * @return string Class name
     */
    private function map_grade_to_class( $grade ) {
        $grade_map = array(
            'Pre Nursery'  => 'Pre Nursery',
            'Nursery'      => 'Nursery',
            'PP1'          => 'PP1',
            'PP2'          => 'PP2',
            'Grade 1'      => 'Grade 1',
            'Grade 2'      => 'Grade 2',
            'Grade 3'      => 'Grade 3',
            'Grade 4'      => 'Grade 4',
            'Grade 5'      => 'Grade 5',
            'Grade 6'      => 'Grade 6',
            'Grade 7'      => 'Grade 7',
            'Grade 8'      => 'Grade 8',
            'Grade 9'      => 'Grade 9',
            'Grade 10'     => 'Grade 10',
        );

        return isset( $grade_map[ $grade ] ) ? $grade_map[ $grade ] : $grade;
    }

    /**
     * Map grade to MCB class ID
     * 
     * @param string $grade Grade
     * @return int Class ID
     */
    private function map_grade_to_class_id( $grade ) {
        $grade_map = array(
            'Pre Nursery'  => 787,
            'Nursery'      => 273,
            'PP1'          => 274,
            'PP2'          => 275,
            'Grade 1'      => 276,
            'Grade 2'      => 277,
            'Grade 3'      => 278,
            'Grade 4'      => 279,
            'Grade 5'      => 280,
            'Grade 6'      => 281,
            'Grade 7'      => 282,
            'Grade 8'      => 283,
            'Grade 9'      => 315,
            'Grade 10'     => 631,
            'Grade 11'     => 910,
            'Grade 12'     => 914,
        );

        return isset( $grade_map[ $grade ] ) ? $grade_map[ $grade ] : 280;
    }

    /**
     * Sync enquiry to MyClassBoard
     * 
     * @param int   $enquiry_id Enquiry ID
     * @param array $enquiry    Enquiry data
     * @return array Sync result
     */
    public function sync_enquiry_to_mcb( $enquiry_id, $enquiry ) {
        global $wpdb;

        $settings = $this->get_settings();

        // Check if MCB integration is enabled
        if ( ! $settings['enabled'] || ! $settings['sync_enabled'] ) {
            return array(
                'success'   => false,
                'message'   => 'MCB integration not enabled',
                'enquiry_id'=> $enquiry_id,
            );
        }

        // Map enquiry to MCB format
        $mcb_data = $this->map_enquiry_to_mcb( $enquiry );

        // Make API call
        $response = $this->send_to_mcb( $mcb_data, $settings );

        // Log sync attempt
        $this->log_sync( $enquiry_id, $mcb_data, $response, $settings );

        // Update enquiry with MCB data if successful
        if ( isset( $response['success'] ) && $response['success'] ) {
            $mcb_enquiry_id = isset( $response['enquiry_id'] ) ? $response['enquiry_id'] : '';
            $mcb_query_code = isset( $response['query_code'] ) ? $response['query_code'] : '';

            $wpdb->update(
                $wpdb->prefix . 'edubot_enquiries',
                array(
                    'mcb_sync_status' => 'synced',
                    'mcb_enquiry_id'  => $mcb_enquiry_id,
                    'mcb_query_code'  => $mcb_query_code,
                ),
                array( 'id' => $enquiry_id ),
                array( '%s', '%s', '%s' ),
                array( '%d' )
            );
        }

        return $response;
    }

    /**
     * Send data to MyClassBoard API
     * 
     * @param array $data     MCB formatted data
     * @param array $settings MCB settings
     * @return array API response
     */
    public function send_to_mcb( $data, $settings ) {
        $attempts = 0;
        $max_attempts = $settings['retry_attempts'];
        
        // Use custom API URL if set, otherwise use default
        $api_url = ! empty( $settings['api_url'] ) ? $settings['api_url'] : self::MCB_API_ENDPOINT;

        while ( $attempts < $max_attempts ) {
            $attempts++;

            $response = wp_remote_post(
                $api_url,
                array(
                    'timeout'    => $settings['timeout'],
                    'headers'    => array(
                        'Content-Type' => 'application/json; charset=utf-8',
                    ),
                    'body'       => wp_json_encode( $data ),
                    'sslverify'  => true,
                )
            );

            // Check for request error
            if ( is_wp_error( $response ) ) {
                error_log( 'EduBot MCB: Request error - ' . $response->get_error_message() );
                
                if ( $attempts < $max_attempts ) {
                    sleep( 2 ); // Wait before retry
                    continue;
                }

                return array(
                    'success'   => false,
                    'error'     => $response->get_error_message(),
                    'message'   => 'MCB API request failed',
                );
            }

            // Parse response
            $body = wp_remote_retrieve_body( $response );
            $status_code = wp_remote_retrieve_response_code( $response );

            if ( $status_code < 200 || $status_code >= 300 ) {
                error_log( 'EduBot MCB: API error - Status ' . $status_code );
                
                if ( $attempts < $max_attempts ) {
                    sleep( 2 );
                    continue;
                }

                return array(
                    'success'   => false,
                    'error'     => 'HTTP ' . $status_code,
                    'message'   => 'MCB API returned error',
                );
            }

            // Parse JSON response
            $response_data = json_decode( $body, true );

            // Check for success indicators
            if ( isset( $response_data['Status'] ) && strtolower( $response_data['Status'] ) === 'success' ) {
                return array(
                    'success'   => true,
                    'enquiry_id'=> $response_data['EnquiryID'] ?? '',
                    'query_code'=> $response_data['QueryCode'] ?? '',
                    'message'   => 'Synced to MCB successfully',
                );
            }

            // Check for duplicate
            if ( strpos( $body, 'already Exists' ) !== false ) {
                return array(
                    'success'   => true,
                    'message'   => 'MCB: Enquiry already exists (duplicate)',
                    'status'    => 'duplicate',
                );
            }

            // Success response found
            if ( strpos( $body, 'Thank You' ) !== false ) {
                preg_match( '/EnquiryCode is (.+?)\./', $body, $matches );
                $query_code = isset( $matches[1] ) ? trim( $matches[1] ) : '';

                return array(
                    'success'   => true,
                    'query_code'=> $query_code,
                    'message'   => 'Synced to MCB successfully',
                );
            }

            if ( $attempts < $max_attempts ) {
                sleep( 2 );
                continue;
            }

            return array(
                'success'   => false,
                'message'   => 'MCB API: Unknown response format',
                'raw'       => substr( $body, 0, 200 ),
            );
        }

        return array(
            'success'   => false,
            'message'   => 'Max retry attempts reached',
        );
    }

    /**
     * Log sync attempt
     * 
     * @param int   $enquiry_id Enquiry ID
     * @param array $request    Request data
     * @param array $response   Response data
     * @param array $settings   MCB settings
     */
    private function log_sync( $enquiry_id, $request, $response, $settings ) {
        global $wpdb;

        // Create table if needed
        $this->ensure_sync_log_table();

        $success = isset( $response['success'] ) ? (bool) $response['success'] : false;

        $wpdb->insert(
            $this->sync_log_table,
            array(
                'enquiry_id'    => $enquiry_id,
                'request_data'  => wp_json_encode( $request ),
                'response_data' => wp_json_encode( $response ),
                'success'       => $success ? 1 : 0,
                'error_message' => isset( $response['error'] ) ? $response['error'] : '',
                'created_at'    => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%d', '%s', '%s' )
        );
    }

    /**
     * Create sync log table if not exists
     */
    public function ensure_sync_log_table() {
        global $wpdb;

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$this->sync_log_table}'" ) === $this->sync_log_table ) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->sync_log_table} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            enquiry_id BIGINT(20) NOT NULL,
            request_data LONGTEXT,
            response_data LONGTEXT,
            success TINYINT(1) DEFAULT 0,
            error_message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_enquiry (enquiry_id),
            KEY idx_success (success),
            KEY idx_created (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * AJAX: Manually sync enquiry
     */
    public function ajax_sync_enquiry() {
        check_ajax_referer( 'edubot_mcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $enquiry_id = intval( $_POST['enquiry_id'] ?? 0 );

        if ( ! $enquiry_id ) {
            wp_send_json_error( 'Invalid enquiry ID' );
        }

        // Get enquiry data
        global $wpdb;
        $enquiry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}edubot_enquiries WHERE id = %d",
                $enquiry_id
            ),
            ARRAY_A
        );

        if ( ! $enquiry ) {
            wp_send_json_error( 'Enquiry not found' );
        }

        // Sync to MCB
        $result = $this->sync_enquiry_to_mcb( $enquiry_id, $enquiry );

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * Get sync status for enquiry
     * 
     * @param int $enquiry_id Enquiry ID
     * @return array Sync status
     */
    public function get_sync_status( $enquiry_id ) {
        global $wpdb;

        $status = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT success, error_message, created_at FROM {$this->sync_log_table} 
                 WHERE enquiry_id = %d ORDER BY created_at DESC LIMIT 1",
                $enquiry_id
            ),
            ARRAY_A
        );

        return $status ?? array(
            'success'       => null,
            'error_message' => 'No sync attempt',
            'created_at'    => null,
        );
    }

    /**
     * Get sync statistics
     * 
     * @return array Statistics
     */
    public function get_sync_stats() {
        global $wpdb;

        $this->ensure_sync_log_table();

        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->sync_log_table}" );
        $successful = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->sync_log_table} WHERE success = 1" );
        $failed = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->sync_log_table} WHERE success = 0" );
        $today = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->sync_log_table} 
             WHERE DATE(created_at) = CURDATE()"
        );

        return array(
            'total'      => (int) $total,
            'successful' => (int) $successful,
            'failed'     => (int) $failed,
            'today'      => (int) $today,
            'success_rate' => $total > 0 ? round( ( (int) $successful / (int) $total ) * 100, 2 ) : 0,
        );
    }

    /**
     * Get recent sync logs
     * 
     * @param int $limit Number of logs to retrieve
     * @return array Sync logs
     */
    public function get_recent_sync_logs( $limit = 20 ) {
        global $wpdb;

        $this->ensure_sync_log_table();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.id, e.enquiry_number, e.student_name, e.email,
                        l.success, l.error_message, l.created_at
                 FROM {$this->sync_log_table} l
                 JOIN {$wpdb->prefix}edubot_enquiries e ON l.enquiry_id = e.id
                 ORDER BY l.created_at DESC
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }
}
