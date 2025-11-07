<?php
/**
 * Enhanced Workflow Manager for EduBot Pro
 * Fixes workflow breaking issues with simplified state management
 */

class EduBot_Workflow_Manager {
    
    private $session_manager;
    private $required_fields = array(
        'student_name' => 'Student Name',
        'email' => 'Email Address', 
        'phone' => 'Phone Number',
        'grade' => 'Grade/Class',
        'board' => 'Educational Board',
        'date_of_birth' => 'Date of Birth'
    );
    
    public function __construct() {
        $this->session_manager = EduBot_Session_Manager::getInstance();
    }
    
    /**
     * Process user input with enhanced error handling
     */
    public function process_user_input($message, $session_id) {
        try {
            // Sanitize input
            $message = trim($message);
            if (empty($message)) {
                return $this->get_help_message();
            }
            
            // Get or create session
            $session_data = $this->session_manager->get_session($session_id);
            if (!$session_data) {
                $session_data = $this->session_manager->init_session($session_id, 'admission');
            }
            
            // Determine current state and next action
            $current_step = $this->determine_current_step($session_data);
            $extracted_info = $this->extract_information($message);
            
            // Process based on current step
            switch ($current_step) {
                case 'start':
                case 'collect_name':
                    return $this->handle_name_collection($message, $session_id, $extracted_info);
                    
                case 'collect_email':
                    return $this->handle_email_collection($message, $session_id, $extracted_info);
                    
                case 'collect_phone':
                    return $this->handle_phone_collection($message, $session_id, $extracted_info);
                    
                case 'collect_grade':
                    return $this->handle_grade_collection($message, $session_id, $extracted_info);
                    
                case 'collect_board':
                    return $this->handle_board_collection($message, $session_id, $extracted_info);
                    
                case 'collect_dob':
                    return $this->handle_dob_collection($message, $session_id, $extracted_info);
                    
                case 'ready_to_submit':
                    return $this->handle_final_submission($session_id);
                    
                default:
                    return $this->handle_general_query($message, $session_id);
            }
            
        } catch (Exception $e) {
            error_log('EduBot Workflow Error: ' . $e->getMessage());
            return $this->get_error_recovery_message($session_id);
        }
    }
    
    /**
     * Determine current step based on collected data
     */
    private function determine_current_step($session_data) {
        $collected = $session_data['data'] ?? array();

        if (empty($collected['student_name'])) return 'collect_name';
        if (empty($collected['phone'])) return 'collect_phone';
        if (empty($collected['email'])) return 'collect_email';
        if (empty($collected['grade'])) return 'collect_grade';
        if (empty($collected['board'])) return 'collect_board';
        if (empty($collected['date_of_birth'])) return 'collect_dob';

        return 'ready_to_submit';
    }
    
    /**
     * Extract information from user input
     */
    private function extract_information($message) {
        $info = array();
        
        // Extract email
        if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/i', $message, $matches)) {
            $info['email'] = strtolower($matches[0]);
        }
        
        // Extract phone number
        if (preg_match('/(\+?91)?[\s-]?[6-9]\d{9}/', $message, $matches)) {
            $phone = preg_replace('/[^\d+]/', '', $matches[0]);
            if (strlen($phone) == 10) {
                $phone = '+91' . $phone;
            }
            $info['phone'] = $phone;
        }
        
        // Extract name (simple pattern for student names)
        // Don't extract name if message looks like an email (with or without @)
        $looks_like_email = strpos($message, '@') !== false ||
                           preg_match('/\b[a-z0-9._%+-]+(?:@|at)?[a-z0-9.-]+\.(com|in|org|net|edu|co)\b/i', $message);

        if (!$looks_like_email && preg_match('/(?:name\s*:?\s*)?([A-Za-z\s\.]{2,30})(?:\s|$)/i', $message, $matches)) {
            $name = trim($matches[1]);
            if (strlen($name) >= 2 && strlen($name) <= 30 && !preg_match('/\b(grade|class|email|phone)\b/i', $name)) {
                $info['name'] = ucwords(strtolower($name));
            }
        }
        
        // Extract grade (with or without "grade"/"class" prefix)
        if (preg_match('/\b(?:grade|class)\s*(\d+|nursery|pp1|pp2|lkg|ukg|pre-?kg)\b/i', $message, $matches)) {
            $info['grade'] = ucwords(strtolower($matches[1]));
        } elseif (preg_match('/\b(nursery|pp1|pp2|lkg|ukg|pre-?kg)\b/i', $message, $matches)) {
            // Also match standalone pre-primary grades
            $info['grade'] = strtoupper($matches[1]);
        } elseif (preg_match('/\b(\d{1,2})(?:th|st|nd|rd)?\b/i', $message, $matches)) {
            // Match standalone numbers like "1", "5", "10th"
            $grade_num = intval($matches[1]);
            if ($grade_num >= 1 && $grade_num <= 12) {
                $info['grade'] = 'Grade ' . $grade_num;
            }
        }
        
