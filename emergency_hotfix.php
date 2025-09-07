<?php
/**
 * Emergency Hotfix for EduBot 500 Error
 * Run this ONCE in WordPress admin or via file manager
 */

// Only run if WordPress is loaded
if (!defined('ABSPATH')) {
    die('WordPress not loaded');
}

echo "<h2>EduBot Emergency Hotfix</h2>";

// Check if we can modify WordPress options to disable AI temporarily
$result = update_option('edubot_emergency_disable_ai', '1');

if ($result) {
    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
    echo "<strong>✅ Emergency Fix Applied!</strong><br>";
    echo "AI processing has been temporarily disabled to prevent 500 errors.<br>";
    echo "Your chatbot will now use rule-based responses only.<br>";
    echo "This should resolve the 500 error immediately.";
    echo "</div>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Test your chatbot now - it should work without 500 errors</li>";
    echo "<li>Upload the proper fixed files when possible</li>";
    echo "<li>Remove this emergency fix by deleting this file</li>";
    echo "</ol>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
    echo "<strong>❌ Emergency Fix Failed</strong><br>";
    echo "Could not apply the emergency fix. Please upload the fixed files manually.";
    echo "</div>";
}

// Also try to apply the API key fix directly
try {
    $current_options = get_option('edubot_pro_settings', array());
    if (!empty($current_options)) {
        echo "<h3>Current Settings Check:</h3>";
        echo "<p>EduBot settings found. Emergency mode activated.</p>";
        
        // Force disable AI in settings
        $current_options['emergency_mode'] = true;
        $updated = update_option('edubot_pro_settings', $current_options);
        
        if ($updated) {
            echo "<p style='color: green;'>✅ Emergency mode enabled in settings</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Settings update error: " . $e->getMessage() . "</p>";
}

echo "<p><strong>Delete this file after running!</strong></p>";
?>
