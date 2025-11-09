<?php
/**
 * Test UTM Data Capture in Form Submissions
 * This simulates a form submission with UTM parameters in cookies
 */

require '/xampp/htdocs/demo/wp-load.php';

// Start session to set cookies
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>🧪 Testing UTM Capture in Form Submissions</h1>";
echo "<hr>";

// Step 1: Simulate setting UTM cookies (like capture_utm_to_cookies does)
echo "<h2>Step 1: Simulating UTM Cookie Capture</h2>";
echo "<p>Setting cookies as if user visited: ?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025</p>";

$utm_params = array(
    'utm_source' => 'google',
    'utm_medium' => 'cpc',
    'utm_campaign' => 'admissions_2025',
    'utm_term' => 'school admission',
    'utm_content' => 'banner_1',
    'gclid' => 'test_gclid_abc123',
    'fbclid' => 'test_fbclid_xyz789'
);

foreach ($utm_params as $param => $value) {
    $_COOKIE['edubot_' . $param] = $value;
    echo "<p>✅ Set cookie: edubot_{$param} = {$value}</p>";
}

echo "<hr>";

// Step 2: Load the shortcode class and call get_utm_data()
echo "<h2>Step 2: Calling get_utm_data() from Shortcode</h2>";

$shortcode = new EduBot_Shortcode();
$utm_data = $shortcode->call_get_utm_data(); // We need to make this method public or call via reflection

// If the method is private, we need to use reflection
if (!method_exists($shortcode, 'call_get_utm_data')) {
    echo "<p>Using PHP Reflection to call private method...</p>";
    $reflection = new ReflectionClass($shortcode);
    $method = $reflection->getMethod('get_utm_data');
    $method->setAccessible(true);
    $utm_data = $method->invoke($shortcode);
}

echo "<p>Retrieved UTM data:</p>";
echo "<pre>" . json_encode($utm_data, JSON_PRETTY_PRINT) . "</pre>";

// Step 3: Check the most recent application
echo "<h2>Step 3: Checking Recent Applications</h2>";

global $wpdb;
$app_table = $wpdb->prefix . 'edubot_applications';

$recent_apps = $wpdb->get_results(
    "SELECT id, application_number, utm_data, gclid, fbclid, created_at 
     FROM {$app_table} 
     ORDER BY created_at DESC 
     LIMIT 3",
    ARRAY_A
);

if ($recent_apps) {
    echo "<table border='1' cellpadding='10' style='width: 100%;'>";
    echo "<tr><th>ID</th><th>App #</th><th>UTM Data</th><th>gclid</th><th>fbclid</th><th>Created</th></tr>";
    foreach ($recent_apps as $app) {
        $utm_json = $app['utm_data'] ? json_decode($app['utm_data'], true) : 'NULL';
        $utm_display = is_array($utm_json) ? 'Has data' : 'NULL';
        echo "<tr>";
        echo "<td>{$app['id']}</td>";
        echo "<td>{$app['application_number']}</td>";
        echo "<td>" . ($utm_display === 'Has data' ? '✅ Has UTM data' : '❌ NULL') . "</td>";
        echo "<td>{$app['gclid']}</td>";
        echo "<td>{$app['fbclid']}</td>";
        echo "<td>{$app['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No applications found in wp_edubot_applications table</p>";
}

echo "<hr>";

// Step 4: Show how to test
echo "<h2>Step 4: How to Test</h2>";
echo "<ol>";
echo "<li>Clear browser cache/cookies</li>";
echo "<li>Visit with UTM params: <a href='http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025' target='_blank'>Test URL with UTM</a></li>";
echo "<li>Fill out and submit the form</li>";
echo "<li>Check Admin Panel → Applications and click 'View' on your application</li>";
echo "<li>Look for 'Marketing Tracking (UTM)' section</li>";
echo "</ol>";

echo "<hr>";

// Step 5: Show admin link
echo "<h2>Step 5: Admin Links</h2>";
echo "<p><a href='" . admin_url('admin.php?page=edubot-applications') . "' target='_blank'>📊 Go to Applications</a></p>";

?>