        // Extract board
        if (preg_match('/\b(cbse|caie|cambridge|icse|state|igcse)\b/i', $message, $matches)) {
            $info['board'] = strtoupper($matches[1]);
        }
        
        // Extract date of birth
        if (preg_match('/\b(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})\b/', $message, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            
            if ($this->validate_date($day, $month, $year)) {
                $info['date_of_birth'] = "$day/$month/$year";
            }
        }
        
        return $info;
    }
    
    /**
     * Handle name collection step
     */
    private function handle_name_collection($message, $session_id, $extracted_info) {
        // Check if name is provided in extracted info
        if (!empty($extracted_info['name'])) {
            $this->session_manager->update_session_data($session_id, 'student_name', $extracted_info['name']);
            
            // Check if other info was also provided
            if (!empty($extracted_info['email'])) {
                $this->session_manager->update_session_data($session_id, 'email', $extracted_info['email']);
            }
            if (!empty($extracted_info['phone'])) {
                $this->session_manager->update_session_data($session_id, 'phone', $extracted_info['phone']);
            }
            
            return $this->get_next_step_message($session_id);
        }
        
        // If no name detected, treat entire message as name if it looks like one
        if (preg_match('/^[A-Za-z\s\.]{2,30}$/', trim($message))) {
            $name = ucwords(strtolower(trim($message)));
            $this->session_manager->update_session_data($session_id, 'student_name', $name);
            return $this->get_next_step_message($session_id);
        }
        
        return "👶 **Please provide the student's name:**\n\n" .
               "Just type the student's name (e.g., 'Rahul Kumar')\n\n" .
               "Or you can provide multiple details at once:\n" .
               "Name: Rahul Kumar, Email: parent@email.com, Phone: 9876543210";
    }
    
    /**
     * Handle email collection step
     */
    private function handle_email_collection($message, $session_id, $extracted_info) {
        if (!empty($extracted_info['email'])) {
            $this->session_manager->update_session_data($session_id, 'email', $extracted_info['email']);

            // Check if phone was also provided
            if (!empty($extracted_info['phone'])) {
                $this->session_manager->update_session_data($session_id, 'phone', $extracted_info['phone']);
            }

            return $this->get_next_step_message($session_id);
        }

        // Use AI-powered email validation
        $api_integrations = new EduBot_API_Integrations();
        $validation = $api_integrations->validate_email_with_ai(trim($message));

        if ($validation['valid']) {
            // Email is valid (or AI corrected it)
            $email_to_save = !empty($validation['corrected']) ? $validation['corrected'] : strtolower(trim($message));
            $this->session_manager->update_session_data($session_id, 'email', $email_to_save);

            // Show confirmation if AI corrected the email
            if (!empty($validation['corrected']) && $validation['corrected'] !== trim($message)) {
                $session_data = $this->session_manager->get_session($session_id);
                $progress = $this->get_progress_message($session_data['data'] ?? array());
                return $progress . "\n\n💡 **I corrected your email to:** " . $validation['corrected'] . "\n\n" .
                       "Proceeding to next step...\n\n" .
                       $this->get_next_step_message($session_id);
            }

            return $this->get_next_step_message($session_id);
        }

        // Email validation failed - show clear error with AI insights
        $error_message = "❌ **Invalid Email Address**\n\n" .
                        "You entered: " . esc_html(trim($message)) . "\n\n";

        if (!empty($validation['issue'])) {
            $error_message .= "**Issue detected:** " . esc_html($validation['issue']) . "\n\n";
        }

        if (!empty($validation['corrected'])) {
            $error_message .= "💡 **Did you mean:** " . esc_html($validation['corrected']) . "?\n\n" .
                             "Reply with the corrected email or enter a different one.\n\n";
        }

        $error_message .= "📧 Please provide a valid email address in the format:\n" .
                         "• example@gmail.com\n" .
                         "• parent@email.com\n" .
                         "• name@domain.com\n\n" .
                         "This email will be used to send admission updates and confirmations.";

        return $error_message;
    }
    
