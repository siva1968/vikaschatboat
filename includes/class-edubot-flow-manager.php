<?php
/**
 * Enhanced Multi-Flow Chatbot System
 * Comprehensive workflow management for multiple enquiry types
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * EduBot Flow Manager
 * Handles multiple conversation flows and session management
 */
class EduBot_Flow_Manager {
    
    // Define supported flow types
    const FLOW_TYPES = array(
        'admission' => array(
            'name' => 'Admission Enquiry',
            'steps' => array('personal_info', 'academic_info', 'final_details', 'completed'),
            'required_fields' => array('name', 'email', 'phone', 'grade', 'board', 'dob'),
            'completion_action' => 'generate_enquiry_number'
        ),
        'information' => array(
            'name' => 'Information Request',
            'steps' => array('topic_selection', 'details_collection', 'completed'),
            'required_fields' => array('name', 'email', 'topic'),
            'completion_action' => 'send_information'
        ),
        'callback' => array(
            'name' => 'Callback Request',
            'steps' => array('contact_collection', 'timing_preference', 'completed'),
            'required_fields' => array('name', 'phone', 'preferred_time'),
            'completion_action' => 'schedule_callback'
        ),
        'tour' => array(
            'name' => 'Virtual Tour Request',
            'steps' => array('visitor_info', 'tour_scheduling', 'completed'),
            'required_fields' => array('name', 'email', 'phone', 'visit_date'),
            'completion_action' => 'schedule_tour'
        ),
        'fees' => array(
            'name' => 'Fee Enquiry',
            'steps' => array('grade_selection', 'contact_details', 'completed'),
            'required_fields' => array('name', 'email', 'grade'),
            'completion_action' => 'send_fee_structure'
        )
    );
    
    // Session expiry time (in seconds) - 1 hour
    const SESSION_EXPIRY = 3600;
    
    private $current_flow = null;
    private $current_session = null;
    
    public function __construct() {
        add_action('wp_loaded', array($this, 'cleanup_expired_sessions'));
    }
    
    /**
     * Initialize a new conversation flow
     */
    public function init_flow($flow_type, $session_id = null) {
        // Validate flow type
        if (!isset(self::FLOW_TYPES[$flow_type])) {
            throw new InvalidArgumentException("Unsupported flow type: {$flow_type}");
        }
        
        // Generate session ID if not provided
        if (!$session_id) {
            $session_id = 'flow_' . $flow_type . '_' . uniqid();
        }
        
        // Create session data
        $session_data = array(
            'session_id' => $session_id,
            'flow_type' => $flow_type,
            'flow_config' => self::FLOW_TYPES[$flow_type],
            'current_step' => 0,
            'step_name' => self::FLOW_TYPES[$flow_type]['steps'][0],
            'started_at' => current_time('timestamp'),
            'last_activity' => current_time('timestamp'),
            'status' => 'active',
            'collected_data' => array(),
            'validation_errors' => array(),
            'retry_count' => 0
        );
        
        // Save session
        $this->save_session($session_id, $session_data);
        
        error_log("EduBot Flow: Initialized {$flow_type} flow with session {$session_id}");
        
        return $session_data;
    }
    
    /**
     * Process message within current flow context
     */
    public function process_message($session_id, $message, $context = array()) {
        // Load session
        $session = $this->get_session($session_id);
        if (!$session) {
            throw new Exception("Session not found: {$session_id}");
        }
        
        // Check if session expired
        if ($this->is_session_expired($session)) {
            $this->expire_session($session_id);
            throw new Exception("Session expired: {$session_id}");
        }
        
        // Update last activity
        $session['last_activity'] = current_time('timestamp');
        
        // Route to appropriate flow handler
        $flow_type = $session['flow_type'];
        $handler_method = "handle_{$flow_type}_flow";
        
        if (method_exists($this, $handler_method)) {
            $result = $this->$handler_method($session, $message, $context);
        } else {
            $result = $this->handle_generic_flow($session, $message, $context);
        }
        
        // Save updated session
        $this->save_session($session_id, $session);
        
        return $result;
    }
    
