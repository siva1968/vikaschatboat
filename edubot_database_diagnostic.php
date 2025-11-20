<?php
/**
 * EduBot Pro - Database Diagnostic and Migration Tool
 * 
 * This script checks the database structure and runs migrations if needed.
 * Place this file in your WordPress root directory and access it via browser.
 */

// Basic WordPress bootstrap (adjust path if needed)
define('WP_USE_THEMES', false);
require_once('./wp-config.php');
require_once('./wp-load.php');

// Security check - only allow admin users
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. Please log in as administrator.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>EduBot Pro - Database Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #f0fff0; padding: 10px; border: 1px solid green; }
        .error { color: red; background: #fff0f0; padding: 10px; border: 1px solid red; }
        .warning { color: orange; background: #fff8f0; padding: 10px; border: 1px solid orange; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 5px; }
        .btn:hover { background: #005a87; }
        .code { background: #f5f5f5; padding: 10px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>EduBot Pro - Database Diagnostic Tool</h1>
    
    <?php
    global $wpdb;
    
    $enquiries_table = $wpdb->prefix . 'edubot_enquiries';
    $current_version = get_option('edubot_enquiries_db_version', '0.0.0');
    
    echo "<div class='code'>Current Database Version: {$current_version}</div>";
    echo "<div class='code'>Plugin Version: " . (defined('EDUBOT_PRO_VERSION') ? EDUBOT_PRO_VERSION : 'Not defined') . "</div>";
    
    // Handle migration request
    if (isset($_POST['run_migration'])) {
        echo "<h2>Running Migration...</h2>";
        
        try {
            // Include migration class
            if (file_exists(WP_PLUGIN_DIR . '/edubot-pro/includes/class-enquiries-migration.php')) {
                require_once WP_PLUGIN_DIR . '/edubot-pro/includes/class-enquiries-migration.php';
                
                if (class_exists('EduBot_Enquiries_Migration')) {
                    EduBot_Enquiries_Migration::run_migration();
                    echo "<div class='success'>Migration completed successfully!</div>";
                } else {
                    echo "<div class='error'>EduBot_Enquiries_Migration class not found!</div>";
                }
            } else {
                echo "<div class='error'>Migration file not found!</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>Migration failed: " . $e->getMessage() . "</div>";
        }
        
        // Refresh version after migration
        $current_version = get_option('edubot_enquiries_db_version', '0.0.0');
        echo "<div class='code'>New Database Version: {$current_version}</div>";
    }
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$enquiries_table}'") == $enquiries_table;
    
    if ($table_exists) {
        echo "<div class='success'>✓ Table '{$enquiries_table}' exists</div>";
        
        // Get table structure
        $columns = $wpdb->get_results("DESCRIBE {$enquiries_table}");
        
        echo "<h2>Current Table Structure</h2>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        $required_columns = array('gclid', 'fbclid', 'click_id_data', 'ip_address', 'user_agent', 'utm_data', 'whatsapp_sent', 'email_sent', 'sms_sent');
        $existing_columns = array();
        
        foreach ($columns as $column) {
            $exists = in_array($column->Field, $required_columns);
            $class = $exists ? 'success' : '';
            echo "<tr class='{$class}'>";
            echo "<td>{$column->Field}</td>";
            echo "<td>{$column->Type}</td>";
            echo "<td>{$column->Null}</td>";
            echo "<td>{$column->Key}</td>";
            echo "<td>{$column->Default}</td>";
            echo "</tr>";
            
            $existing_columns[] = $column->Field;
        }
        echo "</table>";
        
        // Check for missing columns
        $missing_columns = array_diff($required_columns, $existing_columns);
        
        if (!empty($missing_columns)) {
            echo "<div class='warning'>⚠ Missing columns: " . implode(', ', $missing_columns) . "</div>";
            echo "<div class='warning'>You need to run the database migration.</div>";
        } else {
            echo "<div class='success'>✓ All required columns are present</div>";
        }
        
        // Show sample data
        $sample_data = $wpdb->get_results("SELECT * FROM {$enquiries_table} ORDER BY created_at DESC LIMIT 5");
        
        if ($sample_data) {
            echo "<h2>Sample Data (Latest 5 Records)</h2>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Enquiry Number</th><th>Student Name</th><th>Email</th><th>gclid</th><th>fbclid</th><th>Created</th></tr>";
            
            foreach ($sample_data as $row) {
                echo "<tr>";
                echo "<td>{$row->id}</td>";
                echo "<td>{$row->enquiry_number}</td>";
                echo "<td>" . (isset($row->student_name) ? $row->student_name : 'N/A') . "</td>";
                echo "<td>" . (isset($row->email) ? $row->email : 'N/A') . "</td>";
                echo "<td>" . (isset($row->gclid) ? $row->gclid : 'N/A') . "</td>";
                echo "<td>" . (isset($row->fbclid) ? $row->fbclid : 'N/A') . "</td>";
                echo "<td>{$row->created_at}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='warning'>No data found in the table</div>";
        }
        
    } else {
        echo "<div class='error'>✗ Table '{$enquiries_table}' does not exist</div>";
        echo "<div class='warning'>The table needs to be created. Run the migration below.</div>";
    }
    
    // Migration form
    echo "<h2>Database Migration</h2>";
    echo "<form method='post'>";
    echo "<button type='submit' name='run_migration' class='btn'>Run Database Migration</button>";
    echo "</form>";
    
    echo "<h2>System Information</h2>";
    echo "<table>";
    echo "<tr><td><strong>WordPress Version</strong></td><td>" . get_bloginfo('version') . "</td></tr>";
    echo "<tr><td><strong>PHP Version</strong></td><td>" . PHP_VERSION . "</td></tr>";
    echo "<tr><td><strong>MySQL Version</strong></td><td>" . $wpdb->db_version() . "</td></tr>";
    echo "<tr><td><strong>Plugin Path</strong></td><td>" . WP_PLUGIN_DIR . "/edubot-pro/</td></tr>";
    echo "<tr><td><strong>Plugin Active</strong></td><td>" . (is_plugin_active('edubot-pro/edubot-pro.php') ? 'Yes' : 'No') . "</td></tr>";
    echo "</table>";
    
    ?>
    
    <h2>Manual SQL Commands (if needed)</h2>
    <p>If the migration doesn't work automatically, you can run these SQL commands manually in phpMyAdmin:</p>
    
    <div class='code'>
-- Add click ID tracking columns<br>
ALTER TABLE <?php echo $enquiries_table; ?> ADD COLUMN gclid varchar(255) NULL;<br>
ALTER TABLE <?php echo $enquiries_table; ?> ADD COLUMN fbclid varchar(255) NULL;<br>
ALTER TABLE <?php echo $enquiries_table; ?> ADD COLUMN click_id_data longtext NULL;<br>
<br>
-- Add indexes for performance<br>
ALTER TABLE <?php echo $enquiries_table; ?> ADD INDEX idx_gclid (gclid);<br>
ALTER TABLE <?php echo $enquiries_table; ?> ADD INDEX idx_fbclid (fbclid);<br>
<br>
-- Update database version<br>
UPDATE <?php echo $wpdb->prefix; ?>options SET option_value = '1.3.1' WHERE option_name = 'edubot_enquiries_db_version';
    </div>
    
    <p><strong>Note:</strong> After running migrations, delete this diagnostic file for security.</p>
    
</body>
</html>
