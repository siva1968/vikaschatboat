<?php
/**
 * Check Notification Settings
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== NOTIFICATION SETTINGS CHECK ===\n\n";

$school_config = EduBot_School_Config::getInstance();
$config = $school_config->get_config();

echo "NOTIFICATION SETTINGS:\n";
echo str_repeat("-", 60) . "\n";
echo "Email Enabled: " . (($config['notification_settings']['email_enabled'] ?? false) ? 'YES ✓' : 'NO ✗') . "\n";
echo "WhatsApp Enabled: " . (($config['notification_settings']['whatsapp_enabled'] ?? false) ? 'YES ✓' : 'NO ✗') . "\n";
echo "SMS Enabled: " . (($config['notification_settings']['sms_enabled'] ?? false) ? 'YES ✓' : 'NO ✗') . "\n";
echo "Admin Notifications: " . (($config['notification_settings']['admin_notifications'] ?? false) ? 'YES ✓' : 'NO ✗') . "\n";
echo "Parent Notifications: " . (($config['notification_settings']['parent_notifications'] ?? false) ? 'YES ✓' : 'NO ✗') . "\n\n";

echo "Admin Contact:\n";
echo "- Email: " . ($config['notification_settings']['admin_email'] ?? 'NOT SET') . "\n";
echo "- Phone: " . ($config['notification_settings']['admin_phone'] ?? 'NOT SET') . "\n\n";

// Check if all required settings are present
$issues = array();

if (empty($config['notification_settings']['email_enabled'])) {
    $issues[] = "Email notifications are DISABLED";
}

if (empty($config['notification_settings']['admin_notifications'])) {
    $issues[] = "Admin notifications are DISABLED";
}

if (empty($config['notification_settings']['admin_email'])) {
    $issues[] = "Admin email is NOT SET";
}

if (!empty($issues)) {
    echo "❌ PROBLEMS FOUND:\n";
    foreach ($issues as $issue) {
        echo "   - $issue\n";
    }
    echo "\nThese settings must be enabled for notifications to work!\n\n";

    echo "FIX:\n";
    echo "Run this SQL to enable notifications:\n\n";

    echo "UPDATE wp_edubot_school_configs SET config_data = JSON_SET(\n";
    echo "  config_data,\n";
    echo "  '$.notification_settings.email_enabled', true,\n";
    echo "  '$.notification_settings.admin_notifications', true,\n";
    echo "  '$.notification_settings.admin_email', 'prasadmasina@gmail.com'\n";
    echo ") WHERE site_id = 1;\n\n";
} else {
    echo "✅ All notification settings are properly configured!\n\n";
    echo "Notifications SHOULD be working when enquiries are submitted.\n";
    echo "If they're still not coming through, the issue may be:\n";
    echo "1. Notification Manager not being called after enquiry submission\n";
    echo "2. Error in notification sending logic\n";
    echo "3. Check debug logs for errors\n";
}

echo "\n=== CHECK COMPLETE ===\n";
