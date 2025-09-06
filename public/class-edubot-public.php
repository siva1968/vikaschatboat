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
     * Initialize the class and set its properties.
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
        wp_enqueue_script(
            $this->plugin_name,
            EDUBOT_PRO_PLUGIN_URL . 'public/js/edubot-public.js',
            array('jquery'),
            $this->version,
            false
        );

        // Localize script for AJAX
        wp_localize_script(
            $this->plugin_name,
            'edubot_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('edubot_public_nonce'),
                'strings' => array(
                    'connecting' => __('Connecting...', 'edubot-pro'),
                    'typing' => __('Bot is typing...', 'edubot-pro'),
                    'error' => __('Sorry, something went wrong. Please try again.', 'edubot-pro'),
                    'send' => __('Send', 'edubot-pro'),
                    'type_message' => __('Type your message...', 'edubot-pro')
                )
            )
        );
    }

    /**
     * Add custom branding styles
     */
    private function add_custom_branding_styles() {
        $branding_manager = new EduBot_Branding_Manager();
        $custom_css = $branding_manager->generate_custom_css();
        
        if ($custom_css) {
            wp_add_inline_style($this->plugin_name, $custom_css);
        }
    }

    /**
     * Render chatbot widget
     */
    public function render_chatbot() {
        $school_config = new EduBot_School_Config();
        $config = $school_config->get_config();
        
        // Generate unique session ID
        $session_id = $this->generate_session_id();
        
        ?>
        <div id="edubot-chatbot-widget" class="edubot-chatbot-widget">
            <div id="edubot-chat-toggle" class="edubot-chat-toggle">
                <div class="edubot-chat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12C2 13.54 2.38 14.99 3.06 16.26L2 22L7.74 20.94C9.01 21.62 10.46 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C10.74 20 9.54 19.68 8.5 19.12L8.19 18.95L4.55 19.85L5.45 16.21L5.28 15.9C4.72 14.86 4.4 13.66 4.4 12.4C4.4 7.92 7.92 4.4 12.4 4.4C16.88 4.4 20.4 7.92 20.4 12.4C20.4 16.88 16.88 20.4 12.4 20.4H12V20Z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="edubot-chat-label"><?php echo esc_html__('Chat with us', 'edubot-pro'); ?></div>
            </div>
            
            <div id="edubot-chat-container" class="edubot-chat-container" style="display: none;">
                <div class="edubot-chat-header">
                    <div class="edubot-header-info">
                        <div class="edubot-header-title"><?php echo esc_html($config['school_info']['name']); ?></div>
                        <div class="edubot-header-subtitle"><?php echo esc_html__('Admission Assistant', 'edubot-pro'); ?></div>
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
        if (typeof EduBotChatWidget !== 'undefined') {
            EduBotChatWidget.init('<?php echo esc_js($session_id); ?>');
        }
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
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('edubot_chatbot', array($this, 'chatbot_shortcode'));
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
        $school_config = new EduBot_School_Config();
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

        $school_config = new EduBot_School_Config();
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
