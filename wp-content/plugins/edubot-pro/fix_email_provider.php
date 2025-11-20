<?php
/**
 * Fix Email Provider Setting
 */

require_once dirname(__FILE__) . '/wp-load.php';

echo "<h1>🔧 Fix Email Provider Setting</h1>";

// Check current setting
$current_provider = get_option('edubot_email_provider', '');
echo "<h2>Current Settings</h2>";
echo "<p>Email Provider: <strong>" . (!empty($current_provider) ? $current_provider : 'NOT SET') . "</strong></p>";

// Set provider to zeptomail
echo "<h2>Setting Email Provider to ZeptoMail</h2>";
$updated = update_option('edubot_email_provider', 'zeptomail');

if ($updated) {
    echo "<p style='color: green;'>✅ Successfully updated email provider to: <strong>zeptomail</strong></p>";
} else {
    echo "<p style='color: orange;'>⚠️ Option already set to zeptomail (not changed)</p>";
}

// Verify it was set
$verified_provider = get_option('edubot_email_provider', '');
echo "<h2>Verification</h2>";
echo "<p>Email Provider after fix: <strong>" . $verified_provider . "</strong></p>";

if ($verified_provider === 'zeptomail') {
    echo "<p style='color: green; font-size: 18px;'><strong>✅ Email Provider Successfully Set to ZeptoMail!</strong></p>";
} else {
    echo "<p style='color: red;'><strong>❌ Failed to set email provider</strong></p>";
}

// Check all email settings
echo "<h2>All Email Settings</h2>";
$settings = array(
    'edubot_email_provider' => 'Provider',
    'edubot_email_api_key' => 'API Key',
    'edubot_email_from_address' => 'From Address',
    'edubot_email_from_name' => 'From Name',
    'edubot_email_notifications' => 'Parent Email Enabled',
    'edubot_school_notifications' => 'School Email Enabled'
);

echo "<ul>";
foreach ($settings as $option_name => $label) {
    $value = get_option($option_name, '');
    if ($option_name === 'edubot_email_api_key') {
        $value = !empty($value) ? '✅ Configured (' . strlen($value) . ' chars)' : '❌ NOT SET';
    } elseif ($option_name === 'edubot_email_notifications' || $option_name === 'edubot_school_notifications') {
        $value = $value ? '✅ ENABLED' : '❌ DISABLED';
    }
    echo "<li><strong>$label:</strong> " . (!empty($value) ? $value : '(empty)') . "</li>";
}
echo "</ul>";

echo "<h2>Next Steps</h2>";
echo "<p>1. <a href='http://localhost/demo/verify_email_sending.php'>Test Email Sending</a></p>";
echo "<p>2. <a href='http://localhost/demo/'>Submit a Test Enquiry</a></p>";
echo "<p>3. Check your email inbox for confirmation</p>";

?>
