<?php
/**
 * Plugin Name: EduBot Pro
 * Plugin URI: https://edubotpro.com
 * Description: AI-powered chatbot for educational institutions with multi-school support, white-label branding, and comprehensive application management.
 * Version: 1.1.0
 * Author: EduBot Pro Team
 * Author URI: https://edubotpro.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: edubot-pro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package EdubotPro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EDUBOT_PRO_VERSION', '1.1.0');
define('EDUBOT_PRO_PLUGIN_FILE', __FILE__);
define('EDUBOT_PRO_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('EDUBOT_PRO_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EDUBOT_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EDUBOT_PRO_PLUGIN_DIR', dirname(__FILE__));

// Include additional constants
if (file_exists(EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-constants.php')) {
    require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-constants.php';
}

// Include the autoloader first
if (file_exists(EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-autoloader.php')) {
    require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-autoloader.php';
    EduBot_Autoloader::register();
    
    // Initialize error handler
    if (class_exists('EduBot_Error_Handler')) {
        EduBot_Error_Handler::init();
    }
}

// Validate that all required classes can be loaded
$missing_classes = EduBot_Autoloader::validate_classes();
if (!empty($missing_classes)) {
    add_action('admin_notices', function() use ($missing_classes) {
        if (current_user_can('activate_plugins')) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>' . esc_html__('EduBot Pro Error:', 'edubot-pro') . '</strong> ';
            echo esc_html__('Critical files are missing:', 'edubot-pro') . '<br>';
            foreach ($missing_classes as $class) {
                echo '• ' . esc_html($class) . '<br>';
            }
            echo esc_html__('Please reinstall the plugin.', 'edubot-pro');
            echo '</p></div>';
        }
    });
    return; // Stop execution if critical classes are missing
}

/**
 * Plugin activation hook
 * Handles database table creation and initial setup
 */
function activate_edubot_pro() {
    if (class_exists('EduBot_Activator')) {
        EduBot_Activator::activate();
    } else {
        error_log('EduBot Pro Activation Error: EduBot_Activator class not found');
    }
}
register_activation_hook(__FILE__, 'activate_edubot_pro');

/**
 * Plugin deactivation hook
 * Cleans up scheduled events and temporary data
 */
function deactivate_edubot_pro() {
    if (class_exists('EduBot_Deactivator')) {
        EduBot_Deactivator::deactivate();
    } else {
        error_log('EduBot Pro Deactivation Error: EduBot_Deactivator class not found');
    }
}
register_deactivation_hook(__FILE__, 'deactivate_edubot_pro');

/**
 * Initialize the plugin
 * Creates and runs the main plugin instance with proper error handling
 */
function run_edubot_pro() {
    // Check for required classes before initialization
    $required_classes = array(
        'EduBot_Core' => 'Core plugin functionality',
        'EduBot_Database_Manager' => 'Database operations',
        'EduBot_Security_Manager' => 'Security features',
        'EduBot_Loader' => 'Hook management'
    );
    
    $missing_classes = array();
    foreach ($required_classes as $class => $description) {
        if (!class_exists($class)) {
            $missing_classes[] = $class . ' (' . $description . ')';
        }
    }
    
    // Display error notice if critical classes are missing
    if (!empty($missing_classes)) {
        add_action('admin_notices', function() use ($missing_classes) {
            if (current_user_can('activate_plugins')) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>' . esc_html__('EduBot Pro Error:', 'edubot-pro') . '</strong> ';
                echo esc_html__('Missing critical classes. Plugin cannot function properly:', 'edubot-pro') . '<br>';
                foreach ($missing_classes as $class) {
                    echo '• ' . esc_html($class) . '<br>';
                }
                echo esc_html__('Please reinstall the plugin or contact support.', 'edubot-pro');
                echo '</p></div>';
            }
        });
        return;
    }
    
    // Initialize the plugin if all classes are available
    if (class_exists('EduBot_Core')) {
        try {
            $plugin = new EduBot_Core();
            $plugin->run();
            
            // Initialize analytics if available
            if (class_exists('EduBot_Visitor_Analytics')) {
                new EduBot_Visitor_Analytics();
            }
            
            if (class_exists('EduBot_Analytics_AJAX')) {
                new EduBot_Analytics_AJAX();
            }
            
        } catch (Exception $e) {
            error_log('EduBot Pro Initialization Error: ' . $e->getMessage());
            add_action('admin_notices', function() use ($e) {
                if (current_user_can('activate_plugins')) {
                    echo '<div class="notice notice-error"><p>';
                    echo '<strong>' . esc_html__('EduBot Pro Error:', 'edubot-pro') . '</strong> ';
                    echo esc_html__('Plugin initialization failed. Please check error logs.', 'edubot-pro');
                    echo '</p></div>';
                }
            });
        }
    } else {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>EduBot Pro Error:</strong> Core class not found. Please check plugin installation.';
            echo '</p></div>';
        });
    }
}

