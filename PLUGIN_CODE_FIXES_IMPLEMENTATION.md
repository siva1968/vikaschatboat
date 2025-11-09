# Plugin Code Fixes - Implementation Guide
**EduBot Pro Optimization** | November 5, 2025

---

## PART 1: LOGGING IMPROVEMENTS

### Create New Logging System

**File:** `includes/class-edubot-logger.php` (NEW)

```php
<?php

/**
 * EduBot Logging System
 * Centralized, conditional logging with performance considerations
 */
class EduBot_Logger {
    
    // Throttle counters to prevent log spam
    private static $log_counts = [];
    private static $last_log_time = [];
    
    const LOG_LEVEL_DEBUG = 'debug';
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_WARNING = 'warning';
    const LOG_LEVEL_ERROR = 'error';
    const LOG_LEVEL_CRITICAL = 'critical';
    
    /**
     * Log a message with optional throttling
     */
    public static function log($message, $level = self::LOG_LEVEL_INFO, $throttle = false) {
        // Only log if debug mode enabled
        if (!self::should_log($level)) {
            return false;
        }
        
        // Apply throttling if requested
        if ($throttle && self::is_throttled($level)) {
            return false;
        }
        
        $timestamp = current_time('mysql');
        $formatted_message = "[{$timestamp}] [{$level}] {$message}";
        
        error_log($formatted_message);
        
        // Track logged message for throttling
        if ($throttle) {
            self::$log_counts[$level] = (self::$log_counts[$level] ?? 0) + 1;
            self::$last_log_time[$level] = time();
        }
        
        return true;
    }
    
    /**
     * Determine if message should be logged
     */
    private static function should_log($level) {
        if (!defined('EDUBOT_PRO_DEBUG')) {
            define('EDUBOT_PRO_DEBUG', defined('WP_DEBUG') && WP_DEBUG);
        }
        
        // Always log critical/error in production
        if (in_array($level, [self::LOG_LEVEL_CRITICAL, self::LOG_LEVEL_ERROR])) {
            return true;
        }
        
        // Only log info/debug in debug mode
        return EDUBOT_PRO_DEBUG;
    }
    
    /**
     * Check if this log is throttled
     */
    private static function is_throttled($key, $max_per_hour = 10) {
        if (!isset(self::$log_counts[$key])) {
            return false;
        }
        
        // Reset counter if more than 1 hour has passed
        if (time() - self::$last_log_time[$key] > 3600) {
            self::$log_counts[$key] = 0;
            return false;
        }
        
        return self::$log_counts[$key] >= $max_per_hour;
    }
    
    /**
     * Log debug info (only in debug mode)
     */
    public static function debug($message, $throttle = true) {
        return self::log($message, self::LOG_LEVEL_DEBUG, $throttle);
    }
    
    /**
     * Log info message (only in debug mode)
     */
    public static function info($message, $throttle = true) {
        return self::log($message, self::LOG_LEVEL_INFO, $throttle);
    }
    
    /**
     * Log warning (always)
     */
    public static function warning($message) {
        return self::log($message, self::LOG_LEVEL_WARNING, false);
    }
    
    /**
     * Log error (always)
     */
    public static function error($message) {
        return self::log($message, self::LOG_LEVEL_ERROR, false);
    }
    
    /**
     * Log critical error (always)
     */
    public static function critical($message) {
        return self::log($message, self::LOG_LEVEL_CRITICAL, false);
    }
    
    /**
     * Store operation in database for admin review
     */
    public static function log_operation($operation, $details, $status = 'success') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_operation_logs';
        
        $wpdb->insert($table, [
            'operation' => $operation,
            'details' => wp_json_encode($details),
            'status' => $status,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ]);
    }
    
    /**
     * Get operation logs from database
     */
    public static function get_operation_logs($limit = 100, $operation_type = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_operation_logs';
        
        $sql = "SELECT * FROM {$table}";
        
        if ($operation_type) {
            $sql .= $wpdb->prepare(" WHERE operation = %s", $operation_type);
        }
        
        $sql .= " ORDER BY timestamp DESC LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }
}
```

---

