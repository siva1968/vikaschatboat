<?php
/**
 * Test Application Submission with Notification Tracking
 */

// Load WordPress
require_once('/xamppdev/htdocs/demo/wp-load.php');

global $wpdb;

// Suppress WordPress hooks to simulate direct submission
define('DOING_AJAX', false);

// Prepare test application data
$test_data = array(
    'student_name' => 'Test Student',
    'date_of_birth' => '2015-05-20',
    'grade' => 'Grade 3',
    'gender' => 'male',
    'parent_name' => 'Test Parent',
    'email' => 'test@example.com',
    'phone' => '9876543210',
    'address' => '123 Test Street, Test City',
    'educational_board' => 'CBSE',
    'academic_year' => '2025-26',
    'marketing_consent' => 1,
    'submitted_at' => current_time('mysql'),
    'submission_ip' => '127.0.0.1'
);

echo "<h2>Testing Application Submission & Notification System</h2>";
echo "<hr>";

// Generate application number
$prefix = 'APP-' . date('Y') . '-';
$suffix = str_pad(wp_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
$application_number = $prefix . $suffix;

// Save to applications table
$table = $wpdb->prefix . 'edubot_applications';
$result = $wpdb->insert(
    $table,
    array(
        'site_id' => get_current_blog_id(),
        'application_number' => $application_number,
        'student_data' => json_encode($test_data),
        'source' => 'form',
        'status' => 'pending',
        'email_sent' => 0,
        'whatsapp_sent' => 0,
        'sms_sent' => 0,
        'ip_address' => '127.0.0.1',
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    ),
    array('%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s')
);

if ($result === false) {
    echo "<p style='color: red;'><strong>❌ Database Error:</strong> " . $wpdb->last_error . "</p>";
    die();
}

$application_id = $wpdb->insert_id;
echo "<p style='color: green;'><strong>✅ Application Inserted:</strong> ID: {$application_id}, Number: {$application_number}</p>";

// Test sending notifications via API Integrations
if (!class_exists('EduBot_API_Integrations')) {
    echo "<p style='color: red;'><strong>❌ API Integrations class not found</strong></p>";
    die();
}

$api_integrations = new EduBot_API_Integrations();
$database_manager = new EduBot_Database_Manager();

echo "<h3>Testing Notification Sending:</h3>";

// Test email sending
echo "<p><strong>1. Testing Email to Parent:</strong></p>";
$subject = "Test Email - Admission Confirmation";
$message = "<h2>Test Email</h2><p>This is a test email sent at " . current_time('F j, Y H:i:s') . "</p>";
$headers = array('Content-Type: text/html; charset=UTF-8');

$email_result = $api_integrations->send_email('test@example.com', $subject, $message, $headers);

if ($email_result) {
    echo "<p style='color: green;'>✅ Email sent successfully</p>";
    // Update database
    $database_manager->update_notification_status($application_id, 'email', 1, 'applications');
    echo "<p>✅ Database status updated to email_sent = 1</p>";
} else {
    echo "<p style='color: red;'>❌ Email sending failed</p>";
}

// Verify status
echo "<h3>Verification - Current Application Status:</h3>";
$application = $wpdb->get_row($wpdb->prepare(
    "SELECT id, application_number, email_sent, whatsapp_sent, sms_sent FROM $table WHERE id = %d",
    $application_id
));

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Value</th></tr>";
echo "<tr><td>Application ID</td><td>" . $application->id . "</td></tr>";
echo "<tr><td>Application Number</td><td>" . $application->application_number . "</td></tr>";
echo "<tr><td>Email Sent</td><td>" . ($application->email_sent ? '✅ 1 (Sent)' : '❌ 0 (Not Sent)') . "</td></tr>";
echo "<tr><td>WhatsApp Sent</td><td>" . ($application->whatsapp_sent ? '✅ 1 (Sent)' : '❌ 0 (Not Sent)') . "</td></tr>";
echo "<tr><td>SMS Sent</td><td>" . ($application->sms_sent ? '✅ 1 (Sent)' : '❌ 0 (Not Sent)') . "</td></tr>";
echo "</table>";

echo "<h3>Debug Info:</h3>";
echo "<p><strong>Recent Debug Log entries:</strong></p>";
echo "<pre>";
system('powershell -Command "Get-Content -Path \"D:\\xamppdev\\htdocs\\demo\\wp-content\\debug.log\" -Tail 20"');
echo "</pre>";

?>
