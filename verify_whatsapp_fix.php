<?php
/**
 * Verify WhatsApp Template Fixes
 */

require_once dirname(__FILE__) . '/wp-load.php';

echo "<h1>✅ WhatsApp Template Fix Verification</h1>";

echo "<h2>1. Template Names Verification</h2>";
$parent_template = get_option('edubot_whatsapp_template_name', '');
$school_template = get_option('edubot_school_whatsapp_template_name', '');
$parent_template_type = get_option('edubot_whatsapp_template_type', '');

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Setting</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Value</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Status</th>";
echo "</tr>";

// Parent Template
echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>Parent Template Name</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($parent_template) . "</td>";
if ($parent_template === 'admission_confirmation') {
    echo "<td style='border: 1px solid #ddd; padding: 8px; color: green;'>✅ Correct</td>";
} else {
    echo "<td style='border: 1px solid #ddd; padding: 8px; color: red;'>❌ Wrong: " . $parent_template . "</td>";
}
echo "</tr>";

// School Template
echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>School Template Name</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($school_template) . "</td>";
if ($school_template === 'admission_confirmation') {
    echo "<td style='border: 1px solid #ddd; padding: 8px; color: green;'>✅ Correct</td>";
} else {
    echo "<td style='border: 1px solid #ddd; padding: 8px; color: red;'>❌ Wrong: " . $school_template . "</td>";
}
echo "</tr>";

// Parent Template Type
echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>Parent Template Type</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($parent_template_type) . "</td>";
if ($parent_template_type === 'business_template') {
    echo "<td style='border: 1px solid #ddd; padding: 8px; color: green;'>✅ Correct</td>";
} else {
    echo "<td style='border: 1px solid #ddd; padding: 8px; color: orange;'>⚠️ " . $parent_template_type . "</td>";
}
echo "</tr>";

echo "</table>";

// Code verification
echo "<h2>2. Code File Verification</h2>";
$shortcode_file = '/home/epistemo-stage/htdocs/stage.epistemo.in/wp-content/plugins/edubot-pro/includes/class-edubot-shortcode.php';
$local_shortcode = 'D:/xamppdev/htdocs/demo/wp-content/plugins/edubot-pro/includes/class-edubot-shortcode.php';

if (file_exists($local_shortcode)) {
    $content = file_get_contents($local_shortcode);
    
    // Check if fix is in place
    if (strpos($content, "'admission_confirmation'); // Fixed: template name") !== false || 
        strpos($content, "get_option('edubot_school_whatsapp_template_name', 'admission_confirmation')") !== false) {
        echo "<p style='color: green;'><strong>✅ Code Fix Verified:</strong> Template names corrected in shortcode file</p>";
    } else {
        echo "<p style='color: orange;'><strong>⚠️ Warning:</strong> Code fix may need to be redeployed</p>";
    }
}

echo "<h2>3. API Configuration Status</h2>";
$api_settings = array(
    'edubot_whatsapp_provider' => 'Provider',
    'edubot_whatsapp_token' => 'Access Token',
    'edubot_whatsapp_phone_id' => 'Phone Number ID',
    'edubot_school_whatsapp_notifications' => 'School Notifications',
);

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Setting</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Status</th>";
echo "</tr>";

foreach ($api_settings as $option => $label) {
    $value = get_option($option, '');
    $status = '';
    
    if ($option === 'edubot_whatsapp_token') {
        $status = !empty($value) ? '✅ Configured (' . strlen($value) . ' chars)' : '❌ NOT SET';
    } elseif ($option === 'edubot_school_whatsapp_notifications') {
        $status = $value ? '✅ ENABLED' : '❌ DISABLED';
    } else {
        $status = !empty($value) ? '✅ ' . $value : '❌ NOT SET';
    }
    
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>$label</strong></td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>$status</td>";
    echo "</tr>";
}

echo "</table>";

// Final verdict
echo "<h2>✅ Verification Result</h2>";
if ($parent_template === 'admission_confirmation' && $school_template === 'admission_confirmation') {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
    echo "<p style='color: #155724; font-size: 16px;'><strong>🎉 All fixes verified and active!</strong></p>";
    echo "<p>Both parent and school WhatsApp templates are correctly configured.</p>";
    echo "<p>WhatsApp notifications should now work properly for both parent and admin.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<p style='color: #721c24; font-size: 16px;'><strong>⚠️ Templates not fully configured</strong></p>";
    echo "<p>Please run: <a href='http://localhost/demo/fix_whatsapp_templates.php'>fix_whatsapp_templates.php</a></p>";
    echo "</div>";
}

echo "<h2>🧪 Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Clear Browser Cache:</strong> Press Ctrl+F5</li>";
echo "<li><strong>Submit Test Enquiry:</strong> Go to <a href='http://localhost/demo/' target='_blank'>chatbot</a></li>";
echo "<li><strong>Check Your Phone:</strong> You should receive WhatsApp messages</li>";
echo "<li><strong>View Debug Log:</strong> <a href='http://localhost/demo/debug_log_viewer.php'>Debug Log</a></li>";
echo "</ol>";

?>
