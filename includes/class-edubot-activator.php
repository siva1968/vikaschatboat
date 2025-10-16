<?php

/**
 * Fired during plugin activation
 */
class EduBot_Activator {

    /**
     * Plugin activation
     */
    public static function activate() {
        // Check if we need migration
        $current_db_version = get_option('edubot_pro_db_version', '0');
        
        if (version_compare($current_db_version, EDUBOT_PRO_DB_VERSION, '<')) {
            self::create_tables();
            self::migrate_data($current_db_version);
            update_option('edubot_pro_db_version', EDUBOT_PRO_DB_VERSION);
        }
        
        self::set_default_options();
        self::schedule_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log activation
        error_log('EduBot Pro activated successfully. Version: ' . EDUBOT_PRO_VERSION);
    }
    
    /**
     * Handle data migration between versions
     */
    private static function migrate_data($from_version) {
        global $wpdb;
        
        // Ensure enquiries table exists with all columns
        self::ensure_enquiries_table_exists();
        
        // Migration from 1.0.x to 1.3.x
        if (version_compare($from_version, '1.3.0', '<')) {
            // Add new columns if they don't exist
            $table_applications = $wpdb->prefix . 'edubot_applications';
            
            $columns_to_add = array(
                'utm_data' => 'longtext',
                'follow_up_scheduled' => 'datetime',
                'assigned_to' => 'bigint(20)',
                'priority' => "varchar(20) DEFAULT 'normal'"
            );
            
            foreach ($columns_to_add as $column => $definition) {
                $column_exists = $wpdb->get_results($wpdb->prepare(
                    "SHOW COLUMNS FROM $table_applications LIKE %s",
                    $column
                ));
                
                if (empty($column_exists)) {
                    $wpdb->query("ALTER TABLE $table_applications ADD COLUMN $column $definition");
                }
            }
        }
        
        error_log("EduBot Pro: Database migrated from version $from_version to " . EDUBOT_PRO_DB_VERSION);
    }
    
    /**
     * Ensure enquiries table exists and has all required columns
     */
    private static function ensure_enquiries_table_exists() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'edubot_enquiries';
        $charset_collate = $wpdb->get_charset_collate();
        
        // First check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        if (!$table_exists) {
            // Create the table
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                enquiry_number varchar(50) NOT NULL,
                student_name varchar(100) NOT NULL,
                date_of_birth date NULL,
                grade varchar(50) NULL,
                board varchar(50) NULL,
                academic_year varchar(20) NULL,
                parent_name varchar(100) NULL,
                email varchar(100) NULL,
                phone varchar(20) NULL,
                address text NULL,
                gender varchar(10) NULL,
                ip_address varchar(45) NULL,
                user_agent text NULL,
                utm_data longtext NULL,
                gclid varchar(100) NULL,
                fbclid varchar(100) NULL,
                click_id_data longtext NULL,
                whatsapp_sent tinyint(1) DEFAULT 0,
                email_sent tinyint(1) DEFAULT 0,
                sms_sent tinyint(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                status varchar(20) DEFAULT 'pending',
                source varchar(50) DEFAULT 'chatbot',
                PRIMARY KEY (id),
                UNIQUE KEY enquiry_number (enquiry_number)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            error_log("EduBot: Created enquiries table");
        } else {
            // Table exists, check if it's missing the source column and add if needed
            $required_columns = array(
                'source' => "varchar(50) DEFAULT 'chatbot'",
                'ip_address' => 'varchar(45) NULL',
                'user_agent' => 'text NULL',
                'utm_data' => 'longtext NULL',
                'gclid' => 'varchar(100) NULL',
                'fbclid' => 'varchar(100) NULL',
                'click_id_data' => 'longtext NULL',
                'whatsapp_sent' => 'tinyint(1) DEFAULT 0',
                'email_sent' => 'tinyint(1) DEFAULT 0',
                'sms_sent' => 'tinyint(1) DEFAULT 0'
            );
            
            foreach ($required_columns as $column_name => $column_definition) {
                $column_exists = $wpdb->get_results($wpdb->prepare(
                    "SHOW COLUMNS FROM $table_name LIKE %s",
                    $column_name
                ));
                
                if (empty($column_exists)) {
                    $wpdb->query("ALTER TABLE $table_name ADD COLUMN $column_name $column_definition");
                    error_log("EduBot: Added missing column '$column_name' to enquiries table");
                }
            }
        }
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

        // Security log table
        $table_security = $wpdb->prefix . 'edubot_security_log';
        $sql_security = "CREATE TABLE $table_security (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            site_id bigint(20) NOT NULL,
            event_type varchar(100) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            details longtext,
            severity varchar(20) DEFAULT 'medium',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY site_id (site_id),
            KEY event_type (event_type),
            KEY ip_address (ip_address),
            KEY created_at (created_at),
            KEY severity (severity)
        ) $charset_collate;";

        // Visitor analytics table (enhanced tracking)
        $table_visitor_analytics = $wpdb->prefix . 'edubot_visitor_analytics';
        $sql_visitor_analytics = "CREATE TABLE $table_visitor_analytics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            site_id bigint(20) NOT NULL,
            visitor_id varchar(255) NOT NULL,
            session_id varchar(255) NOT NULL,
            event_type varchar(50) NOT NULL,
            event_data longtext,
            ip_address varchar(45),
            user_agent text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY site_id (site_id),
            KEY visitor_id (visitor_id),
            KEY session_id (session_id),
            KEY event_type (event_type),
            KEY timestamp (timestamp),
            KEY ip_address (ip_address)
        ) $charset_collate;";

        // Visitors table (visitor profiles and return tracking)
        $table_visitors = $wpdb->prefix . 'edubot_visitors';
        $sql_visitors = "CREATE TABLE $table_visitors (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            site_id bigint(20) NOT NULL,
            visitor_id varchar(255) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            browser varchar(50),
            device_type varchar(20),
            operating_system varchar(50),
            first_visit datetime DEFAULT CURRENT_TIMESTAMP,
            last_activity datetime DEFAULT CURRENT_TIMESTAMP,
            visit_count int(11) DEFAULT 1,
            is_returning tinyint(1) DEFAULT 0,
            marketing_source varchar(100),
            utm_campaign varchar(100),
            utm_medium varchar(50),
            utm_source varchar(50),
            referrer_domain varchar(255),
            PRIMARY KEY (id),
            UNIQUE KEY visitor_id (visitor_id),
            KEY site_id (site_id),
            KEY ip_address (ip_address),
            KEY is_returning (is_returning),
            KEY last_activity (last_activity),
            KEY marketing_source (marketing_source)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta(array($sql_schools, $sql_applications, $sql_analytics, $sql_sessions, $sql_security, $sql_visitor_analytics, $sql_visitors));
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
        
        if (!wp_next_scheduled('edubot_analytics_cleanup')) {
            wp_schedule_event(time(), 'daily', 'edubot_analytics_cleanup');
        }
        
        // Hook the cleanup functions
        add_action('edubot_analytics_cleanup', array('EduBot_Visitor_Analytics', 'cleanup_old_analytics_static'));
    }
}
