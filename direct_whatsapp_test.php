<?php
/**
 * Direct WhatsApp API Test
 * Tests WhatsApp configuration directly with the API to verify credentials
 */

// Configuration - Update these with your actual values
$TEST_PHONE = '+919866133566'; // Your phone number
$TEST_MESSAGE = '🧪 WhatsApp Configuration Test from EduBot Pro at ' . date('Y-m-d H:i:s') . '. If you received this message, your WhatsApp integration is working correctly!';

echo "<h1>🧪 Direct WhatsApp API Configuration Test</h1>\n";
echo "<p><strong>Target Phone:</strong> {$TEST_PHONE}</p>\n";
echo "<p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
echo "<hr>\n";

/**
 * Test Meta WhatsApp Business API
 */
function test_meta_whatsapp_direct($access_token, $phone_number_id, $recipient_phone, $message) {
    echo "<h2>📱 Testing Meta WhatsApp Business API</h2>\n";
    
    if (empty($access_token) || empty($phone_number_id)) {
        echo "<p style='color: red;'>❌ Missing credentials. Please configure Access Token and Phone Number ID.</p>\n";
        return false;
    }
    
    echo "<p><strong>Phone Number ID:</strong> {$phone_number_id}</p>\n";
    echo "<p><strong>Access Token:</strong> " . substr($access_token, 0, 20) . "...</p>\n";
    
    // First, test API connectivity by getting phone number info
    echo "<h3>Step 1: Testing API Connectivity</h3>\n";
    
    $info_url = "https://graph.facebook.com/v17.0/{$phone_number_id}";
    $info_headers = array(
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $info_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $info_headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $info_response = curl_exec($ch);
    $info_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        echo "<p style='color: red;'>❌ Network Error: {$curl_error}</p>\n";
        return false;
    }
    
    echo "<p><strong>API Response Code:</strong> {$info_http_code}</p>\n";
    
    if ($info_http_code === 200) {
        echo "<p style='color: green;'>✅ API connectivity successful!</p>\n";
        $phone_info = json_decode($info_response, true);
        if (isset($phone_info['display_phone_number'])) {
            echo "<p><strong>Phone Number:</strong> {$phone_info['display_phone_number']}</p>\n";
        }
        if (isset($phone_info['verified_name'])) {
            echo "<p><strong>Business Name:</strong> {$phone_info['verified_name']}</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ API Error: {$info_response}</p>\n";
        return false;
    }
    
    // Step 2: Send test message
    echo "<h3>Step 2: Sending Test Message</h3>\n";
    
    $send_url = "https://graph.facebook.com/v17.0/{$phone_number_id}/messages";
    
    // Clean phone number (remove + and any spaces)
    $clean_phone = preg_replace('/[^\d]/', '', $recipient_phone);
    
    $message_data = array(
        'messaging_product' => 'whatsapp',
        'to' => $clean_phone,
        'type' => 'text',
        'text' => array(
            'body' => $message
        )
    );
    
    echo "<p><strong>Recipient:</strong> {$clean_phone}</p>\n";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($message) . "</p>\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $send_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $send_response = curl_exec($ch);
    $send_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $send_curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($send_curl_error) {
        echo "<p style='color: red;'>❌ Network Error: {$send_curl_error}</p>\n";
        return false;
    }
    
    echo "<p><strong>Send Response Code:</strong> {$send_http_code}</p>\n";
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px;'>\n";
    echo "<strong>API Response:</strong><br>\n";
    echo "<pre>" . htmlspecialchars($send_response) . "</pre>\n";
    echo "</div>\n";
    
    if ($send_http_code === 200) {
        $response_data = json_decode($send_response, true);
        if (isset($response_data['messages'][0]['id'])) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 15px 0;'>\n";
            echo "<h3>✅ SUCCESS!</h3>\n";
            echo "<p><strong>Message sent successfully!</strong></p>\n";
            echo "<p><strong>Message ID:</strong> {$response_data['messages'][0]['id']}</p>\n";
            echo "<p><strong>Check your phone ({$recipient_phone}) for the test message.</strong></p>\n";
            echo "</div>\n";
            return true;
        }
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 15px 0;'>\n";
        echo "<h3>❌ FAILED!</h3>\n";
        echo "<p><strong>Message could not be sent.</strong></p>\n";
        
        $error_data = json_decode($send_response, true);
        if (isset($error_data['error'])) {
            echo "<p><strong>Error Code:</strong> {$error_data['error']['code']}</p>\n";
            echo "<p><strong>Error Message:</strong> {$error_data['error']['message']}</p>\n";
            if (isset($error_data['error']['error_subcode'])) {
                echo "<p><strong>Error Subcode:</strong> {$error_data['error']['error_subcode']}</p>\n";
            }
        }
        echo "</div>\n";
        return false;
    }
}

/**
 * Get configuration from WordPress options (if available)
 */
