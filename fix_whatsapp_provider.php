<?php
/**
 * Fix WhatsApp Provider Setting
 */

require_once dirname(__FILE__) . '/wp-load.php';

echo "<h1>🔧 Fix WhatsApp Provider Setting</h1>";

// Check current setting
$current_provider = get_option('edubot_whatsapp_provider', '');
echo "<h2>Current Status</h2>";
echo "<p>WhatsApp Provider: <strong>" . (!empty($current_provider) ? $current_provider : 'NOT SET') . "</strong></p>";

// Set provider to meta
echo "<h2>Setting WhatsApp Provider to 'meta'</h2>";
$updated = update_option('edubot_whatsapp_provider', 'meta');

if ($updated || $current_provider === 'meta') {
    echo "<p style='color: green;'>✅ Successfully set WhatsApp provider to: <strong>meta</strong></p>";
} else {
    echo "<p style='color: orange;'>ℹ️ Provider already set to meta (no change needed)</p>";
}

// Verify it was set
$verified_provider = get_option('edubot_whatsapp_provider', '');
echo "<h2>Verification</h2>";
echo "<p>WhatsApp Provider after fix: <strong>" . $verified_provider . "</strong></p>";

if ($verified_provider === 'meta') {
    echo "<p style='color: green; font-size: 18px;'><strong>✅ WhatsApp Provider Successfully Set!</strong></p>";
} else {
    echo "<p style='color: red;'><strong>❌ Failed to set WhatsApp provider</strong></p>";
}

// Show all WhatsApp settings
echo "<h2>All WhatsApp Settings</h2>";
$whatsapp_settings = array(
    'edubot_whatsapp_provider' => 'Provider',
    'edubot_whatsapp_token' => 'Access Token',
    'edubot_whatsapp_phone_id' => 'Phone Number ID',
    'edubot_whatsapp_use_templates' => 'Use Templates',
    'edubot_whatsapp_template_namespace' => 'Template Namespace',
    'edubot_whatsapp_template_name' => 'Template Name',
    'edubot_whatsapp_template_language' => 'Template Language',
    'edubot_whatsapp_notifications' => 'Parent WhatsApp Enabled',
    'edubot_school_whatsapp_notifications' => 'School WhatsApp Enabled',
);

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Setting</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Status</th>";
echo "</tr>";

foreach ($whatsapp_settings as $option_name => $label) {
    $value = get_option($option_name, '');
    $status = '';
    
    if ($option_name === 'edubot_whatsapp_token') {
        $status = !empty($value) ? '✅ Configured (' . strlen($value) . ' chars)' : '❌ NOT SET';
    } elseif ($option_name === 'edubot_whatsapp_use_templates' || 
              $option_name === 'edubot_whatsapp_notifications' ||
              $option_name === 'edubot_school_whatsapp_notifications') {
        $status = $value ? '✅ ENABLED' : '❌ DISABLED';
    } else {
        $status = !empty($value) ? '✅ Set' : '❌ NOT SET';
    }
    
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>$label</strong></td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>$status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>✅ Next Steps</h2>";
echo "<ol>";
echo "<li>Go to chatbot: <a href='http://localhost/demo/' target='_blank'>http://localhost/demo/</a></li>";
echo "<li>Submit a test enquiry with your phone number</li>";
echo "<li>You should receive a WhatsApp message</li>";
echo "<li>Check verification: <a href='http://localhost/demo/whatsapp_verification.php'>WhatsApp Verification</a></li>";
echo "</ol>";

?>
