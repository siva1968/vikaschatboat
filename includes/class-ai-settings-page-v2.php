<?php
/**
 * EduBot AI Settings Page - Simplified Version
 * 
 * Ultra-simple admin settings page for AI configuration
 * No complex object instantiation - just direct WordPress hooks
 * 
 * @package EduBot_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add admin menu
add_action( 'admin_menu', function() {
    add_options_page(
        'EduBot AI Configuration',
        'EduBot AI Config',
        'manage_options',
        'edubot-ai-config',
        'edubot_ai_render_settings_page'
    );
});

// Handle form submission
add_action( 'admin_init', function() {
    if ( ! isset( $_POST['edubot_ai_settings_nonce'] ) ) {
        return;
    }
    
    if ( ! wp_verify_nonce( $_POST['edubot_ai_settings_nonce'], 'edubot_ai_settings' ) ) {
        return;
    }
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    
    // Get form values
    $settings = array(
        'enabled'     => isset( $_POST['edubot_enabled'] ) ? 1 : 0,
        'provider'    => sanitize_text_field( $_POST['edubot_provider'] ?? 'claude' ),
        'api_key'     => sanitize_text_field( $_POST['edubot_api_key'] ?? '' ),
        'model'       => sanitize_text_field( $_POST['edubot_model'] ?? 'claude-3-5-sonnet' ),
        'temperature' => floatval( $_POST['edubot_temperature'] ?? 0.3 ),
        'max_tokens'  => intval( $_POST['edubot_max_tokens'] ?? 500 ),
        'timeout'     => intval( $_POST['edubot_timeout'] ?? 10 ),
    );
    
    // Save to database
    update_option( 'edubot_ai_validator_settings', $settings );
    
    // Redirect with success
    wp_safe_remote_redirect(
        add_query_arg(
            'message',
            'settings_saved',
            admin_url( 'options-general.php?page=edubot-ai-config' )
        )
    );
    exit;
});

// Render settings page
function edubot_ai_render_settings_page() {
    // Get current settings
    $settings = get_option( 'edubot_ai_validator_settings', array() );
    
    $enabled = isset( $settings['enabled'] ) ? $settings['enabled'] : 0;
    $provider = isset( $settings['provider'] ) ? $settings['provider'] : 'claude';
    $api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
    $model = isset( $settings['model'] ) ? $settings['model'] : 'claude-3-5-sonnet';
    $temperature = isset( $settings['temperature'] ) ? $settings['temperature'] : 0.3;
    $max_tokens = isset( $settings['max_tokens'] ) ? $settings['max_tokens'] : 500;
    $timeout = isset( $settings['timeout'] ) ? $settings['timeout'] : 10;
    
    $claude_models = array(
        'claude-3-5-sonnet'  => 'Claude 3.5 Sonnet (Recommended)',
        'claude-3-opus'      => 'Claude 3 Opus (Most Powerful)',
        'claude-3-sonnet'    => 'Claude 3 Sonnet (Balanced)',
        'claude-3-haiku'     => 'Claude 3 Haiku (Fastest)',
    );
    
    $openai_models = array(
        'gpt-4'              => 'GPT-4 (Most Powerful)',
        'gpt-4-turbo'        => 'GPT-4 Turbo (Balanced)',
        'gpt-3.5-turbo'      => 'GPT-3.5 Turbo (Fastest)',
    );
    
    ?>
    <div class="wrap">
        <h1>EduBot AI Validator Configuration</h1>
        
        <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'settings_saved' ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>✅ Settings saved successfully!</strong></p>
            </div>
        <?php endif; ?>
        
        <form method="POST" style="max-width: 800px;">
            <?php wp_nonce_field( 'edubot_ai_settings', 'edubot_ai_settings_nonce' ); ?>
            
            <table class="form-table">
                <!-- ENABLE CHECKBOX -->
                <tr>
                    <th scope="row">
                        <label for="edubot_enabled">Enable AI Validation</label>
                    </th>
                    <td>
                        <input type="checkbox" 
                            id="edubot_enabled" 
                            name="edubot_enabled" 
                            value="1"
                            <?php checked( $enabled ); ?>>
                        <p class="description">Enable AI-powered phone and grade validation</p>
                    </td>
                </tr>
                
                <!-- PROVIDER DROPDOWN -->
                <tr>
                    <th scope="row">
                        <label for="edubot_provider">AI Provider</label>
                    </th>
                    <td>
                        <select id="edubot_provider" name="edubot_provider" onchange="updateModels()">
                            <option value="claude" <?php selected( $provider, 'claude' ); ?>>
                                Claude (Anthropic) - Recommended
                            </option>
                            <option value="openai" <?php selected( $provider, 'openai' ); ?>>
                                OpenAI
                            </option>
                        </select>
                        <p class="description">
                            <strong>Claude</strong>: Fast, accurate, cost-effective (recommended)<br>
                            <strong>OpenAI</strong>: GPT models, very powerful
                        </p>
                    </td>
                </tr>
                
                <!-- MODEL DROPDOWN -->
                <tr>
                    <th scope="row">
                        <label for="edubot_model">AI Model</label>
                    </th>
                    <td>
                        <select id="edubot_model" name="edubot_model">
                            <!-- Claude Models -->
                            <?php foreach ( $claude_models as $model_id => $model_name ) : ?>
                                <option value="<?php echo esc_attr( $model_id ); ?>"
                                    class="claude-model"
                                    <?php selected( $model, $model_id ); ?>>
                                    <?php echo esc_html( $model_name ); ?>
                                </option>
                            <?php endforeach; ?>
                            
                            <!-- OpenAI Models -->
                            <?php foreach ( $openai_models as $model_id => $model_name ) : ?>
                                <option value="<?php echo esc_attr( $model_id ); ?>"
                                    class="openai-model"
                                    <?php selected( $model, $model_id ); ?>>
                                    <?php echo esc_html( $model_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Select the AI model to use for validation</p>
                    </td>
                </tr>
                
                <!-- API KEY -->
                <tr>
                    <th scope="row">
                        <label for="edubot_api_key">API Key</label>
                    </th>
                    <td>
                        <input type="password" 
                            id="edubot_api_key" 
                            name="edubot_api_key" 
                            value="<?php echo esc_attr( $api_key ); ?>"
                            placeholder="sk-ant-... or sk-..."
                            style="width: 100%; max-width: 400px;">
                        <p class="description">
                            Get your API key from:
                            <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a> (Claude)
                            or <a href="https://platform.openai.com/" target="_blank">platform.openai.com</a> (OpenAI)
                        </p>
                    </td>
                </tr>
                
                <!-- TEMPERATURE -->
                <tr>
                    <th scope="row">
                        <label for="edubot_temperature">Temperature</label>
                    </th>
                    <td>
                        <input type="number" 
                            id="edubot_temperature" 
                            name="edubot_temperature" 
                            step="0.1" 
                            min="0" 
                            max="1"
                            value="<?php echo esc_attr( $temperature ); ?>"
                            style="width: 100px;">
                        <p class="description">
                            0 = Deterministic (same output)<br>
                            1 = Creative (random output)<br>
                            Recommended: 0.3
                        </p>
                    </td>
                </tr>
                
                <!-- MAX TOKENS -->
                <tr>
                    <th scope="row">
                        <label for="edubot_max_tokens">Max Tokens</label>
                    </th>
                    <td>
                        <input type="number" 
                            id="edubot_max_tokens" 
                            name="edubot_max_tokens" 
                            min="100" 
                            max="4000"
                            value="<?php echo esc_attr( $max_tokens ); ?>"
                            style="width: 100px;">
                        <p class="description">Maximum response length. Recommended: 500</p>
                    </td>
                </tr>
                
                <!-- TIMEOUT -->
                <tr>
                    <th scope="row">
                        <label for="edubot_timeout">Timeout (seconds)</label>
                    </th>
                    <td>
                        <input type="number" 
                            id="edubot_timeout" 
                            name="edubot_timeout" 
                            min="1" 
                            max="60"
                            value="<?php echo esc_attr( $timeout ); ?>"
                            style="width: 100px;">
                        <p class="description">API request timeout. Recommended: 10</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button( 'Save AI Configuration', 'primary', 'submit', true ); ?>
        </form>
        
        <!-- INFO BOXES -->
        <div style="margin-top: 30px;">
            <h2>Current Configuration</h2>
            <div style="background: #f5f5f5; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                <p><strong>Provider:</strong> <?php echo esc_html( $provider ); ?></p>
                <p><strong>Model:</strong> <?php echo esc_html( $model ); ?></p>
                <p><strong>Enabled:</strong> <?php echo $enabled ? '✅ Yes' : '❌ No'; ?></p>
                <p><strong>Temperature:</strong> <?php echo esc_html( $temperature ); ?></p>
                <p><strong>Max Tokens:</strong> <?php echo esc_html( $max_tokens ); ?></p>
                <p><strong>Timeout:</strong> <?php echo esc_html( $timeout ); ?> seconds</p>
            </div>
        </div>
    </div>
    
    <script>
    function updateModels() {
        var provider = document.getElementById('edubot_provider').value;
        var claudeModels = document.querySelectorAll('.claude-model');
        var openaiModels = document.querySelectorAll('.openai-model');
        
        if ( provider === 'claude' ) {
            claudeModels.forEach(function(el) { el.style.display = 'block'; });
            openaiModels.forEach(function(el) { el.style.display = 'none'; });
        } else {
            claudeModels.forEach(function(el) { el.style.display = 'none'; });
            openaiModels.forEach(function(el) { el.style.display = 'block'; });
        }
    }
    
    // Run on page load
    updateModels();
    </script>
    <?php
}
