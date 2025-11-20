<?php
/**
 * ✅ Configure Correct WhatsApp Template Names
 * Found both templates in Meta!
 */

require_once dirname(__FILE__) . '/wp-load.php';

echo "<h1>✅ WhatsApp Templates Configuration - FINAL FIX</h1>";

echo "<h2>🎉 Both Templates Found in Meta!</h2>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #d4edda;'>";
echo "<th style='border: 1px solid #c3e6cb; padding: 10px; text-align: left;'>Template</th>";
echo "<th style='border: 1px solid #c3e6cb; padding: 10px; text-align: left;'>Name in Meta</th>";
echo "<th style='border: 1px solid #c3e6cb; padding: 10px; text-align: left;'>Purpose</th>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 10px;'><strong>Template 1</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 10px;'><code>admission_confirmation</code></td>";
echo "<td style='border: 1px solid #ddd; padding: 10px;'>Parent/Student notification</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 10px;'><strong>Template 2</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 10px;'><code>edubot_school_whatsapp_template_name_</code></td>";
echo "<td style='border: 1px solid #ddd; padding: 10px;'>Admin/School enquiry alert</td>";
echo "</tr>";

echo "</table>";

echo "<h2>✅ Setting Correct Template Names</h2>";

// Set correct template names
update_option('edubot_whatsapp_template_name', 'admission_confirmation');
echo "<p style='color: green;'>✅ Parent Template → <strong>admission_confirmation</strong></p>";

update_option('edubot_school_whatsapp_template_name', 'edubot_school_whatsapp_template_name_');
echo "<p style='color: green;'>✅ Admin Template → <strong>edubot_school_whatsapp_template_name_</strong></p>";

update_option('edubot_whatsapp_template_type', 'business_template');
echo "<p style='color: green;'>✅ Template Type → <strong>business_template</strong></p>";

update_option('edubot_school_whatsapp_template_type', 'business_template');
echo "<p style='color: green;'>✅ School Template Type → <strong>business_template</strong></p>";

echo "<h2>✅ Verification</h2>";

$parent_template = get_option('edubot_whatsapp_template_name', '');
$admin_template = get_option('edubot_school_whatsapp_template_name', '');
$parent_type = get_option('edubot_whatsapp_template_type', '');
$admin_type = get_option('edubot_school_whatsapp_template_type', '');

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Setting</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Value</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Status</th>";
echo "</tr>";

$checks = array(
    'Parent Template Name' => array('value' => $parent_template, 'expected' => 'admission_confirmation'),
    'Admin Template Name' => array('value' => $admin_template, 'expected' => 'edubot_school_whatsapp_template_name_'),
    'Parent Template Type' => array('value' => $parent_type, 'expected' => 'business_template'),
    'Admin Template Type' => array('value' => $admin_type, 'expected' => 'business_template'),
);

foreach ($checks as $label => $check) {
    $status = ($check['value'] === $check['expected']) ? '✅ Correct' : '❌ Wrong: ' . $check['value'];
    $color = ($check['value'] === $check['expected']) ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>$label</strong></td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'><code>" . htmlspecialchars($check['value']) . "</code></td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px; color: $color;'>$status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>📊 What Happens Now</h2>";
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>✅ When an enquiry is submitted:</strong></p>";
echo "<ol>";
echo "<li><strong>Parent receives:</strong> WhatsApp message via <code>admission_confirmation</code> template";
echo "  <ul>";
echo "    <li>Format: Personal greeting to student/parent</li>";
echo "    <li>Content: Thank you message with enquiry number and grade</li>";
echo "  </ul>";
echo "</li>";
echo "<li><strong>Admin/School receives:</strong> WhatsApp message via <code>edubot_school_whatsapp_template_name_</code> template";
echo "  <ul>";
echo "    <li>Format: Formatted enquiry summary with all details</li>";
echo "    <li>Content: Enquiry number, student name, grade, board, parent, phone, email, submission date</li>";
echo "  </ul>";
echo "</li>";
echo "<li><strong>Email notifications:</strong> Both receive email confirmations via ZeptoMail</li>";
echo "</ol>";
echo "</div>";

echo "<h2>🧪 Test Now</h2>";
echo "<ol>";
echo "<li><strong>Clear browser cache:</strong> Press Ctrl+F5</li>";
echo "<li><strong>Go to chatbot:</strong> <a href='http://localhost/demo/' target='_blank'>http://localhost/demo/</a></li>";
echo "<li><strong>Submit a test enquiry</strong> with your phone number</li>";
echo "<li><strong>Check your phone:</strong>";
echo "  <ul>";
echo "    <li>You (parent) should receive: Personal admission confirmation message</li>";
echo "    <li>Admin should receive: Formatted enquiry summary alert</li>";
echo "  </ul>";
echo "</li>";
echo "<li><strong>Check debug log:</strong> <a href='http://localhost/demo/debug_log_viewer.php'>View Debug Log</a></li>";
echo "</ol>";

echo "<h2>✅ Summary</h2>";
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px;'>";
echo "<p style='font-size: 16px;'><strong>🎉 WhatsApp configuration is now COMPLETE!</strong></p>";
echo "<ul>";
echo "<li>✅ Parent template: admission_confirmation</li>";
echo "<li>✅ Admin template: edubot_school_whatsapp_template_name_</li>";
echo "<li>✅ Both using Meta WhatsApp Business API templates</li>";
echo "<li>✅ Ready for testing and production</li>";
echo "</ul>";
echo "</div>";

?>