## PART 2: IMPROVED UTM CAPTURE

**File:** `includes/class-edubot-utm-capture.php` (NEW)

```php
<?php

/**
 * UTM Parameter Capture System
 * Secure, validated cookie handling
 */
class EduBot_UTM_Capture {
    
    // Configuration with max length validation
    private static $utm_parameters = [
        'utm_source' => ['max' => 50, 'description' => 'Advertising source'],
        'utm_medium' => ['max' => 50, 'description' => 'Advertising medium'],
        'utm_campaign' => ['max' => 50, 'description' => 'Campaign identifier'],
        'utm_term' => ['max' => 100, 'description' => 'Search term'],
        'utm_content' => ['max' => 100, 'description' => 'Link content'],
        'gclid' => ['max' => 100, 'description' => 'Google Click ID'],
        'fbclid' => ['max' => 100, 'description' => 'Facebook Click ID'],
        'msclkid' => ['max' => 100, 'description' => 'Microsoft Click ID'],
        'ttclid' => ['max' => 100, 'description' => 'TikTok Click ID'],
        'twclid' => ['max' => 100, 'description' => 'Twitter Click ID'],
        '_kenshoo_clickid' => ['max' => 100, 'description' => 'Kenshoo Click ID'],
        'irclickid' => ['max' => 100, 'description' => 'IR Click ID'],
        'li_fat_id' => ['max' => 100, 'description' => 'LinkedIn Fat ID'],
        'sc_click_id' => ['max' => 100, 'description' => 'Snapchat Click ID'],
        'yclid' => ['max' => 100, 'description' => 'Yahoo Click ID']
    ];
    
    private static $cookie_lifetime_days = 30;
    
    /**
     * Initialize UTM capture on plugin load
     */
    public static function init() {
        // Capture immediately before WordPress initializes
        add_action('wp', [__CLASS__, 'capture_utm_parameters'], 1);
    }
    
    /**
     * Capture and store UTM parameters
     */
    public static function capture_utm_parameters() {
        // Only if GET has parameters
        if (empty($_GET)) {
            return;
        }
        
        $captured_params = [];
        $failed_params = [];
        
        foreach (self::$utm_parameters as $param_name => $config) {
            if (!isset($_GET[$param_name]) || empty($_GET[$param_name])) {
                continue;
            }
            
            // Validate and sanitize
            $validation = self::validate_parameter($param_name, $_GET[$param_name], $config);
            
            if (!$validation['valid']) {
                $failed_params[] = "{$param_name}: {$validation['reason']}";
                EduBot_Logger::warning("UTM parameter rejected: {$param_name} - {$validation['reason']}", true);
                continue;
            }
            
            $value = $validation['value'];
            
            // Set cookie
            $cookie_name = "edubot_{$param_name}";
            $expires = time() + (self::$cookie_lifetime_days * DAY_IN_SECONDS);
            
            if (self::set_secure_cookie($cookie_name, $value, $expires)) {
                $captured_params[] = $param_name;
                
                // Log only in debug mode
                EduBot_Logger::debug("UTM captured: {$param_name}", true);
            } else {
                $failed_params[] = "{$param_name}: cookie set failed";
                EduBot_Logger::warning("Failed to set cookie: {$cookie_name}");
            }
        }
        
        // Log summary only if debug enabled
        if (!empty($captured_params)) {
            EduBot_Logger::info("Captured " . count($captured_params) . " UTM parameters", true);
            
            // Store in session for later retrieval
            if (!session_id()) {
                session_start();
            }
            $_SESSION['edubot_utm_params'] = $captured_params;
        }
        
        if (!empty($failed_params) && EDUBOT_PRO_DEBUG) {
            EduBot_Logger::debug("Failed to capture: " . implode(', ', $failed_params), true);
        }
    }
    
    /**
     * Validate a single parameter
     */
    private static function validate_parameter($param_name, $param_value, $config) {
        // Sanitize input
        $sanitized = sanitize_text_field($param_value);
        
        // Check if empty after sanitization
        if (empty($sanitized)) {
            return [
                'valid' => false,
                'reason' => 'Empty after sanitization'
            ];
        }
        
        // Check length
        if (strlen($sanitized) > $config['max']) {
            $sanitized = substr($sanitized, 0, $config['max']);
        }
        
        // Minimum length check (2 chars minimum to avoid noise)
        if (strlen($sanitized) < 2) {
            return [
                'valid' => false,
                'reason' => 'Too short after sanitization'
            ];
        }
        
        // Validate format (alphanumeric, hyphens, underscores, dots)
        if (!preg_match('/^[a-zA-Z0-9\-_.]+$/', $sanitized)) {
            return [
                'valid' => false,
                'reason' => 'Contains invalid characters'
            ];
        }
        
        return [
            'valid' => true,
            'value' => $sanitized
        ];
    }
    
    /**
     * Set secure cookie with proper settings
     */
    private static function set_secure_cookie($cookie_name, $value, $expires) {
        // Determine domain
        $domain = self::get_safe_domain();
        $secure = is_ssl();
        
        // Use PHP 7.3+ setcookie with options array if available
        if (PHP_VERSION_ID >= 70300) {
            return setcookie($cookie_name, $value, [
                'expires' => $expires,
                'path' => '/',
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        } else {
            // Fallback for older PHP versions
            return setcookie(
                $cookie_name,
                $value,
                $expires,
                '/',
                $domain,
                $secure,
                true // httponly
            );
        }
    }
    
    /**
     * Get safe domain for cookie
     */
    private static function get_safe_domain() {
        // Use WordPress home URL
        $home_url = home_url();
        $parsed = wp_parse_url($home_url);
        
        if (isset($parsed['host'])) {
            // Remove 'www.' prefix if present
            $domain = preg_replace('/^www\./', '', $parsed['host']);
            
            // Remove port if present
            $domain = preg_replace('/:\d+$/', '', $domain);
            
            return $domain;
        }
        
        // Fallback to empty (browser default)
        return '';
    }
    
    /**
     * Get captured UTM parameters from cookies
     */
    public static function get_utm_parameters() {
        $params = [];
        
        foreach (array_keys(self::$utm_parameters) as $param_name) {
            $cookie_name = "edubot_{$param_name}";
            
            if (isset($_COOKIE[$cookie_name])) {
                $params[$param_name] = sanitize_text_field($_COOKIE[$cookie_name]);
            }
        }
        
        return $params;
    }
    
    /**
     * Clear all UTM cookies
     */
    public static function clear_utm_cookies() {
        foreach (array_keys(self::$utm_parameters) as $param_name) {
            $cookie_name = "edubot_{$param_name}";
            setcookie($cookie_name, '', time() - 3600);
            unset($_COOKIE[$cookie_name]);
        }
    }
}

// Initialize on plugin load
EduBot_UTM_Capture::init();
```

