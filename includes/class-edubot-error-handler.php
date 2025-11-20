<?php

/**
 * EduBot Pro Error Handler
 * Centralized error handling and logging
 */
class EduBot_Error_Handler {

    private static $log_file;
    private static $max_log_size = 5 * 1024 * 1024; // 5MB

    /**
     * Initialize error handler
     */
    public static function init() {
        $upload_dir = wp_upload_dir();
        self::$log_file = $upload_dir['basedir'] . '/edubot-pro-errors.log';
        
        // Set custom error handler for EduBot errors
        set_error_handler(array(__CLASS__, 'handle_error'), E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE);
        
        // Register shutdown function to catch fatal errors
        register_shutdown_function(array(__CLASS__, 'handle_fatal_error'));
    }

    /**
     * Handle PHP errors
     */
    public static function handle_error($errno, $errstr, $errfile, $errline) {
        // Only handle EduBot related errors
        if (strpos($errfile, 'edubot-pro') === false) {
            return false; // Let PHP handle non-EduBot errors
        }

        $error_type = self::get_error_type($errno);
        $message = "EduBot Pro {$error_type}: {$errstr} in {$errfile} on line {$errline}";
        
        self::log_error($message, $errno);
        
        // Don't execute PHP internal error handler
        return true;
    }

    /**
     * Handle fatal errors
     */
    public static function handle_fatal_error() {
        $error = error_get_last();
        if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
            if (strpos($error['file'], 'edubot-pro') !== false) {
                $message = "EduBot Pro Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
                self::log_error($message, $error['type']);
            }
        }
    }

    /**
     * Log error to file
     */
    public static function log_error($message, $level = E_USER_NOTICE) {
        if (!self::$log_file) {
            self::init();
        }

        // Rotate log if too large
        if (file_exists(self::$log_file) && filesize(self::$log_file) > self::$max_log_size) {
            self::rotate_log();
        }

        $timestamp = date('Y-m-d H:i:s');
        $severity = self::get_severity_level($level);
        $log_entry = "[{$timestamp}] [{$severity}] {$message}" . PHP_EOL;
        
        // Attempt to write to log file
        if (is_writable(dirname(self::$log_file))) {
            file_put_contents(self::$log_file, $log_entry, FILE_APPEND | LOCK_EX);
        }
        
        // Also log to WordPress error log for critical errors
        if (in_array($level, array(E_ERROR, E_USER_ERROR, E_CORE_ERROR))) {
            error_log($message);
        }
    }

    /**
     * Log custom message
     */
    public static function log($message, $level = 'INFO') {
        $formatted_message = "EduBot Pro [{$level}]: {$message}";
        self::log_error($formatted_message, E_USER_NOTICE);
    }

    /**
     * Log debug information
     */
    public static function debug($message, $data = null) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $debug_message = "EduBot Pro [DEBUG]: {$message}";
        if ($data !== null) {
            $debug_message .= " | Data: " . wp_json_encode($data);
        }
        
        self::log_error($debug_message, E_USER_NOTICE);
    }

    /**
     * Get error type string
     */
    private static function get_error_type($errno) {
        switch ($errno) {
            case E_ERROR:
            case E_USER_ERROR:
                return 'Error';
            case E_WARNING:
            case E_USER_WARNING:
                return 'Warning';
            case E_NOTICE:
            case E_USER_NOTICE:
                return 'Notice';
            default:
                return 'Unknown';
        }
    }

    /**
     * Get severity level
     */
    private static function get_severity_level($errno) {
        switch ($errno) {
            case E_ERROR:
            case E_USER_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
                return 'CRITICAL';
            case E_WARNING:
            case E_USER_WARNING:
                return 'WARNING';
            case E_NOTICE:
            case E_USER_NOTICE:
                return 'NOTICE';
            default:
                return 'INFO';
        }
    }

    /**
     * Rotate log file
     */
    private static function rotate_log() {
        if (file_exists(self::$log_file)) {
            $backup_file = self::$log_file . '.backup';
            if (file_exists($backup_file)) {
                unlink($backup_file);
            }
            rename(self::$log_file, $backup_file);
        }
    }

    /**
     * Get log contents
     */
    public static function get_log_contents($lines = 100) {
        if (!file_exists(self::$log_file)) {
            return array();
        }

        $log_lines = file(self::$log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice($log_lines, -$lines);
    }

    /**
     * Clear log file
     */
    public static function clear_log() {
        if (file_exists(self::$log_file)) {
            file_put_contents(self::$log_file, '');
            return true;
        }
        return false;
    }

    /**
     * Get log file path
     */
    public static function get_log_file_path() {
        return self::$log_file;
    }

    /**
     * Check if logging is available
     */
    public static function is_logging_available() {
        if (!self::$log_file) {
            self::init();
        }
        return is_writable(dirname(self::$log_file));
    }
}
