<?php
require '/xampp/htdocs/demo/wp-load.php';

global $wpdb;

$app_table = $wpdb->prefix . 'edubot_applications';

// Get record 31
$record = $wpdb->get_row($wpdb->prepare(
    "SELECT id, application_number, utm_data, gclid, fbclid, click_id_data FROM {$app_table} WHERE id = %d",
    31
), ARRAY_A);

echo "Record ID 31:\n";
echo "utm_data: '" . ($record['utm_data'] ?? 'NULL') . "'\n";
echo "gclid: '" . ($record['gclid'] ?? 'NULL') . "'\n";
echo "fbclid: '" . ($record['fbclid'] ?? 'NULL') . "'\n";
echo "click_id_data: '" . ($record['click_id_data'] ?? 'NULL') . "'\n\n";

// Get the latest record
$latest = $wpdb->get_row("SELECT id, application_number, utm_data, gclid, fbclid, click_id_data FROM {$app_table} ORDER BY id DESC LIMIT 1", ARRAY_A);

echo "Latest record (ID {$latest['id']}):\n";
echo "utm_data: '" . ($latest['utm_data'] ?? 'NULL') . "'\n";
echo "gclid: '" . ($latest['gclid'] ?? 'NULL') . "'\n";
echo "fbclid: '" . ($latest['fbclid'] ?? 'NULL') . "'\n";
echo "click_id_data: '" . ($latest['click_id_data'] ?? 'NULL') . "'\n";

?>
