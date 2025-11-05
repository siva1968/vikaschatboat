<?php
/**
 * Check if the delete AJAX action is being called
 */

// Load WordPress
require_once(__DIR__ . '/wp-load.php');

// Log all AJAX actions
add_action('wp_ajax_edubot_delete_application', function() {
    error_log('✅✅✅ AJAX ACTION CALLED: wp_ajax_edubot_delete_application');
    wp_send_json_success(array('test' => 'The action was called!'));
}, -999);

// Check if WordPress is loaded
if (defined('WP_DEBUG_LOG')) {
    error_log('✅ WordPress loaded, WP_DEBUG_LOG enabled');
    echo "WordPress is loaded and logging is enabled";
} else {
    echo "WordPress loaded but WP_DEBUG_LOG not enabled";
}

// If this is an AJAX request, trigger it
if (defined('DOING_AJAX') && DOING_AJAX) {
    error_log('✅ DOING_AJAX is true');
    do_action('wp_ajax_' . $_REQUEST['action']);
}

?>
