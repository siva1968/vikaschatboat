<?php
/**
 * Standalone WhatsApp Configuration Test
 * Simple test without WordPress dependencies
 */

// Test phone number
$TEST_PHONE = '+919866133566';
$TEST_MESSAGE = '🧪 WhatsApp Configuration Test from EduBot Pro at ' . date('Y-m-d H:i:s') . '. If you received this message, your WhatsApp integration is working correctly!';

echo "=== WhatsApp Configuration Test ===\n";
echo "Target Phone: {$TEST_PHONE}\n";
echo "Test Time: " . date('Y-m-d H:i:s') . "\n";
echo "====================================\n\n";

/**
 * Test Meta WhatsApp Business API
 */
function test_meta_whatsapp_api($access_token, $phone_number_id, $recipient_phone, $message) {
    echo "Testing Meta WhatsApp Business API...\n";
    echo "Phone Number ID: {$phone_number_id}\n";
    echo "Access Token: " . substr($access_token, 0, 20) . "...\n\n";
    
    if (empty($access_token) || empty($phone_number_id)) {
        echo "❌ ERROR: Missing credentials\n";
        echo "Please configure Access Token and Phone Number ID\n";
        return false;
    }
    
    // Step 1: Test API connectivity
    echo "Step 1: Testing API Connectivity\n";
    echo "================================\n";
    
    $info_url = "https://graph.facebook.com/v17.0/{$phone_number_id}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $info_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $info_response = curl_exec($ch);
    $info_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        echo "❌ Network Error: {$curl_error}\n";
        return false;
    }
    
    echo "API Response Code: {$info_http_code}\n";
    
    if ($info_http_code === 200) {
        echo "✅ API connectivity successful!\n";
        $phone_info = json_decode($info_response, true);
        if (isset($phone_info['display_phone_number'])) {
            echo "Phone Number: {$phone_info['display_phone_number']}\n";
        }
        if (isset($phone_info['verified_name'])) {
            echo "Business Name: {$phone_info['verified_name']}\n";
        }
    } else {
        echo "❌ API Error:\n";
        echo $info_response . "\n";
        return false;
    }
    
    echo "\n";
    
    // Step 2: Send test message
    echo "Step 2: Sending Test Message\n";
    echo "============================\n";
    
    $send_url = "https://graph.facebook.com/v17.0/{$phone_number_id}/messages";
    
    // Clean phone number
    $clean_phone = preg_replace('/[^\d]/', '', $recipient_phone);
    
    $message_data = array(
        'messaging_product' => 'whatsapp',
        'to' => $clean_phone,
        'type' => 'text',
        'text' => array(
            'body' => $message
        )
    );
    
    echo "Recipient: {$clean_phone}\n";
    echo "Message: {$message}\n\n";
    
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
        echo "❌ Network Error: {$send_curl_error}\n";
        return false;
    }
    
    echo "Send Response Code: {$send_http_code}\n";
    echo "API Response:\n";
    echo $send_response . "\n\n";
    
    if ($send_http_code === 200) {
        $response_data = json_decode($send_response, true);
        if (isset($response_data['messages'][0]['id'])) {
            echo "✅ SUCCESS!\n";
            echo "Message sent successfully!\n";
            echo "Message ID: {$response_data['messages'][0]['id']}\n";
            echo "Check your phone ({$recipient_phone}) for the test message.\n";
            return true;
        }
    } else {
        echo "❌ FAILED!\n";
        echo "Message could not be sent.\n";
        
        $error_data = json_decode($send_response, true);
        if (isset($error_data['error'])) {
            echo "Error Code: {$error_data['error']['code']}\n";
            echo "Error Message: {$error_data['error']['message']}\n";
            if (isset($error_data['error']['error_subcode'])) {
                echo "Error Subcode: {$error_data['error']['error_subcode']}\n";
            }
        }
        return false;
    }
}

// Manual configuration section
echo "Manual Configuration Required\n";
echo "============================\n";
echo "Since WordPress is not available, please manually configure your WhatsApp credentials:\n\n";

echo "1. Edit this file (standalone_whatsapp_test.php)\n";
echo "2. Uncomment the manual configuration section below\n";
echo "3. Fill in your actual credentials\n";
echo "4. Run the test again\n\n";

echo "Configuration template:\n";
echo "// Uncomment and fill these values:\n";
echo "// \$access_token = 'YOUR_ACCESS_TOKEN_HERE';\n";
echo "// \$phone_number_id = 'YOUR_PHONE_NUMBER_ID_HERE';\n";
echo "// test_meta_whatsapp_api(\$access_token, \$phone_number_id, '{$TEST_PHONE}', '{$TEST_MESSAGE}');\n\n";

/*
// MANUAL CONFIGURATION - Uncomment and fill these values:
$access_token = 'YOUR_ACCESS_TOKEN_HERE';
$phone_number_id = 'YOUR_PHONE_NUMBER_ID_HERE';

// Run the test
test_meta_whatsapp_api($access_token, $phone_number_id, $TEST_PHONE, $TEST_MESSAGE);
*/

echo "Instructions:\n";
echo "============\n";
echo "1. Get your Access Token from Meta Business Manager → System Users\n";
echo "2. Get your Phone Number ID from WhatsApp Manager → API Setup\n";
echo "3. Uncomment the manual configuration section in this file\n";
echo "4. Replace 'YOUR_ACCESS_TOKEN_HERE' with your actual token\n";
echo "5. Replace 'YOUR_PHONE_NUMBER_ID_HERE' with your actual phone number ID\n";
echo "6. Run: php standalone_whatsapp_test.php\n\n";

echo "Expected result: WhatsApp message sent to {$TEST_PHONE}\n";

?>
