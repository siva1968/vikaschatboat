<?php
/**
 * EduBot Shortcode Generator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EduBot_Shortcode {
    
    public function __construct() {
        add_action('init', array($this, 'init_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_ajax_edubot_submit_application', array($this, 'handle_application_submission'));
        add_action('wp_ajax_nopriv_edubot_submit_application', array($this, 'handle_application_submission'));
        add_action('wp_ajax_edubot_chatbot_response', array($this, 'handle_chatbot_response'));
        add_action('wp_ajax_nopriv_edubot_chatbot_response', array($this, 'handle_chatbot_response'));
    }
    
    public function init_shortcode() {
        add_shortcode('edubot_chatbot', array($this, 'render_chatbot'));
        add_shortcode('edubot_application_form', array($this, 'render_application_form'));
    }
    
    public function enqueue_frontend_scripts() {
        if (is_admin()) return;
        
        wp_enqueue_script(
            'edubot-frontend',
            EDUBOT_PRO_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            EDUBOT_PRO_VERSION,
            true
        );
        
        wp_enqueue_style(
            'edubot-frontend',
            EDUBOT_PRO_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            EDUBOT_PRO_VERSION
        );
        
        wp_localize_script('edubot-frontend', 'edubot_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('edubot_nonce'),
            'messages' => array(
                'sending' => __('Sending...', 'edubot-pro'),
                'error' => __('Sorry, there was an error. Please try again.', 'edubot-pro'),
                'success' => __('Thank you! Your message has been sent.', 'edubot-pro')
            )
        ));
    }
    
    public function render_chatbot($atts) {
        $atts = shortcode_atts(array(
            'theme' => 'default',
            'position' => 'bottom-right',
            'button_text' => 'Ask us anything',
            'welcome_message' => 'Hello! How can I help you with admissions today?'
        ), $atts, 'edubot_chatbot');
        
        $settings = get_option('edubot_pro_settings', array());
        $school_name = $settings['school_name'] ?? 'Our School';
        
        ob_start();
        ?>
        <div id="edubot-chatbot" class="edubot-chatbot theme-<?php echo esc_attr($atts['theme']); ?> position-<?php echo esc_attr($atts['position']); ?>">
            <!-- Chat Button -->
            <div class="edubot-chat-button">
                <div class="button-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h4v3c0 .6.4 1 1 1 .2 0 .5-.1.7-.3L14.6 18H20c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H14l-4 3v-3H4V4h16v12z" fill="currentColor"/>
                    </svg>
                </div>
                <span class="button-text"><?php echo esc_html($atts['button_text']); ?></span>
            </div>
            
            <!-- Chat Window -->
            <div class="edubot-chat-window" style="display: none;">
                <div class="chat-header">
                    <div class="header-info">
                        <h4><?php echo esc_html($school_name); ?> Admissions</h4>
                        <span class="status">Online</span>
                    </div>
                    <button class="close-chat">&times;</button>
                </div>
                
                <div class="chat-messages">
                    <div class="message bot-message">
                        <div class="message-avatar">🤖</div>
                        <div class="message-content">
                            <p><?php echo esc_html($atts['welcome_message']); ?></p>
                            <div class="quick-actions">
                                <button class="quick-action" data-action="admission_info">Admission Information</button>
                                <button class="quick-action" data-action="fees_structure">Fees Structure</button>
                                <button class="quick-action" data-action="application_form">Apply Now</button>
                                <button class="quick-action" data-action="contact_info">Contact Us</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="chat-input-area">
                    <div class="typing-indicator" style="display: none;">
                        <span></span><span></span><span></span>
                    </div>
                    <div class="input-container">
                        <input type="text" id="chat-input" placeholder="Type your message...">
                        <button id="send-message">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" fill="currentColor"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .edubot-chatbot {
            position: fixed;
            z-index: 999999;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .edubot-chatbot.position-bottom-right {
            bottom: 20px;
            right: 20px;
        }
        .edubot-chatbot.position-bottom-left {
            bottom: 20px;
            left: 20px;
        }
        .edubot-chat-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 15px 20px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .edubot-chat-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }
        .edubot-chat-window {
            position: absolute;
            bottom: 70px;
            right: 0;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-info h4 {
            margin: 0;
            font-size: 16px;
        }
        .status {
            font-size: 12px;
            opacity: 0.9;
        }
        .close-chat {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        .message {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
            gap: 10px;
        }
        .message-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .message-content {
            background: white;
            padding: 12px 15px;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            max-width: 250px;
        }
        .message-content p {
            margin: 0;
            font-size: 14px;
            line-height: 1.4;
        }
        .user-message {
            flex-direction: row-reverse;
        }
        .user-message .message-content {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .user-message .message-avatar {
            background: #28a745;
        }
        .quick-actions {
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .quick-action {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 12px;
            cursor: pointer;
            text-align: left;
            transition: background-color 0.2s;
        }
        .quick-action:hover {
            background: #e9ecef;
        }
        .chat-input-area {
            padding: 20px;
            border-top: 1px solid #e9ecef;
        }
        .input-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        #chat-input {
            flex: 1;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            padding: 10px 15px;
            font-size: 14px;
            outline: none;
        }
        #chat-input:focus {
            border-color: #667eea;
        }
        #send-message {
            background: #667eea;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .typing-indicator {
            padding: 10px 0;
            text-align: center;
        }
        .typing-indicator span {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #667eea;
            margin: 0 2px;
            animation: typing 1.4s infinite ease-in-out;
        }
        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
        @keyframes typing {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        
        @media (max-width: 480px) {
            .edubot-chat-window {
                width: 300px;
                height: 450px;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    public function render_application_form($atts) {
        $atts = shortcode_atts(array(
            'style' => 'inline',
            'title' => 'Application Form'
        ), $atts, 'edubot_application_form');
        
        // Get school config for boards and academic years
        $school_config = new EduBot_School_Config();
        $enabled_boards = $school_config->get_enabled_boards();
        $default_board = $school_config->get_default_board();
        $board_required = $school_config->is_board_selection_required();
        
        $available_years = $school_config->get_available_academic_years();
        $default_year = $school_config->get_default_academic_year();
        $year_options = $school_config->get_academic_years_dropdown_options();
        
        ob_start();
        ?>
        <div class="edubot-application-form">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <form id="edubot-application" method="post">
                <?php wp_nonce_field('edubot_application', 'edubot_nonce'); ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_name">Student Name *</label>
                        <input type="text" id="student_name" name="student_name" required>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth *</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required>
                    </div>
                </div>
                
                <?php if (!empty($enabled_boards)): ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="educational_board">Educational Board<?php echo $board_required ? ' *' : ''; ?></label>
                        <select id="educational_board" name="educational_board" <?php echo $board_required ? 'required' : ''; ?>>
                            <option value="">Select Educational Board</option>
                            <?php foreach ($enabled_boards as $board): ?>
                                <option value="<?php echo esc_attr($board['code']); ?>" 
                                        <?php selected($default_board, $board['code']); ?>
                                        data-description="<?php echo esc_attr($board['description'] ?? ''); ?>"
                                        data-grades="<?php echo esc_attr($board['grades'] ?? ''); ?>">
                                    <?php echo esc_html($board['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="board-info" class="board-info" style="display: none;">
                            <div class="board-description"></div>
                            <div class="board-grades"></div>
                            <div class="board-features"></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($available_years)): ?>
                    <div class="form-group">
                        <label for="academic_year">Academic Year *</label>
                        <select id="academic_year" name="academic_year" required>
                            <option value="">Select Academic Year</option>
                            <?php foreach ($year_options as $year => $label): ?>
                                <option value="<?php echo esc_attr($year); ?>" <?php selected($default_year, $year); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="academic-year-info">
                            <small class="text-muted">
                                Admissions are currently open for the selected academic year(s)
                            </small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="grade">Grade/Class *</label>
                        <select id="grade" name="grade" required>
                            <option value="">Select Grade</option>
                            <option value="nursery">Nursery</option>
                            <option value="lkg">LKG</option>
                            <option value="ukg">UKG</option>
                            <option value="1">Class 1</option>
                            <option value="2">Class 2</option>
                            <option value="3">Class 3</option>
                            <option value="4">Class 4</option>
                            <option value="5">Class 5</option>
                            <option value="6">Class 6</option>
                            <option value="7">Class 7</option>
                            <option value="8">Class 8</option>
                            <option value="9">Class 9</option>
                            <option value="10">Class 10</option>
                            <option value="11">Class 11</option>
                            <option value="12">Class 12</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="parent_name">Parent/Guardian Name *</label>
                    <input type="text" id="parent_name" name="parent_name" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address *</label>
                    <textarea id="address" name="address" rows="3" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="previous_school">Previous School</label>
                        <input type="text" id="previous_school" name="previous_school">
                    </div>
                    <div class="form-group">
                        <label for="transfer_reason">Reason for Transfer</label>
                        <input type="text" id="transfer_reason" name="transfer_reason">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="special_requirements">Special Requirements/Medical Conditions</label>
                    <textarea id="special_requirements" name="special_requirements" rows="3"></textarea>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="agree_terms" name="agree_terms" required>
                        I agree to the <a href="#" target="_blank">Terms and Conditions</a> and <a href="#" target="_blank">Privacy Policy</a> *
                    </label>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="marketing_consent" name="marketing_consent">
                        I agree to receive updates about admissions and school events
                    </label>
                </div>
                
                <button type="submit" class="submit-button">
                    <span class="button-text">Submit Application</span>
                    <span class="loading-spinner" style="display: none;">Submitting...</span>
                </button>
            </form>
        </div>
        
        <style>
        .edubot-application-form {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .edubot-application-form h3 {
            margin-top: 0;
            margin-bottom: 30px;
            text-align: center;
            color: #333;
            font-size: 24px;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            flex: 1;
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .checkbox-group label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-weight: normal;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-top: 2px;
        }
        .submit-button {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .submit-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .submit-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Board Info Styles */
        .board-info {
            margin-top: 10px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
        }
        .board-description {
            margin-bottom: 8px;
            color: #666;
        }
        .board-grades {
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        .board-features {
            color: #6c757d;
            font-style: italic;
        }
        
        /* Academic Year Styles */
        .academic-year-info {
            margin-top: 5px;
        }
        
        .academic-year-info .text-muted {
            color: #6c757d;
            font-style: italic;
        }
        
        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            .edubot-application-form {
                padding: 20px;
            }
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const boardSelect = document.getElementById('educational_board');
            const boardInfo = document.getElementById('board-info');
            
            if (boardSelect && boardInfo) {
                boardSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    
                    if (selectedOption.value) {
                        const description = selectedOption.getAttribute('data-description');
                        const grades = selectedOption.getAttribute('data-grades');
                        const features = selectedOption.getAttribute('data-features');
                        
                        let infoHtml = '';
                        
                        if (description) {
                            infoHtml += '<div class="board-description"><strong>About:</strong> ' + description + '</div>';
                        }
                        
                        if (grades) {
                            infoHtml += '<div class="board-grades"><strong>Grades Offered:</strong> ' + grades + '</div>';
                        }
                        
                        if (features) {
                            infoHtml += '<div class="board-features"><strong>Key Features:</strong> ' + features + '</div>';
                        }
                        
                        if (infoHtml) {
                            boardInfo.innerHTML = infoHtml;
                            boardInfo.style.display = 'block';
                        } else {
                            boardInfo.style.display = 'none';
                        }
                    } else {
                        boardInfo.style.display = 'none';
                    }
                });
                
                // Trigger change event on page load if there's a default selection
                if (boardSelect.value) {
                    boardSelect.dispatchEvent(new Event('change'));
                }
            }
        });
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    public function handle_chatbot_response() {
        check_ajax_referer('edubot_nonce', 'nonce');
        
        $message = sanitize_text_field($_POST['message'] ?? '');
        $action = sanitize_text_field($_POST['action'] ?? '');
        
        if (empty($message) && empty($action)) {
            wp_die('Invalid request');
        }
        
        $response = $this->generate_response($message, $action);
        
        wp_send_json_success(array(
            'message' => $response,
            'timestamp' => current_time('c')
        ));
    }
    
    private function generate_response($message, $action) {
        $settings = get_option('edubot_pro_settings', array());
        $school_name = $settings['school_name'] ?? 'Our School';
        
        // Handle quick actions
        if (!empty($action)) {
            switch ($action) {
                case 'admission_info':
                    return "Here's information about admissions at {$school_name}:\n\n• Online application process\n• Document verification\n• Entrance test (if applicable)\n• Interview with parents\n• Final selection and fee payment\n\nWould you like more details about any specific step?";
                
                case 'fees_structure':
                    return "Our fee structure includes:\n\n• Admission Fee: Contact for details\n• Tuition Fee: Varies by grade\n• Activity Fee: Included\n• Transport Fee: Optional\n\nFor exact amounts, please call our office or schedule a visit. Would you like our contact information?";
                
                case 'application_form':
                    return "Great! I can help you with the application process.\n\nYou can:\n1. Fill out our online application form\n2. Schedule a school visit\n3. Get a callback from our admissions team\n\nWhat would you prefer?";
                
                case 'contact_info':
                    $contact_info = "Here's how to reach us:\n\n";
                    if (!empty($settings['phone'])) {
                        $contact_info .= "📞 Phone: " . $settings['phone'] . "\n";
                    }
                    if (!empty($settings['email'])) {
                        $contact_info .= "📧 Email: " . $settings['email'] . "\n";
                    }
                    if (!empty($settings['address'])) {
                        $contact_info .= "📍 Address: " . $settings['address'] . "\n";
                    }
                    $contact_info .= "\nOur office hours are Monday to Friday, 9 AM to 5 PM.";
                    return $contact_info;
            }
        }
        
        // Basic keyword responses
        $message_lower = strtolower($message);
        
        if (strpos($message_lower, 'fee') !== false || strpos($message_lower, 'cost') !== false) {
            return "Our fee structure varies by grade level. For detailed information about fees, please contact our office or schedule a visit. Would you like me to help you get in touch with our admissions team?";
        }
        
        if (strpos($message_lower, 'admission') !== false || strpos($message_lower, 'apply') !== false) {
            return "I'd be happy to help with admissions information! Our admission process includes application submission, document verification, and an interaction session. Would you like to start with our online application form?";
        }
        
        if (strpos($message_lower, 'visit') !== false || strpos($message_lower, 'tour') !== false) {
            return "We'd love to give you a tour of our campus! School visits can be scheduled Monday through Friday. Would you like me to help you schedule a visit or get you connected with our admissions team?";
        }
        
        if (strpos($message_lower, 'contact') !== false || strpos($message_lower, 'phone') !== false) {
            return "Here's our contact information:\n\n📞 Phone: " . ($settings['phone'] ?? 'Contact for details') . "\n📧 Email: " . ($settings['email'] ?? 'Contact for details') . "\n\nOur team is available Monday to Friday, 9 AM to 5 PM. How else can I help you?";
        }
        
        // Default response
        return "Thank you for your message! I'm here to help with information about admissions, fees, school visits, and applications. You can also speak with our admissions team directly - would you like me to help you get in touch with them?";
    }
    
    public function handle_application_submission() {
        check_ajax_referer('edubot_application', 'edubot_nonce');
        
        // Get school config for validation
        $school_config = new EduBot_School_Config();
        $board_required = $school_config->is_board_selection_required();
        $available_years = $school_config->get_available_academic_years();
        
        // Validate required fields
        $required_fields = array('student_name', 'date_of_birth', 'grade', 'gender', 'parent_name', 'email', 'phone', 'address');
        
        // Add educational board to required fields if it's mandatory
        if ($board_required) {
            $required_fields[] = 'educational_board';
        }
        
        // Add academic year to required fields if years are configured
        if (!empty($available_years)) {
            $required_fields[] = 'academic_year';
        }
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => 'Please fill in all required fields.'));
            }
        }
        
        // Validate educational board if provided
        $educational_board = sanitize_text_field($_POST['educational_board'] ?? '');
        if (!empty($educational_board) && !$school_config->is_valid_board($educational_board)) {
            wp_send_json_error(array('message' => 'Please select a valid educational board.'));
        }
        
        // Validate academic year if provided
        $academic_year = sanitize_text_field($_POST['academic_year'] ?? '');
        if (!empty($academic_year) && !$school_config->is_valid_academic_year($academic_year)) {
            wp_send_json_error(array('message' => 'Please select a valid academic year.'));
        }
        
        // Sanitize and prepare data
        $student_data = array(
            'student_name' => sanitize_text_field($_POST['student_name']),
            'date_of_birth' => sanitize_text_field($_POST['date_of_birth']),
            'grade' => sanitize_text_field($_POST['grade']),
            'educational_board' => $educational_board,
            'academic_year' => $academic_year,
            'gender' => sanitize_text_field($_POST['gender']),
            'parent_name' => sanitize_text_field($_POST['parent_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'address' => sanitize_textarea_field($_POST['address']),
            'previous_school' => sanitize_text_field($_POST['previous_school'] ?? ''),
            'transfer_reason' => sanitize_text_field($_POST['transfer_reason'] ?? ''),
            'special_requirements' => sanitize_textarea_field($_POST['special_requirements'] ?? ''),
            'marketing_consent' => isset($_POST['marketing_consent']) ? 1 : 0
        );
        
        $application_data = array(
            'application_number' => 'APP-' . date('Y') . '-' . str_pad(wp_rand(1000, 9999), 4, '0', STR_PAD_LEFT),
            'student_data' => json_encode($student_data),
            'conversation_log' => json_encode(array(
                'source' => 'application_form',
                'timestamp' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            )),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        
        // Save to database using the database manager
        $database_manager = new EduBot_Database_Manager();
        $application_id = $database_manager->save_application($application_data);
        
        if ($application_id === false) {
            wp_send_json_error(array('message' => 'Sorry, there was an error submitting your application. Please try again.'));
        }
        
        // Add the application data to student_data for notifications
        $notification_data = array_merge($student_data, array(
            'application_number' => $application_data['application_number'],
            'application_id' => $application_id
        ));
        
        // Send notification emails (if configured)
        $this->send_application_notifications($notification_data);
        
        wp_send_json_success(array(
            'message' => 'Thank you! Your application has been submitted successfully. We will contact you soon.',
            'application_number' => $application_data['application_number']
        ));
    }
    
    private function send_application_notifications($application_data) {
        $settings = get_option('edubot_pro_settings', array());
        
        // Send confirmation email to parent
        if (!empty($application_data['email'])) {
            $subject = 'Application Received - ' . ($settings['school_name'] ?? 'School');
            $message = "Dear " . $application_data['parent_name'] . ",\n\n";
            $message .= "Thank you for submitting an application for " . $application_data['student_name'] . ".\n\n";
            $message .= "Application Number: " . $application_data['application_number'] . "\n";
            $message .= "Grade: " . $application_data['grade'] . "\n\n";
            $message .= "We will review your application and contact you soon.\n\n";
            $message .= "Best regards,\n";
            $message .= $settings['school_name'] ?? 'School Administration';
            
            wp_mail($application_data['email'], $subject, $message);
        }
        
        // Send notification to admin
        if (!empty($settings['admin_email'])) {
            $subject = 'New Application Received - ' . $application_data['application_number'];
            $message = "A new application has been received:\n\n";
            $message .= "Student: " . $application_data['student_name'] . "\n";
            $message .= "Parent: " . $application_data['parent_name'] . "\n";
            $message .= "Grade: " . $application_data['grade'] . "\n";
            $message .= "Email: " . $application_data['email'] . "\n";
            $message .= "Phone: " . $application_data['phone'] . "\n";
            $message .= "Application Number: " . $application_data['application_number'] . "\n\n";
            $message .= "Please review the application in the admin panel.";
            
            wp_mail($settings['admin_email'], $subject, $message);
        }
    }
}

// Initialize the shortcode handler
new EduBot_Shortcode();
