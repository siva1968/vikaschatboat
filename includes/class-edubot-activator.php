<?php

/**
 * Fired during plugin activation
 */
class EduBot_Activator {

    /**
     * Plugin activation
     * Permanent fix - proper database initialization
     */
    public static function activate() {
        // Create core tables first
        self::create_tables();
        
        // Initialize database with proper schema and dependency order
        $db_result = self::initialize_database();
        
        // Set default options
        self::set_default_options();
        
        // Schedule WP-Cron events
        self::schedule_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log activation
        error_log('✓ EduBot Pro activated successfully. Version: ' . EDUBOT_PRO_VERSION);
        if (!empty($db_result['errors'])) {
            error_log('⚠ Activation warnings: ' . implode('; ', $db_result['errors']));
        }
    }

    /**
     * Initialize database with proper table dependencies
     * PERMANENT FIX - Creates parent tables before child tables
     */
    private static function initialize_database() {
        global $wpdb;
        
        // Disable foreign key checks temporarily
        $wpdb->query('SET FOREIGN_KEY_CHECKS=0');
        
        $tables_created = [];
        $errors = [];

        try {
            // Create tables in dependency order (parents first)
            
            // 1. Enquiries (Parent - no foreign keys)
            $enquiries = $wpdb->prefix . 'edubot_enquiries';
            if (!self::table_exists($enquiries)) {
                $sql = self::sql_enquiries();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "enquiries: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'enquiries';
                }
            }

            // 2. Attribution Sessions (References enquiries)
            $sessions = $wpdb->prefix . 'edubot_attribution_sessions';
            if (!self::table_exists($sessions)) {
                $sql = self::sql_attribution_sessions();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "attribution_sessions: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'attribution_sessions';
                }
            }

