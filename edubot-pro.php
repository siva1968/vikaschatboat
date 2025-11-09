<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://example.com
 * @since             1.0.0
 * @package           EduBot_Pro
 *
 * @wordpress-plugin
 * Plugin Name:       EduBot Pro
 * Plugin URI:        https://example.com/edubot-pro
 * Description:       Advanced AI-powered educational chatbot for WordPress with enhanced conversational flow and multi-institutional support.
 * Version:           1.4.2
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       edubot-pro
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('EDUBOT_PRO_VERSION', '1.4.2');

/**
 * CRITICAL: Capture UTM to cookies IMMEDIATELY in plugin bootstrap
 * This runs BEFORE any hooks, ensuring setcookie() works
 */
if (!function_exists('edubot_capture_utm_immediately')) {
    function edubot_capture_utm_immediately() {
        // Only if GET has parameters
        if (!empty($_GET)) {
            $utm_params = array(
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
                'gclid', 'fbclid', 'msclkid', 'ttclid', 'twclid', 
                '_kenshoo_clickid', 'irclickid', 'li_fat_id', 'sc_click_id', 'yclid'
            );
            
            $cookie_lifetime = time() + (30 * 24 * 60 * 60); // 30 days
            $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            
            $cookies_set = 0;
            
            foreach ($utm_params as $param) {
                if (isset($_GET[$param]) && !empty($_GET[$param])) {
                    $value = sanitize_text_field($_GET[$param]);
                    
                    if (@setcookie("edubot_{$param}", $value, $cookie_lifetime, '/', $domain, $secure, true)) {
                        $cookies_set++;
                        error_log("EduBot Bootstrap: Set cookie edubot_{$param} = {$value}");
                    }
                }
            }
            
            if ($cookies_set > 0) {
                error_log("EduBot Bootstrap: Successfully set {$cookies_set} UTM cookies");
            }
        }
    }
    
    // Call immediately - before WordPress does anything
    edubot_capture_utm_immediately();
}

/**
 * Plugin file and path constants
 */
define('EDUBOT_PRO_PLUGIN_FILE', __FILE__);
define('EDUBOT_PRO_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EDUBOT_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-edubot-activator.php
 */
function activate_edubot_pro() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-edubot-activator.php';
    EduBot_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-edubot-deactivator.php
 */
function deactivate_edubot_pro() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-edubot-deactivator.php';
    EduBot_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_edubot_pro');
register_deactivation_hook(__FILE__, 'deactivate_edubot_pro');

/**
 * Load plugin constants first
 */
require plugin_dir_path(__FILE__) . 'includes/class-edubot-constants.php';

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-edubot-core.php';

/**
 * Load applications table fixer to ensure table exists
 */
require plugin_dir_path(__FILE__) . 'includes/class-applications-table-fixer.php';

/**
 * Load MCB (MyClassBoard) sync service and integration
 */
require plugin_dir_path(__FILE__) . 'includes/class-edubot-mcb-service.php';
require plugin_dir_path(__FILE__) . 'includes/class-edubot-mcb-integration.php';
require plugin_dir_path(__FILE__) . 'includes/class-edubot-mcb-admin.php';

/**
 * Check plugin requirements before activation
 */
function edubot_pro_check_requirements() {
    // Check WordPress version
    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('EduBot Pro requires WordPress 5.0 or higher. Please update WordPress.', 'edubot-pro'),
            __('Plugin Activation Error', 'edubot-pro'),
            array('back_link' => true)
        );
    }
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('EduBot Pro requires PHP 7.4 or higher. Current version: ', 'edubot-pro') . PHP_VERSION,
            __('Plugin Activation Error', 'edubot-pro'),
            array('back_link' => true)
        );
    }
    
    // Check required PHP extensions
    $required_extensions = array('json', 'mbstring');
    $missing_extensions = array();
    
    foreach ($required_extensions as $extension) {
        if (!extension_loaded($extension)) {
            $missing_extensions[] = $extension;
        }
    }
    
    if (!empty($missing_extensions)) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('EduBot Pro requires the following PHP extensions: ', 'edubot-pro') . implode(', ', $missing_extensions),
            __('Plugin Activation Error', 'edubot-pro'),
            array('back_link' => true)
        );
    }
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_edubot_pro() {
    try {
        // Check requirements
        edubot_pro_check_requirements();
        
        $plugin = new EduBot_Core();
        $plugin->run();
    } catch (Exception $e) {
        // Log the error
        error_log('EduBot Pro Fatal Error: ' . $e->getMessage());
        
        // Show admin notice
        add_action('admin_notices', function() use ($e) {
            if (current_user_can('activate_plugins')) {
                echo '<div class="notice notice-error"><p><strong>' . esc_html__('EduBot Pro Error:', 'edubot-pro') . '</strong> ' . esc_html($e->getMessage()) . '</p></div>';
            }
        });
    }
}

// Only run if not in admin and all dependencies are loaded
if (!is_admin() || (is_admin() && !wp_doing_ajax())) {
    add_action('plugins_loaded', 'run_edubot_pro');
} else {
    run_edubot_pro();
}
