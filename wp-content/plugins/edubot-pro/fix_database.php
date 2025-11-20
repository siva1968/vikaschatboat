<?php
/**
 * EduBot Pro - Database Diagnostic and Fix Script
 * 
 * Run this script to diagnose and fix database issues
 * Access via: yourdomain.com/wp-content/plugins/AI ChatBoat/fix_database.php
 */

// WordPress environment setup
if (!defined('ABSPATH')) {
    // Try to load WordPress
    $wp_load_paths = array(
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php'
    );
    
    foreach ($wp_load_paths as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            require_once __DIR__ . '/' . $path;
            break;
        }
    }
    
    if (!defined('ABSPATH')) {
        die('WordPress environment not found. Please run this from WordPress admin or place in correct directory.');
    }
}

// Security check - only admins can run this
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Administrator privileges required.');
}

echo "<h1>EduBot Pro - Database Diagnostic & Fix</h1>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . " UTC</p>";

global $wpdb;
$enquiries_table = $wpdb->prefix . 'edubot_enquiries';

echo "<h2>1. Database Connection Test</h2>";
try {
    $mysql_version = $wpdb->db_version();
    echo "✅ <strong>MySQL Connection:</strong> Success (Version: {$mysql_version})<br>";
} catch (Exception $e) {
    echo "❌ <strong>MySQL Connection:</strong> Failed - " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>2. Table Existence Check</h2>";
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$enquiries_table}'");

if ($table_exists) {
    echo "✅ <strong>Table {$enquiries_table}:</strong> EXISTS<br>";
} else {
    echo "❌ <strong>Table {$enquiries_table}:</strong> MISSING<br>";
}

echo "<h2>3. Column Structure Analysis</h2>";

if ($table_exists) {
    $columns = $wpdb->get_results("SHOW COLUMNS FROM {$enquiries_table}");
    $existing_columns = wp_list_pluck($columns, 'Field');
    
    // Required columns for v1.3.1
    $required_columns = array(
        'id' => 'bigint(20) NOT NULL AUTO_INCREMENT',
        'enquiry_number' => 'varchar(20) NOT NULL',
        'student_name' => 'varchar(255) NULL',
        'date_of_birth' => 'date NULL',
        'grade' => 'varchar(50) NULL',
        'board' => 'varchar(100) NULL',
        'academic_year' => 'varchar(20) NULL',
        'parent_name' => 'varchar(255) NULL',
        'email' => 'varchar(255) NULL',
        'phone' => 'varchar(20) NULL',
        'ip_address' => 'varchar(45) NULL',
        'user_agent' => 'text NULL',
        'utm_data' => 'longtext NULL',
        'gclid' => 'varchar(255) NULL',
        'fbclid' => 'varchar(255) NULL',
        'click_id_data' => 'longtext NULL',
        'whatsapp_sent' => 'tinyint(1) DEFAULT 0',
        'email_sent' => 'tinyint(1) DEFAULT 0',
        'sms_sent' => 'tinyint(1) DEFAULT 0',
        'address' => 'text NULL',
        'gender' => 'varchar(10) NULL',
        'status' => 'varchar(20) DEFAULT \'pending\'',
        'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    );
    
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Column</th><th>Status</th><th>Action Needed</th></tr>";
    
    $missing_columns = array();
    
    foreach ($required_columns as $column => $definition) {
        $exists = in_array($column, $existing_columns);
        $status = $exists ? '✅ EXISTS' : '❌ MISSING';
        $action = $exists ? 'None' : 'ADD COLUMN';
        
        if (!$exists) {
            $missing_columns[$column] = $definition;
        }
        
        echo "<tr>";
        echo "<td><strong>{$column}</strong></td>";
        echo "<td>{$status}</td>";
        echo "<td>{$action}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    if (!empty($missing_columns)) {
        echo "<h2>4. Auto-Fix Missing Columns</h2>";
        echo "<p><strong style='color: orange;'>Found " . count($missing_columns) . " missing columns. Attempting to fix...</strong></p>";
        
        foreach ($missing_columns as $column => $definition) {
            $sql = "ALTER TABLE {$enquiries_table} ADD COLUMN {$column} {$definition}";
            echo "<p><strong>Executing:</strong> <code>{$sql}</code></p>";
            
            $result = $wpdb->query($sql);
            if ($result !== false) {
                echo "<p style='color: green;'>✅ Successfully added column: {$column}</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to add column: {$column}</p>";
                echo "<p><strong>Error:</strong> " . $wpdb->last_error . "</p>";
            }
        }
        
        echo "<h2>5. Index Creation</h2>";
        
        // Create indexes for performance
        $indexes = array(
            'idx_gclid' => "CREATE INDEX idx_gclid ON {$enquiries_table} (gclid)",
            'idx_fbclid' => "CREATE INDEX idx_fbclid ON {$enquiries_table} (fbclid)",
            'idx_ip_address' => "CREATE INDEX idx_ip_address ON {$enquiries_table} (ip_address)",
            'idx_whatsapp_sent' => "CREATE INDEX idx_whatsapp_sent ON {$enquiries_table} (whatsapp_sent)",
            'idx_email_sent' => "CREATE INDEX idx_email_sent ON {$enquiries_table} (email_sent)",
            'idx_sms_sent' => "CREATE INDEX idx_sms_sent ON {$enquiries_table} (sms_sent)"
        );
        
        foreach ($indexes as $index_name => $sql) {
            // Check if index exists
            $index_exists = $wpdb->get_results($wpdb->prepare(
                "SHOW INDEX FROM {$enquiries_table} WHERE Key_name = %s",
                $index_name
            ));
            
            if (empty($index_exists)) {
                echo "<p><strong>Creating Index:</strong> <code>{$sql}</code></p>";
                $result = $wpdb->query($sql);
                if ($result !== false) {
                    echo "<p style='color: green;'>✅ Successfully created index: {$index_name}</p>";
                } else {
                    echo "<p style='color: orange;'>⚠️ Index creation skipped (may already exist): {$index_name}</p>";
                }
            } else {
                echo "<p style='color: blue;'>ℹ️ Index already exists: {$index_name}</p>";
            }
        }
        
        // Update database version
        update_option('edubot_enquiries_db_version', '1.3.1');
        echo "<p style='color: green;'><strong>✅ Updated database version to 1.3.1</strong></p>";
        
    } else {
        echo "<h2>4. Database Status</h2>";
        echo "<p style='color: green;'><strong>✅ All required columns exist! Database is up to date.</strong></p>";
    }
    
} else {
    echo "<h2>4. Create Missing Table</h2>";
    echo "<p><strong style='color: red;'>The edubot_enquiries table is missing. Attempting to create...</strong></p>";
    
    // Create table with full structure
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE {$enquiries_table} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        enquiry_number varchar(20) NOT NULL,
        student_name varchar(255) NULL,
        date_of_birth date NULL,
        grade varchar(50) NULL,
        board varchar(100) NULL,
        academic_year varchar(20) NULL,
        parent_name varchar(255) NULL,
        email varchar(255) NULL,
        phone varchar(20) NULL,
        ip_address varchar(45) NULL,
        user_agent text NULL,
        utm_data longtext NULL,
        gclid varchar(255) NULL,
        fbclid varchar(255) NULL,
        click_id_data longtext NULL,
        whatsapp_sent tinyint(1) DEFAULT 0,
        email_sent tinyint(1) DEFAULT 0,
        sms_sent tinyint(1) DEFAULT 0,
        address text NULL,
        gender varchar(10) NULL,
        status varchar(20) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY enquiry_number (enquiry_number),
        KEY idx_status (status),
        KEY idx_created_at (created_at),
        KEY idx_email (email),
        KEY idx_phone (phone),
        KEY idx_ip_address (ip_address),
        KEY idx_gclid (gclid),
        KEY idx_fbclid (fbclid),
        KEY idx_whatsapp_sent (whatsapp_sent),
        KEY idx_email_sent (email_sent),
        KEY idx_sms_sent (sms_sent)
    ) {$charset_collate};";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $result = dbDelta($sql);
    
    if ($wpdb->get_var("SHOW TABLES LIKE '{$enquiries_table}'")) {
        echo "<p style='color: green;'>✅ Successfully created edubot_enquiries table</p>";
        update_option('edubot_enquiries_db_version', '1.3.1');
    } else {
        echo "<p style='color: red;'>❌ Failed to create table</p>";
        echo "<p><strong>Error:</strong> " . $wpdb->last_error . "</p>";
    }
}

