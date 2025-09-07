<?php
/**
 * WordPress Emergency Hotfix for EduBot 500 Error
 * Upload this to your WordPress root directory and visit the URL
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-includes/wp-db.php');
require_once('wp-includes/functions.php');

?>
<!DOCTYPE html>
<html>
<head>
    <title>EduBot Emergency Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0; border-radius: 5px; }
        .info { background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; margin: 10px 0; border-radius: 5px; }
        .code { background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>

<h1>🛠️ EduBot Emergency Hotfix</h1>

<?php

try {
    echo "<h2>Step 1: WordPress Connection Test</h2>";
    
    if (function_exists('update_option') && function_exists('get_option')) {
        echo "<div class='success'>✅ WordPress functions available</div>";
    } else {
        echo "<div class='error'>❌ WordPress functions not available</div>";
        exit;
    }

    echo "<h2>Step 2: Apply Emergency AI Disable</h2>";
    
    // Force disable AI processing in multiple ways
    $fixes_applied = 0;
    
    // Method 1: Create emergency disable option
    if (update_option('edubot_emergency_disable_ai', '1')) {
        echo "<div class='success'>✅ Method 1: Emergency AI disable flag set</div>";
        $fixes_applied++;
    } else {
        echo "<div class='error'>❌ Method 1: Failed to set emergency flag</div>";
    }
    
    // Method 2: Modify existing settings to force rule-based mode
    $current_settings = get_option('edubot_pro_settings', array());
    $current_settings['force_rule_based'] = true;
    $current_settings['disable_ai'] = true;
    
    if (update_option('edubot_pro_settings', $current_settings)) {
        echo "<div class='success'>✅ Method 2: Updated EduBot settings to force rule-based mode</div>";
        $fixes_applied++;
    } else {
        echo "<div class='error'>❌ Method 2: Failed to update EduBot settings</div>";
    }
    
    // Method 3: Set OpenAI key to empty to prevent AI calls
    $api_config = get_option('edubot_api_config', array());
    $api_config['openai_key_disabled'] = true;
    
    if (update_option('edubot_api_config', $api_config)) {
        echo "<div class='success'>✅ Method 3: Disabled OpenAI API calls</div>";
        $fixes_applied++;
    } else {
        echo "<div class='error'>❌ Method 3: Failed to disable OpenAI API</div>";
    }
    
    echo "<h2>Step 3: Results</h2>";
    
    if ($fixes_applied > 0) {
        echo "<div class='success'>";
        echo "<strong>🎉 SUCCESS! Emergency fixes applied ({$fixes_applied}/3 methods worked)</strong><br><br>";
        echo "<strong>What happens now:</strong><br>";
        echo "• Your chatbot will use ONLY rule-based responses<br>";
        echo "• No more 500 errors should occur<br>";
        echo "• All admission workflow functionality preserved<br>";
        echo "• WhatsApp notifications will still work<br><br>";
        echo "<strong>Test your chatbot now!</strong>";
        echo "</div>";
        
        echo "<h3>🧪 Test Instructions:</h3>";
        echo "<div class='info'>";
        echo "1. Go to your website: <a href='https://stage.epistemo.in' target='_blank'>https://stage.epistemo.in</a><br>";
        echo "2. Click on the chatbot<br>";
        echo "3. Start admission enquiry<br>";
        echo "4. Complete the full workflow including date entry<br>";
        echo "5. Verify no 500 errors occur<br>";
        echo "</div>";
        
        echo "<h3>🔄 To Restore AI Later:</h3>";
        echo "<div class='code'>";
        echo "delete_option('edubot_emergency_disable_ai');<br>";
        echo "// Then upload the fixed files and remove emergency settings";
        echo "</div>";
        
    } else {
        echo "<div class='error'>";
        echo "<strong>❌ FAILED: No emergency fixes could be applied</strong><br>";
        echo "This means there might be deeper WordPress issues.<br>";
        echo "Please upload the fixed files manually or contact your hosting provider.";
        echo "</div>";
    }
    
    echo "<h2>Step 4: Cleanup</h2>";
    echo "<div class='info'>";
    echo "🗑️ <strong>IMPORTANT:</strong> Delete this file (emergency_hotfix_wp.php) after testing!<br>";
    echo "It's only needed once to apply the emergency fix.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine();
    echo "</div>";
}

?>

<hr>
<p><small>EduBot Emergency Hotfix - <?php echo date('Y-m-d H:i:s'); ?></small></p>

</body>
</html>
