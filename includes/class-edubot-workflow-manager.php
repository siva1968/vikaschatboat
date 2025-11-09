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
                    
                case 'collect_academic_year':
                    return $this->handle_academic_year_collection($message, $session_id, $extracted_info);
                    
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
        
        // Check if multiple academic years are available - if yes, ask user to select
        $school_config = EduBot_School_Config::getInstance();
        $available_years = $school_config->get_available_academic_years();
        if (count($available_years) > 1 && empty($collected['academic_year'])) {
            return 'collect_academic_year';
        }
        
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
     * Handle academic year selection step
     */
    private function handle_academic_year_collection($message, $session_id, $extracted_info) {
        $school_config = EduBot_School_Config::getInstance();
        $available_years = $school_config->get_available_academic_years();
        
        // Try to extract year selection as a number (1, 2, etc.)
        $message_trimmed = trim($message);
        if (is_numeric($message_trimmed)) {
            $year_index = intval($message_trimmed) - 1; // Convert to 0-based index
            
            if ($year_index >= 0 && $year_index < count($available_years)) {
                $selected_year = $available_years[$year_index];
                $this->session_manager->update_session_data($session_id, 'academic_year', $selected_year);
                return $this->get_next_step_message($session_id);
            }
        }
        
        // Invalid selection, show menu again
        $year_options = "";
        foreach ($available_years as $idx => $year) {
            $year_options .= "• " . ($idx + 1) . ": " . $year . "\n";
        }
        
        return "❌ **Please select a valid year number**\n\n" .
               "Available options:\n" .
               $year_options . "\n" .
               "Reply with the number (1, 2, etc.)";
    }
    
    /**
     * Handle date of birth collection step
     */
    private function handle_dob_collection($message, $session_id, $extracted_info) {
        if (!empty($extracted_info['date_of_birth'])) {
            $this->session_manager->update_session_data($session_id, 'date_of_birth', $extracted_info['date_of_birth']);
            return $this->get_next_step_message($session_id);
        }
        
        // Check if user provided a date but it's invalid
        if (preg_match('/\d{1,2}[-\/]\d{1,2}[-\/]\d{4}/', $message, $matches)) {
            return "❌ **Invalid Date**\n\n" .
                   "You entered: {$matches[0]}\n\n" .
                   "Please provide a valid date of birth in DD/MM/YYYY format.\n" .
                   "Student should be between 2-20 years old.\n\n" .
                   "Example: 16/10/2010";
        }
        
        return "📅 **Please provide the student's date of birth:**\n\n" .
               "Format: DD/MM/YYYY\n" .
               "Example: 16/10/2010\n\n" .
               "Student should be between 2-20 years old.";
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
                       "Examples: Grade 5, Class 1, Nursery, PP1";
                       
            case 'collect_board':
                return $progress . "\n📚 **Almost done! Which educational board do you prefer?**\n\n" .
                       "• **CBSE** • **CAIE**";
                       
            case 'collect_academic_year':
                // Get available years and show selection menu
                $school_config = EduBot_School_Config::getInstance();
                $available_years = $school_config->get_available_academic_years();
                
                $year_options = "";
                foreach ($available_years as $idx => $year) {
                    $year_options .= "• " . ($idx + 1) . ": " . $year . "\n";
                }
                
                return $progress . "\n📅 **Which academic year are you applying for?**\n\n" .
                       $year_options . "\n" .
                       "Reply with the number (1, 2, etc.)";
                       
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
        if (!empty($collected['academic_year'])) {
            $progress .= "📅 Year: {$collected['academic_year']}\n";
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
     * Process enquiry submission - saves to database and sends notifications
     */
    private function process_enquiry_submission($collected_data, $session_id) {
        global $wpdb;
        
        try {
            error_log('EduBot Workflow Manager: Starting enquiry submission with data: ' . json_encode($collected_data));
            
            // Generate enquiry number
            $enquiry_number = 'ENQ' . date('Y') . wp_rand(1000, 9999);
            
            // Get school settings
            $settings = get_option('edubot_pro_settings', array());
            $school_name = isset($settings['school_name']) ? $settings['school_name'] : 'Epistemo Vikas Leadership School';
            
            // Convert DOB from DD/MM/YYYY to YYYY-MM-DD
            $dob = '';
            if (!empty($collected_data['date_of_birth'])) {
                $dob = $this->convert_date_format($collected_data['date_of_birth']);
                error_log("EduBot Workflow Manager: Converted DOB from {$collected_data['date_of_birth']} to {$dob}");
            }
            
            // Save to database
            $table_name = $wpdb->prefix . 'edubot_enquiries';
            
            // Get tracking data
            $utm_data = $this->get_utm_data();
            $ip_address = $this->get_client_ip();
            $user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
            
            // Extract click IDs
            $gclid = $utm_data['gclid'] ?? null;
            $fbclid = $utm_data['fbclid'] ?? null;
            
            // Determine source
            $source = $utm_data['utm_source'] ?? 'chatbot';
            if (!empty($source)) {
                $source = sanitize_text_field($source);
            }
            
            // Prepare click ID data
            $click_id_data = array();
            if ($gclid) {
                $click_id_data['gclid'] = $gclid;
                $click_id_data['gclid_captured_at'] = current_time('mysql');
            }
            if ($fbclid) {
                $click_id_data['fbclid'] = $fbclid;
                $click_id_data['fbclid_captured_at'] = current_time('mysql');
            }
            
            // Save enquiry to database
            $result = $wpdb->insert(
                $table_name,
                array(
                    'enquiry_number' => $enquiry_number,
                    'student_name' => $collected_data['student_name'] ?? '',
                    'date_of_birth' => $dob,
                    'grade' => $collected_data['grade'] ?? '',
                    'board' => $collected_data['board'] ?? '',
                    'academic_year' => $collected_data['academic_year'] ?? '2026-27',
                    'parent_name' => $collected_data['parent_name'] ?? '',
                    'email' => $collected_data['email'] ?? '',
                    'phone' => $collected_data['phone'] ?? '',
                    'ip_address' => $ip_address,
                    'user_agent' => $user_agent,
                    'utm_data' => wp_json_encode($utm_data),
                    'gclid' => $gclid,
                    'fbclid' => $fbclid,
                    'click_id_data' => !empty($click_id_data) ? wp_json_encode($click_id_data) : null,
                    'created_at' => current_time('mysql'),
                    'status' => 'pending',
                    'source' => $source
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                error_log('EduBot Workflow Manager: Failed to save enquiry to database: ' . $wpdb->last_error);
                throw new Exception('Database insert failed: ' . $wpdb->last_error);
            }
            
            $enquiry_id = $wpdb->insert_id;
            error_log("EduBot Workflow Manager: Successfully saved enquiry {$enquiry_number} to database with ID {$enquiry_id}");
            
            // Trigger MCB sync (if enabled)
            try {
                do_action('edubot_enquiry_submitted', $enquiry_id);
                error_log("EduBot Workflow Manager: MCB sync triggered for enquiry {$enquiry_id}");
            } catch (Exception $mcb_error) {
                error_log("EduBot Workflow Manager: Exception during MCB sync trigger: " . $mcb_error->getMessage());
            }
            
            // Save to applications table
            try {
                $this->save_to_applications_table($collected_data, $enquiry_number);
                error_log("EduBot Workflow Manager: Successfully saved to applications table");
            } catch (Exception $app_error) {
                error_log("EduBot Workflow Manager: Exception when saving to applications table: " . $app_error->getMessage());
            }
            
            // Try to send notifications
            try {
                $this->send_notifications($collected_data, $enquiry_number, $school_name, $enquiry_id);
            } catch (Exception $notif_error) {
                error_log("EduBot Workflow Manager: Exception when sending notifications: " . $notif_error->getMessage());
            }
            
            // Mark session as completed
            $this->session_manager->update_session_data($session_id, '_status', 'completed');
            
            // Clear session data after successful submission so chatbot is ready for new enquiry
            // This allows the same session to start fresh for a new admission enquiry
            $this->session_manager->clear_session($session_id);
            
            return "🎉 **Admission Enquiry Submitted Successfully!**\n\n" .
                   "**📋 Your Enquiry Number: {$enquiry_number}**\n\n" .
                   "Our admission team will contact you within 24 hours.\n\n" .
                   "📞 For immediate assistance: 7702800800 / 9248111448";
            
        } catch (Exception $e) {
            error_log('EduBot Workflow Manager: Exception in process_enquiry_submission: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Convert date from DD/MM/YYYY to YYYY-MM-DD
     */
    private function convert_date_format($date_string) {
        // Try to parse DD/MM/YYYY format
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date_string, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            
            // Validate date
            if (checkdate($month, $day, $year)) {
                return "{$year}-{$month}-{$day}";
            }
        }
        
        // If parsing fails, return empty or the original string
        error_log("EduBot Workflow Manager: Could not parse date: {$date_string}");
        return '';
    }
    
    /**
     * Get UTM tracking data
     */
    private function get_utm_data() {
        $utm_data = array();
        $utm_params = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid');
        
        foreach ($utm_params as $param) {
            // First check $_GET (immediate parameters in URL)
            if (!empty($_GET[$param])) {
                $utm_data[$param] = sanitize_text_field($_GET[$param]);
                error_log("EduBot get_utm_data: Found {$param} in \$_GET: " . $utm_data[$param]);
            }
            // If not in $_GET, check cookies (from previous page visit with UTM params)
            elseif (!empty($_COOKIE['edubot_' . $param])) {
                $utm_data[$param] = sanitize_text_field($_COOKIE['edubot_' . $param]);
                error_log("EduBot get_utm_data: Found {$param} in COOKIE: " . $utm_data[$param]);
            }
        }
        
        error_log("EduBot get_utm_data: Final UTM data collected: " . wp_json_encode($utm_data));
        
        return $utm_data;
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return sanitize_text_field(trim($ips[0]));
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        return '';
    }
    
    /**
     * Save to applications table (using Database Manager)
     */
    private function save_to_applications_table($collected_data, $enquiry_number) {
        try {
            // Use Database Manager to properly format and save application
            if (!class_exists('EduBot_Database_Manager')) {
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-database-manager.php';
            }
            
            $database_manager = new EduBot_Database_Manager();
            
            // Prepare student data in the format expected by applications table
            $student_data = array(
                'student_name' => !empty($collected_data['student_name']) ? $collected_data['student_name'] : 'Not Provided',
                'date_of_birth' => !empty($collected_data['date_of_birth']) ? $collected_data['date_of_birth'] : '',
                'grade' => !empty($collected_data['grade']) ? $collected_data['grade'] : 'Not Provided',
                'educational_board' => !empty($collected_data['board']) ? $collected_data['board'] : 'Not Provided',
                'academic_year' => !empty($collected_data['academic_year']) ? $collected_data['academic_year'] : '2026-27',
                'parent_name' => !empty($collected_data['parent_name']) ? $collected_data['parent_name'] : 'Not Provided',
                'email' => !empty($collected_data['email']) ? $collected_data['email'] : 'Not Provided',
                'phone' => !empty($collected_data['phone']) ? $collected_data['phone'] : '',
                'address' => !empty($collected_data['address']) ? $collected_data['address'] : '',
                'gender' => !empty($collected_data['gender']) ? $collected_data['gender'] : ''
            );

            error_log('EduBot Workflow Manager: Student data prepared: ' . wp_json_encode($student_data));

            // Collect UTM data from GET parameters
            $utm_data = $this->get_utm_data();
            $gclid = $utm_data['gclid'] ?? null;
            $fbclid = $utm_data['fbclid'] ?? null;
            
            // Build click_id_data
            $click_id_data = array();
            if ($gclid) {
                $click_id_data['gclid'] = $gclid;
                $click_id_data['gclid_captured_at'] = current_time('mysql');
            }
            if ($fbclid) {
                $click_id_data['fbclid'] = $fbclid;
                $click_id_data['fbclid_captured_at'] = current_time('mysql');
            }

            error_log('EduBot Workflow Manager: UTM data collected for applications table: ' . wp_json_encode($utm_data));

            $application_data = array(
                'application_number' => $enquiry_number,
                'student_data' => $student_data,
                'conversation_log' => array(),
                'status' => 'pending',
                'source' => 'chatbot',
                'utm_data' => wp_json_encode($utm_data),
                'gclid' => $gclid,
                'fbclid' => $fbclid,
                'click_id_data' => wp_json_encode($click_id_data)
            );

            error_log('EduBot Workflow Manager: Application data for save: ' . wp_json_encode($application_data));

            $result = $database_manager->save_application($application_data);
            
            if (is_wp_error($result)) {
                error_log('EduBot Workflow Manager: Failed to save to applications table: ' . $result->get_error_message());
            } else {
                error_log("EduBot Workflow Manager: Successfully saved {$enquiry_number} to applications table with ID: {$result}");
            }
            
        } catch (Exception $e) {
            error_log('EduBot Workflow Manager: Exception during applications table save: ' . $e->getMessage());
            // Don't throw - enquiry already saved
        }
    }
    
    /**
     * Send email and WhatsApp notifications
     */
    private function send_notifications($collected_data, $enquiry_number, $school_name, $enquiry_id) {
        error_log("EduBot Workflow Manager: Starting notification process for {$enquiry_number}");
        
        // Send email if enabled
        if (get_option('edubot_email_notifications', 0)) {
            try {
                error_log("EduBot Workflow Manager: Email notifications enabled, sending email for {$enquiry_number}");
                $email_sent = $this->send_parent_confirmation_email($collected_data, $enquiry_number, $school_name);
                
                if ($email_sent && $enquiry_id && class_exists('EduBot_Database_Manager')) {
                    $db_manager = new EduBot_Database_Manager();
                    $db_manager->update_notification_status($enquiry_id, 'email', 1, 'enquiries');
                    error_log("EduBot Workflow Manager: Email notification status marked as sent for {$enquiry_number}");
                }
            } catch (Exception $e) {
                error_log("EduBot Workflow Manager: Exception during email sending: " . $e->getMessage());
            }
        } else {
            error_log("EduBot Workflow Manager: Email notifications disabled");
        }
        
        // Send WhatsApp if enabled
        if (get_option('edubot_whatsapp_notifications', 0)) {
            try {
                error_log("EduBot Workflow Manager: WhatsApp notifications enabled, sending WhatsApp for {$enquiry_number}");
                $whatsapp_sent = $this->send_parent_whatsapp_confirmation($collected_data, $enquiry_number, $school_name);
                
                if ($whatsapp_sent && $enquiry_id && class_exists('EduBot_Database_Manager')) {
                    $db_manager = new EduBot_Database_Manager();
                    $db_manager->update_notification_status($enquiry_id, 'whatsapp', 1, 'enquiries');
                    error_log("EduBot Workflow Manager: WhatsApp notification status marked as sent for {$enquiry_number}");
                }
            } catch (Exception $e) {
                error_log("EduBot Workflow Manager: Exception during WhatsApp sending: " . $e->getMessage());
            }
        } else {
            error_log("EduBot Workflow Manager: WhatsApp notifications disabled");
        }
        
        // Send school notifications if enabled
        if (get_option('edubot_school_notifications', 0)) {
            try {
                error_log("EduBot Workflow Manager: School notifications enabled, sending to admin");
                $this->send_school_enquiry_notification($collected_data, $enquiry_number, $school_name);
            } catch (Exception $e) {
                error_log("EduBot Workflow Manager: Exception during school notification: " . $e->getMessage());
            }
        }
        
        if (get_option('edubot_school_whatsapp_notifications', 0)) {
            try {
                error_log("EduBot Workflow Manager: School WhatsApp notifications enabled, sending to admin");
                $this->send_school_whatsapp_notification($collected_data, $enquiry_number, $school_name);
            } catch (Exception $e) {
                error_log("EduBot Workflow Manager: Exception during school WhatsApp notification: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Send parent confirmation email via ZeptoMail API
     */
    private function send_parent_confirmation_email($collected_data, $enquiry_number, $school_name) {
        error_log("EduBot Workflow Manager: Attempting to send parent confirmation email for {$enquiry_number}");
        
        try {
            global $wpdb;
            
            $parent_email = $collected_data['email'] ?? '';
            
            if (empty($parent_email)) {
                error_log("EduBot Workflow Manager: No parent email found for {$enquiry_number}");
                return false;
            }
            
            // Get email configuration from wp_edubot_api_integrations table
            $api_config = $wpdb->get_row(
                "SELECT email_provider, email_api_key, email_from_address FROM {$wpdb->prefix}edubot_api_integrations WHERE status = 'active' LIMIT 1"
            );
            
            if (empty($api_config) || empty($api_config->email_api_key)) {
                error_log("EduBot Workflow Manager: Email API not configured");
                return false;
            }
            
            error_log("EduBot Workflow Manager: Email provider: {$api_config->email_provider}");
            
            // For ZeptoMail, use the API directly
            if ($api_config->email_provider === 'zeptomail') {
                return $this->send_zeptomail_email($parent_email, $enquiry_number, $school_name, $collected_data, $api_config->email_api_key);
            }
            
            // Fallback for other providers
            error_log("EduBot Workflow Manager: Unsupported email provider: {$api_config->email_provider}");
            return false;
            
        } catch (Exception $e) {
            error_log("EduBot Workflow Manager: Exception in send_parent_confirmation_email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email via ZeptoMail API with correct authorization header
     */
    private function send_zeptomail_email($parent_email, $enquiry_number, $school_name, $collected_data, $api_key) {
        try {
            // Build email content
            $email_subject = "Admission Enquiry Confirmation - {$enquiry_number}";
            $email_body = $this->build_parent_confirmation_email($collected_data, $enquiry_number, $school_name);
            
            // Get admin email for from address - use noreply@epistemo.in (verified sender in ZeptoMail)
            $admin_email = get_option('edubot_admin_contact_email', 'noreply@epistemo.in');
            
            // Prepare ZeptoMail payload
            $payload = array(
                'from' => array(
                    'address' => $admin_email
                ),
                'to' => array(
                    array(
                        'email_address' => array(
                            'address' => $parent_email
                        )
                    )
                ),
                'subject' => $email_subject,
                'htmlbody' => $email_body
            );
            
            // Make API call to ZeptoMail with correct authorization header format
            $response = wp_remote_post(
                'https://api.zeptomail.in/v1.1/email',
                array(
                    'headers' => array(
                        'Authorization' => 'Zoho-enczapikey ' . $api_key,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Cache-Control' => 'no-cache'
                    ),
                    'body' => json_encode($payload),
                    'timeout' => 30,
                    'sslverify' => false
                )
            );
            
            if (is_wp_error($response)) {
                error_log("EduBot ZeptoMail: Request error: " . $response->get_error_message());
                return false;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            error_log("EduBot ZeptoMail: Response code: {$response_code}");
            
            if ($response_code >= 200 && $response_code < 300) {
                error_log("EduBot Workflow Manager: Email sent successfully for {$enquiry_number} to {$parent_email}");
                return true;
            } else {
                error_log("EduBot ZeptoMail: Error response: {$response_body}");
                error_log("EduBot Workflow Manager: Email send failed for {$enquiry_number}");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("EduBot ZeptoMail: Exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send parent WhatsApp confirmation
     */
    private function send_parent_whatsapp_confirmation($collected_data, $enquiry_number, $school_name) {
        error_log("EduBot Workflow Manager: Attempting to send parent WhatsApp for {$enquiry_number}");
        
        try {
            global $wpdb;
            
            $parent_phone = $collected_data['phone'] ?? '';
            
            if (empty($parent_phone)) {
                error_log("EduBot Workflow Manager: No parent phone found for {$enquiry_number}");
                return false;
            }
            
            // Clean phone number
            $parent_phone = preg_replace('/[^0-9]/', '', $parent_phone);
            if (strlen($parent_phone) === 10) {
                $parent_phone = '91' . $parent_phone; // Add country code if not present
            }
            
            // Get WhatsApp configuration from wp_edubot_api_integrations table
            $api_config = $wpdb->get_row(
                "SELECT whatsapp_provider, whatsapp_token, whatsapp_phone_id, whatsapp_template_name FROM {$wpdb->prefix}edubot_api_integrations WHERE status = 'active' LIMIT 1"
            );
            
            if (empty($api_config) || empty($api_config->whatsapp_token)) {
                error_log("EduBot Workflow Manager: WhatsApp API not configured");
                return false;
            }
            
            error_log("EduBot Workflow Manager: WhatsApp provider: {$api_config->whatsapp_provider}");
            
            // Prepare template parameters (5 parameters in exact order for admission_confirmation)
            // Use student_name as fallback if parent_name is not provided
            $parent_name = !empty($collected_data['parent_name']) ? $collected_data['parent_name'] : ($collected_data['student_name'] ?? 'Valued Parent');
            $student_grade = $collected_data['grade'] ?? 'Not Specified';
            $submission_date = date('d/m/Y'); // DD/MM/YYYY format as expected by template
            
            // Template parameters in exact order for admission_confirmation template:
            // 1. Parent/Student Name
            // 2. Enquiry Number
            // 3. School Name
            // 4. Grade
            // 5. Date (DD/MM/YYYY format)
            $template_params = array(
                $parent_name,
                $enquiry_number,
                $school_name,
                $student_grade,
                $submission_date
            );
            
            error_log("EduBot Workflow Manager: Using admission_confirmation template with params: " . json_encode($template_params));
            
            // Send via template
            $result = $this->send_meta_whatsapp_template(
                $parent_phone,
                'admission_confirmation',
                $template_params
            );
            
            // If template fails, fall back to text message
            if (!$result) {
                error_log("EduBot Workflow Manager: admission_confirmation template failed, falling back to text message");
                $message_text = "Hello {$parent_name},\n\nThank you for submitting your admission enquiry to {$school_name}.\n\n" .
                                "Enquiry Number: {$enquiry_number}\n" .
                                "Grade: {$student_grade}\n\n" .
                                "Our admission team will review your application and contact you within 24 hours.\n\n" .
                                "Best regards,\n{$school_name} Admission Team";
                
                $result = $this->send_meta_whatsapp($parent_phone, $message_text, $api_config->whatsapp_token);
            }
            
            if ($result) {
                error_log("EduBot Workflow Manager: WhatsApp sent successfully for {$enquiry_number} to {$parent_phone}");
                return true;
            } else {
                error_log("EduBot Workflow Manager: WhatsApp send failed for {$enquiry_number}");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("EduBot Workflow Manager: Exception in send_parent_whatsapp_confirmation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send WhatsApp via Meta API
     */
    private function send_meta_whatsapp($phone, $message, $access_token) {
        error_log("EduBot Workflow Manager: Sending WhatsApp via Meta API to {$phone}");
        
        try {
            global $wpdb;
            
            // Get phone ID from config
            $phone_id = $wpdb->get_var(
                "SELECT whatsapp_phone_id FROM {$wpdb->prefix}edubot_api_integrations WHERE status = 'active' LIMIT 1"
            );
            
            if (empty($phone_id)) {
                error_log("EduBot Workflow Manager: WhatsApp phone ID not configured");
                return false;
            }
            
            // Use correct Meta endpoint (graph.facebook.com, not graph.instagram.com)
            $url = "https://graph.facebook.com/v22.0/{$phone_id}/messages";
            
            // Send text message via WhatsApp API
            $payload = array(
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'text',
                'text' => array(
                    'body' => $message
                )
            );
            
            $body = json_encode($payload);
            
            $args = array(
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $access_token
                ),
                'body' => $body,
                'timeout' => 30,
                'sslverify' => false
            );
            
            error_log("EduBot Workflow Manager: Sending to {$url}");
            error_log("EduBot Workflow Manager: WhatsApp payload: " . $body);
            error_log("EduBot Workflow Manager: Target phone: {$phone}");
            error_log("EduBot Workflow Manager: Message text: {$message}");
            $response = wp_remote_post($url, $args);
            
            if (is_wp_error($response)) {
                error_log("EduBot Workflow Manager: WhatsApp request error: " . $response->get_error_message());
                return false;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            error_log("EduBot Workflow Manager: WhatsApp response status: {$status_code}");
            error_log("EduBot Workflow Manager: WhatsApp response body: {$response_body}");
            
            if ($status_code === 200) {
                $result = json_decode($response_body, true);
                if (isset($result['messages'][0]['id'])) {
                    error_log("EduBot Workflow Manager: WhatsApp message sent successfully, ID: " . $result['messages'][0]['id']);
                    return true;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("EduBot Workflow Manager: Exception in send_meta_whatsapp: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send WhatsApp message via template (new method)
     * @param string $phone - Phone number (format: 919866133566)
     * @param string $template_name - Template name (e.g., 'admission_confirmation')
     * @param array $parameters - Template parameters in order (5 parameters required)
     * @return bool - True if message sent successfully
     */
    private function send_meta_whatsapp_template($phone, $template_name, $parameters = array()) {
        error_log("EduBot Workflow Manager: Sending WhatsApp template '{$template_name}' to {$phone}");
        
        try {
            global $wpdb;
            
            // Get API credentials
            $config = $wpdb->get_row(
                "SELECT whatsapp_phone_id, whatsapp_token FROM {$wpdb->prefix}edubot_api_integrations WHERE status = 'active' LIMIT 1"
            );
            
            if (!$config || empty($config->whatsapp_phone_id) || empty($config->whatsapp_token)) {
                error_log("EduBot Workflow Manager: WhatsApp configuration not found for template");
                return false;
            }
            
            $phone_id = $config->whatsapp_phone_id;
            $access_token = $config->whatsapp_token;
            $url = "https://graph.facebook.com/v22.0/{$phone_id}/messages";
            
            // Build body parameters (text type)
            $body_params = array();
            if (!empty($parameters)) {
                foreach ($parameters as $param_value) {
                    $body_params[] = array(
                        'type' => 'text',
                        'text' => (string)$param_value
                    );
                }
            }
            
            // Build payload with correct template structure
            // Must include header component (empty) + body component with parameters
            $payload = array(
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => array(
                    'name' => $template_name,
                    'language' => array(
                        'code' => 'en'  // MUST be 'en' not 'en_US'
                    ),
                    'components' => array(
                        array(
                            'type' => 'header',
                            'parameters' => array()  // Header component with empty params
                        ),
                        array(
                            'type' => 'body',
                            'parameters' => $body_params  // Body with actual parameters
                        )
                    )
                )
            );
            
            $body = json_encode($payload);
            
            $args = array(
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $access_token
                ),
                'body' => $body,
                'timeout' => 30,
                'sslverify' => false
            );
            
            error_log("EduBot Workflow Manager: Template '{$template_name}' payload: " . $body);
            error_log("EduBot Workflow Manager: Template parameters count: " . count($parameters));
            $response = wp_remote_post($url, $args);
            
            if (is_wp_error($response)) {
                error_log("EduBot Workflow Manager: Template send error: " . $response->get_error_message());
                return false;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            error_log("EduBot Workflow Manager: Template response status: {$status_code}");
            error_log("EduBot Workflow Manager: Template response body: {$response_body}");
            
            if ($status_code === 200) {
                $result = json_decode($response_body, true);
                if (isset($result['messages'][0]['id'])) {
                    error_log("EduBot Workflow Manager: Template message sent, ID: " . $result['messages'][0]['id']);
                    return true;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("EduBot Workflow Manager: Exception in send_meta_whatsapp_template: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send school enquiry notification via ZeptoMail
     */
    private function send_school_enquiry_notification($collected_data, $enquiry_number, $school_name) {
        error_log("EduBot Workflow Manager: Sending school notification for {$enquiry_number}");
        
        try {
            global $wpdb;
            
            // Get admin email
            $school_email = get_option('edubot_school_email', get_option('admin_email'));
            if (empty($school_email)) {
                error_log("EduBot Workflow Manager: No school email configured");
                return false;
            }
            
            // Get API key
            $api_key = $wpdb->get_var(
                "SELECT email_api_key FROM {$wpdb->prefix}edubot_api_integrations WHERE status = 'active' LIMIT 1"
            );
            
            if (empty($api_key)) {
                error_log("EduBot Workflow Manager: Email API key not configured");
                return false;
            }
            
            $student_name = $collected_data['student_name'] ?? 'N/A';
            $student_email = $collected_data['email'] ?? 'N/A';
            $student_phone = $collected_data['phone'] ?? 'N/A';
            $grade = $collected_data['grade'] ?? 'N/A';
            $board = $collected_data['board'] ?? 'N/A';
            $contact_person = $collected_data['parent_name'] ?? $student_name;
            
            $subject = "🎓 New Admission Enquiry - {$student_name} - {$enquiry_number}";
            
            // Build professional HTML email for admin
            $body = "
            <html>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                    <div style='max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #2c3e50; border-bottom: 3px solid #0066cc; padding-bottom: 10px;'>🎓 New Admission Enquiry Received</h2>
                        
                        <div style='background-color: #e8f4f8; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <p style='margin: 0; font-size: 16px;'><strong>Enquiry Number:</strong> {$enquiry_number}</p>
                            <p style='margin: 10px 0 0 0; font-size: 14px; color: #555;'>Submitted: " . date('F j, Y \a\t g:i A') . "</p>
                        </div>
                        
                        <div style='background-color: #f8f9fa; padding: 20px; border-left: 4px solid #0066cc; margin: 20px 0;'>
                            <p style='margin: 0 0 15px 0;'><strong style='color: #0066cc;'>� Student Information</strong></p>
                            <table style='width: 100%; border-collapse: collapse;'>
                                <tr style='border-bottom: 1px solid #ddd;'>
                                    <td style='padding: 8px; font-weight: bold; width: 40%;'>Student Name:</td>
                                    <td style='padding: 8px;'>{$student_name}</td>
                                </tr>
                                <tr style='border-bottom: 1px solid #ddd;'>
                                    <td style='padding: 8px; font-weight: bold;'>Grade:</td>
                                    <td style='padding: 8px;'>{$grade}</td>
                                </tr>
                                <tr style='border-bottom: 1px solid #ddd;'>
                                    <td style='padding: 8px; font-weight: bold;'>Board:</td>
                                    <td style='padding: 8px;'>{$board}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div style='background-color: #f8f9fa; padding: 20px; border-left: 4px solid #28a745; margin: 20px 0;'>
                            <p style='margin: 0 0 15px 0;'><strong style='color: #28a745;'>👥 Contact Information</strong></p>
                            <table style='width: 100%; border-collapse: collapse;'>
                                <tr style='border-bottom: 1px solid #ddd;'>
                                    <td style='padding: 8px; font-weight: bold; width: 40%;'>Parent/Guardian:</td>
                                    <td style='padding: 8px;'>{$contact_person}</td>
                                </tr>
                                <tr style='border-bottom: 1px solid #ddd;'>
                                    <td style='padding: 8px; font-weight: bold;'>Phone:</td>
                                    <td style='padding: 8px;'><a href='tel:{$student_phone}' style='color: #0066cc; text-decoration: none;'>{$student_phone}</a></td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px; font-weight: bold;'>Email:</td>
                                    <td style='padding: 8px;'><a href='mailto:{$student_email}' style='color: #0066cc; text-decoration: none;'>{$student_email}</a></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                            <p style='margin: 0;'><strong>⚡ Action Required:</strong> Please review this enquiry and contact the family to proceed with the admission process.</p>
                        </div>
                        
                        <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px;'>
                            <p>This is an automated notification from EduBot Admission System.<br/>
                            <a href='" . admin_url('admin.php?page=edubot-enquiries') . "' style='color: #0066cc; text-decoration: none;'>View in Admin Panel</a></p>
                        </div>
                    </div>
                </body>
            </html>";
            
            // Get from email - use verified sender from ZeptoMail
            $from_email = get_option('edubot_admin_contact_email', 'noreply@epistemo.in');
            
            // Send via ZeptoMail API with correct authorization header
            $payload = array(
                'from' => array(
                    'address' => $from_email
                ),
                'to' => array(
                    array(
                        'email_address' => array(
                            'address' => $school_email
                        )
                    )
                ),
                'subject' => $subject,
                'htmlbody' => $body
            );
            
            $response = wp_remote_post(
                'https://api.zeptomail.in/v1.1/email',
                array(
                    'headers' => array(
                        'Authorization' => 'Zoho-enczapikey ' . $api_key,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Cache-Control' => 'no-cache'
                    ),
                    'body' => json_encode($payload),
                    'timeout' => 30,
                    'sslverify' => false
                )
            );
            
            if (is_wp_error($response)) {
                error_log("EduBot Workflow Manager: School email request error: " . $response->get_error_message());
                return false;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code >= 200 && $response_code < 300) {
                error_log("EduBot Workflow Manager: School notification sent to {$school_email}");
                return true;
            } else {
                error_log("EduBot Workflow Manager: School notification failed: " . wp_remote_retrieve_body($response));
                return false;
            }
            
        } catch (Exception $e) {
            error_log("EduBot Workflow Manager: Exception in send_school_enquiry_notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send school WhatsApp notification
     */
    private function send_school_whatsapp_notification($collected_data, $enquiry_number, $school_name) {
        error_log("EduBot Workflow Manager: Attempting to send school WhatsApp for {$enquiry_number}");
        
        try {
            global $wpdb;
            
            // Get admin phone
            $admin_phone = get_option('edubot_school_phone', '');
            if (empty($admin_phone)) {
                error_log("EduBot Workflow Manager: No school WhatsApp phone configured");
                return false;
            }
            
            // Clean phone number
            $admin_phone = preg_replace('/[^0-9]/', '', $admin_phone);
            if (strlen($admin_phone) === 10) {
                $admin_phone = '91' . $admin_phone;
            }
            
            // Get WhatsApp token and template config from database
            $whatsapp_config = $wpdb->get_row(
                "SELECT whatsapp_token, whatsapp_template_name FROM {$wpdb->prefix}edubot_api_integrations WHERE status = 'active' LIMIT 1"
            );
            
            if (empty($whatsapp_config) || empty($whatsapp_config->whatsapp_token)) {
                error_log("EduBot Workflow Manager: WhatsApp token not configured");
                return false;
            }
            
            $student_name = $collected_data['student_name'] ?? 'N/A';
            $student_email = $collected_data['email'] ?? 'N/A';
            $student_phone = $collected_data['phone'] ?? 'N/A';
            $grade = $collected_data['grade'] ?? 'N/A';
            $parent_name = $collected_data['parent_name'] ?? $student_name; // Use student name as fallback
            $school_name = $collected_data['school_name'] ?? 'N/A';
            $board = $collected_data['board'] ?? 'CBSE';
            $contact_person = $collected_data['contact_person'] ?? $parent_name;
            
            // Template parameters for school admin notification (9 parameters in exact order)
            // Meta template: "New Admission Enquiry - {student_name}"
            // 1. Student Name (for header: "New Admission Enquiry - {{1}}")
            // 2. Enquiry Number
            // 3. Student Name (duplicate for body)
            // 4. Grade
            // 5. Board (e.g., CBSE, ICSE)
            // 6. Contact Person/Parent Name
            // 7. Phone
            // 8. Email
            // 9. Submission Date/Time
            $template_params = array(
                $student_name,          // 1. Student name (for header)
                $enquiry_number,        // 2. Enquiry number
                $student_name,          // 3. Student name (for body)
                $grade,                 // 4. Grade
                $board,                 // 5. Board
                $contact_person,        // 6. Contact person
                $student_phone,         // 7. Phone
                $student_email,         // 8. Email
                date('d/m/Y h A')       // 9. Date/Time (DD/MM/YYYY HH AM)
            );
            
            // Get template name from settings
            $template_name = get_option('edubot_school_whatsapp_template_name', 'edubot_school_whatsapp_template_name_');
            
            error_log("EduBot Workflow Manager: Using school admin template: {$template_name} with params: " . json_encode($template_params));
            
            $result = $this->send_meta_whatsapp_template(
                $admin_phone,
                $template_name,
                $template_params
            );
            
            // If template fails, fall back to text message
            if (!$result) {
                error_log("EduBot Workflow Manager: School template failed, falling back to text message");
                // Fallback to text message
                $message_text = "New admission enquiry received!\n\n" .
                                "Enquiry: {$enquiry_number}\n" .
                                "Student: {$student_name}\n" .
                                "Grade: {$grade}\n" .
                                "Parent: {$parent_name}\n" .
                                "Contact: {$student_phone}\n\n" .
                                "Check admin panel for full details.";
                
                $result = $this->send_meta_whatsapp($admin_phone, $message_text, $whatsapp_config->whatsapp_token);
            }
            
            if ($result) {
                error_log("EduBot Workflow Manager: School WhatsApp sent for {$enquiry_number}");
                return true;
            } else {
                error_log("EduBot Workflow Manager: School WhatsApp send failed");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("EduBot Workflow Manager: Exception in send_school_whatsapp_notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Build parent confirmation email HTML
     */
    private function build_parent_confirmation_email($collected_data, $enquiry_number, $school_name) {
        $student_name = $collected_data['student_name'] ?? 'Valued Parent';
        $primary_color = get_option('edubot_primary_color', '#4facfe');
        $secondary_color = get_option('edubot_secondary_color', '#00f2fe');
        $school_phone = get_option('edubot_school_phone', '7702800800 / 9248111448');
        $school_logo = get_option('edubot_school_logo', '');
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Enquiry Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f7fa; margin: 0; padding: 0;">
    <div style="max-width: 600px; margin: 0 auto; background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        <div style="background: linear-gradient(135deg, ' . esc_attr($primary_color) . ' 0%, ' . esc_attr($secondary_color) . ' 100%); color: white; text-align: center; padding: 30px 20px;">
            ' . (!empty($school_logo) ? '<img src="' . esc_url($school_logo) . '" style="max-width: 120px; margin-bottom: 10px;" />' : '') . '
            <h1 style="margin: 0; font-size: 24px;">' . esc_html($school_name) . '</h1>
            <p style="margin: 5px 0 0 0; font-size: 16px;">Admission Enquiry Confirmation</p>
        </div>
        
        <div style="padding: 30px 25px; text-align: center; background-color: #fef3c7; border-bottom: 3px solid ' . esc_attr($primary_color) . ';">
            <p style="margin: 0; font-size: 14px; color: #666; margin-bottom: 10px;">Your Enquiry Number</p>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #000;">' . esc_html($enquiry_number) . '</p>
        </div>
        
        <div style="padding: 30px 25px;">
            <p style="margin: 0 0 20px 0; font-size: 16px; color: #333;">Dear ' . esc_html($student_name) . ',</p>
            
            <p style="margin: 0 0 15px 0; font-size: 15px; color: #555; line-height: 1.6;">
                Thank you for submitting your admission enquiry to ' . esc_html($school_name) . '. We are delighted to receive your interest in our institution.
            </p>
            
            <p style="margin: 0 0 15px 0; font-size: 15px; color: #555; line-height: 1.6;">
                Your enquiry has been received and assigned the reference number above. Our admissions team will review your information and contact you within 24 hours to discuss further steps.
            </p>
            
            <div style="background-color: #f0f9ff; padding: 20px; border-left: 4px solid ' . esc_attr($primary_color) . '; margin: 20px 0;">
                <p style="margin: 0; font-weight: bold; color: #333; margin-bottom: 10px;">What Happens Next?</p>
                <ul style="margin: 10px 0; padding-left: 20px; color: #555;">
                    <li>Our team will review your application</li>
                    <li>You will receive contact within 24 hours</li>
                    <li>We will discuss admission requirements and schedule</li>
                    <li>Campus tour and interview can be arranged</li>
                </ul>
            </div>
            
            <p style="margin: 20px 0 0 0; font-size: 15px; color: #555; line-height: 1.6;">
                If you have any questions in the meantime, please feel free to contact us.
            </p>
        </div>
        
        <div style="background-color: #f8f9fa; padding: 20px 25px; text-align: center; border-top: 1px solid #ddd;">
            <p style="margin: 0 0 10px 0; font-weight: bold; color: #333;">Contact Us</p>
            <p style="margin: 0; color: #666; font-size: 14px;">
                📞 ' . esc_html($school_phone) . '<br>
                📧 admissions@epistemo.in<br>
                🌐 www.epistemo.in
            </p>
        </div>
        
        <div style="background-color: #f0f0f0; padding: 15px 25px; text-align: center; font-size: 12px; color: #999;">
            <p style="margin: 0;">This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
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
        $school_config = EduBot_School_Config::getInstance();
        $available_years = $school_config->get_available_academic_years();
        $years_text = implode(' & ', $available_years);
        
        return "👋 **Welcome to Epistemo Vikas Leadership School!**\n\n" .
               "I'll help you with your admission enquiry for **AY {$years_text}**.\n\n" .
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
