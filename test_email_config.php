<?php
/**
 * Test email configuration and ZeptoMail integration
 */

require_once dirname(__FILE__) . '/wp-load.php';

echo "<h1>✅ Email Configuration Test</h1>";

// Check if API Integrations class exists
echo "<h2>1. Check API Integrations Class</h2>";
if (class_exists('EduBot_API_Integrations')) {
    echo "<p style='color: green;'>✅ EduBot_API_Integrations class found</p>";
} else {
    echo "<p style='color: red;'>❌ EduBot_API_Integrations class NOT found</p>";
}

// Check email provider setting
echo "<h2>2. Email Provider Configuration</h2>";
$email_provider = get_option('edubot_email_provider', '');
echo "<p>Email Provider: <strong>$email_provider</strong></p>";

if ($email_provider === 'zeptomail') {
    echo "<p style='color: green;'>✅ ZeptoMail is selected</p>";
} else {
    echo "<p style='color: red;'>❌ ZeptoMail is NOT selected (currently: $email_provider)</p>";
}

// Check API key
echo "<h2>3. ZeptoMail API Key</h2>";
$api_key = get_option('edubot_email_api_key', '');
if (!empty($api_key)) {
    echo "<p style='color: green;'>✅ API Key is configured (length: " . strlen($api_key) . " chars)</p>";
} else {
    echo "<p style='color: red;'>❌ API Key is NOT configured</p>";
}

// Check from address
echo "<h2>4. From Address Configuration</h2>";
$from_address = get_option('edubot_email_from_address', '');
$from_name = get_option('edubot_email_from_name', '');

if (!empty($from_address)) {
    echo "<p style='color: green;'>✅ From Address: <strong>$from_address</strong></p>";
} else {
    echo "<p style='color: red;'>❌ From Address is NOT configured</p>";
}

if (!empty($from_name)) {
    echo "<p style='color: green;'>✅ From Name: <strong>$from_name</strong></p>";
} else {
    echo "<p style='color: orange;'>⚠️ From Name is not configured (will use default)</p>";
}

// Check email notifications enabled
echo "<h2>5. Email Notifications Setting</h2>";
$email_enabled = get_option('edubot_email_notifications', 1);
if ($email_enabled) {
    echo "<p style='color: green;'>✅ Parent confirmation emails are ENABLED</p>";
} else {
    echo "<p style='color: red;'>❌ Parent confirmation emails are DISABLED</p>";
}

// Check school notifications enabled
echo "<h2>6. School Notifications Setting</h2>";
$school_notifications = get_option('edubot_school_notifications', 1);
if ($school_notifications) {
    echo "<p style='color: green;'>✅ School notification emails are ENABLED</p>";
} else {
    echo "<p style='color: red;'>❌ School notification emails are DISABLED</p>";
}

// Check school email
echo "<h2>7. School Contact Email</h2>";
$school_email = get_option('edubot_school_email', '');
if (!empty($school_email)) {
    echo "<p style='color: green;'>✅ School Email: <strong>$school_email</strong></p>";
} else {
    echo "<p style='color: orange;'>⚠️ School Email not configured (will use fallback)</p>";
}

// Test sending an email
echo "<h2>8. Send Test Email</h2>";
if (class_exists('EduBot_API_Integrations') && $email_provider === 'zeptomail' && !empty($api_key)) {
    try {
        $api_integrations = new EduBot_API_Integrations();
        
        $test_email = get_option('admin_email', 'admin@example.com');
        $subject = 'EduBot Pro - Email Configuration Test - ' . date('Y-m-d H:i:s');
        $message = '<p>This is a test email from EduBot Pro to verify ZeptoMail integration is working correctly.</p>';
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        echo "<p>Sending test email to: <strong>$test_email</strong></p>";
        
        $result = $api_integrations->send_email($test_email, $subject, $message, $headers);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Test email sent successfully!</p>";
            echo "<p>Check your email inbox for the test message from EduBot Pro</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to send test email</p>";
            echo "<p>Check the debug log for details: /wp-content/debug.log</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error sending test email: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Cannot send test email - configuration incomplete</p>";
    if (!class_exists('EduBot_API_Integrations')) {
        echo "<p>- API Integrations class not found</p>";
    }
    if ($email_provider !== 'zeptomail') {
        echo "<p>- Email provider is not ZeptoMail</p>";
    }
    if (empty($api_key)) {
        echo "<p>- API Key not configured</p>";
    }
}

// Display recent log entries
echo "<h2>9. Recent Debug Log Entries</h2>";
$log_file = ABSPATH . 'wp-content/debug.log';
if (file_exists($log_file)) {
    $lines = file($log_file, FILE_IGNORE_NEW_LINES);
    $lines = array_reverse($lines);
    
    echo "<p>Last 20 entries:</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 400px; overflow-y: auto;'>";
    
    $count = 0;
    foreach ($lines as $line) {
        if (strpos($line, 'EduBot') !== false || strpos($line, 'email') !== false) {
            echo htmlspecialchars($line) . "\n";
            $count++;
            if ($count >= 20) break;
        }
    }
    echo "</pre>";
} else {
    echo "<p style='color: orange;'>⚠️ Debug log file not found at: $log_file</p>";
}

echo "<h2>Summary</h2>";
echo "<p><strong>Configuration Status:</strong></p>";
echo "<ul>";
echo "<li>Email Provider: " . (!empty($email_provider) ? "✅ $email_provider" : "❌ Not set") . "</li>";
echo "<li>API Key: " . (!empty($api_key) ? "✅ Configured" : "❌ Not set") . "</li>";
echo "<li>From Address: " . (!empty($from_address) ? "✅ $from_address" : "❌ Not set") . "</li>";
echo "<li>Parent Emails: " . ($email_enabled ? "✅ Enabled" : "❌ Disabled") . "</li>";
echo "<li>School Emails: " . ($school_notifications ? "✅ Enabled" : "❌ Disabled") . "</li>";
echo "</ul>";

echo "<p><a href='http://localhost/demo/'>← Back to Site</a></p>";

?>
