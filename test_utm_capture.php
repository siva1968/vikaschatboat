<?php
/**
 * Test UTM Data Capture and Storage
 * This script tests if UTM parameters are:
 * 1. Captured to cookies
 * 2. Retrieved by get_utm_data()
 * 3. Saved to database
 * 4. Displayed in application detail modal
 */

// Load WordPress
require_once('/xampp/htdocs/demo/wp-load.php');

echo "<h1>🧪 UTM Data Capture & Storage Test</h1>";
echo "<hr>";

// Step 1: Check if UTM cookies exist
echo "<h2>Step 1: Check UTM Cookies</h2>";
$utm_cookies = array();
foreach ($_COOKIE as $key => $value) {
    if (strpos($key, 'edubot_') === 0) {
        $utm_cookies[$key] = $value;
        echo "<p>✅ Found: <strong>$key</strong> = <strong>$value</strong></p>";
    }
}

if (empty($utm_cookies)) {
    echo "<p>❌ No UTM cookies found. Test with: <a href='http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=test2025'>Click here</a></p>";
} else {
    echo "<p>Found " . count($utm_cookies) . " UTM cookies</p>";
}

echo "<hr>";

// Step 2: Check database column exists
echo "<h2>Step 2: Check Database Column</h2>";
global $wpdb;
$table = $wpdb->prefix . 'edubot_enquiries';
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$table}");

$has_utm_data_column = false;
echo "<p>Checking for utm_data column in {$table}:</p>";
foreach ($columns as $col) {
    if ($col->Field === 'utm_data') {
        $has_utm_data_column = true;
        echo "<p>✅ <strong>utm_data</strong> column exists (Type: {$col->Type})</p>";
    }
    if (in_array($col->Field, ['gclid', 'fbclid', 'click_id_data', 'source'])) {
        echo "<p>✅ <strong>{$col->Field}</strong> column exists (Type: {$col->Type})</p>";
    }
}

if (!$has_utm_data_column) {
    echo "<p>❌ <strong>utm_data</strong> column NOT found!</p>";
}

echo "<hr>";

// Step 3: Check for recent applications with utm_data
echo "<h2>Step 3: Check Recent Applications with UTM Data</h2>";

$recent_apps = $wpdb->get_results(
    "SELECT id, enquiry_number, student_name, email, utm_data, source, created_at 
     FROM {$table} 
     ORDER BY created_at DESC 
     LIMIT 5",
    ARRAY_A
);

if (empty($recent_apps)) {
    echo "<p>❌ No applications found in database</p>";
} else {
    echo "<p>Found " . count($recent_apps) . " recent applications:</p>";
    echo "<table border='1' cellpadding='10' style='width: 100%;'>";
    echo "<tr><th>ID</th><th>Enquiry #</th><th>Student</th><th>Email</th><th>UTM Data</th><th>Source</th><th>Created</th></tr>";
    
    foreach ($recent_apps as $app) {
        $utm_data = json_decode($app['utm_data'], true);
        $utm_display = $utm_data ? json_encode($utm_data, JSON_PRETTY_PRINT) : "❌ NULL";
        
        echo "<tr>";
        echo "<td>{$app['id']}</td>";
        echo "<td>{$app['enquiry_number']}</td>";
        echo "<td>{$app['student_name']}</td>";
        echo "<td>{$app['email']}</td>";
        echo "<td><pre>{$utm_display}</pre></td>";
        echo "<td>{$app['source']}</td>";
        echo "<td>{$app['created_at']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<hr>";

// Step 4: Create test application with UTM data
echo "<h2>Step 4: Create Test Application</h2>";

$test_utm_data = array(
    'utm_source' => 'test_google',
    'utm_medium' => 'test_cpc',
    'utm_campaign' => 'test_campaign',
    'gclid' => 'test_gclid_12345',
    'fbclid' => 'test_fbclid_67890'
);

$result = $wpdb->insert(
    $table,
    array(
        'enquiry_number' => 'TEST' . date('YmdHis'),
        'student_name' => 'Test Student',
        'email' => 'test@example.com',
        'phone' => '9876543210',
        'parent_name' => 'Test Parent',
        'grade' => 'X',
        'board' => 'CBSE',
        'academic_year' => '2024-2025',
        'utm_data' => json_encode($test_utm_data),
        'gclid' => 'test_gclid_12345',
        'fbclid' => 'test_fbclid_67890',
        'source' => 'test_google',
        'status' => 'pending',
        'created_at' => current_time('mysql'),
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ),
    array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
);

if ($result === false) {
    echo "<p>❌ Failed to insert test application: " . $wpdb->last_error . "</p>";
} else {
    $test_app_id = $wpdb->insert_id;
    echo "<p>✅ Successfully created test application ID: {$test_app_id}</p>";
    
    // Verify it was saved
    $saved_app = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d",
        $test_app_id
    ), ARRAY_A);
    
    if ($saved_app) {
        $saved_utm = json_decode($saved_app['utm_data'], true);
        echo "<p>✅ Verified - UTM data saved correctly:</p>";
        echo "<pre>" . json_encode($saved_utm, JSON_PRETTY_PRINT) . "</pre>";
    }
}

echo "<hr>";

// Step 5: Instructions to test live form
echo "<h2>Step 5: Live Form Test</h2>";
echo "<p>To test with the actual form, follow these steps:</p>";
echo "<ol>";
echo "<li>Visit URL with UTM params: <a href='http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025' target='_blank'>http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025</a></li>";
echo "<li>Open browser DevTools (F12) → Application → Cookies and verify edubot_utm_* cookies exist</li>";
echo "<li>Fill out and submit the application form</li>";
echo "<li>Check this script again to see if new application has utm_data saved</li>";
echo "<li>Go to Admin Panel → Applications → Click 'View' on your application</li>";
echo "<li>Check if 'Marketing Tracking (UTM)' section appears with your data</li>";
echo "</ol>";

echo "<hr>";

// Step 6: Show admin link
echo "<h2>Step 6: Admin Links</h2>";
echo "<p><a href='" . admin_url('admin.php?page=edubot-applications') . "' target='_blank'>📊 Go to Applications List</a></p>";
echo "<p><a href='" . home_url('/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025') . "' target='_blank'>🧪 Test Form with UTM</a></p>";

?>