---

## PART 3: IMPROVED ACTIVATION

**File:** `includes/class-edubot-activator.php` (REPLACE activate function)

```php
<?php

public static function activate() {
    try {
        // Don't use output buffering - use proper logging
        EduBot_Logger::info('Starting EduBot Pro activation...');
        
        // Initialize database
        $db_result = self::initialize_database();
        
        if (!$db_result['success']) {
            EduBot_Logger::error('Database initialization failed: ' . 
                implode('; ', $db_result['errors']));
            
            update_option('edubot_activation_error', [
                'errors' => $db_result['errors'],
                'timestamp' => current_time('mysql')
            ]);
            
            throw new Exception('Database initialization failed');
        }
        
        // Set default options
        self::set_default_options();
        
        // Schedule events
        self::schedule_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Store activation info
        update_option('edubot_activation_status', [
            'status' => 'success',
            'version' => EDUBOT_PRO_VERSION,
            'timestamp' => current_time('mysql'),
            'db_version' => EDUBOT_PRO_DB_VERSION,
            'tables_created' => $db_result['tables_created']
        ]);
        
        EduBot_Logger::info('EduBot Pro activated successfully. Version: ' . 
            EDUBOT_PRO_VERSION . '. Tables: ' . 
            implode(', ', $db_result['tables_created']));
        
    } catch (Exception $e) {
        EduBot_Logger::critical('Activation failed: ' . $e->getMessage());
        
        update_option('edubot_activation_error', [
            'message' => $e->getMessage(),
            'timestamp' => current_time('mysql'),
            'trace' => $e->getTraceAsString()
        ]);
        
        throw $e;
    }
}

/**
 * Initialize database with transactions
 */
private static function initialize_database() {
    global $wpdb;
    
    $tables_created = [];
    $errors = [];
    
    try {
        // Start transaction
        if ($wpdb->query('START TRANSACTION') === false) {
            throw new Exception('Failed to start transaction: ' . $wpdb->last_error);
        }
        
        // Disable FK checks
        $wpdb->query('SET FOREIGN_KEY_CHECKS=0');
        $wpdb->query('SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0');
        
        // Create tables in dependency order
        $tables = [
            'enquiries' => ['sql_method' => 'sql_enquiries', 'exists' => false],
            'attribution_sessions' => ['sql_method' => 'sql_attribution_sessions', 'exists' => false],
            'attribution_touchpoints' => ['sql_method' => 'sql_attribution_touchpoints', 'exists' => false],
            'attribution_journeys' => ['sql_method' => 'sql_attribution_journeys', 'exists' => false],
            'conversions' => ['sql_method' => 'sql_conversions', 'exists' => false],
            'api_logs' => ['sql_method' => 'sql_api_logs', 'exists' => false],
            'report_schedules' => ['sql_method' => 'sql_report_schedules', 'exists' => false],
            'logs' => ['sql_method' => 'sql_logs', 'exists' => false],
            'applications' => ['sql_method' => 'sql_applications', 'exists' => false]
        ];
        
        foreach ($tables as $table_name => $config) {
            $table_prefix = $wpdb->prefix . 'edubot_' . $table_name;
            
            if (self::table_exists($table_prefix)) {
                $tables[$table_name]['exists'] = true;
                continue;
            }
            
            $method = $config['sql_method'];
            if (!method_exists(__CLASS__, $method)) {
                throw new Exception("SQL method not found: {$method}");
            }
            
            $sql = call_user_func([__CLASS__, $method]);
            
            if ($wpdb->query($sql) === false) {
                throw new Exception("Failed to create {$table_name} table: " . 
                    $wpdb->last_error);
            }
            
            $tables_created[] = $table_name;
            EduBot_Logger::info("Created {$table_name} table");
        }
        
        // Re-enable settings
        $wpdb->query('SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS');
        $wpdb->query('SET FOREIGN_KEY_CHECKS=1');
        
        // Commit transaction
        if ($wpdb->query('COMMIT') === false) {
            throw new Exception('Failed to commit transaction: ' . $wpdb->last_error);
        }
        
        return [
            'success' => true,
            'errors' => [],
            'tables_created' => $tables_created
        ];
        
    } catch (Exception $e) {
        // Rollback on error
        $wpdb->query('ROLLBACK');
        $wpdb->query('SET FOREIGN_KEY_CHECKS=1');
        
        return [
            'success' => false,
            'errors' => [$e->getMessage()],
            'tables_created' => $tables_created
        ];
    }
}
```

