<?php
/**
 * Diagnose ZeptoMail Configuration Issue
 *
 * This script investigates why the admin UI shows ZeptoMail configured
 * but the database doesn't have it.
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== ZEPTOMAIL CONFIGURATION DIAGNOSTIC ===\n\n";

echo "1. CHECKING WORDPRESS OPTIONS (Where Admin UI Saves):\n";
echo str_repeat("-", 60) . "\n";

$email_service = get_option('edubot_email_service', 'NOT SET');
$email_api_key = get_option('edubot_email_api_key', 'NOT SET');
$email_from_address = get_option('edubot_email_from_address', 'NOT SET');
$email_from_name = get_option('edubot_email_from_name', 'NOT SET');

echo "   Email Service Provider: " . $email_service . "\n";
echo "   Email API Key: " . ($email_api_key !== 'NOT SET' ? substr($email_api_key, 0, 20) . "..." : 'NOT SET') . "\n";
echo "   From Email Address: " . $email_from_address . "\n";
echo "   From Name: " . $email_from_name . "\n\n";

echo "2. CHECKING SCHOOL CONFIG TABLE (Where System Reads From):\n";
echo str_repeat("-", 60) . "\n";

$school_config = EduBot_School_Config::getInstance();
$config = $school_config->get_config();

echo "   Email Provider: " . ($config['notification_settings']['email_provider'] ?? 'NOT SET') . "\n";
echo "   Email Enabled: " . (($config['notification_settings']['email_enabled'] ?? false) ? 'Yes' : 'No') . "\n";
echo "   Admin Email: " . ($config['notification_settings']['admin_email'] ?? 'NOT SET') . "\n\n";

// Check API keys from school config
$api_keys = $school_config->get_api_keys();
echo "   ZeptoMail Token in Config: " . (!empty($api_keys['zeptomail_token']) ? 'CONFIGURED' : 'NOT SET') . "\n";
echo "   Email Service in API Keys: " . ($api_keys['email_service'] ?? 'NOT SET') . "\n";
echo "   Email API Key in API Keys: " . (!empty($api_keys['email_api_key']) ? 'CONFIGURED' : 'NOT SET') . "\n\n";

echo "3. ROOT CAUSE ANALYSIS:\n";
echo str_repeat("=", 60) . "\n";

$wp_has_zeptomail = ($email_service === 'zeptomail' && $email_api_key !== 'NOT SET');
$config_has_zeptomail = !empty($api_keys['zeptomail_token']) ||
                         ($api_keys['email_service'] === 'zeptomail' && !empty($api_keys['email_api_key']));

echo "\n";
echo "WordPress Options Status:\n";
echo "   Provider: " . ($email_service === 'zeptomail' ? '✓ ZeptoMail' : '✗ ' . $email_service) . "\n";
echo "   API Key: " . ($email_api_key !== 'NOT SET' ? '✓ Configured' : '✗ Missing') . "\n";
echo "   Admin UI Shows: ✓ ZeptoMail with API Key (from screenshot)\n\n";

echo "School Config Table Status:\n";
echo "   Provider: " . ($config['notification_settings']['email_provider'] ?? 'NOT SET') . "\n";
echo "   ZeptoMail Token: " . ($config_has_zeptomail ? '✓ Configured' : '✗ Missing') . "\n\n";

echo "THE PROBLEM:\n";
echo str_repeat("-", 60) . "\n";

if ($wp_has_zeptomail && !$config_has_zeptomail) {
    echo "⚠️  CONFIGURATION MISMATCH DETECTED!\n\n";
    echo "The admin UI saves ZeptoMail settings to WordPress options,\n";
    echo "but the notification system reads from the school config table.\n\n";
    echo "These are TWO DIFFERENT STORAGE LOCATIONS!\n\n";

    echo "What's Happening:\n";
    echo "1. Admin UI saves to: wp_options table\n";
    echo "   - edubot_email_service = 'zeptomail'\n";
    echo "   - edubot_email_api_key = 'your-key'\n\n";

    echo "2. Notification Manager reads from: wp_edubot_school_configs table\n";
    echo "   - notification_settings['email_provider'] = 'NOT SET'\n";
    echo "   - api_keys['zeptomail_token'] = 'NOT SET'\n\n";

    echo "SOLUTION:\n";
    echo "The settings need to be migrated from wp_options to school configs,\n";
    echo "OR the notification manager needs to read from wp_options instead.\n\n";

} elseif (!$wp_has_zeptomail && !$config_has_zeptomail) {
    echo "⚠️  ZEPTOMAIL NOT CONFIGURED IN EITHER LOCATION!\n\n";
    echo "Even though admin UI shows ZeptoMail configured, it's not saved.\n";
    echo "This suggests the save operation is failing silently.\n\n";

} else {
    echo "✓ Configuration appears correct in both locations!\n";
    echo "If emails still not sending, check:\n";
    echo "- ZeptoMail API key is valid\n";
    echo "- ZeptoMail account is active\n";
    echo "- From email domain is verified in ZeptoMail\n";
}

echo "\n4. NOTIFICATION MANAGER CHECK:\n";
echo str_repeat("-", 60) . "\n";

// Check what notification manager will use
if (class_exists('EduBot_Notification_Manager')) {
    echo "Notification Manager class exists: YES\n";
    echo "Checking what email provider it will use...\n\n";

    // Show the logic
    echo "Provider Selection Logic:\n";
    echo "1. Check notification_settings['email_provider']\n";
    echo "   Current value: " . ($config['notification_settings']['email_provider'] ?? 'NOT SET') . "\n\n";

    if (empty($config['notification_settings']['email_provider'])) {
        echo "   ⚠️  Email provider not set in notification_settings!\n";
        echo "   System will fall back to WordPress wp_mail()\n";
    }
} else {
    echo "Notification Manager class not found\n";
}

echo "\n5. RECOMMENDED FIX:\n";
echo str_repeat("=", 60) . "\n";
echo "\nOption A (Quick Fix):\n";
echo "  Update school config to use ZeptoMail settings from wp_options\n";
echo "  Run: php sync_zeptomail_to_config.php\n\n";

echo "Option B (Proper Fix):\n";
echo "  Modify Notification Manager to read from wp_options\n";
echo "  This is where admin UI actually saves the settings\n\n";

echo "=== DIAGNOSTIC COMPLETE ===\n";
