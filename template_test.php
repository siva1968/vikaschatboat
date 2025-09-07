<?php
/**
 * WhatsApp Template Test with Backend Configuration
 * Tests WhatsApp template message similar to your admission confirmation
 */

// Test configuration
$TEST_PHONE = '+919866133566';
$TEST_STUDENT_NAME = 'John Smith';
$TEST_SCHOOL_NAME = 'ABC International School';
$TEST_ENQUIRY_NUMBER = 'ENQ' . date('Ymd') . '001';
$TEST_GRADE = 'Grade 10';
$TEST_DATE = date('d/m/Y');

echo "=== WhatsApp Template Test ===\n";
echo "Target Phone: {$TEST_PHONE}\n";
echo "Test Time: " . date('Y-m-d H:i:s') . "\n";
echo "Student: {$TEST_STUDENT_NAME}\n";
echo "School: {$TEST_SCHOOL_NAME}\n";
echo "Enquiry: {$TEST_ENQUIRY_NUMBER}\n";
echo "Grade: {$TEST_GRADE}\n";
echo "==============================\n\n";

/**
 * Test WhatsApp with Template Message
 */
function test_whatsapp_template($access_token, $phone_number_id, $template_namespace, $template_name, $template_language, $recipient_phone, $student_name, $enquiry_number, $school_name, $grade, $date) {
    echo "Testing WhatsApp Business API with Template...\n";
    echo "Phone Number ID: {$phone_number_id}\n";
    echo "Template Namespace: {$template_namespace}\n";
    echo "Template Name: {$template_name}\n";
    echo "Template Language: {$template_language}\n\n";
    
    if (empty($access_token) || empty($phone_number_id)) {
        echo "❌ ERROR: Missing basic credentials\n";
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
    
    // Step 2: Build and send template message
    echo "Step 2: Building Template Message\n";
    echo "=================================\n";
    
    $send_url = "https://graph.facebook.com/v17.0/{$phone_number_id}/messages";
    $clean_phone = preg_replace('/[^\d]/', '', $recipient_phone);
    
    // Check if template configuration is provided
    if (!empty($template_name)) {
        echo "Using Template Mode\n";
        echo "Template: {$template_name}\n";
        echo "Language: {$template_language}\n\n";
        
        // Build template message payload
        $message_data = array(
            'messaging_product' => 'whatsapp',
            'to' => $clean_phone,
            'type' => 'template',
            'template' => array(
                'name' => $template_name,
                'language' => array(
                    'code' => $template_language
                )
            )
        );
        
        // Add namespace if provided
        if (!empty($template_namespace)) {
            $message_data['template']['namespace'] = $template_namespace;
        }
        
        // Add template parameters
        $parameters = array();
        
        // Parameter 1: Student Name
        if (!empty($student_name)) {
            $parameters[] = array(
                'type' => 'text',
                'text' => $student_name
            );
        }
        
        // Parameter 2: Enquiry Number
        if (!empty($enquiry_number)) {
            $parameters[] = array(
                'type' => 'text',
                'text' => $enquiry_number
            );
        }
        
        // Parameter 3: School Name
        if (!empty($school_name)) {
            $parameters[] = array(
                'type' => 'text',
                'text' => $school_name
            );
        }
        
        // Parameter 4: Grade
        if (!empty($grade)) {
            $parameters[] = array(
                'type' => 'text',
                'text' => $grade
            );
        }
        
        // Parameter 5: Date
        $parameters[] = array(
            'type' => 'text',
            'text' => $date
        );
        
        // Add parameters to template
        if (!empty($parameters)) {
            $message_data['template']['components'] = array(
                array(
                    'type' => 'body',
                    'parameters' => $parameters
                )
            );
        }
        
        echo "Template Parameters:\n";
        echo "1. Student Name: {$student_name}\n";
        echo "2. Enquiry Number: {$enquiry_number}\n";
        echo "3. School Name: {$school_name}\n";
        echo "4. Grade: {$grade}\n";
        echo "5. Date: {$date}\n\n";
        
    } else {
        echo "Using Text Mode (Fallback)\n\n";
        
        // Build text message as fallback
        $message_text = "📚 Admission Enquiry Confirmation\n\n";
        $message_text .= "Dear {$student_name},\n\n";
        $message_text .= "Thank you for your enquiry at {$school_name}. Your enquiry number is {$enquiry_number} for {$grade}.\n\n";
        $message_text .= "We have received your application on {$date} and will contact you within 24-48 hours with next steps.\n\n";
        $message_text .= "Best regards,\nAdmissions Team";
        
        $message_data = array(
            'messaging_product' => 'whatsapp',
            'to' => $clean_phone,
            'type' => 'text',
            'text' => array(
                'body' => $message_text
            )
        );
        
        echo "Text Message Preview:\n";
        echo "--------------------\n";
        echo $message_text . "\n";
        echo "--------------------\n\n";
    }
    
    echo "Sending to: {$clean_phone}\n";
    echo "Message Payload:\n";
    echo json_encode($message_data, JSON_PRETTY_PRINT) . "\n\n";
    
    // Send the message
    echo "Step 3: Sending Message\n";
    echo "=======================\n";
    
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
    
    echo "Response Code: {$send_http_code}\n";
    echo "API Response:\n";
    echo $send_response . "\n\n";
    
    if ($send_http_code === 200) {
        $response_data = json_decode($send_response, true);
        if (isset($response_data['messages'][0]['id'])) {
            echo "✅ SUCCESS!\n";
            echo "Template message sent successfully!\n";
            echo "Message ID: {$response_data['messages'][0]['id']}\n";
            echo "Check your phone ({$recipient_phone}) for the admission confirmation message.\n\n";
            
            echo "Expected Message Format:\n";
            echo "------------------------\n";
            echo "📚 Admission Enquiry Confirmation\n\n";
            echo "Dear {$student_name},\n\n";
            echo "Thank you for your enquiry at {$school_name}. Your enquiry number is {$enquiry_number} for {$grade}.\n\n";
            echo "We have received your application on {$date} and will contact you within 24-48 hours with next steps.\n\n";
            echo "Best regards,\nAdmissions Team\n";
            echo "------------------------\n";
            
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
            
            // Provide specific guidance based on error
            if (strpos($error_data['error']['message'], 'template') !== false) {
                echo "\n🔧 Template Issue Detected:\n";
                echo "- Verify template '{$template_name}' is approved in Meta Business Manager\n";
                echo "- Check template namespace '{$template_namespace}' is correct\n";
                echo "- Ensure template language '{$template_language}' matches\n";
                echo "- Try disabling templates (use text mode) for testing\n";
            }
        }
        return false;
    }
}

// Configuration instructions
echo "Configuration Required\n";
echo "=====================\n";
echo "To test your WhatsApp template configuration:\n\n";

echo "1. Edit this file and uncomment the configuration section below\n";
echo "2. Fill in your WhatsApp credentials from EduBot backend\n";
echo "3. Configure your template settings\n";
echo "4. Run the test\n\n";

echo "Configuration Template:\n";
echo "// Uncomment and configure these values:\n";
echo "/*\n";
echo "\$access_token = 'YOUR_ACCESS_TOKEN_HERE';\n";
echo "\$phone_number_id = 'YOUR_PHONE_NUMBER_ID_HERE';\n";
echo "\$template_namespace = 'YOUR_TEMPLATE_NAMESPACE'; // Optional, can be empty\n";
echo "\$template_name = 'admission_confirmation'; // Your template name\n";
echo "\$template_language = 'en'; // Template language code\n";
echo "\n";
echo "test_whatsapp_template(\n";
echo "    \$access_token,\n";
echo "    \$phone_number_id,\n";
echo "    \$template_namespace,\n";
echo "    \$template_name,\n";
echo "    \$template_language,\n";
echo "    '{$TEST_PHONE}',\n";
echo "    '{$TEST_STUDENT_NAME}',\n";
echo "    '{$TEST_ENQUIRY_NUMBER}',\n";
echo "    '{$TEST_SCHOOL_NAME}',\n";
echo "    '{$TEST_GRADE}',\n";
echo "    '{$TEST_DATE}'\n";
echo ");\n";
echo "*/\n\n";

echo "For Text Mode Testing (no templates):\n";
echo "// Use this to test without templates:\n";
echo "/*\n";
echo "\$access_token = 'YOUR_ACCESS_TOKEN_HERE';\n";
echo "\$phone_number_id = 'YOUR_PHONE_NUMBER_ID_HERE';\n";
echo "\n";
echo "test_whatsapp_template(\n";
echo "    \$access_token,\n";
echo "    \$phone_number_id,\n";
echo "    '', // No namespace\n";
echo "    '', // No template name\n";
echo "    'en',\n";
echo "    '{$TEST_PHONE}',\n";
echo "    '{$TEST_STUDENT_NAME}',\n";
echo "    '{$TEST_ENQUIRY_NUMBER}',\n";
echo "    '{$TEST_SCHOOL_NAME}',\n";
echo "    '{$TEST_GRADE}',\n";
echo "    '{$TEST_DATE}'\n";
echo ");\n";
echo "*/\n\n";

/*
// CONFIGURATION SECTION - Uncomment and fill your credentials:

$access_token = 'YOUR_ACCESS_TOKEN_HERE';
$phone_number_id = 'YOUR_PHONE_NUMBER_ID_HERE';
$template_namespace = ''; // Optional, leave empty if not using
$template_name = 'admission_confirmation'; // Your template name
$template_language = 'en'; // Template language

// Run the template test
test_whatsapp_template(
    $access_token,
    $phone_number_id,
    $template_namespace,
    $template_name,
    $template_language,
    $TEST_PHONE,
    $TEST_STUDENT_NAME,
    $TEST_ENQUIRY_NUMBER,
    $TEST_SCHOOL_NAME,
    $TEST_GRADE,
    $TEST_DATE
);
*/

echo "How to Get Your Credentials:\n";
echo "===========================\n";
echo "1. Access Token: Meta Business Manager → System Users → Generate Token\n";
echo "2. Phone Number ID: WhatsApp Manager → API Setup → Phone Number ID\n";
echo "3. Template Namespace: WhatsApp Manager → Message Templates → Template Details\n";
echo "4. Template Name: The name you used when creating the template\n";
echo "5. Template Language: Language code (en, hi, es, etc.)\n\n";

echo "This will send an 'Admission Enquiry Confirmation' message to {$TEST_PHONE}\n";
echo "matching the format you specified in your template configuration.\n";

?>
