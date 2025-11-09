# Where to Select AI Model

**Date**: November 6, 2025  
**Status**: ✅ Complete Guide

---

## Quick Answer

**There are 2 places** where you can select the AI model:

1. **WordPress Settings Option** (Database)
2. **Code Configuration** (class-rest-ai-validator.php)

---

## Method 1: WordPress Settings Option (Recommended) 🟢

### How to Set AI Model via Settings

The AI model is stored in a WordPress option that you can set programmatically.

**Settings Storage Location:**
```
Option Name: edubot_ai_validator_settings
Location: wp_options table
```

**Default Settings:**
```php
$settings = array(
    'enabled'     => true,                          // Enable AI
    'provider'    => 'claude',                      // 'claude' or 'openai'
    'api_key'     => 'sk-ant-...',                 // Your API key
    'model'       => 'claude-3-5-sonnet',          // ← MODEL SELECTION
    'temperature' => 0.3,                           // 0-1 (lower = more deterministic)
    'max_tokens'  => 500,                           // Max response length
    'timeout'     => 10,                            // Request timeout in seconds
);
```

### Option A: Set via Admin PHP Script

**File**: `D:\xampp\htdocs\demo\set-ai-model.php`

```php
<?php
/**
 * Set AI Model Configuration
 * 
 * Visit: http://localhost/demo/set-ai-model.php
 */

require_once('wp-load.php');

// Check admin access
if ( !current_user_can('manage_options') ) {
    die('Not authorized. Please log in as admin first.');
}

// Get current settings
$settings = get_option( 'edubot_ai_validator_settings', array() );

// UPDATE THIS SECTION TO CHANGE MODEL:
// ==========================================

// For Claude
$settings['provider'] = 'claude';
$settings['model'] = 'claude-3-5-sonnet';  // ← Change to your preferred model
$settings['api_key'] = 'sk-ant-...';       // Paste your Claude API key here

// OR for OpenAI (uncomment to use OpenAI instead)
// $settings['provider'] = 'openai';
// $settings['model'] = 'gpt-4';            // ← Change to your preferred model
// $settings['api_key'] = 'sk-...';         // Paste your OpenAI API key here

$settings['enabled'] = true;
$settings['temperature'] = 0.3;
$settings['max_tokens'] = 500;
$settings['timeout'] = 10;

// ==========================================

// Save settings
update_option( 'edubot_ai_validator_settings', $settings );

echo '<h2>✅ AI Model Configuration Saved</h2>';
echo '<pre>' . print_r( $settings, true ) . '</pre>';
echo '<p><a href="http://localhost/demo/test-ai-model.php">Test Configuration</a></p>';
?>
```

### Option B: Set via WordPress Admin

Create a simple settings page in WordPress admin:

**File**: `D:\xampp\htdocs\demo\wp-content\plugins\edubot-pro\admin\class-ai-model-settings.php`

