<?php
/**
 * Simple WhatsApp Webhook Test Tool
 * Direct testing without WordPress dependencies
 * 
 * Usage: https://chatbot.getinstantleads.in/wp-content/plugins/edubot-pro/simple_webhook_test.php
 */

$test_phone = '+91 81794 76486';
$webhook_url = 'https://chatbot.getinstantleads.in/wp-content/whatsapp_webhook_verify.php';
$verify_token = 'EduBot1763659599';

// Test webhook verification
function test_webhook_verification($webhook_url, $verify_token) {
    $test_challenge = 'test_' . time();
    $test_url = $webhook_url . '?hub.mode=subscribe&hub.challenge=' . $test_challenge . '&hub.verify_token=' . $verify_token;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return array(
        'success' => ($http_code === 200 && $response === $test_challenge),
        'http_code' => $http_code,
        'response' => $response,
        'expected' => $test_challenge,
        'error' => $error
    );
}

header('Content-Type: application/json');

$result = test_webhook_verification($webhook_url, $verify_token);

echo json_encode(array(
    'test_phone' => $test_phone,
    'webhook_url' => $webhook_url,
    'verify_token' => $verify_token,
    'webhook_test' => $result,
    'status' => $result['success'] ? 'READY' : 'FAILED',
    'message' => $result['success'] 
        ? 'Webhook verification successful! Ready for WhatsApp testing.' 
        : 'Webhook verification failed. Check configuration.',
    'next_steps' => array(
        '1. Configure Meta Business API with webhook URL: ' . $webhook_url,
        '2. Set verify token: ' . $verify_token,
        '3. Add phone ' . $test_phone . ' as test user',
        '4. Send "Hello" from ' . $test_phone . ' to your WhatsApp Business number'
    ),
    'timestamp' => date('Y-m-d H:i:s')
), JSON_PRETTY_PRINT);
?>