// Start the plugin
run_edubot_pro();

/**
 * Add plugin action links
 * Adds Settings and Support links to the plugins page
 */
function edubot_pro_action_links($links) {
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=edubot-pro') . '">' . __('Settings', 'edubot-pro') . '</a>',
        '<a href="https://edubotpro.com/support" target="_blank">' . __('Support', 'edubot-pro') . '</a>',
        '<a href="https://edubotpro.com/docs" target="_blank">' . __('Documentation', 'edubot-pro') . '</a>'
    );
    
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . EDUBOT_PRO_PLUGIN_BASENAME, 'edubot_pro_action_links');

/**
 * Add plugin meta links
 * Adds additional links to the plugin description area
 */
function edubot_pro_meta_links($links, $file) {
    if ($file === EDUBOT_PRO_PLUGIN_BASENAME) {
        $links[] = '<a href="https://edubotpro.com/changelog" target="_blank">' . __('Changelog', 'edubot-pro') . '</a>';
        $links[] = '<a href="https://edubotpro.com/roadmap" target="_blank">' . __('Roadmap', 'edubot-pro') . '</a>';
    }
    
    return $links;
}
add_filter('plugin_row_meta', 'edubot_pro_meta_links', 10, 2);

/**
 * Check for plugin updates
 * Custom update checker for premium plugin
 */
function edubot_pro_check_for_updates() {
    // This would typically connect to a licensing server
    // For now, it's a placeholder for future update functionality
    if (current_user_can('update_plugins')) {
        $current_version = get_option('edubot_pro_version', '0.0.0');
        if (version_compare($current_version, EDUBOT_PRO_VERSION, '<')) {
            update_option('edubot_pro_version', EDUBOT_PRO_VERSION);
            // Trigger any update routines here
            do_action('edubot_pro_updated', $current_version, EDUBOT_PRO_VERSION);
        }
    }
}
add_action('admin_init', 'edubot_pro_check_for_updates');

/**
 * Load plugin textdomain
 * Enables internationalization support
 */
function edubot_pro_load_textdomain() {
    load_plugin_textdomain(
        'edubot-pro',
        false,
        dirname(EDUBOT_PRO_PLUGIN_BASENAME) . '/languages/'
    );
}
add_action('init', 'edubot_pro_load_textdomain');
add_action('plugins_loaded', 'edubot_pro_load_textdomain');

/**
 * Plugin compatibility and health check
 * Ensures WordPress and PHP version requirements are met
 */
