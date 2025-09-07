<?php
/**
 * Test script to check what email settings are available
 * Upload this file to your WordPress site and run it to see what contact emails are configured
 */

// Make sure we're in WordPress context
if (!defined('ABSPATH')) {
    // Try to load WordPress
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

echo "<h1>EduBot Email Settings Check</h1>";
echo "<p>Checking what email addresses are configured in your system...</p>";

echo "<h2>WordPress Options (Direct)</h2>";
$options_to_check = [
    'edubot_school_email' => 'EduBot School Email (from School Settings)',
    'school_contact_email' => 'School Contact Email',
    'school_information_contact_email' => 'School Information Contact Email',
    'edubot_school_contact_email' => 'EduBot School Contact Email',
    'admin_email' => 'WordPress Admin Email',
    'edubot_pro_settings' => 'EduBot Pro Settings (full array)'
];

foreach ($options_to_check as $option_name => $description) {
    $value = get_option($option_name);
    echo "<p><strong>{$description} ({$option_name}):</strong> ";
    
    if (is_array($value)) {
        echo "<br><pre>" . print_r($value, true) . "</pre>";
    } else {
        echo $value ? esc_html($value) : '<em>Not set</em>';
    }
    echo "</p>";
}

echo "<h2>EduBot School Config Class</h2>";
if (class_exists('EduBot_School_Config')) {
    echo "<p>✅ EduBot_School_Config class exists</p>";
    
    try {
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        
        echo "<p><strong>Full Config Structure:</strong></p>";
        echo "<pre>" . print_r($config, true) . "</pre>";
        
        $contact_email = $config['school_info']['contact_info']['email'] ?? null;
        echo "<p><strong>Contact Email from config:</strong> " . ($contact_email ? esc_html($contact_email) : '<em>Not set</em>') . "</p>";
        
    } catch (Exception $e) {
        echo "<p>❌ Error accessing EduBot_School_Config: " . esc_html($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>❌ EduBot_School_Config class not found</p>";
    
    // Try to load it manually
    $config_file = __DIR__ . '/includes/class-school-config.php';
    if (file_exists($config_file)) {
        require_once $config_file;
        echo "<p>🔄 Manually loaded class from: $config_file</p>";
        
        if (class_exists('EduBot_School_Config')) {
            echo "<p>✅ Class now available, trying again...</p>";
            try {
                $school_config = EduBot_School_Config::getInstance();
                $config = $school_config->get_config();
                
                echo "<p><strong>Full Config Structure:</strong></p>";
                echo "<pre>" . print_r($config, true) . "</pre>";
                
                $contact_email = $config['school_info']['contact_info']['email'] ?? null;
                echo "<p><strong>Contact Email from config:</strong> " . ($contact_email ? esc_html($contact_email) : '<em>Not set</em>') . "</p>";
                
            } catch (Exception $e) {
                echo "<p>❌ Error accessing EduBot_School_Config: " . esc_html($e->getMessage()) . "</p>";
            }
        }
    } else {
        echo "<p>❌ Config file not found at: $config_file</p>";
    }
}

echo "<h2>Email Priority Test</h2>";
echo "<p>Testing the same logic that's used in the chatbot:</p>";

// Simulate the email detection logic
$school_email = '';
$found_via = '';

// Priority 1: WordPress options
$possible_options = [
    'edubot_school_email' => 'EduBot School Email (from School Settings)',
    'school_contact_email' => 'School Contact Email',
    'school_information_contact_email' => 'School Information Contact Email',
    'edubot_school_contact_email' => 'EduBot School Contact Email',
    'admin_email' => 'WordPress Admin Email'
];

foreach ($possible_options as $option_name => $description) {
    $option_value = get_option($option_name);
    if (!empty($option_value) && filter_var($option_value, FILTER_VALIDATE_EMAIL)) {
        $school_email = $option_value;
        $found_via = "WordPress option: $description ($option_name)";
        break;
    }
}

// Priority 2: EduBot School Config
if (empty($school_email) && class_exists('EduBot_School_Config')) {
    try {
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        $contact_info = $config['school_info']['contact_info'] ?? array();
        if (!empty($contact_info['email'])) {
            $school_email = $contact_info['email'];
            $found_via = 'EduBot School Config';
        }
    } catch (Exception $e) {
        echo "<p>⚠️ Could not get school config: " . esc_html($e->getMessage()) . "</p>";
    }
}

// Priority 3: Plugin settings
if (empty($school_email)) {
    $settings = get_option('edubot_pro_settings', array());
    if (!empty($settings['contact_email'])) {
        $school_email = $settings['contact_email'];
        $found_via = 'Plugin settings: contact_email';
    } elseif (!empty($settings['admin_email'])) {
        $school_email = $settings['admin_email'];
        $found_via = 'Plugin settings: admin_email';
    }
}

// Priority 4: Fallback
if (empty($school_email)) {
    $school_email = 'admissions@epistemo.in';
    $found_via = 'Hardcoded fallback';
}

echo "<p><strong>📧 Email that would be used:</strong> " . esc_html($school_email) . "</p>";
echo "<p><strong>🔍 Found via:</strong> " . esc_html($found_via) . "</p>";

if ($found_via === 'Hardcoded fallback') {
    echo "<div style='background: #ffeeee; padding: 15px; border-left: 4px solid #cc0000; margin: 20px 0;'>";
    echo "<h3>❌ No Contact Email Configured</h3>";
    echo "<p>The system is falling back to the hardcoded email 'admissions@epistemo.in' because no contact email is configured.</p>";
    echo "<p><strong>To fix this:</strong></p>";
    echo "<ol>";
    echo "<li>Go to <strong>WordPress Admin > EduBot Pro > School Settings</strong></li>";
    echo "<li>Fill in the <strong>Contact Email</strong> field</li>";
    echo "<li>Click <strong>Save Settings</strong></li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #eeffee; padding: 15px; border-left: 4px solid #00cc00; margin: 20px 0;'>";
    echo "<h3>✅ Contact Email Found</h3>";
    echo "<p>The system found a configured contact email and will use it for school notifications.</p>";
    echo "</div>";
}

echo "<hr><p><em>Generated on: " . date('Y-m-d H:i:s') . "</em></p>";
?>
