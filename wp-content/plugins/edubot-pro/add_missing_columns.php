<?php
require '/xampp/htdocs/demo/wp-load.php';

global $wpdb;

$app_table = $wpdb->prefix . 'edubot_applications';

echo "Adding missing columns to wp_edubot_applications...\n\n";

$columns_to_add = array(
    'gclid' => 'varchar(100)',
    'fbclid' => 'varchar(100)',
    'click_id_data' => 'longtext'
);

foreach ($columns_to_add as $column => $definition) {
    // Check if column exists
    $column_exists = $wpdb->get_results($wpdb->prepare(
        "SHOW COLUMNS FROM {$app_table} LIKE %s",
        $column
    ));
    
    if (empty($column_exists)) {
        echo "Adding column: {$column}...\n";
        $result = $wpdb->query("ALTER TABLE {$app_table} ADD COLUMN {$column} {$definition}");
        if ($result === false) {
            echo "❌ Error: " . $wpdb->last_error . "\n";
        } else {
            echo "✅ Column {$column} added successfully\n";
        }
    } else {
        echo "⏭️  Column {$column} already exists\n";
    }
}

echo "\n✅ Done! Now checking columns...\n";

$columns = $wpdb->get_results("DESCRIBE {$app_table}");
echo "\nColumns in wp_edubot_applications:\n";
foreach ($columns as $col) {
    if (in_array($col->Field, ['utm_data', 'gclid', 'fbclid', 'click_id_data'])) {
        echo "✅ " . $col->Field . " (" . $col->Type . ")\n";
    }
}

?>
