<?php
/**
 * Test MCB Button Conditional Logic
 * Verifies that MCB sync button only appears when integration is enabled
 */

// WordPress setup
require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== MCB Button Conditional Logic Test ===\n\n";

// Check if MCB Service exists
if (class_exists('EduBot_MCB_Service')) {
    echo "✓ EduBot_MCB_Service class found\n";
    
    $mcb_service = EduBot_MCB_Service::get_instance();
    $is_enabled = $mcb_service->is_sync_enabled();
    
    echo "✓ MCB Service instance created\n";
    echo "✓ is_sync_enabled() returns: " . ($is_enabled ? 'TRUE' : 'FALSE') . "\n";
    
    // Get the settings to see details
    if (method_exists($mcb_service, 'get_settings')) {
        $settings = $mcb_service->get_settings();
        echo "\n--- Current MCB Settings ---\n";
        echo "enabled: " . ($settings['enabled'] ? 'YES' : 'NO') . "\n";
        echo "sync_enabled: " . ($settings['sync_enabled'] ? 'YES' : 'NO') . "\n";
        echo "auto_sync: " . ($settings['auto_sync'] ? 'YES' : 'NO') . "\n";
    }
    
    echo "\n--- Button Logic Result ---\n";
    if ($is_enabled) {
        echo "✓ MCB Sync button WILL BE DISPLAYED\n";
    } else {
        echo "✗ MCB Sync button WILL BE HIDDEN\n";
    }
    
} else {
    echo "✗ EduBot_MCB_Service class NOT found\n";
}

echo "\n=== Test Complete ===\n";
?>
