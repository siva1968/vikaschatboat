<?php
/**
 * WhatsApp Debug - Find Why Messages Aren't Being Triggered
 * This will help identify exactly where the WhatsApp flow is breaking
 */

// Load WordPress
if (!defined('ABSPATH')) {
    $wp_load_paths = [
        '../../wp-load.php',
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../wp-load.php',
        './wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('❌ Cannot find WordPress. Please upload this file to your WordPress site.');
    }
}

echo "<h1>🔍 WhatsApp Debug - Why Messages Aren't Being Triggered</h1>";

// 1. Check if the shortcode class exists and method is available
echo "<h2>1. Plugin Classes Check</h2>";

$shortcode_file = ABSPATH . 'wp-content/plugins/edubot-pro/includes/class-edubot-shortcode.php';
$api_file = ABSPATH . 'wp-content/plugins/edubot-pro/includes/class-api-integrations.php';

echo "<p><strong>EduBot Shortcode File:</strong> ";
if (file_exists($shortcode_file)) {
    echo "✅ Found at " . $shortcode_file . "</p>";
} else {
    echo "❌ NOT FOUND - Check plugin installation</p>";
}

echo "<p><strong>API Integrations File:</strong> ";
if (file_exists($api_file)) {
    echo "✅ Found at " . $api_file . "</p>";
} else {
    echo "❌ NOT FOUND - Check plugin installation</p>";
}

// Load classes if they exist
if (file_exists($shortcode_file)) {
    require_once $shortcode_file;
}
if (file_exists($api_file)) {
    require_once $api_file;
}

echo "<p><strong>EduBot_Shortcode Class:</strong> ";
if (class_exists('EduBot_Shortcode')) {
    echo "✅ Available</p>";
    
    // Check if WhatsApp method exists
    if (method_exists('EduBot_Shortcode', 'send_parent_whatsapp_confirmation')) {
        echo "<p><strong>WhatsApp Method:</strong> ✅ send_parent_whatsapp_confirmation exists</p>";
    } else {
        echo "<p><strong>WhatsApp Method:</strong> ❌ send_parent_whatsapp_confirmation NOT FOUND</p>";
    }
} else {
    echo "❌ NOT AVAILABLE</p>";
}

echo "<p><strong>EduBot_API_Integrations Class:</strong> ";
if (class_exists('EduBot_API_Integrations')) {
    echo "✅ Available</p>";
} else {
    echo "❌ NOT AVAILABLE</p>";
}

// 2. Check WordPress error logs for recent WhatsApp messages
echo "<h2>2. Recent Error Logs Analysis</h2>";

$log_entries = [];
$log_file = ini_get('error_log');

if ($log_file && file_exists($log_file)) {
    echo "<p><strong>Error Log File:</strong> " . $log_file . "</p>";
    
    // Get recent log entries
    $log_content = file_get_contents($log_file);
    $lines = explode("\n", $log_content);
    $recent_lines = array_slice($lines, -200); // Last 200 lines
    
    $whatsapp_logs = array_filter($recent_lines, function($line) {
        return stripos($line, 'edubot') !== false && stripos($line, 'whatsapp') !== false;
    });
    
    if (!empty($whatsapp_logs)) {
        echo "<p><strong>Recent WhatsApp Log Entries:</strong></p>";
        echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;'>";
        foreach (array_slice($whatsapp_logs, -10) as $log) {
            echo htmlspecialchars($log) . "<br>";
        }
        echo "</div>";
    } else {
        echo "<p>❌ <strong>No WhatsApp-related log entries found</strong></p>";
        echo "<p>This suggests the WhatsApp method is not being called at all.</p>";
    }
} else {
    echo "<p>⚠️ Error log file not accessible</p>";
}

// 3. Test the WhatsApp method directly
echo "<h2>3. Direct Method Test</h2>";

