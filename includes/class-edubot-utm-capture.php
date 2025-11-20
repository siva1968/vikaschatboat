<?php

/**
 * EduBot Pro UTM Capture Class
 * 
 * Securely captures and stores UTM parameters and click IDs from URLs.
 * Implements validation, length limits, and secure cookie handling.
 * 
 * @package    EduBot_Pro
 * @subpackage EduBot_Pro/includes
 * @since      1.4.2
 */

if (!defined('WPINC')) {
    die;
}

/**
 * UTM Capture class for secure parameter handling
 * 
 * This class replaces direct $_GET access and raw setcookie() calls.
 * It provides:
 * - Parameter validation before capture
 * - Length limits to prevent buffer overflow
 * - Domain validation to prevent host header injection
 * - Secure cookie flags (HttpOnly, Secure, SameSite)
 * - Never logs parameter values
 * 
 * @since 1.4.2
 */
class EduBot_UTM_Capture {

    /**
     * UTM parameters to capture
     */
    const UTM_PARAMS = array(
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
    );

    /**
     * Click ID parameters to capture (from various ad platforms)
     */
    const CLICK_ID_PARAMS = array(
        'gclid',      // Google Click ID
        'fbclid',     // Facebook Click ID
        'msclkid',    // Microsoft Click ID
        'ttclid',     // TikTok Click ID
        'twclid',     // Twitter Click ID
        '_kenshoo_clickid',
        'irclickid',
        'li_fat_id',
        'sc_click_id',
        'yclid',
    );

    /**
     * Maximum length for parameter values
     */
    const MAX_PARAM_LENGTH = 200;

    /**
     * Cookie expiration time (30 days)
     */
    const COOKIE_LIFETIME = 30 * 24 * 60 * 60;

    /**
     * Capture UTM parameters from URL and set as cookies
     * 
     * This should be called early in the plugin bootstrap, before any headers sent.
     * 
     * @since 1.4.2
     */
    public static function capture_on_init() {
        
        // Only process if we have GET parameters
        if (empty($_GET)) {
            return;
        }

        // Get safe domain for cookie
        $domain = self::get_safe_domain();
        if (empty($domain)) {
            return; // Can't set cookies without valid domain
        }

        // Determine if connection is secure
        $secure = self::is_secure_connection();
        
        $captured_count = 0;

        // Capture UTM parameters
        foreach (self::UTM_PARAMS as $param) {
            if (isset($_GET[$param]) && !empty($_GET[$param])) {
                $value = self::validate_parameter($_GET[$param]);
                if ($value !== false) {
                    self::set_secure_cookie("edubot_{$param}", $value, $domain, $secure);
                    $captured_count++;
                }
            }
        }

        // Capture Click ID parameters
        foreach (self::CLICK_ID_PARAMS as $param) {
            if (isset($_GET[$param]) && !empty($_GET[$param])) {
                $value = self::validate_parameter($_GET[$param]);
                if ($value !== false) {
                    self::set_secure_cookie("edubot_{$param}", $value, $domain, $secure);
                    $captured_count++;
                }
            }
        }

        // Log that we captured parameters (but not the values!)
        if ($captured_count > 0 && function_exists('EduBot_Logger')) {
            EduBot_Logger::debug("Captured {$captured_count} URL parameters to cookies", array(
                'param_count' => $captured_count,
            ));
        }
    }

    /**
     * Validate a URL parameter value
     * 
     * Ensures:
     * - Value is not empty
     * - Value doesn't exceed length limit
     * - Value doesn't contain null bytes
     * - Value is safe for use in cookies
     * 
     * @param string $value The parameter value to validate
     * @return string|false The sanitized value, or false if invalid
     * @since 1.4.2
     */
    private static function validate_parameter($value) {
        
        // Check if value is empty
        if (empty($value)) {
            return false;
        }

        // Convert to string
        $value = (string)$value;

        // Check for null bytes (potential injection)
        if (strpos($value, "\0") !== false) {
            return false;
        }

        // Check length (prevent buffer overflow)
        if (strlen($value) > self::MAX_PARAM_LENGTH) {
            return false;
        }

        // Sanitize using WordPress sanitization
        $sanitized = sanitize_text_field($value);

        // Make sure sanitization didn't empty the value
        if (empty($sanitized)) {
            return false;
        }

        return $sanitized;
    }

    /**
     * Set a secure cookie with proper flags
     * 
     * Uses:
     * - HttpOnly flag (prevents JavaScript access)
     * - Secure flag (HTTPS only)
     * - SameSite=Lax (prevents CSRF)
     * 
     * @param string $name   Cookie name
     * @param string $value  Cookie value
     * @param string $domain Cookie domain
     * @param boolean $secure Whether connection is secure
     * @since 1.4.2
     */
    private static function set_secure_cookie($name, $value, $domain, $secure) {
        
        // Calculate expiration time
        $expiration = time() + self::COOKIE_LIFETIME;

        // Set cookie options (PHP 7.3+)
        $options = array(
            'expires'  => $expiration,
            'path'     => '/',
            'domain'   => $domain,
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        );

        // Set the cookie
        if (PHP_VERSION_ID >= 70300) {
            // PHP 7.3+ supports options array
            setcookie($name, $value, $options);
        } else {
            // PHP 7.2 and earlier - use raw setcookie
            setcookie(
                $name,
                $value,
                $expiration,
                '/',
                $domain,
                $secure,
                true // httponly
            );
        }
    }