function edubot_pro_compatibility_check() {
    global $wp_version;
    
    $wp_required = '5.0';
    $php_required = '7.4';
    
    if (version_compare($wp_version, $wp_required, '<')) {
        deactivate_plugins(EDUBOT_PRO_PLUGIN_BASENAME);
        wp_die(sprintf(
            __('EduBot Pro requires WordPress %s or higher. You are running WordPress %s. Please upgrade WordPress to activate this plugin.', 'edubot-pro'),
            $wp_required,
            $wp_version
        ));
    }
    
    if (version_compare(PHP_VERSION, $php_required, '<')) {
        deactivate_plugins(EDUBOT_PRO_PLUGIN_BASENAME);
        wp_die(sprintf(
            __('EduBot Pro requires PHP %s or higher. You are running PHP %s. Please upgrade PHP to activate this plugin.', 'edubot-pro'),
            $php_required,
            PHP_VERSION
        ));
    }
    
    // Run health check for admin users
    if (is_admin() && current_user_can('manage_options') && class_exists('EduBot_Health_Check')) {
        $health = EduBot_Health_Check::get_health_status();
        if ($health['status'] === 'critical') {
            add_action('admin_notices', function() use ($health) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>' . esc_html__('EduBot Pro Health Check Failed:', 'edubot-pro') . '</strong> ';
                echo esc_html($health['message']);
                echo '</p></div>';
            });
        }
    }
}
add_action('admin_init', 'edubot_pro_compatibility_check');

/**
 * Add admin notices
 * Shows important messages to administrators
 */
function edubot_pro_admin_notices() {
    // Check if OpenAI API key is configured
    $openai_key = get_option('edubot_openai_api_key');
    if (empty($openai_key) && current_user_can('manage_options')) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>' . __('EduBot Pro', 'edubot-pro') . '</strong>: ';
        echo sprintf(
            __('Please configure your OpenAI API key to enable AI chatbot functionality. <a href="%s">Configure now</a>', 'edubot-pro'),
            admin_url('admin.php?page=edubot-pro&tab=api-settings')
        );
        echo '</p>';
        echo '</div>';
    }
    
    // Check if at least one school is configured
    global $wpdb;
    $table_name = $wpdb->prefix . 'edubot_school_configs';
    
    // Check if table exists first
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        $school_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'active'");
        if ($school_count == 0 && current_user_can('manage_options')) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>' . __('EduBot Pro', 'edubot-pro') . '</strong>: ';
            echo sprintf(
                __('Get started by adding your first school configuration. <a href="%s">Add school now</a>', 'edubot-pro'),
                admin_url('admin.php?page=edubot-pro')
            );
            echo '</p>';
            echo '</div>';
        }
    }
}
add_action('admin_notices', 'edubot_pro_admin_notices');

/**
 * Register custom post types and taxonomies
 * Creates additional content types if needed
 */
function edubot_pro_register_post_types() {
    // This could be used for creating custom post types for
    // application forms, testimonials, etc. if needed in the future
    do_action('edubot_pro_register_post_types');
}
add_action('init', 'edubot_pro_register_post_types');

/**
 * Enqueue admin scripts and styles
 * Loads necessary assets for the admin interface
 */
function edubot_pro_admin_enqueue_scripts($hook) {
    // Only load on EduBot Pro admin pages
    if (strpos($hook, 'edubot-pro') === false) {
        return;
    }
    
    // Check if CSS file exists before enqueuing
    $admin_css_path = EDUBOT_PRO_PLUGIN_PATH . 'admin/css/edubot-admin.css';
    if (file_exists($admin_css_path)) {
        wp_enqueue_style(
            'edubot-admin-css',
            EDUBOT_PRO_PLUGIN_URL . 'admin/css/edubot-admin.css',
            array(),
            EDUBOT_PRO_VERSION
        );
    }
    
    // Check if JS file exists before enqueuing
    $admin_js_path = EDUBOT_PRO_PLUGIN_PATH . 'admin/js/edubot-admin.js';
    if (file_exists($admin_js_path)) {
        wp_enqueue_script(
            'edubot-admin-js',
            EDUBOT_PRO_PLUGIN_URL . 'admin/js/edubot-admin.js',
            array('jquery', 'wp-color-picker'),
            EDUBOT_PRO_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('edubot-admin-js', 'edubot_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('edubot_admin_nonce'),
            'plugin_url' => EDUBOT_PRO_PLUGIN_URL,
            'version' => EDUBOT_PRO_VERSION,
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'edubot-pro'),
                'save_success' => __('Settings saved successfully!', 'edubot-pro'),
                'save_error' => __('Error saving settings. Please try again.', 'edubot-pro'),
                'test_connection' => __('Testing connection...', 'edubot-pro'),
                'connection_success' => __('Connection successful!', 'edubot-pro'),
                'connection_failed' => __('Connection failed. Please check your settings.', 'edubot-pro')
            )
        ));
    }
    
    // Enqueue WordPress media uploader
    if (!did_action('wp_enqueue_media')) {
        wp_enqueue_media();
    }
    
    // Enqueue color picker
    wp_enqueue_style('wp-color-picker');
}
add_action('admin_enqueue_scripts', 'edubot_pro_admin_enqueue_scripts');

