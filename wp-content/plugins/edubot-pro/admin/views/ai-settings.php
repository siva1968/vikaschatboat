<?php
/**
 * AI Settings View
 */
if (!defined('ABSPATH')) {
    exit;
}

// Initialize security manager for decryption
$security_manager = new EduBot_Security_Manager();

// Decrypt API keys for display
$openai_key = '';
$encrypted_openai_key = get_option('edubot_openai_api_key', '');
if (!empty($encrypted_openai_key)) {
    $openai_key = $security_manager->decrypt_api_key($encrypted_openai_key);
}

// Get AI settings
$ai_enabled = get_option('edubot_ai_enabled', false);
$ai_model = get_option('edubot_openai_model', 'gpt-3.5-turbo');
$ai_temperature = get_option('edubot_ai_temperature', '0.7');
$ai_max_tokens = get_option('edubot_ai_max_tokens', '500');
$ai_prompt_template = get_option('edubot_ai_prompt_template', '');
$ai_fallback_message = get_option('edubot_ai_fallback_message', '');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('edubot_save_ai_settings', 'edubot_ai_nonce'); ?>
        
        <div class="edubot-settings">
            <div class="edubot-card">
                <h2>🤖 AI Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable AI Responses</th>
                        <td>
                            <label for="ai_enabled">
                                <input type="checkbox" id="ai_enabled" name="ai_enabled" value="1" <?php checked($ai_enabled, true); ?> />
                                Enable AI-powered automatic responses
                            </label>
                            <p class="description">When enabled, the bot will use AI to generate responses for enquiries</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">OpenAI API Key</th>
                        <td>
                            <input type="password" name="openai_key" value="<?php echo esc_attr($openai_key); ?>" class="regular-text" />
                            <p class="description">Your OpenAI API key for AI-powered responses. <a href="https://platform.openai.com/api-keys" target="_blank">Get your API key</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">AI Model</th>
                        <td>
                            <select name="ai_model" class="regular-text">
                                <option value="gpt-3.5-turbo" <?php selected($ai_model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo (Faster, Cost-effective)</option>
                                <option value="gpt-4" <?php selected($ai_model, 'gpt-4'); ?>>GPT-4 (More Accurate, Higher Cost)</option>
                                <option value="gpt-4-turbo" <?php selected($ai_model, 'gpt-4-turbo'); ?>>GPT-4 Turbo (Best Performance)</option>
                            </select>
                            <p class="description">Choose the AI model for generating responses. GPT-4 is more accurate but costs more.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="edubot-card">
                <h2>🎛️ AI Response Parameters</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Temperature</th>
                        <td>
                            <input type="number" name="ai_temperature" value="<?php echo esc_attr($ai_temperature); ?>" 
                                   class="small-text" min="0" max="2" step="0.1" />
                            <p class="description">Controls randomness (0.0 = deterministic, 2.0 = very creative). Recommended: 0.7</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Max Tokens</th>
                        <td>
                            <input type="number" name="ai_max_tokens" value="<?php echo esc_attr($ai_max_tokens); ?>" 
                                   class="small-text" min="50" max="4000" />
                            <p class="description">Maximum length of AI response in tokens. Recommended: 500-1000</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="edubot-card">
                <h2>📝 AI Prompt Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">System Prompt Template</th>
                        <td>
                            <textarea name="ai_prompt_template" rows="8" class="large-text code" placeholder="Enter your custom AI prompt template..."><?php echo esc_textarea($ai_prompt_template); ?></textarea>
                            <p class="description">
                                Custom prompt template for AI responses. Use variables like {school_name}, {student_name}, {enquiry_type}, {message}.
                                <br><strong>Default:</strong> "You are an AI assistant for {school_name}. Help with student enquiries professionally and helpfully."
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Fallback Message</th>
                        <td>
                            <textarea name="ai_fallback_message" rows="4" class="large-text" placeholder="Message to send when AI is unavailable..."><?php echo esc_textarea($ai_fallback_message); ?></textarea>
                            <p class="description">Message to send when AI service is unavailable or returns an error</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="edubot-card">
                <h2>🧪 AI Testing & Monitoring</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Test AI Response</th>
                        <td>
                            <textarea id="test-prompt" rows="3" class="large-text" placeholder="Enter a test message to see how AI responds..."></textarea>
                            <br><br>
                            <button type="button" class="button button-secondary" id="test-ai-response">Test AI Response</button>
                            <div id="ai-test-result" style="margin-top: 15px; display: none;">
                                <strong>AI Response:</strong>
                                <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin-top: 5px;">
                                    <span id="ai-response-text"></span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Usage Monitor</th>
                        <td>
                            <div style="background: #f0f8ff; padding: 15px; border: 1px solid #0073aa;">
                                <strong>💡 Tip:</strong> Monitor your OpenAI API usage in the 
                                <a href="<?php echo admin_url('admin.php?page=edubot-api-logs'); ?>">API Logs</a> section.
                                <br>Check your OpenAI usage limits at: <a href="https://platform.openai.com/usage" target="_blank">OpenAI Usage Dashboard</a>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="submit-section">
            <div class="section-save-buttons">
                <button type="button" class="button button-primary save-section" data-section="ai">Save AI Settings</button>
                <button type="button" class="button test-api" id="test-openai">Test OpenAI Connection</button>
            </div>
        </div>
    </form>
</div>

<style>
.edubot-settings {
    display: grid;
    gap: 20px;
    margin-top: 20px;
}

.edubot-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.edubot-card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.section-save-buttons {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.section-save-buttons .button {
    margin-right: 10px;
}

.submit-section {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #0073aa;
    background: #f9f9f9;
    padding: 20px;
    border-radius: 4px;
}

#ai-test-result {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.test-api {
    background: #00a32a !important;
    border-color: #00a32a !important;
    color: white !important;
}

.test-api:hover {
    background: #008a20 !important;
    border-color: #008a20 !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Test AI Response functionality
    $('#test-ai-response').click(function() {
        var prompt = $('#test-prompt').val().trim();
        if (!prompt) {
            alert('Please enter a test message first.');
            return;
        }
        
        var button = $(this);
        button.text('Testing...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'edubot_test_ai_response',
                prompt: prompt,
                nonce: '<?php echo wp_create_nonce('edubot_test_ai'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#ai-response-text').text(response.data.response);
                    $('#ai-test-result').show();
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to test AI response. Please check your settings.');
            },
            complete: function() {
                button.text('Test AI Response').prop('disabled', false);
            }
        });
    });

    // Section-specific save functionality
    $('.save-section[data-section="ai"]').click(function() {
        var button = $(this);
        var originalText = button.text();
        button.text('Saving...').prop('disabled', true);
        
        var formData = {
            action: 'edubot_save_ai_settings',
            nonce: $('[name="edubot_ai_nonce"]').val(),
            ai_enabled: $('#ai_enabled').is(':checked') ? 1 : 0,
            openai_key: $('[name="openai_key"]').val(),
            ai_model: $('[name="ai_model"]').val(),
            ai_temperature: $('[name="ai_temperature"]').val(),
            ai_max_tokens: $('[name="ai_max_tokens"]').val(),
            ai_prompt_template: $('[name="ai_prompt_template"]').val(),
            ai_fallback_message: $('[name="ai_fallback_message"]').val()
        };
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('<div class="notice notice-success is-dismissible"><p>AI settings saved successfully!</p></div>')
                        .insertAfter('.wrap h1').delay(3000).fadeOut();
                } else {
                    alert('Error saving AI settings: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to save AI settings. Please try again.');
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    });

    // Test OpenAI connection
    $('#test-openai').click(function() {
        var button = $(this);
        var apiKey = $('[name="openai_key"]').val().trim();
        
        if (!apiKey) {
            alert('Please enter an OpenAI API key first.');
            return;
        }
        
        button.text('Testing...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'edubot_test_api',
                api_type: 'openai',
                api_key: apiKey,
                nonce: '<?php echo wp_create_nonce('edubot_test_api'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ OpenAI connection successful!');
                } else {
                    alert('❌ OpenAI connection failed: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('❌ Failed to test OpenAI connection.');
            },
            complete: function() {
                button.text('Test OpenAI Connection').prop('disabled', false);
            }
        });
    });
});
</script>
