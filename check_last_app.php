<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '/xampp/htdocs/demo/wp-load.php';

global $wpdb;

$app_table = $wpdb->prefix . 'edubot_applications';

// Check last record
$last = $wpdb->get_row("SELECT * FROM {$app_table} ORDER BY id DESC LIMIT 1", ARRAY_A);

if ($last) {
    echo "Last application ID: " . $last['id'] . "\n";
    echo "Application Number: " . $last['application_number'] . "\n";
    echo "Source: " . $last['source'] . "\n";
    echo "utm_data: '" . $last['utm_data'] . "'\n";
    echo "utm_data length: " . strlen($last['utm_data']) . "\n";
    echo "utm_data NULL: " . (is_null($last['utm_data']) ? 'YES' : 'NO') . "\n";
    echo "utm_data empty: " . (empty($last['utm_data']) ? 'YES' : 'NO') . "\n";
    echo "gclid: '" . $last['gclid'] . "'\n";
    echo "fbclid: '" . $last['fbclid'] . "'\n";
    echo "click_id_data: '" . $last['click_id_data'] . "'\n";
} else {
    echo "No records found\n";
}

?>
