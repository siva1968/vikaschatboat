<?php
/**
 * Test WhatsApp Integration After Method Visibility Fix
 * This file tests if the send_meta_whatsapp method can now be called properly
 */

// WordPress environment setup (if running outside WordPress)
if (!defined('ABSPATH')) {
    // Simulate WordPress environment for testing
    define('ABSPATH', '/home/epistemo-stage/htdocs/stage.epistemo.in/');
    
    // Include WordPress configuration
    require_once(ABSPATH . 'wp-config.php');
    require_once(ABSPATH . 'wp-includes/wp-db.php');
    require_once(ABSPATH . 'wp-includes/pluggable.php');
}

// Include the API integrations class
require_once(__DIR__ . '/includes/class-api-integrations.php');

echo "=== WhatsApp Integration Test After Method Visibility Fix ===\n\n";

// Test 1: Check if the class can be instantiated
try {
    $api_integrations = new EduBot_API_Integrations();
    echo "✅ EduBot_API_Integrations class instantiated successfully\n";
} catch (Exception $e) {
    echo "❌ Failed to instantiate EduBot_API_Integrations: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check method visibility
echo "\n=== Method Visibility Test ===\n";
$reflection = new ReflectionClass('EduBot_API_Integrations');

try {
    $method = $reflection->getMethod('send_meta_whatsapp');
    
    if ($method->isPublic()) {
        echo "✅ send_meta_whatsapp method is PUBLIC - can be called from other classes\n";
    } elseif ($method->isPrivate()) {
        echo "❌ send_meta_whatsapp method is PRIVATE - cannot be called from other classes\n";
    } elseif ($method->isProtected()) {
        echo "⚠️ send_meta_whatsapp method is PROTECTED - can only be called from subclasses\n";
    }
    
    echo "Method signature: " . $method->getName() . "\n";
    
    // Get method parameters
    $parameters = $method->getParameters();
    echo "Parameters: ";
    foreach ($parameters as $param) {
        echo '$' . $param->getName() . ' ';
    }
    echo "\n";
    
} catch (ReflectionException $e) {
    echo "❌ Method send_meta_whatsapp not found: " . $e->getMessage() . "\n";
}

// Test 3: Simulate the method call that was failing
echo "\n=== Method Call Simulation ===\n";

// Get WordPress options for WhatsApp configuration
$whatsapp_enabled = get_option('edubot_whatsapp_notifications_enabled', false);
$whatsapp_provider = get_option('edubot_whatsapp_provider', '');
$whatsapp_token = get_option('edubot_whatsapp_token', '');
$phone_id = get_option('edubot_whatsapp_phone_id', '');

echo "WhatsApp Notifications Enabled: " . ($whatsapp_enabled ? 'YES' : 'NO') . "\n";
echo "WhatsApp Provider: " . $whatsapp_provider . "\n";
echo "WhatsApp Token: " . (strlen($whatsapp_token) > 0 ? 'CONFIGURED (' . strlen($whatsapp_token) . ' chars)' : 'NOT SET') . "\n";
echo "Phone Number ID: " . ($phone_id ? $phone_id : 'NOT SET') . "\n";

if ($whatsapp_enabled && $whatsapp_provider === 'meta' && $whatsapp_token && $phone_id) {
    echo "\n✅ All WhatsApp configuration is present\n";
    
    // Test the template message format that should work
    $test_phone = "919866133566"; // Test phone number
    $template_message = array(
        'type' => 'template',
        'template' => array(
            'name' => 'admission_confirmation',
            'language' => array('code' => 'en'),
            'components' => array(
                array(
                    'type' => 'body',
                    'parameters' => array(
                        array('type' => 'text', 'text' => 'Test Parent'),
                        array('type' => 'text', 'text' => 'TEST123'),
                        array('type' => 'text', 'text' => 'Epistemo School'),
                        array('type' => 'text', 'text' => 'Grade 5'),
                        array('type' => 'text', 'text' => date('d/m/Y'))
                    )
                )
            )
        )
    );
    
    $api_keys = array(
        'whatsapp_token' => $whatsapp_token,
        'whatsapp_phone_id' => $phone_id
    );
    
    echo "\n=== Testing Method Call ===\n";
    
    try {
        // This should now work since we made the method public
        $result = $api_integrations->send_meta_whatsapp($test_phone, $template_message, $api_keys);
        
        if ($result) {
            echo "✅ WhatsApp method called successfully - returned TRUE\n";
            echo "Message should be sent to WhatsApp number: $test_phone\n";
        } else {
            echo "⚠️ WhatsApp method called but returned FALSE - check API response\n";
        }
        
    } catch (Error $e) {
        echo "❌ Fatal Error: " . $e->getMessage() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        echo "File: " . $e->getFile() . "\n";
    } catch (Exception $e) {
        echo "⚠️ Exception: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "\n❌ WhatsApp configuration is incomplete\n";
    echo "Please ensure all required settings are configured in WordPress admin.\n";
}

echo "\n=== Test Complete ===\n";
echo "If you see '✅ WhatsApp method called successfully', the fix is working!\n";
echo "The chatbot should now be able to send WhatsApp messages without Fatal Errors.\n";

?>
