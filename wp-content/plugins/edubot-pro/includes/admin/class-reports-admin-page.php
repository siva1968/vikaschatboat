<?php
/**
 * Reports Settings and Management Page
 * 
 * Handles report configuration, recipient management, and scheduling
 * 
 * @since 1.3.4
 * @package EduBot_Pro
 * @subpackage Reports
 */

if (!defined('ABSPATH')) {
    exit;
}

class EduBot_Reports_Admin_Page {
    
    /**
     * Singleton instance
     * 
     * @var EduBot_Reports_Admin_Page
     */
    private static $instance = null;
    
    /**
     * Logger instance
     * 
     * @var EduBot_Logger
     */
    private $logger;
    
    /**
     * Reports instance
     * 
     * @var EduBot_Performance_Reports
     */
    private $reports;
    
    /**
     * Get singleton instance
     * 
     * @param EduBot_Logger $logger Logger instance
     * @return EduBot_Reports_Admin_Page
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
        $this->reports = EduBot_Performance_Reports::get_instance($logger);
        
        if (is_admin()) {
            add_action('admin_init', [$this, 'handle_form_submission']);
            add_action('admin_init', [$this, 'handle_recipient_actions']);
        }
    }
    
    /**
     * Render reports management page
     * 
     * @since 1.3.4
     * @return void
     */
    public function render_page() {
        // Verify capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Get current settings
        $daily_enabled = get_option('edubot_daily_report_enabled', false);
        $daily_time = get_option('edubot_daily_report_time', '06:00');
        $weekly_enabled = get_option('edubot_weekly_report_enabled', false);
        $weekly_day = get_option('edubot_weekly_report_day', 1);
        $weekly_time = get_option('edubot_weekly_report_time', '08:00');
        $monthly_enabled = get_option('edubot_monthly_report_enabled', false);
        $monthly_day = get_option('edubot_monthly_report_day', 1);
        $monthly_time = get_option('edubot_monthly_report_time', '09:00');
        $recipients = get_option('edubot_report_recipients', []);
        $include_charts = get_option('edubot_report_include_charts', true);
        
        // Get scheduled info
        $scheduled = EduBot_Cron_Scheduler::get_all_scheduled();
        $stats = $this->reports->get_report_statistics();
        ?>
        
        <div class="wrap">
            <h1>📋 Automated Performance Reports</h1>
            
            <!-- Tabs -->
            <nav class="nav-tab-wrapper wp-clearfix">
                <a href="#configuration" class="nav-tab nav-tab-active">Configuration</a>
                <a href="#recipients" class="nav-tab">Recipients</a>
                <a href="#history" class="nav-tab">History</a>
                <a href="#statistics" class="nav-tab">Statistics</a>
            </nav>
            
            <!-- Configuration Tab -->
            <div id="configuration" class="tab-content">
                <div class="card" style="margin-top: 20px;">
                    <h2 style="margin-top: 0;">📅 Report Schedule Settings</h2>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('edubot_reports_settings_nonce'); ?>
                        
                        <table class="form-table">
                            <!-- Daily Report -->
                            <tr>
                                <th colspan="2" style="background: #f8f9fa; padding: 15px;">
                                    <strong>Daily Report</strong>
                                    <span style="float: right; color: #666; font-size: 12px;">
                                        <?php echo $scheduled['daily']['enabled'] ? '🟢 Active' : '🔴 Inactive'; ?>
                                    </span>
                                </th>
                            </tr>
                            <tr>
                                <th><label for="daily_report_enabled">Enable Daily Report</label></th>
                                <td>
                                    <input type="checkbox" name="edubot_daily_report_enabled" id="daily_report_enabled" value="1" 
                                           <?php checked($daily_enabled); ?>>
                                    <p class="description">Send a report every day at the specified time</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="daily_report_time">Report Time</label></th>
                                <td>
                                    <input type="time" name="edubot_daily_report_time" id="daily_report_time" 
                                           value="<?php echo esc_attr($daily_time); ?>" class="small-text">
                                    <p class="description">Time in 24-hour format (HH:MM)</p>
                                    <?php if ($scheduled['daily']['enabled'] && $scheduled['daily']['next_run']): ?>
                                        <p style="color: #0073aa; margin-top: 10px;">
                                            <strong>Next run:</strong> <?php echo date('F j, Y H:i:s', $scheduled['daily']['next_run']); ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Weekly Report -->
                            <tr>
                                <th colspan="2" style="background: #f8f9fa; padding: 15px;">
                                    <strong>Weekly Report</strong>
                                    <span style="float: right; color: #666; font-size: 12px;">
                                        <?php echo $scheduled['weekly']['enabled'] ? '🟢 Active' : '🔴 Inactive'; ?>
                                    </span>
                                </th>
                            </tr>
                            <tr>
                                <th><label for="weekly_report_enabled">Enable Weekly Report</label></th>
                                <td>
                                    <input type="checkbox" name="edubot_weekly_report_enabled" id="weekly_report_enabled" value="1" 
                                           <?php checked($weekly_enabled); ?>>
                                    <p class="description">Send a report once per week on the specified day</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="weekly_report_day">Day of Week</label></th>
                                <td>
                                    <select name="edubot_weekly_report_day" id="weekly_report_day" class="small-text">
                                        <option value="0" <?php selected($weekly_day, 0); ?>>Sunday</option>
                                        <option value="1" <?php selected($weekly_day, 1); ?>>Monday</option>
                                        <option value="2" <?php selected($weekly_day, 2); ?>>Tuesday</option>
                                        <option value="3" <?php selected($weekly_day, 3); ?>>Wednesday</option>
                                        <option value="4" <?php selected($weekly_day, 4); ?>>Thursday</option>
                                        <option value="5" <?php selected($weekly_day, 5); ?>>Friday</option>
                                        <option value="6" <?php selected($weekly_day, 6); ?>>Saturday</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="weekly_report_time">Report Time</label></th>
                                <td>
                                    <input type="time" name="edubot_weekly_report_time" id="weekly_report_time" 
                                           value="<?php echo esc_attr($weekly_time); ?>" class="small-text">
                                    <p class="description">Time in 24-hour format (HH:MM)</p>
                                    <?php if ($scheduled['weekly']['enabled'] && $scheduled['weekly']['next_run']): ?>
                                        <p style="color: #0073aa; margin-top: 10px;">
                                            <strong>Next run:</strong> <?php echo date('F j, Y H:i:s', $scheduled['weekly']['next_run']); ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Monthly Report -->
                            <tr>
                                <th colspan="2" style="background: #f8f9fa; padding: 15px;">
                                    <strong>Monthly Report</strong>
                                    <span style="float: right; color: #666; font-size: 12px;">
                                        <?php echo $scheduled['monthly']['enabled'] ? '🟢 Active' : '🔴 Inactive'; ?>
                                    </span>
                                </th>
                            </tr>
                            <tr>
                                <th><label for="monthly_report_enabled">Enable Monthly Report</label></th>
                                <td>
                                    <input type="checkbox" name="edubot_monthly_report_enabled" id="monthly_report_enabled" value="1" 
                                           <?php checked($monthly_enabled); ?>>
                                    <p class="description">Send a report once per month on the specified day</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="monthly_report_day">Day of Month</label></th>
                                <td>
                                    <input type="number" name="edubot_monthly_report_day" id="monthly_report_day" 
                                           value="<?php echo esc_attr($monthly_day); ?>" min="1" max="28" class="small-text">
                                    <p class="description">Day 1-28 (to ensure it works every month)</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="monthly_report_time">Report Time</label></th>
                                <td>
                                    <input type="time" name="edubot_monthly_report_time" id="monthly_report_time" 
                                           value="<?php echo esc_attr($monthly_time); ?>" class="small-text">
                                    <p class="description">Time in 24-hour format (HH:MM)</p>
                                    <?php if ($scheduled['monthly']['enabled'] && $scheduled['monthly']['next_run']): ?>
                                        <p style="color: #0073aa; margin-top: 10px;">
                                            <strong>Next run:</strong> <?php echo date('F j, Y H:i:s', $scheduled['monthly']['next_run']); ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Content Options -->
                            <tr>
                                <th colspan="2" style="background: #f8f9fa; padding: 15px;">
                                    <strong>Report Content</strong>
                                </th>
                            </tr>
                            <tr>
                                <th><label for="include_charts">Include Charts</label></th>
                                <td>
                                    <input type="checkbox" name="edubot_report_include_charts" id="include_charts" value="1" 
                                           <?php checked($include_charts); ?>>
                                    <p class="description">Include chart images in email reports</p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button('Save Settings'); ?>
                    </form>
                </div>
            </div>
            
            <!-- Recipients Tab -->
            <div id="recipients" class="tab-content" style="display: none;">
                <div class="card" style="margin-top: 20px;">
                    <h2 style="margin-top: 0;">📧 Report Recipients</h2>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('edubot_add_recipient_nonce', 'add_recipient_nonce'); ?>
                        <input type="hidden" name="action" value="add_recipient">
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="new_recipient">Add Email Address</label></th>
                                <td>
                                    <input type="email" name="new_recipient" id="new_recipient" class="regular-text" required>
                                    <p class="description">Enter an email address to receive reports</p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button('Add Recipient'); ?>
                    </form>
                    
                    <h3 style="margin-top: 30px;">Current Recipients</h3>
                    
                    <?php if (!empty($recipients)): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Email Address</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recipients as $recipient): ?>
                                <tr>
                                    <td><?php echo esc_html($recipient); ?></td>
                                    <td><span style="color: #28a745;">✓ Active</span></td>
                                    <td>
                                        <form method="post" action="" style="display: inline;">
                                            <?php wp_nonce_field('edubot_remove_recipient_nonce', 'remove_recipient_nonce'); ?>
                                            <input type="hidden" name="action" value="remove_recipient">
                                            <input type="hidden" name="recipient" value="<?php echo esc_attr($recipient); ?>">
                                            <button type="submit" class="button button-link-delete" 
                                                    onclick="return confirm('Remove this recipient?');">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="background: #f8f9fa; padding: 15px; border-radius: 4px; color: #666;">
                            No recipients configured. Add an email address above to enable reports.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- History Tab -->
            <div id="history" class="tab-content" style="display: none;">
                <div class="card" style="margin-top: 20px;">
                    <h2 style="margin-top: 0;">📜 Report History</h2>
                    
