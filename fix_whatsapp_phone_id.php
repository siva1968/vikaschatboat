<?php
/**
 * Fix Missing WhatsApp Phone Number ID
 * 
 * This script sets the missing edubot_whatsapp_phone_number_id option
 * Upload to WordPress root and run via browser
 */

// WordPress bootstrap
require_once 'wp-config.php';
require_once 'wp-load.php';

echo "<h1>🔧 Fixing WhatsApp Phone Number ID</h1>";

// Set the Phone Number ID that we know works
$phone_number_id = '614525638411206';

// Update the WordPress option
$updated = update_option('edubot_whatsapp_phone_number_id', $phone_number_id);

if ($updated) {
    echo "<p>✅ <strong>SUCCESS:</strong> Phone Number ID set to: <code>$phone_number_id</code></p>";
} else {
    echo "<p>⚠️ <strong>INFO:</strong> Phone Number ID was already set or no change needed</p>";
}

// Verify all WhatsApp settings
echo "<h2>📋 Current WhatsApp Configuration:</h2>";
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";

$settings = [
    'edubot_whatsapp_notifications' => 'Notifications Enabled',
    'edubot_whatsapp_provider' => 'Provider',
    'edubot_whatsapp_token' => 'Access Token',
    'edubot_whatsapp_phone_number_id' => 'Phone Number ID',
    'edubot_whatsapp_template_type' => 'Template Type',
    'edubot_whatsapp_template_name' => 'Template Name',
    'edubot_whatsapp_template_language' => 'Template Language'
];

foreach ($settings as $option => $label) {
    $value = get_option($option, 'NOT SET');
    
    // Hide sensitive token for display
    if ($option === 'edubot_whatsapp_token' && !empty($value) && $value !== 'NOT SET') {
        $display_value = substr($value, 0, 10) . '...';
    } else {
        $display_value = $value;
    }
    
    $status = ($value && $value !== 'NOT SET') ? '✅' : '❌';
    
    echo "<tr>";
    echo "<td><strong>$label</strong></td>";
    echo "<td>$status</td>";
    echo "<td><code>$display_value</code></td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>🧪 Next Steps:</h2>";
echo "<ol>";
echo "<li><strong>Submit another test enquiry</strong> through your chatbot</li>";
echo "<li><strong>Check the debug log</strong> - Phone Number ID should now show as CONFIGURED</li>";
echo "<li><strong>WhatsApp message should be sent successfully</strong></li>";
echo "</ol>";

echo "<p><em>Configuration updated at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
