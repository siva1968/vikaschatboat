<?php

/**
 * EduBot Visitor Analytics Migration
 * Handles database updates for existing installations
 */
class EduBot_Analytics_Migration {

    /**
     * Check and run database migrations
     */
    public static function check_and_migrate() {
        $current_version = get_option('edubot_analytics_db_version', '0.0.0');
        $target_version = '1.1.0';
        
        if (version_compare($current_version, $target_version, '<')) {
            self::migrate_to_v1_1_0();
            update_option('edubot_analytics_db_version', $target_version);
        }
    }

    /**
     * Migration to version 1.1.0 - Add visitor analytics tables
     */
    private static function migrate_to_v1_1_0() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        // Check if visitor analytics table exists
        $visitor_analytics_table = $wpdb->prefix . 'edubot_visitor_analytics';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$visitor_analytics_table}'") != $visitor_analytics_table) {
            $sql_visitor_analytics = "CREATE TABLE $visitor_analytics_table (
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

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_visitor_analytics);
        }

        // Check if visitors table exists
        $visitors_table = $wpdb->prefix . 'edubot_visitors';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$visitors_table}'") != $visitors_table) {
            $sql_visitors = "CREATE TABLE $visitors_table (
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
            dbDelta($sql_visitors);
        }

        // Set up cron job for analytics cleanup if not exists
        if (!wp_next_scheduled('edubot_analytics_cleanup')) {
            wp_schedule_event(time(), 'daily', 'edubot_analytics_cleanup');
        }

        error_log('EduBot: Visitor analytics tables migrated successfully');
    }

    /**
     * Check if migration is needed on plugin load
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
// EduBot_Analytics_Migration::init();
