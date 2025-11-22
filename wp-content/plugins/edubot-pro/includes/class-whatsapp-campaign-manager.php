<?php
/**
 * WhatsApp Campaign Manager
 * 
 * Manages predefined campaign configurations for simplified link generation
 * Just pass campaign name + phone number to get complete WhatsApp links
 * 
 * @package EduBot_Pro
 * @subpackage Integrations
 * @version 1.0.0
 */

class EduBot_WhatsApp_Campaign_Manager {
    
    /**
     * Get all configured campaigns
     * 
     * @return array Campaign configurations
     */
    public static function get_campaigns() {
        return get_option('edubot_whatsapp_campaigns', array());
    }
    
    /**
     * Get a specific campaign configuration
     * 
     * @param string $campaign_name Campaign name
     * @return array|false Campaign config or false if not found
     */
    public static function get_campaign($campaign_name) {
        $campaigns = self::get_campaigns();
        return isset($campaigns[$campaign_name]) ? $campaigns[$campaign_name] : false;
    }
    
    /**
     * Save campaign configuration
     * 
     * @param string $campaign_name Campaign name
     * @param array $config Campaign configuration
     * @return bool Success status
     */
    public static function save_campaign($campaign_name, $config, $is_update = false) {
        $campaigns = self::get_campaigns();
        
        // Validate required fields
        $required_fields = array('platform', 'message_template', 'target_grades');
        foreach ($required_fields as $field) {
            if (empty($config[$field])) {
                return false;
            }
        }
        
        // Preserve created_at if updating existing campaign
        $created_at = current_time('mysql');
        if ($is_update && isset($campaigns[$campaign_name]['created_at'])) {
            $created_at = $campaigns[$campaign_name]['created_at'];
        }
        
        // Sanitize and save
        $campaigns[$campaign_name] = array(
            'platform' => sanitize_text_field($config['platform']),
            'message_template' => sanitize_textarea_field($config['message_template']),
            'target_grades' => sanitize_text_field($config['target_grades']),
            'notes' => sanitize_textarea_field($config['notes'] ?? ''),
            'created_at' => $created_at,
            'updated_at' => current_time('mysql')
        );
        
        return update_option('edubot_whatsapp_campaigns', $campaigns);
    }
    
    /**
     * Generate WhatsApp link using campaign name and phone only
     * 
     * @param string $campaign_name Campaign name
     * @param string $phone WhatsApp Business phone number (required)
     * @param array $custom_params Optional custom parameters to override campaign defaults
     * @return string|WP_Error Result with clean WhatsApp link or error
     */
    public static function generate_link_by_campaign($campaign_name, $phone, $custom_params = array()) {
        // Get campaign configuration
        $campaign = self::get_campaign($campaign_name);
        if (!$campaign) {
            return new WP_Error('campaign_not_found', 'Campaign not found: ' . $campaign_name);
        }
        
        // Validate phone number
        if (empty($phone)) {
            return new WP_Error('missing_phone', 'Phone number is required');
        }
        
        // Clean and format phone number
        $clean_phone = self::format_phone_number($phone);
        if (!$clean_phone) {
            return new WP_Error('invalid_phone', 'Invalid phone number format');
        }
        
        // Process message template variables
        $message = self::process_message_template($campaign['message_template'], array(
            'campaign_name' => $campaign_name,
            'grades' => $campaign['target_grades']
        ));
        
        // Generate clean tracking suffix (simplified format)
        $tracking_suffix = "\n\n[Campaign: " . $campaign_name . "]";
        $message_with_tracking = $message . $tracking_suffix;
        
        // Generate WhatsApp link with tracking-enabled message
        $whatsapp_link = 'https://wa.me/' . $clean_phone . '?text=' . urlencode($message_with_tracking);
        
        // Save campaign tracking data to database
        self::save_campaign_tracking($campaign_name, $campaign, $phone, $whatsapp_link);
        
        return $whatsapp_link;
    }
    
    /**
     * Save campaign tracking data to database
     * 
     * @param string $campaign_name Campaign name
     * @param array $campaign Campaign configuration
     * @param string $phone Phone number
     * @param string $link Generated WhatsApp link
     * @return bool Success status
     */
    private static function save_campaign_tracking($campaign_name, $campaign, $phone, $link) {
        global $wpdb;
        
        // Ensure campaign tracking table exists
        self::create_campaign_tracking_table();
        
        $tracking_data = array(
            'campaign_name' => $campaign_name,
            'phone' => self::format_phone_number($phone),
            'platform' => $campaign['platform'],
            'target_grades' => $campaign['target_grades'],
            'message_template' => $campaign['message_template'],
            'whatsapp_link' => $link,
            'utm_source' => str_replace('_ads', '', $campaign['platform']),
            'utm_medium' => 'whatsapp_click_to_chat',
            'utm_campaign' => sanitize_title($campaign_name),
            'created_at' => current_time('mysql'),
            'status' => 'active'
        );
        
        $table_name = $wpdb->prefix . 'edubot_whatsapp_campaigns_tracking';
        
        $result = $wpdb->insert(
            $table_name,
            $tracking_data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('EduBot Campaign Manager: Failed to save tracking data - ' . $wpdb->last_error);
            return false;
        }
        
        error_log("EduBot Campaign Manager: Saved tracking data for campaign '{$campaign_name}' to database (ID: {$wpdb->insert_id})");
        return true;
    }
    
