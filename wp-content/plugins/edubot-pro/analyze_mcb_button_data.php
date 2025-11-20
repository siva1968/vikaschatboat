<?php
/**
 * Check MCB Preview Button Data Source
 * 
 * Directly queries the database to see what data is available for the preview button
 */

// Load WordPress
require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== MCB Preview Button Data Source Analysis ===\n\n";

global $wpdb;
$applications_table = $wpdb->prefix . 'edubot_applications';

// Get table structure
echo "📋 Applications Table Structure:\n";
$columns = $wpdb->get_results("DESCRIBE {$applications_table}");

foreach ($columns as $column) {
    echo "   - {$column->Field} ({$column->Type})\n";
}

// Get sample data
echo "\n📊 Sample Applications Data:\n";
$apps = $wpdb->get_results("SELECT * FROM {$applications_table} LIMIT 3");

if (empty($apps)) {
    echo "❌ No applications found\n";
} else {
    foreach ($apps as $index => $app) {
        echo "\n   Application #" . ($index + 1) . ":\n";
        $app_array = (array) $app;
        foreach ($app_array as $key => $value) {
            $display_value = is_null($value) ? 'NULL' : (strlen($value) > 50 ? substr($value, 0, 47) . '...' : $value);
            echo "      - {$key}: {$display_value}\n";
        }
    }
}

// Check how apply_filters passes the data
echo "\n🔍 Testing Filter Hook:\n";

// Simulate what the view does
if (!empty($apps)) {
    $test_app = $apps[0];
    
    echo "\n   Original object keys: " . implode(', ', array_keys((array)$test_app)) . "\n";
    echo "   Value of 'id': " . ($test_app->id ?? 'NOT SET') . "\n";
    
    // Now test the filter
    $actions = array();
    $filtered_actions = apply_filters('edubot_applications_row_actions', $actions, (array)$test_app);
    
    echo "\n   After filter:\n";
    foreach ($filtered_actions as $key => $value) {
        echo "      - $key: ";
        if (strlen($value) > 100) {
            echo substr($value, 0, 100) . "...\n";
        } else {
            echo $value . "\n";
        }
        
        // Extract data-enquiry-id if present
        if (preg_match('/data-enquiry-id="(\d+)"/', $value, $matches)) {
            echo "         ↳ data-enquiry-id value: " . $matches[1] . "\n";
        }
    }
}

echo "\n=== Analysis Complete ===\n";
?>
