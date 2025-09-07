<?php
/**
 * WordPress WhatsApp Configuration Fix
 * Upload this to your WordPress site and run it to fix WhatsApp settings
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('You need administrator privileges to run this script.');
}

echo "<h1>🔧 WhatsApp Configuration Fix</h1>";

// Your working configuration
$whatsapp_config = [
    'edubot_whatsapp_notifications' => 1,
    'edubot_email_notifications' => 1,
    'edubot_school_notifications' => 1,
    'edubot_whatsapp_provider' => 'meta',
    'edubot_whatsapp_token' => 'EAASeCKYjY2sBPfLljPAnLtWsXwUzCzPZAd92PfUIqaScZAFjpM9fK3UhLzxxt4OhgzLYpRpWZAlmVjZCSpTV19FcJXRZALTtHlbtjCqNfp5BLdLmXZBzW90c4v4REIko62w6QguwNMWXN1qITGK9D1su8YeILdogvDPeJTOIjdBrC2VgnzKKOLWKOAOT2n2wZDZD',
    'edubot_whatsapp_phone_id' => '614525638411206',
    'edubot_whatsapp_template_type' => 'business_template',
    'edubot_whatsapp_template_name' => 'admission_confirmation',
    'edubot_whatsapp_template_language' => 'en',
    'edubot_school_name' => 'Epistemo Vikas Leadership School'
];

echo "<h2>Current Settings Check</h2>";

// Check current values
foreach ($whatsapp_config as $option => $new_value) {
    $current_value = get_option($option, 'NOT_SET');
    echo "<p><strong>{$option}:</strong> ";
    
    if ($current_value == $new_value) {
        echo "✅ Already correct ({$current_value})</p>";
    } else {
        echo "❌ Wrong ({$current_value}) → Updating to ({$new_value})</p>";
        update_option($option, $new_value);
    }
}

echo "<h2>Updated Settings Verification</h2>";

// Verify all settings are now correct
$all_correct = true;
foreach ($whatsapp_config as $option => $expected_value) {
    $actual_value = get_option($option);
    $correct = ($actual_value == $expected_value);
    
    echo "<p><strong>{$option}:</strong> ";
    if ($correct) {
        echo "✅ {$actual_value}</p>";
    } else {
        echo "❌ Expected {$expected_value}, got {$actual_value}</p>";
        $all_correct = false;
    }
}

if ($all_correct) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "<strong>✅ All Settings Correct!</strong><br>";
    echo "WhatsApp notifications should now work automatically.";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ Some Settings Still Wrong</strong><br>";
    echo "There may be a caching or permissions issue.";
    echo "</div>";
}

// Test the WhatsApp integration
echo "<h2>Testing WhatsApp Integration</h2>";

// Test data from your enquiry
$test_data = [
    'parent_name' => 'Siva',
    'student_name' => 'Siva', 
    'phone' => '+919866133566',
    'email' => 'prasadmasina@gmail.com',
    'grade' => 'Grade 5',
    'board' => 'CBSE',
    'academic_year' => '2026-27'
];

echo "<p>🧪 Testing with enquiry data: ENQ20254651</p>";

// Load the shortcode class to test the method
if (class_exists('EduBot_Shortcode')) {
    $shortcode = new EduBot_Shortcode();
    
    // Test using reflection to access private method
    try {
        $reflection = new ReflectionClass($shortcode);
        $method = $reflection->getMethod('send_parent_whatsapp_confirmation');
        $method->setAccessible(true);
        
        echo "<p>🚀 Testing WhatsApp method...</p>";
        
        $result = $method->invoke($shortcode, $test_data, 'ENQ20254651', 'Epistemo Vikas Leadership School');
        
        if ($result) {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px;'>";
            echo "<strong>✅ WhatsApp Method Test Successful!</strong><br>";
            echo "Check your phone (+919866133566) for the message.";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
            echo "<strong>❌ WhatsApp Method Test Failed</strong><br>";
            echo "Check WordPress error logs for details.";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error testing method: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p>❌ EduBot_Shortcode class not found. Plugin may not be activated.</p>";
}

// Manual API test as fallback
echo "<h2>Direct API Test</h2>";

$direct_test_data = [
    'messaging_product' => 'whatsapp',
    'to' => '+919866133566',
    'type' => 'template',
    'template' => [
        'name' => 'admission_confirmation',
        'language' => ['code' => 'en'],
        'components' => [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => 'Siva'], // {{1}}
                    ['type' => 'text', 'text' => 'ENQ20254651'], // {{2}}
                    ['type' => 'text', 'text' => 'Epistemo'], // {{3}}
                    ['type' => 'text', 'text' => 'Grade 5'], // {{4}}
                    ['type' => 'text', 'text' => date('d/m/Y')] // {{5}}
                ]
            ]
        ]
    ]
];

$response = wp_remote_post('https://graph.facebook.com/v21.0/614525638411206/messages', [
    'headers' => [
        'Authorization' => 'Bearer ' . $whatsapp_config['edubot_whatsapp_token'],
        'Content-Type' => 'application/json'
    ],
    'body' => wp_json_encode($direct_test_data),
    'timeout' => 30
]);

if (!is_wp_error($response)) {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code === 200) {
        $result = json_decode($body, true);
        if (isset($result['messages'][0]['id'])) {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px;'>";
            echo "<strong>✅ Direct API Test Successful!</strong><br>";
            echo "Message ID: " . $result['messages'][0]['id'] . "<br>";
            echo "Your WhatsApp API is working correctly.";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<strong>❌ Direct API Test Failed</strong><br>";
        echo "HTTP {$status_code}: " . htmlspecialchars($body);
        echo "</div>";
    }
} else {
    echo "<p>❌ Network error: " . $response->get_error_message() . "</p>";
}

echo "<h2>Summary</h2>";
echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ Configuration Complete</h3>";
echo "<p>Your WhatsApp integration should now work automatically. Next time someone submits an enquiry:</p>";
echo "<ol>";
echo "<li>Data gets saved to database</li>";
echo "<li>Email confirmation is sent</li>";
echo "<li>WhatsApp message is sent automatically</li>";
echo "<li>School notification is sent</li>";
echo "</ol>";

echo "<h3>🧪 To Test:</h3>";
echo "<ol>";
echo "<li>Submit another enquiry through your chatbot</li>";
echo "<li>Check if WhatsApp message arrives automatically</li>";
echo "<li>Check WordPress error logs if issues persist</li>";
echo "</ol>";
echo "</div>";

echo "<hr><p><em>Configuration completed: " . date('Y-m-d H:i:s') . "</em></p>";
?>
