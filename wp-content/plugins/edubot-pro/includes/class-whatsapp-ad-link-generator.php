<?php
/**
 * WhatsApp Ad Link Generator with Multi-Platform Support
 * 
 * Generates WhatsApp click-to-chat links for ad campaigns with unique tracking codes
 * Supports simultaneous campaigns to same phone from different platforms
 * 
 * @package EduBot_Pro
 * @subpackage Integrations
 * @version 1.7.0 - Multi-platform tracking codes
 */

class EduBot_WhatsApp_Ad_Link_Generator {
    
    /**
     * Generate WhatsApp click-to-chat link for ad campaigns
     * Now supports multi-platform with unique tracking codes
     * 
     * @param array $params {
     *     @type int    $campaign_id    - Campaign database ID (required for multi-platform)
     *     @type string $phone          - WhatsApp Business phone number (required)
     *     @type string $platform       - Ad platform (facebook|instagram|google|tiktok|linkedin|twitter|other)
     *     @type string $message        - Message text to send
     *     @type string $campaign_name  - Campaign name (optional)
     *     @type string $source         - Legacy: Ad source (facebook_ads, instagram_ads, etc)
     *     @type string $campaign       - Legacy: Campaign ID or name
     *     @type string $medium         - Legacy: Ad medium (default: 'paid_social')
     *     @type string $content        - Ad content/variant
     *     @type string $grades         - Target grades (comma-separated)
     * }
     * @return array|string WhatsApp link (new) or legacy response
     */
    public static function generate_whatsapp_link( $params = array() ) {
        $instance = new self();
        
        // Check if using new multi-platform method
        if ( isset( $params['campaign_id'] ) && isset( $params['platform'] ) ) {
            return $instance->_generate_link_with_tracking( $params );
        }
        
        // Fall back to legacy method
        return $instance->_generate_link( $params );
    }
    