echo "<h2>6. Final Verification</h2>";

// Re-check everything
$final_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$enquiries_table}'");
if ($final_table_exists) {
    $final_columns = $wpdb->get_results("SHOW COLUMNS FROM {$enquiries_table}");
    $final_column_names = wp_list_pluck($final_columns, 'Field');
    
    $click_id_columns = array('gclid', 'fbclid', 'click_id_data');
    $all_present = true;
    
    foreach ($click_id_columns as $col) {
        if (!in_array($col, $final_column_names)) {
            $all_present = false;
            break;
        }
    }
    
    if ($all_present) {
        echo "<p style='color: green; font-size: 18px;'><strong>🎉 SUCCESS! Database is now ready for click ID tracking!</strong></p>";
        echo "<ul>";
        echo "<li>✅ gclid column: Ready for Google Ads tracking</li>";
        echo "<li>✅ fbclid column: Ready for Facebook tracking</li>";
        echo "<li>✅ click_id_data column: Ready for other platforms</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'><strong>❌ Some issues remain. Please contact support.</strong></p>";
    }
    
    // Show current database version
    $db_version = get_option('edubot_enquiries_db_version', '0.0.0');
    echo "<p><strong>Current Database Version:</strong> {$db_version}</p>";
    
} else {
    echo "<p style='color: red;'><strong>❌ Table still missing after creation attempt.</strong></p>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Visit your WordPress admin and go to EduBot Pro → System Status</li>";
echo "<li>Verify all columns show as 'EXISTS'</li>";
echo "<li>Test enquiry submission with UTM parameters and click IDs</li>";
echo "<li>Check admin interface for click ID display</li>";
echo "</ol>";

echo "<p style='color: #666; font-size: 12px;'>Script completed at " . date('Y-m-d H:i:s') . " UTC</p>";
?>
