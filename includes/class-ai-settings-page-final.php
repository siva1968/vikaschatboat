<?php
/**
 * EduBot AI Settings Page - Final Version
 * 
 * Ultra-clean admin settings page with NO register_setting() call
 * Just pure hooks and direct database access
 * 
 * @package EduBot_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================================================
// ADD ADMIN MENU
// ============================================================================

add_action( 'admin_menu', function() {
    add_options_page(
        'EduBot AI Configuration',
        'EduBot AI Config',
        'manage_options',
        'edubot-ai-config',
        'edubot_ai_config_render_page'
    );
} );

// ============================================================================
// HANDLE FORM SUBMISSION
// ============================================================================

add_action( 'admin_init', function() {
    // Only process our form
    if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'edubot_save_ai_settings' ) {
        return;
    }
    
    // Verify nonce
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'edubot_ai_config_nonce' ) ) {
        wp_die( 'Security check failed' );
    }
    
    // Check capability
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    
    // Prepare settings array
    $settings = array(
        'enabled'     => isset( $_POST['enabled'] ) ? 1 : 0,
        'provider'    => isset( $_POST['provider'] ) ? sanitize_text_field( $_POST['provider'] ) : 'claude',
        'api_key'     => isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '',
        'model'       => isset( $_POST['model'] ) ? sanitize_text_field( $_POST['model'] ) : 'claude-3-5-sonnet',
        'temperature' => isset( $_POST['temperature'] ) ? floatval( $_POST['temperature'] ) : 0.3,
        'max_tokens'  => isset( $_POST['max_tokens'] ) ? intval( $_POST['max_tokens'] ) : 500,
        'timeout'     => isset( $_POST['timeout'] ) ? intval( $_POST['timeout'] ) : 10,
    );
    
    // Save to database
    update_option( 'edubot_ai_validator_settings', $settings );
    
    // Redirect with success message
    wp_redirect(
        add_query_arg(
            'message',
            'settings_saved',
            admin_url( 'options-general.php?page=edubot-ai-config' )
        )
    );
    exit;
} );

// ============================================================================
// RENDER SETTINGS PAGE
// ============================================================================

function edubot_ai_config_render_page() {
    // Get current settings
    $settings = get_option( 'edubot_ai_validator_settings', array() );
    
    $enabled = isset( $settings['enabled'] ) ? intval( $settings['enabled'] ) : 0;
    $provider = isset( $settings['provider'] ) ? $settings['provider'] : 'claude';
    $api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
    $model = isset( $settings['model'] ) ? $settings['model'] : 'claude-3-5-sonnet';
    $temperature = isset( $settings['temperature'] ) ? floatval( $settings['temperature'] ) : 0.3;
    $max_tokens = isset( $settings['max_tokens'] ) ? intval( $settings['max_tokens'] ) : 500;
    $timeout = isset( $settings['timeout'] ) ? intval( $settings['timeout'] ) : 10;
    
    // Model lists
    $models = array(
        'claude' => array(
            'claude-3-5-sonnet' => 'Claude 3.5 Sonnet (Recommended)',
            'claude-3-opus' => 'Claude 3 Opus (Most Powerful)',
            'claude-3-sonnet' => 'Claude 3 Sonnet (Balanced)',
            'claude-3-haiku' => 'Claude 3 Haiku (Fastest)',
        ),
        'openai' => array(
            'gpt-4' => 'GPT-4 (Most Powerful)',
            'gpt-4-turbo' => 'GPT-4 Turbo (Balanced)',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Fastest)',
        ),
    );
    
    ?>
    <div class="wrap">
        <h1>EduBot AI Configuration</h1>
        
        <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'settings_saved' ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>✅ Settings saved successfully!</strong></p>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="edubot_save_ai_settings">
            <?php wp_nonce_field( 'edubot_ai_config_nonce' ); ?>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <!-- Enable AI -->
                    <tr>
                        <th scope="row">
                            <label for="enabled">Enable AI Validation</label>
                        </th>
                        <td>
                            <input type="checkbox" id="enabled" name="enabled" value="1" <?php checked( $enabled, 1 ); ?>>
                            <p class="description">Enable AI-powered validation for phone numbers and grades</p>
                        </td>
                    </tr>
                    
                    <!-- Provider -->
                    <tr>
                        <th scope="row">
                            <label for="provider">AI Provider</label>
                        </th>
                        <td>
                            <select id="provider" name="provider" onchange="filterModels()">
                                <option value="claude" <?php selected( $provider, 'claude' ); ?>>Claude (Recommended)</option>
                                <option value="openai" <?php selected( $provider, 'openai' ); ?>>OpenAI</option>
                            </select>
                            <p class="description">Claude: Fast & accurate. OpenAI: GPT models.</p>
                        </td>
                    </tr>
                    
                    <!-- Model -->
                    <tr>
                        <th scope="row">
                            <label for="model">AI Model</label>
                        </th>
                        <td>
                            <select id="model" name="model">
                                <?php foreach ( $models as $prov => $model_list ) : ?>
                                    <?php foreach ( $model_list as $model_id => $model_name ) : ?>
                                        <option value="<?php echo esc_attr( $model_id ); ?>" 
                                            class="model-<?php echo esc_attr( $prov ); ?>"
                                            <?php selected( $model, $model_id ); ?>>
                                            <?php echo esc_html( $model_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">AI model to use for validation</p>
                        </td>
                    </tr>
                    
                    <!-- API Key -->
                    <tr>
                        <th scope="row">
                            <label for="api_key">API Key</label>
                        </th>
                        <td>
                            <input type="password" id="api_key" name="api_key" value="<?php echo esc_attr( $api_key ); ?>" placeholder="sk-..." style="width: 100%; max-width: 400px;">
                            <p class="description">
                                Get from: 
                                <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a> (Claude)
                                or <a href="https://platform.openai.com/" target="_blank">platform.openai.com</a> (OpenAI)
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Temperature -->
                    <tr>
                        <th scope="row">
                            <label for="temperature">Temperature</label>
                        </th>
                        <td>
                            <input type="number" id="temperature" name="temperature" min="0" max="1" step="0.1" value="<?php echo esc_attr( $temperature ); ?>" style="width: 80px;">
                            <p class="description">0=deterministic, 1=random. Recommended: 0.3</p>
                        </td>
                    </tr>
                    
                    <!-- Max Tokens -->
                    <tr>
                        <th scope="row">
                            <label for="max_tokens">Max Tokens</label>
                        </th>
                        <td>
                            <input type="number" id="max_tokens" name="max_tokens" min="100" max="4000" value="<?php echo esc_attr( $max_tokens ); ?>" style="width: 80px;">
                            <p class="description">Maximum response length. Recommended: 500</p>
                        </td>
                    </tr>
                    
                    <!-- Timeout -->
                    <tr>
                        <th scope="row">
                            <label for="timeout">Timeout (seconds)</label>
                        </th>
                        <td>
                            <input type="number" id="timeout" name="timeout" min="1" max="60" value="<?php echo esc_attr( $timeout ); ?>" style="width: 80px;">
                            <p class="description">API request timeout. Recommended: 10</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <?php submit_button( 'Save AI Configuration' ); ?>
        </form>
        
        <!-- Current Settings -->
        <h2>Current Settings</h2>
        <div style="background: #f5f5f5; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
            <p><strong>Status:</strong> <?php echo $enabled ? '✅ Enabled' : '❌ Disabled'; ?></p>
            <p><strong>Provider:</strong> <?php echo esc_html( $provider ); ?></p>
            <p><strong>Model:</strong> <?php echo esc_html( $model ); ?></p>
            <p><strong>Temperature:</strong> <?php echo esc_html( $temperature ); ?></p>
            <p><strong>Max Tokens:</strong> <?php echo esc_html( $max_tokens ); ?></p>
            <p><strong>Timeout:</strong> <?php echo esc_html( $timeout ); ?> seconds</p>
        </div>
    </div>
    
    <script>
    function filterModels() {
        var provider = document.getElementById('provider').value;
        var options = document.querySelectorAll('select[name="model"] option');
        
        options.forEach(function(opt) {
            var isMatch = opt.classList.contains('model-' + provider);
            opt.style.display = isMatch ? 'block' : 'none';
        });
    }
    
    filterModels();
    </script>
    <?php
}
