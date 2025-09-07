<?php
/**
 * Debug script to check EduBot configuration and identify the 500 error
 */

// Include WordPress
$wp_config_path = 'C:/Users/prasa/OneDrive/Desktop/wp-config.php';
if (file_exists($wp_config_path)) {
    require_once($wp_config_path);
} else {
    echo "WordPress config not found at: $wp_config_path\n";
    exit;
}

// Try to load WordPress
if (!defined('ABSPATH')) {
    echo "WordPress not loaded properly\n";
    exit;
}

echo "=== EduBot Configuration Debug ===\n\n";

// Check if EduBot classes exist
$classes_to_check = [
    'EduBot_API_Integrations',
    'EduBot_Security_Manager', 
    'EduBot_School_Config',
    'EduBot_Shortcode'
];

echo "Class Availability Check:\n";
foreach ($classes_to_check as $class) {
    $exists = class_exists($class) ? '✅ EXISTS' : '❌ MISSING';
    echo "- {$class}: {$exists}\n";
}

echo "\n";

// Check OpenAI configuration
echo "OpenAI Configuration:\n";
$openai_key = get_option('edubot_openai_api_key', '');
$openai_model = get_option('edubot_openai_model', '');

echo "- OpenAI Key: " . (empty($openai_key) ? '❌ NOT CONFIGURED' : '✅ CONFIGURED (' . strlen($openai_key) . ' chars)') . "\n";
echo "- OpenAI Model: " . (empty($openai_model) ? '❌ NOT SET' : "✅ {$openai_model}") . "\n";

// Check database tables
global $wpdb;
echo "\nDatabase Tables Check:\n";
$tables_to_check = [
    $wpdb->prefix . 'edubot_school_configs',
    $wpdb->prefix . 'edubot_conversations',
    $wpdb->prefix . 'edubot_applications'
];

foreach ($tables_to_check as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
    $status = $exists ? '✅ EXISTS' : '❌ MISSING';
    echo "- {$table}: {$status}\n";
}

// Try to create instances and identify errors
echo "\nTrying to Create Class Instances:\n";

try {
    if (class_exists('EduBot_Security_Manager')) {
        $security_manager = new EduBot_Security_Manager();
        echo "- EduBot_Security_Manager: ✅ CREATED\n";
    }
} catch (Exception $e) {
    echo "- EduBot_Security_Manager: ❌ ERROR - " . $e->getMessage() . "\n";
}

try {
    if (class_exists('EduBot_School_Config')) {
        $school_config = EduBot_School_Config::getInstance();
        echo "- EduBot_School_Config: ✅ CREATED\n";
    }
} catch (Exception $e) {
    echo "- EduBot_School_Config: ❌ ERROR - " . $e->getMessage() . "\n";
}

try {
    if (class_exists('EduBot_API_Integrations')) {
        $api_integrations = new EduBot_API_Integrations();
        echo "- EduBot_API_Integrations: ✅ CREATED\n";
    }
} catch (Exception $e) {
    echo "- EduBot_API_Integrations: ❌ ERROR - " . $e->getMessage() . "\n";
}

// Check WordPress AJAX actions
echo "\nAJAX Actions Registration:\n";
$ajax_actions = [
    'edubot_chatbot_response',
    'edubot_save_api_settings',
    'edubot_test_api'
];

foreach ($ajax_actions as $action) {
    $has_action = has_action("wp_ajax_{$action}") || has_action("wp_ajax_nopriv_{$action}");
    $status = $has_action ? '✅ REGISTERED' : '❌ NOT REGISTERED';
    echo "- {$action}: {$status}\n";
}

// Test the specific error scenario
echo "\nTesting Chatbot Response Simulation:\n";

try {
    // Simulate the chatbot response call
    if (class_exists('EduBot_Shortcode')) {
        $shortcode = new EduBot_Shortcode();
        echo "- EduBot_Shortcode instance: ✅ CREATED\n";
        
        // Check if the handler method exists
        if (method_exists($shortcode, 'handle_chatbot_response')) {
            echo "- handle_chatbot_response method: ✅ EXISTS\n";
        } else {
            echo "- handle_chatbot_response method: ❌ MISSING\n";
        }
    }
} catch (Exception $e) {
    echo "- EduBot_Shortcode: ❌ ERROR - " . $e->getMessage() . "\n";
    echo "- Stack trace: " . $e->getTraceAsString() . "\n";
}

// Check error logs
echo "\nRecent WordPress Errors (last 50 lines):\n";
$error_log_path = ini_get('error_log');
if (!empty($error_log_path) && file_exists($error_log_path)) {
    $lines = file($error_log_path);
    $recent_lines = array_slice($lines, -50);
    foreach ($recent_lines as $line) {
        if (stripos($line, 'edubot') !== false || stripos($line, 'fatal') !== false || stripos($line, 'error') !== false) {
            echo $line;
        }
    }
} else {
    echo "Error log not found or not configured\n";
}

echo "\n=== Debug Complete ===\n";
