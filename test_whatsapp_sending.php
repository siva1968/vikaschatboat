<?php
/**
 * Test WhatsApp Notification Sending
 */

require_once('/xamppdev/htdocs/demo/wp-load.php');

global $wpdb;

echo "<h2>Testing WhatsApp Notification Sending</h2>";
echo "<hr>";

// Get the latest application to test with
$table = $wpdb->prefix . 'edubot_applications';
$latest_app = $wpdb->get_row("SELECT * FROM $table ORDER BY created_at DESC LIMIT 1");

if (!$latest_app) {
    echo "<p style='color: red;'><strong>❌ No applications found in database</strong></p>";
    die();
}

echo "<h3>Latest Application:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Value</th></tr>";
echo "<tr><td>Application Number</td><td>" . $latest_app->application_number . "</td></tr>";

$student_data = json_decode($latest_app->student_data, true);
echo "<tr><td>Student Name</td><td>" . $student_data['student_name'] . "</td></tr>";
echo "<tr><td>Parent Phone</td><td>" . $student_data['phone'] . "</td></tr>";
echo "<tr><td>Email Sent</td><td>" . ($latest_app->email_sent ? '✅ Yes' : '❌ No') . "</td></tr>";
echo "<tr><td>WhatsApp Sent</td><td>" . ($latest_app->whatsapp_sent ? '✅ Yes' : '❌ No') . "</td></tr>";
echo "<tr><td>SMS Sent</td><td>" . ($latest_app->sms_sent ? '✅ Yes' : '❌ No') . "</td></tr>";
echo "</table>";

// Check if WhatsApp is enabled
$school_config_table = $wpdb->prefix . 'edubot_school_configs';
$config = $wpdb->get_row("SELECT config_data FROM $school_config_table WHERE site_id = 1");
$whatsapp_enabled = false;

if ($config) {
    $config_data = json_decode($config->config_data, true);
    $whatsapp_enabled = $config_data['notification_settings']['whatsapp_enabled'] ?? false;
}

echo "<h3>WhatsApp Configuration:</h3>";
echo "<p><strong>WhatsApp Enabled in Database:</strong> " . ($whatsapp_enabled ? '✅ YES' : '❌ NO') . "</p>";

// Check API configuration
$api_table = $wpdb->prefix . 'edubot_api_integrations';
$whatsapp_api = $wpdb->get_row("SELECT * FROM $api_table WHERE type = 'whatsapp' AND site_id = 1 ORDER BY created_at DESC LIMIT 1");

if ($whatsapp_api && $whatsapp_api->status === 'active') {
    echo "<p><strong>WhatsApp API Provider:</strong> " . $whatsapp_api->provider . " (✅ Active)</p>";
} else {
    echo "<p style='color: orange;'><strong>⚠️ WhatsApp API:</strong> Not configured or inactive</p>";
}

// Test sending if enabled
if ($whatsapp_enabled && $whatsapp_api && $whatsapp_api->status === 'active') {
    echo "<h3>Testing WhatsApp Send:</h3>";
    
    if (!class_exists('EduBot_API_Integrations')) {
        echo "<p style='color: red;'>❌ API Integrations class not found</p>";
        die();
    }
    
    $api_integrations = new EduBot_API_Integrations();
    $phone = preg_replace('/[^0-9+]/', '', $student_data['phone']);
    
    echo "<p><strong>Sending to:</strong> " . htmlspecialchars($phone) . "</p>";
    
    $message = "🎉 *Test WhatsApp Notification* 🎉\n\n";
    $message .= "This is a test message for application: " . $latest_app->application_number . "\n";
    $message .= "Student: " . $student_data['student_name'] . "\n";
    $message .= "Sent at: " . current_time('Y-m-d H:i:s') . "\n\n";
    $message .= "If you received this, WhatsApp notifications are working! ✅";
    
    $result = $api_integrations->send_whatsapp($phone, $message);
    
    if ($result) {
        echo "<p style='color: green;'><strong>✅ WhatsApp message sent successfully!</strong></p>";
        
        // Update database
        $database_manager = new EduBot_Database_Manager();
        $database_manager->update_notification_status($latest_app->id, 'whatsapp', 1, 'applications');
        
        echo "<p>✅ Database status updated to whatsapp_sent = 1</p>";
    } else {
        echo "<p style='color: red;'><strong>❌ WhatsApp message sending failed</strong></p>";
    }
} else {
    echo "<p style='color: red;'><strong>❌ WhatsApp is not properly configured</strong></p>";
    echo "<p>To enable WhatsApp notifications:</p>";
    echo "<ol>";
    echo "<li>Go to WordPress Admin > EduBot Pro > API Integrations</li>";
    echo "<li>Configure WhatsApp provider (Meta/Twilio)</li>";
    echo "<li>Save and activate</li>";
    echo "<li>Go to EduBot Pro > Notification Settings</li>";
    echo "<li>Enable 'WhatsApp Notifications'</li>";
    echo "</ol>";
}

// Show debug log
echo "<h3>Recent Debug Log (Last 20 WhatsApp-related lines):</h3>";
echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 300px; overflow-y: auto;'>";
$log_file = 'D:\\xamppdev\\htdocs\\demo\\wp-content\\debug.log';
if (file_exists($log_file)) {
    $output = shell_exec("powershell -Command \"Get-Content -Path '$log_file' -Tail 100 | Select-String -Pattern 'whatsapp|WhatsApp|send_whatsapp' | Select-Object -Last 20\"");
    echo htmlspecialchars($output ?: "No WhatsApp messages found in debug log");
} else {
    echo "Debug log not found at $log_file";
}
echo "</pre>";

?>
