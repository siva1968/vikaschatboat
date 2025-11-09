<?php
/**
 * Email & WhatsApp Notification Testing Script
 * 
 * Place this file in the WordPress root directory to test notifications.
 * Access via: http://yoursite.com/test_notifications.php
 * 
 * SECURITY: Delete this file after testing!
 */

// Load WordPress
require_once('wp-load.php');

// Verify current user is admin
if (!current_user_can('manage_options')) {
    wp_die('Admin access required');
}

echo '<h1>🧪 EduBot Notification Testing</h1>';
echo '<p><strong>WARNING:</strong> Delete this file after testing!</p>';
echo '<hr>';

// 1. Check Configuration
echo '<h2>1️⃣ Configuration Check</h2>';

$config = EduBot_School_Config::getInstance()->get_config();
$notification_settings = $config['notification_settings'] ?? array();

echo '<table border="1" cellpadding="10">';
echo '<tr><th>Setting</th><th>Value</th><th>Status</th></tr>';

// Check notifications enabled
$parent_notifications_enabled = !empty($notification_settings['parent_notifications']);
echo '<tr><td>Parent Notifications</td><td>' . ($parent_notifications_enabled ? 'ENABLED' : 'DISABLED') . '</td><td>' . ($parent_notifications_enabled ? '✅' : '❌') . '</td></tr>';

$admin_notifications_enabled = !empty($notification_settings['admin_notifications']);
echo '<tr><td>Admin Notifications</td><td>' . ($admin_notifications_enabled ? 'ENABLED' : 'DISABLED') . '</td><td>' . ($admin_notifications_enabled ? '✅' : '❌') . '</td></tr>';

$email_enabled = !empty($notification_settings['email_enabled']);
echo '<tr><td>Email Notifications</td><td>' . ($email_enabled ? 'ENABLED' : 'DISABLED') . '</td><td>' . ($email_enabled ? '✅' : '❌') . '</td></tr>';

$whatsapp_enabled = !empty($notification_settings['whatsapp_enabled']);
echo '<tr><td>WhatsApp Notifications</td><td>' . ($whatsapp_enabled ? 'ENABLED' : 'DISABLED') . '</td><td>' . ($whatsapp_enabled ? '✅' : '❌') . '</td></tr>';

$sms_enabled = !empty($notification_settings['sms_enabled']);
echo '<tr><td>SMS Notifications</td><td>' . ($sms_enabled ? 'ENABLED' : 'DISABLED') . '</td><td>' . ($sms_enabled ? '✅' : '❌') . '</td></tr>';

echo '</table>';

// 2. Check Email API Configuration
echo '<h2>2️⃣ Email API Configuration</h2>';

$email_provider = get_option('edubot_email_provider', '');
$email_api_key = get_option('edubot_email_api_key', '');
$email_from_address = get_option('edubot_email_from_address', '');
$email_from_name = get_option('edubot_email_from_name', '');

echo '<table border="1" cellpadding="10">';
echo '<tr><th>Configuration</th><th>Value</th><th>Status</th></tr>';

echo '<tr><td>Email Provider</td><td>' . (empty($email_provider) ? 'NOT SET' : esc_html($email_provider)) . '</td><td>' . (empty($email_provider) ? '⚠️ Will use WordPress wp_mail()' : '✅') . '</td></tr>';

echo '<tr><td>Email API Key</td><td>' . (empty($email_api_key) ? 'NOT SET' : '••••••' . substr($email_api_key, -4)) . '</td><td>' . (empty($email_api_key) ? '⚠️' : '✅') . '</td></tr>';

echo '<tr><td>From Email Address</td><td>' . (empty($email_from_address) ? 'NOT SET' : esc_html($email_from_address)) . '</td><td>' . (empty($email_from_address) ? '⚠️' : '✅') . '</td></tr>';

echo '<tr><td>From Name</td><td>' . (empty($email_from_name) ? 'NOT SET' : esc_html($email_from_name)) . '</td><td>' . (empty($email_from_name) ? '⚠️' : '✅') . '</td></tr>';