    /**
     * Generate link with unique tracking code (NEW - v1.7.0)
     * Supports multiple simultaneous campaigns per phone
     * 
     * @param array $params Campaign and platform parameters
     * @return array|WP_Error Result array with link and tracking code
     */
    private function _generate_link_with_tracking( $params ) {
        global $wpdb;
        
        // Validate required parameters
        $campaign_id = isset( $params['campaign_id'] ) ? intval( $params['campaign_id'] ) : 0;
        $platform = isset( $params['platform'] ) ? sanitize_text_field( $params['platform'] ) : '';
        $phone = isset( $params['phone'] ) ? sanitize_text_field( $params['phone'] ) : '';
        $message = isset( $params['message'] ) ? sanitize_textarea_field( $params['message'] ) : '';
        $campaign_name = isset( $params['campaign_name'] ) ? sanitize_text_field( $params['campaign_name'] ) : '';
        
        // Validation
        if ( !$campaign_id ) {
            return new WP_Error( 'missing_campaign_id', 'Campaign ID is required' );
        }
        
        if ( !$phone ) {
            return new WP_Error( 'missing_phone', 'Phone number is required' );
        }
        
        if ( !$message ) {
            return new WP_Error( 'missing_message', 'Message is required' );
        }
        
        // Validate platform
        $valid_platforms = array( 'facebook', 'instagram', 'google', 'tiktok', 'linkedin', 'twitter', 'other' );
        if ( !in_array( $platform, $valid_platforms ) ) {
            return new WP_Error( 'invalid_platform', 'Invalid platform: ' . $platform );
        }
        
        try {
            // Clean phone number
            $clean_phone = preg_replace( '/[^0-9+]/', '', $phone );
            
            // Ensure phone has country code
            if ( strpos( $clean_phone, '+' ) === false && strlen( $clean_phone ) === 10 ) {
                $clean_phone = '+91' . $clean_phone;
            } elseif ( strpos( $clean_phone, '+' ) === false && strlen( $clean_phone ) > 10 ) {
                $clean_phone = '+' . $clean_phone;
            }
            
            // Remove + for API calls
            $phone_for_api = preg_replace( '/[^0-9]/', '', $clean_phone );
            
            // Generate unique tracking code
            $timestamp = time();
            $platform_abbr = strtoupper( substr( $platform, 0, 2 ) );
            $tracking_code = sprintf( 'CAMP_%d_%s_%d', $campaign_id, $platform_abbr, $timestamp );
            
            // Prepare data for database insertion
            $campaign_table = $wpdb->prefix . 'edubot_campaign_tracking';
            
            $insert_data = array(
                'campaign_id' => $campaign_id,
                'phone' => $phone_for_api,
                'platform' => $platform,
                'utm_source' => $platform,
                'utm_medium' => 'whatsapp_click_to_chat',
                'utm_campaign' => empty( $campaign_name ) ? 'campaign_' . $campaign_id : $campaign_name,
                'tracking_code' => $tracking_code,
                'status' => 'active'
            );
            
            // Insert into database
            $result = $wpdb->insert( $campaign_table, $insert_data, array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
            
            if ( $result === false ) {
                return new WP_Error( 'db_insert_error', 'Failed to insert tracking record: ' . $wpdb->last_error );
            }
            
            // Create message with tracking code
            $tracking_message = sprintf( "%s\n\n[Track: %s]", $message, $tracking_code );
            
            // Generate WhatsApp link
            $link = 'https://wa.me/' . $phone_for_api . '?text=' . urlencode( $tracking_message );
            
            error_log( sprintf( 'EduBot: Generated multi-platform link - Campaign: %d, Platform: %s, Code: %s', $campaign_id, $platform, $tracking_code ) );
            
            // Return success response
            return array(
                'success' => true,
                'link' => $link,
                'tracking_code' => $tracking_code,
                'message' => $tracking_message,
                'campaign_data' => array(
                    'campaign_id' => $campaign_id,
                    'platform' => $platform,
                    'phone' => $phone_for_api,
                    'campaign_name' => $campaign_name,
                    'utm_source' => $platform,
                    'utm_medium' => 'whatsapp_click_to_chat',
                    'utm_campaign' => $insert_data['utm_campaign']
                )
            );
            
        } catch ( Exception $e ) {
            error_log( 'EduBot: Exception in link generation - ' . $e->getMessage() );
            return new WP_Error( 'exception', 'Exception: ' . $e->getMessage() );
        }
    }
    
    /**
     * Generate link (LEGACY - v1.0.0)
     * Kept for backwards compatibility
     */
    private function _generate_link( $params ) {
        $defaults = array(
            'phone' => $this->get_business_phone(),
            'source' => 'facebook_ads',
            'campaign' => '',
            'medium' => 'paid_social',
            'content' => '',
            'grades' => '',
            'utm_source' => 'facebook',
            'utm_medium' => 'whatsapp_ad'
        );
        
        $params = wp_parse_args( $params, $defaults );
        
        // Format phone number
        $phone_formatted = $this->format_phone_number( $params['phone'] );
        
        if ( empty( $phone_formatted ) ) {
            error_log( 'EduBot: Invalid phone number provided' );
            return '';
        }
        
        // Generate welcome message
        $welcome_message = $this->get_welcome_message( $params );
        $encoded_message = urlencode( $welcome_message );
        
        // Generate WhatsApp Click-to-Chat link
        $whatsapp_url = "https://wa.me/{$phone_formatted}?text={$encoded_message}";
        
        // Store click tracking data (will be recorded when link is clicked)
        $this->store_link_metadata( $params );
        
        error_log( 'EduBot: Generated WhatsApp ad link for source: ' . $params['source'] );
        
        return $whatsapp_url;
    }
    
    /**
     * Get business phone number from settings
     */
    private function get_business_phone() {
        $phone = get_option( 'edubot_whatsapp_business_phone', '' );
        
        if ( empty( $phone ) ) {
            // Try to get from API integrations settings
            $phone = get_option( 'edubot_whatsapp_phone_id', '' );
        }
        
        return $phone;
    }
    
    /**
     * Get welcome message based on ad source and grade
     */
    private function get_welcome_message( $params ) {
        $source = $params['source'];
        $grades = $params['grades'];
        $school_name = $this->get_school_name();
        
        // Build grade text
        $grade_text = '';
        if ( !empty( $grades ) ) {
            $grades_array = array_map( 'trim', explode( ',', $grades ) );
            $grade_text = 'for Grade ' . implode( '/', $grades_array );
        }
        
        $messages = array(
            'facebook_ads' => sprintf(
                "Hi! 👋 I saw your Facebook ad for %s admissions %s. Tell me more about the admission process! 📚",
                $school_name,
                $grade_text
            ),
            'instagram_ads' => sprintf(
                "Hey! 😊 Saw your Instagram ad for %s. Interested in learning about admissions %s. 🏫",
                $school_name,
                $grade_text
            ),
            'google_ads' => sprintf(
                "Hi! 👋 Clicked on your Google ad for %s. Want to know about admissions %s. 📖",
                $school_name,
                $grade_text
            ),
            'tiktok_ads' => sprintf(
                "Hey! 🎬 Saw your TikTok ad for %s. Interested in admissions %s!",
                $school_name,
                $grade_text
            ),
            'linkedin_ads' => sprintf(
                "Hi! 💼 Saw your LinkedIn ad for %s admissions %s. Tell me more! 📚",
                $school_name,
                $grade_text
            ),
        );
        
        $source_key = sanitize_key( $source );
        $message = isset( $messages[ $source_key ] ) ? $messages[ $source_key ] : "Hello! Interested in admission enquiry at {$school_name}. 👋";
        
        return $message;
    }
    
    /**
     * Get school name from settings
     */
    private function get_school_name() {
        $settings = get_option( 'edubot_pro_settings', array() );
        return isset( $settings['school_name'] ) ? $settings['school_name'] : 'Epistemo Vikas Leadership School';
    }
    
    /**
     * Format phone number for WhatsApp API
     * 
     * @param string $phone Phone number
     * @return string Formatted phone number
     */
    private function format_phone_number( $phone ) {
        if ( empty( $phone ) ) {
            return '';
        }
        
        // Remove all non-digits
        $cleaned = preg_replace( '/\D/', '', $phone );
        
        // Add country code if not present (India default)
        if ( strlen( $cleaned ) === 10 ) {
            $cleaned = '91' . $cleaned;
        }
        
        // Validate final format (should be 12-15 digits)
        if ( strlen( $cleaned ) < 10 || strlen( $cleaned ) > 15 ) {
            error_log( 'EduBot: Invalid phone format after cleaning: ' . $cleaned );
            return '';
        }
        
        return $cleaned;
    }
    
    /**
     * Store link metadata for analytics
     */
    private function store_link_metadata( $params ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_ad_link_metadata';
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) != $table ) {
            error_log( 'EduBot: Ad link metadata table does not exist' );
            return;
        }
        
