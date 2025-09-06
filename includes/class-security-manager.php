<?php

/**
 * Handle security and encryption for sensitive data
 */
class EduBot_Security_Manager {

    /**
     * Encryption key for API keys
     */
    private $encryption_key;

    /**
     * Constructor
     */
    public function __construct() {
        $this->encryption_key = $this->get_encryption_key();
    }

    /**
     * Get or generate encryption key
     */
    private function get_encryption_key() {
        $key = get_option('edubot_encryption_key');
        if (!$key) {
            // Use WordPress salt for better security
            $key = hash('sha256', SECURE_AUTH_KEY . NONCE_KEY . AUTH_SALT . wp_generate_password(64, true, true));
            update_option('edubot_encryption_key', $key);
        }
        return $key;
    }

    /**
     * Generate secure salt for additional encryption layer
     */
    private function get_encryption_salt() {
        $salt = get_option('edubot_encryption_salt');
        if (!$salt) {
            $salt = wp_generate_password(32, true, true);
            update_option('edubot_encryption_salt', $salt);
        }
        return $salt;
    }

    /**
     * Encrypt API key with improved security
     */
    public function encrypt_api_key($api_key) {
        if (empty($api_key)) {
            return '';
        }

        if (!function_exists('openssl_encrypt')) {
            error_log('EduBot Security: OpenSSL not available for encryption');
            return base64_encode($api_key); // Fallback to basic encoding
        }

        $method = 'AES-256-CBC';
        $salt = $this->get_encryption_salt();
        $key = hash('sha256', $this->encryption_key . $salt);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        
        $encrypted = openssl_encrypt($api_key, $method, $key, 0, $iv);

        if ($encrypted === false) {
            error_log('EduBot Security: Failed to encrypt API key');
            return '';
        }

        // Add integrity check
        $hmac = hash_hmac('sha256', $encrypted, $key);
        
        return base64_encode($iv . $hmac . $encrypted);
    }

    /**
     * Decrypt API key with improved security
     */
    public function decrypt_api_key($encrypted_key) {
        if (empty($encrypted_key)) {
            return '';
        }

        $data = base64_decode($encrypted_key);
        if ($data === false) {
            return '';
        }

        if (!function_exists('openssl_decrypt')) {
            error_log('EduBot Security: OpenSSL not available for decryption');
            return base64_decode($encrypted_key); // Fallback
        }

        $method = 'AES-256-CBC';
        $salt = $this->get_encryption_salt();
        $key = hash('sha256', $this->encryption_key . $salt);
        $iv_length = openssl_cipher_iv_length($method);
        $hmac_length = 64; // SHA256 HMAC length
        
        if (strlen($data) < $iv_length + $hmac_length) {
            error_log('EduBot Security: Invalid encrypted data length');
            return '';
        }

        $iv = substr($data, 0, $iv_length);
        $hmac = substr($data, $iv_length, $hmac_length);
        $encrypted = substr($data, $iv_length + $hmac_length);

        // Verify integrity
        $expected_hmac = hash_hmac('sha256', $encrypted, $key);
        if (!hash_equals($hmac, $expected_hmac)) {
            error_log('EduBot Security: HMAC verification failed');
            return '';
        }

        $decrypted = openssl_decrypt($encrypted, $method, $key, 0, $iv);
        
        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * Save API keys with encryption
     */
    public function save_api_keys($api_keys) {
        $encrypted_keys = array();

        foreach ($api_keys as $key => $value) {
            if ($this->is_sensitive_field($key)) {
                $encrypted_keys[$key] = $this->encrypt_api_key($value);
            } else {
                $encrypted_keys[$key] = sanitize_text_field($value);
            }
        }

        return $encrypted_keys;
    }

    /**
     * Decrypt API keys for use
     */
    public function decrypt_api_keys($encrypted_keys) {
        $decrypted_keys = array();

        foreach ($encrypted_keys as $key => $value) {
            if ($this->is_sensitive_field($key)) {
                $decrypted_keys[$key] = $this->decrypt_api_key($value);
            } else {
                $decrypted_keys[$key] = $value;
            }
        }

        return $decrypted_keys;
    }

    /**
     * Check if field contains sensitive data
     */
    private function is_sensitive_field($field_name) {
        $sensitive_fields = array(
            'openai_key',
            'whatsapp_token',
            'email_api_key',
            'smtp_password',
            'sms_api_key'
        );

        return in_array($field_name, $sensitive_fields);
    }

    /**
     * Generate application number
     */
    public function generate_application_number() {
        $site_id = get_current_blog_id();
        $year = date('Y');
        $random = wp_generate_password(6, false, false);
        
        return sprintf('APP%d%s%s', $site_id, $year, strtoupper($random));
    }

    /**
     * Sanitize user input
     */
    public function sanitize_input($input) {
        if (is_array($input)) {
            return array_map(array($this, 'sanitize_input'), $input);
        }
        
        return sanitize_text_field($input);
    }

    /**
     * Validate nonce for security
     */
    public function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Get user IP address
     */
    public function get_user_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }

