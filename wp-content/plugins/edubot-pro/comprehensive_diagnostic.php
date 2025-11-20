<?php
/**
 * Comprehensive Notification System Test & Diagnostic
 */

require_once('/xamppdev/htdocs/demo/wp-load.php');

global $wpdb;

echo "<h1>🔧 Comprehensive Notification System Diagnostic</h1>";
echo "<hr>";

// ======================
// STEP 1: CHECK CONFIGURATION
// ======================
echo "<h2>STEP 1: API Configuration Status</h2>";

$table = $wpdb->prefix . 'edubot_api_integrations';
$config = $wpdb->get_row("SELECT * FROM $table WHERE site_id = 1 ORDER BY created_at DESC LIMIT 1");

if (!$config) {
    echo "<p style='color: red;'><strong>❌ CRITICAL: No API configuration found in database</strong></p>";
    die();
}

$status_summary = array();

// Email configuration
if ($config->email_provider && $config->email_api_key && $config->email_from_address) {
    echo "<p style='color: green;'><strong>✅ EMAIL:</strong> Configured</p>";
    echo "<ul>";
    echo "<li>Provider: " . htmlspecialchars($config->email_provider) . "</li>";
    echo "<li>From Address: " . htmlspecialchars($config->email_from_address) . "</li>";
    echo "<li>From Name: " . htmlspecialchars($config->email_from_name) . "</li>";
    echo "</ul>";
    $status_summary['email'] = 'configured';
} else {
    echo "<p style='color: orange;'><strong>⚠️ EMAIL:</strong> Incomplete configuration</p>";
    if (!$config->email_provider) echo "<li>Missing: Provider</li>";
    if (!$config->email_api_key) echo "<li>Missing: API Key</li>";
    if (!$config->email_from_address) echo "<li>Missing: From Address</li>";
    $status_summary['email'] = 'incomplete';
}

// WhatsApp configuration
if ($config->whatsapp_provider && $config->whatsapp_token && $config->whatsapp_phone_id) {
    echo "<p style='color: green;'><strong>✅ WHATSAPP:</strong> Configured</p>";
    echo "<ul>";
    echo "<li>Provider: " . htmlspecialchars($config->whatsapp_provider) . "</li>";
    echo "<li>Phone ID: " . htmlspecialchars($config->whatsapp_phone_id) . "</li>";
    echo "<li>Token: " . substr($config->whatsapp_token, 0, 20) . "...</li>";
    echo "</ul>";
    $status_summary['whatsapp'] = 'configured';
} else {
    echo "<p style='color: red;'><strong>❌ WHATSAPP:</strong> Not fully configured</p>";
    if (!$config->whatsapp_provider) echo "<li>Missing: Provider</li>";
    if (!$config->whatsapp_token) echo "<li>Missing: Token</li>";
    if (!$config->whatsapp_phone_id) echo "<li>Missing: Phone ID</li>";
    $status_summary['whatsapp'] = 'not_configured';
}

// SMS configuration
if ($config->sms_provider && $config->sms_api_key) {
    echo "<p style='color: green;'><strong>✅ SMS:</strong> Configured</p>";
    $status_summary['sms'] = 'configured';
} else {
    echo "<p style='color: red;'><strong>❌ SMS:</strong> Not configured</p>";
    $status_summary['sms'] = 'not_configured';
}

// ======================
// STEP 2: CHECK NOTIFICATION SETTINGS
// ======================
echo "<h2>STEP 2: Notification Settings</h2>";

$school_config_table = $wpdb->prefix . 'edubot_school_configs';
$school_config = $wpdb->get_row("SELECT config_data FROM $school_config_table WHERE site_id = 1");

$notification_settings = array();
if ($school_config) {
    $config_data = json_decode($school_config->config_data, true);
    $notification_settings = $config_data['notification_settings'] ?? array();
}

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Setting</th><th>Enabled</th></tr>";
echo "<tr><td>Email Notifications</td><td>" . ($notification_settings['email_enabled'] ? '✅ Yes' : '❌ No') . "</td></tr>";
echo "<tr><td>WhatsApp Notifications</td><td>" . ($notification_settings['whatsapp_enabled'] ? '✅ Yes' : '❌ No') . "</td></tr>";
echo "<tr><td>SMS Notifications</td><td>" . ($notification_settings['sms_enabled'] ? '✅ Yes' : '❌ No') . "</td></tr>";
echo "<tr><td>Admin Notifications</td><td>" . ($notification_settings['admin_notifications'] ? '✅ Yes' : '❌ No') . "</td></tr>";
echo "<tr><td>Parent Notifications</td><td>" . ($notification_settings['parent_notifications'] ? '✅ Yes' : '❌ No') . "</td></tr>";
echo "</table>";

// ======================
// STEP 3: CHECK MIGRATION CLASS
// ======================
echo "<h2>STEP 3: Testing API Migration Class</h2>";

