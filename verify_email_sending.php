<?php
/**
 * Complete Email Sending Test - Step by Step
 */

require_once dirname(__FILE__) . '/wp-load.php';

echo "<h1>📧 Email Sending Verification Test</h1>";

// Get current debug log
$log_file = ABSPATH . 'wp-content/debug.log';

echo "<h2>Step 1: Check Current Log Size</h2>";
if (file_exists($log_file)) {
    $size = filesize($log_file);
    echo "<p>Debug log size: <strong>" . number_format($size) . " bytes</strong></p>";
    echo "<p style='color: green;'>✅ Log file exists</p>";
} else {
    echo "<p style='color: red;'>❌ Log file not found</p>";
}

echo "<h2>Step 2: Test Email Configuration</h2>";

$provider = get_option('edubot_email_provider', '');
$api_key = get_option('edubot_email_api_key', '');
$from_address = get_option('edubot_email_from_address', '');
$from_name = get_option('edubot_email_from_name', '');

echo "<p>Provider: <strong>" . (!empty($provider) ? $provider : 'NOT SET') . "</strong></p>";
echo "<p>API Key: <strong>" . (!empty($api_key) ? '✅ Configured (' . strlen($api_key) . ' chars)' : '❌ NOT SET') . "</strong></p>";
echo "<p>From Address: <strong>" . (!empty($from_address) ? $from_address : 'NOT SET') . "</strong></p>";
echo "<p>From Name: <strong>" . (!empty($from_name) ? $from_name : 'NOT SET') . "</strong></p>";

if ($provider !== 'zeptomail') {
    echo "<p style='color: red;'><strong>⚠️ WARNING: Provider is not ZeptoMail!</strong></p>";
}

echo "<h2>Step 3: Send Test Email via API Integrations</h2>";

if (!class_exists('EduBot_API_Integrations')) {
    echo "<p style='color: red;'>❌ EduBot_API_Integrations class not found</p>";
} else {
    echo "<p style='color: green;'>✅ EduBot_API_Integrations class found</p>";
    
    try {
        $api_integrations = new EduBot_API_Integrations();
        $test_email = 'smasina@gmail.com'; // Your test email
        $subject = '[EDUBOT TEST] Email Test - ' . date('Y-m-d H:i:s');
        $message = '
        <html>
        <body style="font-family: Arial, sans-serif;">
            <h2>EduBot Pro - Email Test</h2>
            <p>This is a test email to verify ZeptoMail integration is working.</p>
            <p><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>From Address:</strong> ' . $from_address . '</p>
            <p><strong>From Name:</strong> ' . $from_name . '</p>
            <p>If you received this email, ZeptoMail integration is working correctly!</p>
        </body>
        </html>';
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        echo "<p>Attempting to send test email to: <strong>" . $test_email . "</strong></p>";
        echo "<p>Subject: <strong>" . $subject . "</strong></p>";
        
        $result = $api_integrations->send_email($test_email, $subject, $message, $headers);
        
        echo "<p style='color: green; font-size: 18px;'><strong>✅ Email sent! Result: " . ($result ? 'TRUE' : 'FALSE') . "</strong></p>";
        
        // Check if log was written
        if (file_exists($log_file)) {
            $current_size = filesize($log_file);
            echo "<p>New log size: <strong>" . number_format($current_size) . " bytes</strong></p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>❌ Exception: " . $e->getMessage() . "</strong></p>";
    }
}

echo "<h2>Step 4: Recent Email Logs</h2>";
if (file_exists($log_file)) {
    $all_lines = file($log_file);
    $recent_lines = array_slice($all_lines, -50); // Last 50 lines
    
    echo "<p>Last 50 log entries (email/error related):</p>";
    echo "<pre style='background: #f0f0f0; padding: 15px; max-height: 500px; overflow-y: auto; border-left: 4px solid #0073aa;'>";
    
    foreach ($recent_lines as $line) {
        $line = trim($line);
        if (!empty($line) && (strpos($line, 'mail') !== false || strpos($line, 'EduBot') !== false || strpos($line, 'email') !== false || strpos($line, 'Zepto') !== false)) {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
}

echo "<h2>Step 5: Instructions for Testing</h2>";
echo "<ol>";
echo "<li>Keep this page open</li>";
echo "<li><a href='http://localhost/demo/' target='_blank'>Go to the chatbot page</a></li>";
echo "<li>Submit a new admission enquiry with your email: <strong>smasina@gmail.com</strong></li>";
echo "<li>Return to this page and scroll down to see the new log entries</li>";
echo "<li>Check your email inbox for the confirmation message</li>";
echo "</ol>";

echo "<p><br><a href='javascript:location.reload()' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;'>🔄 Refresh This Page</a></p>";

echo "<p><a href='http://localhost/demo/debug_log_viewer.php'>📋 View Full Debug Log</a></p>";

?>
