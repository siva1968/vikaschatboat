<?php
/**
 * WhatsApp Session Manager
 * 
 * Manages WhatsApp conversation sessions for ad-driven users
 * Handles session creation, updates, and state management
 * 
 * @package EduBot_Pro
 * @subpackage Integrations
 * @version 1.0.0
 */

class EduBot_WhatsApp_Session_Manager {
    
    /**
     * Create new WhatsApp session with ad attribution
     * 
     * @param string $phone User's phone number
     * @param array $ad_params Ad attribution parameters
     * @return string Session ID
     */
    public static function create_session( $phone, $ad_params = array() ) {
        $instance = new self();
        return $instance->_create_session( $phone, $ad_params );
    }
    
    /**
     * Internal session creation
     */
    private function _create_session( $phone, $ad_params ) {
        global $wpdb;
        
        $session_id = $this->generate_session_id();
        $formatted_phone = $this->format_phone( $phone );
        
        // Get or create contact
        $contact_id = $this->get_or_create_contact( $formatted_phone );
        
        // Determine campaign ID
        $campaign_id = $this->get_campaign_id_from_params( $ad_params );
        
        // Store session
        $sessions_table = $wpdb->prefix . 'edubot_whatsapp_sessions';
        
        $wpdb->insert(
            $sessions_table,
            array(
                'session_id' => $session_id,
                'contact_id' => $contact_id,
                'phone' => $formatted_phone,
                'campaign_id' => $campaign_id,
                'source' => sanitize_text_field( $ad_params['source'] ?? 'direct' ),
                'campaign' => sanitize_text_field( $ad_params['campaign'] ?? '' ),
                'medium' => sanitize_text_field( $ad_params['medium'] ?? 'whatsapp' ),
                'utm_source' => sanitize_text_field( $ad_params['utm_source'] ?? '' ),
                'utm_medium' => sanitize_text_field( $ad_params['utm_medium'] ?? '' ),
                'utm_campaign' => sanitize_text_field( $ad_params['utm_campaign'] ?? '' ),
                'state' => 'greeting',
                'ip_address' => $this->get_client_ip(),
                'user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
                'started_at' => current_time( 'mysql' )
            ),
            array( '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );
        
        error_log( "EduBot: Created WhatsApp session {$session_id} for phone {$phone}" );
        
        return $session_id;
    }
    
    /**
     * Get or create contact in database
     */
    private function get_or_create_contact( $phone ) {
        global $wpdb;
        
        $contacts_table = $wpdb->prefix . 'edubot_contacts';
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$contacts_table'" ) != $contacts_table ) {
            error_log( 'EduBot: Contacts table does not exist' );
            return 0;
        }
        
        // Try to find existing contact
        $contact = $wpdb->get_row( $wpdb->prepare(
            "SELECT id FROM $contacts_table WHERE phone = %s",
            $phone
        ) );
        
        if ( $contact ) {
            // Update last contact time
            $wpdb->update(
                $contacts_table,
                array( 'last_contacted_at' => current_time( 'mysql' ) ),
                array( 'id' => $contact->id ),
                array( '%s' ),
                array( '%d' )
            );
            return $contact->id;
        }
        
        // Create new contact
        $wpdb->insert(
            $contacts_table,
            array(
                'phone' => $phone,
                'source' => 'whatsapp',
                'created_at' => current_time( 'mysql' )
            ),
            array( '%s', '%s', '%s' )
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get session by ID
     */
    public static function get_session( $session_id ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_whatsapp_sessions';
        
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE session_id = %s",
            $session_id
        ), ARRAY_A );
    }
    
    /**
     * Get session by phone number
     */
    public static function get_session_by_phone( $phone ) {
        global $wpdb;
        
        $formatted_phone = ( new self() )->format_phone( $phone );
        $table = $wpdb->prefix . 'edubot_whatsapp_sessions';
        
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE phone = %s AND completed_at IS NULL ORDER BY started_at DESC LIMIT 1",
            $formatted_phone
        ), ARRAY_A );
    }
    
    /**
     * Update session state
     */
    public static function update_session_state( $session_id, $state, $data = array() ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_whatsapp_sessions';
        
        $update_data = array(
            'state' => sanitize_text_field( $state ),
            'data' => json_encode( $data ),
            'updated_at' => current_time( 'mysql' )
        );
        
        $wpdb->update(
            $table,
            $update_data,
            array( 'session_id' => $session_id ),
            array( '%s', '%s', '%s' ),
            array( '%s' )
        );
        
        error_log( "EduBot: Updated session {$session_id} to state: {$state}" );
    }
    
    /**
     * Mark session as completed
     */
    public static function complete_session( $session_id, $application_data = array() ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_whatsapp_sessions';
        
        $wpdb->update(
            $table,
            array(
                'state' => 'completed',
                'data' => json_encode( $application_data ),
                'completed_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' )
            ),
            array( 'session_id' => $session_id ),
            array( '%s', '%s', '%s', '%s' ),
            array( '%s' )
        );
        
        error_log( "EduBot: Completed WhatsApp session {$session_id}" );
    }
    
    /**
     * Get session data
     */
    public static function get_session_data( $session_id ) {
        $session = self::get_session( $session_id );
        
        if ( !$session ) {
            return array();
        }
        
        return json_decode( $session['data'], true ) ?? array();
    }
    
    /**
     * Update session data (merge with existing)
     */
    public static function update_session_data( $session_id, $new_data = array() ) {
        global $wpdb;
        
        $existing_data = self::get_session_data( $session_id );
        $merged_data = wp_parse_args( $new_data, $existing_data );
        
        $table = $wpdb->prefix . 'edubot_whatsapp_sessions';
        
        $wpdb->update(
            $table,
            array(
                'data' => json_encode( $merged_data ),
                'updated_at' => current_time( 'mysql' )
            ),
            array( 'session_id' => $session_id ),
            array( '%s', '%s' ),
            array( '%s' )
        );
    }
    
    /**
     * Get session messages
     */
    public static function get_session_messages( $session_id ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_whatsapp_messages';
        
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) != $table ) {
            return array();
        }
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table WHERE session_id = %s ORDER BY created_at ASC",
            $session_id
        ), ARRAY_A );
    }
    
    /**
     * Store message in session
     */
    public static function store_message( $session_id, $sender, $message, $message_id = '' ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_whatsapp_messages';
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) != $table ) {
            error_log( 'EduBot: Messages table does not exist' );
            return false;
        }
        
        $wpdb->insert(
            $table,
            array(
                'session_id' => $session_id,
                'sender' => sanitize_text_field( $sender ),
                'message' => sanitize_textarea_field( $message ),
                'message_id' => sanitize_text_field( $message_id ),
                'created_at' => current_time( 'mysql' )
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get active sessions (not completed)
     */
    public static function get_active_sessions( $limit = 100 ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_whatsapp_sessions';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE completed_at IS NULL ORDER BY updated_at DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get campaign performance
     */
    public static function get_campaign_performance( $campaign_id = 0 ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_whatsapp_sessions';
        
        $where = $campaign_id > 0 ? $wpdb->prepare( "WHERE campaign_id = %d", $campaign_id ) : '';
        
        return $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_sessions,
                SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN completed_at IS NULL THEN 1 ELSE 0 END) as active,
                AVG(TIMESTAMPDIFF(MINUTE, started_at, COALESCE(completed_at, NOW()))) as avg_duration_mins
            FROM $table $where"
        );
    }
    
    /**
     * Format phone number
     */
    private function format_phone( $phone ) {
        $cleaned = preg_replace( '/\D/', '', $phone );
        
        if ( strlen( $cleaned ) === 10 ) {
            $cleaned = '91' . $cleaned;
        }
        
        return $cleaned;
    }
    
    /**
     * Generate unique session ID
     */
    private function generate_session_id() {
        return 'whatsapp_' . wp_generate_password( 16, false );
    }
    
    /**
     * Get campaign ID from parameters
     */
    private function get_campaign_id_from_params( $params ) {
        global $wpdb;
        
        if ( empty( $params['campaign'] ) ) {
            return 0;
        }
        
        $table = $wpdb->prefix . 'edubot_ad_campaigns';
        
        // Try to find campaign by name
        $campaign = $wpdb->get_row( $wpdb->prepare(
            "SELECT id FROM $table WHERE name = %s",
            sanitize_text_field( $params['campaign'] )
        ) );
        
        return $campaign ? $campaign->id : 0;
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        if ( !empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            return sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
        } elseif ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ips = explode( ',', sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
            return trim( $ips[0] );
        } else {
            return sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
        }
    }
}
