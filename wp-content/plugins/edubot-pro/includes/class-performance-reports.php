<?php
/**
 * Performance Reports System
 * 
 * Generates and manages automated performance reports with email delivery,
 * scheduling via WP-Cron, and recipient management.
 * 
 * @since 1.3.4
 * @package EduBot_Pro
 * @subpackage Reports
 */

if (!defined('ABSPATH')) {
    exit;
}

class EduBot_Performance_Reports {
    
    /**
     * Singleton instance
     * 
     * @var EduBot_Performance_Reports
     */
    private static $instance = null;
    
    /**
     * Logger instance
     * 
     * @var EduBot_Logger
     */
    private $logger;
    
    /**
     * Dashboard instance for data retrieval
     * 
     * @var EduBot_Admin_Dashboard
     */
    private $dashboard;
    
    /**
     * Report types
     * 
     * @var array
     */
    private $report_types = ['daily', 'weekly', 'monthly'];
    
    /**
     * Get singleton instance
     * 
     * @param EduBot_Logger $logger Logger instance
     * @return EduBot_Performance_Reports
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
        
        // Schedule WP-Cron hooks
        if (is_admin()) {
            add_action('wp_edubot_daily_report', [$this, 'generate_daily_report']);
            add_action('wp_edubot_weekly_report', [$this, 'generate_weekly_report']);
            add_action('wp_edubot_monthly_report', [$this, 'generate_monthly_report']);
            
            // Settings
            add_action('admin_init', [$this, 'register_settings']);
        }
    }
    
    /**
     * Register settings for reports
     * 
     * @since 1.3.4
     * @return void
     */
    public function register_settings() {
        // Daily report enabled
        register_setting('edubot_reports_settings', 'edubot_daily_report_enabled', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ]);
        
