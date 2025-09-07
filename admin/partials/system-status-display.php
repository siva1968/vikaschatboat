<?php

/**
 * System Status Display Page
 * Shows comprehensive system health and configuration information
 */

// Ensure this file is called from WordPress
if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-admin-tools"></span>
        EduBot Pro - System Status
    </h1>

    <!-- Health Status Overview -->
    <div class="edubot-status-overview" style="margin-bottom: 30px;">
        <?php
        $status_class = 'notice-success';
        $status_icon = 'yes';
        $status_text = 'System is healthy';
        
        if ($health_check['status'] === 'warning') {
            $status_class = 'notice-warning';
            $status_icon = 'warning';
            $status_text = 'System has warnings';
        } elseif ($health_check['status'] === 'critical') {
            $status_class = 'notice-error';
            $status_icon = 'no';
            $status_text = 'System has critical issues';
        }
        ?>
        
        <div class="notice <?php echo $status_class; ?>" style="padding: 15px;">
            <h2 style="margin: 0;">
                <span class="dashicons dashicons-<?php echo $status_icon; ?>"></span>
                <?php echo esc_html($status_text); ?>
            </h2>
            <p style="margin: 10px 0 0 0;"><?php echo esc_html($health_check['message']); ?></p>
        </div>
    </div>

    <!-- System Information Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        
        <!-- Plugin Information -->
        <div class="edubot-status-section" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0; color: #2271b1;">Plugin Information</h2>
            <table class="widefat" style="border: none;">
                <tbody>
                    <tr>
                        <td style="font-weight: bold;">Plugin Version:</td>
                        <td><?php echo esc_html($plugin_info['version']); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Database Version:</td>
                        <td><?php echo esc_html($plugin_info['db_version']); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Plugin Path:</td>
                        <td style="word-break: break-all; font-size: 12px;"><?php echo esc_html($plugin_info['plugin_path']); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Plugin URL:</td>
                        <td style="word-break: break-all; font-size: 12px;"><?php echo esc_html($plugin_info['plugin_url']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Environment Information -->
        <div class="edubot-status-section" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0; color: #2271b1;">Environment Information</h2>
            <table class="widefat" style="border: none;">
                <tbody>
                    <tr>
                        <td style="font-weight: bold;">WordPress Version:</td>
                        <td><?php echo esc_html($environment_info['wp_version']); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">PHP Version:</td>
                        <td><?php echo esc_html($environment_info['php_version']); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Memory Limit:</td>
                        <td><?php echo esc_html($environment_info['memory_limit']); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Max Execution Time:</td>
                        <td><?php echo esc_html($environment_info['max_execution_time']); ?>s</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Upload Max Size:</td>
                        <td><?php echo esc_html($environment_info['upload_max_filesize']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Database Information -->
    <div class="edubot-status-section" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin-top: 0; color: #2271b1;">Database Information</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div>
                <strong>MySQL Version:</strong> <?php echo esc_html($db_info['mysql_version']); ?>
            </div>
            <div>
                <strong>Charset:</strong> <?php echo esc_html($db_info['charset']); ?>
            </div>
            <div>
                <strong>Collation:</strong> <?php echo esc_html($db_info['collate']); ?>
            </div>
        </div>

        <!-- Database Tables Status -->
        <h3>Database Tables</h3>
        <div style="margin-bottom: 20px;">
            <?php
            $status_icon = $db_info['enquiries_table_exists'] ? 'yes' : 'no';
            $status_color = $db_info['enquiries_table_exists'] ? '#46b450' : '#dc3232';
            $status_text = $db_info['enquiries_table_exists'] ? 'EXISTS' : 'MISSING';
            ?>
            <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-<?php echo $status_icon; ?>" style="color: <?php echo $status_color; ?>; font-size: 20px;"></span>
                <div>
                    <strong>edubot_enquiries</strong> - Main enquiries table
                    <div style="font-size: 12px; color: #666;"><?php echo $status_text; ?></div>
                </div>
            </div>
        </div>

        <!-- Database Column Status -->
        <?php if ($db_info['enquiries_table_exists'] && isset($db_info['required_columns'])): ?>
        <h3>Required Columns Status</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
            <?php foreach ($db_info['required_columns'] as $column => $exists): ?>
                <?php
                $status_icon = $exists ? 'yes' : 'no';
                $status_color = $exists ? '#46b450' : '#dc3232';
                $status_text = $exists ? 'EXISTS' : 'MISSING';
                ?>
                <div style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
                    <span class="dashicons dashicons-<?php echo $status_icon; ?>" style="color: <?php echo $status_color; ?>;"></span>
                    <div style="font-size: 11px; margin-top: 5px; font-weight: bold;"><?php echo esc_html($column); ?></div>
                    <div style="font-size: 10px; color: #666;"><?php echo $status_text; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Migration Button -->
        <?php
        $missing_columns = array_filter($db_info['required_columns'], function($exists) { return !$exists; });
        if (!empty($missing_columns)):
        ?>
        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
            <h4 style="margin: 0 0 10px 0; color: #856404;">Migration Required</h4>
            <p style="margin: 0 0 15px 0; color: #856404;">Some database columns are missing. Click the button below to run the migration.</p>
            <button type="button" id="run-migration-btn" class="button button-primary">Run Database Migration</button>
            <div id="migration-status" style="margin-top: 10px;"></div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Loaded Classes -->
    <div class="edubot-status-section" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin-top: 0; color: #2271b1;">Loaded Classes</h2>
        <p style="color: #666;">Classes successfully loaded by the autoloader:</p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px;">
            <?php foreach ($available_classes as $class): ?>
                <div style="padding: 8px; background: #f0f0f1; border-radius: 4px; font-family: monospace; font-size: 12px;">
                    <span class="dashicons dashicons-yes" style="color: #46b450;"></span>
                    <?php echo esc_html($class); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Health Check Details -->
    <?php if (isset($health_check['issues']) && !empty($health_check['issues'])): ?>
    <div class="edubot-status-section" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin-top: 0; color: #dc3232;">Issues Found</h2>
        <?php foreach ($health_check['issues'] as $category => $issues): ?>
            <h3 style="text-transform: capitalize; color: #333;"><?php echo esc_html($category); ?></h3>
            <ul style="margin-left: 20px;">
                <?php foreach ($issues as $issue): ?>
                    <li style="color: #dc3232; margin-bottom: 5px;">
                        <span class="dashicons dashicons-warning" style="font-size: 16px; vertical-align: middle;"></span>
                        <?php echo esc_html($issue); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- System Actions -->
    <div class="edubot-status-section" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #2271b1;">System Actions</h2>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <button type="button" class="button button-primary" onclick="runHealthCheck()">
                <span class="dashicons dashicons-update"></span> Run Health Check
            </button>
            
            <button type="button" class="button button-secondary" onclick="clearErrorLogs()">
                <span class="dashicons dashicons-trash"></span> Clear Error Logs
            </button>
            
            <button type="button" class="button button-secondary" onclick="downloadSystemInfo()">
                <span class="dashicons dashicons-download"></span> Download System Info
            </button>

            <?php if (class_exists('EduBot_Analytics_Migration')): ?>
            <button type="button" class="button button-secondary" onclick="runDatabaseMigration()">
                <span class="dashicons dashicons-database"></span> Run Database Migration
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function runHealthCheck() {
    location.reload();
}

function clearErrorLogs() {
    if (confirm('Are you sure you want to clear all error logs?')) {
        jQuery.post(ajaxurl, {
            action: 'edubot_clear_error_logs',
            nonce: '<?php echo wp_create_nonce('edubot_admin_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                alert('Error logs cleared successfully');
            } else {
                alert('Failed to clear error logs: ' + response.data);
            }
        });
    }
}

function downloadSystemInfo() {
    const systemInfo = {
        plugin: <?php echo wp_json_encode($plugin_info); ?>,
        environment: <?php echo wp_json_encode($environment_info); ?>,
        database: <?php echo wp_json_encode($db_info); ?>,
        health: <?php echo wp_json_encode($health_check); ?>,
        classes: <?php echo wp_json_encode($available_classes); ?>
    };
    
    const blob = new Blob([JSON.stringify(systemInfo, null, 2)], {type: 'application/json'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'edubot-pro-system-info.json';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function runDatabaseMigration() {
    if (confirm('Are you sure you want to run database migration? This will update your database schema.')) {
        jQuery.post(ajaxurl, {
            action: 'edubot_run_migration',
            nonce: '<?php echo wp_create_nonce('edubot_admin_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                alert('Database migration completed successfully');
                location.reload();
            } else {
                alert('Migration failed: ' + response.data);
            }
        });
    }
}
</script>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    
    div[style*="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr))"] {
        grid-template-columns: 1fr !important;
    }
    
    div[style*="display: flex"] {
        flex-direction: column !important;
    }
}

.edubot-status-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease;
}
</style>
