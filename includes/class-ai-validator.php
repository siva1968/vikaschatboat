<?php
/**
 * AI Validator Class - EduBot Pro
 * 
 * Provides intelligent input validation using LLM (Claude, GPT) as a fallback
 * when regular regex/pattern matching fails to parse user input correctly.
 * 
 * Supports:
 * - Phone number validation and extraction
 * - Grade/class validation
 * - Email validation
 * - Custom input validation
 * 
 * @package EduBot_Pro
 * @subpackage AI
 * @version 1.0.0
 */

class EduBot_AI_Validator {

    /**
     * Settings key
     */
    const SETTINGS_KEY = 'edubot_ai_validator_settings';
    const CACHE_KEY = 'edubot_ai_validator_cache';

    /**
     * Supported AI models
     */
    const SUPPORTED_MODELS = array(
        'claude-3-5-sonnet' => 'Claude 3.5 Sonnet (Recommended)',
        'claude-3-opus' => 'Claude 3 Opus',
        'gpt-4' => 'GPT-4',
        'gpt-4-turbo' => 'GPT-4 Turbo',
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
    );

    /**
     * API endpoints
     */
    const API_ENDPOINTS = array(
        'claude' => 'https://api.anthropic.com/v1/messages',
        'openai' => 'https://api.openai.com/v1/chat/completions',
    );

    /**
     * Initialize AI Validator
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // AJAX endpoints for settings
        add_action( 'wp_ajax_edubot_ai_test_connection', array( $this, 'ajax_test_connection' ) );
        add_action( 'wp_ajax_nopriv_edubot_ai_test_connection', array( $this, 'ajax_test_connection' ) );
    }

    /**
     * Get AI settings from database
     * 
     * @return array AI configuration
     */
    public function get_settings() {
        // Static flag to prevent recursive calls
        static $loading = false;
        
        // If already loading settings, return safe defaults to prevent infinite recursion
        if ( $loading ) {
            return $this->get_default_settings();
        }
        
        $loading = true;
        
        try {
            // Get defaults first
            $defaults = $this->get_default_settings();
            
            // Check memory before loading settings to prevent exhaustion
            $memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
            $memory_used = memory_get_usage( true );
            
            // If we're using more than 75% of memory, return defaults to prevent crash
            if ( $memory_used > ( $memory_limit * 0.75 ) ) {
                return $defaults;
            }
            
            // Get stored settings
            $settings = get_option( self::SETTINGS_KEY );

            // If no stored settings, return defaults
            if ( ! $settings || ! is_array( $settings ) ) {
                return $defaults;
            }

            // Only merge if settings is actually an array
            if ( is_array( $settings ) ) {
                return array_merge( $defaults, $settings );
            }
            
            return $defaults;
        } finally {
            $loading = false;
        }
    }

    /**
     * Get default settings
     * 
     * @return array Default configuration
     */
    private function get_default_settings() {
        return array(
            'enabled'           => false,
            'provider'          => 'claude',  // 'claude' or 'openai'
            'model'             => 'claude-3-5-sonnet',
            'api_key'           => '',
            'temperature'       => 0.3,  // Lower = more deterministic
            'max_tokens'        => 500,
            'timeout'           => 10,
            'use_as_fallback'   => true,  // Use AI when regex fails
            'cache_results'     => true,
            'cache_ttl'         => 3600,  // 1 hour
            'rate_limit'        => 100,   // Requests per hour
            'log_ai_calls'      => true,
        );
    }

    /**
     * Update AI settings
     * 
     * @param array $settings New settings
     * @return array Sanitized settings (for use in sanitize_callback)
     */
    public function update_settings( $settings ) {
        // Validate input is array
        if ( ! is_array( $settings ) ) {
            return array();
        }

        // Sanitize directly without calling get_settings() to avoid recursion
        $sanitized = array(
            'enabled'           => (bool) ( $settings['enabled'] ?? false ),
            'provider'          => in_array( $settings['provider'] ?? '', array( 'claude', 'openai' ) ) 
                ? $settings['provider'] 
                : 'claude',
            'model'             => $this->sanitize_model( $settings['model'] ?? 'claude-3-5-sonnet' ),
            'api_key'           => sanitize_text_field( $settings['api_key'] ?? '' ),
            'temperature'       => floatval( $settings['temperature'] ?? 0.3 ),
            'max_tokens'        => intval( $settings['max_tokens'] ?? 500 ),
            'timeout'           => intval( $settings['timeout'] ?? 10 ),
            'use_as_fallback'   => (bool) ( $settings['use_as_fallback'] ?? true ),
            'cache_results'     => (bool) ( $settings['cache_results'] ?? true ),
            'cache_ttl'         => intval( $settings['cache_ttl'] ?? 3600 ),
            'rate_limit'        => intval( $settings['rate_limit'] ?? 100 ),
            'log_ai_calls'      => (bool) ( $settings['log_ai_calls'] ?? true ),
        );

        // Update database
        update_option( self::SETTINGS_KEY, $sanitized );
        
        // Return sanitized for the callback
        return $sanitized;
    }

