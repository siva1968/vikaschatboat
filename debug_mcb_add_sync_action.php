<?php
/**
 * Debug MCB Admin Function
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== Debug add_sync_action() ===\n\n";

// Load MCB Admin class
if (!class_exists('EduBot_MCB_Admin')) {
    require_once(plugin_dir_path(__FILE__) . 'includes/class-edubot-mcb-admin.php');
}

// Load MCB Service
if (!class_exists('EduBot_MCB_Service')) {
    require_once(plugin_dir_path(__FILE__) . 'includes/class-edubot-mcb-service.php');
}

echo "Step 1: Check if EduBot_MCB_Service exists\n";
var_dump(class_exists('EduBot_MCB_Service'));

echo "\nStep 2: Get MCB Service instance\n";
$mcb_service = EduBot_MCB_Service::get_instance();
var_dump($mcb_service !== null);

echo "\nStep 3: Check is_sync_enabled()\n";
$is_enabled = $mcb_service->is_sync_enabled();
var_dump($is_enabled);

echo "\nStep 4: Examine mcb_settings property\n";
if (method_exists($mcb_service, 'get_settings')) {
    $settings = $mcb_service->get_settings();
    echo "Settings: \n";
    var_dump($settings);
}

echo "\nStep 5: Trace through filter call\n";
echo "Current function behavior - will return early if not enabled:\n";

$test_application = array(
    'enquiry_id' => 123,
    'mcb_sync_status' => 'pending'
);
$test_actions = array('view' => 'View', 'edit' => 'Edit');

// Manually call to debug
if (!class_exists('EduBot_MCB_Service')) {
    echo "Return: Service not available\n";
} else {
    $mcb_service_check = EduBot_MCB_Service::get_instance();
    if (!$mcb_service_check->is_sync_enabled()) {
        echo "Return: Service disabled, actions returned as-is\n";
        echo "Actions count: " . count($test_actions) . "\n";
    } else {
        echo "Service enabled, would add button\n";
    }
}
?>
