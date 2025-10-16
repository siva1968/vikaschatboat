<?php
/**
 * Applications Table Fix - Validation Script
 * Run this after form submission to verify both tables are populated
 */

if (!defined('ABSPATH')) {
    echo "❌ ERROR: Must run within WordPress\n";
    exit;
}

echo "====================================================\n";
echo "Applications Table Fix - Validation Report\n";
echo "====================================================\n\n";

global $wpdb;

// Test 1: Check if applications table exists
echo "Test 1: Checking Applications Table\n";
$apps_table = $wpdb->prefix . 'edubot_applications';
$apps_exists = $wpdb->get_var("SHOW TABLES LIKE '$apps_table'") === $apps_table;

if ($apps_exists) {
    echo "✅ Applications table exists\n";
    
    // Count entries
    $app_count = $wpdb->get_var("SELECT COUNT(*) FROM $apps_table");
    echo "   Total entries: $app_count\n\n";
} else {
    echo "❌ Applications table does not exist\n";
    echo "   Run: CREATE TABLE {$apps_table} ...\n\n";
}

// Test 2: Check if enquiries table exists
echo "Test 2: Checking Enquiries Table\n";
$enq_table = $wpdb->prefix . 'enquiries';
$enq_exists = $wpdb->get_var("SHOW TABLES LIKE '$enq_table'") === $enq_table;

if ($enq_exists) {
    echo "✅ Enquiries table exists\n";
    
    // Count entries
    $enq_count = $wpdb->get_var("SELECT COUNT(*) FROM $enq_table");
    echo "   Total entries: $enq_count\n\n";
} else {
    echo "❌ Enquiries table does not exist\n\n";
}

// Test 3: Compare recent entries
if ($apps_exists && $enq_exists) {
    echo "Test 3: Comparing Recent Entries\n";
    
    $recent_enquiries = $wpdb->get_results(
        "SELECT enquiry_number, student_name, email, created_at 
         FROM $enq_table 
         ORDER BY created_at DESC 
         LIMIT 5"
    );
    
    $recent_applications = $wpdb->get_results(
        "SELECT application_number, student_data, created_at 
         FROM $apps_table 
         ORDER BY created_at DESC 
         LIMIT 5"
    );
    
    if (!empty($recent_enquiries)) {
        echo "Recent Enquiries:\n";
        foreach ($recent_enquiries as $enq) {
            echo "  • ENQ: " . esc_html($enq->enquiry_number) . 
                 " | Student: " . esc_html($enq->student_name) . 
                 " | Email: " . esc_html($enq->email) . "\n";
        }
        echo "\n";
    } else {
        echo "⚠️  No enquiries found\n\n";
    }
    
    if (!empty($recent_applications)) {
        echo "Recent Applications:\n";
        foreach ($recent_applications as $app) {
            $student_data = json_decode($app->student_data, true);
            echo "  • APP: " . esc_html($app->application_number) . 
                 " | Student: " . esc_html($student_data['student_name'] ?? 'Not Set') . 
                 " | Email: " . esc_html($student_data['email'] ?? 'Not Set') . "\n";
        }
        echo "\n";
    } else {
        echo "❌ No applications found\n";
        echo "   This indicates the applications table is not being populated\n\n";
    }
    
    // Test 4: Match enquiries to applications
    echo "Test 4: Matching Enquiries to Applications\n";
    
    if (!empty($recent_enquiries) && !empty($recent_applications)) {
        $matched = 0;
        $unmatched_enquiries = array();
        
        foreach ($recent_enquiries as $enq) {
            $found = false;
            foreach ($recent_applications as $app) {
                if ($enq->enquiry_number === $app->application_number) {
                    $found = true;
                    $matched++;
                    break;
                }
            }
            if (!$found) {
                $unmatched_enquiries[] = $enq->enquiry_number;
            }
        }
        
        echo "Matched pairs: $matched / " . count($recent_enquiries) . "\n";
        
        if (!empty($unmatched_enquiries)) {
            echo "⚠️  Unmatched enquiries (not in applications table):\n";
            foreach ($unmatched_enquiries as $unmatched) {
                echo "   • " . esc_html($unmatched) . "\n";
            }
        } else {
            echo "✅ All enquiries have matching applications\n";
        }
        echo "\n";
    }
}

// Test 5: Check for recent validation errors
echo "Test 5: Checking for Recent Errors\n";
$log_path = WP_CONTENT_DIR . '/debug.log';

if (file_exists($log_path)) {
    $log_lines = array_reverse(file($log_path));
    $recent_errors = array();
    
    $count = 0;
    foreach ($log_lines as $line) {
        if (strpos($line, 'EduBot') !== false && 
            (strpos($line, 'validation failed') !== false || 
             strpos($line, 'Failed to save') !== false ||
             strpos($line, 'Application') !== false)) {
            $recent_errors[] = trim($line);
            $count++;
            if ($count >= 10) break;
        }
    }
    
    if (!empty($recent_errors)) {
        echo "Recent application-related logs:\n";
        foreach ($recent_errors as $error) {
            // Highlight errors vs successes
            if (strpos($error, 'Successfully') !== false) {
                echo "✅ " . substr($error, 0, 120) . "...\n";
            } else if (strpos($error, 'Failed') !== false) {
                echo "❌ " . substr($error, 0, 120) . "...\n";
            } else {
                echo "ℹ️  " . substr($error, 0, 120) . "...\n";
            }
        }
        echo "\n";
    } else {
        echo "No recent application-related logs found\n\n";
    }
} else {
    echo "⚠️  Debug log not found at: $log_path\n";
    echo "   Enable debug logging in wp-config.php\n\n";
}

// Test 6: Summary
echo "====================================================\n";
echo "Summary\n";
echo "====================================================\n";

$issues = array();

if (!$apps_exists) {
    $issues[] = "Applications table missing";
}

if (!$enq_exists) {
    $issues[] = "Enquiries table missing";
}

if (empty($recent_applications)) {
    $issues[] = "No applications being saved";
}

if (!empty($unmatched_enquiries)) {
    $issues[] = count($unmatched_enquiries) . " enquiries missing from applications table";
}

if (empty($issues)) {
    echo "✅ All checks passed!\n";
    echo "   Both tables exist and are being populated correctly.\n";
} else {
    echo "❌ Issues found:\n";
    foreach ($issues as $issue) {
        echo "   • " . $issue . "\n";
    }
}

echo "====================================================\n\n";

?>
