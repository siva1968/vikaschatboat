<?php
/**
 * EduBot Pro - Settings Export/Backup Tool
 * 
 * This script exports all school settings and API integrations from the database
 * Supports multiple export formats: JSON, SQL, CSV
 * 
 * Usage:
 * 1. Place in WordPress root directory
 * 2. Access via: http://localhost/demo/export_settings_backup.php?format=json
 * 3. Formats available: json, sql, csv, html
 */

// Load WordPress
require_once 'wp-load.php';

// Check if user is logged in and is admin
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Access Denied: Admin privileges required');
}

global $wpdb;

/**
 * Main Settings Exporter Class
 */
class EduBot_Settings_Exporter {
    
    private $wpdb;
    private $blog_id;
    private $export_data;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->blog_id = get_current_blog_id();
        $this->export_data = array(
            'export_time' => current_time('mysql'),
            'blog_id' => $this->blog_id,
            'site_url' => get_site_url(),
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => defined('EDUBOT_PRO_VERSION') ? EDUBOT_PRO_VERSION : 'Unknown',
            'school_configs' => array(),
            'api_integrations' => array(),
            'wordpress_options' => array(),
        );
    }
    
    /**
     * Export school configurations from database
     */
    public function export_school_configs() {
        $table = $this->wpdb->prefix . 'edubot_school_configs';
        
        // Check if table exists
        if (!$this->table_exists($table)) {
            return array('status' => 'error', 'message' => 'School configs table does not exist');
        }
        
        $configs = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT id, site_id, school_name, config_data, status, created_at, updated_at 
                 FROM $table WHERE site_id = %d ORDER BY id DESC",
                $this->blog_id
            ),
            ARRAY_A
        );
        
        foreach ($configs as &$config) {
            if (!empty($config['config_data'])) {
                $config['config_data_decoded'] = json_decode($config['config_data'], true);
            }
        }
        
        $this->export_data['school_configs'] = $configs;
        return array('status' => 'success', 'count' => count($configs));
    }
    
    /**
     * Export API integrations from database
     */
    public function export_api_integrations() {
        $table = $this->wpdb->prefix . 'edubot_api_integrations';
        
        // Check if table exists
        if (!$this->table_exists($table)) {
            return array('status' => 'error', 'message' => 'API integrations table does not exist');
        }
        
        $integrations = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT id, site_id, whatsapp_provider, whatsapp_phone_id, whatsapp_business_account_id,
                        email_provider, email_from_address, email_from_name, email_domain,
                        sms_provider, sms_sender_id, openai_model,
                        notification_settings, status, created_at, updated_at
                 FROM $table WHERE site_id = %d ORDER BY id DESC",
                $this->blog_id
            ),
            ARRAY_A
        );
        
        // Note: API keys are NOT exported for security reasons
        // Users should save these separately in a secure location
        
        // Mask sensitive data
        foreach ($integrations as &$integration) {
            // Mask tokens and API keys
            if (isset($integration['whatsapp_phone_id'])) {
                $integration['whatsapp_phone_id_masked'] = '***' . substr($integration['whatsapp_phone_id'], -4);
            }
            if (isset($integration['email_domain'])) {
                $integration['email_domain_set'] = !empty($integration['email_domain']) ? 'YES' : 'NO';
            }
            if (isset($integration['sms_sender_id'])) {
                $integration['sms_sender_id_masked'] = !empty($integration['sms_sender_id']) ? '***' . substr($integration['sms_sender_id'], -3) : 'NOT SET';
            }
            if (isset($integration['notification_settings'])) {
                $integration['notification_settings_decoded'] = json_decode($integration['notification_settings'], true);
            }
        }
        
        $this->export_data['api_integrations'] = $integrations;
        return array('status' => 'success', 'count' => count($integrations));
    }
    
    /**
     * Export critical WordPress options
     */
    public function export_wordpress_options() {
        $edubot_options = array(
            'edubot_welcome_message',
            'edubot_current_school_id',
            'edubot_configured_boards',
            'edubot_default_board',
            'edubot_board_selection_required',
            'edubot_academic_calendar_type',
            'edubot_custom_start_month',
            'edubot_available_academic_years',
            'edubot_admission_period',
            'edubot_default_academic_year',
        );
        
        foreach ($edubot_options as $option_name) {
            $value = get_option($option_name);
            if ($value !== false) {
                $this->export_data['wordpress_options'][$option_name] = $value;
            }
        }
        
        return array('status' => 'success', 'count' => count($this->export_data['wordpress_options']));
    }
    
    /**
     * Check if table exists
     */
    private function table_exists($table) {
        return $this->wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
    }
    
    /**
     * Export to JSON format
     */
    public function export_as_json() {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="edubot-settings-backup-' . date('Y-m-d-H-i-s') . '.json"');
        
        echo json_encode($this->export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Export to SQL format (INSERT statements)
     */
    public function export_as_sql() {
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="edubot-settings-backup-' . date('Y-m-d-H-i-s') . '.sql"');
        
        $sql = "-- EduBot Pro Settings Backup\n";
        $sql .= "-- Exported: " . current_time('mysql') . "\n";
        $sql .= "-- Blog ID: " . $this->blog_id . "\n";
        $sql .= "-- Site URL: " . get_site_url() . "\n\n";
        
        // School Configs INSERT statements
        if (!empty($this->export_data['school_configs'])) {
            $sql .= "-- ========================================\n";
            $sql .= "-- SCHOOL CONFIGURATIONS\n";
            $sql .= "-- ========================================\n\n";
            
            foreach ($this->export_data['school_configs'] as $config) {
                $sql .= $this->generate_insert_statement(
                    $this->wpdb->prefix . 'edubot_school_configs',
                    array(
                        'site_id' => $config['site_id'],
                        'school_name' => $config['school_name'],
                        'config_data' => $config['config_data'],
                        'status' => $config['status']
                    )
                );
            }
        }
        
        // API Integrations INSERT statements (WITHOUT sensitive keys)
        if (!empty($this->export_data['api_integrations'])) {
            $sql .= "\n-- ========================================\n";
            $sql .= "-- API INTEGRATIONS (keys masked for security)\n";
            $sql .= "-- ========================================\n\n";
            
            foreach ($this->export_data['api_integrations'] as $integration) {
                $safe_integration = array(
                    'site_id' => $integration['site_id'],
                    'whatsapp_provider' => $integration['whatsapp_provider'] ?? '',
                    'whatsapp_phone_id' => $integration['whatsapp_phone_id'] ?? '',
                    'whatsapp_business_account_id' => $integration['whatsapp_business_account_id'] ?? '',
                    'email_provider' => $integration['email_provider'] ?? '',
                    'email_from_address' => $integration['email_from_address'] ?? '',
                    'email_from_name' => $integration['email_from_name'] ?? '',
                    'email_domain' => $integration['email_domain'] ?? '',
                    'sms_provider' => $integration['sms_provider'] ?? '',
                    'sms_sender_id' => $integration['sms_sender_id'] ?? '',
                    'openai_model' => $integration['openai_model'] ?? '',
                    'notification_settings' => $integration['notification_settings'] ?? '{}',
                    'status' => $integration['status'] ?? 'active'
                );
                
                $sql .= $this->generate_insert_statement(
                    $this->wpdb->prefix . 'edubot_api_integrations',
                    $safe_integration
                );
            }
        }
        
        // WordPress Options
        if (!empty($this->export_data['wordpress_options'])) {
            $sql .= "\n-- ========================================\n";
            $sql .= "-- WORDPRESS OPTIONS\n";
            $sql .= "-- ========================================\n\n";
            
            foreach ($this->export_data['wordpress_options'] as $option_name => $option_value) {
                $sql .= $this->generate_options_update($option_name, $option_value);
            }
        }
        
        $sql .= "\n-- Backup completed: " . current_time('mysql') . "\n";
        
        echo $sql;
    }
    
    /**
     * Generate INSERT statement
     */
    private function generate_insert_statement($table, $data) {
        $columns = array_keys($data);
        $values = array_values($data);
        
        $safe_values = array_map(function($val) {
            if (is_null($val)) {
                return 'NULL';
            }
            return "'" . str_replace("'", "''", $val) . "'";
        }, $values);
        
        return sprintf(
            "INSERT INTO %s (%s) VALUES (%s);\n",
            $table,
            implode(', ', $columns),
            implode(', ', $safe_values)
        );
    }
    
    /**
     * Generate WordPress options update statements
     */
    private function generate_options_update($option_name, $option_value) {
        if (is_array($option_value) || is_object($option_value)) {
            $option_value = json_encode($option_value);
        }
        
        $safe_value = str_replace("'", "''", $option_value);
        return sprintf(
            "UPDATE wp_options SET option_value = '%s' WHERE option_name = '%s';\n",
            $safe_value,
            $option_name
        );
    }
    
    /**
     * Export to HTML format (readable report)
     */
    public function export_as_html() {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>EduBot Pro Settings Report</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    background-color: #f5f5f5;
                }
                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                    background-color: white;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                h1 {
                    color: #333;
                    border-bottom: 3px solid #4facfe;
                    padding-bottom: 10px;
                }
                h2 {
                    color: #4facfe;
                    margin-top: 30px;
                    padding-top: 10px;
                    border-left: 4px solid #4facfe;
                    padding-left: 10px;
                }
                .info-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                    margin-bottom: 20px;
                }
                .info-box {
                    background-color: #f9f9f9;
                    padding: 10px;
                    border-left: 4px solid #00f2fe;
                    border-radius: 4px;
                }
                .info-box strong {
                    color: #333;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                table th {
                    background-color: #4facfe;
                    color: white;
                    padding: 12px;
                    text-align: left;
                    font-weight: bold;
                }
                table td {
                    padding: 10px;
                    border-bottom: 1px solid #ddd;
                }
                table tr:hover {
                    background-color: #f5f5f5;
                }
                .config-section {
                    background-color: #f0f8ff;
                    padding: 15px;
                    border-radius: 4px;
                    margin-bottom: 15px;
                }
                .json-block {
                    background-color: #272822;
                    color: #f8f8f2;
                    padding: 15px;
                    border-radius: 4px;
                    overflow-x: auto;
                    font-family: 'Courier New', monospace;
                    font-size: 12px;
                }
                .success {
                    color: #28a745;
                    font-weight: bold;
                }
                .warning {
                    color: #ffc107;
                    font-weight: bold;
                }
                .error {
                    color: #dc3545;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>📋 EduBot Pro Settings Report</h1>
                
                <h2>Export Information</h2>
                <div class="info-grid">
                    <div class="info-box">
                        <strong>Export Time:</strong> <?php echo $this->export_data['export_time']; ?>
                    </div>
                    <div class="info-box">
                        <strong>Blog ID:</strong> <?php echo $this->export_data['blog_id']; ?>
                    </div>
                    <div class="info-box">
                        <strong>Site URL:</strong> <?php echo $this->export_data['site_url']; ?>
                    </div>
                    <div class="info-box">
                        <strong>WordPress Version:</strong> <?php echo $this->export_data['wordpress_version']; ?>
                    </div>
                    <div class="info-box">
                        <strong>EduBot Version:</strong> <?php echo $this->export_data['plugin_version']; ?>
                    </div>
                </div>
                
                <!-- School Configurations -->
                <?php if (!empty($this->export_data['school_configs'])): ?>
                <h2>🏫 School Configurations</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>School Name</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->export_data['school_configs'] as $config): ?>
                        <tr>
                            <td><?php echo $config['id']; ?></td>
                            <td><?php echo htmlspecialchars($config['school_name']); ?></td>
                            <td><span class="success"><?php echo $config['status']; ?></span></td>
                            <td><?php echo $config['created_at']; ?></td>
                            <td><?php echo $config['updated_at']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Config Details -->
                <?php foreach ($this->export_data['school_configs'] as $config): ?>
                <div class="config-section">
                    <h3>📝 Configuration Details: <?php echo htmlspecialchars($config['school_name']); ?></h3>
                    <div class="json-block">
                        <pre><?php echo json_encode($config['config_data_decoded'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- API Integrations -->
                <?php if (!empty($this->export_data['api_integrations'])): ?>
                <h2>🔌 API Integrations</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email Provider</th>
                            <th>WhatsApp Provider</th>
                            <th>SMS Provider</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->export_data['api_integrations'] as $integration): ?>
                        <tr>
                            <td><?php echo $integration['id']; ?></td>
                            <td><?php echo htmlspecialchars($integration['email_provider'] ?? 'Not Set'); ?></td>
                            <td><?php echo htmlspecialchars($integration['whatsapp_provider'] ?? 'Not Set'); ?></td>
                            <td><?php echo htmlspecialchars($integration['sms_provider'] ?? 'Not Set'); ?></td>
                            <td><span class="success"><?php echo $integration['status']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Integration Details -->
                <?php foreach ($this->export_data['api_integrations'] as $integration): ?>
                <div class="config-section">
                    <h3>🔐 Integration Details (ID: <?php echo $integration['id']; ?>)</h3>
                    
                    <h4>📧 Email Configuration</h4>
                    <table>
                        <tr><td><strong>Provider:</strong></td><td><?php echo htmlspecialchars($integration['email_provider'] ?? 'Not Set'); ?></td></tr>
                        <tr><td><strong>From Address:</strong></td><td><?php echo htmlspecialchars($integration['email_from_address'] ?? 'Not Set'); ?></td></tr>
                        <tr><td><strong>From Name:</strong></td><td><?php echo htmlspecialchars($integration['email_from_name'] ?? 'Not Set'); ?></td></tr>
                        <tr><td><strong>Domain:</strong></td><td><?php echo htmlspecialchars($integration['email_domain_set'] ?? 'Not Set'); ?></td></tr>
                    </table>
                    
                    <h4>💬 WhatsApp Configuration</h4>
                    <table>
                        <tr><td><strong>Provider:</strong></td><td><?php echo htmlspecialchars($integration['whatsapp_provider'] ?? 'Not Set'); ?></td></tr>
                        <tr><td><strong>Phone ID:</strong></td><td><?php echo $integration['whatsapp_phone_id_masked']; ?></td></tr>
                        <tr><td><strong>Business Account ID:</strong></td><td><?php echo htmlspecialchars($integration['whatsapp_business_account_id'] ?? 'Not Set'); ?></td></tr>
                    </table>
                    
                    <h4>📱 SMS Configuration</h4>
                    <table>
                        <tr><td><strong>Provider:</strong></td><td><?php echo htmlspecialchars($integration['sms_provider'] ?? 'Not Set'); ?></td></tr>
                        <tr><td><strong>Sender ID:</strong></td><td><?php echo $integration['sms_sender_id_masked']; ?></td></tr>
                    </table>
                    
                    <h4>⚙️ Other Settings</h4>
                    <table>
                        <tr><td><strong>OpenAI Model:</strong></td><td><?php echo htmlspecialchars($integration['openai_model'] ?? 'Not Set'); ?></td></tr>
                        <tr><td><strong>Status:</strong></td><td><span class="success"><?php echo $integration['status']; ?></span></td></tr>
                        <tr><td><strong>Created:</strong></td><td><?php echo $integration['created_at']; ?></td></tr>
                        <tr><td><strong>Updated:</strong></td><td><?php echo $integration['updated_at']; ?></td></tr>
                    </table>
                    
                    <h4>🔔 Notification Settings</h4>
                    <div class="json-block">
                        <pre><?php echo json_encode($integration['notification_settings_decoded'], JSON_PRETTY_PRINT); ?></pre>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- WordPress Options -->
                <?php if (!empty($this->export_data['wordpress_options'])): ?>
                <h2>⚙️ WordPress Options</h2>
                <div class="config-section">
                    <div class="json-block">
                        <pre><?php echo json_encode($this->export_data['wordpress_options'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
                    </div>
                </div>
                <?php endif; ?>
                
                <h2>📝 Export Notes</h2>
                <div class="config-section">
                    <p><strong>✅ Included:</strong></p>
                    <ul>
                        <li>School configurations (all settings)</li>
                        <li>API integrations (non-sensitive fields only)</li>
                        <li>WordPress options (EduBot-related)</li>
                        <li>Notification settings</li>
                        <li>Timestamps and status information</li>
                    </ul>
                    <p><strong>❌ NOT Included (for security):</strong></p>
                    <ul>
                        <li>WhatsApp API tokens</li>
                        <li>Email API keys</li>
                        <li>SMS API keys</li>
                        <li>OpenAI API keys</li>
                        <li>SMTP passwords</li>
                    </ul>
                    <p class="warning">⚠️ IMPORTANT: Save your API keys separately in a secure location!</p>
                </div>
                
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * Run export process
     */
    public function run() {
        $this->export_school_configs();
        $this->export_api_integrations();
        $this->export_wordpress_options();
    }
}

// Handle export request
$format = isset($_GET['format']) ? sanitize_key($_GET['format']) : 'json';
$exporter = new EduBot_Settings_Exporter();
$exporter->run();

switch ($format) {
    case 'sql':
        $exporter->export_as_sql();
        break;
    case 'html':
        $exporter->export_as_html();
        break;
    case 'json':
    default:
        $exporter->export_as_json();
        break;
}
?>
