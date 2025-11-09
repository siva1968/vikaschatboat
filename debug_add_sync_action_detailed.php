<?php
/**
 * Detailed Debug: add_sync_action() Test
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== Detailed Debug: add_sync_action() ===\n\n";

// Load classes
if (!class_exists('EduBot_MCB_Admin')) {
    require_once(plugin_dir_path(__FILE__) . 'includes/class-edubot-mcb-admin.php');
}

if (!class_exists('EduBot_MCB_Service')) {
    require_once(plugin_dir_path(__FILE__) . 'includes/class-edubot-mcb-service.php');
}

echo "Initial state:\n";
$mcb_service = EduBot_MCB_Service::get_instance();
echo "- is_sync_enabled(): " . ($mcb_service->is_sync_enabled() ? 'TRUE' : 'FALSE') . "\n";

$test_application = array(
    'enquiry_id' => 123,
    'mcb_sync_status' => 'pending',
    'name' => 'Test Application'
);

$initial_actions = array(
    'view' => '<a href="#">View</a>',
    'edit' => '<a href="#">Edit</a>',
    'delete' => '<a href="#">Delete</a>'
);

echo "\nBefore add_sync_action():\n";
echo "- Initial actions count: " . count($initial_actions) . "\n";
echo "- Keys: " . implode(', ', array_keys($initial_actions)) . "\n";

// Call the function
$result_actions = EduBot_MCB_Admin::add_sync_action($initial_actions, $test_application);

echo "\nAfter add_sync_action():\n";
echo "- Result actions count: " . count($result_actions) . "\n";
echo "- Keys: " . implode(', ', array_keys($result_actions)) . "\n";

echo "\nAnalysis:\n";
if (isset($result_actions['mcb_sync'])) {
    echo "❌ Button was added (mcb_sync key present)\n";
} else {
    echo "✅ Button NOT added (mcb_sync key NOT present) - CORRECT BEHAVIOR\n";
}

echo "\nActions are same object? " . ($result_actions === $initial_actions ? 'YES' : 'NO') . "\n";
echo "Actions are identical? " . ($result_actions == $initial_actions ? 'YES' : 'NO') . "\n";

if ($result_actions !== $initial_actions) {
    echo "Difference detected:\n";
    foreach ($result_actions as $key => $value) {
        if (!isset($initial_actions[$key])) {
            echo "  + NEW: $key\n";
        }
    }
}
?>
