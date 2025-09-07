<?php
/**
 * Test WhatsApp and SMS Integration with Backend Settings
 * This test verifies that WhatsApp and SMS confirmations work with the existing backend configuration
 */

// Load WordPress
require_once('wp-config.php');

function test_whatsapp_sms_integration() {
    echo "<h2>📱 WhatsApp & SMS Integration Test with Backend Settings</h2>\n";
    
    // Test data
    $test_enquiry_data = array(
        'student_name' => 'Arjun Sharma',
        'phone' => '9876543210',
        'email' => 'test.parent@example.com',
        'grade' => 'Grade 9',
        'board' => 'CBSE',
        'academic_year' => '2026-27'
    );
    
    $test_enquiry_number = 'TEST-ENQ-' . date('Y') . '-' . rand(1000, 9999);
    $school_name = 'Epistemo Vikas Leadership School';
    
    echo "<h3>📋 Test Enquiry Data:</h3>\n";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<strong>Student:</strong> {$test_enquiry_data['student_name']}<br>\n";
    echo "<strong>Phone:</strong> {$test_enquiry_data['phone']}<br>\n";
    echo "<strong>Email:</strong> {$test_enquiry_data['email']}<br>\n";
    echo "<strong>Grade:</strong> {$test_enquiry_data['grade']}<br>\n";
    echo "<strong>Enquiry Number:</strong> {$test_enquiry_number}<br>\n";
    echo "</div>\n";
    
    try {
        // Check if EduBot_Shortcode class exists
        if (!class_exists('EduBot_Shortcode')) {
            echo "❌ EduBot_Shortcode class not available<br>\n";
            return;
        }
        
        // Create shortcode instance
        $edubot_shortcode = new EduBot_Shortcode();
        
        // Test WhatsApp Configuration Reading
        echo "<h3>🔧 Backend Configuration Status:</h3>\n";
        
        // Check WhatsApp settings
        $whatsapp_provider = get_option('edubot_whatsapp_provider', '');
        $whatsapp_token = get_option('edubot_whatsapp_token', '');
        $whatsapp_phone_id = get_option('edubot_whatsapp_phone_id', '');
        
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h4>📱 WhatsApp Configuration:</h4>\n";
        echo "<strong>Provider:</strong> " . ($whatsapp_provider ?: 'Not set') . "<br>\n";
        echo "<strong>Token:</strong> " . ($whatsapp_token ? 'Set (' . strlen($whatsapp_token) . ' chars)' : 'Not set') . "<br>\n";
        echo "<strong>Phone ID:</strong> " . ($whatsapp_phone_id ?: 'Not set') . "<br>\n";
        
        if ($whatsapp_provider && $whatsapp_token) {
            echo "<span style='color: #28a745;'>✅ WhatsApp configuration appears complete</span><br>\n";
        } else {
            echo "<span style='color: #dc3545;'>❌ WhatsApp configuration incomplete</span><br>\n";
        }
        echo "</div>\n";
        
        // Check SMS settings
        $sms_provider = get_option('edubot_sms_provider', '');
        $sms_api_key = get_option('edubot_sms_api_key', '');
        $sms_sender_id = get_option('edubot_sms_sender_id', '');
        
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h4>📧 SMS Configuration:</h4>\n";
        echo "<strong>Provider:</strong> " . ($sms_provider ?: 'Not set') . "<br>\n";
        echo "<strong>API Key:</strong> " . ($sms_api_key ? 'Set (' . strlen($sms_api_key) . ' chars)' : 'Not set') . "<br>\n";
        echo "<strong>Sender ID:</strong> " . ($sms_sender_id ?: 'Not set') . "<br>\n";
        
        if ($sms_provider && $sms_api_key) {
            echo "<span style='color: #28a745;'>✅ SMS configuration appears complete</span><br>\n";
        } else {
            echo "<span style='color: #dc3545;'>❌ SMS configuration incomplete</span><br>\n";
        }
        echo "</div>\n";
        
        // Test configuration retrieval methods (using reflection to access private methods)
        $reflection = new ReflectionClass($edubot_shortcode);
        
        // Test WhatsApp configuration method
        $whatsapp_config_method = $reflection->getMethod('get_whatsapp_configuration');
        $whatsapp_config_method->setAccessible(true);
        $whatsapp_config = $whatsapp_config_method->invoke($edubot_shortcode);
        
        echo "<h3>🔍 Parsed Configuration:</h3>\n";
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h4>📱 WhatsApp Config Parsed:</h4>\n";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px; font-size: 12px;'>";
        echo json_encode($whatsapp_config, JSON_PRETTY_PRINT);
        echo "</pre>\n";
        echo "</div>\n";
        
        // Test SMS configuration method
        $sms_config_method = $reflection->getMethod('get_sms_configuration');
        $sms_config_method->setAccessible(true);
        $sms_config = $sms_config_method->invoke($edubot_shortcode);
        
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h4>📧 SMS Config Parsed:</h4>\n";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px; font-size: 12px;'>";
        echo json_encode($sms_config, JSON_PRETTY_PRINT);
        echo "</pre>\n";
        echo "</div>\n";
        
        // Test comprehensive confirmation (this will attempt to send real notifications)
        echo "<h3>🚀 Live Notification Test:</h3>\n";
        echo "<div style='background: #ffecb3; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<strong>⚠️ Warning:</strong> This will attempt to send real WhatsApp and SMS messages if configured!<br>\n";
        echo "Make sure your test phone number is your own number.<br><br>\n";
        
        // Test comprehensive confirmation
        $comprehensive_method = $reflection->getMethod('send_comprehensive_confirmation');
        $comprehensive_method->setAccessible(true);
        $notification_result = $comprehensive_method->invoke($edubot_shortcode, $test_enquiry_data, $test_enquiry_number, $school_name);
        
        echo "<h4>📊 Notification Results:</h4>\n";
        echo "<strong>Email Sent:</strong> " . ($notification_result['email_sent'] ? '✅ Yes' : '❌ No') . "<br>\n";
        echo "<strong>WhatsApp Sent:</strong> " . ($notification_result['whatsapp_sent'] ? '✅ Yes' : '❌ No') . "<br>\n";
        echo "<strong>SMS Sent:</strong> " . ($notification_result['sms_sent'] ? '✅ Yes' : '❌ No') . "<br>\n";
        echo "<strong>Total Notifications:</strong> {$notification_result['notifications_sent']}<br>\n";
        echo "</div>\n";
        
        // Test individual components if comprehensive failed
        if ($notification_result['notifications_sent'] == 0) {
            echo "<h3>🔧 Individual Component Tests:</h3>\n";
            
            // Test phone formatting
            $format_phone_method = $reflection->getMethod('format_phone_for_whatsapp');
            $format_phone_method->setAccessible(true);
            $formatted_phone = $format_phone_method->invoke($edubot_shortcode, $test_enquiry_data['phone']);
            
            echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
            echo "<strong>Phone Formatting Test:</strong><br>\n";
            echo "Original: {$test_enquiry_data['phone']}<br>\n";
            echo "Formatted: " . ($formatted_phone ?: 'Failed') . "<br>\n";
            echo "</div>\n";
            
            // Test WhatsApp message building
            if (method_exists($edubot_shortcode, 'build_whatsapp_confirmation_message')) {
                $build_message_method = $reflection->getMethod('build_whatsapp_confirmation_message');
                $build_message_method->setAccessible(true);
                $whatsapp_message = $build_message_method->invoke($edubot_shortcode, $test_enquiry_data, $test_enquiry_number, $school_name);
                
                echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
                echo "<strong>WhatsApp Message Preview:</strong><br>\n";
                echo "<pre style='white-space: pre-wrap; background: #fff; padding: 10px; border: 1px solid #ddd;'>";
                echo esc_html($whatsapp_message);
                echo "</pre>\n";
                echo "</div>\n";
            }
        }
        
        echo "<h3>✅ Integration Test Completed!</h3>\n";
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
        echo "<h4>📋 Test Summary:</h4>\n";
        echo "<ul>\n";
        echo "<li>✅ Backend configuration reading: Successful</li>\n";
        echo "<li>✅ WhatsApp config parsing: " . ($whatsapp_config['enabled'] ? 'Enabled' : 'Disabled') . "</li>\n";
        echo "<li>✅ SMS config parsing: " . ($sms_config['enabled'] ? 'Enabled' : 'Disabled') . "</li>\n";
        echo "<li>✅ Multi-channel notification: " . ($notification_result['notifications_sent'] > 0 ? 'Successful' : 'Check configuration') . "</li>\n";
        echo "</ul>\n";
        
        echo "<h4>🚀 Next Steps:</h4>\n";
        echo "<ol>\n";
        echo "<li>Configure WhatsApp/SMS providers in <strong>EduBot Pro → API Integrations</strong></li>\n";
        echo "<li>Test with real phone numbers</li>\n";
        echo "<li>Monitor logs for any delivery issues</li>\n";
        echo "<li>Customize message templates as needed</li>\n";
        echo "</ol>\n";
        echo "</div>\n";
        
    } catch (Exception $e) {
        echo "❌ Exception during test: " . $e->getMessage() . "<br>\n";
        echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
    }
}

// Run the test
echo "<style>body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 40px; } pre { background: #f8f9fa; padding: 10px; }</style>\n";
test_whatsapp_sms_integration();
?>
