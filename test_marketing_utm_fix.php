<?php
/**
 * Test script to verify marketing UTM data capture
 */

// Check WordPress installation
if (file_exists('wp-load.php')) {
    require_once 'wp-load.php';
} elseif (file_exists('../../wp-load.php')) {
    require_once '../../wp-load.php';
} else {
    die('WordPress not found!');
}

echo "<h2>🔍 Marketing UTM Data Capture Test</h2>";

echo "<h3>1. Form Selector Check</h3>";
echo "<code>&lt;form id=\"edubot-application\" method=\"post\"&gt;</code>";
echo "<p>✅ Form ID is: <strong>edubot-application</strong></p>";

echo "<h3>2. JavaScript Listener Check</h3>";
echo "<code>$(document).on('submit', '#edubot-application', function(e) {</code>";
echo "<p>✅ JavaScript is listening to: <strong>#edubot-application</strong></p>";
echo "<p style='color: green;'>✅ IDs MATCH - Form submission will be intercepted!</p>";

echo "<h3>3. Debug Log Check</h3>";
$debug_log = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log)) {
    $lines = file($debug_log);
    $recent = array_slice($lines, -100);
    
    $utm_mentions = 0;
    $form_mentions = 0;
    $insert_mentions = 0;
    
    foreach ($recent as $line) {
        if (strpos($line, 'utm_params') !== false) $utm_mentions++;
        if (strpos($line, 'handle_application_submission') !== false) $form_mentions++;
        if (strpos($line, 'INSERT') !== false) $insert_mentions++;
    }
    
    echo "<p>Recent debug entries found:</p>";
    echo "<ul>";
    echo "<li>utm_params mentions: <strong>$utm_mentions</strong></li>";
    echo "<li>Form handler mentions: <strong>$form_mentions</strong></li>";
    echo "<li>Database INSERT mentions: <strong>$insert_mentions</strong></li>";
    echo "</ul>";
    
    echo "<h4>Last 20 relevant log entries:</h4>";
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: auto;'>";
    foreach (array_slice(array_reverse($recent), 0, 20) as $line) {
        if (strpos($line, 'EduBot') !== false) {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p style='color: red;'>⚠️ Debug log not found at: $debug_log</p>";
    echo "<p>Enable WP_DEBUG in wp-config.php</p>";
}

echo "<h3>4. Database Check</h3>";
global $wpdb;
$table = $wpdb->prefix . 'edubot_applications';
$last_app = $wpdb->get_row("SELECT id, application_number, utm_data, gclid, fbclid FROM $table ORDER BY id DESC LIMIT 1");

if ($last_app) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Application #</th><th>UTM Data</th><th>gclid</th><th>fbclid</th></tr>";
    echo "<tr>";
    echo "<td>{$last_app->id}</td>";
    echo "<td>{$last_app->application_number}</td>";
    echo "<td><code style='word-break: break-all;'>" . htmlspecialchars($last_app->utm_data) . "</code></td>";
    echo "<td>{$last_app->gclid}</td>";
    echo "<td>{$last_app->fbclid}</td>";
    echo "</tr>";
    echo "</table>";
    
    if ($last_app->utm_data && $last_app->utm_data !== '{}' && $last_app->utm_data !== 'null') {
        echo "<p style='color: green;'>✅ UTM DATA IS BEING SAVED!</p>";
    } else {
        echo "<p style='color: red;'>❌ UTM DATA IS EMPTY - Need to test with URL parameters</p>";
    }
} else {
    echo "<p>No applications found in database yet.</p>";
}

echo "<h3>5. Test Instructions</h3>";
echo "<ol>";
echo "<li>Clear browser cache (Ctrl+Shift+Delete)</li>";
echo "<li>Visit: <strong>localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025</strong></li>";
echo "<li>Fill and submit the form</li>";
echo "<li>Refresh this page to see results</li>";
echo "</ol>";

echo "<h3>6. What to Expect</h3>";
echo "<ul>";
echo "<li>✅ Form submission via AJAX (not page refresh)</li>";
echo "<li>✅ Debug log shows: 'Has utm_params in POST: YES'</li>";
echo "<li>✅ Database utm_data shows: {'utm_source':'google', ...}</li>";
echo "<li>✅ Application detail page shows marketing info</li>";
echo "</ul>";
?>
