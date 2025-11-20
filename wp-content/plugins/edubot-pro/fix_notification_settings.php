<?php
/**
 * Fix WhatsApp and SMS Notification Settings
 * Enables WhatsApp notifications in the database configuration
 */

// Load WordPress
require_once('/xampp/htdocs/demo/wp-load.php');

global $wpdb;

$table = $wpdb->prefix . 'edubot_school_configs';
$site_id = 1;

// Get current config
$config = $wpdb->get_row(
    $wpdb->prepare("SELECT id, config FROM $table WHERE site_id = %d", $site_id),
    ARRAY_A
);

if (!$config) {
    die('❌ No configuration found for site_id: ' . $site_id);
}

// Decode JSON config
$config_data = json_decode($config['config'], true);

if (!$config_data) {
    die('❌ Could not decode configuration JSON');
}

echo "<h2>Current Configuration:</h2>";
echo "<pre>";
echo "WhatsApp Enabled: " . (isset($config_data['notification_settings']['whatsapp_enabled']) ? ($config_data['notification_settings']['whatsapp_enabled'] ? 'true ✅' : 'false ❌') : 'NOT SET ❌');
echo "\n";
echo "SMS Enabled: " . (isset($config_data['notification_settings']['sms_enabled']) ? ($config_data['notification_settings']['sms_enabled'] ? 'true ✅' : 'false ❌') : 'NOT SET ❌');
echo "\n";
echo "Email Enabled: " . (isset($config_data['notification_settings']['email_enabled']) ? ($config_data['notification_settings']['email_enabled'] ? 'true ✅' : 'false ❌') : 'NOT SET ❌');
echo "\n";
echo "Admin Notifications: " . (isset($config_data['notification_settings']['admin_notifications']) ? ($config_data['notification_settings']['admin_notifications'] ? 'true ✅' : 'false ❌') : 'NOT SET ❌');
echo "\n";
echo "Parent Notifications: " . (isset($config_data['notification_settings']['parent_notifications']) ? ($config_data['notification_settings']['parent_notifications'] ? 'true ✅' : 'false ❌') : 'NOT SET ❌');
echo "</pre>";

// Update settings
$config_data['notification_settings']['whatsapp_enabled'] = true;
$config_data['notification_settings']['sms_enabled'] = false;  // SMS disabled for now
$config_data['notification_settings']['email_enabled'] = true;
$config_data['notification_settings']['admin_notifications'] = true;
$config_data['notification_settings']['parent_notifications'] = true;

// Update database
$result = $wpdb->update(
    $table,
    array(
        'config' => json_encode($config_data),
        'updated_at' => current_time('mysql')
    ),
    array('id' => $config['id']),
    array('%s', '%s'),
    array('%d')
);

if ($result === false) {
    echo "<h2>❌ UPDATE FAILED</h2>";
    echo "Error: " . $wpdb->last_error;
} else {
    echo "<h2>✅ CONFIGURATION UPDATED SUCCESSFULLY</h2>";
    
    // Verify update
    $updated_config = $wpdb->get_row(
        $wpdb->prepare("SELECT config FROM $table WHERE site_id = %d", $site_id),
        ARRAY_A
    );
    
    $updated_data = json_decode($updated_config['config'], true);
    
    echo "<h3>New Configuration:</h3>";
    echo "<pre>";
    echo "WhatsApp Enabled: " . ($updated_data['notification_settings']['whatsapp_enabled'] ? 'true ✅' : 'false ❌');
    echo "\n";
    echo "SMS Enabled: " . ($updated_data['notification_settings']['sms_enabled'] ? 'true ✅' : 'false ❌');
    echo "\n";
    echo "Email Enabled: " . ($updated_data['notification_settings']['email_enabled'] ? 'true ✅' : 'false ❌');
    echo "\n";
    echo "Admin Notifications: " . ($updated_data['notification_settings']['admin_notifications'] ? 'true ✅' : 'false ❌');
    echo "\n";
    echo "Parent Notifications: " . ($updated_data['notification_settings']['parent_notifications'] ? 'true ✅' : 'false ❌');
    echo "</pre>";
    
    echo "<h3>✅ All Notification Settings Fixed!</h3>";
    echo "<p>WhatsApp and Email notifications are now enabled.</p>";
    echo "<p><strong>Next Step:</strong> Run the diagnostic again to verify.</p>";
    echo "<p>Go to: <a href='http://localhost/demo/diagnose_notifications.php' target='_blank'>Diagnostic Tool</a></p>";
}
?>
