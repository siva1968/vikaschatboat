<?php
/**
 * Fix missing columns in enquiries table
 */

require_once dirname(__FILE__) . '/wp-load.php';

global $wpdb;

$table = $wpdb->prefix . 'edubot_enquiries';

echo "<h1>Fix Enquiries Table - Add Missing Columns</h1>";

// Check current columns
$columns = $wpdb->get_results("DESCRIBE $table");

echo "<h2>Current Columns</h2>";
echo "<pre>";
foreach ($columns as $col) {
    echo $col->Field . " (" . $col->Type . ")\n";
}
echo "</pre>";

// List of columns that should exist (from process_final_submission)
$required_columns = array(
    'enquiry_number' => 'VARCHAR(50)',
    'student_name' => 'VARCHAR(100)',
    'date_of_birth' => 'DATE',
    'grade' => 'VARCHAR(50)',
    'board' => 'VARCHAR(50)',
    'academic_year' => 'VARCHAR(20)',
    'parent_name' => 'VARCHAR(100)',
    'email' => 'VARCHAR(100)',
    'phone' => 'VARCHAR(20)',
    'ip_address' => 'VARCHAR(45)',
    'user_agent' => 'TEXT',
    'utm_data' => 'LONGTEXT',
    'gclid' => 'VARCHAR(100)',
    'fbclid' => 'VARCHAR(100)',
    'click_id_data' => 'LONGTEXT',
    'whatsapp_sent' => 'TINYINT(1)',
    'email_sent' => 'TINYINT(1)',
    'sms_sent' => 'TINYINT(1)',
    'address' => 'TEXT',
    'gender' => 'VARCHAR(10)',
    'status' => 'VARCHAR(50)',
    'source' => 'VARCHAR(50)',
    'created_at' => 'DATETIME'
);

// Get existing columns
$existing_cols = array();
foreach ($columns as $col) {
    $existing_cols[] = $col->Field;
}

// Find missing columns
$missing_cols = array();
foreach ($required_columns as $col => $type) {
    if (!in_array($col, $existing_cols)) {
        $missing_cols[$col] = $type;
    }
}

if (empty($missing_cols)) {
    echo "<h2 style='color: green;'>✅ All required columns exist!</h2>";
} else {
    echo "<h2 style='color: red;'>❌ Missing Columns</h2>";
    echo "<p>The following columns are missing:</p>";
    echo "<ul>";
    foreach ($missing_cols as $col => $type) {
        echo "<li><code>$col</code> ($type)</li>";
    }
    echo "</ul>";
    
    echo "<h2>Adding Missing Columns...</h2>";
    
    $added = 0;
    $errors = array();
    
    foreach ($missing_cols as $col => $type) {
        $sql = "ALTER TABLE $table ADD COLUMN $col $type";
        
        if ($wpdb->query($sql) !== false) {
            echo "<p style='color: green;'>✅ Added column: <code>$col</code></p>";
            $added++;
        } else {
            echo "<p style='color: red;'>❌ Failed to add column: <code>$col</code></p>";
            echo "<p>Error: " . $wpdb->last_error . "</p>";
            $errors[] = $col;
        }
    }
    
    echo "<h2>Summary</h2>";
    echo "<p style='color: green;'><strong>✅ Added $added columns</strong></p>";
    
    if (!empty($errors)) {
        echo "<p style='color: red;'><strong>❌ Failed to add " . count($errors) . " columns:</strong></p>";
        echo "<ul>";
        foreach ($errors as $col) {
            echo "<li>$col</li>";
        }
        echo "</ul>";
    }
}

echo "<h2>Verify</h2>";
echo "<p><a href='http://localhost/demo/test_enquiry_creation.php'>Run Verification Script</a></p>";

?>
