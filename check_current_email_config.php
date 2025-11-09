<?php
/**
 * Check Current Email Configuration
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== Current Email Configuration ===\n\n";

$school_config = EduBot_School_Config::getInstance();
$config = $school_config->get_config();
$api_keys = $school_config->get_api_keys();

echo "1. EMAIL PROVIDER SETTING:\n";
$email_provider = $config['notification_settings']['email_provider'] ?? 'NOT SET';
echo "   Provider: " . $email_provider . "\n\n";

echo "2. ZEPTOMAIL CONFIGURATION:\n";
$has_zeptomail_token = !empty($api_keys['zeptomail_token']);
echo "   Token Configured: " . ($has_zeptomail_token ? 'YES ✓' : 'NO ✗') . "\n";
if ($has_zeptomail_token) {
    echo "   Token: " . substr($api_keys['zeptomail_token'], 0, 20) . "...\n";
}
echo "\n";

echo "3. NOTIFICATION SETTINGS:\n";
echo "   Email Enabled: " . (($config['notification_settings']['email_enabled'] ?? false) ? 'Yes' : 'No') . "\n";
echo "   Admin Notifications: " . (($config['notification_settings']['admin_notifications'] ?? false) ? 'Yes' : 'No') . "\n";
echo "   Admin Email: " . ($config['notification_settings']['admin_email'] ?? 'NOT SET') . "\n";
echo "   Admin Phone: " . ($config['notification_settings']['admin_phone'] ?? 'NOT SET') . "\n\n";

echo "4. ISSUE ANALYSIS:\n";
if ($email_provider === 'wordpress') {
    echo "   ⚠️  Provider is set to 'wordpress' (wp_mail)\n";
    echo "   ⚠️  Should be 'zeptomail' to use ZeptoMail API\n\n";

    if ($has_zeptomail_token) {
        echo "   ✓ ZeptoMail token IS configured\n";
        echo "   ✗ But email_provider setting is wrong\n\n";
        echo "   SOLUTION: Change email_provider from 'wordpress' to 'zeptomail'\n";
    } else {
        echo "   ✗ ZeptoMail token NOT configured\n";
        echo "   ✗ Using WordPress mail (won't work on XAMPP)\n\n";
        echo "   SOLUTION: Configure ZeptoMail token in API settings\n";
    }
} elseif ($email_provider === 'zeptomail') {
    echo "   ✓ Provider is correctly set to 'zeptomail'\n";
    if ($has_zeptomail_token) {
        echo "   ✓ ZeptoMail token is configured\n";
        echo "   ✅ Configuration looks correct!\n\n";
        echo "   If emails still not sending, check:\n";
        echo "   - ZeptoMail token is valid\n";
        echo "   - ZeptoMail account is active\n";
        echo "   - Check error logs for API errors\n";
    } else {
        echo "   ✗ ZeptoMail token NOT configured\n";
        echo "   SOLUTION: Add ZeptoMail token in API settings\n";
    }
} else {
    echo "   ⚠️  Email provider: $email_provider\n";
    echo "   Should be 'zeptomail' if using ZeptoMail\n";
}

echo "\n5. RECOMMENDED ACTION:\n";
if ($has_zeptomail_token && $email_provider !== 'zeptomail') {
    echo "   Change email_provider setting to 'zeptomail'\n";
    echo "   Run: php fix_email_provider.php\n";
} elseif (!$has_zeptomail_token) {
    echo "   Configure ZeptoMail token:\n";
    echo "   - Go to WordPress Admin → EduBot Pro → API Settings\n";
    echo "   - Add ZeptoMail token\n";
    echo "   - Set email provider to 'zeptomail'\n";
} else {
    echo "   Configuration appears correct!\n";
    echo "   If emails not sending, check ZeptoMail account status\n";
}

echo "\n=== Check Complete ===\n";
