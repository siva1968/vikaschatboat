<?php
/**
 * Check Email Provider Configuration
 */

require_once('/xamppdev/htdocs/demo/wp-load.php');

global $wpdb;

echo "<h2>Email Provider Configuration Check</h2>";
echo "<hr>";

// Check if API integrations table is being used
$table = $wpdb->prefix . 'edubot_api_integrations';
$api_config = $wpdb->get_row("SELECT * FROM $table WHERE type = 'email' AND site_id = 1 ORDER BY created_at DESC LIMIT 1");

if ($api_config) {
    echo "<h3>Email API Configuration (Database):</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>Type</td><td>" . $api_config->type . "</td></tr>";
    echo "<tr><td>Provider</td><td>" . $api_config->provider . "</td></tr>";
    echo "<tr><td>Status</td><td>" . ($api_config->status === 'active' ? '✅ Active' : '❌ ' . $api_config->status) . "</td></tr>";
    
    $settings = json_decode($api_config->settings, true);
    if ($settings) {
        echo "<tr><td>Settings Keys</td><td>";
        foreach (array_keys($settings) as $key) {
            if (stripos($key, 'key') !== false || stripos($key, 'token') !== false) {
                echo $key . ": [REDACTED]<br>";
            } else {
                echo $key . ": " . htmlspecialchars((string)$settings[$key]) . "<br>";
            }
        }
        echo "</td></tr>";
    }
    
    echo "</table>";
} else {
    echo "<p style='color: orange;'><strong>⚠️ No email configuration found in api_integrations table</strong></p>";
}

// Check WordPress options (legacy settings)
echo "<h3>Legacy WordPress Options (wp_options):</h3>";
$email_provider = get_option('edubot_email_provider');
echo "<p><strong>Email Provider Option:</strong> " . ($email_provider ? htmlspecialchars($email_provider) : "Not set") . "</p>";

$zeptomail_key = get_option('edubot_zeptomail_key');
echo "<p><strong>ZeptoMail API Key:</strong> " . ($zeptomail_key ? "[REDACTED - Set]" : "❌ Not set") . "</p>";

$zeptomail_sender = get_option('edubot_zeptomail_sender');
echo "<p><strong>ZeptoMail Sender Email:</strong> " . ($zeptomail_sender ? htmlspecialchars($zeptomail_sender) : "❌ Not set") . "</p>";

$sendgrid_key = get_option('edubot_sendgrid_key');
echo "<p><strong>SendGrid API Key:</strong> " . ($sendgrid_key ? "[REDACTED - Set]" : "❌ Not set") . "</p>";

$mailgun_key = get_option('edubot_mailgun_key');
echo "<p><strong>Mailgun API Key:</strong> " . ($mailgun_key ? "[REDACTED - Set]" : "❌ Not set") . "</p>";

// Check school config
echo "<h3>School Configuration:</h3>";
$school_config = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "edubot_school_configs WHERE site_id = 1");
if ($school_config) {
    $config_data = json_decode($school_config->config_data, true);
    echo "<p><strong>Contact Email (from school info):</strong> ";
    if (!empty($config_data['school_info']['contact_info']['email'])) {
        echo htmlspecialchars($config_data['school_info']['contact_info']['email']);
    } else {
        echo "Not set";
    }
    echo "</p>";
}

?>