    /**
     * Handle admission enquiry flow
     */
    private function handle_admission_flow($session, $message, $context) {
        $step_name = $session['step_name'];
        $collected_data = $session['collected_data'];
        
        switch ($step_name) {
            case 'personal_info':
                return $this->process_personal_info_step($session, $message);
                
            case 'academic_info':
                return $this->process_academic_info_step($session, $message);
                
            case 'final_details':
                return $this->process_final_details_step($session, $message);
                
            default:
                return $this->handle_unknown_step($session, $message);
        }
    }
    
    /**
     * Handle information request flow
     */
    private function handle_information_flow($session, $message, $context) {
        $step_name = $session['step_name'];
        
        switch ($step_name) {
            case 'topic_selection':
                return $this->process_topic_selection_step($session, $message);
                
            case 'details_collection':
                return $this->process_details_collection_step($session, $message);
                
            default:
                return $this->handle_unknown_step($session, $message);
        }
    }
    
    /**
     * Handle callback request flow
     */
    private function handle_callback_flow($session, $message, $context) {
        $step_name = $session['step_name'];
        
        switch ($step_name) {
            case 'contact_collection':
                return $this->process_contact_collection_step($session, $message);
                
            case 'timing_preference':
                return $this->process_timing_preference_step($session, $message);
                
            default:
                return $this->handle_unknown_step($session, $message);
        }
    }
    
    /**
     * Advance to next step in flow
     */
    private function advance_step($session) {
        $flow_config = $session['flow_config'];
        $current_step = $session['current_step'];
        
        if ($current_step < count($flow_config['steps']) - 1) {
            $session['current_step'] = $current_step + 1;
            $session['step_name'] = $flow_config['steps'][$current_step + 1];
            
            // Reset retry count on successful advancement
            $session['retry_count'] = 0;
        } else {
            // Flow completed
            $session['status'] = 'completed';
            $session['completed_at'] = current_time('timestamp');
        }
        
        return $session;
    }
    
    /**
     * Validate collected data for current step
     */
    private function validate_step_data($session, $data) {
        $flow_config = $session['flow_config'];
        $step_name = $session['step_name'];
        $errors = array();
        
        // Define validation rules per step
        $validation_rules = $this->get_validation_rules($session['flow_type'], $step_name);
        
        foreach ($validation_rules as $field => $rules) {
            if (isset($rules['required']) && $rules['required'] && empty($data[$field])) {
                $errors[] = "Please provide {$field}";
            }
            
            if (!empty($data[$field]) && isset($rules['pattern']) && !preg_match($rules['pattern'], $data[$field])) {
                $errors[] = "Invalid format for {$field}";
            }
        }
        
        return $errors;
    }
    
