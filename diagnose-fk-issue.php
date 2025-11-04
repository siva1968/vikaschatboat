<?php
/**
 * Diagnose Foreign Key Constraint Errors
 * 
 * This script checks:
 * 1. Actual data types of parent table columns
 * 2. Actual data types of child table columns
 * 3. MySQL version and FK constraint settings
 */

// Load WordPress
require_once 'D:/xamppdev/htdocs/demo/wp-load.php';

global $wpdb;

echo "=== EduBot FK Constraint Diagnosis ===\n\n";

// 1. Check MySQL version
$mysql_version = $wpdb->get_var("SELECT VERSION()");
echo "MySQL Version: $mysql_version\n\n";

// 2. Check Foreign Key Constraints setting
$fk_setting = $wpdb->get_var("SELECT @@FOREIGN_KEY_CHECKS");
echo "FOREIGN_KEY_CHECKS: $fk_setting\n\n";

// 3. Check enquiries table structure
$enquiries_table = $wpdb->prefix . 'edubot_enquiries';
echo "=== Parent Table: $enquiries_table ===\n";
$enquiries_cols = $wpdb->get_results("DESCRIBE $enquiries_table");
foreach ($enquiries_cols as $col) {
    if ($col->Field === 'id') {
        echo "  id: Type={$col->Type}, Null={$col->Null}, Key={$col->Key}\n";
    }
}
echo "\n";

// 4. Check each child table and its FK columns
$child_tables = [
    'wp_edubot_attribution_sessions' => 'enquiry_id',
    'wp_edubot_attribution_touchpoints' => ['session_id', 'enquiry_id'],
    'wp_edubot_attribution_journeys' => 'enquiry_id',
    'wp_edubot_api_logs' => 'enquiry_id',
];

foreach ($child_tables as $table => $fk_cols) {
    $full_table = $wpdb->prefix . substr($table, 3); // Remove wp_ and re-add wpdb prefix
    
    echo "=== Child Table: $full_table ===\n";
    
    if (!is_array($fk_cols)) {
        $fk_cols = [$fk_cols];
    }
    
    // Check if table exists
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'");
    if ($exists) {
        $cols_result = $wpdb->get_results("DESCRIBE $full_table");
        foreach ($cols_result as $col) {
            if (in_array($col->Field, $fk_cols)) {
                echo "  {$col->Field}: Type={$col->Type}, Null={$col->Null}\n";
            }
        }
        
        // Check FK constraints
        $fk_check = $wpdb->get_results("
            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '$full_table'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        if ($fk_check) {
            echo "  Foreign Keys:\n";
            foreach ($fk_check as $fk) {
                echo "    {$fk->CONSTRAINT_NAME}: {$fk->COLUMN_NAME} -> {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
            }
        } else {
            echo "  No foreign keys defined\n";
        }
    } else {
        echo "  TABLE DOES NOT EXIST\n";
    }
    
    echo "\n";
}

// 5. Check for any CREATE TABLE errors in WordPress debug log
if (defined('WP_DEBUG_LOG') && file_exists(WP_DEBUG_LOG)) {
    echo "=== Recent Errors from Debug Log ===\n";
    $log_content = file_get_contents(WP_DEBUG_LOG);
    $lines = array_reverse(explode("\n", $log_content));
    
    $count = 0;
    foreach ($lines as $line) {
        if (stripos($line, 'attribution') !== false || stripos($line, 'FK') !== false || stripos($line, 'errno: 150') !== false) {
            echo $line . "\n";
            $count++;
            if ($count >= 20) break;
        }
    }
}

echo "\n=== Diagnosis Complete ===\n";
?>
