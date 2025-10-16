<?php
/**
 * EduBot Email & Enquiry Number Validation Script
 * Run this script to verify the fixes are working correctly
 */

// Check if WordPress is loaded
if (!defined('ABSPATH')) {
    echo "❌ ERROR: WordPress not loaded. Please run this script from within WordPress.\n";
    exit;
}

echo "====================================================\n";
echo "EduBot Email & Enquiry Number Validation Report\n";
echo "====================================================\n\n";

$all_good = true;

// Test 1: Check if class exists
echo "Test 1: Checking if EduBot_Shortcode class exists...\n";
if (class_exists('EduBot_Shortcode')) {
    echo "✅ PASS: EduBot_Shortcode class found\n\n";
} else {
    echo "❌ FAIL: EduBot_Shortcode class not found\n\n";
    $all_good = false;
}

// Test 2: Check if method exists
echo "Test 2: Checking if build_parent_confirmation_html method exists...\n";
if (method_exists('EduBot_Shortcode', 'build_parent_confirmation_html')) {
    echo "✅ PASS: build_parent_confirmation_html method found\n\n";
} else {
    echo "❌ FAIL: build_parent_confirmation_html method not found\n\n";
    $all_good = false;
}

// Test 3: Check WordPress options
echo "Test 3: Checking WordPress options for school settings...\n";
$checks = array(
    'edubot_school_email' => 'School Email',
    'edubot_school_phone' => 'School Phone',
    'edubot_primary_color' => 'Primary Color',
    'edubot_email_notifications' => 'Email Notifications',
);

foreach ($checks as $option => $label) {
    $value = get_option($option);
    if ($value !== false) {
        echo "✅ {$label}: " . esc_html($value) . "\n";
    } else {
        echo "⚠️  {$label}: Not set (using default)\n";
    }
}
echo "\n";

// Test 4: Check database table
echo "Test 4: Checking database structure...\n";
global $wpdb;
$table_name = $wpdb->prefix . 'enquiries';
$charset_collate = $wpdb->get_charset_collate();

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
if ($table_exists) {
    echo "✅ PASS: Enquiries table exists\n";
    
    // Check for required columns
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    $required_columns = array(
        'id', 
        'enquiry_number', 
        'student_name', 
        'email', 
        'phone', 
        'email_sent', 
        'whatsapp_sent'
    );
    
    $column_names = array_column($columns, 'Field');
    $missing_columns = array_diff($required_columns, $column_names);
    
    if (empty($missing_columns)) {
        echo "✅ All required columns present\n";
    } else {
        echo "❌ Missing columns: " . implode(', ', $missing_columns) . "\n";
        $all_good = false;
    }
} else {
    echo "❌ FAIL: Enquiries table does not exist\n";
    $all_good = false;
}
echo "\n";

// Test 5: Check recent enquiries
echo "Test 5: Checking recent enquiries...\n";
$recent_enquiries = $wpdb->get_results(
    "SELECT enquiry_number, student_name, email, email_sent, whatsapp_sent, created_at 
     FROM {$wpdb->prefix}enquiries 
     ORDER BY created_at DESC 
     LIMIT 5"
);

if (!empty($recent_enquiries)) {
    echo "✅ Found " . count($recent_enquiries) . " recent enquiries:\n";
    foreach ($recent_enquiries as $enquiry) {
        echo "\n  Enquiry #: " . esc_html($enquiry->enquiry_number) . "\n";
        echo "  Student: " . esc_html($enquiry->student_name) . "\n";
        echo "  Email: " . esc_html($enquiry->email) . "\n";
        echo "  Email Sent: " . ($enquiry->email_sent ? '✅ Yes' : '❌ No') . "\n";
        echo "  WhatsApp Sent: " . ($enquiry->whatsapp_sent ? '✅ Yes' : '❌ No') . "\n";
        echo "  Created: " . esc_html($enquiry->created_at) . "\n";
    }
} else {
    echo "⚠️  No enquiries found in database\n";
}
echo "\n";

// Test 6: Simulate email building
echo "Test 6: Testing email template building...\n";
try {
    $edubot = new EduBot_Shortcode();
    
    // Create test data
    $test_data = array(
        'student_name' => 'Test Student',
        'grade' => '10',
        'board' => 'ICSE',
        'email' => 'test@example.com',
        'phone' => '9876543210',
        'date_of_birth' => '2010-01-15',
    );
    
    // Call the method (using reflection to access private method)
    $reflection = new ReflectionClass('EduBot_Shortcode');
    $method = $reflection->getMethod('build_parent_confirmation_html');
    $method->setAccessible(true);
    
    $html = $method->invoke($edubot, $test_data, 'ENQ202501001', 'Test School');
    
    if (strpos($html, 'ENQ202501001') !== false) {
        echo "✅ PASS: Email template contains enquiry number\n";
    } else {
        echo "❌ FAIL: Email template does not contain enquiry number\n";
        $all_good = false;
    }
    
    if (strpos($html, 'Test Student') !== false) {
        echo "✅ PASS: Email template contains student name\n";
    } else {
        echo "❌ FAIL: Email template does not contain student name\n";
        $all_good = false;
    }
    
    if (strpos($html, 'grade') !== false || strpos($html, 'Grade') !== false) {
        echo "✅ PASS: Email template contains grade information\n";
    } else {
        echo "❌ FAIL: Email template missing grade information\n";
        $all_good = false;
    }
    
} catch (Exception $e) {
    echo "❌ FAIL: Error testing email template: " . esc_html($e->getMessage()) . "\n";
    $all_good = false;
}
echo "\n";

// Test 7: Check for PHP errors
echo "Test 7: Checking for PHP errors in plugin files...\n";
$files_to_check = array(
    WP_PLUGIN_DIR . '/AI ChatBoat/includes/class-edubot-shortcode.php',
    WP_PLUGIN_DIR . '/AI ChatBoat/admin/class-edubot-admin.php',
);

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check for common issues
        if (strpos($content, '$settings[') !== false && strpos($content, '$settings =') === false) {
            echo "⚠️  " . basename($file) . ": May have undefined \$settings array reference\n";
        } else {
            echo "✅ " . basename($file) . ": No obvious \$settings issues\n";
        }
    }
}
echo "\n";

// Final Summary
echo "====================================================\n";
if ($all_good) {
    echo "✅ ALL TESTS PASSED\n";
    echo "The email and enquiry number fixes appear to be working correctly.\n";
} else {
    echo "❌ SOME TESTS FAILED\n";
    echo "Please review the issues above and check the error logs.\n";
}
echo "====================================================\n";
echo "\nFor detailed troubleshooting, check:\n";
echo "- WordPress Error Log: " . WP_CONTENT_DIR . "/debug.log\n";
echo "- This script: " . __FILE__ . "\n";
echo "\n";

?>
