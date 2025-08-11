<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET['action'] so it is the plugin name
 * - Do the actual work
 *
 * @package EdubotPro
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if the current user has the capability to uninstall plugins
if (!current_user_can('activate_plugins')) {
    return;
}

// Check that the plugin is being uninstalled
check_admin_referer('bulk-plugins');

// Make sure we're really uninstalling EduBot Pro
if (__FILE__ != WP_UNINSTALL_PLUGIN) {
    return;
}

/**
 * Only remove ALL plugin data if EDUBOT_PRO_REMOVE_ALL_DATA constant is set to true
 * This prevents accidental data loss during plugin deactivation/reactivation
 */
if (defined('EDUBOT_PRO_REMOVE_ALL_DATA') && EDUBOT_PRO_REMOVE_ALL_DATA) {
    
    global $wpdb;
    
    // Define table names
    $tables_to_remove = array(
        $wpdb->prefix . 'edubot_conversations',
        $wpdb->prefix . 'edubot_applications',
        $wpdb->prefix . 'edubot_analytics',
        $wpdb->prefix . 'edubot_schools',
        $wpdb->prefix . 'edubot_notifications'
    );
    
    // Remove all plugin tables
    foreach ($tables_to_remove as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
    
    // Remove all plugin options
    $options_to_remove = array(
        'edubot_pro_settings',
        'edubot_pro_school_config',
        'edubot_pro_academic_config',
        'edubot_pro_api_settings',
        'edubot_pro_form_settings',
        'edubot_pro_branding_settings',
        'edubot_pro_notification_settings',
        'edubot_pro_analytics_settings',
        'edubot_pro_security_settings',
        'edubot_pro_version',
        'edubot_pro_db_version',
        'edubot_pro_activation_date',
        'edubot_pro_first_activation'
    );
    
    foreach ($options_to_remove as $option) {
        delete_option($option);
        
        // For multisite, also remove from site options
        if (is_multisite()) {
            delete_site_option($option);
        }
    }
    
    // Remove all transients (cached data)
    $transients_to_remove = array(
        'edubot_pro_analytics_cache',
        'edubot_pro_conversation_cache',
        'edubot_pro_application_stats',
        'edubot_pro_school_stats',
        'edubot_pro_api_status',
        'edubot_pro_system_check'
    );
    
    foreach ($transients_to_remove as $transient) {
        delete_transient($transient);
        
        // For multisite
        if (is_multisite()) {
            delete_site_transient($transient);
        }
    }
    
    // Remove user meta data
    $user_meta_keys = array(
        'edubot_pro_user_preferences',
        'edubot_pro_last_seen',
        'edubot_pro_conversation_count',
        'edubot_pro_admin_notices_dismissed'
    );
    
    foreach ($user_meta_keys as $meta_key) {
        delete_metadata('user', 0, $meta_key, '', true);
    }
    
    // Remove post meta (if any posts were created by the plugin)
    delete_post_meta_by_key('_edubot_pro_related');
    delete_post_meta_by_key('_edubot_pro_chatbot_enabled');
    
    // Remove uploaded files (if any)
    $upload_dir = wp_upload_dir();
    $edubot_upload_dir = $upload_dir['basedir'] . '/edubot-pro';
    
    if (is_dir($edubot_upload_dir)) {
        // Remove directory and all its contents
        array_map('unlink', glob("$edubot_upload_dir/*.*"));
        rmdir($edubot_upload_dir);
    }
    
    // Remove any scheduled cron jobs
    $cron_jobs = array(
        'edubot_pro_daily_cleanup',
        'edubot_pro_weekly_analytics',
        'edubot_pro_send_notifications',
        'edubot_pro_backup_data',
        'edubot_pro_cleanup_old_conversations',
        'edubot_pro_send_scheduled_followups'
    );
    
    foreach ($cron_jobs as $hook) {
        wp_clear_scheduled_hook($hook);
    }
    
    // Remove custom capabilities (if any were added)
    $capabilities = array(
        'manage_edubot_pro',
        'view_edubot_analytics',
        'manage_edubot_applications'
    );
    
    $roles = array('administrator', 'editor', 'author');
    
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            foreach ($capabilities as $cap) {
                $role->remove_cap($cap);
            }
        }
    }
    
    // Clean up any remaining plugin-specific database entries
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'edubot_pro_%'");
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'edubot_pro_%'");
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_edubot_pro_%'");
    
    // If multisite, clean up site options
    if (is_multisite()) {
        $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE 'edubot_pro_%'");
    }
    
    // Log the uninstallation (for debugging purposes)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('EduBot Pro: Complete uninstallation completed at ' . current_time('mysql'));
    }
    
    // Clear any object cache
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
} else {
    
    /**
     * If EDUBOT_PRO_REMOVE_ALL_DATA is not set, we only remove temporary data
     * This is the safer default behavior
     */
    
    // Only remove transients and temporary data
    $temp_transients = array(
        'edubot_pro_analytics_cache',
        'edubot_pro_conversation_cache',
        'edubot_pro_api_status',
        'edubot_pro_system_check'
    );
    
    foreach ($temp_transients as $transient) {
        delete_transient($transient);
        if (is_multisite()) {
            delete_site_transient($transient);
        }
    }
    
    // Clear scheduled events
    wp_clear_scheduled_hook('edubot_pro_daily_cleanup');
    
    // Log the partial cleanup
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('EduBot Pro: Plugin deactivated, temporary data cleared. Set EDUBOT_PRO_REMOVE_ALL_DATA to true for complete removal.');
    }
}

// Final cleanup
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}