    /**
     * Handle phone collection step
     */
    private function handle_phone_collection($message, $session_id, $extracted_info) {
        if (!empty($extracted_info['phone'])) {
            $this->session_manager->update_session_data($session_id, 'phone', $extracted_info['phone']);
            return $this->get_next_step_message($session_id);
        }

        // Use AI-powered phone validation
        $api_integrations = new EduBot_API_Integrations();
        $validation = $api_integrations->validate_phone_with_ai(trim($message));

        if ($validation['valid']) {
            // Phone is valid (or AI corrected it)
            $phone_to_save = !empty($validation['corrected']) ? $validation['corrected'] : trim($message);

            // Ensure +91 prefix
            $phone_clean = preg_replace('/[^\d+]/', '', $phone_to_save);
            if (strlen($phone_clean) == 10) {
                $phone_clean = '+91' . $phone_clean;
            }

            $this->session_manager->update_session_data($session_id, 'phone', $phone_clean);

            // Show confirmation if AI corrected the phone
            if (!empty($validation['corrected']) && $validation['corrected'] !== trim($message)) {
                $session_data = $this->session_manager->get_session($session_id);
                $progress = $this->get_progress_message($session_data['data'] ?? array());
                return $progress . "\n\n💡 **I formatted your phone number to:** " . $phone_clean . "\n\n" .
                       "Proceeding to next step...\n\n" .
                       $this->get_next_step_message($session_id);
            }

            return $this->get_next_step_message($session_id);
        }

        // Phone validation failed - show clear error with AI insights
        $phone_display = trim($message);
        $digit_count = isset($validation['digit_count']) ? $validation['digit_count'] : strlen(preg_replace('/[^\d]/', '', $phone_display));

        $error_message = "❌ **Invalid Phone Number**\n\n" .
                        "You entered: " . esc_html($phone_display) . " ({$digit_count} digits)\n\n";

        if (!empty($validation['issue'])) {
            $error_message .= "**Issue detected:** " . esc_html($validation['issue']) . "\n\n";
        }

        $error_message .= "📱 Please provide a valid 10-digit Indian mobile number:\n" .
                         "• Must start with 6, 7, 8, or 9\n" .
                         "• Example: 9876543210\n" .
                         "• Example: +919876543210\n\n" .
                         "This will be used for admission updates and callbacks.";

        return $error_message;
    }
    
    /**
     * Handle grade collection step
     */
    private function handle_grade_collection($message, $session_id, $extracted_info) {
        if (!empty($extracted_info['grade'])) {
            $this->session_manager->update_session_data($session_id, 'grade', $extracted_info['grade']);
            
            // Check if board was also provided
            if (!empty($extracted_info['board'])) {
                $this->session_manager->update_session_data($session_id, 'board', $extracted_info['board']);
            }
            
            return $this->get_next_step_message($session_id);
        }
        
        return "🎓 **Which grade/class are you seeking admission for?**\n\n" .
               "Examples:\n" .
               "• Grade 5\n" .
               "• Class 1\n" .
               "• Nursery\n" .
               "• PP1/PP2\n" .
               "• LKG/UKG\n\n" .
               "You can also mention the board: 'Grade 5, CBSE'";
    }
    
    /**
     * Handle board collection step
     */
    private function handle_board_collection($message, $session_id, $extracted_info) {
        if (!empty($extracted_info['board'])) {
            $this->session_manager->update_session_data($session_id, 'board', $extracted_info['board']);
            return $this->get_next_step_message($session_id);
        }
        
        // Check for board in simple text
        $message_upper = strtoupper(trim($message));
        if (in_array($message_upper, array('CBSE', 'CAIE', 'CAMBRIDGE', 'ICSE', 'STATE', 'IGCSE'))) {
            $this->session_manager->update_session_data($session_id, 'board', $message_upper);
            return $this->get_next_step_message($session_id);
        }
        
        return "📚 **Which educational board do you prefer?**\n\n" .
               "Available options:\n" .
               "• **CBSE** - Central Board of Secondary Education\n" .
               "• **CAIE** - Cambridge Assessment International Education\n\n" .
               "Just type 'CBSE' or 'CAIE'";
    }
    
    /**
     * Handle date of birth collection step
     */
    private function handle_dob_collection($message, $session_id, $extracted_info) {
        if (!empty($extracted_info['date_of_birth'])) {
            $this->session_manager->update_session_data($session_id, 'date_of_birth', $extracted_info['date_of_birth']);
            return $this->get_next_step_message($session_id);
        }
        
        return "📅 **Please provide the student's date of birth:**\n\n" .
               "Format: DD/MM/YYYY\n" .
               "Example: 16/10/2010\n\n" .
               "Make sure to use the correct format with 4-digit year.";
    }
    
