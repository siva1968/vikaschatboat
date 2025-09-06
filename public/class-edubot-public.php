<?php

/**
 * The public-facing functionality of the plugin.
 */
class EduBot_Public {

    /**
     * The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     */
    private $version;

    /**
     *         // Initialize chatbot with session ID
        console.log('EduBot: Initializing chatbot widget...');
        console.log('EduBot: Session ID:', '<?php echo esc_js($session_id); ?>');
        
        if (typeof EduBotChatWidget !== 'undefined') {
            console.log('EduBot: EduBotChatWidget found, initializing...');
            EduBotChatWidget.init('<?php echo esc_js($session_id); ?>');
        } else {
            console.error('EduBot: EduBotChatWidget not found! Check if JavaScript is loaded.');
        }

        // Debug input field functionality
        $(document).ready(function() {
            var inputField = $('#edubot-chat-input');
            console.log('EduBot Debug: Input field found:', inputField.length);
            console.log('EduBot Debug: Input field disabled:', inputField.prop('disabled'));
            console.log('EduBot Debug: Input field visible:', inputField.is(':visible'));
            
            // Test input field after 1 second
            setTimeout(function() {
                console.log('EduBot Debug: Testing input field after 1s...');
                inputField.focus();
                console.log('EduBot Debug: Focus attempted on input field');
            }, 1000);
        });
        </script>the class and set its properties.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            EDUBOT_PRO_PLUGIN_URL . 'public/css/edubot-public.css',
            array(),
            $this->version,
            'all'
        );

        // Add custom branding styles
        $this->add_custom_branding_styles();
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        // Use version only for cache busting - update when JS changes
        // Note: time() was removed to allow proper browser caching
        wp_enqueue_script(
            $this->plugin_name,
            EDUBOT_PRO_PLUGIN_URL . 'public/js/edubot-public.js',
            array('jquery'),
            $this->version, // Proper versioning without cache busting
            false
        );

        // Localize script for AJAX
        wp_localize_script(
            $this->plugin_name,
            'edubot_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('edubot_nonce'),
                'version' => $this->version . '.' . time(),
                'strings' => array(
                    'connecting' => __('Connecting...', 'edubot-pro'),
                    'typing' => __('Bot is typing...', 'edubot-pro'),
                    'error' => __('Sorry, something went wrong. Please try again.', 'edubot-pro'),
                    'send' => __('Send', 'edubot-pro'),
                    'type_message' => __('Type your message...', 'edubot-pro'),
                    'new_application' => __('New Application', 'edubot-pro'),
                    'school_info' => __('School Information', 'edubot-pro'),
                    'contact_info' => __('Contact Information', 'edubot-pro'),
                    'admission' => __('Admission', 'edubot-pro'),
                    'school_visit' => __('School Visit', 'edubot-pro'),
                    'other_info' => __('Any Other Information', 'edubot-pro')
                )
            )
        );
    }

    /**
     * Add custom branding styles
     */
    private function add_custom_branding_styles() {
        // Generate CSS directly to avoid branding manager loops
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        
        // Force your database colors - Updated for your specific colors
        $primary_color = '#74a211';   // Your green primary color from database
        $secondary_color = '#113b02'; // Your dark green secondary color from database
        
        $custom_css = "
        :root {
            --edubot-primary-color: {$primary_color};
            --edubot-secondary-color: {$secondary_color};
            --edubot-gradient: linear-gradient(135deg, {$primary_color} 0%, {$secondary_color} 100%);
        }
        ";
        
        if ($custom_css) {
            wp_add_inline_style($this->plugin_name, $custom_css);
        }
    }

