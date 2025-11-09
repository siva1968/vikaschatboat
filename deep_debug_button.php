<?php
/**
 * Deep Debug: Trace why button isn't showing
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== DEEP DEBUG: Why Button Not Showing ===\n\n";

// Step 1: Check if filter is hooked
echo "Step 1: Check if filter is hooked\n";
global $wp_filter;
if (isset($wp_filter['edubot_applications_row_actions'])) {
    echo "✅ edubot_applications_row_actions filter EXISTS\n";
    echo "   Callbacks:\n";
    $callbacks = $wp_filter['edubot_applications_row_actions']->callbacks;
    foreach ($callbacks as $priority => $cbs) {
        echo "   Priority $priority:\n";
        foreach ($cbs as $name => $callback) {
            echo "     - $name\n";
        }
    }
} else {
    echo "❌ edubot_applications_row_actions filter NOT FOUND\n";
    echo "\nAll filters containing 'edubot':\n";
    foreach ($wp_filter as $hook => $data) {
        if (strpos($hook, 'edubot') !== false) {
            echo "  - $hook\n";
        }
    }
}

echo "\n\nStep 2: Check if EduBot_MCB_Admin is initialized\n";
if (class_exists('EduBot_MCB_Admin')) {
    echo "✅ EduBot_MCB_Admin class exists\n";
    
    // Check if init was called
    if (method_exists('EduBot_MCB_Admin', 'init')) {
        echo "✅ init() method exists\n";
    }
} else {
    echo "❌ EduBot_MCB_Admin class NOT found\n";
}

echo "\n\nStep 3: Check EduBot_MCB_Service\n";
if (class_exists('EduBot_MCB_Service')) {
    echo "✅ EduBot_MCB_Service class exists\n";
    $service = EduBot_MCB_Service::get_instance();
    echo "✅ Service instance created\n";
    echo "   is_sync_enabled() = " . ($service->is_sync_enabled() ? 'TRUE' : 'FALSE') . "\n";
} else {
    echo "❌ EduBot_MCB_Service class NOT found\n";
}

echo "\n\nStep 4: Check where MCB_Admin is initialized\n";
// Search for init calls
if (has_action('admin_init', 'EduBot_MCB_Admin')) {
    echo "✅ EduBot_MCB_Admin hooked to admin_init\n";
} else {
    echo "❌ EduBot_MCB_Admin NOT hooked to admin_init\n";
}

// Check if it's called on any other hook
$found = false;
foreach ($wp_filter as $hook => $data) {
    foreach ($data->callbacks as $priority => $cbs) {
        foreach ($cbs as $name => $callback) {
            if (is_array($callback['function']) && 
                is_object($callback['function'][0]) && 
                get_class($callback['function'][0]) === 'EduBot_MCB_Admin') {
                echo "✅ EduBot_MCB_Admin callback found on: $hook\n";
                $found = true;
            }
        }
    }
}

if (!$found) {
    echo "⚠️  EduBot_MCB_Admin callbacks not found on any hooks\n";
}

echo "\n\nStep 5: Check main plugin file for MCB_Admin initialization\n";
$plugin_file = plugin_dir_path(__FILE__) . 'edubot-pro.php';
if (file_exists($plugin_file)) {
    $content = file_get_contents($plugin_file);
    if (strpos($content, 'EduBot_MCB_Admin') !== false) {
        echo "✅ EduBot_MCB_Admin mentioned in main plugin file\n";
        if (strpos($content, 'EduBot_MCB_Admin::init()') !== false) {
            echo "✅ EduBot_MCB_Admin::init() called in plugin file\n";
        } else {
            echo "❌ EduBot_MCB_Admin::init() NOT called in plugin file\n";
        }
    } else {
        echo "❌ EduBot_MCB_Admin NOT mentioned in main plugin file\n";
    }
}

echo "\n\nStep 6: Manually test the add_sync_action function\n";
if (class_exists('EduBot_MCB_Admin')) {
    $test_app = array('enquiry_id' => 999, 'mcb_sync_status' => 'pending');
    $test_actions = array('view' => 'View', 'delete' => 'Delete');
    
    $result = EduBot_MCB_Admin::add_sync_action($test_actions, $test_app);
    
    if (isset($result['mcb_sync'])) {
        echo "✅ Function works - button added to result\n";
    } else {
        echo "❌ Function not working - button NOT added to result\n";
    }
}

echo "\n\n=== SUMMARY ===\n";
echo "If you see mostly ✅ but button still not showing:\n";
echo "→ Likely a WordPress cache/transient issue\n";
echo "→ Or the filter is applied BEFORE MCB_Admin initializes\n";
echo "→ Try: Go to Settings > Permalinks > Save Changes (flushes WordPress cache)\n";
?>
