<?php
/**
 * Critical Analysis: Debug the exact 500 error in EduBot chatbot
 * This script will test each component step by step to identify the exact failure point
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "=== EduBot 500 Error Critical Analysis ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

// Test 1: Basic WordPress Loading
echo "Step 1: WordPress Loading Test\n";
echo "==============================\n";

$wp_config_path = 'C:/Users/prasa/OneDrive/Desktop/wp-config.php';
if (file_exists($wp_config_path)) {
    require_once($wp_config_path);
    echo "✅ WordPress config loaded\n";
} else {
    echo "❌ WordPress config not found\n";
    exit;
}

if (defined('ABSPATH') && function_exists('get_option')) {
    echo "✅ WordPress functions available\n";
} else {
    echo "❌ WordPress not properly loaded\n";
    exit;
}

// Test 2: EduBot Classes Check
echo "\nStep 2: EduBot Classes Availability\n";
echo "===================================\n";

$critical_classes = [
    'EduBot_API_Integrations' => 'includes/class-api-integrations.php',
    'EduBot_Security_Manager' => 'includes/class-security-manager.php',
    'EduBot_School_Config' => 'includes/class-school-config.php',
    'EduBot_Shortcode' => 'includes/class-edubot-shortcode.php'
];

foreach ($critical_classes as $class => $file) {
    if (class_exists($class)) {
        echo "✅ {$class}: Available\n";
    } else {
        echo "❌ {$class}: Missing\n";
    }
}

// Test 3: OpenAI API Configuration Check
echo "\nStep 3: OpenAI API Configuration\n";
echo "=================================\n";

try {
    $school_config = EduBot_School_Config::getInstance();
    $api_keys = $school_config->get_api_keys();
    
    if (isset($api_keys['openai_key']) && !empty($api_keys['openai_key'])) {
        $key_preview = substr($api_keys['openai_key'], 0, 10) . '...' . substr($api_keys['openai_key'], -5);
        echo "✅ OpenAI API Key: {$key_preview}\n";
        echo "📏 Key Length: " . strlen($api_keys['openai_key']) . " characters\n";
        
        // Validate key format
        if (preg_match('/^sk-[a-zA-Z0-9_\-\.]{32,}$/', $api_keys['openai_key'])) {
            echo "✅ Key Format: Valid\n";
        } else {
            echo "❌ Key Format: Invalid (should start with 'sk-' and be at least 35 chars)\n";
        }
    } else {
        echo "❌ OpenAI API Key: Not configured\n";
    }
} catch (Exception $e) {
    echo "❌ Error getting API configuration: " . $e->getMessage() . "\n";
}

// Test 4: Simulate the Exact Error Scenario
echo "\nStep 4: Simulating Chatbot Response (Date Input)\n";
echo "================================================\n";

try {
    // Simulate the exact scenario that causes 500 error
    $_POST['action'] = 'edubot_chatbot_response';
    $_POST['message'] = '10/10/2010';  // The exact input that causes 500 error
    $_POST['session_id'] = 'test_session_' . time();
    $_POST['nonce'] = 'test_nonce';
    
    echo "🔍 Simulating message: '10/10/2010'\n";
    echo "🔍 Session ID: {$_POST['session_id']}\n";
    
    // Test the shortcode handler
    if (class_exists('EduBot_Shortcode')) {
        $shortcode = new EduBot_Shortcode();
        echo "✅ EduBot_Shortcode instance created\n";
        
        // Check if AI integration will work
        if (class_exists('EduBot_API_Integrations')) {
            $api_integrations = new EduBot_API_Integrations();
            echo "✅ EduBot_API_Integrations instance created\n";
            
            // Test OpenAI connection with actual API call
            echo "\nTesting OpenAI API Call:\n";
            $test_message = "Test message";
            $test_context = "You are a helpful assistant.";
            
            $ai_response = $api_integrations->get_ai_response($test_message, $test_context);
            
            if (is_wp_error($ai_response)) {
                echo "❌ OpenAI API Error: " . $ai_response->get_error_message() . "\n";
                echo "🔍 Error Code: " . $ai_response->get_error_code() . "\n";
            } else {
                echo "✅ OpenAI API: Working correctly\n";
                echo "📝 Response preview: " . substr($ai_response, 0, 50) . "...\n";
            }
        } else {
            echo "❌ EduBot_API_Integrations class not available\n";
        }
        
    } else {
        echo "❌ EduBot_Shortcode class not available\n";
    }
    
} catch (Exception $e) {
    echo "❌ Critical Error in Step 4: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . "\n";
    echo "📍 Line: " . $e->getLine() . "\n";
    echo "🔍 Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

// Test 5: Database Operations Test
echo "\nStep 5: Database Operations Test\n";
echo "================================\n";

try {
    global $wpdb;
    
    // Test basic database connectivity
    $result = $wpdb->get_var("SELECT 1");
    if ($result == 1) {
        echo "✅ Database Connection: Working\n";
    } else {
        echo "❌ Database Connection: Failed\n";
    }
    
    // Test WordPress options table
    $test_option_name = 'edubot_debug_test_' . time();
    $test_value = 'debug_test_value';
    
    if (update_option($test_option_name, $test_value)) {
        echo "✅ WordPress Options: Write OK\n";
        delete_option($test_option_name); // Clean up
    } else {
        echo "❌ WordPress Options: Write Failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database Test Error: " . $e->getMessage() . "\n";
}

// Test 6: PHP Environment Check
echo "\nStep 6: PHP Environment Check\n";
echo "=============================\n";

echo "PHP Version: " . phpversion() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "s\n";
echo "cURL Available: " . (function_exists('curl_init') ? 'Yes' : 'No') . "\n";
echo "JSON Available: " . (function_exists('json_encode') ? 'Yes' : 'No') . "\n";

// Test 7: Error Log Analysis
echo "\nStep 7: Recent Error Analysis\n";
echo "=============================\n";

$error_log_path = ini_get('error_log');
if (!empty($error_log_path) && file_exists($error_log_path)) {
    echo "📍 Error Log Location: {$error_log_path}\n";
    
    $recent_lines = array_slice(file($error_log_path), -20);
    echo "📋 Recent Errors (last 20 lines):\n";
    foreach ($recent_lines as $line) {
        if (stripos($line, 'edubot') !== false || stripos($line, 'fatal') !== false || stripos($line, 'error') !== false) {
            echo "🔴 " . trim($line) . "\n";
        }
    }
} else {
    echo "📍 No error log file found or accessible\n";
}

echo "\n=== Analysis Complete ===\n";
echo "🔍 Check the results above to identify the exact cause of the 500 error.\n";
echo "🚨 The most likely causes are:\n";
echo "   1. Missing or invalid OpenAI API key\n";
echo "   2. API key format validation failing\n";
echo "   3. cURL/network issues with OpenAI API\n";
echo "   4. PHP memory or execution time limits\n";
echo "   5. Database connection or permission issues\n";
?>
