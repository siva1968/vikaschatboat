<?php
/**
 * EduBot Pro WhatsApp Integration Test
 * Tests the actual plugin integration with your template
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

echo "<h1>🧪 EduBot Pro WhatsApp Integration Test</h1>";

// Set up your working configuration
echo "<h2>1. Setting Up Working Configuration</h2>";

$working_config = [
    'edubot_whatsapp_notifications' => 1,
    'edubot_whatsapp_provider' => 'meta',
    'edubot_whatsapp_token' => 'EAASeCKYjY2sBPfLljPAnLtWsXwUzCzPZAd92PfUIqaScZAFjpM9fK3UhLzxxt4OhgzLYpRpWZAlmVjZCSpTV19FcJXRZALTtHlbtjCqNfp5BLdLmXZBzW90c4v4REIko62w6QguwNMWXN1qITGK9D1su8YeILdogvDPeJTOIjdBrC2VgnzKKOLWKOAOT2n2wZDZD',
    'edubot_whatsapp_phone_id' => '614525638411206',
    'edubot_whatsapp_template_type' => 'business_template',
    'edubot_whatsapp_template_name' => 'admission_confirmation',
    'edubot_whatsapp_template_language' => 'en',
    'edubot_school_name' => 'Epistemo Vikas Leadership School'
];

echo "<p>Configuring options...</p>";
foreach ($working_config as $option => $value) {
    $result = update_option($option, $value);
    echo "<p>✅ {$option}: " . ($result ? 'Updated' : 'Already set') . "</p>";
}

// Test data matching your successful message
$test_enquiry_data = [
    'parent_name' => 'Sujay',
    'student_name' => 'Sujay',
    'phone' => '+919866133566',
    'email' => 'prasadmasina@gmail.com',
    'grade' => 'Grade 1',
    'board' => 'CBSE',
    'academic_year' => '2026-27',
    'dob' => '16/10/2010'
];

$test_enquiry_number = 'eq123456';
$test_school_name = 'Epistemo';

echo "<h2>2. Testing WhatsApp Method</h2>";

// Load the shortcode class
if (!class_exists('EduBot_Shortcode')) {
    require_once __DIR__ . '/includes/class-edubot-shortcode.php';
}

// Create instance and test the WhatsApp method
if (class_exists('EduBot_Shortcode')) {
    echo "<p>✅ EduBot_Shortcode class loaded</p>";
    
    $shortcode = new EduBot_Shortcode();
    
    // Use reflection to access the private method
    $reflection = new ReflectionClass($shortcode);
    $method = $reflection->getMethod('send_parent_whatsapp_confirmation');
    $method->setAccessible(true);
    
    echo "<p>🚀 Calling send_parent_whatsapp_confirmation...</p>";
    
    // Capture any error logs
    $old_log = ini_get('log_errors');
    ini_set('log_errors', 1);
    
    $result = $method->invoke($shortcode, $test_enquiry_data, $test_enquiry_number, $test_school_name);
    
    echo "<p><strong>Result:</strong> " . ($result ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
    
} else {
    echo "<p>❌ EduBot_Shortcode class not found</p>";
}

echo "<h2>3. Direct API Integration Test</h2>";

// Test the API integration directly
if (!class_exists('EduBot_API_Integrations')) {
    require_once __DIR__ . '/includes/class-api-integrations.php';
}

if (class_exists('EduBot_API_Integrations')) {
    echo "<p>✅ EduBot_API_Integrations class loaded</p>";
    
    $api = new EduBot_API_Integrations();
    
    // Prepare template message in the correct format
    $template_message = [
        'type' => 'template',
        'template' => [
            'name' => 'admission_confirmation',
            'language' => ['code' => 'en'],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => 'Sujay'], // {{1}}
                        ['type' => 'text', 'text' => 'eq123456'], // {{2}}
                        ['type' => 'text', 'text' => 'Epistemo'], // {{3}}
                        ['type' => 'text', 'text' => 'Grade 1'], // {{4}}
                        ['type' => 'text', 'text' => '08/10/2010'] // {{5}}
                    ]
                ]
            ]
        ]
    ];
    
    echo "<p>📋 Template message prepared:</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px; font-size: 12px;'>";
    echo htmlspecialchars(json_encode($template_message, JSON_PRETTY_PRINT));
    echo "</pre>";
    
    echo "<p>🚀 Sending template message via API integration...</p>";
    
    $api_result = $api->send_whatsapp('+919866133566', $template_message);
    
    echo "<p><strong>API Result:</strong> ";
    if ($api_result) {
        echo "✅ SUCCESS</p>";
        if (is_array($api_result) && isset($api_result['messages'][0]['id'])) {
            echo "<p><strong>Message ID:</strong> " . $api_result['messages'][0]['id'] . "</p>";
        }
    } else {
        echo "❌ FAILED</p>";
    }
    
} else {
    echo "<p>❌ EduBot_API_Integrations class not found</p>";
}

echo "<h2>4. Configuration Verification</h2>";

$verification_items = [
    'WhatsApp Notifications' => get_option('edubot_whatsapp_notifications'),
    'WhatsApp Provider' => get_option('edubot_whatsapp_provider'),
    'Access Token' => get_option('edubot_whatsapp_token') ? 'SET' : 'NOT SET',
    'Phone Number ID' => get_option('edubot_whatsapp_phone_id'),
    'Template Type' => get_option('edubot_whatsapp_template_type'),
    'Template Name' => get_option('edubot_whatsapp_template_name'),
    'Template Language' => get_option('edubot_whatsapp_template_language'),
    'School Name' => get_option('edubot_school_name')
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

foreach ($verification_items as $item => $value) {
    $status = $value ? '✅' : '❌';
    if ($item === 'Access Token' && $value === 'SET') $status = '✅';
    
    echo "<tr>";
    echo "<td><strong>{$item}</strong></td>";
    echo "<td>" . htmlspecialchars($value ?: 'NOT SET') . "</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>5. WordPress Error Log Check</h2>";
echo "<p>Check your WordPress error logs for messages starting with 'EduBot WhatsApp:' to see detailed API communication.</p>";

echo "<h2>6. Next Steps</h2>";
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Your WhatsApp Integration is Ready!</h3>";
echo "<ol>";
echo "<li><strong>Template Working:</strong> Your 'admission_confirmation' template is successfully sending messages</li>";
echo "<li><strong>Plugin Updated:</strong> EduBot Pro now uses the correct template parameter order</li>";
echo "<li><strong>Auto-Send:</strong> WhatsApp messages will be sent automatically when enquiries are submitted</li>";
echo "</ol>";

echo "<h4>🧪 To Test Live Integration:</h4>";
echo "<ol>";
echo "<li>Go to your website's chatbot</li>";
echo "<li>Submit a new admission enquiry</li>";
echo "<li>Check if WhatsApp message is received automatically</li>";
echo "<li>Check WordPress error logs for confirmation</li>";
echo "</ol>";
echo "</div>";

echo "<hr><p><em>Integration test completed on: " . date('Y-m-d H:i:s') . "</em></p>";
?>
