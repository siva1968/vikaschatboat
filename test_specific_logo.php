<?php
/**
 * Test Specific Logo URL
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');
require_once('D:/xampp/htdocs/demo/wp-content/plugins/edubot-pro/includes/class-security-manager.php');

$security_manager = new EduBot_Security_Manager();

echo "=== Testing Your Logo URL ===\n\n";

// Test the exact URLs
$test_urls = array(
    'http://localhost/demo/wp-content/uploads/2025/11/site-logo.svg',
    '/demo/wp-content/uploads/2025/11/site-logo.svg',
);

foreach ($test_urls as $url) {
    echo "URL: $url\n";
    $is_safe = $security_manager->is_safe_url($url);
    echo "Result: " . ($is_safe ? '✓ SAFE' : '✗ BLOCKED') . "\n";

    if ($is_safe) {
        echo "  ✅ This URL will work!\n";
    } else {
        echo "  ❌ This URL is blocked\n";

        // Diagnose why
        if (strpos($url, 'localhost') !== false) {
            echo "  Reason: 'localhost' is blocked in production mode\n";
            echo "  Fix: Use relative path without http://localhost\n";
        }
    }
    echo "\n";
}

echo "=== Recommendation ===\n";
echo "Use this URL in the logo field:\n";
echo "/demo/wp-content/uploads/2025/11/site-logo.svg\n";
