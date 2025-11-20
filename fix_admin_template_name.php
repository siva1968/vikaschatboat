<?php
/**
 * Fix Admin Template Name - Use Separate Template
 */

require_once dirname(__FILE__) . '/wp-load.php';

echo "<h1>🔧 Fix Admin WhatsApp Template Configuration</h1>";

echo "<h2>📋 Current Situation</h2>";
echo "<p>✅ <strong>Parent Template:</strong> admission_confirmation (personal message to student)</p>";
echo "<p>❌ <strong>Admin Template:</strong> Currently using 'admission_confirmation' (WRONG - should be different template)</p>";

echo "<h2>✅ The Fix</h2>";
echo "<p>Meta WhatsApp has TWO different templates created for Epistemo:</p>";
echo "<ol>";
echo "<li><strong>admission_confirmation</strong> - For parent/student (personal greeting)</li>";
echo "<li><strong>school_notification</strong> - For admin/school (enquiry summary alert) <strong>OR</strong></li>";
echo "<li><strong>new_admission_enquiry</strong> - Alternative admin template name</li>";
echo "</ol>";

echo "<h2>⚙️ Setting Correct Template Names</h2>";

// Current settings
$parent_template = get_option('edubot_whatsapp_template_name', '');
$admin_template = get_option('edubot_school_whatsapp_template_name', '');

echo "<p><strong>Before:</strong></p>";
echo "<ul>";
echo "<li>Parent Template: <strong>" . htmlspecialchars($parent_template) . "</strong></li>";
echo "<li>Admin Template: <strong>" . htmlspecialchars($admin_template) . "</strong></li>";
echo "</ul>";

// Set correct template names
echo "<p><strong>Setting:</strong></p>";

// Parent stays as admission_confirmation
update_option('edubot_whatsapp_template_name', 'admission_confirmation');
echo "<li>Parent Template → <strong>admission_confirmation</strong> ✅</li>";

// Admin should be school_notification or new_admission_enquiry
// Try school_notification first as it's more commonly used
$admin_template_name = 'school_notification';  // Change this if your template has different name
update_option('edubot_school_whatsapp_template_name', $admin_template_name);
echo "<li>Admin Template → <strong>" . $admin_template_name . "</strong> ✅</li>";

echo "<h2>📝 Important: Verify Template Names on Meta</h2>";
echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
echo "<p><strong>❗ You need to verify the exact template names in Meta WhatsApp Business Account:</strong></p>";
echo "<ol>";
echo "<li>Go to: <a href='https://business.facebook.com' target='_blank'>Meta Business Suite</a></li>";
echo "<li>Navigate to: WhatsApp → Message Templates</li>";
echo "<li>Look for templates with names like:</li>";
echo "  <ul>";
echo "    <li>admission_confirmation</li>";
echo "    <li>school_notification</li>";
echo "    <li>new_admission_enquiry</li>";
echo "    <li>enquiry_alert</li>";
echo "  </ul>";
echo "<li>Copy the <strong>EXACT template name</strong> from Meta</li>";
echo "<li>Update the field below with the correct name</li>";
echo "</ol>";
echo "</div>";

echo "<h2>🔄 Update Admin Template Name</h2>";
echo "<form method='POST' style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<p>";
echo "<label for='admin_template'><strong>Admin/School Template Name:</strong></label><br>";
echo "<input type='text' id='admin_template' name='admin_template' value='" . esc_attr($admin_template_name) . "' class='regular-text' style='padding: 8px; font-size: 14px; width: 300px;' />";
echo "</p>";
echo "<p><em>Enter the EXACT template name from your Meta WhatsApp Business Account</em></p>";
echo "<p>";
echo "<button type='submit' class='button button-primary' style='padding: 8px 20px;'>Update Admin Template Name</button>";
echo "</p>";
echo "</form>";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_template'])) {
    $new_template_name = sanitize_text_field($_POST['admin_template']);
    update_option('edubot_school_whatsapp_template_name', $new_template_name);
    echo "<p style='color: green; font-size: 16px;'><strong>✅ Admin template name updated to: " . htmlspecialchars($new_template_name) . "</strong></p>";
}

echo "<h2>✅ Current Configuration After Fix</h2>";
$final_parent = get_option('edubot_whatsapp_template_name', '');
$final_admin = get_option('edubot_school_whatsapp_template_name', '');

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Template</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Type</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Format</th>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>" . htmlspecialchars($final_parent) . "</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>Parent/Student</td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>Personal greeting message</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>" . htmlspecialchars($final_admin) . "</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>Admin/School</td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>Formatted enquiry alert</td>";
echo "</tr>";

echo "</table>";

echo "<h2>🚀 Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Verify</strong> template names in Meta Business Account</li>";
echo "<li><strong>Update</strong> admin template name if different from 'school_notification'</li>";
echo "<li><strong>Test</strong> by submitting a new enquiry: <a href='http://localhost/demo/' target='_blank'>Chatbot</a></li>";
echo "<li><strong>Check</strong> that parent and admin receive different formatted messages</li>";
echo "<li><strong>Verify</strong> logs: <a href='http://localhost/demo/debug_log_viewer.php'>Debug Log</a></li>";
echo "</ol>";

?>