/**
 * Enqueue public scripts and styles
 * Loads necessary assets for the frontend chatbot
 */
function edubot_pro_public_enqueue_scripts() {
    // Only load if chatbot is active and properly configured
    $openai_key = get_option('edubot_openai_api_key');
    if (empty($openai_key)) {
        return;
    }
    
    // Check if CSS file exists before enqueuing
    $public_css_path = EDUBOT_PRO_PLUGIN_PATH . 'public/css/edubot-public.css';
    if (file_exists($public_css_path)) {
        wp_enqueue_style(
            'edubot-public-css',
            EDUBOT_PRO_PLUGIN_URL . 'public/css/edubot-public.css',
            array(),
            EDUBOT_PRO_VERSION
        );
    }
    
    // Check if JS file exists before enqueuing
    $public_js_path = EDUBOT_PRO_PLUGIN_PATH . 'public/js/edubot-public.js';
    if (file_exists($public_js_path)) {
        wp_enqueue_script(
            'edubot-public-js',
            EDUBOT_PRO_PLUGIN_URL . 'public/js/edubot-public.js',
            array('jquery'),
            EDUBOT_PRO_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('edubot-public-js', 'edubot_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('edubot_public_nonce'),
            'plugin_url' => EDUBOT_PRO_PLUGIN_URL,
            'strings' => array(
                'error_message' => __('Sorry, something went wrong. Please try again.', 'edubot-pro'),
                'typing_indicator' => __('Bot is typing...', 'edubot-pro'),
                'connection_error' => __('Connection error. Please check your internet connection.', 'edubot-pro')
            )
        ));
    }
}
add_action('wp_enqueue_scripts', 'edubot_pro_public_enqueue_scripts');

/**
 * Add custom capabilities
 * Creates plugin-specific user capabilities
 */
function edubot_pro_add_capabilities() {
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('manage_edubot_pro');
        $admin_role->add_cap('view_edubot_analytics');
        $admin_role->add_cap('manage_edubot_schools');
        $admin_role->add_cap('export_edubot_data');
    }
    
    // Add capabilities to custom roles if they exist
    do_action('edubot_pro_add_capabilities');
}
add_action('admin_init', 'edubot_pro_add_capabilities');

/**
 * Schedule cron events
 * Sets up regular maintenance and notification tasks
 */
function edubot_pro_schedule_events() {
    // Only schedule if classes exist
    if (!class_exists('Edubot_Database_Manager') || !class_exists('Edubot_Notification_Manager')) {
        return;
    }
    
    // Schedule analytics cleanup (daily)
    if (!wp_next_scheduled('edubot_pro_cleanup_analytics')) {
        wp_schedule_event(time(), 'daily', 'edubot_pro_cleanup_analytics');
    }
    
    // Schedule follow-up notifications (hourly)
    if (!wp_next_scheduled('edubot_pro_send_followups')) {
        wp_schedule_event(time(), 'hourly', 'edubot_pro_send_followups');
    }
    
    // Schedule backup creation (weekly)
    if (!wp_next_scheduled('edubot_pro_create_backup')) {
        wp_schedule_event(time(), 'weekly', 'edubot_pro_create_backup');
    }
}
add_action('wp', 'edubot_pro_schedule_events');

