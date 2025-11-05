<?php
/**
 * Test file to validate logo URL security validation fixes
 * Run this to test various URL formats
 */

// Simulated security manager test
class Test_Security_Manager {
    public function is_safe_url($url) {
        // Basic URL validation
        if (empty($url) || !is_string($url)) {
            return false;
        }

        // Allow relative URLs (like WordPress media paths)
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            // Relative URL - validate path only (no scheme/host needed)
            if (strlen($url) <= 2048 && strpos($url, '%25') === false) {
                // Block dangerous patterns in relative paths
                $dangerous_patterns = array(
                    'javascript:',
                    'data:',
                    'vbscript:',
                    'file:',
                    'ftp:',
                    '<script',
                    'onload=',
                    'onerror=',
                    'onclick='
                );
                $url_lower = strtolower($url);
                foreach ($dangerous_patterns as $pattern) {
                    if (strpos($url_lower, $pattern) !== false) {
                        return false;
                    }
                }
                return true; // Relative URL is safe
            }
            return false;
        }

        // Validate URL format for absolute URLs
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Parse URL components
        $parsed_url = parse_url($url);
        if (!$parsed_url || !isset($parsed_url['scheme']) || !isset($parsed_url['host'])) {
            return false;
        }

        // Only allow HTTP and HTTPS schemes
        if (!in_array(strtolower($parsed_url['scheme']), array('http', 'https'))) {
            return false;
        }

        return true;
    }
}

// Test cases
$tester = new Test_Security_Manager();

$test_cases = array(
    // Should PASS - Relative URLs
    array(
        'url' => '/wp-content/uploads/school-logo.png',
        'expected' => true,
        'description' => 'Relative URL - WordPress uploads'
    ),
    array(
        'url' => '/wp-content/uploads/2024/11/logo.jpg',
        'expected' => true,
        'description' => 'Relative URL - WordPress uploads with date'
    ),
    array(
        'url' => '/wp-content/plugins/edubot-pro/assets/logo.svg',
        'expected' => true,
        'description' => 'Relative URL - Plugin assets'
    ),
    array(
        'url' => '/images/school-logo.png',
        'expected' => true,
        'description' => 'Relative URL - Custom path'
    ),
    
    // Should PASS - Absolute URLs
    array(
        'url' => 'https://example.com/logo.png',
        'expected' => true,
        'description' => 'Absolute URL - HTTPS'
    ),
    array(
        'url' => 'http://example.com/logo.png',
        'expected' => true,
        'description' => 'Absolute URL - HTTP'
    ),
    array(
        'url' => 'https://cdn.example.com/schools/logo-123.jpg',
        'expected' => true,
        'description' => 'Absolute URL - CDN with query'
    ),
    
    // Should FAIL - Malicious patterns
    array(
        'url' => 'javascript:alert("xss")',
        'expected' => false,
        'description' => 'Malicious - JavaScript protocol'
    ),
    array(
        'url' => 'data:image/svg+xml,<svg onload=alert("xss")>',
        'expected' => false,
        'description' => 'Malicious - Data URI'
    ),
    array(
        'url' => '/wp-content/uploads/<script>alert("xss")</script>',
        'expected' => false,
        'description' => 'Malicious - Relative with script'
    ),
    array(
        'url' => '/wp-content/uploads/logo.png?onclick=alert("xss")',
        'expected' => false,
        'description' => 'Malicious - Event handler in query'
    ),
    
    // Should FAIL - Invalid formats
    array(
        'url' => 'not-a-url',
        'expected' => false,
        'description' => 'Invalid - No protocol or path'
    ),
    array(
        'url' => 'ftp://example.com/logo.png',
        'expected' => false,
        'description' => 'Invalid - FTP protocol'
    ),
    array(
        'url' => '',
        'expected' => false,
        'description' => 'Invalid - Empty string'
    ),
);

// Run tests
echo "================================\n";
echo "Logo URL Validation Test Suite\n";
echo "================================\n\n";

$passed = 0;
$failed = 0;

foreach ($test_cases as $test) {
    $result = $tester->is_safe_url($test['url']);
    $status = $result === $test['expected'] ? '✓ PASS' : '✗ FAIL';
    
    if ($result === $test['expected']) {
        $passed++;
    } else {
        $failed++;
    }
    
    echo $status . " | " . $test['description'] . "\n";
    echo "    URL: " . $test['url'] . "\n";
    echo "    Expected: " . ($test['expected'] ? 'PASS' : 'FAIL') . " | Got: " . ($result ? 'PASS' : 'FAIL') . "\n\n";
}

echo "================================\n";
echo "Test Results: " . $passed . " passed, " . $failed . " failed\n";
echo "================================\n";

if ($failed === 0) {
    echo "\n✓ All tests passed! Logo URL validation is working correctly.\n";
} else {
    echo "\n✗ Some tests failed. Please review the implementation.\n";
}
