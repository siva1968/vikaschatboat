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
        
        // CRITICAL: Capture UTM parameters at the EARLIEST possible hook
        // This must happen before any output is sent (before headers are sent)
        add_action('plugins_loaded', array($this, 'capture_utm_to_cookies'), 1);
        
        // Set WordPress timezone to Indian Standard Time for the school
        add_action('init', array($this, 'set_indian_timezone'));
        
        add_action('init', array($this, 'init_shortcode'), 15); // Higher priority to override public class
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_ajax_edubot_submit_application', array($this, 'handle_application_submission'));
        add_action('wp_ajax_nopriv_edubot_submit_application', array($this, 'handle_application_submission'));
        add_action('wp_ajax_edubot_chatbot_response', array($this, 'handle_chatbot_response'));
        add_action('wp_ajax_nopriv_edubot_chatbot_response', array($this, 'handle_chatbot_response'));
        
        // Add new AJAX handlers for enhanced flow management
        add_action('wp_ajax_edubot_start_flow', array($this, 'handle_start_flow'));
        add_action('wp_ajax_nopriv_edubot_start_flow', array($this, 'handle_start_flow'));
        add_action('wp_ajax_edubot_get_user_flows', array($this, 'handle_get_user_flows'));
        add_action('wp_ajax_nopriv_edubot_get_user_flows', array($this, 'handle_get_user_flows'));
    }
    
    /**
     * Debug logging helper
     */
    private function debug_log($message) {
        if ($this->debug_enabled) {
            error_log("EduBot Debug: " . $message);
        }
    }
    
    /**
     * Get Indian Standard Time (IST) formatted date
     * @param string $format PHP date format string (default: 'F j, Y g:i A')
     * @return string Formatted date in IST
     */
    private function get_indian_time($format = 'F j, Y g:i A') {
        // Set timezone to Indian Standard Time (IST)
        $ist_timezone = new DateTimeZone('Asia/Kolkata');
        $date = new DateTime('now', $ist_timezone);
        return $date->format($format);
    }
    
    /**
     * Get user-friendly IST time for chatbot responses
     * @return string Formatted time like "2:30 PM IST, September 7, 2025"
     */
    private function get_chatbot_friendly_time() {
        return $this->get_indian_time('g:i A \I\S\T, F j, Y');
    }
    
    /**
     * Set WordPress timezone to Indian Standard Time
     * This ensures all WordPress functions use IST
     */
    public function set_indian_timezone() {
        // Set WordPress timezone to India (IST)
        if (get_option('timezone_string') !== 'Asia/Kolkata') {
            update_option('timezone_string', 'Asia/Kolkata');
            
            // Also set the GMT offset as backup
            update_option('gmt_offset', 5.5); // IST is UTC+5:30
        }
    }
    
    /**
     * Capture UTM parameters to cookies at the earliest possible hook
     * This must run before any output is sent to avoid "headers already sent" error
     * Called on 'plugins_loaded' hook with priority 1 (earliest)
     * 
     * IMPORTANT: This captures UTM to 30-day cookies so even if user returns
     * after 1 month, we can still retrieve their original campaign source
     */
    public function capture_utm_to_cookies() {
        // Only process if there are URL parameters
        if (empty($_GET)) {
            error_log("EduBot capture_utm_to_cookies: No GET parameters, skipping");
            return;
        }
        
        error_log("EduBot capture_utm_to_cookies: Starting UTM capture");
        
        // Start session if needed
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        // UTM parameters and click IDs to capture
        $utm_params_to_capture = array(
            // Standard UTM parameters
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
            // Click IDs from major platforms
            'gclid', 'fbclid', 'msclkid', 'ttclid', 'twclid', 
            '_kenshoo_clickid', 'irclickid', 'li_fat_id', 'sc_click_id', 'yclid'
        );
        
        // 30 days expiration
        $cookie_lifetime = time() + (30 * 24 * 60 * 60);
        $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        
        $utm_captured = false;
        $cookies_set = 0;
        
        foreach ($utm_params_to_capture as $param) {
            if (isset($_GET[$param]) && !empty($_GET[$param])) {
                $param_value = sanitize_text_field($_GET[$param]);
                
                // Store in session (for immediate use)
                $_SESSION['edubot_' . $param] = $param_value;
                
                // Store in cookie (for 30-day persistence)
                // Try setcookie with error suppression
                $cookie_set = @setcookie(
                    'edubot_' . $param,           // Cookie name
                    $param_value,                 // Cookie value
                    $cookie_lifetime,             // Expires in 30 days
                    '/',                          // Path: entire site
                    $domain,                      // Domain
                    $secure,                      // Secure (HTTPS only if applicable)
                    true                          // HttpOnly (JavaScript can't access)
                );
                
                if ($cookie_set) {
                    $cookies_set++;
                    error_log("EduBot: Successfully set cookie 'edubot_{$param}' = '{$param_value}'");
                } else {
                    error_log("EduBot: FAILED to set cookie 'edubot_{$param}' - headers may have already been sent");
                }
                
                $utm_captured = true;
            }
        }
        
        error_log("EduBot capture_utm_to_cookies: Found {$utm_captured} UTM params, set {$cookies_set} cookies");
        
        // Store capture timestamp in both session and cookie
        if ($utm_captured) {
            $captured_at = current_time('mysql');
            
            $_SESSION['edubot_utm_captured_at'] = $captured_at;
            
            @setcookie(
                'edubot_utm_captured_at',
                $captured_at,
                $cookie_lifetime,
                '/',
                $domain,
                $secure,
                true
            );
            
            error_log("EduBot: UTM capture timestamp stored: {$captured_at}");
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
            EDUBOT_PRO_VERSION . '.' . time() // Force cache refresh
        );
        
        // Add inline styles with current colors to ensure they override any cached CSS
        $primary_color = get_option('edubot_primary_color', '#4facfe');
        $secondary_color = get_option('edubot_secondary_color', '#00f2fe');
        
        $custom_css = "
        :root {
            --edubot-primary-color: {$primary_color} !important;
            --edubot-secondary-color: {$secondary_color} !important;
            --edubot-gradient: linear-gradient(135deg, {$primary_color} 0%, {$secondary_color} 100%) !important;
        }
        .edubot-chatbot-widget {
            --edubot-primary-color: {$primary_color} !important;
            --edubot-secondary-color: {$secondary_color} !important;
            --edubot-gradient: linear-gradient(135deg, {$primary_color} 0%, {$secondary_color} 100%) !important;
        }
        ";
        
        wp_add_inline_style('edubot-frontend', $custom_css);
        
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
        // UTM parameters are already captured to cookies in capture_utm_to_cookies()
        // which runs on 'plugins_loaded' hook before any output
        // We just ensure session is started for this request
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set the new welcome message format
        $new_welcome_message = "Hello! Welcome to Vikas The Concept School. We are currently accepting applications for AY 2026–27.\n\nHow can I help you today?\n\n1. Admission Enquiry\n2. Curriculum & Classes\n3. Facilities\n4. Contact / Visit School\n5. Online Enquiry Form";
        
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
        $school_name = $config['school_info']['name'] ?? $settings['school_name'] ?? 'Vikas The Concept School';
        
        // Get colors directly from WordPress options (where admin settings are saved)
        $primary_color = get_option('edubot_primary_color', '#4facfe');
        $secondary_color = get_option('edubot_secondary_color', '#00f2fe');
        
        // Log the colors being used for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('EduBot Chatbot Colors: Primary=' . $primary_color . ', Secondary=' . $secondary_color);
        }
        
        $colors = array(
            'primary' => $primary_color,
            'secondary' => $secondary_color
        );
        
        ob_start();
        ?>
        <!-- Force color override with unique cache-busting ID -->
        <style id="edubot-colors-<?php echo time(); ?>">
            #edubot-chatbot .quick-action,
            .edubot-chatbot .quick-action,
            .quick-action[data-action] {
                background: <?php echo esc_attr($colors['primary']); ?> !important;
                border-color: <?php echo esc_attr($colors['primary']); ?> !important;
            }
            #edubot-chatbot .quick-action:hover,
            .edubot-chatbot .quick-action:hover,
            .quick-action[data-action]:hover {
                background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%) !important;
            }
            #edubot-chatbot .edubot-chat-button,
            .edubot-chatbot .edubot-chat-button {
                background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%) !important;
            }
            #edubot-chatbot .chat-header,
            .edubot-chatbot .chat-header {
                background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%) !important;
            }
        </style>
        <div id="edubot-chatbot" 
             class="edubot-chatbot theme-<?php echo esc_attr($atts['theme']); ?> position-<?php echo esc_attr($atts['position']); ?>"
             data-primary-color="<?php echo esc_attr($colors['primary']); ?>"
             data-secondary-color="<?php echo esc_attr($colors['secondary']); ?>"
             data-version="<?php echo EDUBOT_PRO_VERSION; ?>">
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
                                <button class="quick-action" data-action="admission" style="background: <?php echo esc_attr($colors['primary']); ?> !important; border-color: <?php echo esc_attr($colors['primary']); ?> !important;">1. Admission Enquiry</button>
                                <button class="quick-action" data-action="curriculum" style="background: <?php echo esc_attr($colors['primary']); ?> !important; border-color: <?php echo esc_attr($colors['primary']); ?> !important;">2. Curriculum & Classes</button>
                                <button class="quick-action" data-action="facilities" style="background: <?php echo esc_attr($colors['primary']); ?> !important; border-color: <?php echo esc_attr($colors['primary']); ?> !important;">3. Facilities</button>
                                <button class="quick-action" data-action="contact_visit" style="background: <?php echo esc_attr($colors['primary']); ?> !important; border-color: <?php echo esc_attr($colors['primary']); ?> !important;">4. Contact / Visit School</button>
                                <button class="quick-action" data-action="online_enquiry" style="background: <?php echo esc_attr($colors['primary']); ?> !important; border-color: <?php echo esc_attr($colors['primary']); ?> !important;">5. Online Enquiry Form</button>
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
            background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%) !important;
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
            background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%) !important;
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
            background: <?php echo esc_attr($colors['primary']); ?> !important;
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
            background: <?php echo esc_attr($colors['primary']); ?> !important;
            color: white;
            border-color: <?php echo esc_attr($colors['primary']); ?> !important;
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
        // Robust nonce verification with detailed logging
        $nonce = $_POST['nonce'] ?? '';
        
        // Log nonce details for debugging
        error_log('EduBot: AJAX request received');
        error_log('EduBot: Nonce provided: ' . (!empty($nonce) ? 'Yes (length: ' . strlen($nonce) . ')' : 'No'));
        
        // Verify nonce
        $nonce_verified = wp_verify_nonce($nonce, 'edubot_nonce');
        error_log('EduBot: Nonce verification result: ' . ($nonce_verified ? 'Valid' : 'Invalid'));
        
        if (!$nonce_verified) {
            error_log('EduBot: Nonce verification failed - possible causes: expired, invalid action, or missing nonce');
            // Send error but be helpful
            wp_send_json_error(array(
                'message' => 'Security check failed. Please refresh the page and try again.',
                'code' => 'nonce_verification_failed'
            ));
            return;
        }

        // Input sanitization
        $message = sanitize_text_field($_POST['message'] ?? '');
        $action_type = sanitize_text_field($_POST['action_type'] ?? '');
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        
        // Generate session ID if not provided
        if (empty($session_id)) {
            $session_id = wp_generate_uuid4();
        }
        
        // Basic validation
        if (empty($message) && empty($action_type)) {
            wp_send_json_error(array('message' => 'Please provide a message or select an action.'));
            return;
        }
        
        try {
            error_log("EduBot AJAX: Processing request - Message: '{$message}' | Action: '{$action_type}' | Session: '{$session_id}'");
            
            // DEBUG: Test personal info parsing immediately
            $debug_personal_info = $this->parse_personal_info($message);
            error_log("EduBot DEBUG: Immediate personal info parse result: " . print_r($debug_personal_info, true));
            
            // Use the main response handler that includes personal info detection
            $response = $this->generate_response($message, $action_type, $session_id);
            
            error_log("EduBot AJAX: Generated response type: " . (is_array($response) ? 'array' : 'string'));
            error_log("EduBot AJAX: Response content: " . (is_array($response) ? json_encode($response) : substr($response, 0, 200)));
            
            // DEBUG: Check session state after response generation
            $debug_session = $this->get_conversation_session($session_id);
            error_log("EduBot DEBUG: Session after generation: " . print_r($debug_session, true));
            
            // Ensure response is in the correct format expected by JavaScript
            if (is_array($response)) {
                // Convert old 'response' key to 'message' for compatibility
                if (isset($response['response'])) {
                    $response['message'] = $response['response'];
                    unset($response['response']);
                }
                $action = $response['action'] ?? 'info';
                $final_response = array(
                    'message' => $response['message'] ?? 'Thank you for your message!',
                    'action' => $action,
                    'session_data' => $response['session_data'] ?? array(),
                    'session_id' => $session_id,
                    'quick_actions' => $response['quick_actions'] ?? array(),
                    'options' => $response['options'] ?? array(),
                    'nonce' => wp_create_nonce('edubot_nonce') // Fresh nonce for next request
                );
                // If this is an online enquiry response, always include a URL button option
                if ($action === 'online_enquiry_info' && empty($final_response['options'])) {
                    $final_response['options'] = array(
                        array('type' => 'url', 'url' => 'https://www.vikasconcept.com/enquiry/', 'text' => '🔗 Open Enquiry Form')
                    );
                }
                error_log("EduBot AJAX: Sending array response: " . json_encode($final_response));
                wp_send_json_success($final_response);
            } else {
                // Handle string responses
                $action_str = 'info';
                $options_str = array();
                // If this is an online enquiry action, include URL button even for string responses
                if ($action_type === 'online_enquiry') {
                    $action_str = 'online_enquiry_info';
                    $options_str = array(
                        array('type' => 'url', 'url' => 'https://www.vikasconcept.com/enquiry/', 'text' => '🔗 Open Enquiry Form')
                    );
                }
                $final_response = array(
                    'message' => is_string($response) ? $response : 'Thank you for your message!',
                    'action' => $action_str,
                    'session_data' => array(),
                    'session_id' => $session_id,
                    'quick_actions' => array(),  // Ensure quick_actions is always present
                    'options' => $options_str,
                    'nonce' => wp_create_nonce('edubot_nonce') // Fresh nonce for next request
                );
                error_log("EduBot AJAX: Sending string response: " . json_encode($final_response));
                wp_send_json_success($final_response);
            }
        } catch (Exception $e) {
            error_log('EduBot AJAX Error: ' . $e->getMessage());
            error_log('EduBot AJAX Error Stack: ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => 'Sorry, there was an error. Please try again.'));
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
                $school_name = isset($settings['school_name']) ? $settings['school_name'] : 'Vikas The Concept School';
                
                // Get available academic years for dynamic message
                $school_config = EduBot_School_Config::getInstance();
                $available_years = $school_config->get_available_academic_years();
                $years_text = implode(' & ', $available_years);
            
            switch ($action_type) {
                case 'admission':
                    // Initialize session for admission flow - DIRECT APPROACH
                    $this->init_conversation_session($session_id, 'admission');
                    error_log("EduBot: Initialized admission session {$session_id}");
                    return array(
                        'response' => "Hello! **Welcome to {$school_name}.**\n\n" .
                                   "We are currently accepting applications for **AY {$years_text}**.\n\n" .
                                   "I'll help you with your admission enquiry. Please provide:\n\n" .
                                   "👶 **Student Name**\n" .
                                   "📱 **Mobile Number**\n" .
                                   "📧 **Email Address**\n\n" .
                                   "You can type them like:\n" .
                                   "• Name: Sujay\n" .
                                   "• Mobile: 9876543210\n" .
                                   "• Email: parent@email.com\n\n" .
                                   "Or just start with the student's name and I'll ask for the rest step by step.",
                        'action' => 'admission_started',
                        'session_data' => array('session_id' => $session_id, 'step' => 'start', 'flow_type' => 'admission')
                    );
                

                case 'curriculum':
                    // Load configured response or use default
                    $response_text = get_option('edubot_button_curriculum_response', 
                        "📚 **Academic Programs & Curriculum at {$school_name}**\n\n" .
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
                        "Ready to **start your admission enquiry**? Just type '**admission**' or click the admission button!"
                    );
                    
                    // Replace placeholders
                    $response_text = $this->replace_placeholders($response_text);
                    
                    return array(
                        'response' => $response_text,
                        'action' => 'curriculum_info',
                        'session_data' => array()
                    );
                
                case 'facilities':
                    // Load configured response or use default
                    $response_text = get_option('edubot_button_facilities_response',
                        "🏢 **World-Class Facilities at {$school_name}**\n\n" .
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
                        "Would you like to schedule a campus tour to see these facilities?"
                    );
                    
                    // Replace placeholders
                    $response_text = $this->replace_placeholders($response_text);
                    
                    return array(
                        'response' => $response_text,
                        'action' => 'facilities_info',
                        'session_data' => array()
                    );
                
                case 'contact_visit':
                    // Load configured response or use default
                    $response_text = get_option('edubot_button_contact_visit_response',
                        "🏫 **Contact / Visit {$school_name}**\n\n" .
                        "You can reach us in the following ways:\n\n" .
                        "📞 **Call Admission Office**\n" .
                        "• 7702800800 / 9248111448\n\n" .
                        "📧 **Email Us**\n" .
                        "• admissions@vikasconcept.com\n\n" .
                        "🏫 **Book a Campus Tour**\n" .
                        "• Schedule a personalized campus visit\n\n" .
                        "📞 **Request a Callback**\n" .
                        "• We'll call you at your convenience\n\n" .
                        "How would you like to connect with us?"
                    );
                    
                    // Replace placeholders
                    $response_text = $this->replace_placeholders($response_text);
                    
                    return array(
                        'response' => $response_text,
                        'action' => 'contact_info',
                        'session_data' => array()
                    );
                

                case 'online_enquiry':
                    // Load configured response or use default
                    $response_text = get_option('edubot_button_online_enquiry_response',
                        "🌐 **Online Enquiry Form**\n\n" .
                        "For your convenience, you can fill out our detailed online enquiry form:\n\n" .
                        "🔗 **Direct Link:** https://www.vikasconcept.com/enquiry/\n\n" .
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
                        "If you prefer, I can also help you with the admission process right here in the chat. Just let me know!"
                    );
                    
                    // Replace placeholders
                    $response_text = $this->replace_placeholders($response_text);
                    
                    return array(
                        'response' => $response_text,
                        'action' => 'online_enquiry_info',
                        'session_data' => array()
                    );
            }
        }
        
        // Check if this is an admission-related message or session
        // Use the new AI-powered Workflow Manager for better validation and UX
        $existing_session = !empty($session_id) ? $this->get_conversation_session($session_id) : null;
        $is_admission_flow = !empty($existing_session) && isset($existing_session['flow_type']) && $existing_session['flow_type'] === 'admission';

        $personal_info = $this->parse_personal_info($message);
        $has_personal_info = !empty($personal_info) && (
            !empty($personal_info['name']) ||
            !empty($personal_info['email']) ||
            !empty($personal_info['phone'])
        );

        // Route to AI-powered workflow manager if admission-related
        if ($is_admission_flow || $has_personal_info) {
            error_log('EduBot: Routing to AI-powered Workflow Manager');

            // CRITICAL FIX: Use existing session ID if provided, only create new if empty
            if (empty($session_id)) {
                $session_id = 'sess_' . uniqid();  // Create fresh session only if none provided
                error_log('EduBot Debug: No session ID provided, created fresh session: ' . $session_id);
            } else {
                error_log('EduBot Debug: Using provided session ID: ' . $session_id);
            }

            // Initialize workflow manager with all dependencies
            if (!class_exists('EduBot_Session_Manager')) {
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-session-manager.php';
            }
            if (!class_exists('EduBot_API_Integrations')) {
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-school-config.php';
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-security-manager.php';
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-api-integrations.php';
            }
            if (!class_exists('EduBot_Workflow_Manager')) {
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-workflow-manager.php';
            }

            $workflow_manager = new EduBot_Workflow_Manager();

            // Process message through workflow manager (includes AI validation)
            $workflow_response = $workflow_manager->process_user_input($message, $session_id);

            error_log('EduBot Debug: Workflow Manager response: ' . substr($workflow_response, 0, 200));

            // Return formatted response
            return array(
                'response' => $workflow_response,
                'action' => 'workflow_processed',
                'session_data' => array('session_id' => $session_id)
            );
        }
        
        // Check if this message contains academic information for existing session
        $academic_info = $this->parse_academic_info($message);
        if (!empty($academic_info) && !empty($session_id)) {
            error_log('EduBot: Message contains academic info, checking for existing session');
            
            $existing_session = $this->get_conversation_session($session_id);
            if ($existing_session && !empty($existing_session['data']['student_name'])) {
                error_log('EduBot Debug: Found existing session with personal info, processing academic info');
                
                $result = $this->handle_admission_flow_safe($message, 'academic_info', $session_id);
                
                if (is_string($result)) {
                    return array(
                        'response' => $result,
                        'action' => 'academic_info_processed',
                        'session_data' => array('session_id' => $session_id)
                    );
                }
                return $result;
            }
        }

        // Process regular text messages with safe fallback
        error_log('EduBot: Processing regular message: ' . substr($message, 0, 30));
        return $this->process_user_message_safely($message, $session_id);
            
        } catch (Exception $e) {
            error_log('EduBot Error in generate_response: ' . $e->getMessage());
            $settings = get_option('edubot_pro_settings', array());
            $school_name = isset($settings['school_name']) ? $settings['school_name'] : 'Vikas The Concept School';
            return array(
                'response' => "Thank you for your interest in {$school_name}! For immediate assistance, please contact our admission office at 7702800800 or email admissions@vikasconcept.com",
                'action' => 'error_fallback',
                'session_data' => array()
            );
        }
    }

    /**
     * Get welcome message for specific flow type
     */
    private function get_flow_welcome_message($flow_type, $topic = null) {
        $settings = get_option('edubot_pro_settings', array());
        $school_name = isset($settings['school_name']) ? $settings['school_name'] : 'Vikas The Concept School';
        
        switch ($flow_type) {
            case 'admission':
                return "Hello! **Welcome to {$school_name}.**\n\n" .
                       "We are currently accepting applications for **AY 2026–27**.\n\n" .
                       "I'll help you with your admission enquiry. Please provide:\n\n" .
                       "👶 **Student Name**\n" .
                       "📱 **Mobile Number**\n" .
                       "📧 **Email Address**\n\n" .
                       "You can type them like:\n" .
                       "• Name: Sujay\n" .
                       "• Mobile: 9876543210\n" .
                       "• Email: parent@email.com\n\n" .
                       "Or just start with the student's name and I'll ask for the rest step by step.";
                       
            case 'information':
                if ($topic === 'curriculum') {
                    return "📚 **Academic Information Request**\n\n" .
                           "I'd be happy to provide detailed information about our curriculum and academic programs.\n\n" .
                           "To send you the most relevant information, please provide:\n" .
                           "• **Your Name**\n" .
                           "• **Email Address**\n" .
                           "• **Specific grade/program of interest**\n\n" .
                           "What specific aspect of our curriculum interests you most?";
                } else {
                    return "ℹ️ **Information Request**\n\n" .
                           "I'll be happy to provide you with detailed information.\n\n" .
                           "Please let me know:\n" .
                           "• **Your Name**\n" .
                           "• **Email Address**\n" .
                           "• **What information you need**\n\n" .
                           "What would you like to know about {$school_name}?";
                }
                
            case 'callback':
                return "📞 **Callback Request**\n\n" .
                       "I'll arrange for our admission counselor to call you back.\n\n" .
                       "Please provide:\n" .
                       "• **Your Name**\n" .
                       "• **Phone Number**\n" .
                       "• **Preferred time for callback**\n\n" .
                       "When would be the best time to reach you?";
                       
            case 'tour':
                return "🏫 **Virtual Tour Request**\n\n" .
                       "I'll help you schedule a virtual tour of our campus.\n\n" .
                       "Please provide:\n" .
                       "• **Your Name**\n" .
                       "• **Email Address**\n" .
                       "• **Phone Number**\n" .
                       "• **Preferred date/time**\n\n" .
                       "When would you like to visit our campus virtually?";
                       
            case 'fees':
                return "💰 **Fee Structure Information**\n\n" .
                       "I'll provide you with detailed fee information.\n\n" .
                       "Please specify:\n" .
                       "• **Your Name**\n" .
                       "• **Email Address**\n" .
                       "• **Grade/Level of interest**\n\n" .
                       "Which grade level are you inquiring about?";
                       
            default:
                return "Hello! How can I assist you with your enquiry about {$school_name}?";
        }
    }

    /**
     * Handle new AJAX endpoint for starting specific flows
     */
    public function handle_start_flow() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'edubot_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            return;
        }

        $flow_type = sanitize_text_field($_POST['flow_type'] ?? '');
        $user_identifier = sanitize_text_field($_POST['user_identifier'] ?? wp_get_current_user()->ID);

        try {
            // Simple session initialization without flow manager
            $session_id = $this->generate_session_id();
            $this->save_session($session_id, array(
                'session_id' => $session_id,
                'flow_type' => $flow_type,
                'started' => current_time('mysql'),
                'step' => 'start',
                'data' => array()
            ));
            
            wp_send_json_success(array(
                'message' => $this->get_flow_welcome_message($flow_type),
                'session_id' => $session_id,
                'flow_type' => $flow_type,
                'step' => 'start'
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Unable to start flow: ' . $e->getMessage()));
        }
    }

    /**
     * Handle AJAX endpoint for getting user's active flows
     */
    public function handle_get_user_flows() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'edubot_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            return;
        }

        $user_identifier = sanitize_text_field($_POST['user_identifier'] ?? wp_get_current_user()->ID);

        try {
            // Simple flow management without complex flow manager
            wp_send_json_success(array(
                'active_flows' => array(),
                'available_flows' => array('admission', 'information', 'callback'),
                'can_start_multiple' => false
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Unable to retrieve flows: ' . $e->getMessage()));
        }
    }
    
    /**
     * Handle admission flow with full AI conversation management
     * Restored from backup with enhanced session handling
     */
    private function handle_admission_flow_safe($message, $action_type = '', $session_id = '') {
        $settings = get_option('edubot_pro_settings', array());
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        $school_name = $config['school_info']['name'] ?? $settings['school_name'] ?? 'Vikas The Concept School';
        $message_lower = strtolower($message);
        
        // Get conversation session data
        $session_data = $this->get_conversation_session($session_id);
        $current_step = $session_data ? ($session_data['step'] ?? '') : '';
        
        error_log("EduBot: Session ID: {$session_id}");
        error_log("EduBot: Session data retrieved: " . print_r($session_data, true));
        error_log("EduBot: Current step: '{$current_step}', Message: '{$message}'");
        
        // If no session data but we have a session ID, log this issue
        if (!empty($session_id) && !$session_data) {
            error_log("EduBot: WARNING - Session ID provided but no session data found!");
        }
        
        // Handle legacy "CONFIRM" messages and variations
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
        
        // Handle academic year selection step
        if ($current_step === 'academic_year') {
            error_log("EduBot: Processing academic year selection - message: {$message}");
            $collected_data = $session_data ? $session_data['data'] : array();
            
            // Get available academic years
            $available_years = $school_config->get_available_academic_years();
            
            // Try to parse year selection from message
            $year_selected = null;
            
            // Check if message is a number (1, 2, etc.)
            if (preg_match('/^\s*(\d+)\s*$/', trim($message), $matches)) {
                $selection_num = intval($matches[1]) - 1;
                if ($selection_num >= 0 && $selection_num < count($available_years)) {
                    $year_selected = $available_years[$selection_num];
                }
            } else {
                // Check if message contains a year directly
                foreach ($available_years as $year) {
                    if (stripos($message, $year) !== false) {
                        $year_selected = $year;
                        break;
                    }
                }
            }
            
            // Validate year selection
            if ($year_selected === null) {
                $year_options = "Please select the admission year:\n\n";
                foreach ($available_years as $idx => $year) {
                    $year_options .= "• " . ($idx + 1) . ": " . $year . "\n";
                }
                
                return "❌ **Invalid Selection**\n\n" .
                       "You entered: {$message}\n\n" .
                       $year_options .
                       "Reply with the number (1, 2, etc.) or the year directly.";
            }
            
            // Save selected academic year
            $this->update_conversation_data($session_id, 'academic_year', $year_selected);
            $collected_data['academic_year'] = $year_selected;
            
            // Move to final step (DOB collection)
            $this->update_conversation_data($session_id, 'step', 'final');
            
            $academic_summary = "• Grade: {$collected_data['grade']}\n• Board: {$collected_data['board']}\n";
            $academic_summary .= "• Academic Year: {$year_selected}\n";
            
            return "✅ **Admission Year: {$year_selected}**\n\n" .
                   $academic_summary .
                   "**Step 3: Final Details** 📋\n\n" .
                   "Please provide:\n\n" .
                   "**Student's Date of Birth** (dd/mm/yyyy format)\n\n" .
                   "**Example:**\n" .
                   "• 16/10/2010\n\n" .
                   "Please enter the date of birth in dd/mm/yyyy format only.";
        }
        
        // Handle final details step (DOB collection)
        if ($current_step === 'final' || $current_step === 'age') {
            error_log("EduBot: Processing final step - current_step: {$current_step}, message: {$message}");
            $collected_data = $session_data ? $session_data['data'] : array();
            
            // Parse the message for DOB
            $additional_info = $this->parse_additional_info($message);
            error_log("EduBot: Additional info parsed: " . print_r($additional_info, true));
            
            // Check for validation errors first - return error immediately without "❌" prefix (already included in error message)
            if (!empty($additional_info['error'])) {
                return $additional_info['error'];
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
        
        // Handle admission enquiry initiation (but only if no existing session with data AND no personal info in current message)
        $session_data = $this->get_conversation_session($session_id);
        $collected_data = $session_data ? $session_data['data'] : array();
        
        // CRITICAL FIX: Don't show welcome message if current message contains personal info
        $current_personal_info = $this->parse_personal_info($message);
        $has_personal_info_now = !empty($current_personal_info) && (
            !empty($current_personal_info['name']) || 
            !empty($current_personal_info['email']) || 
            !empty($current_personal_info['phone'])
        );
        
        if ((strpos($message_lower, 'admission') !== false || 
            strpos($message_lower, 'apply') !== false || 
            strpos($message_lower, 'enroll') !== false ||
            strpos($message_lower, 'join') !== false ||
            $action_type === 'admission') && 
            empty($collected_data) && 
            !$has_personal_info_now) {  // CRITICAL: Don't show welcome if personal info is provided
            
            // Get available academic years for dynamic message
            $school_config = EduBot_School_Config::getInstance();
            $available_years = $school_config->get_available_academic_years();
            $years_text = implode(' & ', $available_years);
            
            // No specific information found, show generic admission welcome
            return "Hello! **Welcome to {$school_name}.**\n\n" .
                   "We are currently accepting applications for **AY {$years_text}**.\n\n" .
                   "Please help me with your:\n\n" .
                   "👶 **Student Name**\n" .
                   "📱 **Mobile Number**\n" .
                   "📧 **Email Address**\n\n" .
                   "You can type them like:\n" .
                   "• Name: Sujay\n" .
                   "• Mobile: 9876543210\n" .
                   "• Email: parent@email.com\n\n" .
                   "Or just start with the student's name and I'll ask for the rest step by step.";
        }
        
        // Handle personal information collection
        $personal_info = $this->parse_personal_info($message);
        error_log("EduBot Debug: Parsed personal info: " . print_r($personal_info, true));
        
        // CRITICAL: Validate phone number if it was extracted AND is marked invalid
        // This happens regardless of whether we have other fields
        if (!empty($personal_info['phone']) && !empty($personal_info['phone_invalid'])) {
            $phone_digit_count = strlen(preg_replace('/[^\d]/', '', $personal_info['phone']));
            
            // Check if phone number is actually valid (10 digits starting with 6-9)
            $phone_digits_only = preg_replace('/[^\d]/', '', $personal_info['phone']);
            if ($phone_digit_count === 10 && preg_match('/^[6-9]/', $phone_digits_only)) {
                // Actually valid - just accept it (don't show error)
                error_log("EduBot: Phone is actually valid despite being marked invalid: " . $personal_info['phone']);
                // Don't return error - let it proceed to be saved
            } else {
                // Actually invalid - ALWAYS show error immediately
                $digit_str = $phone_digit_count === 1 ? 'digit' : 'digits';
                
                // Check if phone contains letters (alphanumeric error)
                if (preg_match('/[a-zA-Z]/', $personal_info['phone'])) {
                    return "❌ **Invalid Phone Number - Contains Letters**\n\n" .
                           "You entered: {$message}\n\n" .
                           "⚠️ Phone numbers should only contain **digits**, not letters.\n\n" .
                           "Please provide the complete 10-digit phone number.";
                }
                
                // Check if it's an incomplete number (less than 10 digits)
                if ($phone_digit_count < 10) {
                    return "❌ **Incomplete Phone Number**\n\n" .
                           "You entered: {$message} ({$phone_digit_count} {$digit_str})\n\n" .
                           "Please provide the complete 10-digit phone number.";
                }
                
                // Other invalid formats (too many digits or doesn't start with 6-9)
                return "❌ **Invalid Phone Number**\n\n" .
                       "You entered: {$message} ({$phone_digit_count} {$digit_str})\n\n" .
                       "Please provide the complete 10-digit phone number.";
            }
        }
        
        // Session data already retrieved above
        error_log("EduBot Debug: Current collected data: " . print_r($collected_data, true));
        
        // Enhanced personal info detection - handle name-only inputs better
        $has_name_only = !empty($personal_info['name']) && 
                        empty($personal_info['email']) && 
                        empty($personal_info['phone']);
        
        // Check if this looks like personal info input
        if (!empty($personal_info) && (
            !empty($personal_info['name']) || 
            !empty($personal_info['email']) || 
            !empty($personal_info['phone'])
        ) && (
            // Only process personal info if we don't have complete personal information yet
            empty($collected_data['student_name']) || 
            empty($collected_data['email']) || 
            empty($collected_data['phone']) ||
            // OR if this is a name-only input and we're starting fresh
            ($has_name_only && empty($collected_data))
        )) {
            
            error_log("EduBot Debug: Processing personal information input");
            
            // Initialize admission session if not already initialized
            if (!$session_data || empty($session_data['flow_type'])) {
                error_log("EduBot Debug: Initializing admission session for personal info collection");
                $this->init_conversation_session($session_id, 'admission');
                $session_data = $this->get_conversation_session($session_id);
                $collected_data = $session_data ? $session_data['data'] : array();
            }
            
            // Store original session state to check if name was already present
            $had_name_before = !empty($collected_data['student_name']);
            
            // Store any collected info with validation
            if (!empty($personal_info['name']) && strlen(trim($personal_info['name'])) >= 2) {
                $this->update_conversation_data($session_id, 'student_name', $personal_info['name']);
                error_log("EduBot Debug: Stored student name: " . $personal_info['name']);
            }
            if (!empty($personal_info['email']) && filter_var($personal_info['email'], FILTER_VALIDATE_EMAIL)) {
                $this->update_conversation_data($session_id, 'email', $personal_info['email']);
                error_log("EduBot Debug: Stored email: " . $personal_info['email']);
            }
            if (!empty($personal_info['parent_name']) && strlen(trim($personal_info['parent_name'])) >= 2) {
                $this->update_conversation_data($session_id, 'parent_name', $personal_info['parent_name']);
                error_log("EduBot Debug: Stored parent name: " . $personal_info['parent_name']);
            }
            // Store phone if it was extracted and NOT marked as invalid
            if (!empty($personal_info['phone'])) {
                if (empty($personal_info['phone_invalid'])) {
                    // Definitely valid - store it
                    $this->update_conversation_data($session_id, 'phone', $personal_info['phone']);
                    error_log("EduBot Debug: Stored valid phone: " . $personal_info['phone']);
                } else {
                    // Marked as invalid - check if it's actually valid before storing
                    $phone_digit_count = strlen(preg_replace('/[^\d]/', '', $personal_info['phone']));
                    $phone_digits_only = preg_replace('/[^\d]/', '', $personal_info['phone']);
                    if ($phone_digit_count === 10 && preg_match('/^[6-9]/', $phone_digits_only)) {
                        // Actually valid - store it anyway
                        $this->update_conversation_data($session_id, 'phone', $personal_info['phone']);
                        error_log("EduBot Debug: Stored phone marked invalid but actually valid: " . $personal_info['phone']);
                    } else {
                        error_log("EduBot Debug: Phone marked invalid and not storing: " . $personal_info['phone']);
                    }
                }
            }
            
            // Always refresh session data to get the latest complete data
            $session_data = $this->get_conversation_session($session_id);
            $collected_data = $session_data && isset($session_data['data']) ? $session_data['data'] : array();
            
            // ENHANCED: Better handling for name-only inputs
            if (!empty($personal_info['name']) && 
                empty($personal_info['email']) && 
                empty($personal_info['phone'])) {
                
                error_log("EduBot Debug: Name-only input detected, checking if we need more info");
                
                // Store the name first
                $this->update_conversation_data($session_id, 'student_name', $personal_info['name']);
                $session_data = $this->get_conversation_session($session_id);
                $collected_data = $session_data && isset($session_data['data']) ? $session_data['data'] : array();
                
                // Check what's still missing
                $missing_fields = array();
                if (empty($collected_data['email'])) $missing_fields[] = "📧 Email Address";
                if (empty($collected_data['phone'])) $missing_fields[] = "📱 Phone Number";
                
                // If we already have email and phone from previous inputs, move to next step
                if (empty($missing_fields)) {
                    error_log("EduBot Debug: All contact info already collected, moving to academic info");
                    $this->update_conversation_data($session_id, 'step', 'academic');
                    return "✅ **Personal Information Complete!**\n\n" .
                           "Perfect! I have your contact details:\n" .
                           "👶 **Student:** {$collected_data['student_name']}\n" .
                           "📧 **Email:** {$collected_data['email']}\n" .
                           "📱 **Phone:** {$collected_data['phone']}\n\n" .
                           "**Step 2: Academic Information** 🎓\n\n" .
                           "Now, what is your child's current grade?\n" .
                           "• **Grade 1-12** (e.g., Grade 10)\n" .
                           "• **Nursery / Play school** (e.g., Nursery, LKG, UKG)\n" .
                           "• **Homeschooled** or other setup\n\n" .
                           "Please mention the grade:";
                }
                
                // Build response based on what's still needed
                $response = "✅ **Student Name: {$personal_info['name']}**\n\n";
                
                if (count($missing_fields) === 1) {
                    // Only one field missing
                    if (!empty($collected_data['phone'])) {
                        // We have phone, only need email
                        $response .= "Great! Now I need your email address:\n\n" .
                                   "📧 **Your Email Address**\n\n" .
                                   "Example: parent@email.com";
                    } else if (!empty($collected_data['email'])) {
                        // We have email, only need phone
                        $response .= "Great! Now I need your phone number:\n\n" .
                                   "📱 **Your Phone Number**\n\n" .
                                   "Example: 9876543210 or +91 9876543210";
                    }
                } else {
                    // Multiple fields missing (should only be email and phone at this point)
                    $response .= "Great! Now I need your contact details:\n\n" .
                               "📧 **Your Email Address**\n" .
                               "📱 **Your Phone Number**\n\n" .
                               "You can enter them like:\n" .
                               "Email: parent@email.com, Phone: 9876543210\n\n" .
                               "Or just enter your email address first.";
                }
                
                error_log("EduBot Debug: Name-only input detected, asking for missing contact details: " . json_encode($missing_fields));
                return $response;
            }
            
            // Check what's still needed
            $missing_fields = array();
            if (empty($collected_data['student_name'])) $missing_fields[] = "👶 Student Name";
            if (empty($collected_data['email'])) $missing_fields[] = "📧 Email Address";
            if (empty($collected_data['phone'])) $missing_fields[] = "📱 Phone Number";
            if (empty($collected_data['parent_name'])) $missing_fields[] = "👨‍👩‍👧 Parent/Guardian Name";
            
            if (!empty($missing_fields)) {
                $response = "✅ **Information Recorded:**\n";
                if (!empty($collected_data['student_name'])) $response .= "• Student: {$collected_data['student_name']}\n";
                if (!empty($collected_data['email'])) $response .= "• Email: {$collected_data['email']}\n";
                if (!empty($collected_data['phone'])) $response .= "• Phone: {$collected_data['phone']}\n";
                if (!empty($collected_data['parent_name'])) $response .= "• Parent: {$collected_data['parent_name']}\n";
                
                $response .= "\n**Still needed:**\n";
                foreach ($missing_fields as $field) {
                    $response .= "• {$field}\n";
                }
                $response .= "\nPlease provide the remaining information.";
                return $response;
            }
            
            // All personal info collected, move to academic info
            $this->update_conversation_data($session_id, 'step', 'academic');
            
            return "✅ **Personal Information Complete!**\n\n" .
                   "Perfect! I have your contact details:\n" .
                   "👶 **Student:** {$collected_data['student_name']}\n" .
                   "👨‍👩‍👧 **Parent:** {$collected_data['parent_name']}\n" .
                   "📧 **Email:** {$collected_data['email']}\n" .
                   "📱 **Phone:** {$collected_data['phone']}\n\n" .
                   "**Step 2: Academic Information** 🎓\n\n" .
                   "Please share:\n" .
                   "• **Grade/Class** seeking admission for\n" .
                   "• **Board Preference** (CBSE/CAIE)\n\n" .
                   "You can type like:\n" .
                   "Grade 5, CBSE\n\n" .
                   "Or just tell me the grade and I'll ask about board preference.";
        }
        
        // Handle academic information (grade and board)
        $academic_info = $this->parse_academic_info($message);
        $session_data = $this->get_conversation_session($session_id);
        $collected_data = $session_data ? $session_data['data'] : array();
        
        // FIXED: Validate grade if extracted
        if (!empty($academic_info['grade'])) {
            // Check if it's a valid grade
            if ($academic_info['grade'] === null) {
                // Invalid grade detected
                return "❌ **Invalid Grade**\n\n" .
                       "You entered: {$message}\n\n" .
                       "We offer admission for:\n" .
                       "**Pre-Primary:** Nursery, PP1, PP2\n" .
                       "**Primary:** Grade 1-5\n" .
                       "**Secondary:** Grade 6-10\n" .
                       "**Senior Secondary:** Grade 11-12\n\n" .
                       "Please enter a valid grade like:\n" .
                       "• Grade 5, CBSE\n" .
                       "• Nursery\n" .
                       "• Grade 10, CAIE\n\n" .
                       "Try again:";
            }
        }
        
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
            // NOTE: Do NOT auto-populate academic_year from parse_academic_info()
            // Year selection must be done explicitly by user when multiple years available
            // Skip: if (!empty($academic_info['academic_year'])) { ... }
            
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
                    $response .= "\n**Available Boards:**\n• **CBSE** • **CAIE**\n";
                }
                
                return $response;
            }
            
            // Set default academic year if not provided
            if (empty($collected_data['academic_year'])) {
                $school_config = EduBot_School_Config::getInstance();
                $available_years = $school_config->get_available_academic_years();
                
                if (count($available_years) > 1) {
                    // Multiple years available - ask parent to select
                    $this->update_conversation_data($session_id, 'step', 'academic_year');
                    
                    $year_options = "Please select the admission year:\n\n";
                    foreach ($available_years as $idx => $year) {
                        $year_options .= "• " . ($idx + 1) . ": " . $year . "\n";
                    }
                    
                    $academic_summary = "• Grade: {$collected_data['grade']}\n• Board: {$collected_data['board']}\n";
                    
                    return "✅ **Academic Information Complete!**\n\n" .
                           $academic_summary . "\n" .
                           "**Step 2a: Select Admission Year** 📚\n\n" .
                           $year_options . "\n" .
                           "Reply with the number (1, 2, etc.)";
                } else {
                    // Single year available - auto-select
                    $academic_year = $available_years[0];
                    $this->update_conversation_data($session_id, 'academic_year', $academic_year);
                    $collected_data['academic_year'] = $academic_year;
                    // Continue to final step with auto-selected year
                }
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
        
        // Handle simple name inputs (like "sujay") - treat as start of admission process
        if (preg_match('/^[a-zA-Z\s\.]{2,50}$/', trim($message)) && 
            !preg_match('/\b(admission|hello|hi|help|info|contact|about|school|curriculum|facility|fee|grade|class|board)\b/i', $message) &&
            strlen(trim($message)) <= 20) {
            
            error_log("EduBot: Detected simple name input: {$message}");
            
            // Initialize admission session if not already initialized
            $session_data = $this->get_conversation_session($session_id);
            if (!$session_data || empty($session_data['flow_type'])) {
                error_log("EduBot Debug: Initializing admission session for simple name input");
                $this->init_conversation_session($session_id, 'admission');
            }
            
            // Treat this as student name and start admission flow
            $this->update_conversation_data($session_id, 'student_name', ucwords(strtolower(trim($message))));
            
            return "✅ **Student Name: " . ucwords(strtolower(trim($message))) . "**\n\n" .
                   "Great! Now I need your contact details:\n\n" .
                   "📧 **Your Email Address**\n" .
                   "📱 **Your Phone Number**\n\n" .
                   "You can enter them like:\n" .
                   "Email: parent@email.com, Phone: 9876543210\n\n" .
                   "Or just enter your email address first.";
        }
        
        // Handle simple email inputs when we already have a name in session
        if (preg_match('/^\s*[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\s*$/', trim($message))) {
            $session_data = $this->get_conversation_session($session_id);
            $collected_data = $session_data ? $session_data['data'] : array();
            
            error_log("EduBot Debug: Simple email detected: {$message}");
            error_log("EduBot Debug: Session data for email: " . print_r($collected_data, true));
            
            // Initialize admission session if not already initialized
            if (!$session_data || empty($session_data['flow_type'])) {
                error_log("EduBot Debug: Initializing admission session for email input");
                $this->init_conversation_session($session_id, 'admission');
                $session_data = $this->get_conversation_session($session_id);
                $collected_data = $session_data ? $session_data['data'] : array();
            }
            
            if (!empty($collected_data['student_name']) && empty($collected_data['email'])) {
                error_log("EduBot: Processing email input for existing session with name: {$collected_data['student_name']}");
                
                $email = trim($message);
                $this->update_conversation_data($session_id, 'email', $email);
                
                return "✅ **Email Address: {$email}**\n\n" .
                       "Great! Now I need your phone number:\n\n" .
                       "📱 **Phone Number**\n\n" .
                       "Please enter your 10-digit mobile number.\n\n" .
                       "**Example:** 9876543210";
            }
        }
        
        // Handle simple phone number inputs when we have name and email in session
        // FIXED: Accept phone numbers with 8-15 digits, not just strict 10-digit pattern
        $message_clean = preg_replace('/[^\d+]/', '', trim($message));
        $phone_digit_count = strlen(preg_replace('/[^\d]/', '', $message_clean));
        
        // Check if this looks like a phone number attempt (has at least 8 digits)
        if ($phone_digit_count >= 8 && $phone_digit_count <= 15) {
            $session_data = $this->get_conversation_session($session_id);
            $collected_data = $session_data ? $session_data['data'] : array();
            
            error_log("EduBot Debug: Simple phone detected: {$message} (digits: {$phone_digit_count})");
            error_log("EduBot Debug: Session data for phone: " . print_r($collected_data, true));
            
            if (!empty($collected_data['student_name']) && 
                !empty($collected_data['email']) && 
                empty($collected_data['phone'])) {
                
                error_log("EduBot: Processing phone input for existing session");
                
                $phone = preg_replace('/[^\d+]/', '', trim($message));
                
                // Validate phone number format
                if (!preg_match('/^\+/', $phone) && $phone_digit_count === 10 && preg_match('/^[6-9]/', $phone)) {
                    // Valid 10-digit phone starting with 6-9
                    $phone = '+91' . $phone;
                } elseif (preg_match('/^\+91/', $phone) && $phone_digit_count === 12) {
                    // Already has +91 prefix
                    $phone = substr($phone, 1); // Remove + and add it back later
                    $phone = '+91' . substr($phone, 2);
                } elseif (preg_match('/^91/', $phone) && $phone_digit_count === 12) {
                    // Has 91 without +
                    $phone = '+' . $phone;
                } elseif ($phone_digit_count === 10 && preg_match('/^[6-9]/', $phone)) {
                    // Plain 10 digits starting with 6-9
                    $phone = '+91' . $phone;
                } else {
                    // Invalid phone format
                    $digit_str = $phone_digit_count === 1 ? 'digit' : 'digits';
                    return "❌ **Invalid Phone Number**\n\n" .
                           "You entered: {$message} ({$phone_digit_count} {$digit_str})\n\n" .
                           "📱 Please enter a valid 10-digit mobile number:\n" .
                           "• **Start with:** 6, 7, 8, or 9\n" .
                           "• **Format:** 9876543210 or +91 9876543210\n" .
                           "• **Length:** Exactly 10 digits\n\n" .
                           "Try again:";
                }
                
                $this->update_conversation_data($session_id, 'phone', $phone);
                
                // Move to academic information step
                $this->update_conversation_data($session_id, 'step', 'academic');
                
                return "✅ **Personal Information Complete!**\n\n" .
                       "Perfect! I have your contact details:\n" .
                       "👶 **Student:** {$collected_data['student_name']}\n" .
                       "📧 **Email:** {$collected_data['email']}\n" .
                       "📱 **Phone:** {$phone}\n\n" .
                       "**Step 2: Academic Information** 🎓\n\n" .
                       "Please share:\n" .
                       "• **Grade/Class** seeking admission for\n" .
                       "• **Board Preference** (CBSE/CAIE)\n\n" .
                       "You can type like:\n" .
                       "Grade 5, CBSE\n\n" .
                       "Or just tell me the grade and I'll ask about board preference.";
            }
        }
        
        // Handle academic information (grade and board) after personal info is complete
        $session_data = $this->get_conversation_session($session_id);
        $collected_data = $session_data ? $session_data['data'] : array();
        
        // Check if we have complete personal info and this looks like academic info
        if (!empty($collected_data['student_name']) && 
            !empty($collected_data['email']) && 
            !empty($collected_data['phone']) && 
            (empty($collected_data['grade']) || empty($collected_data['board']))) {
            
            $academic_info = $this->parse_academic_info($message);
            error_log("EduBot Debug: Academic info parsing result: " . print_r($academic_info, true));
            
            // Check if this looks like academic info
            if (!empty($academic_info) || 
                preg_match('/\b(nursery|pp1|pp2|pre-?kg|lkg|ukg|grade|grde|class|\d+th|\d+st|\d+nd|\d+rd|cbse|caie|cambridge|state|icse|igcse)\b/i', strtolower($message))) {
                
                error_log("EduBot: Processing academic information input");
                
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
                        $response .= "\n**Available Boards:**\n• **CBSE** • **CAIE**\n";
                    }
                    
                    return $response;
                }
                
                // Set default academic year if not provided
                if (empty($collected_data['academic_year'])) {
                    $school_config = EduBot_School_Config::getInstance();
                    $academic_year = $school_config->get_default_academic_year();
                    $this->update_conversation_data($session_id, 'academic_year', $academic_year);
                    $collected_data['academic_year'] = $academic_year;
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
        }
        
        // Failsafe: If message looks like date of birth format, try to process it
        // This handles cases where session step might not be set correctly
        if (preg_match('/^\s*(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})\s*$/', trim($message))) {
            error_log("EduBot: Detected date format input: {$message}, checking if we have enough data to process");
            
            $session_data = $this->get_conversation_session($session_id);
            $collected_data = $session_data ? $session_data['data'] : array();
            
            // Check if we have the required data for final submission
            if (!empty($collected_data['student_name']) && 
                !empty($collected_data['email']) && 
                !empty($collected_data['phone']) && 
                !empty($collected_data['grade']) && 
                !empty($collected_data['board'])) {
                
                error_log("EduBot: Have required data, processing date input as DOB");
                
                // Parse the message for DOB
                $additional_info = $this->parse_additional_info($message);
                error_log("EduBot: DOB parsing result: " . print_r($additional_info, true));
                
                // Check for validation errors first - return error immediately (already includes ❌ in error message)
                if (!empty($additional_info['error'])) {
                    return $additional_info['error'];
                }
                
                // Store collected DOB if valid
                if (!empty($additional_info['date_of_birth'])) {
                    $this->update_conversation_data($session_id, 'date_of_birth', $additional_info['date_of_birth']);
                    $collected_data['date_of_birth'] = $additional_info['date_of_birth'];
                    
                    // Automatically generate enquiry number and save to database
                    $this->update_conversation_data($session_id, 'step', 'completed');
                    
                    error_log("EduBot: Processing final submission with failsafe");
                    return $this->process_final_submission($collected_data, $session_id);
                } else {
                    return "Please enter the student's date of birth in **dd/mm/yyyy** format.\n\n" .
                           "**Example:** 16/10/2010\n\n" .
                           "Make sure to use the correct format with 4-digit year.";
                }
            }
        }
        
        // Fall back to the basic message processing for other queries
        return $this->process_user_message_safely($message, $session_id);
    }
    

    
    /**
     * Process user messages safely without external dependencies
     */
    private function process_user_message_safely($message, $session_id) {
        $settings = get_option('edubot_pro_settings', array());
        $school_name = isset($settings['school_name']) ? $settings['school_name'] : 'Vikas The Concept School';
        
        // Check if this message contains personal information and redirect to admission flow
        $personal_info = $this->parse_personal_info($message);
        if (!empty($personal_info) && (
            !empty($personal_info['name']) || 
            !empty($personal_info['email']) || 
            !empty($personal_info['phone'])
        )) {
            error_log('EduBot: Personal info detected in safe mode, redirecting to admission flow');
            
            // If session is completed or doesn't exist, create a new session for new admission enquiry  
            if (empty($session_id) || $this->is_session_completed($session_id)) {
                $session_id = 'sess_' . uniqid();  // Create fresh session
                error_log('EduBot Debug: Created fresh session in safe mode for new personal info: ' . $session_id);
                // Initialize the session properly
                $this->init_conversation_session($session_id, 'admission');
            }
            
            $admission_result = $this->handle_admission_flow_safe($message, 'admission', $session_id);
            
            // Ensure we return the proper array format
            if (is_array($admission_result)) {
                return $admission_result;
            } else {
                return array(
                    'response' => $admission_result,
                    'action' => 'admission_started',
                    'session_data' => array('session_id' => $session_id, 'step' => 'personal_info_received')
                );
            }
        }
        
        // Check if this message contains academic information for existing session
        $academic_info = $this->parse_academic_info($message);
        if (!empty($academic_info) && !empty($session_id)) {
            error_log('EduBot: Academic info detected in safe mode, checking for existing session');
            
            $existing_session = $this->get_conversation_session($session_id);
            if ($existing_session && !empty($existing_session['data']['student_name'])) {
                error_log('EduBot Debug: Found existing session in safe mode, processing academic info');
                
                $admission_result = $this->handle_admission_flow_safe($message, 'academic_info', $session_id);
                
                if (is_array($admission_result)) {
                    return $admission_result;
                } else {
                    return array(
                        'response' => $admission_result,
                        'action' => 'academic_info_processed',
                        'session_data' => array('session_id' => $session_id, 'step' => 'academic_info_received')
                    );
                }
            }
        }
        
        // FALLBACK: Handle potential name-only inputs that might have been missed
        if (preg_match('/^[A-Za-z]{2,20}(\s+[A-Za-z]{2,20})?$/i', trim($message))) {
            error_log('EduBot: Fallback name detection for: ' . $message);
            // This looks like a name (2-40 characters, only letters and spaces, max 2 words)
            return array(
                'response' => "✅ **Student Name: " . trim($message) . "**\n\n" .
                           "Great! Now I need your contact details:\n\n" .
                           "📧 **Your Email Address**\n" .
                           "📱 **Your Phone Number**\n\n" .
                           "You can enter them like:\n" .
                           "Email: parent@email.com, Phone: 9876543210\n\n" .
                           "Or just enter your email address first.",
                'action' => 'name_processed',
                'session_data' => array('student_name' => trim($message), 'step' => 'collect_contact')
            );
        }
        
        // Simple keyword-based responses
        $message_lower = strtolower(trim($message));
        
        if (preg_match('/\b(admission|admissions|admit|enroll|enrollment|join|apply|application)\b/i', $message)) {
            return array(
                'response' => "🎓 I'd be happy to help with admission information! Would you like to start the admission enquiry process?",
                'action' => 'admission_info',
                'session_data' => array()
            );
        }
        
        if (preg_match('/\b(curriculum|academic|program|course|study|subject)\b/i', $message)) {
            return array(
                'response' => "📚 Our curriculum includes CBSE and CAIE boards with comprehensive programs. Would you like to know more about our academic approach?",
                'action' => 'curriculum_info',
                'session_data' => array()
            );
        }
        
        if (preg_match('/\b(fees?|cost|price|tuition|payment)\b/i', $message)) {
            return array(
                'response' => "💰 For detailed fee structure and payment options, our admission counselor will provide complete information. Shall I help you start the admission process?",
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
        
        if (preg_match('/\b(contact|phone|email|address|location)\b/i', $message)) {
            return array(
                'response' => "📞 Contact us at 7702800800 or admissions@vikasconcept.com. Ready to start your admission enquiry?",
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
     * Parse multi-field input to extract name, email, phone, parent_name - ENHANCED
     */
    private function parse_personal_info($message) {
        $info = array();
        $message_clean = trim($message);
        $original_message = $message_clean;
        
        // Initialize phone_invalid flag to false by default
        $info['phone_invalid'] = false;
        
        // Try to extract email first
        if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $message_clean, $email_matches)) {
            $info['email'] = $email_matches[0];
            $message_clean = str_replace($email_matches[0], ' ', $message_clean);
        }
        
        // FIRST: Check for mixed alphanumeric phone attempts (e.g., "986612sasad")
        // These are invalid phone attempts that should be caught
        if (preg_match('/\b(\d{6,15}[a-zA-Z]+|[a-zA-Z]*\d{6,15})\b/', $message_clean, $alphanumeric_matches)) {
            // This is a mixed alphanumeric input that looks like a phone attempt
            $info['phone'] = $alphanumeric_matches[1];  // Store the mixed input
            $info['phone_invalid'] = true;  // Mark as invalid (contains letters)
            $message_clean = str_replace($alphanumeric_matches[1], ' ', $message_clean);
        }
        // Try to extract phone number (flexible - accepts 8-15 digits, including invalid ones)
        // FIXED: Now detects 9-digit and other invalid formats too, not just 10-digit
        elseif (preg_match('/\+?91?[\s-]?[0-9]{8,15}/', $message_clean, $phone_matches)) {
            $phone_raw = preg_replace('/[^\d+]/', '', $phone_matches[0]);
            
            // Store raw phone (may be invalid) - validation happens later
            $digit_count = strlen(preg_replace('/[^\d]/', '', $phone_raw));
            
            // Normalize phone number - even if invalid, store for later validation
            if ($digit_count == 10 && preg_match('/^[6-9]/', $phone_raw)) {
                // Valid 10-digit number
                $info['phone'] = '+91' . $phone_raw;
                $info['phone_invalid'] = false;  // Mark as valid
            } elseif ($digit_count == 12 && substr($phone_raw, 0, 2) == '91') {
                // Has 91 prefix without +
                $info['phone'] = '+' . $phone_raw;
                $info['phone_invalid'] = false;  // Mark as valid
            } elseif ($digit_count == 13 && substr($phone_raw, 0, 3) == '+91') {
                // Already has +91 prefix
                $info['phone'] = $phone_raw;
                $info['phone_invalid'] = false;  // Mark as valid
            } else {
                // INVALID FORMAT - store anyway for downstream validation
                // This includes 9-digit, 11-digit, or numbers starting with 0-5
                $info['phone'] = $phone_raw;  // Store raw for validation
                $info['phone_invalid'] = true;  // Mark as needing validation
            }
            
            // Remove phone number from message for name extraction
            // Use broader pattern to catch all digit sequences
            $message_clean = preg_replace('/\+?91?[\s-]?[0-9]{8,15}/', ' ', $message_clean);
        }
        
        // Try to extract parent_name using "Parent:" or "Guardian:" prefix
        if (preg_match('/\b(?:parent\s*:?\s*|guardian\s*:?\s*)\s*([a-zA-Z\s\.]{2,30})(?:\s|,|$)/i', $original_message, $parent_matches)) {
            $candidate_parent = trim($parent_matches[1]);
            if (strlen($candidate_parent) >= 2 && strlen($candidate_parent) <= 30 && preg_match('/^[a-zA-Z\s\.]+$/', $candidate_parent)) {
                $info['parent_name'] = ucwords(strtolower($candidate_parent));
                // Remove parent name from message for student name extraction
                $message_clean = str_replace($parent_matches[0], ' ', $message_clean);
            }
        }
        
        // Try to extract name from structured input first (Name: format)
        if (preg_match('/\b(?:student\s*:?\s*|name\s*:?\s*)\s*([a-zA-Z\s\.]{2,30})(?:\s|$)/i', $original_message, $name_matches)) {
            $candidate_name = trim($name_matches[1]);
            if (strlen($candidate_name) >= 2 && strlen($candidate_name) <= 30) {
                $info['name'] = ucwords(strtolower($candidate_name));
            }
        }
        
        // If no structured name found, try to extract from remaining text
        if (empty($info['name'])) {
            // Clean the message: remove labels and non-name content
            $name_clean = $message_clean;
            $name_clean = preg_replace('/\s*(student\s*:?\s*|name\s*:?\s*|email\s*:?\s*|phone\s*:?\s*|mobile\s*:?\s*|contact\s*:?\s*|parent\s*:?\s*|guardian\s*:?\s*)/i', ' ', $name_clean);
            $name_clean = preg_replace('/\b(grade\s*\d+|class\s*\d+|cbse|caie|cambridge|icse|igcse)\b/i', ' ', $name_clean);
            $name_clean = preg_replace('/[^\w\s\.]/', ' ', $name_clean);
            $name_clean = preg_replace('/\s+/', ' ', $name_clean);
            $name_clean = trim($name_clean);
            
            // If we have reasonable text left, treat as name
            if (!empty($name_clean) && 
                strlen($name_clean) >= 2 && 
                strlen($name_clean) <= 30 &&
                preg_match('/^[a-zA-Z\s\.]+$/', $name_clean) &&
                !preg_match('/\b(email|phone|mobile|contact|gmail|yahoo|hotmail)\b/i', $name_clean)) {
                $info['name'] = ucwords(strtolower($name_clean));
            }
        }
        
        // FALLBACK: For combined inputs like "Siva prasadmasina@gmail.com +91 9866133566"
        // Extract name from the beginning if email/phone found but no name yet
        if (empty($info['name']) && (!empty($info['email']) || !empty($info['phone']))) {
            // Try to get name from start of original message before email/phone
            $temp_message = $original_message;
            
            // Remove email and phone from original to isolate name
            if (!empty($info['email'])) {
                $temp_message = str_replace($info['email'], '', $temp_message);
            }
            if (!empty($info['phone'])) {
                // Remove various phone formats
                $temp_message = preg_replace('/(\+?91[\s-]?[6-9]\d{9}|\+91\d{10}|\b[6-9]\d{9}\b)/', '', $temp_message);
            }
            
            // Clean and extract remaining text as name
            $temp_message = trim($temp_message);
            $temp_message = preg_replace('/[^\w\s\.]/', ' ', $temp_message);
            $temp_message = preg_replace('/\s+/', ' ', $temp_message);
            $temp_message = trim($temp_message);
            
            if (!empty($temp_message) && 
                strlen($temp_message) >= 2 && 
                strlen($temp_message) <= 30 &&
                preg_match('/^[a-zA-Z\s\.]+$/', $temp_message)) {
                $info['name'] = ucwords(strtolower($temp_message));
            }
        }
        
        return $info;
    }
    
    /**
     * Parse academic info (grade and board)
     */
    private function parse_academic_info($message) {
        $info = array();
        $message_lower = strtolower($message);
        
        // Extract grade
        if (preg_match('/\b(nursery|pp1|pp2|pre-?kg|lkg|ukg|grade\s*\d+|class\s*\d+|\d+th|\d+st|\d+nd|\d+rd)\b/i', $message_lower)) {
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
            
            // Validate the date is real using Indian timezone
            try {
                $ist_timezone = new DateTimeZone('Asia/Kolkata');
                $birth_date = new DateTime($dob, $ist_timezone);
                $current_date = new DateTime('now', $ist_timezone);
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
        // Check for invalid year formats (2-digit year)
        elseif (preg_match('/\b(\d{1,2})[-\/](\d{1,2})[-\/](\d{2})\b/', $message)) {
            $info['error'] = "❌ **Invalid Date Format**\n\nPlease use 4-digit year format (dd/mm/yyyy).\n\nExample: 16/10/2010\n\nMake sure to enter the complete 4-digit year.";
            return $info;
        }
        // Check for invalid year formats (more or less than 4 digits, like 20101 or 201)
        elseif (preg_match('/\b(\d{1,2})[-\/](\d{1,2})[-\/](\d{1,3}|\d{5,})\b/', $message)) {
            $info['error'] = "❌ **Invalid Date Format**\n\nPlease enter the date in dd/mm/yyyy format with exactly 4 digits for the year.\n\nExample: 16/10/2010\n\nYou entered an invalid year. Please check and try again.";
            return $info;
        }
        elseif (preg_match('/\b(\d{1,2})\s*(years?|yrs?|year\s*old)\b/i', $message) || 
                preg_match('/^\s*(\d{1,2})\s*$/', $message)) {
            $info['error'] = "❌ **Invalid Format**\n\nPlease enter the date of birth in dd/mm/yyyy format, not age.\n\nExample: 16/10/2010";
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
        global $wpdb;
        
        try {
            error_log("EduBot: Starting final submission with data: " . json_encode($collected_data));
            error_log("EduBot: Collected data keys: " . implode(', ', array_keys($collected_data)));
            
            // Verify required fields exist
            if (empty($collected_data['student_name'])) {
                error_log("EduBot ERROR: student_name is empty");
            }
            if (empty($collected_data['email'])) {
                error_log("EduBot ERROR: email is empty");
            }
            if (empty($collected_data['phone'])) {
                error_log("EduBot ERROR: phone is empty");
            }
            
            // Generate enquiry number
            $enquiry_number = 'ENQ' . $this->get_indian_time('Y') . wp_rand(1000, 9999);
            
            // Get school name
            $settings = get_option('edubot_pro_settings', array());
            $school_name = $settings['school_name'] ?? 'Vikas The Concept School';
            
            // Save to database - ensure table exists first
            $table_name = $wpdb->prefix . 'edubot_enquiries';
            
            // Create table if it doesn't exist
            $this->ensure_enquiry_table_exists();
            
            // Capture tracking data
            $utm_data = $this->get_utm_data();
            $ip_address = $this->get_client_ip();
            $user_agent = $this->get_user_agent();
            
            // Extract click IDs for separate storage
            $gclid = $utm_data['gclid'] ?? null;
            $fbclid = $utm_data['fbclid'] ?? null;
            
            // Determine source from UTM data or default to chatbot
            $source = 'chatbot'; // Default source
            if (!empty($utm_data['utm_source'])) {
                // Use utm_source as the source (e.g., 'google', 'facebook', 'email', 'organic_search', 'direct')
                $source = sanitize_text_field($utm_data['utm_source']);
                error_log("EduBot: Source determined from UTM: " . $source);
            } else {
                error_log("EduBot: No UTM source found, using default: chatbot");
            }
            
            // Prepare click ID data for comprehensive tracking
            $click_id_data = array();
            if ($gclid) {
                $click_id_data['gclid'] = $gclid;
                $click_id_data['gclid_captured_at'] = current_time('mysql');
            }
            if ($fbclid) {
                $click_id_data['fbclid'] = $fbclid;
                $click_id_data['fbclid_captured_at'] = current_time('mysql');
            }
            
            // Add other tracking IDs if present
            $other_click_params = array('msclkid', 'ttclid', 'twclid', '_kenshoo_clickid', 'irclickid');
            foreach ($other_click_params as $param) {
                if (isset($utm_data[$param])) {
                    $click_id_data[$param] = $utm_data[$param];
                    $click_id_data[$param . '_captured_at'] = current_time('mysql');
                }
            }
            
            $result = $wpdb->insert(
                $table_name,
                array(
                    'enquiry_number' => $enquiry_number,
                    'student_name' => $collected_data['student_name'] ?? '',
                    'date_of_birth' => $collected_data['date_of_birth'] ?? '',
                    'grade' => $collected_data['grade'] ?? '',
                    'board' => $collected_data['board'] ?? '',
                    'academic_year' => $collected_data['academic_year'] ?? (EduBot_School_Config::getInstance()->get_default_academic_year()),
                    'parent_name' => $collected_data['parent_name'] ?? '',
                    'email' => $collected_data['email'] ?? '',
                    'phone' => $collected_data['phone'] ?? '',
                    'ip_address' => $ip_address,
                    'user_agent' => $user_agent,
                    'utm_data' => wp_json_encode($utm_data),
                    'gclid' => $gclid,
                    'fbclid' => $fbclid,
                    'click_id_data' => !empty($click_id_data) ? wp_json_encode($click_id_data) : null,
                    'whatsapp_sent' => 0,
                    'email_sent' => 0,
                    'sms_sent' => 0,
                    'address' => $collected_data['address'] ?? '',
                    'gender' => $collected_data['gender'] ?? '',
                    'created_at' => current_time('mysql'),
                    'status' => 'pending',
                    'source' => $source
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                error_log('EduBot: Failed to save enquiry to database: ' . $wpdb->last_error);
                throw new Exception('Database insert failed: ' . $wpdb->last_error);
            }
            
            // Get the inserted enquiry ID for status tracking
            $enquiry_id = $wpdb->insert_id;
            error_log("EduBot: Successfully saved enquiry {$enquiry_number} to database with ID {$enquiry_id}");
            
            // Also save to applications table for unified admin interface
            try {
                error_log("EduBot: About to call save_to_applications_table for {$enquiry_number}");
                $this->save_to_applications_table($collected_data, $enquiry_number);
                error_log("EduBot: Finished calling save_to_applications_table for {$enquiry_number}");
            } catch (Exception $app_error) {
                error_log("EduBot: CRITICAL - Exception when saving to applications table: " . $app_error->getMessage());
                error_log("EduBot: Exception trace: " . $app_error->getTraceAsString());
            }
            
            // Initialize database manager for status updates
            $database_manager = new EduBot_Database_Manager();
            
            // Send confirmation email to parent
            try {
                $email_sent = $this->send_parent_confirmation_email($collected_data, $enquiry_number, $school_name);
            } catch (Exception $email_error) {
                error_log('EduBot: Exception during email sending: ' . $email_error->getMessage());
                $email_sent = false;
            }
            if ($email_sent && $enquiry_id) {
                $database_manager->update_notification_status($enquiry_id, 'email', 1, 'enquiries');
                error_log("EduBot: Updated email_sent status to 1 for enquiry ID {$enquiry_id}");
            }
            
            // Send WhatsApp confirmation to parent if enabled
            try {
                $debug_file = '/home/epistemo-stage/htdocs/stage.epistemo.in/wp-content/edubot-debug.log';
                $debug_msg = "\n>>> CALLING WhatsApp confirmation for enquiry $enquiry_number at " . $this->get_indian_time('Y-m-d H:i:s') . " IST\n";
                file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
                
                $whatsapp_sent = $this->send_parent_whatsapp_confirmation($collected_data, $enquiry_number, $school_name);
            } catch (Exception $wa_error) {
                error_log('EduBot: Exception during WhatsApp confirmation: ' . $wa_error->getMessage());
                $whatsapp_sent = false;
            }
            if ($whatsapp_sent && $enquiry_id) {
                $database_manager->update_notification_status($enquiry_id, 'whatsapp', 1, 'enquiries');
                error_log("EduBot: Updated whatsapp_sent status to 1 for enquiry ID {$enquiry_id}");
            }
            
            // Send enquiry notification to school (this is for admin, not tracked in parent notification status)
            try {
                $this->send_school_enquiry_notification($collected_data, $enquiry_number, $school_name);
            } catch (Exception $school_email_error) {
                error_log('EduBot: Exception during school email notification: ' . $school_email_error->getMessage());
            }
            
            // Send WhatsApp notification to school if enabled
            try {
                $this->send_school_whatsapp_notification($collected_data, $enquiry_number, $school_name);
            } catch (Exception $school_wa_error) {
                error_log('EduBot: Exception during school WhatsApp notification: ' . $school_wa_error->getMessage());
            }
            
            // Clear session
            $transient_key = 'edubot_session_' . $session_id;
            delete_transient($transient_key);
            
            error_log("EduBot: Enquiry submission completed successfully");
            
            return "🎉 **Admission Enquiry Submitted Successfully!**\n\n" .
                   "**📋 Your Enquiry Number: {$enquiry_number}**\n\n" .
                   "**✅ Information Submitted:**\n" .
                   "👶 **Student:** {$collected_data['student_name']}\n" .
                   "🎓 **Grade:** {$collected_data['grade']}\n" .
                   "📚 **Board:** {$collected_data['board']}\n" .
                   "📧 **Email:** {$collected_data['email']}\n" .
                   "📱 **Phone:** {$collected_data['phone']}\n" .
                   "📅 **DOB:** {$collected_data['date_of_birth']}\n\n" .
                   "**🔄 Next Steps:**\n" .
                   "• Our admission team will contact you within 24 hours\n" .
                   "• You'll receive detailed information about the admission process\n" .
                   "• Campus visit will be scheduled as per your convenience\n\n" .
                   "**📞 Need immediate assistance?**\n" .
                   "Call: 7702800800 / 9248111448\n\n" .
                   "Thank you for choosing {$school_name}! 🏫";
                   
        } catch (Exception $e) {
            error_log('EduBot: Error in final submission: ' . $e->getMessage());
            error_log('EduBot: Stack trace: ' . $e->getTraceAsString());
            error_log('EduBot: Error code: ' . $e->getCode());
            error_log('EduBot: Collected data at error: ' . json_encode($collected_data));
            
            // Return an error message that indicates something went wrong
            return "❌ **Error Submitting Your Enquiry**\n\n" .
                   "We encountered a technical error while processing your information:\n" .
                   "**Error:** " . $e->getMessage() . "\n\n" .
                   "Your information was NOT saved. Please try again or contact:\n" .
                   "📞 **7702800800** / **9248111448**\n\n" .
                   "📧 **admissions@vikasconcept.com**";
        }
    }
    
    /**
     * Send confirmation email to parent
     */
    private function send_parent_confirmation_email($collected_data, $enquiry_number, $school_name) {
        try {
            // Check if email notifications are enabled
            $email_enabled = get_option('edubot_email_notifications', 1);
            if (!$email_enabled) {
                error_log('EduBot: Email notifications are disabled in settings');
                return false;
            }
            
            $to = $collected_data['email'] ?? '';
            if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                error_log('EduBot: Invalid email address for enquiry notification');
                return false;
            }
            
            $subject = "Admission Enquiry Confirmation - {$school_name}";
            
            // Create HTML email content
            $message = $this->build_parent_confirmation_html($collected_data, $enquiry_number, $school_name);
            
            $headers = array('Content-Type: text/html; charset=UTF-8');
            
            // Use API integrations for email sending
            if (!class_exists('EduBot_API_Integrations')) {
                error_log('EduBot: API Integrations class not found');
                return false;
            }
            
            $api_integrations = new EduBot_API_Integrations();
            $sent = $api_integrations->send_email($to, $subject, $message, $headers);
            
            if ($sent) {
                error_log("EduBot: Confirmation email sent to {$to}");
            } else {
                error_log("EduBot: Failed to send confirmation email to {$to}");
            }
            
            return $sent;
            
        } catch (Exception $e) {
            error_log('EduBot: Email sending error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send enquiry notification to school
     */
    private function send_school_enquiry_notification($collected_data, $enquiry_number, $school_name) {
        try {
            // Check if school notifications are enabled
            $school_notifications_enabled = get_option('edubot_school_notifications', 1);
            if (!$school_notifications_enabled) {
                error_log('EduBot: School notifications are disabled in settings');
                return false;
            }
            
            // Get school email from School Information > Contact Email setting
            $school_email = '';
            
            // Priority 1: Try multiple possible WordPress options for school contact email
            $possible_options = [
                'edubot_school_email', // From School Settings page
                'school_contact_email',
                'school_information_contact_email', 
                'edubot_school_contact_email',
                'admin_email' // WordPress default
            ];
            
            foreach ($possible_options as $option_name) {
                $option_value = get_option($option_name);
                if (!empty($option_value) && filter_var($option_value, FILTER_VALIDATE_EMAIL)) {
                    $school_email = $option_value;
                    error_log('EduBot: Using email from WordPress option ' . $option_name . ': ' . $school_email);
                    break;
                }
            }
            
            // Priority 2: Try to get from School Information > Contact Email via EduBot_School_Config
            if (empty($school_email) && class_exists('EduBot_School_Config')) {
                try {
                    $school_config = EduBot_School_Config::getInstance();
                    $config = $school_config->get_config();
                    $contact_info = $config['school_info']['contact_info'] ?? array();
                    if (!empty($contact_info['email'])) {
                        $school_email = $contact_info['email'];
                        error_log('EduBot: Using school contact email from School Information: ' . $school_email);
                    }
                } catch (Exception $e) {
                    error_log('EduBot: Could not get school config: ' . $e->getMessage());
                }
            }
            
            // Priority 3: Fallback to plugin settings if School Information not available
            if (empty($school_email)) {
                $settings = get_option('edubot_pro_settings', array());
                if (!empty($settings['contact_email'])) {
                    $school_email = $settings['contact_email'];
                    error_log('EduBot: Using contact email from plugin settings: ' . $school_email);
                } elseif (!empty($settings['admin_email'])) {
                    $school_email = $settings['admin_email'];
                    error_log('EduBot: Using admin email from plugin settings: ' . $school_email);
                }
            }
            
            // Priority 4: Final fallback
            if (empty($school_email)) {
                $school_email = 'admissions@vikasconcept.com';
                error_log('EduBot: Using fallback email: ' . $school_email);
            }
            
            if (!filter_var($school_email, FILTER_VALIDATE_EMAIL)) {
                error_log('EduBot: Invalid school email address: ' . $school_email);
                return false;
            }
            
            $subject = "New Admission Enquiry - {$enquiry_number}";
            
            // Create HTML email content for school notification
            $message = $this->build_school_notification_html($collected_data, $enquiry_number, $school_name);
            
            $headers = array('Content-Type: text/html; charset=UTF-8');
            
            // Use API integrations for email sending
            if (!class_exists('EduBot_API_Integrations')) {
                error_log('EduBot: API Integrations class not found');
                return false;
            }
            
            $api_integrations = new EduBot_API_Integrations();
            $sent = $api_integrations->send_email($school_email, $subject, $message, $headers);
            
            if ($sent) {
                error_log("EduBot: School notification email sent to {$school_email}");
            } else {
                error_log("EduBot: Failed to send school notification email to {$school_email}");
            }
            
            return $sent;
            
        } catch (Exception $e) {
            error_log('EduBot: School notification email error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send WhatsApp notification to school admission team
     */
    private function send_school_whatsapp_notification($collected_data, $enquiry_number, $school_name) {
        try {
            // Check if school WhatsApp notifications are enabled
            $school_whatsapp_enabled = get_option('edubot_school_whatsapp_notifications', 0);
            if (!$school_whatsapp_enabled) {
                error_log('EduBot: School WhatsApp notifications are disabled in settings');
                return false;
            }
            
            // Check if WhatsApp is configured
            $whatsapp_enabled = get_option('edubot_whatsapp_notifications', 0);
            if (!$whatsapp_enabled) {
                error_log('EduBot: WhatsApp notifications are not configured');
                return false;
            }
            
            // Get school phone number from Contact Phone setting
            $school_phone = get_option('edubot_school_phone', '');
            if (empty($school_phone)) {
                error_log('EduBot: School Contact Phone not configured in School Settings - please set Contact Phone for admission team notifications');
                return false;
            }
            
            error_log("EduBot: Using school Contact Phone for admission team notification: {$school_phone}");
            
            // Normalize phone number
            $school_phone = preg_replace('/[^0-9+]/', '', $school_phone);
            if (strlen($school_phone) < 10) {
                error_log('EduBot: Invalid school phone number format for WhatsApp');
                return false;
            }
            
            // Check school template type (separate from parent templates)
            $template_type = get_option('edubot_school_whatsapp_template_type', 'freeform');
            
            if ($template_type === 'business_template') {
                // Use business template for school notifications
                return $this->send_school_whatsapp_template($school_phone, $collected_data, $enquiry_number, $school_name);
            } else {
                // Use freeform message
                return $this->send_school_whatsapp_freeform($school_phone, $collected_data, $enquiry_number, $school_name);
            }
            
        } catch (Exception $e) {
            error_log('EduBot: School WhatsApp notification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send school WhatsApp using business template
     */
    private function send_school_whatsapp_template($phone, $collected_data, $enquiry_number, $school_name) {
        try {
            $template_name = get_option('edubot_school_whatsapp_template_name', 'edubot_school_whatsapp_template_name_');
            $template_language = get_option('edubot_school_whatsapp_template_language', 'en');
            
            // Prepare template parameters for school notification
            $template_params = [
                $school_name, // {{1}}
                $enquiry_number, // {{2}}  
                $collected_data['student_name'] ?? 'N/A', // {{3}}
                $collected_data['grade'] ?? 'N/A', // {{4}}
                $collected_data['board'] ?? 'N/A', // {{5}}
                $collected_data['parent_name'] ?? 'Not Provided', // {{6}}
                $collected_data['phone'] ?? 'N/A', // {{7}}
                $collected_data['email'] ?? 'N/A', // {{8}}
                $this->get_indian_time('d/m/Y H:i') // {{9}} - submission time
            ];
            
            if (!class_exists('EduBot_API_Integrations')) {
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-api-integrations.php';
            }
            
            $api_integrations = new EduBot_API_Integrations();
            
            // Prepare API keys array
            $api_keys = [
                'whatsapp_phone_id' => get_option('edubot_whatsapp_phone_id', ''),
                'whatsapp_token' => get_option('edubot_whatsapp_token', '')
            ];
            
            // Format message for Meta Business API with CORRECT structure
            // Must include header component (empty) + body component with parameters
            $formatted_message = [
                'type' => 'template',
                'template' => [
                    'name' => $template_name,
                    'language' => ['code' => $template_language],
                    'components' => [
                        [
                            'type' => 'header',
                            'parameters' => []  // Header component with empty parameters
                        ],
                        [
                            'type' => 'body',
                            'parameters' => array_map(function($param) {
                                return ['type' => 'text', 'text' => (string)$param];
                            }, $template_params)
                        ]
                    ]
                ]
            ];
            
            $result = $api_integrations->send_meta_whatsapp($phone, $formatted_message, $api_keys);
            
            if ($result && isset($result['success']) && $result['success']) {
                error_log("EduBot: School WhatsApp template notification sent successfully to {$phone}");
                return true;
            } else {
                error_log("EduBot: Failed to send school WhatsApp template notification: " . json_encode($result));
                return false;
            }
            
        } catch (Exception $e) {
            error_log('EduBot: School WhatsApp template error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send school WhatsApp using freeform message
     */
    private function send_school_whatsapp_freeform($phone, $collected_data, $enquiry_number, $school_name) {
        try {
            // Get school-specific WhatsApp template
            $default_school_template = "🎓 *New Admission Enquiry - {school_name}*\n\n" .
                "📋 *Enquiry Number:* {enquiry_number}\n" .
                "👶 *Student:* {student_name}\n" .
                "🎯 *Grade:* {grade}\n" .
                "📚 *Board:* {board}\n" .
                "👨‍👩‍👧 *Parent:* {parent_name}\n" .
                "📱 *Phone:* {phone}\n" .
                "📧 *Email:* {email}\n" .
                "📅 *Submitted:* {submission_date}\n\n" .
                "Please review and contact the family for next steps.\n\n" .
                "EduBot Pro - Admission Management";
            
            $template = get_option('edubot_school_whatsapp_template', $default_school_template);
            
            // Replace placeholders
            $message = str_replace(
                ['{school_name}', '{enquiry_number}', '{student_name}', '{grade}', '{board}', 
                 '{parent_name}', '{phone}', '{email}', '{submission_date}', '{academic_year}'],
                [
                    $school_name,
                    $enquiry_number,
                    $collected_data['student_name'] ?? 'N/A',
                    $collected_data['grade'] ?? 'N/A',
                    $collected_data['board'] ?? 'N/A',
                    $collected_data['parent_name'] ?? 'N/A',
                    $collected_data['phone'] ?? 'N/A',
                    $collected_data['email'] ?? 'N/A',
                    $this->get_indian_time('d/m/Y H:i'),
                    $collected_data['academic_year'] ?? '2026-27'
                ],
                $template
            );
            
            if (!class_exists('EduBot_API_Integrations')) {
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-api-integrations.php';
            }
            
            $api_integrations = new EduBot_API_Integrations();
            $result = $api_integrations->send_whatsapp($phone, $message);
            
            if ($result && !is_wp_error($result)) {
                error_log("EduBot: School WhatsApp freeform notification sent successfully to {$phone}");
                return true;
            } else {
                $error_msg = is_wp_error($result) ? $result->get_error_message() : 'Unknown error';
                error_log("EduBot: Failed to send school WhatsApp freeform notification: {$error_msg}");
                return false;
            }
            
        } catch (Exception $e) {
            error_log('EduBot: School WhatsApp freeform error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate unique enquiry number
     */
    private function generate_enquiry_number() {
        $prefix = 'ENQ' . $this->get_indian_time('Y');
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
        $html .= '<tr><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Date Submitted</td><td style="padding: 10px; border: 1px solid #ddd;">' . $this->get_indian_time('F j, Y g:i A') . ' (IST)</td></tr>';
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
        $response .= "📚 **Board:** {$collected_data['board']}\n";
        $response .= "🕐 **Submitted:** {$this->get_chatbot_friendly_time()}\n\n";
        
        $response .= "📧 **Confirmation email sent to:** {$collected_data['email']}\n\n";
        
        $response .= "🔄 **Next Steps:**\n";
        $response .= "• Our admission team will contact you within 24 hours\n";
        $response .= "• Schedule a campus visit\n";
        $response .= "• Guide you through the admission process\n\n";
        
        $response .= "📞 For immediate assistance, please contact us directly.\n\n";
        $response .= "🌐 **Visit our website:** www.vikasconcept.com\n\n";
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
        // COMPREHENSIVE DEBUG LOGGING
        error_log("========================================");
        error_log("EduBot handle_application_submission STARTED");
        error_log("========================================");
        error_log("POST Keys: " . implode(', ', array_keys($_POST)));
        error_log("Has utm_params in POST: " . (isset($_POST['utm_params']) ? 'YES' : 'NO'));
        if (isset($_POST['utm_params'])) {
            error_log("utm_params content: " . json_encode($_POST['utm_params']));
        }
        error_log("========================================");
        
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
        
        // Check age range (between 2 and 19 years old) using Indian timezone
        $ist_timezone = new DateTimeZone('Asia/Kolkata');
        $current_date_ist = new DateTime('now', $ist_timezone);
        $age = $current_date_ist->diff($dob)->y;
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
        
        // Capture marketing/UTM data from multiple sources with priority
        // Priority 1: Check if sent in POST from JavaScript (most reliable during AJAX)
        $utm_data = array();
        $utm_params = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'msclkid', 'ttclid', 'twclid', '_kenshoo_clickid', 'irclickid', 'li_fat_id', 'sc_click_id', 'yclid');
        
        foreach ($utm_params as $param) {
            // Check POST first (from AJAX/JavaScript with URL params)
            if (isset($_POST['utm_params']) && is_array($_POST['utm_params']) && isset($_POST['utm_params'][$param])) {
                $utm_data[$param] = sanitize_text_field($_POST['utm_params'][$param]);
                error_log("EduBot Form Submission: Got {$param} from POST utm_params");
            }
            // Fallback to direct POST fields
            elseif (isset($_POST[$param])) {
                $utm_data[$param] = sanitize_text_field($_POST[$param]);
            }
        }
        
        // Priority 2: Fallback to cookies/session
        if (empty($utm_data)) {
            $utm_data = $this->get_utm_data();
            error_log("EduBot Form Submission: Using UTM from cookies/session");
        } else {
            error_log("EduBot Form Submission: Using UTM from AJAX POST data");
        }
        
        error_log("EduBot Form Submission: Captured UTM data: " . json_encode($utm_data));
        error_log("EduBot Form Submission: gclid = " . ($gclid ?? 'NULL'));
        error_log("EduBot Form Submission: fbclid = " . ($fbclid ?? 'NULL'));
        error_log("EduBot Form Submission: click_id_data = " . json_encode($click_id_data));
        error_log("========================================");
        
        // Extract click IDs for separate storage
        $gclid = $utm_data['gclid'] ?? null;
        $fbclid = $utm_data['fbclid'] ?? null;
        
        // Prepare click ID data for comprehensive tracking
        $click_id_data = array();
        if ($gclid) {
            $click_id_data['gclid'] = $gclid;
            $click_id_data['gclid_captured_at'] = current_time('mysql');
        }
        if ($fbclid) {
            $click_id_data['fbclid'] = $fbclid;
            $click_id_data['fbclid_captured_at'] = current_time('mysql');
        }
        
        // Add other tracking IDs if present
        $other_click_params = array('msclkid', 'ttclid', 'twclid', '_kenshoo_clickid', 'irclickid');
        foreach ($other_click_params as $param) {
            if (isset($utm_data[$param])) {
                $click_id_data[$param] = $utm_data[$param];
                $click_id_data[$param . '_captured_at'] = current_time('mysql');
            }
        }
        
        // Determine source from UTM data or default to chatbot
        $source = 'application_form'; // Default source for form submissions
        if (!empty($utm_data['utm_source'])) {
            // Use utm_source as the source (e.g., 'google', 'facebook', 'email', 'organic_search', 'direct')
            $source = sanitize_text_field($utm_data['utm_source']);
            error_log("EduBot Form Submission: Source determined from UTM: " . $source);
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
            'source' => $source,
            'utm_data' => wp_json_encode($utm_data),
            'gclid' => $gclid,
            'fbclid' => $fbclid,
            'click_id_data' => !empty($click_id_data) ? wp_json_encode($click_id_data) : null
        );
        
        // DEBUG: Log before save
        error_log("========================================");
        error_log("EduBot: About to save application");
        error_log("Application Data Keys: " . implode(', ', array_keys($application_data)));
        error_log("utm_data value: " . substr($application_data['utm_data'], 0, 100));
        error_log("gclid value: " . ($application_data['gclid'] ?? 'NULL'));
        error_log("fbclid value: " . ($application_data['fbclid'] ?? 'NULL'));
        error_log("click_id_data value: " . substr($application_data['click_id_data'] ?? 'NULL', 0, 100));
        error_log("========================================");
        
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
        $prefix = 'APP-' . $this->get_indian_time('Y') . '-';
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
        global $wpdb;
        
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        $school_name = $config['school_info']['name'] ?? 'School';
        $settings = get_option('edubot_pro_settings', array());
        
        // Get API settings from the correct table using migration helper
        if (!class_exists('EduBot_API_Migration')) {
            error_log('EduBot: API Migration class not found');
            return;
        }
        
        $api_settings = EduBot_API_Migration::get_api_settings(get_current_blog_id());
        
        // Get notification settings from school config table
        $school_config_table = $wpdb->prefix . 'edubot_school_configs';
        $school_config_row = $wpdb->get_row($wpdb->prepare(
            "SELECT config_data FROM $school_config_table WHERE site_id = %d LIMIT 1",
            get_current_blog_id()
        ));
        $config_data = array();
        if ($school_config_row) {
            $config_data = json_decode($school_config_row->config_data, true);
        }
        
        // Initialize API integrations and database manager
        if (!class_exists('EduBot_API_Integrations')) {
            error_log('EduBot: API Integrations class not found for form submissions');
            return;
        }
        
        $api_integrations = new EduBot_API_Integrations();
        $database_manager = new EduBot_Database_Manager();
        
        // Get the application record ID from database to track notification status
        $table = $wpdb->prefix . 'edubot_applications';
        $application_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE application_number = %s LIMIT 1",
            $application_data['application_number']
        ));
        
        // 1. SEND PARENT CONFIRMATION EMAIL
        if (!empty($application_data['email']) && filter_var($application_data['email'], FILTER_VALIDATE_EMAIL)) {
            try {
                $subject = "✅ Admission Enquiry Confirmation - {$school_name}";
                
                // Build HTML email
                $message = "
                <html>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='max-width: 600px; margin: 0 auto;'>
                            <h2 style='color: #2c3e50;'>Thank you for your admission enquiry!</h2>
                            <p>Dear " . sanitize_text_field($application_data['parent_name']) . ",</p>
                            
                            <p>We have received your application for <strong>" . sanitize_text_field($application_data['student_name']) . "</strong>.</p>
                            
                            <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #0066cc; margin: 20px 0;'>
                                <p><strong>📋 Enquiry Number: " . sanitize_text_field($application_data['application_number']) . "</strong></p>
                                <p><strong>📚 Grade Applied: </strong>" . sanitize_text_field($application_data['grade']) . "</p>
                                <p><strong>📅 Submitted: </strong>" . current_time('F j, Y \a\t g:i A') . "</p>
                            </div>
                            
                            <p><strong>✅ Information Submitted:</strong></p>
                            <ul>
                                <li>Student Name: " . sanitize_text_field($application_data['student_name']) . "</li>
                                <li>Grade: " . sanitize_text_field($application_data['grade']) . "</li>
                                " . (!empty($application_data['educational_board']) ? "<li>Board: " . sanitize_text_field($application_data['educational_board']) . "</li>" : "") . "
                                " . (!empty($application_data['academic_year']) ? "<li>Academic Year: " . sanitize_text_field($application_data['academic_year']) . "</li>" : "") . "
                                <li>Email: " . sanitize_email($application_data['email']) . "</li>
                                <li>Phone: " . sanitize_text_field($application_data['phone']) . "</li>
                            </ul>
                            
                            <p><strong>🚀 Next Steps:</strong></p>
                            <ul>
                                <li>Our admission team will review your application</li>
                                <li>You'll receive detailed information about the admission process</li>
                                <li>Campus visit will be scheduled as per your convenience</li>
                            </ul>
                            
                            <div style='background-color: #fffbea; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                                <p><strong>📞 Need immediate assistance?</strong></p>
                                <p>Call: 7702800800 / 9248111448<br/>
                                Email: admissions@vikasconcept.com</p>
                            </div>
                            
                            <p>Thank you for choosing " . sanitize_text_field($school_name) . "! 🏫</p>
                            
                            <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'>
                            <p style='font-size: 12px; color: #999;'>This is an automated email. Please do not reply to this message.</p>
                        </div>
                    </body>
                </html>";
                
                $headers = array('Content-Type: text/html; charset=UTF-8');
                $email_sent = $api_integrations->send_email(
                    $application_data['email'],
                    $subject,
                    $message,
                    $headers
                );
                
                if ($email_sent) {
                    error_log("EduBot: Parent confirmation email sent to {$application_data['email']} for application {$application_data['application_number']}");
                    if ($application_id) {
                        $database_manager->update_notification_status($application_id, 'email', 1, 'applications');
                    }
                } else {
                    error_log("EduBot: Failed to send parent confirmation email to {$application_data['email']}");
                }
            } catch (Exception $e) {
                error_log('EduBot: Exception sending parent confirmation email: ' . $e->getMessage());
            }
        }
        
        // 2. SEND SCHOOL NOTIFICATION EMAIL
        $school_email = '';
        $possible_options = [
            'edubot_school_email',
            'school_contact_email',
            'school_information_contact_email',
            'edubot_school_contact_email',
            'admin_email'
        ];
        
        foreach ($possible_options as $option_name) {
            $option_value = get_option($option_name);
            if (!empty($option_value) && filter_var($option_value, FILTER_VALIDATE_EMAIL)) {
                $school_email = $option_value;
                break;
            }
        }
        
        if (empty($school_email) && class_exists('EduBot_School_Config')) {
            try {
                $config = $school_config->get_config();
                $contact_info = $config['school_info']['contact_info'] ?? array();
                if (!empty($contact_info['email'])) {
                    $school_email = $contact_info['email'];
                }
            } catch (Exception $e) {
                error_log('EduBot: Could not get school config: ' . $e->getMessage());
            }
        }
        
        if (empty($school_email)) {
            $settings = get_option('edubot_pro_settings', array());
            $school_email = $settings['admin_email'] ?? get_option('admin_email');
        }
        
        if (!empty($school_email) && filter_var($school_email, FILTER_VALIDATE_EMAIL)) {
            try {
                $subject = "📋 New Application Received - {$application_data['application_number']}";
                
                $message = "
                <html>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='max-width: 600px; margin: 0 auto;'>
                            <h2 style='color: #2c3e50;'>New Admission Application Received</h2>
                            
                            <div style='background-color: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0;'>
                                <p style='margin: 5px 0;'><strong>Application Number: " . sanitize_text_field($application_data['application_number']) . "</strong></p>
                                <p style='margin: 5px 0;'><strong>Submitted: </strong>" . current_time('F j, Y \a\t g:i A') . "</p>
                            </div>
                            
                            <p><strong>📝 Applicant Information:</strong></p>
                            <table style='width: 100%; border-collapse: collapse;'>
                                <tr>
                                    <td style='padding: 8px; border: 1px solid #ddd; width: 40%;'><strong>Student Name</strong></td>
                                    <td style='padding: 8px; border: 1px solid #ddd;'>" . sanitize_text_field($application_data['student_name']) . "</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px; border: 1px solid #ddd;'><strong>Parent/Guardian</strong></td>
                                    <td style='padding: 8px; border: 1px solid #ddd;'>" . sanitize_text_field($application_data['parent_name']) . "</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px; border: 1px solid #ddd;'><strong>Grade</strong></td>
                                    <td style='padding: 8px; border: 1px solid #ddd;'>" . sanitize_text_field($application_data['grade']) . "</td>
                                </tr>
                                " . (!empty($application_data['educational_board']) ? "
                                <tr>
                                    <td style='padding: 8px; border: 1px solid #ddd;'><strong>Board</strong></td>
                                    <td style='padding: 8px; border: 1px solid #ddd;'>" . sanitize_text_field($application_data['educational_board']) . "</td>
                                </tr>" : "") . "
                                " . (!empty($application_data['academic_year']) ? "
                                <tr>
                                    <td style='padding: 8px; border: 1px solid #ddd;'><strong>Academic Year</strong></td>
                                    <td style='padding: 8px; border: 1px solid #ddd;'>" . sanitize_text_field($application_data['academic_year']) . "</td>
                                </tr>" : "") . "
                                <tr>
                                    <td style='padding: 8px; border: 1px solid #ddd;'><strong>Email</strong></td>
                                    <td style='padding: 8px; border: 1px solid #ddd;'>" . sanitize_email($application_data['email']) . "</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px; border: 1px solid #ddd;'><strong>Phone</strong></td>
                                    <td style='padding: 8px; border: 1px solid #ddd;'>" . sanitize_text_field($application_data['phone']) . "</td>
                                </tr>
                            </table>
                            
                            <p style='margin-top: 20px;'><strong>🔗 Next Action:</strong></p>
                            <p>Review this application in the admin panel: <a href='" . admin_url('admin.php?page=edubot-applications') . "'>View Applications</a></p>
                            
                            <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'>
                            <p style='font-size: 12px; color: #999;'>This is an automated notification from the EduBot system.</p>
                        </div>
                    </body>
                </html>";
                
                $headers = array('Content-Type: text/html; charset=UTF-8');
                $school_email_sent = $api_integrations->send_email(
                    $school_email,
                    $subject,
                    $message,
                    $headers
                );
                
                if ($school_email_sent) {
                    error_log("EduBot: School notification email sent to {$school_email} for application {$application_data['application_number']}");
                } else {
                    error_log("EduBot: Failed to send school notification email to {$school_email}");
                }
            } catch (Exception $e) {
                error_log('EduBot: Exception sending school notification email: ' . $e->getMessage());
            }
        }
        
        // 3. SEND WHATSAPP CONFIRMATION TO PARENT
        if (!empty($application_data['phone'])) {
            try {
                // Check if WhatsApp notifications are enabled and configured
                $whatsapp_enabled = false;
                
                // Check if WhatsApp provider is configured
                if (!empty($api_settings['whatsapp_provider']) && !empty($api_settings['whatsapp_token'])) {
                    // Provider is configured, check if enabled in settings
                    if ($config_data && isset($config_data['notification_settings']['whatsapp_enabled'])) {
                        $whatsapp_enabled = $config_data['notification_settings']['whatsapp_enabled'];
                    } else {
                        $whatsapp_enabled = get_option('edubot_whatsapp_notifications', 0);
                    }
                } else {
                    error_log('EduBot: WhatsApp provider not configured - provider: ' . $api_settings['whatsapp_provider'] . ', token: ' . (empty($api_settings['whatsapp_token']) ? 'not set' : 'set'));
                }
                
                if (!$whatsapp_enabled) {
                    error_log('EduBot: WhatsApp notifications are disabled in settings');
                } else {
                    // Normalize phone number
                    $phone = preg_replace('/[^0-9+]/', '', $application_data['phone']);
                    
                    // WhatsApp message for parent
                    $whatsapp_message = "🎉 *Admission Enquiry Confirmation* 🎉\n\n";
                    $whatsapp_message .= "Thank you for your application to *" . sanitize_text_field($school_name) . "*!\n\n";
                    $whatsapp_message .= "📋 *Enquiry Number:* " . sanitize_text_field($application_data['application_number']) . "\n";
                    $whatsapp_message .= "👶 *Student:* " . sanitize_text_field($application_data['student_name']) . "\n";
                    $whatsapp_message .= "📚 *Grade Applied:* " . sanitize_text_field($application_data['grade']) . "\n\n";
                    $whatsapp_message .= "✅ *Next Steps:*\n";
                    $whatsapp_message .= "• Our admission team will review your application\n";
                    $whatsapp_message .= "• You'll receive detailed information about the admission process\n";
                    $whatsapp_message .= "• Campus visit will be scheduled as per your convenience\n\n";
                    $whatsapp_message .= "📞 *Need immediate assistance?*\n";
                    $whatsapp_message .= "Call: 7702800800 / 9248111448\n";
                    $whatsapp_message .= "Email: admissions@vikasconcept.com\n\n";
                    $whatsapp_message .= "Thank you! 🙏";
                    
                    // Send via API integrations
                    $whatsapp_sent = $api_integrations->send_whatsapp($phone, $whatsapp_message);
                    
                    if ($whatsapp_sent) {
                        error_log("EduBot: WhatsApp confirmation sent to {$phone} for application {$application_data['application_number']}");
                        if ($application_id) {
                            $database_manager->update_notification_status($application_id, 'whatsapp', 1, 'applications');
                        }
                    } else {
                        error_log("EduBot: Failed to send WhatsApp confirmation to {$phone}");
                    }
                }
            } catch (Exception $e) {
                error_log('EduBot: Exception sending WhatsApp confirmation: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Provide intelligent fallback responses when chatbot engine fails
     */
    private function provide_intelligent_fallback($message, $action_type = '', $session_id = '') {
        $settings = get_option('edubot_pro_settings', array());
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        $school_name = $config['school_info']['name'] ?? $settings['school_name'] ?? 'Vikas The Concept School';
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
                   "• **admissions@vikasconcept.com**\n\n" .
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
                   "• Email: admissions@vikasconcept.com\n" .
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
            (preg_match('/\b(nursery|pp1|pp2|pre-?kg|lkg|ukg|grade|class|\d+th|\d+st|\d+nd|\d+rd|cbse|caie|cambridge|state|icse|igcse)\b/i', $message_lower))) {
            
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
            error_log("EduBot: About to submit with collected_data: " . json_encode($collected_data));
            
            // Verify we have critical fields
            if (empty($collected_data['student_name']) || empty($collected_data['email']) || empty($collected_data['phone'])) {
                error_log("EduBot: ERROR - Missing critical fields in collected_data: " . json_encode($collected_data));
                return "❌ Error: Missing required contact information. Please start over with your name, email, and phone number.";
            }
            
            return $this->process_final_submission($collected_data, $session_id);
        }
        
        // Handle grade selection responses (only if not combined academic input)
        if (preg_match('/\b(nursery|pp1|pp2|pre-?kg|lkg|ukg|grade\s*\d+|class\s*\d+|\d+th|\d+st|\d+nd|\d+rd)\b/i', $message_lower) && 
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
                   "🔗 **Direct Link:** https://www.vikasconcept.com/enquiry/\n\n" .
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
        $school_name = $config['school_info']['name'] ?? $settings['school_name'] ?? 'Vikas The Concept School';
        
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
        $school_name = $config['school_info']['name'] ?? $settings['school_name'] ?? 'Vikas The Concept School';
        
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
        
        // Extract grade/class information with enhanced fuzzy matching
        $normalized_message = $this->normalize_grade_input_v2($message);
        $grade = $this->extract_fuzzy_grade_v2($normalized_message);
        
        if ($grade) {
            $extracted_data['grade'] = $grade;
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
    
    /**
     * Get valid grades for admission
     * 
     * @return array Valid grades
     */
    private function get_valid_grades() {
        return array(
            'Nursery',
            'Pre Nursery',
            'PP1',
            'PP2',
            'Grade 1',
            'Grade 2',
            'Grade 3',
            'Grade 4',
            'Grade 5',
            'Grade 6',
            'Grade 7',
            'Grade 8',
            'Grade 9',
            'Grade 10',
            'Grade 11',
            'Grade 12',
        );
    }

    private function extract_grade_from_message($message) {
        $message_lower = strtolower($message);
        $valid_grades = $this->get_valid_grades();
        
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
            return 'Grade 11';  // Store as Grade 11 only
        }
        if (preg_match('/grade\s*11\s*commerce/i', $message_lower)) {
            return 'Grade 11';  // Store as Grade 11 only
        }
        if (preg_match('/grade\s*11\s*humanities/i', $message_lower)) {
            return 'Grade 11';  // Store as Grade 11 only
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
            return 'Pre Nursery';  // Changed from Pre-KG to Pre Nursery
        }
        if (stripos($message_lower, 'lkg') !== false) {
            return 'PP1';  // LKG maps to PP1
        }
        if (stripos($message_lower, 'ukg') !== false) {
            return 'PP2';  // UKG maps to PP2
        }
        
        // Extract grade numbers (with validation)
        if (preg_match('/grade\s*(\d+)/i', $message, $matches)) {
            $grade_num = intval($matches[1]);
            // FIXED: Validate grade is between 1-12
            if ($grade_num >= 1 && $grade_num <= 12) {
                return 'Grade ' . $grade_num;
            }
        }
        
        if (preg_match('/class\s*(\d+)/i', $message, $matches)) {
            $grade_num = intval($matches[1]);
            // FIXED: Validate grade is between 1-12
            if ($grade_num >= 1 && $grade_num <= 12) {
                return 'Grade ' . $grade_num;
            }
        }
        
        if (preg_match('/(\d+)(th|st|nd|rd)/i', $message, $matches)) {
            $grade_num = intval($matches[1]);
            // FIXED: Validate grade is between 1-12
            if ($grade_num >= 1 && $grade_num <= 12) {
                return 'Grade ' . $grade_num;
            }
        }
        
        return null;  // Changed from 'Selected Grade' to null for invalid
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
    
    /**
     * Ensure enquiry table exists
     */
    private function ensure_enquiry_table_exists() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'edubot_enquiries';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            enquiry_number varchar(50) NOT NULL,
            student_name varchar(100) NOT NULL,
            date_of_birth date NULL,
            grade varchar(50) NULL,
            board varchar(50) NULL,
            academic_year varchar(20) NULL,
            parent_name varchar(100) NULL,
            email varchar(100) NULL,
            phone varchar(20) NULL,
            address text NULL,
            gender varchar(10) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'pending',
            source varchar(50) DEFAULT 'chatbot',
            PRIMARY KEY (id),
            UNIQUE KEY enquiry_number (enquiry_number)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log("EduBot: Ensured enquiry table exists");
    }

    /**
     * Save to applications table for unified admin interface
     */
    private function save_to_applications_table($collected_data, $enquiry_number) {
        try {
            error_log("EduBot: Attempting to save to applications table for enquiry {$enquiry_number}");
            
            // Log collected data for debugging
            error_log('EduBot: Collected data: ' . wp_json_encode($collected_data));
            
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

            error_log('EduBot: Student data prepared: ' . wp_json_encode($student_data));

            $application_data = array(
                'application_number' => $enquiry_number,
                'student_data' => $student_data,
                'conversation_log' => array(),
                'status' => 'pending',
                'source' => 'chatbot'
            );

            $result = $database_manager->save_application($application_data);
            
            if (is_wp_error($result)) {
                error_log('EduBot: Failed to save to applications table: ' . $result->get_error_message());
                error_log('EduBot: WP_Error code: ' . $result->get_error_code());
                error_log('EduBot: WP_Error data: ' . wp_json_encode($result->get_error_data()));
            } else {
                error_log("EduBot: Successfully saved {$enquiry_number} to applications table with ID: {$result}");
            }

        } catch (Exception $e) {
            error_log('EduBot: Exception during applications table save: ' . $e->getMessage());
            error_log('EduBot: Exception trace: ' . $e->getTraceAsString());
            // Don't throw exception as enquiry was already saved successfully
        }
    }

    /**
     * Build HTML email content for parent confirmation
     */
    private function build_parent_confirmation_html($collected_data, $enquiry_number, $school_name) {
        // Get branding colors and logo from school settings
        $primary_color = get_option('edubot_primary_color', '#4facfe');
        $secondary_color = get_option('edubot_secondary_color', '#00f2fe');
        $school_logo = get_option('edubot_school_logo', '');
        $school_phone = get_option('edubot_school_phone', '7702800800 / 9248111448');
        $school_email = get_option('edubot_school_email', get_option('admin_email'));
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Enquiry Confirmation - ' . esc_attr($enquiry_number) . '</title>
    <style>
        @media only screen and (max-width: 600px) {
            .container { width: 100% !important; padding: 10px !important; }
            .header-logo { font-size: 24px !important; }
            .content-section { padding: 15px !important; }
            .details-table { font-size: 14px !important; }
            .details-table td { padding: 8px !important; }
            .logo-img { max-width: 120px !important; max-height: 50px !important; }
            .enquiry-number-box { font-size: 28px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f7fa;">
    <div class="container" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, ' . esc_attr($primary_color) . ' 0%, ' . esc_attr($secondary_color) . ' 100%); color: white; text-align: center; padding: 30px 20px;">';
        
        if (!empty($school_logo)) {
            $html .= '<div style="margin-bottom: 15px;">
                <img src="' . esc_url($school_logo) . '" alt="' . esc_attr($school_name) . '" class="logo-img" style="max-width: 150px; max-height: 60px; display: block; margin: 0 auto;" />
            </div>';
        }
        
        $html .= '<div class="header-logo" style="font-size: 28px; font-weight: bold; margin-bottom: 10px;">' . esc_html($school_name) . '</div>
            <div style="font-size: 16px; opacity: 0.9;">Admission Enquiry Confirmation</div>
        </div>
        
        <!-- Enquiry Number Highlight -->
        <div style="background-color: #fef3c7; padding: 20px 25px; text-align: center; border-bottom: 3px solid ' . esc_attr($primary_color) . ';">
            <div style="font-size: 13px; color: #92400e; margin-bottom: 8px; font-weight: bold;">YOUR ENQUIRY REFERENCE NUMBER</div>
            <div class="enquiry-number-box" style="font-size: 36px; font-weight: bold; color: ' . esc_attr($primary_color) . '; letter-spacing: 2px; font-family: monospace;">' . esc_html($enquiry_number) . '</div>
            <div style="font-size: 12px; color: #92400e; margin-top: 8px;">Save this number for your reference</div>
        </div>
        
        <!-- Success Message -->
        <div class="content-section" style="padding: 30px 25px; text-align: center; background-color: #f8fafc;">
            <div style="background-color: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #22c55e;">
                <div style="font-size: 20px; font-weight: bold; margin-bottom: 5px;">✅ Enquiry Successfully Submitted!</div>
            </div>
            
            <p style="color: #475569; font-size: 16px; margin: 0;">Dear Parent/Guardian,</p>
            <p style="color: #475569; font-size: 15px; line-height: 1.6; margin: 15px 0 0 0;">
                Thank you for your interest in ' . esc_html($school_name) . '. We have received your admission enquiry for <strong>' . esc_html($collected_data['student_name'] ?? 'your ward') . '</strong> and are excited to help you through the admission process.
            </p>
        </div>
        
        <!-- Enquiry Details -->
        <div class="content-section" style="padding: 0 25px 20px;">
            <h3 style="color: ' . esc_attr($primary_color) . '; font-size: 18px; margin-bottom: 15px; border-bottom: 2px solid ' . esc_attr($primary_color) . '; padding-bottom: 8px;">📋 Enquiry Details</h3>
            
            <table class="details-table" style="width: 100%; border-collapse: collapse; font-size: 15px;">
                <tr style="background-color: ' . esc_attr($primary_color) . '; color: white;">
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold;">Enquiry Reference</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold;">' . esc_html($enquiry_number) . '</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151;">Student Name</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937;">' . esc_html($collected_data['student_name'] ?? 'Not provided') . '</td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151;">Grade/Class</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937;">' . esc_html($collected_data['grade'] ?? 'Not provided') . '</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151;">Board</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937;">' . esc_html($collected_data['board'] ?? 'Not provided') . '</td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151;">Date of Birth</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937;">' . esc_html($collected_data['date_of_birth'] ?? 'Not provided') . '</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151;">Email</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937;">' . esc_html($collected_data['email'] ?? 'Not provided') . '</td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151;">Phone</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937;">' . esc_html($collected_data['phone'] ?? 'Not provided') . '</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151;">Submission Time</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937;">' . esc_html($this->get_indian_time('d-m-Y H:i:s')) . ' IST</td>
                </tr>
            </table>
        </div>
        
        <!-- Next Steps -->
        <div class="content-section" style="padding: 0 25px 20px;">
            <h3 style="color: ' . esc_attr($primary_color) . '; font-size: 18px; margin-bottom: 15px; border-bottom: 2px solid ' . esc_attr($primary_color) . '; padding-bottom: 8px;">🔄 Next Steps</h3>
            <ol style="color: #475569; font-size: 14px; line-height: 2; margin: 0; padding-left: 20px;">
                <li>Our admission team will review your enquiry</li>
                <li>We will contact you within 24 hours at ' . esc_html($collected_data['phone'] ?? 'the phone number provided') . '</li>
                <li>Detailed information about the admission process will be shared</li>
                <li>Campus visit will be scheduled as per your convenience</li>
                <li>Complete application form will be provided for submission</li>
            </ol>
        </div>
        
        <!-- Contact Information -->
        <div class="content-section" style="padding: 0 25px 20px;">
            <h3 style="color: ' . esc_attr($primary_color) . '; font-size: 18px; margin-bottom: 15px; border-bottom: 2px solid ' . esc_attr($primary_color) . '; padding-bottom: 8px;">📞 Contact Us</h3>
            <div style="background-color: #f0f9ff; padding: 15px; border-radius: 8px; border-left: 4px solid ' . esc_attr($primary_color) . ';">
                <p style="margin: 0 0 10px 0; color: #475569; font-size: 14px;">
                    <strong>For immediate assistance or queries:</strong>
                </p>
                <p style="margin: 5px 0; color: #1f2937; font-size: 14px;">
                    📞 Phone: ' . esc_html($school_phone) . '
                </p>
                <p style="margin: 5px 0; color: #1f2937; font-size: 14px;">
                    📧 Email: ' . esc_html($school_email) . '
                </p>
                <p style="margin: 5px 0; color: #1f2937; font-size: 14px;">
                    🌐 Website: <a href="https://www.vikasconcept.com" style="color: ' . esc_attr($primary_color) . '; text-decoration: none;">Visit Our Website</a>
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div style="background-color: #f8fafc; padding: 20px 25px; text-align: center; border-top: 1px solid #e5e7eb;">
            <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 12px;">
                This is an automated email. Please do not reply to this message.
            </p>
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">
                © 2025 ' . esc_html($school_name) . '. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
                <div style="color: #92400e; font-size: 15px; line-height: 1.6;">
                    <p style="margin: 0 0 10px 0;"><strong>📱 Phone:</strong> 7702800800 / 9248111448</p>
                    <p style="margin: 0 0 10px 0;"><strong>📧 Email:</strong> admissions@vikasconcept.com</p>
                    <p style="margin: 0;"><strong>🕒 Office Hours:</strong> Monday to Saturday, 9:00 AM - 6:00 PM</p>
                </div>
            </div>
        </div>
        
        <!-- Important Note -->
        <div style="background-color: ' . esc_attr($primary_color) . '; color: white; padding: 20px 25px; text-align: center;">
            <div style="font-size: 16px; font-weight: bold; margin-bottom: 5px;">📌 Please Save Your Enquiry Number</div>
            <div style="font-size: 24px; font-weight: bold; background-color: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 6px; display: inline-block;">' . esc_html($enquiry_number) . '</div>
            <div style="font-size: 14px; margin-top: 8px; opacity: 0.9;">You\'ll need this number for all future communications</div>
        </div>
        
        <!-- Footer -->
        <div style="background-color: #f8fafc; padding: 25px; text-align: center; border-top: 1px solid #e5e7eb;">
            <p style="color: #6b7280; margin: 0 0 10px 0; font-size: 16px;">We look forward to welcoming your child to the <strong>' . esc_html($school_name) . '</strong> family!</p>
            <p style="color: #6b7280; margin: 0; font-size: 14px;">
                Warm regards,<br>
                <strong>' . esc_html($school_name) . ' Admissions Team</strong>
            </p>
            <hr style="margin: 20px 0; border: none; height: 1px; background-color: #e5e7eb;">
            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                This is an automated confirmation email from our admission enquiry system.<br>
                Please do not reply to this email.
            </p>
        </div>
        
    </div>
</body>
</html>';
        
        return $html;
    }

    /**
     * Build HTML email content for school notification
     */
    private function build_school_notification_html($collected_data, $enquiry_number, $school_name) {
        // Get branding colors and logo from school settings
        $primary_color = get_option('edubot_primary_color', '#4facfe');
        $secondary_color = get_option('edubot_secondary_color', '#00f2fe');
        $school_logo = get_option('edubot_school_logo', '');
        
        // Calculate age if DOB is provided
        $age_info = '';
        if (!empty($collected_data['date_of_birth'])) {
            try {
                $birth_date = new DateTime($collected_data['date_of_birth']);
                $current_date = new DateTime();
                $age = $current_date->diff($birth_date)->y;
                $age_info = ' (Age: ' . $age . ' years)';
            } catch (Exception $e) {
                $age_info = '';
            }
        }

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Admission Enquiry - Action Required</title>
    <style>
        @media only screen and (max-width: 600px) {
            .container { width: 100% !important; padding: 10px !important; }
            .header-logo { font-size: 22px !important; }
            .content-section { padding: 15px !important; }
            .details-table { font-size: 13px !important; }
            .details-table td { padding: 8px !important; }
            .logo-img { max-width: 120px !important; max-height: 50px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f7fa;">
    <div class="container" style="max-width: 650px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, ' . esc_attr($primary_color) . ' 0%, ' . esc_attr($secondary_color) . ' 100%); color: white; text-align: center; padding: 25px 20px;">';
        
        if (!empty($school_logo)) {
            $html .= '<div style="margin-bottom: 15px;">
                <img src="' . esc_url($school_logo) . '" alt="' . esc_attr($school_name) . '" class="logo-img" style="max-width: 150px; max-height: 60px; display: block; margin: 0 auto;" />
            </div>';
        }
        
        $html .= '<div class="header-logo" style="font-size: 26px; font-weight: bold; margin-bottom: 8px;">🔔 New Admission Enquiry</div>
            <div style="font-size: 15px; opacity: 0.9;">' . esc_html($school_name) . ' - Action Required</div>
        </div>
        
        <!-- Alert Banner -->
        <div class="content-section" style="padding: 20px 25px; text-align: center; background-color: #f0f9ff;">
            <div style="background-color: #dbeafe; color: #1e40af; padding: 15px; border-radius: 8px; border-left: 4px solid ' . esc_attr($primary_color) . ';">
                <div style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">⚡ Priority: Contact within 24 hours</div>
                <div style="font-size: 14px;">Enquiry Number: <strong>' . esc_html($enquiry_number) . '</strong> | Submitted: ' . $this->get_indian_time('d-m-Y H:i:s') . ' (IST)</div>
            </div>
        </div>
        
        <!-- Student Information -->
        <div class="content-section" style="padding: 0 25px 20px;">
            <h3 style="color: ' . esc_attr($primary_color) . '; font-size: 18px; margin-bottom: 15px; border-bottom: 2px solid ' . esc_attr($primary_color) . '; padding-bottom: 8px;">👨‍🎓 Student Information</h3>
            
            <table class="details-table" style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr style="background-color: #f8fafc;">
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151; width: 35%;">Student Name</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937; font-weight: 600;">' . esc_html($collected_data['student_name'] ?? 'Not provided') . '</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151;">Grade/Class Seeking</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937;">' . esc_html($collected_data['grade'] ?? 'Not provided') . '</td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151;">Board Preference</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937;">' . esc_html($collected_data['board'] ?? 'Not provided') . '</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151;">Academic Year</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937;">' . esc_html($collected_data['academic_year'] ?? '2026-27') . '</td>
                </tr>';
        
        if (!empty($collected_data['date_of_birth'])) {
            $html .= '<tr style="background-color: #f8fafc;">
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151;">Date of Birth</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937;">' . esc_html($collected_data['date_of_birth']) . $age_info . '</td>
                </tr>';
        }
        
        if (!empty($collected_data['gender'])) {
            $html .= '<tr>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151;">Gender</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937;">' . esc_html($collected_data['gender']) . '</td>
                </tr>';
        }
        
        $html .= '</table>
        </div>
        
        <!-- Parent/Guardian Information -->
        <div class="content-section" style="padding: 0 25px 20px;">
            <h3 style="color: ' . esc_attr($primary_color) . '; font-size: 18px; margin-bottom: 15px; border-bottom: 2px solid ' . esc_attr($primary_color) . '; padding-bottom: 8px;">👨‍👩‍👧‍👦 Parent/Guardian Contact Details</h3>
            
            <table class="details-table" style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr style="background-color: #fef3c7;">
                    <td style="padding: 12px; border: 1px solid #f59e0b; font-weight: bold; color: #92400e; width: 35%;">📧 Email</td>
                    <td style="padding: 12px; border: 1px solid #f59e0b; color: #92400e; font-weight: 600;"><a href="mailto:' . esc_attr($collected_data['email'] ?? '') . '" style="color: #92400e; text-decoration: none;">' . esc_html($collected_data['email'] ?? 'Not provided') . '</a></td>
                </tr>
                <tr style="background-color: #fef3c7;">
                    <td style="padding: 12px; border: 1px solid #f59e0b; font-weight: bold; color: #92400e;">📱 Phone</td>
                    <td style="padding: 12px; border: 1px solid #f59e0b; color: #92400e; font-weight: 600;"><a href="tel:' . esc_attr($collected_data['phone'] ?? '') . '" style="color: #92400e; text-decoration: none;">' . esc_html($collected_data['phone'] ?? 'Not provided') . '</a></td>
                </tr>';
        
        if (!empty($collected_data['parent_name'])) {
            $html .= '<tr style="background-color: #f8fafc;">
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151;">Parent Name</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937;">' . esc_html($collected_data['parent_name']) . '</td>
                </tr>';
        }
        
        if (!empty($collected_data['address'])) {
            $html .= '<tr style="background-color: #f8fafc;">
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151;">Address</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937;">' . esc_html($collected_data['address']) . '</td>
                </tr>';
        }
        
        $html .= '<tr style="background-color: #f8fafc;">
                    <td style="padding: 12px; border: 1px solid #e5e7eb; font-weight: bold; color: #374151;">Enquiry Source</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; color: #1f2937;">Website Chatbot</td>
                </tr>
            </table>
        </div>
        
        <!-- Action Items -->
        <div class="content-section" style="padding: 0 25px 25px;">
            <h3 style="color: ' . esc_attr($primary_color) . '; font-size: 18px; margin-bottom: 15px; border-bottom: 2px solid ' . esc_attr($primary_color) . '; padding-bottom: 8px;">✅ Required Actions</h3>
            
            <div style="background-color: #f0f9ff; border: 1px solid ' . esc_attr($primary_color) . '; padding: 20px; border-radius: 8px;">
                <div style="color: ' . esc_attr($primary_color) . '; font-size: 15px; line-height: 1.8;">
                    <div style="margin-bottom: 12px; display: flex; align-items: center;">
                        <span style="background-color: ' . esc_attr($primary_color) . '; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; margin-right: 10px;">1</span>
                        <span><strong>Contact parent within 24 hours</strong> via phone or email</span>
                    </div>
                    <div style="margin-bottom: 12px; display: flex; align-items: center;">
                        <span style="background-color: ' . esc_attr($primary_color) . '; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; margin-right: 10px;">2</span>
                        <span>Schedule campus visit and provide school tour</span>
                    </div>
                    <div style="margin-bottom: 12px; display: flex; align-items: center;">
                        <span style="background-color: ' . esc_attr($primary_color) . '; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; margin-right: 10px;">3</span>
                        <span>Send detailed admission kit with fee structure</span>
                    </div>
                    <div style="margin-bottom: 12px; display: flex; align-items: center;">
                        <span style="background-color: ' . esc_attr($primary_color) . '; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; margin-right: 10px;">4</span>
                        <span>Provide information about curriculum and programs</span>
                    </div>
                    <div style="display: flex; align-items: center;">
                        <span style="background-color: ' . esc_attr($primary_color) . '; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; margin-right: 10px;">5</span>
                        <span>Update enquiry status in the system</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Parent Notification Status -->
        <div style="background-color: #dcfce7; border: 1px solid #16a34a; padding: 20px 25px; margin: 0;">
            <h4 style="color: #166534; margin: 0 0 10px 0; font-size: 16px;">📧 Parent Notification Status</h4>
            <p style="color: #166534; margin: 0; font-size: 14px;">
                ✅ The parent has already received an automated confirmation email with enquiry number: <strong>' . esc_html($enquiry_number) . '</strong><br>
                📍 This enquiry has been automatically stored in the database for your reference.
            </p>
        </div>
        
        <!-- Footer -->
        <div style="background-color: #374151; color: white; padding: 25px; text-align: center;">
            <p style="margin: 0 0 10px 0; font-size: 16px; font-weight: bold;">' . esc_html($school_name) . '</p>
            <p style="margin: 0 0 15px 0; font-size: 14px; opacity: 0.8;">Automated Enquiry Management System</p>
            <div style="background-color: rgba(255,255,255,0.1); padding: 10px; border-radius: 6px;">
                <p style="margin: 0; font-size: 12px; opacity: 0.7;">
                    This enquiry was generated automatically by the EduBot Pro chatbot system.<br>
                    Please ensure prompt follow-up to maintain excellent customer service standards.
                </p>
            </div>
        </div>
        
    </div>
</body>
</html>';
        
        return $html;
    }

    /**
     * Send WhatsApp confirmation to parent
     */
    private function send_parent_whatsapp_confirmation($collected_data, $enquiry_number, $school_name) {
        try {
            // Enhanced debug logging to specific file
            $debug_file = '/home/epistemo-stage/htdocs/stage.epistemo.in/wp-content/edubot-debug.log';
            $timestamp = $this->get_indian_time('Y-m-d H:i:s');
            
            $debug_msg = "\n=== EDUBOT WHATSAPP DEBUG [$timestamp] ===\n";
            $debug_msg .= "Function: send_parent_whatsapp_confirmation\n";
            $debug_msg .= "Enquiry Number: $enquiry_number\n";
            $debug_msg .= "School Name: $school_name\n";
            $debug_msg .= "Collected Data: " . json_encode($collected_data) . "\n";
            file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
            
            error_log("EduBot: Starting WhatsApp confirmation for enquiry {$enquiry_number}");
            
            // Check if WhatsApp notifications are enabled
            $whatsapp_enabled = get_option('edubot_whatsapp_notifications', 0);
            $debug_msg = "1. WhatsApp Notifications Enabled: " . ($whatsapp_enabled ? 'YES' : 'NO') . "\n";
            file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
            
            error_log("EduBot: WhatsApp notifications enabled: " . ($whatsapp_enabled ? 'YES' : 'NO'));
            if (!$whatsapp_enabled) {
                $debug_msg = "❌ STOPPED: WhatsApp notifications are disabled in settings\n";
                file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
                error_log('EduBot: WhatsApp notifications are disabled in settings');
                return false;
            }
            
            // Check if WhatsApp API is configured
            $whatsapp_token = get_option('edubot_whatsapp_token', '');
            $whatsapp_provider = get_option('edubot_whatsapp_provider', '');
            $whatsapp_phone_id = get_option('edubot_whatsapp_phone_id', ''); // Fixed: correct option name
            
            $debug_msg = "2. WhatsApp API Configuration:\n";
            $debug_msg .= "   - Provider: " . ($whatsapp_provider ?: 'NOT SET') . "\n";
            $debug_msg .= "   - Token: " . (empty($whatsapp_token) ? 'NOT SET' : 'CONFIGURED (' . substr($whatsapp_token, 0, 10) . '...)') . "\n";
            $debug_msg .= "   - Phone Number ID: " . ($whatsapp_phone_id ?: 'NOT SET') . "\n";
            file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
            
            error_log("EduBot: WhatsApp provider: " . ($whatsapp_provider ?: 'NOT SET'));
            error_log("EduBot: WhatsApp token: " . (empty($whatsapp_token) ? 'NOT SET' : 'CONFIGURED'));
            
            // Check for required configuration
            if (empty($whatsapp_token) || empty($whatsapp_provider)) {
                $debug_msg = "❌ STOPPED: WhatsApp API not configured (missing token or provider)\n";
                file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
                error_log('EduBot: WhatsApp API not configured (missing token or provider)');
                return false;
            }
            
            // For Meta provider, Phone Number ID is required
            if ($whatsapp_provider === 'meta' && empty($whatsapp_phone_id)) {
                $debug_msg = "❌ STOPPED: Meta WhatsApp API requires Phone Number ID but it's missing\n";
                file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
                error_log('EduBot: Meta WhatsApp API requires Phone Number ID but it\'s not configured');
                return false;
            }
            
            $phone = $collected_data['phone'] ?? '';
            $debug_msg = "3. Phone Number Processing:\n";
            $debug_msg .= "   - Original Phone: " . ($phone ?: 'NOT PROVIDED') . "\n";
            file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
            
            error_log("EduBot: Phone number from form: " . ($phone ?: 'NOT PROVIDED'));
            if (empty($phone)) {
                $debug_msg = "❌ STOPPED: No phone number provided for WhatsApp confirmation\n";
                file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
                error_log('EduBot: No phone number provided for WhatsApp confirmation');
                return false;
            }
            
            // Clean and validate phone number
            $original_phone = $phone;
            $phone = preg_replace('/[^0-9+]/', '', $phone);
            
            $debug_msg = "   - Cleaned Phone: $phone\n";
            file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
            
            if (empty($phone)) {
                $debug_msg = "❌ STOPPED: Invalid phone number format for WhatsApp: $original_phone\n";
                file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
                error_log('EduBot: Invalid phone number format for WhatsApp: ' . $collected_data['phone']);
                return false;
            }
            
            // Ensure phone number has country code (Meta API expects without + prefix)
            if (preg_match('/^\+/', $phone)) {
                $phone = ltrim($phone, '+'); // Remove + prefix for Meta API
            } else {
                // Add India country code if not present
                if (preg_match('/^[6-9]\d{9}$/', $phone)) {
                    $phone = '91' . $phone;
                } elseif (!preg_match('/^91[6-9]\d{9}$/', $phone)) {
                    $debug_msg = "❌ STOPPED: Unable to format phone number for WhatsApp: $phone\n";
                    file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
                    error_log('EduBot: Unable to format phone number for WhatsApp: ' . $phone);
                    return false;
                }
            }
            
            $debug_msg = "   - Final Formatted Phone: $phone\n";
            file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
            
            // Get WhatsApp message template from settings
            $template = get_option('edubot_whatsapp_template', "Admission Enquiry Confirmation
Dear {parent_name},

Thank you for your enquiry at {school_name}. Your enquiry number is {enquiry_number} for Grade {grade}.

We have received your application on {submission_date} and will contact you within 24-48 hours with the next steps.

Best regards,
Admissions Team
Reply STOP to unsubscribe");
            $template_type = get_option('edubot_whatsapp_template_type', 'freeform');
            $template_name = get_option('edubot_whatsapp_template_name', 'admission_confirmation');
            $template_language = get_option('edubot_whatsapp_template_language', 'en');
            
            // Prepare message data based on template type
            $debug_msg = "4. Template Configuration:\n";
            $debug_msg .= "   - Template Type: $template_type\n";
            $debug_msg .= "   - Template Name: $template_name\n";
            $debug_msg .= "   - Template Language: $template_language\n";
            file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
            
            if ($template_type === 'business_template') {
                // For Meta WhatsApp Business API templates - use simplified format for our send_meta_whatsapp method
                $template_params = [
                    $collected_data['parent_name'] ?? $collected_data['student_name'] ?? 'Parent', // {{1}}
                    $enquiry_number, // {{2}}
                    $school_name, // {{3}}
                    $collected_data['grade'] ?? '', // {{4}}
                    $this->get_indian_time('d/m/Y H:i:s') . ' IST' // {{5}}
                ];
                
                $debug_msg = "5. Business Template Parameters:\n";
                $debug_msg .= "   - {{1}} Parent Name: " . $template_params[0] . "\n";
                $debug_msg .= "   - {{2}} Enquiry Number: " . $template_params[1] . "\n";
                $debug_msg .= "   - {{3}} School Name: " . $template_params[2] . "\n";
                $debug_msg .= "   - {{4}} Grade: " . $template_params[3] . "\n";
                $debug_msg .= "   - {{5}} Date: " . $template_params[4] . "\n";
                file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
                
                $message_data = [
                    'phone' => $phone,
                    'template_name' => $template_name,
                    'template_language' => $template_language,
                    'template_params' => $template_params
                ];
                
                $message = $message_data;
            } else {
                // For free-form messages, replace placeholders in template
                $placeholders = [
                    '{school_name}' => $school_name,
                    '{parent_name}' => $collected_data['parent_name'] ?? $collected_data['student_name'] ?? 'Parent',
                    '{student_name}' => $collected_data['student_name'] ?? '',
                    '{enquiry_number}' => $enquiry_number,
                    '{grade}' => $collected_data['grade'] ?? '',
                    '{board}' => $collected_data['board'] ?? '',
                    '{academic_year}' => $collected_data['academic_year'] ?? '2026-27',
                    '{submission_date}' => $this->get_indian_time('d/m/Y'),
                    '{phone}' => $collected_data['phone'] ?? '',
                    '{email}' => $collected_data['email'] ?? ''
                ];
                
                $message = str_replace(array_keys($placeholders), array_values($placeholders), $template);
            }
            
            // Send WhatsApp message using API integrations
            $debug_msg = "6. Sending WhatsApp Message:\n";
            $debug_msg .= "   - Method: " . ($template_type === 'business_template' ? 'send_meta_whatsapp (Business Template)' : 'send_whatsapp (Freeform)') . "\n";
            $debug_msg .= "   - Phone: $phone\n";
            if ($template_type === 'business_template') {
                $debug_msg .= "   - Template Name: " . $message_data['template_name'] . "\n";
                $debug_msg .= "   - Template Language: " . $message_data['template_language'] . "\n";
                $debug_msg .= "   - Template Parameters: " . json_encode($message_data['template_params']) . "\n";
            } else {
                $debug_msg .= "   - Message Data: " . json_encode($message) . "\n";
            }
            file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
            
            if (!class_exists('EduBot_API_Integrations')) {
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-api-integrations.php';
            }
            
            $api_integrations = new EduBot_API_Integrations();
            
            // Use the correct API method based on template type
            if ($template_type === 'business_template') {
                // Prepare API keys array
                $api_keys = [
                    'whatsapp_phone_id' => get_option('edubot_whatsapp_phone_id', ''),
                    'whatsapp_token' => get_option('edubot_whatsapp_token', '')
                ];
                
                // Format message for Meta Business API with CORRECT structure
                // Must include header component (empty) + body component with parameters
                $formatted_message = [
                    'type' => 'template',
                    'template' => [
                        'name' => $message_data['template_name'],
                        'language' => ['code' => $message_data['template_language']],
                        'components' => [
                            [
                                'type' => 'header',
                                'parameters' => []  // Header component with empty parameters
                            ],
                            [
                                'type' => 'body',
                                'parameters' => array_map(function($param) {
                                    return ['type' => 'text', 'text' => (string)$param];
                                }, $message_data['template_params'])
                            ]
                        ]
                    ]
                ];
                
                $result = $api_integrations->send_meta_whatsapp($phone, $formatted_message, $api_keys);
            } else {
                $result = $api_integrations->send_whatsapp($phone, $message);
            }
            
            $debug_msg = "7. API Response: " . json_encode($result) . "\n";
            file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
            
            if ($result && !is_wp_error($result)) {
                // Check if response contains messages (Meta API format)
                if (is_array($result) && isset($result['messages'][0]['id'])) {
                    $debug_msg = "✅ SUCCESS: WhatsApp message sent successfully to $phone\n";
                    $debug_msg .= "   - Message ID: {$result['messages'][0]['id']}\n";
                    file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
                    error_log("EduBot: WhatsApp confirmation sent successfully to {$phone}");
                    return true;
                } elseif (is_array($result) && isset($result['success']) && $result['success']) {
                    // Fallback for other API formats
                    $debug_msg = "✅ SUCCESS: WhatsApp message sent successfully to $phone\n";
                    if (isset($result['message_id'])) {
                        $debug_msg .= "   - Message ID: {$result['message_id']}\n";
                    }
                    file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
                    error_log("EduBot: WhatsApp confirmation sent successfully to {$phone}");
                    return true;
                } else {
                    $debug_msg = "❌ FAILED: API returned unsuccessful response\n";
                    file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
                    error_log("EduBot: WhatsApp API returned unsuccessful response: " . json_encode($result));
                    return false;
                }
            } else {
                $error_msg = is_wp_error($result) ? $result->get_error_message() : 'Unknown error';
                $debug_msg = "❌ FAILED: Error sending WhatsApp message: $error_msg\n";
                file_put_contents($debug_file, $debug_msg, FILE_APPEND | LOCK_EX);
                error_log("EduBot: Failed to send WhatsApp confirmation to {$phone}: {$error_msg}");
                return false;
            }
            
        } catch (Exception $e) {
            error_log('EduBot: WhatsApp confirmation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Normalize grade input to handle common misspellings and typos (version 2)
     */
    private function normalize_grade_input_v2($message) {
        // Remove extra spaces and normalize
        $message = preg_replace('/\s+/', ' ', strtolower(trim($message)));
        
        // Handle common misspellings of "grade"
        $grade_variations = array(
            '/\bgrad\b/' => 'grade',
            '/\bograde\b/' => 'grade', 
            '/\bgrde\b/' => 'grade',
            '/\bgrsd\b/' => 'grade',
            '/\bgrd\b/' => 'grade'
        );
        
        foreach ($grade_variations as $pattern => $replacement) {
            $message = preg_replace($pattern, $replacement, $message);
        }
        
        // Handle common misspellings of "class"
        $class_variations = array(
            '/\bclas\b/' => 'class',
            '/\bclss\b/' => 'class',
            '/\bcalss\b/' => 'class'
        );
        
        foreach ($class_variations as $pattern => $replacement) {
            $message = preg_replace($pattern, $replacement, $message);
        }
        
        return $message;
    }

    /**
     * Extract grade with enhanced fuzzy matching (version 2)
     */
    private function extract_fuzzy_grade_v2($message) {
        // Handle nursery and pre-school grades first
        if (strpos($message, 'nursery') !== false || strpos($message, 'nursary') !== false) {
            return 'Nursery';
        }
        if (strpos($message, 'pre-kg') !== false || strpos($message, 'prekg') !== false) {
            return 'Pre-KG';
        }
        if (strpos($message, 'lkg') !== false) {
            return 'LKG';
        }
        if (strpos($message, 'ukg') !== false) {
            return 'UKG';
        }
        
        // Enhanced pattern matching for grades with numbers
        $patterns = array(
            // Grade 10, grad10, ograde10, etc.
            '/(?:grade|grad|ograde|grde|grd)\s*(\d{1,2})/i',
            // Class 10, clas10, etc.
            '/(?:class|clas|clss|calss)\s*(\d{1,2})/i',
            // 10th, 10st (common typo)
            '/(\d{1,2})(?:th|st|nd|rd)\s*(?:grade|class|grad|clas)?/i',
            // Direct number patterns like "grade10", "class10"
            '/(?:grade|grad|class|clas)(\d{1,2})/',
            // Numbers with space variations like "grade 1 0" -> "grade 10"
            '/(?:grade|grad|class|clas)\s*(\d)\s*(\d)/'
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                // Handle the case where we have two digit captures (like "grade 1 0")
                if (isset($matches[2]) && is_numeric($matches[2])) {
                    $grade_number = $matches[1] . $matches[2];
                } else {
                    $grade_number = $matches[1];
                }
                
                // Validate grade number is reasonable (1-12)
                if (is_numeric($grade_number) && $grade_number >= 1 && $grade_number <= 12) {
                    // Determine if it should be "Grade" or "Class" based on original input
                    if (preg_match('/class/i', $message)) {
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
     * Get UTM parameters from the current session or request
     */
    private function get_utm_data() {
        $utm_data = array();
        
        // Check session first (preferred)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // UTM parameters and click IDs to capture
        $utm_params = array(
            // Standard UTM parameters
            'utm_source', 'utm_medium', 'utm_campaign', 
            'utm_term', 'utm_content',
            // Click IDs from major platforms
            'gclid',        // Google Ads Click ID
            'fbclid',       // Facebook Click ID  
            'msclkid',      // Microsoft Ads Click ID
            'ttclid',       // TikTok Click ID
            'twclid',       // Twitter Click ID
            '_kenshoo_clickid', // Kenshoo/Sizmek Click ID
            'irclickid',    // Impact Radius Click ID
            'li_fat_id',    // LinkedIn Click ID
            'sc_click_id',  // Snapchat Click ID
            'yclid'         // Yandex Click ID
        );
        
        foreach ($utm_params as $param) {
            // Priority 1: Check current request FIRST (fresh data from URL)
            if (isset($_GET[$param])) {
                $utm_data[$param] = sanitize_text_field($_GET[$param]);
                // Update both session AND cookie with fresh data
                $_SESSION['edubot_' . $param] = $utm_data[$param];
                error_log("EduBot get_utm_data: Using UTM from current request: {$param} = " . $utm_data[$param]);
            }
            // Priority 2: Fallback to POST data
            elseif (isset($_POST[$param])) {
                $utm_data[$param] = sanitize_text_field($_POST[$param]);
                $_SESSION['edubot_' . $param] = $utm_data[$param];
            }
            // Priority 3: Check session (intermediate storage)
            elseif (isset($_SESSION['edubot_' . $param])) {
                $utm_data[$param] = sanitize_text_field($_SESSION['edubot_' . $param]);
            }
            // Priority 4: Check cookies (long-term persistence, 30+ days)
            // This is the MOST IMPORTANT for user returning after 1+ month
            elseif (isset($_COOKIE['edubot_' . $param])) {
                $utm_data[$param] = sanitize_text_field($_COOKIE['edubot_' . $param]);
                // Re-populate session from cookie
                $_SESSION['edubot_' . $param] = $utm_data[$param];
                error_log("EduBot get_utm_data: Using UTM from persistent cookie (30 day): {$param} = " . $utm_data[$param]);
            }
        }
        
        // Add timestamp when UTM data was first captured
        if (!empty($utm_data) && !isset($_SESSION['edubot_utm_captured_at'])) {
            $_SESSION['edubot_utm_captured_at'] = current_time('mysql');
        }
        
        // Get timestamp from session or cookie
        if (isset($_SESSION['edubot_utm_captured_at'])) {
            $utm_data['captured_at'] = $_SESSION['edubot_utm_captured_at'];
        } elseif (isset($_COOKIE['edubot_utm_captured_at'])) {
            $utm_data['captured_at'] = sanitize_text_field($_COOKIE['edubot_utm_captured_at']);
            error_log("EduBot get_utm_data: Using captured_at timestamp from cookie: " . $utm_data['captured_at']);
        }
        
        return $utm_data;
    }

    /**
     * Replace placeholders in button response text with actual school data
     */
    private function replace_placeholders($text) {
        $replacements = array(
            '{school_name}' => get_option('edubot_school_name', 'Our School'),
            '{school_phone}' => get_option('edubot_school_phone', ''),
            '{school_email}' => get_option('edubot_school_email', ''),
            '{school_address}' => get_option('edubot_school_address', ''),
            '{school_website}' => get_option('edubot_school_website', '')
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}

// Initialize the shortcode handler
new EduBot_Shortcode();
