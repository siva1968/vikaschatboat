<?php
/**
 * EduBot Database Diagnostic Tool
 * Run this file to check if the database tables exist and are working properly
 */

// Include WordPress
$wp_config_path = '';
$current_dir = __DIR__;
while (!file_exists($current_dir . '/wp-config.php') && $current_dir !== '/') {
    $current_dir = dirname($current_dir);
}

if (file_exists($current_dir . '/wp-config.php')) {
    require_once($current_dir . '/wp-config.php');
    require_once($current_dir . '/wp-includes/wp-db.php');
} else {
    die('WordPress configuration not found. Please run this file from your WordPress directory.');
}

global $wpdb;

echo "<h2>EduBot Pro Database Diagnostic</h2>\n";
echo "<p>Checking database tables and functionality...</p>\n";

// Check if tables exist
$tables = [
    'edubot_school_configs',
    'edubot_applications', 
    'edubot_analytics',
    'edubot_sessions',
    'edubot_security_log',
    'edubot_visitor_analytics',
    'edubot_visitors'
];

echo "<h3>1. Checking Table Existence</h3>\n";
$missing_tables = [];
foreach ($tables as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
    if ($exists) {
        echo "✅ {$full_table_name} - EXISTS<br>\n";
    } else {
        echo "❌ {$full_table_name} - MISSING<br>\n";
        $missing_tables[] = $full_table_name;
    }
}

if (!empty($missing_tables)) {
    echo "<p><strong>Missing tables found!</strong> Please run the plugin activation to create them:</p>\n";
    echo "<pre>deactivate_plugins('edubot-pro/edubot-pro.php');\nactivate_plugin('edubot-pro/edubot-pro.php');</pre>\n";
}

// Check EduBot classes
echo "<h3>2. Checking Plugin Classes</h3>\n";
$plugin_path = dirname(__FILE__);
$include_files = [
    'includes/class-edubot-autoloader.php',
    'includes/class-database-manager.php', 
    'includes/class-school-config.php'
];

foreach ($include_files as $file) {
    $full_path = $plugin_path . '/' . $file;
    if (file_exists($full_path)) {
        echo "✅ {$file} - EXISTS<br>\n";
        require_once($full_path);
    } else {
        echo "❌ {$file} - MISSING<br>\n";
    }
}

// Check if classes can be instantiated
echo "<h3>3. Checking Class Functionality</h3>\n";

if (class_exists('EduBot_Database_Manager')) {
    echo "✅ EduBot_Database_Manager class - AVAILABLE<br>\n";
    try {
        $db_manager = new EduBot_Database_Manager();
        echo "✅ EduBot_Database_Manager instantiation - SUCCESS<br>\n";
    } catch (Exception $e) {
        echo "❌ EduBot_Database_Manager instantiation - FAILED: " . $e->getMessage() . "<br>\n";
    }
} else {
    echo "❌ EduBot_Database_Manager class - NOT FOUND<br>\n";
}

if (class_exists('EduBot_School_Config')) {
    echo "✅ EduBot_School_Config class - AVAILABLE<br>\n";
    try {
        $school_config = EduBot_School_Config::getInstance();
        echo "✅ EduBot_School_Config instantiation - SUCCESS<br>\n";
        
        $config = $school_config->get_config();
        if (!empty($config['school_info']['contact_info']['email'])) {
            echo "✅ Admin email from config: " . $config['school_info']['contact_info']['email'] . "<br>\n";
        } else {
            echo "⚠️ No admin email found in school config<br>\n";
        }
    } catch (Exception $e) {
        echo "❌ EduBot_School_Config instantiation - FAILED: " . $e->getMessage() . "<br>\n";
    }
} else {
    echo "❌ EduBot_School_Config class - NOT FOUND<br>\n";
}

// Test database save functionality
echo "<h3>4. Testing Database Save</h3>\n";

if (class_exists('EduBot_Database_Manager') && !empty($missing_tables) === false) {
    try {
        $db_manager = new EduBot_Database_Manager();
        
        // Test data
        $test_data = [
            'application_number' => 'TEST' . time(),
            'student_data' => [
                'student_name' => 'Test Student',
                'grade' => 'Grade 10',
                'board' => 'CBSE',
                'parent_name' => 'Test Parent',
                'email' => 'test@example.com',
                'phone' => '9876543210'
            ],
            'conversation_log' => [
                ['timestamp' => date('Y-m-d H:i:s'), 'type' => 'test', 'data' => 'test']
            ],
            'status' => 'test_enquiry',
            'source' => 'diagnostic_test'
        ];
        
        $result = $db_manager->save_application($test_data);
        
        if (is_wp_error($result)) {
            echo "❌ Database save test - FAILED: " . $result->get_error_message() . "<br>\n";
        } elseif ($result) {
            echo "✅ Database save test - SUCCESS (ID: {$result})<br>\n";
            // Clean up test data
            $wpdb->delete($wpdb->prefix . 'edubot_applications', ['id' => $result], ['%d']);
            echo "✅ Test data cleaned up<br>\n";
        } else {
            echo "❌ Database save test - FAILED: No result returned<br>\n";
        }
    } catch (Exception $e) {
        echo "❌ Database save test - EXCEPTION: " . $e->getMessage() . "<br>\n";
    }
} else {
    echo "⚠️ Skipping database save test (missing classes or tables)<br>\n";
}

echo "<h3>5. WordPress Email Test</h3>\n";

// Test WordPress mail function
$test_email = 'prasad.m@lsnsoft.com';
$subject = 'EduBot Diagnostic Test - ' . date('Y-m-d H:i:s');
$message = 'This is a test email from EduBot Pro diagnostic tool.';

if (function_exists('wp_mail')) {
    $mail_result = wp_mail($test_email, $subject, $message);
    if ($mail_result) {
        echo "✅ WordPress mail function - SUCCESS (sent to {$test_email})<br>\n";
    } else {
        echo "❌ WordPress mail function - FAILED (check SMTP settings)<br>\n";
    }
} else {
    echo "❌ WordPress mail function - NOT AVAILABLE<br>\n";
}

echo "<hr>\n";
echo "<p><strong>Diagnostic Complete!</strong></p>\n";
echo "<p>If you see any ❌ errors above, please address them before testing the enquiry form.</p>\n";
?>
