<?php
/**
 * Test Button with Real Application Data
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== Test MCB Button with Real Application Data ===\n\n";

global $wpdb;

// Get a real application
$app = $wpdb->get_row(
    "SELECT * FROM {$wpdb->prefix}edubot_applications LIMIT 1",
    ARRAY_A
);

if (!$app) {
    echo "❌ No applications found\n";
    exit;
}

echo "Testing with real application:\n";
echo "  - id: " . $app['id'] . "\n";
echo "  - application_number: " . $app['application_number'] . "\n";
echo "  - mcb_sync_status: " . ($app['mcb_sync_status'] ?? 'NULL') . "\n";
echo "  - mcb_enquiry_id: " . ($app['mcb_enquiry_id'] ?? 'NULL') . "\n";

if (class_exists('EduBot_MCB_Admin')) {
    $test_actions = array(
        'view' => '<a href="#">View</a>',
        'delete' => '<a href="#">Delete</a>'
    );
    
    $result = EduBot_MCB_Admin::add_sync_action($test_actions, $app);
    
    echo "\nButton test:\n";
    if (isset($result['mcb_sync'])) {
        echo "✅ MCB button ADDED\n";
        echo "   Button HTML: " . substr($result['mcb_sync'], 0, 80) . "...\n";
    } else {
        echo "❌ MCB button NOT added\n";
    }
    
    echo "\nFinal actions count: " . count($result) . " (View, Delete, MCB Sync)\n";
} else {
    echo "❌ EduBot_MCB_Admin class not found\n";
}
?>
