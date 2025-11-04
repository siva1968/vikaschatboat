<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 */
class EduBot_Core {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        if (defined('EDUBOT_PRO_VERSION')) {
            $this->version = EDUBOT_PRO_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'edubot-pro';

        $this->load_dependencies();
        $this->init_error_handler();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Initialize error handler
     */
    private function init_error_handler() {
        if (class_exists('EduBot_Error_Handler')) {
            EduBot_Error_Handler::init();
        }
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        
        // Define required files with their paths
        $required_files = array(
            'includes/class-edubot-loader.php',
            'includes/class-edubot-i18n.php',
            'admin/class-edubot-admin.php',
            'public/class-edubot-public.php',
            'includes/class-school-config.php',
            'includes/class-database-manager.php',
            'includes/class-security-manager.php',
            'includes/class-chatbot-engine.php',
            'includes/class-api-integrations.php',
            'includes/class-notification-manager.php',
            'includes/class-branding-manager.php',
            'includes/class-edubot-shortcode.php',
            'includes/class-edubot-health-check.php',
            'includes/class-edubot-autoloader.php',
            'includes/class-enquiries-migration.php',
            'includes/class-visitor-analytics.php',
            'includes/class-rate-limiter.php',
            'includes/class-edubot-logger.php',
            'includes/class-edubot-error-handler.php'
        );

        $missing_files = array();

        // Load each file with existence check
        foreach ($required_files as $file) {
            $file_path = EDUBOT_PRO_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                $missing_files[] = $file;
            }
        }

        // Show admin notice if files are missing
        if (!empty($missing_files)) {
            add_action('admin_notices', function() use ($missing_files) {
                if (current_user_can('activate_plugins')) {
                    echo '<div class="notice notice-error"><p><strong>' . esc_html__('EduBot Pro Error:', 'edubot-pro') . '</strong> ' . esc_html__('Missing required files:', 'edubot-pro') . '<br>';
                    foreach ($missing_files as $file) {
                        echo '• ' . esc_html($file) . '<br>';
                    }
                    echo esc_html__('Please ensure all plugin files are uploaded correctly.', 'edubot-pro') . '</p></div>';
                }
            });
        }

        // Only instantiate loader if the class exists
        if (class_exists('EduBot_Loader')) {
            $this->loader = new EduBot_Loader();
        }
    }

    /**
     * Define the locale for this plugin for internationalization.
     */
    private function set_locale() {
        if (class_exists('EduBot_i18n') && isset($this->loader)) {
            $plugin_i18n = new EduBot_i18n();
            $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
        }
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     */
    private function define_admin_hooks() {
        if (class_exists('EduBot_Admin') && isset($this->loader)) {
            $plugin_admin = new EduBot_Admin($this->get_plugin_name(), $this->get_version());

            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
            $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
            $this->loader->add_action('admin_init', $plugin_admin, 'admin_init');
            
            // AJAX hooks for admin
            $this->loader->add_action('wp_ajax_edubot_test_api', $plugin_admin, 'test_api_connection');
            $this->loader->add_action('wp_ajax_edubot_save_settings', $plugin_admin, 'save_settings');
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     */
    private function define_public_hooks() {
        if (class_exists('EduBot_Public') && isset($this->loader)) {
            $plugin_public = new EduBot_Public($this->get_plugin_name(), $this->get_version());

            $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
            $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
            $this->loader->add_action('wp_footer', $plugin_public, 'render_chatbot');
            
            // AJAX hooks for public
            $this->loader->add_action('wp_ajax_edubot_chatbot', $plugin_public, 'handle_chatbot_request');
            $this->loader->add_action('wp_ajax_nopriv_edubot_chatbot', $plugin_public, 'handle_chatbot_request');
            
            // Shortcode support
            $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        }
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        if (isset($this->loader) && method_exists($this->loader, 'run')) {
            $this->loader->run();
        }
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
