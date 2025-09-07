<?php
/**
 * Test WhatsApp notifications for EduBot Pro
 * Upload this file to test WhatsApp messaging functionality
 */

// Load WordPress
if (!defined('ABSPATH')) {
    $wp_load_paths = [
        '../../wp-load.php',
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../wp-load.php',
        './wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('Cannot find WordPress. Please upload this file to your WordPress site.');
    }
}

echo "<h1>EduBot WhatsApp Integration Test</h1>";
echo "<p>Testing WhatsApp messaging functionality...</p>";

// Check notification settings
echo "<h2>Notification Settings</h2>";
$email_enabled = get_option('edubot_email_notifications', 1);
$whatsapp_enabled = get_option('edubot_whatsapp_notifications', 0);
$school_notifications = get_option('edubot_school_notifications', 1);

echo "<p><strong>Email Notifications:</strong> " . ($email_enabled ? '✅ Enabled' : '❌ Disabled') . "</p>";
echo "<p><strong>WhatsApp Notifications:</strong> " . ($whatsapp_enabled ? '✅ Enabled' : '❌ Disabled') . "</p>";
echo "<p><strong>School Notifications:</strong> " . ($school_notifications ? '✅ Enabled' : '❌ Disabled') . "</p>";

// Check WhatsApp API configuration
echo "<h2>WhatsApp API Configuration</h2>";
$whatsapp_provider = get_option('edubot_whatsapp_provider', '');
$whatsapp_token = get_option('edubot_whatsapp_token', '');
$whatsapp_phone_id = get_option('edubot_whatsapp_phone_id', '');

echo "<p><strong>Provider:</strong> " . ($whatsapp_provider ? esc_html($whatsapp_provider) : '<em>Not configured</em>') . "</p>";
echo "<p><strong>Token:</strong> " . ($whatsapp_token ? '✅ Configured (hidden for security)' : '❌ Not configured') . "</p>";
echo "<p><strong>Phone ID:</strong> " . ($whatsapp_phone_id ? esc_html($whatsapp_phone_id) : '<em>Not configured</em>') . "</p>";

// Test WhatsApp API if configured
if ($whatsapp_enabled && $whatsapp_token && $whatsapp_provider) {
    echo "<h2>WhatsApp API Test</h2>";
    
    if (!class_exists('EduBot_API_Integrations')) {
        $integration_file = __DIR__ . '/includes/class-api-integrations.php';
        if (file_exists($integration_file)) {
            require_once $integration_file;
        } else {
            echo "<p>❌ EduBot_API_Integrations class file not found</p>";
        }
    }
    
    if (class_exists('EduBot_API_Integrations')) {
        echo "<p>✅ EduBot_API_Integrations class loaded</p>";
        
        // Test connection
        $api = new EduBot_API_Integrations();
        $test_result = $api->test_whatsapp_connection($whatsapp_token, $whatsapp_provider, $whatsapp_phone_id);
        
        if ($test_result && isset($test_result['success'])) {
            if ($test_result['success']) {
                echo "<p>✅ <strong>WhatsApp API Connection:</strong> " . esc_html($test_result['message']) . "</p>";
                
                // Test sending a message (optional - uncomment and add your test phone number)
                /*
                $test_phone = '+919999999999'; // Replace with your test phone number
                $test_message = "🧪 Test message from EduBot Pro WhatsApp integration\n\nThis is a test to verify WhatsApp messaging is working correctly.\n\nTime: " . date('Y-m-d H:i:s');
                
                echo "<h3>Sending Test Message</h3>";
                $send_result = $api->send_whatsapp($test_phone, $test_message);
                
                if ($send_result && !is_wp_error($send_result)) {
                    echo "<p>✅ Test message sent successfully to {$test_phone}</p>";
                } else {
                    $error_msg = is_wp_error($send_result) ? $send_result->get_error_message() : 'Unknown error';
                    echo "<p>❌ Failed to send test message: " . esc_html($error_msg) . "</p>";
                }
                */
                
            } else {
                echo "<p>❌ <strong>WhatsApp API Connection Failed:</strong> " . esc_html($test_result['message']) . "</p>";
            }
        } else {
            echo "<p>❌ WhatsApp API test failed - invalid response</p>";
        }
    } else {
        echo "<p>❌ EduBot_API_Integrations class not available</p>";
    }
} else {
    echo "<h2>WhatsApp Configuration Required</h2>";
    echo "<p>To enable WhatsApp messaging:</p>";
    echo "<ol>";
    echo "<li>Go to <strong>EduBot Pro > School Settings</strong> and enable 'WhatsApp Notifications'</li>";
    echo "<li>Go to <strong>EduBot Pro > API Integrations</strong> and configure:</li>";
    echo "<ul>";
    echo "<li>WhatsApp Provider (Meta or Twilio)</li>";
    echo "<li>Access Token</li>";
    echo "<li>Phone ID (for Meta) or Account SID (for Twilio)</li>";
    echo "</ul>";
    echo "<li>Test the connection using the 'Test WhatsApp' button</li>";
    echo "</ol>";
}

// School branding test
echo "<h2>School Branding Configuration</h2>";
$school_name = get_option('edubot_school_name', '');
$school_logo = get_option('edubot_school_logo', '');
$primary_color = get_option('edubot_primary_color', '#4facfe');
$secondary_color = get_option('edubot_secondary_color', '#00f2fe');

echo "<p><strong>School Name:</strong> " . ($school_name ? esc_html($school_name) : '<em>Not configured</em>') . "</p>";
echo "<p><strong>School Logo:</strong> " . ($school_logo ? '✅ Configured' : '❌ Not configured') . "</p>";
if ($school_logo) {
    echo "<div style='margin: 10px 0;'><img src='" . esc_url($school_logo) . "' style='max-width: 200px; max-height: 100px; border: 1px solid #ddd; padding: 5px;' alt='School Logo' /></div>";
}
echo "<p><strong>Primary Color:</strong> <span style='background-color: " . esc_attr($primary_color) . "; color: white; padding: 3px 8px; border-radius: 3px;'>" . esc_html($primary_color) . "</span></p>";
echo "<p><strong>Secondary Color:</strong> <span style='background-color: " . esc_attr($secondary_color) . "; color: white; padding: 3px 8px; border-radius: 3px;'>" . esc_html($secondary_color) . "</span></p>";

echo "<h2>Summary</h2>";
$all_configured = $whatsapp_enabled && $whatsapp_token && $whatsapp_provider && $school_name;

if ($all_configured) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "<h3>✅ WhatsApp Integration Ready</h3>";
    echo "<p>Your EduBot Pro is configured for WhatsApp messaging. Parents will receive WhatsApp confirmations when they submit admission enquiries.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>⚠️ Configuration Needed</h3>";
    echo "<p>Complete the WhatsApp and branding configuration in the admin panel to enable full functionality.</p>";
    echo "</div>";
}

echo "<hr><p><em>Generated on: " . date('Y-m-d H:i:s') . "</em></p>";
?>
