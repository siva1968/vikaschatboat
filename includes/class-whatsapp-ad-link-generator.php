<?php
/**
 * WhatsApp Ad Link Generator
 * 
 * Generates WhatsApp click-to-chat links for Facebook/Instagram ad campaigns
 * with proper attribution and tracking
 * 
 * @package EduBot_Pro
 * @subpackage Integrations
 * @version 1.0.0
 */

class EduBot_WhatsApp_Ad_Link_Generator {
    
    /**
     * Generate WhatsApp click-to-chat link for ad campaigns
     * 
     * @param array $params {
     *     @type string $phone - WhatsApp Business phone number
     *     @type string $source - Ad source (facebook, instagram, google, etc)
     *     @type string $campaign - Campaign ID or name
     *     @type string $medium - Ad medium (default: 'paid_social')
     *     @type string $content - Ad content/variant
     *     @type string $grades - Target grades (comma-separated)
     * }
     * @return string WhatsApp link
     */
    public static function generate_whatsapp_link( $params = array() ) {
        $instance = new self();
        return $instance->_generate_link( $params );
    }
    
    /**
     * Generate link (internal method)
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
        return isset( $settings['school_name'] ) ? $settings['school_name'] : 'Vikas The Concept School';
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
}
