<?php
/**
 * Test Foreign Key Constraints
 */

require_once 'D:/xamppdev/htdocs/demo/wp-load.php';

global $wpdb;

echo "=== Testing Foreign Key Constraints ===\n\n";

// Test 1: Insert parent record
echo "Test 1: Inserting enquiry...\n";
$result = $wpdb->insert(
    $wpdb->prefix . 'edubot_enquiries',
    [
        'enquiry_number' => 'TEST-' . time(),
        'student_name' => 'Test Student',
        'email' => 'test@example.com',
        'phone' => '9876543210',
        'status' => 'pending'
    ]
);

if ($result) {
    $enquiry_id = $wpdb->insert_id;
    echo "✓ Enquiry created: ID=$enquiry_id\n\n";
    
    // Test 2: Insert child record (attribution session)
    echo "Test 2: Inserting attribution session referencing enquiry...\n";
    $result = $wpdb->insert(
        $wpdb->prefix . 'edubot_attribution_sessions',
        [
            'enquiry_id' => $enquiry_id,
            'user_session_key' => 'sess_' . time(),
            'first_touch_source' => 'organic',
            'attribution_model' => 'last-click'
        ]
    );
    
    if ($result) {
        $session_id = $wpdb->insert_id;
        echo "✓ Attribution session created: ID=$session_id, References enquiry $enquiry_id\n\n";
        
        // Test 3: Insert touchpoint
        echo "Test 3: Inserting attribution touchpoint referencing session and enquiry...\n";
        $result = $wpdb->insert(
            $wpdb->prefix . 'edubot_attribution_touchpoints',
            [
                'session_id' => $session_id,
                'enquiry_id' => $enquiry_id,
                'source' => 'google',
                'medium' => 'cpc',
                'position_in_journey' => 1
            ]
        );
        
        if ($result) {
            echo "✓ Attribution touchpoint created successfully\n\n";
        } else {
            echo "✗ Failed to create touchpoint: " . $wpdb->last_error . "\n\n";
        }
    } else {
        echo "✗ Failed to create session: " . $wpdb->last_error . "\n\n";
    }
} else {
    echo "✗ Failed to create enquiry: " . $wpdb->last_error . "\n\n";
}

// Test 4: Try to create a session referencing non-existent enquiry
echo "Test 4: Attempting to insert invalid FK (should fail)...\n";
$result = $wpdb->insert(
    $wpdb->prefix . 'edubot_attribution_sessions',
    [
        'enquiry_id' => 999999,  // This doesn't exist
        'user_session_key' => 'invalid_' . time(),
        'first_touch_source' => 'test',
        'attribution_model' => 'last-click'
    ]
);

if ($result === false) {
    echo "✓ FK constraint properly enforced - invalid insert rejected\n";
    echo "  Error: " . $wpdb->last_error . "\n\n";
} else {
    echo "✗ FK constraint NOT enforced - invalid insert was allowed!\n\n";
}

echo "=== All FK Tests Complete ===\n";
?>
