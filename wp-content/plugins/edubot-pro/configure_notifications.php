<?php
/**
 * Configure Notification Settings
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== Configuring Notification Settings ===\n\n";

// Get current config
$school_config = EduBot_School_Config::getInstance();
$config = $school_config->get_config();

echo "Current notification settings:\n";
echo "- Email Provider: " . ($config['notification_settings']['email_provider'] ?? 'NOT SET') . "\n";
echo "- WhatsApp Provider: " . ($config['notification_settings']['whatsapp_provider'] ?? 'NOT SET') . "\n";
echo "- Admin Email: " . ($config['notification_settings']['admin_email'] ?? 'NOT SET') . "\n";
echo "- Admin Phone: " . ($config['notification_settings']['admin_phone'] ?? 'NOT SET') . "\n\n";

// Update notification settings
echo "Configuring default notification settings...\n";

global $wpdb;
$table = $wpdb->prefix . 'edubot_school_configs';

// Get current config
$current_config = $wpdb->get_var($wpdb->prepare(
    "SELECT config_data FROM $table WHERE site_id = %d",
    1
));

if ($current_config) {
    $config_array = json_decode($current_config, true);

    // Update notification settings
    if (!isset($config_array['notification_settings'])) {
        $config_array['notification_settings'] = array();
    }

    // Set default email provider to WordPress mail (always available)
    if (empty($config_array['notification_settings']['email_provider'])) {
        $config_array['notification_settings']['email_provider'] = 'wordpress';
        echo "✓ Set email provider to 'wordpress'\n";
    }

    // Enable email notifications
    $config_array['notification_settings']['email_enabled'] = true;
    echo "✓ Enabled email notifications\n";

    // Enable admin notifications
    $config_array['notification_settings']['admin_notifications'] = true;
    echo "✓ Enabled admin notifications\n";

    // Set admin email if not set
    if (empty($config_array['notification_settings']['admin_email'])) {
        $admin_email = get_option('admin_email', 'admissions@vikasconcept.com');
        $config_array['notification_settings']['admin_email'] = $admin_email;
        echo "✓ Set admin email to: $admin_email\n";
    }

    // Set admin phone if not set
    if (empty($config_array['notification_settings']['admin_phone'])) {
        $config_array['notification_settings']['admin_phone'] = '+917702800800';
        echo "✓ Set admin phone to: +917702800800\n";
    }

    // Disable WhatsApp for now (needs API key)
    $config_array['notification_settings']['whatsapp_enabled'] = false;
    echo "✓ WhatsApp disabled (configure API key to enable)\n";

    // Update database
    $updated = $wpdb->update(
        $table,
        array('config_data' => wp_json_encode($config_array)),
        array('site_id' => 1),
        array('%s'),
        array('%d')
    );

    if ($updated !== false) {
        echo "\n✅ SUCCESS: Notification settings configured!\n\n";
    } else {
        echo "\n❌ FAILED: Could not update database\n";
        echo "Error: " . $wpdb->last_error . "\n\n";
    }

    // Clear cache
    wp_cache_delete('edubot_school_config_1', 'edubot');
    echo "✓ Cache cleared\n\n";

    // Test email
    echo "=== Testing Email ===\n";
    $admin_email = $config_array['notification_settings']['admin_email'];
    echo "Sending test email to: $admin_email\n";

    $subject = 'EduBot Test - Notification System Active';
    $message = "This is a test email from EduBot notification system.\n\n";
    $message .= "Time: " . date('Y-m-d H:i:s') . "\n";
    $message .= "If you received this, email notifications are working!\n";

    $result = wp_mail($admin_email, $subject, $message);

    if ($result) {
        echo "✅ Test email sent successfully!\n";
        echo "Check inbox: $admin_email\n";
    } else {
        echo "❌ Failed to send test email\n";
        echo "This might be a server mail configuration issue\n";
    }

} else {
    echo "❌ ERROR: No configuration found in database\n";
}

echo "\n=== Configuration Complete ===\n";
echo "\nNext Steps:\n";
echo "1. Check if test email was received\n";
echo "2. Submit a test enquiry through chatbot\n";
echo "3. Check for notification in admin email\n";
echo "\nTo configure WhatsApp:\n";
echo "- Add Interakt API key in admin settings\n";
echo "- Enable WhatsApp notifications\n";
