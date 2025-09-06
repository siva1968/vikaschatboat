<?php

/**
 * The admin-specific functionality of the plugin.
 */
class EduBot_Admin {

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
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            EDUBOT_PRO_PLUGIN_URL . 'admin/css/edubot-admin.css',
            array(),
            $this->version,
            'all'
        );
        
        // Color picker
        wp_enqueue_style('wp-color-picker');
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        // Enqueue WordPress media scripts
        wp_enqueue_media();
        
        wp_enqueue_script(
            $this->plugin_name,
            EDUBOT_PRO_PLUGIN_URL . 'admin/js/edubot-admin.js',
            array('jquery', 'wp-color-picker'),
            $this->version,
            true
        );

        // Localize script for AJAX
        wp_localize_script(
            $this->plugin_name,
            'edubot_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('edubot_admin_nonce'),
                'strings' => array(
                    'testing' => __('Testing...', 'edubot-pro'),
                    'success' => __('Connection successful!', 'edubot-pro'),
                    'error' => __('Connection failed!', 'edubot-pro'),
                    'saving' => __('Saving...', 'edubot-pro'),
                    'saved' => __('Settings saved!', 'edubot-pro')
                )
            )
        );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('EduBot Pro', 'edubot-pro'),
            __('EduBot Pro', 'edubot-pro'),
            'manage_options',
            'edubot-pro',
            array($this, 'display_admin_page'),
            'dashicons-format-chat',
            30
        );

        add_submenu_page(
            'edubot-pro',
            __('Dashboard', 'edubot-pro'),
            __('Dashboard', 'edubot-pro'),
            'manage_options',
            'edubot-pro',
            array($this, 'display_admin_page')
        );

        add_submenu_page(
            'edubot-pro',
            __('School Settings', 'edubot-pro'),
            __('School Settings', 'edubot-pro'),
            'manage_options',
            'edubot-school-settings',
            array($this, 'display_school_settings_page')
        );

        add_submenu_page(
            'edubot-pro',
            __('Academic Configuration', 'edubot-pro'),
            __('Academic Configuration', 'edubot-pro'),
            'manage_options',
            'edubot-academic-config',
            array($this, 'display_academic_config_page')
        );

        add_submenu_page(
            'edubot-pro',
            __('API Integrations', 'edubot-pro'),
            __('API Integrations', 'edubot-pro'),
            'manage_options',
            'edubot-api-settings',
            array($this, 'display_api_settings_page')
        );

        add_submenu_page(
            'edubot-pro',
            __('Form Builder', 'edubot-pro'),
            __('Form Builder', 'edubot-pro'),
            'manage_options',
            'edubot-form-builder',
            array($this, 'display_form_builder_page')
        );

        add_submenu_page(
            'edubot-pro',
            __('Applications', 'edubot-pro'),
            __('Applications', 'edubot-pro'),
            'manage_options',
            'edubot-applications',
            array($this, 'display_applications_page')
        );

        add_submenu_page(
            'edubot-pro',
            __('Analytics', 'edubot-pro'),
            __('Analytics', 'edubot-pro'),
            'manage_options',
            'edubot-analytics',
            array($this, 'display_analytics_page')
        );
    }

    /**
     * Initialize admin settings
     */
    public function admin_init() {
        // Register settings
        register_setting('edubot_school_settings', 'edubot_school_config');
        register_setting('edubot_api_settings', 'edubot_api_keys');
        register_setting('edubot_form_settings', 'edubot_form_config');
    }

    /**
     * Display main admin dashboard
     */
    public function display_admin_page() {
        $database_manager = new EduBot_Database_Manager();
        $analytics_data = $database_manager->get_analytics_data(30);
        $recent_applications = $database_manager->get_recent_applications(5);
        
        include EDUBOT_PRO_PLUGIN_PATH . 'admin/views/dashboard.php';
    }

    /**
     * Display school settings page
     */
    public function display_school_settings_page() {
        $school_config = new EduBot_School_Config();
        $config = $school_config->get_config();
        
        $save_result = false;
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'edubot_school_settings')) {
            $save_result = $this->save_school_settings();
        }
        
        // Display success message if save was successful
        if ($save_result === true) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'edubot-pro') . '</p></div>';
        } elseif ($save_result === false && isset($_POST['submit'])) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('Error saving settings. Please check your entries and try again.', 'edubot-pro') . '</p></div>';
        }
        
        include EDUBOT_PRO_PLUGIN_PATH . 'admin/views/school-settings.php';
    }

    /**
     * Display academic configuration page
     */
    public function display_academic_config_page() {
        $school_config = new EduBot_School_Config();
        $school_id = $school_config->get_school_id();
        
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['edubot_academic_nonce'], 'edubot_save_academic_config')) {
            $this->save_academic_config();
        }
        
        include EDUBOT_PRO_PLUGIN_PATH . 'admin/partials/academic-config.php';
    }

    /**
     * Display API settings page
     */
    public function display_api_settings_page() {
        $school_config = new EduBot_School_Config();
        $api_keys = $school_config->get_api_keys();
        
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'edubot_api_settings')) {
            $this->save_api_settings();
        }
        
        include EDUBOT_PRO_PLUGIN_PATH . 'admin/views/api-integrations.php';
    }

    /**
     * Display form builder page
     */
    public function display_form_builder_page() {
        $school_config = new EduBot_School_Config();
        $config = $school_config->get_config();
        
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'edubot_form_settings')) {
            $this->save_form_settings();
        }
        
        include EDUBOT_PRO_PLUGIN_PATH . 'admin/views/form-builder.php';
    }

    /**
     * Display applications page
     */
    public function display_applications_page() {
        $database_manager = new EduBot_Database_Manager();
        
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $filters = array();
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $filters['status'] = sanitize_text_field($_GET['status']);
        }
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = sanitize_text_field($_GET['search']);
        }
        
        $applications_data = $database_manager->get_applications($page, 20, $filters);
        
        // Parse student data JSON for display
        if (isset($applications_data['applications'])) {
            foreach ($applications_data['applications'] as &$app) {
                $student_data = json_decode($app['student_data'], true);
                if ($student_data) {
                    $app['student_name'] = $student_data['student_name'] ?? 'N/A';
                    $app['parent_name'] = $student_data['parent_name'] ?? 'N/A';
                    $app['grade'] = $student_data['grade'] ?? 'N/A';
                    $app['educational_board'] = $student_data['educational_board'] ?? 'N/A';
                    $app['academic_year'] = $student_data['academic_year'] ?? 'N/A';
                    $app['email'] = $student_data['email'] ?? 'N/A';
                    $app['phone'] = $student_data['phone'] ?? 'N/A';
                    $app['gender'] = $student_data['gender'] ?? 'N/A';
                    $app['date_of_birth'] = $student_data['date_of_birth'] ?? 'N/A';
                }
            }
        }
        
        // Pass the parsed data to the view
        $applications = $applications_data['applications'] ?? array();
        
        include EDUBOT_PRO_PLUGIN_PATH . 'admin/views/applications-list.php';
    }

    /**
     * Display analytics page
     */
    public function display_analytics_page() {
        $database_manager = new EduBot_Database_Manager();
        $analytics_data = $database_manager->get_analytics_data(30);
        
        include EDUBOT_PRO_PLUGIN_PATH . 'admin/views/analytics.php';
    }

    /**
     * Save school settings
     */
    private function save_school_settings() {
        // Check if we have basic required fields
        if (empty($_POST['edubot_school_name'])) {
            return false;
        }
        
        $school_config = new EduBot_School_Config();
        
        // Save basic school settings as individual options
        update_option('edubot_school_name', sanitize_text_field($_POST['edubot_school_name'] ?? ''));
        update_option('edubot_school_logo', esc_url_raw($_POST['edubot_school_logo'] ?? ''));
        update_option('edubot_school_phone', sanitize_text_field($_POST['edubot_school_phone'] ?? ''));
        update_option('edubot_school_email', sanitize_email($_POST['edubot_school_email'] ?? ''));
        update_option('edubot_school_address', sanitize_textarea_field($_POST['edubot_school_address'] ?? ''));
        update_option('edubot_school_website', esc_url_raw($_POST['edubot_school_website'] ?? ''));
        update_option('edubot_primary_color', sanitize_hex_color($_POST['edubot_primary_color'] ?? '#4facfe'));
        update_option('edubot_secondary_color', sanitize_hex_color($_POST['edubot_secondary_color'] ?? '#00f2fe'));
        update_option('edubot_welcome_message', sanitize_textarea_field($_POST['edubot_welcome_message'] ?? ''));
        update_option('edubot_completion_message', sanitize_textarea_field($_POST['edubot_completion_message'] ?? ''));
        
        // Handle boards configuration
        $boards_data = array();
        if (isset($_POST['edubot_boards']) && is_array($_POST['edubot_boards'])) {
            foreach ($_POST['edubot_boards'] as $index => $board) {
                $boards_data[] = array(
                    'code' => sanitize_text_field($board['code']),
                    'name' => sanitize_text_field($board['name']),
                    'description' => sanitize_textarea_field($board['description'] ?? ''),
                    'grades' => sanitize_text_field($board['grades'] ?? ''),
                    'features' => sanitize_textarea_field($board['features'] ?? ''),
                    'enabled' => isset($board['enabled']) && $board['enabled'] === '1'
                );
            }
        }
        
        // Save boards configuration
        update_option('edubot_configured_boards', $boards_data);
        update_option('edubot_default_board', sanitize_text_field($_POST['edubot_default_board'] ?? ''));
        update_option('edubot_board_selection_required', isset($_POST['edubot_board_selection_required']) && $_POST['edubot_board_selection_required'] === '1');
        
        // Save academic year configuration
        update_option('edubot_academic_calendar_type', sanitize_text_field($_POST['edubot_academic_calendar_type'] ?? 'april-march'));
        update_option('edubot_custom_start_month', intval($_POST['edubot_custom_start_month'] ?? 4));
        update_option('edubot_custom_end_month', intval($_POST['edubot_custom_end_month'] ?? 3));
        
        $available_years = array();
        if (isset($_POST['edubot_available_academic_years']) && is_array($_POST['edubot_available_academic_years'])) {
            $available_years = array_map('sanitize_text_field', $_POST['edubot_available_academic_years']);
        }
        update_option('edubot_available_academic_years', $available_years);
        update_option('edubot_default_academic_year', sanitize_text_field($_POST['edubot_default_academic_year'] ?? ''));
        update_option('edubot_admission_period', sanitize_text_field($_POST['edubot_admission_period'] ?? 'next'));
        
        $config_data = array(
            'school_info' => array(
                'name' => sanitize_text_field($_POST['edubot_school_name']),
                'logo' => esc_url_raw($_POST['edubot_school_logo']),
                'colors' => array(
                    'primary' => sanitize_hex_color($_POST['edubot_primary_color']),
                    'secondary' => sanitize_hex_color($_POST['edubot_secondary_color'])
                ),
                'contact_info' => array(
                    'phone' => sanitize_text_field($_POST['edubot_school_phone']),
                    'email' => sanitize_email($_POST['edubot_school_email']),
                    'address' => sanitize_textarea_field($_POST['edubot_school_address']),
                    'website' => esc_url_raw($_POST['edubot_school_website'])
                )
            ),
            'chatbot_settings' => array(
                'welcome_message' => sanitize_textarea_field($_POST['edubot_welcome_message']),
                'completion_message' => sanitize_textarea_field($_POST['edubot_completion_message']),
                'language' => sanitize_text_field($_POST['edubot_language'] ?? 'en'),
                'response_style' => sanitize_text_field($_POST['edubot_response_style'] ?? 'friendly')
            ),
            // Add boards to config
            'available_boards' => $boards_data
        );
        
        $errors = $school_config->validate_config($config_data);
        
        if (empty($errors)) {
            if ($school_config->update_config($config_data)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Save API settings
     */
    private function save_api_settings() {
        $school_config = new EduBot_School_Config();
        
        $api_data = array(
            'api_keys' => array(
                'openai_key' => sanitize_text_field($_POST['openai_key']),
                'ai_model' => sanitize_text_field($_POST['ai_model']),
                'whatsapp_provider' => sanitize_text_field($_POST['whatsapp_provider']),
                'whatsapp_token' => sanitize_text_field($_POST['whatsapp_token']),
                'whatsapp_phone_id' => sanitize_text_field($_POST['whatsapp_phone_id']),
                'email_service' => sanitize_text_field($_POST['email_provider']),
                'smtp_host' => sanitize_text_field($_POST['smtp_host']),
                'smtp_port' => intval($_POST['smtp_port']),
                'smtp_username' => sanitize_text_field($_POST['smtp_username']),
                'smtp_password' => sanitize_text_field($_POST['smtp_password']),
                'email_api_key' => sanitize_text_field($_POST['email_api_key']),
                'email_domain' => sanitize_text_field($_POST['email_domain']),
                'sms_provider' => sanitize_text_field($_POST['sms_provider']),
                'sms_api_key' => sanitize_text_field($_POST['sms_api_key']),
                'sms_sender_id' => sanitize_text_field($_POST['sms_sender_id'])
            )
        );
        
        if ($school_config->update_config($api_data)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . __('API settings saved successfully!', 'edubot-pro') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Error saving API settings. Please try again.', 'edubot-pro') . '</p></div>';
            });
        }
    }

    /**
     * Save form settings
     */
    private function save_form_settings() {
        $school_config = new EduBot_School_Config();
        
        $form_data = array(
            'form_settings' => array(
                'academic_years' => array_map('sanitize_text_field', $_POST['academic_years']),
                'boards' => array_map('sanitize_text_field', $_POST['boards']),
                'grades' => array_map('sanitize_text_field', $_POST['grades']),
                'collect_parent_photos' => isset($_POST['collect_parent_photos']),
                'collect_student_photo' => isset($_POST['collect_student_photo']),
                'require_previous_school' => isset($_POST['require_previous_school']),
                'collect_sibling_info' => isset($_POST['collect_sibling_info'])
            )
        );
        
        // Handle custom fields
        if (isset($_POST['custom_field_label']) && is_array($_POST['custom_field_label'])) {
            $custom_fields = array();
            for ($i = 0; $i < count($_POST['custom_field_label']); $i++) {
                if (!empty($_POST['custom_field_label'][$i])) {
                    $custom_fields[] = array(
                        'label' => sanitize_text_field($_POST['custom_field_label'][$i]),
                        'type' => sanitize_text_field($_POST['custom_field_type'][$i]),
                        'options' => sanitize_text_field($_POST['custom_field_options'][$i]),
                        'required' => isset($_POST['custom_field_required'][$i])
                    );
                }
            }
            $form_data['form_settings']['custom_fields'] = $custom_fields;
        }
        
        if ($school_config->update_config($form_data)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . __('Form settings saved successfully!', 'edubot-pro') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Error saving form settings. Please try again.', 'edubot-pro') . '</p></div>';
            });
        }
    }

    /**
     * Save academic configuration
     */
    private function save_academic_config() {
        $school_id = intval($_POST['school_id']);
        
        // Save academic structure configuration
        if (isset($_POST['academic_config'])) {
            $academic_config = $_POST['academic_config'];
            
            // Process custom grades if present
            if ($academic_config['grade_system'] === 'custom') {
                $custom_grades = array();
                if (isset($academic_config['custom_grades_keys']) && isset($academic_config['custom_grades_labels'])) {
                    $keys = $academic_config['custom_grades_keys'];
                    $labels = $academic_config['custom_grades_labels'];
                    
                    for ($i = 0; $i < count($keys); $i++) {
                        if (!empty($keys[$i]) && !empty($labels[$i])) {
                            $custom_grades[sanitize_key($keys[$i])] = sanitize_text_field($labels[$i]);
                        }
                    }
                }
                $academic_config['custom_grades'] = $custom_grades;
            }
            
            // Process admission cycles
            if (isset($academic_config['admission_cycles'])) {
                $cycles = array();
                foreach ($academic_config['admission_cycles'] as $cycle) {
                    if (!empty($cycle['name'])) {
                        $cycles[] = array(
                            'name' => sanitize_text_field($cycle['name']),
                            'start_date' => sanitize_text_field($cycle['start_date']),
                            'end_date' => sanitize_text_field($cycle['end_date']),
                            'grades_available' => isset($cycle['grades_available']) ? $cycle['grades_available'] : array()
                        );
                    }
                }
                $academic_config['admission_cycles'] = $cycles;
            }
            
            // Validate and save
            $errors = Edubot_Academic_Config::validate_academic_config($academic_config);
            if (empty($errors)) {
                Edubot_Academic_Config::save_school_academic_config($school_id, $academic_config);
            } else {
                add_action('admin_notices', function() use ($errors) {
                    echo '<div class="notice notice-error"><p>' . implode('<br>', $errors) . '</p></div>';
                });
                return;
            }
        }
        
        // Save board configuration
        if (isset($_POST['board_config'])) {
            $board_config = $_POST['board_config'];
            
            // Process requirements and subjects
            if (isset($board_config['requirements'])) {
                $board_config['requirements'] = array_map('sanitize_text_field', $board_config['requirements']);
            }
            if (isset($board_config['subjects'])) {
                $board_config['subjects'] = array_map('sanitize_text_field', $board_config['subjects']);
            }
            
            Edubot_Academic_Config::save_school_board_config($school_id, $board_config);
        }
        
        // Save academic year configuration
        if (isset($_POST['academic_year_config'])) {
            $year_config = $_POST['academic_year_config'];
            $year_config['auto_update_year'] = isset($year_config['auto_update_year']);
            
            Edubot_Academic_Config::save_school_academic_year_config($school_id, $year_config);
        }
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . __('Academic configuration saved successfully!', 'edubot-pro') . '</p></div>';
        });
    }

    /**
     * Test API connection via AJAX
     */
    public function test_api_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'edubot_admin_nonce')) {
            wp_die(__('Security check failed.', 'edubot-pro'));
        }

        $api_type = sanitize_text_field($_POST['api_type']);
        $api_integrations = new EduBot_API_Integrations();
        
        $result = false;
        
        switch ($api_type) {
            case 'openai':
                $api_key = sanitize_text_field($_POST['api_key']);
                $result = $api_integrations->test_openai_connection($api_key);
                break;
                
            case 'whatsapp':
                $token = sanitize_text_field($_POST['token']);
                $provider = sanitize_text_field($_POST['provider']);
                $result = $api_integrations->test_whatsapp_connection($token, $provider);
                break;
                
            case 'email':
                $settings = array(
                    'provider' => sanitize_text_field($_POST['provider']),
                    'api_key' => sanitize_text_field($_POST['api_key']),
                    'host' => sanitize_text_field($_POST['host']),
                    'port' => intval($_POST['port']),
                    'username' => sanitize_text_field($_POST['username']),
                    'password' => sanitize_text_field($_POST['password'])
                );
                $result = $api_integrations->test_email_connection($settings);
                break;
        }
        
        wp_send_json(array(
            'success' => $result,
            'message' => $result ? __('Connection successful!', 'edubot-pro') : __('Connection failed!', 'edubot-pro')
        ));
    }

    /**
     * Save settings via AJAX
     */
    public function save_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'edubot_admin_nonce')) {
            wp_die(__('Security check failed.', 'edubot-pro'));
        }

        $settings_type = sanitize_text_field($_POST['settings_type']);
        $success = false;
        
        switch ($settings_type) {
            case 'school':
                $success = $this->save_school_settings();
                break;
                
            case 'api':
                $success = $this->save_api_settings();
                break;
                
            case 'form':
                $success = $this->save_form_settings();
                break;
        }
        
        wp_send_json(array(
            'success' => $success,
            'message' => $success ? __('Settings saved!', 'edubot-pro') : __('Error saving settings!', 'edubot-pro')
        ));
    }
}
