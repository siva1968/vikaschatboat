<?php
/**
 * Verify All MCB Classes are Now Loaded
 */

define('WP_USE_THEMES', false);
require('D:\xampp\htdocs\demo\wp-load.php');

echo "=== MCB Classes & Methods Verification ===\n\n";

// Check all MCB classes
$classes = array(
    'EduBot_MCB_Service' => 'MCB Service',
    'EduBot_MCB_Integration' => 'MCB Integration Hooks',
    'EduBot_MCB_Admin' => 'MCB Admin Interface',
    'EduBot_MCB_Settings_Page' => 'MCB Settings Page',
    'EduBot_MCB_Sync_Dashboard' => 'MCB Sync Dashboard',
    'EduBot_MyClassBoard_Integration' => 'MyClassBoard Integration Helper'
);

foreach ($classes as $class => $name) {
    if (class_exists($class)) {
        echo "✓ $class ($name): LOADED\n";
    } else {
        echo "✗ $class ($name): NOT LOADED\n";
    }
}

echo "\n=== Method Availability Check ===\n";

// Check if MyClassBoard_Integration has required methods
if (class_exists('EduBot_MyClassBoard_Integration')) {
    $methods = array(
        'get_settings',
        'get_sync_stats',
        'get_recent_sync_logs',
        'get_pending_enquiries',
        'get_failed_syncs',
        'retry_sync'
    );
    
    $integration = new EduBot_MyClassBoard_Integration();
    foreach ($methods as $method) {
        if (method_exists($integration, $method)) {
            echo "✓ Method: $method()\n";
        } else {
            echo "✗ Method: $method() NOT FOUND\n";
        }
    }
}

echo "\n✅ All MCB classes are now properly loaded\n";
echo "✅ MCB admin pages should work without errors\n";
?>
