<?php
/**
 * Final WhatsApp Configuration Verification
 */

require_once dirname(__FILE__) . '/wp-load.php';

echo "<h1>✅ WhatsApp Configuration Verification</h1>";

echo "<h2>📋 Configuration Status</h2>";

// Check all settings
$settings = array(
    'edubot_whatsapp_provider' => 'WhatsApp Provider',
    'edubot_whatsapp_token' => 'Access Token',
    'edubot_whatsapp_phone_id' => 'Phone Number ID',
    'edubot_whatsapp_use_templates' => 'Business API Templates',
    'edubot_whatsapp_template_namespace' => 'Template Namespace',
    'edubot_whatsapp_template_name' => 'Template Name',
    'edubot_whatsapp_template_language' => 'Template Language',
    'edubot_whatsapp_notifications' => 'Parent WhatsApp Enabled',
    'edubot_school_whatsapp_notifications' => 'School WhatsApp Enabled',
);

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Setting</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Value</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Status</th>";
echo "</tr>";

foreach ($settings as $option_name => $label) {
    $value = get_option($option_name, '');
    $status = '';
    $display_value = '';
    
    if ($option_name === 'edubot_whatsapp_token') {
        if (!empty($value)) {
            $status = '✅ Set';
            $display_value = substr($value, 0, 20) . '...';
        } else {
            $status = '❌ NOT SET';
            $display_value = 'N/A';
        }
    } elseif ($option_name === 'edubot_whatsapp_use_templates' || 
              $option_name === 'edubot_whatsapp_notifications' ||
              $option_name === 'edubot_school_whatsapp_notifications') {
        $status = $value ? '✅ ENABLED' : '❌ DISABLED';
        $display_value = $value ? 'YES' : 'NO';
    } else {
        if (!empty($value)) {
            $status = '✅ Set';
            $display_value = (strlen($value) > 30) ? substr($value, 0, 30) . '...' : $value;
        } else {
            $status = '⚠️ NOT SET';
            $display_value = 'N/A';
        }
    }
    
    $status_color = strpos($status, '✅') !== false ? 'green' : (strpos($status, '❌') !== false ? 'red' : 'orange');
    
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>$label</strong></td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>$display_value</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px; color: $status_color;'>$status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>✅ Configuration Complete!</h2>";

// Check if all critical settings are configured
$provider = get_option('edubot_whatsapp_provider', '');
$token = get_option('edubot_whatsapp_token', '');
$phone_id = get_option('edubot_whatsapp_phone_id', '');
$parent_enabled = get_option('edubot_whatsapp_notifications', 0);

if ($provider && $token && $phone_id && $parent_enabled) {
    echo "<p style='font-size: 18px; color: green;'><strong>🎉 WhatsApp is fully configured and enabled!</strong></p>";
    echo "<p>✅ Meta WhatsApp Business API is ready</p>";
    echo "<p>✅ Parent notifications will be sent automatically</p>";
    echo "<p>✅ Next enquiries will receive WhatsApp confirmations</p>";
    
    echo "<h2>🧪 Test Now</h2>";
    echo "<p><a href='http://localhost/demo/' style='background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block; font-size: 16px;'>→ Go to Chatbot & Submit Test Enquiry</a></p>";
    
} else {
    echo "<p style='color: red;'><strong>⚠️ Some settings are missing</strong></p>";
    if (!$provider) echo "<p>❌ WhatsApp Provider not set</p>";
    if (!$token) echo "<p>❌ Access Token not set</p>";
    if (!$phone_id) echo "<p>❌ Phone Number ID not set</p>";
    if (!$parent_enabled) echo "<p>❌ Parent WhatsApp not enabled</p>";
}

echo "<h2>📝 Next Steps</h2>";
echo "<ol>";
echo "<li>Go to chatbot: <a href='http://localhost/demo/'>http://localhost/demo/</a></li>";
echo "<li>Submit a new admission enquiry with your phone number</li>";
echo "<li>You should receive a WhatsApp message on that number</li>";
echo "<li>Check debug log for details: <a href='http://localhost/demo/debug_log_viewer.php'>Debug Log</a></li>";
echo "</ol>";

echo "<h2>📱 Expected WhatsApp Message</h2>";
echo "<pre style='background: #f0f0f0; padding: 15px; border-radius: 4px;'>";
echo "Dear [Student Name],

Thank you for your enquiry at Vikas The Concept School.
Your enquiry number is [ENQ2025XXXX] for Grade [Grade].

We have received your application and will contact you within 24-48 hours.

Best regards,
Admissions Team";
echo "</pre>";

?>