```php
<?php
/**
 * AI Model Settings Page
 * 
 * Simple admin interface to select AI model
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EduBot_AI_Model_Settings {
    
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_form' ) );
    }
    
    public function add_menu() {
        add_options_page(
            'EduBot AI Model',           // Page title
            'EduBot AI Model',           // Menu title
            'manage_options',            // Capability
            'edubot-ai-model',           // Menu slug
            array( $this, 'render_page' ) // Function
        );
    }
    
    public function handle_form() {
        if ( !isset( $_POST['edubot_ai_model_nonce'] ) ) {
            return;
        }
        
        if ( !wp_verify_nonce( $_POST['edubot_ai_model_nonce'], 'edubot_ai_model_action' ) ) {
            return;
        }
        
        $settings = array(
            'enabled'     => isset( $_POST['enabled'] ),
            'provider'    => sanitize_text_field( $_POST['provider'] ?? 'claude' ),
            'model'       => sanitize_text_field( $_POST['model'] ?? 'claude-3-5-sonnet' ),
            'api_key'     => sanitize_text_field( $_POST['api_key'] ?? '' ),
            'temperature' => floatval( $_POST['temperature'] ?? 0.3 ),
            'max_tokens'  => intval( $_POST['max_tokens'] ?? 500 ),
            'timeout'     => intval( $_POST['timeout'] ?? 10 ),
        );
        
        update_option( 'edubot_ai_validator_settings', $settings );
        
        wp_safe_remote_redirect( 
            add_query_arg( 'updated', '1', admin_url( 'options-general.php?page=edubot-ai-model' ) )
        );
        exit;
    }
    
    public function render_page() {
        $settings = get_option( 'edubot_ai_validator_settings', array() );
        
        $providers = array(
            'claude' => array(
                'name'   => 'Claude (Recommended)',
                'models' => array(
                    'claude-3-5-sonnet'  => 'Claude 3.5 Sonnet (Fastest, Balanced)',
                    'claude-3-opus'      => 'Claude 3 Opus (Most Powerful)',
                    'claude-3-sonnet'    => 'Claude 3 Sonnet (Balanced)',
                    'claude-3-haiku'     => 'Claude 3 Haiku (Fastest)',
                ),
            ),
            'openai' => array(
                'name'   => 'OpenAI',
                'models' => array(
                    'gpt-4'              => 'GPT-4 (Most Powerful)',
                    'gpt-4-turbo'        => 'GPT-4 Turbo (Fast & Powerful)',
                    'gpt-3.5-turbo'      => 'GPT-3.5 Turbo (Fastest)',
                ),
            ),
        );
        
        $current_provider = $settings['provider'] ?? 'claude';
        $current_model = $settings['model'] ?? 'claude-3-5-sonnet';
        
        ?>
        <div class="wrap">
            <h1>EduBot AI Model Selection</h1>
            
            <form method="POST">
                <?php wp_nonce_field( 'edubot_ai_model_action', 'edubot_ai_model_nonce' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="enabled">Enable AI Validation</label></th>
                        <td>
                            <input type="checkbox" id="enabled" name="enabled" 
                                <?php checked( !empty( $settings['enabled'] ) ); ?>>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="provider">AI Provider</label></th>
                        <td>
                            <select id="provider" name="provider" onchange="updateModels()">
                                <?php foreach ( $providers as $provider_id => $provider ) : ?>
                                    <option value="<?php echo esc_attr( $provider_id ); ?>"
                                        <?php selected( $current_provider, $provider_id ); ?>>
                                        <?php echo esc_html( $provider['name'] ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="model">AI Model</label></th>
                        <td>
                            <select id="model" name="model">
                                <?php foreach ( $providers as $provider_id => $provider ) : ?>
                                    <?php foreach ( $provider['models'] as $model_id => $model_name ) : ?>
                                        <option value="<?php echo esc_attr( $model_id ); ?>"
                                            data-provider="<?php echo esc_attr( $provider_id ); ?>"
                                            <?php selected( $current_model, $model_id ); ?>>
                                            <?php echo esc_html( $model_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="api_key">API Key</label></th>
                        <td>
                            <input type="password" id="api_key" name="api_key" 
                                value="<?php echo esc_attr( isset( $settings['api_key'] ) ? '••••••••' : '' ); ?>"
                                placeholder="sk-..." required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="temperature">Temperature</label></th>
                        <td>
                            <input type="number" id="temperature" name="temperature" 
                                step="0.1" min="0" max="1"
                                value="<?php echo esc_attr( $settings['temperature'] ?? 0.3 ); ?>">
                            <p class="description">0 = Deterministic, 1 = Random</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="max_tokens">Max Tokens</label></th>
                        <td>
                            <input type="number" id="max_tokens" name="max_tokens" 
                                min="100" max="4000"
                                value="<?php echo esc_attr( $settings['max_tokens'] ?? 500 ); ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="timeout">Timeout (seconds)</label></th>
                        <td>
                            <input type="number" id="timeout" name="timeout" 
                                min="1" max="60"
                                value="<?php echo esc_attr( $settings['timeout'] ?? 10 ); ?>">
                        </td>
                    </tr>
                </table>
                
                <?php submit_button( 'Save AI Model Configuration' ); ?>
            </form>
        </div>
        
        <script>
        function updateModels() {
            const provider = document.getElementById('provider').value;
            const options = document.querySelectorAll('#model option');
            
            options.forEach( option => {
                if ( option.dataset.provider === provider ) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Select first visible option
            document.getElementById('model').value = 
                Array.from(options).find(o => o.style.display !== 'none').value;
        }
        
        updateModels();
        </script>
        <?php
    }
}

new EduBot_AI_Model_Settings();
```

