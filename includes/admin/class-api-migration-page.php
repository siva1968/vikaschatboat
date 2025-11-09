<?php

/**
 * API Migration Admin Page
 * Allows admin to manually trigger migration from WordPress options to database table
 */
class EduBot_API_Migration_Page {

    /**
     * Page slug
     */
    const PAGE_SLUG = 'edubot-api-migration';

    /**
     * Capability required
     */
    const CAPABILITY = 'manage_options';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_page'));
        add_action('admin_post_edubot_api_migration_trigger', array($this, 'handle_migration_trigger'));
    }

    /**
     * Add admin menu page
     */
    public function add_admin_page() {
        add_submenu_page(
            'edubot-pro',
            __('API Migration', 'edubot-pro'),
            __('API Migration', 'edubot-pro'),
            self::CAPABILITY,
            self::PAGE_SLUG,
            array($this, 'render_page')
        );
    }

    /**
     * Render migration page
     */
    public function render_page() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(__('Insufficient permissions', 'edubot-pro'));
        }
        
        $migration_status = get_transient('edubot_api_migration_status');
        $migration_time = get_transient('edubot_api_migration_time');
        ?>
        <div class="wrap">
            <h1><?php _e('API Configuration Migration', 'edubot-pro'); ?></h1>
            
            <div class="edubot-migration-container">
                <!-- Status Box -->
                <div class="migration-status-box">
                    <h2><?php _e('Migration Status', 'edubot-pro'); ?></h2>
                    <?php $this->render_status_info(); ?>
                </div>

                <!-- Migration Info Box -->
                <div class="migration-info-box">
                    <h2><?php _e('About This Migration', 'edubot-pro'); ?></h2>
                    <p><?php _e('This tool migrates your Email, SMS, and WhatsApp API configurations from WordPress Options to the wp_edubot_api_integrations database table.', 'edubot-pro'); ?></p>
                    <h3><?php _e('Benefits:', 'edubot-pro'); ?></h3>
                    <ul>
                        <li><?php _e('Centralized API configuration storage', 'edubot-pro'); ?></li>
                        <li><?php _e('Better performance with fewer option queries', 'edubot-pro'); ?></li>
                        <li><?php _e('Easier multi-site management', 'edubot-pro'); ?></li>
                        <li><?php _e('Future-proof for new integrations', 'edubot-pro'); ?></li>
                        <li><?php _e('Backward compatibility with existing options', 'edubot-pro'); ?></li>
                    </ul>
                </div>

                <!-- Migration Trigger Box -->
                <div class="migration-action-box">
                    <h2><?php _e('Run Migration', 'edubot-pro'); ?></h2>
                    <?php
                    if (EduBot_API_Migration::migration_needed()) {
                        ?>
                        <p style="color: #d63638;">
                            <strong><?php _e('Migration Available:', 'edubot-pro'); ?></strong>
                            <?php _e('Your API settings are still stored in WordPress options. Click the button below to migrate them to the database table.', 'edubot-pro'); ?>
                        </p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('edubot_api_migration_nonce'); ?>
                            <input type="hidden" name="action" value="edubot_api_migration_trigger">
                            <button type="submit" class="button button-primary button-large migration-trigger-btn">
                                <?php _e('Start Migration Now', 'edubot-pro'); ?>
                            </button>
                            <p class="description">
                                <?php _e('Note: This will safely migrate your existing settings without deleting them from WordPress options (for backward compatibility).', 'edubot-pro'); ?>
                            </p>
                        </form>
                        <?php
                    } else {
                        ?>
                        <p style="color: #00a32a;">
                            <strong><?php _e('✓ Migration Complete:', 'edubot-pro'); ?></strong>
                            <?php _e('Your API settings are already stored in the database table. No action needed.', 'edubot-pro'); ?>
                        </p>
                        <?php
                    }
                    ?>
                </div>

                <!-- Migration History Box -->
                <div class="migration-history-box">
                    <h2><?php _e('Recent Migration History', 'edubot-pro'); ?></h2>
                    <?php $this->render_migration_history(); ?>
                </div>

                <!-- Technical Details Box -->
                <div class="migration-technical-box">
                    <h2><?php _e('Technical Details', 'edubot-pro'); ?></h2>
                    <h3><?php _e('Settings Being Migrated:', 'edubot-pro'); ?></h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Setting Name', 'edubot-pro'); ?></th>
                                <th><?php _e('Database Column', 'edubot-pro'); ?></th>
                                <th><?php _e('Current Status', 'edubot-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $this->render_settings_table(); ?>
                        </tbody>
                    </table>
                </div>

                <!-- Style -->
                <style>
                    .edubot-migration-container {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
                        gap: 20px;
                        margin: 20px 0;
                    }
                    
                    .migration-status-box,
                    .migration-info-box,
                    .migration-action-box,
                    .migration-history-box,
                    .migration-technical-box {
                        background: white;
                        border: 1px solid #ccc;
                        border-radius: 8px;
                        padding: 20px;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    }
                    
                    .migration-technical-box {
                        grid-column: 1 / -1;
                    }
                    
                    .migration-status-box h2,
                    .migration-info-box h2,
                    .migration-action-box h2,
                    .migration-history-box h2,
                    .migration-technical-box h2 {
                        margin-top: 0;
                        color: #333;
                        border-bottom: 2px solid #667eea;
                        padding-bottom: 10px;
                    }
                    
                    .migration-trigger-btn {
                        margin-top: 10px;
                    }
                    
                    .status-item {
                        display: flex;
                        justify-content: space-between;
                        padding: 10px 0;
                        border-bottom: 1px solid #eee;
                    }
                    
                    .status-item:last-child {
                        border-bottom: none;
                    }
                    
                    .status-item strong {
                        color: #333;
                    }
                    
                    .status-value {
                        font-family: monospace;
                    }
                    
                    .status-value.success {
                        color: #00a32a;
                    }
                    
                    .status-value.pending {
                        color: #d63638;
                    }
                </style>
            </div>
        </div>
        <?php
    }

    /**
     * Render current status information
     */
    private function render_status_info() {
        $api_settings = EduBot_API_Migration::get_api_settings();
        $migration_needed = EduBot_API_Migration::migration_needed();
        
        echo '<div class="status-item">';
        echo '<strong>' . __('Migration Status:', 'edubot-pro') . '</strong>';
        echo '<span class="status-value ' . ($migration_needed ? 'pending' : 'success') . '">';
        echo $migration_needed ? __('Pending', 'edubot-pro') : __('Complete', 'edubot-pro');
        echo '</span>';
        echo '</div>';
        
        echo '<div class="status-item">';
        echo '<strong>' . __('Email Provider:', 'edubot-pro') . '</strong>';
        echo '<span class="status-value">' . (!empty($api_settings['email_provider']) ? esc_html($api_settings['email_provider']) : __('Not configured', 'edubot-pro')) . '</span>';
        echo '</div>';
        
        echo '<div class="status-item">';
        echo '<strong>' . __('SMS Provider:', 'edubot-pro') . '</strong>';
        echo '<span class="status-value">' . (!empty($api_settings['sms_provider']) ? esc_html($api_settings['sms_provider']) : __('Not configured', 'edubot-pro')) . '</span>';
        echo '</div>';
        
        echo '<div class="status-item">';
        echo '<strong>' . __('WhatsApp Provider:', 'edubot-pro') . '</strong>';
        echo '<span class="status-value">' . (!empty($api_settings['whatsapp_provider']) ? esc_html($api_settings['whatsapp_provider']) : __('Not configured', 'edubot-pro')) . '</span>';
        echo '</div>';
    }

    /**
     * Render migration history
     */
    private function render_migration_history() {
        $migration_status = get_transient('edubot_api_migration_status');
        $migration_time = get_transient('edubot_api_migration_time');
        
        if ($migration_status && $migration_time) {
            echo '<p>';
            echo __('Last migration run:', 'edubot-pro') . ' <strong>' . esc_html($migration_time) . '</strong><br>';
            echo __('Status:', 'edubot-pro') . ' <strong style="color: ' . ($migration_status === 'success' ? '#00a32a' : '#d63638') . '">';
            echo $migration_status === 'success' ? __('SUCCESS', 'edubot-pro') : __('FAILED', 'edubot-pro');
            echo '</strong>';
            echo '</p>';
        } else {
            echo '<p>' . __('No migrations have been run yet.', 'edubot-pro') . '</p>';
        }
    }

    /**
     * Render settings table showing what will be migrated
     */
    private function render_settings_table() {
        $email_options = array(
            'edubot_email_service' => 'email_provider',
            'edubot_email_from_address' => 'email_from_address',
            'edubot_email_from_name' => 'email_from_name',
            'edubot_email_api_key' => 'email_api_key',
            'edubot_email_domain' => 'email_domain',
        );
        
        $sms_options = array(
            'edubot_sms_provider' => 'sms_provider',
            'edubot_sms_api_key' => 'sms_api_key',
            'edubot_sms_sender_id' => 'sms_sender_id',
        );
        
        $whatsapp_options = array(
            'edubot_whatsapp_provider' => 'whatsapp_provider',
            'edubot_whatsapp_token' => 'whatsapp_token',
            'edubot_whatsapp_phone_id' => 'whatsapp_phone_id',
            'edubot_whatsapp_template_namespace' => 'whatsapp_template_namespace',
            'edubot_whatsapp_template_name' => 'whatsapp_template_name',
            'edubot_whatsapp_template_language' => 'whatsapp_template_language',
        );
        
        echo '<tr style="background-color: #f5f5f5;"><td colspan="3"><strong>' . __('Email Settings', 'edubot-pro') . '</strong></td></tr>';
        foreach ($email_options as $option => $column) {
            $value = get_option($option);
            $status = !empty($value) ? '<span style="color: #00a32a;">✓ ' . __('Set', 'edubot-pro') . '</span>' : '<span style="color: #999;">- ' . __('Empty', 'edubot-pro') . '</span>';
            echo '<tr><td>' . esc_html($option) . '</td><td>' . esc_html($column) . '</td><td>' . $status . '</td></tr>';
        }
        
        echo '<tr style="background-color: #f5f5f5;"><td colspan="3"><strong>' . __('SMS Settings', 'edubot-pro') . '</strong></td></tr>';
        foreach ($sms_options as $option => $column) {
            $value = get_option($option);
            $status = !empty($value) ? '<span style="color: #00a32a;">✓ ' . __('Set', 'edubot-pro') . '</span>' : '<span style="color: #999;">- ' . __('Empty', 'edubot-pro') . '</span>';
            echo '<tr><td>' . esc_html($option) . '</td><td>' . esc_html($column) . '</td><td>' . $status . '</td></tr>';
        }
        
        echo '<tr style="background-color: #f5f5f5;"><td colspan="3"><strong>' . __('WhatsApp Settings', 'edubot-pro') . '</strong></td></tr>';
        foreach ($whatsapp_options as $option => $column) {
            $value = get_option($option);
            $status = !empty($value) ? '<span style="color: #00a32a;">✓ ' . __('Set', 'edubot-pro') . '</span>' : '<span style="color: #999;">- ' . __('Empty', 'edubot-pro') . '</span>';
            echo '<tr><td>' . esc_html($option) . '</td><td>' . esc_html($column) . '</td><td>' . $status . '</td></tr>';
        }
    }

    /**
     * Handle migration trigger from admin form
     */
    public function handle_migration_trigger() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(__('Insufficient permissions', 'edubot-pro'));
        }
        
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'edubot_api_migration_nonce')) {
            wp_die(__('Nonce verification failed', 'edubot-pro'));
        }
        
        // Run migration
        $result = EduBot_API_Migration::migrate_api_settings();
        
        // Store result in transient for display
        set_transient('edubot_api_migration_status', $result['success'] ? 'success' : 'failed', HOUR_IN_SECONDS);
        set_transient('edubot_api_migration_time', current_time('Y-m-d H:i:s'), HOUR_IN_SECONDS);
        
        // Log result
        if ($result['success']) {
            error_log('EduBot: API Migration triggered by admin - SUCCESS');
            error_log('EduBot: Migrated fields: ' . implode(', ', $result['migrated_fields']));
        } else {
            error_log('EduBot: API Migration triggered by admin - FAILED');
            foreach ($result['errors'] as $error) {
                error_log('EduBot: Migration Error - ' . $error);
            }
        }
        
        // Redirect back to page
        wp_safe_redirect(admin_url('admin.php?page=' . self::PAGE_SLUG));
        exit;
    }
}

// Initialize the page
if (is_admin()) {
    new EduBot_API_Migration_Page();
}
