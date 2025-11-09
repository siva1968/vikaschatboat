<?php
/**
 * Verify Version 1.5.1 Deployment
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Version 1.5.1 Deployment Verification                         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// 1. Check version
echo "1️⃣ VERSION CHECK\n";
$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/edubot-pro/edubot-pro.php');
echo "   Version: " . $plugin_data['Version'] . "\n";
if ($plugin_data['Version'] === '1.5.1') {
    echo "   ✅ PASS: Version is 1.5.1\n";
} else {
    echo "   ❌ FAIL: Version is " . $plugin_data['Version'] . "\n";
}

// 2. Check MCB Service
echo "\n2️⃣ MCB SERVICE CHECK\n";
if (class_exists('EduBot_MCB_Service')) {
    echo "   ✅ PASS: EduBot_MCB_Service class exists\n";
    $service = EduBot_MCB_Service::get_instance();
    echo "   ✅ PASS: Service instance created\n";
    echo "   └─ is_sync_enabled(): " . ($service->is_sync_enabled() ? 'TRUE' : 'FALSE') . "\n";
} else {
    echo "   ❌ FAIL: EduBot_MCB_Service class not found\n";
}

// 3. Check MCB Admin
echo "\n3️⃣ MCB ADMIN CHECK\n";
if (class_exists('EduBot_MCB_Admin')) {
    echo "   ✅ PASS: EduBot_MCB_Admin class exists\n";
    if (method_exists('EduBot_MCB_Admin', 'add_sync_action')) {
        echo "   ✅ PASS: add_sync_action() method exists\n";
    }
} else {
    echo "   ❌ FAIL: EduBot_MCB_Admin class not found\n";
}

// 4. Check MCB Settings
echo "\n4️⃣ MCB SETTINGS CHECK\n";
$settings = get_option('edubot_mcb_settings');
if (is_array($settings)) {
    echo "   ✅ PASS: MCB settings found\n";
    echo "   ├─ enabled: " . ($settings['enabled'] ? 'YES (1)' : 'NO (0)') . "\n";
    echo "   ├─ sync_enabled: " . ($settings['sync_enabled'] ? 'YES (1)' : 'NO (0)') . "\n";
    echo "   └─ auto_sync: " . ($settings['auto_sync'] ? 'YES (1)' : 'NO (0)') . "\n";
    
    if ($settings['enabled'] && $settings['sync_enabled']) {
        echo "   ✅ PASS: Both enabled and sync_enabled are ON\n";
        echo "   ✅ Button SHOULD DISPLAY\n";
    } else {
        echo "   ℹ️  INFO: Button will not display (MCB not fully enabled)\n";
    }
} else {
    echo "   ❌ FAIL: MCB settings not found\n";
}

// 5. Test the function
echo "\n5️⃣ FUNCTION TEST\n";
if (class_exists('EduBot_MCB_Admin')) {
    $test_app = array('enquiry_id' => 999, 'mcb_sync_status' => 'pending');
    $test_actions = array('view' => 'View', 'delete' => 'Delete');
    
    $result = EduBot_MCB_Admin::add_sync_action($test_actions, $test_app);
    
    if (isset($result['mcb_sync'])) {
        echo "   ✅ PASS: MCB button added to actions\n";
        echo "   └─ Actions count: " . count($result) . " (was: " . count($test_actions) . ")\n";
    } else {
        echo "   ℹ️  INFO: MCB button not added (MCB disabled in settings)\n";
    }
}

// 6. Check admin_init hook
echo "\n6️⃣ WORDPRESS HOOKS CHECK\n";
echo "   (These are checked during WordPress page load, not CLI)\n";
echo "   ℹ️  admin_init: MCB_Admin::init() will be called\n";
echo "   ℹ️  edubot_applications_row_actions: Filter will apply button\n";

// 7. Summary
echo "\n" . str_repeat("━", 64) . "\n";
echo "📋 DEPLOYMENT STATUS\n";
echo str_repeat("━", 64) . "\n\n";

$version_ok = $plugin_data['Version'] === '1.5.1';
$service_ok = class_exists('EduBot_MCB_Service');
$admin_ok = class_exists('EduBot_MCB_Admin');
$settings_ok = is_array($settings);

if ($version_ok && $service_ok && $admin_ok && $settings_ok) {
    echo "✅ ALL CHECKS PASSED\n\n";
    echo "The plugin is ready for testing!\n";
    echo "→ Refresh your browser (Ctrl+F5)\n";
    echo "→ Go to EduBot Pro > Applications\n";
    echo "→ Look for 'Sync MCB' button in Actions column\n";
} else {
    echo "⚠️  SOME CHECKS FAILED\n\n";
    if (!$version_ok) echo "  ❌ Version not updated\n";
    if (!$service_ok) echo "  ❌ MCB Service class not found\n";
    if (!$admin_ok) echo "  ❌ MCB Admin class not found\n";
    if (!$settings_ok) echo "  ❌ MCB Settings not found\n";
}

echo "\n" . str_repeat("━", 64) . "\n";
?>
