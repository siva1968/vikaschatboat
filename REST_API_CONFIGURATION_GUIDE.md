# REST API Configuration Guide

**Date**: November 6, 2025  
**Status**: ✅ Complete & Ready  
**Version**: 2.0

---

## Overview

The REST API endpoints are **already configured and working**. This guide shows you:
1. How they work
2. How to customize them
3. How to extend them
4. How to configure settings

---

## Current REST API Endpoints

### Default Endpoints (Auto-Configured)

```
POST /wp-json/edubot/v1/validate/phone
POST /wp-json/edubot/v1/validate/grade
POST /wp-json/edubot/v1/validate/test-connection
```

These are registered automatically in `class-rest-ai-validator.php`.

---

## How REST API Works (Current Setup)

### Registration Process

The REST API is registered in `class-rest-ai-validator.php`:

```php
class EduBot_REST_AI_Validator {
    
    const NAMESPACE = 'edubot/v1';      // API namespace
    const ROUTE = 'validate';            // Base route
    
    public function __construct() {
        // Register REST routes when WordPress initializes
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }
    
    public function register_routes() {
        // Register each endpoint
        register_rest_route(...);
        register_rest_route(...);
        register_rest_route(...);
    }
}

// Auto-instantiate on plugin load
new EduBot_REST_AI_Validator();
```

### How It Works

```
User Request
    ↓
POST /wp-json/edubot/v1/validate/phone
    ↓
WordPress REST API Router
    ↓
Finds matching endpoint (registered in register_routes)
    ↓
Calls callback function (validate_phone)
    ↓
Returns JSON response
```

---

## Testing Current Endpoints

### 1. Test Phone Validation

```bash
curl -X POST http://localhost/demo/wp-json/edubot/v1/validate/phone \
  -H "Content-Type: application/json" \
  -d '{"input":"9876543210"}'

# Response:
{
  "valid": true,
  "message": "Valid phone number",
  "method": "regex",
  "value": "9876543210"
}
```

### 2. Test Grade Validation

```bash
curl -X POST http://localhost/demo/wp-json/edubot/v1/validate/grade \
  -H "Content-Type: application/json" \
  -d '{"input":"Grade 5"}'

# Response:
{
  "valid": true,
  "message": "Valid grade",
  "method": "regex",
  "value": 5
}
```

### 3. Test Connection (Admin Only)

```bash
curl -X POST http://localhost/demo/wp-json/edubot/v1/validate/test-connection \
  -u admin:password

# Response:
{
  "success": true,
  "message": "Connection successful!"
}
```

---

## Customizing REST API Endpoints

### Method 1: Add a New Endpoint

Edit `class-rest-ai-validator.php` and add a new route:

```php
public function register_routes() {
    // ... existing routes ...
    
    // NEW: Add email validation endpoint
    register_rest_route(
        self::NAMESPACE,
        '/' . self::ROUTE . '/email',
        array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'validate_email' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'input' => array(
                    'type'     => 'string',
                    'required' => true,
                ),
            ),
        )
    );
}

// NEW: Add callback function
public function validate_email( WP_REST_Request $request ) {
    $input = sanitize_email( $request->get_param( 'input' ) );
    
    if ( is_email( $input ) ) {
        return new WP_REST_Response( array(
            'valid'   => true,
            'message' => 'Valid email',
            'method'  => 'regex',
            'value'   => $input,
        ) );
    }
    
    return new WP_REST_Response( array(
        'valid'   => false,
        'message' => 'Invalid email',
        'method'  => 'regex',
    ) );
}
```

Then deploy the file:
```bash
Copy-Item "c:\Users\prasa\source\repos\AI ChatBoat\includes\class-rest-ai-validator.php" `
  -Destination "D:\xampp\htdocs\demo\wp-content\plugins\edubot-pro\includes\class-rest-ai-validator.php" -Force
```

Test the new endpoint:
```bash
curl -X POST http://localhost/demo/wp-json/edubot/v1/validate/email \
  -H "Content-Type: application/json" \
  -d '{"input":"user@example.com"}'
