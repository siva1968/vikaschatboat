<?php

/**
 * Fired during plugin activation
 */
class EduBot_Activator {

    /**
     * Plugin activation
     * Permanent fix - proper database initialization with transactions
     */
    public static function activate() {
        global $wpdb;
        
        try {
            // Start database transaction for atomic operations
            $wpdb->query('START TRANSACTION');
            
            // Initialize database with proper schema and dependency order
            // This replaces the old create_tables() - we only create tables once
            $db_result = self::initialize_database();
            
            // Run migrations to update existing table schemas
            $migrations = self::run_migrations();
            
            // Set default options
            self::set_default_options();
            
            // Auto-migrate API settings from WordPress options to table if needed
            if (class_exists('EduBot_API_Migration')) {
                if (EduBot_API_Migration::migration_needed()) {
                    $migration_result = EduBot_API_Migration::migrate_api_settings();
                    error_log('EduBot Activation: API Migration Result - ' . ($migration_result['success'] ? 'SUCCESS' : 'FAILED'));
                    if (!empty($migration_result['migrated_fields'])) {
                        error_log('EduBot Activation: Migrated ' . count($migration_result['migrated_fields']) . ' API settings to database table');
                    }
                }
            }
            
            // Schedule WP-Cron events
            self::schedule_events();
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            // Log activation using secure Logger
            if (function_exists('EduBot_Logger')) {
                EduBot_Logger::info('EduBot Pro activated successfully', array(
                    'version' => EDUBOT_PRO_VERSION,
                    'tables_created' => count($db_result['created']),
                    'has_errors' => !empty($db_result['errors']),
                    'migrations_run' => count($migrations),
                ));
                
                if (!empty($db_result['errors'])) {
                    EduBot_Logger::warning('EduBot Pro activation warnings', array(
                        'error_count' => count($db_result['errors']),
                    ));
                }
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $wpdb->query('ROLLBACK');
            
            // Log error using secure Logger
            if (function_exists('EduBot_Logger')) {
                EduBot_Logger::critical('EduBot Pro activation error', array(
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ));
            }
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

            // 9. Applications (For storing enquiry applications)
            $applications = $wpdb->prefix . 'edubot_applications';
            if (!self::table_exists($applications)) {
                $sql = self::sql_applications();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "applications: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'applications';
                }
            }

            // 10. School Configs (Stores school settings and configuration)
            $school_configs = $wpdb->prefix . 'edubot_school_configs';
            if (!self::table_exists($school_configs)) {
                $sql = self::sql_school_configs();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "school_configs: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'school_configs';
                }
            }

            // 11. Visitor Analytics (Tracks analytics events, UTM data, page views)
            $visitor_analytics = $wpdb->prefix . 'edubot_visitor_analytics';
            if (!self::table_exists($visitor_analytics)) {
                $sql = self::sql_visitor_analytics();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "visitor_analytics: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'visitor_analytics';
                }
            }

