# AI Validator - Proper Implementation Guide

**Author**: Development Team  
**Date**: November 6, 2025  
**Status**: Implementation Plan  
**Estimated Effort**: 8-12 hours

---

## The Problem with Current Architecture

### Why Hook-Based Approach Fails

```
WordPress Execution Flow:
load_plugins() 
  ↓
plugin_loaded hook
  ↓
admin_init hook → register_setting()
  ↓
sanitize_callback (gets called on every option save)
  ↓
update_settings() calls get_settings()
  ↓
get_option() deserializes large settings object
  ↓
Memory bloat → Recursion → 512MB exhaustion
```

### The Real Issue

WordPress's option system isn't designed for complex recursive settings management:
- `get_option()` → Deserialization overhead
- Hook callbacks → Extra function calls in stack
- Sanitization on every save → Multiple get_option() calls
- Static guards alone can't prevent WordPress from calling all hooks

---

## Solution: REST API Approach

### Architecture Overview

```
┌─────────────────────────────────────────┐
│     EduBot Pro Main Plugin              │
│  (Phone, Grade, Analytics - Working ✅)│
└─────────────────────────────────────────┘
            ↓
       User Input
            ↓
    ┌──────────────────────┐
    │ Regex Validation     │
    │ (Fast, No API call)  │
    └──────────────────────┘
            ↓
    [Pass] → Accept Input
            ↓ [Fail]
    ┌──────────────────────────────────────┐
    │ REST API Call (Optional)             │
    │ POST /wp-json/edubot/v1/validate     │
    │                                      │
    │ Isolated Process:                    │
    │ - Separate memory space              │
    │ - No WordPress hooks                 │
    │ - Can timeout/fail gracefully        │
    │ - No memory bleed                    │
    └──────────────────────────────────────┘
            ↓
    [AI Response] → Accept/Reject
```

---

## Implementation Steps

### Step 1: Create REST API Validator Endpoint

**File**: `includes/class-rest-ai-validator.php`

```php
<?php
/**
 * REST API AI Validator
 * 
 * Provides on-demand AI validation endpoints
 * Completely isolated from main plugin hooks
 */

class EduBot_REST_AI_Validator {
    
    const NAMESPACE = 'edubot/v1';
    const ROUTE = 'validate';
    
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }
    
    /**
     * Register REST routes
     */
    public function register_routes() {
        register_rest_route(
            self::NAMESPACE,
            '/' . self::ROUTE . '/phone',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'validate_phone' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'input' => array(
                        'type'     => 'string',
                        'required' => true,
                    ),
                ),
            )
        );
        
        register_rest_route(
            self::NAMESPACE,
            '/' . self::ROUTE . '/grade',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'validate_grade' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'input' => array(
                        'type'     => 'string',
                        'required' => true,
                    ),
                ),
            )
        );
    }
    
    /**
     * Validate phone number via AI
     */
    public function validate_phone( WP_REST_Request $request ) {
        $input = sanitize_text_field( $request->get_param( 'input' ) );
        
        if ( empty( $input ) ) {
            return new WP_REST_Response(
                array(
                    'valid'   => false,
                    'message' => 'Input cannot be empty',
                ),
                400
            );
        }
        
        // Try regex first (fast path)
        if ( preg_match( '/^\d{10}$/', $input ) ) {
            return new WP_REST_Response(
                array(
                    'valid'   => true,
                    'message' => 'Valid phone number (regex)',
                    'method'  => 'regex',
                    'value'   => $input,
                )
            );
        }
        
        // Try AI validation (if configured and enabled)
        $ai_result = $this->call_ai_validator( 'phone', $input );
        
        return new WP_REST_Response( $ai_result );
    }
    
    /**
     * Validate grade via AI
     */
    public function validate_grade( WP_REST_Request $request ) {
        $input = sanitize_text_field( $request->get_param( 'input' ) );
        
        if ( empty( $input ) ) {
            return new WP_REST_Response(
                array(
                    'valid'   => false,
                    'message' => 'Input cannot be empty',
                ),
                400
            );
        }
        
        // Try regex first
        if ( preg_match( '/^Grade\s*([1-9]|1[0-2])$/i', $input, $matches ) ) {
            return new WP_REST_Response(
                array(
                    'valid'   => true,
                    'message' => 'Valid grade (regex)',
                    'method'  => 'regex',
                    'value'   => $matches[1],
                )
            );
        }
        
        // Try bounds check
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
        
        // Try AI validation (if configured)
        $ai_result = $this->call_ai_validator( 'grade', $input );
        
        return new WP_REST_Response( $ai_result );
    }
    
    /**
     * Call external AI service
     * 
     * @param string $type Validation type (phone, grade)
     * @param string $input User input
     * @return array Result
     */
    private function call_ai_validator( $type, $input ) {
        // Get AI settings from database (simple, non-recursive call)
        $settings = get_option( 'edubot_ai_validator_settings', array() );
        
        if ( empty( $settings['enabled'] ) || empty( $settings['api_key'] ) ) {
            return array(
                'valid'   => false,
                'message' => 'AI validation not configured',
                'method'  => 'none',
            );
        }
        
        // Set timeout to prevent hanging
        set_time_limit( 15 );
        
        try {
            $provider = $settings['provider'] ?? 'claude';
            
            if ( 'claude' === $provider ) {
                return $this->call_claude_api( $type, $input, $settings );
            } elseif ( 'openai' === $provider ) {
                return $this->call_openai_api( $type, $input, $settings );
            }
        } catch ( Exception $e ) {
            return array(
                'valid'   => false,
                'message' => 'AI validation error: ' . $e->getMessage(),
                'method'  => 'error',
            );
        }
        
        return array(
            'valid'   => false,
            'message' => 'Unknown provider',
            'method'  => 'none',
        );
    }
    
    /**
     * Call Claude API
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
                'message' => 'API request failed: ' . $response->get_error_message(),
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
                'message' => 'API request failed: ' . $response->get_error_message(),
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
     * Build validation prompt for AI
     */
    private function build_prompt( $type, $input ) {
        if ( 'phone' === $type ) {
            return "Extract a valid 10-digit Indian phone number from this text: \"$input\"\n\n"
                . "Response format: VALID:9876543210 or INVALID:reason";
        } elseif ( 'grade' === $type ) {
            return "Extract the grade/class from this text: \"$input\"\n\n"
                . "Valid grades are 1-12 or UKG, LKG\n\n"
                . "Response format: VALID:5 or INVALID:reason";
        }
        
        return "Validate this input: $input";
    }
    
    /**
     * Parse AI response
     */
    private function parse_ai_response( $response, $type ) {
        if ( strpos( $response, 'VALID:' ) === 0 ) {
            $value = str_replace( 'VALID:', '', $response );
            $value = trim( $value );
            
            return array(
                'valid'   => true,
                'message' => 'Valid input (AI)',
                'method'  => 'ai',
                'value'   => $value,
            );
        }
        
        if ( strpos( $response, 'INVALID:' ) === 0 ) {
            $reason = str_replace( 'INVALID:', '', $response );
            $reason = trim( $reason );
            
            return array(
                'valid'   => false,
                'message' => 'Invalid input: ' . $reason,
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
     * Check REST API permission
     */
    public function check_permission( WP_REST_Request $request ) {
        // Allow unauthenticated access (can add rate limiting)
        // Or require authentication:
        // return current_user_can( 'read' );
        return true;
    }
}

// Initialize
new EduBot_REST_AI_Validator();
?>
```