```

---

### Method 2: Customize Validation Rules

Edit the validation layers in the callback function:

```php
public function validate_phone( WP_REST_Request $request ) {
    $input = sanitize_text_field( $request->get_param( 'input' ) );
    
    // CUSTOMIZE: Change regex pattern
    if ( preg_match( '/^\+91\d{10}$/', $input ) ) {  // +91 prefix required
        return new WP_REST_Response( array(
            'valid'   => true,
            'message' => 'Valid Indian phone with +91',
            'method'  => 'regex',
            'value'   => $input,
        ) );
    }
    
    // CUSTOMIZE: Add custom validation layer
    if ( $this->is_valid_custom_phone( $input ) ) {
        return new WP_REST_Response( array(
            'valid'   => true,
            'message' => 'Valid phone (custom)',
            'method'  => 'custom',
            'value'   => $input,
        ) );
    }
    
    // ... rest of validation ...
}

// NEW: Custom validation method
private function is_valid_custom_phone( $input ) {
    // Your custom logic here
    return true;
}
```

---

### Method 3: Change API Namespace/Route

```php
class EduBot_REST_AI_Validator {
    
    // CUSTOMIZE: Change namespace
    const NAMESPACE = 'api/v2';        // Changed from 'edubot/v1'
    
    // CUSTOMIZE: Change route
    const ROUTE = 'validators';         // Changed from 'validate'
}

// Now endpoints will be:
// POST /wp-json/api/v2/validators/phone
// POST /wp-json/api/v2/validators/grade
// POST /wp-json/api/v2/validators/test-connection
```

---

## Configuring AI Settings

### Setting API Credentials (Non-Hook Based)

The REST API reads credentials from WordPress options:

```php
// Store credentials (ONE TIME - use this in a setup script)
update_option( 'edubot_ai_validator_settings', array(
    'enabled'     => true,                      // Enable AI
    'provider'    => 'claude',                  // 'claude' or 'openai'
    'api_key'     => 'sk-ant-...',             // Your API key
    'model'       => 'claude-3-5-sonnet',      // Model name
    'temperature' => 0.3,                       // 0-1 (lower = more deterministic)
    'max_tokens'  => 500,                       // Max response length
    'timeout'     => 10,                        // Request timeout in seconds
) );

// Get current settings
$settings = get_option( 'edubot_ai_validator_settings' );

// Update settings
$settings['enabled'] = false;
update_option( 'edubot_ai_validator_settings', $settings );
```

### Create a Setup Script

**File**: `setup-ai-validator.php`

```php
<?php
/**
 * Setup AI Validator Credentials
 * 
 * Run this once to configure AI settings:
 * http://localhost/demo/setup-ai-validator.php
 */

require_once('wp-load.php');

// Check if user is logged in as admin
if ( !is_user_logged_in() || !current_user_can('manage_options') ) {
    die('Not authorized');
}

// Get form values or use defaults
$provider = $_POST['provider'] ?? 'claude';
$api_key = $_POST['api_key'] ?? '';
$model = $_POST['model'] ?? 'claude-3-5-sonnet';

if ( empty( $api_key ) ) {
    ?>
    <form method="POST">
        <h2>Setup AI Validator</h2>
        
        <label>
            Provider:
            <select name="provider">
                <option value="claude">Claude (Recommended)</option>
                <option value="openai">OpenAI</option>
            </select>
        </label>
        <br>
        
        <label>
            API Key:
            <input type="password" name="api_key" placeholder="sk-ant-..." required>
        </label>
        <br>
        
        <label>
            Model:
            <input type="text" name="model" value="claude-3-5-sonnet">
        </label>
        <br>
        
        <button type="submit">Save Settings</button>
    </form>
    <?php
    exit;
}

// Save settings
$settings = array(
    'enabled'     => true,
    'provider'    => sanitize_text_field( $provider ),
    'api_key'     => sanitize_text_field( $api_key ),
    'model'       => sanitize_text_field( $model ),
    'temperature' => 0.3,
    'max_tokens'  => 500,
    'timeout'     => 10,
);

update_option( 'edubot_ai_validator_settings', $settings );

