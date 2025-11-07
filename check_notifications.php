<?php
/**
 * Check Notification Configuration
 */

// Load WordPress
require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== Notification Configuration Check ===\n\n";

// Get school configuration
$school_config = EduBot_School_Config::getInstance();
$config = $school_config->get_config();

echo "1. EMAIL CONFIGURATION:\n";
echo "   Provider: " . ($config['notification_settings']['email_provider'] ?? 'NOT SET') . "\n";
echo "   Enabled: " . (isset($config['notification_settings']['email_enabled']) && $config['notification_settings']['email_enabled'] ? 'Yes' : 'No') . "\n";

// Check email API keys
$api_keys = $school_config->get_api_keys();
if (!empty($config['notification_settings']['email_provider'])) {
    $provider = $config['notification_settings']['email_provider'];
    echo "   Provider: $provider\n";

    if ($provider === 'zeptomail') {
        $has_key = !empty($api_keys['zeptomail_token']);
        echo "   ZeptoMail Token: " . ($has_key ? 'Configured ✓' : 'NOT CONFIGURED ✗') . "\n";
    } elseif ($provider === 'smtp') {
        echo "   SMTP Host: " . ($config['notification_settings']['smtp_host'] ?? 'NOT SET') . "\n";
        echo "   SMTP Port: " . ($config['notification_settings']['smtp_port'] ?? 'NOT SET') . "\n";
        echo "   SMTP Username: " . ($config['notification_settings']['smtp_username'] ?? 'NOT SET') . "\n";
    }
}

echo "\n2. WHATSAPP CONFIGURATION:\n";
echo "   Enabled: " . (isset($config['notification_settings']['whatsapp_enabled']) && $config['notification_settings']['whatsapp_enabled'] ? 'Yes' : 'No') . "\n";
echo "   Provider: " . ($config['notification_settings']['whatsapp_provider'] ?? 'NOT SET') . "\n";

if (!empty($config['notification_settings']['whatsapp_provider'])) {
    $wa_provider = $config['notification_settings']['whatsapp_provider'];
    if ($wa_provider === 'interakt') {
        $has_key = !empty($api_keys['interakt_api_key']);
        echo "   Interakt API Key: " . ($has_key ? 'Configured ✓' : 'NOT CONFIGURED ✗') . "\n";
    } elseif ($wa_provider === 'twilio') {
        $has_sid = !empty($config['notification_settings']['twilio_account_sid']);
        $has_token = !empty($config['notification_settings']['twilio_auth_token']);
        echo "   Twilio SID: " . ($has_sid ? 'Configured ✓' : 'NOT CONFIGURED ✗') . "\n";
        echo "   Twilio Token: " . ($has_token ? 'Configured ✓' : 'NOT CONFIGURED ✗') . "\n";
    }
}

echo "\n3. NOTIFICATION RECIPIENTS:\n";
echo "   Admin Email: " . ($config['notification_settings']['admin_email'] ?? 'NOT SET') . "\n";
echo "   Admin Phone: " . ($config['notification_settings']['admin_phone'] ?? 'NOT SET') . "\n";

echo "\n4. TESTING EMAIL:\n";
try {
    if (!class_exists('EduBot_Notification_Manager')) {
        require_once('D:/xampp/htdocs/demo/wp-content/plugins/edubot-pro/includes/class-notification-manager.php');
    }

    $notification_manager = new EduBot_Notification_Manager();

    // Test email
    $test_email = $config['notification_settings']['admin_email'] ?? 'test@example.com';
    echo "   Sending test email to: $test_email\n";

    $email_result = $notification_manager->send_enquiry_notification(array(
        'student_name' => 'Test Student',
        'email' => $test_email,
        'phone' => '+919876543210',
        'grade' => 'Grade 5',
        'source' => 'Test'
    ));

    echo "   Result: " . ($email_result ? 'SUCCESS ✓' : 'FAILED ✗') . "\n";

} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n5. CHECKING DATABASE TABLES:\n";
global $wpdb;
$table_name = $wpdb->prefix . 'edubot_enquiries';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
echo "   enquiries table: " . ($table_exists ? 'EXISTS ✓' : 'MISSING ✗') . "\n";

if ($table_exists) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "   Total enquiries: $count\n";

    // Get recent enquiry
    $recent = $wpdb->get_row("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 1", ARRAY_A);
    if ($recent) {
        echo "   Latest enquiry:\n";
        echo "     - Name: " . $recent['student_name'] . "\n";
        echo "     - Email: " . $recent['email'] . "\n";
        echo "     - Phone: " . $recent['phone'] . "\n";
        echo "     - Notification Sent: " . ($recent['notification_sent'] ?? 'Unknown') . "\n";
    }
}

echo "\n=== Check Complete ===\n";