        // Daily report time
        register_setting('edubot_reports_settings', 'edubot_daily_report_time', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '06:00'
        ]);
        
        // Weekly report enabled
        register_setting('edubot_reports_settings', 'edubot_weekly_report_enabled', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ]);
        
        // Weekly report day (0=Sunday, 6=Saturday)
        register_setting('edubot_reports_settings', 'edubot_weekly_report_day', [
            'type' => 'integer',
            'sanitize_callback' => 'intval',
            'default' => 1
        ]);
        
        // Weekly report time
        register_setting('edubot_reports_settings', 'edubot_weekly_report_time', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '08:00'
        ]);
        
        // Monthly report enabled
        register_setting('edubot_reports_settings', 'edubot_monthly_report_enabled', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ]);
        
        // Monthly report day (1-28)
        register_setting('edubot_reports_settings', 'edubot_monthly_report_day', [
            'type' => 'integer',
            'sanitize_callback' => 'intval',
            'default' => 1
        ]);
        
        // Monthly report time
        register_setting('edubot_reports_settings', 'edubot_monthly_report_time', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '09:00'
        ]);
        
        // Report recipients
        register_setting('edubot_reports_settings', 'edubot_report_recipients', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_recipients'],
            'default' => []
        ]);
        
        // Include charts in email
        register_setting('edubot_reports_settings', 'edubot_report_include_charts', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ]);
    }
    
    /**
     * Sanitize recipients array
     * 
     * @since 1.3.4
     * @param array $recipients Array of email addresses
     * @return array Sanitized recipients
     */
    public function sanitize_recipients($recipients) {
        if (!is_array($recipients)) {
            return [];
        }
        
        return array_map(function($email) {
            return sanitize_email($email);
        }, $recipients);
    }
    
    /**
     * Schedule daily report
     * 
     * @since 1.3.4
     * @return void
     */
    public function schedule_daily_report() {
        if (!wp_next_scheduled('wp_edubot_daily_report')) {
            $time = get_option('edubot_daily_report_time', '06:00');
            list($hour, $minute) = explode(':', $time);
            $timestamp = strtotime("today $hour:$minute");
            
            wp_schedule_event($timestamp, 'daily', 'wp_edubot_daily_report');
        }
    }
    
    /**
     * Schedule weekly report
     * 
     * @since 1.3.4
     * @return void
     */
    public function schedule_weekly_report() {
        if (!wp_next_scheduled('wp_edubot_weekly_report')) {
            $day = (int) get_option('edubot_weekly_report_day', 1);
            $time = get_option('edubot_weekly_report_time', '08:00');
            list($hour, $minute) = explode(':', $time);
            
            // Calculate next occurrence of the specified day
            $days_ahead = $day - date('w');
            if ($days_ahead < 0) {
                $days_ahead += 7;
            }
            
            $timestamp = strtotime("+" . $days_ahead . " days $hour:$minute");
            
            wp_schedule_event($timestamp, 'weekly', 'wp_edubot_weekly_report');
        }
    }
    
    /**
     * Schedule monthly report
     * 
     * @since 1.3.4
     * @return void
     */
    public function schedule_monthly_report() {
        if (!wp_next_scheduled('wp_edubot_monthly_report')) {
            $day = (int) get_option('edubot_monthly_report_day', 1);
            $time = get_option('edubot_monthly_report_time', '09:00');
            list($hour, $minute) = explode(':', $time);
            
            // Calculate next occurrence of the specified day
            $timestamp = strtotime(date('Y-m-') . $day . " $hour:$minute");
            if ($timestamp < time()) {
                $timestamp = strtotime('+1 month', $timestamp);
            }
            
            wp_schedule_event($timestamp, 'monthly', 'wp_edubot_monthly_report');
        }
    }
    
    /**
     * Generate daily report
     * 
     * @since 1.3.4
     * @return void
     */
    public function generate_daily_report() {
        try {
            if (!get_option('edubot_daily_report_enabled', false)) {
                return;
            }
            
            $this->send_scheduled_report('daily');
        } catch (Exception $e) {
            $this->logger && $this->logger->error('Daily report generation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Generate weekly report
     * 
     * @since 1.3.4
     * @return void
     */
    public function generate_weekly_report() {
        try {
            if (!get_option('edubot_weekly_report_enabled', false)) {
                return;
            }
            
            $this->send_scheduled_report('week');
        } catch (Exception $e) {
            $this->logger && $this->logger->error('Weekly report generation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Generate monthly report
     * 
     * @since 1.3.4
     * @return void
     */
    public function generate_monthly_report() {
        try {
            if (!get_option('edubot_monthly_report_enabled', false)) {
                return;
            }
            
            $this->send_scheduled_report('month');
        } catch (Exception $e) {
            $this->logger && $this->logger->error('Monthly report generation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Send scheduled report to recipients
     * 
     * @since 1.3.4
     * @param string $period Period type (daily, week, month)
     * @return bool Success status
     */
    private function send_scheduled_report($period) {
        // Get recipients
        $recipients = get_option('edubot_report_recipients', []);
        if (empty($recipients)) {
            return false;
        }
        
        // Generate report
        $report = $this->generate_report($period);
        if (!$report) {
            return false;
        }
        
        // Get email content
        $html_content = $this->get_html_email_template($report, $period);
        $text_content = $this->get_text_email_template($report, $period);
        
        // Send email
        $subject = sprintf(
            esc_html__('EduBot Pro - %s Report', 'edubot-pro'),
            ucfirst($period)
        );
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
        
        $sent = false;
        foreach ($recipients as $recipient) {
            $recipient = sanitize_email($recipient);
            if (is_email($recipient)) {
                $result = wp_mail($recipient, $subject, $html_content, $headers);
                if ($result) {
                    $sent = true;
                    $this->log_report_sent($recipient, $period, 'success');
                } else {
                    $this->log_report_sent($recipient, $period, 'failed');
                }
            }
        }
        
        return $sent;
    }
    
    /**
     * Generate report data
     * 
     * @since 1.3.4
     * @param string $period Report period
     * @return array|false Report data or false on failure
     */
    public function generate_report($period) {
        try {
            return [
                'period' => $period,
                'generated_at' => current_time('mysql'),
                'kpis' => $this->dashboard->get_kpis($period),
                'comparison' => $this->dashboard->get_enquiries_comparison($period),
                'sources' => $this->dashboard->get_enquiries_by_source($period, 10),
                'campaigns' => $this->dashboard->get_enquiries_by_campaign($period, 10),
                'trends' => $this->dashboard->get_enquiry_trends($period),
                'devices' => $this->dashboard->get_device_breakdown($period),
                'top_sources' => $this->dashboard->get_top_performing_sources($period, 5)
            ];
        } catch (Exception $e) {
            $this->logger && $this->logger->error('Report generation failed', [
                'error' => $e->getMessage(),
                'period' => $period
            ]);
            return false;
        }
    }
    
    /**
     * Get HTML email template
     * 
     * @since 1.3.4
     * @param array $report Report data
     * @param string $period Period type
     * @return string HTML content
     */
    private function get_html_email_template($report, $period) {
        $kpis = $report['kpis'];
        $comparison = $report['comparison'];
        $sources = $report['sources'];
        $campaigns = $report['campaigns'];
        
        $period_label = strtoupper($period);
        $blog_name = get_bloginfo('name');
        
        // Determine card class
        $comparison_class = $comparison['is_increase'] ? 'success' : 'danger';
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduBot Pro Report</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #007cba 0%, #0056b3 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 30px 20px;
        }
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .kpi-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #007cba;
        }
        .kpi-card.success {
            border-left-color: #28a745;
        }
        .kpi-card.danger {
            border-left-color: #dc3545;
        }
        .kpi-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .kpi-value {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }
        .kpi-change {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #007cba;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:hover {
            background: #f9fafb;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #dee2e6;
            border-radius: 0 0 8px 8px;
        }
        .footer a {
            color: #007cba;
            text-decoration: none;
        }
        .metric {
            color: #007cba;
            font-weight: 600;
        }
        @media (max-width: 600px) {
            .kpi-grid {
                grid-template-columns: 1fr;
            }
            .container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Performance Report</h1>
            <p>$blog_name - $period_label Report</p>
        </div>
        
        <div class="content">
            <!-- KPI Section -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Total Enquiries</div>
                    <div class="kpi-value">{$kpis['total_enquiries']}</div>
                </div>
                <div class="kpi-card $comparison_class">
                    <div class="kpi-label">Period Comparison</div>
                    <div class="kpi-value">{$comparison['current']}</div>
                    <div class="kpi-change">{$comparison['change_text']} {$comparison['change_percent']}%</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Unique Sources</div>
                    <div class="kpi-value">{$kpis['unique_sources']}</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Daily Average</div>
                    <div class="kpi-value">{$kpis['avg_per_day']}</div>
                </div>
            </div>
            
            <!-- Top Sources Section -->
            <div class="section">
                <h2 class="section-title">🎯 Top Performing Sources</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>Enquiries</th>
                            <th>% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
HTML;
        
        foreach (array_slice($sources, 0, 5) as $source) {
            $html .= sprintf(
                '<tr><td>%s</td><td class="metric">%d</td><td>%s%%</td></tr>',
                esc_html(trim($source['source'] ?? 'Unknown', '"')),
                $source['count'],
                $source['percentage']
            );
        }
        
        $html .= <<<HTML
                    </tbody>
                </table>
            </div>
            
            <!-- Top Campaigns Section -->
            <div class="section">
                <h2 class="section-title">📈 Top Campaigns</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Source</th>
                            <th>Enquiries</th>
                            <th>% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
HTML;
        
        foreach (array_slice($campaigns, 0, 5) as $campaign) {
            $html .= sprintf(
                '<tr><td>%s</td><td>%s</td><td class="metric">%d</td><td>%s%%</td></tr>',
                esc_html(trim($campaign['campaign'] ?? 'Unknown', '"')),
                esc_html(trim($campaign['source'] ?? 'Unknown', '"')),
                $campaign['enquiries'],
                $campaign['percentage']
            );
        }
        
        $html .= <<<HTML
                    </tbody>
                </table>
            </div>
            
            <!-- Summary -->
            <div class="section" style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-top: 30px;">
                <p style="margin: 0;">
                    <strong>Report Generated:</strong> {$report['generated_at']}<br>
                    <strong>Period:</strong> {$period_label}<br>
                    <strong>View Full Dashboard:</strong> <a href="{admin_url('admin.php?page=edubot-dashboard')}">Click Here</a>
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p>This is an automated report from EduBot Pro. <a href="#">Manage Preferences</a></p>
            <p>&copy; {date('Y')} $blog_name. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
        
        return $html;
    }
    
    /**
     * Get plain text email template
     * 
     * @since 1.3.4
     * @param array $report Report data
     * @param string $period Period type
     * @return string Text content
     */
    private function get_text_email_template($report, $period) {
        $kpis = $report['kpis'];
        $comparison = $report['comparison'];
        $sources = $report['sources'];
        $campaigns = $report['campaigns'];
        
        $period_label = strtoupper($period);
        $blog_name = get_bloginfo('name');
        
        $text = "EDUBOT PRO - $period_label REPORT\n";
        $text .= "======================================\n\n";
        
        $text .= "Report for: $blog_name\n";
        $text .= "Generated: {$report['generated_at']}\n\n";
        
        $text .= "KEY PERFORMANCE INDICATORS\n";
        $text .= "----------------------------\n";
        $text .= "Total Enquiries: {$kpis['total_enquiries']}\n";
        $text .= "Period Comparison: {$comparison['current']} ({$comparison['change_text']} {$comparison['change_percent']}%)\n";
        $text .= "Unique Sources: {$kpis['unique_sources']}\n";
        $text .= "Daily Average: {$kpis['avg_per_day']}\n\n";
        
        $text .= "TOP PERFORMING SOURCES (Top 5)\n";
        $text .= "----------------------------\n";
        foreach (array_slice($sources, 0, 5) as $source) {
            $text .= sprintf(
                "• %s: %d enquiries (%s%%)\n",
                trim($source['source'] ?? 'Unknown', '"'),
                $source['count'],
                $source['percentage']
            );
        }
        
        $text .= "\nTOP CAMPAIGNS (Top 5)\n";
        $text .= "----------------------------\n";
        foreach (array_slice($campaigns, 0, 5) as $campaign) {
            $text .= sprintf(
                "• %s (%s): %d enquiries (%s%%)\n",
                trim($campaign['campaign'] ?? 'Unknown', '"'),
                trim($campaign['source'] ?? 'Unknown', '"'),
                $campaign['enquiries'],
                $campaign['percentage']
            );
        }
        
        $text .= "\n======================================\n";
        $text .= "View Full Dashboard: " . admin_url('admin.php?page=edubot-dashboard') . "\n";
        $text .= "======================================\n\n";
        $text .= "This is an automated report from EduBot Pro.\n";
        $text .= "© " . date('Y') . " $blog_name. All rights reserved.\n";
        
        return $text;
    }
    
    /**
     * Log report sent event
     * 
     * @since 1.3.4
     * @param string $recipient Email recipient
     * @param string $period Report period
     * @param string $status Send status
     * @return void
     */
    private function log_report_sent($recipient, $period, $status) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'edubot_report_schedules',
            [
                'report_type' => $period,
                'recipient' => $recipient,
                'status' => $status,
                'sent_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s']
        );
    }
    
    /**
     * Get report history
     * 
     * @since 1.3.4
     * @param int $limit Number of records to return
     * @return array Report history
     */
    public function get_report_history($limit = 50) {
        global $wpdb;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}edubot_report_schedules 
                 ORDER BY sent_at DESC 
                 LIMIT %d",
                $limit
            )
        );
        
        return $results ?? [];
    }
    
    /**
     * Get report statistics
     * 
     * @since 1.3.4
     * @return array Statistics
     */
    public function get_report_statistics() {
        global $wpdb;
        
        $total_sent = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}edubot_report_schedules 
             WHERE status = 'success'"
        );
        
        $total_failed = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}edubot_report_schedules 
             WHERE status = 'failed'"
        );
        
        $by_type = $wpdb->get_results(
            "SELECT report_type, COUNT(*) as count 
             FROM {$wpdb->prefix}edubot_report_schedules 
             WHERE status = 'success'
             GROUP BY report_type"
        );
        
        return [
            'total_sent' => (int) $total_sent,
            'total_failed' => (int) $total_failed,
            'success_rate' => $total_sent + $total_failed > 0 
                ? round(($total_sent / ($total_sent + $total_failed)) * 100, 1) 
                : 0,
            'by_type' => $by_type ?? []
        ];
    }
    
    /**
     * Clear old reports (older than 90 days)
     * 
     * @since 1.3.4
     * @return int Number of records deleted
     */
    public function cleanup_old_reports() {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-90 days'));
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}edubot_report_schedules 
                 WHERE sent_at < %s",
                $cutoff_date
            )
        );
    }
}

// Initialize reports system
if (is_admin()) {
    EduBot_Performance_Reports::get_instance();
}
