<?php
/**
 * EduBot AI Settings Page
 * 
 * Provides WordPress admin interface for AI Validator configuration
 * 
 * @package EduBot_Pro
 * @subpackage Admin
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EduBot_AI_Settings_Page {
    
    const SETTINGS_KEY = 'edubot_ai_validator_settings';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_options_page(
            'EduBot AI Configuration',        // Page title
            'EduBot AI Config',               // Menu title
            'manage_options',                 // Capability
            'edubot-ai-config',               // Menu slug
            array( $this, 'render_settings_page' ) // Callback
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'edubot_ai_settings_group',
            self::SETTINGS_KEY,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'show_in_rest'      => true,
            )
        );
    }
    
    /**
     * Handle form submission
     */
    public function handle_form_submission() {
        if ( ! isset( $_POST['edubot_ai_config_nonce'] ) ) {
            return;
        }
        
        if ( ! wp_verify_nonce( $_POST['edubot_ai_config_nonce'], 'edubot_ai_config_action' ) ) {
            return;
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
        
        // Get form data
        $settings = array(
            'enabled'     => isset( $_POST['edubot_enabled'] ),
            'provider'    => sanitize_text_field( $_POST['edubot_provider'] ?? 'claude' ),
            'api_key'     => sanitize_text_field( $_POST['edubot_api_key'] ?? '' ),
            'model'       => sanitize_text_field( $_POST['edubot_model'] ?? 'claude-3-5-sonnet' ),
            'temperature' => floatval( $_POST['edubot_temperature'] ?? 0.3 ),
            'max_tokens'  => intval( $_POST['edubot_max_tokens'] ?? 500 ),
            'timeout'     => intval( $_POST['edubot_timeout'] ?? 10 ),
        );
        
        // Save settings
        update_option( self::SETTINGS_KEY, $settings );
        
        // Redirect with success message
        wp_safe_remote_redirect(
            add_query_arg(
                'settings-updated',
                'true',
                admin_url( 'options-general.php?page=edubot-ai-config' )
            )
        );
        exit;
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings( $settings ) {
        if ( ! is_array( $settings ) ) {
            return array();
        }
        
        return array(
            'enabled'     => isset( $settings['enabled'] ),
            'provider'    => sanitize_text_field( $settings['provider'] ?? 'claude' ),
            'api_key'     => sanitize_text_field( $settings['api_key'] ?? '' ),
            'model'       => sanitize_text_field( $settings['model'] ?? 'claude-3-5-sonnet' ),
            'temperature' => floatval( $settings['temperature'] ?? 0.3 ),
            'max_tokens'  => intval( $settings['max_tokens'] ?? 500 ),
            'timeout'     => intval( $settings['timeout'] ?? 10 ),
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Get current settings
        $settings = get_option( self::SETTINGS_KEY, array() );
        
        // Define available models
        $providers = array(
            'claude' => array(
                'label'  => 'Claude (Anthropic)',
                'api_key_placeholder' => 'sk-ant-...',
                'models' => array(
                    'claude-3-5-sonnet'  => 'Claude 3.5 Sonnet (Recommended - Fast & Powerful)',
                    'claude-3-opus'      => 'Claude 3 Opus (Most Powerful)',
                    'claude-3-sonnet'    => 'Claude 3 Sonnet (Balanced)',
                    'claude-3-haiku'     => 'Claude 3 Haiku (Fastest)',
                ),
            ),
            'openai' => array(
                'label'  => 'OpenAI',
                'api_key_placeholder' => 'sk-...',
                'models' => array(
                    'gpt-4'              => 'GPT-4 (Most Powerful)',
                    'gpt-4-turbo'        => 'GPT-4 Turbo (Balanced)',
                    'gpt-3.5-turbo'      => 'GPT-3.5 Turbo (Fastest & Cheapest)',
                ),
            ),
        );
        
        $current_provider = $settings['provider'] ?? 'claude';
        $current_model = $settings['model'] ?? 'claude-3-5-sonnet';
        
        ?>
        <div class="wrap">
            <h1>EduBot AI Validator Configuration</h1>
            
            <?php if ( isset( $_GET['settings-updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✅ Settings saved successfully!</strong></p>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <?php wp_nonce_field( 'edubot_ai_config_action', 'edubot_ai_config_nonce' ); ?>
                
                <table class="form-table">
                    <!-- ENABLE/DISABLE -->
                    <tr>
                        <th scope="row">
                            <label for="edubot_enabled">Enable AI Validation</label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                id="edubot_enabled" 
                                name="edubot_enabled" 
                                value="1"
                                <?php checked( !empty( $settings['enabled'] ) ); ?>>
                            <p class="description">Enable AI-powered phone and grade validation</p>
                        </td>
                    </tr>
                    
                    <!-- PROVIDER SELECTION -->
                    <tr>
                        <th scope="row">
                            <label for="edubot_provider">AI Provider</label>
                        </th>
                        <td>
                            <select id="edubot_provider" name="edubot_provider" onchange="updateModels()">
                                <?php foreach ( $providers as $provider_id => $provider ) : ?>
                                    <option value="<?php echo esc_attr( $provider_id ); ?>"
                                        <?php selected( $current_provider, $provider_id ); ?>>
                                        <?php echo esc_html( $provider['label'] ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <strong>Claude</strong>: Recommended - fastest and most accurate<br>
                                <strong>OpenAI</strong>: Alternative - GPT models
                            </p>
                        </td>
                    </tr>
                    
                    <!-- MODEL SELECTION -->
                    <tr>
                        <th scope="row">
                            <label for="edubot_model">AI Model</label>
                        </th>
                        <td>
                            <select id="edubot_model" name="edubot_model">
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
                                value="<?php echo esc_attr( $settings['api_key'] ?? '' ); ?>"
                                placeholder="<?php echo esc_attr( $providers[ $current_provider ]['api_key_placeholder'] ?? 'sk-...' ); ?>"
                                style="width: 100%; max-width: 400px;">
                            <p class="description">
                                Get your API key from:
                                <?php if ( 'claude' === $current_provider ) : ?>
                                    <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a>
                                <?php else : ?>
                                    <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>
                                <?php endif; ?>
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
                                value="<?php echo esc_attr( $settings['temperature'] ?? 0.3 ); ?>"
                                style="width: 100px;">
                            <p class="description">
                                0 = Deterministic (same output)<br>
                                1 = Creative (random output)<br>
                                <strong>Recommended: 0.3</strong> (for validation)
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
                                value="<?php echo esc_attr( $settings['max_tokens'] ?? 500 ); ?>"
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
                                value="<?php echo esc_attr( $settings['timeout'] ?? 10 ); ?>"
                                style="width: 100px;">
                            <p class="description">API request timeout. Recommended: 10</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button( 'Save AI Configuration', 'primary', 'submit', true ); ?>
            </form>
            
            <!-- INFO BOX -->
            <div style="background: #f5f5f5; border: 1px solid #ddd; padding: 15px; margin-top: 30px; border-radius: 5px;">
                <h3>📚 Configuration Guide</h3>
                <ul>
                    <li><strong>Enable AI Validation:</strong> Check to enable AI-powered validation</li>
                    <li><strong>Provider:</strong> Choose between Claude (recommended) or OpenAI</li>
                    <li><strong>Model:</strong> Choose model based on speed vs accuracy needs</li>
                    <li><strong>API Key:</strong> Get from your provider's dashboard</li>
                    <li><strong>Temperature:</strong> Lower = more deterministic, Higher = more creative</li>
                </ul>
            </div>
            
            <!-- CURRENT SETTINGS BOX -->
            <div style="background: #e8f5e9; border: 1px solid #4caf50; padding: 15px; margin-top: 20px; border-radius: 5px;">
                <h3>✅ Current Settings</h3>
                <pre style="background: white; padding: 10px; border-radius: 3px; overflow-x: auto;">
<?php
echo "Provider: " . esc_html( $current_provider ) . "\n";
echo "Model: " . esc_html( $current_model ) . "\n";
echo "Enabled: " . ( !empty( $settings['enabled'] ) ? 'Yes' : 'No' ) . "\n";
echo "Temperature: " . esc_html( $settings['temperature'] ?? 0.3 ) . "\n";
echo "Max Tokens: " . esc_html( $settings['max_tokens'] ?? 500 ) . "\n";
echo "Timeout: " . esc_html( $settings['timeout'] ?? 10 ) . " seconds\n";
?>
                </pre>
            </div>
        </div>
        
        <script>
        function updateModels() {
            const provider = document.getElementById('edubot_provider').value;
            const modelSelect = document.getElementById('edubot_model');
            const options = modelSelect.getElementsByTagName('option');
            
            // Hide/show options based on provider
            for ( let i = 0; i < options.length; i++ ) {
                const optionProvider = options[i].getAttribute('data-provider');
                
                if ( optionProvider === provider ) {
                    options[i].style.display = 'block';
                } else {
                    options[i].style.display = 'none';
                }
            }
            
            // Select first visible option for the selected provider
            const firstVisible = Array.from(options).find(
                opt => opt.getAttribute('data-provider') === provider && opt.style.display !== 'none'
            );
            if ( firstVisible ) {
                modelSelect.value = firstVisible.value;
            }
        }
        
        // Initialize on page load
        document.addEventListener( 'DOMContentLoaded', function() {
            updateModels();
        });
        </script>
        
        <style>
        .wrap table.form-table td {
            padding: 15px 10px;
        }
        
        .wrap input[type="text"],
        .wrap input[type="password"],
        .wrap input[type="number"],
        .wrap select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .wrap input[type="text"]:focus,
        .wrap input[type="password"]:focus,
        .wrap input[type="number"]:focus,
        .wrap select:focus {
            border-color: #0073aa;
            box-shadow: 0 0 2px rgba(0, 115, 170, 0.8);
        }
        </style>
        <?php
    }
}

// Instantiate the class
new EduBot_AI_Settings_Page();
