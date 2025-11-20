<?php
/**
 * Admin Dashboard View
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (!empty($missing_tables)): ?>
    <div class="notice notice-warning">
        <p>
            <strong>Database Issue:</strong> Some required database tables are missing: 
            <code><?php echo esc_html(implode(', ', $missing_tables)); ?></code>
        </p>
        <p>
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=edubot-pro&action=repair_database'), 'edubot_repair_db', 'nonce'); ?>" 
               class="button button-primary">
                Repair Database Tables
            </a>
        </p>
    </div>
    <?php endif; ?>
    
    <div class="edubot-dashboard">
        <div class="edubot-row">
            <div class="edubot-col-8">
                <div class="edubot-card">
                    <h2>Recent Analytics</h2>
                    <?php if (isset($analytics_data) && !empty($analytics_data)): ?>
                        <div class="analytics-summary">
                            <div class="stat-box">
                                <h3><?php echo esc_html($analytics_data['total_conversations'] ?? 0); ?></h3>
                                <p>Total Conversations</p>
                            </div>
                            <div class="stat-box">
                                <h3><?php echo esc_html($analytics_data['total_applications'] ?? 0); ?></h3>
                                <p>Applications Received</p>
                            </div>
                            <div class="stat-box">
                                <h3><?php echo esc_html(round($analytics_data['conversion_rate'] ?? 0, 1)); ?>%</h3>
                                <p>Conversion Rate</p>
                            </div>
                            <div class="stat-box">
                                <h3><?php echo esc_html(round($analytics_data['avg_completion_time'] ?? 0, 1)); ?> min</h3>
                                <p>Avg. Completion Time</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p>No analytics data available yet. Start using the chatbot to see statistics here.</p>
                    <?php endif; ?>
                </div>

                <div class="edubot-card">
                    <h2>Recent Applications</h2>
                    <?php if (isset($recent_applications) && !empty($recent_applications)): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Application #</th>
                                    <th>Student Name</th>
                                    <th>Grade</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_applications as $app): ?>
                                    <tr>
                                        <td><?php echo esc_html($app['application_number']); ?></td>
                                        <td><?php echo esc_html($app['student_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo esc_html($app['grade'] ?? 'N/A'); ?></td>
                                        <td><?php echo esc_html(date('M j, Y', strtotime($app['created_at']))); ?></td>
                                        <td><span class="status status-<?php echo esc_attr($app['status']); ?>"><?php echo esc_html(ucfirst($app['status'])); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=edubot-applications'); ?>" class="button">
                                View All Applications
                            </a>
                        </p>
                    <?php else: ?>
                        <p>No applications received yet. The chatbot will start collecting applications once configured.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="edubot-col-4">
                <div class="edubot-card">
                    <h2>Quick Setup</h2>
                    <div class="setup-checklist">
                        <div class="setup-item">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <a href="<?php echo admin_url('admin.php?page=edubot-school-settings'); ?>">
                                Configure School Settings
                            </a>
                        </div>
                        <div class="setup-item">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <a href="<?php echo admin_url('admin.php?page=edubot-academic-config'); ?>">
                                Set Up Academic Configuration
                            </a>
                        </div>
                        <div class="setup-item">
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <a href="<?php echo admin_url('admin.php?page=edubot-api-integrations'); ?>">
                                Configure API Integrations
                            </a>
                        </div>
                        <div class="setup-item">
                            <span class="dashicons dashicons-forms"></span>
                            <a href="<?php echo admin_url('admin.php?page=edubot-form-builder'); ?>">
                                Customize Application Form
                            </a>
                        </div>
                    </div>
                </div>

                <div class="edubot-card">
                    <h2>Support & Resources</h2>
                    <ul>
                        <li><a href="https://edubotpro.com/docs" target="_blank">Documentation</a></li>
                        <li><a href="https://edubotpro.com/support" target="_blank">Support Center</a></li>
                        <li><a href="https://edubotpro.com/changelog" target="_blank">What's New</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.edubot-dashboard {
    margin-top: 20px;
}
.edubot-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}
.edubot-col-8 {
    flex: 0 0 70%;
}
.edubot-col-4 {
    flex: 0 0 28%;
}
.edubot-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 4px;
}
.edubot-card h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}
.analytics-summary {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}
.stat-box {
    text-align: center;
    flex: 1;
    min-width: 120px;
}
.stat-box h3 {
    font-size: 2em;
    margin: 0;
    color: #4facfe;
}
.stat-box p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 0.9em;
}
.setup-checklist .setup-item {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}
.setup-checklist .setup-item .dashicons {
    margin-right: 10px;
    color: #4facfe;
}
.status {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 0.85em;
    font-weight: bold;
}
.status-pending {
    background: #fff3cd;
    color: #856404;
}
.status-approved {
    background: #d4edda;
    color: #155724;
}
.status-rejected {
    background: #f8d7da;
    color: #721c24;
}
</style>
