<?php
/**
 * Check what fields are in applications
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== Check Application Fields ===\n\n";

global $wpdb;

// Get one application
$app = $wpdb->get_row(
    "SELECT * FROM {$wpdb->prefix}edubot_applications LIMIT 1",
    ARRAY_A
);

if ($app) {
    echo "Application #1 fields:\n";
    foreach ($app as $key => $value) {
        $val_display = is_null($value) ? 'NULL' : (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value);
        echo "  - $key: $val_display\n";
    }
    
    echo "\n\nKey field checks:\n";
    echo "  - Has 'id': " . (isset($app['id']) ? 'YES' : 'NO') . "\n";
    echo "  - Has 'enquiry_id': " . (isset($app['enquiry_id']) ? 'YES' : 'NO') . "\n";
    echo "  - Has 'mcb_sync_status': " . (isset($app['mcb_sync_status']) ? 'YES' : 'NO') . "\n";
    echo "  - Has 'mcb_enquiry_id': " . (isset($app['mcb_enquiry_id']) ? 'YES' : 'NO') . "\n";
    
    echo "\n\nMCB-related fields:\n";
    foreach ($app as $key => $value) {
        if (stripos($key, 'mcb') !== false) {
            echo "  - $key: $value\n";
        }
    }
} else {
    echo "No applications found in database\n";
}
?>
