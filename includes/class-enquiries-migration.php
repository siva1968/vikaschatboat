<?php

/**
 * EduBot Enquiries Migration
 * Handles database updates for enquiries table
 */
class EduBot_Enquiries_Migration {

    /**
     * Check and run database migrations for enquiries
     */
    public static function check_and_migrate() {
        $current_version = get_option('edubot_enquiries_db_version', '0.0.0');
        
        // Migration to 1.3.0 - Add tracking fields
        if (version_compare($current_version, '1.3.0', '<')) {
            self::migrate_to_v1_3_0();
            update_option('edubot_enquiries_db_version', '1.3.0');
        }
        
        // Migration to 1.3.1 - Add click ID fields
        if (version_compare($current_version, '1.3.1', '<')) {
            self::migrate_to_v1_3_1();
            update_option('edubot_enquiries_db_version', '1.3.1');
        }
    }

    /**
     * Migration to version 1.3.0 - Add tracking fields to enquiries table
     */
    private static function migrate_to_v1_3_0() {
        global $wpdb;
        
        $enquiries_table = $wpdb->prefix . 'edubot_enquiries';
        
        // Check if table exists first
        if ($wpdb->get_var("SHOW TABLES LIKE '{$enquiries_table}'") != $enquiries_table) {
            // If table doesn't exist, create it with all fields
            self::create_enquiries_table();
            return;
        }

        // Add new columns if they don't exist
        $columns_to_add = array(
            'ip_address' => "ALTER TABLE {$enquiries_table} ADD COLUMN ip_address varchar(45) NULL AFTER phone",
            'user_agent' => "ALTER TABLE {$enquiries_table} ADD COLUMN user_agent text NULL AFTER ip_address", 
            'utm_data' => "ALTER TABLE {$enquiries_table} ADD COLUMN utm_data longtext NULL AFTER user_agent",
            'gclid' => "ALTER TABLE {$enquiries_table} ADD COLUMN gclid varchar(255) NULL AFTER utm_data",
            'fbclid' => "ALTER TABLE {$enquiries_table} ADD COLUMN fbclid varchar(255) NULL AFTER gclid",
            'click_id_data' => "ALTER TABLE {$enquiries_table} ADD COLUMN click_id_data longtext NULL AFTER fbclid",
            'whatsapp_sent' => "ALTER TABLE {$enquiries_table} ADD COLUMN whatsapp_sent tinyint(1) DEFAULT 0 AFTER click_id_data",
            'email_sent' => "ALTER TABLE {$enquiries_table} ADD COLUMN email_sent tinyint(1) DEFAULT 0 AFTER whatsapp_sent",
            'sms_sent' => "ALTER TABLE {$enquiries_table} ADD COLUMN sms_sent tinyint(1) DEFAULT 0 AFTER email_sent"
        );

        foreach ($columns_to_add as $column => $sql) {
            // Check if column already exists
            $column_exists = $wpdb->get_results($wpdb->prepare(
                "SHOW COLUMNS FROM {$enquiries_table} LIKE %s",
                $column
            ));

            if (empty($column_exists)) {
                $result = $wpdb->query($sql);
                if ($result === false) {
                    error_log("EduBot Migration Error: Failed to add column {$column} - " . $wpdb->last_error);
                } else {
                    error_log("EduBot Migration: Successfully added column {$column} to enquiries table");
                }
            }
        }

        // Add indexes for better performance
        $indexes_to_add = array(
            'ip_address' => "ALTER TABLE {$enquiries_table} ADD INDEX idx_ip_address (ip_address)",
            'whatsapp_sent' => "ALTER TABLE {$enquiries_table} ADD INDEX idx_whatsapp_sent (whatsapp_sent)",
            'email_sent' => "ALTER TABLE {$enquiries_table} ADD INDEX idx_email_sent (email_sent)",
            'sms_sent' => "ALTER TABLE {$enquiries_table} ADD INDEX idx_sms_sent (sms_sent)"
        );

        foreach ($indexes_to_add as $index => $sql) {
            // Check if index already exists
            $index_exists = $wpdb->get_results($wpdb->prepare(
                "SHOW INDEX FROM {$enquiries_table} WHERE Key_name = %s",
                'idx_' . $index
            ));

            if (empty($index_exists)) {
                $result = $wpdb->query($sql);
                if ($result !== false) {
                    error_log("EduBot Migration: Successfully added index for {$index}");
                }
            }
        }
    }

