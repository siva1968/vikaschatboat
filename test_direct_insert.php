<?php
require '/xampp/htdocs/demo/wp-load.php';

global $wpdb;

$table = $wpdb->prefix . 'edubot_applications';

echo "Testing direct wpdb->insert with utm_data...\n\n";

$data = array(
    'site_id' => 1,
    'application_number' => 'TEST-DIRECT-' . time(),
    'student_data' => json_encode(array('name' => 'Test')),
    'conversation_log' => json_encode(array('test' => 'data')),
    'status' => 'pending',
    'source' => 'test',
    'ip_address' => '127.0.0.1',
    'user_agent' => 'Test',
    'utm_data' => json_encode(array('utm_source' => 'google', 'utm_medium' => 'cpc')),
    'gclid' => 'test_gclid',
    'fbclid' => 'test_fbclid',
    'click_id_data' => json_encode(array('test' => 'click_data'))
);

$formats = array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');

echo "Data array:\n";
foreach ($data as $key => $value) {
    echo "  {$key}: " . substr($value, 0, 50) . "\n";
}

echo "\nInserting...\n";
$result = $wpdb->insert($table, $data, $formats);

if ($result === false) {
    echo "❌ Error: " . $wpdb->last_error . "\n";
} else {
    echo "✅ Insert successful. ID: " . $wpdb->insert_id . "\n";
    
    // Verify
    $id = $wpdb->insert_id;
    $saved = $wpdb->get_row($wpdb->prepare(
        "SELECT utm_data, gclid, fbclid, click_id_data FROM {$table} WHERE id = %d",
        $id
    ), ARRAY_A);
    
    echo "\nSaved data:\n";
    echo "  utm_data: '" . $saved['utm_data'] . "'\n";
    echo "  gclid: '" . $saved['gclid'] . "'\n";
    echo "  fbclid: '" . $saved['fbclid'] . "'\n";
    echo "  click_id_data: '" . $saved['click_id_data'] . "'\n";
}

?>