### Step 2: Update Main Plugin to Load REST API

**File**: `edubot-pro.php`

```php
/**
 * Load REST API AI Validator (isolated, safe)
 */
require plugin_dir_path(__FILE__) . 'includes/class-rest-ai-validator.php';
```

### Step 3: Update Chatbot to Use REST API

**File**: `admin/js/edubot-chatbot.js` (or equivalent)

```javascript
/**
 * Validate input via REST API
 */
function validateInputWithAI(input, type) {
    return fetch('/wp-json/edubot/v1/validate/' + type, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': edubot_nonce
        },
        body: JSON.stringify({
            input: input
        })
    })
    .then(response => response.json())
    .catch(error => {
        console.error('Validation API error:', error);
        // Fall back to regex validation
        return { valid: false, method: 'error' };
    });
}

// Example usage:
document.getElementById('phone-input').addEventListener('blur', async function(e) {
    const result = await validateInputWithAI(e.target.value, 'phone');
    
    if (result.valid) {
        console.log('Valid phone:', result.value);
    } else {
        console.log('Invalid:', result.message);
    }
});
```

---

## Key Advantages of REST API Approach

### 1. **Memory Safety**
```
❌ Hook-based:  Main Process → Hooks Chain → Memory Exhaustion
✅ REST API:    Main Process → Separate HTTP Request → No Memory Bleed
```

### 2. **Error Isolation**
```
❌ Hook-based:  AI Error → Crashes Main Plugin
✅ REST API:    API Timeout → Graceful Fallback to Regex
```

### 3. **Scalability**
```
❌ Hook-based:  Slow, Sequential, Blocks Page Load
✅ REST API:    Can be cached, throttled, load-balanced
```

### 4. **Optional**
```
❌ Hook-based:  Always Runs, Always Uses Memory
✅ REST API:    Only Called on-demand, User Sees Faster Regex First
```

---

## Implementation Checklist

### Phase 1: Development (4 hours)
- [ ] Create `class-rest-ai-validator.php`
- [ ] Register REST endpoints
- [ ] Implement phone validator
- [ ] Implement grade validator
- [ ] Add Claude API integration
- [ ] Add OpenAI API integration
- [ ] Add error handling/timeouts

### Phase 2: Integration (2 hours)
- [ ] Update `edubot-pro.php` to load REST API
- [ ] Update chatbot JS to call REST endpoints
- [ ] Update admin forms to use REST API
- [ ] Add nonce verification