echo '<tr><td>School Email (Config)</td><td>' . (empty($config['school_info']['contact_info']['email']) ? 'NOT SET' : esc_html($config['school_info']['contact_info']['email'])) . '</td><td>' . (empty($config['school_info']['contact_info']['email']) ? '⚠️' : '✅') . '</td></tr>';

echo '<tr><td>WordPress Admin Email</td><td>' . esc_html(get_option('admin_email')) . '</td><td>✅</td></tr>';

echo '</table>';

// 3. Check WhatsApp API Configuration
echo '<h2>3️⃣ WhatsApp API Configuration</h2>';

$api_keys = $config['api_keys'] ?? array();
$whatsapp_provider = $api_keys['whatsapp_provider'] ?? '';
$whatsapp_token = $api_keys['whatsapp_token'] ?? '';
$whatsapp_phone_id = $api_keys['whatsapp_phone_id'] ?? '';

echo '<table border="1" cellpadding="10">';
echo '<tr><th>Configuration</th><th>Value</th><th>Status</th></tr>';

echo '<tr><td>WhatsApp Provider</td><td>' . (empty($whatsapp_provider) ? 'NOT SET' : esc_html($whatsapp_provider)) . '</td><td>' . (empty($whatsapp_provider) ? '⚠️' : '✅') . '</td></tr>';

echo '<tr><td>WhatsApp Access Token</td><td>' . (empty($whatsapp_token) ? 'NOT SET' : '••••••' . substr($whatsapp_token, -4)) . '</td><td>' . (empty($whatsapp_token) ? '⚠️' : '✅') . '</td></tr>';

echo '<tr><td>WhatsApp Phone ID</td><td>' . (empty($whatsapp_phone_id) ? 'NOT SET' : esc_html($whatsapp_phone_ID)) . '</td><td>' . (empty($whatsapp_phone_id) ? '⚠️' : '✅') . '</td></tr>';

echo '</table>';

// 4. Send Test Email
echo '<h2>4️⃣ Send Test Email</h2>';

if (isset($_POST['send_test_email'])) {
    $test_email = sanitize_email($_POST['test_email']);
    $admin_email = get_option('admin_email');
    
    if (!is_email($test_email)) {
        echo '<div style="background-color:#ffcccc;padding:10px;border:1px solid red;">❌ Invalid email address</div>';
    } else {
        // Send test email via API integrations
        $api_integrations = new EduBot_API_Integrations();
        $result = $api_integrations->send_email(
            $test_email,
            '[TEST] EduBot Notification System Working',
            'This is a test email to verify your email notification system is working correctly. If you received this email, email notifications are functioning properly!',
            array('Content-Type: text/plain; charset=UTF-8')
        );
        
        if ($result) {
            echo '<div style="background-color:#ccffcc;padding:10px;border:1px solid green;">✅ Test email sent to ' . esc_html($test_email) . '. Check your inbox!</div>';
        } else {
            echo '<div style="background-color:#ffcccc;padding:10px;border:1px solid red;">❌ Failed to send test email. Check error log at wp-content/debug.log</div>';
        }
    }
}

echo '<form method="POST">';
echo 'Send test email to: <input type="email" name="test_email" required placeholder="' . esc_attr(get_option('admin_email')) . '">';
echo '<button type="submit" name="send_test_email">📧 Send Test Email</button>';
echo '</form>';

// 5. Check Recent Applications
echo '<h2>5️⃣ Recent Applications & Notification Status</h2>';

