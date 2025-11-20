<?php
/**
 * Enable debugging in WordPress
 */

$config_file = 'D:\\xamppdev\\htdocs\\demo\\wp-config.php';

if (!file_exists($config_file)) {
    die("wp-config.php not found at: $config_file");
}

$content = file_get_contents($config_file);

// Check if debugging is already enabled
if (strpos($content, 'WP_DEBUG') !== false) {
    echo "<p style='color: orange;'><strong>⚠️ Debug configuration already exists in wp-config.php</strong></p>";
    
    // Show current settings
    preg_match('/define\(\s*[\'"]WP_DEBUG[\'"]\s*,\s*([^)]+)\)/i', $content, $matches);
    if (!empty($matches[1])) {
        echo "<p>Current WP_DEBUG: " . trim($matches[1]) . "</p>";
    }
    
    preg_match('/define\(\s*[\'"]WP_DEBUG_LOG[\'"]\s*,\s*([^)]+)\)/i', $content, $matches);
    if (!empty($matches[1])) {
        echo "<p>Current WP_DEBUG_LOG: " . trim($matches[1]) . "</p>";
    }
} else {
    // Find where to insert (before "That's all, stop editing!")
    $insert_point = strpos($content, "/* That's all, stop editing!");
    
    if ($insert_point === false) {
        $insert_point = strpos($content, "/* That is all, stop editing!");
    }
    
    if ($insert_point === false) {
        die("Could not find insertion point in wp-config.php");
    }
    
    $debug_code = "// Enable Debug Mode
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SAVEQUERIES', true);

";
    
    $new_content = substr_replace($content, $debug_code, $insert_point, 0);
    
    if (file_put_contents($config_file, $new_content)) {
        echo "<p style='color: green;'><strong>✅ Debug mode enabled!</strong></p>";
        echo "<p>Debug log will be saved to: <code>wp-content/debug.log</code></p>";
        echo "<p><a href='http://localhost/demo/debug_log_viewer.php'>View Debug Log</a></p>";
    } else {
        echo "<p style='color: red;'><strong>❌ Failed to update wp-config.php</strong></p>";
        echo "<p>Permission denied or file is not writable</p>";
    }
}

?>
