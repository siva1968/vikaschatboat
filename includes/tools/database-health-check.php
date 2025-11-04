<?php
/**
 * EduBot Pro - Database Health Check
 * 
 * Comprehensive verification of all database tables and constraints
 * Run this periodically to ensure database integrity
 */

if (!defined('WPINC')) {
    // Allow standalone execution
    require_once 'D:/xamppdev/htdocs/demo/wp-load.php';
}

global $wpdb;

class EduBot_Health_Check {
    private $wpdb;
    private $health_status = [];
    private $all_ok = true;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    public function run_full_check() {
        echo "═════════════════════════════════════════════════════════════\n";
        echo "  EduBot Pro - Database Health Check\n";
        echo "  Generated: " . date('Y-m-d H:i:s') . "\n";
        echo "═════════════════════════════════════════════════════════════\n\n";
        
        $this->check_mysql_version();
        $this->check_fk_support();
        $this->check_tables_exist();
        $this->check_data_types();
        $this->check_foreign_keys();
        $this->check_constraints_enforced();
        $this->check_table_stats();
        
        $this->print_summary();
        
        return $this->all_ok;
    }
    
    private function check_mysql_version() {
        $version = $this->wpdb->get_var("SELECT VERSION()");
        echo "✓ MySQL Version: $version\n";
        echo "  (InnoDB available: " . ($this->has_innodb() ? "YES" : "NO") . ")\n\n";
    }
    
    private function has_innodb() {
        $result = $this->wpdb->get_var("SHOW ENGINES LIKE 'InnoDB'");
        return !is_null($result);
    }
    
    private function check_fk_support() {
        $fk_check = $this->wpdb->get_var("SELECT @@FOREIGN_KEY_CHECKS");
        echo "✓ Foreign Key Checks: " . ($fk_check ? "ENABLED" : "DISABLED") . "\n";
        echo "  Current Session Setting: @@FOREIGN_KEY_CHECKS = $fk_check\n\n";
    }
    
    private function check_tables_exist() {
        echo "Checking Table Existence:\n";
        
        $required_tables = [
            'wp_edubot_enquiries',
            'wp_edubot_attribution_sessions',
            'wp_edubot_attribution_touchpoints',
            'wp_edubot_attribution_journeys',
            'wp_edubot_conversions',
            'wp_edubot_api_logs',
            'wp_edubot_report_schedules',
            'wp_edubot_logs'
        ];
        
        $prefix = $this->wpdb->prefix;
        foreach ($required_tables as $table) {
            $exists = $this->wpdb->get_var("SHOW TABLES LIKE '$table'");
            $status = $exists ? "✓ EXISTS" : "✗ MISSING";
            echo "  $status - $table\n";
            
            if (!$exists) {
                $this->all_ok = false;
            }
        }
        echo "\n";
    }
    
    private function check_data_types() {
        echo "Checking Data Type Consistency:\n\n";
        
        // Check parent table
        $enquiries = $this->wpdb->prefix . 'edubot_enquiries';
        $result = $this->wpdb->get_results("DESCRIBE $enquiries");
        foreach ($result as $col) {
            if ($col->Field === 'id') {
                $is_unsigned = stripos($col->Type, 'unsigned') !== false;
                $is_bigint = stripos($col->Type, 'bigint') !== false;
                $status = ($is_unsigned && $is_bigint) ? "✓" : "✗";
                echo "  $status Parent (enquiries.id): {$col->Type}\n";
                if (!($is_unsigned && $is_bigint)) {
                    $this->all_ok = false;
                }
                break;
            }
        }
        
        // Check child tables
        $child_tables = [
            $this->wpdb->prefix . 'edubot_attribution_sessions' => 'enquiry_id',
            $this->wpdb->prefix . 'edubot_attribution_touchpoints' => ['session_id', 'enquiry_id'],
            $this->wpdb->prefix . 'edubot_attribution_journeys' => 'enquiry_id',
            $this->wpdb->prefix . 'edubot_api_logs' => 'enquiry_id'
        ];
        
        foreach ($child_tables as $table => $fk_cols) {
            if (!is_array($fk_cols)) {
                $fk_cols = [$fk_cols];
            }
            
            $cols_result = $this->wpdb->get_results("DESCRIBE $table");
            $table_name = str_replace($this->wpdb->prefix, '', $table);
            
            foreach ($cols_result as $col) {
                if (in_array($col->Field, $fk_cols)) {
                    $is_unsigned = stripos($col->Type, 'unsigned') !== false;
                    $is_bigint = stripos($col->Type, 'bigint') !== false;
                    $status = ($is_unsigned && $is_bigint) ? "✓" : "✗";
                    echo "  $status Child ($table_name.{$col->Field}): {$col->Type}\n";
                    if (!($is_unsigned && $is_bigint)) {
                        $this->all_ok = false;
                    }
                }
            }
        }
        echo "\n";
    }
    