Load this in `edubot-pro.php`:
```php
require plugin_dir_path(__FILE__) . 'admin/class-ai-model-settings.php';
```

---

## Method 2: Code Configuration (Direct Edit)

### Location in Code

**File**: `includes/class-rest-ai-validator.php`

**Line 267** (Claude model):
```php
'model'       => $settings['model'] ?? 'claude-3-5-sonnet',
                                       ↑ DEFAULT MODEL ↑
```

**Line 323** (OpenAI model):
```php
'model'       => $settings['model'] ?? 'gpt-4',
                                       ↑ DEFAULT MODEL ↑
```

### How to Change Default Model

Edit `class-rest-ai-validator.php`:

```php
// CLAUDE - Line 267
// Change from:
'model'       => $settings['model'] ?? 'claude-3-5-sonnet',

// To:
'model'       => $settings['model'] ?? 'claude-3-opus',  // Faster & Powerful
// Or:
'model'       => $settings['model'] ?? 'claude-3-haiku', // Fastest
```

Or for OpenAI (Line 323):
```php
// OPENAI - Line 323
// Change from:
'model'       => $settings['model'] ?? 'gpt-4',

// To:
'model'       => $settings['model'] ?? 'gpt-4-turbo',    // Balanced
// Or:
'model'       => $settings['model'] ?? 'gpt-3.5-turbo',  // Fastest
```

---

## Available Models

### Claude Models

| Model | Speed | Power | Cost | Best For |
|-------|-------|-------|------|----------|
| `claude-3-5-sonnet` | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | 💰 | **Recommended** |
| `claude-3-opus` | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | 💰💰 | Complex tasks |
| `claude-3-sonnet` | ⭐⭐⭐ | ⭐⭐⭐⭐ | 💰 | Balanced |
| `claude-3-haiku` | ⭐⭐⭐⭐⭐ | ⭐⭐ | 💰 | Fast validation |

### OpenAI Models

| Model | Speed | Power | Cost | Best For |
|-------|-------|-------|------|----------|
| `gpt-4` | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | 💰💰💰 | Most powerful |
| `gpt-4-turbo` | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | 💰💰 | Balanced |
| `gpt-3.5-turbo` | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | 💰 | Fast & cheap |

---

## How to Test Model Selection

### Test Current Model

**File**: `D:\xampp\htdocs\demo\test-ai-model.php`

```php
<?php
require_once('wp-load.php');

if ( !current_user_can('manage_options') ) {
    die('Not authorized');
}

// Get settings
$settings = get_option( 'edubot_ai_validator_settings' );

echo '<h2>Current AI Model Configuration</h2>';
echo '<table style="border:1px solid #ddd; padding:10px;">';
echo '<tr><td>Provider:</td><td>' . esc_html( $settings['provider'] ?? 'not set' ) . '</td></tr>';
echo '<tr><td>Model:</td><td>' . esc_html( $settings['model'] ?? 'not set' ) . '</td></tr>';
echo '<tr><td>Enabled:</td><td>' . ( $settings['enabled'] ? '✅ Yes' : '❌ No' ) . '</td></tr>';
echo '<tr><td>Temperature:</td><td>' . esc_html( $settings['temperature'] ?? 'not set' ) . '</td></tr>';
echo '<tr><td>Max Tokens:</td><td>' . esc_html( $settings['max_tokens'] ?? 'not set' ) . '</td></tr>';
echo '</table>';

// Test validation
echo '<h2>Test Validation</h2>';

$test_input = '9876543210';
$response = wp_remote_post(
    'http://localhost/demo/wp-json/edubot/v1/validate/phone',
    array(
        'headers' => array( 'Content-Type' => 'application/json' ),
        'body'    => json_encode( array( 'input' => $test_input ) ),
    )
);

$result = json_decode( wp_remote_retrieve_body( $response ), true );

echo '<pre>';
echo 'Input: ' . $test_input . "\n";
echo 'Response: ' . print_r( $result, true );
echo '</pre>';
?>
```