                    <?php
                    $history = $this->reports->get_report_history(50);
                    if (!empty($history)):
                    ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Report Type</th>
                                <th>Recipient</th>
                                <th>Status</th>
                                <th>Sent At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $entry): ?>
                            <tr>
                                <td><strong><?php echo esc_html(ucfirst($entry->report_type)); ?></strong></td>
                                <td><?php echo esc_html($entry->recipient); ?></td>
                                <td>
                                    <?php 
                                    if ($entry->status === 'success') {
                                        echo '<span style="color: #28a745;">✓ Success</span>';
                                    } else {
                                        echo '<span style="color: #dc3545;">✗ Failed</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html(date('M d, Y H:i:s', strtotime($entry->sent_at))); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p style="background: #f8f9fa; padding: 15px; border-radius: 4px; color: #666;">
                            No reports sent yet.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Statistics Tab -->
            <div id="statistics" class="tab-content" style="display: none;">
                <div class="card" style="margin-top: 20px;">
                    <h2 style="margin-top: 0;">📊 Report Statistics</h2>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #007cba;">
                            <div style="font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 8px;">Total Sent</div>
                            <div style="font-size: 32px; font-weight: 700;"><?php echo $stats['total_sent']; ?></div>
                        </div>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745;">
                            <div style="font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 8px;">Success Rate</div>
                            <div style="font-size: 32px; font-weight: 700;"><?php echo $stats['success_rate']; ?>%</div>
                        </div>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545;">
                            <div style="font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 8px;">Failed</div>
                            <div style="font-size: 32px; font-weight: 700;"><?php echo $stats['total_failed']; ?></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($stats['by_type'])): ?>
                    <h3 style="margin-top: 30px;">Reports by Type</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Report Type</th>
                                <th>Total Sent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['by_type'] as $type): ?>
                            <tr>
                                <td><?php echo esc_html(ucfirst($type->report_type)); ?></td>
                                <td><?php echo esc_html($type->count); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        
        <style>
            .tab-content {
                display: none;
                animation: fadeIn 0.3s ease;
            }
            .tab-content.active {
                display: block;
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            .nav-tab {
                cursor: pointer;
            }
        </style>
        
        <script>
            jQuery(document).ready(function($) {
                $('.nav-tab').on('click', function(e) {
                    e.preventDefault();
                    var target = $(this).attr('href');
                    
                    $('.tab-content').fadeOut(200);
                    $('.nav-tab').removeClass('nav-tab-active');
                    
                    setTimeout(function() {
                        $(target).fadeIn(200);
                    }, 200);
                    
                    $(this).addClass('nav-tab-active');
                });
            });
        </script>
        
        <?php
    }
    
    /**
     * Handle form submission
     * 
     * @since 1.3.4
     * @return void
     */
    public function handle_form_submission() {
        // Check if this is a form submission
        if (!isset($_POST['action'])) {
            return;
        }
        
        $action = sanitize_text_field($_POST['action']);
        
        if ($action === 'add_recipient') {
            check_admin_referer('edubot_add_recipient_nonce', 'add_recipient_nonce');
            
            if (!current_user_can('manage_options')) {
                return;
            }
            
            $new_recipient = sanitize_email($_POST['new_recipient'] ?? '');
            
            if (empty($new_recipient) || !is_email($new_recipient)) {
                add_settings_error('edubot_reports', 'invalid_email', 'Invalid email address.', 'error');
                return;
            }
            
            $recipients = get_option('edubot_report_recipients', []);
            if (!in_array($new_recipient, $recipients)) {
                $recipients[] = $new_recipient;
                update_option('edubot_report_recipients', $recipients);
                add_settings_error('edubot_reports', 'recipient_added', 'Recipient added successfully.', 'updated');
            } else {
                add_settings_error('edubot_reports', 'recipient_exists', 'This recipient already exists.', 'error');
            }
        }
    }
    
    /**
     * Handle recipient actions
     * 
     * @since 1.3.4
     * @return void
     */
    public function handle_recipient_actions() {
        // Remove recipient
        if (isset($_POST['action']) && $_POST['action'] === 'remove_recipient') {
            check_admin_referer('edubot_remove_recipient_nonce', 'remove_recipient_nonce');
            
            if (!current_user_can('manage_options')) {
                return;
            }
            
            $recipient = sanitize_email($_POST['recipient'] ?? '');
            
            $recipients = get_option('edubot_report_recipients', []);
            $recipients = array_diff($recipients, [$recipient]);
            
            update_option('edubot_report_recipients', $recipients);
            add_settings_error('edubot_reports', 'recipient_removed', 'Recipient removed successfully.', 'updated');
        }
    }
}

// Initialize reports admin page
if (is_admin()) {
    EduBot_Reports_Admin_Page::get_instance();
}