/**
 * Handle cron events - only if classes exist
 */
if (class_exists('EduBot_Database_Manager')) {
    add_action('edubot_pro_cleanup_analytics', array('EduBot_Database_Manager', 'cron_cleanup_old_analytics'));
    add_action('edubot_pro_create_backup', array('EduBot_Database_Manager', 'create_backup'));
}

if (class_exists('EduBot_Notification_Manager')) {
    add_action('edubot_pro_send_followups', array('EduBot_Notification_Manager', 'send_scheduled_followups'));
}

/**
 * Plugin uninstall cleanup
 * Removes all plugin data when uninstalled (if option is enabled)
 */
function edubot_pro_uninstall_cleanup() {
    // Only run if user has chosen to remove all data on uninstall
    $remove_data = get_option('edubot_remove_data_on_uninstall', false);
    if ($remove_data) {
        global $wpdb;
        
        // Remove database tables
        $tables = array(
            $wpdb->prefix . 'edubot_school_configs',
            $wpdb->prefix . 'edubot_applications',
            $wpdb->prefix . 'edubot_analytics',
            $wpdb->prefix . 'edubot_chat_sessions'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Remove options
        $options = array(
            'edubot_openai_api_key',
            'edubot_whatsapp_settings',
            'edubot_email_settings',
            'edubot_sms_settings',
            'edubot_branding_settings',
            'edubot_security_settings',
            'edubot_remove_data_on_uninstall',
            'edubot_pro_version'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Clear any cached data
        wp_cache_flush();
    }
}

// Only register uninstall hook if file is included during uninstall
if (defined('WP_UNINSTALL_PLUGIN')) {
    edubot_pro_uninstall_cleanup();
}

/**
 * Manual database repair function
 * Can be called to create missing tables
 */
function edubot_pro_repair_database() {
    if (!class_exists('EduBot_Database_Manager')) {
        return array('success' => false, 'message' => 'Database manager not found');
    }
    
    $db_manager = new EduBot_Database_Manager();
    return $db_manager->ensure_tables_exist();
}

/**
 * Admin notice for database issues
 */
function edubot_pro_database_admin_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if we have database issues
    global $wpdb;
    
    $tables_to_check = array(
        'edubot_security_log',
        'edubot_visitor_analytics',
        'edubot_visitors'
    );
    
    $missing_tables = array();
    
    foreach ($tables_to_check as $table_name) {
        $full_table = $wpdb->prefix . $table_name;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'");
        
        if ($table_exists != $full_table) {
            $missing_tables[] = $table_name;
        }
    }
    
    if (!empty($missing_tables)) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>EduBot Pro:</strong> Missing database tables: <code>' . esc_html(implode(', ', $missing_tables)) . '</code></p>';
        echo '<p><a href="' . wp_nonce_url(admin_url('admin.php?page=edubot-pro&action=repair_database'), 'edubot_repair_db', 'nonce') . '" class="button button-primary">Repair Database</a></p>';
        echo '</div>';
    }
}

// Add admin notice hook
add_action('admin_notices', 'edubot_pro_database_admin_notice');

// Handle database repair action
add_action('admin_init', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'edubot-pro' && 
        isset($_GET['action']) && 
        ($_GET['action'] === 'repair_db' || $_GET['action'] === 'repair_database') && 
        current_user_can('manage_options')) {
        
        // Verify nonce if present
        if (isset($_GET['nonce']) && !wp_verify_nonce($_GET['nonce'], 'edubot_repair_db')) {
            wp_die('Security check failed');
        }
        
        $result = edubot_pro_repair_database();
        
        if ($result['success']) {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>EduBot Pro:</strong> ' . esc_html($result['message']) . '</p>';
                echo '</div>';
            });
        } else {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>EduBot Pro:</strong> Database repair failed. ' . esc_html($result['message']) . '</p>';
                echo '</div>';
            });
        }
    }
});
