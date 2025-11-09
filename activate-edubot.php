<?php
/**
 * Manual Plugin Activation Script
 * Manually triggers plugin activation to create database tables
 * 
 * Usage: Place in WordPress root and visit via browser
 * http://yourdomain.com/activate-edubot.php
 */

// Load WordPress
require_once('wp-load.php');

// Security check - admin only
if (!current_user_can('activate_plugins')) {
    wp_die('Access denied. Only administrators can activate plugins.');
}

echo '<h1>EduBot Pro - Manual Activation</h1>';
echo '<p>Triggering plugin activation to create database tables...</p>';
echo '<hr>';

// Load the activator class
require_once(WP_PLUGIN_DIR . '/edubot-pro/includes/class-edubot-activator.php');

echo '<h2>Starting Activation Process</h2>';

try {
    // Call the activator
    EduBot_Activator::activate();
    echo '<p style="color: green;"><strong>✓ Activation completed successfully!</strong></p>';
} catch (Exception $e) {
    echo '<p style="color: red;"><strong>✗ Error during activation:</strong></p>';
    echo '<p style="color: red;">' . $e->getMessage() . '</p>';
}

echo '<hr>';
echo '<h2>Verification</h2>';

global $wpdb;

// Check for enquiries table
$enquiries_table = $wpdb->prefix . 'edubot_enquiries';
$enquiries_exists = $wpdb->get_var("SHOW TABLES LIKE '{$enquiries_table}'");

if ($enquiries_exists) {
    echo '<p style="color: green;"><strong>✓ wp_edubot_enquiries table exists</strong></p>';
    
    // Show table structure
    $columns = $wpdb->get_results("DESCRIBE {$enquiries_table}");
    echo '<p>Table has ' . count($columns) . ' columns</p>';
    echo '<table border="1" cellpadding="10" style="margin-top: 20px;">';
    echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
    foreach ($columns as $col) {
        echo '<tr>';
        echo '<td>' . $col->Field . '</td>';
        echo '<td>' . $col->Type . '</td>';
        echo '<td>' . ($col->Null === 'YES' ? 'YES' : 'NO') . '</td>';
        echo '<td>' . $col->Key . '</td>';
        echo '<td>' . ($col->Default ?? 'NULL') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p style="color: red;"><strong>✗ wp_edubot_enquiries table NOT found</strong></p>';
}

// Count all EduBot tables
$edubot_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}edubot_%'");
echo '<p><strong>Total EduBot tables created: ' . count($edubot_tables) . '</strong></p>';

if (!empty($edubot_tables)) {
    echo '<ul>';
    foreach ($edubot_tables as $table) {
        echo '<li>' . $table . '</li>';
    }
    echo '</ul>';
}

echo '<hr>';
echo '<p><strong>Next Steps:</strong></p>';
echo '<ol>';
echo '<li>Delete this script from the server (it\'s a security risk)</li>';
echo '<li>Test the chatbot enquiry submission</li>';
echo '<li>Verify that enquiries are being saved</li>';
echo '</ol>';
?>