    /**
     * Get safe domain for cookie setting
     * 
     * Validates domain to prevent host header injection attacks.
     * 
     * @return string|false Safe domain, or false if invalid
     * @since 1.4.2
     */
    private static function get_safe_domain() {
        
        // Get home URL from WordPress (safe method)
        if (function_exists('home_url')) {
            $home = home_url();
            $parsed = wp_parse_url($home);
            
            if (isset($parsed['host'])) {
                $domain = $parsed['host'];
                
                // Validate domain format
                if (self::is_valid_domain($domain)) {
                    return $domain;
                }
            }
        }

        return false;
    }

    /**
     * Validate domain format to prevent injection
     * 
     * @param string $domain The domain to validate
     * @return boolean True if domain is valid
     * @since 1.4.2
     */
    private static function is_valid_domain($domain) {
        
        // Domain should not be empty
        if (empty($domain)) {
            return false;
        }

        // Domain should not contain newlines or special characters
        if (preg_match('/[\r\n\0]/', $domain)) {
            return false;
        }

        // Domain should not contain spaces
        if (preg_match('/\s/', $domain)) {
            return false;
        }

        // Domain should not contain semicolon (could inject cookie attributes)
        if (strpos($domain, ';') !== false) {
            return false;
        }

        // Domain should look like a valid hostname
        if (!preg_match('/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z]{2,}$/i', $domain)) {
            return false;
        }

        return true;
    }

    /**
     * Check if connection is secure (HTTPS)
     * 
     * Uses multiple checks for compatibility with different server setups.
     * 
     * @return boolean True if connection is secure
     * @since 1.4.2
     */
    private static function is_secure_connection() {
        
        // Check HTTPS
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        // Check SERVER_PORT
        if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
            return true;
        }

        // Check X-Forwarded-Proto header (for proxies)
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        return false;
    }

    /**
     * Get a captured UTM parameter from cookies
     * 
     * @param string $param The parameter name (e.g., 'utm_source')
     * @return string|false The parameter value, or false if not set
     * @since 1.4.2
     */
    public static function get_parameter($param) {
        
        $cookie_name = "edubot_{$param}";

        if (isset($_COOKIE[$cookie_name])) {
            // Validate before returning
            $value = sanitize_text_field($_COOKIE[$cookie_name]);
            if (!empty($value) && strlen($value) <= self::MAX_PARAM_LENGTH) {
                return $value;
            }
        }

        return false;
    }

    /**
     * Get all captured parameters as array
     * 
     * @return array Array of parameter_name => value
     * @since 1.4.2
     */
    public static function get_all_parameters() {
        
        $params = array();
        
        // Get all UTM parameters
        foreach (self::UTM_PARAMS as $param) {
            $value = self::get_parameter($param);
            if ($value !== false) {
                $params[$param] = $value;
            }
        }

        // Get all Click ID parameters
        foreach (self::CLICK_ID_PARAMS as $param) {
            $value = self::get_parameter($param);
            if ($value !== false) {
                $params[$param] = $value;
            }
        }

        return $params;
    }

    /**
     * Clear captured UTM cookies
     * 
     * Useful for resetting or testing.
     * 
     * @since 1.4.2
     */
    public static function clear_cookies() {
        
        $domain = self::get_safe_domain();
        if (empty($domain)) {
            return;
        }

        // Clear all UTM cookies
        foreach (self::UTM_PARAMS as $param) {
            self::clear_cookie("edubot_{$param}", $domain);
        }

        // Clear all Click ID cookies
        foreach (self::CLICK_ID_PARAMS as $param) {
            self::clear_cookie("edubot_{$param}", $domain);
        }
    }

    /**
     * Clear a single cookie
     * 
     * @param string $name   Cookie name
     * @param string $domain Cookie domain
     * @since 1.4.2
     */
    private static function clear_cookie($name, $domain) {
        
        if (PHP_VERSION_ID >= 70300) {
            setcookie($name, '', array(
                'expires'  => time() - 3600,
                'path'     => '/',
                'domain'   => $domain,
            ));
        } else {
            setcookie($name, '', time() - 3600, '/', $domain);
        }
    }

    /**
     * Log UTM capture event
     * 
     * @param array $params The captured parameters
     * @since 1.4.2
     */
    public static function log_capture($params) {
        
        if (function_exists('EduBot_Logger')) {
            EduBot_Logger::info("UTM parameters captured", array(
                'param_count' => count($params),
                'source' => isset($params['utm_source']) ? 'set' : 'not_set',
                'campaign' => isset($params['utm_campaign']) ? 'set' : 'not_set',
            ));
        }
    }
}
