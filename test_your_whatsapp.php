<?php
/**
 * WhatsApp Template Test with Your Actual Configuration
 * Testing admission_confirmation template with your credentials
 */

// Your actual WhatsApp configuration
$ACCESS_TOKEN = 'EAASeCKYjY2sBPfLljPAnLtWsXwUzCzPZAd92PfUIqaScZAFjpM9fK3UhLzxxt4OhgzLYpRpWZAlmVjZCSpTV19FcJXRZALTtHlbtjCqNfp5BLdLmXZBzW90c4v4REIko62w6QguwNMWXN1qITGK9D1su8YeILdogvDPeJTOIjdBrC2VgnzKKOLWKOAOT2n2wZDZD';
$PHONE_NUMBER_ID = '614525638411206';
$TEMPLATE_NAMESPACE = '9eb1f1dc_68e7_42f1_802a_dbc7582c5c3a';
$TEMPLATE_NAME = 'admission_confirmation';
$TEMPLATE_LANGUAGE = 'en';

// Test data
$TEST_PHONE = '+919866133566';
$TEST_STUDENT_NAME = 'John Smith';
$TEST_SCHOOL_NAME = 'ABC International School';
$TEST_ENQUIRY_NUMBER = 'ENQ' . date('Ymd') . '001';
$TEST_GRADE = 'Grade 10';
$TEST_DATE = date('d/m/Y');

echo "=== WhatsApp Template Test with Your Configuration ===\n";
echo "Target Phone: {$TEST_PHONE}\n";
echo "Test Time: " . date('Y-m-d H:i:s') . "\n";
echo "Template: {$TEMPLATE_NAME}\n";
echo "Namespace: {$TEMPLATE_NAMESPACE}\n";
echo "Language: {$TEMPLATE_LANGUAGE}\n";
echo "=====================================================\n\n";

/**
 * Test WhatsApp Business API with your configuration
 */