    /**
     * Rate limiting for API calls
     */
    public function check_rate_limit($identifier, $max_requests = 30, $time_window = 900) {
        $transient_key = 'edubot_rate_limit_' . md5($identifier);
        $current_count = get_transient($transient_key);
        
        if ($current_count === false) {
            // First request in the time window
            set_transient($transient_key, 1, $time_window);
            return true;
        }
        
        if ($current_count >= $max_requests) {
            // Rate limit exceeded
            error_log("EduBot Security: Rate limit exceeded for identifier: {$identifier}");
            return false;
        }
        
        // Increment counter
        set_transient($transient_key, $current_count + 1, $time_window);
        return true;
    }

    /**
     * Clear rate limit for a specific identifier
     */
    public function clear_rate_limit($identifier) {
        $transient_key = 'edubot_rate_limit_' . md5($identifier);
        return delete_transient($transient_key);
    }

    /**
     * Check for malicious content in user input
     */
    public function is_malicious_content($content) {
        $malicious_patterns = array(
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/vbscript:/i',
            '/data:text\/html/i',
            '/onload=/i',
            '/onerror=/i',
            '/onclick=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/\beval\s*\(/i',
            '/document\.cookie/i',
            '/document\.location/i',
            '/window\.location/i'
        );
        
        foreach ($malicious_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                error_log("EduBot Security: Malicious content detected: " . substr($content, 0, 100));
                return true;
            }
        }
        
