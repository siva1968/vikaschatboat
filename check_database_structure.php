<?php
/**
 * EduBot Pro Database Structure Checker
 * 
 * This script checks if all the required columns exist in the edubot_enquiries table
 * Run this on your server to verify the database migration completed successfully
 */

// WordPress integration (place this file in your WordPress root and access via browser)
if (file_exists('./wp-config.php')) {
    require_once './wp-config.php';
    require_once './wp-includes/wp-db.php';
} else {
    echo "<h2>Error: WordPress not found</h2>";
    echo "<p>Please place this file in your WordPress root directory</p>";
    exit;
}

// Create database connection
global $wpdb;

$enquiries_table = $wpdb->prefix . 'edubot_enquiries';

echo "<h2>EduBot Pro - Database Structure Checker</h2>";
echo "<p>Checking table: <strong>{$enquiries_table}</strong></p>";

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$enquiries_table}'");

if ($table_exists != $enquiries_table) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<strong>❌ ERROR:</strong> Table '{$enquiries_table}' does not exist!<br>";
    echo "Please run the database migration first.";
    echo "</div>";
    exit;
}

echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
echo "✅ Table '{$enquiries_table}' exists";
echo "</div>";

// Get all columns
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$enquiries_table}");

// Required columns for EduBot Pro v1.3.1
$required_columns = array(
    'id' => 'Primary key',
    'enquiry_number' => 'Unique enquiry identifier',
    'student_name' => 'Student name',
    'date_of_birth' => 'Student DOB', 
    'grade' => 'Student grade/class',
    'board' => 'Education board',
    'academic_year' => 'Academic year',
    'parent_name' => 'Parent/Guardian name',
    'email' => 'Contact email',
    'phone' => 'Phone number',
    'ip_address' => 'User IP address (v1.3.0+)',
    'user_agent' => 'Browser user agent (v1.3.0+)',
    'utm_data' => 'UTM tracking data (v1.3.0+)',
    'gclid' => 'Google Ads click ID (v1.3.0+)',
    'fbclid' => 'Facebook click ID (v1.3.0+)', 
    'click_id_data' => 'Other platform click IDs (v1.3.0+)',
    'whatsapp_sent' => 'WhatsApp notification sent (v1.3.0+)',
    'email_sent' => 'Email notification sent (v1.3.0+)',
    'sms_sent' => 'SMS notification sent (v1.3.0+)',
    'address' => 'Student address',
    'gender' => 'Student gender',
    'status' => 'Application status',
    'created_at' => 'Creation timestamp',
    'updated_at' => 'Last update timestamp'
);

// Check each column
$existing_columns = array();
foreach ($columns as $column) {
    $existing_columns[$column->Field] = $column;
}

echo "<h3>Column Status Check</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Column Name</th><th>Status</th><th>Type</th><th>Description</th>";
echo "</tr>";

$missing_columns = array();
$new_columns = array();

foreach ($required_columns as $column_name => $description) {
    echo "<tr>";
    echo "<td><strong>{$column_name}</strong></td>";
    
    if (isset($existing_columns[$column_name])) {
        echo "<td style='color: green;'>✅ EXISTS</td>";
        echo "<td>{$existing_columns[$column_name]->Type}</td>";
        echo "<td>{$description}</td>";
        
        // Check if it's a new column (tracking fields)
        if (in_array($column_name, ['ip_address', 'user_agent', 'utm_data', 'gclid', 'fbclid', 'click_id_data', 'whatsapp_sent', 'email_sent', 'sms_sent'])) {
            $new_columns[] = $column_name;
        }
    } else {
        echo "<td style='color: red;'>❌ MISSING</td>";
        echo "<td>-</td>";
        echo "<td>{$description}</td>";
        $missing_columns[] = $column_name;
    }
    
    echo "</tr>";
}

echo "</table>";

// Summary
echo "<h3>Migration Status Summary</h3>";

if (empty($missing_columns)) {
    echo "<div style='color: green; padding: 15px; border: 2px solid green; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>🎉 SUCCESS!</strong> All required columns exist.<br>";
    echo "Database migration completed successfully.";
    echo "</div>";
    
    if (!empty($new_columns)) {
        echo "<div style='color: blue; padding: 10px; border: 1px solid blue; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>📊 New Tracking Features Available:</strong><br>";
        echo "• " . implode("<br>• ", $new_columns);
        echo "</div>";
    }
} else {
    echo "<div style='color: red; padding: 15px; border: 2px solid red; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>⚠️ MIGRATION INCOMPLETE!</strong><br>";
    echo "Missing columns: " . implode(', ', $missing_columns) . "<br><br>";
    echo "<strong>Action Required:</strong> Run the database migration via WordPress admin.";
    echo "</div>";
}

// Check database version
$db_version = get_option('edubot_enquiries_db_version', '1.0.0');
echo "<div style='padding: 10px; border: 1px solid #ccc; margin: 10px 0; border-radius: 5px;'>";
echo "<strong>Current Database Version:</strong> {$db_version}<br>";
echo "<strong>Required Version:</strong> 1.3.1<br>";

if (version_compare($db_version, '1.3.1', '>=')) {
    echo "<span style='color: green;'>✅ Database version is up to date</span>";
} else {
    echo "<span style='color: red;'>❌ Database version needs update</span>";
}
echo "</div>";

// Check indexes
echo "<h3>Database Indexes Check</h3>";
$indexes = $wpdb->get_results("SHOW INDEX FROM {$enquiries_table}");

$expected_indexes = array('PRIMARY', 'enquiry_number', 'idx_status', 'idx_created_at', 'idx_email', 'idx_phone');
$existing_indexes = array();

foreach ($indexes as $index) {
    $existing_indexes[] = $index->Key_name;
}

echo "<p><strong>Existing Indexes:</strong> " . implode(', ', array_unique($existing_indexes)) . "</p>";

// Sample data check
$row_count = $wpdb->get_var("SELECT COUNT(*) FROM {$enquiries_table}");
echo "<div style='padding: 10px; border: 1px solid #ccc; margin: 10px 0; border-radius: 5px;'>";
echo "<strong>Total Enquiries:</strong> {$row_count}";
echo "</div>";

if ($row_count > 0) {
    // Check if new columns have data
    $tracking_data = $wpdb->get_results("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN ip_address IS NOT NULL AND ip_address != '' THEN 1 ELSE 0 END) as with_ip,
            SUM(CASE WHEN gclid IS NOT NULL AND gclid != '' THEN 1 ELSE 0 END) as with_gclid,
            SUM(CASE WHEN fbclid IS NOT NULL AND fbclid != '' THEN 1 ELSE 0 END) as with_fbclid,
            SUM(CASE WHEN utm_data IS NOT NULL AND utm_data != '' THEN 1 ELSE 0 END) as with_utm
        FROM {$enquiries_table}
    ");
    
    if (!empty($tracking_data)) {
        $stats = $tracking_data[0];
        echo "<h4>Tracking Data Statistics</h4>";
        echo "<ul>";
        echo "<li>Enquiries with IP Address: {$stats->with_ip}/{$stats->total}</li>";
        echo "<li>Enquiries with Google Click ID: {$stats->with_gclid}/{$stats->total}</li>";
        echo "<li>Enquiries with Facebook Click ID: {$stats->with_fbclid}/{$stats->total}</li>";
        echo "<li>Enquiries with UTM Data: {$stats->with_utm}/{$stats->total}</li>";
        echo "</ul>";
    }
}

echo "<hr>";
echo "<p><small>Generated: " . date('Y-m-d H:i:s') . " | EduBot Pro Database Checker v1.3.1</small></p>";
?>
