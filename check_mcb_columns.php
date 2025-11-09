<?php
/**
 * Check if MCB columns exist in applications table
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== Check MCB Columns in Applications Table ===\n\n";

global $wpdb;

// Get table structure
$result = $wpdb->get_results(
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$wpdb->prefix}edubot_applications'"
);

echo "All columns in wp_edubot_applications:\n";
$columns = array();
foreach ($result as $row) {
    echo "  - " . $row->COLUMN_NAME . "\n";
    $columns[$row->COLUMN_NAME] = true;
}

echo "\n\nMCB-related columns check:\n";
$mcb_cols = array('mcb_sync_status', 'mcb_enquiry_id', 'enquiry_id');
foreach ($mcb_cols as $col) {
    if (isset($columns[$col])) {
        echo "  ✅ $col: EXISTS\n";
    } else {
        echo "  ❌ $col: MISSING\n";
    }
}

echo "\n\nAdd these columns if missing (run these SQL queries):\n";
echo "ALTER TABLE {$wpdb->prefix}edubot_applications ADD COLUMN mcb_sync_status VARCHAR(50) DEFAULT 'pending';\n";
echo "ALTER TABLE {$wpdb->prefix}edubot_applications ADD COLUMN mcb_enquiry_id VARCHAR(100);\n";
echo "ALTER TABLE {$wpdb->prefix}edubot_applications ADD COLUMN enquiry_id INT;\n";
?>
