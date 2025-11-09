<?php
/**
 * Test ZeptoMail Email Sending
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== TESTING ZEPTOMAIL EMAIL SENDING ===\n\n";

// Load required classes
require_once('D:/xampp/htdocs/demo/wp-content/plugins/edubot-pro/includes/class-school-config.php');
require_once('D:/xampp/htdocs/demo/wp-content/plugins/edubot-pro/includes/class-api-migration.php');
require_once('D:/xampp/htdocs/demo/wp-content/plugins/edubot-pro/includes/class-api-integrations.php');

echo "1. Checking API Settings:\n";
echo str_repeat("-", 60) . "\n";

$api_settings = EduBot_API_Migration::get_api_settings();
echo "Email Provider: " . ($api_settings['email_provider'] ?? 'NOT SET') . "\n";
echo "API Key Present: " . (!empty($api_settings['email_api_key']) ? 'YES' : 'NO') . "\n";
echo "From Address: " . ($api_settings['email_from_address'] ?? 'NOT SET') . "\n";
echo "From Name: " . ($api_settings['email_from_name'] ?? 'NOT SET') . "\n\n";

if (empty($api_settings['email_provider']) || $api_settings['email_provider'] !== 'zeptomail') {
    echo "❌ ERROR: Email provider is not set to zeptomail!\n";
    echo "Cannot proceed with test.\n";
    exit;
}

if (empty($api_settings['email_api_key'])) {
    echo "❌ ERROR: ZeptoMail API key is missing!\n";
    echo "Cannot proceed with test.\n";
    exit;
}

echo "✓ Configuration looks good!\n\n";

echo "2. Testing Email Send:\n";
echo str_repeat("-", 60) . "\n";

$school_config = EduBot_School_Config::getInstance();
$api_integrations = new EduBot_API_Integrations($school_config);

$to = 'prasadmasina@gmail.com';
$subject = 'ZeptoMail Test - ' . date('Y-m-d H:i:s');
$message = "This is a test email from EduBot Pro using ZeptoMail API.\n\n";
$message .= "If you received this, ZeptoMail integration is working correctly!\n\n";
$message .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";

echo "Sending test email to: $to\n";
echo "Subject: $subject\n\n";

try {
    $result = $api_integrations->send_email($to, $subject, $message);

    if ($result) {
        echo "✅ SUCCESS: Email sent via ZeptoMail!\n";
        echo "Check inbox: $to\n";
    } else {
        echo "❌ FAILED: Email send returned false\n";
        echo "Check error logs for details\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n3. Check Error Logs:\n";
echo str_repeat("-", 60) . "\n";
echo "If the send failed, check:\n";
echo "- WordPress debug.log for error messages\n";
echo "- ZeptoMail dashboard for API errors\n";
echo "- Verify from email domain is verified in ZeptoMail\n\n";

echo "=== TEST COMPLETE ===\n";
