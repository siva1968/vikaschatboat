<?php
/**
 * EduBot Pro - Notification Status Test Script
 * 
 * Test notification status updates in the database
 * Access via: yourdomain.com/wp-content/plugins/AI ChatBoat/test_notification_status.php
 */

// WordPress environment setup
if (!defined('ABSPATH')) {
    // Try to load WordPress
    $wp_load_paths = array(
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php'
    );
    
    foreach ($wp_load_paths as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            require_once __DIR__ . '/' . $path;
            break;
        }
    }
    
    if (!defined('ABSPATH')) {
        die('WordPress environment not found. Please run this from WordPress admin or place in correct directory.');
    }
}

// Security check - only admins can run this
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Administrator privileges required.');
}

echo "<h1>EduBot Pro - Notification Status Test</h1>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . " UTC</p>";

global $wpdb;
$enquiries_table = $wpdb->prefix . 'edubot_enquiries';

echo "<h2>1. Recent Enquiries Status Check</h2>";

// Get recent enquiries with notification status
$recent_enquiries = $wpdb->get_results(
    "SELECT id, enquiry_number, student_name, email, phone, 
            email_sent, whatsapp_sent, sms_sent, created_at 
     FROM {$enquiries_table} 
     ORDER BY created_at DESC 
     LIMIT 10",
    ARRAY_A
);

