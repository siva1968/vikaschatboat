<?php
/**
 * WhatsApp and SMS Configuration Guide
 * Instructions for setting up WhatsApp and SMS notifications
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display WhatsApp and SMS configuration guide
 */
function edubot_display_messaging_config_guide() {
    ?>
    <div class="wrap edubot-messaging-guide">
        <h1>📱 WhatsApp & SMS Configuration Guide</h1>
        
        <div class="notice notice-info">
            <p><strong>Note:</strong> WhatsApp and SMS notifications are automatically integrated with your enquiry confirmations. Configure the providers below to enable multi-channel notifications.</p>
        </div>

        <!-- Current Configuration Status -->
        <div class="edubot-card">
            <h2>Current Configuration Status</h2>
            
            <?php
            // Check WhatsApp configuration
            $whatsapp_provider = get_option('edubot_whatsapp_provider', '');
            $whatsapp_token = get_option('edubot_whatsapp_token', '');
            $whatsapp_phone_id = get_option('edubot_whatsapp_phone_id', '');
            $whatsapp_configured = !empty($whatsapp_provider) && !empty($whatsapp_token);
            
            // Check SMS configuration  
            $sms_provider = get_option('edubot_sms_provider', '');
            $sms_api_key = get_option('edubot_sms_api_key', '');
            $sms_configured = !empty($sms_provider) && !empty($sms_api_key);
            ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                <div class="status-card <?php echo $whatsapp_configured ? 'configured' : 'not-configured'; ?>">
                    <h3>📱 WhatsApp Status</h3>
                    <?php if ($whatsapp_configured): ?>
                        <p class="status-text">✅ Configured</p>
                        <p><strong>Provider:</strong> <?php echo esc_html(ucfirst($whatsapp_provider)); ?></p>
                        <p><strong>Phone ID:</strong> <?php echo esc_html($whatsapp_phone_id ?: 'Not set'); ?></p>
                    <?php else: ?>
                        <p class="status-text">❌ Not Configured</p>
                        <p>Configure WhatsApp to send instant confirmations</p>
                    <?php endif; ?>
                </div>
                
                <div class="status-card <?php echo $sms_configured ? 'configured' : 'not-configured'; ?>">
                    <h3>📧 SMS Status</h3>
                    <?php if ($sms_configured): ?>
                        <p class="status-text">✅ Configured</p>
                        <p><strong>Provider:</strong> <?php echo esc_html(ucfirst($sms_provider)); ?></p>
                        <p><strong>Sender ID:</strong> <?php echo esc_html(get_option('edubot_sms_sender_id', 'EDUBOT')); ?></p>
                    <?php else: ?>
                        <p class="status-text">❌ Not Configured</p>
                        <p>Configure SMS for backup notifications</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Configuration Instructions -->
        <div class="edubot-card">
            <h2>🚀 How to Configure</h2>
            
            <div class="config-steps">
                <div class="step">
                    <h3>Step 1: Go to API Integrations</h3>
                    <p>Navigate to <strong>EduBot Pro → API Integrations</strong> in your WordPress admin.</p>
                    <a href="<?php echo admin_url('admin.php?page=edubot-api-integrations'); ?>" class="button button-primary">Open API Integrations</a>
                </div>
                
                <div class="step">
                    <h3>Step 2: Configure WhatsApp</h3>
                    <p>Choose your WhatsApp provider and enter the required credentials:</p>
                    <ul>
                        <li><strong>Meta WhatsApp Business API:</strong> Access Token + Phone Number ID</li>
                        <li><strong>Twilio:</strong> Account SID:Auth Token + WhatsApp Number</li>
                    </ul>
                </div>
                
                <div class="step">
                    <h3>Step 3: Configure SMS</h3>
                    <p>Choose your SMS provider and enter the API credentials:</p>
                    <ul>
                        <li><strong>Twilio:</strong> Account SID:Auth Token + Sender ID</li>
                        <li><strong>TextLocal:</strong> API Key + Sender ID</li>
                        <li><strong>Nexmo/Vonage:</strong> API Key + Sender ID</li>
                    </ul>
                </div>
                
                <div class="step">
                    <h3>Step 4: Test Configuration</h3>
                    <p>Use the test buttons in API Integrations to verify your setup.</p>
                    <a href="<?php echo plugin_dir_url(__FILE__) . '../test_whatsapp_sms_integration.php'; ?>" class="button button-secondary" target="_blank">Run Integration Test</a>
                </div>
            </div>
        </div>

        <!-- Provider Setup Guides -->
        <div class="edubot-card">
            <h2>📋 Provider Setup Guides</h2>
            
            <div class="provider-guides">
                <!-- Meta WhatsApp Business API -->
                <div class="provider-guide">
                    <h3>📱 Meta WhatsApp Business API</h3>
                    <div class="guide-content">
                        <h4>Requirements:</h4>
                        <ul>
                            <li>Facebook Business Account (verified)</li>
                            <li>WhatsApp Business Account</li>
                            <li>App with WhatsApp Business API permissions</li>
                            <li><strong>Approved Message Templates (Required for Production)</strong></li>
                        </ul>
                        
                        <h4>Setup Steps:</h4>
                        <ol>
                            <li>Create Facebook App at <a href="https://developers.facebook.com" target="_blank">developers.facebook.com</a></li>
                            <li>Add WhatsApp Business API product</li>
                            <li>Get permanent access token</li>
                            <li>Get Phone Number ID from WhatsApp Business Account</li>
                            <li><strong>Create & get approved message templates</strong></li>
                            <li>Configure template settings in EduBot</li>
                            <li>Enter all credentials in EduBot API Integrations</li>
                        </ol>
                        
                        <div class="notice notice-warning inline" style="margin: 10px 0;">
                            <p><strong>⚠️ Production Requirement:</strong> WhatsApp Business API requires pre-approved templates for production messaging. You cannot send arbitrary text messages to users outside sandbox environment.</p>
                        </div>
                        
                        <h4>Template Configuration:</h4>
                        <ul>
                            <li><strong>Template Namespace:</strong> Found in Meta Business Manager</li>
                            <li><strong>Template Name:</strong> Name of your approved template (e.g., "admission_confirmation")</li>
                            <li><strong>Template Language:</strong> Language code (e.g., "en", "hi")</li>
                        </ul>
                        
                        <p><strong>Format:</strong> Access Token: Your permanent token, Phone ID: Your phone number ID</p>
                    </div>
                </div>
                
                <!-- Twilio -->
                <div class="provider-guide">
                    <h3>📞 Twilio (WhatsApp & SMS)</h3>
                    <div class="guide-content">
                        <h4>Requirements:</h4>
                        <ul>
                            <li>Twilio Account</li>
                            <li>WhatsApp Sandbox or Approved Business Profile</li>
                        </ul>
                        
                        <h4>Setup Steps:</h4>
                        <ol>
                            <li>Create account at <a href="https://twilio.com" target="_blank">twilio.com</a></li>
                            <li>Get Account SID and Auth Token from Console</li>
                            <li>For WhatsApp: Set up WhatsApp Sandbox or Business Profile</li>
                            <li>For SMS: Get a phone number</li>
                            <li>Enter credentials in format: AccountSID:AuthToken</li>
                        </ol>
                        
                        <p><strong>Format:</strong> API Key: ACXXXXX:your_auth_token, Phone/Sender: +1234567890</p>
                    </div>
                </div>
                
                <!-- TextLocal -->
                <div class="provider-guide">
                    <h3>📱 TextLocal (SMS)</h3>
                    <div class="guide-content">
                        <h4>Requirements:</h4>
                        <ul>
                            <li>TextLocal Account (Popular in India)</li>
                            <li>API Key</li>
                        </ul>
                        
                        <h4>Setup Steps:</h4>
                        <ol>
                            <li>Create account at <a href="https://textlocal.in" target="_blank">textlocal.in</a></li>
                            <li>Get API Key from Settings</li>
                            <li>Choose or register Sender ID</li>
                            <li>Enter credentials in EduBot</li>
                        </ol>
                        
                        <p><strong>Format:</strong> API Key: Your TextLocal API key, Sender ID: 6-character sender name</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Templates -->
        <div class="edubot-card">
            <h2>💬 Message Templates</h2>
            
            <p>EduBot automatically generates appropriate messages for each channel:</p>
            
            <div class="template-examples">
                <div class="template-example">
                    <h4>📧 Email Template</h4>
                    <div class="template-content">
                        <p>Professional HTML email with school branding, complete enquiry details, next steps, and contact information.</p>
                    </div>
                </div>
                
                <div class="template-example">
                    <h4>📱 WhatsApp Template</h4>
                    <div class="template-content">
                        <p>Concise confirmation with enquiry number, student details, and immediate next steps. Includes emojis for better engagement.</p>
                    </div>
                </div>
                
                <div class="template-example">
                    <h4>📧 SMS Template</h4>
                    <div class="template-content">
                        <p>Short confirmation message within 160 characters, focusing on enquiry number and callback promise.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- WhatsApp Template Configuration -->
        <div class="edubot-card">
            <h2>📋 WhatsApp Business API Template Configuration</h2>
            
            <div class="notice notice-warning">
                <p><strong>⚠️ Production Requirement:</strong> WhatsApp Business API requires pre-approved message templates for production use. You cannot send arbitrary text messages to users outside of sandbox/testing environments.</p>
            </div>
            
            <h3>Template Setup Process:</h3>
            <div class="config-steps">
                <div class="step">
                    <h4>Step 1: Create Template in Meta Business Manager</h4>
                    <p>Go to <strong>Meta Business Manager → WhatsApp → Message Templates</strong></p>
                    <ul>
                        <li>Click "Create Template"</li>
                        <li>Choose template name: <code>admission_confirmation</code></li>
                        <li>Select category: <strong>TRANSACTIONAL</strong></li>
                        <li>Choose language: <strong>English</strong> (or your preferred language)</li>
                    </ul>
                </div>
                
                <div class="step">
                    <h4>Step 2: Design Template Content</h4>
                    <p>Recommended template structure:</p>
                    <div style="background: #f0f0f0; padding: 15px; border-radius: 5px; font-family: monospace;">
                        <strong>Header:</strong> Admission Enquiry Confirmation<br><br>
                        <strong>Body:</strong><br>
                        Dear {{1}},<br><br>
                        Thank you for your enquiry at {{3}}. Your enquiry number is {{2}} for Grade {{4}}.<br><br>
                        We have received your application on {{5}} and will contact you within 24-48 hours.<br><br>
                        Best regards,<br>
                        Admissions Team<br><br>
                        <strong>Footer:</strong> Reply STOP to unsubscribe
                    </div>
                    <p><strong>Parameters:</strong> {{1}}=Student Name, {{2}}=Enquiry Number, {{3}}=School Name, {{4}}=Grade, {{5}}=Date</p>
                </div>
                
                <div class="step">
                    <h4>Step 3: Submit for Approval</h4>
                    <ul>
                        <li>Review template content follows WhatsApp guidelines</li>
                        <li>Submit template for Meta review</li>
                        <li>Wait 24-48 hours for approval</li>
                        <li>Check approval status in WhatsApp Manager</li>
                    </ul>
                </div>
                
                <div class="step">
                    <h4>Step 4: Configure in EduBot</h4>
                    <p>Once approved, configure in <strong>API Integrations</strong>:</p>
                    <ul>
                        <li><strong>Use Templates:</strong> ✅ Enable</li>
                        <li><strong>Template Namespace:</strong> Found in Meta Business Manager</li>
                        <li><strong>Template Name:</strong> <code>admission_confirmation</code></li>
                        <li><strong>Template Language:</strong> <code>en</code> (or your language code)</li>
                    </ul>
                </div>
            </div>
            
            <h3>Finding Your Template Configuration:</h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Setting</th>
                        <th>Where to Find</th>
                        <th>Example</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Template Namespace</strong></td>
                        <td>Meta Business Manager → WhatsApp → Message Templates → Template Details</td>
                        <td><code>my_school_system</code></td>
                    </tr>
                    <tr>
                        <td><strong>Template Name</strong></td>
                        <td>Name you used when creating template</td>
                        <td><code>admission_confirmation</code></td>
                    </tr>
                    <tr>
                        <td><strong>Template Language</strong></td>
                        <td>Language selected during template creation</td>
                        <td><code>en</code>, <code>hi</code>, <code>es</code></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="notice notice-info">
                <p><strong>💡 Tip:</strong> EduBot automatically maps your admission data to template parameters. The system will use your approved template format while maintaining all the dynamic content (student names, enquiry numbers, etc.)</p>
            </div>
        </div>

        <!-- Troubleshooting -->
        <div class="edubot-card">
            <h2>🔧 Troubleshooting</h2>
            
            <div class="troubleshooting-section">
                <h3>Common Issues:</h3>
                <ul>
                    <li><strong>Messages not sending:</strong> Check API credentials and provider limits</li>
                    <li><strong>WhatsApp not working:</strong> Verify phone number format and Business API approval</li>
                    <li><strong>WhatsApp Template Errors:</strong> Ensure template is approved and template settings match exactly</li>
                    <li><strong>"Template Not Found":</strong> Check template namespace, name, and language code</li>
                    <li><strong>SMS not delivered:</strong> Check sender ID registration and message content</li>
                    <li><strong>Rate limiting:</strong> Most providers have sending limits, check your quotas</li>
                </ul>
                
                <h3>WhatsApp Template Issues:</h3>
                <ul>
                    <li><strong>Template Rejected:</strong> Review WhatsApp's template policies and content guidelines</li>
                    <li><strong>Parameter Mismatch:</strong> Ensure your template parameters match EduBot's data mapping</li>
                    <li><strong>Sandbox vs Production:</strong> Disable templates for sandbox testing, enable for production</li>
                    <li><strong>Namespace Issues:</strong> Verify namespace matches your Meta Business Manager exactly</li>
                </ul>
                
                <h3>Debug Tools:</h3>
                <ul>
                    <li>Check WordPress debug logs for detailed error messages</li>
                    <li>Use provider test APIs to verify credentials</li>
                    <li>Run EduBot integration tests to diagnose issues</li>
                    <li>Monitor provider dashboards for delivery status</li>
                </ul>
            </div>
        </div>
    </div>

    <style>
    .edubot-messaging-guide {
        max-width: 1200px;
    }
    
    .edubot-card {
        background: #fff;
        border: 1px solid #ccd0d4;
        padding: 20px;
        margin: 20px 0;
        border-radius: 4px;
    }
    
    .status-card {
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }
    
    .status-card.configured {
        background: #d4edda;
        border: 1px solid #c3e6cb;
    }
    
    .status-card.not-configured {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
    }
    
    .status-text {
        font-size: 18px;
        font-weight: bold;
        margin: 10px 0;
    }
    
    .config-steps .step {
        margin: 20px 0;
        padding: 20px;
        background: #f8f9fa;
        border-left: 4px solid #007cba;
    }
    
    .provider-guides {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .provider-guide {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
    }
    
    .provider-guide h3 {
        margin-top: 0;
        color: #007cba;
    }
    
    .guide-content ul, .guide-content ol {
        margin: 10px 0;
        padding-left: 20px;
    }
    
    .template-examples {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
    }
    
    .template-example {
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background: #fafafa;
    }
    
    .template-content {
        color: #666;
        font-style: italic;
    }
    
    .troubleshooting-section ul {
        list-style-type: disc;
        padding-left: 20px;
    }
    
    .troubleshooting-section li {
        margin: 8px 0;
    }
    </style>
    <?php
}

// Display the guide
edubot_display_messaging_config_guide();
?>
