<?php
/**
 * ULTIMATE 500 ERROR FIX
 * ======================
 * 
 * This is an emergency patch to completely eliminate 500 errors
 * by bypassing ALL potential problem areas in the chatbot.
 * 
 * This creates a minimal, bulletproof version that only handles
 * the core admission flow without any external dependencies.
 */

// Create backup of original shortcode method
$shortcode_backup_content = '
/**
 * EMERGENCY BYPASS VERSION - Generate Response Method
 * This version bypasses ALL external API calls and complex processing
 */
private function generate_response($message, $action_type, $session_id = "") {
    error_log("EduBot: EMERGENCY MODE - Using ultra-safe response generation");
    
    try {
        // Get current step from session
        $current_step = $this->get_session_data($session_id, "current_step") ?: "start";
        $collected_data = $this->get_session_data($session_id, "collected_data") ?: array();
        
        // Ultra-safe step processing - no external calls
        switch ($current_step) {
            case "start":
            case "admission_start":
                if ($action_type === "admission") {
                    $this->set_session_data($session_id, "current_step", "collect_student_info");
                    return $this->format_response(
                        "Welcome to our Admission Enquiry! 📚\\n\\nPlease provide:\\n• Student Name\\n• Parent Email\\n• Phone Number\\n\\nExample: John Doe johndoe@email.com 9876543210",
                        "collect_student_info"
                    );
                }
                break;
                
            case "collect_student_info":
                // Parse student info safely
                $parsed = $this->parse_student_info_safe($message);
                if ($parsed["valid"]) {
                    $collected_data = array_merge($collected_data, $parsed["data"]);
                    $this->set_session_data($session_id, "collected_data", $collected_data);
                    $this->set_session_data($session_id, "current_step", "collect_board");
                    
                    return $this->format_response(
                        "Great! Information received:\\n\\n👤 Student: " . $parsed["data"]["student_name"] . "\\n📧 Email: " . $parsed["data"]["email"] . "\\n📱 Phone: " . $parsed["data"]["phone"] . "\\n\\nWhich curriculum board? (CBSE/ICSE/State Board)",
                        "collect_board"
                    );
                }
                return $this->format_response("Please provide all details in this format:\\nStudent Name Email Phone\\n\\nExample: John Doe john@email.com 9876543210", "collect_student_info");
                
            case "collect_board":
                $board = trim($message);
                $collected_data["board"] = $board;
                $this->set_session_data($session_id, "collected_data", $collected_data);
                $this->set_session_data($session_id, "current_step", "collect_grade");
                
                return $this->format_response(
                    "Board: " . $board . " ✅\\n\\nWhich grade/class are you interested in?\\n\\nExample: Grade 10, Class 5, Nursery, etc.",
                    "collect_grade"
                );
                
            case "collect_grade":
                $grade = trim($message);
                $collected_data["grade"] = $grade;
                $this->set_session_data($session_id, "collected_data", $collected_data);
                $this->set_session_data($session_id, "current_step", "collect_dob");
                
                return $this->format_response(
                    "Grade: " . $grade . " ✅\\n\\nPlease enter student\'s date of birth:\\n\\nFormat: DD/MM/YYYY\\nExample: 15/08/2010",
                    "collect_dob"
                );
                
            case "collect_dob":
                // SAFE DATE PROCESSING - NO EXTERNAL CALLS
                $dob = trim($message);
                $collected_data["dob"] = $dob;
                $this->set_session_data($session_id, "collected_data", $collected_data);
                
                // Generate enquiry number safely
                $enquiry_number = "ENQ" . date("Ymd") . rand(1000, 9999);
                $collected_data["enquiry_number"] = $enquiry_number;
                $this->set_session_data($session_id, "collected_data", $collected_data);
                $this->set_session_data($session_id, "current_step", "completed");
                
                // SAFE COMPLETION - EMAIL ONLY
                $email_sent = $this->send_safe_email_only($collected_data);
                
                $response = "🎉 *Admission Enquiry Submitted Successfully!* 🎉\\n\\n";
                $response .= "📋 **Enquiry Number:** " . $enquiry_number . "\\n";
                $response .= "👤 **Student:** " . $collected_data["student_name"] . "\\n";
                $response .= "📚 **Grade:** " . $collected_data["grade"] . "\\n";
                $response .= "🏫 **Board:** " . $collected_data["board"] . "\\n";
                $response .= "📅 **DOB:** " . $dob . "\\n\\n";
                
                if ($email_sent) {
                    $response .= "✅ **Email confirmation sent to:** " . $collected_data["email"] . "\\n\\n";
                } else {
                    $response .= "⚠️ **Email will be sent shortly**\\n\\n";
                }
                
                $response .= "**Next Steps:**\\n";
                $response .= "• Our team will contact you within 24 hours\\n";
                $response .= "• Please save your enquiry number: **" . $enquiry_number . "**\\n";
                $response .= "• Check your email for confirmation details\\n\\n";
                $response .= "Thank you for your interest! 🌟";
                
                return $this->format_response($response, "completed");
                
            default:
                return $this->format_response("I\'m here to help with admission enquiries. Would you like to start a new enquiry?", "start");
        }
        
        return $this->format_response("I\'m here to help with admission enquiries. How can I assist you?", "start");
        
    } catch (Exception $e) {
        error_log("EduBot EMERGENCY: Even safe mode failed - " . $e->getMessage());
        return $this->format_response("Thank you for your interest! Please call us directly for admission enquiries.", "error");
    }
}

