<?php
require '/xampp/htdocs\demo/wp-load.php';

global $wpdb;

// Simulate what save_application does
$table = $wpdb->prefix . 'edubot_applications';
$site_id = 1;

$application_data = array(
    'application_number' => 'SIMTEST-' . time(),
    'student_data' => array('name' => 'Test'),
    'conversation_log' => array('log' => 'test'),
    'status' => 'pending',
    'source' => 'test',
    'utm_data' => '{"utm_source":"google"}',
    'gclid' => 'test_gclid',
    'fbclid' => 'test_fbclid',
    'click_id_data' => '{"test":"data"}'
);

// Simulate validate_application_data (just returns the input for our test)
$validated_data = $application_data;

// Build data array like save_application does
$data = array(
    'site_id' => $site_id,
    'application_number' => sanitize_text_field($validated_data['application_number']),
    'student_data' => wp_json_encode($validated_data['student_data']),
    'conversation_log' => wp_json_encode($validated_data['conversation_log']),
    'status' => sanitize_text_field($validated_data['status']),
    'source' => sanitize_text_field($validated_data['source']),
    'ip_address' => '127.0.0.1',
    'user_agent' => 'Test',
    'utm_data' => isset($validated_data['utm_data']) ? $validated_data['utm_data'] : null,
    'gclid' => isset($validated_data['gclid']) ? sanitize_text_field($validated_data['gclid']) : null,
    'fbclid' => isset($validated_data['fbclid']) ? sanitize_text_field($validated_data['fbclid']) : null,
    'click_id_data' => isset($validated_data['click_id_data']) ? $validated_data['click_id_data'] : null
);

echo "Data array before insert:\n";
foreach ($data as $key => $value) {
    echo "  {$key}: '" . substr($value ?? 'NULL', 0, 50) . "'\n";
}

$formats = array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');

echo "\nInserting...\n";
$result = $wpdb->insert($table, $data, $formats);

if ($result === false) {
    echo "❌ Error: " . $wpdb->last_error . "\n";
} else {
    echo "✅ Success ID: " . $wpdb->insert_id . "\n";
    
    // Verify
    $saved = $wpdb->get_row($wpdb->prepare(
        "SELECT utm_data, gclid, fbclid, click_id_data FROM {$table} WHERE id = %d",
        $wpdb->insert_id
    ), ARRAY_A);
    
    echo "\nSaved values:\n";
    echo "  utm_data: '" . $saved['utm_data'] . "'\n";
    echo "  gclid: '" . $saved['gclid'] . "'\n";
    echo "  fbclid: '" . $saved['fbclid'] . "'\n";
    echo "  click_id_data: '" . $saved['click_id_data'] . "'\n";
}

?>
