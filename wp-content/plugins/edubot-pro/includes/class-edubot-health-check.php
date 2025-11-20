<?php

/**
 * EduBot Pro Health Check
 * Validates plugin integrity and configuration
 */
class EduBot_Health_Check {

    /**
     * Run comprehensive health check
     */
    public static function run_health_check() {
        $issues = array();
        
        // Check file integrity
        $file_issues = self::check_file_integrity();
        if (!empty($file_issues)) {
            $issues['files'] = $file_issues;
        }
        
        // Check database tables
        $db_issues = self::check_database_tables();
        if (!empty($db_issues)) {
            $issues['database'] = $db_issues;
        }
        
        // Check WordPress compatibility
        $wp_issues = self::check_wordpress_compatibility();
        if (!empty($wp_issues)) {
            $issues['wordpress'] = $wp_issues;
        }
        
        // Check PHP requirements
        $php_issues = self::check_php_requirements();
        if (!empty($php_issues)) {
            $issues['php'] = $php_issues;
        }
        
        // Check configuration
        $config_issues = self::check_configuration();
        if (!empty($config_issues)) {
            $issues['configuration'] = $config_issues;
        }
        
        return $issues;
    }

    /**
     * Check file integrity
     */
    private static function check_file_integrity() {
        $issues = array();
        $required_files = array(
            'includes/class-edubot-core.php',
            'includes/class-edubot-loader.php',
            'includes/class-database-manager.php',
            'includes/class-security-manager.php',
            'admin/class-edubot-admin.php',
            'public/class-edubot-public.php'
        );
        
        foreach ($required_files as $file) {
            $file_path = EDUBOT_PRO_PLUGIN_PATH . $file;
            if (!file_exists($file_path)) {
                $issues[] = "Missing required file: {$file}";
            } elseif (!is_readable($file_path)) {
                $issues[] = "File not readable: {$file}";
            }
        }
        
        // Check directories
        $required_dirs = array('includes', 'admin', 'public', 'languages', 'assets');
        foreach ($required_dirs as $dir) {
            $dir_path = EDUBOT_PRO_PLUGIN_PATH . $dir;
            if (!is_dir($dir_path)) {
                $issues[] = "Missing directory: {$dir}";
            }
        }
        
        return $issues;
    }

    /**
     * Check database tables
     */
    private static function check_database_tables() {
        global $wpdb;
        $issues = array();
        
        $required_tables = array(
            'edubot_school_configs',
            'edubot_applications',
            'edubot_analytics',
            'edubot_sessions',
            'edubot_security_log',
            'edubot_visitor_analytics',
            'edubot_visitors'
        );
        
        foreach ($required_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
            if ($exists !== $table_name) {
                $issues[] = "Missing database table: {$table_name}";
            }
        }
        
        return $issues;
    }

    /**
     * Check WordPress compatibility
     */
    private static function check_wordpress_compatibility() {
        global $wp_version;
        $issues = array();
        
        $min_wp_version = '5.0';
        if (version_compare($wp_version, $min_wp_version, '<')) {
            $issues[] = "WordPress version {$wp_version} is below minimum requirement {$min_wp_version}";
        }
        
        // Check for required WordPress features
        if (!function_exists('wp_doing_ajax')) {
            $issues[] = "wp_doing_ajax function not available";
        }
        
        if (!function_exists('wp_send_json_success')) {
            $issues[] = "wp_send_json_success function not available";
        }
        
        return $issues;
    }

    /**
     * Check PHP requirements
     */
    private static function check_php_requirements() {
        $issues = array();
        
        $min_php_version = '7.4';
        if (version_compare(PHP_VERSION, $min_php_version, '<')) {
            $issues[] = "PHP version " . PHP_VERSION . " is below minimum requirement {$min_php_version}";
        }
        
        // Check required PHP extensions
        $required_extensions = array('json', 'curl', 'openssl', 'mbstring');
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $issues[] = "Required PHP extension missing: {$ext}";
            }
        }
        
        // Check memory limit
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $recommended_memory = 128 * 1024 * 1024; // 128MB
        if ($memory_limit < $recommended_memory) {
            $issues[] = "PHP memory limit " . ini_get('memory_limit') . " is below recommended 128M";
        }
        
        return $issues;
    }

    /**
     * Check plugin configuration
     */
    private static function check_configuration() {
        $issues = array();
        
        // Check if plugin constants are defined
        $required_constants = array(
            'EDUBOT_PRO_VERSION',
            'EDUBOT_PRO_PLUGIN_PATH',
            'EDUBOT_PRO_PLUGIN_URL'
        );
        
        foreach ($required_constants as $constant) {
            if (!defined($constant)) {
                $issues[] = "Required constant not defined: {$constant}";
            }
        }
        
        // Check write permissions
        $upload_dir = wp_upload_dir();
        if (!wp_is_writable($upload_dir['basedir'])) {
            $issues[] = "Upload directory is not writable";
        }
        
        return $issues;
    }

    /**
     * Get health status summary
     */
    public static function get_health_status() {
        $issues = self::run_health_check();
        
        if (empty($issues)) {
            return array(
                'status' => 'healthy',
                'message' => 'All systems operational'
            );
        }
        
        $critical_issues = 0;
        $warning_issues = 0;
        
        foreach ($issues as $category => $category_issues) {
            if (in_array($category, array('files', 'database', 'php'))) {
                $critical_issues += count($category_issues);
            } else {
                $warning_issues += count($category_issues);
            }
        }
        
        if ($critical_issues > 0) {
            return array(
                'status' => 'critical',
                'message' => "Found {$critical_issues} critical issues and {$warning_issues} warnings",
                'issues' => $issues
            );
        } else {
            return array(
                'status' => 'warning',
                'message' => "Found {$warning_issues} warnings",
                'issues' => $issues
            );
        }
    }

    /**
     * Display health check in admin
     */
    public static function display_health_check() {
        $health = self::get_health_status();
        
        $class = 'notice-success';
        if ($health['status'] === 'warning') {
            $class = 'notice-warning';
        } elseif ($health['status'] === 'critical') {
            $class = 'notice-error';
        }
        
        echo "<div class='notice {$class}'>";
        echo "<p><strong>EduBot Pro Health Check:</strong> " . esc_html($health['message']) . "</p>";
        
        if (isset($health['issues']) && !empty($health['issues'])) {
            echo "<ul>";
            foreach ($health['issues'] as $category => $issues) {
                echo "<li><strong>" . ucfirst($category) . ":</strong>";
                echo "<ul>";
                foreach ($issues as $issue) {
                    echo "<li>" . esc_html($issue) . "</li>";
                }
                echo "</ul></li>";
            }
            echo "</ul>";
        }
        
        echo "</div>";
    }
}