if ($recent_enquiries) {
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Enquiry Number</th><th>Student Name</th><th>Email Status</th><th>WhatsApp Status</th><th>SMS Status</th><th>Created</th>";
    echo "</tr>";
    
    foreach ($recent_enquiries as $enquiry) {
        $email_status = $enquiry['email_sent'] ? '✅ Sent' : '❌ Not Sent';
        $whatsapp_status = $enquiry['whatsapp_sent'] ? '✅ Sent' : '❌ Not Sent';
        $sms_status = $enquiry['sms_sent'] ? '✅ Sent' : '❌ Not Sent';
        
        echo "<tr>";
        echo "<td>{$enquiry['id']}</td>";
        echo "<td>{$enquiry['enquiry_number']}</td>";
        echo "<td>{$enquiry['student_name']}</td>";
        echo "<td>{$email_status}</td>";
        echo "<td>{$whatsapp_status}</td>";
        echo "<td>{$sms_status}</td>";
        echo "<td>{$enquiry['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No enquiries found.</p>";
}

echo "<h2>2. Test Notification Status Update</h2>";

if (isset($_GET['test_update']) && $_GET['test_update'] === 'true') {
    // Test updating notification status
    if (!empty($recent_enquiries)) {
        $test_enquiry = $recent_enquiries[0]; // Get the most recent enquiry
        $enquiry_id = $test_enquiry['id'];
        
        echo "<p><strong>Testing with Enquiry ID:</strong> {$enquiry_id} ({$test_enquiry['enquiry_number']})</p>";
        
        // Test database manager
        if (class_exists('EduBot_Database_Manager')) {
            $db_manager = new EduBot_Database_Manager();
            
            // Test email status update
            echo "<p><strong>Testing Email Status Update...</strong></p>";
            $email_result = $db_manager->update_notification_status($enquiry_id, 'email', 1, 'enquiries');
            echo "<p>Email Status Update Result: " . ($email_result !== false ? '✅ Success' : '❌ Failed') . "</p>";
            
            // Test WhatsApp status update
            echo "<p><strong>Testing WhatsApp Status Update...</strong></p>";
            $whatsapp_result = $db_manager->update_notification_status($enquiry_id, 'whatsapp', 1, 'enquiries');
            echo "<p>WhatsApp Status Update Result: " . ($whatsapp_result !== false ? '✅ Success' : '❌ Failed') . "</p>";
            
            // Verify the updates
            $updated_enquiry = $wpdb->get_row($wpdb->prepare(
                "SELECT email_sent, whatsapp_sent FROM {$enquiries_table} WHERE id = %d",
                $enquiry_id
            ), ARRAY_A);
            
            if ($updated_enquiry) {
                echo "<p><strong>Verification After Update:</strong></p>";
                echo "<ul>";
                echo "<li>Email Sent: " . ($updated_enquiry['email_sent'] ? '✅ Yes (1)' : '❌ No (0)') . "</li>";
                echo "<li>WhatsApp Sent: " . ($updated_enquiry['whatsapp_sent'] ? '✅ Yes (1)' : '❌ No (0)') . "</li>";
                echo "</ul>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ EduBot_Database_Manager class not found</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No enquiries available for testing</p>";
    }
} else {
    if (!empty($recent_enquiries)) {
        echo "<p><a href='?test_update=true' style='background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>🧪 Run Test Update</a></p>";
        echo "<p><em>This will update the notification status of the most recent enquiry for testing purposes.</em></p>";
    }
}

echo "<h2>3. Notification Status Summary</h2>";

// Get statistics
$total_enquiries = $wpdb->get_var("SELECT COUNT(*) FROM {$enquiries_table}");
$email_sent_count = $wpdb->get_var("SELECT COUNT(*) FROM {$enquiries_table} WHERE email_sent = 1");
$whatsapp_sent_count = $wpdb->get_var("SELECT COUNT(*) FROM {$enquiries_table} WHERE whatsapp_sent = 1");
$sms_sent_count = $wpdb->get_var("SELECT COUNT(*) FROM {$enquiries_table} WHERE sms_sent = 1");

echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'><th>Metric</th><th>Count</th><th>Percentage</th></tr>";

$email_percentage = $total_enquiries > 0 ? round(($email_sent_count / $total_enquiries) * 100, 1) : 0;
$whatsapp_percentage = $total_enquiries > 0 ? round(($whatsapp_sent_count / $total_enquiries) * 100, 1) : 0;
$sms_percentage = $total_enquiries > 0 ? round(($sms_sent_count / $total_enquiries) * 100, 1) : 0;

echo "<tr><td><strong>Total Enquiries</strong></td><td>{$total_enquiries}</td><td>-</td></tr>";
echo "<tr><td><strong>Emails Sent</strong></td><td>{$email_sent_count}</td><td>{$email_percentage}%</td></tr>";
echo "<tr><td><strong>WhatsApp Sent</strong></td><td>{$whatsapp_sent_count}</td><td>{$whatsapp_percentage}%</td></tr>";
echo "<tr><td><strong>SMS Sent</strong></td><td>{$sms_sent_count}</td><td>{$sms_percentage}%</td></tr>";
echo "</table>";

echo "<h2>4. Troubleshooting Tips</h2>";

echo "<ul>";
echo "<li><strong>If notifications show 'Not Sent' but were actually sent:</strong> Check if the notification status update code is being called after successful sends</li>";
echo "<li><strong>If status updates fail:</strong> Verify the database manager class is loaded and the enquiry ID is correct</li>";
echo "<li><strong>If columns don't exist:</strong> Run the database migration via System Status page</li>";
echo "<li><strong>To manually mark as sent:</strong> Use the test update feature above</li>";
echo "</ul>";

// Manual update form
if (!empty($recent_enquiries)) {
    echo "<h2>5. Manual Status Update</h2>";
    echo "<form method='POST'>";
    echo "<label>Select Enquiry:</label><br>";
    echo "<select name='enquiry_id' style='margin: 10px 0; padding: 5px; width: 300px;'>";
    foreach ($recent_enquiries as $enquiry) {
        echo "<option value='{$enquiry['id']}'>{$enquiry['enquiry_number']} - {$enquiry['student_name']}</option>";
    }
    echo "</select><br>";
    
    echo "<label>Notification Type:</label><br>";
    echo "<select name='notification_type' style='margin: 10px 0; padding: 5px;'>";
    echo "<option value='email'>Email</option>";
    echo "<option value='whatsapp'>WhatsApp</option>";
    echo "<option value='sms'>SMS</option>";
    echo "</select><br>";
    
    echo "<label>Status:</label><br>";
    echo "<select name='status' style='margin: 10px 0; padding: 5px;'>";
    echo "<option value='1'>Sent (1)</option>";
    echo "<option value='0'>Not Sent (0)</option>";
    echo "</select><br>";
    
    echo "<input type='submit' name='manual_update' value='Update Status' style='background: #007cba; color: white; padding: 10px 15px; border: none; border-radius: 3px; cursor: pointer;'>";
    echo "</form>";
    
    // Process manual update
    if (isset($_POST['manual_update'])) {
        $enquiry_id = intval($_POST['enquiry_id']);
        $notification_type = sanitize_text_field($_POST['notification_type']);
        $status = intval($_POST['status']);
        
        if (class_exists('EduBot_Database_Manager')) {
            $db_manager = new EduBot_Database_Manager();
            $result = $db_manager->update_notification_status($enquiry_id, $notification_type, $status, 'enquiries');
            
            if ($result !== false) {
                echo "<p style='color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 3px;'>";
                echo "✅ Successfully updated {$notification_type} status to " . ($status ? 'Sent' : 'Not Sent') . " for enquiry ID {$enquiry_id}";
                echo "</p>";
                echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
            } else {
                echo "<p style='color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px;'>";
                echo "❌ Failed to update notification status";
                echo "</p>";
            }
        }
    }
}

echo "<p style='color: #666; font-size: 12px;'>Test completed at " . date('Y-m-d H:i:s') . " UTC</p>";
?>