    /**
     * Get validation rules for specific flow and step
     */
    private function get_validation_rules($flow_type, $step_name) {
        $rules = array();
        
        switch ($flow_type) {
            case 'admission':
                switch ($step_name) {
                    case 'personal_info':
                        $rules = array(
                            'name' => array('required' => true, 'pattern' => '/^[a-zA-Z\s]{2,50}$/'),
                            'email' => array('required' => true, 'pattern' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/'),
                            'phone' => array('required' => true, 'pattern' => '/^[6-9]\d{9}$/')
                        );
                        break;
                    case 'academic_info':
                        $rules = array(
                            'grade' => array('required' => true),
                            'board' => array('required' => true, 'pattern' => '/^(CBSE|CAIE|IB)$/i')
                        );
                        break;
                    case 'final_details':
                        $rules = array(
                            'dob' => array('required' => true, 'pattern' => '/^\d{2}\/\d{2}\/\d{4}$/')
                        );
                        break;
                }
                break;
                
            case 'callback':
                switch ($step_name) {
                    case 'contact_collection':
                        $rules = array(
                            'name' => array('required' => true, 'pattern' => '/^[a-zA-Z\s]{2,50}$/'),
                            'phone' => array('required' => true, 'pattern' => '/^[6-9]\d{9}$/')
                        );
                        break;
                    case 'timing_preference':
                        $rules = array(
                            'preferred_time' => array('required' => true)
                        );
                        break;
                }
                break;
        }
        
        return $rules;
    }
    
    /**
     * Complete flow and execute completion action
     */
    private function complete_flow($session) {
        $flow_config = $session['flow_config'];
        $completion_action = $flow_config['completion_action'];
        
        // Execute completion action
        $completion_result = $this->execute_completion_action($completion_action, $session);
        
        // Mark session as completed
        $session['status'] = 'completed';
        $session['completed_at'] = current_time('timestamp');
        $session['completion_result'] = $completion_result;
        
        return array(
            'session' => $session,
            'result' => $completion_result
        );
    }
    
    /**
     * Execute flow completion action
     */
    private function execute_completion_action($action, $session) {
        switch ($action) {
            case 'generate_enquiry_number':
                return $this->generate_enquiry_number($session);
                
            case 'send_information':
                return $this->send_information_packet($session);
                
            case 'schedule_callback':
                return $this->schedule_callback_request($session);
                
            case 'schedule_tour':
                return $this->schedule_tour_request($session);
                
            case 'send_fee_structure':
                return $this->send_fee_structure($session);
                
            default:
                return array('success' => true, 'message' => 'Flow completed successfully');
        }
    }
    
    /**
     * Session management methods
     */
    public function get_session($session_id) {
        $sessions = get_option('edubot_flow_sessions', array());
        return isset($sessions[$session_id]) ? $sessions[$session_id] : null;
    }
    
    private function save_session($session_id, $session_data) {
        $sessions = get_option('edubot_flow_sessions', array());
        $sessions[$session_id] = $session_data;
        
        // Limit stored sessions to prevent memory issues
        if (count($sessions) > 100) {
            $sessions = array_slice($sessions, -50, 50, true);
        }
        
        update_option('edubot_flow_sessions', $sessions);
        error_log("EduBot Flow: Saved session {$session_id}");
    }
    
    private function is_session_expired($session) {
        $last_activity = $session['last_activity'];
        $current_time = current_time('timestamp');
        return ($current_time - $last_activity) > self::SESSION_EXPIRY;
    }
    
    private function expire_session($session_id) {
        $sessions = get_option('edubot_flow_sessions', array());
        if (isset($sessions[$session_id])) {
            $sessions[$session_id]['status'] = 'expired';
            update_option('edubot_flow_sessions', $sessions);
        }
    }
    
    /**
     * Cleanup expired sessions
     */
    public function cleanup_expired_sessions() {
        $sessions = get_option('edubot_flow_sessions', array());
        $cleaned_sessions = array();
        $current_time = current_time('timestamp');
        
        foreach ($sessions as $session_id => $session) {
            if (!$this->is_session_expired($session) && $session['status'] !== 'expired') {
                $cleaned_sessions[$session_id] = $session;
            }
        }
        
        if (count($cleaned_sessions) !== count($sessions)) {
            update_option('edubot_flow_sessions', $cleaned_sessions);
            error_log("EduBot Flow: Cleaned " . (count($sessions) - count($cleaned_sessions)) . " expired sessions");
        }
    }
    
    /**
     * Get available flow types
     */
    public function get_available_flows() {
        return self::FLOW_TYPES;
    }
    
    /**
     * Check if user can start multiple flows
     */
    public function can_start_multiple_flows($user_identifier) {
        // Allow users to have multiple concurrent flows
        return true;
    }
    
    /**
     * Get user's active flows
     */
    public function get_user_active_flows($user_identifier) {
        $sessions = get_option('edubot_flow_sessions', array());
        $user_flows = array();
        
        foreach ($sessions as $session_id => $session) {
            // Match sessions by some user identifier (could be IP, email, etc.)
            if (isset($session['user_identifier']) && 
                $session['user_identifier'] === $user_identifier && 
                $session['status'] === 'active') {
                $user_flows[] = $session;
            }
        }
        
        return $user_flows;
    }
    
    /**
     * Step processing methods for different flows
     */
    private function process_personal_info_step($session, $message) {
        // Parse personal information
        $personal_info = $this->parse_personal_info($message);
        
        if (empty($personal_info)) {
            $session['retry_count']++;
            return array(
                'response' => "I didn't catch that. Please provide your personal information:\n\n" .
                           "👶 **Student Name**\n" .
                           "📧 **Email Address**\n" .
                           "📱 **Mobile Number**\n\n" .
                           "You can type them all together like: John Smith john@email.com 9876543210",
                'next_step' => $session['step_name'],
                'session' => $session
            );
        }
        
        // Validate the information
        $errors = $this->validate_step_data($session, $personal_info);
        if (!empty($errors)) {
            $session['retry_count']++;
            return array(
                'response' => "Please check the following:\n• " . implode("\n• ", $errors),
                'next_step' => $session['step_name'],
                'session' => $session
            );
        }
        
        // Save collected data and advance
        $session['collected_data'] = array_merge($session['collected_data'], $personal_info);
        $session = $this->advance_step($session);
        
        return array(
            'response' => "✅ **Personal Information Complete!**\n\n" .
                       "Perfect! I have your contact details:\n" .
                       "👶 Student: " . $personal_info['name'] . "\n" .
                       "📧 Email: " . $personal_info['email'] . "\n" .
                       "📱 Phone: " . $personal_info['phone'] . "\n\n" .
                       "**Step 2: Academic Information** 🎓\n\n" .
                       "Please share:\n" .
                       "• Grade/Class seeking admission for\n" .
                       "• Board Preference (CBSE/CAIE)\n\n" .
                       "You can type like: Grade 5, CBSE",
            'next_step' => $session['step_name'],
            'session' => $session
        );
    }
    
    private function process_academic_info_step($session, $message) {
        // Parse academic information (grade and board)
        $academic_info = $this->parse_academic_info($message);
        
        if (empty($academic_info)) {
            $session['retry_count']++;
            return array(
                'response' => "Please provide your academic preferences:\n\n" .
                           "• **Grade/Class** (e.g., Grade 5, Class 10)\n" .
                           "• **Board** (CBSE or CAIE)\n\n" .
                           "Example: Grade 8, CBSE",
                'next_step' => $session['step_name'],
                'session' => $session
            );
        }
        
        // Save and advance
        $session['collected_data'] = array_merge($session['collected_data'], $academic_info);
        $session = $this->advance_step($session);
        
        return array(
            'response' => "✅ **Academic Information Complete!**\n" .
                       "• Grade: " . $academic_info['grade'] . "\n" .
                       "• Board: " . $academic_info['board'] . "\n" .
                       "• Academic Year: 2026-27\n\n" .
                       "**Step 3: Final Details** 📋\n\n" .
                       "Please provide:\n" .
                       "Student's Date of Birth (dd/mm/yyyy format)\n\n" .
                       "Example: 16/10/2010",
            'next_step' => $session['step_name'],
            'session' => $session
        );
    }
    
    private function process_final_details_step($session, $message) {
        // Parse date of birth
        $dob_info = $this->parse_date_of_birth($message);
        
        if (empty($dob_info)) {
            $session['retry_count']++;
            return array(
                'response' => "Please enter the student's date of birth in **dd/mm/yyyy** format.\n\n" .
                           "**Example:** 16/10/2010\n\n" .
                           "Make sure to use the correct format with 4-digit year.",
                'next_step' => $session['step_name'],
                'session' => $session
            );
        }
        
        // Save and complete flow
        $session['collected_data'] = array_merge($session['collected_data'], $dob_info);
        $completion_result = $this->complete_flow($session);
        
        return array(
            'response' => $completion_result['result']['message'],
            'next_step' => 'completed',
            'session' => $completion_result['session']
        );
    }
    
    /**
     * Parsing helper methods
     */
    private function parse_personal_info($message) {
        $result = array();
        $message = trim($message);
        
        // Email pattern
        if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $message, $email_matches)) {
            $result['email'] = $email_matches[0];
        }
        
