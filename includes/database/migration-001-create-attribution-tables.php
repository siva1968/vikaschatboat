<?php
/**
 * Database Migration: Create Attribution Tables
 * 
 * Creates 5 new tables for multi-touch attribution tracking:
 * 1. wp_edubot_attribution_sessions - Main session tracking
 * 2. wp_edubot_attribution_touchpoints - Individual touchpoint records
 * 3. wp_edubot_attribution_journeys - Full journey analysis
 * 4. wp_edubot_api_logs - Conversion API request/response logs
 * 5. wp_edubot_report_schedules - Email report scheduling
 * 
 * @since 1.3.3
 * @package EduBot_Pro
 */

global $wpdb;

$charset_collate = $wpdb->get_charset_collate();
$table_prefix = $wpdb->prefix;

// Track SQL statements for logging
$sql_statements = [];
$errors = [];

// ============================================================================
// TABLE 1: Attribution Sessions
// ============================================================================

$table_name_sessions = $table_prefix . 'edubot_attribution_sessions';
$sql = "CREATE TABLE IF NOT EXISTS $table_name_sessions (
  session_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  enquiry_id BIGINT NOT NULL,
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
    REFERENCES {$table_prefix}edubot_enquiries(id) ON DELETE CASCADE
) $charset_collate;";

$sql_statements[] = $sql;

// ============================================================================
// TABLE 2: Attribution Touchpoints
// ============================================================================

$table_name_touchpoints = $table_prefix . 'edubot_attribution_touchpoints';
$sql = "CREATE TABLE IF NOT EXISTS $table_name_touchpoints (
  touchpoint_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  session_id BIGINT NOT NULL,
  enquiry_id BIGINT NOT NULL,
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
    REFERENCES $table_name_sessions(session_id) ON DELETE CASCADE,
  CONSTRAINT fk_touchpoints_enquiry FOREIGN KEY (enquiry_id) 
    REFERENCES {$table_prefix}edubot_enquiries(id) ON DELETE CASCADE
) $charset_collate;";

$sql_statements[] = $sql;

// ============================================================================
// TABLE 3: Attribution Journeys
// ============================================================================

$table_name_journeys = $table_prefix . 'edubot_attribution_journeys';
$sql = "CREATE TABLE IF NOT EXISTS $table_name_journeys (
  journey_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  enquiry_id BIGINT NOT NULL,
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
    REFERENCES {$table_prefix}edubot_enquiries(id) ON DELETE CASCADE
) $charset_collate;";

$sql_statements[] = $sql;

// ============================================================================
// TABLE 4: API Logs
// ============================================================================

$table_name_api_logs = $table_prefix . 'edubot_api_logs';
$sql = "CREATE TABLE IF NOT EXISTS $table_name_api_logs (
  log_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  enquiry_id BIGINT,
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
    REFERENCES {$table_prefix}edubot_enquiries(id) ON DELETE SET NULL
) $charset_collate;";

$sql_statements[] = $sql;

// ============================================================================
// TABLE 5: Report Schedules
// ============================================================================

$table_name_schedules = $table_prefix . 'edubot_report_schedules';
$sql = "CREATE TABLE IF NOT EXISTS $table_name_schedules (
  schedule_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  report_type VARCHAR(50),
  recipient_email VARCHAR(255),
  recipient_name VARCHAR(100),
  include_dashboard BOOLEAN DEFAULT TRUE,
  include_sources BOOLEAN DEFAULT TRUE,
  include_campaigns BOOLEAN DEFAULT TRUE,
  include_attribution BOOLEAN DEFAULT TRUE,
  frequency VARCHAR(20),
  day_of_week INT,
  time_of_day TIME,
  timezone VARCHAR(50),
  enabled BOOLEAN DEFAULT TRUE,
  last_sent DATETIME,
  next_send DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_enabled (enabled),
  KEY idx_next_send (next_send),
  KEY idx_frequency (frequency),
  KEY idx_email (recipient_email)
) $charset_collate;";

$sql_statements[] = $sql;

// ============================================================================
// EXECUTE MIGRATIONS
// ============================================================================

// Require WordPress
require_once(ABSPATH . 'wp-load.php');

$migration_log = [];
$migration_log[] = "=== EduBot Pro Attribution Tables Migration ===";
$migration_log[] = "Date: " . current_time('mysql');
$migration_log[] = "Version: 1.3.3";
$migration_log[] = "";

// Execute each SQL statement
foreach ($sql_statements as $index => $statement) {
    $migration_log[] = "Executing statement " . ($index + 1) . "...";
    
    if ($wpdb->query($statement)) {
        $migration_log[] = "✓ Success: Table created/verified";
    } else {
        $migration_log[] = "✗ Error: " . $wpdb->last_error;
        $errors[] = $wpdb->last_error;
    }
}

// Add indexes if tables exist
$migration_log[] = "";
$migration_log[] = "Adding additional indexes...";

// Verify tables were created
$tables_to_check = [
    $table_name_sessions,
    $table_name_touchpoints,
    $table_name_journeys,
    $table_name_api_logs,
    $table_name_schedules
];

foreach ($tables_to_check as $table) {
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($table_exists) {
        $migration_log[] = "✓ Table verified: $table";
    } else {
        $migration_log[] = "✗ Table missing: $table";
        $errors[] = "Table not created: $table";
    }
}

// ============================================================================
// SAVE MIGRATION LOG
// ============================================================================

$log_file = WP_CONTENT_DIR . '/uploads/edubot-migrations.log';
$log_content = implode("\n", $migration_log) . "\n";

// Append to existing log
if (file_exists($log_file)) {
    file_put_contents($log_file, "\n\n" . $log_content, FILE_APPEND);
} else {
    file_put_contents($log_file, $log_content);
}

// ============================================================================
// RETURN RESULTS
// ============================================================================

return [
    'success' => count($errors) === 0,
    'tables_created' => 5,
    'errors' => $errors,
    'log' => $migration_log,
    'log_file' => $log_file
];
?>
