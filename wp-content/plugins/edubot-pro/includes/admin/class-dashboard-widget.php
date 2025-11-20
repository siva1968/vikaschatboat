<?php
/**
 * Dashboard Widget - Quick Analytics Stats
 * 
 * Displays key analytics metrics on the WordPress dashboard
 * using the WP Dashboard widget API.
 * 
 * @since 1.4.0
 * @package EduBot_Pro
 * @subpackage Admin\Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

class EduBot_Dashboard_Widget {
    
    /**
     * Plugin instance
     * 
     * @var EduBot_Dashboard_Widget
     */
    private static $instance = null;
    
    /**
     * Logger instance
     * 
     * @var EduBot_Logger
     */
    private $logger;
    
    /**
     * Dashboard instance
     * 
     * @var EduBot_Admin_Dashboard
     */
    private $dashboard;
    
    /**
     * Get singleton instance
     * 
     * @param EduBot_Logger $logger Logger instance
     * @return EduBot_Dashboard_Widget
     */
    public static function get_instance($logger = null) {
        if (null === self::$instance) {
            self::$instance = new self($logger);
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     * 
     * @param EduBot_Logger $logger Logger instance
     */
    public function __construct($logger = null) {
        $this->logger = $logger;
        $this->dashboard = new EduBot_Admin_Dashboard($logger);
        
        if (is_admin()) {
            add_action('wp_dashboard_setup', [$this, 'register_widgets']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_widget_assets']);
            add_action('wp_ajax_edubot_widget_refresh', [$this, 'ajax_refresh_widget']);
        }
    }
    
    /**
     * Register dashboard widgets
     * 
     * @since 1.4.0
     * @return void
     */
    public function register_widgets() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Main analytics summary widget
        wp_add_dashboard_widget(
            'edubot_analytics_summary',
            'EduBot Analytics Summary',
            [$this, 'render_analytics_widget']
        );
        
        // Recent conversions widget
        wp_add_dashboard_widget(
            'edubot_recent_conversions',
            'Recent Conversions',
            [$this, 'render_recent_conversions_widget']
        );
        
        // Top channels widget
        wp_add_dashboard_widget(
            'edubot_top_channels',
            'Top Marketing Channels',
            [$this, 'render_top_channels_widget']
        );
    }
    
    /**
     * Render main analytics summary widget
     * 
     * @since 1.4.0
     * @return void
     */
    public function render_analytics_widget() {
        try {
            $stats = $this->dashboard->get_kpi_summary();
            
            if (empty($stats)) {
                echo '<p style="color: #666;">No data available yet. Analytics data will appear here once enquiries are tracked.</p>';
                return;
            }
        ?>
            <div class="edubot-widget-container">
                <div class="edubot-widget-stats">
                    <div class="stat-box">
                        <div class="stat-label">Total Enquiries</div>
                        <div class="stat-value"><?php echo esc_html($stats['total_conversions'] ?? 0); ?></div>
                        <div class="stat-period">This Month</div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-label">Top Channel</div>
                        <div class="stat-value" style="font-size: 14px;">
                            <?php 
                            if (!empty($stats['top_channel'])) {
                                echo esc_html($stats['top_channel']['channel']);
                            } else {
                                echo '—';
                            }
                            ?>
                        </div>
                        <div class="stat-period">By Volume</div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-label">Conversion Rate</div>
                        <div class="stat-value">
                            <?php 
                            if (!empty($stats['total_clicks']) && $stats['total_clicks'] > 0) {
                                $rate = ($stats['total_conversions'] / $stats['total_clicks']) * 100;
                                echo esc_html(number_format($rate, 2)) . '%';
                            } else {
                                echo '—';
                            }
                            ?>
                        </div>
                        <div class="stat-period">Last 30 days</div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-label">Avg. Session Time</div>
                        <div class="stat-value">
                            <?php 
                            if (!empty($stats['avg_session_duration'])) {
                                echo esc_html(gmdate('i:s', $stats['avg_session_duration']));
                            } else {
                                echo '—';
                            }
                            ?>
                        </div>
                        <div class="stat-period">Per Session</div>
                    </div>
                </div>
                
                <div class="edubot-widget-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=edubot-dashboard')); ?>" 
                       class="button button-primary">
                        View Full Dashboard
                    </a>
                    <button class="button button-secondary edubot-widget-refresh-btn" data-widget="analytics">
                        Refresh
                    </button>
                </div>
            </div>
        <?php
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->log_error('Dashboard widget error: ' . $e->getMessage());
            }
            echo '<p style="color: #d32f2f;">Error loading analytics data. Please check the plugin status.</p>';
        }
    }
    
    /**
     * Render recent conversions widget
     * 
     * @since 1.4.0
     * @return void
     */
    public function render_recent_conversions_widget() {
        try {
            global $wpdb;
            
            $table = $wpdb->prefix . 'edubot_conversions';
            $recent = $wpdb->get_results(
                "SELECT * FROM $table ORDER BY created_at DESC LIMIT 5",
                ARRAY_A
            );
            
            if (empty($recent)) {
                echo '<p style="color: #666;">No conversions recorded yet.</p>';
                return;
            }
        ?>
            <div class="edubot-widget-recent">
                <table class="edubot-widget-table">
                    <thead>
                        <tr>
                            <th>Channel</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $conversion) : ?>
                            <tr>
                                <td class="channel-cell">
                                    <span class="channel-badge"><?php echo esc_html($conversion['channel'] ?? 'Direct'); ?></span>
                                </td>
                                <td class="date-cell">
                                    <?php echo esc_html(date('M j, g:i a', strtotime($conversion['created_at']))); ?>
                                </td>
                                <td class="status-cell">
                                    <span class="status-badge status-<?php echo esc_attr($conversion['status'] ?? 'completed'); ?>">
                                        <?php echo esc_html(ucfirst($conversion['status'] ?? 'Completed')); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->log_error('Recent conversions widget error: ' . $e->getMessage());
            }
            echo '<p style="color: #d32f2f;">Error loading recent conversions.</p>';
        }
    }
    
    /**
     * Render top channels widget
     * 
     * @since 1.4.0
     * @return void
     */
    public function render_top_channels_widget() {
        try {
            global $wpdb;
            
            $table = $wpdb->prefix . 'edubot_conversions';
            $channels = $wpdb->get_results(
                "SELECT channel, COUNT(*) as count FROM $table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY channel ORDER BY count DESC LIMIT 5",
                ARRAY_A
            );
            
            if (empty($channels)) {
                echo '<p style="color: #666;">No channel data available.</p>';
                return;
            }
            
            $total = array_sum(array_column($channels, 'count'));
        ?>
            <div class="edubot-widget-channels">
                <?php foreach ($channels as $channel) : 
                    $percentage = ($channel['count'] / $total) * 100;
                ?>
                    <div class="channel-item">
                        <div class="channel-name">
                            <span><?php echo esc_html($channel['channel']); ?></span>
                            <span class="channel-count"><?php echo esc_html($channel['count']); ?></span>
                        </div>
                        <div class="channel-bar">
                            <div class="channel-progress" style="width: <?php echo esc_attr($percentage); ?>%">
                                <span class="channel-percentage"><?php echo number_format($percentage, 1); ?>%</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->log_error('Top channels widget error: ' . $e->getMessage());
            }
            echo '<p style="color: #d32f2f;">Error loading channel data.</p>';
        }
    }
    
    /**
     * Enqueue widget assets (CSS and JS)
     * 
     * @since 1.4.0
     * @return void
     */
    public function enqueue_widget_assets() {
        if (!function_exists('get_current_screen')) {
            return;
        }
        
        $screen = get_current_screen();
        if (!$screen || $screen->base !== 'dashboard') {
            return;
        }
        
        // Inline CSS
        $css = $this->get_widget_styles();
        wp_add_inline_style('common', $css);
        
        // Inline JS
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', $this->get_widget_javascript());
    }
    
    /**
     * Get widget CSS styles
     * 
     * @since 1.4.0
     * @return string
     */
    private function get_widget_styles() {
        return <<<CSS
        .edubot-widget-container {
            padding: 12px;
        }
        
        .edubot-widget-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .stat-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .stat-period {
            font-size: 11px;
            opacity: 0.8;
        }
        
        .edubot-widget-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        
        .edubot-widget-actions .button {
            flex: 1;
            max-width: 200px;
        }
        
        .edubot-widget-refresh-btn {
            position: relative;
        }
        
        .edubot-widget-refresh-btn.loading::after {
            content: '';
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            border: 2px solid #f3f3f3;
            border-top: 2px solid #0073aa;
            border-radius: 50%;
            width: 14px;
            height: 14px;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: translateY(-50%) rotate(0deg); }
            100% { transform: translateY(-50%) rotate(360deg); }
        }
        
        .edubot-widget-recent table.edubot-widget-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .edubot-widget-table thead {
            background: #f5f5f5;
            border-bottom: 2px solid #ddd;
        }
        
        .edubot-widget-table th {
            padding: 10px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        
        .edubot-widget-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        
        .channel-badge {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-completed {
            background: #c8e6c9;
            color: #2e7d32;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-failed {
            background: #ffcdd2;
            color: #c62828;
        }
        
        .edubot-widget-channels {
            padding: 8px 0;
        }
        
        .channel-item {
            margin-bottom: 16px;
        }
        
        .channel-name {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 13px;
        }
        
        .channel-name span:first-child {
            font-weight: 600;
            color: #333;
        }
        
        .channel-count {
            background: #f0f0f0;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: #666;
        }
        
        .channel-bar {
            background: #f5f5f5;
            height: 24px;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .channel-progress {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 11px;
            font-weight: 600;
            transition: width 0.3s ease;
        }
        
        .channel-percentage {
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        CSS;
    }
    
    /**
     * Get widget JavaScript
     * 
     * @since 1.4.0
     * @return string
     */
    private function get_widget_javascript() {
        return <<<JS
        (function($) {
            'use strict';
            
            $(document).on('click', '.edubot-widget-refresh-btn', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var widget = $btn.data('widget');
                
                $btn.addClass('loading').prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'edubot_widget_refresh',
                        widget: widget,
                        nonce: '<?php echo wp_create_nonce('edubot_widget_refresh'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error refreshing widget: ' + (response.data || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Error refreshing widget. Please try again.');
                    },
                    complete: function() {
                        $btn.removeClass('loading').prop('disabled', false);
                    }
                });
            });
        })(jQuery);
        JS;
    }
    
    /**
     * Handle AJAX widget refresh request
     * 
     * @since 1.4.0
     * @return void
     */
    public function ajax_refresh_widget() {
        check_ajax_referer('edubot_widget_refresh', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Clear any transients/cache for widgets
        delete_transient('edubot_kpi_summary');
        
        wp_send_json_success('Widget refreshed');
    }
}

// Initialize on plugins_loaded
add_action('plugins_loaded', function() {
    if (class_exists('EduBot_Logger')) {
        EduBot_Dashboard_Widget::get_instance();
    }
}, 15);
