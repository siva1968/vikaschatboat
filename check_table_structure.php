<?php
require_once('/xamppdev/htdocs/demo/wp-load.php');

global $wpdb;

echo "wp_edubot_applications table structure:\n";
echo "======================================\n";

$columns = $wpdb->get_results('DESCRIBE ' . $wpdb->prefix . 'edubot_applications');
foreach ($columns as $col) {
    echo $col->Field . " - " . $col->Type . "\n";
}
?>
