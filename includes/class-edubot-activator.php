<?php

/**
 * Fired during plugin activation
 */
class EduBot_Activator {

    /**
     * Plugin activation
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::schedule_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create plugin tables
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // School configurations table
        $table_schools = $wpdb->prefix . 'edubot_school_configs';
        $sql_schools = "CREATE TABLE $table_schools (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            site_id bigint(20) NOT NULL,
            school_name varchar(255) NOT NULL,
            config_data longtext NOT NULL,
            api_keys_encrypted longtext,
            branding_settings longtext,
            academic_structure longtext,
            board_settings longtext,
            academic_year_settings longtext,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY site_id (site_id)
        ) $charset_collate;";

        // Applications table
        $table_applications = $wpdb->prefix . 'edubot_applications';
        $sql_applications = "CREATE TABLE $table_applications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            site_id bigint(20) NOT NULL,
            application_number varchar(50) NOT NULL,
            student_data longtext NOT NULL,
            custom_fields_data longtext,
            conversation_log longtext,
            status varchar(50) DEFAULT 'pending',
            source varchar(50) DEFAULT 'chatbot',
            ip_address varchar(45),
            user_agent text,
            utm_data longtext,
            whatsapp_sent tinyint(1) DEFAULT 0,
            email_sent tinyint(1) DEFAULT 0,
            sms_sent tinyint(1) DEFAULT 0,
            follow_up_scheduled datetime,
            assigned_to bigint(20),
            priority varchar(20) DEFAULT 'normal',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY application_number (application_number),
            KEY site_id (site_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Conversation analytics table
        $table_analytics = $wpdb->prefix . 'edubot_analytics';
        $sql_analytics = "CREATE TABLE $table_analytics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            site_id bigint(20) NOT NULL,
            session_id varchar(255) NOT NULL,
            event_type varchar(50) NOT NULL,
            event_data longtext,
            ip_address varchar(45),
            user_agent text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY site_id (site_id),
            KEY session_id (session_id),
            KEY event_type (event_type),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        // Conversation sessions table
        $table_sessions = $wpdb->prefix . 'edubot_sessions';
        $sql_sessions = "CREATE TABLE $table_sessions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            site_id bigint(20) NOT NULL,
            session_id varchar(255) NOT NULL,
            user_data longtext,
            conversation_state longtext,
            last_activity datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY site_id (site_id),
            KEY last_activity (last_activity)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta(array($sql_schools, $sql_applications, $sql_analytics, $sql_sessions));
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $site_id = get_current_blog_id();
        
        $default_config = array(
            'school_info' => array(
                'name' => get_bloginfo('name'),
                'logo' => '',
                'colors' => array('primary' => '#4facfe', 'secondary' => '#00f2fe'),
                'contact_info' => array(),
                'address' => '',
                'website' => get_site_url()
            ),
            'api_keys' => array(
                'openai_key' => '',
                'whatsapp_token' => '',
                'email_service' => 'smtp',
                'email_api_key' => '',
                'sms_provider' => '',
                'sms_api_key' => ''
            ),
            'form_settings' => array(
                'required_fields' => array('student_name', 'parent_name', 'phone', 'email', 'grade'),
                'optional_fields' => array('address', 'previous_school'),
                'custom_fields' => array(),
                'academic_years' => array('2025-26'),
                'boards' => array('CBSE', 'Cambridge'),
                'grades' => array('Pre-K', 'K', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII')
            ),
            'chatbot_settings' => array(
                'welcome_message' => 'Hello! 👋 Welcome to our school admission process. I\'m here to help you with your application.',
                'language' => 'en',
                'ai_model' => 'gpt-3.5-turbo',
                'response_style' => 'friendly',
                'max_retries' => 3
            ),
            'notification_settings' => array(
                'whatsapp_enabled' => false,
                'email_enabled' => true,
                'sms_enabled' => false,
                'admin_notifications' => true,
                'parent_notifications' => true
            ),
            'automation_settings' => array(
                'auto_send_brochure' => true,
                'follow_up_enabled' => true,
                'follow_up_delay' => 24,
                'reminder_sequence' => array()
            )
        );

        global $wpdb;
        $table_schools = $wpdb->prefix . 'edubot_school_configs';
        
        $wpdb->insert(
            $table_schools,
            array(
                'site_id' => $site_id,
                'school_name' => $default_config['school_info']['name'],
                'config_data' => json_encode($default_config),
                'status' => 'active'
            ),
            array('%d', '%s', '%s', '%s')
        );
    }

    /**
     * Schedule cron events
     */
    private static function schedule_events() {
        if (!wp_next_scheduled('edubot_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'edubot_daily_cleanup');
        }
        
        if (!wp_next_scheduled('edubot_follow_up_check')) {
            wp_schedule_event(time(), 'hourly', 'edubot_follow_up_check');
        }
    }
}
