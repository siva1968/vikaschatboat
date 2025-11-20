<?php
/**
 * Deactivate and Reactivate Plugin to Clear Caches
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== Clearing Plugin Caches ===\n\n";

// Deactivate plugin
echo "1. Deactivating plugin...\n";
deactivate_plugins('edubot-pro/edubot-pro.php');
echo "   ✅ Plugin deactivated\n\n";

// Clear all WordPress caches
echo "2. Clearing WordPress caches...\n";
wp_cache_flush();
echo "   ✅ Object cache cleared\n";

// Clear transients
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%transient%'");
echo "   ✅ Transients cleared\n\n";

// Reactivate plugin
echo "3. Reactivating plugin...\n";
activate_plugin('edubot-pro/edubot-pro.php');
echo "   ✅ Plugin reactivated\n\n";

// Verify version
$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/edubot-pro/edubot-pro.php');
echo "4. Current version: " . $plugin_data['Version'] . "\n";
echo "   ✅ Version updated\n\n";

echo "=== Cache Clear Complete ===\n";
echo "Now refresh your WordPress admin to see the changes!\n";
?>
