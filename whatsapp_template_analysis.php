<?php
/**
 * Configuration: Using Single Template for Both Parent & Admin
 * OR Create Second Template for Admin Enquiry Summary
 */

require_once dirname(__FILE__) . '/wp-load.php';

echo "<h1>📋 WhatsApp Template Configuration Analysis</h1>";

echo "<h2>Current Meta Templates Available</h2>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Template Name</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Type</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Status</th>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>admission_confirmation</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>Personal message to parent/student</td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>✅ Exists</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>school_notification</strong> (or similar)</td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>Formatted enquiry alert for admin</td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>❓ Need to verify</td>";
echo "</tr>";

echo "</table>";

echo "<h2>⚠️ Current Issue</h2>";
echo "<p>The admin template format is completely different from the parent template:</p>";

echo "<h3>Parent Format (admission_confirmation):</h3>";
echo "<blockquote style='background: #f0f0f0; padding: 10px; border-left: 4px solid #007cba;'>";
echo "Dear {{name}},<br>";
echo "Thank you for your enquiry at {{school}}.<br>";
echo "Your enquiry number is {{enquiry_id}} for {{grade}}.<br>";
echo "We have received your application on {{date}} and will contact you within 24-48 hours.";
echo "</blockquote>";

echo "<h3>Admin Format (needs separate template):</h3>";
echo "<blockquote style='background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107;'>";
echo "📋 Enquiry Number: {{enquiry_id}}<br>";
echo "👶 Student: {{student_name}}<br>";
echo "🎯 Grade: {{grade}}<br>";
echo "📚 Board: {{board}}<br>";
echo "👨‍👩‍👧 Parent: {{parent_name}}<br>";
echo "📱 Phone: {{phone}}<br>";
echo "📧 Email: {{email}}<br>";
echo "📅 Submitted: {{date}}<br>";
echo "<em>Please review and contact the family for next steps.</em>";
echo "</blockquote>";

echo "<h2>✅ Solution Options</h2>";

echo "<h3>Option 1: Create Second Template in Meta (RECOMMENDED)</h3>";
echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
echo "<ol>";
echo "<li><strong>Go to Meta Business Suite</strong></li>";
echo "<li><strong>Create NEW template:</strong>";
echo "  <ul>";
echo "    <li>Template Name: <strong>school_enquiry_alert</strong> (or <strong>enquiry_summary</strong>)</li>";
echo "    <li>Category: Utility</li>";
echo "    <li>Language: English</li>";
echo "    <li>Add body parameters for formatted enquiry details</li>";
echo "  </ul>";
echo "</li>";
echo "<li><strong>Once created, come back here and configure the name</strong></li>";
echo "</ol>";
echo "</div>";

echo "<h3>Option 2: Use Same Template with Different Parameters</h3>";
echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107; border-radius: 5px; margin: 10px 0;'>";
echo "<p>⚠️ <strong>Not recommended</strong> - Template format doesn't match admin needs (only 5 parameters)</p>";
echo "</div>";

echo "<h2>📝 What You Need to Do</h2>";
echo "<ol>";
echo "<li><strong>Check Meta:</strong> Do you have a second template for admin notifications?</li>";
echo "<li><strong>If YES:</strong> Tell me the exact template name (e.g., school_enquiry_alert, enquiry_summary, etc.)</li>";
echo "<li><strong>If NO:</strong> Create one following the template structure shown above</li>";
echo "<li><strong>Then:</strong> I'll configure the system to use both templates correctly</li>";
echo "</ol>";

echo "<h2>🔧 Current Configuration</h2>";
$parent_template = get_option('edubot_whatsapp_template_name', '');
$admin_template = get_option('edubot_school_whatsapp_template_name', '');
$parent_type = get_option('edubot_whatsapp_template_type', '');

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Setting</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Value</th>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>Parent Template Name</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($parent_template) . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>Admin Template Name</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($admin_template) . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>Parent Template Type</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($parent_type) . "</td>";
echo "</tr>";

echo "</table>";

echo "<h2>📝 Update Admin Template Name</h2>";
echo "<form method='POST' style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<p>";
echo "<label for='admin_template'><strong>Enter Admin Template Name (from Meta):</strong></label><br>";
echo "<input type='text' id='admin_template' name='admin_template' placeholder='e.g., school_enquiry_alert' style='padding: 8px; font-size: 14px; width: 100%; max-width: 400px;' />";
echo "<p style='font-size: 12px; color: #666;'>Use the exact template name from your Meta Business Suite</p>";
echo "</p>";
echo "<button type='submit' class='button button-primary'>Update Admin Template</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['admin_template'])) {
    $new_template = sanitize_text_field($_POST['admin_template']);
    update_option('edubot_school_whatsapp_template_name', $new_template);
    echo "<p style='color: green; font-size: 16px; margin-top: 10px;'><strong>✅ Admin template updated to: " . htmlspecialchars($new_template) . "</strong></p>";
}

?>
