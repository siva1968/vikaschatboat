<?php
/**
 * WhatsApp Ad Integration Admin Page
 * 
 * @package EduBot_Pro
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EduBot_WhatsApp_Ad_Integration_Page {
    
    /**
     * Register admin page
     */
    public static function register() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }
    
    /**
     * Add menu page
     */
    public static function add_menu_page() {
        add_submenu_page(
            'edubot-pro-settings',
            'WhatsApp Ad Integration',
            '📱 WhatsApp Ads',
            'manage_options',
            'edubot-whatsapp-ads',
            array( __CLASS__, 'render_page' )
        );
    }
    
    /**
     * Register settings and AJAX handlers
     */
    public static function register_settings() {
        register_setting( 'edubot-whatsapp-ads-group', 'edubot_whatsapp_business_phone' );
        register_setting( 'edubot-whatsapp-ads-group', 'edubot_whatsapp_webhook_token' );
        
        // Register AJAX handlers for campaign management
        add_action( 'wp_ajax_edubot_generate_campaign_link', array( __CLASS__, 'ajax_generate_campaign_link' ) );
        add_action( 'wp_ajax_edubot_save_campaign_template', array( __CLASS__, 'ajax_save_campaign_template' ) );
        add_action( 'wp_ajax_edubot_delete_campaign_template', array( __CLASS__, 'ajax_delete_campaign_template' ) );
    }
    
    /**
     * AJAX handler for generating campaign-based WhatsApp links
     */
    public static function ajax_generate_campaign_link() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'edubot_campaign_nonce' ) ) {
            wp_die( 'Security check failed' );
        }
        
        // Load campaign manager
        if (!class_exists('EduBot_WhatsApp_Campaign_Manager')) {
            require_once plugin_dir_path(__FILE__) . '../../includes/class-whatsapp-campaign-manager.php';
        }
        
        $campaign_name = sanitize_text_field( $_POST['campaign_name'] );
        $phone = sanitize_text_field( $_POST['phone'] );
        
        // Generate clean link using campaign manager
        $result = EduBot_WhatsApp_Campaign_Manager::generate_link_by_campaign( $campaign_name, $phone );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
            // Get campaign config for additional info
            $campaign = EduBot_WhatsApp_Campaign_Manager::get_campaign( $campaign_name );
            
            wp_send_json_success( array( 
                'link' => $result,
                'campaign_name' => $campaign_name,
                'platform' => $campaign['platform'] ?? 'unknown',
                'message' => 'Clean WhatsApp link generated successfully! Campaign data saved to database.',
                'clean_link' => true
            ) );
        }
    }
    
    /**
     * AJAX handler for saving campaign templates
     */
    public static function ajax_save_campaign_template() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'edubot_campaign_nonce' ) ) {
            wp_die( 'Security check failed' );
        }
        
        // Load campaign manager
        if (!class_exists('EduBot_WhatsApp_Campaign_Manager')) {
            require_once plugin_dir_path(__FILE__) . '../../includes/class-whatsapp-campaign-manager.php';
        }
        
        $name = sanitize_text_field( $_POST['name'] );
        $config = array(
            'platform' => sanitize_text_field( $_POST['platform'] ),
            'message_template' => sanitize_textarea_field( $_POST['message'] ),
            'target_grades' => sanitize_text_field( $_POST['grades'] ),
            'notes' => sanitize_textarea_field( $_POST['notes'] ?? '' )
        );
        
        $success = EduBot_WhatsApp_Campaign_Manager::save_campaign( $name, $config );
        
        if ( $success ) {
            wp_send_json_success( array( 'message' => 'Campaign template saved successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to save campaign template' ) );
        }
    }
    
    /**
     * AJAX handler for deleting campaign templates
     */
    public static function ajax_delete_campaign_template() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'edubot_campaign_nonce' ) ) {
            wp_die( 'Security check failed' );
        }
        
        // Load campaign manager
        if (!class_exists('EduBot_WhatsApp_Campaign_Manager')) {
            require_once plugin_dir_path(__FILE__) . '../../includes/class-whatsapp-campaign-manager.php';
        }
        
        $name = sanitize_text_field( $_POST['name'] );
        $success = EduBot_WhatsApp_Campaign_Manager::delete_campaign( $name );
        
        if ( $success ) {
            wp_send_json_success( array( 'message' => 'Campaign template deleted successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to delete campaign template' ) );
        }
    }
    
    /**
     * Render admin page
     */
    public static function render_page() {
        ?>
        <div class="wrap">
            <h1>📱 WhatsApp Ad Integration for EduBot Pro</h1>
            
            <?php self::render_configuration_section(); ?>
            
            <div class="edubot-columns">
                <div class="edubot-column-left">
                    <?php self::render_campaign_based_generator(); ?>
                    <?php self::render_campaign_management(); ?>
                    <?php self::render_simple_link_generator(); ?>
                    <?php self::render_campaign_generator(); ?>
                    <?php self::render_active_campaigns(); ?>
                </div>
                <div class="edubot-column-right">
                    <?php self::render_campaign_analytics(); ?>
                    <?php self::render_analytics_dashboard(); ?>
                </div>
            </div>
        </div>
        
        <style>
            .edubot-columns {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 20px;
            }
            
            .edubot-card {
                background: #fff;
                border: 1px solid #ccc;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            
            .edubot-card h2 {
                margin-top: 0;
                margin-bottom: 20px;
                border-bottom: 2px solid #0073aa;
                padding-bottom: 10px;
            }
            
            .form-table th {
                text-align: left;
            }
            
            .form-table input[type="text"],
            .form-table input[type="number"],
            .form-table select {
                width: 100%;
                max-width: 400px;
            }
            
            .generated-link-box {
                background: #f5f5f5;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 15px;
                margin-top: 10px;
                font-family: monospace;
                word-break: break-all;
            }
            
            .button-group {
                display: flex;
                gap: 10px;
                margin-top: 15px;
            }
            
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-top: 10px;
            }
            
            .stat-box {
                background: #f9f9f9;
                border-left: 4px solid #0073aa;
                padding: 15px;
                border-radius: 3px;
            }
            
            .stat-box .label {
                font-size: 12px;
                color: #666;
                text-transform: uppercase;
            }
            
            .stat-box .value {
                font-size: 28px;
                font-weight: bold;
                color: #0073aa;
                margin-top: 5px;
            }
            
            .notice-box {
                background: #e7f3ff;
                border: 1px solid #0073aa;
                border-radius: 4px;
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .notice-box.success {
                background: #e7f7e7;
                border-color: #5ba65b;
            }
            
            .notice-box.warning {
                background: #fff8e5;
                border-color: #f0ad4e;
            }
        </style>
        <?php
    }
    
    /**
     * Render configuration section
     */
    private static function render_configuration_section() {
        $business_phone = get_option( 'edubot_whatsapp_business_phone', '' );
        $webhook_token = get_option( 'edubot_whatsapp_webhook_token', '' );
        
        ?>
        <div class="edubot-card notice-box">
            <h3>🔧 Configuration Setup</h3>
            <form method="post" action="options.php">
                <?php settings_fields( 'edubot-whatsapp-ads-group' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="edubot_whatsapp_business_phone">Business Phone Number</label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                id="edubot_whatsapp_business_phone"
                                name="edubot_whatsapp_business_phone"
                                value="<?php echo esc_attr( $business_phone ); ?>"
                                placeholder="+91 9876543210"
                            />
                            <p class="description">Your WhatsApp Business Account phone number with country code</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Webhook URL</th>
                        <td>
                            <code style="background: #f5f5f5; padding: 10px; display: block; overflow-x: auto;">
                                <?php echo esc_html( rest_url( 'edubot/v1/whatsapp-webhook' ) ); ?>
                            </code>
                            <p class="description">Configure this URL in your Meta App settings → Webhooks</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="edubot_whatsapp_webhook_token">Webhook Verify Token</label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                id="edubot_whatsapp_webhook_token"
                                name="edubot_whatsapp_webhook_token"
                                value="<?php echo esc_attr( $webhook_token ); ?>"
                                readonly
                            />
                            <button type="button" class="button" onclick="generateWebhookToken()">
                                Generate New Token
                            </button>
                            <p class="description">Use this token in your Meta App webhook configuration</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button( 'Save Configuration' ); ?>
            </form>
        </div>
        
        <script>
            function generateWebhookToken() {
                if (!confirm('Generate a new webhook token? This will invalidate the previous one.')) {
                    return;
                }
                
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'edubot_generate_webhook_token'
                    },
                    success: function(response) {
                        location.reload();
                    }
                });
            }
        </script>
        <?php
    }
    
    /**
     * Render campaign-based generator (NEW - Simplified)
     */
    private static function render_campaign_based_generator() {
        // Load campaign manager
        if (!class_exists('EduBot_WhatsApp_Campaign_Manager')) {
            require_once plugin_dir_path(__FILE__) . '../../includes/class-whatsapp-campaign-manager.php';
        }
        
        // Initialize default campaigns if needed
        EduBot_WhatsApp_Campaign_Manager::init_default_campaigns();
        
        $campaigns = EduBot_WhatsApp_Campaign_Manager::get_campaigns();
        $business_phone = get_option('edubot_whatsapp_business_phone', '');
        ?>
        <!-- DEBUG: Campaign-based generator loaded at <?php echo date('Y-m-d H:i:s'); ?> -->
        <div class="edubot-card" style="border-left: 4px solid #007cba; background: #f8f9fa;">
            <h2>🚀 Campaign-Based Link Generator</h2>
            <p style="color: #666; margin-bottom: 20px;">
                <strong>Super Simple:</strong> Just select a pre-configured campaign and enter phone number. All other parameters (platform, message, grades) are automatically loaded!<br>
                <strong>📊 Marketing Attribution:</strong> UTM tracking data is automatically embedded for lead source tracking.
            </p>
            
            <form id="campaign-based-form">
                <table class="form-table">
                    <tr>
                        <th scope="row" style="width: 200px;">
                            <label for="selected_campaign">Campaign Template *</label>
                        </th>
                        <td>
                            <select id="selected_campaign" style="width: 100%; max-width: 400px;" required>
                                <option value="">-- Select Campaign Template --</option>
                                <?php foreach ($campaigns as $name => $config): ?>
                                    <option value="<?php echo esc_attr($name); ?>" 
                                            data-platform="<?php echo esc_attr($config['platform']); ?>"
                                            data-message="<?php echo esc_attr($config['message_template']); ?>"
                                            data-grades="<?php echo esc_attr($config['target_grades']); ?>">
                                        <?php echo esc_html($name); ?> (<?php echo esc_html(ucfirst(str_replace('_ads', '', $config['platform']))); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Pre-configured campaign with platform, message template, and target grades</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="campaign_phone">WhatsApp Phone Number *</label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                id="campaign_phone"
                                style="width: 100%; max-width: 400px;"
                                required
                                placeholder="+91 9876543210"
                                value="<?php echo esc_attr($business_phone); ?>"
                            />
                            <p class="description">WhatsApp Business phone number (required for generating links)</p>
                        </td>
                    </tr>
                </table>
                
                <div id="campaign-preview" style="display: none; background: #e8f5e8; padding: 15px; border-radius: 4px; margin: 15px 0;">
                    <h4>Campaign Preview:</h4>
                    <p><strong>Platform:</strong> <span id="preview-platform"></span></p>
                    <p><strong>Target Grades:</strong> <span id="preview-grades"></span></p>
                    <p><strong>Message Template:</strong></p>
                    <div style="background: #fff; padding: 10px; border-radius: 3px; border-left: 3px solid #25d366;">
                        <span id="preview-message"></span>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="button" class="button button-primary button-large" onclick="generateCampaignBasedLink()">
                        ⚡ Generate WhatsApp Link (Campaign Mode)
                    </button>
                </div>
            </form>
            
            <div id="campaign-generated-link-area" style="display: none; margin-top: 20px;">
                <h3>✅ Generated WhatsApp Link</h3>
                <div class="generated-link-box" id="campaign-generated-link"></div>
                <div class="button-group" style="margin-top: 15px;">
                    <button type="button" class="button button-secondary" onclick="copyCampaignLink()">
                        📋 Copy Link
                    </button>
                    <button type="button" class="button" onclick="testCampaignLink()">
                        🧪 Test Link
                    </button>
                    <button type="button" class="button" onclick="previewMessage()">
                        👁️ Preview Message
                    </button>
                </div>
            </div>
        </div>

        <script>
            // Show campaign preview when selection changes
            document.getElementById('selected_campaign').addEventListener('change', function() {
                const option = this.options[this.selectedIndex];
                const preview = document.getElementById('campaign-preview');
                
                if (option.value) {
                    document.getElementById('preview-platform').textContent = option.dataset.platform.replace('_ads', '').toUpperCase();
                    document.getElementById('preview-grades').textContent = option.dataset.grades;
                    document.getElementById('preview-message').textContent = option.dataset.message;
                    preview.style.display = 'block';
                } else {
                    preview.style.display = 'none';
                }
            });

            function generateCampaignBasedLink() {
                const campaign = document.getElementById('selected_campaign').value;
                const phone = document.getElementById('campaign_phone').value;
                
                if (!campaign || !phone) {
                    alert('Please select a campaign template and enter phone number');
                    return;
                }
                
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'edubot_generate_campaign_link',
                        campaign_name: campaign,
                        phone: phone,
                        nonce: '<?php echo wp_create_nonce('edubot_campaign_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            const link = response.data.link;
                            
                            // Extract and decode the message
                            const url = new URL(link);
                            const message = decodeURIComponent(url.searchParams.get('text'));
                            
                            // Split message from tracking data (new simplified format)
                            const parts = message.split('\n\n[Campaign:');
                            const userMessage = parts[0];
                            const trackingInfo = parts.length > 1 ? '[Campaign:' + parts[1] : '';
                            
                            document.getElementById('campaign-generated-link').innerHTML = 
                                '<div style="margin-bottom: 15px;"><strong>📱 WhatsApp Link:</strong><br>' +
                                '<code style="background: #f0f0f0; padding: 8px; border-radius: 4px; word-break: break-all; display: block; margin-top: 5px;">' + link + '</code></div>' +
                                '<div style="margin-bottom: 15px;"><strong>💬 User Message:</strong><br>' +
                                '<div style="background: #e8f5e8; padding: 10px; border-radius: 4px; border-left: 3px solid #25d366;">' + userMessage + '</div></div>' +
                                '<div><strong>📊 Campaign Tracking:</strong><br>' +
                                '<small style="color: #666; font-family: monospace;">' + trackingInfo + '</small><br>' +
                                '<em style="color: #28a745; font-size: 12px;">✓ UTM parameters will be captured automatically for lead attribution</em></div>';
                            document.getElementById('campaign-generated-link-area').style.display = 'block';
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('Connection error. Please try again.');
                    }
                });
            }
            
            function copyCampaignLink() {
                const linkBox = document.getElementById('campaign-generated-link');
                const links = linkBox.textContent.match(/https:\/\/[^\s]+/g);
                if (links && links.length > 0) {
                    navigator.clipboard.writeText(links[0]);
                    alert('Link copied to clipboard!');
                }
            }
            
            function testCampaignLink() {
                const linkBox = document.getElementById('campaign-generated-link');
                const links = linkBox.textContent.match(/https:\/\/[^\s]+/g);
                if (links && links.length > 0) {
                    window.open(links[0], '_blank');
                }
            }
            
            function previewMessage() {
                const campaign = document.getElementById('selected_campaign').value;
                if (campaign) {
                    const option = document.getElementById('selected_campaign').options[document.getElementById('selected_campaign').selectedIndex];
                    alert('Message Preview:\n\n' + option.dataset.message);
                }
            }
        </script>
        <?php
    }
    
    /**
     * Render campaign management section
     */
    private static function render_campaign_management() {
        if (!class_exists('EduBot_WhatsApp_Campaign_Manager')) {
            require_once plugin_dir_path(__FILE__) . '../../includes/class-whatsapp-campaign-manager.php';
        }
        
        $campaigns = EduBot_WhatsApp_Campaign_Manager::get_campaigns();
        $platforms = EduBot_WhatsApp_Campaign_Manager::get_available_platforms();
        $grades = EduBot_WhatsApp_Campaign_Manager::get_available_grades();
        ?>
        <div class="edubot-card">
            <h2>⚙️ Campaign Template Management</h2>
            <p style="color: #666; margin-bottom: 15px;">
                Create and manage campaign templates. Once created, you can generate links with just campaign name + phone number.
            </p>
            
            <form id="campaign-template-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="template_name">Template Name *</label>
                        </th>
                        <td>
                            <input type="text" id="template_name" required placeholder="e.g., Grade10 admissions"/>
                            <p class="description">Unique name for this campaign template</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="template_platform">Platform *</label>
                        </th>
                        <td>
                            <select id="template_platform" required>
                                <option value="">-- Select Platform --</option>
                                <?php foreach ($platforms as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="template_grades">Target Grades *</label>
                        </th>
                        <td>
                            <select id="template_grades" required>
                                <option value="">-- Select Grades --</option>
                                <?php foreach ($grades as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="template_message">Message Template *</label>
                        </th>
                        <td>
                            <textarea id="template_message" rows="4" required 
                                     placeholder="Hi! I'm interested in {school_name} Grade 10 admissions for {academic_year}. Can you help me with the application process?"></textarea>
                            <p class="description">
                                Available variables: {campaign_name}, {grades}, {school_name}, {current_year}, {academic_year}
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="template_notes">Notes</label>
                        </th>
                        <td>
                            <textarea id="template_notes" rows="2" placeholder="Optional notes about this campaign template"></textarea>
                        </td>
                    </tr>
                </table>
                
                <div class="button-group">
                    <button type="button" class="button button-primary" onclick="saveCampaignTemplate()">
                        💾 Save Campaign Template
                    </button>
                    <button type="button" class="button" onclick="resetCampaignForm()" style="margin-left: 10px;">
                        🔄 Reset Form
                    </button>
                </div>
            </form>
            
            <?php if (!empty($campaigns)): ?>
            <div style="margin-top: 30px;">
                <h3>Existing Campaign Templates</h3>
                <div style="max-height: 300px; overflow-y: auto;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Template Name</th>
                                <th>Platform</th>
                                <th>Target Grades</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaigns as $name => $config): ?>
                            <tr>
                                <td><strong><?php echo esc_html($name); ?></strong></td>
                                <td><?php echo esc_html($platforms[$config['platform']] ?? $config['platform']); ?></td>
                                <td><?php echo esc_html($config['target_grades']); ?></td>
                                <td>
                                    <button type="button" class="button button-small" onclick="editCampaignTemplate('<?php echo esc_js($name); ?>')">Edit</button>
                                    <button type="button" class="button button-small button-link-delete" onclick="deleteCampaignTemplate('<?php echo esc_js($name); ?>')">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <script>
            function saveCampaignTemplate() {
                const name = document.getElementById('template_name').value;
                const platform = document.getElementById('template_platform').value;
                const grades = document.getElementById('template_grades').value;
                const message = document.getElementById('template_message').value;
                const notes = document.getElementById('template_notes').value;
                const button = document.querySelector('#campaign-template-form .button-primary');
                const isEditing = button.getAttribute('data-editing');
                
                if (!name || !platform || !grades || !message) {
                    alert('Please fill all required fields');
                    return;
                }
                
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'edubot_save_campaign_template',
                        name: name,
                        platform: platform,
                        grades: grades,
                        message: message,
                        notes: notes,
                        editing: isEditing,
                        nonce: '<?php echo wp_create_nonce('edubot_campaign_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Campaign template ' + (isEditing ? 'updated' : 'saved') + ' successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    }
                });
            }
            
            // Reset form function
            function resetCampaignForm() {
                document.getElementById('campaign-template-form').reset();
                const button = document.querySelector('#campaign-template-form .button-primary');
                button.innerHTML = '💾 Save Campaign Template';
                button.removeAttribute('data-editing');
            }
            
            function editCampaignTemplate(name) {
                // Get campaign data via AJAX
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'edubot_get_campaign_template',
                        name: name,
                        nonce: '<?php echo wp_create_nonce('edubot_campaign_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            // Populate form with existing data
                            document.getElementById('template_name').value = data.name;
                            document.getElementById('template_platform').value = data.platform;
                            document.getElementById('template_grades').value = data.target_grades;
                            document.getElementById('template_message').value = data.message_template;
                            document.getElementById('template_notes').value = data.notes || '';
                            
                            // Change button text to indicate editing
                            const button = document.querySelector('#campaign-template-form .button-primary');
                            button.innerHTML = '📝 Update Campaign Template';
                            button.setAttribute('data-editing', name);
                            
                            // Scroll to form
                            document.getElementById('campaign-template-form').scrollIntoView({ behavior: 'smooth' });
                        } else {
                            alert('Error loading campaign: ' + response.data.message);
                        }
                    }
                });
            }
            
            function deleteCampaignTemplate(name) {
                if (confirm('Are you sure you want to delete the campaign template: ' + name + '?')) {
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'edubot_delete_campaign_template',
                            name: name,
                            nonce: '<?php echo wp_create_nonce('edubot_campaign_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Campaign template deleted successfully!');
                                location.reload();
                            } else {
                                alert('Error: ' + response.data.message);
                            }
                        }
                    });
                }
            }
        </script>
        <?php
    }
    
    /**
     * Render campaign analytics section
     */
    private static function render_campaign_analytics() {
        if (!class_exists('EduBot_WhatsApp_Campaign_Manager')) {
            require_once plugin_dir_path(__FILE__) . '../../includes/class-whatsapp-campaign-manager.php';
        }
        
        $analytics = EduBot_WhatsApp_Campaign_Manager::get_campaign_analytics();
        $recent_links = EduBot_WhatsApp_Campaign_Manager::get_recent_links(5);
        ?>
        <div class="edubot-card">
            <h2>📊 Campaign Analytics</h2>
            
            <?php if (!empty($analytics)): ?>
                <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Platform</th>
                                <th>Links</th>
                                <th>Clicks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics as $data): ?>
                            <tr>
                                <td><strong><?php echo esc_html($data['campaign_name']); ?></strong></td>
                                <td><?php echo esc_html(ucfirst(str_replace('_ads', '', $data['platform']))); ?></td>
                                <td><?php echo intval($data['total_links_generated']); ?></td>
                                <td><?php echo intval($data['total_clicks']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: #666; text-align: center; padding: 20px;">
                    No campaign data yet. Generate your first campaign link to see analytics!
                </p>
            <?php endif; ?>
            
            <?php if (!empty($recent_links)): ?>
                <h3>Recent Links Generated</h3>
                <div style="max-height: 200px; overflow-y: auto;">
                    <?php foreach ($recent_links as $link): ?>
                        <div style="background: #f9f9f9; padding: 10px; margin-bottom: 10px; border-radius: 4px; font-size: 12px;">
                            <strong><?php echo esc_html($link['campaign_name']); ?></strong> 
                            (<?php echo esc_html($link['platform']); ?>)<br>
                            Phone: <?php echo esc_html($link['phone']); ?><br>
                            <span style="color: #666;"><?php echo esc_html($link['created_at']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render simple link generator (Backend Interface)
     */
    private static function render_simple_link_generator() {
        ?>
        <div class="edubot-card" style="border-left: 4px solid #28a745;">
            <h2>⚡ Quick Link Generator</h2>
            <p style="color: #666; margin-bottom: 15px;">
                Simple interface for generating WhatsApp ad links. Just enter campaign name like "Admission Drive - Google" and select platform.
            </p>
            
            <form id="simple-link-form">
                <table class="form-table">
                    <tr>
                        <th scope="row" style="width: 200px;">
                            <label for="simple_campaign">Campaign Name *</label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                id="simple_campaign"
                                style="width: 100%; max-width: 400px;"
                                required
                                placeholder="Admission Drive - Google"
                            />
                            <p class="description">Simple campaign description (e.g., "Admission Drive - Google")</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="simple_source">Platform *</label>
                        </th>
                        <td>
                            <select id="simple_source" style="width: 100%; max-width: 400px;" required>
                                <option value="">-- Select Platform --</option>
                                <option value="facebook_ads">📘 Facebook Ads</option>
                                <option value="instagram_ads">📷 Instagram Ads</option>
                                <option value="google_ads">🔴 Google Ads</option>
                                <option value="tiktok_ads">🎵 TikTok Ads</option>
                                <option value="linkedin_ads">💼 LinkedIn Ads</option>
                                <option value="twitter_ads">🐦 Twitter Ads</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="simple_grades">Target Grades</label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                id="simple_grades"
                                style="width: 100%; max-width: 400px;"
                                placeholder="Nursery,KG,Grade 1,Grade 2"
                            />
                            <p class="description">Optional: Comma-separated list of grades (e.g., "Nursery,KG,Grade 1")</p>
                        </td>
                    </tr>
                </table>
                
                <div class="button-group">
                    <button type="button" class="button button-primary button-large" onclick="generateSimpleLink()">
                        🚀 Generate WhatsApp Link
                    </button>
                </div>
            </form>
            
            <div id="simple-link-result" style="display: none; margin-top: 20px; padding: 15px; background: #f0f8f0; border: 1px solid #28a745; border-radius: 4px;">
                <h4>🎉 Generated WhatsApp Link:</h4>
                <div style="background: #fff; padding: 10px; border-radius: 3px; margin: 10px 0;">
                    <code id="simple_generated_link" style="word-break: break-all; display: block;"></code>
                </div>
                <div class="button-group">
                    <button type="button" class="button" onclick="copySimpleLink()">
                        📋 Copy Link
                    </button>
                    <button type="button" class="button" onclick="testSimpleLink()">
                        🧪 Test Link
                    </button>
                </div>
            </div>
        </div>
        
        <script>
            function generateSimpleLink() {
                const campaign = document.getElementById('simple_campaign').value;
                const source = document.getElementById('simple_source').value;
                const grades = document.getElementById('simple_grades').value;
                
                if (!campaign || !source) {
                    alert('Please fill in campaign name and select platform');
                    return;
                }
                
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'edubot_simple_whatsapp_link',
                        campaign: campaign,
                        source: source,
                        grades: grades,
                        nonce: '<?php echo wp_create_nonce( 'edubot_whatsapp_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            document.getElementById('simple_generated_link').textContent = response.data.link;
                            document.getElementById('simple-link-result').style.display = 'block';
                            
                            // Show success message
                            const notice = document.createElement('div');
                            notice.className = 'notice notice-success is-dismissible';
                            notice.innerHTML = '<p><strong>Success!</strong> WhatsApp link generated successfully. Campaign ID: ' + response.data.campaign_id + '</p>';
                            document.querySelector('.wrap h1').after(notice);
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Error generating link. Please try again.');
                    }
                });
            }
            
            function copySimpleLink() {
                const link = document.getElementById('simple_generated_link').textContent;
                navigator.clipboard.writeText(link).then(function() {
                    alert('✅ Link copied to clipboard!\\n\\nThis link includes:\\n• Platform tracking\\n• Campaign attribution\\n• Automatic welcome message');
                }).catch(function(err) {
                    // Fallback for older browsers
                    const textarea = document.createElement('textarea');
                    textarea.value = link;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    alert('✅ Link copied to clipboard!');
                });
            }
            
            function testSimpleLink() {
                const link = document.getElementById('simple_generated_link').textContent;
                if (link) {
                    window.open(link, '_blank');
                } else {
                    alert('Please generate a link first');
                }
            }
        </script>
        <?php
    }

    /**
     * Render campaign generator section
     */
    private static function render_campaign_generator() {
        ?>
        <div class="edubot-card">
            <h2>🎯 Create Ad Campaign</h2>
            
            <form id="campaign-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="campaign_name">Campaign Name *</label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                id="campaign_name"
                                required
                                placeholder="e.g., Summer Admissions 2025"
                            />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ad_source">Ad Platform *</label>
                        </th>
                        <td>
                            <select id="ad_source" required>
                                <option value="">-- Select --</option>
                                <option value="facebook_ads">Facebook Ads</option>
                                <option value="instagram_ads">Instagram Ads</option>
                                <option value="google_ads">Google Ads</option>
                                <option value="tiktok_ads">TikTok Ads</option>
                                <option value="linkedin_ads">LinkedIn Ads</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="target_grades">Target Grades *</label>
                        </th>
                        <td>
                            <select id="target_grades" required>
                                <option value="">-- Select --</option>
                                <option value="Pre-K">Pre-K</option>
                                <option value="K">Kindergarten</option>
                                <option value="Grade 1">Grade 1</option>
                                <option value="Grade 2">Grade 2</option>
                                <option value="Grade 3">Grade 3</option>
                                <option value="Grade 4">Grade 4</option>
                                <option value="Grade 5">Grade 5</option>
                                <option value="Grade 6,Grade 7,Grade 8">Middle School</option>
                                <option value="Grade 9,Grade 10">High School</option>
                                <option value="Grade 11,Grade 12">Senior School</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="campaign_notes">Campaign Notes</label>
                        </th>
                        <td>
                            <textarea 
                                id="campaign_notes"
                                rows="3"
                                placeholder="Internal notes about this campaign"
                            ></textarea>
                        </td>
                    </tr>
                </table>
                
                <div class="button-group">
                    <button type="button" class="button button-primary" onclick="generateCampaignLink()">
                        ✨ Generate WhatsApp Link
                    </button>
                </div>
            </form>
            
            <div id="generated-link-area" style="display: none; margin-top: 20px;">
                <h3>Generated WhatsApp Link</h3>
                <div class="generated-link-box" id="generated_link"></div>
                <div class="button-group" style="margin-top: 10px;">
                    <button type="button" class="button" onclick="copyToClipboard()">
                        📋 Copy Link
                    </button>
                    <button type="button" class="button" onclick="testLink()">
                        🧪 Test Link
                    </button>
                </div>
            </div>
        </div>
        
        <script>
            function generateCampaignLink() {
                const campaign = document.getElementById('campaign_name').value;
                const source = document.getElementById('ad_source').value;
                const grades = document.getElementById('target_grades').value;
                
                if (!campaign || !source) {
                    alert('Please fill all required fields');
                    return;
                }
                
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'edubot_generate_whatsapp_link',
                        campaign: campaign,
                        source: source,
                        grades: grades,
                        nonce: '<?php echo wp_create_nonce( 'edubot_whatsapp_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            document.getElementById('generated_link').textContent = response.data.link;
                            document.getElementById('generated-link-area').style.display = 'block';
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Error generating link');
                    }
                });
            }
            
            function copyToClipboard() {
                const link = document.getElementById('generated_link').textContent;
                const textarea = document.createElement('textarea');
                textarea.value = link;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('Link copied to clipboard!');
            }
            
            function testLink() {
                const link = document.getElementById('generated_link').textContent;
                window.open(link, '_blank');
            }
        </script>
        <?php
    }
    
    /**
     * Render active campaigns section
     */
    private static function render_active_campaigns() {
        // Load campaign manager
        if (!class_exists('EduBot_WhatsApp_Campaign_Manager')) {
            require_once plugin_dir_path(__FILE__) . '../../includes/class-whatsapp-campaign-manager.php';
        }
        
        $campaigns = EduBot_WhatsApp_Campaign_Manager::get_campaigns();
        
        ?>
        <div class="edubot-card">
            <h2>📊 Active Campaigns</h2>
            
            <?php if ( empty( $campaigns ) ) : ?>
                <p style="color: #666;">No active campaigns yet. Create one above to get started!</p>
            <?php else : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Campaign Name</th>
                            <th>Platform</th>
                            <th>Target Grades</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $campaigns as $name => $campaign ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $name ); ?></strong></td>
                                <td><?php echo esc_html( ucfirst( str_replace( '_ads', '', $campaign['platform'] ) ) ); ?></td>
                                <td><?php echo esc_html( $campaign['target_grades'] ); ?></td>
                                <td><?php echo esc_html( date( 'M d, Y', strtotime( $campaign['created_at'] ) ) ); ?></td>
                                <td>
                                    <button class="button button-small" onclick="viewCampaignStats('<?php echo esc_js($name); ?>')">
                                        View Stats
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render analytics dashboard section
     */
    private static function render_analytics_dashboard() {
        // Get global stats
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'edubot_whatsapp_sessions';
        $total_sessions = $wpdb->get_var( "SELECT COUNT(*) FROM $sessions_table" );
        $completed_sessions = $wpdb->get_var( "SELECT COUNT(*) FROM $sessions_table WHERE completed_at IS NOT NULL" );
        $active_sessions = $wpdb->get_var( "SELECT COUNT(*) FROM $sessions_table WHERE completed_at IS NULL" );
        
        $conversion_rate = $total_sessions > 0 ? round( ( $completed_sessions / $total_sessions ) * 100, 1 ) : 0;
        
        ?>
        <div class="edubot-card">
            <h2>📈 Performance Metrics</h2>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="label">Total Sessions</div>
                    <div class="value"><?php echo $total_sessions; ?></div>
                </div>
                
                <div class="stat-box">
                    <div class="label">Completed</div>
                    <div class="value"><?php echo $completed_sessions; ?></div>
                </div>
                
                <div class="stat-box">
                    <div class="label">Active</div>
                    <div class="value"><?php echo $active_sessions; ?></div>
                </div>
                
                <div class="stat-box">
                    <div class="label">Conversion Rate</div>
                    <div class="value"><?php echo $conversion_rate; ?>%</div>
                </div>
            </div>
        </div>
        
        <div class="edubot-card">
            <h2>🧪 Testing</h2>
            <button type="button" class="button button-primary" onclick="testWebhook()">
                Test Webhook Connection
            </button>
            <p class="description" style="margin-top: 10px;">
                Verify that your Meta App webhook is properly configured
            </p>
        </div>
        
        <script>
            function testWebhook() {
                alert('Webhook test will be implemented');
            }
            
            function viewCampaignStats(campaignId) {
                alert('Campaign stats for ID: ' + campaignId);
            }
        </script>
        <?php
    }
}

// Register the admin page
if ( is_admin() ) {
    EduBot_WhatsApp_Ad_Integration_Page::register();
}