echo '<p>✅ AI Validator settings saved!</p>';
echo '<p><a href="http://localhost/demo/wp-admin/">Go to Admin</a></p>';
?>
```

---

## Advanced: Custom Response Formats

### Change Response Format

Edit the callback function to return custom format:

```php
public function validate_phone( WP_REST_Request $request ) {
    $input = sanitize_text_field( $request->get_param( 'input' ) );
    
    if ( preg_match( '/^\d{10}$/', $input ) ) {
        // CUSTOMIZE: Return detailed response
        return new WP_REST_Response( array(
            'status'       => 'success',          // Changed from 'valid'
            'data'         => array(
                'phone'    => $input,
                'country'  => 'India',
                'type'     => 'mobile',
            ),
            'validation'   => array(
                'method'   => 'regex',
                'duration' => 1.2,                // ms
                'cache'    => false,
            ),
            'timestamp'    => current_time('mysql'),
        ) );
    }
    
    return new WP_REST_Response( array(
        'status'  => 'error',
        'message' => 'Invalid phone',
    ), 400 );  // HTTP status code
}
```

### Add Custom HTTP Headers

```php
public function validate_phone( WP_REST_Request $request ) {
    $input = sanitize_text_field( $request->get_param( 'input' ) );
    
    if ( preg_match( '/^\d{10}$/', $input ) ) {
        $response = new WP_REST_Response( array(
            'valid' => true,
            'value' => $input,
        ) );
        
        // CUSTOMIZE: Add custom headers
        $response->set_headers( array(
            'X-Validation-Method' => 'regex',
            'X-Processing-Time'   => '1.2ms',
            'Cache-Control'       => 'no-cache',
        ) );
        
        return $response;
    }
    
    return new WP_REST_Response( array(
        'valid' => false,
    ), 400 );
}
```

---

## Advanced: Add Authentication

### Require API Key

```php
public function register_routes() {
    register_rest_route(
        self::NAMESPACE,
        '/' . self::ROUTE . '/phone',
        array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'validate_phone' ),
            'permission_callback' => array( $this, 'check_api_key' ),  // NEW
            'args'                => array(
                'input' => array(
                    'type'     => 'string',
                    'required' => true,
                ),
            ),
        )
    );
}

// NEW: Check API key
public function check_api_key( WP_REST_Request $request ) {
    $api_key = $request->get_header( 'X-API-Key' );
    $stored_key = get_option( 'edubot_api_key' );
    
    if ( empty( $api_key ) || $api_key !== $stored_key ) {
        return new WP_Error(
            'invalid_api_key',
            'Invalid API key',
            array( 'status' => 401 )
        );
    }
    
    return true;
}
```

Then test with API key:
```bash
curl -X POST http://localhost/demo/wp-json/edubot/v1/validate/phone \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-secret-key" \
  -d '{"input":"9876543210"}'
```

---

## Advanced: Add Rate Limiting

```php
public function validate_phone( WP_REST_Request $request ) {
    // NEW: Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'];
    $cache_key = 'edubot_api_' . $ip;
    $request_count = get_transient( $cache_key );
    
    if ( $request_count > 100 ) {  // Max 100 requests per hour
        return new WP_Error(
            'rate_limit_exceeded',
            'Too many requests',
            array( 'status' => 429 )
        );
    }
    
    // Increment counter (expires in 1 hour)
    set_transient( $cache_key, $request_count + 1, HOUR_IN_SECONDS );
    
    // ... rest of validation ...
}
```

---

## Advanced: Add Caching

```php
public function validate_phone( WP_REST_Request $request ) {
    $input = sanitize_text_field( $request->get_param( 'input' ) );
    
    // NEW: Check cache
    $cache_key = 'edubot_validate_phone_' . md5( $input );
    $cached_result = get_transient( $cache_key );
    
    if ( $cached_result ) {
        return new WP_REST_Response( array_merge(
            $cached_result,
            array( 'cached' => true )
        ) );
    }
    
    // Do validation
    if ( preg_match( '/^\d{10}$/', $input ) ) {
        $result = array(
            'valid'   => true,
            'message' => 'Valid phone number',
            'method'  => 'regex',
            'value'   => $input,
        );
    } else {
        $result = array(
            'valid'   => false,
            'message' => 'Invalid phone',
            'method'  => 'regex',
        );
    }
    
    // NEW: Cache result for 1 hour
    set_transient( $cache_key, $result, HOUR_IN_SECONDS );
    
    return new WP_REST_Response( array_merge(
        $result,
        array( 'cached' => false )
    ) );
}
```

---

## Configuration Files to Create

### File 1: Settings Store Script

**Path**: `D:\xampp\htdocs\demo\configure-ai.php`

```php
<?php
require_once('wp-load.php');

if ( !current_user_can('manage_options') ) {
    die('Access Denied');
}