---

## PART 4: IMPROVED ADMIN CLASS

**File:** `admin/class-edubot-admin.php` (REPLACE enqueue section)

```php
<?php

/**
 * Register the stylesheets for the admin area.
 */
public function enqueue_styles() {
    wp_enqueue_style(
        $this->plugin_name,
        plugin_dir_url(__FILE__) . 'css/edubot-admin.css',
        [],
        $this->version,
        'all'
    );
}

/**
 * Register the JavaScript for the admin area.
 */
public function enqueue_scripts() {
    wp_enqueue_script(
        $this->plugin_name,
        plugin_dir_url(__FILE__) . 'js/edubot-admin.js',
        ['jquery'],
        $this->version,
        false
    );
    
    // Localize script with proper security
    wp_localize_script($this->plugin_name, 'edubot_admin_ajax', $this->get_admin_localization());
}

/**
 * Get admin localization data (safe for output)
 */
private function get_admin_localization() {
    return [
        'ajax_url' => esc_url(admin_url('admin-ajax.php')),
        'nonce' => wp_create_nonce('edubot_admin_nonce'),
        'strings' => [
            'saving' => __('Saving...', 'edubot-pro'),
            'saved' => __('Settings saved successfully!', 'edubot-pro'),
            'error' => __('Error saving settings. Please try again.', 'edubot-pro'),
            'testing' => __('Testing connection...', 'edubot-pro'),
            'connection_success' => __('Connection successful!', 'edubot-pro'),
            'connection_failed' => __('Connection failed!', 'edubot-pro')
        ]
    ];
}

/**
 * Clear error logs via AJAX
 */
public function clear_error_logs_ajax() {
    // Verify nonce
    check_ajax_referer('edubot_admin_nonce', 'nonce');
    
    // Check capability
    if (!current_user_can('manage_options')) {
        wp_send_json_error(
            ['message' => __('Insufficient permissions', 'edubot-pro')],
            403
        );
    }
    
    // Clear operation logs
    global $wpdb;
    $table = $wpdb->prefix . 'edubot_operation_logs';
    
    $deleted = $wpdb->query("TRUNCATE TABLE {$table}");
    
    if ($deleted !== false) {
        EduBot_Logger::info('Admin cleared operation logs');
        wp_send_json_success([
            'message' => __('Logs cleared successfully', 'edubot-pro')
        ]);
    } else {
        EduBot_Logger::error('Failed to clear logs: ' . $wpdb->last_error);
        wp_send_json_error(
            ['message' => __('Failed to clear logs', 'edubot-pro')],
            500
        );
    }
}
```

