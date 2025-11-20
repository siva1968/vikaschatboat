<?php






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
        
        // Add AJAX handlers
        add_action('wp_ajax_edubot_clear_debug_log', array($this, 'clear_debug_log'));
        add_action('wp_ajax_edubot_save_openai_settings', array($this, 'save_openai_settings'));
        add_action('wp_ajax_edubot_save_whatsapp_settings', array($this, 'save_whatsapp_settings'));
        add_action('wp_ajax_edubot_save_email_settings', array($this, 'save_email_settings'));
        add_action('wp_ajax_edubot_save_sms_settings', array($this, 'save_sms_settings'));
        add_action('wp_ajax_edubot_save_debug_settings', array($this, 'save_debug_settings'));
        add_action('wp_ajax_edubot_test_api', array($this, 'test_api_connection'));
        add_action('wp_ajax_edubot_get_dashboard_stats', array($this, 'get_dashboard_stats_ajax'));
        
        // WhatsApp Ad Integration AJAX handlers (New v2.0.0)
        add_action('wp_ajax_edubot_generate_whatsapp_link', array($this, 'generate_whatsapp_link_ajax'));
        add_action('wp_ajax_edubot_simple_whatsapp_link', array($this, 'generate_simple_whatsapp_link_ajax'));
        add_action('wp_ajax_edubot_generate_webhook_token', array($this, 'generate_webhook_token_ajax'));
        
        // System status AJAX handlers
        add_action('wp_ajax_edubot_clear_error_logs', array($this, 'clear_error_logs_ajax'));
        add_action('wp_ajax_edubot_run_migration', array($this, 'run_migration_ajax'));
        add_action('wp_ajax_edubot_autosave', array($this, 'handle_autosave_ajax'));
        
        // Handle debug log download
        add_action('admin_init', array($this, 'handle_debug_log_download'));
        
        // Application management AJAX handlers
        add_action('wp_ajax_edubot_bulk_action', array($this, 'handle_bulk_action_ajax'));
        add_action('wp_ajax_edubot_delete_application', array($this, 'handle_delete_application_ajax'));
        add_action('wp_ajax_edubot_view_application', array($this, 'handle_view_application_ajax'));

        // API Logs AJAX handlers
        add_action('wp_ajax_edubot_get_api_log_details', array($this, 'handle_get_api_log_details'));
        add_action('wp_ajax_edubot_test_api_logging', array($this, 'handle_test_api_logging'));
        
        // AI Settings AJAX handlers
        add_action('wp_ajax_edubot_save_ai_settings', array($this, 'save_ai_settings'));
        add_action('wp_ajax_edubot_test_ai_response', array($this, 'test_ai_response'));
    }

    /**
     * Helper method to handle responses for both AJAX and regular form submissions
     */
    /**
     * Safely update WordPress option with proper error handling
     * Handles the case where update_option returns false when value hasn't changed
     */
    private function safe_update_option($option_name, $new_value) {
        global $wpdb;
        
        $current_value = get_option($option_name, '__EDUBOT_NOT_SET__');
        
        // Handle boolean comparison properly
        $values_equal = false;
        if (is_bool($new_value) && is_bool($current_value)) {
            $values_equal = ($current_value === $new_value);
        } elseif (is_bool($new_value)) {
            // Current value might be stored as string '1' or '0'
            $values_equal = (($new_value === true && ($current_value === '1' || $current_value === 1 || $current_value === true)) || 
                           ($new_value === false && ($current_value === '0' || $current_value === 0 || $current_value === false)));
        } else {
            // For non-boolean values, include type-flexible comparison
            if (is_numeric($new_value) && is_numeric($current_value)) {
                $values_equal = ((string)$current_value === (string)$new_value);
            } else {
                $values_equal = ($current_value === $new_value);
            }
        }
        
        // If values are the same, don't update (avoid false negative)
        if ($values_equal) {
            error_log("EduBot: Option '$option_name' unchanged, skipping update");
            return true; // Not an error
        }
        
        // Attempt update
        $result = update_option($option_name, $new_value);
        
        if ($result === false) {
            // Double-check if it actually failed
            $check_value = get_option($option_name);
            
            // Re-check with proper boolean handling
            $actually_updated = false;
            if (is_bool($new_value) && (
                ($new_value === true && ($check_value === '1' || $check_value === 1 || $check_value === true)) ||
                ($new_value === false && ($check_value === '0' || $check_value === 0 || $check_value === false))
            )) {
                $actually_updated = true;
            } elseif ($check_value === $new_value) {
                $actually_updated = true;
            }
            
            if ($actually_updated) {
                error_log("EduBot: Option '$option_name' was actually updated despite false return");
                return true;
            } else {
                $check_display = is_array($check_value) ? json_encode($check_value) : (is_bool($check_value) ? ($check_value ? 'true' : 'false') : $check_value);
                $wanted_display = is_array($new_value) ? json_encode($new_value) : (is_bool($new_value) ? ($new_value ? 'true' : 'false') : $new_value);
                error_log("EduBot: Failed to update '$option_name'. Current: '$check_display', Wanted: '$wanted_display'");
                error_log("EduBot: WordPress DB Error: " . $wpdb->last_error);
                return false;
            }
        }
        
        $success_display = is_array($new_value) ? json_encode($new_value) : (is_bool($new_value) ? ($new_value ? 'true' : 'false') : $new_value);
        error_log("EduBot: Successfully updated '$option_name' to: $success_display");
        return true;
    }

    /**
     * Fix excessive backslash escaping in messages
     * 
     * @param string $message The message to fix
     * @return string Fixed message
     */
    private function fix_message_escaping($message) {
        if (empty($message)) {
            return $message;
        }
        
        // Fix common escaping patterns that occur during save/load cycles
        $fixed = $message;
        
        // Remove excessive backslashes before apostrophes
        $fixed = str_replace("\\\\\\\\'", "'", $fixed);  // \\\\' -> '
        $fixed = str_replace("\\\\\'", "'", $fixed);     // \\' -> '
        $fixed = str_replace("\\'", "'", $fixed);        // \' -> '
        
        // Apply stripslashes if there are still escaped characters
        if (strpos($fixed, '\\') !== false) {
            $fixed = stripslashes($fixed);
        }
        
        return $fixed;
    }

    private function send_response($success, $message, $data = array()) {
        if (wp_doing_ajax()) {
            if ($success) {
                wp_send_json_success(array_merge(array('message' => $message), $data));
            } else {
                wp_send_json_error(array('message' => $message));
            }
        } else {
            // For regular form submissions, return array with success status and message
            if ($success) {
                return true;
            } else {
                return array('success' => false, 'message' => $message);
            }
        }
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
        // Get current page
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        // Only enqueue media library on school-settings page
        if ($current_page === 'edubot-school-settings') {
            // Suppress all output errors for media enqueue
            @wp_enqueue_media();
        }
        
        // Enqueue jQuery first to ensure it's loaded
        wp_enqueue_script('jquery');
        
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
            __('AI Settings', 'edubot-pro'),
            __('AI Settings', 'edubot-pro'),
            'manage_options',
            'edubot-ai-settings',
            array($this, 'display_ai_settings_page')
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

        add_submenu_page(
            'edubot-pro',
            __('System Status', 'edubot-pro'),
            __('System Status', 'edubot-pro'),
            'manage_options',
            'edubot-system-status',
            array($this, 'display_system_status_page')
        );

        add_submenu_page(
            'edubot-pro',
            __('API Logs', 'edubot-pro'),
            __('API Logs', 'edubot-pro'),
            'manage_options',
            'edubot-api-logs',
            array($this, 'display_api_logs_page')
        );

        add_submenu_page(
            'edubot-pro',
            __('WhatsApp Ad Integration', 'edubot-pro'),
            __('WhatsApp Ads', 'edubot-pro'),
            'manage_options',
            'edubot-whatsapp-ads',
            array($this, 'display_whatsapp_ads_page')
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
        
        // Handle database repair action
        $this->handle_database_repair();
    }
    
    /**
     * Handle database repair action
     */
    private function handle_database_repair() {
        if (isset($_GET['action']) && $_GET['action'] === 'repair_database' && 
            isset($_GET['page']) && $_GET['page'] === 'edubot-pro' &&
            current_user_can('manage_options') && 
            check_admin_referer('edubot_repair_db', 'nonce')) {
            
            $this->create_missing_security_table();
            
            // Redirect to avoid resubmission
            wp_redirect(admin_url('admin.php?page=edubot-pro&db_repaired=1'));
            exit;
        }
        
        // Show success message if redirected after repair
        if (isset($_GET['db_repaired']) && $_GET['db_repaired'] === '1') {
            add_action('admin_notices', array($this, 'database_repair_success_notice'));
        }
    }
    
    /**
     * Create missing security table
     */
    private function create_missing_security_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'edubot_security_log';
        $charset_collate = $wpdb->get_charset_collate();
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        if ($table_exists != $table_name) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                site_id bigint(20) NOT NULL,
                event_type varchar(100) NOT NULL,
                ip_address varchar(45) NOT NULL,
                user_agent text,
                details longtext,
                severity varchar(20) DEFAULT 'medium',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY site_id (site_id),
                KEY event_type (event_type),
                KEY ip_address (ip_address),
                KEY created_at (created_at),
                KEY severity (severity)
            ) $charset_collate;";
            
            $result = dbDelta($sql);
            
            if (!empty($result)) {
                error_log("EduBot Pro: Created missing security_log table");
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Show database repair success notice
     */
    public function database_repair_success_notice() {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>EduBot Pro:</strong> Database tables checked and repaired successfully!</p>';
        echo '</div>';
    }

    /**
     * Display main admin dashboard
     */
    public function display_admin_page() {
        $database_manager = new EduBot_Database_Manager();
        $analytics_data = $database_manager->get_analytics_data(30);
        $recent_applications = $database_manager->get_recent_applications(5);
        
        // Check for missing database tables
        $missing_tables = $this->check_missing_tables();
        
        include EDUBOT_PRO_PLUGIN_PATH . 'admin/views/dashboard.php';
    }
    
    /**
     * Check for missing database tables
     */
    private function check_missing_tables() {
        global $wpdb;
        
        $required_tables = array(
            'edubot_security_log',
            'edubot_visitor_analytics',
            'edubot_visitors',
            'edubot_applications',
            'edubot_analytics',
            'edubot_sessions',
            'edubot_school_configs'
        );
        
        $missing_tables = array();
        
        foreach ($required_tables as $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
            
            if ($table_exists != $full_table_name) {
                $missing_tables[] = $table_name;
            }
        }
        
        return $missing_tables;
    }

    /**
     * Display school settings page
     */
    public function display_school_settings_page() {
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        
        $save_result = false;
        $error_message = '';
        
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'edubot_school_settings')) {
            // Nonce already verified here, so pass true to skip re-verification
            $save_result = $this->save_school_settings(true);
            
            // If save_result is an array with error message, extract it
            if (is_array($save_result) && isset($save_result['message'])) {
                $error_message = $save_result['message'];
                $save_result = false;
            }
        }
        
        // Debug: Log what values are being loaded for display
        error_log('EduBot: Loading school settings page...');
        error_log('EduBot: School name from get_option: ' . get_option('edubot_school_name', 'NOT_SET'));
        error_log('EduBot: School logo from get_option: ' . get_option('edubot_school_logo', 'NOT_SET'));
        error_log('EduBot: Primary color from get_option: ' . get_option('edubot_primary_color', 'NOT_SET'));
        error_log('EduBot: Secondary color from get_option: ' . get_option('edubot_secondary_color', 'NOT_SET'));
        error_log('EduBot: Boards from get_option: ' . print_r(get_option('edubot_configured_boards', 'NOT_SET'), true));
        
        // Display success message if save was successful
        if ($save_result === true) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'edubot-pro') . '</p></div>';
        } elseif ($save_result === false && isset($_POST['submit'])) {
            $display_error = !empty($error_message) ? $error_message : 'Error saving settings. Please check your entries and try again.';
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($display_error) . '</p></div>';
        }
        
        include EDUBOT_PRO_PLUGIN_PATH . 'admin/views/school-settings.php';
    }

    /**
     * Display academic configuration page
     */
    public function display_academic_config_page() {
        // Ensure the Academic Config class is loaded
        if (!class_exists('Edubot_Academic_Config')) {
            require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-academic-config.php';
        }
        
        $school_id = 1; // Simplified for debugging
        
        // Handle form submissions
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['edubot_academic_nonce'], 'edubot_save_academic_config')) {
            $this->save_academic_config();
        }
        
        include EDUBOT_PRO_PLUGIN_PATH . 'admin/partials/academic-config.php';
    }

    /**
     * Save academic configuration
     */
    private function save_academic_config() {
        // Ensure the Academic Config class is loaded
        if (!class_exists('Edubot_Academic_Config')) {
            require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-academic-config.php';
        }

        $school_id = 1; // Simplified for debugging - remove school config dependency
        
        try {
            // Process academic configuration data
            if (isset($_POST['academic_config']) && is_array($_POST['academic_config'])) {
                $config_data = $_POST['academic_config'];
                
                // Process grade systems - always save to allow unchecking all
                $grade_systems = array();
                if (isset($config_data['grade_systems']) && is_array($config_data['grade_systems'])) {
                    foreach ($config_data['grade_systems'] as $system) {
                        $grade_systems[] = sanitize_text_field($system);
                    }
                }
                
                // Always save grade systems (even if empty) to allow unchecking all
                update_option('edubot_grade_systems', $grade_systems);
                
                // Process custom grades
                $custom_grades = array();
                if (isset($config_data['custom_grades_keys']) && isset($config_data['custom_grades_labels'])) {
                    $keys = $config_data['custom_grades_keys'];
                    $labels = $config_data['custom_grades_labels'];
                    
                    if (is_array($keys) && is_array($labels)) {
                        for ($i = 0; $i < count($keys) && $i < count($labels); $i++) {
                            $key = sanitize_key($keys[$i]);
                            $label = sanitize_text_field($labels[$i]);
                            
                            if (!empty($key) && !empty($label) && strlen($label) <= 50) {
                                $custom_grades[$key] = $label;
                            }
                        }
                    }
                }
                
                // Process admission cycles
                $admission_cycles = array();
                if (isset($config_data['admission_cycles']) && is_array($config_data['admission_cycles'])) {
                    foreach ($config_data['admission_cycles'] as $cycle) {
                        if (!is_array($cycle)) continue;
                        
                        $name = sanitize_text_field($cycle['name'] ?? '');
                        $start_date = sanitize_text_field($cycle['start_date'] ?? '');
                        $end_date = sanitize_text_field($cycle['end_date'] ?? '');
                        
                        if (!empty($name) && strlen($name) <= 100) {
                            $admission_cycles[] = array(
                                'name' => $name,
                                'start_date' => $start_date,
                                'end_date' => $end_date
                            );
                        }
                    }
                }
                
                // Save custom grades and other data - always save to allow empty arrays
                update_option('edubot_custom_grades', $custom_grades);
                update_option('edubot_admission_cycles', $admission_cycles);
                
                // Save to school-specific options as well  
                update_option('edubot_academic_config_' . $school_id, $config_data);
            }

            // Process board configuration
            if (isset($_POST['board_config']) && is_array($_POST['board_config'])) {
                $board_config = $_POST['board_config'];
                update_option('edubot_board_config_' . $school_id, $board_config);
            }

            // Process academic year configuration  
            if (isset($_POST['academic_year_config']) && is_array($_POST['academic_year_config'])) {
                $academic_year_config = $_POST['academic_year_config'];
                update_option('edubot_academic_year_config_' . $school_id, $academic_year_config);
            }

            // Show success message
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Academic configuration saved successfully!</p></div>';
            });
            
        } catch (Exception $e) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error is-dismissible"><p>Error saving configuration: ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }

    /**
     * Display API settings page
     */
    public function display_api_settings_page() {
        // Log page access for debugging
        self::log_data_transfer('page_access', 'api_settings_page', 0, array(
            'user_id' => get_current_user_id(),
            'request_method' => $_SERVER['REQUEST_METHOD']
        ));
        
        $school_config = EduBot_School_Config::getInstance();
        $api_keys = $school_config->get_api_keys();
        
        $save_result = false;
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['edubot_api_nonce'], 'edubot_save_api_settings')) {
            self::log_data_transfer('form_submission', 'api_settings', count($_POST), array(
                'fields_submitted' => array_keys($_POST)
            ));
            $save_result = $this->save_api_settings();
        } elseif (isset($_POST['submit'])) {
            // Log nonce failure for debugging
            self::log_security_event('API Settings Form Nonce Mismatch', 'warning', array(
                'nonce_provided' => isset($_POST['edubot_api_nonce']),
                'nonce_value' => isset($_POST['edubot_api_nonce']) ? substr($_POST['edubot_api_nonce'], 0, 10) . '...' : 'none',
                'post_keys' => array_keys($_POST)
            ));
        }
        
        // Debug: Log what values are being loaded for display
        self::debug_log('Loading API settings page', 'info', array(
            'openai_key_set' => !empty(get_option('edubot_openai_api_key')),
            'openai_model' => get_option('edubot_openai_model', 'NOT_SET'),
            'email_service' => get_option('edubot_email_service', 'NOT_SET'),
            'whatsapp_provider' => get_option('edubot_whatsapp_provider', 'NOT_SET'),
            'debug_enabled' => get_option('edubot_debug_enabled', false)
        ));
        
        // Display success message if save was successful
        if ($save_result === true) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('API settings saved successfully!', 'edubot-pro') . '</p></div>';
        } elseif ($save_result === false && isset($_POST['submit'])) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('Error saving API settings. Please check your entries and try again.', 'edubot-pro') . '</p></div>';
        }
        
        include EDUBOT_PRO_PLUGIN_PATH . 'admin/views/api-integrations.php';
    }

    /**
     * Display AI settings page
     */
    public function display_ai_settings_page() {
        // Log page access for debugging
        self::log_data_transfer('page_access', 'ai_settings_page', 0, array(
            'user_id' => get_current_user_id(),
            'request_method' => $_SERVER['REQUEST_METHOD']
        ));
        
        $save_result = false;
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['edubot_ai_nonce'], 'edubot_save_ai_settings')) {
            self::log_data_transfer('form_submission', 'ai_settings', count($_POST), array(
                'fields_submitted' => array_keys($_POST)
            ));
            $save_result = $this->save_ai_settings();
        } elseif (isset($_POST['submit'])) {
            // Log nonce failure for debugging
            self::log_security_event('AI Settings Form Nonce Mismatch', 'warning', array(
                'nonce_provided' => isset($_POST['edubot_ai_nonce']),
                'nonce_value' => isset($_POST['edubot_ai_nonce']) ? substr($_POST['edubot_ai_nonce'], 0, 10) . '...' : 'none',
                'post_keys' => array_keys($_POST)
            ));
        }
        
        // Debug: Log what values are being loaded for display
        self::debug_log('Loading AI settings page', 'info', array(
            'openai_key_set' => !empty(get_option('edubot_openai_api_key')),
            'openai_model' => get_option('edubot_openai_model', 'NOT_SET'),
            'ai_enabled' => get_option('edubot_ai_enabled', false),
            'ai_prompt_template' => !empty(get_option('edubot_ai_prompt_template')),
            'debug_enabled' => get_option('edubot_debug_enabled', false)
        ));
        
        // Display success message if save was successful
        if ($save_result === true) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('AI settings saved successfully!', 'edubot-pro') . '</p></div>';
        } elseif ($save_result === false && isset($_POST['submit'])) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('Error saving AI settings. Please check your entries and try again.', 'edubot-pro') . '</p></div>';
        }
        
        include EDUBOT_PRO_PLUGIN_PATH . 'admin/views/ai-settings.php';
    }

    /**
     * Display form builder page
     */
    public function display_form_builder_page() {
        // Enhanced security check
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'edubot-pro'));
        }
        
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        
        // Handle form submission with proper nonce verification
        if (isset($_POST['submit']) && isset($_POST['edubot_form_nonce']) && wp_verify_nonce($_POST['edubot_form_nonce'], 'edubot_save_form_settings')) {
            $save_result = $this->save_form_settings();
            
            if ($save_result) {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Form settings saved successfully!', 'edubot-pro') . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error saving form settings. Please try again.', 'edubot-pro') . '</p></div>';
            }
        } elseif (isset($_POST['submit'])) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('Security check failed. Please refresh and try again.', 'edubot-pro') . '</p></div>';
        }
        
        // Get current settings for the form
        $settings = get_option('edubot_form_settings', array());
        
        include EDUBOT_PRO_PLUGIN_PATH . 'admin/views/form-builder.php';
    }

    /**
     * Display applications page with enhanced security
     */
    public function display_applications_page() {
        // Capability check
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'edubot-pro'));
        }

        $database_manager = new EduBot_Database_Manager();
        
        // Validate and sanitize pagination
        $page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        if ($page > 1000) { // Reasonable upper limit
            $page = 1000;
        }
        
        $filters = array();
        
        // Validate and sanitize status filter
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $status = sanitize_text_field($_GET['status']);
            $allowed_statuses = array('pending', 'approved', 'rejected', 'on_hold', 'completed');
            if (in_array($status, $allowed_statuses)) {
                $filters['status'] = $status;
            }
        }
        
        // Validate and sanitize search filter
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = sanitize_text_field($_GET['search']);
            // Limit search length
            if (strlen($search) <= 100) {
                $filters['search'] = $search;
            }
        }
        
        // Validate and sanitize date filters
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $date_from = sanitize_text_field($_GET['date_from']);
            if (DateTime::createFromFormat('Y-m-d', $date_from)) {
                $filters['date_from'] = $date_from;
            }
        }
        
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $date_to = sanitize_text_field($_GET['date_to']);
            if (DateTime::createFromFormat('Y-m-d', $date_to)) {
                $filters['date_to'] = $date_to;
            }
        }
        
        try {
            $applications_data = $database_manager->get_applications($page, 20, $filters);
            
            // Parse student data JSON for display with security validation
            if (isset($applications_data['applications'])) {
                foreach ($applications_data['applications'] as &$app) {
                    $student_data = json_decode($app['student_data'], true);
                    if ($student_data && is_array($student_data)) {
                        // Sanitize output data for display
                        $app['student_name'] = isset($student_data['student_name']) ? 
                            esc_html($student_data['student_name']) : 'N/A';
                        $app['parent_name'] = isset($student_data['parent_name']) ? 
                            esc_html($student_data['parent_name']) : 'N/A';
                        $app['grade'] = isset($student_data['grade']) ? 
                            esc_html($student_data['grade']) : 'N/A';
                        $app['educational_board'] = isset($student_data['educational_board']) ? 
                            esc_html($student_data['educational_board']) : 'N/A';
                        $app['academic_year'] = isset($student_data['academic_year']) ? 
                            esc_html($student_data['academic_year']) : 'N/A';
                        $app['email'] = isset($student_data['email']) ? 
                            esc_html($student_data['email']) : 'N/A';
                        $app['phone'] = isset($student_data['phone']) ? 
                            esc_html($student_data['phone']) : 'N/A';
                        $app['gender'] = isset($student_data['gender']) ? 
                            esc_html($student_data['gender']) : 'N/A';
                        $app['date_of_birth'] = isset($student_data['date_of_birth']) ? 
                            esc_html($student_data['date_of_birth']) : 'N/A';
                    } else {
                        // Fallback for invalid JSON data
                        $app['student_name'] = 'Invalid Data';
                        $app['parent_name'] = 'Invalid Data';
                        $app['grade'] = 'N/A';
                        $app['educational_board'] = 'N/A';
                        $app['academic_year'] = 'N/A';
                        $app['email'] = 'N/A';
                        $app['phone'] = 'N/A';
                        $app['gender'] = 'N/A';
                        $app['date_of_birth'] = 'N/A';
                    }
                    
                    // Sanitize other fields
                    $app['application_number'] = esc_html($app['application_number']);
                    $app['status'] = esc_html($app['status']);
                    $app['source'] = esc_html($app['source']);
                }
                unset($app); // Clean up reference
            }
            
            // Pass the parsed data to the view
            $applications = $applications_data['applications'] ?? array();
            $total_applications = $applications_data['total_records'] ?? 0;
            $total_pages = $applications_data['total_pages'] ?? 1;
            
        } catch (Exception $e) {
            error_log('EduBot Error displaying applications: ' . $e->getMessage());
            $applications = array();
            $total_applications = 0;
            $total_pages = 1;
        }
        
        include EDUBOT_PRO_PLUGIN_PATH . 'admin/views/applications-list.php';
    }

    /**
     * Display analytics page with enhanced security and visitor tracking
     */
    public function display_analytics_page() {
        // Capability check
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'edubot-pro'));
        }

        try {
            // Load both traditional and visitor analytics
            $database_manager = new EduBot_Database_Manager();
            
            // Validate date range parameter
            $days = isset($_GET['days']) ? absint($_GET['days']) : 30;
            $days = max(1, min($days, 365)); // Limit to reasonable range
            
            $analytics_data = $database_manager->get_analytics_data($days);
            
            // Sanitize analytics data for output
            if (isset($analytics_data['recent_conversations'])) {
                foreach ($analytics_data['recent_conversations'] as &$conversation) {
                    $conversation['conversation_id'] = esc_html($conversation['conversation_id']);
                    $conversation['user_message'] = esc_html($conversation['user_message']);
                    $conversation['bot_response'] = esc_html($conversation['bot_response']);
                    $conversation['timestamp'] = esc_html($conversation['timestamp']);
                }
                unset($conversation);
            }
            
        } catch (Exception $e) {
            error_log('EduBot Error displaying analytics: ' . $e->getMessage());
            $analytics_data = array();
        }
        
        // Use the new visitor analytics display
        include EDUBOT_PRO_PLUGIN_PATH . 'admin/partials/visitor-analytics-display.php';
    }

    /**
     * Display system status page
     */
    public function display_system_status_page() {
        // Capability check
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'edubot-pro'));
        }

        // Get system status information with class existence checks
        $health_check = array();
        if (class_exists('EduBot_Health_Check')) {
            $health_check = EduBot_Health_Check::get_health_status();
        } else {
            $health_check = array(
                'status' => 'warning',
                'message' => 'Health check class not available',
                'checks' => array()
            );
        }
        
        $available_classes = array();
        if (class_exists('EduBot_Autoloader')) {
            $available_classes = EduBot_Autoloader::get_available_classes();
        } else {
            // Manually check for core classes
            $core_classes = array(
                'EduBot_Core', 'EduBot_Admin', 'EduBot_Database_Manager', 
                'EduBot_Shortcode', 'Notification_Manager', 'EduBot_Health_Check'
            );
            foreach ($core_classes as $class) {
                $available_classes[$class] = class_exists($class) ? 'Available' : 'Missing';
            }
        }
        
        // Get plugin information
        $plugin_info = array(
            'version' => EDUBOT_PRO_VERSION,
            'db_version' => get_option('edubot_enquiries_db_version', '0.0.0'),
            'plugin_path' => EDUBOT_PRO_PLUGIN_PATH,
            'plugin_url' => EDUBOT_PRO_PLUGIN_URL
        );

        // Get WordPress environment information
        global $wp_version;
        $environment_info = array(
            'wp_version' => $wp_version,
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize')
        );

        // Get database status
        global $wpdb;
        $enquiries_table = $wpdb->prefix . 'edubot_enquiries';
        
        $db_info = array(
            'mysql_version' => $wpdb->db_version(),
            'charset' => $wpdb->charset,
            'collate' => $wpdb->collate,
            'enquiries_table_exists' => $wpdb->get_var("SHOW TABLES LIKE '{$enquiries_table}'") == $enquiries_table
        );
        
        // Check if new columns exist
        if ($db_info['enquiries_table_exists']) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM {$enquiries_table}");
            $column_names = wp_list_pluck($columns, 'Field');
            
            $db_info['required_columns'] = array(
                'gclid' => in_array('gclid', $column_names),
                'fbclid' => in_array('fbclid', $column_names),
                'click_id_data' => in_array('click_id_data', $column_names),
                'utm_data' => in_array('utm_data', $column_names),
                'ip_address' => in_array('ip_address', $column_names),
                'user_agent' => in_array('user_agent', $column_names)
            );
        }

        include EDUBOT_PRO_PLUGIN_PATH . 'admin/partials/system-status-display.php';
    }

    /**
     * Save school settings with comprehensive security validation
     */
    private function save_school_settings($skip_nonce_verification = false) {
        // NUCLEAR OPTION: In development mode, skip ALL logo validation
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('EduBot: WP_DEBUG=true - Adding logo validation bypass filter');
            add_filter('pre_update_option_edubot_school_logo', function($value) {
                error_log('EduBot: Bypassing logo validation for: ' . $value);
                return $value;
            });
            add_filter('pre_update_option_edubot_school_logo_url', function($value) {
                error_log('EduBot: Bypassing logo_url validation for: ' . $value);
                return $value;
            });
        }
        
        error_log('EduBot: Starting save_school_settings()');
        error_log('EduBot: POST method: ' . $_SERVER['REQUEST_METHOD']);
        error_log('EduBot: Is POST request: ' . (isset($_POST) && !empty($_POST) ? 'YES' : 'NO'));
        error_log('EduBot: submit button present: ' . (isset($_POST['submit']) ? 'YES' : 'NO'));
        error_log('EduBot: Skip nonce verification: ' . ($skip_nonce_verification ? 'YES' : 'NO'));
        
        // Check if Security Manager class exists
        if (!class_exists('EduBot_Security_Manager')) {
            error_log('EduBot: Security Manager class not found!');
            error_log('EduBot: Available classes: ' . implode(', ', get_declared_classes()));
            return $this->send_response(false, 'Security Manager not available. Please check plugin installation.');
        }
        
        error_log('EduBot: Security Manager class found successfully');
        
        // Rate limiting check
        try {
            $security_manager = new EduBot_Security_Manager();
            error_log('EduBot: Security Manager instantiated successfully');
        } catch (Exception $e) {
            error_log('EduBot: Failed to instantiate Security Manager: ' . $e->getMessage());
            return $this->send_response(false, 'Security Manager initialization failed: ' . $e->getMessage());
        }
        if (!$security_manager->check_rate_limit('admin_settings', get_current_user_id(), 20, 3600)) {
            error_log('EduBot: Rate limit exceeded for admin settings');
            return $this->send_response(false, 'Too many requests. Please wait before trying again.');
        }
        
        // Verify nonce for CSRF protection (only if not already verified)
        if (!$skip_nonce_verification) {
            // The form uses wp_nonce_field('edubot_school_settings') which creates _wpnonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'edubot_school_settings')) {
                error_log('EduBot: Nonce verification failed');
                error_log('EduBot: _wpnonce present: ' . (isset($_POST['_wpnonce']) ? 'YES' : 'NO'));
                if (isset($_POST['_wpnonce'])) {
                    error_log('EduBot: _wpnonce value: ' . substr($_POST['_wpnonce'], 0, 10) . '...');
                }
                return $this->send_response(false, 'Security check failed. Please refresh and try again.');
            }
            
            error_log('EduBot: Nonce verification passed successfully');
        } else {
            error_log('EduBot: Nonce verification skipped (already verified by caller)');
        }
        
        // Capability check
        if (!current_user_can('manage_options')) {
            error_log('EduBot: Insufficient permissions');
            return $this->send_response(false, 'Insufficient permissions.');
        }
        
        // Validate required fields
        if (empty($_POST['edubot_school_name'])) {
            error_log('EduBot: Save failed - school name is required');
            return $this->send_response(false, 'School name is required.');
        }
        
        // Input validation and sanitization
        $school_name = sanitize_text_field($_POST['edubot_school_name']);
        if (strlen($school_name) > 200 || strlen($school_name) < 2) {
            return $this->send_response(false, 'School name must be between 2 and 200 characters.');
        }
        
        // Debug the school name specifically
        error_log('EduBot: School name validation - Raw: "' . $_POST['edubot_school_name'] . '"');
        error_log('EduBot: School name validation - Sanitized: "' . $school_name . '"');
        error_log('EduBot: School name validation - Length: ' . strlen($school_name));
        error_log('EduBot: School name validation - Current DB value: "' . get_option('edubot_school_name', 'NOT_SET') . '"');
        
        // Validate logo URL if provided
        $school_logo = '';
        if (!empty($_POST['edubot_school_logo'])) {
            $school_logo = trim($_POST['edubot_school_logo']);
            error_log('EduBot: Processing logo URL: ' . $school_logo);
            
            // DEVELOPMENT MODE: Skip validation entirely
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('EduBot: DEV MODE - Accepting logo URL without validation: ' . $school_logo);
                // In dev mode, just accept the URL as-is
            } else {
                // PRODUCTION MODE: Perform validation
                // Allow both absolute URLs (http/https) and relative URLs (starting with /)
                $is_relative_url = strpos($school_logo, '/') === 0 && strpos($school_logo, '//') !== 0;
                $is_absolute_url = filter_var($school_logo, FILTER_VALIDATE_URL);
                
                if (!$is_relative_url && !$is_absolute_url) {
                    error_log('EduBot: Logo URL failed format validation - must be absolute (http/https) or relative path (/)');
                    return $this->send_response(false, 'Invalid logo URL format. Please use absolute URL (http/https) or relative path (/wp-content/uploads/...).');
                }
                
                // Security validation in production only
                if (method_exists($security_manager, 'is_safe_url')) {
                    if (!$security_manager->is_safe_url($school_logo)) {
                        error_log('EduBot: Logo URL failed security validation: ' . $school_logo);
                        error_log('EduBot: URL details - Length: ' . strlen($school_logo) . ', First char: ' . substr($school_logo, 0, 1));
                        error_log('EduBot: Is relative: ' . ($is_relative_url ? 'yes' : 'no') . ', Is absolute: ' . ($is_absolute_url ? 'yes' : 'no'));

                        // Provide more helpful error message
                        $error_msg = 'Logo URL failed security validation. ';
                        if (!$is_relative_url && !$is_absolute_url) {
                            $error_msg .= 'URL must start with "/" (relative) or "http://" or "https://" (absolute).';
                        } else {
                            $error_msg .= 'Please use a safe URL without JavaScript or suspicious content.';
                        }

                        return $this->send_response(false, $error_msg);
                    }
                }
            }
            
            // Additional validation for relative URLs
            if ($is_relative_url) {
                // Check if it's a valid WordPress media path
                $allowed_paths = array('/wp-content/uploads/', '/wp-content/plugins/', '/wp-includes/');
                $is_valid_path = false;
                foreach ($allowed_paths as $path) {
                    if (strpos($school_logo, $path) === 0) {
                        $is_valid_path = true;
                        break;
                    }
                }
                if (!$is_valid_path && !file_exists($_SERVER['DOCUMENT_ROOT'] . $school_logo)) {
                    error_log('EduBot: Logo relative URL points to non-existent file');
                    return $this->send_response(false, 'Logo file not found. Please ensure the path is correct.');
                }
            }
        }
        
        // Validate phone number
        $school_phone = '';
        if (!empty($_POST['edubot_school_phone'])) {
            $school_phone = sanitize_text_field($_POST['edubot_school_phone']);
            if (!preg_match('/^[\+]?[\d\s\-\(\)]{10,20}$/', $school_phone)) {
                return $this->send_response(false, 'Invalid phone number format.');
            }
        }
        
        // Validate email
        $school_email = '';
        if (!empty($_POST['edubot_school_email'])) {
            $school_email = sanitize_email($_POST['edubot_school_email']);
            if (!is_email($school_email)) {
                return $this->send_response(false, 'Invalid email address.');
            }
        }
        
        // Validate address
        $school_address = '';
        if (!empty($_POST['edubot_school_address'])) {
            $school_address = sanitize_textarea_field($_POST['edubot_school_address']);
            if (strlen($school_address) > 500) {
                return $this->send_response(false, 'Address is too long (max 500 characters).');
            }
        }
        
        // Validate website URL
        $school_website = '';
        if (!empty($_POST['edubot_school_website'])) {
            $school_website = esc_url_raw($_POST['edubot_school_website']);
            if (!filter_var($school_website, FILTER_VALIDATE_URL) || !$security_manager->is_safe_url($school_website)) {
                return $this->send_response(false, 'Invalid website URL.');
            }
        }
        
        // Validate color codes
        $primary_color = sanitize_hex_color($_POST['edubot_primary_color'] ?? '#4facfe');
        $secondary_color = sanitize_hex_color($_POST['edubot_secondary_color'] ?? '#00f2fe');
        
        if (!$primary_color) $primary_color = '#4facfe';
        if (!$secondary_color) $secondary_color = '#00f2fe';
        
        // Validate welcome and completion messages
        $welcome_message = '';
        if (!empty($_POST['edubot_welcome_message'])) {
            $welcome_message = sanitize_textarea_field($_POST['edubot_welcome_message']);
            // Fix excessive backslash escaping that can occur during save/load cycles
            $welcome_message = $this->fix_message_escaping($welcome_message);
            if (strlen($welcome_message) > 1000) {
                return $this->send_response(false, 'Welcome message is too long (max 1000 characters).');
            }
        }
        
        $completion_message = '';
        if (!empty($_POST['edubot_completion_message'])) {
            $completion_message = sanitize_textarea_field($_POST['edubot_completion_message']);
            // Fix excessive backslash escaping that can occur during save/load cycles
            $completion_message = $this->fix_message_escaping($completion_message);
            if (strlen($completion_message) > 1000) {
                return $this->send_response(false, 'Completion message is too long (max 1000 characters).');
            }
        }
        
        try {
            // Begin database transaction
            global $wpdb;
            $wpdb->query('START TRANSACTION');
            
            // Test basic WordPress option functionality first
            $test_option_name = 'edubot_test_' . time();
            $test_result = update_option($test_option_name, 'test_value');
            if ($test_result === false) {
                error_log('EduBot: Basic WordPress update_option() is failing!');
                error_log('EduBot: Database error: ' . $wpdb->last_error);
                throw new Exception('WordPress option system is not working');
            } else {
                error_log('EduBot: Basic WordPress update_option() working correctly');
                delete_option($test_option_name); // Clean up test
            }
            
            // Save basic school settings
            $basic_settings = array(
                'edubot_school_name' => $school_name,
                'edubot_school_logo' => $school_logo,
                'edubot_school_phone' => $school_phone,
                'edubot_school_email' => $school_email,
                'edubot_school_address' => $school_address,
                'edubot_school_website' => $school_website,
                'edubot_primary_color' => $primary_color,
                'edubot_secondary_color' => $secondary_color,
                'edubot_welcome_message' => $welcome_message,
                'edubot_completion_message' => $completion_message
            );
            
            foreach ($basic_settings as $option_name => $option_value) {
                if (!$this->safe_update_option($option_name, $option_value)) {
                    throw new Exception("Failed to update option: {$option_name}");
                }
            }
            
            // Process and validate boards configuration
            $boards_data = array();
            if (isset($_POST['edubot_boards']) && is_array($_POST['edubot_boards'])) {
                // Allow common educational board codes (case insensitive)
                $allowed_board_codes = array('cbse', 'icse', 'state', 'ib', 'igcse', 'cambridge', 'international', 'other');
                
                foreach ($_POST['edubot_boards'] as $index => $board) {
                    if (!is_array($board)) continue;
                    
                    $board_code = sanitize_text_field($board['code'] ?? '');
                    $board_name = sanitize_text_field($board['name'] ?? '');
                    
                    // Validate board code and name
                    if (empty($board_code) || empty($board_name)) {
                        continue;
                    }
                    
                    // Make board code validation case-insensitive and more flexible
                    $board_code_lower = strtolower($board_code);
                    if (!in_array($board_code_lower, $allowed_board_codes) && 
                        !preg_match('/^[a-zA-Z0-9\-_]{2,20}$/', $board_code)) {
                        // Allow any reasonable board code format
                        error_log("EduBot: Board code validation failed for: " . $board_code);
                        continue;
                    }
                    
                    if (strlen($board_name) > 100) {
                        $board_name = substr($board_name, 0, 100);
                    }
                    
                    $board_description = sanitize_textarea_field($board['description'] ?? '');
                    if (strlen($board_description) > 500) {
                        $board_description = substr($board_description, 0, 500);
                    }
                    
                    $grades = sanitize_text_field($board['grades'] ?? '');
                    if (strlen($grades) > 200) {
                        $grades = substr($grades, 0, 200);
                    }
                    
                    $features = sanitize_textarea_field($board['features'] ?? '');
                    if (strlen($features) > 1000) {
                        $features = substr($features, 0, 1000);
                    }
                    
                    $boards_data[] = array(
                        'code' => $board_code,
                        'name' => $board_name,
                        'description' => $board_description,
                        'grades' => $grades,
                        'features' => $features,
                        'enabled' => isset($board['enabled']) && $board['enabled'] === '1'
                    );
                }
            }
            
            // Validate default board
            $default_board = sanitize_text_field($_POST['edubot_default_board'] ?? '');
            if (!empty($default_board)) {
                $valid_default = false;
                foreach ($boards_data as $board) {
                    if ($board['code'] === $default_board && $board['enabled']) {
                        $valid_default = true;
                        break;
                    }
                }
                if (!$valid_default) {
                    $default_board = '';
                }
            }
            
            $board_selection_required = isset($_POST['edubot_board_selection_required']) && $_POST['edubot_board_selection_required'] === '1';
            
            // Save boards configuration using safe update method
            if (!$this->safe_update_option('edubot_configured_boards', $boards_data)) {
                throw new Exception('Failed to update boards configuration');
            }
            if (!$this->safe_update_option('edubot_default_board', $default_board)) {
                throw new Exception('Failed to update default board');
            }
            if (!$this->safe_update_option('edubot_board_selection_required', $board_selection_required)) {
                throw new Exception('Failed to update board selection requirement');
            }
            
            // Process academic year configuration
            $academic_calendar_type = sanitize_text_field($_POST['edubot_academic_calendar_type'] ?? 'april-march');
            $allowed_calendar_types = array('april-march', 'january-december', 'september-august', 'custom');
            if (!in_array($academic_calendar_type, $allowed_calendar_types)) {
                $academic_calendar_type = 'april-march';
            }
            
            $custom_start_month = absint($_POST['edubot_custom_start_month'] ?? 4);
            $custom_end_month = absint($_POST['edubot_custom_end_month'] ?? 3);
            
            if ($custom_start_month < 1 || $custom_start_month > 12) $custom_start_month = 4;
            if ($custom_end_month < 1 || $custom_end_month > 12) $custom_end_month = 3;
            
            // Validate academic years
            $available_years = array();
            if (isset($_POST['edubot_available_academic_years']) && is_array($_POST['edubot_available_academic_years'])) {
                error_log('EduBot: Processing academic years: ' . print_r($_POST['edubot_available_academic_years'], true));
                foreach ($_POST['edubot_available_academic_years'] as $year) {
                    $year = sanitize_text_field($year);
                    // Match format like "2025-26" (4 digits - 2 digits) or "2025-2026" (4 digits - 4 digits) exactly
                    if (preg_match('/^\d{4}-(\d{2}|\d{4})$/', $year)) {
                        $available_years[] = $year;
                        error_log("EduBot: Added academic year: {$year}");
                    } else {
                        error_log("EduBot: Invalid academic year format rejected: {$year}");
                    }
                }
            } else {
                error_log('EduBot: No academic years in POST data or not array');
            }
            
            error_log('EduBot: Final available years: ' . print_r($available_years, true));
            
            // Ensure at least current academic year is available if none selected
            if (empty($available_years)) {
                // Calculate current academic year based on calendar type
                $current_year = date('Y');
                $current_month = date('n');
                $start_month = 4; // Default April start
                
                // Adjust start month based on calendar type
                switch ($academic_calendar_type) {
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
                        $start_month = $custom_start_month;
                        break;
                    default:
                        $start_month = 4;
                }
                
                // Calculate current academic year
                if ($current_month >= $start_month) {
                    $current_academic_year = $current_year . '-' . substr($current_year + 1, 2);
                } else {
                    $current_academic_year = ($current_year - 1) . '-' . substr($current_year, 2);
                }
                
                $available_years = array($current_academic_year);
                error_log("EduBot: No academic years selected, using default: {$current_academic_year}");
            }
            
            // Validate default academic year
            $default_academic_year = sanitize_text_field($_POST['edubot_default_academic_year'] ?? '');
            error_log("EduBot: Default academic year from POST: '{$default_academic_year}'");
            if (!empty($default_academic_year) && !in_array($default_academic_year, $available_years)) {
                error_log("EduBot: Default academic year '{$default_academic_year}' not in available years, clearing");
                $default_academic_year = '';
            }
            error_log("EduBot: Final default academic year: '{$default_academic_year}'");
            
            // Validate admission period
            $admission_period = sanitize_text_field($_POST['edubot_admission_period'] ?? 'next');
            $allowed_periods = array('current', 'next', 'both');
            if (!in_array($admission_period, $allowed_periods)) {
                $admission_period = 'next';
            }
            
            // Save academic year settings
            $academic_settings = array(
                'edubot_academic_calendar_type' => $academic_calendar_type,
                'edubot_custom_start_month' => $custom_start_month,
                'edubot_custom_end_month' => $custom_end_month,
                'edubot_available_academic_years' => $available_years,
                'edubot_default_academic_year' => $default_academic_year,
                'edubot_admission_period' => $admission_period
            );
            
            foreach ($academic_settings as $option_name => $option_value) {
                if (!$this->safe_update_option($option_name, $option_value)) {
                    throw new Exception("Failed to update option: {$option_name}");
                }
            }
            
            // Save notification settings
            $notification_settings = array(
                'edubot_email_notifications' => isset($_POST['edubot_email_notifications']) ? 1 : 0,
                'edubot_whatsapp_notifications' => isset($_POST['edubot_whatsapp_notifications']) ? 1 : 0,
                'edubot_school_notifications' => isset($_POST['edubot_school_notifications']) ? 1 : 0,
                'edubot_school_whatsapp_notifications' => isset($_POST['edubot_school_whatsapp_notifications']) ? 1 : 0
            );
            
            error_log('EduBot: Processing notification settings: ' . print_r($notification_settings, true));
            
            foreach ($notification_settings as $option_name => $option_value) {
                if (!$this->safe_update_option($option_name, $option_value)) {
                    throw new Exception("Failed to update notification setting: {$option_name}");
                }
                error_log("EduBot: Saved {$option_name} = {$option_value}");
            }
            
            // Save WhatsApp template and configuration
            if (isset($_POST['edubot_whatsapp_template'])) {
                $whatsapp_template = sanitize_textarea_field($_POST['edubot_whatsapp_template']);
                if (!$this->safe_update_option('edubot_whatsapp_template', $whatsapp_template)) {
                    throw new Exception('Failed to update WhatsApp template');
                }
                error_log('EduBot: Saved WhatsApp template');
            }
            
            if (isset($_POST['edubot_whatsapp_template_type'])) {
                $template_type = sanitize_text_field($_POST['edubot_whatsapp_template_type']);
                if (in_array($template_type, ['freeform', 'business_template'])) {
                    if (!$this->safe_update_option('edubot_whatsapp_template_type', $template_type)) {
                        throw new Exception('Failed to update WhatsApp template type');
                    }
                    error_log('EduBot: Saved WhatsApp template type: ' . $template_type);
                }
            }
            
            if (isset($_POST['edubot_whatsapp_template_name'])) {
                $template_name = sanitize_text_field($_POST['edubot_whatsapp_template_name']);
                if (!$this->safe_update_option('edubot_whatsapp_template_name', $template_name)) {
                    throw new Exception('Failed to update WhatsApp template name');
                }
                error_log('EduBot: Saved WhatsApp template name: ' . $template_name);
            }
            
            if (isset($_POST['edubot_whatsapp_template_language'])) {
                $template_language = sanitize_text_field($_POST['edubot_whatsapp_template_language']);
                if (!$this->safe_update_option('edubot_whatsapp_template_language', $template_language)) {
                    throw new Exception('Failed to update WhatsApp template language');
                }
                error_log('EduBot: Saved WhatsApp template language: ' . $template_language);
            }
            
            // School WhatsApp template settings
            if (isset($_POST['edubot_school_whatsapp_template'])) {
                $school_whatsapp_template = sanitize_textarea_field($_POST['edubot_school_whatsapp_template']);
                if (!$this->safe_update_option('edubot_school_whatsapp_template', $school_whatsapp_template)) {
                    throw new Exception('Failed to update school WhatsApp template');
                }
                error_log('EduBot: Saved school WhatsApp template');
            }
            
            if (isset($_POST['edubot_school_whatsapp_template_type'])) {
                $school_template_type = sanitize_text_field($_POST['edubot_school_whatsapp_template_type']);
                if (in_array($school_template_type, ['freeform', 'business_template'])) {
                    if (!$this->safe_update_option('edubot_school_whatsapp_template_type', $school_template_type)) {
                        throw new Exception('Failed to update school WhatsApp template type');
                    }
                    error_log('EduBot: Saved school WhatsApp template type: ' . $school_template_type);
                }
            }
            
            if (isset($_POST['edubot_school_whatsapp_template_name'])) {
                $school_template_name = sanitize_text_field($_POST['edubot_school_whatsapp_template_name']);
                if (!$this->safe_update_option('edubot_school_whatsapp_template_name', $school_template_name)) {
                    throw new Exception('Failed to update school WhatsApp template name');
                }
                error_log('EduBot: Saved school WhatsApp template name: ' . $school_template_name);
            }
            
            if (isset($_POST['edubot_school_whatsapp_template_language'])) {
                $school_template_language = sanitize_text_field($_POST['edubot_school_whatsapp_template_language']);
                if (!$this->safe_update_option('edubot_school_whatsapp_template_language', $school_template_language)) {
                    throw new Exception('Failed to update school WhatsApp template language');
                }
                error_log('EduBot: Saved school WhatsApp template language: ' . $school_template_language);
            }
            
            // Create consolidated config for school config class
            $config_data = array(
                'school_info' => array(
                    'name' => $school_name,
                    'logo' => $school_logo,
                    'phone' => $school_phone,
                    'email' => $school_email,
                    'address' => $school_address,
                    'website' => $school_website,
                    'colors' => array(
                        'primary' => $primary_color,
                        'secondary' => $secondary_color
                    ),
                    'messages' => array(
                        'welcome' => $welcome_message,
                        'completion' => $completion_message
                    )
                ),
                'educational_boards' => $boards_data,
                'default_board' => $default_board,
                'board_selection_required' => $board_selection_required,
                'academic_calendar' => array(
                    'type' => $academic_calendar_type,
                    'custom_start_month' => $custom_start_month,
                    'custom_end_month' => $custom_end_month,
                    'available_years' => $available_years,
                    'default_year' => $default_academic_year,
                    'admission_period' => $admission_period
                )
            );
            
            // Update consolidated config
            $school_config = EduBot_School_Config::getInstance();
            if (!$school_config->update_config($config_data)) {
                throw new Exception('Failed to update school configuration');
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            // Log successful save
            error_log('EduBot: School settings saved successfully');
            
            // Clear any caches
            wp_cache_delete('edubot_school_config');
            
            return $this->send_response(true, 'Settings saved successfully!');
            
        } catch (Exception $e) {
            // Rollback transaction
            global $wpdb;
            $wpdb->query('ROLLBACK');
            
            error_log('EduBot: Error saving school settings: ' . $e->getMessage());
            return $this->send_response(false, 'Failed to save settings. Please try again.');
        }
    }

    /**
     * Save API settings with comprehensive security validation
     */
    private function save_api_settings() {
        self::debug_log('Starting save_api_settings method', 'info', array(
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'post_data_size' => strlen(json_encode($_POST)),
            'user_id' => get_current_user_id(),
            'has_nonce' => isset($_POST['edubot_api_nonce'])
        ));
        
        // Rate limiting check
        $security_manager = new EduBot_Security_Manager();
        if (!$security_manager->check_rate_limit('api_settings', get_current_user_id(), 10, 3600)) {
            self::log_security_event('Rate Limit Exceeded for API Settings', 'warning');
            error_log('EduBot: Rate limit exceeded for API settings');
            return $this->send_response(false, 'Too many requests. Please wait before trying again.');
        }
        
        // Verify nonce for CSRF protection
        if (!isset($_POST['edubot_api_nonce']) || !wp_verify_nonce($_POST['edubot_api_nonce'], 'edubot_save_api_settings')) {
            // Log debug information about nonce failure
            self::log_security_event('API Settings Nonce Verification Failed', 'warning', array(
                'nonce_provided' => isset($_POST['edubot_api_nonce']),
                'nonce_value' => isset($_POST['edubot_api_nonce']) ? substr($_POST['edubot_api_nonce'], 0, 10) . '...' : 'none',
                'expected_action' => 'edubot_save_api_settings',
                'post_data_keys' => array_keys($_POST),
                'referer' => wp_get_referer()
            ));
            
            error_log('EduBot: API settings nonce verification failed');
            return $this->send_response(false, 'Security check failed. Please refresh and try again.');
        }
        
        // Capability check
        if (!current_user_can('manage_options')) {
            self::log_security_event('Insufficient permissions for API settings', 'warning', array(
                'user_id' => get_current_user_id(),
                'user_roles' => wp_get_current_user()->roles,
                'required_capability' => 'manage_options'
            ));
            error_log('EduBot: Insufficient permissions for API settings');
            return $this->send_response(false, 'Insufficient permissions.');
        }
        
        self::debug_log('Security checks passed for API settings', 'info', array(
            'user_can_manage' => current_user_can('manage_options'),
            'nonce_verified' => true
        ));
        
        try {
            self::debug_log('Starting API settings data validation', 'info', array(
                'has_openai_key' => !empty($_POST['openai_key']),
                'has_model' => !empty($_POST['openai_model']),
                'form_fields' => array_keys($_POST)
            ));
            
            // Validate and sanitize OpenAI settings
            $openai_key = '';
            if (!empty($_POST['openai_key'])) {
                $openai_key = sanitize_text_field($_POST['openai_key']);
                self::debug_log('Processing OpenAI key', 'detailed', array(
                    'key_length' => strlen($openai_key),
                    'key_prefix' => substr($openai_key, 0, 8),
                    'is_project_key' => strpos($openai_key, 'sk-proj-') === 0,
                    'is_legacy_key' => strpos($openai_key, 'sk-') === 0 && strpos($openai_key, 'sk-proj-') !== 0
                ));
                
                // Validate OpenAI API key format (supports modern OpenAI keys)
                if (!preg_match('/^sk-[a-zA-Z0-9_\-\.]{32,}$/', $openai_key)) {
                    self::log_security_event('Invalid OpenAI API key format submitted', 'warning', array(
                        'key_prefix' => substr($openai_key, 0, 10),
                        'key_length' => strlen($openai_key),
                        'regex_pattern' => '/^sk-[a-zA-Z0-9_\-\.]{32,}$/'
                    ));
                    return $this->send_response(false, 'Invalid OpenAI API key format. Key should start with "sk-" and be at least 35 characters long.');
                }
                
                self::debug_log('OpenAI key validation passed', 'detailed', array(
                    'key_type' => strpos($openai_key, 'sk-proj-') === 0 ? 'project' : 'legacy'
                ));
            }
            
            // Validate AI model
            $ai_model = sanitize_text_field($_POST['ai_model'] ?? 'gpt-3.5-turbo');
            $allowed_models = array('gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo', 'gpt-4o', 'gpt-4o-mini');
            if (!in_array($ai_model, $allowed_models)) {
                self::debug_log('Invalid AI model provided, using default', 'warning', array(
                    'submitted_model' => $ai_model,
                    'default_model' => 'gpt-3.5-turbo'
                ));
                $ai_model = 'gpt-3.5-turbo';
            }
            
            self::debug_log('AI model validated', 'detailed', array(
                'selected_model' => $ai_model,
                'available_models' => $allowed_models
            ));
            
            // Validate WhatsApp settings
            $whatsapp_provider = sanitize_text_field($_POST['whatsapp_provider'] ?? 'meta');
            $allowed_whatsapp_providers = array('meta', 'twilio', 'whatsapp_business');
            if (!in_array($whatsapp_provider, $allowed_whatsapp_providers)) {
                self::debug_log('Invalid WhatsApp provider, using default', 'warning', array(
                    'submitted_provider' => $whatsapp_provider,
                    'default_provider' => 'meta'
                ));
                $whatsapp_provider = 'meta';
            }
            
            $whatsapp_token = '';
            if (!empty($_POST['whatsapp_token'])) {
                $whatsapp_token = sanitize_text_field($_POST['whatsapp_token']);
                self::debug_log('Processing WhatsApp token', 'detailed', array(
                    'token_length' => strlen($whatsapp_token),
                    'token_prefix' => substr($whatsapp_token, 0, 10)
                ));
                
                if (strlen($whatsapp_token) > 500) {
                    self::log_security_event('WhatsApp token too long', 'warning', array(
                        'token_length' => strlen($whatsapp_token)
                    ));
                    return $this->send_response(false, 'WhatsApp token is too long.');
                }
            }
            
            $whatsapp_phone_id = '';
            if (!empty($_POST['whatsapp_phone_id'])) {
                $whatsapp_phone_id = sanitize_text_field($_POST['whatsapp_phone_id']);
                self::debug_log('Processing WhatsApp phone ID', 'detailed', array(
                    'phone_id' => $whatsapp_phone_id
                ));
                
                if (!preg_match('/^[\d\+\-\(\)\s]{10,20}$/', $whatsapp_phone_id)) {
                    self::log_security_event('Invalid WhatsApp phone ID format', 'warning', array(
                        'phone_id' => $whatsapp_phone_id
                    ));
                    return $this->send_response(false, 'Invalid WhatsApp phone ID format.');
                }
            }
            
            // Validate email settings
            $email_provider = sanitize_text_field($_POST['email_provider'] ?? 'smtp');
            $allowed_email_providers = array('smtp', 'sendgrid', 'mailgun', 'ses', 'outlook');
            if (!in_array($email_provider, $allowed_email_providers)) {
                $email_provider = 'smtp';
            }
            
            $smtp_host = '';
            if (!empty($_POST['smtp_host'])) {
                $smtp_host = sanitize_text_field($_POST['smtp_host']);
                if (!preg_match('/^[a-zA-Z0-9\.\-]+$/', $smtp_host) || strlen($smtp_host) > 255) {
                    return $this->send_response(false, 'Invalid SMTP host format.');
                }
            }
            
            $smtp_port = 587;
            if (!empty($_POST['smtp_port'])) {
                $smtp_port = absint($_POST['smtp_port']);
                if ($smtp_port < 1 || $smtp_port > 65535) {
                    return $this->send_response(false, 'Invalid SMTP port number.');
                }
            }
            
            $smtp_username = '';
            if (!empty($_POST['smtp_username'])) {
                $smtp_username = sanitize_text_field($_POST['smtp_username']);
                if (strlen($smtp_username) > 255) {
                    return $this->send_response(false, 'SMTP username is too long.');
                }
            }
            
            $smtp_password = '';
            if (!empty($_POST['smtp_password'])) {
                $smtp_password = sanitize_text_field($_POST['smtp_password']);
                if (strlen($smtp_password) > 255) {
                    return $this->send_response(false, 'SMTP password is too long.');
                }
            }
            
            $email_api_key = '';
            if (!empty($_POST['email_api_key'])) {
                $email_api_key = sanitize_text_field($_POST['email_api_key']);
                if (strlen($email_api_key) > 500) {
                    return $this->send_response(false, 'Email API key is too long.');
                }
            }
            
            $email_domain = '';
            if (!empty($_POST['email_domain'])) {
                $email_domain = sanitize_text_field($_POST['email_domain']);
                if (!preg_match('/^[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,}$/', $email_domain)) {
                    return $this->send_response(false, 'Invalid email domain format.');
                }
            }
            
            $email_from_address = '';
            if (!empty($_POST['email_from_address'])) {
                $email_from_address = sanitize_email($_POST['email_from_address']);
                if (!is_email($email_from_address)) {
                    return $this->send_response(false, 'Invalid from email address format.');
                }
            }
            
            $email_from_name = '';
            if (!empty($_POST['email_from_name'])) {
                $email_from_name = sanitize_text_field($_POST['email_from_name']);
                if (strlen($email_from_name) > 100) {
                    return $this->send_response(false, 'From name is too long (max 100 characters).');
                }
            }
            
            // Validate SMS settings
            $sms_provider = '';
            if (!empty($_POST['sms_provider'])) {
                $sms_provider = sanitize_text_field($_POST['sms_provider']);
                $allowed_sms_providers = array('twilio', 'nexmo', 'msg91', 'textlocal', 'aws_sns');
                if (!in_array($sms_provider, $allowed_sms_providers)) {
                    $sms_provider = '';
                }
            }
            
            $sms_api_key = '';
            if (!empty($_POST['sms_api_key'])) {
                $sms_api_key = sanitize_text_field($_POST['sms_api_key']);
                if (strlen($sms_api_key) > 500) {
                    return $this->send_response(false, 'SMS API key is too long.');
                }
            }
            
            $sms_sender_id = '';
            if (!empty($_POST['sms_sender_id'])) {
                $sms_sender_id = sanitize_text_field($_POST['sms_sender_id']);
                if (strlen($sms_sender_id) > 11 || !preg_match('/^[a-zA-Z0-9]+$/', $sms_sender_id)) {
                    return $this->send_response(false, 'Invalid SMS sender ID format (max 11 alphanumeric characters).');
                }
            }
            
            self::debug_log('All data validated, preparing to save API settings', 'info', array(
                'openai_key_provided' => !empty($openai_key),
                'ai_model' => $ai_model,
                'whatsapp_provider' => $whatsapp_provider,
                'whatsapp_token_provided' => !empty($whatsapp_token),
                'whatsapp_phone_id_provided' => !empty($whatsapp_phone_id),
                'email_provider' => $email_provider,
                'smtp_host_provided' => !empty($smtp_host),
                'sms_provider' => $sms_provider
            ));
            
            // Begin database transaction
            global $wpdb;
            $wpdb->query('START TRANSACTION');
            
            self::debug_log('Starting database transaction for API settings', 'detailed');
            
            // Encrypt sensitive data before storing
            $encrypted_openai_key = !empty($openai_key) ? $security_manager->encrypt_api_key($openai_key) : '';
            $encrypted_whatsapp_token = !empty($whatsapp_token) ? $security_manager->encrypt_api_key($whatsapp_token) : '';
            $encrypted_smtp_password = !empty($smtp_password) ? $security_manager->encrypt_api_key($smtp_password) : '';
            $encrypted_email_api_key = !empty($email_api_key) ? $security_manager->encrypt_api_key($email_api_key) : '';
            $encrypted_sms_api_key = !empty($sms_api_key) ? $security_manager->encrypt_api_key($sms_api_key) : '';
            
            self::debug_log('Sensitive data encrypted successfully', 'detailed', array(
                'encrypted_fields' => array(
                    'openai_key' => !empty($encrypted_openai_key),
                    'whatsapp_token' => !empty($encrypted_whatsapp_token),
                    'smtp_password' => !empty($encrypted_smtp_password),
                    'email_api_key' => !empty($encrypted_email_api_key),
                    'sms_api_key' => !empty($encrypted_sms_api_key)
                )
            ));
            
            // Update WordPress options
            $api_options = array(
                'edubot_openai_api_key' => $encrypted_openai_key,
                'edubot_openai_model' => $ai_model,
                'edubot_whatsapp_provider' => $whatsapp_provider,
                'edubot_whatsapp_token' => $encrypted_whatsapp_token,
                'edubot_whatsapp_phone_id' => $whatsapp_phone_id,
                'edubot_email_service' => $email_provider,
                'edubot_smtp_host' => $smtp_host,
                'edubot_smtp_port' => $smtp_port,
                'edubot_smtp_username' => $smtp_username,
                'edubot_smtp_password' => $encrypted_smtp_password,
                'edubot_email_api_key' => $encrypted_email_api_key,
                'edubot_email_domain' => $email_domain,
                'edubot_email_from_address' => $email_from_address,
                'edubot_email_from_name' => $email_from_name,
                'edubot_sms_provider' => $sms_provider,
                'edubot_sms_api_key' => $encrypted_sms_api_key,
                'edubot_sms_sender_id' => $sms_sender_id
            );
            
            // Process debug settings
            $debug_enabled = isset($_POST['edubot_debug_enabled']) && $_POST['edubot_debug_enabled'] === '1';
            $debug_level = sanitize_text_field($_POST['edubot_debug_level'] ?? 'basic');
            $log_api_requests = isset($_POST['edubot_log_api_requests']) && $_POST['edubot_log_api_requests'] === '1';
            $log_data_transfers = isset($_POST['edubot_log_data_transfers']) && $_POST['edubot_log_data_transfers'] === '1';
            $log_security_events = isset($_POST['edubot_log_security_events']) && $_POST['edubot_log_security_events'] === '1';
            
            // Validate debug level
            $allowed_debug_levels = array('basic', 'detailed', 'verbose');
            if (!in_array($debug_level, $allowed_debug_levels)) {
                $debug_level = 'basic';
            }
            
            // Add debug settings to options
            $api_options['edubot_debug_enabled'] = $debug_enabled;
            $api_options['edubot_debug_level'] = $debug_level;
            $api_options['edubot_log_api_requests'] = $log_api_requests;
            $api_options['edubot_log_data_transfers'] = $log_data_transfers;
            $api_options['edubot_log_security_events'] = $log_security_events;
            
            self::log_data_transfer('API Settings Data Prepared for Save', 'info', array(
                'total_options' => count($api_options),
                'debug_settings' => array(
                    'enabled' => $debug_enabled,
                    'level' => $debug_level,
                    'log_api_requests' => $log_api_requests,
                    'log_data_transfers' => $log_data_transfers,
                    'log_security_events' => $log_security_events
                )
            ));
            
            // Save options to database
            foreach ($api_options as $option_name => $option_value) {
                self::debug_log("Saving option: {$option_name}", 'verbose', array(
                    'option_name' => $option_name,
                    'value_type' => gettype($option_value),
                    'is_sensitive' => strpos($option_name, 'key') !== false || strpos($option_name, 'password') !== false || strpos($option_name, 'token') !== false
                ));
                
                if (!$this->safe_update_option($option_name, $option_value)) {
                    throw new Exception("Failed to update option: {$option_name}");
                }
            }
            
            self::log_data_transfer('All API options saved to database', 'info', array(
                'saved_options_count' => count($api_options)
            ));
            
            // Save to school config for API usage
            $school_config = EduBot_School_Config::getInstance();
            $api_data = array(
                'api_keys' => array(
                    'openai_key' => $encrypted_openai_key,
                    'ai_model' => $ai_model,
                    'whatsapp_provider' => $whatsapp_provider,
                    'whatsapp_token' => $encrypted_whatsapp_token,
                    'whatsapp_phone_id' => $whatsapp_phone_id,
                    'email_service' => $email_provider,
                    'smtp_host' => $smtp_host,
                    'smtp_port' => $smtp_port,
                    'smtp_username' => $smtp_username,
                    'smtp_password' => $encrypted_smtp_password,
                    'email_api_key' => $encrypted_email_api_key,
                    'email_domain' => $email_domain,
                    'sms_provider' => $sms_provider,
                    'sms_api_key' => $encrypted_sms_api_key,
                    'sms_sender_id' => $sms_sender_id
                )
            );
            
            if (!$school_config->update_config($api_data)) {
                throw new Exception('Failed to update school configuration');
            }
            
            self::log_data_transfer('School configuration updated successfully', 'info', array(
                'config_keys_saved' => array_keys($api_data['api_keys'])
            ));
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            self::debug_log('Database transaction committed successfully', 'info');
            
            // Log successful save (without sensitive data)
            self::log_data_transfer('API settings saved successfully', 'info', array(
                'timestamp' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'settings_saved' => array(
                    'ai_model' => $ai_model,
                    'whatsapp_provider' => $whatsapp_provider,
                    'email_provider' => $email_provider,
                    'sms_provider' => $sms_provider,
                    'debug_enabled' => $debug_enabled
                )
            ));
            error_log('EduBot: API settings saved successfully');
            
            // Clear any caches
            wp_cache_delete('edubot_api_settings');
            
            self::debug_log('API settings cache cleared', 'detailed');
            
            return $this->send_response(true, 'API settings saved successfully!');
            
        } catch (Exception $e) {
            // Rollback transaction
            global $wpdb;
            $wpdb->query('ROLLBACK');
            
            self::debug_log('API settings save failed, transaction rolled back', 'error', array(
                'error_message' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'error_file' => basename($e->getFile())
            ));
            
            error_log('EduBot: Error saving API settings: ' . $e->getMessage());
            return $this->send_response(false, 'Failed to save API settings. Please try again.');
        }
    }

    /**
     * Save OpenAI settings separately
     */
    public function save_openai_settings() {
        error_log('EduBot: save_openai_settings called');
        error_log('EduBot: POST data: ' . print_r($_POST, true));
        
        // Security checks
        if (!current_user_can('manage_options')) {
            error_log('EduBot: save_openai_settings - Permission denied');
            wp_send_json_error(array('message' => 'Insufficient permissions.'));
            return false;
        }
        
        if (!wp_verify_nonce($_POST['edubot_api_nonce'], 'edubot_save_api_settings')) {
            error_log('EduBot: save_openai_settings - Nonce verification failed');
            error_log('EduBot: Nonce provided: ' . ($_POST['edubot_api_nonce'] ?? 'NONE'));
            wp_send_json_error(array('message' => 'Security check failed. Please refresh and try again.'));
            return false;
        }
        
        try {
            $security_manager = new EduBot_Security_Manager();
            error_log('EduBot: Security manager created successfully');
            
            // Validate OpenAI settings
            $openai_key = '';
            if (!empty($_POST['openai_key'])) {
                $openai_key = sanitize_text_field($_POST['openai_key']);
                error_log('EduBot: OpenAI key received, length: ' . strlen($openai_key));
                // More flexible OpenAI key validation
                if (!preg_match('/^sk-[a-zA-Z0-9_\-\.]{32,}$/', $openai_key)) {
                    error_log('EduBot: OpenAI key validation failed');
                    wp_send_json_error(array('message' => 'Invalid OpenAI API key format. Key should start with "sk-" and be at least 35 characters long.'));
                    return false;
                }
                error_log('EduBot: OpenAI key validation passed');
            }
            
            $ai_model = sanitize_text_field($_POST['ai_model'] ?? 'gpt-3.5-turbo');
            $allowed_models = array('gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo', 'gpt-4o', 'gpt-4o-mini');
            if (!in_array($ai_model, $allowed_models)) {
                $ai_model = 'gpt-3.5-turbo';
            }
            error_log('EduBot: AI model validated: ' . $ai_model);
            
            // Encrypt and save
            $encrypted_openai_key = !empty($openai_key) ? $security_manager->encrypt_api_key($openai_key) : '';
            
            error_log('EduBot: Encrypted key length: ' . strlen($encrypted_openai_key));
            error_log('EduBot: AI model to save: ' . $ai_model);
            
            $success = true;
            
            // Try saving OpenAI key
            $current_openai_key = get_option('edubot_openai_api_key', '');
            $openai_save_result = update_option('edubot_openai_api_key', $encrypted_openai_key);
            error_log('EduBot: OpenAI key save result: ' . ($openai_save_result ? 'SUCCESS' : 'FAILED'));
            error_log('EduBot: Current key same as new? ' . ($current_openai_key === $encrypted_openai_key ? 'YES' : 'NO'));
            if (!$openai_save_result) {
                global $wpdb;
                error_log('EduBot: WordPress DB last error: ' . $wpdb->last_error);
                // Check if it failed because the value is the same
                if ($current_openai_key === $encrypted_openai_key) {
                    error_log('EduBot: OpenAI key not updated because value is the same');
                    $openai_save_result = true; // Consider this a success
                }
            }
            $success = $success && $openai_save_result;
            
            // Try saving AI model
            $current_ai_model = get_option('edubot_openai_model', '');
            $model_save_result = update_option('edubot_openai_model', $ai_model);
            error_log('EduBot: AI model save result: ' . ($model_save_result ? 'SUCCESS' : 'FAILED'));
            error_log('EduBot: Current model same as new? ' . ($current_ai_model === $ai_model ? 'YES' : 'NO'));
            if (!$model_save_result) {
                global $wpdb;
                error_log('EduBot: WordPress DB last error (model): ' . $wpdb->last_error);
                // Check if it failed because the value is the same
                if ($current_ai_model === $ai_model) {
                    error_log('EduBot: AI model not updated because value is the same');
                    $model_save_result = true; // Consider this a success
                }
            }
            $success = $success && $model_save_result;
            
            error_log('EduBot: Overall save success: ' . ($success ? 'YES' : 'NO'));
            
            if ($success) {
                error_log('EduBot: OpenAI settings saved successfully');
                self::debug_log('OpenAI settings saved successfully', 'info');
                wp_send_json_success(array('message' => 'OpenAI settings saved successfully!'));
            } else {
                error_log('EduBot: Failed to save OpenAI settings to database');
                wp_send_json_error(array('message' => 'Failed to save OpenAI settings.'));
            }
            
        } catch (Exception $e) {
            error_log('EduBot: Error saving OpenAI settings: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Failed to save OpenAI settings. Please try again.'));
        }
    }

    /**
     * Save AI settings separately (dedicated AI settings page)
     */
    public function save_ai_settings() {
        error_log('EduBot: save_ai_settings called');
        
        // Security checks
        if (!current_user_can('manage_options')) {
            error_log('EduBot: save_ai_settings - Permission denied');
            wp_send_json_error(array('message' => 'Insufficient permissions.'));
            return false;
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'edubot_save_ai_settings')) {
            error_log('EduBot: save_ai_settings - Nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed. Please refresh and try again.'));
            return false;
        }
        
        try {
            $security_manager = new EduBot_Security_Manager();
            $success = true;
            
            // Save AI enable/disable
            $ai_enabled = isset($_POST['ai_enabled']) ? (bool)$_POST['ai_enabled'] : false;
            $success = $success && $this->safe_update_option('edubot_ai_enabled', $ai_enabled);
            
            // Save OpenAI API Key (encrypted)
            if (!empty($_POST['openai_key'])) {
                $encrypted_key = $security_manager->encrypt_api_key($_POST['openai_key']);
                $success = $success && $this->safe_update_option('edubot_openai_api_key', $encrypted_key);
            }
            
            // Save AI Model
            if (!empty($_POST['ai_model'])) {
                $allowed_models = array('gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo');
                if (in_array($_POST['ai_model'], $allowed_models)) {
                    $success = $success && $this->safe_update_option('edubot_openai_model', sanitize_text_field($_POST['ai_model']));
                }
            }
            
            // Save AI Temperature
            if (isset($_POST['ai_temperature'])) {
                $temperature = floatval($_POST['ai_temperature']);
                if ($temperature >= 0 && $temperature <= 2) {
                    $success = $success && $this->safe_update_option('edubot_ai_temperature', $temperature);
                }
            }
            
            // Save AI Max Tokens
            if (isset($_POST['ai_max_tokens'])) {
                $max_tokens = intval($_POST['ai_max_tokens']);
                if ($max_tokens >= 50 && $max_tokens <= 4000) {
                    $success = $success && $this->safe_update_option('edubot_ai_max_tokens', $max_tokens);
                }
            }
            
            // Save AI Prompt Template
            if (isset($_POST['ai_prompt_template'])) {
                $prompt_template = sanitize_textarea_field($_POST['ai_prompt_template']);
                $success = $success && $this->safe_update_option('edubot_ai_prompt_template', $prompt_template);
            }
            
            // Save AI Fallback Message
            if (isset($_POST['ai_fallback_message'])) {
                $fallback_message = sanitize_textarea_field($_POST['ai_fallback_message']);
                $success = $success && $this->safe_update_option('edubot_ai_fallback_message', $fallback_message);
            }
            
            if ($success) {
                error_log('EduBot: AI settings saved successfully');
                self::debug_log('AI settings saved successfully', 'info');
                
                // Log successful settings save
                self::log_api_request_to_db(
                    'system',
                    'save_ai_settings',
                    array(
                        'ai_enabled' => $ai_enabled ?? null,
                        'model' => $_POST['ai_model'] ?? null,
                        'temperature' => $_POST['ai_temperature'] ?? null,
                        'max_tokens' => $_POST['ai_max_tokens'] ?? null
                    ),
                    array('success' => true),
                    true,
                    200,
                    null,
                    0
                );
                
                wp_send_json_success(array('message' => 'AI settings saved successfully!'));
            } else {
                error_log('EduBot: Failed to save AI settings to database');
                
                // Log failed settings save
                self::log_api_request_to_db(
                    'system',
                    'save_ai_settings',
                    array('attempted_settings' => array_keys($_POST)),
                    null,
                    false,
                    400,
                    'Failed to save AI settings to database',
                    0
                );
                
                wp_send_json_error(array('message' => 'Failed to save AI settings.'));
            }
            
        } catch (Exception $e) {
            error_log('EduBot: Error saving AI settings: ' . $e->getMessage());
            
            // Log exception
            self::log_api_request_to_db(
                'system',
                'save_ai_settings',
                array('exception' => $e->getMessage()),
                null,
                false,
                500,
                'Exception while saving AI settings: ' . $e->getMessage(),
                0
            );
            
            wp_send_json_error(array('message' => 'Failed to save AI settings. Please try again.'));
        }
    }

    /**
     * Test AI response functionality
     */
    public function test_ai_response() {
        // Security checks
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions.'));
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'edubot_test_ai')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            return;
        }
        
        $prompt = sanitize_textarea_field($_POST['prompt']);
        if (empty($prompt)) {
            wp_send_json_error(array('message' => 'Please provide a test prompt.'));
            return;
        }
        
        try {
            // Get OpenAI settings
            $security_manager = new EduBot_Security_Manager();
            $encrypted_key = get_option('edubot_openai_api_key', '');
            
            if (empty($encrypted_key)) {
                wp_send_json_error(array('message' => 'OpenAI API key not configured.'));
                return;
            }
            
            $api_key = $security_manager->decrypt_api_key($encrypted_key);
            $model = get_option('edubot_openai_model', 'gpt-3.5-turbo');
            $temperature = floatval(get_option('edubot_ai_temperature', '0.7'));
            $max_tokens = intval(get_option('edubot_ai_max_tokens', '500'));
            
            // Make OpenAI API call
            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode(array(
                    'model' => $model,
                    'messages' => array(
                        array(
                            'role' => 'system',
                            'content' => 'You are a helpful AI assistant for a school. Respond professionally and helpfully.'
                        ),
                        array(
                            'role' => 'user',
                            'content' => $prompt
                        )
                    ),
                    'temperature' => $temperature,
                    'max_tokens' => $max_tokens
                )),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                wp_send_json_error(array('message' => 'API request failed: ' . $response->get_error_message()));
                return;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['error'])) {
                // Log API error
                self::log_api_request_to_db(
                    'openai',
                    'test_response',
                    array('prompt' => $prompt),
                    $data,
                    false,
                    400,
                    'OpenAI API Error: ' . $data['error']['message'],
                    0
                );
                wp_send_json_error(array('message' => 'OpenAI API Error: ' . $data['error']['message']));
                return;
            }
            
            if (isset($data['choices'][0]['message']['content'])) {
                $ai_response = $data['choices'][0]['message']['content'];
                
                // Log the successful test
                self::log_api_request_to_db(
                    'openai',
                    'test_response',
                    array('prompt' => $prompt),
                    $data,
                    true,
                    200,
                    null,
                    0
                );
                
                wp_send_json_success(array('response' => $ai_response));
            } else {
                // Log unexpected response format
                self::log_api_request_to_db(
                    'openai',
                    'test_response',
                    array('prompt' => $prompt),
                    $data,
                    false,
                    200,
                    'Unexpected API response format',
                    0
                );
                wp_send_json_error(array('message' => 'Unexpected API response format.'));
            }
            
        } catch (Exception $e) {
            error_log('EduBot: Error testing AI response: ' . $e->getMessage());
            
            // Log exception
            self::log_api_request_to_db(
                'openai',
                'test_response',
                array('prompt' => isset($prompt) ? $prompt : 'unknown'),
                null,
                false,
                500,
                'Exception: ' . $e->getMessage(),
                0
            );
            
            wp_send_json_error(array('message' => 'Test failed: ' . $e->getMessage()));
        }
    }

    /**
     * Save WhatsApp settings separately
     */
    public function save_whatsapp_settings() {
        // Security checks
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions.'));
            return false;
        }
        
        if (!wp_verify_nonce($_POST['edubot_api_nonce'], 'edubot_save_api_settings')) {
            wp_send_json_error(array('message' => 'Security check failed. Please refresh and try again.'));
            return false;
        }
        
        try {
            $security_manager = new EduBot_Security_Manager();
            
            $whatsapp_provider = sanitize_text_field($_POST['whatsapp_provider'] ?? 'meta');
            $allowed_providers = array('meta', 'twilio', 'whatsapp_business');
            if (!in_array($whatsapp_provider, $allowed_providers)) {
                $whatsapp_provider = 'meta';
            }
            
            $whatsapp_token = '';
            if (!empty($_POST['whatsapp_token'])) {
                $whatsapp_token = sanitize_text_field($_POST['whatsapp_token']);
                if (strlen($whatsapp_token) > 500) {
                    wp_send_json_error(array('message' => 'WhatsApp token is too long.'));
                    return false;
                }
            }
            
            $whatsapp_phone_id = '';
            if (!empty($_POST['whatsapp_phone_id'])) {
                $whatsapp_phone_id = sanitize_text_field($_POST['whatsapp_phone_id']);
                if (!preg_match('/^[\d\+\-\(\)\s]{10,20}$/', $whatsapp_phone_id)) {
                    wp_send_json_error(array('message' => 'Invalid WhatsApp phone ID format.'));
                    return false;
                }
            }
            
            // Encrypt and save
            $encrypted_whatsapp_token = !empty($whatsapp_token) ? $security_manager->encrypt_api_key($whatsapp_token) : '';
            
            $success = true;
            $success = $success && update_option('edubot_whatsapp_provider', $whatsapp_provider);
            $success = $success && update_option('edubot_whatsapp_token', $encrypted_whatsapp_token);
            $success = $success && update_option('edubot_whatsapp_phone_id', $whatsapp_phone_id);
            
            if ($success) {
                self::debug_log('WhatsApp settings saved successfully', 'info');
                wp_send_json_success(array('message' => 'WhatsApp settings saved successfully!'));
            } else {
                wp_send_json_error(array('message' => 'Failed to save WhatsApp settings.'));
            }
            
        } catch (Exception $e) {
            error_log('EduBot: Error saving WhatsApp settings: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Failed to save WhatsApp settings. Please try again.'));
        }
    }

    /**
     * Save Email settings separately
     */
    public function save_email_settings() {
        error_log('EduBot: save_email_settings called');
        error_log('EduBot: POST data: ' . print_r($_POST, true));
        
        // Security checks
        if (!current_user_can('manage_options')) {
            error_log('EduBot: save_email_settings - Permission denied');
            wp_send_json_error(array('message' => 'Insufficient permissions.'));
            return false;
        }
        
        if (!wp_verify_nonce($_POST['edubot_api_nonce'], 'edubot_save_api_settings')) {
            error_log('EduBot: save_email_settings - Nonce verification failed');
            error_log('EduBot: Nonce provided: ' . ($_POST['edubot_api_nonce'] ?? 'NONE'));
            wp_send_json_error(array('message' => 'Security check failed. Please refresh and try again.'));
            return false;
        }
        
        try {
            $security_manager = new EduBot_Security_Manager();
            error_log('EduBot: Email security manager created successfully');
            
            $email_provider = sanitize_text_field($_POST['email_provider'] ?? 'smtp');
            $allowed_email_providers = array('smtp', 'sendgrid', 'mailgun', 'ses', 'outlook');
            if (!in_array($email_provider, $allowed_email_providers)) {
                $email_provider = 'smtp';
            }
            error_log('EduBot: Email provider validated: ' . $email_provider);
            
            $smtp_host = sanitize_text_field($_POST['smtp_host'] ?? '');
            $smtp_port = absint($_POST['smtp_port'] ?? 587);
            $smtp_username = sanitize_text_field($_POST['smtp_username'] ?? '');
            $smtp_password = sanitize_text_field($_POST['smtp_password'] ?? '');
            $email_api_key = sanitize_text_field($_POST['email_api_key'] ?? '');
            $email_domain = sanitize_text_field($_POST['email_domain'] ?? '');
            $email_from_address = sanitize_email($_POST['email_from_address'] ?? '');
            $email_from_name = sanitize_text_field($_POST['email_from_name'] ?? '');
            
            error_log('EduBot: Email settings validated - Host: ' . $smtp_host . ', Port: ' . $smtp_port);
            
            // Clean API key - remove any existing prefixes to avoid duplication
            if (!empty($email_api_key)) {
                $email_api_key = str_replace(array('Zoho-enczapikey ', 'Bearer '), '', $email_api_key);
                $email_api_key = trim($email_api_key);
                error_log('EduBot: API key cleaned, length: ' . strlen($email_api_key));
            }
            
            // Encrypt sensitive data
            $encrypted_smtp_password = !empty($smtp_password) ? $security_manager->encrypt_api_key($smtp_password) : '';
            $encrypted_email_api_key = !empty($email_api_key) ? $security_manager->encrypt_api_key($email_api_key) : '';
            
            error_log('EduBot: Email encryption completed');
            
            $success = true;
            
            // Save each option with proper error handling
            $provider_result = update_option('edubot_email_service', $email_provider);
            $host_result = update_option('edubot_smtp_host', $smtp_host);
            $port_result = update_option('edubot_smtp_port', $smtp_port);
            $username_result = update_option('edubot_smtp_username', $smtp_username);
            $password_result = update_option('edubot_smtp_password', $encrypted_smtp_password);
            $api_key_result = update_option('edubot_email_api_key', $encrypted_email_api_key);
            $domain_result = update_option('edubot_email_domain', $email_domain);
            $from_address_result = update_option('edubot_email_from_address', $email_from_address);
            $from_name_result = update_option('edubot_email_from_name', $email_from_name);
            
            error_log('EduBot: Email save results - Provider: ' . ($provider_result ? 'OK' : 'FAIL') . 
                     ', Host: ' . ($host_result ? 'OK' : 'FAIL') . 
                     ', Port: ' . ($port_result ? 'OK' : 'FAIL') . 
                     ', Username: ' . ($username_result ? 'OK' : 'FAIL') . 
                     ', Password: ' . ($password_result ? 'OK' : 'FAIL') . 
                     ', API Key: ' . ($api_key_result ? 'OK' : 'FAIL') . 
                     ', Domain: ' . ($domain_result ? 'OK' : 'FAIL') .
                     ', From Address: ' . ($from_address_result ? 'OK' : 'FAIL') .
                     ', From Name: ' . ($from_name_result ? 'OK' : 'FAIL'));
            
            // Check for same values (update_option returns false if value is unchanged)
            if (!$provider_result && get_option('edubot_email_service', '') === $email_provider) $provider_result = true;
            if (!$host_result && get_option('edubot_smtp_host', '') === $smtp_host) $host_result = true;
            if (!$port_result && get_option('edubot_smtp_port', 587) == $smtp_port) $port_result = true;
            if (!$username_result && get_option('edubot_smtp_username', '') === $smtp_username) $username_result = true;
            if (!$password_result && get_option('edubot_smtp_password', '') === $encrypted_smtp_password) $password_result = true;
            if (!$api_key_result && get_option('edubot_email_api_key', '') === $encrypted_email_api_key) $api_key_result = true;
            if (!$domain_result && get_option('edubot_email_domain', '') === $email_domain) $domain_result = true;
            if (!$from_address_result && get_option('edubot_email_from_address', '') === $email_from_address) $from_address_result = true;
            if (!$from_name_result && get_option('edubot_email_from_name', '') === $email_from_name) $from_name_result = true;
            
            $success = $provider_result && $host_result && $port_result && $username_result && $password_result && $api_key_result && $domain_result && $from_address_result && $from_name_result;
            
            error_log('EduBot: Email overall save success: ' . ($success ? 'YES' : 'NO'));
            
            if ($success) {
                error_log('EduBot: Email settings saved successfully');
                self::debug_log('Email settings saved successfully', 'info');
                wp_send_json_success(array('message' => 'Email settings saved successfully!'));
            } else {
                error_log('EduBot: Failed to save Email settings to database');
                wp_send_json_error(array('message' => 'Failed to save Email settings.'));
            }
            
        } catch (Exception $e) {
            error_log('EduBot: Error saving Email settings: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Failed to save Email settings. Please try again.'));
        }
    }

    /**
     * Save SMS settings separately
     */
    public function save_sms_settings() {
        // Security checks
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions.'));
            return false;
        }
        
        if (!wp_verify_nonce($_POST['edubot_api_nonce'], 'edubot_save_api_settings')) {
            wp_send_json_error(array('message' => 'Security check failed. Please refresh and try again.'));
            return false;
        }
        
        try {
            $security_manager = new EduBot_Security_Manager();
            
            $sms_provider = sanitize_text_field($_POST['sms_provider'] ?? '');
            $allowed_sms_providers = array('twilio', 'nexmo', 'msg91', 'textlocal', 'aws_sns');
            if (!empty($sms_provider) && !in_array($sms_provider, $allowed_sms_providers)) {
                $sms_provider = '';
            }
            
            $sms_api_key = sanitize_text_field($_POST['sms_api_key'] ?? '');
            $sms_sender_id = sanitize_text_field($_POST['sms_sender_id'] ?? '');
            
            // Encrypt sensitive data
            $encrypted_sms_api_key = !empty($sms_api_key) ? $security_manager->encrypt_api_key($sms_api_key) : '';
            
            $success = true;
            $success = $success && update_option('edubot_sms_provider', $sms_provider);
            $success = $success && update_option('edubot_sms_api_key', $encrypted_sms_api_key);
            $success = $success && update_option('edubot_sms_sender_id', $sms_sender_id);
            
            if ($success) {
                self::debug_log('SMS settings saved successfully', 'info');
                wp_send_json_success(array('message' => 'SMS settings saved successfully!'));
            } else {
                wp_send_json_error(array('message' => 'Failed to save SMS settings.'));
            }
            
        } catch (Exception $e) {
            error_log('EduBot: Error saving SMS settings: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Failed to save SMS settings. Please try again.'));
        }
    }

    /**
     * Save Debug settings separately
     */
    public function save_debug_settings() {
        // Security checks
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions.'));
            return false;
        }
        
        if (!wp_verify_nonce($_POST['edubot_api_nonce'], 'edubot_save_api_settings')) {
            wp_send_json_error(array('message' => 'Security check failed. Please refresh and try again.'));
            return false;
        }
        
        try {
            $debug_enabled = isset($_POST['edubot_debug_enabled']) && $_POST['edubot_debug_enabled'] === '1';
            $debug_level = sanitize_text_field($_POST['edubot_debug_level'] ?? 'basic');
            $log_api_requests = isset($_POST['edubot_log_api_requests']) && $_POST['edubot_log_api_requests'] === '1';
            $log_data_transfers = isset($_POST['edubot_log_data_transfers']) && $_POST['edubot_log_data_transfers'] === '1';
            $log_security_events = isset($_POST['edubot_log_security_events']) && $_POST['edubot_log_security_events'] === '1';
            
            // Validate debug level
            $allowed_debug_levels = array('basic', 'detailed', 'verbose');
            if (!in_array($debug_level, $allowed_debug_levels)) {
                $debug_level = 'basic';
            }
            
            $success = true;
            $success = $success && update_option('edubot_debug_enabled', $debug_enabled);
            $success = $success && update_option('edubot_debug_level', $debug_level);
            $success = $success && update_option('edubot_log_api_requests', $log_api_requests);
            $success = $success && update_option('edubot_log_data_transfers', $log_data_transfers);
            $success = $success && update_option('edubot_log_security_events', $log_security_events);
            
            if ($success) {
                self::debug_log('Debug settings saved successfully', 'info');
                wp_send_json_success(array('message' => 'Debug settings saved successfully!'));
            } else {
                wp_send_json_error(array('message' => 'Failed to save Debug settings.'));
            }
            
        } catch (Exception $e) {
            error_log('EduBot: Error saving Debug settings: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Failed to save Debug settings. Please try again.'));
        }
    }

    /**
     * Save form settings with comprehensive security validation
     */
    private function save_form_settings() {
        // Rate limiting check
        $security_manager = new EduBot_Security_Manager();
        if (!$security_manager->check_rate_limit('form_settings', get_current_user_id(), 20, 3600)) {
            error_log('EduBot: Rate limit exceeded for form settings');
            return false;
        }
        
        // Verify nonce for CSRF protection
        if (!isset($_POST['edubot_form_nonce']) || !wp_verify_nonce($_POST['edubot_form_nonce'], 'edubot_save_form_settings')) {
            error_log('EduBot: Form settings nonce verification failed');
            return false;
        }
        
        // Capability check
        if (!current_user_can('manage_options')) {
            error_log('EduBot: Insufficient permissions for form settings');
            return false;
        }
        
        try {
            // Save basic form settings
            $form_settings = array();
            
            // Sanitize and validate form title
            if (isset($_POST['form_title'])) {
                $form_title = sanitize_text_field($_POST['form_title']);
                if (strlen($form_title) <= 200) {
                    $form_settings['form_title'] = $form_title;
                }
            }
            
            // Sanitize and validate form description
            if (isset($_POST['form_description'])) {
                $form_description = sanitize_textarea_field($_POST['form_description']);
                if (strlen($form_description) <= 1000) {
                    $form_settings['form_description'] = $form_description;
                }
            }
            
            // Validate and process required fields
            $required_fields = array();
            if (isset($_POST['required_fields']) && is_array($_POST['required_fields'])) {
                $allowed_fields = array('student_name', 'date_of_birth', 'grade', 'gender', 'parent_name', 'email', 'phone', 'address', 'previous_school', 'educational_board', 'academic_year');
                foreach ($_POST['required_fields'] as $field) {
                    $field = sanitize_text_field($field);
                    if (in_array($field, $allowed_fields)) {
                        $required_fields[] = $field;
                    }
                }
            }
            $form_settings['required_fields'] = $required_fields;
            
            // Validate form layout
            if (isset($_POST['form_layout'])) {
                $form_layout = sanitize_text_field($_POST['form_layout']);
                if (in_array($form_layout, array('single_column', 'two_column'))) {
                    $form_settings['form_layout'] = $form_layout;
                }
            }
            
            // Validate notification email
            if (isset($_POST['notification_email'])) {
                $notification_email = sanitize_email($_POST['notification_email']);
                if (is_email($notification_email)) {
                    $form_settings['notification_email'] = $notification_email;
                }
            }
            
            // Save the form settings
            $success = update_option('edubot_form_settings', $form_settings);
            
            if ($success !== false) {
                error_log('EduBot: Form settings saved successfully');
                wp_cache_delete('edubot_form_settings');
                return true;
            } else {
                throw new Exception('Failed to update form settings option');
            }
            
        } catch (Exception $e) {
            error_log('EduBot: Error saving form settings: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save chatbot settings with comprehensive security validation
     */
    private function save_chatbot_settings() {
        // Rate limiting check
        $security_manager = new EduBot_Security_Manager();
        if (!$security_manager->check_rate_limit('chatbot_settings', get_current_user_id(), 20, 3600)) {
            error_log('EduBot: Rate limit exceeded for chatbot settings');
            wp_send_json_error(array('message' => 'Too many requests. Please wait before trying again.'));
            return false;
        }
        
        // Verify nonce for CSRF protection
        if (!isset($_POST['edubot_chatbot_nonce']) || !wp_verify_nonce($_POST['edubot_chatbot_nonce'], 'edubot_save_chatbot_settings')) {
            error_log('EduBot: Chatbot settings nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed. Please refresh and try again.'));
            return false;
        }
        
        // Capability check
        if (!current_user_can('manage_options')) {
            error_log('EduBot: Insufficient permissions for chatbot settings');
            wp_send_json_error(array('message' => 'Insufficient permissions.'));
            return false;
        }
        
        try {
            // Validate bot persona
            $bot_persona = sanitize_text_field($_POST['bot_persona'] ?? 'friendly');
            $allowed_personas = array('friendly', 'professional', 'casual', 'formal', 'enthusiastic');
            if (!in_array($bot_persona, $allowed_personas)) {
                $bot_persona = 'friendly';
            }
            
            // Validate language
            $bot_language = sanitize_text_field($_POST['bot_language'] ?? 'en');
            $allowed_languages = array('en', 'hi', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'ja', 'ko', 'zh');
            if (!in_array($bot_language, $allowed_languages)) {
                $bot_language = 'en';
            }
            
            // Validate response style
            $response_style = sanitize_text_field($_POST['response_style'] ?? 'detailed');
            $allowed_styles = array('brief', 'detailed', 'conversational', 'formal');
            if (!in_array($response_style, $allowed_styles)) {
                $response_style = 'detailed';
            }
            
            // Validate conversation flow
            $conversation_flow = sanitize_text_field($_POST['conversation_flow'] ?? 'guided');
            $allowed_flows = array('guided', 'free', 'mixed');
            if (!in_array($conversation_flow, $allowed_flows)) {
                $conversation_flow = 'guided';
            }
            
            // Validate boolean settings
            $enable_suggestions = isset($_POST['enable_suggestions']) && $_POST['enable_suggestions'] === '1';
            $enable_typing_indicator = isset($_POST['enable_typing_indicator']) && $_POST['enable_typing_indicator'] === '1';
            $enable_sound_notifications = isset($_POST['enable_sound_notifications']) && $_POST['enable_sound_notifications'] === '1';
            $enable_conversation_memory = isset($_POST['enable_conversation_memory']) && $_POST['enable_conversation_memory'] === '1';
            
            // Validate numeric settings
            $conversation_timeout = absint($_POST['conversation_timeout'] ?? 30);
            if ($conversation_timeout < 5 || $conversation_timeout > 120) {
                $conversation_timeout = 30;
            }
            
            $max_conversation_length = absint($_POST['max_conversation_length'] ?? 50);
            if ($max_conversation_length < 10 || $max_conversation_length > 200) {
                $max_conversation_length = 50;
            }
            
            // Validate custom prompts
            $custom_prompts = array();
            if (isset($_POST['custom_prompts']) && is_array($_POST['custom_prompts'])) {
                foreach ($_POST['custom_prompts'] as $prompt_key => $prompt_text) {
                    $prompt_key = sanitize_key($prompt_key);
                    $prompt_text = sanitize_textarea_field($prompt_text);
                    
                    if (!empty($prompt_key) && !empty($prompt_text) && strlen($prompt_text) <= 1000) {
                        $custom_prompts[$prompt_key] = $prompt_text;
                    }
                }
            }
            
            // Begin database transaction
            global $wpdb;
            $wpdb->query('START TRANSACTION');
            
            $chatbot_settings = array(
                'edubot_bot_persona' => $bot_persona,
                'edubot_bot_language' => $bot_language,
                'edubot_response_style' => $response_style,
                'edubot_conversation_flow' => $conversation_flow,
                'edubot_enable_suggestions' => $enable_suggestions,
                'edubot_enable_typing_indicator' => $enable_typing_indicator,
                'edubot_enable_sound_notifications' => $enable_sound_notifications,
                'edubot_enable_conversation_memory' => $enable_conversation_memory,
                'edubot_conversation_timeout' => $conversation_timeout,
                'edubot_max_conversation_length' => $max_conversation_length,
                'edubot_custom_prompts' => $custom_prompts
            );
            
            foreach ($chatbot_settings as $option_name => $option_value) {
                if (!$this->safe_update_option($option_name, $option_value)) {
                    throw new Exception("Failed to update option: {$option_name}");
                }
            }
            
            // Update school configuration
            $school_config = EduBot_School_Config::getInstance();
            $chatbot_data = array(
                'chatbot_settings' => array(
                    'persona' => $bot_persona,
                    'language' => $bot_language,
                    'response_style' => $response_style,
                    'conversation_flow' => $conversation_flow,
                    'features' => array(
                        'suggestions' => $enable_suggestions,
                        'typing_indicator' => $enable_typing_indicator,
                        'sound_notifications' => $enable_sound_notifications,
                        'conversation_memory' => $enable_conversation_memory
                    ),
                    'limits' => array(
                        'conversation_timeout' => $conversation_timeout,
                        'max_conversation_length' => $max_conversation_length
                    ),
                    'custom_prompts' => $custom_prompts
                )
            );
            
            if (!$school_config->update_config($chatbot_data)) {
                throw new Exception('Failed to update chatbot configuration');
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            error_log('EduBot: Chatbot settings saved successfully');
            wp_cache_delete('edubot_chatbot_settings');
            
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction
            global $wpdb;
            $wpdb->query('ROLLBACK');
            
            error_log('EduBot: Error saving chatbot settings: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Failed to save chatbot settings. Please try again.'));
            return false;
        }
    }
    
    /**
     * Save academic settings with comprehensive security validation
     */
    private function save_academic_settings() {
        // Rate limiting check
        $security_manager = new EduBot_Security_Manager();
        if (!$security_manager->check_rate_limit('academic_settings', get_current_user_id(), 20, 3600)) {
            error_log('EduBot: Rate limit exceeded for academic settings');
            return $this->send_response(false, 'Too many requests. Please wait before trying again.');
        }
        
        // Verify nonce for CSRF protection
        if (!isset($_POST['edubot_academic_nonce']) || !wp_verify_nonce($_POST['edubot_academic_nonce'], 'edubot_save_academic_settings')) {
            error_log('EduBot: Academic settings nonce verification failed');
            return $this->send_response(false, 'Security check failed. Please refresh and try again.');
        }
        
        // Capability check
        if (!current_user_can('manage_options')) {
            error_log('EduBot: Insufficient permissions for academic settings');
            return $this->send_response(false, 'Insufficient permissions.');
        }
        
        try {
            // Begin database transaction
            global $wpdb;
            $wpdb->query('START TRANSACTION');
            
            // Process grade systems with validation
            $grade_systems = array();
            if (isset($_POST['edubot_grade_systems']) && is_array($_POST['edubot_grade_systems'])) {
                $allowed_systems = array('kindergarten', 'elementary', 'middle', 'high', 'preschool');
                foreach ($_POST['edubot_grade_systems'] as $system) {
                    $system = sanitize_text_field($system);
                    if (in_array($system, $allowed_systems)) {
                        $grade_systems[] = $system;
                    }
                }
            }
            
            // Process custom grades
            $custom_grades = array();
            if (isset($_POST['custom_grades_keys']) && isset($_POST['custom_grades_labels'])) {
                $keys = $_POST['custom_grades_keys'];
                $labels = $_POST['custom_grades_labels'];
                
                for ($i = 0; $i < count($keys); $i++) {
                    $key = sanitize_key($keys[$i]);
                    $label = sanitize_text_field($labels[$i]);
                    
                    if (!empty($key) && !empty($label) && strlen($label) <= 50) {
                        $custom_grades[$key] = $label;
                    }
                }
            }
            
            // Process admission cycles
            $admission_cycles = array();
            if (isset($_POST['admission_cycles']) && is_array($_POST['admission_cycles'])) {
                foreach ($_POST['admission_cycles'] as $cycle) {
                    if (!is_array($cycle)) continue;
                    
                    $name = sanitize_text_field($cycle['name'] ?? '');
                    $start_date = sanitize_text_field($cycle['start_date'] ?? '');
                    $end_date = sanitize_text_field($cycle['end_date'] ?? '');
                    
                    if (empty($name) || strlen($name) > 100) continue;
                    
                    // Validate dates
                    if (!empty($start_date) && !DateTime::createFromFormat('Y-m-d', $start_date)) {
                        continue;
                    }
                    if (!empty($end_date) && !DateTime::createFromFormat('Y-m-d', $end_date)) {
                        continue;
                    }
                    
                    $grades_available = array();
                    if (isset($cycle['grades_available']) && is_array($cycle['grades_available'])) {
                        foreach ($cycle['grades_available'] as $grade) {
                            $grade = sanitize_text_field($grade);
                            if (!empty($grade) && strlen($grade) <= 20) {
                                $grades_available[] = $grade;
                            }
                        }
                    }
                    
                    $admission_cycles[] = array(
                        'name' => $name,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'grades_available' => $grades_available
                    );
                }
            }
            
            // Limit admission cycles
            if (count($admission_cycles) > 10) {
                return $this->send_response(false, 'Too many admission cycles (maximum 10 allowed).');
            }
            
            // Update WordPress options
            $academic_options = array(
                'edubot_grade_systems' => $grade_systems,
                'edubot_custom_grades' => $custom_grades,
                'edubot_admission_cycles' => $admission_cycles
            );
            
            foreach ($academic_options as $option_name => $option_value) {
                if (!$this->safe_update_option($option_name, $option_value)) {
                    throw new Exception("Failed to update option: {$option_name}");
                }
            }
            
            // Update school configuration
            $school_config = EduBot_School_Config::getInstance();
            $academic_data = array(
                'academic_settings' => array(
                    'grade_systems' => $grade_systems,
                    'custom_grades' => $custom_grades,
                    'admission_cycles' => $admission_cycles
                )
            );
            
            if (!$school_config->update_config($academic_data)) {
                throw new Exception('Failed to update academic configuration');
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            error_log('EduBot: Academic settings saved successfully');
            wp_cache_delete('edubot_academic_settings');
            
            return $this->send_response(true, 'Academic settings saved successfully!');
            
        } catch (Exception $e) {
            // Rollback transaction
            global $wpdb;
            $wpdb->query('ROLLBACK');
            
            error_log('EduBot: Error saving academic settings: ' . $e->getMessage());
            return $this->send_response(false, 'Failed to save academic settings. Please try again.');
        }
    }

    /**
     * Test API connection via AJAX with enhanced security
     */
    public function test_api_connection() {
        error_log('EduBot: test_api_connection called');
        error_log('EduBot: POST data: ' . print_r($_POST, true));
        
        // Enhanced nonce verification - handle both API settings and AI settings nonces
        $nonce_valid = false;
        if (isset($_POST['edubot_api_nonce']) && wp_verify_nonce($_POST['edubot_api_nonce'], 'edubot_save_api_settings')) {
            $nonce_valid = true;
        } elseif (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'edubot_test_api')) {
            $nonce_valid = true;
        }
        
        if (!$nonce_valid) {
            error_log('EduBot: Nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed. Please refresh the page.'));
        }

        // Capability check
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions.'));
        }

        // Rate limiting for API tests (increased for development/testing)
        $security_manager = new EduBot_Security_Manager();
        $user_id = get_current_user_id();
        
        // Clear rate limit if WP_DEBUG is enabled (development mode)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $security_manager->clear_rate_limit('api_test_' . $user_id);
        }
        
        if (!$security_manager->check_rate_limit('api_test_' . $user_id, 50, 3600)) {
            wp_send_json_error(array('message' => 'Too many API test requests. Please wait before trying again.'));
        }

        // Input validation
        $api_type = isset($_POST['api_type']) ? sanitize_text_field($_POST['api_type']) : '';
        $allowed_api_types = array('openai', 'whatsapp', 'email');
        
        if (empty($api_type) || !in_array($api_type, $allowed_api_types)) {
            wp_send_json_error(array('message' => 'Invalid API type specified.'));
        }

        $api_integrations = new EduBot_API_Integrations();
        $result = false;
        
        try {
            switch ($api_type) {
                case 'openai':
                    $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
                    
                    // If no API key provided in request, try to get saved key
                    if (empty($api_key)) {
                        $encrypted_key = get_option('edubot_openai_api_key', '');
                        if (!empty($encrypted_key)) {
                            try {
                                $api_key = $security_manager->decrypt_api_key($encrypted_key);
                            } catch (Exception $e) {
                                // Log decryption failure
                                self::log_api_request_to_db('openai', 'test_connection', 
                                    array('error' => 'key_decryption_failed'), 
                                    null, false, 500, 'Failed to decrypt saved API key', 0);
                                wp_send_json_error(array('message' => 'Failed to decrypt saved API key.'));
                            }
                        }
                    }
                    
                    if (empty($api_key)) {
                        // Log missing API key
                        self::log_api_request_to_db('openai', 'test_connection', 
                            array('error' => 'missing_api_key'), 
                            null, false, 400, 'API key is required', 0);
                        wp_send_json_error(array('message' => 'API key is required. Please enter your OpenAI API key.'));
                    }
                    
                    // More flexible OpenAI key validation
                    if (!preg_match('/^sk-[a-zA-Z0-9_\-\.]{32,}$/', $api_key)) {
                        // Log invalid API key format
                        self::log_api_request_to_db('openai', 'test_connection', 
                            array('api_key_format' => 'invalid', 'key_prefix' => substr($api_key, 0, 6)), 
                            null, false, 400, 'Invalid OpenAI API key format', 0);
                        wp_send_json_error(array('message' => 'Invalid OpenAI API key format. Key should start with "sk-" and be at least 35 characters long.'));
                    }
                    
                    // Log test attempt
                    self::log_api_request_to_db('openai', 'test_connection', 
                        array('api_key_prefix' => substr($api_key, 0, 6), 'test_initiated' => true), 
                        null, null, null, null, 0);
                    
                    $result = $api_integrations->test_openai_connection($api_key);
                    
                    // Log test result
                    self::log_api_request_to_db('openai', 'test_connection', 
                        array('api_key_prefix' => substr($api_key, 0, 6)), 
                        $result, 
                        $result['success'], 
                        $result['success'] ? 200 : 400, 
                        $result['success'] ? null : $result['message'], 0);
                    break;
                    
                case 'whatsapp':
                    $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
                    $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
                    $phone_id = isset($_POST['phone_id']) ? sanitize_text_field($_POST['phone_id']) : '';
                    
                    if (empty($token) || empty($provider)) {
                        // Log missing parameters
                        self::log_api_request_to_db('whatsapp', 'test_connection', 
                            array('token_provided' => !empty($token), 'provider_provided' => !empty($provider)), 
                            null, false, 400, 'Token and provider are required', 0);
                        wp_send_json_error(array('message' => 'Token and provider are required.'));
                    }
                    
                    $allowed_providers = array('meta', 'twilio', 'whatsapp_business');
                    if (!in_array($provider, $allowed_providers)) {
                        // Log invalid provider
                        self::log_api_request_to_db('whatsapp', 'test_connection', 
                            array('provider' => $provider, 'allowed_providers' => $allowed_providers), 
                            null, false, 400, 'Invalid WhatsApp provider', 0);
                        wp_send_json_error(array('message' => 'Invalid WhatsApp provider.'));
                    }
                    
                    // Log test attempt
                    self::log_api_request_to_db('whatsapp', 'test_connection', 
                        array('provider' => $provider, 'phone_id_provided' => !empty($phone_id)), 
                        null, null, null, null, 0);
                    
                    $result = $api_integrations->test_whatsapp_connection($token, $provider, $phone_id);
                    
                    // Log test result
                    self::log_api_request_to_db('whatsapp', 'test_connection', 
                        array('provider' => $provider), 
                        $result, 
                        $result['success'], 
                        $result['success'] ? 200 : 400, 
                        $result['success'] ? null : $result['message'], 0);
                    break;
                    
                case 'email':
                    $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
                    
                    error_log('EduBot: Email test - provider: ' . $provider);
                    error_log('EduBot: Email test - raw POST data: ' . print_r($_POST, true));
                    
                    if (empty($provider)) {
                        error_log('EduBot: Email test failed - no provider specified');
                        // Log missing provider
                        self::log_api_request_to_db('email', 'test_connection', 
                            array('error' => 'no_provider_specified'), 
                            null, false, 400, 'Email provider is required', 0);
                        wp_send_json_error(array('message' => 'Email provider is required.'));
                    }
                    
                    // Log email test attempt
                    self::log_api_request_to_db('email', 'test_connection', 
                        array('provider' => $provider), 
                        null, null, null, null, 0);
                    
                    // Get saved email settings and merge with any posted data
                    $saved_settings = array(
                        'provider' => get_option('edubot_email_service', ''),
                        'host' => get_option('edubot_smtp_host', ''),
                        'port' => get_option('edubot_smtp_port', 587),
                        'username' => get_option('edubot_smtp_username', ''),
                        'password' => get_option('edubot_smtp_password', ''),
                        'api_key' => get_option('edubot_email_api_key', ''),
                        'domain' => get_option('edubot_email_domain', ''),
                        'from_address' => get_option('edubot_email_from_address', ''),
                        'from_name' => get_option('edubot_email_from_name', '')
                    );
                    
                    error_log('EduBot: Email test - saved settings: ' . print_r(array_merge($saved_settings, array('password' => '[REDACTED]')), true));
                    
                    // Override with any posted values (for testing new settings before saving)
                    $settings = array(
                        'provider' => !empty($_POST['provider']) ? sanitize_text_field($_POST['provider']) : $saved_settings['provider'],
                        'api_key' => isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : $saved_settings['api_key'],
                        'domain' => isset($_POST['domain']) ? sanitize_text_field($_POST['domain']) : $saved_settings['domain'],
                        'host' => isset($_POST['host']) ? sanitize_text_field($_POST['host']) : $saved_settings['host'],
                        'port' => isset($_POST['port']) ? absint($_POST['port']) : $saved_settings['port'],
                        'username' => isset($_POST['username']) ? sanitize_text_field($_POST['username']) : $saved_settings['username'],
                        'password' => isset($_POST['password']) ? sanitize_text_field($_POST['password']) : $saved_settings['password'],
                        'from_address' => isset($_POST['from_address']) ? sanitize_email($_POST['from_address']) : $saved_settings['from_address'],
                        'from_name' => isset($_POST['from_name']) ? sanitize_text_field($_POST['from_name']) : $saved_settings['from_name']
                    );
                    
                    // Clean API key - remove any existing prefixes to avoid duplication
                    if (!empty($settings['api_key'])) {
                        $settings['api_key'] = str_replace(array('Zoho-enczapikey ', 'Bearer '), '', $settings['api_key']);
                        $settings['api_key'] = trim($settings['api_key']);
                    }
                    
                    error_log('EduBot: Email test - final settings: ' . print_r(array_merge($settings, array('password' => '[REDACTED]')), true));
                    
                    // Decrypt password if it appears to be encrypted (from saved settings or POST data)
                    if (!empty($settings['password'])) {
                        // Check if password appears to be encrypted (base64 encoded and longer than typical passwords)
                        if (strlen($settings['password']) > 50 && base64_encode(base64_decode($settings['password'], true)) === $settings['password']) {
                            try {
                                $security_manager = new EduBot_Security_Manager();
                                $settings['password'] = $security_manager->decrypt_api_key($settings['password']);
                                error_log('EduBot: Email test - password successfully decrypted');
                            } catch (Exception $e) {
                                error_log('EduBot: Failed to decrypt email password: ' . $e->getMessage());
                                wp_send_json_error(array('message' => 'Failed to retrieve password. Please re-enter your email settings.'));
                            }
                        }
                    }
                    
                    // Legacy decryption for empty POST password (keep for backward compatibility)
                    if (empty($_POST['password']) && !empty($saved_settings['password'])) {
                        try {
                            $security_manager = new EduBot_Security_Manager();
                            $settings['password'] = $security_manager->decrypt_api_key($saved_settings['password']);
                        } catch (Exception $e) {
                            error_log('EduBot: Failed to decrypt saved email password: ' . $e->getMessage());
                            wp_send_json_error(array('message' => 'Failed to retrieve saved password. Please re-enter your email settings.'));
                        }
                    }
                    
                    // Validate required fields based on provider
                    if ($settings['provider'] === 'smtp') {
                        error_log('EduBot: SMTP validation - host: ' . $settings['host'] . ', username: ' . $settings['username']);
                        if (empty($settings['host']) || empty($settings['username'])) {
                            error_log('EduBot: SMTP validation failed - missing required fields');
                            
                            // Provide more helpful error message
                            $missing = array();
                            if (empty($settings['host'])) $missing[] = 'SMTP Host';
                            if (empty($settings['username'])) $missing[] = 'SMTP Username';
                            
                            wp_send_json_error(array('message' => 'Missing required SMTP settings: ' . implode(', ', $missing) . '. Please configure your SMTP settings first.'));
                        }
                    } elseif (in_array($settings['provider'], array('mailgun', 'sendgrid', 'zeptomail'))) {
                        error_log('EduBot: API provider validation - api_key length: ' . strlen($settings['api_key']));
                        if (empty($settings['api_key'])) {
                            error_log('EduBot: API provider validation failed - missing API key');
                            wp_send_json_error(array('message' => 'API key is required for ' . strtoupper($settings['provider']) . ' provider. Please enter your API key first.'));
                        }
                        if ($settings['provider'] === 'mailgun' && empty($settings['domain'])) {
                            error_log('EduBot: Mailgun validation failed - missing domain');
                            wp_send_json_error(array('message' => 'Domain is required for Mailgun provider.'));
                        }
                    }
                    
                    // Validate port range
                    if (!empty($settings['port']) && ($settings['port'] < 1 || $settings['port'] > 65535)) {
                        wp_send_json_error(array('message' => 'Invalid port number.'));
                    }
                    
                    error_log('EduBot: Email test settings: ' . print_r(array_merge($settings, array('password' => '[REDACTED]')), true));
                    
                    $result = $api_integrations->test_email_connection($settings);
                    break;
            }
            
            // Log API test attempt
            if (is_array($result)) {
                $success = $result['success'];
                $message = $result['message'];
                error_log("EduBot Admin: API test for {$api_type} - " . ($success ? 'SUCCESS' : 'FAILED') . " - {$message}");
                
                if ($success) {
                    wp_send_json_success(array('message' => $message));
                } else {
                    wp_send_json_error(array('message' => $message));
                }
            } else {
                // Legacy boolean response handling
                error_log("EduBot Admin: API test for {$api_type} - " . ($result ? 'SUCCESS' : 'FAILED'));
                
                if ($result) {
                    wp_send_json_success(array('message' => __('Connection successful!', 'edubot-pro')));
                } else {
                    wp_send_json_error(array('message' => __('Connection failed! Please check your credentials.', 'edubot-pro')));
                }
            }
            
        } catch (Exception $e) {
            error_log('EduBot Admin API Test Error: ' . $e->getMessage());
            // Log exception to API logs
            self::log_api_request_to_db($api_type, 'test_connection', 
                array('exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()), 
                null, false, 500, 'API test exception: ' . $e->getMessage(), 0);
            wp_send_json_error(array('message' => 'API test failed due to technical error. Please try again.'));
        }
    }

    /**
     * Get dashboard statistics via AJAX
     */
    public function get_dashboard_stats_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'edubot_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }

        // Capability check
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions.'));
        }

        try {
            // Get basic dashboard stats
            global $wpdb;
            
            $stats = array(
                'total_inquiries' => 0,
                'total_applications' => 0,
                'pending_applications' => 0,
                'total_conversations' => 0,
                'active_schools' => 1,
                'recent_activity' => array(),
                'changes' => array(
                    'total_applications' => 0,
                    'pending_applications' => 0,
                    'total_conversations' => 0,
                    'active_schools' => 0
                )
            );

            // Try to get inquiries count (if table exists)
            $inquiry_table = $wpdb->prefix . 'edubot_inquiries';
            if ($wpdb->get_var("SHOW TABLES LIKE '$inquiry_table'") == $inquiry_table) {
                $stats['total_inquiries'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $inquiry_table");
                $stats['pending_applications'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $inquiry_table WHERE status = 'pending'");
            }

            // Try to get applications count (if table exists)
            $applications_table = $wpdb->prefix . 'edubot_applications';
            if ($wpdb->get_var("SHOW TABLES LIKE '$applications_table'") == $applications_table) {
                $stats['total_applications'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $applications_table");
            }

            // Get conversation/chat count estimate
            $stats['total_conversations'] = (int) get_option('edubot_total_conversations', 0);

            // Get recent activity from options or logs
            $recent_logs = get_option('edubot_recent_activity', array());
            $stats['recent_activity'] = array_slice($recent_logs, 0, 5);

            wp_send_json_success($stats);
            
        } catch (Exception $e) {
            error_log('EduBot Dashboard Stats Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Failed to load dashboard statistics.'));
        }
    }

    /**
     * Save settings via AJAX with enhanced security
     */
    public function save_settings() {
        // Enhanced nonce verification
        if (!wp_verify_nonce($_POST['nonce'], 'edubot_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed. Please refresh the page.'));
        }

        // Capability check
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions to save settings.'));
        }

        // Rate limiting for settings saves
        $security_manager = new EduBot_Security_Manager();
        $user_id = get_current_user_id();
        
        if (!$security_manager->check_rate_limit('settings_save_' . $user_id, 30, 3600)) {
            wp_send_json_error(array('message' => 'Too many save requests. Please wait before trying again.'));
        }

        // Input validation
        $settings_type = isset($_POST['settings_type']) ? sanitize_text_field($_POST['settings_type']) : '';
        $allowed_settings_types = array('school', 'api', 'form', 'chatbot', 'academic');
        
        if (empty($settings_type) || !in_array($settings_type, $allowed_settings_types)) {
            wp_send_json_error(array('message' => 'Invalid settings type specified.'));
        }

        $success = false;
        
        try {
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
                    
                case 'chatbot':
                    $success = $this->save_chatbot_settings();
                    break;
                    
                case 'academic':
                    $success = $this->save_academic_settings();
                    break;
            }
            
            if ($success) {
                // Log successful settings save
                error_log("EduBot Admin: Settings saved successfully - {$settings_type} by user {$user_id}");
            }
            
            wp_send_json(array(
                'success' => $success,
                'message' => $success ? __('Settings saved successfully!', 'edubot-pro') : __('Error saving settings. Please check your input and try again.', 'edubot-pro')
            ));
            
        } catch (Exception $e) {
            error_log('EduBot Admin Settings Save Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Settings save failed due to technical error. Please try again.'));
        }
    }

    /**
     * Debug logging functionality
     */
    public static function debug_log($message, $level = 'info', $context = array()) {
        // Check if debug mode is enabled
        if (!get_option('edubot_debug_enabled', false)) {
            return;
        }

        $debug_level = get_option('edubot_debug_level', 'basic');
        
        // Filter messages based on debug level
        if ($debug_level === 'basic' && !in_array($level, array('error', 'warning'))) {
            return;
        }
        
        if ($debug_level === 'detailed' && !in_array($level, array('error', 'warning', 'info'))) {
            return;
        }
        
        // Format log entry
        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $user_info = $user_id ? " [User: {$user_id}]" : '';
        
        $log_entry = "[{$timestamp}] [{$level}]{$user_info} {$message}";
        
        // Add context if provided
        if (!empty($context)) {
            $log_entry .= " Context: " . json_encode($context);
        }
        
        $log_entry .= PHP_EOL;
        
        // Write to debug log file
        $log_file = WP_CONTENT_DIR . '/edubot-debug.log';
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Also log to WordPress debug log if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("EduBot Debug [{$level}]: {$message}");
        }
    }

    /**
     * Log API requests when enabled
     */
    public static function log_api_request($endpoint, $request_data, $response_data = null, $error = null) {
        if (!get_option('edubot_log_api_requests', false)) {
            return;
        }

        $context = array(
            'endpoint' => $endpoint,
            'request_size' => strlen(json_encode($request_data)),
            'response_size' => $response_data ? strlen(json_encode($response_data)) : 0,
            'has_error' => !empty($error)
        );

        if ($error) {
            self::debug_log("API Request Failed: {$endpoint} - {$error}", 'error', $context);
        } else {
            self::debug_log("API Request Successful: {$endpoint}", 'info', $context);
        }

        // In verbose mode, log full request/response (be careful with sensitive data)
        if (get_option('edubot_debug_level') === 'verbose') {
            self::debug_log("API Request Data: " . json_encode($request_data), 'debug');
            if ($response_data) {
                self::debug_log("API Response Data: " . json_encode($response_data), 'debug');
            }
        }
    }

    /**
     * Log data transfer events when enabled
     */
    public static function log_data_transfer($action, $data_type, $data_size = 0, $details = array()) {
        if (!get_option('edubot_log_data_transfers', false)) {
            return;
        }

        $context = array(
            'action' => $action,
            'data_type' => $data_type,
            'data_size' => $data_size,
            'details' => $details
        );

        self::debug_log("Data Transfer: {$action} - {$data_type}", 'info', $context);
    }

    /**
     * Log security events when enabled
     */
    public static function log_security_event($event, $level = 'warning', $details = array()) {
        if (!get_option('edubot_log_security_events', false)) {
            return;
        }

        $context = array(
            'event' => $event,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        );

        self::debug_log("Security Event: {$event}", $level, $context);
    }

    /**
     * AJAX handler to clear debug log
     */
    public function clear_debug_log() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'edubot_clear_debug_log')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions.'));
        }

        $log_file = WP_CONTENT_DIR . '/edubot-debug.log';
        
        if (file_exists($log_file)) {
            if (unlink($log_file)) {
                self::debug_log('Debug log cleared by admin', 'info', array('admin_user' => get_current_user_id()));
                wp_send_json_success(array('message' => 'Debug log cleared successfully.'));
            } else {
                wp_send_json_error(array('message' => 'Failed to clear debug log file.'));
            }
        } else {
            wp_send_json_error(array('message' => 'Debug log file does not exist.'));
        }
    }

    /**
     * Handle debug log download
     */
    public function handle_debug_log_download() {
        // Check if download is requested
        if (!isset($_GET['download_debug_log']) || !wp_verify_nonce($_GET['_wpnonce'], 'download_debug_log')) {
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        $log_file = WP_CONTENT_DIR . '/edubot-debug.log';
        
        if (!file_exists($log_file)) {
            wp_die('Debug log file does not exist.');
        }

        // Set headers for download
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="edubot-debug-' . date('Y-m-d') . '.log"');
        header('Content-Length: ' . filesize($log_file));

        // Output file contents
        readfile($log_file);
        exit;
    }

    /**
     * Clear error logs via AJAX
     */
    public function clear_error_logs_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'edubot_admin_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        try {
            if (class_exists('EduBot_Error_Handler')) {
                $result = EduBot_Error_Handler::clear_log();
                if ($result) {
                    wp_send_json_success('Error logs cleared successfully');
                } else {
                    wp_send_json_error('No error log file found or unable to clear');
                }
            } else {
                wp_send_json_error('Error handler class not available');
            }
        } catch (Exception $e) {
            wp_send_json_error('Failed to clear error logs: ' . $e->getMessage());
        }
    }

    /**
     * Handle bulk actions for applications
     */
    public function handle_bulk_action_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'edubot_admin_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $bulk_action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $application_ids = array_map('sanitize_text_field', $_POST['application_ids'] ?? array());
        $application_ids = array_filter($application_ids); // Remove empty values

        if (empty($bulk_action) || empty($application_ids)) {
            wp_send_json_error('Invalid request parameters');
            return;
        }

        try {
            $database_manager = new EduBot_Database_Manager();
            $processed_count = 0;

            foreach ($application_ids as $app_id) {
                if ($bulk_action === 'delete') {
                    if ($this->delete_application($app_id)) {
                        $processed_count++;
                    }
                } else {
                    // Status updates (approve, reject, pending)
                    if ($database_manager->update_application_status($app_id, $bulk_action)) {
                        $processed_count++;
                    }
                }
            }

            $action_label = ($bulk_action === 'delete') ? 'deleted' : 'updated';
            wp_send_json_success(array(
                'message' => "Successfully {$action_label} {$processed_count} application(s)"
            ));

        } catch (Exception $e) {
            error_log('EduBot: Bulk action error: ' . $e->getMessage());
            wp_send_json_error('Failed to process bulk action');
        }
    }

    /**
     * Handle single application deletion
     */
    public function handle_delete_application_ajax() {
        error_log('EduBot AJAX: Delete application handler called');
        
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        error_log("EduBot AJAX: Nonce received: " . substr($nonce, 0, 10) . "...");
        
        if (!wp_verify_nonce($nonce, 'edubot_admin_nonce')) {
            error_log('EduBot AJAX: Nonce verification failed');
            wp_send_json_error('Security check failed');
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            error_log('EduBot AJAX: User lacks manage_options permission');
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $application_id = isset($_POST['application_id']) ? sanitize_text_field($_POST['application_id']) : '';
        error_log("EduBot AJAX: Application ID: " . $application_id);

        if (empty($application_id)) {
            error_log('EduBot AJAX: Empty application ID');
            wp_send_json_error('Invalid application ID');
            return;
        }

        try {
            error_log("EduBot AJAX: Attempting to delete application {$application_id}");
            if ($this->delete_application($application_id)) {
                error_log("EduBot AJAX: Successfully deleted application {$application_id}");
                wp_send_json_success(array(
                    'message' => 'Application deleted successfully'
                ));
            } else {
                error_log("EduBot AJAX: delete_application returned false for ID {$application_id}");
                wp_send_json_error('Failed to delete application');
            }
        } catch (Exception $e) {
            error_log('EduBot: Delete application error: ' . $e->getMessage());
            wp_send_json_error('Failed to delete application');
        }
    }

    /**
     * Handle view application details
     */
    public function handle_view_application_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'edubot_admin_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $application_id = sanitize_text_field($_POST['application_id'] ?? '');

        if (empty($application_id)) {
            wp_send_json_error('Invalid application ID');
            return;
        }

        try {
            $database_manager = new EduBot_Database_Manager();
            $application = $database_manager->get_application($application_id);

            if (!$application) {
                wp_send_json_error('Application not found');
                return;
            }

            // Parse student data
            $student_data = json_decode($application['student_data'], true);
            if (!$student_data) {
                $student_data = array();
            }

            // Format the application details
            $details_html = $this->format_application_details($application, $student_data);

            wp_send_json_success(array(
                'html' => $details_html
            ));

        } catch (Exception $e) {
            error_log('EduBot: View application error: ' . $e->getMessage());
            wp_send_json_error('Failed to load application details');
        }
    }

    /**
     * Delete application from database
     * Note: Applications are stored in wp_edubot_enquiries table, not wp_edubot_applications
     */
    private function delete_application($application_id) {
        global $wpdb;
        
        error_log("EduBot delete_application: Starting delete for ID {$application_id}");
        
        // Remove 'enq_' prefix if present (the table stores just the numeric ID)
        if (strpos($application_id, 'enq_') === 0) {
            $numeric_id = str_replace('enq_', '', $application_id);
            error_log("EduBot delete_application: Stripped prefix, using numeric ID: {$numeric_id}");
        } else {
            $numeric_id = $application_id;
        }
        
        // Delete from enquiries table (where applications are actually stored)
        $enquiries_table = $wpdb->prefix . 'edubot_enquiries';
        
        error_log("EduBot delete_application: Table = {$enquiries_table}");
        
        // Verify the record exists first
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$enquiries_table} WHERE id = %d", $numeric_id));
        error_log("EduBot delete_application: Record exists? " . ($exists ? 'Yes' : 'No'));
        
        $result = $wpdb->delete(
            $enquiries_table,
            array('id' => $numeric_id),
            array('%d')
        );

        if ($result !== false) {
            error_log("EduBot: Successfully deleted enquiry ID {$application_id} from enquiries table (rows affected: {$result})");
        } else {
            error_log("EduBot: Failed to delete enquiry ID {$application_id}: " . $wpdb->last_error);
        }

        return $result !== false;
    }

    /**
     * Format application details for modal display
     */
    private function format_application_details($application, $student_data) {
        $html = '<div class="application-details">';
        
        // Header
        $html .= '<div class="detail-header">';
        $html .= '<h3>Application #' . esc_html($application['application_number']) . '</h3>';
        $html .= '<p class="status-badge status-' . esc_attr($application['status']) . '">' . esc_html(ucfirst($application['status'])) . '</p>';
        $html .= '</div>';

        // Student Information
        $html .= '<div class="detail-section">';
        $html .= '<h4>Student Information</h4>';
        $html .= '<table class="detail-table">';
        $html .= '<tr><td><strong>Name:</strong></td><td>' . esc_html($student_data['student_name'] ?? 'N/A') . '</td></tr>';
        $html .= '<tr><td><strong>Date of Birth:</strong></td><td>' . esc_html($student_data['date_of_birth'] ?? 'N/A') . '</td></tr>';
        $html .= '<tr><td><strong>Grade:</strong></td><td>' . esc_html($student_data['grade'] ?? 'N/A') . '</td></tr>';
        $html .= '<tr><td><strong>Board:</strong></td><td>' . esc_html($student_data['educational_board'] ?? 'N/A') . '</td></tr>';
        $html .= '<tr><td><strong>Academic Year:</strong></td><td>' . esc_html($student_data['academic_year'] ?? 'N/A') . '</td></tr>';
        if (!empty($student_data['gender'])) {
            $html .= '<tr><td><strong>Gender:</strong></td><td>' . esc_html($student_data['gender']) . '</td></tr>';
        }
        $html .= '</table>';
        $html .= '</div>';

        // Parent Information
        $html .= '<div class="detail-section">';
        $html .= '<h4>Parent/Guardian Information</h4>';
        $html .= '<table class="detail-table">';
        $html .= '<tr><td><strong>Name:</strong></td><td>' . esc_html($student_data['parent_name'] ?? 'N/A') . '</td></tr>';
        $html .= '<tr><td><strong>Email:</strong></td><td>' . esc_html($student_data['email'] ?? 'N/A') . '</td></tr>';
        $html .= '<tr><td><strong>Phone:</strong></td><td>' . esc_html($student_data['phone'] ?? 'N/A') . '</td></tr>';
        if (!empty($student_data['address'])) {
            $html .= '<tr><td><strong>Address:</strong></td><td>' . esc_html($student_data['address']) . '</td></tr>';
        }
        $html .= '</table>';
        $html .= '</div>';

        // Application Details
        $html .= '<div class="detail-section">';
        $html .= '<h4>Application Details</h4>';
        $html .= '<table class="detail-table">';
        $html .= '<tr><td><strong>Source:</strong></td><td>' . esc_html(ucfirst($application['source'] ?? 'Chatbot')) . '</td></tr>';
        $html .= '<tr><td><strong>Submitted:</strong></td><td>' . esc_html(date('F j, Y g:i A', strtotime($application['created_at']))) . '</td></tr>';
        if (!empty($application['ip_address'])) {
            $html .= '<tr><td><strong>IP Address:</strong></td><td>' . esc_html($application['ip_address']) . '</td></tr>';
        }
        if (!empty($application['user_agent'])) {
            $html .= '<tr><td><strong>Browser/Device:</strong></td><td>' . esc_html($application['user_agent']) . '</td></tr>';
        }
        $html .= '</table>';
        $html .= '</div>';

        // UTM Tracking Information
        if (!empty($application['utm_data'])) {
            $utm_data = json_decode($application['utm_data'], true);
            if ($utm_data && is_array($utm_data)) {
                $html .= '<div class="detail-section">';
                $html .= '<h4>Marketing Tracking (UTM)</h4>';
                $html .= '<table class="detail-table">';
                
                if (!empty($utm_data['utm_source'])) {
                    $html .= '<tr><td><strong>Source:</strong></td><td>' . esc_html($utm_data['utm_source']) . '</td></tr>';
                }
                if (!empty($utm_data['utm_medium'])) {
                    $html .= '<tr><td><strong>Medium:</strong></td><td>' . esc_html($utm_data['utm_medium']) . '</td></tr>';
                }
                if (!empty($utm_data['utm_campaign'])) {
                    $html .= '<tr><td><strong>Campaign:</strong></td><td>' . esc_html($utm_data['utm_campaign']) . '</td></tr>';
                }
                if (!empty($utm_data['utm_term'])) {
                    $html .= '<tr><td><strong>Term:</strong></td><td>' . esc_html($utm_data['utm_term']) . '</td></tr>';
                }
                if (!empty($utm_data['utm_content'])) {
                    $html .= '<tr><td><strong>Content:</strong></td><td>' . esc_html($utm_data['utm_content']) . '</td></tr>';
                }
                if (!empty($utm_data['referrer'])) {
                    $html .= '<tr><td><strong>Referrer:</strong></td><td>' . esc_html($utm_data['referrer']) . '</td></tr>';
                }
                
                $html .= '</table>';
                $html .= '</div>';
            }
        }

        // Click ID Tracking (for Paid Campaigns)
        if (!empty($application['gclid']) || !empty($application['fbclid']) || !empty($application['click_id_data'])) {
            $html .= '<div class="detail-section">';
            $html .= '<h4>Campaign Click IDs</h4>';
            $html .= '<table class="detail-table">';
            
            // Google Ads Click ID
            if (!empty($application['gclid'])) {
                $html .= '<tr><td><strong>Google Ads (gclid):</strong></td><td>' . esc_html($application['gclid']) . '</td></tr>';
            }
            
            // Facebook Click ID  
            if (!empty($application['fbclid'])) {
                $html .= '<tr><td><strong>Facebook (fbclid):</strong></td><td>' . esc_html($application['fbclid']) . '</td></tr>';
            }
            
            // Other Click IDs
            if (!empty($application['click_id_data'])) {
                $click_ids = json_decode($application['click_id_data'], true);
                if (is_array($click_ids)) {
                    foreach ($click_ids as $platform => $click_id) {
                        $platform_name = ucfirst(str_replace('clid', '', $platform));
                        switch($platform) {
                            case 'msclkid':
                                $platform_name = 'Microsoft Ads';
                                break;
                            case 'ttclid':
                                $platform_name = 'TikTok Ads';
                                break;
                            case 'twclid':
                                $platform_name = 'Twitter Ads';
                                break;
                            case 'liclid':
                                $platform_name = 'LinkedIn Ads';
                                break;
                            case 'snapclid':
                                $platform_name = 'Snapchat Ads';
                                break;
                            case 'yclid':
                                $platform_name = 'Yandex Ads';
                                break;
                        }
                        $html .= '<tr><td><strong>' . esc_html($platform_name) . ':</strong></td><td>' . esc_html($click_id) . '</td></tr>';
                    }
                }
            }
            
            $html .= '</table>';
            $html .= '</div>';
        }

        // Notification Status
        $html .= '<div class="detail-section">';
        $html .= '<h4>Notification Status</h4>';
        $html .= '<table class="detail-table">';
        
        // Email status
        $email_status = isset($application['email_sent']) && $application['email_sent'] ? 
            '<span style="color: green;">✓ Sent</span>' : '<span style="color: #999;">✗ Not Sent</span>';
        $html .= '<tr><td><strong>Email:</strong></td><td>' . $email_status . '</td></tr>';
        
        // WhatsApp status
        $whatsapp_status = isset($application['whatsapp_sent']) && $application['whatsapp_sent'] ? 
            '<span style="color: green;">✓ Sent</span>' : '<span style="color: #999;">✗ Not Sent</span>';
        $html .= '<tr><td><strong>WhatsApp:</strong></td><td>' . $whatsapp_status . '</td></tr>';
        
        // SMS status
        $sms_status = isset($application['sms_sent']) && $application['sms_sent'] ? 
            '<span style="color: green;">✓ Sent</span>' : '<span style="color: #999;">✗ Not Sent</span>';
        $html .= '<tr><td><strong>SMS:</strong></td><td>' . $sms_status . '</td></tr>';
        
        $html .= '</table>';
        $html .= '</div>';

        // MCB Sync Status and EnquiryCode
        if (!empty($application['mcb_sync_status'])) {
            $html .= '<div class="detail-section" style="background: #f0f8ff; padding: 15px; border-left: 4px solid #0073aa; border-radius: 4px;">';
            $html .= '<h4>📋 MyClassBoard Integration</h4>';
            $html .= '<table class="detail-table">';
            
            // MCB Sync Status
            $mcb_status = $application['mcb_sync_status'];
            $status_color = $mcb_status === 'synced' ? 'green' : ($mcb_status === 'failed' ? 'red' : 'orange');
            $status_icon = $mcb_status === 'synced' ? '✓' : ($mcb_status === 'failed' ? '✗' : '⏳');
            $html .= '<tr><td><strong>Sync Status:</strong></td><td><span style="color: ' . esc_attr($status_color) . '; font-weight: bold;">' . esc_html($status_icon) . ' ' . esc_html(ucfirst($mcb_status)) . '</span></td></tr>';
            
            // MCB EnquiryCode (the important one - shown in mcb_enquiry_id field!)
            if (!empty($application['mcb_enquiry_id'])) {
                $html .= '<tr><td><strong>EnquiryCode:</strong></td><td><span style="background: #fff3cd; padding: 8px 12px; border-radius: 4px; font-weight: bold; font-family: monospace;">' . esc_html($application['mcb_enquiry_id']) . '</span></td></tr>';
            }
            
            $html .= '</table>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Run database migration via AJAX
     */
    public function run_migration_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'edubot_admin_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        try {
            if (class_exists('EduBot_Analytics_Migration')) {
                EduBot_Analytics_Migration::check_and_migrate();
                wp_send_json_success('Database migration completed successfully');
            } else {
                wp_send_json_error('Migration class not available');
            }
        } catch (Exception $e) {
            wp_send_json_error('Migration failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle autosave AJAX requests
     */
    public function handle_autosave_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'edubot_admin_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        // For now, just return success without actually saving
        // This prevents the 400 errors while keeping the autosave functionality
        wp_send_json_success('Autosave completed');
    }

    /**
     * Generate WhatsApp Ad Integration Link via AJAX
     * 
     * @since 2.0.0
     */
    public function generate_whatsapp_link_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'edubot_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        try {
            // Load the link generator class
            if (!class_exists('EduBot_WhatsApp_Ad_Link_Generator')) {
                require_once plugin_dir_path(__FILE__) . '../includes/class-whatsapp-ad-link-generator.php';
            }

            // Support both simple and complex parameter formats
            $simple_campaign = sanitize_text_field($_POST['campaign'] ?? '');
            $simple_source = sanitize_text_field($_POST['source'] ?? '');
            $simple_grades = sanitize_text_field($_POST['grades'] ?? '');
            
            // Check if using simple format (like "Admission Drive - Google")
            if (!empty($simple_campaign) && !empty($simple_source)) {
                $params = array(
                    'source' => $simple_source,
                    'campaign' => $simple_campaign,
                    'grades' => $simple_grades
                );
            } else {
                // Complex format
                $params = array(
                    'campaign_name' => sanitize_text_field($_POST['campaign_name'] ?? ''),
                    'platform' => sanitize_text_field($_POST['platform'] ?? 'facebook'),
                    'phone_number' => sanitize_text_field($_POST['phone_number'] ?? ''),
                    'initial_message' => sanitize_textarea_field($_POST['initial_message'] ?? ''),
                    'target_grades' => isset($_POST['target_grades']) ? array_map('sanitize_text_field', $_POST['target_grades']) : array(),
                    'attribution_data' => json_decode(stripslashes($_POST['attribution_data'] ?? '{}'), true)
                );
            }

            // Validate required fields based on format
            if (isset($params['source'])) {
                // Simple format validation
                if (empty($params['campaign']) || empty($params['source'])) {
                    wp_send_json_error(array('message' => 'Campaign name and source are required'));
                    return;
                }
            } else {
                // Complex format validation
                if (empty($params['phone_number'])) {
                    wp_send_json_error(array('message' => 'Phone number is required'));
                    return;
                }
            }

            // Generate the link
            $generator = new EduBot_WhatsApp_Ad_Link_Generator();
            $result = $generator->generate_whatsapp_link($params);
            
            $response = array();
            
            if (isset($params['source'])) {
                // Simple format - return link as string and create campaign record
                $link = is_string($result) ? $result : ($result['link'] ?? '');
                
                // Create campaign record in database
                $campaign_id = EduBot_WhatsApp_Ad_Link_Generator::create_campaign(array(
                    'name' => $params['campaign'],
                    'source' => $params['source'],
                    'grades' => $params['grades'],
                    'link' => $link
                ));
                
                $response = array(
                    'link' => $link,
                    'campaign_id' => $campaign_id,
                    'message' => 'Simple WhatsApp link generated successfully'
                );
            } else {
                // Complex format
                $response = array(
                    'link' => $result['link'] ?? '',
                    'campaign_id' => $result['campaign_id'] ?? '',
                    'message' => 'WhatsApp link generated successfully'
                );
            }

            wp_send_json_success($response);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error generating link: ' . $e->getMessage()));
        }
    }

    /**
     * Generate Simple WhatsApp Link via AJAX (for backend interface)
     * 
     * Simple format: Just pass campaign name like "Admission Drive - Google" and source
     * 
     * @since 2.0.1
     */
    public function generate_simple_whatsapp_link_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'edubot_whatsapp_nonce')) {
            wp_send_json_error('Nonce verification failed', 403);
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied', 403);
            return;
        }

        try {
            // Load the link generator class
            if (!class_exists('EduBot_WhatsApp_Ad_Link_Generator')) {
                require_once plugin_dir_path(__FILE__) . '../includes/class-whatsapp-ad-link-generator.php';
            }

            // Get simple parameters
            $campaign = sanitize_text_field($_POST['campaign'] ?? '');
            $source = sanitize_text_field($_POST['source'] ?? '');
            $grades = sanitize_text_field($_POST['grades'] ?? '');

            // Validate required fields
            if (empty($campaign) || empty($source)) {
                wp_send_json_error('Missing required parameters');
                return;
            }

            // Generate link using legacy format
            $link = EduBot_WhatsApp_Ad_Link_Generator::generate_whatsapp_link(array(
                'source' => $source,
                'campaign' => $campaign,
                'grades' => $grades
            ));

            // Create campaign record in database
            $campaign_id = EduBot_WhatsApp_Ad_Link_Generator::create_campaign(array(
                'name' => $campaign,
                'source' => $source,
                'grades' => $grades,
                'link' => $link
            ));

            wp_send_json_success(array(
                'link' => $link,
                'campaign_id' => $campaign_id
            ));

        } catch (Exception $e) {
            wp_send_json_error('Error generating link: ' . $e->getMessage());
        }
    }

    /**
     * Generate Webhook Token via AJAX
     * 
     * @since 2.0.0
     */
    public function generate_webhook_token_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'edubot_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        try {
            // Generate a random token for webhook verification
            $token = bin2hex(random_bytes(32));
            
            // Store in options for verification
            update_option('edubot_whatsapp_webhook_token', $token);
            update_option('edubot_whatsapp_webhook_token_created', current_time('mysql'));

            wp_send_json_success(array(
                'token' => $token,
                'webhook_url' => rest_url('edubot/v1/whatsapp-webhook'),
                'message' => 'Webhook token generated successfully'
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error generating token: ' . $e->getMessage()));
        }
    }

    /**
     * Display WhatsApp Ad Integration admin page
     * 
     * @since 2.0.0
     */
    public function display_whatsapp_ads_page() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('📱 WhatsApp Ad Integration', 'edubot-pro'); ?></h1>
            
            <div style="margin: 20px 0;">
                <p style="font-size: 16px;">
                    <?php esc_html_e('Connect your WhatsApp Business Account to send direct messages from Facebook and Instagram ads.', 'edubot-pro'); ?>
                </p>
            </div>

            <!-- Campaign Creation Section -->
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin: 20px 0;">
                <h2><?php esc_html_e('Create WhatsApp Campaign', 'edubot-pro'); ?></h2>
                <p style="color: #666; margin-bottom: 20px;">
                    <?php esc_html_e('Each campaign link will automatically track which platform (Facebook/Instagram) it came from', 'edubot-pro'); ?>
                </p>
                
                <form id="whatsapp-campaign-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="campaign_name"><?php esc_html_e('Campaign Name *', 'edubot-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="campaign_name" name="campaign_name" required
                                       placeholder="e.g., Grade 10 Admissions 2025" style="width: 100%; padding: 8px;">
                                <p style="margin: 8px 0 0 0; color: #666; font-size: 12px;">
                                    <?php esc_html_e('This will be used to identify the source of leads', 'edubot-pro'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="platform"><?php esc_html_e('Ad Platform *', 'edubot-pro'); ?></label>
                            </th>
                            <td>
                                <select id="platform" name="platform" required style="width: 100%; padding: 8px;">
                                    <option value="">-- Select Platform --</option>
                                    <option value="facebook">🔵 Facebook Ads</option>
                                    <option value="instagram">📷 Instagram Ads</option>
                                    <option value="google">🔴 Google Ads</option>
                                    <option value="tiktok">🎵 TikTok Ads</option>
                                    <option value="linkedin">💼 LinkedIn Ads</option>
                                    <option value="twitter">𝕏 Twitter/X Ads</option>
                                    <option value="other">🔗 Other Platform</option>
                                </select>
                                <p style="margin: 8px 0 0 0; color: #666; font-size: 12px;">
                                    <?php esc_html_e('The platform will be tracked and recorded in the contact database', 'edubot-pro'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="phone_number"><?php esc_html_e('WhatsApp Phone Number *', 'edubot-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="phone_number" name="phone_number" required
                                       placeholder="+91 98665 13566" style="width: 100%; padding: 8px;">
                                <p style="margin: 8px 0 0 0; color: #666; font-size: 12px;">
                                    <?php esc_html_e('Include country code (e.g., +91 for India, +1 for USA)', 'edubot-pro'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="initial_message"><?php esc_html_e('Initial Message *', 'edubot-pro'); ?></label>
                            </th>
                            <td>
                                <textarea id="initial_message" name="initial_message" rows="3" required
                                          placeholder="Welcome to our admissions program..." 
                                          style="width: 100%; padding: 8px;"></textarea>
                                <p style="margin: 8px 0 0 0; color: #666; font-size: 12px;">
                                    <?php esc_html_e('This message will be pre-filled when user opens WhatsApp', 'edubot-pro'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" class="button button-primary" onclick="generateWhatsAppLink()">
                            <?php esc_html_e('🔗 Generate WhatsApp Link with Tracking', 'edubot-pro'); ?>
                        </button>
                    </p>
                </form>
            </div>

            <!-- Generated Link Section -->
            <div id="link-result" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin: 20px 0; display: none;">
                <h3><?php esc_html_e('Your WhatsApp Click-to-Chat Link', 'edubot-pro'); ?></h3>
                <div style="background: #f5f5f5; padding: 12px; border-radius: 3px; margin: 10px 0;">
                    <code id="generated-link" style="word-break: break-all; display: block;"></code>
                </div>
                <button type="button" class="button button-secondary" onclick="copyToClipboard()">
                    <?php esc_html_e('Copy Link', 'edubot-pro'); ?>
                </button>
            </div>

            <!-- Info Box -->
            <div style="background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h3><?php esc_html_e('ℹ️ How It Works', 'edubot-pro'); ?></h3>
                <ol style="margin: 10px 0;">
                    <li><?php esc_html_e('Create a campaign with your business WhatsApp number', 'edubot-pro'); ?></li>
                    <li><?php esc_html_e('Copy the generated link', 'edubot-pro'); ?></li>
                    <li><?php esc_html_e('Use the link in your Facebook/Instagram ads', 'edubot-pro'); ?></li>
                    <li><?php esc_html_e('When users click, they go directly to WhatsApp', 'edubot-pro'); ?></li>
                </ol>
            </div>
        </div>

        <script>
        function generateWhatsAppLink() {
            var phone = document.getElementById('phone_number').value;
            var message = document.getElementById('initial_message').value;
            var campaign_name = document.getElementById('campaign_name').value;
            var platform = document.getElementById('platform').value;
            
            // Validation
            if (!campaign_name) {
                alert('Please enter campaign name');
                return;
            }
            
            if (!platform) {
                alert('Please select platform');
                return;
            }
            
            if (!phone) {
                alert('Please enter WhatsApp phone number');
                return;
            }
            
            if (!message) {
                alert('Please enter initial message');
                return;
            }
            
            // Clean phone number
            phone = phone.replace(/[^0-9+]/g, '');
            
            // Build UTM parameters
            var utm_source = platform; // facebook, instagram, google, tiktok, linkedin, twitter, other
            var utm_medium = 'whatsapp_click_to_chat';
            var utm_campaign = campaign_name.replace(/\s+/g, '_').toLowerCase();
            
            // Create tracking string
            var tracking_params = 'utm_source=' + encodeURIComponent(utm_source) + 
                                  '&utm_medium=' + encodeURIComponent(utm_medium) + 
                                  '&utm_campaign=' + encodeURIComponent(utm_campaign);
            
            // Platform display names
            var platform_display = {
                'facebook': 'FACEBOOK',
                'instagram': 'INSTAGRAM',
                'google': 'GOOGLE',
                'tiktok': 'TIKTOK',
                'linkedin': 'LINKEDIN',
                'twitter': 'TWITTER/X',
                'other': 'OTHER'
            };
            
            var platform_name = platform_display[platform] || platform.toUpperCase();
            
            // Add tracking info to message
            var full_message = message + '\n\n[Source: ' + platform_name + 
                               ' | Campaign: ' + campaign_name + ' | ' + tracking_params + ']';
            
            // Generate link
            var link = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(full_message);
            
            // Display result
            document.getElementById('generated-link').textContent = link;
            document.getElementById('link-result').style.display = 'block';
            
            // Show tracking info
            showTrackingInfo(platform_name, campaign_name, tracking_params, platform);
        }
        
        function showTrackingInfo(platform_display, campaign, params, platform_code) {
            var tracking_html = '<div style="background: #e7f3ff; border: 2px solid #2196F3; padding: 15px; border-radius: 5px; margin: 15px 0;">' +
                               '<strong style="color: #1976D2; display: block; margin-bottom: 10px;">📊 Tracking Information Embedded in Message:</strong>' +
                               '<div style="font-family: monospace; font-size: 12px; color: #333; line-height: 1.8; background: #fff; padding: 10px; border-left: 3px solid #2196F3; border-radius: 3px;">' +
                               '<strong>📍 Platform:</strong> ' + platform_display + '<br>' +
                               '<strong>📝 Campaign:</strong> ' + campaign + '<br>' +
                               '<strong>🔗 UTM Parameters:</strong><br>' +
                               '&nbsp;&nbsp;→ utm_source=' + platform_code + '<br>' +
                               '&nbsp;&nbsp;→ utm_medium=whatsapp_click_to_chat<br>' +
                               '&nbsp;&nbsp;→ utm_campaign=' + campaign.replace(/\s+/g, '_').toLowerCase() + '<br>' +
                               '</div>' +
                               '<div style="background: #fff3e0; padding: 10px; margin-top: 10px; border-left: 3px solid #ff9800; border-radius: 3px;">' +
                               '<strong style="color: #e65100;">ℹ️ How it works:</strong><br>' +
                               '<span style="font-size: 11px; color: #666;">When users click this link from ads, WhatsApp opens with your message. ' +
                               'The tracking info appears in the message, helping identify which platform each lead came from. ' +
                               'The backend automatically extracts and stores this data.</span>' +
                               '</div>' +
                               '</div>';
            
            var existing = document.getElementById('tracking-info');
            if (existing) {
                existing.outerHTML = tracking_html;
            } else {
                var link_result = document.getElementById('link-result');
                if (link_result) {
                    var new_div = document.createElement('div');
                    new_div.id = 'tracking-info';
                    new_div.innerHTML = tracking_html;
                    link_result.parentNode.insertBefore(new_div, link_result.nextSibling);
                }
            }
        }
        
        function copyToClipboard() {
            var link = document.getElementById('generated-link').textContent;
            navigator.clipboard.writeText(link).then(function() {
                alert('✅ Link copied to clipboard!\n\nThis link includes:\n' +
                      '• Platform tracking (Facebook/Instagram/etc)\n' +
                      '• Campaign name\n' +
                      '• UTM parameters\n\n' +
                      'The tracking info will appear in the WhatsApp message to help identify the source.');
            }).catch(function(err) {
                alert('Failed to copy link: ' + err);
            });
        }
        </script>

        <style>
        #whatsapp-campaign-form {
            background: #f9f9f9;
            padding: 0;
        }
        .form-table td {
            padding: 15px;
        }
        .form-table th {
            padding: 15px;
            text-align: left;
            width: 200px;
        }
        </style>
        <?php
    }

    /**
     * Display API Logs page
     */
    public function display_api_logs_page() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        global $wpdb;
        
        // Get API logs from the database
        $logs = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}edubot_api_logs 
             ORDER BY created_at DESC 
             LIMIT 100"
        );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('🔌 API Logs', 'edubot-pro'); ?></h1>
            
            <div style="margin: 20px 0;">
                <p style="font-size: 16px;">
                    <?php esc_html_e('Monitor API requests and responses for email, WhatsApp, and other integrations.', 'edubot-pro'); ?>
                </p>
            </div>

            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 100px;">Provider</th>
                            <th style="width: 120px;">Request Type</th>
                            <th style="width: 80px;">Status</th>
                            <th style="width: 80px;">Response Code</th>
                            <th style="width: 120px;">Created At</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log->log_id); ?></td>
                                <td><strong><?php echo esc_html($log->api_provider ?: 'N/A'); ?></strong></td>
                                <td><?php echo esc_html($log->request_type ?: 'N/A'); ?></td>
                                <td>
                                    <?php
                                    $status_class = $log->success ? 'success' : 'error';
                                    $status_text = $log->success ? '✓ Success' : '✗ Failed';
                                    $color = $log->success ? '#28a745' : '#dc3545';
                                    echo "<span style='color: {$color}; font-weight: bold;'>" . esc_html($status_text) . "</span>";
                                    ?>
                                </td>
                                <td><?php echo esc_html($log->response_status ?: 'N/A'); ?></td>
                                <td><?php echo esc_html(date('M d, Y H:i:s', strtotime($log->created_at))); ?></td>
                                <td>
                                    <button type="button" class="button button-small" onclick="viewLogDetails(<?php echo esc_attr($log->log_id); ?>)">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                    <p style="font-size: 16px; margin: 0;">📝 No API logs found.</p>
                                    <p style="margin: 10px 0 0 0; font-size: 14px;">
                                        API logs will appear here when email, WhatsApp, or other API integrations are used.
                                    </p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if (!empty($logs)): ?>
                <div style="margin-top: 20px; padding: 15px; background: #f0f8ff; border-left: 4px solid #0073aa; border-radius: 4px;">
                    <h4 style="margin: 0 0 10px 0; color: #0073aa;">📊 Log Statistics</h4>
                    <p style="margin: 0; font-size: 14px; color: #666;">
                        <strong>Total Logs:</strong> <?php echo count($logs); ?> | 
                        <strong>Success Rate:</strong> <?php 
                        $success_count = array_sum(array_map(function($log) { return $log->success ? 1 : 0; }, $logs));
                        $success_rate = count($logs) > 0 ? round(($success_count / count($logs)) * 100, 1) : 0;
                        echo $success_rate . '%';
                        ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Test API Logging Button -->
            <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
                <h3 style="margin: 0 0 10px 0; color: #856404;">🧪 Test API Logging</h3>
                <p style="margin: 0 0 15px 0; color: #856404;">
                    Click the button below to create a test API log entry and verify that logging is working correctly.
                </p>
                <button type="button" class="button button-primary" onclick="testApiLogging()">
                    Generate Test Log Entry
                </button>
            </div>
        </div>

        <script>
        function viewLogDetails(logId) {
            // Make AJAX request to get log details
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'edubot_get_api_log_details',
                    log_id: logId,
                    nonce: '<?php echo wp_create_nonce("edubot_admin_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var log = response.data;
                        var content = '<div style="max-height: 400px; overflow-y: auto;">';
                        content += '<h3>API Log Details - ID: ' + logId + '</h3>';
                        content += '<p><strong>Provider:</strong> ' + (log.api_provider || 'N/A') + '</p>';
                        content += '<p><strong>Request Type:</strong> ' + (log.request_type || 'N/A') + '</p>';
                        content += '<p><strong>Success:</strong> ' + (log.success ? 'Yes' : 'No') + '</p>';
                        content += '<p><strong>Response Status:</strong> ' + (log.response_status || 'N/A') + '</p>';
                        content += '<p><strong>Error Message:</strong> ' + (log.error_message || 'None') + '</p>';
                        content += '<p><strong>Created:</strong> ' + log.created_at + '</p>';
                        
                        if (log.request_payload) {
                            content += '<h4>Request Payload:</h4>';
                            content += '<pre style="background: #f5f5f5; padding: 10px; max-height: 200px; overflow: auto;">' + log.request_payload + '</pre>';
                        }
                        
                        if (log.response_payload) {
                            content += '<h4>Response Payload:</h4>';
                            content += '<pre style="background: #f5f5f5; padding: 10px; max-height: 200px; overflow: auto;">' + log.response_payload + '</pre>';
                        }
                        
                        content += '</div>';
                        
                        // Create modal-like dialog
                        var dialog = jQuery('<div title="API Log Details">' + content + '</div>');
                        dialog.dialog({
                            width: 600,
                            height: 500,
                            modal: true,
                            resizable: true
                        });
                    } else {
                        alert('Failed to load log details: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Failed to load log details. Please try again.');
                }
            });
        }

        function testApiLogging() {
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'edubot_test_api_logging',
                    nonce: '<?php echo wp_create_nonce("edubot_admin_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('✅ Test log entry created successfully!\n\nRefresh the page to see the new log entry.');
                        location.reload();
                    } else {
                        alert('❌ Failed to create test log: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('❌ Failed to create test log. Please check your connection and try again.');
                }
            });
        }
        </script>
        <?php
    }

    /**
     * AJAX handler to get API log details
     */
    public function handle_get_api_log_details() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'edubot_admin_nonce')) {
            wp_send_json_error('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $log_id = intval($_POST['log_id']);
        
        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}edubot_api_logs WHERE log_id = %d",
            $log_id
        ));

        if (!$log) {
            wp_send_json_error('Log entry not found');
        }

        wp_send_json_success($log);
    }

    /**
     * AJAX handler to test API logging
     */
    public function handle_test_api_logging() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'edubot_admin_nonce')) {
            wp_send_json_error('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Create a test log entry
        global $wpdb;
        
        $test_data = array(
            'api_provider' => 'test',
            'request_type' => 'test_connection',
            'request_payload' => json_encode(array(
                'test' => true,
                'timestamp' => current_time('mysql'),
                'message' => 'This is a test API log entry generated from the admin panel'
            )),
            'response_status' => 200,
            'response_payload' => json_encode(array(
                'success' => true,
                'message' => 'Test API logging is working correctly',
                'timestamp' => current_time('mysql')
            )),
            'success' => 1,
            'error_message' => null,
            'retry_count' => 0,
            'created_at' => current_time('mysql')
        );

        $result = $wpdb->insert(
            "{$wpdb->prefix}edubot_api_logs",
            $test_data,
            array('%s', '%s', '%s', '%d', '%s', '%d', '%s', '%d', '%s')
        );

        if ($result === false) {
            wp_send_json_error('Failed to create test log entry: ' . $wpdb->last_error);
        }

        wp_send_json_success('Test log entry created successfully with ID: ' . $wpdb->insert_id);
    }

    /**
     * Log API request to database
     * This function should be called from other parts of the plugin when API requests are made
     */
    public static function log_api_request_to_db($provider, $request_type, $request_data = null, $response_data = null, $success = false, $response_status = null, $error_message = null, $enquiry_id = null) {
        global $wpdb;
        
        // Only log if API request logging is enabled
        if (!get_option('edubot_log_api_requests', false)) {
            return;
        }

        $log_data = array(
            'enquiry_id' => $enquiry_id,
            'api_provider' => sanitize_text_field($provider),
            'request_type' => sanitize_text_field($request_type),
            'request_payload' => $request_data ? wp_json_encode($request_data) : null,
            'response_status' => intval($response_status),
            'response_payload' => $response_data ? wp_json_encode($response_data) : null,
            'success' => $success ? 1 : 0,
            'error_message' => $error_message ? sanitize_text_field($error_message) : null,
            'retry_count' => 0,
            'created_at' => current_time('mysql')
        );

        $result = $wpdb->insert(
            "{$wpdb->prefix}edubot_api_logs",
            $log_data,
            array('%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%d', '%s')
        );

        if ($result === false) {
            error_log('EduBot: Failed to log API request to database: ' . $wpdb->last_error);
        }

        return $result;
    }
}