// Save settings
if ( $_POST ) {
    update_option( 'edubot_ai_validator_settings', array(
        'enabled'     => true,
        'provider'    => 'claude',
        'api_key'     => $_POST['api_key'] ?? '',
        'model'       => 'claude-3-5-sonnet',
        'temperature' => 0.3,
        'max_tokens'  => 500,
        'timeout'     => 10,
    ) );
    echo '<p style="color:green;">✅ Settings saved!</p>';
}

$settings = get_option( 'edubot_ai_validator_settings' );
?>

<form method="POST" style="max-width:400px;">
    <h2>Configure AI Validator</h2>
    
    <label>API Key:</label><br>
    <input type="password" name="api_key" value="<?php echo isset($settings['api_key']) ? '•••' : ''; ?>" required><br><br>
    
    <button type="submit">Save</button>
</form>
```

### File 2: Test Endpoints Script

**Path**: `D:\xampp\htdocs\demo\test-api.php`

```php
<?php
require_once('wp-load.php');
?>

<h2>Test REST API Endpoints</h2>

<h3>Phone Validation</h3>
<pre>
POST /wp-json/edubot/v1/validate/phone
{"input":"9876543210"}
</pre>
<button onclick="testPhone()">Test</button>
<div id="phone-result"></div>

<h3>Grade Validation</h3>
<pre>
POST /wp-json/edubot/v1/validate/grade
{"input":"Grade 5"}
</pre>
<button onclick="testGrade()">Test</button>
<div id="grade-result"></div>

<script>
async function testPhone() {
    const res = await fetch('/demo/wp-json/edubot/v1/validate/phone', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({input: '9876543210'})
    });
    const data = await res.json();
    document.getElementById('phone-result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
}

async function testGrade() {
    const res = await fetch('/demo/wp-json/edubot/v1/validate/grade', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({input: 'Grade 5'})
    });
    const data = await res.json();
    document.getElementById('grade-result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
}
</script>
```

---

## Endpoint Reference

### Full Endpoint Specifications

```
Endpoint 1: Validate Phone
├─ URL: POST /wp-json/edubot/v1/validate/phone
├─ Auth: None required
├─ Input: {"input":"9876543210"}
├─ Output: {"valid":true,"method":"regex","value":"9876543210"}
├─ HTTP Status: 200 (success) or 400 (error)
└─ Response Time: 1-2ms

Endpoint 2: Validate Grade
├─ URL: POST /wp-json/edubot/v1/validate/grade
├─ Auth: None required
├─ Input: {"input":"Grade 5"}
├─ Output: {"valid":true,"method":"regex","value":5}
├─ HTTP Status: 200 (success) or 400 (error)
└─ Response Time: 1-2ms

Endpoint 3: Test Connection (Admin Only)
├─ URL: POST /wp-json/edubot/v1/validate/test-connection
├─ Auth: Basic auth (username:password)
├─ Input: (none)
├─ Output: {"success":true,"message":"Connection successful!"}
├─ HTTP Status: 200 (success) or 401 (unauthorized)
└─ Response Time: 5-10s (depends on API)
```

---

## Debugging REST API

### Enable Debug Mode

Add to `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Check logs:
```bash
tail -f D:\xampp\htdocs\demo\wp-content\debug.log
```

### Test Endpoint with Debug Info

```bash
curl -v -X POST http://localhost/demo/wp-json/edubot/v1/validate/phone \
  -H "Content-Type: application/json" \
  -d '{"input":"9876543210"}'

# Look for:
# HTTP/1.1 200 OK          ← Status
# X-WP-Total: 1            ← Headers
# Content-Type: application/json  ← Content type
```

### Check if REST API is Accessible

```bash
curl http://localhost/demo/wp-json/

# Should return something like:
# {"namespace":"","routes":{"\/wp\/v2\/...}}
```

---

## Summary

### Current Setup
✅ REST API already configured and working  
✅ Phone and grade validation endpoints active  
✅ AI validation optional (can be enabled)  

### Customization Options
✅ Add new endpoints  
✅ Change validation rules  
✅ Customize response format  
✅ Add authentication  
✅ Add rate limiting  
✅ Add caching  

### Next Steps
1. Test current endpoints (working ✅)
2. Configure AI settings (optional)
3. Extend endpoints as needed
4. Monitor in production

---

**For more information, see:**
- `README_AI_VALIDATOR_FIX.md` - Quick start
- `AI_VALIDATOR_REST_IMPLEMENTATION.md` - Full specs
- `AI_VALIDATOR_REST_QUICK_START.md` - Testing guide

---

**Status**: ✅ REST API Fully Configured & Ready
