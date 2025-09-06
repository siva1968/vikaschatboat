<?php
/**
 * EduBot Shortcode Generator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure required classes are loaded
if (!class_exists('Edubot_Academic_Config')) {
    require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-academic-config.php';
}

class EduBot_Shortcode {
    
    private $debug_enabled = false; // Set to true for debugging
    
    public function __construct() {
        $this->debug_enabled = defined('WP_DEBUG') && WP_DEBUG;
        add_action('init', array($this, 'init_shortcode'), 15); // Higher priority to override public class
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_ajax_edubot_submit_application', array($this, 'handle_application_submission'));
        add_action('wp_ajax_nopriv_edubot_submit_application', array($this, 'handle_application_submission'));
        add_action('wp_ajax_edubot_chatbot_response', array($this, 'handle_chatbot_response'));
        add_action('wp_ajax_nopriv_edubot_chatbot_response', array($this, 'handle_chatbot_response'));
    }
    
    /**
     * Debug logging helper
     */
    private function debug_log($message) {
        if ($this->debug_enabled) {
            error_log("EduBot Debug: " . $message);
        }
    }
    
    public function init_shortcode() {
        add_shortcode('edubot_chatbot', array($this, 'render_chatbot'));
        add_shortcode('edubot_application_form', array($this, 'render_application_form'));
    }
    
    public function enqueue_frontend_scripts() {
        if (is_admin()) return;
        
        // Don't enqueue separate frontend script as public script already handles chatbot
        // This prevents conflicts between multiple JavaScript implementations
        
        wp_enqueue_style(
            'edubot-frontend',
            EDUBOT_PRO_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            EDUBOT_PRO_VERSION
        );
        
        // Ensure the public script's AJAX object is available for shortcode usage
        if (!wp_script_is('edubot-pro', 'enqueued')) {
            wp_enqueue_script(
                'edubot-pro',
                EDUBOT_PRO_PLUGIN_URL . 'public/js/edubot-public.js',
                array('jquery'),
                EDUBOT_PRO_VERSION . '.' . time(), // Force cache refresh
                false
            );
            
            wp_localize_script(
                'edubot-pro',
                'edubot_ajax',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('edubot_nonce'),
                    'version' => EDUBOT_PRO_VERSION . '.' . time(),
                    'strings' => array(
                        'connecting' => __('Connecting...', 'edubot-pro'),
                        'typing' => __('Bot is typing...', 'edubot-pro'),
                        'error' => __('Sorry, something went wrong. Please try again.', 'edubot-pro'),
                        'send' => __('Send', 'edubot-pro'),
                        'type_message' => __('Type your message...', 'edubot-pro'),
                        'new_application' => __('New Application', 'edubot-pro'),
                        'school_info' => __('School Information', 'edubot-pro'),
                        'contact_info' => __('Contact Information', 'edubot-pro'),
                        'admission' => __('Admission', 'edubot-pro')
                    )
                )
            );
        }
    }
    
    public function render_chatbot($atts) {
        // Set the new welcome message format
        $new_welcome_message = "Hello! Welcome to Epistemo Vikas Leadership School. We are currently accepting applications for AY 2026–27.\n\nHow can I help you today?\n\n1. Admission Enquiry\n2. Curriculum & Classes\n3. Facilities\n4. Contact / Visit School\n5. Online Enquiry Form";
        
        // Get configured welcome message or use new default
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        $configured_welcome = $config['chatbot_settings']['welcome_message'] ?? $new_welcome_message;
        
        $atts = shortcode_atts(array(
            'theme' => 'default',
            'position' => 'bottom-right',
            'button_text' => 'Ask us anything',
            'welcome_message' => $configured_welcome
        ), $atts, 'edubot_chatbot');
        
        $settings = get_option('edubot_pro_settings', array());
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        $school_name = $config['school_info']['name'] ?? $settings['school_name'] ?? 'Epistemo Vikas Leadership School';
        
        // Force your database colors - Updated for your specific colors
        $colors = array(
            'primary' => '#74a211',   // Your green primary color from database
            'secondary' => '#113b02'  // Your dark green secondary color from database
        );
        
        // Fallback to config/options only if not set above
        if (empty($colors['primary'])) {
            $colors['primary'] = isset($config['school_info']['colors']['primary']) ? $config['school_info']['colors']['primary'] : get_option('edubot_primary_color', '#4facfe');
        }
        if (empty($colors['secondary'])) {
            $colors['secondary'] = isset($config['school_info']['colors']['secondary']) ? $config['school_info']['colors']['secondary'] : get_option('edubot_secondary_color', '#00f2fe');
        }
        
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
                        <?php 
                        // Initialize branding manager safely
                        try {
                            if (class_exists('EduBot_Branding_Manager')) {
                                $branding_manager = EduBot_Branding_Manager::getInstance();
                                $logo_html = $branding_manager->get_logo_html('small');
                                if ($logo_html): ?>
                                    <div class="header-logo"><?php echo $logo_html; ?></div>
                                <?php endif;
                            }
                        } catch (Exception $e) {
                            error_log('EduBot Branding Error: ' . $e->getMessage());
                        }
                        ?>
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
                                <button class="quick-action" data-action="admission">1. Admission Enquiry</button>
                                <button class="quick-action" data-action="curriculum">2. Curriculum & Classes</button>
                                <button class="quick-action" data-action="facilities">3. Facilities</button>
                                <button class="quick-action" data-action="contact_visit">4. Contact / Visit School</button>
                                <button class="quick-action" data-action="online_enquiry">5. Online Enquiry Form</button>
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
            background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%);
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
            background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%);
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-logo {
            display: flex;
            align-items: center;
        }
        
        .header-logo img {
            max-height: 30px;
            max-width: 40px;
            object-fit: contain;
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
            background: <?php echo esc_attr($colors['primary']); ?>;
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
            background: <?php echo esc_attr($colors['primary']); ?>;
            color: white;
            border-color: <?php echo esc_attr($colors['primary']); ?>;
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
        .edubot-chatbot .quick-action,
        .quick-action {
            background: <?php echo esc_attr($colors['primary']); ?> !important;
            border: 1px solid <?php echo esc_attr($colors['primary']); ?> !important;
            border-radius: 6px !important;
            padding: 10px 15px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            text-align: left !important;
            color: white !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
        }
        .edubot-chatbot .quick-action:hover,
        .quick-action:hover {
            background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15) !important;
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
            border-color: <?php echo esc_attr($colors['primary']); ?>;
        }
        #send-message {
            background: <?php echo esc_attr($colors['primary']); ?>;
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
            background: <?php echo esc_attr($colors['primary']); ?>;
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
        
        <script>
        jQuery(document).ready(function($) {
            // Generate unique session ID for this chatbot instance
            var sessionId = 'chat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            console.log('EduBot Shortcode: Generated session ID:', sessionId);
            
            // Wait for EduBotChatWidget to be available
            function initializeEduBot() {
                if (typeof window.EduBotChatWidget !== 'undefined' && $('#edubot-chatbot').length > 0) {
                    console.log('EduBot Shortcode: Initializing with EduBotChatWidget');
                    
                    // Initialize the chat widget with session management
                    window.EduBotChatWidget.init(sessionId);
                    
                    // Setup shortcode-specific event handlers for the embedded chatbot
                    $('#edubot-chatbot .edubot-chat-button').off('click').on('click', function() {
                        var chatWindow = $('#edubot-chatbot .edubot-chat-window');
                        if (chatWindow.is(':visible')) {
                            chatWindow.hide();
                        } else {
                            chatWindow.show();
                            setTimeout(function() {
                                $('#edubot-chatbot #chat-input').focus();
                            }, 100);
                        }
                    });
                    
                    // Close button handler
                    $('#edubot-chatbot .close-chat').off('click').on('click', function() {
                        $('#edubot-chatbot .edubot-chat-window').hide();
                    });
                    
                    // Send message handler
                    $('#edubot-chatbot #send-message').off('click').on('click', function() {
                        var input = $('#edubot-chatbot #chat-input');
                        var message = input.val().trim();
                        if (message && typeof window.EduBotChatWidget.sendMessage === 'function') {
                            // Use the session-aware sendMessage from EduBotChatWidget
                            window.EduBotChatWidget.sendMessage(message);
                            input.val('').focus();
                        }
                    });
                    
                    // Enter key handler
                    $('#edubot-chatbot #chat-input').off('keypress').on('keypress', function(e) {
                        if (e.which === 13) {
                            $('#edubot-chatbot #send-message').click();
                        }
                    });
                    
                    // Quick action button handlers
                    $('#edubot-chatbot').off('click', '.quick-action').on('click', '.quick-action', function() {
                        var action = $(this).data('action');
                        var message = $(this).text();
                        if (action && typeof window.EduBotChatWidget.sendMessage === 'function') {
                            // Send with action type for proper routing
                            window.EduBotChatWidget.sendMessage(message, action);
                        }
                    });
                    
                    console.log('EduBot Shortcode: Event handlers bound successfully');
                    
                } else if (typeof window.edubot_ajax !== 'undefined') {
                    // Fallback: Use basic AJAX if EduBotChatWidget is not available
                    console.warn('EduBot Shortcode: EduBotChatWidget not found, using fallback');
                    setupFallbackChatbot(sessionId);
                } else {
                    console.error('EduBot Shortcode: Neither EduBotChatWidget nor edubot_ajax found');
                }
            }
            
            // Setup fallback chatbot functionality
            function setupFallbackChatbot(sessionId) {
                $('#edubot-chatbot .edubot-chat-button').on('click', function() {
                    var chatWindow = $('#edubot-chatbot .edubot-chat-window');
                    chatWindow.toggle();
                    if (chatWindow.is(':visible')) {
                        $('#edubot-chatbot #chat-input').focus();
                    }
                });
                
                $('#edubot-chatbot .close-chat').on('click', function() {
                    $('#edubot-chatbot .edubot-chat-window').hide();
                });
                
                $('#edubot-chatbot #send-message').on('click', function() {
                    sendFallbackMessage(sessionId);
                });
                
                $('#edubot-chatbot #chat-input').on('keypress', function(e) {
                    if (e.which === 13) {
                        sendFallbackMessage(sessionId);
                    }
                });
                
                $('#edubot-chatbot .quick-action').on('click', function() {
                    var action = $(this).data('action');
                    var message = $(this).text();
                    sendFallbackMessage(sessionId, message, action);
                });
            }
            
            // Fallback message sending
            function sendFallbackMessage(sessionId, message, action) {
                var input = $('#edubot-chatbot #chat-input');
                var messageText = message || input.val().trim();
                
                if (!messageText) return;
                
                // Add user message to chat
                addMessageToChat(messageText, 'user');
                input.val('');
                
                // Send via AJAX
                $.ajax({
                    url: window.edubot_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'edubot_chatbot_response',
                        message: messageText,
                        action_type: action || '',
                        session_id: sessionId,
                        nonce: window.edubot_ajax.nonce
                    },
                    beforeSend: function() {
                        showTypingIndicator();
                    },
                    success: function(response) {
                        hideTypingIndicator();
                        if (response.success) {
                            addMessageToChat(response.data.message, 'bot');
                            // Update session ID if provided
                            if (response.data.session_id) {
                                sessionId = response.data.session_id;
                            }
                        } else {
                            addMessageToChat('Sorry, I encountered an error. Please try again.', 'bot');
                        }
                    },
                    error: function() {
                        hideTypingIndicator();
                        addMessageToChat('Connection error. Please check your internet and try again.', 'bot');
                    }
                });
            }
            
            // Helper functions for fallback
            function addMessageToChat(message, type) {
                var messages = $('#edubot-chatbot .chat-messages');
                var messageHtml = '<div class="message ' + (type === 'user' ? 'user-message' : 'bot-message') + '">' +
                    '<div class="message-avatar">' + (type === 'user' ? '👤' : '🤖') + '</div>' +
                    '<div class="message-content"><p>' + $('<div>').text(message).html() + '</p></div>' +
                    '</div>';
                messages.append(messageHtml);
                messages.scrollTop(messages[0].scrollHeight);
            }
            
            function showTypingIndicator() {
                $('#edubot-chatbot .typing-indicator').show();
            }
            
            function hideTypingIndicator() {
                $('#edubot-chatbot .typing-indicator').hide();
            }
            
            // Initialize after a short delay to ensure scripts are loaded
            setTimeout(initializeEduBot, 100);
        });
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    public function render_application_form($atts) {
        $atts = shortcode_atts(array(
            'style' => 'inline',
            'title' => 'Application Form'
        ), $atts, 'edubot_application_form');
        
        // Get school config for boards and academic years
        $school_config = EduBot_School_Config::getInstance();
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
                            <?php
                            $all_grades = Edubot_Academic_Config::get_all_configured_grades();
                            foreach ($all_grades as $grade_key => $grade_name): ?>
                                <option value="<?php echo esc_attr($grade_key); ?>">
                                    <?php echo esc_html($grade_name); ?>
                                </option>
                            <?php endforeach; ?>
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
                
                <!-- Previous school fields removed for streamlined admission process -->
                
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
            border-color: <?php echo esc_attr($colors['primary']); ?>;
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
            background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%);
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
    
    /**
     * Handle chatbot response with enhanced security
     */
    public function handle_chatbot_response() {
        // Enhanced nonce verification
        if (!check_ajax_referer('edubot_nonce', 'nonce', false)) {
            $this->log_security_violation('invalid_nonce_chatbot', array(
                'ip' => $this->get_client_ip(),
                'user_agent' => $this->get_user_agent()
            ));
            wp_send_json_error(array('message' => 'Security verification failed. Please refresh the page.'));
        }

        // Rate limiting - more lenient for development/testing
        $security_manager = new EduBot_Security_Manager();
        $client_ip = $this->get_client_ip();
        
        // Temporarily disable rate limiting for development
        // if (!$security_manager->check_rate_limit('chatbot_response_' . md5($client_ip), 20, 900)) {
        //     $security_manager->log_security_event('rate_limit_exceeded_chatbot', array(
        //         'ip' => $client_ip,
        //         'endpoint' => 'chatbot_response'
        //     ));
        //     wp_send_json_error(array('message' => 'Too many requests. Please wait a moment before trying again.'));
        // }

        // Input validation and sanitization
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        
        // Validate message length
        if (!empty($message) && strlen($message) > 500) {
            wp_send_json_error(array('message' => 'Message too long. Please keep it under 500 characters.'));
        }

        // Security content filtering - temporarily disabled for testing
        // if (!empty($message) && $security_manager->is_malicious_content($message)) {
        //     $security_manager->log_security_event('malicious_content_chatbot_shortcode', array(
        //         'message' => substr($message, 0, 100),
        //         'ip' => $client_ip
        //     ));
        //     wp_send_json_error(array('message' => 'Your message contains invalid content. Please rephrase your question.'));
        // }

        // Validate action parameter
        $allowed_actions = array('admission', 'curriculum', 'facilities', 'contact_visit', 'online_enquiry');
        if (!empty($action_type) && !in_array($action_type, $allowed_actions)) {
            wp_send_json_error(array('message' => 'Invalid action specified.'));
        }
        
        if (empty($message) && empty($action_type)) {
            wp_send_json_error(array('message' => 'Please provide a message or select an action.'));
        }
        
        try {
            $response = $this->generate_response($message, $action_type, $session_id);
            
            wp_send_json_success(array(
                'message' => esc_html($response),
                'session_id' => $session_id,
                'timestamp' => current_time('c')
            ));
        } catch (Exception $e) {
            error_log('EduBot Shortcode: Error generating response - ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Sorry, there was an error processing your request. Please try again.'));
        }
    }
    
    private function generate_response($message, $action_type, $session_id = '') {
        try {
            error_log('EduBot Debug: Starting generate_response with message: ' . substr($message, 0, 50) . ' and action: ' . $action_type);
            
            // Initialize session data if not exists
            if (empty($session_id)) {
                $session_id = 'sess_' . uniqid();
            }
            
            // Handle quick actions FIRST (even for completed sessions - user wants to start new flow)
            if (!empty($action_type)) {
                error_log('EduBot Debug: Processing quick action: ' . $action_type);
                
                // If user clicks quick action after completing enquiry, create fresh session
                if (!empty($session_id) && $this->is_session_completed($session_id)) {
                    $session_id = 'sess_' . uniqid();  // Create fresh session
                    error_log('EduBot Debug: Created fresh session for new quick action: ' . $session_id);
                }
                
                $settings = get_option('edubot_pro_settings', array());
                $school_config = EduBot_School_Config::getInstance();
                $config = $school_config->get_config();
                $school_name = $config['school_info']['name'] ?? $settings['school_name'] ?? 'Epistemo Vikas Leadership School';
            
            switch ($action_type) {
                case 'admission':
                    // Initialize session for admission flow
                    $this->init_conversation_session($session_id, 'admission');
                    // Use direct intelligent fallback instead of complex engine
                    return $this->provide_intelligent_fallback('admission', 'admission', $session_id);
                

                case 'curriculum':
                    return "📚 **Academic Programs & Curriculum at {$school_name}**\n\n" .
                           "🎯 **Our Academic Approach:**\n" .
                           "• Student-centered learning methodology\n" .
                           "• Integrated curriculum design\n" .
                           "• Critical thinking and problem-solving focus\n" .
                           "• Technology-enhanced education\n\n" .
                           "📖 **Curriculum Boards:**\n" .
                           "• CBSE (Central Board of Secondary Education)\n" .
                           "• CAIE (Cambridge Assessment International Education)\n\n" .
                           "🏫 **Grade Levels:**\n" .
                           "• Early Childhood: Nursery, PP1, PP2\n" .
                           "• Primary School: Grades 1-5\n" .
                           "• Middle School: Grades 6-8\n" .
                           "• High School: Grades 9-12\n\n" .
                           "🌟 **Special Programs:**\n" .
                           "• STEAM education\n" .
                           "• Language immersion programs\n" .
                           "• Leadership development\n" .
                           "• Arts and creative expression\n\n" .
                           "Which grade level or subject area interests you most?\n\n" .
                           "Ready to **start your admission enquiry**? Just type '**admission**' or click the admission button!";
                
                case 'facilities':
                    return "🏢 **World-Class Facilities at {$school_name}**\n\n" .
                           "🎯 **Academic Facilities:**\n" .
                           "• Modern, well-equipped classrooms\n" .
                           "• Advanced science laboratories\n" .
                           "• Computer and robotics labs\n" .
                           "• Comprehensive library and media center\n\n" .
                           "🏃 **Sports & Recreation:**\n" .
                           "• Multi-purpose sports complex\n" .
                           "• Swimming pool\n" .
                           "• Indoor and outdoor courts\n" .
                           "• Fitness and wellness center\n\n" .
                           "🎨 **Creative Spaces:**\n" .
                           "• Art and design studios\n" .
                           "• Music and performance halls\n" .
                           "• Drama and theater facilities\n" .
                           "• Maker spaces and innovation labs\n\n" .
                           "🚌 **Support Services:**\n" .
                           "• Safe transportation network\n" .
                           "• Nutritious cafeteria meals\n" .
                           "• Health and medical support\n" .
                           "• 24/7 security systems\n\n" .
                           "Would you like to schedule a campus tour to see these facilities?";
                
                case 'contact_visit':
                    return "🏫 **Contact / Visit {$school_name}**\n\n" .
                           "You can reach us in the following ways:\n\n" .
                           "📞 **Call Admission Office**\n" .
                           "• 7702800800 / 9248111448\n\n" .
                           "📧 **Email Us**\n" .
                           "• admissions@epistemo.in\n\n" .
                           "🏫 **Book a Campus Tour**\n" .
                           "• Schedule a personalized campus visit\n\n" .
                           "📞 **Request a Callback**\n" .
                           "• We'll call you at your convenience\n\n" .
                           "**Please select an option:**\n" .
                           "• Type '**Call**' for immediate phone contact\n" .
                           "• Type '**Email**' for email communication\n" .
                           "• Type '**Tour**' to book a campus tour\n" .
                           "• Type '**Callback**' to request a callback\n\n" .
                           "How would you like to connect with us?";
                           "� **Campus Tour Experience:**\n" .
                           "• Guided tour of all facilities\n" .
                           "• Meet our experienced faculty\n" .
                           "• Interact with current students\n" .
                           "• Q&A session with admissions team\n" .
                           "• Sample our academic programs\n\n" .
                           "⏰ **Visit Schedule:**\n" .
                           "• Monday to Friday: 9:00 AM - 5:00 PM\n" .
                           "• Saturday: 9:00 AM - 1:00 PM\n" .
                           "• Tours available by appointment\n\n" .
                           "� **Contact Information:**\n" .
                           "• Phone: Call for immediate assistance\n" .
                           "• Email: Send us your preferred dates\n" .
                           "• Online: Fill our visit request form\n\n" .
                           "🗺️ **Location:**\n" .
                           "• Easy access via major routes\n" .
                           "• Ample parking available\n" .
                           "• Public transport connectivity\n\n" .
                           "Ready to schedule your visit? I can help you book an appointment!";
                

                case 'online_enquiry':
                    return "🌐 **Online Enquiry Form**\n\n" .
                           "For your convenience, you can fill out our detailed online enquiry form:\n\n" .
                           "🔗 **Direct Link:** https://epistemo.in/enquiry/\n\n" .
                           "📋 **What you can do on the form:**\n" .
                           "• Provide detailed student information\n" .
                           "• Select preferred curriculum and grade\n" .
                           "• Specify your requirements and preferences\n" .
                           "• Upload necessary documents\n" .
                           "• Schedule a campus visit\n\n" .
                           "✅ **Benefits:**\n" .
                           "• Save time with pre-filled information\n" .
                           "• Upload documents directly\n" .
                           "• Get faster response from our team\n" .
                           "• Track your application status\n\n" .
                           "🚀 **Click the link above to get started!**\n\n" .
                           "If you prefer, I can also help you with the admission process right here in the chat. Just let me know!";
            }
        }
        
        // Check if this is a completed session and handle post-submission edits (only if no quick action)
        if (!empty($session_id) && $this->is_session_completed($session_id)) {
            error_log('EduBot Debug: Handling post-submission request for session: ' . $session_id);
            return $this->handle_post_submission_edit($message, $session_id);
        }
        
        // Use hybrid approach: Rule-based for structured data + OpenAI for natural conversation
        try {
            // Check if this is structured admission data that should use rule-based system
            if ($this->is_structured_admission_data($message, $session_id)) {
                error_log('EduBot: Using rule-based system for structured admission data');
                return $this->provide_intelligent_fallback($message, $action_type, $session_id);
            }
            
            // Use OpenAI for natural language queries and complex questions
            error_log('EduBot: Using OpenAI for natural language processing');
            return $this->get_ai_enhanced_response($message, $session_id, $action_type);
            
        } catch (Exception $e) {
            error_log('EduBot Shortcode: Error in hybrid system - ' . $e->getMessage());
            error_log('EduBot Debug: Stack trace: ' . $e->getTraceAsString());
            
            // Always fallback to rule-based system for reliability
            return $this->provide_intelligent_fallback($message, $action_type, $session_id);
        }
        
        } catch (Exception $e) {
            error_log('EduBot Error in generate_response: ' . $e->getMessage());
            error_log('EduBot Error trace: ' . $e->getTraceAsString());
            return "I apologize, but I'm experiencing some technical difficulties. Please try again or contact our admissions team directly.";
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
     * Save conversation session data
     */
    private function save_conversation_session($session_id, $session_data) {
        $sessions = get_option('edubot_conversation_sessions', array());
        $sessions[$session_id] = $session_data;
        
        $this->debug_log("Saving session {$session_id} with data: " . print_r($session_data, true));
        
        // Clean old sessions (older than 24 hours)
        $cutoff_time = strtotime('-24 hours');
        foreach ($sessions as $sid => $data) {
            if (isset($data['started']) && strtotime($data['started']) < $cutoff_time) {
                unset($sessions[$sid]);
            }
        }
        
        $result = update_option('edubot_conversation_sessions', $sessions);
        $this->debug_log("WordPress update_option result: " . ($result ? 'SUCCESS' : 'FAILED'));
        
        // Verify immediately
        $verify = get_option('edubot_conversation_sessions', array());
        if (isset($verify[$session_id])) {
            $this->debug_log("Immediate verification SUCCESS for session {$session_id}");
        } else {
            $this->debug_log("Immediate verification FAILED for session {$session_id}");
        }
    }
    
    /**
     * Get conversation session data
     */
    private function get_conversation_session($session_id) {
        $sessions = get_option('edubot_conversation_sessions', array());
        $result = isset($sessions[$session_id]) ? $sessions[$session_id] : null;
        
        if ($result) {
            error_log("EduBot Debug: Retrieved session {$session_id}: " . print_r($result, true));
        } else {
            error_log("EduBot Debug: No session found for {$session_id}. Available sessions: " . print_r(array_keys($sessions), true));
        }
        
        return $result;
    }
    
    /**
     * Update conversation session data
     */
    private function update_conversation_data($session_id, $key, $value) {
        $session_data = $this->get_conversation_session($session_id);
        
        // If no session exists, create one
        if (!$session_data) {
            error_log("EduBot Debug: No session found for {$session_id}, initializing new session");
            $session_data = $this->init_conversation_session($session_id, 'admission');
        }
        
        // Ensure data array exists
        if (!isset($session_data['data'])) {
            $session_data['data'] = array();
        }
        
        // Update the data
        $session_data['data'][$key] = $value;
        $session_data['updated'] = current_time('mysql');
        
        error_log("EduBot Debug: Storing {$key} = {$value} in session {$session_id}");
        $this->save_conversation_session($session_id, $session_data);
        
        // Verify the data was saved
        $verify_session = $this->get_conversation_session($session_id);
        if ($verify_session && isset($verify_session['data'][$key])) {
            error_log("EduBot Debug: Successfully verified {$key} in session {$session_id}");
        } else {
            error_log("EduBot Debug: FAILED to verify {$key} in session {$session_id}");
        }
    }
    
    /**
     * Parse multi-field input to extract name, email, phone
     */
    private function parse_personal_info($message) {
        $info = array();
        $message_clean = trim($message);
        $original_message = $message_clean;
        
        // Try to extract email first
        if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $message_clean, $email_matches)) {
            $info['email'] = $email_matches[0];
            $message_clean = str_replace($email_matches[0], '', $message_clean);
        }
        
        // Try to extract phone number
        if (preg_match('/(\+?91|0)?[\s-]?[6-9]\d{9}/', $message_clean, $phone_matches) || 
            preg_match('/\b\d{10}\b/', $message_clean, $phone_matches) ||
            preg_match('/\+\d{1,3}[\s-]?\d{6,14}/', $message_clean, $phone_matches)) {
            $info['phone'] = preg_replace('/[^\d+]/', '', $phone_matches[0]);
            $message_clean = preg_replace('/(\+?91|0)?[\s-]?[6-9]\d{9}/', '', $message_clean);
            $message_clean = preg_replace('/\b\d{10}\b/', '', $message_clean);
            $message_clean = preg_replace('/\+\d{1,3}[\s-]?\d{6,14}/', '', $message_clean);
        }
        
        // Clean up the message for name extraction
        $message_clean = preg_replace('/\s*(name\s*:?\s*|email\s*:?\s*|phone\s*:?\s*|mobile\s*:?\s*)/i', ' ', $message_clean);
        $message_clean = preg_replace('/[^\w\s\.]/', ' ', $message_clean);
        $message_clean = preg_replace('/\s+/', ' ', $message_clean);
        $message_clean = trim($message_clean);
        
        // Extract name - if the cleaned message is just a name (no email/phone extracted)
        if (!empty($message_clean) && 
            strlen($message_clean) >= 2 && 
            strlen($message_clean) <= 50 &&
            preg_match('/^[a-zA-Z\s\.]+$/', $message_clean) &&
            !preg_match('/\b(admission|application|school|grade|class|year|board|cbse|icse|cambridge)\b/i', $message_clean)) {
            $info['name'] = ucwords(strtolower(trim($message_clean)));
        }
        
        return $info;
    }
    
    /**
     * Parse academic info (grade and board)
     */
    private function parse_academic_info($message) {
        $info = array();
        $message_lower = strtolower($message);
        
        // Extract grade (handle typos like "grde")
        if (preg_match('/\b(nursery|pp1|pp2|pre-?kg|lkg|ukg|gr[ae]de?\s*\d+|class\s*\d+|\d+th|\d+st|\d+nd|\d+rd)\b/i', $message_lower)) {
            $info['grade'] = $this->extract_grade_from_message($message);
        }
        
        // Extract board
        if (preg_match('/\b(cbse|caie|cambridge|state\s*board|icse|igcse|international|ib|bse\s*telangana)\b/i', $message_lower)) {
            $info['board'] = $this->extract_board_from_message($message);
        }
        
        // Extract academic year
        if (preg_match('/\b(20\d{2}[-\/]20?\d{2}|20\d{2})\b/', $message, $year_matches)) {
            $info['academic_year'] = $year_matches[0];
        }
        
        return $info;
    }
    
    /**
     * Parse additional info (date of birth only in dd/mm/yyyy format)
     */
    private function parse_additional_info($message) {
        $info = array();
        $message_lower = strtolower($message);
        
        // Extract date of birth and calculate age
        // Accept only dd/mm/yyyy format (4-digit year required)
        if (preg_match('/\b(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})\b/', $message, $dob_matches)) {
            $day = str_pad($dob_matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($dob_matches[2], 2, '0', STR_PAD_LEFT);
            $year = $dob_matches[3];
            
            // Validate date ranges
            if ((int)$day > 31 || (int)$day < 1 || (int)$month > 12 || (int)$month < 1) {
                $info['error'] = "Invalid date format. Please enter a valid date in dd/mm/yyyy format (e.g., 16/10/2010).";
                return $info;
            }
            
            $dob = $year . '-' . $month . '-' . $day;
            
            // Validate the date is real
            try {
                $birth_date = new DateTime($dob);
                $current_date = new DateTime();
                $age = $current_date->diff($birth_date)->y;
                
                if ($age < 2 || $age > 18) {
                    $info['error'] = "Age must be between 2 and 18 years. Please check the date of birth.";
                    return $info;
                }
                
                $info['date_of_birth'] = $dob;
                $info['age'] = $age;
            } catch (Exception $e) {
                $info['error'] = "Invalid date format. Please enter a valid date in dd/mm/yyyy format (e.g., 16/10/2010).";
                return $info;
            }
        }
        // Check for invalid formats and provide error message
        elseif (preg_match('/\b(\d{1,2})[-\/](\d{1,2})[-\/](\d{2})\b/', $message)) {
            $info['error'] = "Please use 4-digit year format (dd/mm/yyyy). Example: 16/10/2010";
            return $info;
        }
        elseif (preg_match('/\b(\d{1,2})\s*(years?|yrs?|year\s*old)\b/i', $message) || 
                preg_match('/^\s*(\d{1,2})\s*$/', $message)) {
            $info['error'] = "Please enter the date of birth in dd/mm/yyyy format instead of age. Example: 16/10/2010";
            return $info;
        }

        return $info;
    }

    /**
     * Parse optional parent information
     */
    private function parse_parent_info($message) {
        $info = array();
        $message_lower = strtolower($message);
        
        // Extract Father's name - handle multiple formats
        if (preg_match('/(?:father\s*:?\s*|father\'?s?\s+name\s*:?\s*|father\s*-\s*)([a-zA-Z\s]+?)(?:[,\n]|mother|email|phone|$)/i', $message, $father_matches)) {
            $father_name = trim($father_matches[1]);
            // Clean up common words that might be captured
            $father_name = preg_replace('/\b(name|is|the)\b/i', '', $father_name);
            $father_name = trim($father_name);
            if (strlen($father_name) > 1 && strlen($father_name) < 50) {
                $info['father_name'] = ucwords(strtolower($father_name));
            }
        }
        
        // Extract Mother's name - handle multiple formats
        if (preg_match('/(?:mother\s*:?\s*|mother\'?s?\s+name\s*:?\s*|mother\s*-\s*)([a-zA-Z\s]+?)(?:[,\n]|email|phone|father|$)/i', $message, $mother_matches)) {
            $mother_name = trim($mother_matches[1]);
            // Clean up common words that might be captured
            $mother_name = preg_replace('/\b(name|is|the)\b/i', '', $mother_name);
            $mother_name = trim($mother_name);
            if (strlen($mother_name) > 1 && strlen($mother_name) < 50) {
                $info['mother_name'] = ucwords(strtolower($mother_name));
            }
        }
        
        // Extract Mother's email (separate from main email) - more flexible pattern
        if (preg_match('/(?:mother\'?s?\s+email\s*:?\s*|mother\s+email\s*:?\s*|email\s*:?\s*)([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $message, $email_matches)) {
            $mother_email = trim(strtolower($email_matches[1]));
            if (is_email($mother_email)) {
                $info['mother_email'] = $mother_email;
            }
        }
        
        // Extract Mother's phone (separate from main phone) - more flexible pattern
        if (preg_match('/(?:mother\'?s?\s+phone\s*:?\s*|mother\s+phone\s*:?\s*|mother\'?s?\s+number\s*:?\s*|phone\s*:?\s*)([0-9\+\-\s\(\)]{8,15})/i', $message, $phone_matches)) {
            $mother_phone = preg_replace('/[^\d\+]/', '', $phone_matches[1]);
            if (strlen($mother_phone) >= 8 && strlen($mother_phone) <= 15) {
                $info['mother_phone'] = $mother_phone;
            }
        }
        
        return $info;
    }




    
    /**
     * Process final submission - save to database and send confirmation
     */
    private function process_final_submission($collected_data, $session_id) {
        try {
            // Get school configuration
            $school_config = EduBot_School_Config::getInstance();
            $config = $school_config->get_config();
            $school_name = isset($config['school_info']['name']) ? $config['school_info']['name'] : 'Epistemo Vikas Leadership School';
            
            // Generate unique enquiry number
            $enquiry_number = $this->generate_enquiry_number();
            
            // Prepare application data for database
            $application_data = array(
                'application_number' => $enquiry_number,
                'student_data' => array(
                    'student_name' => isset($collected_data['student_name']) ? $collected_data['student_name'] : '',
                    'grade' => isset($collected_data['grade']) ? $collected_data['grade'] : '',
                    'board' => isset($collected_data['board']) ? $collected_data['board'] : '',
                    'academic_year' => isset($collected_data['academic_year']) ? $collected_data['academic_year'] : '',
                    'date_of_birth' => isset($collected_data['date_of_birth']) ? $collected_data['date_of_birth'] : '',
                    'gender' => isset($collected_data['gender']) ? $collected_data['gender'] : '',
                    'parent_name' => isset($collected_data['parent_name']) ? $collected_data['parent_name'] : (isset($collected_data['father_name']) ? $collected_data['father_name'] : 'Parent'),
                    'email' => isset($collected_data['email']) ? $collected_data['email'] : '',
                    'phone' => isset($collected_data['phone']) ? $collected_data['phone'] : '',
                    'address' => isset($collected_data['address']) ? $collected_data['address'] : '',
                    'father_name' => isset($collected_data['father_name']) ? $collected_data['father_name'] : '',
                    'mother_name' => isset($collected_data['mother_name']) ? $collected_data['mother_name'] : '',
                    'mother_email' => isset($collected_data['mother_email']) ? $collected_data['mother_email'] : '',
                    'mother_phone' => isset($collected_data['mother_phone']) ? $collected_data['mother_phone'] : '',
                ),
                'conversation_log' => array(
                    array(
                        'timestamp' => current_time('mysql'),
                        'type' => 'enquiry_completion',
                        'data' => $collected_data
                    )
                ),
                'status' => 'enquiry_submitted',
                'source' => 'chatbot_enquiry'
            );
            
            // Save to database
            $database_manager = new EduBot_Database_Manager();
            $application_id = $database_manager->save_application($application_data);
            
            if (is_wp_error($application_id)) {
                error_log('EduBot Enquiry Error: ' . $application_id->get_error_message());
                return "Sorry, there was an error submitting your enquiry. Please try again or contact us directly.";
            }
            
            // Send confirmation email
            $this->send_enquiry_confirmation_email($collected_data, $enquiry_number, $school_name);
            
            // Mark session as completed instead of clearing it
            $this->mark_session_completed($session_id, $application_id, $enquiry_number);
            
            // Return success message with enquiry details
            return $this->format_enquiry_confirmation($collected_data, $enquiry_number, $school_name);
            
        } catch (Exception $e) {
            error_log('EduBot Enquiry Submission Error: ' . $e->getMessage());
            return "Sorry, there was a technical error submitting your enquiry. Please try again or contact us directly.";
        }
    }
    
    /**
     * Generate unique enquiry number
     */
    private function generate_enquiry_number() {
        $prefix = 'ENQ' . date('Y');
        $suffix = strtoupper(substr(uniqid(), -8));
        return $prefix . $suffix;
    }
    
    /**
     * Send enquiry confirmation email
     */
    private function send_enquiry_confirmation_email($collected_data, $enquiry_number, $school_name) {
        $to = $collected_data['email'] ?? '';
        if (empty($to)) return;
        
        $subject = "Admission Enquiry Confirmation - {$school_name}";
        
        // Build email content
        $message = $this->build_enquiry_email_content($collected_data, $enquiry_number, $school_name);
        
        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $subject, $message, $headers);
        
        // Also send to admin if configured
        $settings = get_option('edubot_pro_settings', array());
        if (!empty($settings['admin_email'])) {
            $admin_subject = "New Admission Enquiry - {$school_name}";
            wp_mail($settings['admin_email'], $admin_subject, $message, $headers);
        }
    }
    
    /**
     * Build enquiry confirmation email content
     */
    private function build_enquiry_email_content($collected_data, $enquiry_number, $school_name) {
        $config = EduBot_School_Config::getInstance()->get_config();
        $contact_info = $config['school_info']['contact_info'] ?? array();
        
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Admission Enquiry Confirmation</title></head><body>';
        $html .= '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd;">';
        
        // Header
        $html .= '<h2 style="color: #2c5282; text-align: center;">Admission Enquiry Confirmation</h2>';
        $html .= '<p>Dear Parent/Guardian,</p>';
        $html .= '<p>Thank you for your interest in <strong>' . esc_html($school_name) . '</strong>.</p>';
        $html .= '<p>Your admission enquiry has been successfully submitted with the following details:</p>';
        
        // Enquiry details table
        $html .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
        $html .= '<tr style="background-color: #f8f9fa;"><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Enquiry Number</td><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($enquiry_number) . '</td></tr>';
        $html .= '<tr><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Student Name</td><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($collected_data['student_name'] ?? '') . '</td></tr>';
        $html .= '<tr style="background-color: #f8f9fa;"><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Grade</td><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($collected_data['grade'] ?? '') . '</td></tr>';
        $html .= '<tr><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Board</td><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($collected_data['board'] ?? '') . '</td></tr>';
        $html .= '<tr style="background-color: #f8f9fa;"><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Academic Year</td><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($collected_data['academic_year'] ?? '') . '</td></tr>';
        
        // Age removed - only using date of birth
        
        if (!empty($collected_data['date_of_birth'])) {
            $html .= '<tr style="background-color: #f8f9fa;"><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Date of Birth</td><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($collected_data['date_of_birth']) . '</td></tr>';
        }
        
        $html .= '<tr><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Phone</td><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($collected_data['phone'] ?? '') . '</td></tr>';
        $html .= '<tr style="background-color: #f8f9fa;"><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Address</td><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($collected_data['address'] ?? '') . '</td></tr>';
        $html .= '<tr><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Date Submitted</td><td style="padding: 10px; border: 1px solid #ddd;">' . date('F j, Y g:i A') . '</td></tr>';
        $html .= '</table>';
        
        // Next steps
        $html .= '<h3 style="color: #2c5282;">Next Steps</h3>';
        $html .= '<p>Our admission team will contact you within 24 hours to schedule a campus visit and guide you through the next steps.</p>';
        $html .= '<p>Please save this enquiry number for your records: <strong>' . esc_html($enquiry_number) . '</strong></p>';
        
        // Contact information
        $html .= '<h3 style="color: #2c5282;">Contact Information</h3>';
        if (!empty($contact_info['phone'])) {
            $html .= '<p>Phone: ' . esc_html($contact_info['phone']) . '</p>';
        }
        if (!empty($contact_info['email'])) {
            $html .= '<p>Email: ' . esc_html($contact_info['email']) . '</p>';
        }
        
        $html .= '<p>Best regards,<br>' . esc_html($school_name) . ' Admissions Team</p>';
        $html .= '</div></body></html>';
        
        return $html;
    }
    
    /**
     * Format enquiry confirmation message for chatbot
     */
    private function format_enquiry_confirmation($collected_data, $enquiry_number, $school_name) {
        $response = "🎉 **Admission Enquiry Successfully Submitted!** 🎉\n\n";
        $response .= "✅ Your enquiry has been recorded with:\n";
        $response .= "📋 **Enquiry Number:** {$enquiry_number}\n";
        $response .= "🏫 **School:** {$school_name}\n";
        $response .= "👶 **Student:** {$collected_data['student_name']}\n";
        $response .= "🎓 **Grade:** {$collected_data['grade']}\n";
        $response .= "📚 **Board:** {$collected_data['board']}\n\n";
        
        $response .= "📧 **Confirmation email sent to:** {$collected_data['email']}\n\n";
        
        $response .= "🔄 **Next Steps:**\n";
        $response .= "• Our admission team will contact you within 24 hours\n";
        $response .= "• Schedule a campus visit\n";
        $response .= "• Guide you through the admission process\n\n";
        
        $response .= "📞 For immediate assistance, please contact us directly.\n\n";
        $response .= "🌐 **Visit our website:** www.epistemo.in\n\n";
        $response .= "Thank you for choosing {$school_name}! 🌟";
        
        return $response;
    }
    
    /**
     * Clear conversation session data
     */
    private function clear_conversation_session($session_id) {
        $sessions = get_option('edubot_conversation_sessions', array());
        if (isset($sessions[$session_id])) {
            unset($sessions[$session_id]);
            update_option('edubot_conversation_sessions', $sessions);
        }
    }
    
    /**
     * Mark session as completed instead of clearing it
     */
    private function mark_session_completed($session_id, $application_id, $enquiry_number) {
        $session_data = $this->get_conversation_session($session_id);
        if ($session_data) {
            $session_data['status'] = 'completed';
            $session_data['completed_at'] = current_time('mysql');
            $session_data['application_id'] = $application_id;
            $session_data['enquiry_number'] = $enquiry_number;
            $this->save_conversation_session($session_id, $session_data);
        }
    }
    
    /**
     * Check if session is completed
     */
    private function is_session_completed($session_id) {
        $session_data = $this->get_conversation_session($session_id);
        return $session_data && isset($session_data['status']) && $session_data['status'] === 'completed';
    }
    
    /**
     * Handle post-submission edits
     */
    private function handle_post_submission_edit($message, $session_id) {
        $session_data = $this->get_conversation_session($session_id);
        
        if (!$session_data || !isset($session_data['enquiry_number'])) {
            return "I'm sorry, I can't find your submission details. Please contact our admissions team directly.";
        }
        
        $enquiry_number = $session_data['enquiry_number'];
        
        // Parse edit request
        $edit_request = $this->parse_edit_request($message);
        
        if (!$edit_request) {
            return "I understand you want to make changes to your application (Reference: {$enquiry_number}). " .
                   "Please tell me what you'd like to update using this format:\n\n" .
                   "• **Change email to** your_new_email@example.com\n" .
                   "• **Update phone to** 9876543210\n" .
                   "• **Change name to** New Student Name\n" .
                   "• **Update grade to** Grade 8\n" .
                   "• **Change board to** CBSE\n" .
                   "• **Update DOB to** 15/05/2010\n\n" .
                   "What would you like to update?";
        }
        
        // Apply the edit to database
        $update_result = $this->update_application_in_database($session_data['application_id'], $edit_request);
        
        if ($update_result) {
            // Update session data as well
            foreach ($edit_request as $field => $value) {
                if (isset($session_data['data'][$field])) {
                    $session_data['data'][$field] = $value;
                }
            }
            $this->save_conversation_session($session_id, $session_data);
            
            $field_names = array_keys($edit_request);
            $updated_fields = implode(', ', $field_names);
            
            return "✅ **Update Successful!**\n\n" .
                   "I've successfully updated your {$updated_fields} for application reference: **{$enquiry_number}**\n\n" .
                   "The changes have been saved in our system. You should receive an updated confirmation email shortly.\n\n" .
                   "Is there anything else you'd like to update?";
        } else {
            return "I apologize, but there was an error updating your information. " .
                   "Please contact our admissions team directly with your reference number: **{$enquiry_number}**";
        }
    }
    
    /**
     * Parse edit request from user message
     */
    private function parse_edit_request($message) {
        $edits = array();
        $message_lower = strtolower($message);
        
        // Email update
        if (preg_match('/(?:change|update|edit).*?email.*?to.*?([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $message, $matches) ||
            preg_match('/email.*?(?:change|update).*?to.*?([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $message, $matches)) {
            $edits['email'] = $matches[1];
        }
        
        // Phone update
        if (preg_match('/(?:change|update|edit).*?(?:phone|mobile|number).*?to.*?(\+?[\d\s\-]{10,15})/i', $message, $matches) ||
            preg_match('/(?:phone|mobile|number).*?(?:change|update).*?to.*?(\+?[\d\s\-]{10,15})/i', $message, $matches)) {
            $edits['phone'] = preg_replace('/[^\d+]/', '', $matches[1]);
        }
        
        // Name update
        if (preg_match('/(?:change|update|edit).*?name.*?to.*?([a-zA-Z\s]{2,50})/i', $message, $matches) ||
            preg_match('/name.*?(?:change|update).*?to.*?([a-zA-Z\s]{2,50})/i', $message, $matches)) {
            $edits['student_name'] = trim($matches[1]);
        }
        
        // Grade update
        if (preg_match('/(?:change|update|edit).*?grade.*?to.*?((?:grade\s*)?\d+|nursery|pp1|pp2|pre-?kg|lkg|ukg)/i', $message, $matches) ||
            preg_match('/grade.*?(?:change|update).*?to.*?((?:grade\s*)?\d+|nursery|pp1|pp2|pre-?kg|lkg|ukg)/i', $message, $matches)) {
            $edits['grade'] = $this->extract_grade_from_message($matches[1]);
        }
        
        // Board update
        if (preg_match('/(?:change|update|edit).*?board.*?to.*?(cbse|caie|cambridge|icse|igcse|state\s*board)/i', $message, $matches) ||
            preg_match('/board.*?(?:change|update).*?to.*?(cbse|caie|cambridge|icse|igcse|state\s*board)/i', $message, $matches)) {
            $edits['board'] = $this->extract_board_from_message($matches[1]);
        }
        
        // Date of birth update
        if (preg_match('/(?:change|update|edit).*?(?:dob|date.*?birth).*?to.*?(\d{1,2}[-\/]\d{1,2}[-\/]\d{4})/i', $message, $matches) ||
            preg_match('/(?:dob|date.*?birth).*?(?:change|update).*?to.*?(\d{1,2}[-\/]\d{1,2}[-\/]\d{4})/i', $message, $matches)) {
            $edits['date_of_birth'] = $matches[1];
        }
        
        return !empty($edits) ? $edits : false;
    }
    
    /**
     * Update application in database
     */
    private function update_application_in_database($application_id, $updates) {
        global $wpdb;
        
        if (empty($application_id) || empty($updates)) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'edubot_applications';
        
        // Get current application data
        $current_data = $wpdb->get_row($wpdb->prepare(
            "SELECT student_data FROM {$table_name} WHERE id = %d",
            $application_id
        ));
        
        if (!$current_data) {
            return false;
        }
        
        // Decode current student data
        $student_data = json_decode($current_data->student_data, true);
        if (!$student_data) {
            $student_data = array();
        }
        
        // Apply updates
        foreach ($updates as $field => $value) {
            $student_data[$field] = $value;
        }
        
        // Update database
        $result = $wpdb->update(
            $table_name,
            array(
                'student_data' => json_encode($student_data),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $application_id),
            array('%s', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Handle application submission with enhanced security
     */
    public function handle_application_submission() {
        // Enhanced nonce verification
        if (!check_ajax_referer('edubot_application', 'edubot_nonce', false)) {
            $this->log_security_violation('invalid_nonce_application', array(
                'ip' => $this->get_client_ip(),
                'user_agent' => $this->get_user_agent()
            ));
            wp_send_json_error(array('message' => 'Security verification failed. Please refresh the page and try again.'));
        }

        // Rate limiting for application submissions
        $security_manager = new EduBot_Security_Manager();
        $client_ip = $this->get_client_ip();
        
        if (!$security_manager->check_rate_limit('application_submit_' . md5($client_ip), 3, 3600)) {
            $security_manager->log_security_event('rate_limit_exceeded_application', array(
                'ip' => $client_ip,
                'endpoint' => 'application_submission'
            ));
            wp_send_json_error(array('message' => 'Too many application submissions. Please try again later.'));
        }

        // Get school config for validation
        $school_config = EduBot_School_Config::getInstance();
        $board_required = $school_config->is_board_selection_required();
        $available_years = $school_config->get_available_academic_years();
        
        // Validate required fields with enhanced checking
        $required_fields = array('student_name', 'date_of_birth', 'grade', 'gender', 'parent_name', 'email', 'phone', 'address');
        
        // Add conditional required fields
        if ($board_required) {
            $required_fields[] = 'educational_board';
        }
        if (!empty($available_years)) {
            $required_fields[] = 'academic_year';
        }
        
        // Validate required fields presence and format
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                wp_send_json_error(array('message' => "Field '$field' is required."));
            }
        }

        // Comprehensive input validation and sanitization
        $student_name = sanitize_text_field($_POST['student_name']);
        $date_of_birth = sanitize_text_field($_POST['date_of_birth']);
        $grade = sanitize_text_field($_POST['grade']);
        $gender = sanitize_text_field($_POST['gender']);
        $parent_name = sanitize_text_field($_POST['parent_name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $address = sanitize_textarea_field($_POST['address']);

        // Validate name fields (only letters, spaces, dots, apostrophes)
        if (!preg_match("/^[a-zA-Z\s\.']+$/", $student_name) || strlen($student_name) > 100) {
            wp_send_json_error(array('message' => 'Please enter a valid student name (letters only, max 100 characters).'));
        }
        
        if (!preg_match("/^[a-zA-Z\s\.']+$/", $parent_name) || strlen($parent_name) > 100) {
            wp_send_json_error(array('message' => 'Please enter a valid parent/guardian name (letters only, max 100 characters).'));
        }

        // Validate date of birth
        $dob = DateTime::createFromFormat('Y-m-d', $date_of_birth);
        if (!$dob || $dob->format('Y-m-d') !== $date_of_birth) {
            wp_send_json_error(array('message' => 'Please enter a valid date of birth.'));
        }
        
        // Check age range (between 2 and 19 years old)
        $age = (new DateTime())->diff($dob)->y;
        if ($age < 2 || $age > 19) {
            wp_send_json_error(array('message' => 'Student age must be between 2 and 19 years.'));
        }

        // Validate gender
        $allowed_genders = array('male', 'female', 'other');
        if (!in_array($gender, $allowed_genders)) {
            wp_send_json_error(array('message' => 'Please select a valid gender.'));
        }

        // Validate email format
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Please enter a valid email address.'));
        }

        // Validate phone number (international format)
        $phone_cleaned = preg_replace('/[^0-9+\-\s\(\)]/', '', $phone);
        if (strlen($phone_cleaned) < 10 || strlen($phone_cleaned) > 15) {
            wp_send_json_error(array('message' => 'Please enter a valid phone number (10-15 digits).'));
        }

        // Validate address length
        if (strlen($address) < 10 || strlen($address) > 500) {
            wp_send_json_error(array('message' => 'Please enter a valid address (10-500 characters).'));
        }

        // Security content validation for all text fields
        $text_fields = array($student_name, $parent_name, $address);
        foreach ($text_fields as $field_value) {
            if ($security_manager->is_malicious_content($field_value)) {
                $security_manager->log_security_event('malicious_content_application', array(
                    'field_content' => substr($field_value, 0, 50),
                    'ip' => $client_ip
                ));
                wp_send_json_error(array('message' => 'Application contains invalid content. Please check your entries.'));
            }
        }

        // Validate optional fields with limits
        $educational_board = isset($_POST['educational_board']) ? sanitize_text_field($_POST['educational_board']) : '';
        $academic_year = isset($_POST['academic_year']) ? sanitize_text_field($_POST['academic_year']) : '';
        $special_requirements = isset($_POST['special_requirements']) ? sanitize_textarea_field($_POST['special_requirements']) : '';

        // Validate optional text field lengths - previous school fields removed
        
        if (!empty($special_requirements) && strlen($special_requirements) > 1000) {
            wp_send_json_error(array('message' => 'Special requirements text is too long (max 1000 characters).'));
        }

        // Validate educational board if provided
        if (!empty($educational_board) && !$school_config->is_valid_board($educational_board)) {
            wp_send_json_error(array('message' => 'Please select a valid educational board.'));
        }
        
        // Validate academic year if provided
        if (!empty($academic_year) && !$school_config->is_valid_academic_year($academic_year)) {
            wp_send_json_error(array('message' => 'Please select a valid academic year.'));
        }

        // Check for duplicate applications (same email + student name)
        $existing_check = $this->check_duplicate_application($email, $student_name);
        if ($existing_check) {
            wp_send_json_error(array('message' => 'An application for this student already exists with this email address.'));
        }
        
        // Prepare sanitized student data
        $student_data = array(
            'student_name' => $student_name,
            'date_of_birth' => $date_of_birth,
            'grade' => $grade,
            'educational_board' => $educational_board,
            'academic_year' => $academic_year,
            'gender' => $gender,
            'parent_name' => $parent_name,
            'email' => $email,
            'phone' => $phone_cleaned,
            'address' => $address,
            'special_requirements' => $special_requirements,
            'marketing_consent' => isset($_POST['marketing_consent']) ? 1 : 0,
            'submitted_at' => current_time('mysql'),
            'submission_ip' => $client_ip
        );
        
        $application_data = array(
            'application_number' => $this->generate_application_number(),
            'student_data' => $student_data,
            'conversation_log' => array(
                'source' => 'application_form',
                'timestamp' => current_time('mysql'),
                'ip_address' => $client_ip,
                'user_agent' => $this->get_user_agent(),
                'form_version' => '1.0'
            ),
            'status' => 'pending',
            'source' => 'application_form'
        );
        
        try {
            // Save to database using the enhanced database manager
            $database_manager = new EduBot_Database_Manager();
            $application_id = $database_manager->save_application($application_data);
            
            if (is_wp_error($application_id)) {
                error_log('EduBot Application Error: ' . $application_id->get_error_message());
                wp_send_json_error(array('message' => 'Sorry, there was an error submitting your application. Please try again.'));
            }
            
            // Add the application data to student_data for notifications
            $notification_data = array_merge($student_data, array(
                'application_number' => $application_data['application_number'],
                'application_id' => $application_id
            ));
            
            // Send notification emails (if configured)
            $this->send_application_notifications($notification_data);
            
            // Log successful application submission
            error_log("EduBot: Application submitted successfully - {$application_data['application_number']}");
            
            wp_send_json_success(array(
                'message' => 'Thank you! Your application has been submitted successfully. We will contact you soon.',
                'application_number' => $application_data['application_number']
            ));
            
        } catch (Exception $e) {
            error_log('EduBot Application Submission Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Sorry, there was a technical error. Please try again or contact us directly.'));
        }
    }

    /**
     * Generate unique application number
     */
    private function generate_application_number() {
        $prefix = 'APP-' . date('Y') . '-';
        $suffix = str_pad(wp_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        
        // Ensure uniqueness
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';
        $attempts = 0;
        
        do {
            $application_number = $prefix . $suffix;
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE application_number = %s",
                $application_number
            ));
            
            if ($existing == 0) {
                return $application_number;
            }
            
            $suffix = str_pad(wp_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $attempts++;
        } while ($attempts < 10);
        
        // Fallback with timestamp
        return $prefix . time();
    }

    /**
     * Check for duplicate applications
     */
    private function check_duplicate_application($email, $student_name) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
             WHERE JSON_EXTRACT(student_data, '$.email') = %s 
             AND JSON_EXTRACT(student_data, '$.student_name') = %s 
             AND site_id = %d
             AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)",
            $email, $student_name, get_current_blog_id()
        ));
        
        return $existing > 0;
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
     * Get user agent safely
     */
    private function get_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? 
            substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 500) : 'unknown';
    }

    /**
     * Log security violations
     */
    private function log_security_violation($event_type, $details) {
        $security_manager = new EduBot_Security_Manager();
        $security_manager->log_security_event($event_type, $details);
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
    
    /**
     * Provide intelligent fallback responses when chatbot engine fails
     */
    private function provide_intelligent_fallback($message, $action_type = '', $session_id = '') {
        $settings = get_option('edubot_pro_settings', array());
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        $school_name = $config['school_info']['name'] ?? $settings['school_name'] ?? 'Epistemo Vikas Leadership School';
        $message_lower = strtolower($message);
        
        // Get conversation session data
        $session_data = $this->get_conversation_session($session_id);
        $current_step = $session_data ? ($session_data['step'] ?? '') : '';
        
        $this->debug_log("Current step = " . $current_step . ", Message = " . $message);
        
        // Handle legacy "CONFIRM" messages and variations - inform users the system has been updated
        if (preg_match('/^(confirm|confrim|confrm|yes|submit|proceed)$/i', trim($message)) && empty($current_step)) {
            return "Hello! 👋 Our admission process has been **streamlined for your convenience!**\n\n" .
                   "✨ **Good News:** You no longer need to type 'CONFIRM'!\n\n" .
                   "🚀 **New Process:** Simply provide your details and we'll generate your **enquiry number automatically** after collecting your information.\n\n" .
                   "**Let's start your admission enquiry:**\n\n" .
                   "Please share your:\n" .
                   "👶 **Student Name**\n" .
                   "📧 **Email Address**\n" .
                   "📱 **Mobile Number**\n\n" .
                   "You can type them like:\n" .
                   "• Name: Rahul Kumar\n" .
                   "• Mobile: 9876543210\n" .
                   "• Email: parent@email.com\n\n" .
                   "Or just start with the student's name and I'll guide you step by step! 😊";
        }
        
        // OPTIONAL PARENT INFO STEP REMOVED - Now skips directly to confirmation
        /*
        if ($current_step === 'optional_parent_info') {
            // This step is now bypassed - chatbot goes directly to final confirmation
            // after collecting required information (name, email, phone, grade, board, DOB)
        }
        */
        
        // Confirmation step removed - enquiry auto-generated after DOB collection
        
        // PRIORITY: Handle final details step (DOB collection)
        if ($current_step === 'final' || $current_step === 'age') {
            $this->debug_log("Processing final details step");
            $collected_data = $session_data ? $session_data['data'] : array();
            
            // Parse the message for DOB
            $additional_info = $this->parse_additional_info($message);
            
            // Check for validation errors first
            if (!empty($additional_info['error'])) {
                return "❌ " . $additional_info['error'];
            }
            
            // Store collected DOB if valid
            if (!empty($additional_info['date_of_birth'])) {
                $this->update_conversation_data($session_id, 'date_of_birth', $additional_info['date_of_birth']);
                $collected_data['date_of_birth'] = $additional_info['date_of_birth'];
                
                // Automatically generate enquiry number and save to database
                $this->update_conversation_data($session_id, 'step', 'completed');
                
                return $this->process_final_submission($collected_data, $session_id);
            } else {
                return "Please enter the student's date of birth in **dd/mm/yyyy** format.\n\n" .
                       "**Example:** 16/10/2010\n\n" .
                       "Make sure to use the correct format with 4-digit year.";
            }
        }
        
        // Handle specific admission steps with session-like functionality
        if (strpos($message_lower, 'admission') !== false || 
            strpos($message_lower, 'apply') !== false || 
            strpos($message_lower, 'enroll') !== false ||
            strpos($message_lower, 'join') !== false ||
            $action_type === 'admission') {
            
            // Try to extract all possible information from the admission request
            $comprehensive_info = $this->parse_comprehensive_admission_info($message, $session_id);
            
            if (!empty($comprehensive_info['extracted_data'])) {
                // Information was extracted, store it and show what's still needed
                return $comprehensive_info['response'];
            }
            
            // No specific information found, show generic admission welcome
            return "Hello! **Welcome to {$school_name}.**\n\n" .
                   "We are currently accepting applications for **AY 2026–27**.\n\n" .
                   "Please help me with your:\n\n" .
                   "👶 **Name**\n" .
                   "� **Mobile Number**\n" .
                   "� **Email Id**\n\n" .
                   "You can type them like:\n" .
                   "• Name: Sujay\n" .
                   "• Mobile: 9876543210\n" .
                   "• Email: parent@email.com\n\n" .
                   "Or just start with the student's name and I'll ask for the rest step by step.";
        }
        
        // Handle Contact/Visit sub-flows - PRIORITY HANDLER
        if (stripos($message_lower, 'call') !== false && strlen(trim($message)) <= 10) {
            return "📞 **Call Our Admission Office**\n\n" .
                   "Ready to speak with us directly? Here are our contact numbers:\n\n" .
                   "**📞 Admission Office Numbers:**\n" .
                   "• **7702800800** (Primary)\n" .
                   "• **9248111448** (Secondary)\n\n" .
                   "**📅 Office Hours:**\n" .
                   "• Monday to Friday: 9:00 AM - 6:00 PM\n" .
                   "• Saturday: 9:00 AM - 2:00 PM\n" .
                   "• Sunday: Closed\n\n" .
                   "**💡 What to expect:**\n" .
                   "• Immediate assistance from admission counselors\n" .
                   "• Detailed information about programs and fees\n" .
                   "• Help with application process\n" .
                   "• Schedule campus visits\n\n" .
                   "Feel free to call us now! Our team is ready to help you. 😊";
        }
        
        if (stripos($message_lower, 'email') !== false && strlen(trim($message)) <= 10) {
            return "📧 **Email Us for Detailed Information**\n\n" .
                   "Prefer written communication? We'd love to hear from you!\n\n" .
                   "**📧 Email Address:**\n" .
                   "• **admissions@epistemo.in**\n\n" .
                   "**📝 What to include in your email:**\n" .
                   "• Student's name and date of birth\n" .
                   "• Grade/class seeking admission for\n" .
                   "• Your contact number\n" .
                   "• Any specific questions you have\n\n" .
                   "**⚡ Response Time:**\n" .
                   "• We typically respond within 2-4 hours during business days\n" .
                   "• Detailed brochures and fee structure will be attached\n\n" .
                   "**💡 Email us for:**\n" .
                   "• Detailed admission brochures\n" .
                   "• Fee structure documents\n" .
                   "• Academic curriculum details\n" .
                   "• Campus tour scheduling\n\n" .
                   "Send us an email now and we'll get back to you soon! 📬";
        }
        
        if (stripos($message_lower, 'tour') !== false && strlen(trim($message)) <= 10) {
            return "🏫 **Book Your Campus Tour**\n\n" .
                   "Experience {$school_name} firsthand with a personalized campus tour!\n\n" .
                   "**🌟 What's Included in Your Tour:**\n" .
                   "• Guided tour of all academic facilities\n" .
                   "• Visit to science labs, library, and computer centers\n" .
                   "• Sports complex and recreational areas\n" .
                   "• Meeting with faculty and administrative staff\n" .
                   "• Interaction with current students (if available)\n" .
                   "• Q&A session with our admissions team\n\n" .
                   "**📅 Tour Schedule:**\n" .
                   "• Monday to Friday: 10:00 AM, 2:00 PM, 4:00 PM\n" .
                   "• Saturday: 10:00 AM, 12:00 PM\n" .
                   "• Duration: Approximately 60-90 minutes\n\n" .
                   "**📞 To Book Your Tour:**\n" .
                   "• Call: 7702800800 / 9248111448\n" .
                   "• Email: admissions@epistemo.in\n" .
                   "• Mention your preferred date and time\n\n" .
                   "Ready to see what makes us special? Book your tour today! 🎓";
        }
        
        if (stripos($message_lower, 'callback') !== false && strlen(trim($message)) <= 15) {
            return "📞 **Request a Callback**\n\n" .
                   "Let us call you at your convenience!\n\n" .
                   "**📱 How to Request:**\n" .
                   "Please provide the following information:\n\n" .
                   "**Format:** Callback: [Your Name], [Phone Number], [Best Time]\n\n" .
                   "**Example:**\n" .
                   "Callback: Priya Sharma, 9876543210, Evening 6-8 PM\n\n" .
                   "**⏰ Available Callback Times:**\n" .
                   "• Morning: 10:00 AM - 12:00 PM\n" .
                   "• Afternoon: 2:00 PM - 5:00 PM\n" .
                   "• Evening: 6:00 PM - 8:00 PM\n\n" .
                   "**📋 What We'll Discuss:**\n" .
                   "• Your child's educational needs\n" .
                   "• Admission process and requirements\n" .
                   "• Fee structure and payment options\n" .
                   "• Campus tour scheduling\n" .
                   "• Any questions you may have\n\n" .
                   "**⚡ Response Time:** We'll call you within 2 hours during business hours!\n\n" .
                   "Please share your details for the callback. 😊";
        }
        
        // Handle academic information (grade and board together) - PRIORITY HANDLER
        $academic_info = $this->parse_academic_info($message);
        $session_data = $this->get_conversation_session($session_id);
        $collected_data = $session_data ? $session_data['data'] : array();
        
        // Check if this looks like academic info and we have personal info already
        if (!empty($academic_info) && !empty($collected_data['student_name']) && 
            (preg_match('/\b(nursery|pp1|pp2|pre-?kg|lkg|ukg|grade|grde|class|\d+th|\d+st|\d+nd|\d+rd|cbse|caie|cambridge|state|icse|igcse)\b/i', $message_lower))) {
            
            // Store any collected academic info
            if (!empty($academic_info['grade'])) {
                $this->update_conversation_data($session_id, 'grade', $academic_info['grade']);
                $collected_data['grade'] = $academic_info['grade'];
            }
            if (!empty($academic_info['board'])) {
                $this->update_conversation_data($session_id, 'board', $academic_info['board']);
                $collected_data['board'] = $academic_info['board'];
            }
            if (!empty($academic_info['academic_year'])) {
                $this->update_conversation_data($session_id, 'academic_year', $academic_info['academic_year']);
                $collected_data['academic_year'] = $academic_info['academic_year'];
            }
            
            // Check what's still needed for academic info
            $missing_academic = array();
            if (empty($collected_data['grade'])) $missing_academic[] = "🎓 Grade/Class";
            if (empty($collected_data['board'])) $missing_academic[] = "📚 Board Preference";
            
            if (!empty($missing_academic)) {
                $response = "✅ **Academic Information Recorded:**\n";
                if (!empty($collected_data['grade'])) $response .= "• Grade: {$collected_data['grade']}\n";
                if (!empty($collected_data['board'])) $response .= "• Board: {$collected_data['board']}\n";
                if (!empty($collected_data['academic_year'])) $response .= "• Academic Year: {$collected_data['academic_year']}\n";
                
                $response .= "\n**Still needed:**\n";
                foreach ($missing_academic as $field) {
                    $response .= "• {$field}\n";
                }
                
                if (empty($collected_data['board'])) {
                    // Show available boards
                    try {
                        if (class_exists('EduBot_School_Config')) {
                            $school_config = EduBot_School_Config::getInstance();
                            $enabled_boards = $school_config->get_enabled_boards();
                            $configured_boards = $school_config->get_configured_boards();
                            $boards_to_show = !empty($enabled_boards) ? $enabled_boards : $configured_boards;
                            
                            $response .= "\n**Available Boards:**\n";
                            foreach ($boards_to_show as $board) {
                                $response .= "• **{$board['code']}** ({$board['name']})\n";
                            }
                        }
                    } catch (Exception $e) {
                        $response .= "\n**Available Boards:**\n• **CBSE** • **CAIE**\n";
                    }
                }
                
                return $response;
            }
            
            // All academic info collected, move to final details
            $this->update_conversation_data($session_id, 'step', 'final');
            
            $academic_summary = "• Grade: {$collected_data['grade']}\n• Board: {$collected_data['board']}\n";
            if (!empty($collected_data['academic_year'])) {
                $academic_summary .= "• Academic Year: {$collected_data['academic_year']}\n";
            }
            
            return "✅ **Academic Information Complete!**\n" .
                   $academic_summary . "\n" .
                   "**Step 3: Final Details** 📋\n\n" .
                   "Please provide:\n\n" .
                   "**Student's Date of Birth** (dd/mm/yyyy format)\n\n" .
                   "**Example:**\n" .
                   "• 16/10/2010\n\n" .
                   "Please enter the date of birth in dd/mm/yyyy format only.";
        }
        
        // Handle board selection first (to avoid confusion) - but only if not part of combined academic info
        if (preg_match('/\b(cbse|caie|cambridge|state\s*board|icse|igcse|international|ib|bse\s*telangana)\b/i', $message_lower) &&
            !preg_match('/\b(nursery|pp1|pp2|pre-?kg|lkg|ukg|grade|class|\d+th|\d+st|\d+nd|\d+rd)\b/i', $message_lower) &&
            !preg_match('/\b(20\d{2}[-\/]20?\d{2}|20\d{2})\b/', $message)) {
            // Get configured boards from backend to validate the selection with error handling
            try {
                // Check if class exists first
                if (!class_exists('EduBot_School_Config')) {
                    throw new Exception('EduBot_School_Config class not found');
                }
                
                $school_config = EduBot_School_Config::getInstance();
                $configured_boards = $school_config->get_configured_boards();
                
                // Find matching board
                $selected_board = null;
                foreach ($configured_boards as $board) {
                    if (stripos($message_lower, strtolower($board['code'])) !== false) {
                        $selected_board = $board;
                        break;
                    }
                }
                
                // If no exact match found, use the extract function as fallback
                if (!$selected_board) {
                    $board_name = $this->extract_board_from_message($message);
                    $selected_board = array('code' => $board_name, 'name' => $board_name);
                }
                
            } catch (Exception $e) {
                error_log('EduBot Board Selection Error: ' . $e->getMessage());
                // Fallback to simple extraction
                $board_name = $this->extract_board_from_message($message);
                $selected_board = array('code' => $board_name, 'name' => $board_name);
            }
            
            // Store board in session
            if ($session_id) {
                $this->update_conversation_data($session_id, 'board', $selected_board['code']);
                $this->update_conversation_data($session_id, 'step', 'final');
            }
            
            return "✅ **Board Selected: {$selected_board['code']}**\n\n" .
                   "Excellent choice! {$selected_board['name']} offers great educational opportunities.\n\n" .
                   "**What is your child's date of birth?** �\n\n" .
                   "Please enter in dd/mm/yyyy format (e.g., 16/10/2010).";
        }
        
        // Handle when user provides personal information (multi-field or single field)
        $personal_info = $this->parse_personal_info($message);
        $session_data = $this->get_conversation_session($session_id);
        $collected_data = $session_data ? $session_data['data'] : array();
        
        $this->debug_log("Starting personal info check. Message: " . $message);
        $this->debug_log("Parsed personal info: " . print_r($personal_info, true));
        $this->debug_log("Initial collected_data: " . print_r($collected_data, true));
        
        // Check if this looks like personal info input (but not if we already have a complete session)
        if (!empty($personal_info) && (
            !empty($personal_info['name']) || 
            !empty($personal_info['email']) || 
            !empty($personal_info['phone'])
        ) && (
            // Only process personal info if we don't have complete personal information yet
            empty($collected_data['student_name']) || 
            empty($collected_data['email']) || 
            empty($collected_data['phone'])
        ) && 
            // Don't process if this looks like new admission requests or academic queries (but allow contact info)
            !preg_match('/\b(admission\s+for|apply\s+for|enroll\s+for|join\s+for|looking\s+for\s+admission|fee|cost|price|visit|tour|school\s+information|about\s+school|facility|program)\b/i', $message_lower)) {
            
            // Store original session state to check if name was already present
            $had_name_before = !empty($collected_data['student_name']);
            
            // Store any collected info with validation
            if (!empty($personal_info['name']) && strlen(trim($personal_info['name'])) >= 2) {
                $this->update_conversation_data($session_id, 'student_name', $personal_info['name']);
            }
            if (!empty($personal_info['email']) && filter_var($personal_info['email'], FILTER_VALIDATE_EMAIL)) {
                $this->update_conversation_data($session_id, 'email', $personal_info['email']);
            }
            if (!empty($personal_info['phone']) && preg_match('/^\+?[\d\s-]{10,15}$/', $personal_info['phone'])) {
                $this->update_conversation_data($session_id, 'phone', $personal_info['phone']);
            }
            
            // Always refresh session data to get the latest complete data
            $session_data = $this->get_conversation_session($session_id);
            $collected_data = $session_data && isset($session_data['data']) ? $session_data['data'] : array();
            
            $this->debug_log("After refresh - collected_data: " . print_r($collected_data, true));
            
            // If we only got name and didn't have it before, ask for email and phone
            if (!empty($personal_info['name']) && 
                empty($personal_info['email']) && 
                empty($personal_info['phone']) &&
                !$had_name_before) {
                return "✅ **Student Name: {$personal_info['name']}**\n\n" .
                       "Great! Now I need your contact details:\n\n" .
                       "📧 **Your Email Address**\n" .
                       "� **Your Phone Number**\n\n" .
                       "You can enter them like:\n" .
                       "Email: parent@email.com, Phone: 9876543210\n\n" .
                       "Or just enter your email address first.";
            }
            
            // Check what's still needed
            $missing_fields = array();
            if (empty($collected_data['student_name'])) $missing_fields[] = "👶 Student Name";
            if (empty($collected_data['email'])) $missing_fields[] = "📧 Email Address";
            if (empty($collected_data['phone'])) $missing_fields[] = "📱 Phone Number";
            
            $this->debug_log("Missing fields check. Missing: " . print_r($missing_fields, true));
            $this->debug_log("Final collected_data for missing check: " . print_r($collected_data, true));
            
            if (!empty($missing_fields)) {
                $response = "✅ **Information Recorded:**\n";
                if (!empty($collected_data['student_name'])) $response .= "• Student: {$collected_data['student_name']}\n";
                if (!empty($collected_data['email'])) $response .= "• Email: {$collected_data['email']}\n";
                if (!empty($collected_data['phone'])) $response .= "• Phone: {$collected_data['phone']}\n";
                
                $response .= "\n**Still needed:**\n";
                foreach ($missing_fields as $field) {
                    $response .= "• {$field}\n";
                }
                $response .= "\nPlease provide the remaining information.";
                return $response;
            }
            
            // All personal info collected - check what to do next
            // If we already have academic info, skip to next step
            if (!empty($collected_data['grade']) && !empty($collected_data['academic_year'])) {
                // We have both personal and academic info, move to additional details
                $this->update_conversation_data($session_id, 'step', 'additional_details');
                
                return "✅ **Personal Information Complete!**\n\n" .
                       "Perfect! I have all your basic information:\n" .
                       "👶 **Student:** {$collected_data['student_name']}\n" .
                       "🎓 **Grade:** {$collected_data['grade']}\n" .
                       "📅 **Academic Year:** {$collected_data['academic_year']}\n" .
                       "📧 **Email:** {$collected_data['email']}\n" .
                       "📱 **Phone:** {$collected_data['phone']}\n\n" .
                       "📋 **Next: Additional Details**\n\n" .
                       "Please provide:\n" .
                       "• 📅 **Date of Birth** (YYYY-MM-DD format)\n" .
                       "• ⚧ **Gender** (Male/Female/Other)\n" .
                       "• 👨‍👩‍👧‍👦 **Parent/Guardian Name**\n" .
                       "• 🏠 **Full Address**\n\n" .
                       "**Example:**\n" .
                       "DOB: 2015-05-15, Gender: Male, Parent: Rajesh Kumar, Address: 123 Main Street, City";
            }
            
            // All personal info collected, move to academic info
            $this->update_conversation_data($session_id, 'step', 'academic');
            
            // Get configured boards from backend
            try {
                if (!class_exists('EduBot_School_Config')) {
                    throw new Exception('EduBot_School_Config class not found');
                }
                
                $school_config = EduBot_School_Config::getInstance();
                $configured_boards = $school_config->get_configured_boards();
                $enabled_boards = $school_config->get_enabled_boards();
                
                $board_options = "";
                $boards_to_show = !empty($enabled_boards) ? $enabled_boards : $configured_boards;
                
                foreach ($boards_to_show as $board) {
                    $board_options .= "• **{$board['code']}** ({$board['name']})\n";
                }
                
                if (empty($board_options)) {
                    $board_options = "• **CBSE** (Central Board of Secondary Education)\n" .
                                   "• **CAIE** (Cambridge Assessment International Education)\n";
                }
                
            } catch (Exception $e) {
                error_log('EduBot Board Config Error: ' . $e->getMessage());
                $board_options = "• **CBSE** (Central Board of Secondary Education)\n" .
                               "• **CAIE** (Cambridge Assessment International Education)\n";
            }
            
            // Get available grades from backend configuration
            $grade_options = "";
            try {
                if (class_exists('Edubot_Academic_Config')) {
                    $school_id = 1; // Default school ID, you might want to make this configurable
                    $available_grades = Edubot_Academic_Config::get_available_grades_for_admission($school_id);
                    
                    if (!empty($available_grades)) {
                        // Create formatted grade list with streams for Grade 11
                        $grade_list = array();
                        foreach ($available_grades as $grade_key => $grade_name) {
                            if (stripos($grade_name, 'grade 11') !== false || stripos($grade_name, 'class 11') !== false) {
                                // Add Grade 11 with streams
                                $grade_list[] = "Grade 11 Science";
                                $grade_list[] = "Grade 11 Commerce"; 
                                $grade_list[] = "Grade 11 Humanities";
                            } else {
                                $grade_list[] = $grade_name;
                            }
                        }
                        // Remove duplicates and create options string
                        $grade_list = array_unique($grade_list);
                        $grade_options = implode('\n• ', $grade_list);
                        $grade_options = "• " . $grade_options;
                    }
                }
            } catch (Exception $e) {
                error_log('EduBot Grade Config Error: ' . $e->getMessage());
            }
            
            // Fallback if no grades configured
            if (empty($grade_options)) {
                $grade_options = "• Nursery\n• PP1\n• PP2\n• Grade 1\n• Grade 2\n• Grade 3\n• Grade 4\n• Grade 5\n• Grade 6\n• Grade 7\n• Grade 8\n• Grade 9\n• Grade 10\n• Grade 11 Science\n• Grade 11 Commerce\n• Grade 11 Humanities";
            }
            
            return "✅ **Personal Information Complete!**\n" .
                   "• Name: {$collected_data['student_name']}\n" .
                   "• Email: {$collected_data['email']}\n" .
                   "• Mobile: {$collected_data['phone']}\n\n" .
                   "**Curriculum**\n\n" .
                   "Are you interested in the CBSE / Cambridge curriculum?\n\n" .
                   "**🔘 CBSE** (Central Board of Secondary Education)\n" .
                   "**🔘 CAMBRIDGE** (Cambridge Assessment International Education)\n\n" .
                   "Please type **CBSE** or **CAMBRIDGE** to continue.";
        }
        
        // Handle final details (age, address only - no previous school needed)
        $additional_info = $this->parse_additional_info($message);
        $session_data = $this->get_conversation_session($session_id);
        $collected_data = $session_data ? $session_data['data'] : array();
        
        // Check if this looks like final details info and we have academic info already
        if (!empty($additional_info) && !empty($collected_data['grade']) && !empty($collected_data['board'])) {
            
            // Check for validation errors first
            if (!empty($additional_info['error'])) {
                return "❌ " . $additional_info['error'];
            }
            
            // Store collected DOB if valid
            if (!empty($additional_info['date_of_birth'])) {
                $this->update_conversation_data($session_id, 'date_of_birth', $additional_info['date_of_birth']);
                $collected_data['date_of_birth'] = $additional_info['date_of_birth'];
            }
            
            // Check what's still needed for final details (only DOB)
            $missing_final = array();
            if (empty($collected_data['date_of_birth'])) {
                $missing_final[] = "� Date of Birth (dd/mm/yyyy)";
            }
            
            if (!empty($missing_final)) {
                $response = "✅ **Final Details Recorded:**\n";
                if (!empty($collected_data['date_of_birth'])) {
                    $response .= "• Date of Birth: {$collected_data['date_of_birth']}\n";
                }
                
                $response .= "\n**Still needed:**\n";
                foreach ($missing_final as $field) {
                    $response .= "• {$field}\n";
                }
                $response .= "\nPlease provide the date of birth in dd/mm/yyyy format.";
                return $response;
            }
            
            // All required details collected - automatically generate enquiry number
            $this->update_conversation_data($session_id, 'step', 'completed');
            
            // Directly generate enquiry number and save to database
            return $this->process_final_submission($collected_data, $session_id);
        }
        
        // Handle grade selection responses (only if not combined academic input)
        if (preg_match('/\b(nursery|pp1|pp2|pre-?kg|lkg|ukg|gr[ae]de?\s*\d+|class\s*\d+|\d+th|\d+st|\d+nd|\d+rd)\b/i', $message_lower) && 
            !preg_match('/\b(cbse|icse|igcse|caie|cambridge|ib|state)\b/i', $message_lower) &&
            !preg_match('/\b(20\d{2}-?\d{2})\b/', $message_lower)) {
            $grade = $this->extract_grade_from_message($message);
            
            // Special handling for Grade 11 - show stream options if user just typed "Grade 11"
            if ($grade === 'Grade 11' && !preg_match('/\b(science|commerce|humanities)\b/i', $message_lower)) {
                return "✅ **Grade 11 Selected!**\n\n" .
                       "Grade 11 offers different streams based on your interests and career goals. Please choose your preferred stream:\n\n" .
                       "🔬 **Grade 11 Science**\n" .
                       "   • Physics, Chemistry, Biology/Mathematics\n" .
                       "   • For Medical, Engineering, and Science careers\n\n" .
                       "💼 **Grade 11 Commerce**\n" .
                       "   • Accountancy, Business Studies, Economics\n" .
                       "   • For Business, CA, Management careers\n\n" .
                       "🎭 **Grade 11 Humanities**\n" .
                       "   • History, Geography, Political Science, Psychology\n" .
                       "   • For Arts, Literature, Social Science careers\n\n" .
                       "Please type your choice:\n" .
                       "• **Grade 11 Science**\n" .
                       "• **Grade 11 Commerce**\n" .
                       "• **Grade 11 Humanities**";
            }
            
            // Store grade in session
            if ($session_id) {
                $this->update_conversation_data($session_id, 'grade', $grade);
                $this->update_conversation_data($session_id, 'step', 'board');
            }
            
            // Get configured boards from backend with error handling
            try {
                // Check if class exists first
                if (!class_exists('EduBot_School_Config')) {
                    throw new Exception('EduBot_School_Config class not found');
                }
                
                $school_config = EduBot_School_Config::getInstance();
                $configured_boards = $school_config->get_configured_boards();
                $enabled_boards = $school_config->get_enabled_boards();
                
                // Build board options dynamically
                $board_options = "";
                $boards_to_show = !empty($enabled_boards) ? $enabled_boards : $configured_boards;
                
                foreach ($boards_to_show as $board) {
                    $board_options .= "• **{$board['code']}** ({$board['name']})\n";
                }
                
                if (empty($board_options)) {
                    // Fallback if no boards configured
                    $board_options = "• **CBSE** (Central Board of Secondary Education)\n" .
                                   "• **CAIE** (Cambridge Assessment International Education)\n" .
                                   "• **ICSE** (Indian Certificate of Secondary Education)\n" .
                                   "• **IGCSE** (International General Certificate of Secondary Education)\n";
                }
                
            } catch (Exception $e) {
                error_log('EduBot Board Config Error: ' . $e->getMessage());
                // Fallback board options
                $board_options = "• **CBSE** (Central Board of Secondary Education)\n" .
                               "• **CAIE** (Cambridge Assessment International Education)\n" .
                               "• **ICSE** (Indian Certificate of Secondary Education)\n" .
                               "• **IGCSE** (International General Certificate of Secondary Education)\n";
            }
            
            return "✅ **Grade Selected: {$grade}**\n\n" .
                   "Perfect! Now I need to know about the **curriculum board** you prefer.\n\n" .
                   "**Which board would you like?** 📚\n\n" .
                   $board_options . "\n" .
                   "Just type the board code you prefer, like 'CBSE' or 'CAIE'.";
        }
        
        // Age input is no longer supported - only DOB in dd/mm/yyyy format
        
        // Previous school handling removed - go directly to confirmation after DOB
        
        // Legacy confirmation handler removed - now using proper step-based confirmation
        // that saves to database via process_final_submission() method
        
        // Handle fee-related queries
        if (strpos($message_lower, 'fee') !== false || 
            strpos($message_lower, 'cost') !== false || 
            strpos($message_lower, 'price') !== false) {
            
            return "💰 **Fee Information for {$school_name}**\n\n" .
                   "Our fee structure is competitive and includes comprehensive educational services.\n\n" .
                   "**Annual Fee Structure (2024-25):**\n" .
                   "• 🍼 Pre-KG: ₹45,000 - ₹55,000\n" .
                   "• 🧸 LKG/UKG: ₹50,000 - ₹60,000\n" .
                   "• 📚 Grade 1-5: ₹55,000 - ₹65,000\n" .
                   "• 📖 Grade 6-8: ₹65,000 - ₹75,000\n" .
                   "• 🎓 Grade 9-10: ₹75,000 - ₹85,000\n" .
                   "• 🏆 Grade 11-12: ₹85,000 - ₹95,000\n\n" .
                   "**Fee Includes:**\n" .
                   "• Tuition and academic fees\n" .
                   "• Textbooks and study materials\n" .
                   "• School uniform (2 sets)\n" .
                   "• Extracurricular activities\n" .
                   "• Sports and library access\n" .
                   "• Annual events and competitions\n\n" .
                   "**Payment Options:**\n" .
                   "• 💳 Annual payment (5% discount)\n" .
                   "• 📅 Quarterly payments\n" .
                   "• 💰 Monthly installments\n\n" .
                   "**Scholarships Available:**\n" .
                   "• Merit-based scholarships\n" .
                   "• Need-based financial assistance\n\n" .
                   "📞 For detailed fee discussion: " . ($settings['phone'] ?? 'Contact us') . "\n\n" .
                   "Would you like to start the **admission process**?";
        }
        
        // Handle contact/visit queries  
        if (strpos($message_lower, 'visit') !== false || 
            strpos($message_lower, 'tour') !== false || 
            strpos($message_lower, 'contact') !== false ||
            strpos($message_lower, 'phone') !== false ||
            strpos($message_lower, 'address') !== false) {
            
            return "📍 **Contact & Visit Information**\n\n" .
                   "We'd love to welcome you to {$school_name}!\n\n" .
                   "**Contact Details:**\n" .
                   "📞 Phone: " . ($settings['phone'] ?? '+91-80-12345678') . "\n" .
                   "📱 Mobile: " . ($settings['mobile'] ?? '+91-9876543210') . "\n" .
                   "📧 Email: " . ($settings['email'] ?? 'info@school.edu') . "\n" .
                   "🌐 Website: " . ($settings['website'] ?? 'www.school.edu') . "\n" .
                   "🏫 Address: " . ($settings['address'] ?? '123 Education Lane, Knowledge City') . "\n\n" .
                   "**Campus Visit Timings:**\n" .
                   "• 📅 Monday to Friday: 9:00 AM - 4:00 PM\n" .
                   "• 📅 Saturday: 9:00 AM - 1:00 PM\n" .
                   "• 📅 Sunday: Closed\n\n" .
                   "**What You'll Experience:**\n" .
                   "• 🏫 Guided campus tour\n" .
                   "• 👨‍🏫 Meet with faculty and principal\n" .
                   "• 🔬 Visit our labs and libraries\n" .
                   "• 🏃‍♂️ See sports facilities\n" .
                   "• 🎨 Explore activity centers\n" .
                   "• ❓ Q&A session with admissions team\n\n" .
                   "**To Schedule a Visit:**\n" .
                   "• Call us during working hours\n" .
                   "• Start an admission enquiry\n" .
                   "• Email us your preferred timing\n\n" .
                   "📅 *We recommend advance booking for personalized attention.*\n\n" .
                   "Ready to start your **admission enquiry**?";
        }
        
        // Handle online enquiry form requests
        if (strpos($message_lower, 'online enquiry') !== false || 
            strpos($message_lower, 'enquiry form') !== false || 
            strpos($message_lower, 'online form') !== false ||
            strpos($message_lower, '6') !== false ||
            $message_lower === '6' ||
            $action_type === 'online_enquiry') {
            
            return "🌐 **Online Enquiry Form**\n\n" .
                   "For your convenience, you can fill out our detailed online enquiry form:\n\n" .
                   "🔗 **Direct Link:** https://epistemo.in/enquiry/\n\n" .
                   "📋 **What you can do on the form:**\n" .
                   "• Provide detailed student information\n" .
                   "• Select preferred curriculum and grade\n" .
                   "• Specify your requirements and preferences\n" .
                   "• Upload necessary documents\n" .
                   "• Schedule a campus visit\n\n" .
                   "✅ **Benefits:**\n" .
                   "• Save time with pre-filled information\n" .
                   "• Upload documents directly\n" .
                   "• Get faster response from our team\n" .
                   "• Track your application status\n\n" .
                   "🚀 **Click the link above to get started!**\n\n" .
                   "If you prefer, I can also help you with the admission process right here in the chat. Just let me know!";
        }
        
        // Handle school information queries
        if (strpos($message_lower, 'school') !== false || 
            strpos($message_lower, 'about') !== false || 
            strpos($message_lower, 'information') !== false ||
            strpos($message_lower, 'facility') !== false ||
            strpos($message_lower, 'program') !== false) {
            
            return "🏫 **About {$school_name}**\n\n" .
                   "Excellence in education since our establishment, nurturing young minds for a bright future!\n\n" .
                   "**Our Mission:**\n" .
                   "To provide world-class education that develops confident, capable, and caring global citizens.\n\n" .
                   "**Key Features:**\n" .
                   "• 🎓 Multiple curriculum options (CBSE/State/International)\n" .
                   "• 👨‍🏫 Highly qualified faculty (avg. 15+ years experience)\n" .
                   "• 🔬 State-of-the-art laboratories and equipment\n" .
                   "• 📚 Well-stocked library with digital resources\n" .
                   "• 💻 Smart classrooms with modern technology\n" .
                   "• 🏃‍♂️ Comprehensive sports complex\n" .
                   "• 🎨 Dedicated arts, music, and dance studios\n" .
                   "• 🚌 Safe and reliable transportation\n" .
                   "• 🍱 Nutritious meal programs\n\n" .
                   "**Academic Excellence:**\n" .
                   "• 📊 98%+ board exam pass rate\n" .
                   "• 👥 Student-teacher ratio: 20:1\n" .
                   "• 🏆 Regular academic competitions and awards\n" .
                   "• 💡 Individual attention and personalized learning\n\n" .
                   "**Extracurricular Activities:**\n" .
                   "• 🏃‍♂️ Sports: Cricket, Football, Basketball, Swimming\n" .
                   "• 🎭 Arts: Music, Dance, Drama, Fine Arts\n" .
                   "• 🧪 Clubs: Science, Robotics, Environmental, Chess\n" .
                   "• 🎪 Events: Annual day, Sports day, Science exhibitions\n\n" .
                   "**Safety & Security:**\n" .
                   "• 24/7 CCTV surveillance\n" .
                   "• Trained security personnel\n" .
                   "• Medical first-aid facilities\n" .
                   "• Safe transport with GPS tracking\n\n" .
                   "Ready to be part of our school family? Start your **admission enquiry** today!";
        }
        
        // Default fallback response
        return "Hello! 👋 Welcome to {$school_name}!\n\n" .
               "I'm here to assist you with all your school-related queries.\n\n" .
               "**I can help you with:**\n\n" .
               "🎓 **Admissions**\n" .
               "• Step-by-step application process\n" .
               "• Grade-wise requirements\n" .
               "• Document checklist\n" .
               "• Enquiry number generation\n\n" .
               "💰 **Fees & Payments**\n" .
               "• Detailed fee structure\n" .
               "• Payment options and discounts\n" .
               "• Scholarship information\n\n" .
               "🏫 **School Information**\n" .
               "• Facilities and infrastructure\n" .
               "• Academic programs\n" .
               "• Faculty and achievements\n" .
               "• Extracurricular activities\n\n" .
               "📞 **Contact & Visits**\n" .
               "• Campus tour scheduling\n" .
               "• Contact information\n" .
               "• Visit timings and process\n\n" .
               "**Quick Actions:**\n" .
               "• Click **'Admission'** to start your enquiry\n" .
               "• Type **'admission'** to begin application\n" .
               "• Ask any specific questions you have\n\n" .
               "**Sample questions you can ask:**\n" .
               "• \"I want admission for Grade 5\"\n" .
               "• \"What are the fees for LKG?\"\n" .
               "• \"How can I visit the school?\"\n" .
               "• \"Tell me about your facilities\"\n\n" .
               "How can I help you today? 😊";
    }
    
    /**
     * Determine if the message contains structured admission data that should use rule-based processing
     */
    private function is_structured_admission_data($message, $session_id = '') {
        $message_lower = strtolower($message);
        
        // Get session data to understand current conversation state
        $session_data = $this->get_conversation_session($session_id);
        $collected_data = $session_data ? $session_data['data'] : array();
        
        // Always use rule-based system if we're in an active admission flow
        if (!empty($collected_data) && (
            !empty($collected_data['student_name']) || 
            !empty($collected_data['email']) || 
            !empty($collected_data['phone']) ||
            !empty($collected_data['grade']) ||
            !empty($collected_data['board'])
        )) {
            return true;
        }
        
        // Use rule-based for explicit admission requests
        if (preg_match('/\b(admission|apply|enroll|join)\b/i', $message_lower)) {
            return true;
        }
        
        // Use rule-based for structured personal information
        if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $message) ||
            preg_match('/(\+?91|0)?[\s-]?[6-9]\d{9}/', $message)) {
            return true;
        }
        
        // Use rule-based for grade and board combinations
        if (preg_match('/\b(nursery|pp1|pp2|pre-?kg|lkg|ukg|grade|class|\d+th|\d+st|\d+nd|\d+rd)\b/i', $message_lower) &&
            preg_match('/\b(cbse|caie|cambridge|state|icse|igcse|international|ib|bse)\b/i', $message_lower)) {
            return true;
        }
        
        // Use rule-based for confirmation responses
        if (preg_match('/\b(confirm|yes|complete|finish)\b/i', $message_lower)) {
            return true;
        }
        
        // Use rule-based for age and address information (but not in natural conversation)
        if ((preg_match('/\b(\d{1,2})\s*(years?|yrs?)\s*old\b/i', $message_lower) && 
             str_word_count($message) <= 6) ||
            (preg_match('/\b(\d{1,2})\s*(years?|yrs?)?\b/i', $message_lower) && 
             !preg_match('/\b(daughter|son|child|kid|want|good|education|looking|seeking)\b/i', $message_lower)) ||
            preg_match('/\baddress\b/i', $message_lower)) {
            return true;
        }
        
        // Everything else goes to OpenAI for natural language processing
        return false;
    }
    
    /**
     * Get AI-enhanced response using OpenAI for natural conversation
     */
    private function get_ai_enhanced_response($message, $session_id = '', $action_type = '') {
        // Initialize API integrations
        if (!class_exists('EduBot_API_Integrations')) {
            error_log('EduBot: API Integrations class not found, falling back to rule-based');
            return $this->provide_intelligent_fallback($message, $action_type, $session_id);
        }
        
        $api_integrations = new EduBot_API_Integrations();
        
        // Build context for OpenAI
        $context = $this->build_ai_context($session_id, $action_type);
        
        // Get AI response
        $ai_response = $api_integrations->get_ai_response($message, $context);
        
        // Handle errors gracefully
        if (is_wp_error($ai_response)) {
            error_log('EduBot: AI Error - ' . $ai_response->get_error_message());
            error_log('EduBot: Falling back to rule-based system');
            return $this->provide_intelligent_fallback($message, $action_type, $session_id);
        }
        
        // Post-process AI response to add action buttons and school-specific information
        return $this->enhance_ai_response($ai_response, $message, $session_id);
    }
    
    /**
     * Build context for AI to understand the school and conversation state
     */
    private function build_ai_context($session_id = '', $action_type = '') {
        $settings = get_option('edubot_pro_settings', array());
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        $school_name = $config['school_info']['name'] ?? $settings['school_name'] ?? 'Epistemo Vikas Leadership School';
        
        // Get session data for conversation context
        $session_data = $this->get_conversation_session($session_id);
        $collected_data = $session_data ? $session_data['data'] : array();
        
        $context = "You are an AI assistant for {$school_name}, a premium educational institution. ";
        $context .= "You help parents with school inquiries, admissions, and provide information about our programs. ";
        
        // Add school information
        if (!empty($settings['phone'])) {
            $context .= "Our contact number is {$settings['phone']}. ";
        }
        if (!empty($settings['email'])) {
            $context .= "Our email is {$settings['email']}. ";
        }
        if (!empty($settings['address'])) {
            $context .= "We are located at {$settings['address']}. ";
        }
        
        $context .= "\n\nAvailable programs and boards:\n";
        $context .= "- CBSE (Central Board of Secondary Education)\n";
        $context .= "- CAIE/Cambridge (Cambridge Assessment International Education)\n";
        $context .= "- State Board\n";
        $context .= "- ICSE (Indian Certificate of Secondary Education)\n";
        $context .= "- IGCSE (International General Certificate of Secondary Education)\n";
        $context .= "- International Board/IB\n";
        
        // Get available grades from backend configuration for AI context
        $grades_offered = "Nursery, PP1, PP2, Grade 1-12"; // Default fallback
        try {
            if (class_exists('Edubot_Academic_Config')) {
                $school_id = 1; // Default school ID
                $available_grades = Edubot_Academic_Config::get_available_grades_for_admission($school_id);
                
                if (!empty($available_grades)) {
                    $grade_list = array();
                    foreach ($available_grades as $grade_key => $grade_name) {
                        $grade_list[] = $grade_name;
                    }
                    $grades_offered = implode(', ', $grade_list);
                }
            }
        } catch (Exception $e) {
            error_log('EduBot AI Context Grade Error: ' . $e->getMessage());
        }
        
        $context .= "\n\nGrades offered: {$grades_offered}\n";
        
        $context .= "\n\nKey features:\n";
        $context .= "- Modern infrastructure with smart classrooms\n";
        $context .= "- Experienced faculty\n";
        $context .= "- State-of-the-art laboratories\n";
        $context .= "- Sports facilities\n";
        $context .= "- Arts and cultural programs\n";
        $context .= "- Safe transportation\n";
        $context .= "- Nutritious meal programs\n";
        
        // Add conversation context if available
        if (!empty($collected_data)) {
            $context .= "\n\nCurrent conversation context:\n";
            if (!empty($collected_data['student_name'])) {
                $context .= "- Student name: {$collected_data['student_name']}\n";
            }
            if (!empty($collected_data['grade'])) {
                $context .= "- Interested in grade: {$collected_data['grade']}\n";
            }
            if (!empty($collected_data['board'])) {
                $context .= "- Preferred board: {$collected_data['board']}\n";
            }
        }
        
        $context .= "\n\nImportant guidelines:\n";
        $context .= "- Be helpful, friendly, and professional\n";
        $context .= "- If someone wants to start admission process, encourage them to click 'Admission' or type 'admission'\n";
        $context .= "- For specific fee information, provide general ranges but suggest contacting for detailed discussion\n";
        $context .= "- Always offer to help with admission enquiry if appropriate\n";
        $context .= "- Keep responses concise but informative\n";
        $context .= "- Use emojis appropriately to make conversation friendly\n";
        $context .= "- If asked about complex admission requirements, guide them to start the admission process\n";
        
        return $context;
    }
    
    /**
     * Enhance AI response with school-specific elements and action prompts
     */
    private function enhance_ai_response($ai_response, $original_message, $session_id = '') {
        $settings = get_option('edubot_pro_settings', array());
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        $school_name = $config['school_info']['name'] ?? $settings['school_name'] ?? 'Epistemo Vikas Leadership School';
        
        // Add action buttons if response doesn't already contain strong calls to action
        $message_lower = strtolower($original_message);
        $response_lower = strtolower($ai_response);
        
        $enhanced_response = $ai_response;
        
        // Add admission prompt for relevant queries
        if ((strpos($message_lower, 'admission') !== false || 
             strpos($message_lower, 'join') !== false ||
             strpos($message_lower, 'enroll') !== false ||
             strpos($message_lower, 'apply') !== false) &&
            strpos($response_lower, 'admission') === false) {
            
            $enhanced_response .= "\n\n🎓 **Ready to start the admission process?**\n";
            $enhanced_response .= "• Click **'Admission'** below to begin\n";
            $enhanced_response .= "• Type **'admission'** to start your enquiry";
        }
        
        // Add contact information for specific requests
        if (strpos($message_lower, 'contact') !== false || 
            strpos($message_lower, 'phone') !== false ||
            strpos($message_lower, 'call') !== false) {
            
            if (strpos($response_lower, 'contact') === false) {
                $enhanced_response .= "\n\n📞 **Contact Information:**\n";
                $enhanced_response .= "• Phone: " . ($settings['phone'] ?? '+91-80-12345678') . "\n";
                $enhanced_response .= "• Email: " . ($settings['email'] ?? 'info@school.edu');
            }
        }
        
        // Add visit scheduling for tour requests
        if (strpos($message_lower, 'visit') !== false || 
            strpos($message_lower, 'tour') !== false ||
            strpos($message_lower, 'see') !== false) {
            
            if (strpos($response_lower, 'visit') === false) {
                $enhanced_response .= "\n\n🏫 **Schedule a Campus Visit:**\n";
                $enhanced_response .= "• Call us to book a personalized tour\n";
                $enhanced_response .= "• Visit timings: Mon-Fri (9 AM - 4 PM), Sat (9 AM - 1 PM)";
            }
        }
        
        return $enhanced_response;
    }
    
    /**
     * Parse comprehensive admission information from natural language
     */
    private function parse_comprehensive_admission_info($message, $session_id = '') {
        $info = array();
        $extracted_data = array();
        $message_lower = strtolower($message);
        
        // Extract student name from patterns like "for my son Sujay", "my daughter Priya", "child named Alex"
        // Extract student name from patterns - handle specific cases first
        $student_name = '';
        
        // Pattern 1: "for my son/daughter/child NAME for/in CLASS" (handle typos like "sun")
        if (preg_match('/for my (?:son|sun|daughter|child)\s+([a-zA-Z]+)\s+for/i', $message, $matches)) {
            $student_name = ucfirst(trim($matches[1]));
        }
        // Pattern 2: "my son/daughter/child NAME needs/wants"
        elseif (preg_match('/my (?:son|sun|daughter|child)\s+([a-zA-Z]+)\s+(?:needs|wants|requires)/i', $message, $matches)) {
            $student_name = ucfirst(trim($matches[1]));
        }
        // Pattern 3: "my daughter/son NAME"
        elseif (preg_match('/my (?:son|sun|daughter|child)\s+([a-zA-Z]+)/i', $message, $matches)) {
            $student_name = ucfirst(trim($matches[1]));
        }
        // Pattern 4: "child named NAME"
        elseif (preg_match('/child named\s+([a-zA-Z]+)/i', $message, $matches)) {
            $student_name = ucfirst(trim($matches[1]));
        }
        // Pattern 5: "admission for NAME" (but not "admission for my")
        elseif (preg_match('/admission for\s+([a-zA-Z]+)(?:\s|$)/i', $message, $matches)) {
            $potential_name = ucfirst(trim($matches[1]));
            if (!in_array(strtolower($potential_name), array('my', 'his', 'her', 'the', 'a', 'an'))) {
                $student_name = $potential_name;
            }
        }
        
        if (!empty($student_name) && strlen($student_name) >= 2 && strlen($student_name) <= 20) {
            $extracted_data['student_name'] = $student_name;
        }
        
        // Extract grade/class information (handle typos like "nursary", "grde")
        $grade_patterns = array(
            '/\b(nursery|nursary|pre-?kg|lkg|ukg)\b/i',
            '/\b(?:gr[ae]de?|class)\s*(\d+)\b/i',
            '/\b(\d+)(?:st|nd|rd|th)\s*(?:gr[ae]de?|class)?\b/i'
        );
        
        foreach ($grade_patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                if (isset($matches[1])) {
                    $grade = ucfirst(strtolower($matches[0]));
                    // Normalize grade names (handle typos)
                    if (stripos($grade, 'nursery') !== false || stripos($grade, 'nursary') !== false) $grade = 'Nursery';
                    elseif (stripos($grade, 'pre') !== false) $grade = 'Pre-KG';
                    elseif (stripos($grade, 'lkg') !== false) $grade = 'LKG';
                    elseif (stripos($grade, 'ukg') !== false) $grade = 'UKG';
                    elseif (preg_match('/(\d+)/', $grade, $num_match)) {
                        $grade = 'Grade ' . $num_match[1];
                    }
                    $extracted_data['grade'] = $grade;
                    break;
                }
            }
        }
        
        // Extract academic year
        if (preg_match('/\b(20\d{2}[-\/]?\d{2})\b/', $message, $year_matches)) {
            $year = $year_matches[1];
            // Normalize year format
            if (strpos($year, '-') === false && strpos($year, '/') === false) {
                // Convert 202525 to 2025-25
                if (strlen($year) == 6) {
                    $year = substr($year, 0, 4) . '-' . substr($year, 4, 2);
                }
            }
            $extracted_data['academic_year'] = $year;
        }
        
        // If we extracted any information, store it and provide appropriate response
        if (!empty($extracted_data)) {
            error_log("EduBot Debug: Extracted data from message: " . print_r($extracted_data, true));
            
            // Store information in session
            foreach ($extracted_data as $key => $value) {
                error_log("EduBot Debug: About to store {$key} = {$value} in session {$session_id}");
                $this->update_conversation_data($session_id, $key, $value);
            }
            
            // Get current session data and verify it was stored
            $session_data = $this->get_conversation_session($session_id);
            $collected_data = $session_data ? $session_data['data'] : array();
            
            error_log("EduBot Debug: After storage, session data: " . print_r($session_data, true));
            
            // Build response showing what was collected and what's still needed
            $response = "✅ **Information Recorded from Your Request:**\n";
            
            // Debug: Show what's actually in collected_data
            if (empty($collected_data)) {
                $response .= "\n⚠️ DEBUG: Session data is empty for session: " . $session_id . "\n";
                error_log("EduBot Debug: CRITICAL - Session data is empty after storage attempt!");
            } else {
                $response .= "\n📋 DEBUG: Session contains:\n";
                foreach ($collected_data as $key => $value) {
                    $response .= "  • {$key} => {$value}\n";
                }
                error_log("EduBot Debug: SUCCESS - Session data stored successfully");
            }
            
            if (!empty($collected_data['student_name'])) {
                $response .= "👶 **Student Name:** {$collected_data['student_name']}\n";
            }
            if (!empty($collected_data['grade'])) {
                $response .= "🎓 **Grade:** {$collected_data['grade']}\n";
            }
            if (!empty($collected_data['academic_year'])) {
                $response .= "📅 **Academic Year:** {$collected_data['academic_year']}\n";
            }
            
            $response .= "\n";
            
            // Determine what's still needed
            $missing_personal = array();
            $missing_academic = array();
            
            if (empty($collected_data['email'])) $missing_personal[] = "📧 Email Address";
            if (empty($collected_data['phone'])) $missing_personal[] = "📱 Phone Number";
            if (empty($collected_data['grade'])) $missing_academic[] = "🎓 Grade/Class";
            if (empty($collected_data['board'])) $missing_academic[] = "📚 Board Preference";
            
            if (!empty($missing_personal)) {
                $response .= "**Step 1: Contact Information Needed**\n";
                foreach ($missing_personal as $field) {
                    $response .= "• {$field}\n";
                }
                $response .= "\nPlease provide your email and phone number:\n";
                $response .= "Example: Email: parent@email.com, Phone: 9876543210\n";
            } elseif (!empty($missing_academic)) {
                $response .= "**Step 2: Academic Information Needed**\n";
                foreach ($missing_academic as $field) {
                    $response .= "• {$field}\n";
                }
                
                if (empty($collected_data['board'])) {
                    $response .= "\n**Available Boards:**\n";
                    try {
                        if (class_exists('EduBot_School_Config')) {
                            $school_config = EduBot_School_Config::getInstance();
                            $enabled_boards = $school_config->get_enabled_boards();
                            $configured_boards = $school_config->get_configured_boards();
                            $boards_to_show = !empty($enabled_boards) ? $enabled_boards : $configured_boards;
                            
                            foreach ($boards_to_show as $board) {
                                $response .= "• **{$board['code']}** ({$board['name']})\n";
                            }
                        }
                    } catch (Exception $e) {
                        $response .= "• **CBSE** • **CAIE** • **State Board**\n";
                    }
                    $response .= "\nExample: CBSE or Grade 1 CAIE\n";
                }
            } else {
                // All basic info collected, move to final details
                $this->update_conversation_data($session_id, 'step', 'final');
                $response .= "**Step 3: Final Details** 📋\n\n";
                $response .= "Please provide:\n\n";
                $response .= "**Student's Date of Birth** (dd/mm/yyyy format)\n\n";
                $response .= "**Example:**\n";
                $response .= "• 16/10/2010\n\n";
                $response .= "Please enter the date of birth in dd/mm/yyyy format only.\n";
            }
            
            return array(
                'extracted_data' => $extracted_data,
                'response' => $response
            );
        }
        
        return array('extracted_data' => array(), 'response' => '');
    }
    
    private function extract_grade_from_message($message) {
        $message_lower = strtolower($message);
        
        // Try to get grades from backend configuration first
        try {
            if (class_exists('Edubot_Academic_Config')) {
                $school_id = 1; // Default school ID, you might want to make this configurable
                $available_grades = Edubot_Academic_Config::get_available_grades_for_admission($school_id);
                
                // Check if the message matches any configured grades
                // Sort grades by length (longer first) to prevent partial matches
                $sorted_grades = $available_grades;
                uksort($sorted_grades, function($a, $b) {
                    return strlen($b) - strlen($a);
                });
                
                foreach ($sorted_grades as $grade_key => $grade_name) {
                    $grade_name_lower = strtolower($grade_name);
                    $grade_key_lower = strtolower($grade_key);
                    
                    // Check for exact matches first, then word boundary matches
                    if ($message_lower === $grade_name_lower || $message_lower === $grade_key_lower) {
                        return $grade_name;
                    }
                    
                    // Check for word boundary matches to prevent partial matching
                    if (preg_match('/\b' . preg_quote($grade_name_lower, '/') . '\b/', $message_lower) ||
                        preg_match('/\b' . preg_quote($grade_key_lower, '/') . '\b/', $message_lower)) {
                        return $grade_name;
                    }
                    
                    // Special handling for common variations
                    if ($grade_key_lower === 'pp1' && (stripos($message_lower, 'pp1') !== false || stripos($message_lower, 'pp 1') !== false)) {
                        return $grade_name;
                    }
                    if ($grade_key_lower === 'pp2' && (stripos($message_lower, 'pp2') !== false || stripos($message_lower, 'pp 2') !== false)) {
                        return $grade_name;
                    }
                }
            }
        } catch (Exception $e) {
            error_log('EduBot Grade Extraction Error: ' . $e->getMessage());
        }
        
        // Fallback to hardcoded patterns if backend config fails
        
        // Check for Grade 11 with streams first (more specific matches)
        if (preg_match('/grade\s*11\s*science/i', $message_lower)) {
            return 'Grade 11 Science';
        }
        if (preg_match('/grade\s*11\s*commerce/i', $message_lower)) {
            return 'Grade 11 Commerce';
        }
        if (preg_match('/grade\s*11\s*humanities/i', $message_lower)) {
            return 'Grade 11 Humanities';
        }
        
        if (stripos($message_lower, 'nursery') !== false) {
            return 'Nursery';
        }
        if (stripos($message_lower, 'pp1') !== false) {
            return 'PP1';
        }
        if (stripos($message_lower, 'pp2') !== false) {
            return 'PP2';
        }
        if (stripos($message_lower, 'pre-kg') !== false || stripos($message_lower, 'prekg') !== false) {
            return 'Pre-KG';
        }
        if (stripos($message_lower, 'lkg') !== false) {
            return 'LKG';
        }
        if (stripos($message_lower, 'ukg') !== false) {
            return 'UKG';
        }
        
        // Extract grade numbers (handle typos and variations)
        // Handle "Grde10", "Grade10", "grade 10", etc.
        if (preg_match('/(?:gr[ae]de?|class)\s*(\d+)/i', $message, $matches)) {
            return 'Grade ' . $matches[1];
        }
        
        // Handle standalone numbers after "grade/class" keywords
        if (preg_match('/(?:grade|class|grde|grd)\s*(\d+)/i', $message, $matches)) {
            return 'Grade ' . $matches[1];
        }
        
        // Handle ordinal numbers like "10th", "5th", etc.
        if (preg_match('/(\d+)(th|st|nd|rd)/i', $message, $matches)) {
            return 'Grade ' . $matches[1];
        }
        
        // Handle just numbers when in grade context (last resort)
        if (preg_match('/\b(\d{1,2})\b/', $message, $matches)) {
            $grade_num = intval($matches[1]);
            if ($grade_num >= 1 && $grade_num <= 12) {
                return 'Grade ' . $grade_num;
            }
        }
        
        return 'Selected Grade';
    }
    
    private function extract_board_from_message($message) {
        $message_lower = strtolower($message);
        
        if (stripos($message_lower, 'cbse') !== false) {
            return 'CBSE';
        }
        if (stripos($message_lower, 'caie') !== false || stripos($message_lower, 'cambridge') !== false) {
            return 'CAIE';
        }
        if (stripos($message_lower, 'state') !== false) {
            return 'State Board';
        }
        if (stripos($message_lower, 'icse') !== false) {
            return 'ICSE';
        }
        if (stripos($message_lower, 'igcse') !== false) {
            return 'IGCSE';
        }
        if (stripos($message_lower, 'international') !== false || 
            stripos($message_lower, 'ib') !== false) {
            return 'International Board';
        }
        if (stripos($message_lower, 'bse') !== false || stripos($message_lower, 'telangana') !== false) {
            return 'BSE Telangana';
        }
        
        return 'Selected Board';
    }
}

// Initialize the shortcode handler
new EduBot_Shortcode();