        $wpdb->insert(
            $table,
            array(
                'source' => sanitize_text_field( $params['source'] ),
                'campaign' => sanitize_text_field( $params['campaign'] ),
                'medium' => sanitize_text_field( $params['medium'] ),
                'content' => sanitize_text_field( $params['content'] ),
                'grades' => sanitize_text_field( $params['grades'] ),
                'created_at' => current_time( 'mysql' )
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' )
        );
    }
    
    /**
     * Get all active ad campaigns
     */
    public static function get_active_campaigns() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_ad_campaigns';
        
        return $wpdb->get_results(
            "SELECT * FROM $table WHERE status = 'active' ORDER BY created_at DESC"
        );
    }
    
    /**
     * Create new campaign
     */
    public static function create_campaign( $campaign_data ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_ad_campaigns';
        
        $wpdb->insert(
            $table,
            array(
                'name' => sanitize_text_field( $campaign_data['name'] ),
                'source' => sanitize_text_field( $campaign_data['source'] ),
                'grades' => sanitize_text_field( $campaign_data['grades'] ),
                'whatsapp_link' => sanitize_url( $campaign_data['link'] ),
                'status' => 'active',
                'created_at' => current_time( 'mysql' )
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' )
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update campaign status
     */
    public static function update_campaign_status( $campaign_id, $status ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_ad_campaigns';
        
        $wpdb->update(
            $table,
            array( 'status' => sanitize_text_field( $status ) ),
            array( 'id' => $campaign_id ),
            array( '%s' ),
            array( '%d' )
        );
    }
    
    /**
     * Get campaign statistics
     */
    public static function get_campaign_stats( $campaign_id ) {
        global $wpdb;
        
        $clicks_table = $wpdb->prefix . 'edubot_ad_clicks';
        $sessions_table = $wpdb->prefix . 'edubot_whatsapp_sessions';
        
        $clicks = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $clicks_table WHERE campaign_id = %d",
            $campaign_id
        ) );
        
        $sessions = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $sessions_table WHERE campaign_id = %d",
            $campaign_id
        ) );
        
        $completions = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $sessions_table WHERE campaign_id = %d AND completed_at IS NOT NULL",
            $campaign_id
        ) );
        
        return array(
            'clicks' => (int) $clicks,
            'sessions' => (int) $sessions,
            'completions' => (int) $completions,
            'conversion_rate' => $clicks > 0 ? round( ( $completions / $clicks ) * 100, 2 ) : 0
        );
    }
    
    /**
     * Get campaign by tracking code (NEW - v1.7.0)
     * Used by webhook to identify campaign from message
     * 
     * @param string $tracking_code Unique tracking code from message
     * @return object|null Campaign tracking data or null if not found
     */
    public static function get_campaign_by_tracking_code( $tracking_code ) {
        global $wpdb;
        
        if ( empty( $tracking_code ) ) {
            return null;
        }
        
        $campaign_table = $wpdb->prefix . 'edubot_campaign_tracking';
        
        $result = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$campaign_table} 
             WHERE tracking_code = %s 
             AND status = 'active'
             LIMIT 1",
            $tracking_code
        ) );
        
        return $result;
    }
    
    /**
     * Update last message timestamp for tracking code (NEW - v1.7.0)
     * Called by webhook when message is received
     * 
     * @param string $tracking_code Tracking code to update
     * @return bool Success flag
     */
    public static function update_tracking_last_message( $tracking_code ) {
        global $wpdb;
        
        if ( empty( $tracking_code ) ) {
            return false;
        }
        
        $campaign_table = $wpdb->prefix . 'edubot_campaign_tracking';
        
        $result = $wpdb->update(
            $campaign_table,
            array( 'last_message_at' => current_time( 'mysql' ) ),
            array( 'tracking_code' => $tracking_code ),
            array( '%s' ),
            array( '%s' )
        );
        
        return $result !== false;
    }
    
    /**
     * Get all active campaigns for a phone (NEW - v1.7.0)
     * Shows all campaigns currently running for this phone number
     * 
     * @param string $phone Phone number (format: 919866133566)
     * @return array Array of campaign objects
     */
    public static function get_all_campaigns_for_phone( $phone ) {
        global $wpdb;
        
        if ( empty( $phone ) ) {
            return array();
        }
        
        // Clean phone number
        $clean_phone = preg_replace( '/[^0-9]/', '', $phone );
        
        $campaign_table = $wpdb->prefix . 'edubot_campaign_tracking';
        
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$campaign_table} 
             WHERE phone = %s 
             AND status = 'active'
             ORDER BY created_at DESC",
            $clean_phone
        ) );
        
        return $results ? $results : array();
    }
    
    /**
     * Get recent campaign for phone (NEW - v1.7.0)
     * Useful for fallback when no tracking code found
     * 
     * @param string $phone     Phone number
     * @param string $platform  Platform to filter by (optional)
     * @param int    $hours_ago Only get campaigns created in last N hours (default: 24)
     * @return object|null Most recent campaign or null
     */
    public static function get_recent_campaign_for_phone( $phone, $platform = '', $hours_ago = 24 ) {
        global $wpdb;
        
        if ( empty( $phone ) ) {
            return null;
        }
        
        // Clean phone number
        $clean_phone = preg_replace( '/[^0-9]/', '', $phone );
        
        $campaign_table = $wpdb->prefix . 'edubot_campaign_tracking';
        
        $query = "SELECT * FROM {$campaign_table} 
                  WHERE phone = %s 
                  AND status = 'active'";
        
        $params = array( $clean_phone );
        
        if ( !empty( $platform ) ) {
            $query .= " AND platform = %s";
            $params[] = $platform;
        }
        
        $query .= " AND created_at > DATE_SUB(NOW(), INTERVAL %d HOUR)
                    ORDER BY created_at DESC 
                    LIMIT 1";
        $params[] = $hours_ago;
        
        $result = $wpdb->get_row( $wpdb->prepare( $query, $params ) );
        
        return $result;
    }
    
    /**
     * Archive campaign (NEW - v1.7.0)
     * Make a campaign inactive
     * 
     * @param int $campaign_id Campaign ID to archive
     * @return bool Success flag
     */
    public static function archive_campaign( $campaign_id ) {
        global $wpdb;
        
        if ( !$campaign_id ) {
            return false;
        }
        
        $campaign_table = $wpdb->prefix . 'edubot_campaign_tracking';
        
        $result = $wpdb->update(
            $campaign_table,
            array( 'status' => 'archived' ),
            array( 'campaign_id' => $campaign_id ),
            array( '%s' ),
            array( '%d' )
        );
        
        return $result !== false;
    }
    
    /**
     * Get campaign performance metrics (NEW - v1.7.0)
     * 
     * @param int $campaign_id Campaign ID
     * @return array Performance data with stats
     */
    public static function get_campaign_performance( $campaign_id ) {
        global $wpdb;
        
        if ( !$campaign_id ) {
            return array();
        }
        
        $campaign_table = $wpdb->prefix . 'edubot_campaign_tracking';
        
        // Get campaign data
        $campaign_data = $wpdb->get_row( $wpdb->prepare(
            "SELECT 
                COUNT(*) as total_links,
                COUNT(DISTINCT phone) as unique_phones,
                COUNT(DISTINCT CASE WHEN first_message_at IS NOT NULL THEN campaign_id END) as sessions_started,
                DATEDIFF(MAX(last_message_at), MIN(created_at)) as days_active
             FROM {$campaign_table}
             WHERE campaign_id = %d",
            $campaign_id
        ) );
        
        return $campaign_data ? (array) $campaign_data : array();
    }
}