if (class_exists('EduBot_Shortcode')) {
    $shortcode = new EduBot_Shortcode();
    
    // Test data
    $test_data = [
        'parent_name' => 'Test Parent',
        'student_name' => 'Test Student',  
        'phone' => '+919866133566',
        'email' => 'test@example.com',
        'grade' => 'Grade 5',
        'board' => 'CBSE'
    ];
    
    echo "<p>🧪 Testing WhatsApp method with sample data...</p>";
    
    // Use reflection to call private method
    try {
        $reflection = new ReflectionClass($shortcode);
        $method = $reflection->getMethod('send_parent_whatsapp_confirmation');
        $method->setAccessible(true);
        
        echo "<p>📞 Calling send_parent_whatsapp_confirmation...</p>";
        $result = $method->invoke($shortcode, $test_data, 'TEST123', 'Test School');
        
        echo "<p><strong>Direct Method Result:</strong> " . ($result ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
        
    } catch (Exception $e) {
        echo "<p>❌ <strong>Error calling method:</strong> " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ Cannot test - EduBot_Shortcode class not available</p>";
}

// 4. Check if enquiry submission calls WhatsApp
echo "<h2>4. Enquiry Submission Flow Check</h2>";

if (class_exists('EduBot_Shortcode')) {
    // Look for the enquiry processing method
    $reflection = new ReflectionClass('EduBot_Shortcode');
    $methods = $reflection->getMethods();
    
    $enquiry_methods = array_filter($methods, function($method) {
        return stripos($method->getName(), 'enquiry') !== false || 
               stripos($method->getName(), 'submit') !== false ||
               stripos($method->getName(), 'process') !== false;
    });
    
    echo "<p><strong>Enquiry-related methods found:</strong></p>";
    echo "<ul>";
    foreach ($enquiry_methods as $method) {
        echo "<li>" . $method->getName() . "</li>";
    }
    echo "</ul>";
    
    // Check if main processing method calls WhatsApp
    try {
        $process_method = $reflection->getMethod('process_enquiry_submission');
        $filename = $process_method->getFileName();
        $start_line = $process_method->getStartLine();
        $end_line = $process_method->getEndLine();
        
        echo "<p><strong>Checking process_enquiry_submission method...</strong></p>";
        
        $file_content = file($filename);
        $method_content = implode('', array_slice($file_content, $start_line - 1, $end_line - $start_line + 1));
        
        if (strpos($method_content, 'send_parent_whatsapp_confirmation') !== false) {
            echo "<p>✅ <strong>WhatsApp call found in enquiry processing</strong></p>";
        } else {
            echo "<p>❌ <strong>WhatsApp call NOT found in enquiry processing</strong></p>";
            echo "<p>This is likely the problem - the enquiry submission isn't calling the WhatsApp method.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>⚠️ Could not analyze enquiry processing method</p>";
    }
}

// 5. Configuration Summary
echo "<h2>5. Configuration Status</h2>";

$config_status = [
    'WhatsApp Notifications' => get_option('edubot_whatsapp_notifications') ? '✅ Enabled' : '❌ Disabled',
    'Template Type' => get_option('edubot_whatsapp_template_type', 'not set'),
    'Access Token' => get_option('edubot_whatsapp_token') ? '✅ Set' : '❌ Not Set',
    'Phone ID' => get_option('edubot_whatsapp_phone_id', 'not set'),
    'Provider' => get_option('edubot_whatsapp_provider', 'not set')
];

echo "<table border='1' style='border-collapse: collapse;'>";
foreach ($config_status as $item => $status) {
    echo "<tr><td><strong>{$item}</strong></td><td>{$status}</td></tr>";
}
echo "</table>";

// 6. Recommendations
echo "<h2>🎯 Diagnosis & Recommendations</h2>";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px;'>";
echo "<h3>📋 Likely Issues:</h3>";
echo "<ol>";
echo "<li><strong>Method Not Called:</strong> The enquiry submission might not be calling the WhatsApp method</li>";
echo "<li><strong>Silent Failure:</strong> WhatsApp method might be failing silently without logging</li>";
echo "<li><strong>Plugin Update Needed:</strong> Your uploaded changes might not be active</li>";
echo "<li><strong>Caching Issue:</strong> WordPress/plugin cache might need clearing</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
echo "<h3>🔧 Immediate Actions:</h3>";
echo "<ol>";
echo "<li><strong>Check Plugin Files:</strong> Ensure your modified files are actually uploaded and active</li>";
echo "<li><strong>Clear Cache:</strong> Clear any WordPress/plugin caches</li>";
echo "<li><strong>Enable Debug Logging:</strong> Turn on WordPress debug mode to see all errors</li>";
echo "<li><strong>Test Direct Method:</strong> Run the direct method test above to isolate the issue</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><em>Debug completed on: " . date('Y-m-d H:i:s') . "</em></p>";
?>
