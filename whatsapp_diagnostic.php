<?php
/**
 * WhatsApp Delivery Diagnostic
 * Checks why messages might not be received
 */

// Configuration
$ACCESS_TOKEN = 'EAASeCKYjY2sBPfLljPAnLtWsXwUzCzPZAd92PfUIqaScZAFjpM9fK3UhLzxxt4OhgzLYpRpWZAlmVjZCSpTV19FcJXRZALTtHlbtjCqNfp5BLdLmXZBzW90c4v4REIko62w6QguwNMWXN1qITGK9D1su8YeILdogvDPeJTOIjdBrC2VgnzKKOLWKOAOT2n2wZDZD';
$PHONE_NUMBER_ID = '614525638411206';
$TARGET_PHONE = '+919866133566';

echo "🔍 WhatsApp Message Delivery Diagnostic\n";
echo "=====================================\n\n";

// Test 1: Check Phone Number ID validity
echo "1️⃣ Testing Phone Number ID...\n";
$url = "https://graph.facebook.com/v21.0/{$PHONE_NUMBER_ID}";
$headers = ['Authorization: Bearer ' . $ACCESS_TOKEN];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "✅ Phone Number ID is valid\n";
    echo "Display Name: " . ($data['display_phone_number'] ?? 'N/A') . "\n";
    echo "Verified Name: " . ($data['verified_name'] ?? 'N/A') . "\n";
    echo "Quality Rating: " . ($data['quality_rating'] ?? 'N/A') . "\n\n";
} else {
    echo "❌ Phone Number ID validation failed\n";
    echo "HTTP Code: {$httpCode}\n";
    echo "Response: {$response}\n\n";
}

// Test 2: Check WhatsApp Business Account
echo "2️⃣ Checking WhatsApp Business Account...\n";
$waba_url = "https://graph.facebook.com/v21.0/me/phone_numbers";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $waba_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "✅ WhatsApp Business Account accessible\n";
    if (isset($data['data']) && count($data['data']) > 0) {
        echo "Phone Numbers in account:\n";
        foreach ($data['data'] as $phone) {
            echo "- ID: " . ($phone['id'] ?? 'N/A') . "\n";
            echo "  Number: " . ($phone['display_phone_number'] ?? 'N/A') . "\n";
            echo "  Status: " . ($phone['status'] ?? 'N/A') . "\n";
        }
    }
    echo "\n";
} else {
    echo "❌ WhatsApp Business Account check failed\n";
    echo "Response: {$response}\n\n";
}

// Test 3: Check Templates
echo "3️⃣ Checking Message Templates...\n";
$templates_url = "https://graph.facebook.com/v21.0/{$PHONE_NUMBER_ID}/message_templates";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $templates_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "✅ Templates endpoint accessible\n";
    
    $found_template = false;
    if (isset($data['data'])) {
        echo "Available templates:\n";
        foreach ($data['data'] as $template) {
            $name = $template['name'] ?? 'Unknown';
            $status = $template['status'] ?? 'Unknown';
            echo "- Name: {$name} | Status: {$status}\n";
            
            if ($name === 'admission_confirmation') {
                $found_template = true;
                echo "  🎯 Found admission_confirmation template!\n";
                echo "  Status: {$status}\n";
                if ($status !== 'APPROVED') {
                    echo "  ❌ Template is NOT APPROVED - this is likely why messages aren't delivered\n";
                } else {
                    echo "  ✅ Template is APPROVED\n";
                }
            }
        }
        
        if (!$found_template) {
            echo "❌ admission_confirmation template NOT FOUND\n";
            echo "This is why your template messages fail!\n";
        }
    }
    echo "\n";
} else {
    echo "❌ Templates check failed\n";
    echo "Response: {$response}\n\n";
}

// Test 4: Simple text message test
echo "4️⃣ Testing simple text message...\n";
$simple_url = "https://graph.facebook.com/v21.0/{$PHONE_NUMBER_ID}/messages";
$simple_data = [
    'messaging_product' => 'whatsapp',
    'to' => $TARGET_PHONE,
    'type' => 'text',
    'text' => [
        'body' => "Test message from WhatsApp Business API - " . date('Y-m-d H:i:s')
    ]
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $simple_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($simple_data),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $ACCESS_TOKEN,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['messages'])) {
        echo "✅ Simple text message sent successfully\n";
        echo "Message ID: " . $data['messages'][0]['id'] . "\n";
        echo "📱 Check your phone for this test message\n\n";
    } else {
        echo "⚠️ Unexpected response format\n";
        echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    }
} else {
    echo "❌ Simple text message failed\n";
    echo "HTTP Code: {$httpCode}\n";
    echo "Response: {$response}\n\n";
}

// Test 5: Phone number analysis
echo "5️⃣ Analyzing target phone number...\n";
echo "Target: {$TARGET_PHONE}\n";

// Check format
if (preg_match('/^\+91[6-9]\d{9}$/', $TARGET_PHONE)) {
    echo "✅ Phone format appears correct for India\n";
} else {
    echo "⚠️ Phone format may be incorrect\n";
    echo "Expected: +91XXXXXXXXXX (where X is digit 6-9 for first digit)\n";
}

// Check if number is likely WhatsApp registered
echo "💡 To verify if {$TARGET_PHONE} is on WhatsApp:\n";
echo "1. Save this number in your contacts\n";
echo "2. Open WhatsApp and search for the contact\n";
echo "3. If you can see the contact, they have WhatsApp\n\n";

echo "🔧 COMMON SOLUTIONS:\n";
echo "===================\n";
echo "1. ❗ Template Issues:\n";
echo "   - Ensure 'admission_confirmation' template is APPROVED in Meta Business Manager\n";
echo "   - Template must be approved before it can send messages\n\n";

echo "2. 📱 Phone Number Issues:\n";
echo "   - Verify {$TARGET_PHONE} has WhatsApp installed\n";
echo "   - Number must be verified and active on WhatsApp\n\n";

echo "3. 🔐 Business Account Setup:\n";
echo "   - Add {$TARGET_PHONE} to your test recipients in Meta Business Manager\n";
echo "   - Ensure WhatsApp Business API is properly configured\n\n";

echo "4. 💰 Billing Issues:\n";
echo "   - Check Meta Business Manager for billing/payment issues\n";
echo "   - Ensure your Facebook Business account has valid payment method\n\n";

echo "5. 🌍 24-hour Window:\n";
echo "   - Free-form messages only work within 24hrs of last user interaction\n";
echo "   - Use approved templates for promotional messages\n\n";

echo "📋 NEXT STEPS:\n";
echo "==============\n";
echo "1. Check Meta Business Manager > WhatsApp > Message Templates\n";
echo "2. Verify 'admission_confirmation' template status\n";
echo "3. If not approved, submit for approval or use free-form text\n";
echo "4. Test with a simple text message first\n";
echo "5. Check delivery reports in Meta Business Manager\n\n";

echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
?>
