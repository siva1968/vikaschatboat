<?php
/**
 * WordPress Debug Log Monitor
 * 
 * Displays the last MCB debug entries from WordPress debug.log
 * Real-time monitoring tool to see what's happening when the preview button is clicked
 */

$debug_log = 'D:/xampp/htdocs/demo/wp-content/debug.log';

echo "=== WordPress Debug Log Monitor ===\n\n";

if (!file_exists($debug_log)) {
    echo "⚠ Debug log not found at: $debug_log\n";
    echo "Make sure WP_DEBUG is enabled in wp-config.php\n";
    exit(1);
}

// Get file size
$file_size = filesize($debug_log);
echo "📄 Debug log size: " . number_format($file_size) . " bytes\n";

// Read last 100 lines or last 10KB, whichever is smaller
$max_lines = 100;
$lines = [];

if ($file_size > 0) {
    $fh = fopen($debug_log, 'r');
    if ($fh) {
        // Go to end of file
        fseek($fh, 0, SEEK_END);
        $pos = ftell($fh);
        
        // Read backwards
        $count = 0;
        $line = '';
        while ($pos >= 0 && $count < $max_lines) {
            fseek($fh, $pos);
            $char = fgetc($fh);
            if ($char === "\n") {
                if ($line !== '') {
                    array_unshift($lines, $line);
                    $line = '';
                    $count++;
                }
            } else {
                $line = $char . $line;
            }
            $pos--;
        }
        if ($line !== '') {
            array_unshift($lines, $line);
        }
        fclose($fh);
    }
}

if (empty($lines)) {
    echo "✓ Debug log is empty\n";
} else {
    echo "📝 Last " . count($lines) . " entries:\n";
    echo str_repeat("=", 100) . "\n";
    
    // Filter for MCB entries
    $mcb_lines = array_filter($lines, function($line) {
        return strpos($line, 'MCB') !== false;
    });
    
    if (!empty($mcb_lines)) {
        echo "\n🎯 MCB Debug Entries Only:\n";
        echo str_repeat("-", 100) . "\n";
        foreach ($mcb_lines as $line) {
            echo $line . "\n";
        }
        echo str_repeat("-", 100) . "\n";
    } else {
        echo "\n⚠ No MCB entries found in debug log\n";
        echo "↳ MCB debug code may not have executed yet\n\n";
        echo "📋 All Recent Entries:\n";
        echo str_repeat("-", 100) . "\n";
        foreach (array_slice($lines, -20) as $line) {
            echo $line . "\n";
        }
        echo str_repeat("-", 100) . "\n";
    }
}

echo "\n💡 Instructions:\n";
echo "1. Go to: http://localhost/demo/wp-admin/admin.php?page=edubot-applications\n";
echo "2. Click on any 'Preview' button\n";
echo "3. Run this script again to see the debug output\n";
echo "4. Look for lines starting with 'MCB add_sync_action:'\n";
echo "\n";
?>
