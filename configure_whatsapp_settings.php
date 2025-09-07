<?php
/**
 * Configure EduBot Pro WhatsApp Settings
 * Upload this to your WordPress site and run it to set correct configuration
 */

// Load WordPress
if (!defined('ABSPATH')) {
    $wp_load_paths = [
        '../../wp-load.php',
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../wp-load.php',
        './wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('❌ Cannot find WordPress. Please upload this file to your WordPress site root or plugin directory.');
    }
}

echo "<h1>🔧 EduBot Pro WhatsApp Configuration</h1>";

// Your working configuration
$correct_settings = [
    // Notification Settings
    'edubot_whatsapp_notifications' => 1, // ✅ Enable WhatsApp notifications
    'edubot_email_notifications' => 1,    // Keep email notifications enabled
    'edubot_school_notifications' => 1,   // Keep school notifications enabled
    
    // WhatsApp API Settings  
    'edubot_whatsapp_provider' => 'meta',
    'edubot_whatsapp_token' => 'EAASeCKYjY2sBPfLljPAnLtWsXwUzCzPZAd92PfUIqaScZAFjpM9fK3UhLzxxt4OhgzLYpRpWZAlmVjZCSpTV19FcJXRZALTtHlbtjCqNfp5BLdLmXZBzW90c4v4REIko62w6QguwNMWXN1qITGK9D1su8YeILdogvDPeJTOIjdBrC2VgnzKKOLWKOAOT2n2wZDZD',
    'edubot_whatsapp_phone_id' => '614525638411206',
    
    // Template Settings - KEY CONFIGURATION
    'edubot_whatsapp_template_type' => 'business_template', // ✅ Use Business API Template
    'edubot_whatsapp_template_name' => 'admission_confirmation', // ✅ Your approved template
    'edubot_whatsapp_template_language' => 'en',
    
    // School Settings
    'edubot_school_name' => 'Epistemo Vikas Leadership School'
];

echo "<h2>📋 Applying Correct Configuration</h2>";

$success_count = 0;
$total_count = count($correct_settings);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

foreach ($correct_settings as $option_name => $option_value) {
    $result = update_option($option_name, $option_value);
    $current_value = get_option($option_name);
    
    // Check if setting was successful
    if ($current_value == $option_value) {
        $status = "✅ Success";
        $success_count++;
        $row_color = "#d4edda";
    } else {
        $status = "❌ Failed";
        $row_color = "#f8d7da";
    }
    
    echo "<tr style='background-color: {$row_color};'>";
    echo "<td><strong>{$option_name}</strong></td>";
    echo "<td>" . esc_html($option_value) . "</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>📊 Configuration Summary</h2>";

if ($success_count === $total_count) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "<h3>🎉 Configuration Complete!</h3>";
    echo "<p>All {$success_count} settings have been configured correctly.</p>";
    echo "<p><strong>WhatsApp messages will now be sent automatically when enquiries are submitted!</strong></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>⚠️ Partial Configuration</h3>";
    echo "<p>Successfully configured: {$success_count}/{$total_count} settings</p>";
    echo "<p>Some settings may need manual configuration in the admin panel.</p>";
    echo "</div>";
}

echo "<h2>🔍 Verification</h2>";

// Verify current settings
$verification = [
    'WhatsApp Notifications Enabled' => get_option('edubot_whatsapp_notifications') ? '✅ Yes' : '❌ No',
    'Template Type' => get_option('edubot_whatsapp_template_type', 'not set'),
    'Template Name' => get_option('edubot_whatsapp_template_name', 'not set'),  
    'Template Language' => get_option('edubot_whatsapp_template_language', 'not set'),
    'WhatsApp Provider' => get_option('edubot_whatsapp_provider', 'not set'),
    'Access Token' => get_option('edubot_whatsapp_token') ? '✅ Set' : '❌ Not Set',
    'Phone Number ID' => get_option('edubot_whatsapp_phone_id', 'not set'),
    'School Name' => get_option('edubot_school_name', 'not set')
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Setting</th><th>Current Value</th></tr>";

foreach ($verification as $setting => $value) {
    echo "<tr>";
    echo "<td><strong>{$setting}</strong></td>";
    echo "<td>" . esc_html($value) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>🧪 Next Steps</h2>";

echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px;'>";
echo "<h3>Test Your Configuration:</h3>";
echo "<ol>";
echo "<li><strong>Admin Panel Check:</strong> Go to Admin > EduBot Pro > School Settings and verify 'WhatsApp Notifications' is checked</li>";
echo "<li><strong>Submit Test Enquiry:</strong> Use your chatbot to submit a new admission enquiry</li>";
echo "<li><strong>Check WhatsApp:</strong> You should receive the template message automatically</li>";
echo "<li><strong>Check Logs:</strong> Look for 'EduBot WhatsApp:' messages in WordPress error logs</li>";
echo "</ol>";
echo "</div>";

echo "<h2>🎯 Key Configuration Points</h2>";
echo "<ul>";
echo "<li>✅ <strong>Template Type:</strong> 'business_template' (uses your approved Meta template)</li>";
echo "<li>✅ <strong>Template Name:</strong> 'admission_confirmation' (matches your Meta Business template)</li>";
echo "<li>✅ <strong>Provider:</strong> 'meta' (Facebook/Meta WhatsApp Business API)</li>";
echo "<li>✅ <strong>Notifications:</strong> Enabled (checkbox checked)</li>";
echo "</ul>";

echo "<h2>❌ What NOT to Do</h2>";
echo "<ul>";
echo "<li>❌ Don't set template type to 'freeform' - this ignores your approved template</li>";
echo "<li>❌ Don't change the template name from 'admission_confirmation'</li>";
echo "<li>❌ Don't uncheck 'WhatsApp Notifications' checkbox</li>";
echo "</ul>";

echo "<hr>";
echo "<p><em>Configuration completed on: " . date('Y-m-d H:i:s') . "</em></p>";
echo "<p><strong>🎉 Your EduBot Pro is now ready to send WhatsApp messages automatically!</strong></p>";
?>
