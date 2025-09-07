<?php
/**
 * PHP Error Checker for EduBot
 * 
 * This will help identify PHP errors that cause 500 Internal Server Error
 * Upload to WordPress root and access via browser
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>🔍 EduBot PHP Error Checker</h1>";

// Check WordPress configuration
$wp_config_path = getcwd() . '/wp-config.php';
if (file_exists($wp_config_path)) {
    echo "<p>✅ <strong>WordPress found:</strong> wp-config.php exists</p>";
    
    // Try to load WordPress
    try {
        define('WP_USE_THEMES', false);
        require_once(getcwd() . '/wp-load.php');
        echo "<p>✅ <strong>WordPress loaded successfully</strong></p>";
        
        // Check if EduBot plugin is active
        if (class_exists('EduBot_Shortcode')) {
            echo "<p>✅ <strong>EduBot_Shortcode class exists</strong></p>";
            
            // Test instantiation
            try {
                $shortcode = new EduBot_Shortcode();
                echo "<p>✅ <strong>EduBot_Shortcode instantiated successfully</strong></p>";
            } catch (Exception $e) {
                echo "<p>❌ <strong>EduBot_Shortcode instantiation failed:</strong> " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>❌ <strong>EduBot_Shortcode class not found</strong> - Plugin may not be active</p>";
        }
        
        // Check if API Integrations class exists
        if (class_exists('EduBot_API_Integrations')) {
            echo "<p>✅ <strong>EduBot_API_Integrations class exists</strong></p>";
            
            // Test instantiation
            try {
                $api = new EduBot_API_Integrations();
                echo "<p>✅ <strong>EduBot_API_Integrations instantiated successfully</strong></p>";
            } catch (Exception $e) {
                echo "<p>❌ <strong>EduBot_API_Integrations instantiation failed:</strong> " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>❌ <strong>EduBot_API_Integrations class not found</strong></p>";
        }
        
        // Test AJAX handler registration
        if (has_action('wp_ajax_edubot_chatbot_response')) {
            echo "<p>✅ <strong>AJAX handler registered:</strong> wp_ajax_edubot_chatbot_response</p>";
        } else {
            echo "<p>❌ <strong>AJAX handler NOT registered:</strong> wp_ajax_edubot_chatbot_response</p>";
        }
        
        if (has_action('wp_ajax_nopriv_edubot_chatbot_response')) {
            echo "<p>✅ <strong>Public AJAX handler registered:</strong> wp_ajax_nopriv_edubot_chatbot_response</p>";
        } else {
            echo "<p>❌ <strong>Public AJAX handler NOT registered:</strong> wp_ajax_nopriv_edubot_chatbot_response</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ <strong>WordPress loading failed:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Stack trace:</strong></p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<p>❌ <strong>WordPress not found:</strong> wp-config.php missing</p>";
    echo "<p><strong>Current directory:</strong> " . getcwd() . "</p>";
}

// Check PHP version and extensions
echo "<hr><h2>PHP Environment</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

$required_extensions = ['json', 'curl', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p>✅ <strong>Extension $ext:</strong> Loaded</p>";
    } else {
        echo "<p>❌ <strong>Extension $ext:</strong> NOT loaded</p>";
    }
}

// Check memory limit
echo "<p><strong>Memory Limit:</strong> " . ini_get('memory_limit') . "</p>";
echo "<p><strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . " seconds</p>";

// Check file permissions on key directories
echo "<hr><h2>File Permissions</h2>";
$check_dirs = [
    getcwd() . '/wp-content',
    getcwd() . '/wp-content/plugins',
    getcwd() . '/wp-content/themes',
    getcwd() . '/wp-content/uploads'
];

foreach ($check_dirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "<p>✅ <strong>$dir:</strong> Writable</p>";
        } else {
            echo "<p>⚠️ <strong>$dir:</strong> Not writable</p>";
        }
    } else {
        echo "<p>❌ <strong>$dir:</strong> Directory not found</p>";
    }
}

echo "<hr>";
echo "<p><em>Check completed at: " . date('Y-m-d H:i:s') . "</em></p>";
echo "<p><strong>Next:</strong> If all checks pass, the 500 error might be in the AJAX processing logic.</p>";
?>
