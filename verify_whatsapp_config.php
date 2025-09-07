<?php
/**
 * WhatsApp Configuration Verification Script
 * This script verifies that all WhatsApp Business API settings are properly configured
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

echo "<h1>📱 WhatsApp Business API Configuration Verification</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;'>";

// Check basic WhatsApp settings
echo "<h2>🔧 Basic WhatsApp Settings</h2>";
$whatsapp_enabled = get_option('edubot_whatsapp_notifications', 0);
$whatsapp_provider = get_option('edubot_whatsapp_provider', '');
$whatsapp_token = get_option('edubot_whatsapp_token', '');
$whatsapp_phone_id = get_option('edubot_whatsapp_phone_id', '');

echo "<table border='1' cellpadding='8' cellspacing='0' style='width: 100%; border-collapse: collapse;'>";
echo "<tr><td><strong>WhatsApp Notifications</strong></td><td>" . ($whatsapp_enabled ? '✅ Enabled' : '❌ Disabled') . "</td></tr>";
echo "<tr><td><strong>Provider</strong></td><td>" . ($whatsapp_provider ? esc_html($whatsapp_provider) : '❌ Not set') . "</td></tr>";
echo "<tr><td><strong>Access Token</strong></td><td>" . ($whatsapp_token ? '✅ Configured (' . strlen($whatsapp_token) . ' chars)' : '❌ Not set') . "</td></tr>";
echo "<tr><td><strong>Phone Number ID</strong></td><td>" . ($whatsapp_phone_id ? esc_html($whatsapp_phone_id) : '❌ Not set') . "</td></tr>";
echo "</table>";

// Check WhatsApp Business API Template Settings
echo "<h2>📋 WhatsApp Business API Template Settings</h2>";
$template_enabled = get_option('edubot_whatsapp_use_template', 0);
$template_name = get_option('edubot_whatsapp_template_name', '');
$template_language = get_option('edubot_whatsapp_template_language', '');
$template_content = get_option('edubot_whatsapp_template', '');

echo "<table border='1' cellpadding='8' cellspacing='0' style='width: 100%; border-collapse: collapse;'>";
echo "<tr><td><strong>Use Business API Template</strong></td><td>" . ($template_enabled ? '✅ Enabled' : '❌ Disabled (Free-form messaging)') . "</td></tr>";
echo "<tr><td><strong>Template Name</strong></td><td>" . ($template_name ? esc_html($template_name) : '❌ Not set') . "</td></tr>";
echo "<tr><td><strong>Template Language</strong></td><td>" . ($template_language ? esc_html($template_language) : '❌ Not set') . "</td></tr>";
echo "<tr><td><strong>Template Content</strong></td><td>" . ($template_content ? '✅ Configured (' . strlen($template_content) . ' chars)' : '❌ Not set') . "</td></tr>";
echo "</table>";

// Display current template content
if ($template_content) {
    echo "<h3>📝 Current Template Content</h3>";
    echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; border-left: 4px solid #00a884;'>";
    echo "<pre style='margin: 0; font-family: monospace; white-space: pre-wrap;'>" . esc_html($template_content) . "</pre>";
    echo "</div>";
    
    // Show available placeholders
    echo "<h4>Available Placeholders:</h4>";
    echo "<ul>";
    echo "<li><code>{{student_name}}</code> - Student's name</li>";
    echo "<li><code>{{school_name}}</code> - School name</li>";
    echo "<li><code>{{enquiry_number}}</code> - Enquiry reference number</li>";
    echo "<li><code>{{grade}}</code> - Selected grade</li>";
    echo "<li><code>{{enquiry_date}}</code> - Date of enquiry</li>";
    echo "</ul>";
}

// Check school branding
echo "<h2>🎨 School Branding Settings</h2>";
$school_name = get_option('edubot_school_name', '');
$school_logo = get_option('edubot_school_logo', '');

echo "<table border='1' cellpadding='8' cellspacing='0' style='width: 100%; border-collapse: collapse;'>";
echo "<tr><td><strong>School Name</strong></td><td>" . ($school_name ? esc_html($school_name) : '❌ Not set') . "</td></tr>";
echo "<tr><td><strong>School Logo</strong></td><td>" . ($school_logo ? '✅ Configured' : '❌ Not set') . "</td></tr>";
echo "</table>";

// Overall status
echo "<h2>📊 Configuration Status</h2>";

$basic_config = $whatsapp_enabled && $whatsapp_provider && $whatsapp_token && $whatsapp_phone_id;
$template_config = !$template_enabled || ($template_enabled && $template_name && $template_language && $template_content);
$branding_config = $school_name;

if ($basic_config && $template_config && $branding_config) {
    echo "<div style='background: #d1f2eb; border: 2px solid #00a884; padding: 20px; border-radius: 8px; text-align: center;'>";
    echo "<h3 style='color: #00a884; margin: 0 0 10px 0;'>🎉 WhatsApp Integration Fully Configured!</h3>";
    echo "<p style='margin: 0; font-size: 16px;'>Your EduBot Pro is ready to send WhatsApp messages to parents.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #fdeaea; border: 2px solid #e74c3c; padding: 20px; border-radius: 8px;'>";
    echo "<h3 style='color: #e74c3c; margin: 0 0 10px 0;'>⚠️ Configuration Issues Found</h3>";
    echo "<ul style='margin: 10px 0 0 0;'>";
    
    if (!$basic_config) {
        echo "<li>Complete basic WhatsApp settings (Provider, Token, Phone ID)</li>";
    }
    if (!$template_config) {
        echo "<li>Configure WhatsApp Business API template settings</li>";
    }
    if (!$branding_config) {
        echo "<li>Set up school name in branding settings</li>";
    }
    
    echo "</ul>";
    echo "</div>";
}

// Test sample message generation
if ($basic_config && $template_config && $branding_config) {
    echo "<h2>🧪 Sample Message Preview</h2>";
    
    // Sample data
    $sample_data = [
        'student_name' => 'Sujay',
        'school_name' => $school_name,
        'enquiry_number' => 'EQ' . date('Ymd') . '001',
        'grade' => 'Grade 1',
        'enquiry_date' => date('d/m/Y')
    ];
    
    if ($template_enabled && $template_content) {
        // Generate template message
        $message = $template_content;
        foreach ($sample_data as $key => $value) {
            $message = str_replace('{{' . $key . '}}', $value, $message);
        }
        
        echo "<h4>📱 Template Message Output:</h4>";
        echo "<div style='background: #e8f5e8; border: 1px solid #25d366; padding: 15px; border-radius: 8px; font-family: monospace; white-space: pre-wrap;'>";
        echo esc_html($message);
        echo "</div>";
        
        if ($template_enabled) {
            echo "<p><strong>Note:</strong> This will be sent as a WhatsApp Business API Template Message with name: <code>" . esc_html($template_name) . "</code></p>";
        }
    } else {
        echo "<p>❌ Template not configured for preview</p>";
    }
}

echo "<hr>";
echo "<p style='text-align: center; color: #666; font-size: 12px;'>";
echo "Generated on: " . date('Y-m-d H:i:s') . " | ";
echo "EduBot Pro WhatsApp Integration Verification";
echo "</p>";

echo "</div>";
?>