---

## PART 5: MAIN PLUGIN FILE

**File:** `edubot-pro.php` (REPLACE bootstrap section)

```php
<?php

/**
 * CRITICAL: Capture UTM to cookies IMMEDIATELY in plugin bootstrap
 * This runs BEFORE any hooks, ensuring setcookie() works
 */
function edubot_pro_capture_utm_immediately() {
    if (empty($_GET)) {
        return;
    }
    
    // Only proceed if the UTM capture class exists
    if (!class_exists('EduBot_UTM_Capture')) {
        return;
    }
    
    // Use the dedicated capture system
    EduBot_UTM_Capture::capture_utm_parameters();
}

// Call immediately - before WordPress does anything
edubot_pro_capture_utm_immediately();

/**
 * Begins execution of the plugin.
 */
function run_edubot_pro() {
    try {
        // Check requirements
        edubot_pro_check_requirements();
        
        // Initialize plugin
        $plugin = new EduBot_Core();
        $plugin->run();
        
    } catch (Exception $e) {
        // Log error
        EduBot_Logger::critical('Plugin initialization failed: ' . $e->getMessage());
        
        // Show admin notice
        add_action('admin_notices', function() use ($e) {
            if (current_user_can('activate_plugins')) {
                printf(
                    '<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
                    esc_html__('EduBot Pro Error:', 'edubot-pro'),
                    esc_html($e->getMessage())
                );
            }
        });
    }
}

// Always use plugins_loaded hook for consistent execution
add_action('plugins_loaded', 'run_edubot_pro');
```

---

## SUMMARY OF CHANGES

| File | Change | Benefit |
|------|--------|---------|
| class-edubot-logger.php | NEW | Centralized conditional logging |
| class-edubot-utm-capture.php | NEW | Secure UTM handling |
| class-edubot-activator.php | IMPROVED | Transaction support, better errors |
| class-edubot-admin.php | IMPROVED | Better security, cleaner code |
| edubot-pro.php | SIMPLIFIED | Fixed initialization, removed logging |

**Total Lines Removed:** ~200 (debug logs)
**Total Lines Added:** ~400 (with proper error handling)
**Net Impact:** Cleaner, more secure, better performance

---

## NEXT STEPS

1. Create the two new files from Part 1 & 2
2. Apply changes from Part 3, 4, 5
3. Test activation
4. Test AJAX calls
5. Review error logs - should be minimal
6. Deploy to staging
7. Monitor logs for 48 hours
8. Deploy to production