        // Phone pattern
        if (preg_match('/\b(\+91|91)?[-.\s]?([6-9]\d{9})\b/', $message, $phone_matches)) {
            $result['phone'] = end($phone_matches);
        }
        
        // Name pattern - extract text that's not email or phone
        $clean_message = $message;
        if (!empty($result['email'])) {
            $clean_message = str_replace($result['email'], '', $clean_message);
        }
        if (!empty($result['phone'])) {
            $clean_message = str_replace($result['phone'], '', $clean_message);
        }
        
        $clean_message = trim($clean_message);
        if (!empty($clean_message) && preg_match('/^[a-zA-Z\s]+$/', $clean_message)) {
            $result['name'] = trim($clean_message);
        }
        
        return $result;
    }
    
    private function parse_academic_info($message) {
        $result = array();
        $message_lower = strtolower($message);
        
        // Extract grade
        if (preg_match('/(?:grade|class|std)\s*(\d+|nursery|pp1|pp2|lkg|ukg)/i', $message, $grade_matches)) {
            $result['grade'] = 'Grade ' . ucfirst($grade_matches[1]);
        }
        
        // Extract board
        if (preg_match('/\b(cbse|caie|cambridge|ib)\b/i', $message, $board_matches)) {
            $board = strtoupper($board_matches[1]);
            if ($board === 'CAMBRIDGE') $board = 'CAIE';
            $result['board'] = $board;
        }
        
        return $result;
    }
    
    private function parse_date_of_birth($message) {
        $result = array();
        
        // Look for dd/mm/yyyy format
        if (preg_match('/\b(\d{1,2})\/(\d{1,2})\/(\d{4})\b/', $message, $dob_matches)) {
            $day = str_pad($dob_matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($dob_matches[2], 2, '0', STR_PAD_LEFT);
            $year = $dob_matches[3];
            
            // Basic validation
            if (checkdate($month, $day, $year)) {
                $result['dob'] = "{$day}/{$month}/{$year}";
            }
        }
        
        return $result;
    }
    
    /**
     * Completion action implementations
     */
    private function generate_enquiry_number($session) {
        // Generate unique enquiry number
        $enquiry_number = 'ENQ' . date('Y') . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        
        // Save to database (implement your database logic here)
        $this->save_enquiry_to_database($session['collected_data'], $enquiry_number);
        
        return array(
            'success' => true,
            'enquiry_number' => $enquiry_number,
            'message' => "🎉 **Admission Enquiry Submitted Successfully!**\n\n" .
                       "Your enquiry number is: **{$enquiry_number}**\n\n" .
                       "📧 You'll receive a confirmation email shortly with:\n" .
                       "• Detailed admission process\n" .
                       "• Fee structure\n" .
                       "• Next steps\n\n" .
                       "Our admission team will contact you within 24 hours.\n\n" .
                       "Thank you for your interest in our school! 🎓"
        );
    }
    
    private function save_enquiry_to_database($data, $enquiry_number) {
        // Implement database saving logic
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'edubot_enquiries';
        
        $wpdb->insert(
            $table_name,
            array(
                'enquiry_number' => $enquiry_number,
                'student_name' => $data['name'],
                'parent_email' => $data['email'],
                'parent_phone' => $data['phone'],
                'grade' => $data['grade'],
                'board' => $data['board'],
                'date_of_birth' => $data['dob'],
                'created_at' => current_time('mysql'),
                'status' => 'pending'
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
}

/**
 * Singleton instance manager
 */
class EduBot_Flow_Manager_Instance {
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new EduBot_Flow_Manager();
        }
        return self::$instance;
    }
}
?>
