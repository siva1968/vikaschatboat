<?php
/**
 * EduBot Database Setup Script
 * Manually create all required tables in WordPress database
 * 
 * Usage: Place in WordPress root and run via browser
 * http://yourdomain.com/setup-edubot-tables.php
 */

// Load WordPress
require_once('wp-load.php');

// Security check
if (!current_user_can('manage_options') && !defined('DOING_CRON')) {
    wp_die('Access denied. Admin access required.');
}

global $wpdb;

echo '<h1>EduBot Database Setup</h1>';
echo '<p>Creating all required EduBot tables...</p>';
echo '<hr>';

$tables_created = 0;
$tables_skipped = 0;
$errors = array();

// Table definitions
$tables = array(
    // 1. Enquiries (Core table)
    'enquiries' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}edubot_enquiries (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        enquiry_number VARCHAR(50) UNIQUE,
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",
    
    // 2. Visitors
    'visitors' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}edubot_visitors (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        visitor_id VARCHAR(255) UNIQUE NOT NULL,
        visitor_cookie VARCHAR(255),
        ip_address VARCHAR(45),
        device_type VARCHAR(50),
        browser VARCHAR(100),
        os VARCHAR(100),
        location VARCHAR(255),
        first_visit DATETIME,
        last_visit DATETIME,
        visit_count INT DEFAULT 1,
        referrer VARCHAR(500),
        utm_source VARCHAR(100),
        utm_medium VARCHAR(100),
        utm_campaign VARCHAR(100),
        utm_content VARCHAR(100),
        utm_term VARCHAR(100),
        enquiry_id BIGINT UNSIGNED,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_visitor_id (visitor_id),
        KEY idx_ip (ip_address),
        KEY idx_enquiry_id (enquiry_id),
        KEY idx_created (created_at),
        KEY idx_last_visit (last_visit)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",

    // 3. Attribution Journeys
    'attribution_journeys' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}edubot_attribution_journeys (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        enquiry_id BIGINT UNSIGNED NOT NULL,
        visitor_id VARCHAR(255),
        journey_stages LONGTEXT,
        first_touch VARCHAR(100),
        first_touch_timestamp DATETIME,
        last_touch VARCHAR(100),
        last_touch_timestamp DATETIME,
        multi_touch LONGTEXT,
        touchpoint_count INT DEFAULT 0,
        conversion_date DATETIME,
        conversion_value DECIMAL(10,2),
        channel_sequence LONGTEXT,
        attribution_model VARCHAR(50),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_enquiry_id (enquiry_id),
        KEY idx_visitor_id (visitor_id),
        KEY idx_conversion_date (conversion_date),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",

    // 4. Attribution Sessions
    'attribution_sessions' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}edubot_attribution_sessions (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        enquiry_id BIGINT UNSIGNED,
        visitor_id VARCHAR(255),
        session_start DATETIME,
        session_end DATETIME,
        session_duration INT,
        messages_count INT,
        enquiry_created TINYINT(1) DEFAULT 0,
        device VARCHAR(50),
        browser VARCHAR(100),
        ip_address VARCHAR(45),
        location VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_enquiry_id (enquiry_id),
        KEY idx_visitor_id (visitor_id),
        KEY idx_session_start (session_start),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",

    // 5. Attribution Touchpoints
    'attribution_touchpoints' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}edubot_attribution_touchpoints (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        enquiry_id BIGINT UNSIGNED,
        session_id BIGINT UNSIGNED,
        visitor_id VARCHAR(255),
        channel VARCHAR(100),
        interaction_type VARCHAR(100),
        interaction_data LONGTEXT,
        timestamp DATETIME,
        duration INT,
        user_action VARCHAR(255),
        system_response VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_enquiry_id (enquiry_id),
        KEY idx_session_id (session_id),
        KEY idx_visitor_id (visitor_id),
        KEY idx_channel (channel),
        KEY idx_timestamp (timestamp),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",

    // 6. Applications
    'applications' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}edubot_applications (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        enquiry_id BIGINT UNSIGNED NOT NULL,
        student_name VARCHAR(255),
        class_applied VARCHAR(50),
        email VARCHAR(255),
        phone VARCHAR(20),
        parent_name VARCHAR(255),
        school_id INT,
        submission_date DATETIME,
        status VARCHAR(50),
        document_urls LONGTEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_enquiry_id (enquiry_id),
        KEY idx_status (status),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",

    // 7. Conversions
    'conversions' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}edubot_conversions (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        enquiry_id BIGINT UNSIGNED NOT NULL,
        visitor_id VARCHAR(255),
        conversion_type VARCHAR(100),
        conversion_date DATETIME,
        conversion_value DECIMAL(10,2),
        conversion_source VARCHAR(100),
        conversion_steps INT,
        conversion_time INT,
        status VARCHAR(50),
        notes LONGTEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_enquiry_id (enquiry_id),
        KEY idx_visitor_id (visitor_id),
        KEY idx_conversion_date (conversion_date),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",

    // 8. API Integrations
    'api_integrations' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}edubot_api_integrations (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        integration_type VARCHAR(100),
        api_key LONGTEXT,
        api_secret LONGTEXT,
        access_token LONGTEXT,
        is_active TINYINT(1) DEFAULT 1,
        config_data LONGTEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_integration_type (integration_type),
        KEY idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",

    // 9. API Logs
    'api_logs' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}edubot_api_logs (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        api_name VARCHAR(100),
        request_type VARCHAR(20),
        endpoint VARCHAR(500),
        request_body LONGTEXT,
        response_code INT,
        response_body LONGTEXT,
        error_message LONGTEXT,
        timestamp DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_api_name (api_name),
        KEY idx_timestamp (timestamp),
        KEY idx_created (created_at),
        KEY idx_response_code (response_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",

    // 10. MCB Settings
    'mcb_settings' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}edubot_mcb_settings (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        enabled TINYINT(1) DEFAULT 0,
        api_key LONGTEXT,
        access_token LONGTEXT,
        api_url VARCHAR(500),
        organization_id VARCHAR(50),
        branch_id VARCHAR(50),
        sync_enabled TINYINT(1) DEFAULT 0,
        sync_new_enquiries TINYINT(1) DEFAULT 1,
        sync_updates TINYINT(1) DEFAULT 0,
        auto_sync TINYINT(1) DEFAULT 1,
        test_mode TINYINT(1) DEFAULT 0,
        timeout INT DEFAULT 65,
        retry_attempts INT DEFAULT 3,
        lead_source_mapping LONGTEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_enabled (enabled),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",

    // 11. MCB Sync Log
    'mcb_sync_log' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}edubot_mcb_sync_log (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        enquiry_id BIGINT UNSIGNED NOT NULL,
        request_data LONGTEXT,
        response_data LONGTEXT,
        success TINYINT(1) DEFAULT 0,
        error_message LONGTEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_enquiry_id (enquiry_id),
        KEY idx_success (success),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",

    // 12. School Configs
    'school_configs' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}edubot_school_configs (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        school_id INT,
        school_name VARCHAR(255),
        api_key LONGTEXT,
        custom_fields LONGTEXT,
        branding LONGTEXT,
        whatsapp_number VARCHAR(20),
        contact_email VARCHAR(255),
        timezone VARCHAR(50),
        language VARCHAR(20),
        academic_year VARCHAR(20),
        grades_offered LONGTEXT,
        fees LONGTEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_school_id (school_id),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",

    // 13. Visitor Analytics
    'visitor_analytics' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}edubot_visitor_analytics (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        date DATE,
        visitor_count INT,
        page_views INT,
        avg_session_duration DECIMAL(10,2),
        bounce_rate DECIMAL(5,2),
        conversion_rate DECIMAL(5,2),
        top_referrer VARCHAR(500),
        top_campaign VARCHAR(100),
        top_device VARCHAR(50),
        top_location VARCHAR(255),
        traffic_trend VARCHAR(20),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_date (date),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",

    // 14. Logs
    'logs' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}edubot_logs (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        log_level VARCHAR(20),
        category VARCHAR(100),
        message LONGTEXT,
        context LONGTEXT,
        user_id BIGINT,
        enquiry_id BIGINT UNSIGNED,
        timestamp DATETIME,
        file VARCHAR(255),
        line INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_log_level (log_level),
        KEY idx_category (category),
        KEY idx_enquiry_id (enquiry_id),
        KEY idx_timestamp (timestamp),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",

    // 15. Report Schedules
    'report_schedules' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}edubot_report_schedules (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        report_type VARCHAR(100),
        frequency VARCHAR(50),
        time_to_send TIME,
        recipient_email VARCHAR(255),
        metrics_included LONGTEXT,
        is_active TINYINT(1) DEFAULT 1,
        last_sent DATETIME,
        next_scheduled DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_report_type (report_type),
        KEY idx_is_active (is_active),
        KEY idx_next_scheduled (next_scheduled)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;",
);

// Create each table
foreach ($tables as $table_name => $sql) {
    $result = $wpdb->query($sql);
    
    if ($result === false) {
        echo "<p style='color: red;'><strong>Error creating {$table_name}:</strong> {$wpdb->last_error}</p>";
        $errors[] = "{$table_name}: {$wpdb->last_error}";
    } else {
        $table_full = $wpdb->prefix . 'edubot_' . $table_name;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_full}'");
        
        if ($exists) {
            echo "<p style='color: green;'><strong>✓ {$table_name}</strong> - Created successfully</p>";
            $tables_created++;
        } else {
            echo "<p style='color: orange;'><strong>⚠ {$table_name}</strong> - Table exists (skipped)</p>";
            $tables_skipped++;
        }
    }
}

echo '<hr>';
echo "<h2>Summary</h2>";
echo "<p><strong>Tables Created:</strong> {$tables_created}</p>";
echo "<p><strong>Tables Skipped:</strong> {$tables_skipped}</p>";

if (!empty($errors)) {
    echo "<p style='color: red;'><strong>Errors:</strong></p>";
    echo "<ul style='color: red;'>";
    foreach ($errors as $error) {
        echo "<li>{$error}</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: green;'><strong>✓ All tables created successfully!</strong></p>";
    echo "<p>The database is now ready for EduBot Pro.</p>";
}

echo '<hr>';
echo '<p><strong>Next Steps:</strong></p>';
echo '<ol>';
echo '<li>Delete this script from the server</li>';
echo '<li>Test enquiry submission on the chatbot</li>';
echo '<li>Verify data is being saved to the database</li>';
echo '</ol>';
?>
