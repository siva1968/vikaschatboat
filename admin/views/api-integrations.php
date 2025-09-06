<?php
/**
 * API Integrations View
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

// Decrypt email settings for display
$smtp_password = '';
$encrypted_smtp_password = get_option('edubot_smtp_password', '');
if (!empty($encrypted_smtp_password)) {
    $smtp_password = $security_manager->decrypt_api_key($encrypted_smtp_password);
}

$email_api_key = '';
$encrypted_email_api_key = get_option('edubot_email_api_key', '');
if (!empty($encrypted_email_api_key)) {
    $email_api_key = $security_manager->decrypt_api_key($encrypted_email_api_key);
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('edubot_save_api_settings', 'edubot_api_nonce'); ?>
        
        <div class="edubot-settings">
            <div class="edubot-card">
                <h2>OpenAI Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">OpenAI API Key</th>
                        <td>
                            <input type="password" name="openai_key" value="<?php echo esc_attr($openai_key); ?>" class="regular-text" />
                            <p class="description">Your OpenAI API key for AI-powered responses</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Model</th>
                        <td>
                            <select name="ai_model">
                                <option value="gpt-3.5-turbo" <?php selected(get_option('edubot_openai_model', 'gpt-3.5-turbo'), 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                                <option value="gpt-4" <?php selected(get_option('edubot_openai_model', 'gpt-3.5-turbo'), 'gpt-4'); ?>>GPT-4</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <div class="section-save-buttons">
                    <button type="button" class="button button-primary save-section" data-section="openai">Save OpenAI Settings</button>
                    <button type="button" class="button test-api" id="test-openai">Test OpenAI</button>
                </div>
            </div>

            <div class="edubot-card">
                <h2>Email Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Email Service</th>
                        <td>
                            <select name="email_provider" id="edubot_email_service">
                                <option value="smtp" <?php selected(get_option('edubot_email_service', 'smtp'), 'smtp'); ?>>SMTP</option>
                                <option value="sendgrid" <?php selected(get_option('edubot_email_service', 'smtp'), 'sendgrid'); ?>>SendGrid</option>
                                <option value="mailgun" <?php selected(get_option('edubot_email_service', 'smtp'), 'mailgun'); ?>>Mailgun</option>
                                <option value="zeptomail" <?php selected(get_option('edubot_email_service', 'smtp'), 'zeptomail'); ?>>ZeptoMail</option>
                            </select>
                        </td>
                    </tr>
                    <tr class="smtp-fields">
                        <th scope="row">SMTP Host</th>
                        <td>
                            <input type="text" name="smtp_host" value="<?php echo esc_attr(get_option('edubot_smtp_host', '')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr class="smtp-fields">
                        <th scope="row">SMTP Port</th>
                        <td>
                            <input type="number" name="smtp_port" value="<?php echo esc_attr(get_option('edubot_smtp_port', '587')); ?>" class="small-text" />
                        </td>
                    </tr>
                    <tr class="smtp-fields">
                        <th scope="row">SMTP Username</th>
                        <td>
                            <input type="text" name="smtp_username" value="<?php echo esc_attr(get_option('edubot_smtp_username', '')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr class="smtp-fields">
                        <th scope="row">SMTP Password</th>
                        <td>
                            <input type="password" name="smtp_password" value="<?php echo esc_attr($smtp_password); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">From Email Address</th>
                        <td>
                            <input type="email" name="email_from_address" value="<?php echo esc_attr(get_option('edubot_email_from_address', '')); ?>" class="regular-text" placeholder="noreply@yoursite.com" />
                            <p class="description">The email address that will appear as the sender</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">From Name</th>
                        <td>
                            <input type="text" name="email_from_name" value="<?php echo esc_attr(get_option('edubot_email_from_name', '')); ?>" class="regular-text" placeholder="EduBot Support" />
                            <p class="description">The name that will appear as the sender</p>
                        </td>
                    </tr>
                    <tr class="api-fields" style="display: none;">
                        <th scope="row">API Key</th>
                        <td>
                            <input type="password" name="email_api_key" value="<?php echo esc_attr($email_api_key); ?>" class="regular-text" />
                            <p class="description">For SendGrid/Mailgun/ZeptoMail API key</p>
                        </td>
                    </tr>
                    <tr class="api-fields" style="display: none;">
                        <th scope="row">Domain</th>
                        <td>
                            <input type="text" name="email_domain" value="<?php echo esc_attr(get_option('edubot_email_domain', '')); ?>" class="regular-text" />
                            <p class="description">For Mailgun domain</p>
                        </td>
                    </tr>
                </table>
                <div class="section-save-buttons">
                    <button type="button" class="button button-primary save-section" data-section="email">Save Email Settings</button>
                    <button type="button" class="button test-api" id="test-email">Test Email</button>
                </div>
            </div>

            <div class="edubot-card">
                <h2>WhatsApp Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">WhatsApp Provider</th>
                        <td>
                            <select name="whatsapp_provider">
                                <option value="meta" <?php selected(get_option('edubot_whatsapp_provider', 'meta'), 'meta'); ?>>Meta WhatsApp Business API</option>
                                <option value="twilio" <?php selected(get_option('edubot_whatsapp_provider', 'meta'), 'twilio'); ?>>Twilio</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Access Token</th>
                        <td>
                            <input type="password" name="whatsapp_token" value="<?php echo esc_attr(get_option('edubot_whatsapp_token', '')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Phone Number ID</th>
                        <td>
                            <input type="text" name="whatsapp_phone_id" value="<?php echo esc_attr(get_option('edubot_whatsapp_phone_id', '')); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
                <div class="section-save-buttons">
                    <button type="button" class="button button-primary save-section" data-section="whatsapp">Save WhatsApp Settings</button>
                    <button type="button" class="button test-api" id="test-whatsapp">Test WhatsApp</button>
                </div>
            </div>

            <div class="edubot-card">
                <h2>SMS Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">SMS Provider</th>
                        <td>
                            <select name="sms_provider">
                                <option value="" <?php selected(get_option('edubot_sms_provider', ''), ''); ?>>None</option>
                                <option value="twilio" <?php selected(get_option('edubot_sms_provider', ''), 'twilio'); ?>>Twilio</option>
                                <option value="nexmo" <?php selected(get_option('edubot_sms_provider', ''), 'nexmo'); ?>>Nexmo</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="password" name="sms_api_key" value="<?php echo esc_attr(get_option('edubot_sms_api_key', '')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Sender ID</th>
                        <td>
                            <input type="text" name="sms_sender_id" value="<?php echo esc_attr(get_option('edubot_sms_sender_id', '')); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
                <div class="section-save-buttons">
                    <button type="button" class="button button-primary save-section" data-section="sms">Save SMS Settings</button>
                </div>
            </div>

            <div class="edubot-card">
                <h2>Test Configuration</h2>
                <p>Use these buttons to test your API configurations:</p>
                <div id="test-results"></div>
            </div>

            <!-- Debug Settings Section -->
            <div class="edubot-card">
                <h2>Debug Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Debug Mode</th>
                        <td>
                            <label>
                                <input type="checkbox" name="edubot_debug_enabled" value="1" <?php checked(get_option('edubot_debug_enabled', false), true); ?> />
                                Enable detailed logging and debugging information
                            </label>
                            <p class="description">When enabled, detailed logs will be created for API calls, data transfers, and security events.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Debug Level</th>
                        <td>
                            <select name="edubot_debug_level">
                                <option value="basic" <?php selected(get_option('edubot_debug_level', 'basic'), 'basic'); ?>>Basic - Errors and warnings only</option>
                                <option value="detailed" <?php selected(get_option('edubot_debug_level', 'basic'), 'detailed'); ?>>Detailed - Include info messages</option>
                                <option value="verbose" <?php selected(get_option('edubot_debug_level', 'basic'), 'verbose'); ?>>Verbose - All activities and data transfers</option>
                            </select>
                            <p class="description">Select the level of detail for debug logging.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Log API Requests</th>
                        <td>
                            <label>
                                <input type="checkbox" name="edubot_log_api_requests" value="1" <?php checked(get_option('edubot_log_api_requests', false), true); ?> />
                                Log all API requests and responses
                            </label>
                            <p class="description">Log detailed information about API calls to OpenAI, email services, and WhatsApp.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Log Data Transfers</th>
                        <td>
                            <label>
                                <input type="checkbox" name="edubot_log_data_transfers" value="1" <?php checked(get_option('edubot_log_data_transfers', false), true); ?> />
                                Log form submissions and data processing
                            </label>
                            <p class="description">Log when data is submitted, processed, and stored in the database.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Log Security Events</th>
                        <td>
                            <label>
                                <input type="checkbox" name="edubot_log_security_events" value="1" <?php checked(get_option('edubot_log_security_events', false), true); ?> />
                                Log security-related events
                            </label>
                            <p class="description">Log nonce failures, permission checks, and security validations.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Debug Log File</th>
                        <td>
                            <code><?php echo WP_CONTENT_DIR . '/edubot-debug.log'; ?></code>
                            <p class="description">Debug information will be written to this file when debug mode is enabled.</p>
                            <?php if (file_exists(WP_CONTENT_DIR . '/edubot-debug.log')): ?>
                                <p><a href="#" id="clear-debug-log" class="button">Clear Debug Log</a> 
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=edubot-api-settings&download_debug_log=1'), 'download_debug_log'); ?>" class="button">Download Debug Log</a></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <div class="section-save-buttons">
                    <button type="button" class="button button-primary save-section" data-section="debug">Save Debug Settings</button>
                </div>
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
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}
.edubot-card h2 {
    margin-top: 0;
    color: #23282d;
    font-size: 18px;
    font-weight: 600;
}
.section-save-buttons {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
}
.section-save-buttons .button {
    margin-right: 10px;
}
.test-api {
    background: #f7f7f7;
    border-color: #cccccc;
    color: #555;
}
.test-api:hover {
    background: #fafafa;
    border-color: #999;
}
.smtp-fields,
.api-fields {
    transition: opacity 0.3s ease;
}
#test-results {
    margin-top: 15px;
}
#test-results .notice {
    margin: 10px 0;
}
.notice.is-dismissible {
    position: relative;
    padding-right: 38px;
}
</style>
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
    // Show/hide email configuration fields based on service type
    function toggleEmailFields() {
        var service = $('#edubot_email_service').val();
        if (service === 'smtp') {
            $('.smtp-fields').show();
            $('.api-fields').hide();
        } else if (service === 'sendgrid' || service === 'zeptomail') {
            $('.smtp-fields').hide();
            $('.api-fields').show();
            // Hide domain field for SendGrid and ZeptoMail (only needed for Mailgun)
            $('.api-fields:has(input[name="email_domain"])').toggle(service === 'mailgun');
        } else {
            $('.smtp-fields').hide();
            $('.api-fields').show();
        }
    }
    
    // Initialize on page load
    toggleEmailFields();
    
    // Toggle on service change
    $('#edubot_email_service').on('change', toggleEmailFields);
    
    // Sectioned save functionality
    $('.save-section').on('click', function() {
        var $button = $(this);
        var section = $button.data('section');
        var $form = $button.closest('form');
        
        console.log('Save button clicked for section:', section);
        
        // Update button state
        $button.prop('disabled', true).text('Saving...');
        
        // Get form data
        var formData = $form.serialize();
        var actionData = formData + '&action=edubot_save_' + section + '_settings';
        
        console.log('Form data being sent:', actionData);
        
        // Show saving message
        var $message = $('<div class="notice notice-info"><p>Saving ' + section + ' settings...</p></div>');
        $button.closest('.edubot-card').prepend($message);
        
        // Ensure we have the AJAX URL
        var ajax_url = (typeof ajaxurl !== 'undefined') ? ajaxurl : (edubot_admin && edubot_admin.ajax_url ? edubot_admin.ajax_url : '/wp-admin/admin-ajax.php');
        console.log('Save AJAX URL:', ajax_url);
        
        // AJAX call to save settings
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: actionData,
            dataType: 'json',
            success: function(response) {
                console.log('Save response received:', response);
                $message.remove();
                if (response && response.success) {
                    var $successMessage = $('<div class="notice notice-success is-dismissible"><p><strong>Success:</strong> ' + (response.data.message || 'Settings saved successfully!') + '</p></div>');
                    $button.closest('.edubot-card').prepend($successMessage);
                    setTimeout(function() {
                        $successMessage.fadeOut();
                    }, 3000);
                } else {
                    var errorMessage = (response && response.data && response.data.message) ? response.data.message : 'Failed to save settings.';
                    var $errorMessage = $('<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> ' + errorMessage + '</p></div>');
                    $button.closest('.edubot-card').prepend($errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.error('Save AJAX Error:', xhr, status, error);
                console.error('Save Response Text:', xhr.responseText);
                $message.remove();
                var $errorMessage = $('<div class="notice notice-error is-dismissible"><p><strong>Network Error:</strong> Failed to save ' + section + ' settings. Please try again.</p></div>');
                $button.closest('.edubot-card').prepend($errorMessage);
            },
            complete: function() {
                // Reset button state
                $button.prop('disabled', false).text('Save ' + section.charAt(0).toUpperCase() + section.slice(1) + ' Settings');
            }
        });
    });
    
    // Clear debug log functionality
    $('#clear-debug-log').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to clear the debug log?')) {
            $.post(ajaxurl, {
                action: 'edubot_clear_debug_log',
                nonce: '<?php echo wp_create_nonce('edubot_clear_debug_log'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('Debug log cleared successfully!');
                    location.reload();
                } else {
                    alert('Failed to clear debug log: ' + response.data.message);
                }
            });
        }
    });
    
    // API test functionality
    $('.test-api').on('click', function() {
        var $button = $(this);
        var testType = $button.attr('id').replace('test-', '');
        var $card = $button.closest('.edubot-card');
        var $results = $('#test-results');
        
        console.log('Test button clicked:', testType);
        console.log('Button:', $button);
        console.log('Card:', $card);
        console.log('Results div:', $results);
        
        // Update button state
        $button.prop('disabled', true).text('Testing...');
        
        // Show testing message
        $results.html('<div class="notice notice-info"><p>Testing ' + testType + ' connection...</p></div>').show();
        
        // Collect test data based on API type
        var testData = {
            action: 'edubot_test_api',
            api_type: testType,
            edubot_api_nonce: $('input[name="edubot_api_nonce"]').val()
        };
        
        console.log('Nonce value:', $('input[name="edubot_api_nonce"]').val());
        
        // Add specific data based on test type
        if (testType === 'openai') {
            testData.api_key = $card.find('input[name="openai_key"]').val();
            console.log('OpenAI API key found:', testData.api_key ? 'Yes' : 'No');
        } else if (testType === 'whatsapp') {
            testData.token = $card.find('input[name="whatsapp_token"]').val();
            testData.provider = $card.find('select[name="whatsapp_provider"]').val();
            testData.phone_id = $card.find('input[name="whatsapp_phone_id"]').val();
        } else if (testType === 'email') {
            testData.provider = $card.find('select[name="email_provider"]').val();
            testData.api_key = $card.find('input[name="email_api_key"]').val();
            testData.domain = $card.find('input[name="email_domain"]').val();
            testData.host = $card.find('input[name="smtp_host"]').val();
            testData.port = $card.find('input[name="smtp_port"]').val();
            testData.username = $card.find('input[name="smtp_username"]').val();
            testData.password = $card.find('input[name="smtp_password"]').val();
        }
        
        console.log('Test data being sent:', testData);
        
        // Ensure we have the AJAX URL
        var ajax_url = (typeof ajaxurl !== 'undefined') ? ajaxurl : (edubot_admin && edubot_admin.ajax_url ? edubot_admin.ajax_url : '/wp-admin/admin-ajax.php');
        console.log('AJAX URL:', ajax_url);
        
        // AJAX call to test API
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: testData,
            dataType: 'json',
            success: function(response) {
                console.log('Test response received:', response);
                console.log('Response type:', typeof response);
                console.log('Response success:', response ? response.success : 'undefined');
                console.log('Response data:', response ? response.data : 'undefined');
                
                if (response && response.success) {
                    $results.html('<div class="notice notice-success"><p><strong>' + testType.toUpperCase() + ' Test Successful:</strong> ' + (response.data.message || 'Connection successful!') + '</p></div>');
                } else {
                    var errorMessage = 'Connection failed! Please check your credentials.';
                    
                    // Enhanced error message extraction
                    if (response && response.data) {
                        if (typeof response.data === 'string') {
                            errorMessage = response.data;
                        } else if (response.data.message) {
                            errorMessage = response.data.message;
                        } else {
                            errorMessage = 'Test failed. Response data: ' + JSON.stringify(response.data);
                        }
                    } else if (response && typeof response === 'string') {
                        errorMessage = response;
                    }
                    
                    console.error('Test failed with error:', errorMessage);
                    $results.html('<div class="notice notice-error"><p><strong>' + testType.toUpperCase() + ' Test Failed:</strong> ' + errorMessage + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr, status, error);
                console.error('Response Text:', xhr.responseText);
                $results.html('<div class="notice notice-error"><p><strong>Network Error:</strong> Failed to test ' + testType + ' connection. Please check console for details.</p></div>');
            },
            complete: function() {
                // Reset button state
                $button.prop('disabled', false).text('Test ' + testType.charAt(0).toUpperCase() + testType.slice(1));
            }
        });
    });
});
</script>