            // 12. Visitors (Tracks visitor IP, user agent, first/last visit)
            $visitors = $wpdb->prefix . 'edubot_visitors';
            if (!self::table_exists($visitors)) {
                $sql = self::sql_visitors();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "visitors: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'visitors';
                }
            }

            // 13. MCB Settings (MyClassBoard integration configuration)
            $mcb_settings = $wpdb->prefix . 'edubot_mcb_settings';
            if (!self::table_exists($mcb_settings)) {
                $sql = self::sql_mcb_settings();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "mcb_settings: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'mcb_settings';
                }
            }

            // 14. MCB Sync Log (MyClassBoard synchronization tracking)
            $mcb_sync_log = $wpdb->prefix . 'edubot_mcb_sync_log';
            if (!self::table_exists($mcb_sync_log)) {
                $sql = self::sql_mcb_sync_log();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "mcb_sync_log: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'mcb_sync_log';
                }
            }

            // 15. API Integrations (WhatsApp, Email, SMS, OpenAI configurations)
            $api_integrations = $wpdb->prefix . 'edubot_api_integrations';
            if (!self::table_exists($api_integrations)) {
                $sql = self::sql_api_integrations();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "api_integrations: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'api_integrations';
                }
            }

            // 16. WhatsApp Ad Campaigns (Campaign management for ads)
            $ad_campaigns = $wpdb->prefix . 'edubot_ad_campaigns';
            if (!self::table_exists($ad_campaigns)) {
                $sql = self::sql_ad_campaigns();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "ad_campaigns: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'ad_campaigns';
                }
            }

            // 17. WhatsApp Sessions (User session tracking for WhatsApp)
            $whatsapp_sessions = $wpdb->prefix . 'edubot_whatsapp_sessions';
            if (!self::table_exists($whatsapp_sessions)) {
                $sql = self::sql_whatsapp_sessions();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "whatsapp_sessions: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'whatsapp_sessions';
                }
            }

            // 18. Contacts (Contact management for WhatsApp)
            $contacts = $wpdb->prefix . 'edubot_contacts';
            if (!self::table_exists($contacts)) {
                $sql = self::sql_contacts();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "contacts: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'contacts';
                }
            }

            // 19. WhatsApp Messages (Message history for conversations)
            $whatsapp_messages = $wpdb->prefix . 'edubot_whatsapp_messages';
            if (!self::table_exists($whatsapp_messages)) {
                $sql = self::sql_whatsapp_messages();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "whatsapp_messages: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'whatsapp_messages';
                }
            }

            // 20. Ad Link Metadata (Link analytics for ad campaigns)
            $ad_link_metadata = $wpdb->prefix . 'edubot_ad_link_metadata';
            if (!self::table_exists($ad_link_metadata)) {
                $sql = self::sql_ad_link_metadata();
                if ($wpdb->query($sql) === false) {
                    $errors[] = "ad_link_metadata: " . $wpdb->last_error;
                } else {
                    $tables_created[] = 'ad_link_metadata';
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
     * Run database migrations to update existing tables
     * This handles schema changes after initial table creation
     */
    private static function run_migrations() {
        global $wpdb;
        $migrations = [];
        
        // Migration: Add missing columns to visitors table if they don't exist
        $visitors_table = $wpdb->prefix . 'edubot_visitors';
        
        if (self::table_exists($visitors_table)) {
            // Add visitor_id column if it doesn't exist
            $has_visitor_id = $wpdb->get_var("SHOW COLUMNS FROM {$visitors_table} LIKE 'visitor_id'");
            if (!$has_visitor_id) {
                $wpdb->query("ALTER TABLE {$visitors_table} ADD COLUMN visitor_id varchar(255) UNIQUE NOT NULL AFTER id");
                $migrations[] = 'Added visitor_id column to visitors table';
            }
            
            // Add browser column if it doesn't exist
            $has_browser = $wpdb->get_var("SHOW COLUMNS FROM {$visitors_table} LIKE 'browser'");
            if (!$has_browser) {
                $wpdb->query("ALTER TABLE {$visitors_table} ADD COLUMN browser varchar(100) DEFAULT 'Other' AFTER user_agent");
                $migrations[] = 'Added browser column to visitors table';
            }
            
            // Add operating_system column if it doesn't exist
            $has_os = $wpdb->get_var("SHOW COLUMNS FROM {$visitors_table} LIKE 'operating_system'");
            if (!$has_os) {
                $wpdb->query("ALTER TABLE {$visitors_table} ADD COLUMN operating_system varchar(100) DEFAULT 'Other' AFTER browser");
                $migrations[] = 'Added operating_system column to visitors table';
            }
            
            // Add last_activity column if it doesn't exist
            $has_last_activity = $wpdb->get_var("SHOW COLUMNS FROM {$visitors_table} LIKE 'last_activity'");
            if (!$has_last_activity) {
                $wpdb->query("ALTER TABLE {$visitors_table} ADD COLUMN last_activity datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER operating_system");
                $migrations[] = 'Added last_activity column to visitors table';
            }
            
            // Add is_returning column if it doesn't exist
            $has_is_returning = $wpdb->get_var("SHOW COLUMNS FROM {$visitors_table} LIKE 'is_returning'");
            if (!$has_is_returning) {
                $wpdb->query("ALTER TABLE {$visitors_table} ADD COLUMN is_returning tinyint(1) DEFAULT 0 AFTER visit_count");
                $wpdb->query("ALTER TABLE {$visitors_table} ADD INDEX idx_is_returning (is_returning)");
                $migrations[] = 'Added is_returning column to visitors table';
            }
        }
        
        // Migration: Add ip_address column to visitor_analytics table if it doesn't exist
        $analytics_table = $wpdb->prefix . 'edubot_visitor_analytics';
        if (self::table_exists($analytics_table)) {
            $has_ip_address = $wpdb->get_var("SHOW COLUMNS FROM {$analytics_table} LIKE 'ip_address'");
            
            if (!$has_ip_address) {
                // Add the ip_address column after event_data
                $wpdb->query("ALTER TABLE {$analytics_table} ADD COLUMN ip_address varchar(45) NOT NULL DEFAULT '' AFTER event_data");
                $migrations[] = 'Added ip_address column to visitor_analytics table';
            }
            
            // Migration: Add user_agent column to visitor_analytics table if it doesn't exist
            $has_user_agent = $wpdb->get_var("SHOW COLUMNS FROM {$analytics_table} LIKE 'user_agent'");
            
            if (!$has_user_agent) {
                // Add the user_agent column after ip_address
                $wpdb->query("ALTER TABLE {$analytics_table} ADD COLUMN user_agent text AFTER ip_address");
                $migrations[] = 'Added user_agent column to visitor_analytics table';
            }
        }
        
        // Migration: Add MCB columns to applications table (v1.5.0+)
        $applications_table = $wpdb->prefix . 'edubot_applications';
        
        // Add enquiry_id column if it doesn't exist
        $has_enquiry_id = $wpdb->get_var("SHOW COLUMNS FROM {$applications_table} LIKE 'enquiry_id'");
        if (!$has_enquiry_id) {
            $wpdb->query("ALTER TABLE {$applications_table} ADD COLUMN enquiry_id BIGINT UNSIGNED AFTER status");
            $wpdb->query("ALTER TABLE {$applications_table} ADD INDEX idx_enquiry_id (enquiry_id)");
            $migrations[] = 'Added enquiry_id column to applications table';
        }
        
        // Add mcb_sync_status column if it doesn't exist
        $has_mcb_sync = $wpdb->get_var("SHOW COLUMNS FROM {$applications_table} LIKE 'mcb_sync_status'");
        if (!$has_mcb_sync) {
            $wpdb->query("ALTER TABLE {$applications_table} ADD COLUMN mcb_sync_status VARCHAR(50) DEFAULT 'pending' AFTER enquiry_id");
            $wpdb->query("ALTER TABLE {$applications_table} ADD INDEX idx_mcb_sync (mcb_sync_status)");
            $migrations[] = 'Added mcb_sync_status column to applications table';
        }
        
        // Add mcb_enquiry_id column if it doesn't exist
        $has_mcb_enquiry_id = $wpdb->get_var("SHOW COLUMNS FROM {$applications_table} LIKE 'mcb_enquiry_id'");
        if (!$has_mcb_enquiry_id) {
            $wpdb->query("ALTER TABLE {$applications_table} ADD COLUMN mcb_enquiry_id VARCHAR(100) AFTER mcb_sync_status");
            $migrations[] = 'Added mcb_enquiry_id column to applications table';
        }
        
        // Migration: Add platform_source column to whatsapp_sessions table (v1.6.0+)
        // This column stores which ad platform (Facebook, Instagram, Google, etc) the lead came from
        $sessions_table = $wpdb->prefix . 'edubot_whatsapp_sessions';
        
        if (self::table_exists($sessions_table)) {
            $has_platform_source = $wpdb->get_var("SHOW COLUMNS FROM {$sessions_table} LIKE 'platform_source'");
            
            if (!$has_platform_source) {
                // Add platform_source column after campaign_id
                $wpdb->query("ALTER TABLE {$sessions_table} ADD COLUMN platform_source VARCHAR(50) DEFAULT 'unknown' AFTER campaign_id");
                // Add index for faster analytics queries
                $wpdb->query("ALTER TABLE {$sessions_table} ADD INDEX idx_platform_source (platform_source)");
                $migrations[] = 'Added platform_source column and index to whatsapp_sessions table';
            }
        }
        
        // Migration: Create campaign_tracking table for multi-platform support (v1.7.0+)
        // This table maps campaigns to phones with unique tracking codes for simultaneous campaigns
        $campaign_tracking_table = $wpdb->prefix . 'edubot_campaign_tracking';
        
        if (!self::table_exists($campaign_tracking_table)) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS {$campaign_tracking_table} (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                campaign_id BIGINT UNSIGNED NOT NULL,
                phone VARCHAR(20) NOT NULL,
                platform VARCHAR(50) NOT NULL,
                utm_source VARCHAR(50),
                utm_medium VARCHAR(50),
                utm_campaign VARCHAR(255),
                tracking_code VARCHAR(100) UNIQUE NOT NULL,
                status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
                first_message_at TIMESTAMP NULL,
                last_message_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_phone_status (phone, status),
                INDEX idx_tracking_code (tracking_code),
                INDEX idx_campaign_id (campaign_id),
                INDEX idx_platform (platform),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
            
            $wpdb->query($sql);
            $migrations[] = 'Created campaign_tracking table for multi-platform support';
        }
        
        return $migrations;
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
     * FIXED: Reduced key length to fit MySQL 3072 byte limit
     */
    private static function sql_enquiries() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_enquiries';
        $charset_collate = $wpdb->get_charset_collate();
        return "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            enquiry_number VARCHAR(50),
            student_name VARCHAR(255),
            date_of_birth DATE,
            grade VARCHAR(50),
            board VARCHAR(50),
            academic_year VARCHAR(20),
            parent_name VARCHAR(255),
            email VARCHAR(100),
            phone VARCHAR(20),
            mother_name VARCHAR(255),
            mother_phone VARCHAR(20),
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
            source VARCHAR(100),
            status VARCHAR(50) DEFAULT 'pending',
            conversion_value DECIMAL(10,2),
            notes LONGTEXT,
            mcb_sync_status VARCHAR(50),
            mcb_enquiry_id VARCHAR(100),
            mcb_query_code VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_enquiry_number (enquiry_number),
            KEY idx_email (email),
            KEY idx_phone (phone),
            KEY idx_status (status),
            KEY idx_source (source),
            KEY idx_created (created_at),
            KEY idx_status_created (status, created_at),
            KEY idx_student (student_name(100)),
            KEY idx_utm_tracking (gclid, fbclid),
            KEY idx_mcb_sync (mcb_sync_status)
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
     * SQL: Applications table (For storing enquiry applications)
     */
    private static function sql_applications() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';
        $charset_collate = $wpdb->get_charset_collate();
        return "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            site_id BIGINT(20) NOT NULL,
            application_number VARCHAR(50) NOT NULL,
            student_data LONGTEXT NOT NULL,
            custom_fields_data LONGTEXT,
            conversation_log LONGTEXT,
            status VARCHAR(50) DEFAULT 'pending',
            enquiry_id BIGINT UNSIGNED,
            mcb_sync_status VARCHAR(50) DEFAULT 'pending',
            mcb_enquiry_id VARCHAR(100),
            source VARCHAR(50) DEFAULT 'chatbot',
            ip_address VARCHAR(45),
            user_agent TEXT,
            utm_data LONGTEXT,
            gclid VARCHAR(100),
            fbclid VARCHAR(100),
            click_id_data LONGTEXT,
            whatsapp_sent TINYINT(1) DEFAULT 0,
            email_sent TINYINT(1) DEFAULT 0,
            sms_sent TINYINT(1) DEFAULT 0,
            follow_up_scheduled DATETIME,
            assigned_to BIGINT(20),
            priority VARCHAR(20) DEFAULT 'normal',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY application_number (application_number),
            KEY site_id (site_id),
            KEY status (status),
            KEY enquiry_id (enquiry_id),
            KEY mcb_sync (mcb_sync_status),
            KEY created_at (created_at),
            KEY idx_site_status (site_id, status),
            KEY idx_site_created (site_id, created_at),
            KEY idx_status_created (status, created_at),
            KEY idx_assigned (assigned_to, status),
            KEY idx_priority (priority, created_at)
        ) $charset_collate;";
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
                'gclid' => 'varchar(100)',
                'fbclid' => 'varchar(100)',
                'click_id_data' => 'longtext',
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
        
        // Log migration using secure Logger
        if (function_exists('EduBot_Logger')) {
            EduBot_Logger::info('EduBot Pro database migrated', array(
                'from_version' => $from_version,
                'to_version' => EDUBOT_PRO_DB_VERSION,
            ));
        }
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
            
            // Log table creation using secure Logger
            if (function_exists('EduBot_Logger')) {
                EduBot_Logger::info('EduBot enquiries table created', array(
                    'table_name' => $table_name,
                ));
            }
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
                    
                    // Log column addition using secure Logger
                    if (function_exists('EduBot_Logger')) {
                        EduBot_Logger::debug('EduBot column added to enquiries table', array(
                            'column_name' => $column_name,
                            'table_name' => $table_name,
                        ));
                    }
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
                'email_provider' => 'wordpress',  // Default to WordPress mail
                'email_enabled' => true,  // Enable email notifications by default
                'whatsapp_provider' => 'meta',  // Set WhatsApp provider to Meta by default
                'whatsapp_enabled' => true,  // Enable WhatsApp notifications by default
                'sms_enabled' => false,
                'admin_notifications' => true,  // Enable admin notifications by default
                'admin_email' => get_option('admin_email', 'admin@example.com'),  // Set admin email
                'admin_phone' => '',  // Will be set by admin
                'parent_notifications' => true  // Enable parent notifications by default
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
        
        // PERMANENT FIX: Initialize API Integrations table with default configuration
        // This ensures notifications are properly configured on fresh installation
        $table_api_integrations = $wpdb->prefix . 'edubot_api_integrations';
        
        // Check if API integrations record already exists for this site
        $existing_api_config = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table_api_integrations} WHERE site_id = %d",
            $site_id
        ));
        
        if (!$existing_api_config) {
            // Default notification settings stored in api_integrations table
            $default_notification_settings = array(
                'whatsapp_parent_notifications' => true,
                'whatsapp_school_notifications' => true,
                'email_notifications' => true,
                'sms_notifications' => false
            );
            
            $wpdb->insert(
                $table_api_integrations,
                array(
                    'site_id' => $site_id,
                    // Email defaults
                    'email_provider' => 'wordpress',  // Use WordPress default mail
                    'email_from_address' => get_option('admin_email', 'noreply@example.com'),
                    'email_from_name' => get_bloginfo('name'),
                    'smtp_host' => '',
                    'smtp_port' => 587,
                    'smtp_username' => '',
                    'smtp_password' => '',
                    'email_api_key' => '',
                    'email_domain' => '',
                    // WhatsApp defaults (provider set to Meta, but token needs to be added)
                    'whatsapp_provider' => 'meta',
                    'whatsapp_token' => '',  // Will be filled in by admin
                    'whatsapp_phone_id' => '',  // Will be filled in by admin
                    'whatsapp_business_account_id' => '',
                    'whatsapp_template_type' => 'business_template',
                    'whatsapp_template_name' => 'admission_confirmation',
                    // SMS defaults
                    'sms_provider' => '',
                    'sms_api_key' => '',
                    'sms_sender_id' => 'EDUBOT',
                    // OpenAI defaults
                    'openai_api_key' => '',
                    'openai_model' => 'gpt-3.5-turbo',
                    // Notification settings
                    'notification_settings' => json_encode($default_notification_settings),
                    'status' => 'active'
                ),
                array(
                    '%d',  // site_id
                    '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s',  // email fields
                    '%s', '%s', '%s', '%s', '%s', '%s',  // whatsapp fields
                    '%s', '%s', '%s',  // sms fields
                    '%s', '%s',  // openai fields
                    '%s',  // notification_settings
                    '%s'   // status
                )
            );
        }
        
        // CRITICAL: Set version options to prevent migrations from running on every page load
        // If these are not set, migration checks will always return true and re-run migrations
        // This causes dbDelta() to be called repeatedly, which strips UNSIGNED modifiers
        update_option('edubot_db_version', EDUBOT_PRO_VERSION);
        update_option('edubot_enquiries_db_version', '1.3.1');
        update_option('edubot_analytics_db_version', '1.1.0');
        
        // CRITICAL FIX: Set notification enable options so notifications are triggered
        // The shortcode code checks these WordPress options via get_option()
        // If these are not set, notifications won't be triggered even if configured in database
        update_option('edubot_email_notifications', 1);          // 1 = enabled
        update_option('edubot_whatsapp_notifications', 1);       // 1 = enabled (defaults to 0!)
        update_option('edubot_school_whatsapp_notifications', 1); // 1 = enabled
        
        // AI/OpenAI Configuration Options - MISSING FIELDS FIX
        update_option('edubot_openai_api_key', '');               // OpenAI API key (encrypted)
        update_option('edubot_openai_model', 'gpt-3.5-turbo');   // Default AI model
        update_option('edubot_ai_provider', 'openai');           // AI provider (openai/anthropic)
        update_option('edubot_ai_temperature', '0.7');           // AI response temperature
        update_option('edubot_ai_max_tokens', '500');            // Max tokens per response
        
        // Email Service Configuration - MISSING FIELDS FIX
        update_option('edubot_email_service', 'wordpress');      // Email service provider
        update_option('edubot_email_from_address', get_option('admin_email', 'noreply@example.com'));
        update_option('edubot_email_from_name', get_bloginfo('name'));
        update_option('edubot_smtp_host', '');                   // SMTP host
        update_option('edubot_smtp_port', '587');                // SMTP port
        update_option('edubot_smtp_username', '');               // SMTP username
        update_option('edubot_smtp_password', '');               // SMTP password (encrypted)
        update_option('edubot_email_api_key', '');               // Email API key (encrypted)
        update_option('edubot_email_domain', '');                // Email domain
        
        // WhatsApp Configuration - MISSING FIELDS FIX
        update_option('edubot_whatsapp_provider', 'meta');       // WhatsApp provider
        update_option('edubot_whatsapp_token', '');              // WhatsApp token (encrypted)
        update_option('edubot_whatsapp_phone_id', '');           // WhatsApp Phone Number ID
        update_option('edubot_whatsapp_business_account_id', ''); // WhatsApp Business Account ID
        update_option('edubot_whatsapp_template_type', 'freeform'); // Template type
        update_option('edubot_whatsapp_template_name', 'admission_confirmation'); // Template name
        update_option('edubot_whatsapp_template_namespace', '');  // Template namespace
        update_option('edubot_whatsapp_template_language', 'en'); // Template language
        update_option('edubot_whatsapp_use_templates', '0');     // Use business templates
        
        // SMS Configuration - MISSING FIELDS FIX
        update_option('edubot_sms_provider', '');                // SMS provider
        update_option('edubot_sms_api_key', '');                 // SMS API key (encrypted)
        update_option('edubot_sms_sender_id', 'EDUBOT');         // SMS sender ID
        
        // School Configuration - MISSING FIELDS FIX
        update_option('edubot_school_name', get_bloginfo('name')); // School name
        update_option('edubot_school_email', get_option('admin_email')); // School contact email
        update_option('edubot_school_phone', '');                // School contact phone
        update_option('edubot_school_notifications', 1);        // School notifications enabled
        
        // Form and Academic Configuration - MISSING FIELDS FIX
        update_option('edubot_academic_year', '2025-26');        // Current academic year
        update_option('edubot_boards', json_encode(array('CBSE', 'Cambridge', 'State Board'))); // Available boards
        update_option('edubot_grades', json_encode(array('Pre-K', 'K', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'))); // Available grades
        
        // Security and Analytics - MISSING FIELDS FIX
        update_option('edubot_security_enabled', 1);             // Security features enabled
        update_option('edubot_analytics_enabled', 1);            // Analytics tracking enabled
        update_option('edubot_visitor_tracking', 1);             // Visitor tracking enabled
        
        // Chatbot Behavior - MISSING FIELDS FIX
        update_option('edubot_welcome_message', 'Hello! 👋 Welcome to our school admission process. I\'m here to help you with your application.');
        update_option('edubot_language', 'en');                  // Chatbot language
        update_option('edubot_response_style', 'friendly');      // Response style
        update_option('edubot_max_retries', '3');                // Max AI retries
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

    /**
     * SQL: School Configs table (Stores school configuration)
     */
    private static function sql_school_configs() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_school_configs';
        $charset_collate = $wpdb->get_charset_collate();
        return "CREATE TABLE IF NOT EXISTS $table (
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
    }

    /**
     * SQL: Visitor Analytics table (Tracks analytics events, UTM data, page views)
     */
    private static function sql_visitor_analytics() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_visitor_analytics';
        $charset_collate = $wpdb->get_charset_collate();
        return "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            site_id bigint(20) NOT NULL,
            visitor_id varchar(100),
            page_url varchar(500),
            page_title varchar(255),
            referrer_url varchar(500),
            utm_source varchar(100),
            utm_medium varchar(100),
            utm_campaign varchar(100),
            utm_content varchar(100),
            utm_term varchar(100),
            utm_id varchar(100),
            utm_source_platform varchar(100),
            click_id varchar(100),
            fb_pixel_id varchar(100),
            ga_client_id varchar(100),
            event_type varchar(50),
            event_data longtext,
            ip_address varchar(45) NOT NULL DEFAULT '',
            user_agent text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY site_id (site_id),
            KEY visitor_id (visitor_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";
    }

    /**
     * SQL: Visitors table (Tracks visitor IP, user agent, first/last visit)
     */
    private static function sql_visitors() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_visitors';
        $charset_collate = $wpdb->get_charset_collate();
        return "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            visitor_id varchar(255) UNIQUE NOT NULL,
            site_id bigint(20) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            browser varchar(100) DEFAULT 'Other',
            device_type varchar(50) DEFAULT 'Desktop',
            operating_system varchar(100) DEFAULT 'Other',
            first_visit datetime DEFAULT CURRENT_TIMESTAMP,
            last_visit datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_activity datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            visit_count int(11) DEFAULT 1,
            is_returning tinyint(1) DEFAULT 0,
            utm_source varchar(100),
            utm_medium varchar(100),
            utm_campaign varchar(100),
            utm_content varchar(100),
            utm_term varchar(100),
            utm_id varchar(100),
            utm_source_platform varchar(100),
            referrer_url varchar(500),
            landing_page varchar(500),
            browser_name varchar(100),
            browser_version varchar(50),
            os_name varchar(100),
            os_version varchar(50),
            country varchar(100),
            city varchar(100),
            timezone varchar(50),
            language varchar(10),
            PRIMARY KEY (id),
            UNIQUE KEY unique_visitor_id (visitor_id),
            UNIQUE KEY unique_visitor (site_id, ip_address, user_agent(100)),
            KEY site_id (site_id),
            KEY first_visit (first_visit),
            KEY last_activity (last_activity),
            KEY is_returning (is_returning),
            KEY utm_source (utm_source)
        ) $charset_collate;";
    }

    /**
     * SQL: API Integrations table (Stores WhatsApp, Email, SMS, OpenAI configurations)
     * This table stores all API provider settings including credentials (encrypted)
     */
    private static function sql_mcb_settings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'edubot_mcb_settings';
        $charset_collate = $wpdb->get_charset_collate();
        
        return "CREATE TABLE `{$table_name}` (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            site_id bigint(20) NOT NULL,
            config_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_site (site_id),
            KEY idx_updated (updated_at)
        ) $charset_collate;";
    }

    /**
     * MCB Sync Log table - Track synchronization with MyClassBoard
     */
    private static function sql_mcb_sync_log() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'edubot_mcb_sync_log';
        $charset_collate = $wpdb->get_charset_collate();
        
        return "CREATE TABLE `{$table_name}` (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            enquiry_id bigint(20) NOT NULL,
            request_data longtext DEFAULT NULL,
            response_data longtext DEFAULT NULL,
            success tinyint(1) DEFAULT 0,
            error_message text DEFAULT NULL,
            retry_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_enquiry (enquiry_id),
            KEY idx_success (success),
            KEY idx_created (created_at),
            KEY idx_retry (retry_count)
        ) $charset_collate;";
    }

    private static function sql_api_integrations() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_api_integrations';
        $charset_collate = $wpdb->get_charset_collate();
        return "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            site_id bigint(20) NOT NULL,
            
            -- WhatsApp Configuration
            whatsapp_provider varchar(50),
            whatsapp_token longtext,
            whatsapp_phone_id varchar(100),
            whatsapp_business_account_id varchar(100),
            whatsapp_template_type varchar(50),
            whatsapp_template_name varchar(255),
            
            -- Email Configuration
            email_provider varchar(50),
            email_from_address varchar(255),
            email_from_name varchar(255),
            smtp_host varchar(255),
            smtp_port int(5),
            smtp_username varchar(255),
            smtp_password longtext,
            email_api_key longtext,
            email_domain varchar(255),
            
            -- SMS Configuration
            sms_provider varchar(50),
            sms_api_key longtext,
            sms_sender_id varchar(100),
            
            -- OpenAI Configuration
            openai_api_key longtext,
            openai_model varchar(50),
            
            -- Notification Settings (stored as JSON)
            notification_settings longtext,
            
            -- Status
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            PRIMARY KEY (id),
            UNIQUE KEY site_id (site_id),
            KEY whatsapp_provider (whatsapp_provider),
            KEY email_provider (email_provider),
            KEY sms_provider (sms_provider)
        ) $charset_collate;";
    }

    /**
     * SQL: WhatsApp Ad Campaigns
     * Stores campaign information for ad-driven users
     */
    private static function sql_ad_campaigns() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_ad_campaigns';
        $charset_collate = $wpdb->get_charset_collate();
        
        return "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            source varchar(100) NOT NULL,
            grades longtext,
            whatsapp_link longtext,
            status varchar(50) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_source (source),
            KEY idx_status (status),
            KEY idx_created (created_at)
        ) $charset_collate;";
    }

    /**
     * SQL: WhatsApp Sessions
     * Tracks user sessions from WhatsApp ads
     */
    private static function sql_whatsapp_sessions() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_whatsapp_sessions';
        $charset_collate = $wpdb->get_charset_collate();
        
        return "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL UNIQUE,
            contact_id bigint(20),
            phone varchar(20) NOT NULL,
            campaign_id bigint(20),
            source varchar(100),
            campaign varchar(255),
            medium varchar(100),
            utm_source varchar(255),
            utm_medium varchar(255),
            utm_campaign varchar(255),
            state varchar(50) DEFAULT 'greeting',
            data longtext,
            ip_address varchar(45),
            user_agent longtext,
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            completed_at datetime,
            PRIMARY KEY (id),
            UNIQUE KEY idx_session_id (session_id),
            KEY idx_phone (phone),
            KEY idx_contact_id (contact_id),
            KEY idx_campaign_id (campaign_id),
            KEY idx_source (source),
            KEY idx_completed (completed_at),
            KEY idx_created (started_at)
        ) $charset_collate;";
    }

    /**
     * SQL: Contacts
     * Contact management for WhatsApp users
     */
    private static function sql_contacts() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_contacts';
        $charset_collate = $wpdb->get_charset_collate();
        
        return "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            phone varchar(20) NOT NULL UNIQUE,
            name varchar(255),
            email varchar(255),
            source varchar(100),
            status varchar(50) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_contacted_at datetime,
            PRIMARY KEY (id),
            UNIQUE KEY idx_phone (phone),
            KEY idx_email (email),
            KEY idx_source (source),
            KEY idx_created (created_at)
        ) $charset_collate;";
    }

    /**
     * SQL: WhatsApp Messages
     * Message history for conversations
     */
    private static function sql_whatsapp_messages() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_whatsapp_messages';
        $charset_collate = $wpdb->get_charset_collate();
        
        return "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            sender varchar(50) NOT NULL,
            message longtext NOT NULL,
            message_id varchar(255),
            delivery_status varchar(50),
            delivery_timestamp datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_session_id (session_id),
            KEY idx_sender (sender),
            KEY idx_created (created_at),
            KEY idx_message_id (message_id)
        ) $charset_collate;";
    }

    /**
     * SQL: Ad Link Metadata
     * Link analytics for ad campaigns
     */
    private static function sql_ad_link_metadata() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_ad_link_metadata';
        $charset_collate = $wpdb->get_charset_collate();
        
        return "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source varchar(100),
            campaign varchar(255),
            medium varchar(100),
            content varchar(255),
            grades longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_source (source),
            KEY idx_campaign (campaign),
            KEY idx_created (created_at)
        ) $charset_collate;";
    }
}

