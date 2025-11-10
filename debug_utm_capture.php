<?php
/**
 * Debug UTM Capture - What's being received and stored
 */

require_once dirname(__FILE__) . '/wp-load.php';

echo "<h1>🔍 Debug UTM Capture - Detailed Analysis</h1>";

echo "<h2>1. Current Request Parameters ($_GET)</h2>";
if (!empty($_GET)) {
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Parameter</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Value</th>";
    echo "</tr>";
    foreach ($_GET as $key => $value) {
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'><code>" . htmlspecialchars($key) . "</code></td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'><code>" . htmlspecialchars($value) . "</code></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: #999;'><strong>No GET parameters in current request</strong></p>";
}

echo "<h2>2. Session Data ($_SESSION)</h2>";
// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$utm_session_data = array();
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'edubot_') === 0) {
        $utm_session_data[$key] = $value;
    }
}

if (!empty($utm_session_data)) {
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Session Key</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Value</th>";
    echo "</tr>";
    foreach ($utm_session_data as $key => $value) {
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'><code>" . htmlspecialchars($key) . "</code></td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'><code>" . htmlspecialchars($value) . "</code></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: #999;'><strong>No EduBot session data found</strong></p>";
}

echo "<h2>3. What URL Should You Use?</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border: 1px solid #b3d9ff; border-radius: 5px;'>";
echo "<p><strong>Copy and paste one of these URLs:</strong></p>";

$test_urls = array(
    array(
        'name' => 'Google Ads',
        'url' => 'http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025'
    ),
    array(
        'name' => 'Facebook',
        'url' => 'http://localhost/demo/?utm_source=facebook&utm_medium=social&utm_campaign=fb_ads_nov'
    ),
    array(
        'name' => 'Email Campaign',
        'url' => 'http://localhost/demo/?utm_source=email&utm_medium=newsletter&utm_campaign=parent_outreach'
    )
);

foreach ($test_urls as $test) {
    echo "<p>";
    echo "<strong>" . $test['name'] . ":</strong><br>";
    echo "<code style='background: #f5f5f5; padding: 8px; display: block; word-break: break-all;'>" . htmlspecialchars($test['url']) . "</code>";
    echo "</p>";
}
echo "</div>";

echo "<h2>4. Step-by-Step Test</h2>";
echo "<ol>";
echo "<li>Copy the URL above (e.g., Google Ads URL)</li>";
echo "<li>Paste it into your browser address bar</li>";
echo "<li>Press Enter to navigate to that URL</li>";
echo "<li>You should see the chatbot with UTM parameters in the URL</li>";
echo "<li>Scroll down on this page to see if UTM is captured in the table above (item #2)</li>";
echo "<li>Submit an enquiry</li>";
echo "<li>Check the application details - Source should show 'google' (or the UTM source you used)</li>";
echo "</ol>";

echo "<h2>5. Troubleshooting</h2>";

if (empty($_GET)) {
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px;'>";
    echo "<p><strong>⚠️ You're not using a URL with UTM parameters!</strong></p>";
    echo "<p>Current URL: <code>" . htmlspecialchars($_SERVER['REQUEST_URI']) . "</code></p>";
    echo "<p>Please use one of the test URLs above that includes utm_source, utm_medium, etc.</p>";
    echo "</div>";
}

echo "<h2>6. Test Now</h2>";
echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
echo "<p><strong>Quick Action:</strong></p>";
echo "<ol>";
echo "<li><a href='http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025' target='_blank' class='button button-primary'>Test with Google Ads URL</a></li>";
echo "<li>Then come back to this page and refresh to see if UTM was captured</li>";
echo "</ol>";
echo "</div>";

echo "<h2>7. Recent Enquiries</h2>";
global $wpdb;
$recent = $wpdb->get_results("SELECT enquiry_number, source, utm_data, created_at FROM {$wpdb->prefix}edubot_enquiries ORDER BY created_at DESC LIMIT 5");

if ($recent) {
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Enquiry #</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Source</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>UTM Data</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Created</th>";
    echo "</tr>";
    
    foreach ($recent as $enquiry) {
        $utm_decoded = json_decode($enquiry->utm_data, true);
        $utm_display = $utm_decoded ? json_encode($utm_decoded) : '(none)';
        
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'><code>" . htmlspecialchars($enquiry->enquiry_number) . "</code></td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>" . htmlspecialchars($enquiry->source) . "</strong></td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'><code style='font-size: 11px;'>" . htmlspecialchars($utm_display) . "</code></td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . substr($enquiry->created_at, 0, 16) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: #999;'>No enquiries found</p>";
}

?>