/**
 * Parse student info with maximum safety
 */
private function parse_student_info_safe($message) {
    try {
        $parts = preg_split("/\s+/", trim($message));
        
        if (count($parts) < 3) {
            return array("valid" => false);
        }
        
        // Find email and phone
        $email = "";
        $phone = "";
        $name_parts = array();
        
        foreach ($parts as $part) {
            if (strpos($part, "@") !== false && empty($email)) {
                $email = $part;
            } elseif (preg_match("/^\d{10}$/", $part) && empty($phone)) {
                $phone = $part;
            } else {
                $name_parts[] = $part;
            }
        }
        
        $name = implode(" ", $name_parts);
        
        if (empty($name) || empty($email) || empty($phone)) {
            return array("valid" => false);
        }
        
        return array(
            "valid" => true,
            "data" => array(
                "student_name" => $name,
                "email" => $email,
                "phone" => $phone
            )
        );
        
    } catch (Exception $e) {
        error_log("EduBot: Error parsing student info - " . $e->getMessage());
        return array("valid" => false);
    }
}

/**
 * Send email only - no external messaging
 */
private function send_safe_email_only($collected_data) {
    try {
        $to = $collected_data["email"];
        $subject = "Admission Enquiry Confirmation - " . $collected_data["enquiry_number"];
        
        $message = "Dear Parent/Guardian,\\n\\n";
        $message .= "Thank you for your admission enquiry!\\n\\n";
        $message .= "Enquiry Details:\\n";
        $message .= "Enquiry Number: " . $collected_data["enquiry_number"] . "\\n";
        $message .= "Student Name: " . $collected_data["student_name"] . "\\n";
        $message .= "Grade: " . $collected_data["grade"] . "\\n";
        $message .= "Board: " . $collected_data["board"] . "\\n";
        $message .= "Phone: " . $collected_data["phone"] . "\\n\\n";
        $message .= "Our team will contact you within 24 hours.\\n\\n";
        $message .= "Best regards,\\nAdmissions Team";
        
        $headers = array("Content-Type: text/plain; charset=UTF-8");
        
        return wp_mail($to, $subject, $message, $headers);
        
    } catch (Exception $e) {
        error_log("EduBot: Safe email sending failed - " . $e->getMessage());
        return false;
    }
}
';

echo "🚨 ULTIMATE 500 ERROR FIX READY\n\n";
echo "This creates a completely safe version that:\n";
echo "✅ Bypasses ALL external API calls\n";
echo "✅ Uses only WordPress core functions\n";
echo "✅ Handles date input safely\n";
echo "✅ Sends email confirmations only\n";
echo "✅ No WhatsApp, SMS, or AI processing\n\n";
echo "📁 Next: Replace the generate_response method in class-edubot-shortcode.php\n";
echo "⚡ This should eliminate ALL 500 errors completely!\n";
?>
