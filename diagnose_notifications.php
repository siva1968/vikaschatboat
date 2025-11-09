<?php
/**
 * Notification Diagnostics - Debug Script
 * 
 * Place in WordPress root: http://yoursite.com/diagnose_notifications.php
 * Shows exactly why notifications aren't being sent
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Admin only');
}

echo '<h1>🔍 Notification System Diagnostics</h1>';
echo '<style>
    body { font-family: Arial; margin: 20px; }
    .status { padding: 10px; margin: 10px 0; border-left: 4px solid; }
    .success { background: #d4edda; border-color: #28a745; }
    .error { background: #f8d7da; border-color: #dc3545; }
    .warning { background: #fff3cd; border-color: #ffc107; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background: #f8f9fa; }
    code { background: #f4f4f4; padding: 2px 4px; }
</style>';

global $wpdb;

// 1. Check database table exists
echo '<h2>1️⃣ Database Table Check</h2>';
$table = $wpdb->prefix . 'edubot_school_configs';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;

if ($table_exists) {
    echo '<div class="status success">✅ Config table exists: ' . $table . '</div>';
    
    // Check if config exists
    $config_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE site_id = %d AND status = 'active'",
        get_current_blog_id()
    ));
    
    if ($config_data) {
        echo '<div class="status success">✅ Active config found for site ID: ' . get_current_blog_id() . '</div>';
        
        // Decode config
        $config = json_decode($config_data->config_data, true);
        
        if ($config) {
            echo '<div class="status success">✅ Config JSON decoded successfully</div>';
            
            // Check notification_settings
            if (isset($config['notification_settings'])) {
                echo '<div class="status success">✅ notification_settings exists in config</div>';
                
                $notif = $config['notification_settings'];
                echo '<table>';
                echo '<tr><th>Setting</th><th>Value</th><th>Status</th></tr>';
                
                echo '<tr><td>parent_notifications</td><td>' . (isset($notif['parent_notifications']) ? ($notif['parent_notifications'] ? 'true' : 'false') : 'NOT SET') . '</td><td>' . (isset($notif['parent_notifications']) && $notif['parent_notifications'] ? '✅' : '❌') . '</td></tr>';
                
                echo '<tr><td>admin_notifications</td><td>' . (isset($notif['admin_notifications']) ? ($notif['admin_notifications'] ? 'true' : 'false') : 'NOT SET') . '</td><td>' . (isset($notif['admin_notifications']) && $notif['admin_notifications'] ? '✅' : '❌') . '</td></tr>';
                
                echo '<tr><td>email_enabled</td><td>' . (isset($notif['email_enabled']) ? ($notif['email_enabled'] ? 'true' : 'false') : 'NOT SET') . '</td><td>' . (isset($notif['email_enabled']) && $notif['email_enabled'] ? '✅' : '❌') . '</td></tr>';
                
                echo '<tr><td>whatsapp_enabled</td><td>' . (isset($notif['whatsapp_enabled']) ? ($notif['whatsapp_enabled'] ? 'true' : 'false') : 'NOT SET') . '</td><td>' . (isset($notif['whatsapp_enabled']) && $notif['whatsapp_enabled'] ? '✅' : '❌') . '</td></tr>';
                
                echo '<tr><td>sms_enabled</td><td>' . (isset($notif['sms_enabled']) ? ($notif['sms_enabled'] ? 'true' : 'false') : 'NOT SET') . '</td><td>' . (isset($notif['sms_enabled']) && $notif['sms_enabled'] ? '✅' : '❌') . '</td></tr>';
                
                echo '</table>';
                
                // Check if any notifications are enabled
                $any_enabled = (isset($notif['parent_notifications']) && $notif['parent_notifications']) ||
                               (isset($notif['admin_notifications']) && $notif['admin_notifications']);
                
                if (!$any_enabled) {
                    echo '<div class="status error">❌ NO NOTIFICATIONS ENABLED - This is why nothing is sending!</div>';
                }
                
            } else {
                echo '<div class="status error">❌ notification_settings NOT FOUND in config - Using defaults</div>';
            }
        } else {
            echo '<div class="status error">❌ Failed to decode config JSON</div>';
        }
    } else {
        echo '<div class="status error">❌ No active config found for site ID: ' . get_current_blog_id() . '</div>';
        echo '<p>Run: <code>SELECT * FROM ' . $table . ' LIMIT 5;</code></p>';
    }
} else {
    echo '<div class="status error">❌ Config table does NOT exist: ' . $table . '</div>';
}

// 2. Check API Integrations table
echo '<h2>2️⃣ API Integrations Table Check</h2>';
$api_table = $wpdb->prefix . 'edubot_api_integrations';
$api_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$api_table'") == $api_table;

if ($api_table_exists) {
    echo '<div class="status success">✅ API Integrations table exists</div>';
    
    $api_config = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $api_table WHERE site_id = %d AND status = 'active'",
        get_current_blog_id()
    ));
    
    if ($api_config) {
        echo '<div class="status success">✅ Active API config found</div>';
        
        $api_keys = json_decode($api_config->api_keys, true);
        if ($api_keys) {
            echo '<table>';
            echo '<tr><th>API Key</th><th>Configured</th><th>Status</th></tr>';
            
            echo '<tr><td>email_provider</td><td>' . (isset($api_keys['email_provider']) && !empty($api_keys['email_provider']) ? '✅ ' . $api_keys['email_provider'] : '❌') . '</td><td>' . (isset($api_keys['email_provider']) && !empty($api_keys['email_provider']) ? '✅' : '⚠️') . '</td></tr>';
            
            echo '<tr><td>whatsapp_provider</td><td>' . (isset($api_keys['whatsapp_provider']) && !empty($api_keys['whatsapp_provider']) ? '✅ ' . $api_keys['whatsapp_provider'] : '❌') . '</td><td>' . (isset($api_keys['whatsapp_provider']) && !empty($api_keys['whatsapp_provider']) ? '✅' : '⚠️') . '</td></tr>';
            
            echo '<tr><td>sms_provider</td><td>' . (isset($api_keys['sms_provider']) && !empty($api_keys['sms_provider']) ? '✅ ' . $api_keys['sms_provider'] : '❌') . '</td><td>' . (isset($api_keys['sms_provider']) && !empty($api_keys['sms_provider']) ? '✅' : '⚠️') . '</td></tr>';
            
            echo '</table>';
        }
    } else {
        echo '<div class="status warning">⚠️ No active API config found</div>';
    }
} else {
    echo '<div class="status error">❌ API Integrations table does NOT exist</div>';
}

// 3. Check recent applications
echo '<h2>3️⃣ Recent Applications</h2>';
$apps_table = $wpdb->prefix . 'edubot_applications';
$apps_exist = $wpdb->get_var("SHOW TABLES LIKE '$apps_table'") == $apps_table;

if ($apps_exist) {
    $recent_apps = $wpdb->get_results($wpdb->prepare(
        "SELECT id, application_number, email_sent, whatsapp_sent, sms_sent, created_at FROM $apps_table ORDER BY id DESC LIMIT 5"
    ));
    
    if ($recent_apps) {
        echo '<table>';
        echo '<tr><th>App #</th><th>Email</th><th>WhatsApp</th><th>SMS</th><th>Created</th></tr>';
        
        foreach ($recent_apps as $app) {
            echo '<tr>';
            echo '<td>' . $app->application_number . '</td>';
            echo '<td>' . ($app->email_sent ? '✅' : '❌') . '</td>';
            echo '<td>' . ($app->whatsapp_sent ? '✅' : '❌') . '</td>';
            echo '<td>' . ($app->sms_sent ? '✅' : '❌') . '</td>';
            echo '<td>' . date('M d, Y H:i', strtotime($app->created_at)) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        echo '<div class="status warning">⚠️ No applications yet</div>';
    }
} else {
    echo '<div class="status error">❌ Applications table does NOT exist</div>';
}

// 4. Summary
echo '<h2>📋 Summary & Fixes</h2>';

$issues = array();

if (!isset($config['notification_settings']) || !isset($config['notification_settings']['parent_notifications']) || !$config['notification_settings']['parent_notifications']) {
    $issues[] = 'Parent notifications are disabled or not configured';
}

if (!isset($config['notification_settings']) || !isset($config['notification_settings']['admin_notifications']) || !$config['notification_settings']['admin_notifications']) {
    $issues[] = 'Admin notifications are disabled or not configured';
}

if (!isset($config['notification_settings']) || !isset($config['notification_settings']['email_enabled']) || !$config['notification_settings']['email_enabled']) {
    $issues[] = 'Email notifications are disabled or not configured';
}

if (count($issues) > 0) {
    echo '<div class="status error"><strong>Issues Found:</strong>';
    echo '<ul>';
    foreach ($issues as $issue) {
        echo '<li>' . $issue . '</li>';
    }
    echo '</ul>';
    echo '<p><strong>Fix:</strong> Go to <em>EduBot Pro Settings → Notification Settings</em> and enable the notifications.</p>';
    echo '</div>';
} else {
    echo '<div class="status success">✅ All notifications appear to be configured correctly</div>';
}

// 5. Error Log
echo '<h2>📜 Recent Error Log (EduBot entries)</h2>';
$debug_log = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log)) {
    $lines = tail($debug_log, 100);
    $edubot_lines = array_filter($lines, function($line) {
        return stripos($line, 'edubot') !== false || stripos($line, 'notification') !== false;
    });
    
    if (count($edubot_lines) > 0) {
        echo '<pre style="background: #f4f4f4; padding: 10px; max-height: 300px; overflow-y: auto;">';
        foreach (array_slice($edubot_lines, -20) as $line) {
            echo esc_html($line) . "\n";
        }
        echo '</pre>';
    } else {
        echo '<div class="status warning">⚠️ No EduBot entries in error log</div>';
    }
} else {
    echo '<div class="status warning">⚠️ Debug log not found. Enable WP_DEBUG.</div>';
}

echo '<hr><p><strong>DELETE THIS FILE AFTER DEBUGGING</strong></p>';

// Helper function
function tail($file, $lines = 100) {
    $handle = fopen($file, 'r');
    if (!$handle) return array();
    
    fseek($handle, 0, SEEK_END);
    $pos = ftell($handle);
    $buffer = '';
    $line_count = 0;
    
    while ($pos >= 0 && $line_count < $lines) {
        $chunk_size = min(8192, $pos + 1);
        $pos -= $chunk_size;
        fseek($handle, $pos);
        $buffer = fread($handle, $chunk_size) . $buffer;
        $line_count = count(explode("\n", $buffer)) - 1;
    }
    
    fclose($handle);
    $lines = explode("\n", $buffer);
    return array_slice($lines, 0, $line_count);
}

?>
