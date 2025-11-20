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
     * Register settings
     */
    public static function register_settings() {
        register_setting( 'edubot-whatsapp-ads-group', 'edubot_whatsapp_business_phone' );
        register_setting( 'edubot-whatsapp-ads-group', 'edubot_whatsapp_webhook_token' );
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
                    <?php self::render_simple_link_generator(); ?>
                    <?php self::render_campaign_generator(); ?>
                    <?php self::render_active_campaigns(); ?>
                </div>
                <div class="edubot-column-right">
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
        $campaigns = EduBot_WhatsApp_Ad_Link_Generator::get_active_campaigns();
        
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
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $campaigns as $campaign ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $campaign->name ); ?></strong></td>
                                <td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $campaign->source ) ) ); ?></td>
                                <td><?php echo esc_html( date( 'M d, Y', strtotime( $campaign->created_at ) ) ); ?></td>
                                <td>
                                    <button class="button button-small" onclick="viewCampaignStats(<?php echo $campaign->id; ?>)">
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
