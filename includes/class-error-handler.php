<?php

/**
 * Error Handler Class
 * 
 * Centralized error handling for consistent behavior.
 * 
 * @package EduBot_Pro
 * @subpackage Error Handling
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EduBot_Error_Handler {

    /**
     * Handle exceptions consistently
     * 
     * @param EduBot_Exception|Exception $exception The exception
     * @return array Error response
     */
    public static function handle_exception($exception) {
        $code = 500;
        $message = 'An error occurred';
        $details = array();

        // Get exception details
        if ($exception instanceof EduBot_Exception) {
            $code = $exception->getHttpCode();
            $message = $exception->getMessage();
            $details = $exception->getContext();
        } else {
            $message = $exception->getMessage();
        }

        // Log the error
        if (function_exists('EduBot_Logger')) {
            $log_level = $code >= 500 ? 'error' : 'warning';
            EduBot_Logger::{$log_level}($message, array(
                'exception' => get_class($exception),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'details' => $details
            ));
        }

        return array(
            'success' => false,
            'code' => $code,
            'message' => $message,
            'details' => $details,
            'timestamp' => current_time('mysql')
        );
    }

    /**
     * Handle WP_Error consistently
     * 
     * @param WP_Error $error The error
     * @return array Error response
     */
    public static function handle_wp_error($error) {
        $message = $error->get_error_message();
        $code = (int)$error->get_error_code() ?: 500;

        if (function_exists('EduBot_Logger')) {
            EduBot_Logger::warning('WordPress error', array(
                'code' => $code,
                'message' => $message,
                'data' => $error->get_error_data()
            ));
        }

        return array(
            'success' => false,
            'code' => $code,
            'message' => $message,
            'timestamp' => current_time('mysql')
        );
    }

    /**
     * Create error response
     * 
     * @param string $message Error message
     * @param int $code HTTP code
     * @param array $details Additional details
     * @return array Response
     */
    public static function create_error($message, $code = 500, $details = array()) {
        if (function_exists('EduBot_Logger')) {
            EduBot_Logger::error($message, $details);
        }

        return array(
            'success' => false,
            'code' => $code,
            'message' => $message,
            'details' => $details,
            'timestamp' => current_time('mysql')
        );
    }

    /**
     * Create success response
     * 
     * @param mixed $data Response data
     * @param string $message Optional message
     * @return array Response
     */
    public static function create_success($data, $message = 'Success') {
        return array(
            'success' => true,
            'code' => 200,
            'message' => $message,
            'data' => $data,
            'timestamp' => current_time('mysql')
        );
    }

    /**
     * Validate data and throw exception if invalid
     * 
     * @param mixed $data Data to validate
     * @param array $rules Validation rules
     * @throws EduBot_Validation_Exception
     * @return bool True if valid
     */
    public static function validate($data, $rules) {
        $errors = array();

        foreach ($rules as $field => $rule) {
            if ($rule['required'] && empty($data[$field])) {
                $errors[] = $field . ' is required';
            }

            if (isset($rule['type'])) {
                if (!self::check_type($data[$field] ?? null, $rule['type'])) {
                    $errors[] = $field . ' must be ' . $rule['type'];
                }
            }
        }

        if (!empty($errors)) {
            throw new EduBot_Validation_Exception(
                'Validation failed: ' . implode(', ', $errors),
                0,
                null,
                array('errors' => $errors)
            );
        }

        return true;
    }

    /**
     * Check data type
     * 
     * @param mixed $value Value to check
     * @param string $type Expected type
     * @return bool Valid
     */
    private static function check_type($value, $type) {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'int':
                return is_int($value);
            case 'array':
                return is_array($value);
            case 'email':
                return is_email($value);
            default:
                return true;
        }
    }

    /**
     * Register global error handler
     */
    public static function register() {
        set_exception_handler(array(__CLASS__, 'handle_exception'));
        set_error_handler(array(__CLASS__, 'handle_php_error'));
        register_shutdown_function(array(__CLASS__, 'handle_fatal_error'));
    }

    /**
     * Handle PHP errors
     * 
     * @param int $errno Error number
     * @param string $errstr Error string
     * @param string $errfile File
     * @param int $errline Line
     */
    public static function handle_php_error($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        if (function_exists('EduBot_Logger')) {
            EduBot_Logger::error('PHP Error: ' . $errstr, array(
                'errno' => $errno,
                'file' => $errfile,
                'line' => $errline
            ));
        }

        return true;
    }

    /**
     * Handle fatal errors
     */
    public static function handle_fatal_error() {
        $error = error_get_last();
        if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
            if (function_exists('EduBot_Logger')) {
                EduBot_Logger::critical('Fatal Error', array(
                    'message' => $error['message'],
                    'file' => $error['file'],
                    'line' => $error['line']
                ));
            }
        }
    }
}