function test_whatsapp_admission_confirmation() {
    global $ACCESS_TOKEN, $PHONE_NUMBER_ID, $TEMPLATE_NAMESPACE, $TEMPLATE_NAME, $TEMPLATE_LANGUAGE;
    global $TEST_PHONE, $TEST_STUDENT_NAME, $TEST_SCHOOL_NAME, $TEST_ENQUIRY_NUMBER, $TEST_GRADE, $TEST_DATE;
    
    // Step 1: Verify API connectivity
    echo "Step 1: Testing API Connectivity\n";
    echo "================================\n";
    
    $info_url = "https://graph.facebook.com/v17.0/{$PHONE_NUMBER_ID}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $info_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $ACCESS_TOKEN,
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
            echo "📱 Business Phone: {$phone_info['display_phone_number']}\n";
        }
        if (isset($phone_info['verified_name'])) {
            echo "🏢 Business Name: {$phone_info['verified_name']}\n";
        }
        echo "✅ Your WhatsApp Business API is working!\n\n";
    } else {
        echo "❌ API Error ({$info_http_code}):\n";
        echo $info_response . "\n";
        
        $error_data = json_decode($info_response, true);
        if (isset($error_data['error'])) {
            echo "Error: {$error_data['error']['message']}\n";
            echo "💡 Check your access token in Meta Business Manager\n";
        }
        return false;
    }
    
    // Step 2: Build and send template message
    echo "Step 2: Building Template Message\n";
    echo "=================================\n";
    
    $send_url = "https://graph.facebook.com/v17.0/{$PHONE_NUMBER_ID}/messages";
    $clean_phone = preg_replace('/[^\d]/', '', $TEST_PHONE);
    
    // Build template message payload according to your template structure
    $message_data = array(
        'messaging_product' => 'whatsapp',
        'to' => $clean_phone,
        'type' => 'template',
        'template' => array(
            'namespace' => $TEMPLATE_NAMESPACE,
            'name' => $TEMPLATE_NAME,
            'language' => array(
                'code' => $TEMPLATE_LANGUAGE
            ),
            'components' => array(
                array(
                    'type' => 'body',
                    'parameters' => array(
                        array(
                            'type' => 'text',
                            'text' => $TEST_STUDENT_NAME  // {{1}} - Student Name
                        ),
                        array(
                            'type' => 'text',
                            'text' => $TEST_ENQUIRY_NUMBER  // {{2}} - Enquiry Number
                        ),
                        array(
                            'type' => 'text',
                            'text' => $TEST_SCHOOL_NAME  // {{3}} - School Name
                        ),
                        array(
                            'type' => 'text',
                            'text' => $TEST_GRADE  // {{4}} - Grade
                        ),
                        array(
                            'type' => 'text',
                            'text' => $TEST_DATE  // {{5}} - Date
                        )
                    )
                )
            )
        )
    );
    
    echo "📋 Template Parameters:\n";
    echo "   {{1}} Student Name: {$TEST_STUDENT_NAME}\n";
    echo "   {{2}} Enquiry Number: {$TEST_ENQUIRY_NUMBER}\n";
    echo "   {{3}} School Name: {$TEST_SCHOOL_NAME}\n";
    echo "   {{4}} Grade: {$TEST_GRADE}\n";
    echo "   {{5}} Date: {$TEST_DATE}\n\n";
    
    echo "📱 Sending to: {$clean_phone}\n";
    echo "🏷️ Template: {$TEMPLATE_NAMESPACE}/{$TEMPLATE_NAME} ({$TEMPLATE_LANGUAGE})\n\n";
    
    echo "📦 Message Payload:\n";
    echo json_encode($message_data, JSON_PRETTY_PRINT) . "\n\n";
    
    // Step 3: Send the template message
    echo "Step 3: Sending Template Message\n";
    echo "================================\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $send_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $ACCESS_TOKEN,
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
    
    echo "📡 Response Code: {$send_http_code}\n";
    echo "📄 API Response:\n";
    echo $send_response . "\n\n";
    
    if ($send_http_code === 200) {
        $response_data = json_decode($send_response, true);
        if (isset($response_data['messages'][0]['id'])) {
            echo "🎉 SUCCESS! Template message sent successfully!\n";
            echo "📨 Message ID: {$response_data['messages'][0]['id']}\n";
            echo "📱 Check your phone ({$TEST_PHONE}) for the admission confirmation message.\n\n";
            
            echo "📋 Expected Message Format:\n";
            echo "==========================\n";
            echo "📚 Admission Enquiry Confirmation\n\n";
            echo "Dear {$TEST_STUDENT_NAME},\n\n";
            echo "Thank you for your enquiry at {$TEST_SCHOOL_NAME}. Your enquiry number is {$TEST_ENQUIRY_NUMBER} for {$TEST_GRADE}.\n\n";
            echo "We have received your application on {$TEST_DATE} and will contact you within 24-48 hours with next steps.\n\n";
            echo "Best regards,\nAdmissions Team\n";
            echo "==========================\n\n";
            
            echo "✅ Your WhatsApp template integration is working perfectly!\n";
            echo "🚀 Ready for production use with approved templates.\n";
            
            return true;
        }
    } else {
        echo "❌ FAILED! Template message could not be sent.\n";
        
        $error_data = json_decode($send_response, true);
        if (isset($error_data['error'])) {
            echo "📛 Error Code: {$error_data['error']['code']}\n";
            echo "📝 Error Message: {$error_data['error']['message']}\n";
            if (isset($error_data['error']['error_subcode'])) {
                echo "🔢 Error Subcode: {$error_data['error']['error_subcode']}\n";
            }
            
            // Provide specific troubleshooting
            echo "\n🔧 Troubleshooting:\n";
            if (strpos($error_data['error']['message'], 'template') !== false) {
                echo "📋 Template Issue:\n";
                echo "   - Verify template '{$TEMPLATE_NAME}' is APPROVED in Meta Business Manager\n";
                echo "   - Check namespace '{$TEMPLATE_NAMESPACE}' is correct\n";
                echo "   - Ensure template language '{$TEMPLATE_LANGUAGE}' matches\n";
                echo "   - Confirm template has 5 body parameters: {{1}} to {{5}}\n";
            } elseif (strpos($error_data['error']['message'], 'phone') !== false) {
                echo "📞 Phone Number Issue:\n";
                echo "   - Verify phone number {$TEST_PHONE} is valid\n";
                echo "   - Check if WhatsApp Business API can send to this number\n";
                echo "   - Ensure number is not blocked\n";
            } elseif (strpos($error_data['error']['message'], 'token') !== false) {
                echo "🔑 Access Token Issue:\n";
                echo "   - Verify access token is valid and not expired\n";
                echo "   - Check token permissions in Meta Business Manager\n";
                echo "   - Regenerate token if needed\n";
            }
        }
        return false;
    }
}

