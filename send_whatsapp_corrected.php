<?php
/**
 * WhatsApp Template Message - Corrected Version
 * Using your exact template configuration
 */

// Your WhatsApp Business API Configuration
$ACCESS_TOKEN = 'EAASeCKYjY2sBPfLljPAnLtWsXwUzCzPZAd92PfUIqaScZAFjpM9fK3UhLzxxt4OhgzLYpRpWZAlmVjZCSpTV19FcJXRZALTtHlbtjCqNfp5BLdLmXZBzW90c4v4REIko62w6QguwNMWXN1qITGK9D1su8YeILdogvDPeJTOIjdBrC2VgnzKKOLWKOAOT2n2wZDZD';
$PHONE_NUMBER_ID = '614525638411206';
$TO_PHONE = '+919866133566';  // Sujay's phone
$TEMPLATE_NAME = 'admission_confirmation';
$TEMPLATE_LANGUAGE = 'en';

// Template parameters based on your configuration:
// {{1}} = Parent/Student Name -> "Sujay"
// {{2}} = Enquiry Number -> "eq123456" (using your example)
// {{3}} = School Name -> "Epistemo" 
// {{4}} = Grade -> "Grade 1" (using your example)
// {{5}} = Date -> "08/10/2010" (using your example)

$parameters = [
    'Sujay',                    // {{1}} - Name
    'ENQ20251615',              // {{2}} - Enquiry Number (from conversation)
    'Epistemo',                 // {{3}} - School Name
    'Grade 10',                 // {{4}} - Grade (from conversation)
    '07/09/2025'               // {{5}} - Application Date
];

// API endpoint
$url = "https://graph.facebook.com/v21.0/{$PHONE_NUMBER_ID}/messages";

// Template message payload for Meta WhatsApp Business API
$data = [
    'messaging_product' => 'whatsapp',
    'to' => $TO_PHONE,
    'type' => 'template',
    'template' => [
        'name' => $TEMPLATE_NAME,
        'language' => [
            'code' => $TEMPLATE_LANGUAGE
        ],
        'components' => [
            [
                'type' => 'header',
                'parameters' => [] // Header is static text "Admission Enquiry Confirmation"
            ],
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $parameters[0]], // {{1}}
                    ['type' => 'text', 'text' => $parameters[1]], // {{2}}
                    ['type' => 'text', 'text' => $parameters[2]], // {{3}}
                    ['type' => 'text', 'text' => $parameters[3]], // {{4}}
                    ['type' => 'text', 'text' => $parameters[4]]  // {{5}}
                ]
            ]
        ]
    ]
];

// Headers
$headers = [
    'Authorization: Bearer ' . $ACCESS_TOKEN,
    'Content-Type: application/json'
];

echo "🚀 Sending WhatsApp Template Message (Corrected)...\n";
echo "To: {$TO_PHONE}\n";
echo "Template: {$TEMPLATE_NAME}\n";
echo "Language: {$TEMPLATE_LANGUAGE}\n\n";

echo "📋 Template Parameters:\n";
echo "{{1}} (Name): {$parameters[0]}\n";
echo "{{2}} (Enquiry): {$parameters[1]}\n";
echo "{{3}} (School): {$parameters[2]}\n";
echo "{{4}} (Grade): {$parameters[3]}\n";
echo "{{5}} (Date): {$parameters[4]}\n\n";

echo "📄 Expected Message:\n";
echo "Header: Admission Enquiry Confirmation\n\n";
echo "Body:\n";
echo "Dear {$parameters[0]},\n\n";
echo "Thank you for your enquiry at {$parameters[2]}. Your enquiry number is {$parameters[1]} for Grade {$parameters[3]}.\n\n";
echo "We have received your application on {$parameters[4]} and will contact you within 24-48 hours with the next steps.\n\n";
echo "Best regards,\n";
echo "Admissions Team\n\n";
echo "Footer: Reply STOP to unsubscribe\n\n";

// Send request
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_VERBOSE => false
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

echo "🔄 Sending...\n\n";

// Handle response
if ($error) {
    echo "❌ cURL Error: {$error}\n";
} else {
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['messages'])) {
        echo "✅ API SUCCESS!\n";
        echo "Message ID: " . $result['messages'][0]['id'] . "\n";
        
        if (isset($result['contacts'][0]['wa_id'])) {
            echo "WhatsApp ID: " . $result['contacts'][0]['wa_id'] . "\n";
        }
        
        echo "\n🎉 Template message sent!\n";
        echo "📱 Check WhatsApp on {$TO_PHONE} in the next few minutes.\n\n";
        
        echo "💡 If you still don't receive it:\n";
        echo "1. Check if the phone number is registered on WhatsApp\n";
        echo "2. Verify template 'admission_confirmation' is APPROVED in Meta Business Manager\n";
        echo "3. Check WhatsApp Business API delivery status in Meta dashboard\n";
        echo "4. Ensure the phone number is added to your WhatsApp Business account recipients\n";
        
    } else {
        echo "❌ API FAILED!\n";
        echo "HTTP Code: {$httpCode}\n";
        
        if (isset($result['error'])) {
            echo "Error Code: " . $result['error']['code'] . "\n";
            echo "Error Message: " . $result['error']['message'] . "\n";
            
            if (isset($result['error']['error_subcode'])) {
                echo "Error Subcode: " . $result['error']['error_subcode'] . "\n";
            }
            
            // Specific error troubleshooting
            $errorCode = $result['error']['code'];
            echo "\n💡 Troubleshooting:\n";
            switch ($errorCode) {
                case 131056:
                    echo "- Template 'admission_confirmation' is not approved\n";
                    echo "- Go to Meta Business Manager > WhatsApp > Message Templates\n";
                    echo "- Check if template status is 'APPROVED'\n";
                    break;
                case 131026:
                    echo "- Template parameter mismatch\n";
                    echo "- Your template expects different parameters\n";
                    echo "- Check template configuration in Meta Business Manager\n";
                    break;
                case 80007:
                    echo "- Phone number {$TO_PHONE} not registered on WhatsApp\n";
                    echo "- Or number not in your WhatsApp Business approved recipients\n";
                    break;
                case 100:
                    echo "- Invalid phone number format or missing required fields\n";
                    break;
                case 190:
                    echo "- Access token expired or invalid\n";
                    echo "- Generate new token from Meta Business Manager\n";
                    break;
            }
        }
    }
}

echo "\n📊 Full API Response:\n";
echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n";

echo "\n🔍 Debug Information:\n";
echo "API Endpoint: {$url}\n";
echo "Request Payload:\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
?>
