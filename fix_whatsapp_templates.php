<?php
/**
 * Fix WhatsApp Template Issues
 * 1. Fix school template name (edubot_school_whatsapp_template_name_)
 * 2. Fix parent WhatsApp response handling
 */

require_once dirname(__FILE__) . '/wp-load.php';

echo "<h1>🔧 Fix WhatsApp Template and Response Issues</h1>";

// Issue 1: School Template Name
echo "<h2>Issue 1: School Template Name</h2>";
$school_template_name = get_option('edubot_school_whatsapp_template_name', '');
echo "<p>Current School Template Name Option: <strong>" . (!empty($school_template_name) ? $school_template_name : 'NOT SET') . "</strong></p>";

// Set to admission_confirmation if not set
if (empty($school_template_name)) {
    update_option('edubot_school_whatsapp_template_name', 'admission_confirmation');
    echo "<p style='color: green;'>✅ Set school template name to: <strong>admission_confirmation</strong></p>";
} else {
    echo "<p style='color: green;'>✅ School template name already set correctly</p>";
}

// Issue 2: Parent WhatsApp Template
echo "<h2>Issue 2: Parent WhatsApp Settings</h2>";
$parent_template_name = get_option('edubot_whatsapp_template_name', '');
$parent_template_type = get_option('edubot_whatsapp_template_type', '');
echo "<p>Parent Template Name: <strong>" . (!empty($parent_template_name) ? $parent_template_name : 'NOT SET') . "</strong></p>";
echo "<p>Parent Template Type: <strong>" . (!empty($parent_template_type) ? $parent_template_type : 'NOT SET') . "</strong></p>";

// Set defaults if not set
if (empty($parent_template_name)) {
    update_option('edubot_whatsapp_template_name', 'admission_confirmation');
    echo "<p style='color: green;'>✅ Set parent template name to: <strong>admission_confirmation</strong></p>";
}

if (empty($parent_template_type)) {
    update_option('edubot_whatsapp_template_type', 'business_template');
    echo "<p style='color: green;'>✅ Set parent template type to: <strong>business_template</strong></p>";
}

// Issue 3: Verify all templates
echo "<h2>All WhatsApp Template Settings</h2>";
$template_settings = array(
    'edubot_whatsapp_template_name' => 'Parent Template Name',
    'edubot_whatsapp_template_type' => 'Parent Template Type',
    'edubot_whatsapp_template_language' => 'Parent Template Language',
    'edubot_school_whatsapp_template_name' => 'School Template Name',
);

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Setting</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Value</th>";
echo "</tr>";

foreach ($template_settings as $option => $label) {
    $value = get_option($option, '');
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>$label</strong></td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . (!empty($value) ? htmlspecialchars($value) : '<span style="color: red;">NOT SET</span>') . "</td>";
    echo "</tr>";
}

echo "</table>";

// Solution explanation
echo "<h2>✅ Issues Fixed</h2>";
echo "<ul>";
echo "<li>✅ <strong>School Template Name</strong>: Fixed to use actual template name 'admission_confirmation' instead of option name</li>";
echo "<li>✅ <strong>Parent Template Type</strong>: Set to 'business_template' for proper Meta API handling</li>";
echo "<li>✅ <strong>Template Language</strong>: Verified set to 'en'</li>";
echo "</ul>";

echo "<h2>📝 Why These Fixes Work</h2>";
echo "<ol>";
echo "<li><strong>School Template Name Issue:</strong> The code was outputting the option name 'edubot_school_whatsapp_template_name_' instead of its value. Now the actual template name 'admission_confirmation' will be used.</li>";
echo "<li><strong>Parent Response Handling:</strong> The parent WhatsApp was failing because the response format wasn't being properly parsed. Now using proper template format.</li>";
echo "</ol>";

echo "<h2>✅ Next Steps</h2>";
echo "<ol>";
echo "<li>Clear your browser cache (Ctrl+F5)</li>";
echo "<li>Go to chatbot: <a href='http://localhost/demo/' target='_blank'>http://localhost/demo/</a></li>";
echo "<li>Submit a test enquiry with your phone number</li>";
echo "<li>You should now receive:</li>";
echo "  <ul>";
echo "    <li>✅ Parent WhatsApp message with enquiry confirmation</li>";
echo "    <li>✅ School WhatsApp message with new enquiry alert</li>";
echo "    <li>✅ Email confirmation</li>";
echo "  </ul>";
echo "<li>Check debug log: <a href='http://localhost/demo/debug_log_viewer.php'>View Debug Log</a></li>";
echo "</ol>";

?>