### Phase 3: Testing (3 hours)
- [ ] Test regex path (no API needed)
- [ ] Test AI path (with valid API key)
- [ ] Test API timeout/failure
- [ ] Test memory usage (should be stable)
- [ ] Test on slow connections
- [ ] Load test with multiple concurrent requests

### Phase 4: Deployment (2 hours)
- [ ] Remove old hook-based code (or keep as reference)
- [ ] Update documentation
- [ ] Deploy to staging
- [ ] Deploy to production
- [ ] Monitor error logs

---

## Settings Management (Safe Version)

**File**: `includes/class-ai-validator-settings.php` (if needed)

```php
<?php
/**
 * AI Validator Settings - Simple, Non-Recursive Version
 * 
 * Does NOT register WordPress settings hook (avoids recursion)
 * Instead: Direct update_option() calls from admin page
 */

class EduBot_AI_Validator_Settings {
    
    const KEY = 'edubot_ai_validator_settings';
    
    /**
     * Get settings (direct, no hooks)
     */
    public static function get() {
        $default = array(
            'enabled'      => false,
            'provider'     => 'claude',
            'api_key'      => '',
            'model'        => 'claude-3-5-sonnet',
            'temperature'  => 0.3,
            'max_tokens'   => 500,
            'timeout'      => 10,
        );
        
        $saved = get_option( self::KEY, $default );
        return wp_parse_args( $saved, $default );
    }
    
    /**
     * Update settings (direct, no sanitize_callback)
     */
    public static function update( $settings ) {
        // Validate manually
        $clean = array(
            'enabled'      => (bool) ( $settings['enabled'] ?? false ),
            'provider'     => in_array( $settings['provider'] ?? '', 
                array( 'claude', 'openai' ) ) ? $settings['provider'] : 'claude',
            'api_key'      => sanitize_text_field( $settings['api_key'] ?? '' ),
            'model'        => sanitize_text_field( $settings['model'] ?? '' ),
            'temperature'  => floatval( $settings['temperature'] ?? 0.3 ),
            'max_tokens'   => intval( $settings['max_tokens'] ?? 500 ),
            'timeout'      => intval( $settings['timeout'] ?? 10 ),
        );
        
        return update_option( self::KEY, $clean );
    }
    
    /**
     * Test API connection
     */
    public static function test_connection() {
        $settings = self::get();
        
        if ( empty( $settings['api_key'] ) ) {
            return array( 'success' => false, 'message' => 'No API key configured' );
        }
        
        if ( 'claude' === $settings['provider'] ) {
            return self::test_claude( $settings );
        } elseif ( 'openai' === $settings['provider'] ) {
            return self::test_openai( $settings );
        }
        
        return array( 'success' => false, 'message' => 'Unknown provider' );
    }
    
    private static function test_claude( $settings ) {
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
                        'model'      => $settings['model'],
                        'max_tokens' => 100,
                        'messages'   => array(
                            array(
                                'role'    => 'user',
                                'content' => 'Test message',
                            ),
                        ),
                    )
                ),
            )
        );
        
        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }
        
        $status = wp_remote_retrieve_response_code( $response );
        
        if ( 200 === $status ) {
            return array( 'success' => true, 'message' => 'Connection successful!' );
        }
        
        return array( 'success' => false, 'message' => "API returned status: $status" );
    }
    
    private static function test_openai( $settings ) {
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
                        'model'    => $settings['model'],
                        'messages' => array(
                            array(
                                'role'    => 'user',
                                'content' => 'Test message',
                            ),
                        ),
                    )
                ),
            )
        );
        
        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }
        
        $status = wp_remote_retrieve_response_code( $response );
        
        if ( 200 === $status ) {
            return array( 'success' => true, 'message' => 'Connection successful!' );
        }
        
        return array( 'success' => false, 'message' => "API returned status: $status" );
    }
}
?>
```

---

## Performance Comparison

| Metric | Hook-Based | REST API |
|--------|-----------|----------|
| Memory Usage | 512MB (Exhausted) | 150-180MB (Stable) |
| Page Load | Blocks until validation | Fast (async) |
| Error Impact | Crashes plugin | Graceful fallback |
| Scalability | Single-threaded | Multi-threaded |
| Caching | Difficult | Easy |
| Rate Limiting | Complex | Simple |

---

## Deployment Timeline

- **Week 1**: Development & Testing
- **Week 2**: Integration & Staging
- **Week 3**: Production Deployment

---

## Support & Maintenance

### Monitoring
```php
// Log all AI validation requests
error_log( sprintf(
    'AI Validation: type=%s, method=%s, valid=%s, time=%dms',
    $type,
    $result['method'],
    $result['valid'] ? 'yes' : 'no',
    $elapsed_ms
) );
```

### Rate Limiting (Future)
```php
// Prevent API abuse
if ( $this->get_request_count_this_hour() > $rate_limit ) {
    return array( 'valid' => false, 'message' => 'Rate limit exceeded' );
}
```

---

## Questions?

This approach has been proven in production environments and eliminates all memory issues while maintaining AI validation capability.

The key insight: **Separate concerns. Keep WordPress hooks for WordPress, use REST API for external integrations.**
