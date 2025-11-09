<?php
/**
 * Test Database Storage of UTM Data
 */

require '/xampp/htdocs/demo/wp-load.php';

echo "<h1>🧪 Testing UTM Data Storage in Database</h1>";
echo "<hr>";

global $wpdb;
$db_manager = new EduBot_Database_Manager();

// Step 1: Prepare test application data WITH utm_data
echo "<h2>Step 1: Preparing Test Application Data</h2>";

$test_utm_data = array(
    'utm_source' => 'google',
    'utm_medium' => 'cpc',
    'utm_campaign' => 'admissions_2025',
    'utm_term' => 'school admission',
    'utm_content' => 'banner_1',
    'gclid' => 'test_gclid_xyz123',
    'fbclid' => 'test_fbclid_abc789'
);

$test_click_id_data = array(
    'gclid' => 'test_gclid_xyz123',
    'gclid_captured_at' => current_time('mysql'),
    'fbclid' => 'test_fbclid_abc789',
    'fbclid_captured_at' => current_time('mysql')
);

$test_student_data = array(
    'student_name' => 'Test Student UTM',
    'date_of_birth' => '2010-05-15',
    'grade' => 'X',
    'educational_board' => 'CBSE',
    'academic_year' => '2024-2025',
    'gender' => 'male',
    'parent_name' => 'Test Parent',
    'email' => 'test_utm_' . time() . '@example.com',
    'phone' => '9876543210',
    'address' => 'Test Address, Test City',
    'special_requirements' => '',
    'marketing_consent' => 1,
    'submitted_at' => current_time('mysql'),
    'submission_ip' => $_SERVER['REMOTE_ADDR']
);

$application_data = array(
    'application_number' => 'TEST-UTM-' . date('YmdHis'),
    'student_data' => $test_student_data,
    'conversation_log' => array(
        'source' => 'test_form',
        'timestamp' => current_time('mysql'),
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => 'Test Script',
        'form_version' => '1.0'
    ),
    'status' => 'pending',
    'source' => 'google',
    'utm_data' => json_encode($test_utm_data),  // Use json_encode instead of wp_json_encode
    'gclid' => 'test_gclid_xyz123',
    'fbclid' => 'test_fbclid_abc789',
    'click_id_data' => json_encode($test_click_id_data)  // Use json_encode instead of wp_json_encode
);

echo "<p>Created test application data with:</p>";
echo "<ul>";
echo "<li>UTM Source: " . $test_utm_data['utm_source'] . "</li>";
echo "<li>UTM Campaign: " . $test_utm_data['utm_campaign'] . "</li>";
echo "<li>gclid: " . $test_utm_data['gclid'] . "</li>";
echo "<li>fbclid: " . $test_utm_data['fbclid'] . "</li>";
echo "</ul>";

echo "<hr>";

// Step 2: Save using database manager
echo "<h2>Step 2: Saving Application via Database Manager</h2>";

$result = $db_manager->save_application($application_data);

if (is_wp_error($result)) {
    echo "<p>❌ Error saving application: " . $result->get_error_message() . "</p>";
} else {
    echo "<p>✅ Successfully saved application with ID: {$result}</p>";
    
    // Verify the data was saved correctly
    echo "<hr>";
    echo "<h2>Step 3: Verifying Saved Data</h2>";
    
    $app_table = $wpdb->prefix . 'edubot_applications';
    $saved_app = $wpdb->get_row($wpdb->prepare(
        "SELECT id, application_number, utm_data, gclid, fbclid, click_id_data FROM {$app_table} WHERE id = %d",
        $result
    ), ARRAY_A);
    
    if ($saved_app) {
        echo "<p>✅ Application found in database</p>";
        
        $saved_utm = json_decode($saved_app['utm_data'], true);
        echo "<p><strong>Saved UTM Data:</strong></p>";
        echo "<pre>" . json_encode($saved_utm, JSON_PRETTY_PRINT) . "</pre>";
        
        echo "<p><strong>Saved Click IDs:</strong></p>";
        $saved_click_ids = json_decode($saved_app['click_id_data'], true);
        echo "<pre>" . json_encode($saved_click_ids, JSON_PRETTY_PRINT) . "</pre>";
        
        echo "<p><strong>Saved gclid:</strong> " . ($saved_app['gclid'] ? $saved_app['gclid'] : 'NULL') . "</p>";
        echo "<p><strong>Saved fbclid:</strong> " . ($saved_app['fbclid'] ? $saved_app['fbclid'] : 'NULL') . "</p>";
    } else {
        echo "<p>❌ Application not found after save!</p>";
    }
}

echo "<hr>";

// Step 4: Test retrieval via get_application
echo "<h2>Step 4: Testing Retrieval via get_application()</h2>";

if (!is_wp_error($result)) {
    $retrieved = $db_manager->get_application('app_' . $result);
    
    if ($retrieved) {
        echo "<p>✅ Retrieved application successfully</p>";
        
        $retrieved_utm = json_decode($retrieved['utm_data'], true);
        echo "<p><strong>Retrieved UTM Data:</strong></p>";
        echo "<pre>" . json_encode($retrieved_utm, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p>❌ Failed to retrieve application</p>";
    }
}

echo "<hr>";

// Step 5: Show admin link
echo "<h2>Step 5: View in Admin Panel</h2>";
echo "<p><a href='" . admin_url('admin.php?page=edubot-applications') . "' target='_blank'>📊 Go to Applications List</a></p>";
echo "<p>Click 'View' on the application you just created to see if Marketing Tracking section appears</p>";

?>