            // 3. Attribution Touchpoints (References sessions + enquiries)
            $touchpoints = $wpdb->prefix . 'edubot_attribution_touchpoints';
            if (!self::table_exists($touchpoints)) {
                $sql = self::sql_attribution_touchpoints();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "attribution_touchpoints: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'attribution_touchpoints';
                }
            }

            // 4. Attribution Journeys (References enquiries)
            $journeys = $wpdb->prefix . 'edubot_attribution_journeys';
            if (!self::table_exists($journeys)) {
                $sql = self::sql_attribution_journeys();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "attribution_journeys: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'attribution_journeys';
                }
            }

            // 5. Conversions (References enquiries)
            $conversions = $wpdb->prefix . 'edubot_conversions';
            if (!self::table_exists($conversions)) {
                $sql = self::sql_conversions();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "conversions: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'conversions';
                }
            }

            // 6. API Logs (References enquiries with SET NULL)
            $api_logs = $wpdb->prefix . 'edubot_api_logs';
            if (!self::table_exists($api_logs)) {
                $sql = self::sql_api_logs();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "api_logs: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'api_logs';
                }
            }

            // 7. Report Schedules (No dependencies)
            $schedules = $wpdb->prefix . 'edubot_report_schedules';
            if (!self::table_exists($schedules)) {
                $sql = self::sql_report_schedules();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "report_schedules: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'report_schedules';
                }
            }

            // 8. Logs (No dependencies)
            $logs = $wpdb->prefix . 'edubot_logs';
            if (!self::table_exists($logs)) {
                $sql = self::sql_logs();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "logs: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'logs';
                }
            }

        } catch (Exception $e) {
            $errors[] = "Exception: " . $e->getMessage();
        }

        // Re-enable foreign key checks
        $wpdb->query('SET FOREIGN_KEY_CHECKS=1');

        return [
            'created' => $tables_created,
            'errors' => $errors
        ];
    }

    /**
     * Check if table exists
     */
    private static function table_exists($table_name) {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    }

    /**
     * SQL: Enquiries table (Parent)
     */
    private static function sql_enquiries() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_enquiries';
        return "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            enquiry_number VARCHAR(50),
            student_name VARCHAR(255),
            date_of_birth DATE,
            grade VARCHAR(50),
            board VARCHAR(50),
            academic_year VARCHAR(20),
            parent_name VARCHAR(255),
            email VARCHAR(255),
            phone VARCHAR(20),
            address TEXT,
            gender VARCHAR(10),
            ip_address VARCHAR(45),
            user_agent TEXT,
            utm_data LONGTEXT,
            gclid VARCHAR(100),
            fbclid VARCHAR(100),
            click_id_data LONGTEXT,
            whatsapp_sent TINYINT(1) DEFAULT 0,
            email_sent TINYINT(1) DEFAULT 0,
            sms_sent TINYINT(1) DEFAULT 0,
            enquiry_source VARCHAR(100),
            status VARCHAR(50) DEFAULT 'pending',
            conversion_value DECIMAL(10,2),
            notes LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_enquiry_number (enquiry_number),
            KEY idx_email (email),
            KEY idx_phone (phone),
            KEY idx_status (status),
            KEY idx_source (enquiry_source),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }

    /**
     * SQL: Attribution Sessions table
     */
    private static function sql_attribution_sessions() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_attribution_sessions';
        $enquiries = $wpdb->prefix . 'edubot_enquiries';
        return "CREATE TABLE IF NOT EXISTS $table (
            session_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            enquiry_id BIGINT UNSIGNED NOT NULL,
            user_session_key VARCHAR(100),
            first_touch_source VARCHAR(50),
            first_touch_timestamp DATETIME,
            last_touch_source VARCHAR(50),
            last_touch_timestamp DATETIME,
            total_touchpoints INT DEFAULT 1,
            attribution_model VARCHAR(20) DEFAULT 'last-click',
            journey_json LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_enquiry_id (enquiry_id),
            KEY idx_model (attribution_model),
            KEY idx_created (created_at),
            KEY idx_session_key (user_session_key),
            CONSTRAINT fk_sessions_enquiry FOREIGN KEY (enquiry_id) REFERENCES $enquiries(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }

    /**
     * SQL: Attribution Touchpoints table
     */
    private static function sql_attribution_touchpoints() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_attribution_touchpoints';
        $sessions = $wpdb->prefix . 'edubot_attribution_sessions';
        $enquiries = $wpdb->prefix . 'edubot_enquiries';
        return "CREATE TABLE IF NOT EXISTS $table (
            touchpoint_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            session_id BIGINT UNSIGNED NOT NULL,
            enquiry_id BIGINT UNSIGNED NOT NULL,
            source VARCHAR(50),
            medium VARCHAR(50),
            campaign VARCHAR(100),
            platform_click_id VARCHAR(200),
            timestamp DATETIME,
            position_in_journey INT,
            page_title VARCHAR(255),
            page_url TEXT,
            referrer VARCHAR(255),
            device_type VARCHAR(20),
            attribution_weight DECIMAL(5,2) DEFAULT 100.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_session (session_id),
            KEY idx_enquiry (enquiry_id),
            KEY idx_source (source),
            KEY idx_timestamp (timestamp),
            KEY idx_position (position_in_journey),
            CONSTRAINT fk_touchpoints_session FOREIGN KEY (session_id) REFERENCES $sessions(session_id) ON DELETE CASCADE,
            CONSTRAINT fk_touchpoints_enquiry FOREIGN KEY (enquiry_id) REFERENCES $enquiries(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }

    /**
     * SQL: Attribution Journeys table
     */
    private static function sql_attribution_journeys() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_attribution_journeys';
        $enquiries = $wpdb->prefix . 'edubot_enquiries';
        return "CREATE TABLE IF NOT EXISTS $table (
            journey_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            enquiry_id BIGINT UNSIGNED NOT NULL,
            journey_path TEXT,
            journey_length INT,
            total_time_minutes INT,
            first_touch_source VARCHAR(50),
            last_touch_source VARCHAR(50),
            conversion_value DECIMAL(10,2),
            attribution_model VARCHAR(20),
            calculated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_enquiry_journey (enquiry_id),
            KEY idx_path_length (journey_length),
            KEY idx_model (attribution_model),
            KEY idx_calculated (calculated_at),
            CONSTRAINT fk_journeys_enquiry FOREIGN KEY (enquiry_id) REFERENCES $enquiries(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }

    /**
     * SQL: Conversions table
     */
    private static function sql_conversions() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_conversions';
        $enquiries = $wpdb->prefix . 'edubot_enquiries';
        return "CREATE TABLE IF NOT EXISTS $table (
            conversion_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            enquiry_id BIGINT UNSIGNED NOT NULL,
            conversion_type VARCHAR(50),
            conversion_value DECIMAL(10,2),
            platform VARCHAR(50),
            campaign_id VARCHAR(100),
            ad_set_id VARCHAR(100),
            converted_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_enquiry (enquiry_id),
            KEY idx_type (conversion_type),
            KEY idx_platform (platform),
            KEY idx_converted (converted_at),
            CONSTRAINT fk_conversions_enquiry FOREIGN KEY (enquiry_id) REFERENCES $enquiries(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }

    /**
     * SQL: API Logs table
     */
    private static function sql_api_logs() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_api_logs';
        $enquiries = $wpdb->prefix . 'edubot_enquiries';
        return "CREATE TABLE IF NOT EXISTS $table (
            log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            enquiry_id BIGINT UNSIGNED,
            api_provider VARCHAR(50),
            request_type VARCHAR(50),
            request_payload LONGTEXT,
            response_status INT,
            response_payload LONGTEXT,
            success BOOLEAN DEFAULT FALSE,
            error_message TEXT,
            retry_count INT DEFAULT 0,
            last_retry DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_enquiry (enquiry_id),
            KEY idx_provider (api_provider),
            KEY idx_status (response_status),
            KEY idx_success (success),
            KEY idx_created (created_at),
            CONSTRAINT fk_api_logs_enquiry FOREIGN KEY (enquiry_id) REFERENCES $enquiries(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }

    /**
     * SQL: Report Schedules table
     */
    private static function sql_report_schedules() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_report_schedules';
        return "CREATE TABLE IF NOT EXISTS $table (
            schedule_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            report_type VARCHAR(50),
            recipient_email VARCHAR(255),
            frequency VARCHAR(20),
            last_sent DATETIME,
            next_send DATETIME,
            is_active BOOLEAN DEFAULT TRUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_type (report_type),
            KEY idx_email (recipient_email),
            KEY idx_active (is_active),
            KEY idx_next_send (next_send)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }

    /**
     * SQL: Logs table
     */
    private static function sql_logs() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_logs';
        return "CREATE TABLE IF NOT EXISTS $table (
            log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            level VARCHAR(20),
            message LONGTEXT,
            context LONGTEXT,
            trace TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_level (level),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
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
