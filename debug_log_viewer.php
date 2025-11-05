<?php
/**
 * Check debug log and recent EduBot entries
 */

require_once dirname(__FILE__) . '/wp-load.php';

// Enable WP debug logging if not already enabled
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}

$log_file = WP_CONTENT_DIR . '/debug.log';

echo "<h1>Debug Log Viewer</h1>";
echo "<p>Log file: <code>$log_file</code></p>";

if (file_exists($log_file)) {
    echo "<p style='color: green;'>✅ Debug log file exists</p>";
    
    $file_size = filesize($log_file);
    echo "<p>File size: " . number_format($file_size / 1024, 2) . " KB</p>";
    
    $lines = file($log_file);
    $total_lines = count($lines);
    echo "<p>Total lines: $total_lines</p>";
    
    echo "<h2>Last 100 Lines (Newest Last)</h2>";
    
    // Get last 100 lines
    $last_lines = array_slice($lines, max(0, $total_lines - 100));
    
    // Filter for EduBot lines
    $edubot_lines = array_filter($last_lines, function($line) {
        return strpos($line, 'EduBot') !== false;
    });
    
    if (!empty($edubot_lines)) {
        echo "<h3>EduBot Entries (" . count($edubot_lines) . ")</h3>";
        echo "<pre style='background: #f5f5f5; padding: 15px; max-height: 600px; overflow-y: auto; border: 1px solid #ddd;'>";
        foreach ($edubot_lines as $line) {
            echo esc_html($line);
        }
        echo "</pre>";
    } else {
        echo "<p style='color: orange;'>No EduBot entries found in last 100 lines</p>";
        
        echo "<h3>Last 20 Lines (All)</h3>";
        echo "<pre style='background: #f5f5f5; padding: 15px; max-height: 400px; overflow-y: auto; border: 1px solid #ddd;'>";
        foreach (array_slice($last_lines, -20) as $line) {
            echo esc_html($line);
        }
        echo "</pre>";
    }
} else {
    echo "<p style='color: red;'>❌ Debug log file does not exist</p>";
    echo "<p>Create it at: <code>$log_file</code></p>";
    
    echo "<h2>WordPress Configuration</h2>";
    echo "<p><strong>WP_DEBUG:</strong> " . (defined('WP_DEBUG') ? (WP_DEBUG ? 'true' : 'false') : 'not defined') . "</p>";
    echo "<p><strong>WP_DEBUG_LOG:</strong> " . (defined('WP_DEBUG_LOG') ? (WP_DEBUG_LOG ? 'true' : 'false') : 'not defined') . "</p>";
    echo "<p><strong>WP_DEBUG_DISPLAY:</strong> " . (defined('WP_DEBUG_DISPLAY') ? (WP_DEBUG_DISPLAY ? 'true' : 'false') : 'not defined') . "</p>";
    
    // Try to enable it
    echo "<h2>Attempting to Enable Debug Logging</h2>";
    $config_file = ABSPATH . 'wp-config.php';
    
    if (file_exists($config_file)) {
        $config_content = file_get_contents($config_file);
        
        // Check if debug is already configured
        if (strpos($config_content, 'WP_DEBUG') === false) {
            echo "<p>Debug logging is not configured. Would need to edit wp-config.php manually.</p>";
        } else {
            echo "<p>Debug logging is already configured in wp-config.php</p>";
        }
    } else {
        echo "<p>Could not find wp-config.php at: $config_file</p>";
    }
}

// Also check database tables
echo "<h2>Database Status</h2>";

global $wpdb;

$enquiries_table = $wpdb->prefix . 'edubot_enquiries';
$applications_table = $wpdb->prefix . 'edubot_applications';

$enq_exists = $wpdb->get_var("SHOW TABLES LIKE '$enquiries_table'") === $enquiries_table;
$app_exists = $wpdb->get_var("SHOW TABLES LIKE '$applications_table'") === $applications_table;

echo "<p><strong>Enquiries table:</strong> " . ($enq_exists ? '✅ EXISTS' : '❌ MISSING') . "</p>";
echo "<p><strong>Applications table:</strong> " . ($app_exists ? '✅ EXISTS' : '❌ MISSING') . "</p>";

if ($enq_exists) {
    $enq_count = $wpdb->get_var("SELECT COUNT(*) FROM $enquiries_table");
    echo "<p>Enquiries count: $enq_count</p>";
}

if ($app_exists) {
    $app_count = $wpdb->get_var("SELECT COUNT(*) FROM $applications_table");
    echo "<p>Applications count: $app_count</p>";
}

?>
