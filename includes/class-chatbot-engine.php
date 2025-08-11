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
            $this->school_config = new EduBot_School_Config();
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
     * Process chatbot message
     */
    public function process_message($message, $session_id) {
        // Check if required components are available
        if ($this->school_config === null) {
            return array(
                'success' => false,
                'message' => __('Chatbot service is temporarily unavailable. Please try again later or contact us directly.', 'edubot-pro')
            );
        }
        
        // Rate limiting (only if security manager is available)
        if ($this->security_manager !== null) {
            if (!$this->security_manager->check_rate_limit($session_id, 30, 900)) {
                return array(
                    'success' => false,
                    'message' => __('Too many requests. Please try again later.', 'edubot-pro')
                );
            }
        }

        try {
            // Get or create session
            $session = $this->get_session($session_id);
            
            // Process message based on current state
            $response = $this->handle_conversation_flow($message, $session);
            
            // Update session
            $this->update_session($session_id, $response['session_data']);
            
            // Log analytics (only if we have the session data)
            if (isset($response['session_data'])) {
                $this->log_conversation_event($session_id, 'message_processed', array(
                    'user_message' => $message,
                    'bot_response' => $response['message'],
                ));
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
        $welcome_message = $this->school_config->get_message('welcome');
        
        $response_message = $welcome_message . "\n\n" . 
            __("I can help you with:", 'edubot-pro') . "\n" .
            "🎓 " . __("New admission application", 'edubot-pro') . "\n" .
            "📞 " . __("Contact information", 'edubot-pro') . "\n" .
            "📚 " . __("School information", 'edubot-pro') . "\n" .
            "💬 " . __("General queries", 'edubot-pro') . "\n\n" .
            __("What would you like to do today?", 'edubot-pro');

        $session_data = array(
            'state' => 'collecting_basic_info',
            'user_data' => array(),
            'conversation_log' => array(
                array(
                    'timestamp' => current_time('mysql'),
                    'type' => 'bot',
                    'message' => $response_message
                )
            )
        );

        return array(
            'success' => true,
            'message' => $response_message,
            'session_data' => $session_data,
            'options' => array(
                array('text' => __('New Admission Application', 'edubot-pro'), 'value' => 'new_admission'),
                array('text' => __('School Information', 'edubot-pro'), 'value' => 'school_info'),
                array('text' => __('Contact Details', 'edubot-pro'), 'value' => 'contact_info')
            )
        );
    }

    /**
     * Handle basic information collection
     */
    private function handle_basic_info_collection($message, $session, $config) {
        $message = strtolower(trim($message));
        
        if (in_array($message, array('new admission', 'new_admission', 'admission', 'apply'))) {
            return $this->start_admission_process($session, $config);
        } elseif (in_array($message, array('school info', 'school_info', 'about school'))) {
            return $this->provide_school_info($session, $config);
        } elseif (in_array($message, array('contact', 'contact_info', 'phone', 'address'))) {
            return $this->provide_contact_info($session, $config);
        } else {
            return $this->handle_ai_response($message, $session, $config);
        }
    }

    /**
     * Start admission application process
     */
    private function start_admission_process($session, $config) {
        try {
            // Check if school config is available
            if ($this->school_config === null) {
                return array(
                    'success' => false,
                    'message' => __('Sorry, the admission system is temporarily unavailable. Please contact our admissions office directly.', 'edubot-pro'),
                    'session_data' => $session
                );
            }
            
            // Get available grades for current academic year
            $school_id = $this->school_config->get_school_id();
            $available_grades = Edubot_Academic_Config::get_available_grades_for_admission($school_id);
            $current_year = Edubot_Academic_Config::get_current_academic_year($school_id);
            
            // Check if we have valid data
            if (empty($available_grades)) {
                return array(
                    'success' => false,
                    'message' => __('Sorry, no grades are currently available for admission. Please contact the admissions office for more information.', 'edubot-pro'),
                    'session_data' => $session
                );
            }
            
            if (empty($current_year) || !isset($current_year['display'])) {
                return array(
                    'success' => false,
                    'message' => __('Sorry, there was an issue retrieving the current academic year. Please contact the admissions office.', 'edubot-pro'),
                    'session_data' => $session
                );
            }
            
            $response_message = "🎓 " . __("Great! Let's start your admission application.", 'edubot-pro') . "\n\n" .
                sprintf(__("For Academic Year: %s", 'edubot-pro'), $current_year['display']) . "\n\n" .
                __("Please select the grade/class you're applying for:", 'edubot-pro');

            // Create grade options
            $options = array();
            foreach ($available_grades as $grade_key => $grade_name) {
                $options[] = array(
                    'text' => $grade_name,
                    'value' => $grade_key
                );
            }

            $session_data = $session;
            $session_data['state'] = 'selecting_grade';
            $session_data['academic_year'] = $current_year;
            $session_data['available_grades'] = $available_grades;

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
     * Handle grade selection
     */
    private function handle_grade_selection($message, $session, $config) {
        $selected_grade = sanitize_text_field($message);
        $available_grades = isset($session['available_grades']) ? $session['available_grades'] : array();
        
        // Validate selected grade
        if (!array_key_exists($selected_grade, $available_grades)) {
            return array(
                'success' => false,
                'message' => __("Please select a valid grade from the available options.", 'edubot-pro'),
                'session_data' => $session
            );
        }
        
        // Get grade display name
        $school_id = $this->school_config->get_school_id();
        $grade_display_name = Edubot_Academic_Config::get_grade_display_name($school_id, $selected_grade);
        
        // Get board requirements if applicable
        $board_config = Edubot_Academic_Config::get_school_board_config($school_id);
        $board_requirements = '';
        
        if ($board_config['board_type'] !== 'none') {
            $educational_boards = Edubot_Academic_Config::get_educational_boards();
            $board_info = $educational_boards[$board_config['board_type']] ?? null;
            
            if ($board_info && !empty($board_info['requirements'])) {
                $board_requirements = "\n\n📋 " . __("Required documents for admission:", 'edubot-pro') . "\n";
                foreach ($board_info['requirements'] as $requirement) {
                    $board_requirements .= "• " . $requirement . "\n";
                }
            }
        }
        
        $response_message = "✅ " . sprintf(__("Great! You've selected %s for admission.", 'edubot-pro'), $grade_display_name) . 
                           $board_requirements . "\n\n" .
                           __("Now I'll collect some basic information to start your application.", 'edubot-pro') . "\n\n" .
                           __("Let's start with the student's full name:", 'edubot-pro');
        
        // Store selected grade and move to student info collection
        $session['user_data']['selected_grade'] = $selected_grade;
        $session['user_data']['grade_display_name'] = $grade_display_name;
        $session['state'] = 'collecting_student_info';
        $session['current_field'] = 'student_name';
        
        return array(
            'success' => true,
            'message' => $response_message,
            'session_data' => $session,
            'type' => 'text_input'
        );
    }

    /**
     * Handle student information collection
     */
    private function handle_student_info_collection($message, $session, $config) {
        $current_field = isset($session['current_field']) ? $session['current_field'] : 'grade';
        
        // Store the current field value
        if (!isset($session['user_data'])) {
            $session['user_data'] = array();
        }
        
        $session['user_data'][$current_field] = sanitize_text_field($message);
        
        // Determine next field
        $student_fields = array('grade', 'student_name', 'date_of_birth', 'academic_year');
        $current_index = array_search($current_field, $student_fields);
        
        if ($current_index < count($student_fields) - 1) {
            $next_field = $student_fields[$current_index + 1];
            return $this->ask_for_field($next_field, $session, $config);
        } else {
            // Move to parent information
            $session['state'] = 'collecting_parent_info';
            $session['current_field'] = 'parent_name';
            return $this->ask_for_field('parent_name', $session, $config);
        }
    }

    /**
     * Handle parent information collection
     */
    private function handle_parent_info_collection($message, $session, $config) {
        $current_field = isset($session['current_field']) ? $session['current_field'] : 'parent_name';
        
        // Store the current field value
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
        
        if (in_array($message, array('confirm', 'yes', 'submit', 'correct'))) {
            return $this->submit_application($session, $config);
        } else {
            // Restart the process
            $session['state'] = 'collecting_student_info';
            $session['current_field'] = 'grade';
            return array(
                'success' => true,
                'message' => __("No problem! Let's start again. Which grade are you applying for?", 'edubot-pro'),
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
        
        // Generate application number
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
            // Send notifications
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
        
        if ($ai_response) {
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
     * Get or create session
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
}
