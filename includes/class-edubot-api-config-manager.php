<?php
/**
 * API Configuration Manager
 * 
 * Reads and manages API integrations from database table
 * Provides interface to access WhatsApp, Email, SMS, OpenAI configurations
 */

class EduBot_API_Config_Manager {

    /**
     * Get API configuration from database
     * 
     * @param int $site_id Optional site ID (defaults to current blog)
     * @return array|null Configuration array or null if not found
     */
    public static function get_config( $site_id = null ) {
        global $wpdb;
        
        if ( null === $site_id ) {
            $site_id = get_current_blog_id();
        }
        
        $table = $wpdb->prefix . 'edubot_api_integrations';
        $config = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE site_id = %d",
                $site_id
            ),
            ARRAY_A
        );
        
        // Decode JSON fields
        if ( $config && ! empty( $config['notification_settings'] ) ) {
            $config['notification_settings'] = json_decode( $config['notification_settings'], true );
        }
        
        return $config;
    }

    /**
     * Get WhatsApp configuration
     * 
     * @return array|null WhatsApp config or null
     */
    public static function get_whatsapp_config() {
        $config = self::get_config();
        
        if ( ! $config ) {
            return null;
        }
        
        return [
            'provider' => $config['whatsapp_provider'] ?? null,
            'token' => $config['whatsapp_token'] ?? null,
            'phone_id' => $config['whatsapp_phone_id'] ?? null,
            'business_account_id' => $config['whatsapp_business_account_id'] ?? null,
            'template_type' => $config['whatsapp_template_type'] ?? 'business_template',
            'template_name' => $config['whatsapp_template_name'] ?? 'admission_confirmation',
        ];
    }

    /**
     * Get Email configuration
     * 
     * @return array|null Email config or null
     */
    public static function get_email_config() {
        $config = self::get_config();
        
        if ( ! $config ) {
            return null;
        }
        
        return [
            'provider' => $config['email_provider'] ?? null,
            'from_address' => $config['email_from_address'] ?? null,
            'from_name' => $config['email_from_name'] ?? null,
            'api_key' => $config['email_api_key'] ?? null,
            'smtp_host' => $config['smtp_host'] ?? null,
            'smtp_port' => $config['smtp_port'] ?? null,
            'smtp_username' => $config['smtp_username'] ?? null,
            'smtp_password' => $config['smtp_password'] ?? null,
            'domain' => $config['email_domain'] ?? null,
        ];
    }

    /**
     * Get SMS configuration
     * 
     * @return array|null SMS config or null
     */
    public static function get_sms_config() {
        $config = self::get_config();
        
        if ( ! $config ) {
            return null;
        }
        
        return [
            'provider' => $config['sms_provider'] ?? null,
            'api_key' => $config['sms_api_key'] ?? null,
            'sender_id' => $config['sms_sender_id'] ?? null,
        ];
    }

    /**
     * Get OpenAI configuration
     * 
     * @return array|null OpenAI config or null
     */
    public static function get_openai_config() {
        $config = self::get_config();
        
        if ( ! $config ) {
            return null;
        }
        
        return [
            'api_key' => $config['openai_api_key'] ?? null,
            'model' => $config['openai_model'] ?? 'gpt-3.5-turbo',
        ];
    }

    /**
     * Get notification settings
     * 
     * @return array Notification settings
     */
    public static function get_notification_settings() {
        $config = self::get_config();
        
        if ( ! $config ) {
            return [
                'whatsapp_parent_notifications' => false,
                'whatsapp_school_notifications' => false,
                'email_notifications' => false,
                'sms_notifications' => false,
            ];
        }
        
        return $config['notification_settings'] ?? [];
    }

    /**
     * Check if WhatsApp is configured
     * 
     * @return bool True if WhatsApp provider and token are set
     */
    public static function is_whatsapp_configured() {
        $wa_config = self::get_whatsapp_config();
        return ! empty( $wa_config['provider'] ) && ! empty( $wa_config['token'] );
    }

    /**
     * Check if Email is configured
     * 
     * @return bool True if email provider is set
     */
    public static function is_email_configured() {
        $email_config = self::get_email_config();
        return ! empty( $email_config['provider'] );
    }

    /**
     * Check if SMS is configured
     * 
     * @return bool True if SMS provider is set
     */
    public static function is_sms_configured() {
        $sms_config = self::get_sms_config();
        return ! empty( $sms_config['provider'] );
    }

    /**
     * Update API configuration
     * 
     * @param array $data Configuration data to update
     * @param int $site_id Optional site ID
     * @return bool Success
     */
    public static function update_config( $data, $site_id = null ) {
        global $wpdb;
        
        if ( null === $site_id ) {
            $site_id = get_current_blog_id();
        }
        
        $table = $wpdb->prefix . 'edubot_api_integrations';
        
        // Encode notification settings if present
        if ( isset( $data['notification_settings'] ) && is_array( $data['notification_settings'] ) ) {
            $data['notification_settings'] = json_encode( $data['notification_settings'] );
        }
        
        $result = $wpdb->update(
            $table,
            $data,
            [ 'site_id' => $site_id ],
            null,
            [ '%d' ]
        );
        
        return false !== $result;
    }

    /**
     * Create default API configuration
     * 
     * @param int $site_id Optional site ID
     * @return bool Success
     */
    public static function create_default_config( $site_id = null ) {
        global $wpdb;
        
        if ( null === $site_id ) {
            $site_id = get_current_blog_id();
        }
        
        $table = $wpdb->prefix . 'edubot_api_integrations';
        
        // Check if already exists
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE site_id = %d",
                $site_id
            )
        );
        
        if ( $existing ) {
            return true; // Already exists
        }
        
        // Create default config
        $default_data = [
            'site_id' => $site_id,
            'whatsapp_provider' => 'meta',
            'whatsapp_phone_id' => '',
            'whatsapp_token' => '',
            'whatsapp_template_type' => 'business_template',
            'whatsapp_template_name' => 'admission_confirmation',
            'email_provider' => 'zeptomail',
            'email_from_address' => get_bloginfo( 'admin_email' ),
            'email_from_name' => get_bloginfo( 'name' ),
            'sms_provider' => '',
            'sms_sender_id' => 'EDUBOT',
            'openai_model' => 'gpt-3.5-turbo',
            'notification_settings' => json_encode( [
                'whatsapp_parent_notifications' => true,
                'whatsapp_school_notifications' => true,
                'email_notifications' => true,
                'sms_notifications' => false,
            ] ),
            'status' => 'active',
        ];
        
        $result = $wpdb->insert(
            $table,
            $default_data
        );
        
        return false !== $result;
    }
}
