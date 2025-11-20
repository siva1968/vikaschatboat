<?php
/**
 * Fix WhatsApp Template Language Setting
 */

require_once dirname(__FILE__) . '/wp-load.php';

echo "<h1>🔧 Fix WhatsApp Template Language Setting</h1>";

// Check current setting
$current_language = get_option('edubot_whatsapp_template_language', '');
echo "<h2>Current Status</h2>";
echo "<p>Template Language: <strong>" . (!empty($current_language) ? $current_language : 'NOT SET') . "</strong></p>";

// Set language to en (English)
echo "<h2>Setting Template Language to 'en' (English)</h2>";
$updated = update_option('edubot_whatsapp_template_language', 'en');

if ($updated || $current_language === 'en') {
    echo "<p style='color: green;'>✅ Successfully set Template language to: <strong>en</strong></p>";
} else {
    echo "<p style='color: orange;'>ℹ️ Language already set to en (no change needed)</p>";
}

// Verify it was set
$verified_language = get_option('edubot_whatsapp_template_language', '');
echo "<h2>Verification</h2>";
echo "<p>Template Language after fix: <strong>" . $verified_language . "</strong></p>";

if ($verified_language === 'en') {
    echo "<p style='color: green; font-size: 18px;'><strong>✅ Template Language Successfully Set!</strong></p>";
} else {
    echo "<p style='color: red;'><strong>❌ Failed to set Template language</strong></p>";
}

// Show all WhatsApp settings
echo "<h2>All WhatsApp Settings - Final Status</h2>";
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

$all_set = true;
foreach ($whatsapp_settings as $option_name => $label) {
    $value = get_option($option_name, '');
    $status = '';
    
    if ($option_name === 'edubot_whatsapp_token') {
        $status = !empty($value) ? '✅ Configured (' . strlen($value) . ' chars)' : '❌ NOT SET';
        if (empty($value)) $all_set = false;
    } elseif ($option_name === 'edubot_whatsapp_use_templates' || 
              $option_name === 'edubot_whatsapp_notifications' ||
              $option_name === 'edubot_school_whatsapp_notifications') {
        $status = $value ? '✅ ENABLED' : '❌ DISABLED';
        if (!$value) $all_set = false;
    } else {
        $status = !empty($value) ? '✅ Set' : '❌ NOT SET';
        if (empty($value)) $all_set = false;
    }
    
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>$label</strong></td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>$status</td>";
    echo "</tr>";
}

echo "</table>";

if ($all_set) {
    echo "<h2 style='color: green;'>🎉 ALL WHATSAPP SETTINGS CONFIGURED!</h2>";
    echo "<p style='font-size: 16px; color: green;'><strong>✅ WhatsApp is fully ready to send messages!</strong></p>";
} else {
    echo "<h2 style='color: orange;'>⚠️ Some settings still need attention</h2>";
}

echo "<h2>✅ Next Steps</h2>";
echo "<ol>";
echo "<li>Go to chatbot: <a href='http://localhost/demo/' target='_blank'>http://localhost/demo/</a></li>";
echo "<li>Submit a test enquiry with your WhatsApp-enabled phone number</li>";
echo "<li>You should receive a WhatsApp message within seconds</li>";
echo "<li>Check status: <a href='http://localhost/demo/whatsapp_verification.php'>WhatsApp Verification</a></li>";
echo "</ol>";

?>
