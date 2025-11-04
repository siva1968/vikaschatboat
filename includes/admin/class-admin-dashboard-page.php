<?php
/**
 * Admin Dashboard Page Registration
 * 
 * Registers the analytics dashboard page in WordPress admin
 * and manages dashboard rendering and functionality.
 * 
 * @since 1.3.3
 * @package EduBot_Pro
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class EduBot_Admin_Dashboard_Page {
    
    /**
     * Plugin instance
     * 
     * @var EduBot_Admin_Dashboard_Page
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
     * @return EduBot_Admin_Dashboard_Page
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
            add_action('admin_menu', [$this, 'register_dashboard_page']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);
            add_action('wp_ajax_edubot_refresh_dashboard', [$this, 'ajax_refresh_dashboard']);
        }
    }
    
    /**
     * Register dashboard page in WordPress admin
     * 
     * @since 1.3.3
     * @return void
     */
    public function register_dashboard_page() {
        // Main dashboard page
        add_menu_page(
            'EduBot Analytics',                    // Page title
            'EduBot Analytics',                    // Menu title
            'manage_options',                      // Capability
            'edubot-dashboard',                    // Menu slug
            [$this, 'render_dashboard_page'],      // Callback
            'dashicons-chart-line',                // Icon
            58                                     // Position (below Settings)
        );
        
        // Submenu: Dashboard
        add_submenu_page(
            'edubot-dashboard',                    // Parent slug
            'Dashboard',                           // Page title
            'Dashboard',                           // Menu title
            'manage_options',                      // Capability
            'edubot-dashboard',                    // Menu slug
            [$this, 'render_dashboard_page']       // Callback
        );
        
        // Submenu: Reports
        add_submenu_page(
            'edubot-dashboard',
            'Reports',
            'Reports',
            'manage_options',
            'edubot-reports',
            [$this, 'render_reports_page']
        );
        
        // Submenu: API Logs
        add_submenu_page(
            'edubot-dashboard',
            'API Logs',
            'API Logs',
            'manage_options',
            'edubot-api-logs',
            [$this, 'render_api_logs_page']
        );
        
        // Submenu: Settings
        add_submenu_page(
            'edubot-dashboard',
            'Analytics Settings',
            'Settings',
            'manage_options',
            'edubot-analytics-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Render main dashboard page
     * 
     * @since 1.3.3
     * @return void
     */
    public function render_dashboard_page() {
        // Verify capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Set page title
        $page_title = 'EduBot Pro - Marketing Analytics Dashboard';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($page_title); ?></h1>
            <?php
            // Include dashboard template
            include(plugin_dir_path(__FILE__) . 'templates/dashboard-widget.php');
            ?>
        </div>
        <?php
    }
    
    /**
     * Render reports page
     * 
     * @since 1.3.3
     * @return void
     */
    public function render_reports_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        ?>
        <div class="wrap">
            <h1>📋 Performance Reports</h1>
            <div style="margin-top: 20px;">
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h2>Automated Reports</h2>
                    <p>Configure automated reports to be sent via email on a schedule.</p>
                    
                    <!-- Reports configuration will be added in Phase 4 -->
                    <div style="margin-top: 20px; padding: 20px; background: white; border-radius: 8px; border-left: 4px solid #007cba;">
                        <strong>Feature Status:</strong> Coming in Phase 4
                        <br>This section will include:
                        <ul style="margin: 10px 0 0 20px;">
                            <li>Daily/Weekly/Monthly report scheduling</li>
                            <li>Email templates customization</li>
                            <li>Report recipient management</li>
                            <li>Performance summaries</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render API logs page
     * 
     * @since 1.3.3
     * @return void
     */
    public function render_api_logs_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        global $wpdb;
        
        // Get API logs
        $logs = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}edubot_api_logs 
             ORDER BY created_at DESC 
             LIMIT 100"
        );
        
        ?>
        <div class="wrap">
            <h1>🔌 API Logs</h1>
            <div style="margin-top: 20px;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Platform</th>
                            <th>Status</th>
                            <th>Status Code</th>
                            <th>Response Time</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log->id); ?></td>
                                <td><strong><?php echo esc_html($log->platform); ?></strong></td>
                                <td>
                                    <?php
                                    $status_class = $log->response_code === 200 ? 'success' : 'error';
                                    $status_text = $log->response_code === 200 ? '✓ Success' : '✗ Failed';
                                    echo "<span style='color: " . ($status_class === 'success' ? '#28a745' : '#dc3545') . ";'>" . esc_html($status_text) . "</span>";
                                    ?>
                                </td>
                                <td><?php echo esc_html($log->response_code); ?></td>
                                <td><?php echo esc_html($log->response_time); ?>ms</td>
                                <td><?php echo esc_html(date('M d, Y H:i:s', strtotime($log->created_at))); ?></td>
                                <td>
                                    <a href="#" onclick="alert('<?php echo esc_js($log->request_body ?? ''); ?>');">View Request</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px;">
                                    No API logs found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     * 
     * @since 1.3.3
     * @return void
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        ?>
        <div class="wrap">
            <h1>⚙️ Analytics Settings</h1>
            <div style="margin-top: 20px;">
                <div class="postbox">
                    <h2 class="hndle">API Configuration</h2>
                    <div class="inside">
                        <p>Configure API credentials for multi-platform conversion tracking.</p>
                        
                        <form method="post" action="options.php">
                            <?php settings_fields('edubot_analytics_settings'); ?>
                            
                            <table class="form-table">
                                <tr>
                                    <th><label for="facebook_pixel_id">Facebook Pixel ID</label></th>
                                    <td>
                                        <input type="text" name="edubot_facebook_pixel_id" 
                                               id="facebook_pixel_id" class="regular-text"
                                               value="<?php echo esc_attr(get_option('edubot_facebook_pixel_id')); ?>">
                                        <p class="description">Your Facebook Pixel ID for conversion tracking</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="google_ads_conversion_id">Google Ads Conversion ID</label></th>
                                    <td>
                                        <input type="text" name="edubot_google_ads_conversion_id" 
                                               id="google_ads_conversion_id" class="regular-text"
                                               value="<?php echo esc_attr(get_option('edubot_google_ads_conversion_id')); ?>">
                                        <p class="description">Your Google Ads conversion tracking ID</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="tiktok_pixel_id">TikTok Pixel ID</label></th>
                                    <td>
                                        <input type="text" name="edubot_tiktok_pixel_id" 
                                               id="tiktok_pixel_id" class="regular-text"
                                               value="<?php echo esc_attr(get_option('edubot_tiktok_pixel_id')); ?>">
                                        <p class="description">Your TikTok Business Pixel ID</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <?php submit_button(); ?>
                        </form>
                    </div>
                </div>
                
                <div class="postbox" style="margin-top: 20px;">
                    <h2 class="hndle">Dashboard Settings</h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><label for="cache_ttl">Cache TTL (seconds)</label></th>
                                <td>
                                    <input type="number" name="edubot_cache_ttl" 
                                           id="cache_ttl" class="small-text"
                                           value="<?php echo esc_attr(get_option('edubot_cache_ttl', 300)); ?>">
                                    <p class="description">How long to cache dashboard data (default: 300 seconds)</p>
                                </td>
                            </tr>
                        </table>
                        
                        <button class="button button-secondary" onclick="if(confirm('Clear all dashboard caches?')) { location.href = '<?php echo wp_nonce_url(add_query_arg('action', 'edubot_clear_cache'), 'edubot_clear_cache'); ?>'; }">
                            Clear Cache
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue dashboard assets
     * 
     * @since 1.3.3
     * @param string $hook_suffix The current admin page
     * @return void
     */
    public function enqueue_dashboard_assets($hook_suffix) {
        // Only load on dashboard pages
        if (strpos($hook_suffix, 'edubot') === false) {
            return;
        }
        
        // Chart.js
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            [],
            '3.9.1',
            true
        );
        
        // HTML to PDF
        wp_enqueue_script(
            'html2pdf-js',
            'https://cdn.jsdelivr.net/npm/html2pdf@0.10.1/dist/html2pdf.bundle.min.js',
            [],
            '0.10.1',
            true
        );
        
        // Dashboard JavaScript
        wp_enqueue_script(
            'edubot-dashboard-js',
            plugin_dir_url(__FILE__) . 'js/dashboard.js',
            ['jquery', 'chart-js', 'html2pdf-js'],
            '1.3.3',
            true
        );
        
        // Localize script
        wp_localize_script('edubot-dashboard-js', 'edubot_dashboard_config', [
            'nonce' => wp_create_nonce('edubot_dashboard_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'period' => isset($_GET['dashboard_period']) ? sanitize_text_field($_GET['dashboard_period']) : 'month'
        ]);
        
        // Dashboard CSS
        wp_enqueue_style(
            'edubot-dashboard-css',
            plugin_dir_url(__FILE__) . 'css/dashboard.css',
            [],
            '1.3.3'
        );
    }
    
    /**
     * AJAX handler for dashboard refresh
     * 
     * @since 1.3.3
     * @return void
     */
    public function ajax_refresh_dashboard() {
        // Verify nonce
        check_ajax_referer('edubot_dashboard_nonce', 'nonce');
        
        // Verify capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        try {
            $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'month';
            
            // Get fresh data
            $kpis = $this->dashboard->get_kpis($period);
            $sources = $this->dashboard->get_enquiries_by_source($period);
            $campaigns = $this->dashboard->get_enquiries_by_campaign($period);
            $trends = $this->dashboard->get_enquiry_trends($period);
            $devices = $this->dashboard->get_device_breakdown($period);
            
            wp_send_json_success([
                'kpis' => $kpis,
                'charts' => [
                    'source' => $sources,
                    'campaigns' => $campaigns,
                    'trends' => $trends,
                    'devices' => $devices
                ]
            ]);
        } catch (Exception $e) {
            $this->logger && $this->logger->error('Dashboard refresh error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            wp_send_json_error([
                'message' => 'Error refreshing dashboard: ' . $e->getMessage()
            ]);
        }
    }
}

// Initialize dashboard page
if (is_admin()) {
    EduBot_Admin_Dashboard_Page::get_instance();
}
