<?php
/**
 * Diagnostic Script - Missing Fields Issue
 * Shows why MCB preview shows N/A for most fields
 */

// Load WordPress
require_once('/xampp/htdocs/demo/wp-load.php');

echo "<h1 style='color: #0073aa;'>🔍 EduBot MCB Missing Fields Diagnosis</h1>";
echo "<hr>";

// ===========================
// 1. Check MCB Settings
// ===========================
echo "<h2>1️⃣ MCB Configuration Status</h2>";
$mcb_settings = get_option('edubot_mcb_settings', array());
$mcb_pro_settings = get_option('edubot_pro_settings', array());

echo "<table style='border-collapse: collapse; width: 100%; margin: 15px 0;'>";
echo "<tr style='background: #f0f0f0;'><td style='padding: 10px; border: 1px solid #ccc;'><strong>Option</strong></td><td style='padding: 10px; border: 1px solid #ccc;'><strong>Status</strong></td></tr>";
echo "<tr><td style='padding: 10px; border: 1px solid #ccc;'>MCB Settings Option Exists</td><td style='padding: 10px; border: 1px solid #ccc;'>" . (!empty($mcb_settings) ? "✅ YES - " . count($mcb_settings) . " settings" : "❌ NO") . "</td></tr>";
echo "<tr><td style='padding: 10px; border: 1px solid #ccc;'>Organization ID</td><td style='padding: 10px; border: 1px solid #ccc;'>" . (!empty($mcb_settings['organization_id']) ? "✅ " . $mcb_settings['organization_id'] : "❌ NOT SET") . "</td></tr>";
echo "<tr><td style='padding: 10px; border: 1px solid #ccc;'>Branch ID</td><td style='padding: 10px; border: 1px solid #ccc;'>" . (!empty($mcb_settings['branch_id']) ? "✅ " . $mcb_settings['branch_id'] : "❌ NOT SET") . "</td></tr>";
echo "<tr><td style='padding: 10px; border: 1px solid #ccc;'>MCB Sync Enabled</td><td style='padding: 10px; border: 1px solid #ccc;'>" . (!empty($mcb_settings['sync_enabled']) ? "✅ YES" : "❌ NO") . "</td></tr>";
echo "</table>";

if (empty($mcb_settings)) {
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px; margin: 15px 0;'>";
    echo "⚠️ <strong>CRITICAL ISSUE:</strong> MCB settings not found in WordPress options!<br>";
    echo "You need to configure MCB settings in EduBot Admin > Settings > MCB Integration<br>";
    echo "</div>";
}

// ===========================
// 2. Check Enquiry Data
// ===========================
echo "<h2>2️⃣ Latest Enquiry Data</h2>";
global $wpdb;
$table = $wpdb->prefix . 'edubot_enquiries';

// Get latest enquiry
$enquiry = $wpdb->get_row("SELECT * FROM {$table} ORDER BY id DESC LIMIT 1", ARRAY_A);

if ($enquiry) {
    echo "<strong>Enquiry #" . $enquiry['id'] . " (" . $enquiry['enquiry_number'] . ")</strong><br>";
    echo "<table style='border-collapse: collapse; width: 100%; margin: 15px 0;'>";
    echo "<tr style='background: #f0f0f0;'><td style='padding: 10px; border: 1px solid #ccc;'><strong>Field</strong></td><td style='padding: 10px; border: 1px solid #ccc;'><strong>Value</strong></td></tr>";
    
    $fields_to_check = array(
        'student_name' => 'Student Name',
        'parent_name' => 'Parent Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'date_of_birth' => 'Date of Birth',
        'grade' => 'Grade',
        'board' => 'Board',
        'academic_year' => 'Academic Year',
        'address' => 'Address',
        'gender' => 'Gender'
    );
    
    foreach ($fields_to_check as $key => $label) {
        $value = !empty($enquiry[$key]) ? $enquiry[$key] : 'EMPTY';
        $status = !empty($enquiry[$key]) ? '✅' : '❌';
        echo "<tr><td style='padding: 10px; border: 1px solid #ccc;'>{$label}</td><td style='padding: 10px; border: 1px solid #ccc;'>{$status} {$value}</td></tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>❌ No enquiries found</p>";
}

// ===========================
// 3. Diagnose Root Causes
// ===========================
echo "<h2>3️⃣ Root Cause Analysis</h2>";
echo "<ul style='font-size: 16px; line-height: 2;'>";

if (empty($mcb_settings['organization_id'])) {
    echo "<li>❌ <strong>Organization ID not configured</strong><br>";
    echo "   Solution: Go to EduBot Admin > Settings > API Settings > MyClassBoard<br>";
    echo "   Enter Organization ID: 21 and Branch ID: 113</li>";
}

if ($enquiry && empty($enquiry['parent_name'])) {
    echo "<li>❌ <strong>parent_name field is empty</strong><br>";
    echo "   Cause: Chatbot conversation doesn't ask for/save parent name<br>";
    echo "   Solution: Need to add parent_name collection to chatbot flow</li>";
}

if ($enquiry && empty($enquiry['email'])) {
    echo "<li>❌ <strong>email field is empty</strong><br>";
    echo "   Cause: Email not being collected/saved during conversation<br>";
    echo "   Solution: Ensure email is captured and stored in session</li>";
}

if ($enquiry && empty($enquiry['phone'])) {
    echo "<li>❌ <strong>phone field is empty</strong><br>";
    echo "   Cause: Phone not being collected/saved during conversation<br>";
    echo "   Solution: Ensure phone is captured and stored in session</li>";
}

if ($enquiry && empty($enquiry['board'])) {
    echo "<li>❌ <strong>board field is empty</strong><br>";
    echo "   Cause: Board selection not being saved to session<br>";
    echo "   Solution: Verify board is being updated in conversation_data</li>";
}

if ($enquiry && empty($enquiry['academic_year'])) {
    echo "<li>❌ <strong>academic_year field is empty</strong><br>";
    echo "   Cause: Academic year not being selected/saved<br>";
    echo "   Solution: Ensure academic year selection is saved</li>";
}

echo "</ul>";

// ===========================
// 4. Next Steps
// ===========================
echo "<h2>4️⃣ Required Actions</h2>";
echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
echo "<ol style='font-size: 15px; line-height: 1.8;'>";
echo "<li><strong>FIRST - Configure MCB Settings</strong><br>";
echo "   Go to: WordPress Admin > EduBot > Settings > API Settings<br>";
echo "   Click 'MyClassBoard Integration' tab<br>";
echo "   Set:<br>";
echo "   • Organization ID: 21<br>";
echo "   • Branch ID: 113<br>";
echo "   • Enable MCB Sync: YES<br>";
echo "   Click Save Settings</li>";

echo "<li><strong>SECOND - Test New Enquiry</strong><br>";
echo "   Submit a new admission enquiry with all fields:<br>";
echo "   • Student Name, Email, Phone, Grade, Board, Parent Name<br>";
echo "   This will populate the session data</li>";

echo "<li><strong>THIRD - Check MCB Preview Again</strong><br>";
echo "   Go to Applications > Click Preview button<br>";
echo "   Verify all fields now show values</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p style='font-size: 12px; color: #999;'>";
echo "Generated at: " . current_time('mysql') . "<br>";
echo "Database: " . DB_NAME . " | Prefix: " . $wpdb->prefix;
echo "</p>";

?>
