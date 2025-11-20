<?php
require '/xampp/htdocs/demo/wp-load.php';
global $wpdb;

echo "Available EduBot Tables:\n";
echo "=========================\n\n";

// Check wp_edubot_applications
$app_table = $wpdb->prefix . 'edubot_applications';
$app_exists = $wpdb->get_var("SHOW TABLES LIKE '{$app_table}'") === $app_table;
echo "1. wp_edubot_applications: " . ($app_exists ? "✅ EXISTS" : "❌ MISSING") . "\n";

// Check wp_edubot_enquiries
$enq_table = $wpdb->prefix . 'edubot_enquiries';
$enq_exists = $wpdb->get_var("SHOW TABLES LIKE '{$enq_table}'") === $enq_table;
echo "2. wp_edubot_enquiries: " . ($enq_exists ? "✅ EXISTS" : "❌ MISSING") . "\n";

// Count records
if ($app_exists) {
    $app_count = $wpdb->get_var("SELECT COUNT(*) FROM {$app_table}");
    echo "   - Records: {$app_count}\n";
}

if ($enq_exists) {
    $enq_count = $wpdb->get_var("SELECT COUNT(*) FROM {$enq_table}");
    echo "   - Records: {$enq_count}\n";
}

echo "\n3. Comparing columns:\n";

if ($app_exists) {
    echo "\nwp_edubot_applications columns:\n";
    $cols = $wpdb->get_results("DESCRIBE {$app_table}");
    foreach ($cols as $col) {
        echo "  - {$col->Field} ({$col->Type})\n";
    }
}

if ($enq_exists) {
    echo "\nwp_edubot_enquiries columns:\n";
    $cols = $wpdb->get_results("DESCRIBE {$enq_table}");
    foreach ($cols as $col) {
        if (in_array($col->Field, ['utm_data', 'gclid', 'fbclid', 'click_id_data', 'source', 'id', 'student_name', 'email'])) {
            echo "  - {$col->Field} ({$col->Type})\n";
        }
    }
}

echo "\n4. Problem Analysis:\n";
echo "Form submissions use: save_application() → wp_edubot_applications table\n";
echo "But marketing params are in: wp_edubot_enquiries table\n";
echo "The application detail modal queries: wp_edubot_enquiries!\n";
?>
