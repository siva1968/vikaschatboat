<?php
/**
 * EduBot Complete Enquiry Test
 * Test the complete enquiry flow including database save and email notifications
 */

// Simulate a complete enquiry submission
function test_complete_enquiry_flow() {
    echo "<h2>EduBot Complete Enquiry Flow Test</h2>\n";
    
    // Test data similar to what the chatbot would collect
    $test_collected_data = array(
        'student_name' => 'Siva Kumar',
        'phone' => '9866133566',
        'email' => 'prasadmasina@gmail.com',
        'grade' => 'Grade 10',
        'board' => 'CBSE',
        'academic_year' => '2026-27',
        'date_of_birth' => '16/10/2010'
    );
    
    echo "<h3>Test Data:</h3>\n";
    echo "<pre>" . json_encode($test_collected_data, JSON_PRETTY_PRINT) . "</pre>\n";
    
    try {
        // Simulate the process_final_submission function logic
        
        // 1. Get school configuration
        if (!class_exists('EduBot_School_Config')) {
            echo "❌ EduBot_School_Config class not available<br>\n";
            return;
        }
        
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        $school_name = isset($config['school_info']['name']) ? $config['school_info']['name'] : 'Epistemo Vikas Leadership School';
        
        echo "✅ School Name: {$school_name}<br>\n";
        
        // 2. Generate enquiry number
        $enquiry_number = 'ENQ' . date('Y') . strtoupper(substr(uniqid(), -8));
        echo "✅ Generated Enquiry Number: {$enquiry_number}<br>\n";
        
        // 3. Prepare application data
        $application_data = array(
            'application_number' => $enquiry_number,
            'student_data' => array(
                'student_name' => $test_collected_data['student_name'] ?? '',
                'grade' => $test_collected_data['grade'] ?? '',
                'board' => $test_collected_data['board'] ?? '',
                'academic_year' => $test_collected_data['academic_year'] ?? '',
                'date_of_birth' => $test_collected_data['date_of_birth'] ?? '',
                'parent_name' => 'Parent',
                'email' => $test_collected_data['email'] ?? '',
                'phone' => $test_collected_data['phone'] ?? '',
            ),
            'conversation_log' => array(
                array(
                    'timestamp' => current_time('mysql'),
                    'type' => 'enquiry_completion',
                    'data' => $test_collected_data
                )
            ),
            'status' => 'enquiry_submitted',
            'source' => 'chatbot_enquiry_test'
        );
        
        echo "✅ Application data prepared<br>\n";
        
        // 4. Test database save
        if (!class_exists('EduBot_Database_Manager')) {
            echo "❌ EduBot_Database_Manager class not available<br>\n";
            return;
        }
        
        $database_manager = new EduBot_Database_Manager();
        $application_id = $database_manager->save_application($application_data);
        
        if (is_wp_error($application_id)) {
            echo "❌ Database save failed: " . $application_id->get_error_message() . "<br>\n";
            return;
        }
        
        if (!$application_id) {
            echo "❌ Database save failed: No ID returned<br>\n";
            return;
        }
        
        echo "✅ Database save successful! Application ID: {$application_id}<br>\n";
        
        // 5. Test email to parent
        $parent_subject = "Admission Enquiry Confirmation - {$school_name}";
        $parent_message = build_test_email_content($test_collected_data, $enquiry_number, $school_name);
        
        $parent_headers = array('Content-Type: text/html; charset=UTF-8');
        $parent_mail_result = wp_mail($test_collected_data['email'], $parent_subject, $parent_message, $parent_headers);
        
        if ($parent_mail_result) {
            echo "✅ Confirmation email sent to parent: {$test_collected_data['email']}<br>\n";
        } else {
            echo "❌ Failed to send confirmation email to parent<br>\n";
        }
        
        // 6. Test admin notification email
        $admin_email = '';
        if (!empty($config['school_info']['contact_info']['email'])) {
            $admin_email = $config['school_info']['contact_info']['email'];
        }
        
        if (empty($admin_email)) {
            $admin_email = 'prasad.m@lsnsoft.com';
        }
        
        if (!empty($admin_email) && is_email($admin_email)) {
            $admin_subject = "New Admission Enquiry - {$school_name}";
            $admin_message = $parent_message . "\n\n<p><strong>This enquiry was submitted via the chatbot test.</strong></p>";
            $admin_mail_result = wp_mail($admin_email, $admin_subject, $admin_message, $parent_headers);
            
            if ($admin_mail_result) {
                echo "✅ Admin notification sent to: {$admin_email}<br>\n";
            } else {
                echo "❌ Failed to send admin notification<br>\n";
            }
        } else {
            echo "❌ No valid admin email found<br>\n";
        }
        
        // 7. Generate success response
        $response = "🎉 **TEST ENQUIRY SUCCESSFULLY SUBMITTED!** 🎉\n\n";
        $response .= "✅ Your enquiry has been recorded with:\n";
        $response .= "📋 **Enquiry Number:** {$enquiry_number}\n";
        $response .= "🏫 **School:** {$school_name}\n";
        $response .= "👶 **Student:** {$test_collected_data['student_name']}\n";
        $response .= "🎓 **Grade:** {$test_collected_data['grade']}\n";
        $response .= "📚 **Board:** {$test_collected_data['board']}\n\n";
        
        echo "<h3>Success Response Preview:</h3>\n";
        echo "<div style='background: #f0f8ff; padding: 15px; border-left: 4px solid #007cba;'>";
        echo nl2br(esc_html($response));
        echo "</div>\n";
        
        // Clean up test data
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'edubot_applications', ['id' => $application_id], ['%d']);
        echo "✅ Test data cleaned up<br>\n";
        
        echo "<h3>✅ COMPLETE ENQUIRY FLOW TEST - PASSED!</h3>\n";
        
    } catch (Exception $e) {
        echo "❌ Exception during test: " . $e->getMessage() . "<br>\n";
    }
}

function build_test_email_content($collected_data, $enquiry_number, $school_name) {
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Admission Enquiry Confirmation</title></head><body>';
    $html .= '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd;">';
    
    $html .= '<h2 style="color: #2c5282; text-align: center;">Admission Enquiry Confirmation</h2>';
    $html .= '<p>Dear Parent/Guardian,</p>';
    $html .= '<p>Thank you for your interest in <strong>' . esc_html($school_name) . '</strong>.</p>';
    $html .= '<p>Your admission enquiry has been successfully submitted with the following details:</p>';
    
    $html .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
    $html .= '<tr style="background-color: #f8f9fa;"><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Enquiry Number</td><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($enquiry_number) . '</td></tr>';
    $html .= '<tr><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Student Name</td><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($collected_data['student_name'] ?? '') . '</td></tr>';
    $html .= '<tr style="background-color: #f8f9fa;"><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Grade</td><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($collected_data['grade'] ?? '') . '</td></tr>';
    $html .= '<tr><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Board</td><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($collected_data['board'] ?? '') . '</td></tr>';
    $html .= '<tr style="background-color: #f8f9fa;"><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Phone</td><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($collected_data['phone'] ?? '') . '</td></tr>';
    $html .= '<tr><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Date Submitted</td><td style="padding: 10px; border: 1px solid #ddd;">' . date('F j, Y g:i A') . '</td></tr>';
    $html .= '</table>';
    
    $html .= '<p>Please save this enquiry number for your records: <strong>' . esc_html($enquiry_number) . '</strong></p>';
    $html .= '<p>Best regards,<br>' . esc_html($school_name) . ' Admissions Team</p>';
    $html .= '</div></body></html>';
    
    return $html;
}

// Run the test
test_complete_enquiry_flow();
?>
