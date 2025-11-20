<?php
/**
 * MyClassBoard Integration Initialization
 * 
 * Quick activation file for MyClassBoard integration
 * 
 * @since 1.5.0
 * @package EduBot_Pro
 * @subpackage Integrations
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize MyClassBoard integration when plugin loads
add_action('plugins_loaded', function() {
    // Load the setup class which handles all initialization
    require_once plugin_dir_path(__FILE__) . 'class-mcb-integration-setup.php';
    
    // Initialize the setup
    EduBot_MCB_Integration_Setup::init();
});
