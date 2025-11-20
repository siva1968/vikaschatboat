<?php
/**
 * Fix Notification Settings - Enable WhatsApp
 * Updates database configuration to enable WhatsApp and Email notifications
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
    wp_die('❌ No configuration found for site_id: ' . $site_id);
}

// Decode JSON config
$config_data = json_decode($config['config_data'], true);

if (!$config_data) {
    wp_die('❌ Could not decode configuration JSON');
}

echo "<h2>Current Notification Settings:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Setting</th><th>Before</th></tr>";
echo "<tr><td>Parent Notifications</td><td>" . ($config_data['notification_settings']['parent_notifications'] ? '✅ true' : '❌ false') . "</td></tr>";
echo "<tr><td>Admin Notifications</td><td>" . ($config_data['notification_settings']['admin_notifications'] ? '✅ true' : '❌ false') . "</td></tr>";
echo "<tr><td>Email Enabled</td><td>" . ($config_data['notification_settings']['email_enabled'] ? '✅ true' : '❌ false') . "</td></tr>";
echo "<tr><td>WhatsApp Enabled</td><td>" . ($config_data['notification_settings']['whatsapp_enabled'] ? '✅ true' : '❌ false') . "</td></tr>";
echo "<tr><td>SMS Enabled</td><td>" . ($config_data['notification_settings']['sms_enabled'] ? '✅ true' : '❌ false') . "</td></tr>";
echo "</table>";

// UPDATE: Enable WhatsApp and keep Email enabled
$config_data['notification_settings']['whatsapp_enabled'] = true;
$config_data['notification_settings']['sms_enabled'] = false;
$config_data['notification_settings']['email_enabled'] = true;
$config_data['notification_settings']['admin_notifications'] = true;
$config_data['notification_settings']['parent_notifications'] = true;

// Update database using REPLACE INTO (like the school config does)
$result = $wpdb->query($wpdb->prepare(
    "REPLACE INTO $table (site_id, school_name, config_data, status) VALUES (%d, %s, %s, %s)",
    $site_id,
    $config_data['school_info']['name'] ?? 'School',
    json_encode($config_data),
    'active'
));

if ($result === false) {
    echo "<h2>❌ UPDATE FAILED</h2>";
    echo "<p>Error: " . $wpdb->last_error . "</p>";
} else {
    echo "<h2 style='color: green;'>✅ NOTIFICATION SETTINGS UPDATED SUCCESSFULLY</h2>";
    
    // Verify update
    $updated_config = $wpdb->get_row(
        $wpdb->prepare("SELECT config_data FROM $table WHERE site_id = %d", $site_id),
        ARRAY_A
    );
    
    $updated_data = json_decode($updated_config['config_data'], true);
    
    echo "<h3>New Notification Settings:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Setting</th><th>After</th></tr>";
    echo "<tr><td>Parent Notifications</td><td>" . ($updated_data['notification_settings']['parent_notifications'] ? '✅ true' : '❌ false') . "</td></tr>";
    echo "<tr><td>Admin Notifications</td><td>" . ($updated_data['notification_settings']['admin_notifications'] ? '✅ true' : '❌ false') . "</td></tr>";
    echo "<tr><td>Email Enabled</td><td>" . ($updated_data['notification_settings']['email_enabled'] ? '✅ true' : '❌ false') . "</td></tr>";
    echo "<tr><td>WhatsApp Enabled</td><td>" . ($updated_data['notification_settings']['whatsapp_enabled'] ? '✅ true' : '❌ false') . "</td></tr>";
    echo "<tr><td>SMS Enabled</td><td>" . ($updated_data['notification_settings']['sms_enabled'] ? '✅ true' : '❌ false') . "</td></tr>";
    echo "</table>";
    
    echo "<h3 style='color: green;'>✅ Notification Settings Fixed!</h3>";
    echo "<p><strong>WhatsApp notifications are now ENABLED.</strong></p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Go to: <a href='http://localhost/demo/diagnose_notifications.php' target='_blank'>Run Diagnostic Again</a></li>";
    echo "<li>Submit a test enquiry in the chatbot</li>";
    echo "<li>Check if email & WhatsApp notifications are sent</li>";
    echo "</ol>";
    echo "<p><a href='http://localhost/demo/diagnose_notifications.php' style='background: green; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Diagnostic Results</a></p>";
}
?>
