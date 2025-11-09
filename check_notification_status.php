<?php
/**
 * Quick Check - Current Notification Settings
 */

// Load WordPress
require_once('/xamppdev/htdocs/demo/wp-load.php');

global $wpdb;

$table = $wpdb->prefix . 'edubot_school_configs';
$site_id = 1;

// Get current config
$config = $wpdb->get_row(
    $wpdb->prepare("SELECT id, config_data FROM $table WHERE site_id = %d", $site_id),
    ARRAY_A
);

if (!$config) {
    die('ERROR: No configuration found for site_id: ' . $site_id);
}

// Decode JSON config
$config_data = json_decode($config['config_data'], true);

if (!$config_data) {
    die('ERROR: Could not decode configuration JSON');
}

echo "CURRENT NOTIFICATION SETTINGS:\n";
echo "================================\n";
echo "Parent Notifications: " . ($config_data['notification_settings']['parent_notifications'] ? 'true' : 'false') . "\n";
echo "Admin Notifications: " . ($config_data['notification_settings']['admin_notifications'] ? 'true' : 'false') . "\n";
echo "Email Enabled: " . ($config_data['notification_settings']['email_enabled'] ? 'true' : 'false') . "\n";
echo "WhatsApp Enabled: " . ($config_data['notification_settings']['whatsapp_enabled'] ? 'true' : 'false') . "\n";
echo "SMS Enabled: " . ($config_data['notification_settings']['sms_enabled'] ? 'true' : 'false') . "\n";
echo "\nFull Notification Settings:\n";
echo print_r($config_data['notification_settings'], true);
?>
