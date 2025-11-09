<?php
/**
 * Check WhatsApp Configuration and Status
 */

require_once('/xamppdev/htdocs/demo/wp-load.php');

global $wpdb;

echo "<h2>WhatsApp Notification Configuration Check</h2>";
echo "<hr>";

// Check notification settings from database
$school_config_table = $wpdb->prefix . 'edubot_school_configs';
$config = $wpdb->get_row("SELECT config_data FROM $school_config_table WHERE site_id = 1");

if ($config) {
    $config_data = json_decode($config->config_data, true);
    
    echo "<h3>1. Database Configuration (School Config Table):</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    echo "<tr><td>Parent Notifications</td><td>" . ($config_data['notification_settings']['parent_notifications'] ? '✅ true' : '❌ false') . "</td></tr>";
    echo "<tr><td>Admin Notifications</td><td>" . ($config_data['notification_settings']['admin_notifications'] ? '✅ true' : '❌ false') . "</td></tr>";
    echo "<tr><td>Email Enabled</td><td>" . ($config_data['notification_settings']['email_enabled'] ? '✅ true' : '❌ false') . "</td></tr>";
    echo "<tr><td>WhatsApp Enabled</td><td>" . ($config_data['notification_settings']['whatsapp_enabled'] ? '✅ true' : '❌ false') . "</td></tr>";
    echo "<tr><td>SMS Enabled</td><td>" . ($config_data['notification_settings']['sms_enabled'] ? '✅ true' : '❌ false') . "</td></tr>";
    echo "</table>";
}

// Check API integrations table
echo "<h3>2. WhatsApp API Configuration:</h3>";
$api_table = $wpdb->prefix . 'edubot_api_integrations';
$whatsapp_config = $wpdb->get_row("SELECT * FROM $api_table WHERE type = 'whatsapp' AND site_id = 1 ORDER BY created_at DESC LIMIT 1");

if ($whatsapp_config) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>Provider</td><td>" . $whatsapp_config->provider . "</td></tr>";
    echo "<tr><td>Status</td><td>" . ($whatsapp_config->status === 'active' ? '✅ Active' : '❌ ' . $whatsapp_config->status) . "</td></tr>";
    
    $settings = json_decode($whatsapp_config->settings, true);
    if ($settings) {
        echo "<tr><td>Provider Settings</td><td>";
        foreach ($settings as $key => $value) {
            if (stripos($key, 'key') !== false || stripos($key, 'token') !== false || stripos($key, 'password') !== false) {
                echo "<strong>" . htmlspecialchars($key) . ":</strong> [REDACTED]<br>";
            } else {
                echo "<strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars((string)$value) . "<br>";
            }
        }
        echo "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'><strong>❌ No WhatsApp configuration found in api_integrations table</strong></p>";
}

// Check WordPress options (legacy)
echo "<h3>3. Legacy WordPress Options:</h3>";
$whatsapp_enabled = get_option('edubot_whatsapp_notifications');
echo "<p><strong>WhatsApp Notifications Enabled (wp_options):</strong> " . ($whatsapp_enabled ? '✅ ' . $whatsapp_enabled : '❌ Disabled/Not set') . "</p>";

$whatsapp_provider = get_option('edubot_whatsapp_provider');
echo "<p><strong>WhatsApp Provider:</strong> " . ($whatsapp_provider ? htmlspecialchars($whatsapp_provider) : 'Not set') . "</p>";

$meta_token = get_option('edubot_whatsapp_meta_token');
echo "<p><strong>Meta/WhatsApp Business API Token:</strong> " . ($meta_token ? '[REDACTED - Set]' : '❌ Not set') . "</p>";

$meta_business_id = get_option('edubot_whatsapp_meta_business_id');
echo "<p><strong>Meta Business Account ID:</strong> " . ($meta_business_id ? htmlspecialchars($meta_business_id) : '❌ Not set') . "</p>";

$meta_phone_id = get_option('edubot_whatsapp_meta_phone_id');
echo "<p><strong>Meta WhatsApp Phone Number ID:</strong> " . ($meta_phone_id ? htmlspecialchars($meta_phone_id) : '❌ Not set') . "</p>";

// Check school contact info
echo "<h3>4. School Contact Information:</h3>";
if ($config) {
    $config_data = json_decode($config->config_data, true);
    $contact_info = $config_data['school_info']['contact_info'] ?? array();
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>School Phone (for WhatsApp)</td><td>" . (!empty($contact_info['phone']) ? htmlspecialchars($contact_info['phone']) : '❌ Not set') . "</td></tr>";
    echo "<tr><td>Email</td><td>" . (!empty($contact_info['email']) ? htmlspecialchars($contact_info['email']) : 'Not set') . "</td></tr>";
    echo "</table>";
}

// Check if WhatsApp was ever sent
echo "<h3>5. Recent Applications - Notification Status:</h3>";
$app_table = $wpdb->prefix . 'edubot_applications';
$recent_apps = $wpdb->get_results("SELECT id, application_number, email_sent, whatsapp_sent, sms_sent, created_at FROM $app_table ORDER BY created_at DESC LIMIT 5");

if ($recent_apps) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>App Number</th><th>Email Sent</th><th>WhatsApp Sent</th><th>SMS Sent</th><th>Created</th></tr>";
    foreach ($recent_apps as $app) {
        echo "<tr>";
        echo "<td>" . $app->application_number . "</td>";
        echo "<td>" . ($app->email_sent ? '✅ Yes' : '❌ No') . "</td>";
        echo "<td>" . ($app->whatsapp_sent ? '✅ Yes' : '❌ No') . "</td>";
        echo "<td>" . ($app->sms_sent ? '✅ Yes' : '❌ No') . "</td>";
        echo "<td>" . $app->created_at . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check debug log for WhatsApp errors
echo "<h3>6. Recent WhatsApp Debug Messages:</h3>";
echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 300px; overflow-y: auto;'>";
$log_file = 'D:\\xamppdev\\htdocs\\demo\\wp-content\\debug.log';
if (file_exists($log_file)) {
    $lines = shell_exec("powershell -Command \"Get-Content -Path '$log_file' -Tail 100 | Select-String -Pattern 'whatsapp|WhatsApp|Twilio|SMS' -Context 1\"");
    echo htmlspecialchars($lines ?: "No WhatsApp-related messages in debug log");
} else {
    echo "Debug log not found";
}
echo "</pre>";

?>
