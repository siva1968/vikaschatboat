<?php
/**
 * EduBot Diagnostic Script - Test AJAX Handler
 * Run this to test if the chatbot AJAX handler is working properly
 */

// Set WordPress environment
define('WP_USE_THEMES', false);
require_once('../../../wp-config.php');

// Simulate AJAX request
$_POST['action'] = 'edubot_chatbot_response';
$_POST['message'] = 'Hello';
$_POST['action_type'] = 'curriculum';
$_POST['session_id'] = 'test_session_123';
$_POST['nonce'] = wp_create_nonce('edubot_nonce');

echo "=== EduBot AJAX Handler Test ===\n";
echo "Testing with:\n";
echo "- Action: " . $_POST['action'] . "\n";
echo "- Message: " . $_POST['message'] . "\n";
echo "- Action Type: " . $_POST['action_type'] . "\n";
echo "- Session ID: " . $_POST['session_id'] . "\n";
echo "- Nonce: " . $_POST['nonce'] . "\n\n";

// Test if class exists
if (class_exists('EduBot_Shortcode')) {
    echo "✅ EduBot_Shortcode class exists\n";
    
    $shortcode = new EduBot_Shortcode();
    
    if (method_exists($shortcode, 'handle_chatbot_response')) {
        echo "✅ handle_chatbot_response method exists\n";
        
        try {
            // Capture output
            ob_start();
            $shortcode->handle_chatbot_response();
            $output = ob_get_clean();
            
            echo "✅ Method executed successfully\n";
            echo "Output: " . $output . "\n";
            
        } catch (Exception $e) {
            echo "❌ Error executing method: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ handle_chatbot_response method not found\n";
    }
} else {
    echo "❌ EduBot_Shortcode class not found\n";
}

echo "\n=== Test Complete ===\n";
?>
