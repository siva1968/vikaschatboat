<?php
/**
 * Test Notification Settings Save
 * This script helps verify that the notification settings checkbox is working
 */

// Load WordPress
if (!defined('ABSPATH')) {
    $wp_load_paths = [
        '../../wp-load.php',
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../wp-load.php',
        './wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('Cannot find WordPress. Please upload this file to your WordPress site and run it there.');
    }
}

echo "<h1>🔧 Notification Settings Test</h1>";

echo "<h2>Current Notification Settings</h2>";

// Check current values
$email_notifications = get_option('edubot_email_notifications', 'NOT_SET');
$whatsapp_notifications = get_option('edubot_whatsapp_notifications', 'NOT_SET');
$school_notifications = get_option('edubot_school_notifications', 'NOT_SET');

echo "<p><strong>Email Notifications:</strong> ";
if ($email_notifications === 'NOT_SET') {
    echo "❓ Not set in database";
} else {
    echo ($email_notifications ? '✅ Enabled (1)' : '❌ Disabled (0)');
}
echo "</p>";

echo "<p><strong>WhatsApp Notifications:</strong> ";
if ($whatsapp_notifications === 'NOT_SET') {
    echo "❓ Not set in database";
} else {
    echo ($whatsapp_notifications ? '✅ Enabled (1)' : '❌ Disabled (0)');
}
echo "</p>";

echo "<p><strong>School Notifications:</strong> ";
if ($school_notifications === 'NOT_SET') {
    echo "❓ Not set in database";
} else {
    echo ($school_notifications ? '✅ Enabled (1)' : '❌ Disabled (0)');
}
echo "</p>";

echo "<h2>Test Setting Values</h2>";

// Test setting values manually
echo "<p>Testing manual option updates...</p>";

$test_results = [];

// Test email notifications
$result1 = update_option('edubot_email_notifications', 1);
$test_results['email_enable'] = $result1;
echo "<p>Set email notifications to 1: " . ($result1 ? '✅ Success' : '❌ Failed') . "</p>";

// Test WhatsApp notifications  
$result2 = update_option('edubot_whatsapp_notifications', 1);
$test_results['whatsapp_enable'] = $result2;
echo "<p>Set WhatsApp notifications to 1: " . ($result2 ? '✅ Success' : '❌ Failed') . "</p>";

// Test school notifications
$result3 = update_option('edubot_school_notifications', 1);
$test_results['school_enable'] = $result3;
echo "<p>Set school notifications to 1: " . ($result3 ? '✅ Success' : '❌ Failed') . "</p>";

// Verify the values were saved
echo "<h3>Verification</h3>";
$email_check = get_option('edubot_email_notifications');
$whatsapp_check = get_option('edubot_whatsapp_notifications');
$school_check = get_option('edubot_school_notifications');

echo "<p><strong>Email notifications after update:</strong> " . ($email_check ? '✅ 1 (Enabled)' : '❌ 0 (Disabled)') . "</p>";
echo "<p><strong>WhatsApp notifications after update:</strong> " . ($whatsapp_check ? '✅ 1 (Enabled)' : '❌ 0 (Disabled)') . "</p>";
echo "<p><strong>School notifications after update:</strong> " . ($school_check ? '✅ 1 (Enabled)' : '❌ 0 (Disabled)') . "</p>";

// Test disabling
echo "<h3>Test Disabling</h3>";
update_option('edubot_email_notifications', 0);
update_option('edubot_whatsapp_notifications', 0);
update_option('edubot_school_notifications', 0);

$email_disabled = get_option('edubot_email_notifications');
$whatsapp_disabled = get_option('edubot_whatsapp_notifications');
$school_disabled = get_option('edubot_school_notifications');

echo "<p><strong>After setting to 0:</strong></p>";
echo "<p>Email: " . ($email_disabled ? '❌ Still 1' : '✅ Correctly 0') . "</p>";
echo "<p>WhatsApp: " . ($whatsapp_disabled ? '❌ Still 1' : '✅ Correctly 0') . "</p>";
echo "<p>School: " . ($school_disabled ? '❌ Still 1' : '✅ Correctly 0') . "</p>";

echo "<h2>Form Processing Test</h2>";

// Simulate form submission
$_POST = [
    'edubot_school_name' => 'Test School',
    'edubot_whatsapp_notifications' => '1', // Checkbox checked
    'edubot_email_notifications' => '1',    // Checkbox checked
    // edubot_school_notifications not set = checkbox unchecked
    'submit' => 'Save School Settings',
    '_wpnonce' => wp_create_nonce('edubot_school_settings')
];

echo "<p><strong>Simulated POST data:</strong></p>";
echo "<ul>";
echo "<li>edubot_school_name: " . ($_POST['edubot_school_name'] ?? 'NOT SET') . "</li>";
echo "<li>edubot_whatsapp_notifications: " . (isset($_POST['edubot_whatsapp_notifications']) ? '✅ SET (1)' : '❌ NOT SET (0)') . "</li>";
echo "<li>edubot_email_notifications: " . (isset($_POST['edubot_email_notifications']) ? '✅ SET (1)' : '❌ NOT SET (0)') . "</li>";
echo "<li>edubot_school_notifications: " . (isset($_POST['edubot_school_notifications']) ? '✅ SET (1)' : '❌ NOT SET (0)') . "</li>";
echo "</ul>";

// Test the checkbox logic
echo "<h3>Checkbox Processing Logic Test</h3>";
$checkbox_email = isset($_POST['edubot_email_notifications']) ? 1 : 0;
$checkbox_whatsapp = isset($_POST['edubot_whatsapp_notifications']) ? 1 : 0;
$checkbox_school = isset($_POST['edubot_school_notifications']) ? 1 : 0;

echo "<p>Email checkbox result: {$checkbox_email}</p>";
echo "<p>WhatsApp checkbox result: {$checkbox_whatsapp}</p>";
echo "<p>School checkbox result: {$checkbox_school}</p>";

echo "<h2>Summary</h2>";

if (all($test_results)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "<strong>✅ All Tests Passed!</strong><br>";
    echo "The notification settings should now work correctly in the admin panel.";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ Some Tests Failed</strong><br>";
    echo "There may be database or WordPress configuration issues.";
    echo "</div>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Go to <strong>Admin > EduBot Pro > School Settings</strong></li>";
echo "<li>Check the 'WhatsApp Notifications' checkbox</li>";
echo "<li>Click 'Save School Settings'</li>";
echo "<li>Refresh the page and verify the checkbox stays checked</li>";
echo "<li>Test submitting an enquiry to see if WhatsApp is sent</li>";
echo "</ol>";

// Clean up test POST data
unset($_POST);

function all($array) {
    foreach ($array as $value) {
        if (!$value) return false;
    }
    return true;
}

echo "<hr><p><em>Test completed on: " . date('Y-m-d H:i:s') . "</em></p>";
?>
