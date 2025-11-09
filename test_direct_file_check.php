<?php
/**
 * Test with explicit require to bypass cache
 */

// Direct file test - bypass WordPress autoload
require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== Direct PHP test (checking file content) ===\n\n";

$file_path = plugin_dir_path(__FILE__) . 'includes/class-edubot-mcb-admin.php';
$file_content = file_get_contents($file_path);

// Check if the early return is in the file
if (strpos($file_content, 'if (!$mcb_service->is_sync_enabled())') !== false) {
    echo "✅ File CONTAINS the conditional check\n";
    
    // Extract the relevant section
    preg_match('/public static function add_sync_action\(\$actions, \$application\) \{(.*?)\}/s', $file_content, $matches);
    if (!empty($matches[1])) {
        $function_body = $matches[1];
        $lines = explode("\n", $function_body);
        echo "\nFirst 20 lines of add_sync_action():\n";
        for ($i = 0; $i < min(20, count($lines)); $i++) {
            echo "  " . trim($lines[$i]) . "\n";
        }
    }
} else {
    echo "❌ File does NOT contain the conditional check\n";
}

echo "\n=== Now testing with clean load ===\n\n";

// Completely reload the class
if (class_exists('EduBot_MCB_Admin')) {
    echo "EduBot_MCB_Admin class already loaded\n";
}

// Use reflection to examine the function
$reflection = new ReflectionClass('EduBot_MCB_Admin');
$method = $reflection->getMethod('add_sync_action');
$filename = $method->getFileName();
$start = $method->getStartLine();
$end = $method->getEndLine();

echo "\nMethod location: $filename:$start-$end\n";

$code_lines = file($filename);
echo "\nMethod source code:\n";
for ($i = $start - 1; $i < min($start + 20, $end); $i++) {
    echo ($i + 1) . ": " . $code_lines[$i];
}
?>
