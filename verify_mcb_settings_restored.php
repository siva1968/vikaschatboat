<?php
/**
 * Verify MCB Settings Page is now accessible
 */

define('WP_USE_THEMES', false);
require('D:\xampp\htdocs\demo\wp-load.php');

echo "=== MCB Settings Page Verification ===\n\n";

// Check if class is loaded
if (class_exists('EduBot_MCB_Settings_Page')) {
    echo "✓ EduBot_MCB_Settings_Page class: LOADED\n";
} else {
    echo "✗ EduBot_MCB_Settings_Page class: NOT LOADED\n";
}

// Check MCB settings in options
$mcb_settings = get_option('edubot_mcb_settings');
if ($mcb_settings) {
    echo "✓ MCB Settings found in database\n";
    echo "  - Enabled: " . ($mcb_settings['enabled'] ? 'YES' : 'NO') . "\n";
    echo "  - Org ID: " . $mcb_settings['organization_id'] . "\n";
    echo "  - Branch ID: " . $mcb_settings['branch_id'] . "\n";
} else {
    echo "✗ MCB Settings not found\n";
}

// Check if plugin is active
if (is_plugin_active('edubot-pro/edubot-pro.php')) {
    echo "\n✓ EduBot Pro plugin: ACTIVE\n";
} else {
    echo "\n✗ EduBot Pro plugin: NOT ACTIVE\n";
}

echo "\n✅ MCB Settings menu should now appear in WordPress admin under EduBot Pro menu\n";
?>