    /**
     * Create campaign tracking table if it doesn't exist
     */
    private static function create_campaign_tracking_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'edubot_whatsapp_campaigns_tracking';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            campaign_name varchar(255) NOT NULL,
            phone varchar(20) NOT NULL,
            platform varchar(50) NOT NULL,
            target_grades varchar(255) DEFAULT NULL,
            message_template text DEFAULT NULL,
            whatsapp_link text NOT NULL,
            utm_source varchar(50) DEFAULT NULL,
            utm_medium varchar(50) DEFAULT NULL,
            utm_campaign varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            clicks int(11) DEFAULT 0,
            conversions int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY campaign_name (campaign_name),
            KEY platform (platform),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Format phone number to international format
     * 
     * @param string $phone Raw phone number
     * @return string|false Formatted phone number or false if invalid
     */
    private static function format_phone_number($phone) {
        // Remove all non-numeric characters except +
        $clean_phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Handle Indian numbers
        if (strlen($clean_phone) == 10 && preg_match('/^[6-9]/', $clean_phone)) {
            return '91' . $clean_phone; // Return without + for wa.me
        } elseif (strlen($clean_phone) == 12 && substr($clean_phone, 0, 2) == '91') {
            return $clean_phone; // Already in correct format
        } elseif (strlen($clean_phone) == 13 && substr($clean_phone, 0, 3) == '+91') {
            return substr($clean_phone, 1); // Remove + for wa.me
        }
        
        // For other international numbers, return as-is if valid format
        if (strlen($clean_phone) >= 10 && strlen($clean_phone) <= 15) {
            return ltrim($clean_phone, '+');
        }
        
        return false;
    }
    
