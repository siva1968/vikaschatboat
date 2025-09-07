<?php
/**
 * Complete WhatsApp Flow Test
 * This simulates sending a WhatsApp message with actual data
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

echo "<h1>🧪 WhatsApp Flow Test</h1>";

// Test data (same format as what the enquiry form would send)
$test_data = [
    'parent_name' => 'Test Parent',
    'student_name' => 'Test Student',
    'phone' => '+919876543210', // Test phone - REPLACE with your actual number for real testing
    'email' => 'test@example.com',
    'grade' => 'Grade 5',
    'board' => 'CBSE',
    'academic_year' => '2025-26'
];
$test_enquiry_number = 'TEST' . date('YmdHis');
$test_school_name = 'Test School';

echo "<h2>1. Configuration Check</h2>";

// Check settings
$whatsapp_enabled = get_option('edubot_whatsapp_notifications', 0);
$whatsapp_token = get_option('edubot_whatsapp_token', '');
$whatsapp_provider = get_option('edubot_whatsapp_provider', '');
$whatsapp_phone_id = get_option('edubot_whatsapp_phone_id', '');

echo "<p><strong>WhatsApp Enabled:</strong> " . ($whatsapp_enabled ? '✅ YES' : '❌ NO') . "</p>";
echo "<p><strong>Provider:</strong> " . ($whatsapp_provider ?: '❌ NOT SET') . "</p>";
echo "<p><strong>Token:</strong> " . ($whatsapp_token ? '✅ SET' : '❌ NOT SET') . "</p>";
echo "<p><strong>Phone ID:</strong> " . ($whatsapp_phone_id ?: '❌ NOT SET') . "</p>";

if (!$whatsapp_enabled) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ WhatsApp is disabled!</strong><br>";
    echo "Enable it in: Admin > EduBot Pro > School Settings > Notification Settings";
    echo "</div>";
    exit;
}

if (!$whatsapp_token || !$whatsapp_provider) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ WhatsApp API not configured!</strong><br>";
    echo "Configure it in: Admin > EduBot Pro > API Integrations";
    echo "</div>";
    exit;
}

echo "<h2>2. Load API Integration Class</h2>";

// Load the API integration class
if (!class_exists('EduBot_API_Integrations')) {
    $api_file = __DIR__ . '/includes/class-api-integrations.php';
    if (file_exists($api_file)) {
        require_once $api_file;
        echo "<p>✅ API Integrations file loaded</p>";
    } else {
        echo "<p>❌ API Integrations file not found: {$api_file}</p>";
        exit;
    }
}

if (class_exists('EduBot_API_Integrations')) {
    echo "<p>✅ EduBot_API_Integrations class available</p>";
    $api = new EduBot_API_Integrations();
} else {
    echo "<p>❌ EduBot_API_Integrations class not found</p>";
    exit;
}

echo "<h2>3. Template Processing</h2>";

// Get template
$template = get_option('edubot_whatsapp_template', "Admission Enquiry Confirmation
Dear {parent_name},

Thank you for your enquiry at {school_name}. Your enquiry number is {enquiry_number} for Grade {grade}.

We have received your application on {submission_date} and will contact you within 24-48 hours with the next steps.

Best regards,
Admissions Team
Reply STOP to unsubscribe");

echo "<p><strong>Template loaded:</strong></p>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>" . esc_html($template) . "</pre>";

// Process placeholders
$placeholders = [
    '{school_name}' => $test_school_name,
    '{parent_name}' => $test_data['parent_name'],
    '{student_name}' => $test_data['student_name'],
    '{enquiry_number}' => $test_enquiry_number,
    '{grade}' => $test_data['grade'],
    '{board}' => $test_data['board'],
    '{academic_year}' => $test_data['academic_year'],
    '{submission_date}' => date('d/m/Y'),
    '{phone}' => $test_data['phone'],
    '{email}' => $test_data['email']
];

$final_message = str_replace(array_keys($placeholders), array_values($placeholders), $template);

echo "<p><strong>Final message:</strong></p>";
echo "<pre style='background: #e8f5e8; padding: 10px; border-radius: 5px; border: 1px solid #c3e6cb;'>" . esc_html($final_message) . "</pre>";

echo "<h2>4. Phone Number Validation</h2>";

$phone = $test_data['phone'];
echo "<p><strong>Original phone:</strong> {$phone}</p>";

// Clean phone number (same logic as in the main code)
$clean_phone = preg_replace('/[^0-9+]/', '', $phone);
echo "<p><strong>Cleaned phone:</strong> {$clean_phone}</p>";

// Add country code if needed
if (!preg_match('/^\+/', $clean_phone)) {
    if (preg_match('/^[6-9]\d{9}$/', $clean_phone)) {
        $clean_phone = '+91' . $clean_phone;
    } elseif (preg_match('/^91[6-9]\d{9}$/', $clean_phone)) {
        $clean_phone = '+' . $clean_phone;
    }
}

echo "<p><strong>Final phone:</strong> {$clean_phone}</p>";

echo "<h2>5. Test WhatsApp API Call</h2>";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>⚠️ Important:</strong> This will attempt to send a REAL WhatsApp message to {$clean_phone}!<br>";
echo "Make sure this is your test number. To proceed, uncomment the test code below.";
echo "</div>";

// UNCOMMENT THE LINES BELOW TO ACTUALLY TEST WHATSAPP SENDING
/*
echo "<p>🚀 Attempting to send WhatsApp message...</p>";
$result = $api->send_whatsapp($clean_phone, $final_message);

if ($result && !is_wp_error($result)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "<strong>✅ SUCCESS!</strong><br>";
    echo "WhatsApp message sent successfully!";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ FAILED!</strong><br>";
    if (is_wp_error($result)) {
        echo "Error: " . esc_html($result->get_error_message());
    } else {
        echo "Unknown error occurred";
    }
    echo "</div>";
}
*/

echo "<h2>6. Check WordPress Error Logs</h2>";
echo "<p>After running a real test, check your WordPress error logs for detailed information:</p>";
echo "<ul>";
echo "<li>Look for messages starting with 'EduBot:'</li>";
echo "<li>Check both success and error messages</li>";
echo "<li>Verify API response codes and messages</li>";
echo "</ul>";

echo "<h2>7. Manual Test Instructions</h2>";
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px;'>";
echo "<h4>To test WhatsApp in your live system:</h4>";
echo "<ol>";
echo "<li>Make sure all settings are configured (you can run debug_whatsapp.php)</li>";
echo "<li>Submit a real enquiry through your chatbot</li>";
echo "<li>Check WordPress error logs immediately after submission</li>";
echo "<li>Look for EduBot WhatsApp log entries to trace what happened</li>";
echo "</ol>";
echo "</div>";

echo "<hr><p><em>Test completed on: " . date('Y-m-d H:i:s') . "</em></p>";
?>
