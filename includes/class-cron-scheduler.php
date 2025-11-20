<?php
/**
 * WP-Cron Scheduler for Reports
 * 
 * Sets up and manages WP-Cron schedules for automated reports
 * 
 * @since 1.3.4
 * @package EduBot_Pro
 * @subpackage Reports
 */

if (!defined('ABSPATH')) {
    exit;
}

class EduBot_Cron_Scheduler {
    
    /**
     * Initialize scheduler
     * 
     * @since 1.3.4
     * @return void
     */
    public static function init() {
        add_action('wp_schedule_event', [self::class, 'on_schedule_event']);
        add_action('wp_before_cron_exec', [self::class, 'on_before_cron_exec']);
    }
    
    /**
     * Setup cron schedules on activation
     * 
     * @since 1.3.4
     * @return void
     */
    public static function setup_on_activation() {
        // Add weekly and monthly schedules
        self::add_custom_schedules();
        
        // Get reports instance
        $reports = EduBot_Performance_Reports::get_instance();
        
        // Enable and schedule if needed
        if (get_option('edubot_daily_report_enabled', false)) {
            $reports->schedule_daily_report();
        }
        
        if (get_option('edubot_weekly_report_enabled', false)) {
            $reports->schedule_weekly_report();
        }
        
        if (get_option('edubot_monthly_report_enabled', false)) {
            $reports->schedule_monthly_report();
        }
    }
    
    /**
     * Add custom cron schedules
     * 
     * @since 1.3.4
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public static function add_custom_schedules($schedules = []) {
        // Add weekly schedule (every 7 days)
        $schedules['weekly'] = [
            'interval' => WEEK_IN_SECONDS,
            'display' => __('Weekly', 'edubot-pro')
        ];
        
        // Add monthly schedule (every 30 days)
        $schedules['monthly'] = [
            'interval' => 30 * DAY_IN_SECONDS,
            'display' => __('Monthly', 'edubot-pro')
        ];
        
        return $schedules;
    }
    
    /**
     * Cleanup cron on deactivation
     * 
     * @since 1.3.4
     * @return void
     */
    public static function cleanup_on_deactivation() {
        wp_clear_scheduled_hook('wp_edubot_daily_report');
        wp_clear_scheduled_hook('wp_edubot_weekly_report');
        wp_clear_scheduled_hook('wp_edubot_monthly_report');
    }
    
    /**
     * Handle schedule event logging
     * 
     * @since 1.3.4
     * @param string $event Event name
     * @return void
     */
    public static function on_schedule_event($event) {
        if (in_array($event, ['wp_edubot_daily_report', 'wp_edubot_weekly_report', 'wp_edubot_monthly_report'])) {
            do_action('edubot_cron_scheduled', $event);
        }
    }
    
    /**
     * Handle before cron execution
     * 
     * @since 1.3.4
     * @return void
     */
    public static function on_before_cron_exec() {
        // Log cron execution start
        do_action('edubot_cron_start');
    }
    
    /**
     * Get next scheduled time for report
     * 
     * @since 1.3.4
     * @param string $report_type Report type (daily, weekly, monthly)
     * @return int|false Next scheduled time or false
     */
    public static function get_next_scheduled($report_type) {
        $hook_map = [
            'daily' => 'wp_edubot_daily_report',
            'weekly' => 'wp_edubot_weekly_report',
            'monthly' => 'wp_edubot_monthly_report'
        ];
        
        $hook = $hook_map[$report_type] ?? null;
        if (!$hook) {
            return false;
        }
        
        return wp_next_scheduled($hook);
    }
    
    /**
     * Get all scheduled reports
     * 
     * @since 1.3.4
     * @return array Array of scheduled reports
     */
    public static function get_all_scheduled() {
        return [
            'daily' => [
                'enabled' => get_option('edubot_daily_report_enabled', false),
                'next_run' => self::get_next_scheduled('daily'),
                'time' => get_option('edubot_daily_report_time', '06:00')
            ],
            'weekly' => [
                'enabled' => get_option('edubot_weekly_report_enabled', false),
                'next_run' => self::get_next_scheduled('weekly'),
                'day' => get_option('edubot_weekly_report_day', 1),
                'time' => get_option('edubot_weekly_report_time', '08:00')
            ],
            'monthly' => [
                'enabled' => get_option('edubot_monthly_report_enabled', false),
                'next_run' => self::get_next_scheduled('monthly'),
                'day' => get_option('edubot_monthly_report_day', 1),
                'time' => get_option('edubot_monthly_report_time', '09:00')
            ]
        ];
    }
}

// Register custom schedules
add_filter('cron_schedules', [EduBot_Cron_Scheduler::class, 'add_custom_schedules']);

// Initialize on admin
if (is_admin()) {
    EduBot_Cron_Scheduler::init();
}
