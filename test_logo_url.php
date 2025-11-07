<?php
/**
 * Test Logo URL Validation
 */

// Load WordPress
require_once('D:/xampp/htdocs/demo/wp-load.php');

// Load security manager
if (!class_exists('EduBot_Security_Manager')) {
    require_once('D:/xampp/htdocs/demo/wp-content/plugins/edubot-pro/includes/class-security-manager.php');
}

echo "=== Logo URL Validation Test ===\n\n";

// Test different URL formats
$test_urls = array(
    'https://example.com/logo.png',
    'http://example.com/logo.jpg',
    '/wp-content/uploads/2024/01/logo.png',
    '/uploads/logo.png',
    'javascript:alert("xss")',
    'data:image/png;base64,abc123',
    'wp-content/uploads/logo.png', // Relative without leading slash
    'https://epistemo.in/wp-content/uploads/2024/logo.png',
);

$security_manager = new EduBot_Security_Manager();

echo "Testing URL validation:\n\n";

foreach ($test_urls as $url) {
    $is_safe = $security_manager->is_safe_url($url);
    $status = $is_safe ? '✓ SAFE' : '✗ BLOCKED';

    echo "URL: $url\n";
    echo "Result: $status\n";

    // Additional checks
    $is_relative = (strpos($url, '/') === 0 && strpos($url, '//') !== 0);
    $is_absolute = filter_var($url, FILTER_VALIDATE_URL);

    echo "  - Is Relative: " . ($is_relative ? 'Yes' : 'No') . "\n";
    echo "  - Is Absolute: " . ($is_absolute ? 'Yes' : 'No') . "\n";

    if ($is_absolute) {
        $parsed = parse_url($url);
        echo "  - Scheme: " . ($parsed['scheme'] ?? 'none') . "\n";
        echo "  - Host: " . ($parsed['host'] ?? 'none') . "\n";
    }

    echo "\n";
}

echo "\n=== Please Test Your Logo URL ===\n";
echo "Enter your logo URL below and I'll test it:\n";
echo "(Example: https://yourdomain.com/logo.png)\n\n";

// If running from command line with argument
if (isset($argv[1])) {
    $test_url = $argv[1];
    echo "Testing your URL: $test_url\n\n";

    $is_safe = $security_manager->is_safe_url($test_url);

    if ($is_safe) {
        echo "✓ SUCCESS: URL is valid and safe!\n";
        echo "You can use this URL for your logo.\n";
    } else {
        echo "✗ FAILED: URL failed security validation\n\n";
        echo "Possible issues:\n";

        // Detailed diagnostics
        if (empty($test_url)) {
            echo "  - URL is empty\n";
        }

        $is_relative = (strpos($test_url, '/') === 0 && strpos($test_url, '//') !== 0);
        $is_absolute = filter_var($test_url, FILTER_VALIDATE_URL);

        if (!$is_relative && !$is_absolute) {
            echo "  - URL must be absolute (http/https) or relative (/wp-content/...)\n";
        }

        if ($is_absolute) {
            $parsed = parse_url($test_url);
            if (!isset($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), array('http', 'https'))) {
                echo "  - Only HTTP/HTTPS schemes allowed\n";
            }
        }

        // Check for dangerous patterns
        $dangerous = array('javascript:', 'data:', '<script', 'onerror=', 'onclick=', 'onload=');
        $url_lower = strtolower($test_url);
        foreach ($dangerous as $pattern) {
            if (strpos($url_lower, $pattern) !== false) {
                echo "  - Contains dangerous pattern: $pattern\n";
            }
        }

        echo "\nSuggestions:\n";
        echo "  1. Use absolute URL: https://yourdomain.com/logo.png\n";
        echo "  2. Use relative URL: /wp-content/uploads/logo.png\n";
        echo "  3. Upload via WordPress Media Library\n";
    }
}

echo "\n=== Usage ===\n";
echo "Test a URL: php test_logo_url.php \"https://example.com/logo.png\"\n";
