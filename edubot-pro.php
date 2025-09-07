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
 * Version:           1.3.1
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
define('EDUBOT_PRO_VERSION', '1.3.1');

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
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_edubot_pro() {

    $plugin = new EduBot_Core();
    $plugin->run();

}
run_edubot_pro();
