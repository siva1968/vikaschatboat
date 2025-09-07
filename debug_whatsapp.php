<?php
/**
 * Debug WhatsApp Configuration
 * This will help identify why WhatsApp messages are not being sent
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
        die('Cannot find WordPress. Please upload this file to your WordPress site.');
    }
}

echo "<h1>🔍 WhatsApp Debug Report</h1>";
echo "<p>Checking why WhatsApp messages are not being triggered...</p>";

// Check 1: WhatsApp notifications enabled
echo "<h2>1. Notification Settings</h2>";
$whatsapp_enabled = get_option('edubot_whatsapp_notifications', 0);
echo "<p><strong>WhatsApp Notifications:</strong> ";
if ($whatsapp_enabled) {
    echo "✅ ENABLED</p>";
} else {
    echo "❌ <span style='color: red;'>DISABLED</span> - This is likely the issue!</p>";
    echo "<p><em>Fix: Go to Admin > EduBot Pro > School Settings and enable 'WhatsApp Notifications'</em></p>";
}

// Check 2: WhatsApp API Configuration
echo "<h2>2. API Configuration</h2>";
$whatsapp_token = get_option('edubot_whatsapp_token', '');
$whatsapp_provider = get_option('edubot_whatsapp_provider', '');

echo "<p><strong>WhatsApp Provider:</strong> ";
if (!empty($whatsapp_provider)) {
    echo "✅ " . esc_html($whatsapp_provider) . "</p>";
} else {
    echo "❌ <span style='color: red;'>NOT CONFIGURED</span></p>";
    echo "<p><em>Fix: Go to Admin > EduBot Pro > API Integrations and set WhatsApp Provider</em></p>";
}

echo "<p><strong>WhatsApp Token:</strong> ";
if (!empty($whatsapp_token)) {
    echo "✅ Configured (***hidden***)</p>";
} else {
    echo "❌ <span style='color: red;'>NOT CONFIGURED</span></p>";
    echo "<p><em>Fix: Go to Admin > EduBot Pro > API Integrations and set WhatsApp Access Token</em></p>";
}

// Check 3: Phone ID/Account SID
$whatsapp_phone_id = get_option('edubot_whatsapp_phone_id', '');
echo "<p><strong>WhatsApp Phone ID/Account SID:</strong> ";
if (!empty($whatsapp_phone_id)) {
    echo "✅ " . esc_html($whatsapp_phone_id) . "</p>";
} else {
    echo "❌ <span style='color: red;'>NOT CONFIGURED</span></p>";
    echo "<p><em>Fix: Go to Admin > EduBot Pro > API Integrations and set Phone ID (Meta) or Account SID (Twilio)</em></p>";
}

// Check 4: Template Configuration  
echo "<h2>3. Template Configuration</h2>";
$template = get_option('edubot_whatsapp_template', '');
echo "<p><strong>WhatsApp Template:</strong> ";
if (!empty($template)) {
    echo "✅ Configured</p>";
    echo "<div style='background: #f0f0f0; padding: 10px; border-radius: 5px; font-family: monospace; white-space: pre-wrap;'>" . esc_html(substr($template, 0, 200)) . "...</div>";
} else {
    echo "❌ <span style='color: red;'>NOT CONFIGURED</span></p>";
    echo "<p><em>Fix: Go to Admin > EduBot Pro > School Settings and set WhatsApp Message Template</em></p>";
}

// Check 5: API Integration Class
echo "<h2>4. API Integration Class</h2>";
$api_file = __DIR__ . '/includes/class-api-integrations.php';
if (file_exists($api_file)) {
    echo "<p><strong>API Integrations File:</strong> ✅ Found</p>";
    
    if (!class_exists('EduBot_API_Integrations')) {
        require_once $api_file;
    }
    
    if (class_exists('EduBot_API_Integrations')) {
        echo "<p><strong>API Integrations Class:</strong> ✅ Loaded</p>";
        
        $api = new EduBot_API_Integrations();
        if (method_exists($api, 'send_whatsapp')) {
            echo "<p><strong>send_whatsapp Method:</strong> ✅ Available</p>";
        } else {
            echo "<p><strong>send_whatsapp Method:</strong> ❌ <span style='color: red;'>NOT FOUND</span></p>";
        }
    } else {
        echo "<p><strong>API Integrations Class:</strong> ❌ <span style='color: red;'>NOT LOADED</span></p>";
    }
} else {
    echo "<p><strong>API Integrations File:</strong> ❌ <span style='color: red;'>NOT FOUND</span></p>";
    echo "<p><em>Expected location: " . esc_html($api_file) . "</em></p>";
}

// Check 6: WordPress Error Logs
echo "<h2>5. Recent Error Logs</h2>";
$log_file = ini_get('error_log');
if ($log_file && file_exists($log_file)) {
    echo "<p><strong>Error Log:</strong> " . esc_html($log_file) . "</p>";
    $recent_logs = shell_exec("tail -20 " . escapeshellarg($log_file) . " | grep -i 'edubot.*whatsapp'");
    if ($recent_logs) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px;'>";
        echo "<strong>Recent WhatsApp-related errors:</strong><br>";
        echo "<pre>" . esc_html($recent_logs) . "</pre>";
        echo "</div>";
    } else {
        echo "<p><em>No recent WhatsApp-related errors found in logs</em></p>";
    }
} else {
    echo "<p><em>Error log file not accessible</em></p>";
}

// Summary and Action Plan
echo "<h2>🎯 Action Plan</h2>";
$issues_found = 0;

if (!$whatsapp_enabled) {
    $issues_found++;
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>❌ Issue #{$issues_found}: WhatsApp Notifications Disabled</strong><br>";
    echo "Go to <strong>Admin > EduBot Pro > School Settings</strong> and enable 'WhatsApp Notifications'";
    echo "</div>";
}

if (empty($whatsapp_provider) || empty($whatsapp_token)) {
    $issues_found++;
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>❌ Issue #{$issues_found}: WhatsApp API Not Configured</strong><br>";
    echo "Go to <strong>Admin > EduBot Pro > API Integrations</strong> and configure:<br>";
    echo "• WhatsApp Provider (Meta or Twilio)<br>";
    echo "• Access Token<br>";
    echo "• Phone ID (Meta) or Account SID (Twilio)";
    echo "</div>";
}

if (empty($template)) {
    $issues_found++;
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>❌ Issue #{$issues_found}: WhatsApp Template Not Configured</strong><br>";
    echo "Go to <strong>Admin > EduBot Pro > School Settings</strong> and set the WhatsApp Message Template";
    echo "</div>";
}

if (!file_exists($api_file)) {
    $issues_found++;
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>❌ Issue #{$issues_found}: API Integration File Missing</strong><br>";
    echo "The file <code>includes/class-api-integrations.php</code> is missing";
    echo "</div>";
}

if ($issues_found === 0) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "<strong>✅ Configuration Looks Good!</strong><br>";
    echo "WhatsApp should be working. Check WordPress error logs for any runtime issues.";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px;'>";
    echo "<strong>⚠️ Found {$issues_found} Issue(s)</strong><br>";
    echo "Fix the above issues and test again.";
    echo "</div>";
}

echo "<hr><p><em>Debug completed on: " . date('Y-m-d H:i:s') . "</em></p>";
?>
