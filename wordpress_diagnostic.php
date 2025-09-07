<?php
/**
 * WordPress WhatsApp Settings Diagnostic Script
 * Upload this to your WordPress root directory and run once to diagnose the save issue
 */

// Include WordPress
require_once('wp-config.php');
require_once('wp-includes/functions.php');

echo "=== EduBot WhatsApp Settings Diagnostic ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "===============================================\n\n";

// Test 1: Check if we can connect to WordPress
echo "Step 1: WordPress Connection Test\n";
echo "==================================\n";
if (function_exists('get_option')) {
    echo "✅ WordPress functions available\n";
} else {
    echo "❌ WordPress functions NOT available\n";
    exit;
}

// Test 2: Check current WhatsApp settings
echo "\nStep 2: Current WhatsApp Settings\n";
echo "==================================\n";
$current_settings = array(
    'whatsapp_provider' => get_option('edubot_whatsapp_provider', 'not_set'),
    'whatsapp_token' => get_option('edubot_whatsapp_token', 'not_set'),
    'whatsapp_phone_id' => get_option('edubot_whatsapp_phone_id', 'not_set'),
    'whatsapp_template_namespace' => get_option('edubot_whatsapp_template_namespace', 'not_set'),
    'whatsapp_template_name' => get_option('edubot_whatsapp_template_name', 'not_set'),
);

foreach ($current_settings as $key => $value) {
    $display_value = ($key === 'whatsapp_token' && $value !== 'not_set') 
        ? substr($value, 0, 20) . '...' 
        : $value;
    echo "📋 {$key}: {$display_value}\n";
}

// Test 3: Try to save the new token
echo "\nStep 3: Attempting to Save New Token\n";
echo "=====================================\n";

$new_token = 'EAASeCKYjY2sBPfLljPAnLtWsXwUzCzPZAd92PfUIqaScZAFjpM9fK3UhLzxxt4OhgzLYpRpWZAlmVjZCSpTV19FcJXRZALTtHlbtjCqNfp5BLdLmXZBzW90c4v4REIko62w6QguwNMWXN1qITGK9D1su8YeILdogvDPeJTOIjdBrC2VgnzKKOLWKOAOT2n2wZDZD';
$new_settings = array(
    'edubot_whatsapp_provider' => 'meta',
    'edubot_whatsapp_token' => $new_token,
    'edubot_whatsapp_phone_id' => '614525638411206',
    'edubot_whatsapp_template_namespace' => '9eb1f1dc_68e7_42f1_802a_dbc7582c5c3a',
    'edubot_whatsapp_template_name' => 'admission_confirmation',
    'edubot_whatsapp_template_language' => 'en',
    'edubot_whatsapp_use_templates' => '1'
);

$save_results = array();
foreach ($new_settings as $option_name => $option_value) {
    $result = update_option($option_name, $option_value);
    $save_results[$option_name] = $result;
    
    if ($result) {
        echo "✅ Saved: {$option_name}\n";
    } else {
        echo "❌ Failed: {$option_name}\n";
        
        // Check if option already exists with same value
        $current_value = get_option($option_name);
        if ($current_value === $option_value) {
            echo "   ℹ️  Option already has this value\n";
        }
    }
}

// Test 4: Verify the saves worked
echo "\nStep 4: Verification of Saved Settings\n";
echo "======================================\n";
foreach ($new_settings as $option_name => $expected_value) {
    $actual_value = get_option($option_name);
    $display_value = ($option_name === 'edubot_whatsapp_token') 
        ? substr($actual_value, 0, 20) . '...' 
        : $actual_value;
    
    if ($actual_value === $expected_value) {
        echo "✅ {$option_name}: {$display_value}\n";
    } else {
        echo "❌ {$option_name}: Expected vs Actual mismatch\n";
        echo "   Expected: " . substr($expected_value, 0, 50) . "...\n";
        echo "   Actual: " . substr($actual_value, 0, 50) . "...\n";
    }
}

// Test 5: Check database permissions
echo "\nStep 5: Database Write Test\n";
echo "===========================\n";
$test_option_name = 'edubot_diagnostic_test_' . time();
$test_value = 'diagnostic_test_value_' . rand(1000, 9999);

if (update_option($test_option_name, $test_value)) {
    echo "✅ Database write permissions OK\n";
    delete_option($test_option_name); // Clean up
} else {
    echo "❌ Database write permissions FAILED\n";
}

// Test 6: Check for plugin conflicts
echo "\nStep 6: Plugin Environment Check\n";
echo "=================================\n";
if (function_exists('is_plugin_active')) {
    echo "✅ Plugin functions available\n";
    
    // Check if EduBot Pro is active
    if (is_plugin_active('edubot-pro/edubot-pro.php')) {
        echo "✅ EduBot Pro plugin is active\n";
    } else {
        echo "❌ EduBot Pro plugin is NOT active\n";
    }
} else {
    echo "❌ Plugin functions NOT available\n";
}

echo "\n=== Diagnostic Complete ===\n";
echo "If all steps show ✅, the token should be saved successfully.\n";
echo "If there are ❌ errors, those indicate the problem area.\n";
echo "\nPlease delete this file after reviewing results!\n";
?>
