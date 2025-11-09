<?php
require '/xampp/htdocs/demo/wp-load.php';
global $wpdb;
$tables = $wpdb->get_results("SHOW TABLES LIKE '%edubot%'");
foreach ($tables as $t) {
    $key = 'Tables_in_' . DB_NAME;
    echo $t->$key . "\n";
}
?>
