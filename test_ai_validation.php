<?php
/**
 * Test AI-Powered Validation
 */

// Load WordPress
require_once('D:/xampp/htdocs/demo/wp-load.php');

// Load plugin classes
$plugin_dir = 'D:/xampp/htdocs/demo/wp-content/plugins/edubot-pro/';
if (!class_exists('EduBot_API_Integrations')) {
    require_once($plugin_dir . 'includes/class-school-config.php');
    require_once($plugin_dir . 'includes/class-security-manager.php');
    require_once($plugin_dir . 'includes/class-api-integrations.php');
}

echo "=== Testing AI-Powered Validation ===\n\n";

// Check if API key is configured
$school_config = EduBot_School_Config::getInstance();
$api_keys = $school_config->get_api_keys();

if (empty($api_keys['openai_key'])) {
    echo "⚠️  OpenAI API Key: NOT CONFIGURED\n";
    echo "   AI validation will fall back to regex validation.\n\n";
} else {
    echo "✅ OpenAI API Key: CONFIGURED\n";
    echo "   Key: " . substr($api_keys['openai_key'], 0, 10) . "..." . substr($api_keys['openai_key'], -5) . "\n\n";
}

// Initialize API integrations
$api_integrations = new EduBot_API_Integrations();

echo "--- Email Validation Tests ---\n\n";

// Test 1: Missing @ symbol
echo "Test 1: Missing @ Symbol\n";
echo "Input: prasadmasinagmail.com\n";
$result = $api_integrations->validate_email_with_ai('prasadmasinagmail.com');
echo "Valid: " . ($result['valid'] ? 'Yes' : 'No') . "\n";
echo "Corrected: " . ($result['corrected'] ?? 'null') . "\n";
echo "Issue: " . ($result['issue'] ?? 'null') . "\n";
echo "Method: " . $result['method'] . "\n\n";

// Test 2: Wrong symbol %
echo "Test 2: Wrong Symbol %\n";
echo "Input: prasad%gmail.com\n";
$result = $api_integrations->validate_email_with_ai('prasad%gmail.com');
echo "Valid: " . ($result['valid'] ? 'Yes' : 'No') . "\n";
echo "Corrected: " . ($result['corrected'] ?? 'null') . "\n";
echo "Issue: " . ($result['issue'] ?? 'null') . "\n";
echo "Method: " . $result['method'] . "\n\n";

// Test 3: Valid email
echo "Test 3: Valid Email\n";
echo "Input: prasad@gmail.com\n";
$result = $api_integrations->validate_email_with_ai('prasad@gmail.com');
echo "Valid: " . ($result['valid'] ? 'Yes' : 'No') . "\n";
echo "Corrected: " . ($result['corrected'] ?? 'null') . "\n";
echo "Issue: " . ($result['issue'] ?? 'null') . "\n";
echo "Method: " . $result['method'] . "\n\n";

echo "--- Phone Validation Tests ---\n\n";

// Test 4: Missing digit
echo "Test 4: Missing Digit (9 digits)\n";
echo "Input: 986613356\n";
$result = $api_integrations->validate_phone_with_ai('986613356');
echo "Valid: " . ($result['valid'] ? 'Yes' : 'No') . "\n";
echo "Corrected: " . ($result['corrected'] ?? 'null') . "\n";
echo "Issue: " . ($result['issue'] ?? 'null') . "\n";
echo "Digit Count: " . ($result['digit_count'] ?? 'not provided') . "\n";
echo "Method: " . $result['method'] . "\n\n";

// Test 5: Valid phone
echo "Test 5: Valid Phone\n";
echo "Input: 9866133566\n";
$result = $api_integrations->validate_phone_with_ai('9866133566');
echo "Valid: " . ($result['valid'] ? 'Yes' : 'No') . "\n";
echo "Corrected: " . ($result['corrected'] ?? 'null') . "\n";
echo "Issue: " . ($result['issue'] ?? 'null') . "\n";
echo "Method: " . $result['method'] . "\n\n";

echo "=== Test Complete ===\n";
