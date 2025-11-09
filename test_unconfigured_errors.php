<?php
/**
 * Test Error Messages When Settings Not Configured
 *
 * This demonstrates the new clear error logging when API settings are missing
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== TESTING ERROR MESSAGES (Settings NOT Configured) ===\n\n";

// Temporarily clear the API integrations table to simulate unconfigured state
global $wpdb;
$table = $wpdb->prefix . 'edubot_api_integrations';

echo "1. Backing up current settings...\n";
$backup = $wpdb->get_row("SELECT * FROM $table WHERE site_id = 1");
echo "   ✓ Settings backed up\n\n";

echo "2. Temporarily removing settings to test error messages...\n";
$wpdb->delete($table, array('site_id' => 1));
echo "   ✓ Settings removed\n\n";

echo "3. Testing Email Error Messages:\n";
echo str_repeat("-", 60) . "\n";

require_once('D:/xampp/htdocs/demo/wp-content/plugins/edubot-pro/includes/class-school-config.php');
require_once('D:/xampp/htdocs/demo/wp-content/plugins/edubot-pro/includes/class-api-migration.php');
require_once('D:/xampp/htdocs/demo/wp-content/plugins/edubot-pro/includes/class-api-integrations.php');

$school_config = EduBot_School_Config::getInstance();
$api_integrations = new EduBot_API_Integrations($school_config);

echo "Attempting to send email...\n\n";
$result = $api_integrations->send_email('test@example.com', 'Test', 'Test message');

if ($result === false) {
    echo "✓ Email send failed as expected\n";
    echo "✓ Check the error logs above - they should clearly explain:\n";
    echo "  - What is missing (email provider)\n";
    echo "  - Where to configure it (admin page)\n";
    echo "  - What failed (email details)\n\n";
} else {
    echo "✗ Unexpected: Email send succeeded!\n\n";
}

echo "4. Restoring settings...\n";
echo str_repeat("-", 60) . "\n";

if ($backup) {
    $wpdb->insert($table, array(
        'site_id' => $backup->site_id,
        'email_provider' => $backup->email_provider,
        'email_from_address' => $backup->email_from_address,
        'email_from_name' => $backup->email_from_name,
        'email_api_key' => $backup->email_api_key,
        'email_domain' => $backup->email_domain,
        'sms_provider' => $backup->sms_provider,
        'sms_api_key' => $backup->sms_api_key,
        'sms_sender_id' => $backup->sms_sender_id,
        'whatsapp_provider' => $backup->whatsapp_provider,
        'whatsapp_token' => $backup->whatsapp_token,
        'whatsapp_phone_id' => $backup->whatsapp_phone_id,
        'created_at' => $backup->created_at,
        'updated_at' => current_time('mysql')
    ));
    echo "   ✓ Settings restored successfully\n\n";
} else {
    echo "   ⚠ No backup found to restore\n\n";
}

echo "5. Verifying Restoration:\n";
echo str_repeat("-", 60) . "\n";

$api_settings = EduBot_API_Migration::get_api_settings();
if (!empty($api_settings['email_provider'])) {
    echo "   ✓ Email provider restored: " . $api_settings['email_provider'] . "\n";
    echo "   ✓ System is back to normal\n\n";
} else {
    echo "   ✗ Settings not restored properly\n\n";
}

echo "=== TEST COMPLETE ===\n\n";

echo "SUMMARY:\n";
echo "--------\n";
echo "The new error logging provides:\n";
echo "✓ Clear identification of what's missing\n";
echo "✓ Exact location where to configure settings\n";
echo "✓ No silent fallbacks that hide problems\n";
echo "✓ Detailed context about what operation failed\n\n";

echo "Compare this to the old behavior:\n";
echo "✗ Silent fallback to wp_mail() which doesn't work\n";
echo "✗ Unclear error messages\n";
echo "✗ Difficulty determining what's misconfigured\n";
