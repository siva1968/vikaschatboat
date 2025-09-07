<?php
/**
 * WordPress WhatsApp Integration Test
 * Upload to WordPress root and run to test WhatsApp functionality
 */

// Include WordPress
require_once('wp-config.php');
require_once('wp-includes/functions.php');

echo "=== WordPress WhatsApp Integration Test ===\n";
echo "Testing if WhatsApp works from WordPress context...\n";
echo "===============================================\n\n";

// Get saved WhatsApp settings from WordPress
$whatsapp_token = get_option('edubot_whatsapp_token');
$phone_id = get_option('edubot_whatsapp_phone_id');
$template_namespace = get_option('edubot_whatsapp_template_namespace');
$template_name = get_option('edubot_whatsapp_template_name');

echo "Step 1: Checking WordPress WhatsApp Settings\n";
echo "============================================\n";
echo "Token: " . ($whatsapp_token ? substr($whatsapp_token, 0, 20) . '...' : 'NOT SET') . "\n";
echo "Phone ID: " . ($phone_id ?: 'NOT SET') . "\n";
echo "Template Namespace: " . ($template_namespace ?: 'NOT SET') . "\n";
echo "Template Name: " . ($template_name ?: 'NOT SET') . "\n";

if (!$whatsapp_token || !$phone_id) {
    echo "❌ Missing WhatsApp configuration!\n";
    exit;
}

echo "\nStep 2: Testing WhatsApp API from WordPress\n";
echo "===========================================\n";

// Test WhatsApp template message
$message_data = array(
    "messaging_product" => "whatsapp",
    "to" => "919866133566",
    "type" => "template",
    "template" => array(
        "namespace" => $template_namespace,
        "name" => $template_name,
        "language" => array("code" => "en"),
        "components" => array(
            array(
                "type" => "body",
                "parameters" => array(
                    array("type" => "text", "text" => "prasad"),
                    array("type" => "text", "text" => "ENQ20250906TEST"),
                    array("type" => "text", "text" => "Test School"),
                    array("type" => "text", "text" => "Grade 10"),
                    array("type" => "text", "text" => "06/09/2025")
                )
            )
        )
    )
);

$url = "https://graph.facebook.com/v18.0/{$phone_id}/messages";
$headers = array(
    'Authorization: Bearer ' . $whatsapp_token,
    'Content-Type: application/json'
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: $response\n";

if ($http_code == 200) {
    echo "✅ WhatsApp integration working from WordPress!\n";
    $response_data = json_decode($response, true);
    if (isset($response_data['messages'][0]['id'])) {
        echo "📨 Message sent with ID: " . $response_data['messages'][0]['id'] . "\n";
    }
} else {
    echo "❌ WhatsApp integration failed from WordPress!\n";
    echo "Error details: $response\n";
}

echo "\n=== Test Complete ===\n";
echo "Delete this file after testing!\n";
?>
