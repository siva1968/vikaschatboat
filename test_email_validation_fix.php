<?php
/**
 * Test Email Validation Fix
 *
 * This script tests that invalid emails are properly rejected
 * and don't get mistakenly treated as names.
 */

// Load WordPress
require_once('D:/xampp/htdocs/demo/wp-load.php');

// Activate the plugin if not already active
if (!class_exists('EduBot_Session_Manager')) {
    // Load plugin files manually
    $plugin_dir = 'D:/xampp/htdocs/demo/wp-content/plugins/edubot-pro/';
    require_once($plugin_dir . 'includes/class-edubot-session-manager.php');
    require_once($plugin_dir . 'includes/class-edubot-workflow-manager.php');
}

echo "=== Testing Email Validation Fix ===\n\n";

// Initialize session manager
$session_manager = EduBot_Session_Manager::getInstance();
$workflow_manager = new EduBot_Workflow_Manager();

// Create a test session
$session_id = 'test_email_' . time();

echo "Step 1: Initialize session and collect name\n";
$response = $workflow_manager->process_user_input('Prasad', $session_id);
echo "Response: " . substr($response, 0, 100) . "...\n\n";

echo "Step 2: Provide valid phone number\n";
$response = $workflow_manager->process_user_input('9866133566', $session_id);
echo "Response: " . substr($response, 0, 100) . "...\n\n";

echo "Step 3: Provide INVALID email (missing @)\n";
$response = $workflow_manager->process_user_input('prasadmasinagmail.com', $session_id);
echo "Full Response:\n";
echo $response . "\n\n";

// Check if response contains error message
if (strpos($response, 'Invalid Email') !== false) {
    echo "✅ SUCCESS: Invalid email was properly rejected!\n\n";
} else {
    echo "❌ FAILED: Invalid email was not rejected. Got: " . substr($response, 0, 200) . "\n\n";
}

// Check session state
$session_data = $session_manager->get_session($session_id);
echo "Current session state:\n";
echo "- Name: " . ($session_data['data']['student_name'] ?? 'NOT SET') . "\n";
echo "- Phone: " . ($session_data['data']['phone'] ?? 'NOT SET') . "\n";
echo "- Email: " . ($session_data['data']['email'] ?? 'NOT SET') . "\n\n";

if ($session_data['data']['student_name'] === 'Prasad' &&
    $session_data['data']['phone'] === '+919866133566' &&
    !isset($session_data['data']['email'])) {
    echo "✅ SUCCESS: Session state is correct - name and phone preserved, email not set\n\n";
} else {
    echo "❌ FAILED: Session state is incorrect\n\n";
}

echo "Step 4: Now provide VALID email\n";
$response = $workflow_manager->process_user_input('prasad@gmail.com', $session_id);
echo "Response: " . substr($response, 0, 150) . "...\n\n";

// Check session state again
$session_data = $session_manager->get_session($session_id);
echo "Final session state:\n";
echo "- Name: " . ($session_data['data']['student_name'] ?? 'NOT SET') . "\n";
echo "- Phone: " . ($session_data['data']['phone'] ?? 'NOT SET') . "\n";
echo "- Email: " . ($session_data['data']['email'] ?? 'NOT SET') . "\n\n";

if ($session_data['data']['email'] === 'prasad@gmail.com') {
    echo "✅ SUCCESS: Valid email was accepted and stored!\n\n";
} else {
    echo "❌ FAILED: Valid email was not accepted\n\n";
}

echo "\n=== Test Complete ===\n";