    /**
     * Create enquiries table with all fields (for new installations)
     */
    private static function create_enquiries_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $enquiries_table = $wpdb->prefix . 'edubot_enquiries';

        $sql = "CREATE TABLE $enquiries_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            enquiry_number varchar(20) NOT NULL,
            student_name varchar(255) NULL,
            date_of_birth date NULL,
            grade varchar(50) NULL,
            board varchar(100) NULL,
            academic_year varchar(20) NULL,
            parent_name varchar(255) NULL,
            email varchar(255) NULL,
            phone varchar(20) NULL,
            ip_address varchar(45) NULL,
            user_agent text NULL,
            utm_data longtext NULL,
            whatsapp_sent tinyint(1) DEFAULT 0,
            email_sent tinyint(1) DEFAULT 0,
            sms_sent tinyint(1) DEFAULT 0,
            address text NULL,
            gender varchar(10) NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY enquiry_number (enquiry_number),
            KEY idx_status (status),
            KEY idx_created_at (created_at),
            KEY idx_email (email),
            KEY idx_phone (phone),
            KEY idx_ip_address (ip_address),
            KEY idx_whatsapp_sent (whatsapp_sent),
            KEY idx_email_sent (email_sent),
            KEY idx_sms_sent (sms_sent)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log("EduBot Migration: Created enquiries table with all fields");
    }

    /**
     * Migration to version 1.3.1 - Add click ID specific fields
     */
    private static function migrate_to_v1_3_1() {
        global $wpdb;
        
        $enquiries_table = $wpdb->prefix . 'edubot_enquiries';
        
        // Add click ID specific columns if they don't exist
        $columns_to_add = array(
            'gclid' => "ALTER TABLE {$enquiries_table} ADD COLUMN gclid varchar(255) NULL",
            'fbclid' => "ALTER TABLE {$enquiries_table} ADD COLUMN fbclid varchar(255) NULL",
            'click_id_data' => "ALTER TABLE {$enquiries_table} ADD COLUMN click_id_data longtext NULL"
        );

        foreach ($columns_to_add as $column => $sql) {
            // Check if column already exists
            $column_exists = $wpdb->get_results($wpdb->prepare(
                "SHOW COLUMNS FROM {$enquiries_table} LIKE %s",
                $column
            ));

            if (empty($column_exists)) {
                $result = $wpdb->query($sql);
                if ($result !== false) {
                    error_log("EduBot Migration: Added column {$column} to enquiries table");
                } else {
                    error_log("EduBot Migration: Failed to add column {$column}: " . $wpdb->last_error);
                }
            } else {
                error_log("EduBot Migration: Column {$column} already exists in enquiries table");
            }
        }

        // Add indexes for better performance
        $indexes_to_add = array(
            'idx_gclid' => "CREATE INDEX idx_gclid ON {$enquiries_table} (gclid)",
            'idx_fbclid' => "CREATE INDEX idx_fbclid ON {$enquiries_table} (fbclid)"
        );

        foreach ($indexes_to_add as $index_name => $sql) {
            // Check if index exists
            $index_exists = $wpdb->get_results($wpdb->prepare(
                "SHOW INDEX FROM {$enquiries_table} WHERE Key_name = %s",
                $index_name
            ));

            if (empty($index_exists)) {
                $result = $wpdb->query($sql);
                if ($result !== false) {
                    error_log("EduBot Migration: Added index {$index_name} to enquiries table");
                } else {
                    error_log("EduBot Migration: Failed to add index {$index_name}: " . $wpdb->last_error);
                }
            }
        }
    }

    /**
     * Initialize migration check on plugin load
     * 
     * DISABLED: Version options are now set during activation in class-edubot-activator.php
     * Migrations should only run once during activation, not on every page load
     */
    public static function init() {
        // Migration check disabled - version options are set during activation
        // This prevents the infinite migration loop that was causing FK constraint errors
    }
}

// Do NOT initialize migration check - it's handled during activation
// EduBot_Enquiries_Migration::init();
