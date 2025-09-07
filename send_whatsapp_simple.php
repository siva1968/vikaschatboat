<?php
/**
 * Simple WhatsApp Message Sender
 * Quick test to send WhatsApp message with your credentials
 */

// Your WhatsApp Business API Configuration
$ACCESS_TOKEN = 'EAASeCKYjY2sBPfLljPAnLtWsXwUzCzPZAd92PfUIqaScZAFjpM9fK3UhLzxxt4OhgzLYpRpWZAlmVjZCSpTV19FcJXRZALTtHlbtjCqNfp5BLdLmXZBzW90c4v4REIko62w6QguwNMWXN1qITGK9D1su8YeILdogvDPeJTOIjdBrC2VgnzKKOLWKOAOT2n2wZDZD';
$PHONE_NUMBER_ID = '614525638411206';
$TO_PHONE = '+919866133566';  // Sujay's phone from conversation

// Message content based on the enquiry
$message = "Admission Enquiry Confirmation
Dear Sujay Parent,

Thank you for your enquiry at Epistemo Vikas Leadership School. Your enquiry number is ENQ20251615 for Grade 10.

We have received your application on 07/09/2025 and will contact you within 24-48 hours with the next steps.

Best regards,
Admissions Team
Reply STOP to unsubscribe";

// API endpoint
$url = "https://graph.facebook.com/v21.0/{$PHONE_NUMBER_ID}/messages";

// Message payload
$data = [
    'messaging_product' => 'whatsapp',
    'to' => $TO_PHONE,
    'type' => 'text',
    'text' => [
        'body' => $message
    ]
];

// Headers
$headers = [
    'Authorization: Bearer ' . $ACCESS_TOKEN,
    'Content-Type: application/json'
];

echo "🚀 Sending WhatsApp Message...\n";
echo "To: {$TO_PHONE}\n";
echo "Message: " . substr($message, 0, 50) . "...\n\n";

// Send request
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 30
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
    } else {
        echo "❌ FAILED!\n";
        echo "HTTP Code: {$httpCode}\n";
        if (isset($result['error'])) {
            echo "Error: " . $result['error']['message'] . "\n";
            echo "Code: " . $result['error']['code'] . "\n";
        } else {
            echo "Response: {$response}\n";
        }
    }
}

echo "\nFull Response:\n";
echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT);
?>
