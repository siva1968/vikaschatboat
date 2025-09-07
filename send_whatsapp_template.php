<?php
/**
 * WhatsApp Template Message Sender
 * Uses your approved business template
 */

// Your WhatsApp Business API Configuration
$ACCESS_TOKEN = 'EAASeCKYjY2sBPfLljPAnLtWsXwUzCzPZAd92PfUIqaScZAFjpM9fK3UhLzxxt4OhgzLYpRpWZAlmVjZCSpTV19FcJXRZALTtHlbtjCqNfp5BLdLmXZBzW90c4v4REIko62w6QguwNMWXN1qITGK9D1su8YeILdogvDPeJTOIjdBrC2VgnzKKOLWKOAOT2n2wZDZD';
$PHONE_NUMBER_ID = '614525638411206';
$TO_PHONE = '+919866133566';  // Sujay's phone
$TEMPLATE_NAME = 'admission_confirmation';
$TEMPLATE_LANGUAGE = 'en';

// Template parameters based on enquiry data
$parameters = [
    'Sujay Parent',                           // Parent name
    'Epistemo Vikas Leadership School',       // School name  
    'ENQ20251615',                           // Enquiry number
    'Grade 10',                              // Grade
    '07/09/2025'                            // Submission date
];

// API endpoint
$url = "https://graph.facebook.com/v21.0/{$PHONE_NUMBER_ID}/messages";

// Template message payload
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
                'type' => 'body',
                'parameters' => array_map(function($param) {
                    return ['type' => 'text', 'text' => $param];
                }, $parameters)
            ]
        ]
    ]
];

// Headers
$headers = [
    'Authorization: Bearer ' . $ACCESS_TOKEN,
    'Content-Type: application/json'
];

echo "🚀 Sending WhatsApp Template Message...\n";
echo "To: {$TO_PHONE}\n";
echo "Template: {$TEMPLATE_NAME}\n";
echo "Parameters:\n";
foreach ($parameters as $i => $param) {
    echo "  " . ($i + 1) . ". {$param}\n";
}
echo "\n";

// Send request
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

// Handle response
if ($error) {
    echo "❌ cURL Error: {$error}\n";
} else {
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['messages'])) {
        echo "✅ SUCCESS!\n";
        echo "Message ID: " . $result['messages'][0]['id'] . "\n";
        echo "Status: " . $result['messages'][0]['message_status'] . "\n";
        echo "\n🎉 Template message sent to Sujay's parent!\n";
    } else {
        echo "❌ FAILED!\n";
        echo "HTTP Code: {$httpCode}\n";
        if (isset($result['error'])) {
            echo "Error: " . $result['error']['message'] . "\n";
            echo "Code: " . $result['error']['code'] . "\n";
            if (isset($result['error']['error_subcode'])) {
                echo "Subcode: " . $result['error']['error_subcode'] . "\n";
            }
        }
        
        // Common error explanations
        if (isset($result['error']['code'])) {
            $errorCode = $result['error']['code'];
            echo "\n💡 Possible solutions:\n";
            switch ($errorCode) {
                case 131056:
                    echo "- Template '{$TEMPLATE_NAME}' may not be approved yet\n";
                    echo "- Check template status in Meta Business Manager\n";
                    break;
                case 131026:
                    echo "- Template parameter count mismatch\n";
                    echo "- Your template expects different number of parameters\n";
                    break;
                case 80007:
                    echo "- Phone number {$TO_PHONE} may not be registered on WhatsApp\n";
                    break;
                case 100:
                    echo "- Invalid parameter format\n";
                    echo "- Check phone number format and template parameters\n";
                    break;
            }
        }
    }
}

echo "\nFull API Response:\n";
echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n";

echo "\n📋 Debug Info:\n";
echo "API URL: {$url}\n";
echo "Payload: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
?>
