<?php
/**
 * Add Missing MCB Columns to Applications Table
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== Adding Missing MCB Columns ===\n\n";

global $wpdb;

$queries = array(
    "ALTER TABLE {$wpdb->prefix}edubot_applications ADD COLUMN IF NOT EXISTS enquiry_id INT AFTER id",
    "ALTER TABLE {$wpdb->prefix}edubot_applications ADD COLUMN IF NOT EXISTS mcb_sync_status VARCHAR(50) DEFAULT 'pending' AFTER click_id_data",
    "ALTER TABLE {$wpdb->prefix}edubot_applications ADD COLUMN IF NOT EXISTS mcb_enquiry_id VARCHAR(100) AFTER mcb_sync_status",
    "ALTER TABLE {$wpdb->prefix}edubot_applications ADD INDEX IF NOT EXISTS idx_enquiry_id (enquiry_id)",
    "ALTER TABLE {$wpdb->prefix}edubot_applications ADD INDEX IF NOT EXISTS idx_mcb_sync_status (mcb_sync_status)"
);

foreach ($queries as $query) {
    echo "Running: $query\n";
    $result = $wpdb->query($query);
    if ($result !== false) {
        echo "✅ Success\n\n";
    } else {
        echo "❌ Error: " . $wpdb->last_error . "\n\n";
    }
}

echo "=== Verification ===\n\n";

// Verify columns exist
$result = $wpdb->get_results(
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$wpdb->prefix}edubot_applications'"
);

$columns = array();
foreach ($result as $row) {
    $columns[$row->COLUMN_NAME] = true;
}

echo "MCB columns verification:\n";
$mcb_cols = array('enquiry_id', 'mcb_sync_status', 'mcb_enquiry_id');
$all_ok = true;
foreach ($mcb_cols as $col) {
    if (isset($columns[$col])) {
        echo "✅ $col: EXISTS\n";
    } else {
        echo "❌ $col: STILL MISSING\n";
        $all_ok = false;
    }
}

if ($all_ok) {
    echo "\n✅ ALL COLUMNS ADDED SUCCESSFULLY!\n";
    echo "The MCB sync button should now work.\n";
} else {
    echo "\n⚠️  Some columns still missing\n";
}
?>
