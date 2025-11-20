<?php
/**
 * AI Validator Settings Page
 * 
 * Displays settings for AI validation configuration including:
 * - Provider selection (Claude or OpenAI)
 * - API key input
 * - Model selection
 * - Temperature and token settings
 * - Connection testing
 * 
 * @package EduBot_Pro
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get AI Validator instance
global $edubot_ai_validator;

if ( ! isset( $edubot_ai_validator ) ) {
    return;
}

$settings = $edubot_ai_validator->get_settings();
$providers = EduBot_AI_Validator::get_providers();
$models = EduBot_AI_Validator::get_models();
?>

<div class="wrap">
    <h1>🤖 AI Input Validation Settings</h1>
    <p class="subtitle">Configure AI model for advanced input validation</p>

    <div class="nav-tab-wrapper">
        <a href="#general" class="nav-tab nav-tab-active">General</a>
        <a href="#advanced" class="nav-tab">Advanced</a>
        <a href="#logs" class="nav-tab">Logs</a>
    </div>

    <div id="general" class="tab-content">
        <form method="post" action="options.php" id="ai-validator-form">
            <?php settings_fields( 'edubot_ai_validator_settings' ); ?>

            <table class="form-table">
                <!-- Enable/Disable -->
                <tr>
                    <th scope="row">
                        <label for="enabled">Enable AI Validation</label>
                    </th>
                    <td>
                        <input type="checkbox" id="enabled" name="edubot_ai_validator_settings[enabled]" value="1" 
                            <?php checked( $settings['enabled'], 1 ); ?> />
                        <p class="description">
                            Enable AI model for validating user input when regular methods fail
                        </p>
                    </td>
                </tr>

                <!-- Provider Selection -->
                <tr>
                    <th scope="row">
                        <label for="provider">AI Provider</label>
                    </th>
                    <td>
                        <select id="provider" name="edubot_ai_validator_settings[provider]" onchange="updateModels()">
                            <?php foreach ( $providers as $key => $label ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>" 
                                    <?php selected( $settings['provider'], $key ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            Choose between Anthropic Claude (recommended) or OpenAI
                        </p>
                    </td>
                </tr>

                <!-- API Key -->
                <tr>
                    <th scope="row">
                        <label for="api_key">API Key</label>
                    </th>
                    <td>
                        <input type="password" id="api_key" name="edubot_ai_validator_settings[api_key]" 
                            value="<?php echo esc_attr( $settings['api_key'] ); ?>" 
                            placeholder="sk-... or sk-ant-..." class="regular-text" />
                        <p class="description">
                            Get your API key from:
                            <a href="https://console.anthropic.com" target="_blank">Anthropic Console</a> or 
                            <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>
                        </p>
                    </td>
                </tr>

                <!-- Model Selection -->
                <tr>
                    <th scope="row">
                        <label for="model">Model</label>
                    </th>
                    <td>
                        <select id="model" name="edubot_ai_validator_settings[model]">
                            <?php foreach ( $models as $key => $label ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>" 
                                    <?php selected( $settings['model'], $key ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            Claude 3.5 Sonnet is recommended for best balance of speed and accuracy
                        </p>
                    </td>
                </tr>

                <!-- Use as Fallback -->
                <tr>
                    <th scope="row">
                        <label for="use_as_fallback">Use AI as Fallback</label>
                    </th>
                    <td>
                        <input type="checkbox" id="use_as_fallback" name="edubot_ai_validator_settings[use_as_fallback]" value="1" 
                            <?php checked( $settings['use_as_fallback'], 1 ); ?> />
                        <p class="description">
                            When enabled, AI validation is used only when regex/pattern matching fails
                        </p>
                    </td>
                </tr>

                <!-- Cache Results -->
                <tr>
                    <th scope="row">
                        <label for="cache_results">Cache Results</label>
                    </th>
                    <td>
                        <input type="checkbox" id="cache_results" name="edubot_ai_validator_settings[cache_results]" value="1" 
                            <?php checked( $settings['cache_results'], 1 ); ?> />
                        <p class="description">
                            Cache validation results to reduce API calls (TTL: <?php echo intval( $settings['cache_ttl'] / 3600 ); ?> hours)
                        </p>
                    </td>
                </tr>
            </table>

            <h3>Test Connection</h3>
            <p>
                <button type="button" class="button button-primary" onclick="testAIConnection()">
                    🧪 Test Connection
                </button>
            </p>
            <div id="test-result" style="margin-top: 10px; display: none;"></div>

            <h3>Configuration Help</h3>
            <table class="widefat striped">
                <tr>
                    <td style="width: 150px;"><strong>Provider</strong></td>
                    <td>Choose Claude for better handling of Indian context, or GPT-4 for alternatives</td>
                </tr>
                <tr>
                    <td><strong>API Key</strong></td>
                    <td>Keep this secret - never share or commit to version control</td>
                </tr>
                <tr>
                    <td><strong>Temperature</strong></td>
                    <td>Lower (0.1-0.3) for consistent validation, higher (0.7-1) for creative responses</td>
                </tr>
                <tr>
                    <td><strong>Cache</strong></td>
                    <td>Enabled by default to save API calls and cost</td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    <div id="advanced" class="tab-content" style="display: none;">
        <form method="post" action="options.php">
            <?php settings_fields( 'edubot_ai_validator_settings' ); ?>

            <table class="form-table">
                <!-- Temperature -->
                <tr>
                    <th scope="row">
                        <label for="temperature">Temperature</label>
                    </th>
                    <td>
                        <input type="number" id="temperature" name="edubot_ai_validator_settings[temperature]" 
                            value="<?php echo esc_attr( $settings['temperature'] ); ?>" 
                            min="0" max="1" step="0.1" />
                        <p class="description">
                            0 = Deterministic (recommended for validation), 1 = Creative
                        </p>
                    </td>
                </tr>

                <!-- Max Tokens -->
                <tr>
                    <th scope="row">
                        <label for="max_tokens">Max Tokens</label>
                    </th>
                    <td>
                        <input type="number" id="max_tokens" name="edubot_ai_validator_settings[max_tokens]" 
                            value="<?php echo esc_attr( $settings['max_tokens'] ); ?>" 
                            min="100" max="2000" step="100" />
                        <p class="description">
                            Maximum length of AI response. 500 is usually sufficient for validation
                        </p>
                    </td>
                </tr>

                <!-- Timeout -->
                <tr>
                    <th scope="row">
                        <label for="timeout">Timeout (seconds)</label>
                    </th>
                    <td>
                        <input type="number" id="timeout" name="edubot_ai_validator_settings[timeout]" 
                            value="<?php echo esc_attr( $settings['timeout'] ); ?>" 
                            min="5" max="30" step="1" />
                        <p class="description">
                            How long to wait for API response before timing out
                        </p>
                    </td>
                </tr>

                <!-- Cache TTL -->
                <tr>
                    <th scope="row">
                        <label for="cache_ttl">Cache Duration (hours)</label>
                    </th>
                    <td>
                        <input type="number" id="cache_ttl" name="edubot_ai_validator_settings[cache_ttl]" 
                            value="<?php echo esc_attr( intval( $settings['cache_ttl'] / 3600 ) ); ?>" 
                            min="1" max="24" step="1" 
                            onchange="convertCacheTTL(this)" />
                        <p class="description">
                            How long to cache validation results
                        </p>
                    </td>
                </tr>

                <!-- Rate Limit -->
                <tr>
                    <th scope="row">
                        <label for="rate_limit">Rate Limit (per hour)</label>
                    </th>
                    <td>
                        <input type="number" id="rate_limit" name="edubot_ai_validator_settings[rate_limit]" 
                            value="<?php echo esc_attr( $settings['rate_limit'] ); ?>" 
                            min="10" max="1000" step="10" />
                        <p class="description">
                            Maximum API calls per hour to control costs
                        </p>
                    </td>
                </tr>

                <!-- Log Calls -->
                <tr>
                    <th scope="row">
                        <label for="log_ai_calls">Log AI Calls</label>
                    </th>
                    <td>
                        <input type="checkbox" id="log_ai_calls" name="edubot_ai_validator_settings[log_ai_calls]" value="1" 
                            <?php checked( $settings['log_ai_calls'], 1 ); ?> />
                        <p class="description">
                            Log all AI validation calls for debugging and analytics
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    <div id="logs" class="tab-content" style="display: none;">
        <h3>AI Validation Logs</h3>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Input</th>
                    <th>Result</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                global $wpdb;
                $table = $wpdb->prefix . 'edubot_ai_validator_log';
                $logs = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 20" );
                
                if ( $logs ) :
                    foreach ( $logs as $log ) :
                        $result = json_decode( $log->result, true );
                        ?>
                        <tr>
                            <td><?php echo esc_html( $log->type ); ?></td>
                            <td><code><?php echo esc_html( substr( $log->input, 0, 50 ) ); ?></code></td>
                            <td>
                                <?php
                                if ( isset( $result['valid'] ) ) {
                                    echo $result['valid'] ? '✅ Valid' : '❌ Invalid';
                                } else {
                                    echo '⚠️ Error';
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html( $log->created_at ); ?></td>
                        </tr>
                    <?php endforeach;
                else :
                    ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px;">No logs yet</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .nav-tab-wrapper {
        margin-bottom: 20px;
    }
    .nav-tab {
        padding: 10px 15px;
        margin-right: 5px;
        background: #f0f0f0;
        border: 1px solid #ccc;
        cursor: pointer;
        text-decoration: none;
        color: #333;
    }
    .nav-tab.nav-tab-active {
        background: #fff;
        border-bottom: 3px solid #0073aa;
    }
    .tab-content {
        background: #fff;
        padding: 20px;
        border: 1px solid #ccc;
    }
    .subtitle {
        color: #666;
        font-size: 14px;
    }
</style>

<script>
function updateModels() {
    // Model options will be updated based on provider selection
    // This is a placeholder for future enhancement
}

function testAIConnection() {
    const button = event.target;
    const resultDiv = document.getElementById('test-result');
    
    button.disabled = true;
    button.textContent = '⏳ Testing...';
    
    const settings = {
        enabled: document.getElementById('enabled').checked,
        provider: document.getElementById('provider').value,
        model: document.getElementById('model').value,
        api_key: document.getElementById('api_key').value,
    };
    
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'edubot_ai_test_connection',
            nonce: '<?php echo wp_create_nonce( 'edubot_ai_nonce' ); ?>',
            settings: JSON.stringify(settings),
        }),
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.style.display = 'block';
        if (data.success) {
            resultDiv.innerHTML = `<div class="notice notice-success"><p>✅ Connection successful! Model: ${data.model} (${data.provider})</p></div>`;
        } else {
            resultDiv.innerHTML = `<div class="notice notice-error"><p>❌ Connection failed: ${data.message}</p></div>`;
        }
    })
    .catch(error => {
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = `<div class="notice notice-error"><p>❌ Error: ${error.message}</p></div>`;
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = '🧪 Test Connection';
    });
}

function convertCacheTTL(input) {
    const hours = parseInt(input.value);
    input.value = hours; // Store in hours, convert to seconds on save
}

// Tab switching
document.querySelectorAll('.nav-tab').forEach(tab => {
    tab.addEventListener('click', (e) => {
        e.preventDefault();
        
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
        
        // Remove active class
        document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('nav-tab-active'));
        
        // Show selected tab
        const tabId = tab.getAttribute('href').substring(1);
        document.getElementById(tabId).style.display = 'block';
        tab.classList.add('nav-tab-active');
    });
});
</script>