    /**
     * Render chatbot widget
     */
    public function render_chatbot() {
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        
        // Force your database colors - Updated for your specific colors
        $primary_color = '#74a211';   // Your green primary color from database
        $secondary_color = '#113b02'; // Your dark green secondary color from database
        
        $colors = array(
            'primary' => $primary_color,
            'secondary' => $secondary_color
        );
        
        // Generate unique session ID
        $session_id = $this->generate_session_id();
        
        ?>
        <style>
        /* Dynamic Branding for Auto-Widget - Override static CSS */
        #edubot-chatbot-widget.edubot-chatbot-widget {
            --edubot-primary-color: <?php echo esc_attr($colors['primary']); ?> !important;
            --edubot-secondary-color: <?php echo esc_attr($colors['secondary']); ?> !important;
            --edubot-gradient: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?> 0%, <?php echo esc_attr($colors['secondary']); ?> 100%) !important;
        }
        #edubot-chat-toggle.edubot-chat-toggle {
            background: var(--edubot-gradient) !important;
        }
        .edubot-chat-header {
            background: var(--edubot-gradient) !important;
        }
        .edubot-header-info {
            display: flex;
            align-items: center;
        }
        .edubot-header-logo img {
            max-height: 30px;
            max-width: 40px;
            object-fit: contain;
        }
        .edubot-send-btn {
            background: var(--edubot-gradient) !important;
        }
        .edubot-quick-actions {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        /* Maximum specificity selectors for your database colors */
        .edubot-chat-container .edubot-quick-actions .edubot-quick-action,
        #edubot-chat-container .edubot-quick-actions .edubot-quick-action,
        div.edubot-chat-container .edubot-quick-actions button.edubot-quick-action {
            background: #74a211 !important;
            background-color: #74a211 !important;
            border: 1px solid #74a211 !important;
            border-color: #74a211 !important;
            border-radius: 6px !important;
            padding: 12px 16px !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            text-align: left !important;
            transition: all 0.3s ease !important;
            color: white !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
        }
        .edubot-chat-container .edubot-quick-actions .edubot-quick-action:hover,
        #edubot-chat-container .edubot-quick-actions .edubot-quick-action:hover,
        div.edubot-chat-container .edubot-quick-actions button.edubot-quick-action:hover {
            background: linear-gradient(135deg, #74a211 0%, #113b02 100%) !important;
            background-color: #74a211 !important;
            border-color: #74a211 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 8px rgba(116, 162, 17, 0.25) !important;
        }
        .edubot-quick-action:active {
            transform: translateY(0);
        }
        /* Input field improvements */
        .edubot-chat-input {
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            padding: 8px 12px !important;
            font-size: 14px !important;
            width: 100% !important;
            box-sizing: border-box !important;
            outline: none !important;
            pointer-events: auto !important;
            user-select: text !important;
            -webkit-user-select: text !important;
        }
        .edubot-chat-input:focus {
            border-color: var(--edubot-primary-color) !important;
            box-shadow: 0 0 5px rgba(79, 172, 254, 0.3) !important;
        }
        .edubot-chat-input:disabled {
            background-color: #f5f5f5 !important;
            cursor: not-allowed !important;
        }
        /* Ensure container is properly positioned as floating widget */
        .edubot-chat-container {
            position: fixed !important;
            bottom: 20px !important;
            right: 20px !important;
            width: 380px !important;
            height: auto !important;
            max-height: 500px !important;
            z-index: 999999 !important;
            background: white !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15) !important;
            overflow: hidden !important;
            display: flex !important;
            flex-direction: column !important;
        }
        /* Ensure widget doesn't affect page layout */
        .edubot-chatbot-widget {
            position: fixed !important;
            bottom: 20px !important;
            right: 20px !important;
            z-index: 999999 !important;
            pointer-events: none !important;
        }
        .edubot-chatbot-widget * {
            pointer-events: auto !important;
        }
        /* Mobile responsive positioning */
        @media (max-width: 768px) {
            .edubot-chat-container {
                width: calc(100vw - 40px) !important;
                max-width: 350px !important;
                bottom: 10px !important;
                right: 10px !important;
                left: 10px !important;
                margin: 0 auto !important;
            }
            .edubot-chatbot-widget {
                bottom: 10px !important;
                right: 10px !important;
                left: 10px !important;
            }
        }
        /* Prevent body scroll issues */
        body.edubot-chat-open {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }
        /* Ensure no layout interference */
        .edubot-chatbot-widget {
            margin: 0 !important;
            padding: 0 !important;
            height: 0 !important;
            width: 0 !important;
            overflow: visible !important;
        }
        /* Hide by default, show when needed */
        .edubot-chat-container {
            display: none !important;
        }
        .edubot-chat-container.show,
        .edubot-chatbot-widget.chat-open .edubot-chat-container {
            display: flex !important;
        }
        /* Ensure toggle button is always visible and properly positioned */
        .edubot-chat-toggle {
            position: fixed !important;
            bottom: 20px !important;
            right: 20px !important;
            z-index: 999999 !important;
            display: flex !important;
        }
        @media (max-width: 768px) {
            .edubot-chat-toggle {
                bottom: 10px !important;
                right: 10px !important;
            }
        }
        </style>
        <div id="edubot-chatbot-widget" class="edubot-chatbot-widget">
            <div id="edubot-chat-toggle" class="edubot-chat-toggle">
                <div class="edubot-chat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12C2 13.54 2.38 14.99 3.06 16.26L2 22L7.74 20.94C9.01 21.62 10.46 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C10.74 20 9.54 19.68 8.5 19.12L8.19 18.95L4.55 19.85L5.45 16.21L5.28 15.9C4.72 14.86 4.4 13.66 4.4 12.4C4.4 7.92 7.92 4.4 12.4 4.4C16.88 4.4 20.4 7.92 20.4 12.4C20.4 16.88 16.88 20.4 12.4 20.4H12V20Z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="edubot-chat-label"><?php echo esc_html__('Chat with us', 'edubot-pro'); ?></div>
            </div>
            
            <div id="edubot-chat-container" class="edubot-chat-container">
                <div class="edubot-chat-header">
                    <div class="edubot-header-info">
                        <?php 
                        // Get logo directly from config to avoid branding manager loops
                        $logo_url = isset($config['school_info']['logo']) ? $config['school_info']['logo'] : get_option('edubot_school_logo', '');
                        if ($logo_url): ?>
                            <div class="edubot-header-logo" style="margin-right: 10px;">
                                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($config['school_info']['name']); ?>" style="max-height: 30px; max-width: 40px; object-fit: contain;">
                            </div>
                        <?php endif; ?>
                        <div>
                            <div class="edubot-header-title"><?php echo esc_html($config['school_info']['name']); ?></div>
                            <div class="edubot-header-subtitle"><?php echo esc_html__('Admission Assistant', 'edubot-pro'); ?></div>
                        </div>
                    </div>
                    <div class="edubot-header-actions">
                        <button id="edubot-minimize" class="edubot-minimize-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19 13H5V11H19V13Z" fill="currentColor"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="edubot-chat-messages" id="edubot-chat-messages">
                    <div class="edubot-welcome-message">
                        <div class="edubot-bot-message">
                            <div class="edubot-message-content">
                                <?php echo esc_html($config['chatbot_settings']['welcome_message']); ?>
                                <div class="edubot-quick-actions">
                                    <button class="edubot-quick-action" data-action="admission">1) Admission Enquiry</button>
                                    <button class="edubot-quick-action" data-action="curriculum">2) Curriculum & Classes</button>
                                    <button class="edubot-quick-action" data-action="facilities">3) Facilities</button>
                                    <button class="edubot-quick-action" data-action="contact_visit">4) Contact / Visit School</button>
                                    <button class="edubot-quick-action" data-action="online_enquiry">5) Online Enquiry Form</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="edubot-chat-input-area">
                    <div class="edubot-chat-options" id="edubot-chat-options" style="display: none;">
                        <!-- Quick reply options will be added here dynamically -->
                    </div>
                    
                    <div class="edubot-chat-input-container">
                        <input 
                            type="text" 
                            id="edubot-chat-input" 
                            class="edubot-chat-input" 
                            placeholder="<?php echo esc_attr__('Type your message...', 'edubot-pro'); ?>"
                            autocomplete="off"
                            autofocus
                        >
                        <button id="edubot-send-btn" class="edubot-send-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2.01 21L23 12L2.01 3L2 10L17 12L2 14L2.01 21Z" fill="currentColor"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Initialize chatbot with session ID
        console.log('EduBot: Initializing chatbot widget...');
        console.log('EduBot: Session ID:', '<?php echo esc_js($session_id); ?>');
        
        if (typeof EduBotChatWidget !== 'undefined') {
            console.log('EduBot: EduBotChatWidget found, initializing...');
            EduBotChatWidget.init('<?php echo esc_js($session_id); ?>');
        } else {
            console.error('EduBot: EduBotChatWidget not found! Check if JavaScript is loaded.');
        }
        
        // Check if elements exist
        jQuery(document).ready(function($) {
            console.log('EduBot: DOM ready, checking elements...');
            console.log('EduBot: Widget:', $('#edubot-chatbot-widget').length);
            console.log('EduBot: Toggle:', $('#edubot-chat-toggle').length);
            console.log('EduBot: Container:', $('#edubot-chat-container').length);
        });
        
        // ULTIMATE COLOR FIX - JavaScript-based approach
        document.addEventListener('DOMContentLoaded', function() {
            console.log('EduBot: Applying database colors via JavaScript...');
            
            // Wait for elements to load and force color application
            setTimeout(function() {
                const buttons = document.querySelectorAll('.edubot-quick-action, button.edubot-quick-action, [class*="edubot-quick-action"]');
                
                console.log('EduBot: Found ' + buttons.length + ' quick action buttons');
                
                buttons.forEach(function(button, index) {
                    // Remove any existing styles that might conflict
                    button.style.removeProperty('background');
                    button.style.removeProperty('background-color');
                    button.style.removeProperty('color');
                    button.style.removeProperty('border');
                    
                    // Apply database colors with maximum priority
                    button.style.setProperty('background', '<?php echo esc_js($primary_color); ?>', 'important');
                    button.style.setProperty('background-color', '<?php echo esc_js($primary_color); ?>', 'important');
                    button.style.setProperty('color', 'white', 'important');
                    button.style.setProperty('border', '1px solid <?php echo esc_js($primary_color); ?>', 'important');
                    button.style.setProperty('padding', '12px 16px', 'important');
                    button.style.setProperty('border-radius', '6px', 'important');
                    button.style.setProperty('font-weight', '500', 'important');
                    button.style.setProperty('cursor', 'pointer', 'important');
                    button.style.setProperty('display', 'block', 'important');
                    button.style.setProperty('width', '100%', 'important');
                    button.style.setProperty('box-sizing', 'border-box', 'important');
                    button.style.setProperty('margin-bottom', '8px', 'important');
                    button.style.setProperty('text-align', 'left', 'important');
                    button.style.setProperty('transition', 'all 0.3s ease', 'important');
                    
                    // Add hover effect via JavaScript
                    button.addEventListener('mouseenter', function() {
                        this.style.setProperty('background', 'linear-gradient(135deg, <?php echo esc_js($primary_color); ?> 0%, <?php echo esc_js($secondary_color); ?> 100%)', 'important');
                        this.style.setProperty('transform', 'translateY(-2px)', 'important');
                        this.style.setProperty('box-shadow', '0 4px 8px rgba(116, 162, 17, 0.25)', 'important');
                    });
                    
                    button.addEventListener('mouseleave', function() {
                        this.style.setProperty('background', '<?php echo esc_js($primary_color); ?>', 'important');
                        this.style.setProperty('transform', 'translateY(0)', 'important');
                        this.style.setProperty('box-shadow', '0 2px 4px rgba(0, 0, 0, 0.1)', 'important');
                    });
                    
                    console.log('EduBot: Applied colors to button ' + (index + 1) + ': ' + button.textContent.trim().substring(0, 20) + '...');
                });
                
                console.log('EduBot: JavaScript color application completed');
                
                // Also apply to any future buttons added dynamically
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1 && (node.classList.contains('edubot-quick-action') || (node.querySelector && node.querySelector('.edubot-quick-action')))) {
                                console.log('EduBot: Detected new button, applying colors...');
                                const newButtons = node.classList.contains('edubot-quick-action') ? [node] : node.querySelectorAll('.edubot-quick-action');
                                newButtons.forEach(function(btn) {
                                    btn.style.setProperty('background', '<?php echo esc_js($primary_color); ?>', 'important');
                                    btn.style.setProperty('color', 'white', 'important');
                                    btn.style.setProperty('border', '1px solid <?php echo esc_js($primary_color); ?>', 'important');
                                });
                            }
                        });
                    });
                });
                
                observer.observe(document.body, { childList: true, subtree: true });
                
            }, 100); // Small delay to ensure DOM is fully ready
            
            // Also try immediate application
            const immediateButtons = document.querySelectorAll('.edubot-quick-action');
            immediateButtons.forEach(function(btn) {
                btn.style.setProperty('background', '<?php echo esc_js($primary_color); ?>', 'important');
                btn.style.setProperty('color', 'white', 'important');
                btn.style.setProperty('border', '1px solid <?php echo esc_js($primary_color); ?>', 'important');
            });
        });
        </script>
        <?php
    }

    /**
     * Handle chatbot AJAX requests
     */
    public function handle_chatbot_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'edubot_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'edubot-pro'));
        }

        // Rate limiting
        $security_manager = new EduBot_Security_Manager();
        $user_ip = $security_manager->get_user_ip();
        
        if (!$security_manager->check_rate_limit($user_ip, 30, 900)) {
            wp_send_json_error(__('Too many requests. Please try again later.', 'edubot-pro'));
        }

        // Get request data
        $message = sanitize_text_field($_POST['message']);
        $session_id = sanitize_text_field($_POST['session_id']);

        if (empty($message) || empty($session_id)) {
            wp_send_json_error(__('Invalid request.', 'edubot-pro'));
        }

        // Process message through chatbot engine
        $chatbot_engine = new EduBot_Chatbot_Engine();
        $response = $chatbot_engine->process_message($message, $session_id);

        if ($response['success']) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error($response['message']);
        }
    }

    /**
     * Register shortcodes - DISABLED to use EduBot_Shortcode class instead
     */
    public function register_shortcodes() {
        // Disabled - using EduBot_Shortcode class with dynamic branding instead
        // add_shortcode('edubot_chatbot', array($this, 'chatbot_shortcode'));
        add_shortcode('edubot_application_form', array($this, 'application_form_shortcode'));
    }

    /**
     * Chatbot shortcode
     */
    public function chatbot_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'widget', // widget, inline, fullscreen
            'trigger' => 'auto', // auto, button, scroll
            'position' => 'bottom-right' // bottom-right, bottom-left, top-right, top-left
        ), $atts);

        ob_start();
        
        if ($atts['style'] === 'inline') {
            $this->render_inline_chatbot($atts);
        } else {
            $this->render_chatbot();
        }
        
        return ob_get_clean();
    }

    /**
     * Render inline chatbot
     */
    private function render_inline_chatbot($atts) {
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        $session_id = $this->generate_session_id();
        
        ?>
        <div class="edubot-inline-chat" data-session="<?php echo esc_attr($session_id); ?>">
            <div class="edubot-inline-header">
                <h3><?php echo esc_html($config['school_info']['name']); ?> - <?php echo esc_html__('Admission Chat', 'edubot-pro'); ?></h3>
            </div>
            
            <div class="edubot-inline-messages" id="edubot-inline-messages-<?php echo esc_attr($session_id); ?>">
                <div class="edubot-bot-message">
                    <div class="edubot-message-content">
                        <?php echo esc_html($config['chatbot_settings']['welcome_message']); ?>
                    </div>
                </div>
            </div>
            
            <div class="edubot-inline-input">
                <input 
                    type="text" 
                    class="edubot-inline-input-field" 
                    placeholder="<?php echo esc_attr__('Type your message...', 'edubot-pro'); ?>"
                    data-session="<?php echo esc_attr($session_id); ?>"
                >
                <button class="edubot-inline-send-btn" data-session="<?php echo esc_attr($session_id); ?>">
                    <?php echo esc_html__('Send', 'edubot-pro'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Application form shortcode
     */
    public function application_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Admission Application Form', 'edubot-pro'),
            'style' => 'default'
        ), $atts);

        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();

        ob_start();
        ?>
        <div class="edubot-application-form">
            <h2><?php echo esc_html($atts['title']); ?></h2>
            
            <form id="edubot-application-form" class="edubot-form">
                <!-- Student Information -->
                <fieldset class="edubot-fieldset">
                    <legend><?php echo esc_html__('Student Information', 'edubot-pro'); ?></legend>
                    
                    <div class="edubot-form-row">
                        <label for="student_name"><?php echo esc_html__('Student Name', 'edubot-pro'); ?> *</label>
                        <input type="text" id="student_name" name="student_name" required>
                    </div>
                    
                    <div class="edubot-form-row">
                        <label for="grade"><?php echo esc_html__('Grade', 'edubot-pro'); ?> *</label>
                        <select id="grade" name="grade" required>
                            <option value=""><?php echo esc_html__('Select Grade', 'edubot-pro'); ?></option>
                            <?php foreach ($config['form_settings']['grades'] as $grade): ?>
                                <option value="<?php echo esc_attr($grade); ?>"><?php echo esc_html($grade); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="edubot-form-row">
                        <label for="date_of_birth"><?php echo esc_html__('Date of Birth', 'edubot-pro'); ?> *</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required>
                    </div>
                    
                    <div class="edubot-form-row">
                        <label for="academic_year"><?php echo esc_html__('Academic Year', 'edubot-pro'); ?> *</label>
                        <select id="academic_year" name="academic_year" required>
                            <option value=""><?php echo esc_html__('Select Academic Year', 'edubot-pro'); ?></option>
                            <?php foreach ($config['form_settings']['academic_years'] as $year): ?>
                                <option value="<?php echo esc_attr($year); ?>"><?php echo esc_html($year); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </fieldset>
                
                <!-- Parent Information -->
                <fieldset class="edubot-fieldset">
                    <legend><?php echo esc_html__('Parent/Guardian Information', 'edubot-pro'); ?></legend>
                    
                    <div class="edubot-form-row">
                        <label for="parent_name"><?php echo esc_html__('Parent/Guardian Name', 'edubot-pro'); ?> *</label>
                        <input type="text" id="parent_name" name="parent_name" required>
                    </div>
                    
                    <div class="edubot-form-row">
                        <label for="phone"><?php echo esc_html__('Phone Number', 'edubot-pro'); ?> *</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    
                    <div class="edubot-form-row">
                        <label for="email"><?php echo esc_html__('Email Address', 'edubot-pro'); ?> *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="edubot-form-row">
                        <label for="address"><?php echo esc_html__('Address', 'edubot-pro'); ?></label>
                        <textarea id="address" name="address" rows="3"></textarea>
                    </div>
                </fieldset>
                
                <!-- Custom Fields -->
                <?php if (!empty($config['form_settings']['custom_fields'])): ?>
                <fieldset class="edubot-fieldset">
                    <legend><?php echo esc_html__('Additional Information', 'edubot-pro'); ?></legend>
                    
                    <?php foreach ($config['form_settings']['custom_fields'] as $field): ?>
                    <div class="edubot-form-row">
                        <label for="custom_<?php echo esc_attr(sanitize_title($field['label'])); ?>">
                            <?php echo esc_html($field['label']); ?>
                            <?php if ($field['required']): ?> *<?php endif; ?>
                        </label>
                        
                        <?php if ($field['type'] === 'select'): ?>
                            <select id="custom_<?php echo esc_attr(sanitize_title($field['label'])); ?>" 
                                    name="custom_<?php echo esc_attr(sanitize_title($field['label'])); ?>"
                                    <?php if ($field['required']): ?>required<?php endif; ?>>
                                <option value=""><?php echo esc_html__('Select Option', 'edubot-pro'); ?></option>
                                <?php 
                                $options = explode(',', $field['options']);
                                foreach ($options as $option): 
                                ?>
                                    <option value="<?php echo esc_attr(trim($option)); ?>"><?php echo esc_html(trim($option)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($field['type'] === 'textarea'): ?>
                            <textarea id="custom_<?php echo esc_attr(sanitize_title($field['label'])); ?>" 
                                     name="custom_<?php echo esc_attr(sanitize_title($field['label'])); ?>"
                                     <?php if ($field['required']): ?>required<?php endif; ?>></textarea>
                        <?php else: ?>
                            <input type="<?php echo esc_attr($field['type']); ?>" 
                                   id="custom_<?php echo esc_attr(sanitize_title($field['label'])); ?>" 
                                   name="custom_<?php echo esc_attr(sanitize_title($field['label'])); ?>"
                                   <?php if ($field['required']): ?>required<?php endif; ?>>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </fieldset>
                <?php endif; ?>
                
                <div class="edubot-form-submit">
                    <button type="submit" class="edubot-submit-btn">
                        <?php echo esc_html__('Submit Application', 'edubot-pro'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate unique session ID
     */
    private function generate_session_id() {
        return 'edubot_' . uniqid() . '_' . time();
    }

    /**
     * Handle form submission
     */
    public function handle_form_submission() {
        if (!wp_verify_nonce($_POST['nonce'], 'edubot_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'edubot-pro'));
        }

        $database_manager = new EduBot_Database_Manager();
        $security_manager = new EduBot_Security_Manager();
        
        // Sanitize form data
        $form_data = $security_manager->sanitize_input($_POST['form_data']);
        
        // Generate application number
        $application_number = $security_manager->generate_application_number();
        
        // Save application
        $application_id = $database_manager->save_application(array(
            'application_number' => $application_number,
            'student_data' => json_encode($form_data),
            'source' => 'form',
            'ip_address' => $security_manager->get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
        ));

        if ($application_id) {
            // Send notifications
            $notification_manager = new EduBot_Notification_Manager();
            $notification_manager->send_application_notifications($application_id, $form_data);
            
            wp_send_json_success(array(
                'message' => __('Application submitted successfully!', 'edubot-pro'),
                'application_number' => $application_number
            ));
        } else {
            wp_send_json_error(__('Error submitting application. Please try again.', 'edubot-pro'));
        }
    }
}
