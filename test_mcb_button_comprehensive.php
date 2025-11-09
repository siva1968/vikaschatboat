<?php
/**
 * Comprehensive MCB Button Conditional Logic Test
 * Tests the add_sync_action() function with different MCB settings
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== Comprehensive MCB Button Logic Test ===\n\n";

// Load MCB Admin class
if (!class_exists('EduBot_MCB_Admin')) {
    require_once(plugin_dir_path(__FILE__) . 'includes/class-edubot-mcb-admin.php');
}

// Load MCB Service
if (!class_exists('EduBot_MCB_Service')) {
    require_once(plugin_dir_path(__FILE__) . 'includes/class-edubot-mcb-service.php');
}

// Test data - a sample application/enquiry
$test_application = array(
    'enquiry_id' => 123,
    'mcb_sync_status' => 'pending',
    'name' => 'Test Application'
);

$test_actions = array(
    'view' => '<a href="#">View</a>',
    'edit' => '<a href="#">Edit</a>',
    'delete' => '<a href="#">Delete</a>'
);

echo "--- Test Case 1: MCB Currently DISABLED ---\n";
$mcb_service = EduBot_MCB_Service::get_instance();
echo "MCB is_sync_enabled() returns: " . ($mcb_service->is_sync_enabled() ? 'TRUE' : 'FALSE') . "\n";

$result_actions = EduBot_MCB_Admin::add_sync_action($test_actions, $test_application);

if (isset($result_actions['mcb_sync'])) {
    echo "❌ FAIL: MCB sync button was added (should be hidden)\n";
} else {
    echo "✅ PASS: MCB sync button is hidden as expected\n";
}

echo "\n--- Current MCB Settings ---\n";
$settings = get_option('edubot_mcb_settings');
echo "enabled: " . ($settings['enabled'] ? 'YES' : 'NO') . "\n";
echo "sync_enabled: " . ($settings['sync_enabled'] ? 'YES' : 'NO') . "\n";

echo "\n--- Logic Explanation ---\n";
echo "Button visibility depends on is_sync_enabled():\n";
echo "- is_sync_enabled() returns: mcb_settings['sync_enabled'] && mcb_settings['enabled']\n";
echo "- Both 'sync_enabled' AND 'enabled' must be TRUE for button to show\n";
echo "- Currently: enabled=" . ($settings['enabled'] ? 'YES' : 'NO') . " && sync_enabled=" . ($settings['sync_enabled'] ? 'YES' : 'NO') . "\n";
echo "- Result: is_sync_enabled() = " . ($mcb_service->is_sync_enabled() ? 'TRUE' : 'FALSE') . "\n";

echo "\n=== Test Complete ===\n";
echo "\nTo enable button:\n";
echo "1. Go to EduBot Pro > MyClassBoard Settings\n";
echo "2. Enable 'Enable MCB Integration' (Main enable switch)\n";
echo "3. Also check 'Enable MCB Sync' is enabled\n";
echo "4. Save Settings\n";
echo "5. Button will appear on Applications list page\n";
?>
