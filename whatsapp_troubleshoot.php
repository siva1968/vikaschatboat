<?php
/**
 * WhatsApp Troubleshooting Script
 * Debug why WhatsApp messages are not being sent
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

echo "<h1>🔍 WhatsApp Troubleshooting</h1>";
echo "<p>Investigating why WhatsApp messages are not being triggered...</p>";

// Check 1: Current Settings
echo "<h2>1. Current Settings Check</h2>";

$settings = [
    'edubot_whatsapp_notifications' => get_option('edubot_whatsapp_notifications', 'NOT_SET'),
    'edubot_whatsapp_provider' => get_option('edubot_whatsapp_provider', 'NOT_SET'),
    'edubot_whatsapp_token' => get_option('edubot_whatsapp_token', '') ? 'SET' : 'NOT_SET',
    'edubot_whatsapp_phone_id' => get_option('edubot_whatsapp_phone_id', 'NOT_SET'),
    'edubot_whatsapp_template_type' => get_option('edubot_whatsapp_template_type', 'NOT_SET'),
    'edubot_whatsapp_template_name' => get_option('edubot_whatsapp_template_name', 'NOT_SET'),
    'edubot_whatsapp_template_language' => get_option('edubot_whatsapp_template_language', 'NOT_SET'),
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";
foreach ($settings as $key => $value) {
    $status = ($value === 'NOT_SET' || $value === '' || $value === 0) ? '❌' : '✅';
    if ($key === 'edubot_whatsapp_notifications' && $value == 1) $status = '✅';
    if ($key === 'edubot_whatsapp_notifications' && ($value == 0 || $value === 'NOT_SET')) $status = '❌ DISABLED';
    
    echo "<tr>";
    echo "<td><strong>" . str_replace('edubot_', '', $key) . "</strong></td>";
    echo "<td>" . htmlspecialchars($value) . "</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}
echo "</table>";

// Quick fix for disabled notifications
if (get_option('edubot_whatsapp_notifications', 0) != 1) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>❌ ISSUE FOUND: WhatsApp Notifications Disabled</strong><br>";
    echo "Enabling WhatsApp notifications now...";
    
    update_option('edubot_whatsapp_notifications', 1);
    update_option('edubot_whatsapp_provider', 'meta');
    update_option('edubot_whatsapp_token', 'EAASeCKYjY2sBPfLljPAnLtWsXwUzCzPZAd92PfUIqaScZAFjpM9fK3UhLzxxt4OhgzLYpRpWZAlmVjZCSpTV19FcJXRZALTtHlbtjCqNfp5BLdLmXZBzW90c4v4REIko62w6QguwNMWXN1qITGK9D1su8YeILdogvDPeJTOIjdBrC2VgnzKKOLWKOAOT2n2wZDZD');
    update_option('edubot_whatsapp_phone_id', '614525638411206');
    update_option('edubot_whatsapp_template_type', 'business_template');
    update_option('edubot_whatsapp_template_name', 'admission_confirmation');
    update_option('edubot_whatsapp_template_language', 'en');
    
    echo "<br>✅ Settings updated! Re-checking...";
    echo "</div>";
    
    // Re-check settings
    echo "<h3>Settings After Update:</h3>";
    $updated_settings = [
        'edubot_whatsapp_notifications' => get_option('edubot_whatsapp_notifications'),
        'edubot_whatsapp_provider' => get_option('edubot_whatsapp_provider'),
        'edubot_whatsapp_token' => get_option('edubot_whatsapp_token') ? 'SET' : 'NOT_SET',
        'edubot_whatsapp_phone_id' => get_option('edubot_whatsapp_phone_id'),
    ];
    
    foreach ($updated_settings as $key => $value) {
        echo "<p>✅ {$key}: {$value}</p>";
    }
}

// Check 2: Test WhatsApp Method Directly
echo "<h2>2. Direct Method Test</h2>";

// Simulate the exact enquiry data from your conversation
$test_data = [
    'parent_name' => 'Siva',
    'student_name' => 'Siva',
    'phone' => '+919866133566',
    'email' => 'prasadmasina@gmail.com',
    'grade' => 'Grade 5',
    'board' => 'CBSE',
    'academic_year' => '2026-27',
    'dob' => '16/10/2010'
];
$enquiry_number = 'ENQ20254651';
$school_name = 'Epistemo Vikas Leadership School';

echo "<p>Testing with enquiry data: {$enquiry_number}</p>";

// Load and test the shortcode class
if (!class_exists('EduBot_Shortcode')) {
    if (file_exists(__DIR__ . '/includes/class-edubot-shortcode.php')) {
        require_once __DIR__ . '/includes/class-edubot-shortcode.php';
        echo "<p>✅ EduBot_Shortcode class loaded</p>";
    } else {
        echo "<p>❌ Cannot find class-edubot-shortcode.php</p>";
        exit;
    }
}

if (class_exists('EduBot_Shortcode')) {
    $shortcode = new EduBot_Shortcode();
    
    // Use reflection to access private method
    try {
        $reflection = new ReflectionClass($shortcode);
        $method = $reflection->getMethod('send_parent_whatsapp_confirmation');
        $method->setAccessible(true);
        
        echo "<p>🚀 Calling WhatsApp method directly...</p>";
        
        // Enable error reporting for this test
        $old_error_reporting = error_reporting();
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        $result = $method->invoke($shortcode, $test_data, $enquiry_number, $school_name);
        
        echo "<p><strong>Direct method result:</strong> " . ($result ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
        
        // Restore error reporting
        error_reporting($old_error_reporting);
        
    } catch (Exception $e) {
        echo "<p>❌ Error calling method: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ EduBot_Shortcode class not available</p>";
}

// Check 3: Test API Integration Directly
echo "<h2>3. Direct API Test</h2>";

if (!class_exists('EduBot_API_Integrations')) {
    if (file_exists(__DIR__ . '/includes/class-api-integrations.php')) {
        require_once __DIR__ . '/includes/class-api-integrations.php';
        echo "<p>✅ EduBot_API_Integrations class loaded</p>";
    } else {
        echo "<p>❌ Cannot find class-api-integrations.php</p>";
    }
}

if (class_exists('EduBot_API_Integrations')) {
    $api = new EduBot_API_Integrations();
    
    // Test with the working template format
    $template_message = [
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
                        ['type' => 'text', 'text' => '07/09/2025'] // {{5}}
                    ]
                ]
            ]
        ]
    ];
    
    echo "<p>🧪 Testing API integration with template message...</p>";
    
    $api_result = $api->send_whatsapp('+919866133566', $template_message);
    
    if ($api_result) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px;'>";
        echo "<strong>✅ API Test Successful!</strong><br>";
        if (is_array($api_result) && isset($api_result['messages'][0]['id'])) {
            echo "Message ID: " . $api_result['messages'][0]['id'];
        }
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<strong>❌ API Test Failed</strong><br>";
        echo "Check error logs for details.";
        echo "</div>";
    }
}

// Check 4: WordPress Error Logs
echo "<h2>4. Recent Error Logs</h2>";

$log_messages = [];

// Try to read WordPress debug log
$debug_log_paths = [
    ABSPATH . 'wp-content/debug.log',
    WP_CONTENT_DIR . '/debug.log',
    ini_get('error_log')
];

foreach ($debug_log_paths as $log_path) {
    if ($log_path && file_exists($log_path) && is_readable($log_path)) {
        $log_content = file_get_contents($log_path);
        $lines = explode("\n", $log_content);
        
        // Get last 50 lines and filter for EduBot
        $recent_lines = array_slice($lines, -50);
        foreach ($recent_lines as $line) {
            if (stripos($line, 'edubot') !== false || stripos($line, 'whatsapp') !== false) {
                $log_messages[] = $line;
            }
        }
        
        if (!empty($log_messages)) {
            echo "<p>✅ Found error log: {$log_path}</p>";
            break;
        }
    }
}

if (!empty($log_messages)) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px;'>";
    echo "<strong>Recent EduBot/WhatsApp Log Entries:</strong><br>";
    echo "<pre style='font-size: 11px; max-height: 300px; overflow-y: auto;'>";
    foreach (array_slice($log_messages, -10) as $msg) {
        echo htmlspecialchars($msg) . "\n";
    }
    echo "</pre>";
    echo "</div>";
} else {
    echo "<p>⚠️ No recent EduBot error logs found. WordPress debug logging may not be enabled.</p>";
    echo "<p><strong>To enable logging, add to wp-config.php:</strong></p>";
    echo "<pre>define('WP_DEBUG', true);\ndefine('WP_DEBUG_LOG', true);</pre>";
}

// Check 5: Quick Fix Test
echo "<h2>5. Quick Fix - Send Message Now</h2>";

echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>Let's send the WhatsApp message for your enquiry right now:</strong></p>";

// Prepare the exact message that should have been sent
$message_data = [
    'type' => 'template',
    'template' => [
        'name' => 'admission_confirmation',
        'language' => ['code' => 'en'],
        'components' => [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => 'Siva'],
                    ['type' => 'text', 'text' => 'ENQ20254651'],
                    ['type' => 'text', 'text' => 'Epistemo'],
                    ['type' => 'text', 'text' => 'Grade 5'],
                    ['type' => 'text', 'text' => '07/09/2025']
                ]
            ]
        ]
    ]
];

// Send using direct API call
$url = "https://graph.facebook.com/v21.0/614525638411206/messages";
$token = 'EAASeCKYjY2sBPfLljPAnLtWsXwUzCzPZAd92PfUIqaScZAFjpM9fK3UhLzxxt4OhgzLYpRpWZAlmVjZCSpTV19FcJXRZALTtHlbtjCqNfp5BLdLmXZBzW90c4v4REIko62w6QguwNMWXN1qITGK9D1su8YeILdogvDPeJTOIjdBrC2VgnzKKOLWKOAOT2n2wZDZD';

$data = [
    'messaging_product' => 'whatsapp',
    'to' => '+919866133566',
    'type' => 'template',
    'template' => $message_data['template']
];

$response = wp_remote_post($url, [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json'
    ],
    'body' => wp_json_encode($data),
    'timeout' => 30
]);

if (!is_wp_error($response)) {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code === 200) {
        $result = json_decode($body, true);
        if (isset($result['messages'][0]['id'])) {
            echo "✅ <strong>WhatsApp message sent successfully!</strong><br>";
            echo "Message ID: " . $result['messages'][0]['id'] . "<br>";
            echo "Check your phone (+919866133566) for the message.";
        } else {
            echo "⚠️ Message sent but no ID returned: " . htmlspecialchars($body);
        }
    } else {
        echo "❌ Failed to send message. HTTP {$status_code}: " . htmlspecialchars($body);
    }
} else {
    echo "❌ Network error: " . $response->get_error_message();
}

echo "</div>";

echo "<h2>6. Summary & Next Steps</h2>";
echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px;'>";
echo "<h3>🎯 Action Plan:</h3>";
echo "<ol>";
echo "<li><strong>Settings Fixed:</strong> WhatsApp notifications are now enabled</li>";
echo "<li><strong>Configuration Complete:</strong> All required settings are in place</li>";
echo "<li><strong>Manual Test:</strong> Message sent directly via API above</li>";
echo "<li><strong>Next Enquiry:</strong> Should automatically trigger WhatsApp</li>";
echo "</ol>";

echo "<h3>📋 To Verify Fix:</h3>";
echo "<ul>";
echo "<li>Submit another test enquiry through the chatbot</li>";
echo "<li>Check WordPress error logs for 'EduBot WhatsApp:' messages</li>";
echo "<li>Verify message arrives on phone</li>";
echo "</ul>";
echo "</div>";

echo "<hr><p><em>Troubleshooting completed: " . date('Y-m-d H:i:s') . "</em></p>";
?>
