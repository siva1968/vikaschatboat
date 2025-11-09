<?php
/**
 * Check MCB Settings Details
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== Current MCB Settings ===\n\n";

// Check option directly from database
$mcb_settings = get_option('edubot_mcb_settings');

if (is_array($mcb_settings)) {
    echo "MCB Settings (from database):\n";
    echo "- enabled: " . ($mcb_settings['enabled'] ? 'YES' : 'NO') . "\n";
    echo "- sync_enabled: " . ($mcb_settings['sync_enabled'] ? 'YES' : 'NO') . "\n";
    echo "- auto_sync: " . ($mcb_settings['auto_sync'] ? 'YES' : 'NO') . "\n";
    echo "- api_key: " . (empty($mcb_settings['api_key']) ? 'NOT SET' : 'SET') . "\n";
    echo "- access_token: " . (empty($mcb_settings['access_token']) ? 'NOT SET' : 'SET') . "\n";
} else {
    echo "MCB Settings not found or not an array\n";
}

echo "\n=== To Enable MCB Integration ===\n";
echo "1. Go to WordPress Admin\n";
echo "2. EduBot Pro > MyClassBoard Settings\n";
echo "3. Check 'Enable MCB Integration' checkbox\n";
echo "4. Also check 'Enable MCB Sync' checkbox\n";
echo "5. Click 'Save Settings'\n";
?>
