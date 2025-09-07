<?php
/**
 * WhatsApp Configuration Diagnostic
 * 
 * This script checks all WhatsApp-related WordPress options to identify
 * configuration issues and option name mismatches
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>📊 WhatsApp Configuration Diagnostic</h1>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>";

// List of possible WhatsApp option names
$whatsapp_options = [
    'edubot_whatsapp_notifications',
    'edubot_whatsapp_provider',
    'edubot_whatsapp_token',
    'edubot_whatsapp_phone_id',
    'edubot_whatsapp_phone_number_id',
    'edubot_whatsapp_template_type',
    'edubot_whatsapp_template_name', 
    'edubot_whatsapp_template_language',
    'edubot_whatsapp_template'
];

echo "<h2>🔍 WordPress Options Check</h2>";
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'><th>Option Name</th><th>Status</th><th>Value Preview</th></tr>";

foreach ($whatsapp_options as $option) {
    $value = get_option($option, null);
    
    if ($value !== null) {
        $status = "✅ SET";
        if (in_array($option, ['edubot_whatsapp_token'])) {
            $preview = substr($value, 0, 10) . '... (' . strlen($value) . ' chars)';
        } elseif (is_array($value)) {
            $preview = 'Array (' . count($value) . ' items)';
        } else {
            $preview = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
        }
    } else {
        $status = "❌ NOT SET";
        $preview = '-';
    }
    
    echo "<tr>";
    echo "<td><code>$option</code></td>";
    echo "<td>$status</td>";
    echo "<td>" . htmlspecialchars($preview) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Check for any other edubot options that might contain whatsapp
global $wpdb;
$results = $wpdb->get_results("
    SELECT option_name, CHAR_LENGTH(option_value) as length 
    FROM {$wpdb->options} 
    WHERE option_name LIKE 'edubot%whatsapp%' 
       OR option_name LIKE '%whatsapp%edubot%'
    ORDER BY option_name
");

if ($results) {
    echo "<h2>🔍 Additional WhatsApp-related Options Found</h2>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Option Name</th><th>Value Length</th></tr>";
    
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td><code>{$row->option_name}</code></td>";
        echo "<td>{$row->length} characters</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<h2>✅ No Additional WhatsApp Options Found</h2>";
}

// Check current configuration status
echo "<h2>📋 Configuration Summary</h2>";

$notifications_enabled = get_option('edubot_whatsapp_notifications', 0);
$provider = get_option('edubot_whatsapp_provider', '');
$token = get_option('edubot_whatsapp_token', '');
$phone_id_1 = get_option('edubot_whatsapp_phone_id', '');
$phone_id_2 = get_option('edubot_whatsapp_phone_number_id', '');
$template_type = get_option('edubot_whatsapp_template_type', '');

echo "<ul>";
echo "<li><strong>Notifications:</strong> " . ($notifications_enabled ? '✅ Enabled' : '❌ Disabled') . "</li>";
echo "<li><strong>Provider:</strong> " . ($provider ? "✅ $provider" : '❌ Not Set') . "</li>";
echo "<li><strong>Token:</strong> " . ($token ? '✅ Configured' : '❌ Not Set') . "</li>";
echo "<li><strong>Phone ID (edubot_whatsapp_phone_id):</strong> " . ($phone_id_1 ? "✅ $phone_id_1" : '❌ Not Set') . "</li>";
echo "<li><strong>Phone ID (edubot_whatsapp_phone_number_id):</strong> " . ($phone_id_2 ? "✅ $phone_id_2" : '❌ Not Set') . "</li>";
echo "<li><strong>Template Type:</strong> " . ($template_type ? "✅ $template_type" : '❌ Not Set') . "</li>";
echo "</ul>";

// Final recommendation
if ($notifications_enabled && $provider && $token && ($phone_id_1 || $phone_id_2) && $template_type) {
    echo "<h3 style='color: green;'>✅ Configuration looks complete!</h3>";
    echo "<p>All required settings appear to be configured. If WhatsApp is still not working, the issue might be in the API call itself.</p>";
} else {
    echo "<h3 style='color: red;'>❌ Configuration incomplete</h3>";
    echo "<p>Some required settings are missing. Please check the missing items above.</p>";
}

echo "<hr>";
echo "<p><em>Run this diagnostic after uploading to: https://stage.epistemo.in/whatsapp_diagnostic.php</em></p>";
?>
