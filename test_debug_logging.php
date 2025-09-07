<?php
/**
 * Test Debug Logging for EduBot WhatsApp
 * 
 * This file tests if the debug logging is working correctly
 * Upload this to your WordPress root and access via browser
 */

// Test debug file write
$debug_file = '/home/epistemo-stage/htdocs/stage.epistemo.in/wp-content/edubot-debug.log';
$timestamp = date('Y-m-d H:i:s');

$debug_msg = "\n=== DEBUG LOG TEST [$timestamp] ===\n";
$debug_msg .= "✅ Debug logging system is working!\n";
$debug_msg .= "Server PHP version: " . phpversion() . "\n";
$debug_msg .= "Current working directory: " . getcwd() . "\n";
$debug_msg .= "Script name: " . $_SERVER['SCRIPT_NAME'] . "\n";

// Try to write to debug file
if (file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX)) {
    echo "<h1>✅ Debug Logging Test Successful!</h1>";
    echo "<p><strong>Debug file location:</strong> <code>$debug_file</code></p>";
    echo "<p><strong>Test message written at:</strong> $timestamp</p>";
    echo "<p>You can now check the debug file to see WhatsApp flow logging.</p>";
    
    // Try to read back the last few lines
    if (file_exists($debug_file)) {
        $content = file_get_contents($debug_file);
        $lines = explode("\n", $content);
        $last_lines = array_slice($lines, -10);
        
        echo "<h3>Last 10 lines from debug log:</h3>";
        echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
        echo htmlspecialchars(implode("\n", $last_lines));
        echo "</pre>";
    }
} else {
    echo "<h1>❌ Debug Logging Test Failed!</h1>";
    echo "<p><strong>Could not write to:</strong> <code>$debug_file</code></p>";
    echo "<p><strong>Possible issues:</strong></p>";
    echo "<ul>";
    echo "<li>Directory doesn't exist</li>";
    echo "<li>Permission denied</li>";
    echo "<li>Disk space full</li>";
    echo "</ul>";
    
    // Check if directory exists
    $debug_dir = dirname($debug_file);
    if (!is_dir($debug_dir)) {
        echo "<p><strong>❌ Directory does not exist:</strong> <code>$debug_dir</code></p>";
    } else {
        echo "<p><strong>✅ Directory exists:</strong> <code>$debug_dir</code></p>";
        if (!is_writable($debug_dir)) {
            echo "<p><strong>❌ Directory is not writable</strong></p>";
        } else {
            echo "<p><strong>✅ Directory is writable</strong></p>";
        }
    }
}

echo "<hr>";
echo "<h3>🔍 Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Submit a test enquiry</strong> through your chatbot</li>";
echo "<li><strong>Check the debug log</strong> at <code>$debug_file</code></li>";
echo "<li><strong>Look for WhatsApp debug messages</strong> that start with '=== EDUBOT WHATSAPP DEBUG'</li>";
echo "</ol>";

echo "<p><em>This test was run at: $timestamp</em></p>";
?>
