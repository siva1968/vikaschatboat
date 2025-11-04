<?php

/**
 * Fired during plugin deactivation
 */
class EduBot_Deactivator {

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        self::clear_scheduled_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Clear scheduled cron events
     */
    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook('edubot_daily_cleanup');
        wp_clear_scheduled_hook('edubot_follow_up_check');
        wp_clear_scheduled_hook('edubot_analytics_cleanup');
    }
}