    /**
     * Process message template variables
     * 
     * @param string $template Message template with variables
     * @param array $params Parameters for variable replacement
     * @return string Processed message
     */
    private static function process_message_template($template, $params) {
        // Replace common variables
        $replacements = array(
            '{campaign_name}' => $params['campaign_name'],
            '{grades}' => $params['grades'],
            '{school_name}' => get_option('edubot_school_name', 'Our School'),
            '{current_year}' => date('Y'),
            '{academic_year}' => self::get_current_academic_year()
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    /**
     * Get current academic year
     * 
     * @return string Current academic year (e.g., "2025-26")
     */
    private static function get_current_academic_year() {
        $current_year = date('Y');
        $current_month = date('n');
        
        // Academic year starts in April (month 4)
        if ($current_month >= 4) {
            return $current_year . '-' . substr($current_year + 1, 2);
        } else {
            return ($current_year - 1) . '-' . substr($current_year, 2);
        }
    }
    
    /**
     * Get default campaign templates for initial setup
     * 
     * @return array Default campaign configurations
     */
    public static function get_default_campaigns() {
        return array(
            'Grade10 admissions' => array(
                'platform' => 'facebook_ads',
                'message_template' => 'Hi! I\'m interested in {school_name} Grade 10 admissions for {academic_year}. Can you help me with the application process?',
                'target_grades' => 'Grade 10',
                'notes' => 'Facebook campaign for Grade 10 admissions'
            ),
            'PP1 admissions - Google' => array(
                'platform' => 'google_ads',
                'message_template' => 'Hello! I\'d like to know about PP1 admissions at {school_name} for {academic_year}. What are the requirements?',
                'target_grades' => 'Pre-K',
                'notes' => 'Google Ads campaign for Pre-K admissions'
            ),
            'Middle School - Instagram' => array(
                'platform' => 'instagram_ads', 
                'message_template' => 'Hi there! I\'m looking for middle school admission information at {school_name}. Can you provide details?',
                'target_grades' => 'Grade 6,Grade 7,Grade 8',
                'notes' => 'Instagram campaign for middle school grades'
            ),
            'High School - Facebook' => array(
                'platform' => 'facebook_ads',
                'message_template' => 'Hello! I\'m interested in high school admissions at {school_name} for {academic_year}. Please share the details.',
                'target_grades' => 'Grade 9,Grade 10',
                'notes' => 'Facebook campaign for high school grades'
            )
        );
    }
    
    /**
     * Initialize default campaigns if none exist
     * 
     * @return bool Success status
     */
    public static function init_default_campaigns() {
        $existing = self::get_campaigns();
        if (empty($existing)) {
            return update_option('edubot_whatsapp_campaigns', self::get_default_campaigns());
        }
        return true;
    }
    
    /**
     * Get available platforms
     * 
     * @return array Platform options
     */
    public static function get_available_platforms() {
        return array(
            'facebook_ads' => 'Facebook Ads',
            'instagram_ads' => 'Instagram Ads', 
            'google_ads' => 'Google Ads',
            'tiktok_ads' => 'TikTok Ads',
            'linkedin_ads' => 'LinkedIn Ads',
            'twitter_ads' => 'Twitter Ads',
            'youtube_ads' => 'YouTube Ads',
            'other' => 'Other Platform'
        );
    }
    
    /**
     * Get available grade options
     * 
     * @return array Grade options
     */
    public static function get_available_grades() {
        return array(
            'Pre-K' => 'Pre-K',
            'K' => 'Kindergarten',
            'Grade 1' => 'Grade 1',
            'Grade 2' => 'Grade 2', 
            'Grade 3' => 'Grade 3',
            'Grade 4' => 'Grade 4',
            'Grade 5' => 'Grade 5',
            'Grade 6,Grade 7,Grade 8' => 'Middle School (6-8)',
            'Grade 9,Grade 10' => 'High School (9-10)',
            'Grade 11,Grade 12' => 'Senior School (11-12)',
            'All Grades' => 'All Grades'
        );
    }
    
    /**
     * Delete campaign configuration
     * 
     * @param string $campaign_name Campaign name
     * @return bool Success status
     */
    public static function delete_campaign($campaign_name) {
        $campaigns = self::get_campaigns();
        if (isset($campaigns[$campaign_name])) {
            unset($campaigns[$campaign_name]);
            return update_option('edubot_whatsapp_campaigns', $campaigns);
        }
        return false;
    }
    
    /**
     * Get campaign analytics and tracking data
     * 
     * @param string $campaign_name Optional specific campaign name
     * @return array Campaign analytics data
     */
    public static function get_campaign_analytics($campaign_name = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'edubot_whatsapp_campaigns_tracking';
        
        $where_clause = '';
        $params = array();
        
        if ($campaign_name) {
            $where_clause = ' WHERE campaign_name = %s';
            $params[] = $campaign_name;
        }
        
        $sql = "SELECT 
                    campaign_name,
                    platform,
                    COUNT(*) as total_links_generated,
                    SUM(clicks) as total_clicks,
                    SUM(conversions) as total_conversions,
                    MAX(created_at) as last_generated,
                    MIN(created_at) as first_generated
                FROM {$table_name} 
                {$where_clause}
                GROUP BY campaign_name, platform 
                ORDER BY total_links_generated DESC";
        
        if (!empty($params)) {
            $results = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        } else {
            $results = $wpdb->get_results($sql, ARRAY_A);
        }
        
        return $results ?: array();
    }
    
    /**
     * Record a click on a campaign link
     * 
     * @param string $campaign_name Campaign name
     * @param string $phone Phone number
     * @return bool Success status
     */
    public static function record_click($campaign_name, $phone) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'edubot_whatsapp_campaigns_tracking';
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} 
             SET clicks = clicks + 1 
             WHERE campaign_name = %s AND phone = %s 
             ORDER BY created_at DESC 
             LIMIT 1",
            $campaign_name,
            self::format_phone_number($phone)
        ));
        
        return $result !== false;
    }
    
    /**
     * Record a conversion from a campaign
     * 
     * @param string $campaign_name Campaign name
     * @param string $phone Phone number
     * @return bool Success status
     */
    public static function record_conversion($campaign_name, $phone) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'edubot_whatsapp_campaigns_tracking';
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} 
             SET conversions = conversions + 1 
             WHERE campaign_name = %s AND phone = %s 
             ORDER BY created_at DESC 
             LIMIT 1",
            $campaign_name,
            self::format_phone_number($phone)
        ));
        
        return $result !== false;
    }
    
    /**
     * Get recent campaign links
     * 
     * @param int $limit Number of recent links to retrieve
     * @return array Recent campaign links
     */
    public static function get_recent_links($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'edubot_whatsapp_campaigns_tracking';
        
        $sql = $wpdb->prepare(
            "SELECT campaign_name, phone, platform, whatsapp_link, created_at, clicks, conversions
             FROM {$table_name} 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        );
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
}