global $wpdb;
$applications = $wpdb->get_results($wpdb->prepare("
    SELECT id, application_number, created_at, email_sent, whatsapp_sent, sms_sent
    FROM {$wpdb->prefix}edubot_applications
    ORDER BY created_at DESC
    LIMIT 5
"));

if (!empty($applications)) {
    echo '<table border="1" cellpadding="10">';
    echo '<tr><th>App #</th><th>Created</th><th>Email Sent</th><th>WhatsApp Sent</th><th>SMS Sent</th></tr>';
    
    foreach ($applications as $app) {
        echo '<tr>';
        echo '<td>' . esc_html($app->application_number) . '</td>';
        echo '<td>' . esc_html(date('M d, Y H:i', strtotime($app->created_at))) . '</td>';
        echo '<td>' . ($app->email_sent ? '✅' : '❌') . '</td>';
        echo '<td>' . ($app->whatsapp_sent ? '✅' : '❌') . '</td>';
        echo '<td>' . ($app->sms_sent ? '✅' : '❌') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
} else {
    echo '<p>No applications found yet.</p>';
}

// 6. Check Error Log
echo '<h2>6️⃣ Recent Errors (Last 20 lines)</h2>';

$debug_log = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log)) {
    $lines = file($debug_log);
    $recent = array_slice($lines, -20);
    
    echo '<pre style="background-color:#f0f0f0;padding:10px;border:1px solid #ccc;max-height:300px;overflow-y:auto;">';
    foreach ($recent as $line) {
        if (stripos($line, 'edubot') !== false || stripos($line, 'notification') !== false) {
            echo '<span style="color:red;">' . esc_html($line) . '</span>';
        } else {
            echo esc_html($line);
        }
    }
    echo '</pre>';
} else {
    echo '<p>Debug log not found. Enable WP_DEBUG in wp-config.php to see errors.</p>';
}

// 7. Configuration Recommendations
echo '<h2>7️⃣ Configuration Recommendations</h2>';

$recommendations = array();

if (!$parent_notifications_enabled || !$admin_notifications_enabled) {
    $recommendations[] = '❌ Notifications are disabled in settings. Enable "parent_notifications" and "admin_notifications" in EduBot settings.';
}

if (!$email_enabled) {
    $recommendations[] = '❌ Email notifications disabled. Enable "email_enabled" to send parent/admin emails.';
}

if (!$whatsapp_enabled) {
    $recommendations[] = '❌ WhatsApp notifications disabled. Enable "whatsapp_enabled" to send WhatsApp messages (after configuring Meta/Twilio).';
}

if (empty($email_provider) && $email_enabled) {
    $recommendations[] = '⚠️ No email provider configured. Using WordPress wp_mail() as fallback. Consider configuring SendGrid/Mailgun/Zeptomail in Settings → API Integrations.';
}

if (empty($whatsapp_provider) && $whatsapp_enabled) {
    $recommendations[] = '⚠️ No WhatsApp provider configured. WhatsApp messages won\'t send until Meta/Twilio is configured in Settings → API Integrations.';
}

if (empty($config['school_info']['contact_info']['email']) && get_option('admin_email')) {
    $recommendations[] = '✅ Using WordPress admin email (' . esc_html(get_option('admin_email')) . ') for school email. Consider setting school email in Settings → School Info.';
}

if (empty($recommendations)) {
    echo '<div style="background-color:#ccffcc;padding:10px;border:1px solid green;">✅ All notifications configured correctly!</div>';
} else {
    echo '<ul>';
    foreach ($recommendations as $rec) {
        echo '<li>' . $rec . '</li>';
    }
    echo '</ul>';
}

echo '<hr>';
echo '<p><strong>⚠️ SECURITY REMINDER:</strong> Delete this file (test_notifications.php) after testing!</p>';
echo '<p><em>Last Updated: ' . date('Y-m-d H:i:s') . '</em></p>';

// Add delete file form
echo '<h2>🗑️ Delete This Test File</h2>';
if (isset($_POST['delete_test_file'])) {
    if (unlink(__FILE__)) {
        echo '<div style="background-color:#ccffcc;padding:10px;border:1px solid green;">✅ Test file deleted successfully.</div>';
    } else {
        echo '<div style="background-color:#ffcccc;padding:10px;border:1px solid red;">❌ Could not delete test file. Please delete it manually: test_notifications.php</div>';
    }
}

echo '<form method="POST">';
echo '<button type="submit" name="delete_test_file" onclick="return confirm(\'Are you sure? This file will be deleted!\')">🗑️ Delete test_notifications.php</button>';
echo '</form>';

?>