if (!class_exists('EduBot_API_Migration')) {
    echo "<p style='color: red;'><strong>❌ API Migration class not found</strong></p>";
} else {
    echo "<p style='color: green;'><strong>✅ API Migration class loaded</strong></p>";
    
    $api_settings = EduBot_API_Migration::get_api_settings(1);
    
    echo "<h3>Settings Read by API Migration:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    echo "<tr><td>email_provider</td><td>" . htmlspecialchars($api_settings['email_provider']) . "</td></tr>";
    echo "<tr><td>email_from_address</td><td>" . htmlspecialchars($api_settings['email_from_address']) . "</td></tr>";
    echo "<tr><td>whatsapp_provider</td><td>" . htmlspecialchars($api_settings['whatsapp_provider']) . "</td></tr>";
    echo "<tr><td>whatsapp_phone_id</td><td>" . htmlspecialchars($api_settings['whatsapp_phone_id']) . "</td></tr>";
    echo "</table>";
}

// ======================
// STEP 4: CHECK RECENT APPLICATIONS
// ======================
echo "<h2>STEP 4: Recent Application Notification Status</h2>";

$app_table = $wpdb->prefix . 'edubot_applications';
$recent_apps = $wpdb->get_results("SELECT id, application_number, email_sent, whatsapp_sent, sms_sent, created_at FROM $app_table ORDER BY created_at DESC LIMIT 5");

if ($recent_apps) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr>";
    echo "<th>Application #</th>";
    echo "<th>Email</th>";
    echo "<th>WhatsApp</th>";
    echo "<th>SMS</th>";
    echo "<th>Created</th>";
    echo "</tr>";
    
    foreach ($recent_apps as $app) {
        echo "<tr>";
        echo "<td>" . $app->application_number . "</td>";
        echo "<td>" . ($app->email_sent ? '✅ Sent' : '❌ Not Sent') . "</td>";
        echo "<td>" . ($app->whatsapp_sent ? '✅ Sent' : '❌ Not Sent') . "</td>";
        echo "<td>" . ($app->sms_sent ? '✅ Sent' : '❌ Not Sent') . "</td>";
        echo "<td>" . $app->created_at . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No applications found</p>";
}

// ======================
// STEP 5: FINAL READINESS ASSESSMENT
// ======================
echo "<h2>STEP 5: System Readiness Assessment</h2>";

$ready_for = array();
$issues = array();

if ($status_summary['email'] === 'configured' && $notification_settings['email_enabled']) {
    $ready_for[] = "Email notifications";
} else {
    $issues[] = "Email: " . ($status_summary['email'] === 'configured' ? "Disabled in settings" : "Not configured");
}

if ($status_summary['whatsapp'] === 'configured' && $notification_settings['whatsapp_enabled']) {
    $ready_for[] = "WhatsApp notifications";
} else {
    $issues[] = "WhatsApp: " . ($status_summary['whatsapp'] === 'configured' ? "Disabled in settings" : "Not configured");
}

if ($status_summary['sms'] === 'configured' && $notification_settings['sms_enabled']) {
    $ready_for[] = "SMS notifications";
} else {
    $issues[] = "SMS: Not configured";
}

echo "<h3>✅ System Ready For:</h3>";
if (count($ready_for) > 0) {
    echo "<ul>";
    foreach ($ready_for as $item) {
        echo "<li>$item</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>⚠️ No notification methods fully configured</p>";
}

if (count($issues) > 0) {
    echo "<h3>⚠️ Issues to Resolve:</h3>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}

// ======================
// STEP 6: DEBUG LOG PREVIEW
// ======================
echo "<h2>STEP 6: Recent Debug Log (Last 15 lines with 'email' or 'whatsapp')</h2>";

echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 300px; overflow-y: auto; border: 1px solid #ccc;'>";
$log_file = 'D:\\xamppdev\\htdocs\\demo\\wp-content\\debug.log';
if (file_exists($log_file)) {
    $output = shell_exec("powershell -Command \"Get-Content -Path '$log_file' -Tail 100 | Select-String -Pattern 'email|whatsapp|Email|WhatsApp' | Select-Object -Last 15\"");
    if ($output) {
        echo htmlspecialchars($output);
    } else {
        echo "No email or WhatsApp messages in recent debug log";
    }
} else {
    echo "Debug log not found";
}
echo "</pre>";

// ======================
// FINAL SUMMARY
// ======================
echo "<h2 style='background: #e8f5e9; padding: 10px; border-radius: 5px;'>📊 FINAL STATUS</h2>";

$overall_status = "✅ READY";
if (count($issues) > 2) {
    $overall_status = "❌ NOT READY - Multiple issues";
} elseif (count($issues) > 0) {
    $overall_status = "⚠️ PARTIAL - Some issues need resolution";
}

echo "<p style='font-size: 18px; font-weight: bold;'>$overall_status</p>";

echo "<p><strong>Next Steps:</strong></p>";
if ($status_summary['email'] === 'incomplete') {
    echo "<p>1. ⚠️ <strong>Email Configuration:</strong> Configure email provider in WordPress Admin > EduBot Pro > API Integrations</p>";
}
if ($status_summary['whatsapp'] === 'not_configured') {
    echo "<p>2. ⚠️ <strong>WhatsApp Configuration:</strong> Set up Meta/Twilio API in WordPress Admin > EduBot Pro > API Integrations</p>";
}
if (count($ready_for) > 0) {
    echo "<p>✅ <strong>Working Methods:</strong> " . implode(", ", $ready_for) . "</p>";
}

?>
