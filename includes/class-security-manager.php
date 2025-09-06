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
            $key = wp_generate_password(64, true, true);
            update_option('edubot_encryption_key', $key);
        }
        return $key;
    }

    /**
     * Encrypt API key
     */
    public function encrypt_api_key($api_key) {
        if (empty($api_key)) {
            return '';
        }

        $method = 'AES-256-CBC';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        $encrypted = openssl_encrypt($api_key, $method, $this->encryption_key, 0, $iv);

        if ($encrypted === false) {
            return '';
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt API key
     */
    public function decrypt_api_key($encrypted_key) {
        if (empty($encrypted_key)) {
            return '';
        }

        $data = base64_decode($encrypted_key);
        if ($data === false) {
            return '';
        }

        $method = 'AES-256-CBC';
        $iv_length = openssl_cipher_iv_length($method);
        
        if (strlen($data) < $iv_length) {
            return '';
        }

        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);

        $decrypted = openssl_decrypt($encrypted, $method, $this->encryption_key, 0, $iv);
        
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
    public function check_rate_limit($identifier, $limit = 60, $window = 3600) {
        $key = 'edubot_rate_limit_' . md5($identifier);
        $current_count = get_transient($key);
        
        if ($current_count === false) {
            set_transient($key, 1, $window);
            return true;
        }
        
        if ($current_count >= $limit) {
            return false;
        }
        
        set_transient($key, $current_count + 1, $window);
        return true;
    }

    /**
     * Log security events
     */
    public function log_security_event($event_type, $details = array()) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'event_type' => $event_type,
            'ip_address' => $this->get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'details' => $details
        );
        
        // Store in options table for now (could be moved to separate table)
        $security_log = get_option('edubot_security_log', array());
        $security_log[] = $log_entry;
        
        // Keep only last 1000 entries
        if (count($security_log) > 1000) {
            $security_log = array_slice($security_log, -1000);
        }
        
        update_option('edubot_security_log', $security_log);
    }
}
