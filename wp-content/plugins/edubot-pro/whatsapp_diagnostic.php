<?php
/**
 * WhatsApp Configuration Diagnostic
 */

require_once '/var/www/html/demo/wp-load.php';

echo "<h1>📱 WhatsApp Configuration Diagnostic</h1>";

echo "<h2>1. WhatsApp Notifications - Parent</h2>";
$whatsapp_parent_enabled = get_option('edubot_whatsapp_notifications', 0);
echo "<p>Parent WhatsApp Enabled: <strong>" . ($whatsapp_parent_enabled ? '✅ YES' : '❌ NO') . "</strong></p>";

if (!$whatsapp_parent_enabled) {
    echo "<p style='color: red;'><strong>⚠️ Parent WhatsApp is DISABLED</strong></p>";
    echo "<p>To enable: Go to Admin → EduBot Pro → School Settings → Check 'WhatsApp Notifications'</p>";
}

echo "<h2>2. WhatsApp Notifications - School</h2>";
$whatsapp_school_enabled = get_option('edubot_school_whatsapp_notifications', 0);
echo "<p>School WhatsApp Enabled: <strong>" . ($whatsapp_school_enabled ? '✅ YES' : '❌ NO') . "</strong></p>";

if (!$whatsapp_school_enabled) {
    echo "<p style='color: orange;'><strong>ℹ️ School WhatsApp is DISABLED</strong></p>";
    echo "<p>To enable: Go to Admin → EduBot Pro → School Settings → Check 'School WhatsApp Notifications'</p>";
}

echo "<h2>3. WhatsApp API Configuration</h2>";
$whatsapp_provider = get_option('edubot_whatsapp_provider', '');
$whatsapp_token = get_option('edubot_whatsapp_token', '');
$whatsapp_phone_id = get_option('edubot_whatsapp_phone_id', '');

echo "<p>Provider: <strong>" . (!empty($whatsapp_provider) ? $whatsapp_provider : 'NOT SET') . "</strong></p>";
echo "<p>Token: <strong>" . (!empty($whatsapp_token) ? '✅ Configured (' . strlen($whatsapp_token) . ' chars)' : '❌ NOT SET') . "</strong></p>";
echo "<p>Phone ID: <strong>" . (!empty($whatsapp_phone_id) ? $whatsapp_phone_id : '❌ NOT SET') . "</strong></p>";

if (empty($whatsapp_provider) || empty($whatsapp_token) || empty($whatsapp_phone_id)) {
    echo "<p style='color: red;'><strong>❌ WhatsApp API is NOT CONFIGURED</strong></p>";
    echo "<p>To configure: Go to Admin → EduBot Pro → API Integrations → WhatsApp section</p>";
}

echo "<h2>4. School Phone Configuration</h2>";
$school_phone = get_option('edubot_school_phone', '');
echo "<p>School Phone: <strong>" . (!empty($school_phone) ? $school_phone : '❌ NOT SET') . "</strong></p>";

if (empty($school_phone) && $whatsapp_school_enabled) {
    echo "<p style='color: red;'><strong>⚠️ School phone not set but school WhatsApp is enabled!</strong></p>";
    echo "<p>To set: Go to Admin → EduBot Pro → School Settings → Contact Phone</p>";
}

echo "<h2>5. Template Configuration</h2>";
$template_type = get_option('edubot_whatsapp_use_templates', 0);
echo "<p>Use Templates: <strong>" . ($template_type ? '✅ YES (Business Template)' : '❌ NO (Freeform)') . "</strong></p>";

if ($template_type) {
    $template_name = get_option('edubot_whatsapp_template_name', '');
    echo "<p>Template Name: <strong>" . (!empty($template_name) ? $template_name : 'NOT SET') . "</strong></p>";
} else {
    echo "<p>Using freeform messages - no template name needed</p>";
}

echo "<h2>6. Summary & Recommendations</h2>";

$issues = array();

if (!$whatsapp_parent_enabled) {
    $issues[] = "❌ Parent WhatsApp notifications are DISABLED";
}

if (empty($whatsapp_provider) || empty($whatsapp_token) || empty($whatsapp_phone_id)) {
    $issues[] = "❌ WhatsApp API is not properly configured";
}

if (empty($issues)) {
    echo "<p style='color: green; font-size: 16px;'><strong>✅ WhatsApp is properly configured!</strong></p>";
    echo "<p>WhatsApp messages should be sending with enquiries.</p>";
} else {
    echo "<p style='color: red;'><strong>⚠️ Issues Found:</strong></p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    
    echo "<h3>📋 Steps to Fix:</h3>";
    echo "<ol>";
    echo "<li>Go to <strong>WordPress Admin → EduBot Pro → API Integrations</strong></li>";
    echo "<li>Find <strong>WhatsApp</strong> tab</li>";
    echo "<li>Configure:";
    echo "<ul>";
    echo "<li>Provider: Select 'Meta'</li>";
    echo "<li>Access Token: Paste your WhatsApp Business API token</li>";
    echo "<li>Phone Number ID: Enter your WhatsApp phone number ID</li>";
    echo "</ul>";
    echo "</li>";
    echo "<li>Click <strong>'Test Connection'</strong> to verify</li>";
    echo "<li>Save settings</li>";
    echo "<li>Go to <strong>School Settings</strong> and check <strong>'WhatsApp Notifications'</strong></li>";
    echo "<li>Set <strong>Contact Phone</strong> to your school's WhatsApp number</li>";
    echo "<li>Test by submitting an enquiry</li>";
    echo "</ol>";
}

echo "<h2>7. Test Recent Logs</h2>";
$log_file = ABSPATH . 'wp-content/debug.log';
if (file_exists($log_file)) {
    $lines = array_reverse(file($log_file));
    
    echo "<p>Recent WhatsApp related log entries:</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 300px; overflow-y: auto;'>";
    
    $found = 0;
    foreach ($lines as $line) {
        if (strpos($line, 'WhatsApp') !== false) {
            echo htmlspecialchars(trim($line)) . "\n";
            $found++;
            if ($found >= 10) break;
        }
    }
    
    if ($found === 0) {
        echo "No WhatsApp logs found yet.\n";
    }
    echo "</pre>";
}

echo "<p><a href='http://localhost/demo/' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;'>← Back to Chatbot</a></p>";

?>