        return false;
    }

    /**
     * Validate file uploads
     */
    public function validate_file_upload($file) {
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx');
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Check file size
        if ($file['size'] > $max_size) {
            return array('valid' => false, 'error' => 'File size exceeds 5MB limit');
        }
        
        // Check file type
        $file_info = pathinfo($file['name']);
        $extension = strtolower($file_info['extension']);
        
        if (!in_array($extension, $allowed_types)) {
            return array('valid' => false, 'error' => 'File type not allowed');
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = array(
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
        
        if (!in_array($mime, $allowed_mimes)) {
            return array('valid' => false, 'error' => 'Invalid file content');
        }
        
        return array('valid' => true);
    }

    /**
     * Log security events to database
     */
    public function log_security_event($event_type, $details = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_security_log';
        $site_id = get_current_blog_id();
        
        $log_entry = array(
            'site_id' => $site_id,
            'event_type' => sanitize_text_field($event_type),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? 
                substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 500) : '',
            'details' => wp_json_encode($details),
            'severity' => $this->get_event_severity($event_type),
            'created_at' => current_time('mysql')
        );
        
        // Log to error log as well for immediate alerting
        error_log('EduBot Security Event: ' . $event_type . ' from ' . $log_entry['ip_address']);
        
        // Insert into database
        $result = $wpdb->insert($table, $log_entry, array(
            '%d', // site_id
            '%s', // event_type
            '%s', // ip_address
            '%s', // user_agent
            '%s', // details
            '%s', // severity
            '%s'  // created_at
        ));
        
        if ($result === false) {
            error_log('EduBot Security: Failed to log security event to database');
        }
        
        // Clean up old logs (keep only last 10,000 entries per site)
        $this->cleanup_old_security_logs();
        
        return $result !== false;
    }

    /**
     * Get event severity level
     */
    private function get_event_severity($event_type) {
        $high_severity_events = array(
            'malicious_content',
            'malicious_content_chatbot',
            'sql_injection_attempt',
            'xss_attempt',
            'file_upload_violation',
            'authentication_bypass'
        );
        
        $medium_severity_events = array(
            'rate_limit_exceeded',
            'invalid_api_key',
            'unauthorized_access',
            'invalid_file_upload'
        );
        
        if (in_array($event_type, $high_severity_events)) {
            return 'high';
        } elseif (in_array($event_type, $medium_severity_events)) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Clean up old security logs
     */
    private function cleanup_old_security_logs() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_security_log';
        $site_id = get_current_blog_id();
        
        // Keep only the most recent 10,000 entries per site
        $result = $wpdb->query($wpdb->prepare("
            DELETE FROM $table 
            WHERE site_id = %d 
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM $table 
                    WHERE site_id = %d 
                    ORDER BY created_at DESC 
                    LIMIT 10000
                ) as recent_logs
            )",
            $site_id, $site_id
        ));
        
        // Also clean entries older than 90 days
        $wpdb->query($wpdb->prepare("
            DELETE FROM $table 
            WHERE site_id = %d 
            AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)",
            $site_id
        ));
        
        return $result;
    }

    /**
     * Check if URL is safe for use
     * @param string $url URL to validate
     * @return bool True if URL is safe, false otherwise
     */
    public function is_safe_url($url) {
        // Basic URL validation
        if (empty($url) || !is_string($url)) {
            return false;
        }

        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Parse URL components
        $parsed_url = parse_url($url);
        if (!$parsed_url || !isset($parsed_url['scheme']) || !isset($parsed_url['host'])) {
            return false;
        }

        // Only allow HTTP and HTTPS schemes
        if (!in_array(strtolower($parsed_url['scheme']), array('http', 'https'))) {
            return false;
        }

        // Block common malicious patterns
        $malicious_patterns = array(
            'javascript:',
            'data:',
            'vbscript:',
            'file:',
            'ftp:',
            '<script',
            'onload=',
            'onerror=',
            'onclick='
        );

        $url_lower = strtolower($url);
        foreach ($malicious_patterns as $pattern) {
            if (strpos($url_lower, $pattern) !== false) {
                return false;
            }
        }

        // Block suspicious domains (you can extend this list)
        $blocked_domains = array(
            'localhost',
            '127.0.0.1',
            '0.0.0.0',
            '192.168.',
            '10.',
            '172.'
        );

        $host = strtolower($parsed_url['host']);
        foreach ($blocked_domains as $blocked) {
            if (strpos($host, $blocked) === 0) {
                // Allow exceptions for development environments
                if (!defined('WP_DEBUG') || !WP_DEBUG) {
                    return false;
                }
            }
        }

        // Check for URL length (prevent extremely long URLs)
        if (strlen($url) > 2048) {
            return false;
        }

        // Additional security: check for double encoding attempts
        if (strpos($url, '%25') !== false) {
            return false;
        }

        return true;
    }

    /**
     * Validate and sanitize URL for safe use
     * @param string $url URL to validate and sanitize
     * @return string|false Sanitized URL or false if invalid
     */
    public function sanitize_url($url) {
        if (!$this->is_safe_url($url)) {
            return false;
        }

        // Additional sanitization
        $url = esc_url_raw($url);
        
        // Remove any potentially dangerous query parameters
        $dangerous_params = array('script', 'javascript', 'vbscript', 'data');
        $parsed = parse_url($url);
        
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query_params);
            foreach ($dangerous_params as $dangerous) {
                if (isset($query_params[$dangerous])) {
                    unset($query_params[$dangerous]);
                }
            }
            $parsed['query'] = http_build_query($query_params);
            $url = $this->build_url($parsed);
        }

        return $url;
    }

    /**
     * Rebuild URL from parsed components
     * @param array $parsed_url Parsed URL components
     * @return string Rebuilt URL
     */
    private function build_url($parsed_url) {
        $url = '';
        
        if (isset($parsed_url['scheme'])) {
            $url .= $parsed_url['scheme'] . '://';
        }
        
        if (isset($parsed_url['user'])) {
            $url .= $parsed_url['user'];
            if (isset($parsed_url['pass'])) {
                $url .= ':' . $parsed_url['pass'];
            }
            $url .= '@';
        }
        
        if (isset($parsed_url['host'])) {
            $url .= $parsed_url['host'];
        }
        
        if (isset($parsed_url['port'])) {
            $url .= ':' . $parsed_url['port'];
        }
        
        if (isset($parsed_url['path'])) {
            $url .= $parsed_url['path'];
        }
        
        if (isset($parsed_url['query']) && !empty($parsed_url['query'])) {
            $url .= '?' . $parsed_url['query'];
        }
        
        if (isset($parsed_url['fragment'])) {
            $url .= '#' . $parsed_url['fragment'];
        }
        
        return $url;
    }
}
