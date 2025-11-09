<?php
require '/xampp/htdocs/demo/wp-load.php';

global $wpdb;

echo "<h1>Checking Database Record</h1>";

$app_table = $wpdb->prefix . 'edubot_applications';

// Check if record 29 exists
$record = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$app_table} WHERE id = %d",
    29
), ARRAY_A);

if ($record) {
    echo "<p>✅ Record found:</p>";
    echo "<pre>";
    print_r($record);
    echo "</pre>";
    
    echo "<h2>UTM Data:</h2>";
    $utm = json_decode($record['utm_data']);
    echo "<pre>";
    print_r($utm);
    echo "</pre>";
} else {
    echo "<p>❌ Record not found</p>";
    echo "<p>Let me check all records in wp_edubot_applications:</p>";
    
    $all_records = $wpdb->get_results("SELECT id, application_number, created_at FROM {$app_table} ORDER BY id DESC LIMIT 5");
    echo "<pre>";
    print_r($all_records);
    echo "</pre>";
}

?>
