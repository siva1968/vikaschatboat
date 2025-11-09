<?php
/**
 * Read API Configuration from Database Correctly
 */

require_once('/xamppdev/htdocs/demo/wp-load.php');

global $wpdb;

echo "<h2>API Configuration from Database</h2>";
echo "<hr>";

// Get the configuration from the correct table
$table = $wpdb->prefix . 'edubot_api_integrations';
$config = $wpdb->get_row("SELECT * FROM $table WHERE site_id = 1 ORDER BY created_at DESC LIMIT 1");

if (!$config) {
    echo "<p style='color: red;'><strong>❌ No API configuration found</strong></p>";
    die();
}

echo "<h3>WhatsApp Configuration:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Value</th></tr>";
echo "<tr><td>WhatsApp Provider</td><td>" . ($config->whatsapp_provider ? htmlspecialchars($config->whatsapp_provider) : '❌ Not set') . "</td></tr>";
echo "<tr><td>WhatsApp Token</td><td>" . ($config->whatsapp_token ? "[✅ SET - " . substr($config->whatsapp_token, 0, 20) . "...]" : "❌ Not set") . "</td></tr>";
echo "<tr><td>WhatsApp Phone ID</td><td>" . ($config->whatsapp_phone_id ? htmlspecialchars($config->whatsapp_phone_id) : "❌ Not set") . "</td></tr>";
echo "<tr><td>WhatsApp Business Account ID</td><td>" . ($config->whatsapp_business_account_id ? htmlspecialchars($config->whatsapp_business_account_id) : "❌ Not set") . "</td></tr>";
echo "<tr><td>WhatsApp Template Type</td><td>" . ($config->whatsapp_template_type ? htmlspecialchars($config->whatsapp_template_type) : "Not set (using freeform)") . "</td></tr>";
echo "<tr><td>WhatsApp Template Name</td><td>" . ($config->whatsapp_template_name ? htmlspecialchars($config->whatsapp_template_name) : "Not set") . "</td></tr>";
echo "</table>";

echo "<h3>Email Configuration:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Value</th></tr>";
echo "<tr><td>Email Provider</td><td>" . (htmlspecialchars($config->email_provider) ?? "Not set") . "</td></tr>";
echo "<tr><td>Email From Address</td><td>" . htmlspecialchars($config->email_from_address) . "</td></tr>";
echo "<tr><td>Email From Name</td><td>" . htmlspecialchars($config->email_from_name) . "</td></tr>";
echo "<tr><td>Email API Key</td><td>" . ($config->email_api_key ? "[✅ SET - " . substr($config->email_api_key, 0, 20) . "...]" : "❌ Not set") . "</td></tr>";
echo "<tr><td>SMTP Host</td><td>" . ($config->smtp_host ? htmlspecialchars($config->smtp_host) : "Not set (not using SMTP)") . "</td></tr>";
echo "<tr><td>SMTP Port</td><td>" . ($config->smtp_port ? htmlspecialchars($config->smtp_port) : "Not set") . "</td></tr>";
echo "</table>";

echo "<h3>Other Providers:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Value</th></tr>";
echo "<tr><td>SMS Provider</td><td>" . ($config->sms_provider ? htmlspecialchars($config->sms_provider) : "❌ Not set") . "</td></tr>";
echo "<tr><td>SMS API Key</td><td>" . ($config->sms_api_key ? "[✅ SET]" : "❌ Not set") . "</td></tr>";
echo "<tr><td>OpenAI API Key</td><td>" . ($config->openai_api_key ? "[✅ SET]" : "❌ Not set") . "</td></tr>";
echo "<tr><td>OpenAI Model</td><td>" . ($config->openai_model ? htmlspecialchars($config->openai_model) : "Not set") . "</td></tr>";
echo "</table>";

echo "<h3>Notification Settings (JSON):</h3>";
$notification_settings = json_decode($config->notification_settings, true);
echo "<pre>" . json_encode($notification_settings, JSON_PRETTY_PRINT) . "</pre>";

echo "<h3>Summary - What's Configured:</h3>";
echo "<ul>";
if ($config->whatsapp_provider && $config->whatsapp_token && $config->whatsapp_phone_id) {
    echo "<li style='color: green;'><strong>✅ WhatsApp:</strong> Configured (Provider: " . htmlspecialchars($config->whatsapp_provider) . ")</li>";
} else {
    echo "<li style='color: orange;'><strong>⚠️ WhatsApp:</strong> Missing some credentials";
    if (!$config->whatsapp_provider) echo " - No provider";
    if (!$config->whatsapp_token) echo " - No token";
    if (!$config->whatsapp_phone_id) echo " - No phone ID";
    echo "</li>";
}

if ($config->email_provider && $config->email_api_key && $config->email_from_address) {
    echo "<li style='color: green;'><strong>✅ Email:</strong> Configured (Provider: " . htmlspecialchars($config->email_provider) . ", From: " . htmlspecialchars($config->email_from_address) . ")</li>";
} else {
    echo "<li style='color: orange;'><strong>⚠️ Email:</strong> Incomplete";
    if (!$config->email_provider) echo " - No provider";
    if (!$config->email_api_key) echo " - No API key";
    if (!$config->email_from_address) echo " - No from address";
    echo "</li>";
}

if ($config->sms_provider && $config->sms_api_key) {
    echo "<li style='color: green;'><strong>✅ SMS:</strong> Configured (Provider: " . htmlspecialchars($config->sms_provider) . ")</li>";
} else {
    echo "<li style='color: red;'><strong>❌ SMS:</strong> Not configured</li>";
}

echo "</ul>";

?>
