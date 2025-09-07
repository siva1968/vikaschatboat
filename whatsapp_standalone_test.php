<?php
/**
 * Standalone WhatsApp Test Script
 * Tests WhatsApp Business API with real enquiry data
 */

// Configuration - Replace with your actual credentials
$config = [
    'access_token' => 'EAASeCKYjY2sBPfLljPAnLtWsXwUzCzPZAd92PfUIqaScZAFjpM9fK3UhLzxxt4OhgzLYpRpWZAlmVjZCSpTV19FcJXRZALTtHlbtjCqNfp5BLdLmXZBzW90c4v4REIko62w6QguwNMWXN1qITGK9D1su8YeILdogvDPeJTOIjdBrC2VgnzKKOLWKOAOT2n2wZDZD',
    'phone_number_id' => '614525638411206',
    'template_namespace' => '9eb1f1dc_68e7_42f1_802a_dbc7582c5c3a',
    'template_name' => 'admission_confirmation',
    'template_language' => 'en',
    'api_version' => 'v21.0'
];

// Test data from the conversation
$enquiry_data = [
    'student_name' => 'Sujay',
    'parent_name' => 'Sujay Parent', // Assuming parent name
    'email' => 'prasadmasina@gmail.com',
    'phone' => '+919866133566',
    'grade' => 'Grade 10',
    'board' => 'CBSE',
    'academic_year' => '2026-27',
    'dob' => '16/10/2010',
    'enquiry_number' => 'ENQ20251615',
    'school_name' => 'Epistemo Vikas Leadership School',
    'submission_date' => '07/09/2025'
];

echo "<h1>🧪 WhatsApp Business API Test Script</h1>";
echo "<p>Testing with real enquiry data from conversation</p>";

// Display configuration (hide sensitive parts)
echo "<h2>📋 Configuration</h2>";
echo "<p><strong>Phone Number ID:</strong> " . $config['phone_number_id'] . "</p>";
echo "<p><strong>Template Name:</strong> " . $config['template_name'] . "</p>";
echo "<p><strong>Template Language:</strong> " . $config['template_language'] . "</p>";
echo "<p><strong>Access Token:</strong> " . substr($config['access_token'], 0, 20) . "...***HIDDEN***</p>";

// Display enquiry data
echo "<h2>📄 Enquiry Data</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
foreach ($enquiry_data as $key => $value) {
    echo "<tr><td><strong>" . ucfirst(str_replace('_', ' ', $key)) . ":</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
}
echo "</table>";

// Test 1: Free-form Message
echo "<h2>🧪 Test 1: Free-form Message</h2>";

$freeform_message = "Admission Enquiry Confirmation
Dear {$enquiry_data['parent_name']},

Thank you for your enquiry at {$enquiry_data['school_name']}. Your enquiry number is {$enquiry_data['enquiry_number']} for {$enquiry_data['grade']}.

We have received your application on {$enquiry_data['submission_date']} and will contact you within 24-48 hours with the next steps.

Best regards,
Admissions Team
Reply STOP to unsubscribe";

echo "<p><strong>Message to send:</strong></p>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($freeform_message) . "</pre>";

$freeform_payload = [
    'messaging_product' => 'whatsapp',
    'to' => $enquiry_data['phone'],
    'type' => 'text',
    'text' => [
        'body' => $freeform_message
    ]
];

echo "<p><strong>API Payload (Free-form):</strong></p>";
echo "<pre style='background: #e8f5e8; padding: 10px; border-radius: 5px; font-size: 12px;'>" . json_encode($freeform_payload, JSON_PRETTY_PRINT) . "</pre>";

// Test 2: Business API Template Message
echo "<h2>🧪 Test 2: Business API Template Message</h2>";

$template_payload = [
    'messaging_product' => 'whatsapp',
    'to' => $enquiry_data['phone'],
    'type' => 'template',
    'template' => [
        'name' => $config['template_name'],
        'language' => [
            'code' => $config['template_language']
        ],
        'namespace' => $config['template_namespace'],
        'components' => [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $enquiry_data['parent_name']],
                    ['type' => 'text', 'text' => $enquiry_data['school_name']],
                    ['type' => 'text', 'text' => $enquiry_data['enquiry_number']],
                    ['type' => 'text', 'text' => $enquiry_data['grade']],
                    ['type' => 'text', 'text' => $enquiry_data['submission_date']]
                ]
            ]
        ]
    ]
];

echo "<p><strong>Template Parameters:</strong></p>";
echo "<ol>";
echo "<li>Parent Name: " . $enquiry_data['parent_name'] . "</li>";
echo "<li>School Name: " . $enquiry_data['school_name'] . "</li>";
echo "<li>Enquiry Number: " . $enquiry_data['enquiry_number'] . "</li>";
echo "<li>Grade: " . $enquiry_data['grade'] . "</li>";
echo "<li>Submission Date: " . $enquiry_data['submission_date'] . "</li>";
echo "</ol>";

