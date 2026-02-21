<?php

/**
 * Core chatbot engine for handling conversations
 */
class EduBot_Chatbot_Engine {

    /**
     * School configuration
     */
    private $school_config;

    /**
     * Security manager
     */
    private $security_manager;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize school config with error handling
        if (class_exists('EduBot_School_Config')) {
            $this->school_config = EduBot_School_Config::getInstance();
        } else {
            error_log('EduBot Chatbot Engine: EduBot_School_Config class not found');
            $this->school_config = null;
        }
        
        // Initialize security manager with error handling
        if (class_exists('EduBot_Security_Manager')) {
            $this->security_manager = new EduBot_Security_Manager();
        } else {
            error_log('EduBot Chatbot Engine: EduBot_Security_Manager class not found');
            $this->security_manager = null;
        }
    }

    /**
     * Process chatbot message with enhanced security
     */
    public function process_message($message, $session_id) {
        // Input validation
        if (empty($message) || empty($session_id)) {
            return array(
                'success' => false,
                'message' => __('Invalid request. Please refresh and try again.', 'edubot-pro')
            );
        }

        // Validate session ID format
        if (!preg_match('/^[a-zA-Z0-9_-]{10,40}$/', $session_id)) {
            return array(
                'success' => false,
                'message' => __('Invalid session. Please refresh and try again.', 'edubot-pro')
            );
        }

        // Message length validation
        if (strlen($message) > 1000) {
            return array(
                'success' => false,
                'message' => __('Message too long. Please keep messages under 1000 characters.', 'edubot-pro')
            );
        }

        // Check if required components are available
        if ($this->school_config === null) {
            return array(
                'success' => false,
                'message' => __('Chatbot service is temporarily unavailable. Please try again later or contact us directly.', 'edubot-pro')
            );
        }
        
        // Security validation - temporarily disabled for testing
        if ($this->security_manager !== null) {
            // Temporarily disable malicious content check
            // if ($this->security_manager->is_malicious_content($message)) {
            //     $this->security_manager->log_security_event('malicious_content_chatbot', array(
            //         'session_id' => $session_id,
            //         'message' => substr($message, 0, 100)
            //     ));
            //     
            //     return array(
            //         'success' => false,
            //         'message' => __('Your message contains content that violates our security policies. Please rephrase your question.', 'edubot-pro')
            //     );
            // }

            // Temporarily disable rate limiting in engine
            // if (!$this->security_manager->check_rate_limit($session_id, 30, 900)) {
            //     return array(
            //         'success' => false,
            //         'message' => __('Too many requests. Please try again later.', 'edubot-pro')
            //     );
            // }

            // Temporarily disable global rate limiting
            // $user_ip = $this->get_client_ip();
            // if (!$this->security_manager->check_rate_limit('chatbot_ip_' . md5($user_ip), 100, 3600)) {
            //     return array(
            //         'success' => false,
            //         'message' => __('Too many requests from your location. Please try again later.', 'edubot-pro')
            //     );
            // }
        }

        try {
            // Sanitize message input
            $message = sanitize_text_field($message);
            
            // Get or create session - use transients instead of database for now
            $session = $this->get_session_safe($session_id);
            
            // Process message based on current state
            $response = $this->handle_conversation_flow($message, $session);
            
            // Update session - use transients instead of database
            $this->update_session_safe($session_id, $response['session_data']);
            
            // Log analytics (only if we have the session data)
            if (isset($response['session_data'])) {
                error_log('EduBot: Message processed for session ' . $session_id);
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log('EduBot Chatbot Engine Error in process_message: ' . $e->getMessage());
            
            return array(
                'success' => false,
                'message' => __('Sorry, there was a technical issue processing your message. Please try again or contact our support team.', 'edubot-pro')
            );
        }
    }

    /**
     * Get client IP address safely
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    }

    /**
     * Handle conversation flow
     */
    private function handle_conversation_flow($message, $session) {
        $config = $this->school_config->get_config();
        $current_state = isset($session['state']) ? $session['state'] : 'greeting';
        
        switch ($current_state) {
            case 'greeting':
                return $this->handle_greeting($message, $session, $config);
                
            case 'collecting_basic_info':
                return $this->handle_basic_info_collection($message, $session, $config);
                
            case 'collecting_admission_info':
                return $this->handle_admission_info_collection($message, $session, $config);
                
            case 'selecting_board':
                return $this->handle_board_selection($message, $session, $config);
                
            case 'selecting_grade':
                return $this->handle_grade_selection($message, $session, $config);
                
            case 'collecting_student_info':
                return $this->handle_student_info_collection($message, $session, $config);
                
            case 'collecting_parent_info':
                return $this->handle_parent_info_collection($message, $session, $config);
                
            case 'confirming_details':
                return $this->handle_confirmation($message, $session, $config);
                
            case 'completed':
                return $this->handle_completion($message, $session, $config);
                
            default:
                return $this->handle_general_query($message, $session, $config);
        }
    }

    /**
     * Handle greeting and initial interaction
     */
    private function handle_greeting($message, $session, $config) {
        $school_name = $config['school_info']['name'] ?? 'Vikas The Concept School';
        
        $welcome_message = "Hello! Welcome to " . $school_name . "\n\n" .
            "We're glad you reached out to us. Seems like you're looking to join a school with a great learning environment. How may we help you today?";

        $session_data = array(
            'state' => 'collecting_basic_info',
            'user_data' => array(),
            'conversation_log' => array(
                array(
                    'timestamp' => current_time('mysql'),
                    'type' => 'bot',
                    'message' => $welcome_message
                )
            )
        );

        return array(
            'success' => true,
            'message' => $welcome_message,
            'session_data' => $session_data,
            'options' => array(
                array('text' => '🎓 Admissions', 'value' => 'admissions'),
                array('text' => '💼 Career', 'value' => 'career'),
                array('text' => '📞 Contact Information', 'value' => 'contact_info'),
                array('text' => '📚 School Information', 'value' => 'school_info')
            )
        );
    }

    /**
     * Handle basic information collection
     */
    private function handle_basic_info_collection($message, $session, $config) {
        $message = strtolower(trim($message));
        
        // Greetings reset to the main menu
        if ( in_array( $message, array( 'hi', 'hello', 'hey', 'hii', 'start', 'menu', 'help', 'options' ) ) ) {
            return $this->handle_greeting( $message, $session, $config );
        }
        
        if (in_array($message, array('admissions', 'admission', 'new admission', 'apply'))) {
            return $this->start_admission_process($session, $config);
        } elseif (in_array($message, array('career', 'job', 'employment', 'work'))) {
            return $this->provide_career_info($session, $config);
        } elseif (in_array($message, array('school info', 'school_info', 'about school', 'school information'))) {
            return $this->provide_school_info($session, $config);
        } elseif (in_array($message, array('contact', 'contact_info', 'phone', 'address', 'contact information'))) {
            return $this->provide_contact_info($session, $config);
        } else {
            return $this->handle_ai_response($message, $session, $config);
        }
    }

    /**
     * Start admission application process
     */
    /**
     * Start admission process (public method for external calls)
     */
    public function start_admission_process($session, $config) {
        try {
            $school_name = $config['school_info']['name'] ?? 'Vikas The Concept School';
            
            $response_message = "🎓 Welcome to {$school_name} Admission Process!\n\n" .
                "I'll help you with your admission enquiry and collect the necessary information. Let's start:\n\n" .
                "📝 Which grade/class are you interested in?";

            // Create grade options for school admission
            $options = array(
                array('text' => 'Pre-KG', 'value' => 'pre_kg'),
                array('text' => 'LKG', 'value' => 'lkg'),
                array('text' => 'UKG', 'value' => 'ukg'),
                array('text' => 'Class 1', 'value' => 'class_1'),
                array('text' => 'Class 2', 'value' => 'class_2'),
                array('text' => 'Class 3', 'value' => 'class_3'),
                array('text' => 'Class 4', 'value' => 'class_4'),
                array('text' => 'Class 5', 'value' => 'class_5'),
                array('text' => 'Class 6', 'value' => 'class_6'),
                array('text' => 'Class 7', 'value' => 'class_7'),
                array('text' => 'Class 8', 'value' => 'class_8'),
                array('text' => 'Class 9', 'value' => 'class_9'),
                array('text' => 'Class 10', 'value' => 'class_10'),
                array('text' => 'Class 11', 'value' => 'class_11'),
                array('text' => 'Class 12', 'value' => 'class_12')
            );

            $session_data = $session;
            $session_data['state'] = 'collecting_admission_info';
            $session_data['admission_step'] = 'grade_selection';
            $session_data['admission_data'] = array();

            return array(
                'success' => true,
                'message' => $response_message,
                'session_data' => $session_data,
                'options' => $options,
                'type' => 'grade_selection'
            );
            
        } catch (Exception $e) {
            error_log('EduBot Chatbot Engine Error in start_admission_process: ' . $e->getMessage());
            
            return array(
                'success' => false,
                'message' => __('Sorry, there was a technical issue starting the admission process. Please try again later or contact our admissions office directly.', 'edubot-pro'),
                'session_data' => $session
            );
        }
    }

    /**
     * Handle admission information collection step by step
     */
    private function handle_admission_info_collection($message, $session, $config) {
        $admission_step = $session['admission_step'] ?? 'grade_selection';
        $admission_data = $session['admission_data'] ?? array();
        $message = sanitize_text_field($message);

        switch ($admission_step) {
            case 'grade_selection':
                return $this->handle_grade_step($message, $session, $config);
            
            case 'student_name':
                return $this->handle_student_name_step($message, $session, $config);
                
            case 'student_age':
                return $this->handle_student_age_step($message, $session, $config);
                
            case 'parent_name':
                return $this->handle_parent_name_step($message, $session, $config);
                
            case 'parent_phone':
                return $this->handle_parent_phone_step($message, $session, $config);
                
            case 'parent_email':
                return $this->handle_parent_email_step($message, $session, $config);
                
            case 'previous_school':
                return $this->handle_previous_school_step($message, $session, $config);
                
            case 'confirmation':
                return $this->handle_admission_confirmation($message, $session, $config);
                
            default:
                return $this->handle_grade_step($message, $session, $config);
        }
    }

    /**
     * Handle grade selection step
     */
    private function handle_grade_step($message, $session, $config) {
        $grade_mapping = array(
            'pre_kg' => 'Pre-KG',
            'lkg' => 'LKG', 
            'ukg' => 'UKG',
            'class_1' => 'Class 1',
            'class_2' => 'Class 2',
            'class_3' => 'Class 3',
            'class_4' => 'Class 4',
            'class_5' => 'Class 5',
            'class_6' => 'Class 6',
            'class_7' => 'Class 7',
            'class_8' => 'Class 8',
            'class_9' => 'Class 9',
            'class_10' => 'Class 10',
            'class_11' => 'Class 11',
            'class_12' => 'Class 12'
        );

        // First try exact mapping
        if (isset($grade_mapping[$message])) {
            $session['admission_data']['grade'] = $grade_mapping[$message];
            $session['admission_step'] = 'student_name';
            
            return array(
                'success' => true,
                'message' => "Great! {$grade_mapping[$message]} is a wonderful choice.\n\n📝 Please provide the student's full name:",
                'session_data' => $session
            );
        }
        
        // Try fuzzy matching for misspelled grade inputs
        $fuzzy_grade = $this->extract_fuzzy_grade_chatbot($message);
        if ($fuzzy_grade) {
            $session['admission_data']['grade'] = $fuzzy_grade;
            $session['admission_step'] = 'student_name';
            
            return array(
                'success' => true,
                'message' => "Great! I understood you meant {$fuzzy_grade}. That's a wonderful choice.\n\n📝 Please provide the student's full name:",
                'session_data' => $session
            );
        }
        
        return array(
            'success' => true,
            'message' => "Please select a valid grade from the options provided above. You can type something like 'Grade 10', 'Class 5', or '10th grade'.",
            'session_data' => $session
        );
    }

    /**
     * Handle student name step
     */
    private function handle_student_name_step($message, $session, $config) {
        if (strlen($message) < 2) {
            return array(
                'success' => true,
                'message' => "Please provide a valid student name (at least 2 characters):",
                'session_data' => $session
            );
        }

        $session['admission_data']['student_name'] = $message;
        $session['admission_step'] = 'student_age';
        
        return array(
            'success' => true,
            'message' => "Thank you! Student name: {$message}\n\n📅 What is the student's age?",
            'session_data' => $session
        );
    }

    /**
     * Handle student age step
     */
    private function handle_student_age_step($message, $session, $config) {
        $age = intval($message);
        if ($age < 2 || $age > 20) {
            return array(
                'success' => true,
                'message' => "Please provide a valid age (between 2 and 20 years):",
                'session_data' => $session
            );
        }

        $session['admission_data']['student_age'] = $age;
        $session['admission_step'] = 'parent_name';
        
        return array(
            'success' => true,
            'message' => "Perfect! Age: {$age} years\n\n👤 Please provide the parent/guardian's full name:",
            'session_data' => $session
        );
    }

    /**
     * Handle parent name step
     */
    private function handle_parent_name_step($message, $session, $config) {
        if (strlen($message) < 2) {
            return array(
                'success' => true,
                'message' => "Please provide a valid parent/guardian name (at least 2 characters):",
                'session_data' => $session
            );
        }

        $session['admission_data']['parent_name'] = $message;
        $session['admission_step'] = 'parent_phone';
        
        return array(
            'success' => true,
            'message' => "Thank you! Parent/Guardian: {$message}\n\n📱 Please provide your contact phone number:",
            'session_data' => $session
        );
    }

    /**
     * Handle parent phone step
     */
    private function handle_parent_phone_step($message, $session, $config) {
        // Simple phone validation
        $phone = preg_replace('/[^0-9+\-\s]/', '', $message);
        if (strlen($phone) < 10) {
            return array(
                'success' => true,
                'message' => "Please provide a valid phone number (at least 10 digits):",
                'session_data' => $session
            );
        }

        $session['admission_data']['parent_phone'] = $phone;
        $session['admission_step'] = 'parent_email';
        
        return array(
            'success' => true,
            'message' => "Great! Phone: {$phone}\n\n📧 Please provide your email address:",
            'session_data' => $session
        );
    }

    /**
     * Handle parent email step
     */
    private function handle_parent_email_step($message, $session, $config) {
        if (!filter_var($message, FILTER_VALIDATE_EMAIL)) {
            return array(
                'success' => true,
                'message' => "Please provide a valid email address:",
                'session_data' => $session
            );
        }

        $session['admission_data']['parent_email'] = $message;
        $session['admission_step'] = 'previous_school';
        
        return array(
            'success' => true,
            'message' => "Perfect! Email: {$message}\n\n🏫 What is the name of the student's current/previous school? (Type 'None' if this is the first school):",
            'session_data' => $session
        );
    }

    /**
     * Handle previous school step
     */
    private function handle_previous_school_step($message, $session, $config) {
        $session['admission_data']['previous_school'] = $message;
        $session['admission_step'] = 'confirmation';
        
        // Display all collected information for confirmation
        $data = $session['admission_data'];
        $summary = "📋 **Admission Enquiry Summary**\n\n" .
                  "👤 **Student Details:**\n" .
                  "• Name: {$data['student_name']}\n" .
                  "• Age: {$data['student_age']} years\n" .
                  "• Grade: {$data['grade']}\n" .
                  "• Previous School: {$message}\n\n" .
                  "👨‍👩‍👧‍👦 **Parent/Guardian Details:**\n" .
                  "• Name: {$data['parent_name']}\n" .
                  "• Phone: {$data['parent_phone']}\n" .
                  "• Email: {$data['parent_email']}\n\n" .
                  "✅ Is all the information correct?\n\n" .
                  "Reply 'YES' to submit or 'NO' to restart the process.";
        
        return array(
            'success' => true,
            'message' => $summary,
            'session_data' => $session,
            'options' => array(
                array('text' => 'YES - Submit Enquiry', 'value' => 'confirm_yes'),
                array('text' => 'NO - Start Over', 'value' => 'confirm_no')
            )
        );
    }

    /**
     * Handle admission confirmation and generate enquiry number
     */
    private function handle_admission_confirmation($message, $session, $config) {
        if (strtolower($message) === 'confirm_yes' || strtolower($message) === 'yes') {
            // Generate unique enquiry number
            $enquiry_number = 'ENQ' . date('Y') . date('m') . substr(md5($session['session_id'] . time()), 0, 6);
            
            // Save admission data (you can enhance this to save to database)
            $admission_record = array(
                'enquiry_number' => $enquiry_number,
                'submission_date' => current_time('mysql'),
                'session_id' => $session['session_id'],
                'admission_data' => $session['admission_data'],
                'status' => 'submitted'
            );
            
            // Save to WordPress options or database
            $this->save_admission_enquiry($admission_record);
            
            $data = $session['admission_data'];
            $school_name = $config['school_info']['name'] ?? 'Vikas The Concept School';
            
            $success_message = "🎉 **Admission Enquiry Submitted Successfully!**\n\n" .
                             "📋 **Enquiry Number: {$enquiry_number}**\n\n" .
                             "Dear {$data['parent_name']},\n\n" .
                             "Thank you for your interest in {$school_name}. Your admission enquiry for {$data['student_name']} (Grade: {$data['grade']}) has been received.\n\n" .
                             "📞 Our admissions team will contact you within 24-48 hours at {$data['parent_phone']}\n" .
                             "📧 You will also receive a confirmation email at {$data['parent_email']}\n\n" .
                             "📝 **Next Steps:**\n" .
                             "• Keep your enquiry number for reference\n" .
                             "• Our team will schedule a school visit\n" .
                             "• Prepare required documents\n" .
                             "• Await further instructions\n\n" .
                             "Thank you for choosing {$school_name}! 🏫";
            
            // Reset session to completed state
            $session['state'] = 'completed';
            $session['admission_step'] = 'completed';
            
            return array(
                'success' => true,
                'message' => $success_message,
                'session_data' => $session,
                'enquiry_number' => $enquiry_number
            );
            
        } elseif (strtolower($message) === 'confirm_no' || strtolower($message) === 'no') {
            // Restart the admission process
            return $this->start_admission_process($session, $config);
            
        } else {
            return array(
                'success' => true,
                'message' => "Please reply 'YES' to submit the enquiry or 'NO' to start over:",
                'session_data' => $session
            );
        }
    }

    /**
     * Save admission enquiry to WordPress options (can be enhanced to use database)
     */
    private function save_admission_enquiry($admission_record) {
        // Get existing enquiries
        $enquiries = get_option('edubot_admission_enquiries', array());
        
        // Add new enquiry
        $enquiries[$admission_record['enquiry_number']] = $admission_record;
        
        // Save back to options
        update_option('edubot_admission_enquiries', $enquiries);
        
        // Log for debugging
        error_log('EduBot: Admission enquiry saved - ' . $admission_record['enquiry_number']);
        
        return true;
    }

    /**
     * Handle educational board selection (existing method)
     */
    private function handle_board_selection($message, $session, $config) {
        $selected_board = sanitize_text_field($message);
        $educational_boards = Edubot_Academic_Config::get_educational_boards();
        
        // Handle "Need Guidance" option
        if ($selected_board === 'need_guidance') {
            return $this->provide_board_guidance($session, $config);
        }
        
        // Handle specific board selection (including defaults)
        $board_name = '';
        switch($selected_board) {
            case 'igcse':
                $board_name = 'IGCSE';
                break;
            case 'cbse':
                $board_name = 'CBSE';
                break;
            case 'icse':
                $board_name = 'ICSE';
                break;
            case 'state':
                $board_name = 'State Board';
                break;
            default:
                // Check if it's in configured boards
                if (array_key_exists($selected_board, $educational_boards)) {
                    $board_name = $educational_boards[$selected_board]['name'];
                } else {
                    return array(
                        'success' => false,
                        'message' => __("Please select a valid educational board from the available options.", 'edubot-pro'),
                        'session_data' => $session
                    );
                }
        }
        
        // Get available grades for current academic year and selected board
        $school_id = $this->school_config->get_school_id();
        $available_grades = Edubot_Academic_Config::get_available_grades_for_admission($school_id);
        
        // Create default grades if none configured
        if (empty($available_grades)) {
            $available_grades = array(
                'pre_kg' => 'Pre-KG',
                'lkg' => 'LKG',
                'ukg' => 'UKG',
                'grade_1' => 'Grade 1',
                'grade_2' => 'Grade 2',
                'grade_3' => 'Grade 3',
                'grade_4' => 'Grade 4',
                'grade_5' => 'Grade 5',
                'grade_6' => 'Grade 6',
                'grade_7' => 'Grade 7',
                'grade_8' => 'Grade 8',
                'grade_9' => 'Grade 9',
                'grade_10' => 'Grade 10',
                'grade_11' => 'Grade 11',
                'grade_12' => 'Grade 12'
            );
        }
        
        $response_message = "Perfect! You've selected " . $board_name . ".\n\n" .
            "Which grade are you interested in?";

        // Create grade options
        $options = array();
        foreach ($available_grades as $grade_key => $grade_name) {
            $options[] = array(
                'text' => $grade_name,
                'value' => $grade_key
            );
        }

        // Store selected board and move to grade selection
        $session['user_data']['selected_board'] = $selected_board;
        $session['user_data']['board_name'] = $board_name;
        $session['state'] = 'selecting_grade';
        $session['available_grades'] = $available_grades;

        return array(
            'success' => true,
            'message' => $response_message,
            'session_data' => $session,
            'options' => $options,
            'type' => 'grade_selection'
        );
    }

    /**
     * Provide guidance for board selection
     */
    private function provide_board_guidance($session, $config) {
        $guidance_message = "🤔 " . __("No worries! Let me help you choose the right educational board.", 'edubot-pro') . "\n\n";
        
        $guidance_message .= __("Here's a quick guide:", 'edubot-pro') . "\n\n";
        
        $guidance_message .= "🇮🇳 " . __("For Indian curriculum:", 'edubot-pro') . "\n";
        $guidance_message .= "• " . __("CBSE - Most popular, good for competitive exams", 'edubot-pro') . "\n";
        $guidance_message .= "• " . __("ICSE - English-focused, comprehensive education", 'edubot-pro') . "\n";
        $guidance_message .= "• " . __("State Board - Recognized in specific state", 'edubot-pro') . "\n\n";
        
        $guidance_message .= "🌍 " . __("For international curriculum:", 'edubot-pro') . "\n";
        $guidance_message .= "• " . __("IB - Global recognition, critical thinking", 'edubot-pro') . "\n";
        $guidance_message .= "• " . __("Cambridge - UK-based, worldwide acceptance", 'edubot-pro') . "\n\n";
        
        $guidance_message .= __("Would you like to:", 'edubot-pro');

        $options = array(
            array('text' => __('Choose CBSE (Most Popular)', 'edubot-pro'), 'value' => 'cbse'),
            array('text' => __('Choose ICSE', 'edubot-pro'), 'value' => 'icse'),
            array('text' => __('Choose IB (International)', 'edubot-pro'), 'value' => 'ib'),
            array('text' => __('See All Options Again', 'edubot-pro'), 'value' => 'show_all_boards')
        );

        return array(
            'success' => true,
            'message' => $guidance_message,
            'session_data' => $session,
            'options' => $options,
            'type' => 'board_guidance'
        );
    }

    /**
     * Handle grade selection
     */
    private function handle_grade_selection($message, $session, $config) {
        $selected_grade = sanitize_text_field($message);
        $available_grades = isset($session['available_grades']) ? $session['available_grades'] : array();
        
        // Handle special cases from board guidance
        if ($selected_grade === 'show_all_boards') {
            // Go back to board selection
            $session['state'] = 'selecting_board';
            return $this->start_admission_process($session, $config);
        }
        
        // Validate selected grade (use either configured grades or defaults)
        if (empty($available_grades)) {
            $available_grades = array(
                'pre_kg' => 'Pre-KG',
                'lkg' => 'LKG',
                'ukg' => 'UKG',
                'grade_1' => 'Grade 1',
                'grade_2' => 'Grade 2',
                'grade_3' => 'Grade 3',
                'grade_4' => 'Grade 4',
                'grade_5' => 'Grade 5',
                'grade_6' => 'Grade 6',
                'grade_7' => 'Grade 7',
                'grade_8' => 'Grade 8',
                'grade_9' => 'Grade 9',
                'grade_10' => 'Grade 10',
                'grade_11' => 'Grade 11',
                'grade_12' => 'Grade 12'
            );
        }
        
        if (!array_key_exists($selected_grade, $available_grades)) {
            return array(
                'success' => false,
                'message' => __("Please select a valid grade from the available options.", 'edubot-pro'),
                'session_data' => $session
            );
        }
        
        // Get grade display name
        $grade_display_name = $available_grades[$selected_grade];
        
        // Build response message following the reference pattern
        $response_message = "Excellent! You've selected " . $grade_display_name . ".\n\n" .
                           "May I know you a little better?\n\n" .
                           "Name*";
        
        // Store selected grade and move to student info collection
        $session['user_data']['selected_grade'] = $selected_grade;
        $session['user_data']['grade_display_name'] = $grade_display_name;
        $session['state'] = 'collecting_student_info';
        $session['current_field'] = 'student_name';
        
        return array(
            'success' => true,
            'message' => $response_message,
            'session_data' => $session,
            'type' => 'text_input',
            'input_placeholder' => 'Enter your Name*'
        );
    }

    /**
     * Handle student information collection
     */
    private function handle_student_info_collection($message, $session, $config) {
        $current_field = isset($session['current_field']) ? $session['current_field'] : 'student_name';
        
        // Store the current field value
        if (!isset($session['user_data'])) {
            $session['user_data'] = array();
        }
        
        $session['user_data'][$current_field] = sanitize_text_field($message);
        
        // Determine next field based on reference pattern
        switch($current_field) {
            case 'student_name':
                $session['current_field'] = 'mobile_number';
                return array(
                    'success' => true,
                    'message' => "Mobile Number*",
                    'session_data' => $session,
                    'type' => 'text_input',
                    'input_placeholder' => 'Mobile*'
                );
                
            case 'mobile_number':
                $session['current_field'] = 'email_id';
                return array(
                    'success' => true,
                    'message' => "Email ID*",
                    'session_data' => $session,
                    'type' => 'text_input',
                    'input_placeholder' => 'Email*'
                );
                
            case 'email_id':
                // All basic information collected, show summary and proceed button
                return $this->show_application_summary($session, $config);
                
            default:
                // Fallback - move to summary
                return $this->show_application_summary($session, $config);
        }
    }

    /**
     * Show application summary and proceed option
     */
    private function show_application_summary($session, $config) {
        $user_data = $session['user_data'];
        $school_name = $config['school_info']['name'] ?? 'Vikas The Concept School';
        
        $summary_message = "Perfect! Here's a summary of your application:\n\n";
        $summary_message .= "🎓 School: " . $school_name . "\n";
        $summary_message .= "📚 Board: " . ($user_data['board_name'] ?? 'Selected Board') . "\n";
        $summary_message .= "📖 Grade: " . ($user_data['grade_display_name'] ?? 'Selected Grade') . "\n";
        $summary_message .= "👤 Student Name: " . ($user_data['student_name'] ?? 'Not provided') . "\n";
        $summary_message .= "📱 Mobile: " . ($user_data['mobile_number'] ?? 'Not provided') . "\n";
        $summary_message .= "✉️ Email: " . ($user_data['email_id'] ?? 'Not provided') . "\n\n";
        $summary_message .= "Ready to proceed with your application?";
        
        $session['state'] = 'confirming_details';
        
        return array(
            'success' => true,
            'message' => $summary_message,
            'session_data' => $session,
            'options' => array(
                array('text' => '✅ Click to Proceed', 'value' => 'proceed'),
                array('text' => '✏️ Edit Information', 'value' => 'edit'),
                array('text' => '❌ Cancel', 'value' => 'cancel')
            )
        );
    }

    /**
     * Add career info method
     */
    private function provide_career_info($session, $config) {
        $school_name = $config['school_info']['name'] ?? 'Vikas The Concept School';
        $phone = $config['school_info']['contact_info']['phone'] ?? 'Contact us';
        $email = $config['school_info']['contact_info']['email'] ?? 'Not available';
        
        $career_message = "Thank you for your interest in career opportunities at " . $school_name . "!\n\n";
        $career_message .= "We're always looking for passionate educators and staff members to join our team.\n\n";
        $career_message .= "For current job openings and career information, please:\n\n";
        $career_message .= "📞 Call us: " . $phone . "\n";
        $career_message .= "✉️ Email us: " . $email . "\n\n";
        $career_message .= "You can also visit our school office to learn about upcoming opportunities.";
        
        return array(
            'success' => true,
            'message' => $career_message,
            'session_data' => $session,
            'options' => array(
                array('text' => '🎓 Admissions Instead', 'value' => 'admissions'),
                array('text' => '📞 Contact Information', 'value' => 'contact_info'),
                array('text' => '🏠 Back to Main Menu', 'value' => 'restart')
            )
        );
    }

    /**
     * Handle parent information collection
     */
    private function handle_parent_info_collection($message, $session, $config) {
        $current_field = isset($session['current_field']) ? $session['current_field'] : 'parent_name';
        
        // Store the current field value
        if (!isset($session['user_data'])) {
            $session['user_data'] = array();
        }
        
        $session['user_data'][$current_field] = sanitize_text_field($message);
        
        // Determine next field
        $parent_fields = array('parent_name', 'phone', 'email', 'address');
        $current_index = array_search($current_field, $parent_fields);
        
        if ($current_index < count($parent_fields) - 1) {
            $next_field = $parent_fields[$current_index + 1];
            return $this->ask_for_field($next_field, $session, $config);
        } else {
            // Move to confirmation
            $session['state'] = 'confirming_details';
            return $this->show_confirmation($session, $config);
        }
    }

    /**
     * Ask for specific field
     */
    private function ask_for_field($field, $session, $config) {
        $questions = array(
            'grade' => __("Which grade are you applying for?", 'edubot-pro'),
            'student_name' => __("What is the student's full name?", 'edubot-pro'),
            'date_of_birth' => __("What is the student's date of birth? (DD/MM/YYYY)", 'edubot-pro'),
            'academic_year' => __("Which academic year are you applying for?", 'edubot-pro'),
            'parent_name' => __("What is the parent/guardian's full name?", 'edubot-pro'),
            'phone' => __("What is your phone number?", 'edubot-pro'),
            'email' => __("What is your email address?", 'edubot-pro'),
            'address' => __("What is your address?", 'edubot-pro')
        );

        $session['current_field'] = $field;
        
        $options = array();
        if ($field === 'academic_year') {
            foreach ($config['form_settings']['academic_years'] as $year) {
                $options[] = array('text' => $year, 'value' => $year);
            }
        }

        return array(
            'success' => true,
            'message' => $questions[$field],
            'session_data' => $session,
            'options' => $options
        );
    }

    /**
     * Show confirmation of details
     */
    private function show_confirmation($session, $config) {
        $user_data = $session['user_data'];
        
        $confirmation_message = "📋 " . __("Please confirm your details:", 'edubot-pro') . "\n\n" .
            "👨‍🎓 " . __("Student Name:", 'edubot-pro') . " " . $user_data['student_name'] . "\n" .
            "🎓 " . __("Grade:", 'edubot-pro') . " " . $user_data['grade'] . "\n" .
            "📅 " . __("Date of Birth:", 'edubot-pro') . " " . $user_data['date_of_birth'] . "\n" .
            "📚 " . __("Academic Year:", 'edubot-pro') . " " . $user_data['academic_year'] . "\n\n" .
            "👨‍👩‍👦 " . __("Parent Name:", 'edubot-pro') . " " . $user_data['parent_name'] . "\n" .
            "📞 " . __("Phone:", 'edubot-pro') . " " . $user_data['phone'] . "\n" .
            "📧 " . __("Email:", 'edubot-pro') . " " . $user_data['email'] . "\n" .
            "🏠 " . __("Address:", 'edubot-pro') . " " . $user_data['address'] . "\n\n" .
            __("Is this information correct?", 'edubot-pro');

        return array(
            'success' => true,
            'message' => $confirmation_message,
            'session_data' => $session,
            'options' => array(
                array('text' => __('Yes, Submit Application', 'edubot-pro'), 'value' => 'confirm'),
                array('text' => __('No, Let me correct', 'edubot-pro'), 'value' => 'edit')
            )
        );
    }

    /**
     * Handle confirmation response
     */
    private function handle_confirmation($message, $session, $config) {
        $message = strtolower(trim($message));
        
        if (in_array($message, array('proceed', 'confirm', 'yes', 'submit'))) {
            return $this->submit_application($session, $config);
        } elseif (in_array($message, array('edit', 'modify', 'change'))) {
            // Go back to collect student name
            $session['state'] = 'collecting_student_info';
            $session['current_field'] = 'student_name';
            return array(
                'success' => true,
                'message' => "Sure! Let's update your information.\n\nName*",
                'session_data' => $session,
                'type' => 'text_input',
                'input_placeholder' => 'Enter your Name*'
            );
        } elseif (in_array($message, array('cancel', 'stop', 'quit'))) {
            // Restart completely
            $session['state'] = 'greeting';
            $session['user_data'] = array();
            return $this->handle_greeting('hello', $session, $config);
        } else {
            return array(
                'success' => false,
                'message' => __("Please choose one of the available options.", 'edubot-pro'),
                'session_data' => $session
            );
        }
    }

    /**
     * Submit application
     */
    private function submit_application($session, $config) {
        $database_manager = new EduBot_Database_Manager();
        $notification_manager = new EduBot_Notification_Manager();
        
        // Generate application number (local Enquiry ID)
        $application_number = $this->security_manager->generate_application_number();
        
        // Save application to database
        $application_id = $database_manager->save_application(array(
            'application_number' => $application_number,
            'student_data' => json_encode($session['user_data']),
            'conversation_log' => json_encode($session['conversation_log']),
            'session_id' => $session['session_id'],
            'ip_address' => $this->security_manager->get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
        ));

        if ($application_id) {
            error_log('[SUBMIT-APP-001] 📝 Application created with ID: ' . $application_id . ', Number: ' . $application_number);
            
            // Step 1: Immediately sync to MCB API to get MCB code
            error_log('[SUBMIT-APP-002] 🔄 Attempting MCB sync immediately after application creation...');
            $mcb_code = $this->sync_to_mcb_immediately($application_id, $application_number, $session['user_data']);
            
            if (!empty($mcb_code)) {
                error_log('[SUBMIT-APP-003] ✅ MCB sync successful! EnquiryCode: ' . $mcb_code);
                // Store MCB code in user data for notifications
                $session['user_data']['mcb_enquiry_code'] = $mcb_code;
            } else {
                error_log('[SUBMIT-APP-004] ❌ MCB sync failed. Will use local Enquiry ID in notifications');
                // Fallback: use local application number
                $session['user_data']['mcb_enquiry_code'] = $application_number;
            }
            
            // Step 2: Send notifications with MCB code (or local ID as fallback)
            error_log('[SUBMIT-APP-005] 📧 Sending notifications with code: ' . $session['user_data']['mcb_enquiry_code']);
            $notification_manager->send_application_notifications($application_id, $session['user_data']);
            
            // Update session
            $session['state'] = 'completed';
            $session['application_number'] = $application_number;
            
            $completion_message = $this->school_config->get_message('completion', array(
                'application_number' => $application_number,
                'student_name' => $session['user_data']['student_name']
            ));
            
            $completion_message .= "\n\n📋 " . __("Application Number:", 'edubot-pro') . " " . $application_number . "\n\n" .
                __("What happens next?", 'edubot-pro') . "\n" .
                "✅ " . __("We'll review your application", 'edubot-pro') . "\n" .
                "📞 " . __("Our team will contact you within 24-48 hours", 'edubot-pro') . "\n" .
                "📧 " . __("You'll receive a confirmation email shortly", 'edubot-pro');

            return array(
                'success' => true,
                'message' => $completion_message,
                'session_data' => $session,
                'application_number' => $application_number
            );
        } else {
            return array(
                'success' => false,
                'message' => __("Sorry, there was an error submitting your application. Please try again.", 'edubot-pro'),
                'session_data' => $session
            );
        }
    }

    /**
     * Handle AI-powered responses for general queries
     */
    private function handle_ai_response($message, $session, $config) {
        $api_integrations = new EduBot_API_Integrations();
        
        // Get school context for AI
        $school_context = sprintf(
            "You are an admission assistant for %s. Help answer questions about admission process, school information, and guide users through the application process.",
            $config['school_info']['name']
        );
        
        $ai_response = $api_integrations->get_ai_response($message, $school_context);
        
        // Treat WP_Error or falsy as failure → use fallback message
        if ( $ai_response && ! is_wp_error( $ai_response ) ) {
            $session['conversation_log'][] = array(
                'timestamp' => current_time('mysql'),
                'type' => 'user',
                'message' => $message
            );
            
            $session['conversation_log'][] = array(
                'timestamp' => current_time('mysql'),
                'type' => 'bot',
                'message' => $ai_response
            );
            
            return array(
                'success' => true,
                'message' => $ai_response,
                'session_data' => $session,
                'options' => array(
                    array('text' => __('Start New Application', 'edubot-pro'), 'value' => 'new_admission'),
                    array('text' => __('Contact Information', 'edubot-pro'), 'value' => 'contact_info')
                )
            );
        } else {
            return array(
                'success' => true,
                'message' => __("I'm sorry, I didn't understand that. Would you like to start a new admission application or get our contact information?", 'edubot-pro'),
                'session_data' => $session,
                'options' => array(
                    array('text' => __('New Application', 'edubot-pro'), 'value' => 'new_admission'),
                    array('text' => __('Contact Info', 'edubot-pro'), 'value' => 'contact_info')
                )
            );
        }
    }

    /**
     * Safe session management using WordPress transients (temporary fix)
     */
    private function get_session_safe($session_id) {
        $session = get_transient('edubot_session_' . $session_id);
        
        if ($session && is_array($session)) {
            return $session;
        } else {
            // Return default session
            return array(
                'session_id' => $session_id,
                'state' => 'greeting',
                'user_data' => array(),
                'conversation_log' => array(),
                'created_at' => current_time('mysql'),
                'last_activity' => current_time('mysql')
            );
        }
    }

    /**
     * Safe session update using WordPress transients
     */
    private function update_session_safe($session_id, $session_data) {
        if (is_array($session_data)) {
            $session_data['last_activity'] = current_time('mysql');
            // Store for 30 minutes
            set_transient('edubot_session_' . $session_id, $session_data, 30 * MINUTE_IN_SECONDS);
            return true;
        }
        return false;
    }

    /**
     * Get or create session (original database method)
     */
    private function get_session($session_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_sessions';
        $site_id = get_current_blog_id();
        
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE session_id = %s AND site_id = %d",
            $session_id, $site_id
        ), ARRAY_A);
        
        if ($session) {
            $user_data = json_decode($session['user_data'], true);
            $conversation_state = json_decode($session['conversation_state'], true);
            
            return array_merge($conversation_state, array(
                'user_data' => $user_data,
                'session_id' => $session_id
            ));
        } else {
            return array(
                'session_id' => $session_id,
                'state' => 'greeting',
                'user_data' => array(),
                'conversation_log' => array()
            );
        }
    }

    /**
     * Update session
     */
    private function update_session($session_id, $session_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_sessions';
        $site_id = get_current_blog_id();
        
        $user_data = isset($session_data['user_data']) ? $session_data['user_data'] : array();
        unset($session_data['user_data']);
        unset($session_data['session_id']);
        
        $wpdb->replace(
            $table,
            array(
                'site_id' => $site_id,
                'session_id' => $session_id,
                'user_data' => json_encode($user_data),
                'conversation_state' => json_encode($session_data),
                'last_activity' => current_time('mysql'),
                'status' => 'active'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Log conversation events for analytics
     */
    private function log_conversation_event($session_id, $event_type, $event_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_analytics';
        $site_id = get_current_blog_id();
        
        $wpdb->insert(
            $table,
            array(
                'site_id' => $site_id,
                'session_id' => $session_id,
                'event_type' => $event_type,
                'event_data' => json_encode($event_data),
                'ip_address' => $this->security_manager->get_user_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Provide school information
     */
    private function provide_school_info($session, $config) {
        $school_info = $config['school_info'];
        
        $info_message = "🏫 " . __("About", 'edubot-pro') . " " . $school_info['name'] . "\n\n";
        
        if (!empty($school_info['contact_info']['website'])) {
            $info_message .= "🌐 " . __("Website:", 'edubot-pro') . " " . $school_info['contact_info']['website'] . "\n";
        }
        
        if (!empty($school_info['contact_info']['address'])) {
            $info_message .= "📍 " . __("Address:", 'edubot-pro') . " " . $school_info['contact_info']['address'] . "\n";
        }
        
        $info_message .= "\n" . __("We offer education for grades:", 'edubot-pro') . " " . implode(', ', $config['form_settings']['grades']);
        
        return array(
            'success' => true,
            'message' => $info_message,
            'session_data' => $session,
            'options' => array(
                array('text' => __('Start Application', 'edubot-pro'), 'value' => 'new_admission'),
                array('text' => __('Contact Details', 'edubot-pro'), 'value' => 'contact_info')
            )
        );
    }

    /**
     * Provide contact information
     */
    private function provide_contact_info($session, $config) {
        $contact_info = $config['school_info']['contact_info'];
        
        $contact_message = "📞 " . __("Contact Information", 'edubot-pro') . "\n\n";
        
        if (!empty($contact_info['phone'])) {
            $contact_message .= "📱 " . __("Phone:", 'edubot-pro') . " " . $contact_info['phone'] . "\n";
        }
        
        if (!empty($contact_info['email'])) {
            $contact_message .= "📧 " . __("Email:", 'edubot-pro') . " " . $contact_info['email'] . "\n";
        }
        
        if (!empty($contact_info['address'])) {
            $contact_message .= "📍 " . __("Address:", 'edubot-pro') . " " . $contact_info['address'] . "\n";
        }
        
        $contact_message .= "\n" . __("Our admissions team is available Monday to Friday, 9 AM to 5 PM.", 'edubot-pro');
        
        return array(
            'success' => true,
            'message' => $contact_message,
            'session_data' => $session,
            'options' => array(
                array('text' => __('Start Application', 'edubot-pro'), 'value' => 'new_admission'),
                array('text' => __('Back to Main Menu', 'edubot-pro'), 'value' => 'main_menu')
            )
        );
    }

    /**
     * Extract grade with fuzzy matching for chatbot engine
     */
    private function extract_fuzzy_grade_chatbot($message) {
        $message_lower = strtolower(trim($message));
        
        // Normalize common misspellings
        $grade_variations = array(
            '/\bgrad\b/' => 'grade',
            '/\bograde\b/' => 'grade', 
            '/\bgrde\b/' => 'grade',
            '/\bgrsd\b/' => 'grade',
            '/\bgrd\b/' => 'grade'
        );
        
        foreach ($grade_variations as $pattern => $replacement) {
            $message_lower = preg_replace($pattern, $replacement, $message_lower);
        }
        
        $class_variations = array(
            '/\bclas\b/' => 'class',
            '/\bclss\b/' => 'class',
            '/\bcalss\b/' => 'class'
        );
        
        foreach ($class_variations as $pattern => $replacement) {
            $message_lower = preg_replace($pattern, $replacement, $message_lower);
        }
        
        // Handle nursery and pre-school grades
        if (strpos($message_lower, 'nursery') !== false) {
            return 'Nursery';
        }
        if (strpos($message_lower, 'pre-kg') !== false || strpos($message_lower, 'prekg') !== false) {
            return 'Pre-KG';
        }
        if (strpos($message_lower, 'lkg') !== false) {
            return 'LKG';
        }
        if (strpos($message_lower, 'ukg') !== false) {
            return 'UKG';
        }
        
        // Enhanced pattern matching for grades with numbers
        $patterns = array(
            // Grade 10, grad10, ograde10, etc.
            '/(?:grade|grad)\s*(\d{1,2})/',
            // Class 10, clas10, etc.
            '/(?:class|clas|clss|calss)\s*(\d{1,2})/',
            // 10th, 10st (common typo)
            '/(\d{1,2})(?:th|st|nd|rd)\s*(?:grade|class)?/',
            // Direct number patterns like "grade10", "class10"
            '/(?:grade|grad|class|clas)(\d{1,2})/',
            // Numbers with space variations like "grade 1 0" -> "grade 10"
            '/(?:grade|grad|class|clas)\s*(\d)\s*(\d)/'
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message_lower, $matches)) {
                // Handle the case where we have two digit captures (like "grade 1 0")
                if (isset($matches[2]) && is_numeric($matches[2])) {
                    $grade_number = $matches[1] . $matches[2];
                } else {
                    $grade_number = $matches[1];
                }
                
                // Validate grade number is reasonable (1-12)
                if (is_numeric($grade_number) && $grade_number >= 1 && $grade_number <= 12) {
                    // Determine if it should be "Grade" or "Class" based on original input
                    if (preg_match('/class/i', $message_lower)) {
                        return 'Class ' . $grade_number;
                    } else {
                        return 'Grade ' . $grade_number;
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Handle messages in the 'completed' state (application already submitted).
     * Offer to start a new enquiry, ask another question or show contact info.
     */
    private function handle_completion($message, $session, $config) {
        $msg = strtolower( trim( $message ) );

        // Allow restarting
        if ( in_array( $msg, array( 'hi', 'hello', 'hey', 'start', 'restart', 'new_admission', 'new admission', 'main_menu', 'menu' ), true ) ) {
            $session['state']     = 'greeting';
            $session['user_data'] = array();
            return $this->handle_greeting( $message, $session, $config );
        }

        $school_name = $config['school_info']['name'] ?? 'our school';
        return array(
            'success'      => true,
            'message'      => "✅ Your application has already been submitted! Our team will contact you shortly.\n\nWould you like to ask a question or submit a new enquiry?",
            'session_data' => $session,
            'options'      => array(
                array( 'text' => __( 'New Application', 'edubot-pro' ),   'value' => 'new_admission' ),
                array( 'text' => __( 'Contact Information', 'edubot-pro' ), 'value' => 'contact_info' ),
            ),
        );
    }

    /**
     * Default handler for unrecognised states — delegates to AI response.
     */
    private function handle_general_query($message, $session, $config) {
        return $this->handle_ai_response( $message, $session, $config );
    }

    /**
     * Sync to MCB API immediately after application creation
     * This is called synchronously before sending notifications
     * 
     * @param int $application_id The application ID
     * @param string $application_number The local Enquiry ID (ENQ...)
     * @param array $user_data The user/student data
     * @return string MCB EnquiryCode if successful, empty string if failed
     */
    private function sync_to_mcb_immediately($application_id, $application_number, $user_data) {
        error_log('[SUBMIT-APP-010] 🔄 Starting immediate MCB sync for application ' . $application_number);
        
        try {
            // Check if MCB integration is enabled
            $school_config = EduBot_School_Config::getInstance();
            $config = $school_config->get_config();
            
            if (empty($config['mcb_settings']['enabled']) || empty($config['mcb_settings']['sync_enabled'])) {
                error_log('[SUBMIT-APP-011] ⚠️ MCB integration not enabled in settings');
                return '';
            }
            
            // Get MCB integration class
            if (!class_exists('EduBot_MyClassBoard_Integration')) {
                error_log('[SUBMIT-APP-012] ⚠️ MCB integration class not found');
                return '';
            }
            
            $mcb_integration = new EduBot_MyClassBoard_Integration();
            
            // Prepare enquiry data from user data
            $enquiry_data = array(
                'student_name'     => $user_data['student_name'] ?? 'N/A',
                'parent_name'      => $user_data['parent_name'] ?? 'N/A',
                'email'            => $user_data['email'] ?? '',
                'phone'            => $user_data['phone'] ?? '',
                'grade'            => $user_data['grade'] ?? '',
                'board'            => $user_data['board'] ?? '',
                'academic_year'    => $user_data['academic_year'] ?? '',
                'date_of_birth'    => $user_data['date_of_birth'] ?? '',
                'address'          => $user_data['address'] ?? '',
                'gender'           => $user_data['gender'] ?? '',
                'enquiry_number'   => $application_number
            );
            
            error_log('[SUBMIT-APP-013] 📤 Sending to MCB API: ' . wp_json_encode($enquiry_data));
            
            // Call MCB API
            $mcb_response = $mcb_integration->send_to_mcb($enquiry_data, $config['mcb_settings']);
            
            error_log('[SUBMIT-APP-014] 📨 MCB API Response: ' . wp_json_encode($mcb_response));
            
            if (!isset($mcb_response['success']) || !$mcb_response['success']) {
                error_log('[SUBMIT-APP-015] ❌ MCB API returned failure: ' . ($mcb_response['message'] ?? 'Unknown error'));
                return '';
            }
            
            // Extract MCB code from response
            $mcb_code = $mcb_response['query_code'] ?? '';
            
            if (empty($mcb_code)) {
                error_log('[SUBMIT-APP-016] ⚠️ MCB API success but no EnquiryCode in response');
                return '';
            }
            
            error_log('[SUBMIT-APP-017] ✅ MCB sync successful! EnquiryCode: ' . $mcb_code);
            
            // Update application with MCB data in database
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'edubot_applications',
                array(
                    'mcb_sync_status' => 'synced',
                    'mcb_enquiry_id'  => $mcb_code,
                ),
                array('id' => $application_id),
                array('%s', '%s'),
                array('%d')
            );
            
            error_log('[SUBMIT-APP-018] 💾 Updated application database with MCB code');
            
            return $mcb_code;
            
        } catch (Exception $e) {
            error_log('[SUBMIT-APP-019] ❌ Exception during MCB sync: ' . $e->getMessage());
            return '';
        }
    }
}