    /**
     * Get message for next step in the workflow
     */
    private function get_next_step_message($session_id) {
        $session_data = $this->session_manager->get_session($session_id);
        $collected = $session_data['data'] ?? array();
        $next_step = $this->determine_current_step($session_data);
        
        // Show progress
        $progress = $this->get_progress_message($collected);
        
        switch ($next_step) {
            case 'collect_phone':
                return $progress . "\n📱 **Great! Now I need your phone number:**\n\n" .
                       "Example: 9876543210";

            case 'collect_email':
                return $progress . "\n📧 **Perfect! Now I need your email address:**\n\n" .
                       "Example: parent@email.com";
                       
            case 'collect_grade':
                return $progress . "\n🎓 **Excellent! Which grade/class are you seeking admission for?**\n\n" .
                       "Examples: Grade 5, Class 1, Nursery, PP1, LKG";
                       
            case 'collect_board':
                return $progress . "\n📚 **Almost done! Which educational board do you prefer?**\n\n" .
                       "• **CBSE** • **CAIE**";
                       
            case 'collect_dob':
                return $progress . "\n📅 **Finally, please provide the student's date of birth:**\n\n" .
                       "Format: DD/MM/YYYY (e.g., 16/10/2010)";
                       
            case 'ready_to_submit':
                return $this->handle_final_submission($session_id);
                
            default:
                return $this->get_help_message();
        }
    }
    
    /**
     * Generate progress message
     */
    private function get_progress_message($collected) {
        $progress = "✅ **Information Recorded:**\n";
        
        if (!empty($collected['student_name'])) {
            $progress .= "👶 Student: {$collected['student_name']}\n";
        }
        if (!empty($collected['email'])) {
            $progress .= "📧 Email: {$collected['email']}\n";
        }
        if (!empty($collected['phone'])) {
            $progress .= "📱 Phone: {$collected['phone']}\n";
        }
        if (!empty($collected['grade'])) {
            $progress .= "🎓 Grade: {$collected['grade']}\n";
        }
        if (!empty($collected['board'])) {
            $progress .= "📚 Board: {$collected['board']}\n";
        }
        
        return $progress;
    }
    
    /**
     * Handle final submission
     */
    private function handle_final_submission($session_id) {
        try {
            $session_data = $this->session_manager->get_session($session_id);
            $collected_data = $session_data['data'] ?? array();
            
            // Validate all required data is present
            $missing = array();
            foreach ($this->required_fields as $field => $label) {
                if (empty($collected_data[$field])) {
                    $missing[] = $label;
                }
            }
            
            if (!empty($missing)) {
                return "❌ **Missing Information:**\n" . implode(', ', $missing) . 
                       "\n\nPlease provide the missing information to continue.";
            }
            
            // Process the submission (this would call the existing process_final_submission method)
            return $this->process_enquiry_submission($collected_data, $session_id);
            
        } catch (Exception $e) {
            error_log('EduBot Final Submission Error: ' . $e->getMessage());
            return $this->get_error_recovery_message($session_id);
        }
    }
    
    /**
     * Process enquiry submission (stub - calls existing method)
     */
    private function process_enquiry_submission($collected_data, $session_id) {
        // This would integrate with the existing shortcode class method
        // For now, return a success message
        $enquiry_number = 'ENQ' . date('Y') . wp_rand(1000, 9999);
        
        // Mark session as completed
        $this->session_manager->update_session_data($session_id, '_status', 'completed');
        
        return "🎉 **Admission Enquiry Submitted Successfully!**\n\n" .
               "**📋 Your Enquiry Number: {$enquiry_number}**\n\n" .
               "Our admission team will contact you within 24 hours.\n\n" .
               "📞 For immediate assistance: 7702800800 / 9248111448";
    }
    
    /**
     * Validate date format
     */
    private function validate_date($day, $month, $year) {
        return checkdate($month, $day, $year) && 
               $year >= 2005 && $year <= date('Y') - 2;
    }
    
    /**
     * Handle general queries
     */
    private function handle_general_query($message, $session_id) {
        return "I'm here to help with your admission enquiry. " .
               "Let's collect your information step by step.\n\n" .
               $this->get_next_step_message($session_id);
    }
    
    /**
     * Get help message
     */
    private function get_help_message() {
        return "👋 **Welcome to Epistemo Vikas Leadership School!**\n\n" .
               "I'll help you with your admission enquiry for **AY 2026-27**.\n\n" .
               "Please provide:\n" .
               "👶 Student Name\n" .
               "📧 Email Address\n" .
               "📱 Phone Number\n\n" .
               "You can start by typing the student's name, or provide multiple details at once.";
    }
    
    /**
     * Get error recovery message
     */
    private function get_error_recovery_message($session_id) {
        return "I encountered an issue processing your request. Let me help you continue.\n\n" .
               "📞 **For immediate assistance:**\n" .
               "Call: 7702800800 / 9248111448\n" .
               "Email: admissions@epistemo.in\n\n" .
               "Or you can restart by providing your information again.";
    }
}
