<?php
require '/xampp/htdocs/demo/wp-load.php';

global $wpdb;

$app_table = $wpdb->prefix . 'edubot_applications';

echo "Columns in wp_edubot_applications:\n";

$cols = $wpdb->get_results("DESCRIBE {$app_table}");

$utm_cols = [];
foreach ($cols as $col) {
    if (in_array($col->Field, ['utm_data', 'gclid', 'fbclid', 'click_id_data'])) {
        $utm_cols[] = $col->Field;
        echo "✅ " . $col->Field . " (" . $col->Type . ")\n";
    }
}

if (count($utm_cols) < 4) {
    echo "\n❌ PROBLEM: Not all utm columns exist!\n";
    echo "Found: " . count($utm_cols) . "/4 columns\n";
} else {
    echo "\n✅ All UTM columns exist\n";
}

?>
