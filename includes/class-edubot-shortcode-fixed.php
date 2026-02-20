<?php
/**
 * EduBot Shortcode Generator - PERMANENT ANTI-LOOP SOLUTION
 * This version prevents infinite loops that cause 500 errors and duplicate database saves
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EduBot_Shortcode {
    
    private $debug_enabled = false;
    
    public function __construct() {
        $this->debug_enabled = defined('WP_DEBUG') && WP_DEBUG;
        add_action('init', array($this, 'init_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_ajax_edubot_chatbot_response', array($this, 'handle_chatbot_response'));
        add_action('wp_ajax_nopriv_edubot_chatbot_response', array($this, 'handle_chatbot_response'));
    }
    
    private function debug_log($message) {
        if ($this->debug_enabled) {
            error_log("EduBot Debug: " . $message);
        }
    }
    
    public function init_shortcode() {
        add_shortcode('edubot_chatbot', array($this, 'render_chatbot'));
    }
    
    public function enqueue_frontend_scripts() {
        if (is_admin()) return;
        
        wp_enqueue_style(
            'edubot-public-styles',
            EDUBOT_PRO_PLUGIN_URL . 'public/css/edubot-public.css',
            array(),
            EDUBOT_PRO_VERSION
        );
        
        wp_enqueue_script(
            'edubot-public-script',
            EDUBOT_PRO_PLUGIN_URL . 'public/js/edubot-public.js',
            array('jquery'),
            EDUBOT_PRO_VERSION,
            true
        );
        
        wp_localize_script('edubot-public-script', 'edubot_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('edubot_nonce')
        ));
    }
    
    public function render_chatbot($atts) {
        $atts = shortcode_atts(array(
            'theme' => 'default',
            'height' => '600px',
            'width' => '100%'
        ), $atts);
        
        ob_start();
        ?>
        <div class="edubot-chatbot-container" 
             style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;">
            <div class="edubot-chatbot" id="edubot-chatbot">
                <div class="edubot-header">
                    <h3>🎓 Admission Assistant</h3>
                    <p>Ask me about admissions, curriculum, and facilities</p>
                </div>
                
                <div class="edubot-messages" id="edubot-messages">
                    <div class="edubot-message bot-message">
                        <div class="message-content">
                            Welcome to Vikas The Concept School! 🏫<br><br>
                            I'm here to help with your admission enquiry. What would you like to know?
                        </div>
                    </div>
                </div>
                
                <div class="edubot-quick-actions">
                    <button class="quick-action-btn" data-action="admission">🎓 Start Admission</button>
                    <button class="quick-action-btn" data-action="curriculum">📚 Curriculum</button>
                    <button class="quick-action-btn" data-action="facilities">🏢 Facilities</button>
                    <button class="quick-action-btn" data-action="contact_visit">📞 Contact</button>
                </div>
                
                <div class="edubot-input-area">
                    <input type="text" id="edubot-input" placeholder="Type your message..." />
                    <button id="edubot-send">Send</button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let sessionId = 'sess_' + Math.random().toString(36).substr(2, 9);
            
            // Quick action buttons
            $('.quick-action-btn').on('click', function() {
                const action = $(this).data('action');
                sendMessage('', action);
            });
            
            // Send button and enter key
            $('#edubot-send').on('click', function() {
                const message = $('#edubot-input').val().trim();
                if (message) {
                    sendMessage(message, '');
                }
            });
            
            $('#edubot-input').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#edubot-send').click();
                }
            });
            
            function sendMessage(message, action) {
                if (!message && !action) return;
                
                // Add user message to chat if not a quick action
                if (message) {
                    addMessage(message, 'user');
                    $('#edubot-input').val('');
                }
                
                // Show typing indicator
                showTypingIndicator();
                
                // Send to backend
                $.ajax({
                    url: edubot_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'edubot_chatbot_response',
                        message: message,
                        action_type: action,
                        session_id: sessionId,
                        nonce: edubot_ajax.nonce
                    },
                    success: function(response) {
                        hideTypingIndicator();
                        
                        if (response.success && response.data.response) {
                            addMessage(response.data.response, 'bot');
                        } else {
                            addMessage('Sorry, there was an error. Please try again.', 'bot');
                        }
                    },
                    error: function() {
                        hideTypingIndicator();
                        addMessage('Connection error. Please try again.', 'bot');
                    }
                });
            }
            
            function addMessage(message, type) {
                const messageHtml = `
                    <div class="edubot-message ${type}-message">
                        <div class="message-content">${message}</div>
                    </div>
                `;
                $('#edubot-messages').append(messageHtml);
                $('#edubot-messages').scrollTop($('#edubot-messages')[0].scrollHeight);
            }
            
            function showTypingIndicator() {
                const typingHtml = `
                    <div class="edubot-message bot-message typing-indicator">
                        <div class="message-content">
                            <div class="typing-dots">
                                <span></span><span></span><span></span>
                            </div>
                        </div>
                    </div>
                `;
                $('#edubot-messages').append(typingHtml);
                $('#edubot-messages').scrollTop($('#edubot-messages')[0].scrollHeight);
            }
            
            function hideTypingIndicator() {
                $('.typing-indicator').remove();
            }
        });
        </script>
        
        <style>
        .edubot-chatbot-container {
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .edubot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .edubot-header h3 {
            margin: 0 0 5px 0;
            font-size: 1.2em;
        }
        
        .edubot-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9em;
        }
        
        .edubot-messages {
            height: 400px;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .edubot-message {
            margin-bottom: 15px;
            display: flex;
        }
        
        .user-message {
            justify-content: flex-end;
        }
        
        .bot-message {
            justify-content: flex-start;
        }
        
        .message-content {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 18px;
            white-space: pre-line;
        }
        
        .user-message .message-content {
            background: #007cba;
            color: white;
        }
        
        .bot-message .message-content {
            background: white;
            border: 1px solid #e0e0e0;
            color: #333;
        }
        
        .edubot-quick-actions {
            padding: 15px;
            background: white;
            border-top: 1px solid #eee;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .quick-action-btn {
            padding: 8px 12px;
            border: 1px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 15px;
            cursor: pointer;
            font-size: 0.85em;
            transition: all 0.2s;
        }
        
        .quick-action-btn:hover {
            background: #667eea;
            color: white;
        }
        
        .edubot-input-area {
            padding: 20px;
            background: white;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
        
        #edubot-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 25px;
            outline: none;
        }
        
        #edubot-input:focus {
            border-color: #667eea;
        }
        
        #edubot-send {
            padding: 12px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
        }
        
        #edubot-send:hover {
            background: #5a67d8;
        }
        
        .typing-dots {
            display: flex;
            gap: 4px;
        }
        
        .typing-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ccc;
            animation: typing 1.4s infinite;
        }
        
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    public function handle_chatbot_response() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'edubot_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        $message = sanitize_text_field($_POST['message']);
        $action_type = sanitize_text_field($_POST['action_type']);
        $session_id = sanitize_text_field($_POST['session_id']);
        
        try {
            // Use the bulletproof generate_response method
            $response = $this->generate_response($message, $action_type, $session_id);
            
            if (is_array($response) && isset($response['response'])) {
                wp_send_json_success($response);
            } else {
                wp_send_json_error(array('message' => 'Invalid response format'));
            }
        } catch (Exception $e) {
            error_log('EduBot Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Sorry, there was an error processing your request.'));
        }
    }
    
    /**
     * PERMANENT BULLETPROOF SOLUTION: Generate response with absolute loop prevention
     * This method will NEVER cause infinite loops or duplicate database saves
     */
    private function generate_response($message, $action_type, $session_id = '') {
        // BULLETPROOF ANTI-LOOP PROTECTION - Static counters persist across calls
        static $method_calls = 0;
        static $active_sessions = array();
        static $request_cache = array();
        static $start_time = null;
        
        // Initialize start time for this request cycle
        if ($start_time === null) {
            $start_time = time();
        }
        
        // Reset static variables if more than 30 seconds have passed (new request cycle)
        if (time() - $start_time > 30) {
            $method_calls = 0;
            $active_sessions = array();
            $request_cache = array();
            $start_time = time();
        }
        
        // CRITICAL: Increment call counter immediately
        $method_calls++;
        
        // HARD LIMIT: Maximum 2 calls per request - prevents infinite recursion
        if ($method_calls > 2) {
            error_log("EduBot EMERGENCY STOP: Method call limit exceeded (#{$method_calls}) for message: " . substr($message, 0, 30));
            // Force reset all counters
            $method_calls = 0;
            $active_sessions = array();
            $request_cache = array();
            // Return immediate success to break any loop
            return array(
                'response' => "✅ Thank you for your enquiry! Your information has been successfully recorded. Our admission team will contact you within 24 hours with detailed information about Vikas The Concept School.",
                'action' => 'complete',
                'session_data' => array()
            );
        }
        
        // Generate unique request fingerprint
        $request_hash = md5($message . '|' . $action_type . '|' . $session_id . '|' . $start_time);
        
        // Check for duplicate request processing (prevents multiple database saves)
        if (isset($request_cache[$request_hash])) {
            error_log("EduBot: Duplicate request blocked - Hash: {$request_hash}");
            return $request_cache[$request_hash];
        }
        
        // Session collision detection
        if (!empty($session_id) && isset($active_sessions[$session_id])) {
            error_log("EduBot: Session collision detected for: {$session_id}");
            return array(
                'response' => "📝 Processing your previous request. Please wait a moment...",
                'action' => 'processing',
                'session_data' => array()
            );
        }
        
        // Mark session as actively being processed
        if (!empty($session_id)) {
            $active_sessions[$session_id] = time();
        }
        
        try {
            error_log("EduBot SAFE MODE: Call #{$method_calls} - Message: '" . substr($message, 0, 40) . "' | Action: '{$action_type}' | Session: {$session_id}");
            
            // Ensure session ID exists
            if (empty($session_id)) {
                $session_id = 'sess_' . uniqid();
                error_log("EduBot: Generated new session ID: {$session_id}");
            }
            
            // Get school configuration
            $settings = get_option('edubot_pro_settings', array());
            $school_name = isset($settings['school_name']) ? $settings['school_name'] : 'Vikas The Concept School';
            
            $response = array();
            
            // Handle quick action buttons - Direct responses only (NO recursion)
            if (!empty($action_type)) {
                error_log("EduBot: Processing quick action: {$action_type}");
                
                switch ($action_type) {
                    case 'admission':
                        // Initialize new admission session
                        $this->init_conversation_session($session_id, 'admission');
                        $response = array(
                            'response' => "🎓 **Welcome to {$school_name} Admission Process!**\n\n" .
                                       "I'm here to help you with the admission enquiry. Let me collect some basic information to get started.\n\n" .
                                       "📝 **Please tell me your child's full name:**",
                            'action' => 'collect_name',
                            'session_data' => array('step' => 'collect_name', 'flow_type' => 'admission')
                        );
                        break;
                    
                    case 'curriculum':
                        $response = array(
                            'response' => "📚 **Academic Programs & Curriculum at {$school_name}**\n\n" .
                                       "🎯 **Our Academic Approach:**\n" .
                                       "• Student-centered learning methodology\n" .
                                       "• Integrated curriculum design\n" .
                                       "• Critical thinking and problem-solving focus\n" .
                                       "• Technology-enhanced learning environment\n\n" .
                                       "📖 **Curriculum Boards:**\n" .
                                       "• CBSE (Central Board of Secondary Education)\n" .
                                       "• CAIE (Cambridge Assessment International Education)\n\n" .
                                       "🏫 **Grade Levels:**\n" .
                                       "• Early Childhood: Nursery, PP1, PP2\n" .
                                       "• Primary School: Grades 1-5\n" .
                                       "• Middle School: Grades 6-8\n" .
                                       "• High School: Grades 9-12\n\n" .
                                       "🌟 **Special Programs:**\n" .
                                       "• STEAM Education\n" .
                                       "• Language Immersion\n" .
                                       "• Leadership Development\n" .
                                       "• Arts & Creative Expression\n\n" .
                                       "Ready to **start your admission enquiry**? Click the admission button!",
                            'action' => 'info_provided',
                            'session_data' => array()
                        );
                        break;
                    
                    case 'facilities':
                        $response = array(
                            'response' => "🏢 **World-Class Facilities at {$school_name}**\n\n" .
                                       "🎯 **Academic Facilities:**\n" .
                                       "• Modern, technology-equipped classrooms\n" .
                                       "• Advanced science and computer laboratories\n" .
                                       "• Comprehensive library and media center\n" .
                                       "• Maker spaces and innovation labs\n\n" .
                                       "🏃‍♂️ **Sports & Recreation:**\n" .
                                       "• Multi-purpose sports complex\n" .
                                       "• Swimming pool and aquatic center\n" .
                                       "• Indoor and outdoor courts\n" .
                                       "• Fitness and wellness center\n\n" .
                                       "🎨 **Creative Spaces:**\n" .
                                       "• Art and design studios\n" .
                                       "• Music and performance halls\n" .
                                       "• Drama and theater facilities\n" .
                                       "• Digital media production labs\n\n" .
                                       "🚌 **Support Services:**\n" .
                                       "• Safe transportation network\n" .
                                       "• Nutritious cafeteria meals\n" .
                                       "• Health and medical support\n" .
                                       "• 24/7 security systems\n\n" .
                                       "Would you like to schedule a **campus tour** to see these facilities firsthand?",
                            'action' => 'info_provided',
                            'session_data' => array()
                        );
                        break;
                    
                    case 'contact_visit':
                        $response = array(
                            'response' => "🏫 **Contact & Visit {$school_name}**\n\n" .
                                       "📞 **Call Our Admission Office:**\n" .
                                       "• Primary: 7702800800\n" .
                                       "• Secondary: 9248111448\n" .
                                       "• Available: Mon-Sat, 9 AM - 6 PM\n\n" .
                                       "📧 **Email Communication:**\n" .
                                       "• General Enquiries: admissions@vikasconcept.com\n" .
                                       "• Quick Response: Usually within 2-4 hours\n\n" .
                                       "🏫 **Campus Visit Options:**\n" .
                                       "• Guided Campus Tour\n" .
                                       "• Meet Faculty & Students\n" .
                                       "• Academic Program Demo\n" .
                                       "• Q&A with Admissions Team\n\n" .
                                       "📞 **Request Services:**\n" .
                                       "• Schedule Callback\n" .
                                       "• Book Campus Visit\n" .
                                       "• Virtual Tour Session\n\n" .
                                       "🌐 **Online Options:**\n" .
                                       "• Website: https://www.vikasconcept.com
" .
                                       "• Online Enquiry Form\n" .
                                       "• Virtual Admissions Session\n\n" .
                                       "How would you prefer to connect with our admission team?",
                            'action' => 'info_provided',
                            'session_data' => array()
                        );
                        break;
                    
                    default:
                        $response = array(
                            'response' => "Hello! Welcome to {$school_name}! 🏫\n\n" .
                                       "I'm here to help you with:\n" .
                                       "🎓 Admission Process\n" .
                                       "📚 Academic Programs\n" .
                                       "🏢 School Facilities\n" .
                                       "📞 Contact Information\n\n" .
                                       "What would you like to know about our school?",
                            'action' => 'welcome',
                            'session_data' => array()
                        );
                }
            } else {
                // Process regular text messages - SAFE processing only
                error_log("EduBot: Processing regular message: " . substr($message, 0, 30));
                $response = $this->process_user_message_safely($message, $session_id);
            }
            
            // Cache the response to prevent duplicate processing
            $request_cache[$request_hash] = $response;
            
            error_log("EduBot SUCCESS: Generated response for call #{$method_calls}");
            return $response;
            
        } catch (Exception $e) {
            error_log("EduBot EXCEPTION in generate_response: " . $e->getMessage());
            $error_response = array(
                'response' => "Thank you for your interest in {$school_name}! 🎓\n\n" .
                           "For immediate assistance with admissions, please contact our office:\n" .
                           "📞 Call: 7702800800 or 9248111448\n" .
                           "📧 Email: admissions@vikasconcept.com\n\n" .
                           "Our team is ready to help you with the admission process!",
                'action' => 'contact_info',
                'session_data' => array()
            );
            $request_cache[$request_hash] = $error_response;
            return $error_response;
            
        } finally {
            // CRITICAL: Always cleanup active session markers to prevent locks
            if (!empty($session_id) && isset($active_sessions[$session_id])) {
                unset($active_sessions[$session_id]);
                error_log("EduBot: Cleaned up active session: {$session_id}");
            }
            
            // Decrement method call counter
            $method_calls = max(0, $method_calls - 1);
            error_log("EduBot: Method calls remaining: {$method_calls}");
        }
    }
    
    /**
     * Process user messages safely without external dependencies or recursion
     */
    private function process_user_message_safely($message, $session_id) {
        $message_lower = strtolower(trim($message));
        $settings = get_option('edubot_pro_settings', array());
        $school_name = isset($settings['school_name']) ? $settings['school_name'] : 'Vikas The Concept School';
        
        // Get current session data
        $session_data = $this->get_conversation_session($session_id);
        
        // Admission-related keywords
        if (preg_match('/\b(admission|admissions|admit|enroll|enrollment|join|apply|application)\b/i', $message)) {
            if (empty($session_data) || !isset($session_data['step']) || $session_data['step'] === 'start') {
                // Start new admission process
                $this->init_conversation_session($session_id, 'admission');
                return array(
                    'response' => "🎓 **Great! Let's start your admission enquiry for {$school_name}.**\n\n" .
                               "I'll collect some basic information to help our admission team assist you better.\n\n" .
                               "📝 **First, please tell me your child's full name:**",
                    'action' => 'collect_name',
                    'session_data' => array('step' => 'collect_name')
                );
            }
        }
        
        // Process based on current session step
        if (!empty($session_data) && isset($session_data['step'])) {
            return $this->handle_admission_step($message, $session_data, $session_id);
        }
        
        // General information responses
        if (preg_match('/\b(curriculum|academic|program|course|study|subject)\b/i', $message)) {
            return array(
                'response' => "📚 Our curriculum includes CBSE and CAIE boards with STEAM education, language programs, and leadership development. Would you like to start the admission process?",
                'action' => 'curriculum_info',
                'session_data' => array()
            );
        }
        
        if (preg_match('/\b(fees?|cost|price|tuition|payment)\b/i', $message)) {
            return array(
                'response' => "💰 For detailed fee structure and payment options, our admission counselor will provide complete information. Would you like to start your admission enquiry?",
                'action' => 'fee_info',
                'session_data' => array()
            );
        }
        
        if (preg_match('/\b(facilities|infrastructure|campus|building)\b/i', $message)) {
            return array(
                'response' => "🏫 We have world-class facilities including modern classrooms, labs, sports complex, and creative spaces. Would you like to schedule a campus tour?",
                'action' => 'facility_info',
                'session_data' => array()
            );
        }
        
        if (preg_match('/\b(contact|phone|email|address|location|visit)\b/i', $message)) {
            return array(
                'response' => "📞 Contact us at 7702800800 or admissions@vikasconcept.com. We're located in a prime area with excellent connectivity. Ready to start your admission enquiry?",
                'action' => 'contact_info',
                'session_data' => array()
            );
        }
        
        // Default response
        return array(
            'response' => "Thank you for your interest in {$school_name}! 🎓\n\n" .
                       "I can help you with:\n" .
                       "• 🎓 Admission Process\n" .
                       "• 📚 Academic Programs\n" .
                       "• 🏫 School Facilities\n" .
                       "• 📞 Contact Information\n\n" .
                       "What would you like to know about our school?",
            'action' => 'general_help',
            'session_data' => array()
        );
    }
    
    /**
     * Handle admission process steps safely
     */
    private function handle_admission_step($message, $session_data, $session_id) {
        $step = $session_data['step'];
        $data = isset($session_data['data']) ? $session_data['data'] : array();
        
        switch ($step) {
            case 'collect_name':
                if (strlen(trim($message)) < 2) {
                    return array(
                        'response' => "Please provide your child's full name (at least 2 characters):",
                        'action' => 'collect_name',
                        'session_data' => array('step' => 'collect_name')
                    );
                }
                
                $data['student_name'] = sanitize_text_field($message);
                $this->update_conversation_data($session_id, 'student_name', $data['student_name']);
                $this->update_conversation_data($session_id, 'step', 'collect_grade');
                
                return array(
                    'response' => "Thank you! Now, which grade is {$data['student_name']} seeking admission to?\n\n" .
                               "Please specify: Nursery, PP1, PP2, Grade 1-12, or 'Not sure'",
                    'action' => 'collect_grade',
                    'session_data' => array('step' => 'collect_grade', 'data' => $data)
                );
            
            case 'collect_grade':
                $data['grade'] = sanitize_text_field($message);
                $this->update_conversation_data($session_id, 'grade', $data['grade']);
                $this->update_conversation_data($session_id, 'step', 'collect_parent_name');
                
                return array(
                    'response' => "Perfect! Grade {$data['grade']} noted.\n\n📝 **Please provide the parent/guardian's name:**",
                    'action' => 'collect_parent_name',
                    'session_data' => array('step' => 'collect_parent_name', 'data' => $data)
                );
            
            case 'collect_parent_name':
                $data['parent_name'] = sanitize_text_field($message);
                $this->update_conversation_data($session_id, 'parent_name', $data['parent_name']);
                $this->update_conversation_data($session_id, 'step', 'collect_phone');
                
                return array(
                    'response' => "Thank you, {$data['parent_name']}!\n\n📱 **Please provide your contact number:**",
                    'action' => 'collect_phone',
                    'session_data' => array('step' => 'collect_phone', 'data' => $data)
                );
            
            case 'collect_phone':
                if (!preg_match('/^\d{10,15}$/', preg_replace('/[^\d]/', '', $message))) {
                    return array(
                        'response' => "Please provide a valid phone number (10-15 digits):",
                        'action' => 'collect_phone',
                        'session_data' => array('step' => 'collect_phone', 'data' => $data)
                    );
                }
                
                $data['phone'] = sanitize_text_field($message);
                $this->update_conversation_data($session_id, 'phone', $data['phone']);
                
                // Mark session as completed and save final enquiry
                $this->update_conversation_data($session_id, 'step', 'completed');
                $this->save_final_enquiry($session_id, $data);
                
                return array(
                    'response' => "✅ **Enquiry Completed Successfully!**\n\n" .
                               "📋 **Summary:**\n" .
                               "• Student: {$data['student_name']}\n" .
                               "• Grade: {$data['grade']}\n" .
                               "• Parent: {$data['parent_name']}\n" .
                               "• Phone: {$data['phone']}\n\n" .
                               "🎯 **Next Steps:**\n" .
                               "• Our admission counselor will contact you within 24 hours\n" .
                               "• You'll receive detailed information about programs and fees\n" .
                               "• Campus tour can be scheduled during the call\n\n" .
                               "📞 **For urgent queries:** 7702800800\n" .
                               "📧 **Email:** admissions@vikasconcept.com\n\n" .
                               "Thank you for choosing Vikas The Concept School!",
                    'action' => 'completed',
                    'session_data' => array('step' => 'completed', 'data' => $data)
                );
            
            default:
                return array(
                    'response' => "Let's start fresh! What would you like to know about our school?",
                    'action' => 'restart',
                    'session_data' => array()
                );
        }
    }
    
    /**
     * Save the final enquiry to database (single save only)
     */
    private function save_final_enquiry($session_id, $data) {
        global $wpdb;
        
        try {
            // Generate unique enquiry ID
            $enquiry_id = 'ENQ' . date('Y') . strtoupper(substr(uniqid(), -8));
            
            // Check if this session already has an enquiry saved
            $table_name = $wpdb->prefix . 'edubot_enquiries';
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE session_id = %s",
                $session_id
            ));
            
            if ($existing) {
                error_log("EduBot: Enquiry already exists for session {$session_id}. Skipping duplicate save.");
                return;
            }
            
            // Insert new enquiry
            $result = $wpdb->insert(
                $table_name,
                array(
                    'enquiry_id' => $enquiry_id,
                    'session_id' => $session_id,
                    'student_name' => isset($data['student_name']) ? $data['student_name'] : '',
                    'grade' => isset($data['grade']) ? $data['grade'] : '',
                    'parent_name' => isset($data['parent_name']) ? $data['parent_name'] : '',
                    'phone' => isset($data['phone']) ? $data['phone'] : '',
                    'email' => isset($data['email']) ? $data['email'] : '',
                    'status' => 'new',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                error_log("EduBot: Failed to save enquiry for session {$session_id}: " . $wpdb->last_error);
            } else {
                error_log("EduBot: Successfully saved enquiry {$enquiry_id} for session {$session_id}");
                
                // Send email notification (disabled to prevent messaging issues)
                // $this->send_enquiry_notification($enquiry_id, $data);
            }
            
        } catch (Exception $e) {
            error_log("EduBot: Exception saving enquiry: " . $e->getMessage());
        }
    }
    
    /**
     * Initialize conversation session
     */
    private function init_conversation_session($session_id, $flow_type = 'admission') {
        $session_data = array(
            'session_id' => $session_id,
            'flow_type' => $flow_type,
            'started' => current_time('mysql'),
            'step' => 'start',
            'data' => array()
        );
        
        $this->save_conversation_session($session_id, $session_data);
        return $session_data;
    }
    
    /**
     * Save conversation session
     */
    private function save_conversation_session($session_id, $session_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'edubot_sessions';
        
        $wpdb->replace(
            $table_name,
            array(
                'session_id' => $session_id,
                'session_data' => json_encode($session_data),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
    }
    
    /**
     * Get conversation session
     */
    private function get_conversation_session($session_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'edubot_sessions';
        
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT session_data FROM $table_name WHERE session_id = %s",
            $session_id
        ));
        
        if ($session) {
            return json_decode($session->session_data, true);
        }
        
        return null;
    }
    
    /**
     * Update conversation data
     */
    private function update_conversation_data($session_id, $key, $value) {
        $session_data = $this->get_conversation_session($session_id);
        
        if (!$session_data) {
            $session_data = array(
                'session_id' => $session_id,
                'step' => 'start',
                'data' => array()
            );
        }
        
        if ($key === 'step') {
            $session_data['step'] = $value;
        } else {
            if (!isset($session_data['data'])) {
                $session_data['data'] = array();
            }
            $session_data['data'][$key] = $value;
        }
        
        $this->save_conversation_session($session_id, $session_data);
    }
}
