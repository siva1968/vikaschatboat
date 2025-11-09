<?php
// Clear any previous errors  
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '/xampp/htdocs/demo/wp-load.php';

global $wpdb;

$db_manager = new EduBot_Database_Manager();

// Create test data
$test_utm_data = array(
    'utm_source' => 'google',
    'utm_medium' => 'cpc',
    'utm_campaign' => 'test_campaign'
);

$application_data = array(
    'application_number' => 'DEBUG-' . time(),
    'student_data' => array(
        'student_name' => 'Debug Test',
        'email' => 'debug@test.com',
        'phone' => '9999999999',
        'parent_name' => 'Parent',
        'date_of_birth' => '2010-05-15',
        'grade' => 'X',
        'address' => 'Test'
    ),
    'conversation_log' => array('source' => 'test'),
    'status' => 'pending',
    'source' => 'test',
    'utm_data' => json_encode($test_utm_data),
    'gclid' => 'test_gclid_123',
    'fbclid' => 'test_fbclid_456',
    'click_id_data' => json_encode(array('test' => 'data'))
);

echo "<p>About to call save_application with utm_data...</p>";
echo "<p>utm_data in input: " . (isset($application_data['utm_data']) ? 'YES' : 'NO') . "</p>";
echo "<p>utm_data value: " . substr($application_data['utm_data'], 0, 50) . "</p>";

// Call save_application
$result = $db_manager->save_application($application_data);

echo "<p>Result: $result</p>";

if (!is_wp_error($result)) {
    // Check what was actually saved
    $app_table = $wpdb->prefix . 'edubot_applications';
    $saved = $wpdb->get_row($wpdb->prepare(
        "SELECT utm_data, gclid, fbclid, click_id_data FROM {$app_table} WHERE id = %d",
        $result
    ), ARRAY_A);
    
    echo "<p><strong>Saved utm_data:</strong> '" . ($saved['utm_data'] ?? 'NULL') . "'</p>";
    echo "<p><strong>Saved gclid:</strong> '" . ($saved['gclid'] ?? 'NULL') . "'</p>";
    echo "<p><strong>Saved fbclid:</strong> '" . ($saved['fbclid'] ?? 'NULL') . "'</p>";
    echo "<p><strong>Saved click_id_data:</strong> '" . substr($saved['click_id_data'] ?? 'NULL', 0, 50) . "'</p>";
}

?>
