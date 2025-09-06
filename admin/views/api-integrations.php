<?php
/**
 * API Integrations View
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('edubot_api_settings');
        do_settings_sections('edubot_api_settings');
        ?>
        
        <div class="edubot-settings">
            <div class="edubot-card">
                <h2>OpenAI Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">OpenAI API Key</th>
                        <td>
                            <input type="password" name="edubot_openai_api_key" value="<?php echo esc_attr(get_option('edubot_openai_api_key', '')); ?>" class="regular-text" />
                            <p class="description">Your OpenAI API key for AI-powered responses</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Model</th>
                        <td>
                            <select name="edubot_openai_model">
                                <option value="gpt-3.5-turbo" <?php selected(get_option('edubot_openai_model', 'gpt-3.5-turbo'), 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                                <option value="gpt-4" <?php selected(get_option('edubot_openai_model', 'gpt-3.5-turbo'), 'gpt-4'); ?>>GPT-4</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="edubot-card">
                <h2>Email Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Email Service</th>
                        <td>
                            <select name="edubot_email_service" id="edubot_email_service">
                                <option value="smtp" <?php selected(get_option('edubot_email_service', 'smtp'), 'smtp'); ?>>SMTP</option>
                                <option value="sendgrid" <?php selected(get_option('edubot_email_service', 'smtp'), 'sendgrid'); ?>>SendGrid</option>
                                <option value="mailgun" <?php selected(get_option('edubot_email_service', 'smtp'), 'mailgun'); ?>>Mailgun</option>
                            </select>
                        </td>
                    </tr>
                    <tr class="smtp-fields">
                        <th scope="row">SMTP Host</th>
                        <td>
                            <input type="text" name="edubot_smtp_host" value="<?php echo esc_attr(get_option('edubot_smtp_host', '')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr class="smtp-fields">
                        <th scope="row">SMTP Port</th>
                        <td>
                            <input type="number" name="edubot_smtp_port" value="<?php echo esc_attr(get_option('edubot_smtp_port', '587')); ?>" class="small-text" />
                        </td>
                    </tr>
                    <tr class="smtp-fields">
                        <th scope="row">SMTP Username</th>
                        <td>
                            <input type="text" name="edubot_smtp_username" value="<?php echo esc_attr(get_option('edubot_smtp_username', '')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr class="smtp-fields">
                        <th scope="row">SMTP Password</th>
                        <td>
                            <input type="password" name="edubot_smtp_password" value="<?php echo esc_attr(get_option('edubot_smtp_password', '')); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
            </div>

            <div class="edubot-card">
                <h2>WhatsApp Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">WhatsApp Provider</th>
                        <td>
                            <select name="edubot_whatsapp_provider">
                                <option value="meta" <?php selected(get_option('edubot_whatsapp_provider', 'meta'), 'meta'); ?>>Meta WhatsApp Business API</option>
                                <option value="twilio" <?php selected(get_option('edubot_whatsapp_provider', 'meta'), 'twilio'); ?>>Twilio</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Access Token</th>
                        <td>
                            <input type="password" name="edubot_whatsapp_token" value="<?php echo esc_attr(get_option('edubot_whatsapp_token', '')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Phone Number ID</th>
                        <td>
                            <input type="text" name="edubot_whatsapp_phone_id" value="<?php echo esc_attr(get_option('edubot_whatsapp_phone_id', '')); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
            </div>

            <div class="edubot-card">
                <h2>Test Configuration</h2>
                <p>Use these buttons to test your API configurations:</p>
                <button type="button" class="button" id="test-openai">Test OpenAI</button>
                <button type="button" class="button" id="test-email">Test Email</button>
                <button type="button" class="button" id="test-whatsapp">Test WhatsApp</button>
                <div id="test-results"></div>
            </div>
        </div>

        <?php submit_button('Save API Settings'); ?>
    </form>
</div>

<style>
.edubot-settings {
    margin-top: 20px;
}
.edubot-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 4px;
}
.edubot-card h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}
#test-results {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
    display: none;
}
.test-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.test-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<script>
jQuery(document).ready(function($) {
    // API test functionality would go here
    $('#test-openai, #test-email, #test-whatsapp').on('click', function() {
        var testType = $(this).attr('id').replace('test-', '');
        $('#test-results').html('Testing ' + testType + '...').show();
        
        // AJAX call to test API would go here
        // For now, just show a placeholder message
        setTimeout(function() {
            $('#test-results').html('Test functionality will be implemented in the next update.').removeClass('test-error').addClass('test-success');
        }, 1000);
    });
});
</script>
