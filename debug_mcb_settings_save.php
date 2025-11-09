<?php
/**
 * Debug MCB Settings Save
 * Check what's being saved to database
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== Debug MCB Settings Save ===\n\n";

// Get current settings from database
$settings = get_option('edubot_mcb_settings');

echo "Raw database option (edubot_mcb_settings):\n";
var_dump($settings);

echo "\n\n=== Field by Field ===\n";
if (is_array($settings)) {
    foreach ($settings as $key => $value) {
        echo "$key: " . var_export($value, true) . "\n";
    }
}

echo "\n\n=== Check if setting exists ===\n";
echo "Option exists: " . (get_option('edubot_mcb_settings') ? 'YES' : 'NO') . "\n";

echo "\n\n=== All WordPress options matching 'mcb' ===\n";
global $wpdb;
$results = $wpdb->get_results(
    "SELECT option_name, option_value FROM {$wpdb->options} 
     WHERE option_name LIKE '%mcb%' 
     ORDER BY option_name"
);

foreach ($results as $row) {
    $value = maybe_unserialize($row->option_value);
    echo $row->option_name . " => ";
    if (is_array($value)) {
        echo "Array(" . count($value) . " items)\n";
        if (is_array($value) && isset($value['enabled'])) {
            echo "  └─ [enabled] = " . $value['enabled'] . "\n";
        }
    } else {
        echo var_export($value, true) . "\n";
    }
}

echo "\n\n=== Last 10 Options (recent) ===\n";
$recent = $wpdb->get_results(
    "SELECT option_name FROM {$wpdb->options} 
     ORDER BY option_id DESC LIMIT 10"
);

foreach ($recent as $row) {
    echo $row->option_name . "\n";
}
?>