Visit: `http://localhost/demo/test-ai-model.php`

---

## Step-by-Step: Change AI Model

### Step 1: Create Configuration Script

Copy this to `D:\xampp\htdocs\demo\configure-model.php`:

```php
<?php
require_once('wp-load.php');

if ( !current_user_can('manage_options') ) {
    die('Admin only');
}

// Option 1: Claude 3.5 Sonnet (Recommended)
update_option( 'edubot_ai_validator_settings', array(
    'enabled'     => true,
    'provider'    => 'claude',
    'model'       => 'claude-3-5-sonnet',
    'api_key'     => 'sk-ant-YOUR-KEY-HERE',
    'temperature' => 0.3,
    'max_tokens'  => 500,
    'timeout'     => 10,
) );

echo '<h2>✅ Model changed to: claude-3-5-sonnet</h2>';
echo '<p><a href="/demo/wp-admin/">Go to Admin</a></p>';
?>
```

### Step 2: Visit Configuration Script

Visit: `http://localhost/demo/configure-model.php`

### Step 3: Verify Configuration

Visit: `http://localhost/demo/test-ai-model.php`

---

## Quick Reference

### Change to Claude (Recommended)
```php
update_option( 'edubot_ai_validator_settings', array(
    'provider' => 'claude',
    'model'    => 'claude-3-5-sonnet',
) );
```

### Change to Claude Opus (Most Powerful)
```php
update_option( 'edubot_ai_validator_settings', array(
    'provider' => 'claude',
    'model'    => 'claude-3-opus',
) );
```

### Change to OpenAI GPT-4
```php
update_option( 'edubot_ai_validator_settings', array(
    'provider' => 'openai',
    'model'    => 'gpt-4',
) );
```

### Change to GPT-3.5 (Fastest & Cheapest)
```php
update_option( 'edubot_ai_validator_settings', array(
    'provider' => 'openai',
    'model'    => 'gpt-3.5-turbo',
) );
```

---

## Recommended Model Selection

### For Phone/Grade Validation
**Recommendation**: `claude-3-5-sonnet` (or `gpt-3.5-turbo`)

- Fast response time (1-2 seconds)
- Accurate for simple validation
- Low cost
- ✅ Best overall balance

### For Complex Validation
**Recommendation**: `claude-3-opus` (or `gpt-4`)

- More accurate
- Handles edge cases
- Slower (5-10 seconds)
- Higher cost

### For Maximum Speed
**Recommendation**: `claude-3-haiku` (or `gpt-3.5-turbo`)

- Fastest response time
- Lower cost
- Slightly less accurate
- Best for high-volume

---

## File Structure

```
Where to Configure Model:

Method 1: WordPress Settings (RECOMMENDED)
├─ Location: wp_options table
├─ Key: edubot_ai_validator_settings
├─ Option: settings['model']
└─ Use: http://localhost/demo/configure-model.php

Method 2: Code Configuration
├─ File: includes/class-rest-ai-validator.php
├─ Line 267: Claude default model
├─ Line 323: OpenAI default model
└─ Change: Default fallback value (??)

Configuration Scripts (Create these):
├─ configure-model.php (Set model via script)
├─ test-ai-model.php (View current config)
└─ admin-settings.php (WordPress admin UI)
```

---

## Summary

**Easiest Way**: Create `configure-model.php` → Visit it → Done ✅

**Best Way**: Create admin settings page (class-ai-model-settings.php)

**Quick Edit**: Edit `class-rest-ai-validator.php` lines 267 & 323

**Recommended Model**: `claude-3-5-sonnet` (best balance)

---

**Status**: ✅ Complete Guide Ready
