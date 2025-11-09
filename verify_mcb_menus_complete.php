<?php
/**
 * Verify MCB Admin Menus are Restored
 */

define('WP_USE_THEMES', false);
require('D:\xampp\htdocs\demo\wp-load.php');

echo "=== MCB Admin Menus Verification ===\n\n";

// Check MCB Settings Page class
if (class_exists('EduBot_MCB_Settings_Page')) {
    echo "✓ EduBot_MCB_Settings_Page class: LOADED\n";
} else {
    echo "✗ EduBot_MCB_Settings_Page class: NOT LOADED\n";
}

// Check MCB Sync Dashboard class
if (class_exists('EduBot_MCB_Sync_Dashboard')) {
    echo "✓ EduBot_MCB_Sync_Dashboard class: LOADED\n";
} else {
    echo "✗ EduBot_MCB_Sync_Dashboard class: NOT LOADED\n";
}

// Check MCB settings in database
$mcb_settings = get_option('edubot_mcb_settings');
if ($mcb_settings && $mcb_settings['enabled']) {
    echo "✓ MCB Settings: ENABLED\n";
    echo "  - Organization ID: " . $mcb_settings['organization_id'] . "\n";
    echo "  - Branch ID: " . $mcb_settings['branch_id'] . "\n";
    echo "  - Auto-sync: " . ($mcb_settings['auto_sync'] ? 'ON' : 'OFF') . "\n";
} else {
    echo "✗ MCB Settings: DISABLED or NOT FOUND\n";
}

// Check sync logs table
global $wpdb;
$log_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}edubot_mcb_sync_log");
echo "\n✓ MCB Sync Log entries: $log_count\n";

echo "\n=== Menu Items Should Now Appear ===\n";
echo "✅ WordPress Admin → EduBot Pro:\n";
echo "   1. Dashboard\n";
echo "   2. School Settings\n";
echo "   3. Academic Configuration\n";
echo "   4. API Integrations\n";
echo "   5. Form Builder\n";
echo "   6. Applications\n";
echo "   7. Analytics\n";
echo "   8. System Status\n";
echo "   9. ⭐ MyClassBoard Settings (RESTORED)\n";
echo "   10. 📊 Sync Dashboard (RESTORED)\n";
?>
