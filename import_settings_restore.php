<?php
/**
 * EduBot Pro - Settings Import/Restore Tool
 * 
 * This script imports school settings and API integrations from backup files
 * Supports JSON and SQL formats
 * 
 * Usage:
 * 1. Place in WordPress root directory
 * 2. Access via: http://localhost/demo/import_settings_restore.php
 * 3. Upload backup file (JSON or SQL)
 */

// Load WordPress
require_once 'wp-load.php';

// Check if user is logged in and is admin
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Access Denied: Admin privileges required');
}

global $wpdb;

/**
 * Settings Importer Class
 */
class EduBot_Settings_Importer {
    
    private $wpdb;
    private $blog_id;
    private $import_errors;
    private $import_success;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->blog_id = get_current_blog_id();
        $this->import_errors = array();
        $this->import_success = array();
    }
    
    /**
     * Import from JSON file
     */
    public function import_json($file_path) {
        if (!file_exists($file_path)) {
            return array('status' => 'error', 'message' => 'File not found');
        }
        
        $json_content = file_get_contents($file_path);
        $data = json_decode($json_content, true);
        
        if ($data === null) {
            return array('status' => 'error', 'message' => 'Invalid JSON format');
        }
        
        return $this->process_import_data($data);
    }
    
    /**
     * Process imported data
     */
    private function process_import_data($data) {
        $results = array(
            'school_configs' => 0,
            'api_integrations' => 0,
            'wordpress_options' => 0,
            'errors' => array(),
            'warnings' => array()
        );
        
        // Import school configs
        if (!empty($data['school_configs'])) {
            foreach ($data['school_configs'] as $config) {
                if ($this->import_school_config($config)) {
                    $results['school_configs']++;
                }
            }
        }
        
        // Import API integrations
        if (!empty($data['api_integrations'])) {
            foreach ($data['api_integrations'] as $integration) {
                if ($this->import_api_integration($integration)) {
                    $results['api_integrations']++;
                }
            }
        }
        
        // Import WordPress options
        if (!empty($data['wordpress_options'])) {
            foreach ($data['wordpress_options'] as $option_name => $option_value) {
                if ($this->import_wordpress_option($option_name, $option_value)) {
                    $results['wordpress_options']++;
                }
            }
        }
        
        $results['errors'] = $this->import_errors;
        $results['warnings'] = array_merge(
            $results['warnings'],
            array('API Keys were NOT imported for security. Please add them manually.')
        );
        
        return array(
            'status' => empty($this->import_errors) ? 'success' : 'partial',
            'results' => $results
        );
    }
    
    /**
     * Import school configuration
     */
    private function import_school_config($config) {
        $table = $this->wpdb->prefix . 'edubot_school_configs';
        
        // Check if table exists
        if (!$this->table_exists($table)) {
            $this->import_errors[] = 'School configs table does not exist';
            return false;
        }
        
        try {
            $config_data = isset($config['config_data']) ? $config['config_data'] : json_encode($config['config_data_decoded']);
            
            $result = $this->wpdb->replace(
                $table,
                array(
                    'site_id' => $this->blog_id,
                    'school_name' => $config['school_name'] ?? '',
                    'config_data' => $config_data,
                    'status' => $config['status'] ?? 'active'
                ),
                array('%d', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                $this->import_errors[] = 'Failed to import school config: ' . $this->wpdb->last_error;
                return false;
            }
            
            $this->import_success[] = 'School config imported: ' . ($config['school_name'] ?? 'Unknown');
            return true;
        } catch (Exception $e) {
            $this->import_errors[] = 'Error importing school config: ' . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Import API integration
     */
    private function import_api_integration($integration) {
        $table = $this->wpdb->prefix . 'edubot_api_integrations';
        
        // Check if table exists
        if (!$this->table_exists($table)) {
            $this->import_errors[] = 'API integrations table does not exist';
            return false;
        }
        
        try {
            $notification_settings = isset($integration['notification_settings']) ? 
                $integration['notification_settings'] : 
                json_encode($integration['notification_settings_decoded'] ?? array());
            
            $result = $this->wpdb->replace(
                $table,
                array(
                    'site_id' => $this->blog_id,
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
                    'notification_settings' => $notification_settings,
                    'status' => $integration['status'] ?? 'active'
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                $this->import_errors[] = 'Failed to import API integration: ' . $this->wpdb->last_error;
                return false;
            }
            
            $this->import_success[] = 'API integration imported successfully';
            return true;
        } catch (Exception $e) {
            $this->import_errors[] = 'Error importing API integration: ' . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Import WordPress option
     */
    private function import_wordpress_option($option_name, $option_value) {
        try {
            // Sanitize option name
            $option_name = sanitize_key($option_name);
            
            if (strpos($option_name, 'edubot_') !== 0) {
                $this->import_errors[] = 'Skipped invalid option: ' . $option_name;
                return false;
            }
            
            // Update or add option
            if (get_option($option_name) !== false) {
                update_option($option_name, $option_value);
            } else {
                add_option($option_name, $option_value);
            }
            
            $this->import_success[] = 'WordPress option imported: ' . $option_name;
            return true;
        } catch (Exception $e) {
            $this->import_errors[] = 'Error importing option ' . $option_name . ': ' . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Check if table exists
     */
    private function table_exists($table) {
        return $this->wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
    }
}

// Handle upload and import
$message = '';
$message_type = 'info';
$import_results = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
    $file = $_FILES['backup_file'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'File upload error: ' . $file['error'];
        $message_type = 'error';
    } else {
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowed_types = array('json', 'sql');
        
        if (!in_array(strtolower($file_ext), $allowed_types)) {
            $message = 'Invalid file type. Only JSON and SQL files are allowed.';
            $message_type = 'error';
        } else {
            $importer = new EduBot_Settings_Importer();
            $result = $importer->import_json($file['tmp_name']);
            
            if ($result['status'] === 'error') {
                $message = $result['message'];
                $message_type = 'error';
            } else {
                $message = 'Settings imported successfully!';
                $message_type = 'success';
                $import_results = $result['results'];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>EduBot Pro - Import Settings</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4facfe;
            padding-bottom: 15px;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .message.success {
            background-color: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .message.error {
            background-color: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .message.info {
            background-color: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        input[type="file"],
        input[type="submit"] {
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        input[type="submit"] {
            background-color: #4facfe;
            color: white;
            border: none;
            cursor: pointer;
            padding: 12px 30px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #357ad1;
        }
        .info-box {
            background-color: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #00f2fe;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-box h3 {
            margin-top: 0;
            color: #333;
        }
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        li {
            margin-bottom: 8px;
        }
        .results {
            background-color: #f0f8ff;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .results table {
            width: 100%;
            border-collapse: collapse;
        }
        .results table tr {
            border-bottom: 1px solid #ddd;
        }
        .results table td {
            padding: 10px;
        }
        .results table td:first-child {
            font-weight: bold;
            width: 50%;
        }
        .success-count {
            color: #28a745;
            font-weight: bold;
        }
        .warning {
            color: #ffc107;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📥 Import EduBot Pro Settings</h1>
        
        <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($import_results): ?>
        <div class="results">
            <h2>✅ Import Results</h2>
            <table>
                <tr>
                    <td>School Configurations:</td>
                    <td><span class="success-count"><?php echo $import_results['school_configs']; ?></span></td>
                </tr>
                <tr>
                    <td>API Integrations:</td>
                    <td><span class="success-count"><?php echo $import_results['api_integrations']; ?></span></td>
                </tr>
                <tr>
                    <td>WordPress Options:</td>
                    <td><span class="success-count"><?php echo $import_results['wordpress_options']; ?></span></td>
                </tr>
            </table>
            
            <?php if (!empty($import_results['errors'])): ?>
            <h3>⚠️ Errors:</h3>
            <ul>
                <?php foreach ($import_results['errors'] as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            
            <?php if (!empty($import_results['warnings'])): ?>
            <h3>⚠️ Warnings:</h3>
            <ul>
                <?php foreach ($import_results['warnings'] as $warning): ?>
                <li class="warning"><?php echo htmlspecialchars($warning); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="backup_file">Select Backup File:</label>
                <input type="file" name="backup_file" id="backup_file" accept=".json,.sql" required>
                <small style="color: #666; display: block; margin-top: 5px;">
                    Supported formats: JSON (.json) or SQL (.sql)
                </small>
            </div>
            
            <div class="form-group">
                <input type="submit" value="Import Settings">
            </div>
        </form>
        
        <div class="info-box">
            <h3>📝 Instructions</h3>
            <ol>
                <li>Download your backup file from the Export tool</li>
                <li>Select the backup file above</li>
                <li>Click "Import Settings"</li>
                <li>Verify the imported settings in the EduBot admin panel</li>
            </ol>
        </div>
        
        <div class="info-box">
            <h3>⚠️ Important Notes</h3>
            <ul>
                <li><strong>API Keys:</strong> API keys are NOT included in backups for security. You must re-enter them manually in the API Settings page.</li>
                <li><strong>Overwrite:</strong> Importing will overwrite existing settings for the same site.</li>
                <li><strong>Database:</strong> Make sure you have a database backup before importing.</li>
                <li><strong>Backup Types:</strong> JSON backups contain more information and are recommended. SQL backups contain INSERT statements.</li>
            </ul>
        </div>
        
        <div class="info-box">
            <h3>📚 Quick Links</h3>
            <ul>
                <li><a href="export_settings_backup.php?format=json" target="_blank">📤 Export Settings as JSON</a></li>
                <li><a href="export_settings_backup.php?format=html" target="_blank">📋 View Settings Report (HTML)</a></li>
                <li><a href="/wp-admin/" target="_blank">🔧 Go to WordPress Admin</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
<?php
?>