    /**
     * Sanitize model name
     * 
     * @param string $model Model name
     * @return string Sanitized model
     */
    private function sanitize_model( $model ) {
        return array_key_exists( $model, self::SUPPORTED_MODELS ) ? $model : 'claude-3-5-sonnet';
    }

    /**
     * Test AI connection and API key
     * 
     * @return array Test result with success status
     */
    public function test_connection() {
        $settings = $this->get_settings();

        if ( ! $settings['enabled'] || empty( $settings['api_key'] ) ) {
            return array(
                'success' => false,
                'message' => 'AI Validator not enabled or API key missing',
            );
        }

        // Test with a simple validation
        $test_prompt = "Validate this phone number: 9876543210. Response format: {\"valid\": true/false, \"number\": \"...\"}";
        
        $response = $this->call_ai_api( $test_prompt, 'phone_validation' );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }

        return array(
            'success' => true,
            'message' => 'AI connection successful',
            'model'   => $settings['model'],
            'provider' => $settings['provider'],
        );
    }

    /**
     * Validate phone number using AI
     * 
     * @param string $input User input
     * @return array Validation result
     */
    public function validate_phone( $input ) {
        $settings = $this->get_settings();

        if ( ! $settings['enabled'] ) {
            return array(
                'valid'   => null,
                'number'  => null,
                'message' => 'AI validation disabled',
            );
        }

        // Check cache first
        $cache_key = 'phone_' . md5( $input );
        $cached = $this->get_cache( $cache_key );
        if ( $cached ) {
            return $cached;
        }

        // Build prompt for phone validation
        $prompt = $this->build_phone_validation_prompt( $input );

        // Call AI API
        $response = $this->call_ai_api( $prompt, 'phone_validation' );

        if ( is_wp_error( $response ) ) {
            return array(
                'valid'   => null,
                'number'  => null,
                'error'   => $response->get_error_message(),
                'message' => 'AI validation failed',
            );
        }

        // Parse response
        $result = $this->parse_ai_response( $response, 'phone' );

        // Cache result
        if ( $settings['cache_results'] ) {
            $this->set_cache( $cache_key, $result, $settings['cache_ttl'] );
        }

        // Log call if enabled
        if ( $settings['log_ai_calls'] ) {
            $this->log_ai_call( 'phone_validation', $input, $result );
        }

        return $result;
    }

    /**
     * Validate grade using AI
     * 
     * @param string $input User input
     * @return array Validation result
     */
    public function validate_grade( $input ) {
        $settings = $this->get_settings();

        if ( ! $settings['enabled'] ) {
            return array(
                'valid'  => null,
                'grade'  => null,
                'message' => 'AI validation disabled',
            );
        }

        // Check cache
        $cache_key = 'grade_' . md5( $input );
        $cached = $this->get_cache( $cache_key );
        if ( $cached ) {
            return $cached;
        }

        // Build prompt
        $prompt = $this->build_grade_validation_prompt( $input );

        // Call API
        $response = $this->call_ai_api( $prompt, 'grade_validation' );

        if ( is_wp_error( $response ) ) {
            return array(
                'valid'   => null,
                'grade'   => null,
                'error'   => $response->get_error_message(),
                'message' => 'AI validation failed',
            );
        }

        // Parse response
        $result = $this->parse_ai_response( $response, 'grade' );

        // Cache
        if ( $settings['cache_results'] ) {
            $this->set_cache( $cache_key, $result, $settings['cache_ttl'] );
        }

        // Log
        if ( $settings['log_ai_calls'] ) {
            $this->log_ai_call( 'grade_validation', $input, $result );
        }

        return $result;
    }

    /**
     * Build phone validation prompt
     * 
     * @param string $input User input
     * @return string Prompt for AI
     */
    private function build_phone_validation_prompt( $input ) {
        $valid_formats = "Indian 10-digit mobile numbers starting with 6-9: 9876543210, +91-9876543210, +91 9876543210";
        
        return "You are a phone number validator for an Indian school admission chatbot.

User Input: {$input}

Task: Extract and validate the phone number from the input.

Valid Format: {$valid_formats}

Invalid examples: 986612sasad (contains letters), 9-digit numbers, 11+ digit numbers

Respond ONLY in this JSON format (no markdown, no extra text):
{
  \"valid\": true or false,
  \"number\": \"extracted 10-digit number or null\",
  \"digits_found\": number,
  \"reason\": \"brief reason why valid/invalid\"
}";
    }

    /**
     * Build grade validation prompt
     * 
     * @param string $input User input
     * @return string Prompt for AI
     */
    private function build_grade_validation_prompt( $input ) {
        $valid_grades = "Nursery, PP1, PP2, Grade 1-12";
        
        return "You are a grade validator for an Indian school admission chatbot.

User Input: {$input}

Task: Extract and validate the grade/class from the input.

Valid Grades: {$valid_grades}

Examples:
- Valid: \"Grade 5, CBSE\", \"Nursery\", \"Class 8\"
- Invalid: \"Grade 22\", \"Class 0\", \"Grade 13\"

Respond ONLY in this JSON format (no markdown, no extra text):
{
  \"valid\": true or false,
  \"grade\": \"extracted grade or null\",
  \"grade_number\": 1-12 or null,
  \"reason\": \"brief reason\"
}";
    }

    /**
     * Call AI API
     * 
     * @param string $prompt The prompt to send
     * @param string $type Type of validation
     * @return string|WP_Error API response or error
     */
    private function call_ai_api( $prompt, $type = 'general' ) {
        $settings = $this->get_settings();

        if ( ! $settings['enabled'] || empty( $settings['api_key'] ) ) {
            return new WP_Error( 'disabled', 'AI Validator not enabled' );
        }

        // Check rate limit
        if ( ! $this->check_rate_limit() ) {
            return new WP_Error( 'rate_limit', 'AI API rate limit exceeded' );
        }

        // Prepare API call based on provider
        if ( $settings['provider'] === 'claude' ) {
            return $this->call_claude_api( $prompt, $settings );
        } else {
            return $this->call_openai_api( $prompt, $settings );
        }
    }

    /**
     * Call Claude API
     * 
     * @param string $prompt Prompt text
     * @param array $settings AI settings
     * @return string|WP_Error Response
     */
    private function call_claude_api( $prompt, $settings ) {
        $body = array(
            'model'       => $settings['model'],
            'max_tokens'  => $settings['max_tokens'],
            'temperature' => $settings['temperature'],
            'messages'    => array(
                array(
                    'role'    => 'user',
                    'content' => $prompt,
                ),
            ),
        );

        $response = wp_remote_post(
            self::API_ENDPOINTS['claude'],
            array(
                'headers'  => array(
                    'x-api-key'       => $settings['api_key'],
                    'anthropic-version' => '2023-06-01',
                    'Content-Type'    => 'application/json',
                ),
                'body'     => wp_json_encode( $body ),
                'timeout'  => $settings['timeout'],
                'sslverify' => true,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $status !== 200 ) {
            return new WP_Error( 'api_error', 'Claude API error: ' . $body );
        }

        $data = json_decode( $body, true );

        if ( ! isset( $data['content'][0]['text'] ) ) {
            return new WP_Error( 'parse_error', 'Failed to parse Claude response' );
        }

        return $data['content'][0]['text'];
    }

    /**
     * Call OpenAI API
     * 
     * @param string $prompt Prompt text
     * @param array $settings AI settings
     * @return string|WP_Error Response
     */
    private function call_openai_api( $prompt, $settings ) {
        $body = array(
            'model'       => $settings['model'],
            'max_tokens'  => $settings['max_tokens'],
            'temperature' => $settings['temperature'],
            'messages'    => array(
                array(
                    'role'    => 'user',
                    'content' => $prompt,
                ),
            ),
        );

        $response = wp_remote_post(
            self::API_ENDPOINTS['openai'],
            array(
                'headers'  => array(
                    'Authorization' => 'Bearer ' . $settings['api_key'],
                    'Content-Type'  => 'application/json',
                ),
                'body'     => wp_json_encode( $body ),
                'timeout'  => $settings['timeout'],
                'sslverify' => true,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $status !== 200 ) {
            return new WP_Error( 'api_error', 'OpenAI API error: ' . $body );
        }

        $data = json_decode( $body, true );

        if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'parse_error', 'Failed to parse OpenAI response' );
        }

        return $data['choices'][0]['message']['content'];
    }

    /**
     * Parse AI response
     * 
     * @param string $response AI response text
     * @param string $type Validation type
     * @return array Parsed result
     */
    private function parse_ai_response( $response, $type = 'phone' ) {
        // Extract JSON from response (handle markdown code blocks)
        $response = trim( $response );
        $response = preg_replace( '/^```json\s*/', '', $response );
        $response = preg_replace( '/\s*```$/', '', $response );

        $data = json_decode( $response, true );

        if ( ! $data ) {
            return array(
                'valid' => null,
                'error' => 'Failed to parse AI response',
            );
        }

        return $data;
    }

    /**
     * Get cache
     * 
     * @param string $key Cache key
     * @return mixed Cached value or false
     */
    private function get_cache( $key ) {
        return get_transient( self::CACHE_KEY . '_' . $key );
    }

    /**
     * Set cache
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds
     */
    private function set_cache( $key, $value, $ttl ) {
        set_transient( self::CACHE_KEY . '_' . $key, $value, $ttl );
    }

    /**
     * Check rate limit
     * 
     * @return bool Within rate limit
     */
    private function check_rate_limit() {
        $settings = $this->get_settings();
        $key = 'ai_validator_rate_limit';
        $count = get_transient( $key );

        if ( false === $count ) {
            $count = 1;
        } else {
            $count++;
        }

        if ( $count > $settings['rate_limit'] ) {
            return false;
        }

        set_transient( $key, $count, 3600 );
        return true;
    }

    /**
     * Log AI call
     * 
     * @param string $type Type of validation
     * @param string $input User input
     * @param array $result Validation result
     */
    private function log_ai_call( $type, $input, $result ) {
        global $wpdb;

        // Ensure log table exists
        $this->ensure_log_table();

        $table = $wpdb->prefix . 'edubot_ai_validator_log';

        $wpdb->insert(
            $table,
            array(
                'type'       => $type,
                'input'      => $input,
                'result'     => wp_json_encode( $result ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Ensure log table exists
     */
    private function ensure_log_table() {
        global $wpdb;

        $table = $wpdb->prefix . 'edubot_ai_validator_log';

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            type VARCHAR(50),
            input TEXT,
            result LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_type (type),
            KEY idx_created (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * AJAX: Test AI connection
     */
    public function ajax_test_connection() {
        check_ajax_referer( 'edubot_ai_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        // Get settings from POST
        $settings_input = isset( $_POST['settings'] ) ? json_decode( wp_unslash( $_POST['settings'] ), true ) : array();
        
        // Temporarily update settings for test
        if ( ! empty( $settings_input ) ) {
            $current = $this->get_settings();
            $test_settings = array_merge( $current, $settings_input );
            $this->update_settings( $test_settings );
        }

        // Test connection
        $result = $this->test_connection();

        wp_send_json( $result );
    }

    /**
     * Get AI models for select dropdown
     * 
     * @return array Models grouped by provider
     */
    public static function get_models() {
        return self::SUPPORTED_MODELS;
    }

    /**
     * Get AI providers for select dropdown
     * 
     * @return array Providers
     */
    public static function get_providers() {
        return array(
            'claude' => 'Anthropic Claude',
            'openai' => 'OpenAI',
        );
    }
}

// Initialize on plugin load (prevent multiple instantiation)
if ( ! isset( $GLOBALS['edubot_ai_validator'] ) || ! $GLOBALS['edubot_ai_validator'] instanceof EduBot_AI_Validator ) {
    global $edubot_ai_validator;
    $edubot_ai_validator = new EduBot_AI_Validator();
}