echo "<p><strong>API Payload (Template):</strong></p>";
echo "<pre style='background: #fff3cd; padding: 10px; border-radius: 5px; font-size: 12px;'>" . json_encode($template_payload, JSON_PRETTY_PRINT) . "</pre>";

// Function to send WhatsApp message
function sendWhatsAppMessage($payload, $config, $test_name) {
    $url = "https://graph.facebook.com/{$config['api_version']}/{$config['phone_number_id']}/messages";
    
    $headers = [
        'Authorization: Bearer ' . $config['access_token'],
        'Content-Type: application/json'
    ];
    
    echo "<h3>🚀 Sending {$test_name}...</h3>";
    echo "<p><strong>API URL:</strong> " . $url . "</p>";
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_VERBOSE => true
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    echo "<p><strong>HTTP Status Code:</strong> {$httpCode}</p>";
    
    if ($error) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<strong>❌ cURL Error:</strong> " . htmlspecialchars($error);
        echo "</div>";
        return false;
    }
    
    $responseData = json_decode($response, true);
    
    if ($httpCode === 200 && isset($responseData['messages'])) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px;'>";
        echo "<strong>✅ Success!</strong><br>";
        echo "Message ID: " . ($responseData['messages'][0]['id'] ?? 'N/A') . "<br>";
        echo "Status: " . ($responseData['messages'][0]['message_status'] ?? 'N/A');
        echo "</div>";
        return true;
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<strong>❌ API Error:</strong><br>";
        if (isset($responseData['error'])) {
            echo "Code: " . ($responseData['error']['code'] ?? 'Unknown') . "<br>";
            echo "Message: " . htmlspecialchars($responseData['error']['message'] ?? 'Unknown error') . "<br>";
            echo "Type: " . ($responseData['error']['type'] ?? 'Unknown') . "<br>";
            if (isset($responseData['error']['error_subcode'])) {
                echo "Subcode: " . $responseData['error']['error_subcode'] . "<br>";
            }
        } else {
            echo "Response: " . htmlspecialchars($response);
        }
        echo "</div>";
        return false;
    }
    
    echo "<p><strong>Full Response:</strong></p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 11px;'>" . htmlspecialchars(json_encode($responseData, JSON_PRETTY_PRINT)) . "</pre>";
}

// Actual sending tests
echo "<h2>🎯 Live API Tests</h2>";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>⚠️ Warning:</strong> This will send REAL WhatsApp messages to {$enquiry_data['phone']}!<br>";
echo "Make sure this is your test number or you have permission to send messages.";
echo "</div>";

// Test configuration first
echo "<h3>🔍 Testing API Connection...</h3>";
$test_url = "https://graph.facebook.com/{$config['api_version']}/{$config['phone_number_id']}";
$test_headers = ['Authorization: Bearer ' . $config['access_token']];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $test_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $test_headers,
    CURLOPT_TIMEOUT => 10
]);

$test_response = curl_exec($curl);
$test_http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($test_http_code === 200) {
    echo "<p>✅ API connection successful</p>";
    
    // Uncomment the lines below to actually send messages
    echo "<h4>Sending Free-form Message</h4>";
    // sendWhatsAppMessage($freeform_payload, $config, "Free-form Message");
    
    echo "<h4>Sending Template Message</h4>";
    // sendWhatsAppMessage($template_payload, $config, "Template Message");
    
    echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>📝 To actually send messages:</strong><br>";
    echo "1. Uncomment the sendWhatsAppMessage() function calls above<br>";
    echo "2. Verify the phone number is correct<br>";
    echo "3. Run this script again";
    echo "</div>";
    
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ API Connection Failed</strong><br>";
    echo "HTTP Code: {$test_http_code}<br>";
    echo "Response: " . htmlspecialchars($test_response);
    echo "</div>";
}

echo "<h2>📋 Manual Testing Instructions</h2>";
echo "<ol>";
echo "<li><strong>Verify Phone Number:</strong> Ensure +919866133566 is correct and can receive WhatsApp</li>";
echo "<li><strong>Check Template:</strong> Verify 'admission_confirmation' template is approved in Meta Business Manager</li>";
echo "<li><strong>Test Free-form First:</strong> Try the free-form message before template</li>";
echo "<li><strong>Monitor Logs:</strong> Check Meta Business Manager for delivery status</li>";
echo "<li><strong>Rate Limits:</strong> Don't send too many test messages quickly</li>";
echo "</ol>";

echo "<h2>🔧 Troubleshooting</h2>";
echo "<ul>";
echo "<li><strong>Error 100:</strong> Invalid parameter - Check phone number format</li>";
echo "<li><strong>Error 131056:</strong> Template not found - Template may not be approved</li>";
echo "<li><strong>Error 80007:</strong> Phone number not registered - Register number in WhatsApp first</li>";
echo "<li><strong>Error 131026:</strong> Message template format error - Check parameter count</li>";
echo "</ul>";

echo "<hr><p><em>Test script generated on: " . date('Y-m-d H:i:s') . "</em></p>";
echo "<p><em>Target Phone: {$enquiry_data['phone']} | Enquiry: {$enquiry_data['enquiry_number']}</em></p>";
?>