    private function check_foreign_keys() {
        echo "Checking Foreign Key Constraints:\n";
        
        $fks = $this->wpdb->get_results("
            SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME LIKE 'wp_edubot_%'
            AND REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY TABLE_NAME, CONSTRAINT_NAME
        ");
        
        if (empty($fks)) {
            echo "  ✗ NO FOREIGN KEYS FOUND\n";
            $this->all_ok = false;
        } else {
            echo "  Found " . count($fks) . " foreign key constraints:\n";
            foreach ($fks as $fk) {
                $table = str_replace($this->wpdb->prefix, '', $fk->TABLE_NAME);
                $ref_table = str_replace($this->wpdb->prefix, '', $fk->REFERENCED_TABLE_NAME);
                echo "  ✓ {$fk->CONSTRAINT_NAME}\n";
                echo "    {$table}.{$fk->COLUMN_NAME} → {$ref_table}.{$fk->REFERENCED_COLUMN_NAME}\n";
            }
        }
        echo "\n";
    }
    
    private function check_constraints_enforced() {
        echo "Testing FK Constraint Enforcement:\n";
        
        $test_result = $this->wpdb->insert(
            $this->wpdb->prefix . 'edubot_attribution_sessions',
            [
                'enquiry_id' => 999999999,
                'user_session_key' => 'test_' . time(),
                'attribution_model' => 'test',
            ],
            ['%d', '%s', '%s']
        );
        
        if ($test_result === false && strpos($this->wpdb->last_error, 'constraint') !== false) {
            echo "  ✓ FK Constraints ARE properly enforced\n";
            echo "    (Invalid FK insert was correctly rejected)\n";
        } else if ($test_result === false && $this->wpdb->last_error) {
            echo "  ⚠ Insert failed, but not due to FK:\n";
            echo "    {$this->wpdb->last_error}\n";
        } else {
            echo "  ✗ FK Constraints NOT enforced (invalid insert was allowed!)\n";
            // Clean up the test record
            $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}edubot_attribution_sessions WHERE enquiry_id = 999999999");
            $this->all_ok = false;
        }
        echo "\n";
    }
    
    private function check_table_stats() {
        echo "Table Statistics:\n";
        
        $tables = [
            'wp_edubot_enquiries',
            'wp_edubot_attribution_sessions',
            'wp_edubot_attribution_touchpoints',
            'wp_edubot_attribution_journeys',
            'wp_edubot_conversions',
            'wp_edubot_api_logs'
        ];
        
        foreach ($tables as $table) {
            $count = $this->wpdb->get_var("SELECT COUNT(*) FROM $table");
            $size = $this->wpdb->get_results("SHOW TABLE STATUS WHERE Name = '$table'");
            if (!empty($size)) {
                $data_size = isset($size[0]->Data_length) ? $this->format_bytes($size[0]->Data_length) : 'N/A';
                echo "  $table: $count rows, Size: $data_size\n";
            }
        }
        echo "\n";
    }
    
    private function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    private function print_summary() {
        echo "═════════════════════════════════════════════════════════════\n";
        if ($this->all_ok) {
            echo "  ✓ DATABASE HEALTH CHECK: PASSED\n";
            echo "  All tables, constraints, and data types are correct\n";
        } else {
            echo "  ✗ DATABASE HEALTH CHECK: FAILED\n";
            echo "  One or more issues were detected above\n";
        }
        echo "═════════════════════════════════════════════════════════════\n";
    }
}

// Run the health check
$health_check = new EduBot_Health_Check();
$result = $health_check->run_full_check();

exit($result ? 0 : 1);
?>
