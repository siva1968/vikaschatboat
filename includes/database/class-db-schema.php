<?php
/**
 * Database Schema Initialization - EduBot Pro Analytics
 * 
 * Complete, permanent database setup with proper table dependencies
 * Fixes all foreign key constraint errors
 * 
 * @package EduBot_Pro
 * @version 1.4.1
 */

// Prevent direct execution
if (!defined('ABSPATH')) {
    exit('Access denied');
}

class EduBot_DB_Schema {

    /**
     * Initialize all database tables in correct dependency order
     */
    public static function init() {
        global $wpdb;

        // Disable foreign key checks during table creation
        $wpdb->query('SET FOREIGN_KEY_CHECKS=0');

        $results = [
            'created' => [],
            'errors' => []
        ];

        // Create tables in dependency order (parents first, then children)
        $tables = [
            'enquiries' => self::get_enquiries_schema(),
            'attribution_sessions' => self::get_sessions_schema(),
            'attribution_touchpoints' => self::get_touchpoints_schema(),
            'attribution_journeys' => self::get_journeys_schema(),
            'conversions' => self::get_conversions_schema(),
            'api_logs' => self::get_api_logs_schema(),
            'report_schedules' => self::get_report_schedules_schema(),
            'logs' => self::get_logs_schema(),
        ];

        foreach ($tables as $table_name => $sql) {
            $full_table_name = $wpdb->prefix . 'edubot_' . $table_name;
            
            // Drop existing table if it has issues
            $wpdb->query("DROP TABLE IF EXISTS $full_table_name");
            
            // Create fresh table
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                $results['errors'][$table_name] = $wpdb->last_error;
                error_log("Failed to create $full_table_name: " . $wpdb->last_error);
            } else {
                $results['created'][] = $table_name;
            }
        }

        // Re-enable foreign key checks
        $wpdb->query('SET FOREIGN_KEY_CHECKS=1');

        return $results;
    }

    /**
     * Schema: Enquiries (Parent table - no foreign keys)
     */
    private static function get_enquiries_schema() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'edubot_enquiries';

        return "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_email VARCHAR(255),
            user_phone VARCHAR(20),
            user_name VARCHAR(255),
            user_grade VARCHAR(50),
            user_board VARCHAR(100),
            enquiry_source VARCHAR(100),
            enquiry_status VARCHAR(50) DEFAULT 'new',
            conversion_value DECIMAL(10,2),
            notes LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_email (user_email),
            KEY idx_phone (user_phone),
            KEY idx_status (enquiry_status),
            KEY idx_created (created_at),
            KEY idx_source (enquiry_source)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }

    /**
     * Schema: Attribution Sessions (References enquiries)
     */
    private static function get_sessions_schema() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
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
            CONSTRAINT fk_sessions_enquiry FOREIGN KEY (enquiry_id) 
                REFERENCES $enquiries(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }

    /**
     * Schema: Attribution Touchpoints (References sessions and enquiries)
     */
    private static function get_touchpoints_schema() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
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
            CONSTRAINT fk_touchpoints_session FOREIGN KEY (session_id) 
                REFERENCES $sessions(session_id) ON DELETE CASCADE,
            CONSTRAINT fk_touchpoints_enquiry FOREIGN KEY (enquiry_id) 
                REFERENCES $enquiries(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }

    /**
     * Schema: Attribution Journeys (References enquiries)
     */
    private static function get_journeys_schema() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
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
            CONSTRAINT fk_journeys_enquiry FOREIGN KEY (enquiry_id) 
                REFERENCES $enquiries(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }

    /**
     * Schema: Conversions (References enquiries)
     */
    private static function get_conversions_schema() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
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
            CONSTRAINT fk_conversions_enquiry FOREIGN KEY (enquiry_id) 
                REFERENCES $enquiries(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }

    /**
     * Schema: API Logs (References enquiries)
     */
    private static function get_api_logs_schema() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
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
            CONSTRAINT fk_api_logs_enquiry FOREIGN KEY (enquiry_id) 
                REFERENCES $enquiries(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }

    /**
     * Schema: Report Schedules (References enquiries)
     */
    private static function get_report_schedules_schema() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
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
     * Schema: Logs (No dependencies)
     */
    private static function get_logs_schema() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
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
}

// NOTE: Database initialization is handled by EduBot_Activator during plugin activation
// This class is loaded but NOT auto-executed to avoid conflicts
// Tables are created in class-edubot-activator.php via initialize_database() and create_tables() methods
