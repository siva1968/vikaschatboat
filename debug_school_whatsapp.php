<?php
/**
 * Debug School WhatsApp Settings
 */

require_once dirname(__FILE__) . '/wp-load.php';

echo "<h1>🔍 Debug School WhatsApp Notifications</h1>";

// Check school WhatsApp settings
echo "<h2>School WhatsApp Configuration</h2>";
$school_whatsapp_enabled = get_option('edubot_school_whatsapp_notifications', false);
$school_phone = get_option('edubot_school_phone', '');
$school_email = get_option('edubot_school_email', '');

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Setting</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Value</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Status</th>";
echo "</tr>";

// School WhatsApp Enabled
echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>School WhatsApp Notifications Enabled</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . ($school_whatsapp_enabled ? 'YES' : 'NO') . "</td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . ($school_whatsapp_enabled ? '✅ ENABLED' : '❌ DISABLED') . "</td>";
echo "</tr>";

// School Phone
echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>School Phone Number</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . (!empty($school_phone) ? htmlspecialchars($school_phone) : 'NOT SET') . "</td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . (!empty($school_phone) ? '✅ Set' : '❌ NOT SET') . "</td>";
echo "</tr>";

// School Email
echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>School Email</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . (!empty($school_email) ? htmlspecialchars($school_email) : 'NOT SET') . "</td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . (!empty($school_email) ? '✅ Set' : '❌ NOT SET') . "</td>";
echo "</tr>";

echo "</table>";

// Check WhatsApp API settings
echo "<h2>WhatsApp API Configuration</h2>";
$whatsapp_provider = get_option('edubot_whatsapp_provider', '');
$whatsapp_token = get_option('edubot_whatsapp_token', '');
$whatsapp_phone_id = get_option('edubot_whatsapp_phone_id', '');

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Setting</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Status</th>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>WhatsApp Provider</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . (!empty($whatsapp_provider) ? '✅ ' . $whatsapp_provider : '❌ NOT SET') . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>WhatsApp Token</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . (!empty($whatsapp_token) ? '✅ Configured (' . strlen($whatsapp_token) . ' chars)' : '❌ NOT SET') . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>Phone Number ID</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . (!empty($whatsapp_phone_id) ? '✅ ' . $whatsapp_phone_id : '❌ NOT SET') . "</td>";
echo "</tr>";

echo "</table>";

// Problem identification
echo "<h2>🔴 Issues Identified</h2>";
$issues = array();

if (!$school_whatsapp_enabled) {
    $issues[] = "❌ School WhatsApp Notifications are DISABLED";
}

if (empty($school_phone)) {
    $issues[] = "❌ School Phone Number NOT SET";
}

if (!$school_whatsapp_enabled && empty($school_phone)) {
    $issues[] = "❌ CRITICAL: Admin cannot receive WhatsApp messages!";
}

if (empty($issues)) {
    echo "<p style='color: green;'>✅ All settings look correct. School should receive WhatsApp messages.</p>";
} else {
    foreach ($issues as $issue) {
        echo "<p style='color: red;'><strong>$issue</strong></p>";
    }
}

// Solution
echo "<h2>✅ Solution</h2>";

if (!$school_whatsapp_enabled) {
    echo "<p>1. Enable School WhatsApp Notifications...</p>";
    update_option('edubot_school_whatsapp_notifications', true);
    echo "<p style='color: green;'>✅ School WhatsApp Notifications ENABLED</p>";
}

if (empty($school_phone)) {
    echo "<p>2. Setting School Phone Number to match WhatsApp Phone ID...</p>";
    // Extract phone number from Phone ID or use a default school phone
    $school_phone_number = '919866133566'; // Default or get from settings
    
    // Try to get from existing school settings
    $school_phones = get_option('edubot_school_phones', array());
    if (!empty($school_phones)) {
        $school_phone_number = is_array($school_phones) ? $school_phones[0] : $school_phones;
    }
    
    echo "<p>School Phone: " . $school_phone_number . "</p>";
    update_option('edubot_school_phone', $school_phone_number);
    echo "<p style='color: green;'>✅ School Phone Number SET</p>";
}

// Final verification
echo "<h2>Final Verification</h2>";
$final_school_whatsapp = get_option('edubot_school_whatsapp_notifications', false);
$final_school_phone = get_option('edubot_school_phone', '');

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Setting</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Status</th>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>School WhatsApp Enabled</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . ($final_school_whatsapp ? '✅ ENABLED' : '❌ DISABLED') . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>School Phone Number</strong></td>";
echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . (!empty($final_school_phone) ? '✅ ' . $final_school_phone : '❌ NOT SET') . "</td>";
echo "</tr>";

echo "</table>";

if ($final_school_whatsapp && !empty($final_school_phone)) {
    echo "<h2 style='color: green;'>🎉 Admin WhatsApp notifications are now ENABLED!</h2>";
    echo "<p style='color: green; font-size: 16px;'><strong>✅ School/Admin should receive WhatsApp messages for new enquiries</strong></p>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Go to chatbot: <a href='http://localhost/demo/' target='_blank'>http://localhost/demo/</a></li>";
echo "<li>Submit a new enquiry</li>";
echo "<li>Both parent AND admin should receive WhatsApp messages</li>";
echo "<li>Verify: <a href='http://localhost/demo/whatsapp_verification.php'>WhatsApp Verification</a></li>";
echo "</ol>";

?>
