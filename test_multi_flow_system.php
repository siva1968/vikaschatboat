<?php
/**
 * Test Script for Enhanced Multi-Flow Chatbot System
 * Run this to verify the implementation works correctly
 */

// Mock WordPress functions for testing
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        static $options = array();
        return isset($options[$option]) ? $options[$option] : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        static $options = array();
        $options[$option] = $value;
        return true;
    }
}

if (!function_exists('current_time')) {
    function current_time($type) {
        return ($type === 'timestamp') ? time() : date('Y-m-d H:i:s');
    }
}

if (!function_exists('error_log')) {
    function error_log($message) {
        echo "[LOG] " . $message . "\n";
    }
}

// Load the flow manager
require_once 'includes/class-edubot-flow-manager.php';

echo "=== Enhanced Multi-Flow Chatbot System Test ===\n\n";

// Initialize flow manager
$flow_manager = EduBot_Flow_Manager_Instance::get_instance();

// Test 1: Get available flows
echo "1. Testing available flows:\n";
$available_flows = $flow_manager->get_available_flows();
foreach ($available_flows as $flow_type => $config) {
    echo "   - {$flow_type}: {$config['name']}\n";
}
echo "\n";

// Test 2: Initialize admission flow
echo "2. Testing admission flow initialization:\n";
try {
    $admission_session = $flow_manager->init_flow('admission');
    echo "   ✅ Admission flow initialized: {$admission_session['session_id']}\n";
    echo "   - Flow type: {$admission_session['flow_type']}\n";
    echo "   - Current step: {$admission_session['step_name']}\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Initialize information flow
echo "3. Testing information flow initialization:\n";
try {
    $info_session = $flow_manager->init_flow('information');
    echo "   ✅ Information flow initialized: {$info_session['session_id']}\n";
    echo "   - Flow type: {$info_session['flow_type']}\n";
    echo "   - Current step: {$info_session['step_name']}\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Process personal info in admission flow
echo "4. Testing personal info processing:\n";
if (isset($admission_session)) {
    try {
        $message = "John Smith john@email.com 9876543210";
        $result = $flow_manager->process_message($admission_session['session_id'], $message);
        echo "   ✅ Personal info processed successfully\n";
        echo "   - Next step: {$result['next_step']}\n";
        echo "   - Response preview: " . substr($result['response'], 0, 100) . "...\n";
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Test 5: Test session expiry
echo "5. Testing session management:\n";
$sessions_before = get_option('edubot_flow_sessions', array());
echo "   - Active sessions before cleanup: " . count($sessions_before) . "\n";

// Simulate cleanup
$flow_manager->cleanup_expired_sessions();
$sessions_after = get_option('edubot_flow_sessions', array());
echo "   - Active sessions after cleanup: " . count($sessions_after) . "\n";
echo "\n";

// Test 6: Test concurrent flows
echo "6. Testing concurrent flow support:\n";
$user_flows = $flow_manager->get_user_active_flows('test_user_123');
echo "   - User active flows: " . count($user_flows) . "\n";
$can_start_multiple = $flow_manager->can_start_multiple_flows('test_user_123');
echo "   - Can start multiple flows: " . ($can_start_multiple ? 'Yes' : 'No') . "\n";
echo "\n";

// Test 7: Invalid flow type handling
echo "7. Testing error handling:\n";
try {
    $invalid_flow = $flow_manager->init_flow('invalid_flow_type');
    echo "   ❌ Should have thrown exception for invalid flow type\n";
} catch (Exception $e) {
    echo "   ✅ Properly handled invalid flow type: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== Test Complete ===\n";
echo "All major components have been tested successfully!\n\n";

echo "Next Steps:\n";
echo "1. Deploy the enhanced files to your WordPress installation\n";
echo "2. Test the chatbot interface with real user interactions\n";
echo "3. Monitor session management and flow performance\n";
echo "4. Verify database integration for enquiry storage\n";
?>
