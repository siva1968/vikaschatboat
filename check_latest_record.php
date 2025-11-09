<?php
require '/xampp/htdocs/demo/wp-load.php';

global $wpdb;

// Get the most recent record
$app_table = $wpdb->prefix . 'edubot_applications';
$latest = $wpdb->get_row("SELECT * FROM {$app_table} ORDER BY id DESC LIMIT 1", ARRAY_A);

echo "Latest record ID: " . $latest['id'] . "\n";
echo "Application Number: " . $latest['application_number'] . "\n";
echo "Source: " . $latest['source'] . "\n";
echo "Status: " . $latest['status'] . "\n";

// Check specific fields
echo "\n=== Marketing Tracking Fields ===\n";
echo "utm_data: " . ($latest['utm_data'] ? 'HAS DATA' : 'EMPTY') . "\n";
echo "gclid: " . ($latest['gclid'] ? $latest['gclid'] : 'EMPTY') . "\n";
echo "fbclid: " . ($latest['fbclid'] ? $latest['fbclid'] : 'EMPTY') . "\n";
echo "click_id_data: " . ($latest['click_id_data'] ? 'HAS DATA' : 'EMPTY') . "\n";

// If utm_data has data, show it
if ($latest['utm_data']) {
    echo "\nutm_data content:\n";
    echo $latest['utm_data'] . "\n";
}

// Check ALL columns
echo "\n=== All Columns ===\n";
foreach ($latest as $key => $value) {
    if (!is_null($value) && $value !== '') {
        echo "{$key}: " . substr($value, 0, 50) . "\n";
    }
}

?>
