<?php
require '/xampp/htdocs/demo/wp-load.php';

global $wpdb;

$app_table = $wpdb->prefix . 'edubot_applications';

foreach ([32, 34] as $id) {
    echo "=== Record ID: {$id} ===\n";
    $record = $wpdb->get_row($wpdb->prepare("SELECT id, application_number, utm_data, gclid, fbclid, click_id_data FROM {$app_table} WHERE id = %d", $id), ARRAY_A);
    
    if ($record) {
        echo "Application Number: " . $record['application_number'] . "\n";
        echo "utm_data: " . ($record['utm_data'] ? 'HAS DATA' : 'EMPTY') . "\n";
        echo "gclid: " . ($record['gclid'] ? $record['gclid'] : 'EMPTY') . "\n";
        echo "fbclid: " . ($record['fbclid'] ? $record['fbclid'] : 'EMPTY') . "\n";
        echo "click_id_data: " . ($record['click_id_data'] ? 'HAS DATA' : 'EMPTY') . "\n";
    } else {
        echo "Record not found\n";
    }
    echo "\n";
}

?>
