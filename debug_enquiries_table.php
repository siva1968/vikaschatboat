<?php
// Debug script to check edubot_enquiries table status
require_once('wp-config.php');
require_once('wp-load.php');

// Include the database manager
require_once('includes/class-database-manager.php');

global $wpdb;

// Check if table exists
$enquiries_table = $wpdb->prefix . 'edubot_enquiries';
$applications_table = $wpdb->prefix . 'edubot_applications';
$enquiries_exists = $wpdb->get_var("SHOW TABLES LIKE '$enquiries_table'") == $enquiries_table;
$applications_exists = $wpdb->get_var("SHOW TABLES LIKE '$applications_table'") == $applications_table;

echo "<h2>EduBot Tables Debug</h2>";
echo "<p><strong>Enquiries Table:</strong> $enquiries_table - " . ($enquiries_exists ? 'EXISTS' : 'NOT EXISTS') . "</p>";
echo "<p><strong>Applications Table:</strong> $applications_table - " . ($applications_exists ? 'EXISTS' : 'NOT EXISTS') . "</p>";

if ($enquiries_exists) {
    // Check table structure
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $enquiries_table");
    echo "<h3>Enquiries Table Structure:</h3>";
    echo "<pre>";
    foreach ($columns as $column) {
        echo $column->Field . " - " . $column->Type . "\n";
    }
    echo "</pre>";
    
    // Count records
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $enquiries_table");
    echo "<p><strong>Enquiries Record Count:</strong> $count</p>";
    
    // Show all records if any
    if ($count > 0) {
        echo "<h3>All Enquiries Records:</h3>";
        $records = $wpdb->get_results("SELECT * FROM $enquiries_table", ARRAY_A);
        echo "<pre>";
        print_r($records);
        echo "</pre>";
    }
}

if ($applications_exists) {
    // Count records in applications table
    $app_count = $wpdb->get_var("SELECT COUNT(*) FROM $applications_table");
    echo "<p><strong>Applications Record Count:</strong> $app_count</p>";
    
    // Show all records if any
    if ($app_count > 0) {
        echo "<h3>All Applications Records:</h3>";
        $app_records = $wpdb->get_results("SELECT * FROM $applications_table", ARRAY_A);
        echo "<pre>";
        print_r($app_records);
        echo "</pre>";
    }
}

// Test the database manager
echo "<h3>Database Manager Test:</h3>";
$db_manager = new EduBot_Database_Manager();

// Test get_from_enquiries_table directly
echo "<h4>Direct get_from_enquiries_table Test:</h4>";
$enquiries_result = $db_manager->get_from_enquiries_table(0, array());
echo "<p><strong>Enquiries Result Count:</strong> " . count($enquiries_result) . "</p>";
echo "<pre>";
print_r($enquiries_result);
echo "</pre>";

// Test full get_applications
echo "<h4>Full get_applications Test:</h4>";
$result = $db_manager->get_applications(1, 20, array());
echo "<p><strong>Applications Result:</strong></p>";
echo "<pre>";
print_r($result);
echo "</pre>";

?>
