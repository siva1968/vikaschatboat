<?php
/**
 * Test manual activation
 */

// Load WordPress
require_once 'D:\xamppdev\htdocs\demo\wp-load.php';

// Load the activator
require_once 'includes/class-edubot-activator.php';

// Manually call activate
echo "Starting manual activation test...\n";
EduBot_Activator::activate();
echo "Activation test complete.\n";

// Check if tables exist
global $wpdb;
$tables = $wpdb->get_results("SHOW TABLES LIKE 'wp_edubot%'");
echo "Found " . count($tables) . " EduBot tables:\n";
foreach ($tables as $table) {
    $table_obj = (object) $table;
    $table_name = array_values((array)$table_obj)[0];
    echo "  - $table_name\n";
}
?>
