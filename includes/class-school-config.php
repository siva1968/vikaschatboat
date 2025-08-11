<?php

/**
 * Handle school configuration and settings
 */
class EduBot_School_Config {

    /**
     * Default configuration structure
     */
    private $default_config = array(
        'school_info' => array(
            'name' => '',
            'logo' => '',
            'colors' => array('primary' => '#4facfe', 'secondary' => '#00f2fe'),
            'contact_info' => array(
                'phone' => '',
                'email' => '',
                'address' => '',
                'website' => ''
            )
        ),
        'api_keys' => array(
            'openai_key' => '',
            'whatsapp_token' => '',
            'whatsapp_phone_id' => '',
            'whatsapp_provider' => '',
            'email_service' => 'smtp',
            'email_api_key' => '',
            'email_domain' => '',
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'sms_provider' => '',
            'sms_api_key' => '',
            'sms_sender_id' => ''
        ),
        'form_settings' => array(
            'required_fields' => array('student_name', 'parent_name', 'phone', 'email', 'grade'),
            'optional_fields' => array('address', 'previous_school', 'sibling_info'),
            'custom_fields' => array(),
            'academic_years' => array('2025-26'),
            'boards' => array('CBSE', 'ICSE', 'IGCSE', 'Cambridge', 'IB'),
            'grades' => array('Pre-K', 'K', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'),
            'collect_parent_photos' => false,
            'collect_student_photo' => true,
            'require_previous_school' => false,
            'collect_sibling_info' => false
        ),
        'chatbot_settings' => array(
            'welcome_message' => 'Hello! 👋 Welcome to {school_name} admission process. I\'m here to help you with your application.',
            'completion_message' => 'Thank you for completing your admission application for {school_name}! 🎉',
            'language' => 'en',
            'ai_model' => 'gpt-3.5-turbo',
            'response_style' => 'friendly',
            'max_retries' => 3,
            'session_timeout' => 30 // minutes
        ),
        'notification_settings' => array(
            'whatsapp_enabled' => false,
            'email_enabled' => true,
            'sms_enabled' => false,
            'admin_notifications' => true,
            'parent_notifications' => true
        ),
        'automation_settings' => array(
            'auto_send_brochure' => true,
            'follow_up_enabled' => true,
            'follow_up_delay' => 24, // hours
            'reminder_sequence' => array()
        ),
        'messages' => array(
            'welcome' => 'Hello! 👋 Welcome to {school_name} admission process. I\'m here to help you with your application.',
            'completion' => 'Thank you for completing your admission application for {school_name}! 🎉',
            'whatsapp_template' => 'Dear {parent_name}, Thank you for your interest in {school_name}! We have received your admission application for {student_name} for Grade {grade} ({academic_year}). Our admissions team will contact you within 24-48 hours. Best regards, {school_name} Admissions Team',
            'email_subject' => 'Admission Application Received - {school_name}',
            'email_template' => 'Dear {parent_name},\n\nThank you for your interest in {school_name}!\n\nWe have successfully received your admission application for {student_name} for Grade {grade} for the academic year {academic_year}.\n\nApplication Details:\n- Application Number: {application_number}\n- Student Name: {student_name}\n- Grade: {grade}\n- Academic Year: {academic_year}\n- Date Submitted: {submission_date}\n\nNext Steps:\n1. Our admissions team will review your application\n2. You will receive a confirmation call within 24-48 hours\n3. We will schedule an interaction session if required\n\nIf you have any questions, please feel free to contact us at {school_phone} or {school_email}.\n\nBest regards,\n{school_name} Admissions Team'
        )
    );

    /**
     * Get school configuration for current site
     */
    public function get_config() {
        global $wpdb;
        $site_id = get_current_blog_id();
        $table = $wpdb->prefix . 'edubot_school_configs';
        
        $config = $wpdb->get_var($wpdb->prepare(
            "SELECT config_data FROM $table WHERE site_id = %d AND status = 'active'",
            $site_id
        ));
        
        if ($config) {
            $decoded_config = json_decode($config, true);
            return wp_parse_args($decoded_config, $this->default_config);
        }
        
        return $this->default_config;
    }

    /**
     * Update school configuration
     */
    public function update_config($config_data) {
        global $wpdb;
        $site_id = get_current_blog_id();
        $table = $wpdb->prefix . 'edubot_school_configs';
        
        // Merge with existing config
        $existing_config = $this->get_config();
        $updated_config = wp_parse_args($config_data, $existing_config);
        
        // Encrypt API keys before saving
        $security_manager = new EduBot_Security_Manager();
        if (isset($updated_config['api_keys'])) {
            $updated_config['api_keys'] = $security_manager->save_api_keys($updated_config['api_keys']);
        }
        
        $result = $wpdb->replace(
            $table,
            array(
                'site_id' => $site_id,
                'school_name' => $updated_config['school_info']['name'],
                'config_data' => json_encode($updated_config),
                'status' => 'active'
            ),
            array('%d', '%s', '%s', '%s')
        );
        
        return $result !== false;
    }

    /**
     * Get decrypted API keys
     */
    public function get_api_keys() {
        $config = $this->get_config();
        $security_manager = new EduBot_Security_Manager();
        
        if (isset($config['api_keys'])) {
            return $security_manager->decrypt_api_keys($config['api_keys']);
        }
        
        return array();
    }

    /**
     * Get personalized message with variables replaced
     */
    public function get_message($message_key, $variables = array()) {
        $config = $this->get_config();
        $template = isset($config['messages'][$message_key]) ? $config['messages'][$message_key] : '';
        
        if (empty($template) && isset($this->default_config['messages'][$message_key])) {
            $template = $this->default_config['messages'][$message_key];
        }
        
        // Add school info to variables
        $variables['school_name'] = $config['school_info']['name'];
        $variables['school_phone'] = $config['school_info']['contact_info']['phone'];
        $variables['school_email'] = $config['school_info']['contact_info']['email'];
        
        // Replace placeholders
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        return $template;
    }

    /**
     * Validate configuration data
     */
    public function validate_config($config_data) {
        $errors = array();
        
        // Validate required school info
        if (empty($config_data['school_info']['name'])) {
            $errors[] = __('School name is required', 'edubot-pro');
        }
        
        // Validate API keys format
        if (isset($config_data['api_keys']['openai_key']) && !empty($config_data['api_keys']['openai_key'])) {
            if (!preg_match('/^sk-[a-zA-Z0-9]{48}$/', $config_data['api_keys']['openai_key'])) {
                $errors[] = __('Invalid OpenAI API key format', 'edubot-pro');
            }
        }
        
        // Validate email
        if (!empty($config_data['school_info']['contact_info']['email'])) {
            if (!is_email($config_data['school_info']['contact_info']['email'])) {
                $errors[] = __('Invalid email address', 'edubot-pro');
            }
        }
        
        // Validate colors
        if (isset($config_data['school_info']['colors'])) {
            foreach ($config_data['school_info']['colors'] as $color) {
                if (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
                    $errors[] = __('Invalid color format. Use hex format like #FF5733', 'edubot-pro');
                }
            }
        }
        
        return $errors;
    }

    /**
     * Get default configuration
     */
    public function get_default_config() {
        return $this->default_config;
    }

    /**
     * Get current school ID
     * For multi-school setups, this could be dynamic
     * For single school setup, return default ID
     */
    public function get_school_id() {
        // For now, return a default school ID
        // In a multi-school setup, this would be determined by:
        // - Current domain/subdomain
        // - User session
        // - URL parameters
        // - Database lookup
        
        return get_option('edubot_current_school_id', 1);
    }

    /**
     * Set current school ID
     */
    public function set_school_id($school_id) {
        return update_option('edubot_current_school_id', intval($school_id));
    }

    /**
     * Get school info by ID
     */
    public function get_school_info($school_id = null) {
        if ($school_id === null) {
            $school_id = $this->get_school_id();
        }
        
        $config = $this->get_config();
        return isset($config['school_info']) ? $config['school_info'] : array();
    }
    
    /**
     * Get configured educational boards
     */
    public function get_configured_boards() {
        return get_option('edubot_configured_boards', array(
            array('code' => 'CBSE', 'name' => 'Central Board of Secondary Education', 'enabled' => true),
            array('code' => 'ICSE', 'name' => 'Indian Certificate of Secondary Education', 'enabled' => false),
            array('code' => 'IGCSE', 'name' => 'International General Certificate of Secondary Education', 'enabled' => false),
            array('code' => 'STATE', 'name' => 'State Board', 'enabled' => false)
        ));
    }
    
    /**
     * Get enabled educational boards only
     */
    public function get_enabled_boards() {
        $all_boards = $this->get_configured_boards();
        return array_filter($all_boards, function($board) {
            return $board['enabled'] === true;
        });
    }
    
    /**
     * Get board information by code
     */
    public function get_board_info($board_code) {
        $boards = $this->get_configured_boards();
        foreach ($boards as $board) {
            if ($board['code'] === $board_code) {
                return $board;
            }
        }
        return null;
    }
    
    /**
     * Get default board setting
     */
    public function get_default_board() {
        return get_option('edubot_default_board', '');
    }
    
    /**
     * Check if board selection is required
     */
    public function is_board_selection_required() {
        return get_option('edubot_board_selection_required', true);
    }
    
    /**
     * Get boards formatted for dropdown options
     */
    public function get_boards_dropdown_options() {
        $enabled_boards = $this->get_enabled_boards();
        $options = array();
        
        foreach ($enabled_boards as $board) {
            $options[$board['code']] = $board['name'];
        }
        
        return $options;
    }
    
    /**
     * Validate board code
     */
    public function is_valid_board($board_code) {
        $enabled_boards = $this->get_enabled_boards();
        foreach ($enabled_boards as $board) {
            if ($board['code'] === $board_code) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get current and next academic years based on calendar type
     */
    public function get_current_academic_years() {
        $calendar_type = get_option('edubot_academic_calendar_type', 'april-march');
        $current_year = date('Y');
        $current_month = date('n');
        
        // Determine start month based on calendar type
        switch ($calendar_type) {
            case 'april-march':
                $start_month = 4;
                break;
            case 'june-may':
                $start_month = 6;
                break;
            case 'september-august':
                $start_month = 9;
                break;
            case 'january-december':
                $start_month = 1;
                break;
            case 'custom':
                $start_month = get_option('edubot_custom_start_month', 4);
                break;
            default:
                $start_month = 4;
        }
        
        // Calculate academic year strings
        if ($current_month >= $start_month) {
            $current_academic_year = $current_year . '-' . substr($current_year + 1, 2);
            $next_academic_year = ($current_year + 1) . '-' . substr($current_year + 2, 2);
        } else {
            $current_academic_year = ($current_year - 1) . '-' . substr($current_year, 2);
            $next_academic_year = $current_year . '-' . substr($current_year + 1, 2);
        }
        
        return array(
            'current' => $current_academic_year,
            'next' => $next_academic_year,
            'calendar_type' => $calendar_type,
            'start_month' => $start_month
        );
    }
    
    /**
     * Get available academic years for admissions
     */
    public function get_available_academic_years() {
        $years = $this->get_current_academic_years();
        $available_years = get_option('edubot_available_academic_years', array($years['current'], $years['next']));
        $admission_period = get_option('edubot_admission_period', 'next');
        
        // Filter based on admission period setting
        switch ($admission_period) {
            case 'current':
                return array($years['current']);
            case 'next':
                return array($years['next']);
            case 'both':
                return $available_years;
            default:
                return $available_years;
        }
    }
    
    /**
     * Get default academic year
     */
    public function get_default_academic_year() {
        $default = get_option('edubot_default_academic_year', '');
        if (empty($default)) {
            $years = $this->get_current_academic_years();
            return $years['next']; // Default to next year for new admissions
        }
        return $default;
    }
    
    /**
     * Check if academic year is valid and available for admissions
     */
    public function is_valid_academic_year($academic_year) {
        $available_years = $this->get_available_academic_years();
        return in_array($academic_year, $available_years);
    }
    
    /**
     * Get academic years formatted for dropdown options
     */
    public function get_academic_years_dropdown_options() {
        $available_years = $this->get_available_academic_years();
        $current_years = $this->get_current_academic_years();
        $options = array();
        
        foreach ($available_years as $year) {
            $label = $year;
            if ($year === $current_years['current']) {
                $label .= ' (Current)';
            } elseif ($year === $current_years['next']) {
                $label .= ' (Next)';
            }
            $options[$year] = $label;
        }
        
        return $options;
    }
    
    /**
     * Get academic year information
     */
    public function get_academic_year_info($academic_year = null) {
        if ($academic_year === null) {
            $academic_year = $this->get_default_academic_year();
        }
        
        $current_years = $this->get_current_academic_years();
        $is_current = ($academic_year === $current_years['current']);
        $is_next = ($academic_year === $current_years['next']);
        
        return array(
            'year' => $academic_year,
            'is_current' => $is_current,
            'is_next' => $is_next,
            'label' => $academic_year . ($is_current ? ' (Current)' : ($is_next ? ' (Next)' : '')),
            'calendar_type' => $current_years['calendar_type'],
            'start_month' => $current_years['start_month']
        );
    }
}