function get_whatsapp_config_from_wordpress() {
    // Try to load WordPress if available
    $wp_config_paths = array(
        '../../../wp-config.php',
        '../../../../wp-config.php',
        '../../../../../wp-config.php'
    );
    
    foreach ($wp_config_paths as $path) {
        if (file_exists($path)) {
            require_once($path);
            
            // Try to include security manager for decryption
            $security_file = __DIR__ . '/includes/class-edubot-security-manager.php';
            if (file_exists($security_file)) {
                require_once($security_file);
                
                try {
                    $security_manager = new EduBot_Security_Manager();
                    
                    $provider = get_option('edubot_whatsapp_provider', '');
                    $phone_id = get_option('edubot_whatsapp_phone_id', '');
                    $encrypted_token = get_option('edubot_whatsapp_token', '');
                    $use_templates = get_option('edubot_whatsapp_use_templates', 0);
                    
                    $token = '';
                    if ($encrypted_token && $security_manager) {
                        $token = $security_manager->decrypt_api_key($encrypted_token);
                    }
                    
                    return array(
                        'provider' => $provider,
                        'phone_id' => $phone_id,
                        'token' => $token,
                        'use_templates' => $use_templates,
                        'found_wordpress' => true
                    );
                } catch (Exception $e) {
                    echo "<p style='color: orange;'>⚠️ WordPress found but could not decrypt credentials: " . $e->getMessage() . "</p>\n";
                }
            }
            break;
        }
    }
    
    return array('found_wordpress' => false);
}

// Main execution
echo "<h2>Configuration Detection</h2>\n";

$config = get_whatsapp_config_from_wordpress();

if ($config['found_wordpress']) {
    echo "<p style='color: green;'>✅ WordPress configuration detected!</p>\n";
    echo "<div style='background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px;'>\n";
    echo "<strong>Detected Configuration:</strong><br>\n";
    echo "Provider: " . ($config['provider'] ?: 'Not set') . "<br>\n";
    echo "Phone ID: " . ($config['phone_id'] ?: 'Not set') . "<br>\n";
    echo "Token: " . ($config['token'] ? 'Available (' . strlen($config['token']) . ' chars)' : 'Not set') . "<br>\n";
    echo "Templates: " . ($config['use_templates'] ? 'Enabled' : 'Disabled') . "<br>\n";
    echo "</div>\n";
    
    if ($config['provider'] === 'meta' && $config['token'] && $config['phone_id']) {
        test_meta_whatsapp_direct($config['token'], $config['phone_id'], $TEST_PHONE, $TEST_MESSAGE);
    } else {
        echo "<p style='color: red;'>❌ Incomplete configuration. Please set up WhatsApp credentials in EduBot Pro → API Integrations.</p>\n";
    }
} else {
    echo "<p style='color: orange;'>⚠️ WordPress configuration not found. Manual configuration required.</p>\n";
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 15px 0;'>\n";
    echo "<h3>Manual Configuration Required</h3>\n";
    echo "<p>To test your WhatsApp configuration manually:</p>\n";
    echo "<ol>\n";
    echo "<li>Edit this file and add your credentials at the top</li>\n";
    echo "<li>Uncomment and fill the manual configuration section below</li>\n";
    echo "<li>Run the test again</li>\n";
    echo "</ol>\n";
    echo "</div>\n";
    
    // Manual configuration section (commented out by default)
    echo "<h3>Manual Configuration (Edit the PHP file to enable)</h3>\n";
    echo "<pre>\n";
    echo "// Uncomment and fill these lines in the PHP file:\n";
    echo "// \$manual_config = array(\n";
    echo "//     'provider' => 'meta',\n";
    echo "//     'token' => 'YOUR_ACCESS_TOKEN_HERE',\n";
    echo "//     'phone_id' => 'YOUR_PHONE_NUMBER_ID_HERE'\n";
    echo "// );\n";
    echo "// test_meta_whatsapp_direct(\$manual_config['token'], \$manual_config['phone_id'], '{$TEST_PHONE}', '{$TEST_MESSAGE}');\n";
    echo "</pre>\n";
    
    /*
    // MANUAL CONFIGURATION - Uncomment and fill these values to test manually:
    $manual_config = array(
        'provider' => 'meta',
        'token' => 'YOUR_ACCESS_TOKEN_HERE',
        'phone_id' => 'YOUR_PHONE_NUMBER_ID_HERE'
    );
    test_meta_whatsapp_direct($manual_config['token'], $manual_config['phone_id'], $TEST_PHONE, $TEST_MESSAGE);
    */
}

echo "<hr>\n";
echo "<h2>📋 Test Instructions</h2>\n";
echo "<div style='background: #e7f3ff; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; margin: 15px 0;'>\n";
echo "<h3>What This Test Does:</h3>\n";
echo "<ul>\n";
echo "<li>🔍 Reads your WhatsApp configuration from WordPress</li>\n";
echo "<li>🌐 Tests API connectivity with Meta WhatsApp Business API</li>\n";
echo "<li>📞 Retrieves your phone number information</li>\n";
echo "<li>📱 Sends a test message to {$TEST_PHONE}</li>\n";
echo "<li>✅ Confirms message delivery status</li>\n";
echo "</ul>\n";
echo "<h3>Expected Results:</h3>\n";
echo "<ul>\n";
echo "<li>✅ If successful: You'll receive a WhatsApp message on {$TEST_PHONE}</li>\n";
echo "<li>❌ If failed: Error details will help diagnose the issue</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; margin: 15px 0;'>\n";
echo "<h3>🛠️ Troubleshooting Common Issues:</h3>\n";
echo "<ul>\n";
echo "<li><strong>Invalid Access Token:</strong> Generate a permanent token in Meta Business Manager</li>\n";
echo "<li><strong>Phone Number Not Found:</strong> Verify Phone Number ID in WhatsApp Manager</li>\n";
echo "<li><strong>Message Rejected:</strong> Ensure phone number is verified for messaging</li>\n";
echo "<li><strong>Template Required:</strong> For production, you need approved message templates</li>\n";
echo "<li><strong>Rate Limits:</strong> Check if you've exceeded API rate limits</li>\n";
echo "</ul>\n";
echo "</div>\n";

?>