// Test with text message fallback
function test_text_message_fallback() {
    global $ACCESS_TOKEN, $PHONE_NUMBER_ID;
    global $TEST_PHONE, $TEST_STUDENT_NAME, $TEST_SCHOOL_NAME, $TEST_ENQUIRY_NUMBER, $TEST_GRADE, $TEST_DATE;
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Fallback Test: Text Message (if template fails)\n";
    echo str_repeat("=", 50) . "\n\n";
    
    $send_url = "https://graph.facebook.com/v17.0/{$PHONE_NUMBER_ID}/messages";
    $clean_phone = preg_replace('/[^\d]/', '', $TEST_PHONE);
    
    // Build text message matching template format
    $message_text = "📚 Admission Enquiry Confirmation\n\n";
    $message_text .= "Dear {$TEST_STUDENT_NAME},\n\n";
    $message_text .= "Thank you for your enquiry at {$TEST_SCHOOL_NAME}. Your enquiry number is {$TEST_ENQUIRY_NUMBER} for {$TEST_GRADE}.\n\n";
    $message_text .= "We have received your application on {$TEST_DATE} and will contact you within 24-48 hours with next steps.\n\n";
    $message_text .= "Best regards,\nAdmissions Team";
    
    $message_data = array(
        'messaging_product' => 'whatsapp',
        'to' => $clean_phone,
        'type' => 'text',
        'text' => array(
            'body' => $message_text
        )
    );
    
    echo "📝 Text Message Preview:\n";
    echo "========================\n";
    echo $message_text . "\n";
    echo "========================\n\n";
    
    echo "📤 Sending text message...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $send_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $ACCESS_TOKEN,
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $send_response = curl_exec($ch);
    $send_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Response Code: {$send_http_code}\n";
    echo "Response: {$send_response}\n\n";
    
    if ($send_http_code === 200) {
        $response_data = json_decode($send_response, true);
        if (isset($response_data['messages'][0]['id'])) {
            echo "✅ Text message sent successfully as fallback!\n";
            echo "Message ID: {$response_data['messages'][0]['id']}\n";
            return true;
        }
    }
    
    echo "❌ Text message also failed\n";
    return false;
}

// Run the template test
echo "🚀 Starting WhatsApp template test...\n\n";

$template_success = test_whatsapp_admission_confirmation();

if (!$template_success) {
    echo "\n🔄 Template failed, trying text message fallback...\n";
    $text_success = test_text_message_fallback();
    
    if ($text_success) {
        echo "\n💡 Recommendation: Templates may need approval or configuration adjustment.\n";
        echo "Text messages work, so your basic WhatsApp integration is functional.\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Test completed at " . date('Y-m-d H:i:s') . "\n";
echo "Check your phone {$TEST_PHONE} for messages!\n";
echo str_repeat("=", 60) . "\n";

?>
