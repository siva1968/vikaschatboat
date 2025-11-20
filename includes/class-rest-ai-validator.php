<?php
/**
 * REST API AI Validator
 * 
 * Provides on-demand AI validation via WordPress REST API
 * Completely isolated from main plugin hooks - prevents memory exhaustion
 * 
 * @package EduBot_Pro
 * @subpackage AI
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EduBot_REST_AI_Validator {
    
    const NAMESPACE = 'edubot/v1';
    const ROUTE = 'validate';
    const SETTINGS_KEY = 'edubot_ai_validator_settings';
    
    /**
     * Initialize REST API validator
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Phone validation endpoint
        register_rest_route(
            self::NAMESPACE,
            '/' . self::ROUTE . '/phone',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'validate_phone' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'input' => array(
                        'type'     => 'string',
                        'required' => true,
                    ),
                ),
            )
        );
        
        // Grade validation endpoint
        register_rest_route(
            self::NAMESPACE,
            '/' . self::ROUTE . '/grade',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'validate_grade' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'input' => array(
                        'type'     => 'string',
                        'required' => true,
                    ),
                ),
            )
        );
        
        // Connection test endpoint
        register_rest_route(
            self::NAMESPACE,
            '/' . self::ROUTE . '/test-connection',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'test_connection' ),
                'permission_callback' => array( $this, 'check_admin_permission' ),
            )
        );
    }
    
    /**
     * Validate phone number
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function validate_phone( WP_REST_Request $request ) {
        $input = sanitize_text_field( $request->get_param( 'input' ) );
        
        if ( empty( $input ) ) {
            return new WP_REST_Response(
                array(
                    'valid'   => false,
                    'message' => 'Input cannot be empty',
                    'method'  => 'none',
                ),
                400
            );
        }
        
        // Layer 1: Strict regex (10 digits)
        if ( preg_match( '/^\d{10}$/', $input ) ) {
            return new WP_REST_Response(
                array(
                    'valid'   => true,
                    'message' => 'Valid phone number',
                    'method'  => 'regex',
                    'value'   => $input,
                )
            );
        }
        
        // Layer 2: Alphanumeric extraction
        $digits = preg_replace( '/\D/', '', $input );
        if ( strlen( $digits ) === 10 ) {
            return new WP_REST_Response(
                array(
                    'valid'   => true,
                    'message' => 'Valid phone number (extracted)',
                    'method'  => 'alphanumeric',
                    'value'   => $digits,
                )
            );
        }
        
        // Layer 3: AI validation (if configured)
        $ai_result = $this->call_ai_validator( 'phone', $input );
        return new WP_REST_Response( $ai_result );
    }
    
    /**
     * Validate grade/class
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function validate_grade( WP_REST_Request $request ) {
        $input = sanitize_text_field( $request->get_param( 'input' ) );
        
        if ( empty( $input ) ) {
            return new WP_REST_Response(
                array(
                    'valid'   => false,
                    'message' => 'Input cannot be empty',
                    'method'  => 'none',
                ),
                400
            );
        }
        
        // Layer 1: Standard grade pattern (Grade 1-12)
        if ( preg_match( '/^Grade\s*([1-9]|1[0-2])$/i', $input, $matches ) ) {
            return new WP_REST_Response(
                array(
                    'valid'   => true,
                    'message' => 'Valid grade',
                    'method'  => 'regex',
                    'value'   => intval( $matches[1] ),
                )
            );
        }
        
        // Layer 2: Bounds check (extract number and validate 1-12)
        if ( preg_match( '/(\d+)/', $input, $matches ) ) {
            $grade = intval( $matches[1] );
            if ( $grade >= 1 && $grade <= 12 ) {
                return new WP_REST_Response(
                    array(
                        'valid'   => true,
                        'message' => 'Valid grade (bounds check)',
                        'method'  => 'bounds',
                        'value'   => $grade,
                    )
                );
            }
        }
        
        // Layer 3: Named grades
        $named_grades = array(
            'ukg'  => 'UKG',
            'lkg'  => 'LKG',
            'play' => 'Play',
        );
        
        $lower_input = strtolower( trim( $input ) );
        if ( isset( $named_grades[ $lower_input ] ) ) {
            return new WP_REST_Response(
                array(
                    'valid'   => true,
                    'message' => 'Valid grade',
                    'method'  => 'named',
                    'value'   => $named_grades[ $lower_input ],
                )
            );
        }
        
        // Layer 4: AI validation (if configured)
        $ai_result = $this->call_ai_validator( 'grade', $input );
        return new WP_REST_Response( $ai_result );
    }
    
    /**
     * Call AI validator service
     * 
     * @param string $type Validation type
     * @param string $input User input
     * @return array Result
     */
    private function call_ai_validator( $type, $input ) {
        // Get settings (simple, direct call - no recursion)
        $settings = get_option( self::SETTINGS_KEY, array() );
        
        // Check if AI validation is enabled
        if ( empty( $settings['enabled'] ) || empty( $settings['api_key'] ) ) {
            return array(
                'valid'   => false,
                'message' => 'AI validation not configured',
                'method'  => 'fallback',
            );
        }
        
        $provider = $settings['provider'] ?? 'claude';
        
        try {
            if ( 'claude' === $provider ) {
                return $this->call_claude_api( $type, $input, $settings );
            } elseif ( 'openai' === $provider ) {
                return $this->call_openai_api( $type, $input, $settings );
            }
        } catch ( Exception $e ) {
            error_log( 'EduBot AI Validator Error: ' . $e->getMessage() );
            return array(
                'valid'   => false,
                'message' => 'AI validation service unavailable',
                'method'  => 'error',
            );
        }
        
        return array(
            'valid'   => false,
            'message' => 'Invalid AI provider',
            'method'  => 'fallback',
        );
    }
    
    /**
     * Call Claude API
     * 
     * @param string $type Validation type
     * @param string $input User input
     * @param array  $settings AI settings
     * @return array Result
     */
    private function call_claude_api( $type, $input, $settings ) {
        $prompt = $this->build_prompt( $type, $input );
        
        $response = wp_remote_post(
            'https://api.anthropic.com/v1/messages',
            array(
                'timeout' => intval( $settings['timeout'] ?? 10 ),
                'headers' => array(
                    'x-api-key'         => $settings['api_key'],
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ),
                'body'    => wp_json_encode(
                    array(
                        'model'       => $settings['model'] ?? 'claude-3-5-sonnet',
                        'max_tokens'  => intval( $settings['max_tokens'] ?? 500 ),
                        'temperature' => floatval( $settings['temperature'] ?? 0.3 ),
                        'messages'    => array(
                            array(
                                'role'    => 'user',
                                'content' => $prompt,
                            ),
                        ),
                    )
                ),
            )
        );
        
        if ( is_wp_error( $response ) ) {
            return array(
                'valid'   => false,
                'message' => 'API request failed',
                'method'  => 'claude',
            );
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( ! isset( $body['content'][0]['text'] ) ) {
            return array(
                'valid'   => false,
                'message' => 'Invalid API response',
                'method'  => 'claude',
            );
        }
        
        return $this->parse_ai_response( $body['content'][0]['text'], $type );
    }
    
    /**
     * Call OpenAI API
     * 
     * @param string $type Validation type
     * @param string $input User input
     * @param array  $settings AI settings
     * @return array Result
     */
    private function call_openai_api( $type, $input, $settings ) {
        $prompt = $this->build_prompt( $type, $input );
        
        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            array(
                'timeout' => intval( $settings['timeout'] ?? 10 ),
                'headers' => array(
                    'Authorization' => 'Bearer ' . $settings['api_key'],
                    'content-type'  => 'application/json',
                ),
                'body'    => wp_json_encode(
                    array(
                        'model'       => $settings['model'] ?? 'gpt-4',
                        'max_tokens'  => intval( $settings['max_tokens'] ?? 500 ),
                        'temperature' => floatval( $settings['temperature'] ?? 0.3 ),
                        'messages'    => array(
                            array(
                                'role'    => 'user',
                                'content' => $prompt,
                            ),
                        ),
                    )
                ),
            )
        );
        
        if ( is_wp_error( $response ) ) {
            return array(
                'valid'   => false,
                'message' => 'API request failed',
                'method'  => 'openai',
            );
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( ! isset( $body['choices'][0]['message']['content'] ) ) {
            return array(
                'valid'   => false,
                'message' => 'Invalid API response',
                'method'  => 'openai',
            );
        }
        
        return $this->parse_ai_response( $body['choices'][0]['message']['content'], $type );
    }
    
    /**
     * Build validation prompt
     * 
     * @param string $type Validation type
     * @param string $input User input
     * @return string Prompt text
     */
    private function build_prompt( $type, $input ) {
        if ( 'phone' === $type ) {
            return "Extract a valid Indian phone number from this text: \"$input\"\n\n"
                . "Rules:\n"
                . "- Must be exactly 10 digits\n"
                . "- Can start with any digit 0-9\n"
                . "- Extract only the digits\n\n"
                . "Response format: VALID:9876543210 or INVALID:reason";
        } elseif ( 'grade' === $type ) {
            return "Extract the grade/class from this text: \"$input\"\n\n"
                . "Valid grades:\n"
                . "- Numeric: 1-12\n"
                . "- Named: UKG, LKG, Play\n\n"
                . "Response format: VALID:5 or INVALID:reason";
        }
        
        return "Validate this input: $input";
    }
    
    /**
     * Parse AI response
     * 
     * @param string $response AI response text
     * @param string $type Validation type
     * @return array Result
     */
    private function parse_ai_response( $response, $type ) {
        $response = trim( $response );
        
        if ( strpos( $response, 'VALID:' ) === 0 ) {
            $value = str_replace( 'VALID:', '', $response );
            $value = trim( $value );
            
            return array(
                'valid'   => true,
                'message' => 'Valid input (AI validated)',
                'method'  => 'ai',
                'value'   => $value,
            );
        }
        
        if ( strpos( $response, 'INVALID:' ) === 0 ) {
            $reason = str_replace( 'INVALID:', '', $response );
            $reason = trim( $reason );
            
            return array(
                'valid'   => false,
                'message' => 'Invalid: ' . $reason,
                'method'  => 'ai',
            );
        }
        
        return array(
            'valid'   => false,
            'message' => 'Could not parse AI response',
            'method'  => 'ai',
        );
    }
    
    /**
     * Test API connection
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function test_connection( WP_REST_Request $request ) {
        $settings = get_option( self::SETTINGS_KEY, array() );
        
        if ( empty( $settings['api_key'] ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'No API key configured',
                )
            );
        }
        
        $provider = $settings['provider'] ?? 'claude';
        
        if ( 'claude' === $provider ) {
            return $this->test_claude_connection( $settings );
        } elseif ( 'openai' === $provider ) {
            return $this->test_openai_connection( $settings );
        }
        
        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => 'Unknown provider',
            )
        );
    }
    
    /**
     * Test Claude API connection
     * 
     * @param array $settings AI settings
     * @return WP_REST_Response Response
     */
    private function test_claude_connection( $settings ) {
        $response = wp_remote_post(
            'https://api.anthropic.com/v1/messages',
            array(
                'timeout' => 10,
                'headers' => array(
                    'x-api-key'         => $settings['api_key'],
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ),
                'body'    => wp_json_encode(
                    array(
                        'model'      => $settings['model'] ?? 'claude-3-5-sonnet',
                        'max_tokens' => 100,
                        'messages'   => array(
                            array(
                                'role'    => 'user',
                                'content' => 'Test connection',
                            ),
                        ),
                    )
                ),
            )
        );
        
        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => $response->get_error_message(),
                )
            );
        }
        
        $status = wp_remote_retrieve_response_code( $response );
        
        if ( 200 === $status ) {
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'message' => 'Connection successful!',
                )
            );
        }
        
        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => "API returned status: $status",
            )
        );
    }
    
    /**
     * Test OpenAI API connection
     * 
     * @param array $settings AI settings
     * @return WP_REST_Response Response
     */
    private function test_openai_connection( $settings ) {
        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            array(
                'timeout' => 10,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $settings['api_key'],
                    'content-type'  => 'application/json',
                ),
                'body'    => wp_json_encode(
                    array(
                        'model'    => $settings['model'] ?? 'gpt-4',
                        'messages' => array(
                            array(
                                'role'    => 'user',
                                'content' => 'Test connection',
                            ),
                        ),
                    )
                ),
            )
        );
        
        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => $response->get_error_message(),
                )
            );
        }
        
        $status = wp_remote_retrieve_response_code( $response );
        
        if ( 200 === $status ) {
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'message' => 'Connection successful!',
                )
            );
        }
        
        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => "API returned status: $status",
            )
        );
    }
    
    /**
     * Check admin permission
     * 
     * @return bool Whether user can manage options
     */
    public function check_admin_permission() {
        return current_user_can( 'manage_options' );
    }
}

// Initialize on plugin load
new EduBot_REST_AI_Validator();
?>
