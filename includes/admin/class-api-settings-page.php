<?php
/**
 * API Settings Admin Page
 * 
 * Provides interface for managing API integrations and credentials
 * for Facebook, Google Ads, TikTok, and LinkedIn.
 * 
 * @since 1.4.0
 * @package EduBot_Pro
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class EduBot_API_Settings_Page {
    
    /**
     * Plugin instance
     * 
     * @var EduBot_API_Settings_Page
     */
    private static $instance = null;
    
    /**
     * Logger instance
     * 
     * @var EduBot_Logger
     */
    private $logger;
    
    /**
     * Get singleton instance
     * 
     * @param EduBot_Logger $logger Logger instance
     * @return EduBot_API_Settings_Page
     */
    public static function get_instance($logger = null) {
        if (null === self::$instance) {
            self::$instance = new self($logger);
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     * 
     * @param EduBot_Logger $logger Logger instance
     */
    public function __construct($logger = null) {
        $this->logger = $logger;
        
        // Register AJAX handlers - these need to be outside is_admin() check
        add_action('wp_ajax_test_api_connection', [$this, 'handle_test_connection']);
        add_action('wp_ajax_nopriv_test_api_connection', [$this, 'handle_test_connection']);
        
        if (is_admin()) {
            add_action('admin_init', [$this, 'register_settings']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_page_assets']);
        }
    }
    
    /**
     * Register settings
     * 
     * @since 1.4.0
     * @return void
     */
    public function register_settings() {
        // Facebook settings
        register_setting(
            'edubot_api_settings',
            'edubot_facebook_app_id',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_facebook_app_secret',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_facebook_access_token',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        // Google Ads settings
        register_setting(
            'edubot_api_settings',
            'edubot_google_client_id',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_google_client_secret',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_google_refresh_token',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        // TikTok settings
        register_setting(
            'edubot_api_settings',
            'edubot_tiktok_app_id',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_tiktok_app_secret',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_tiktok_access_token',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        // LinkedIn settings
        register_setting(
            'edubot_api_settings',
            'edubot_linkedin_client_id',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_linkedin_client_secret',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_linkedin_access_token',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        // Email settings (ZeptoMail only)
        register_setting(
            'edubot_api_settings',
            'edubot_email_service',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'zeptomail',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_email_from_address',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_email',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_email_from_name',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_email_api_key',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_email_domain',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        // SMS settings
        register_setting(
            'edubot_api_settings',
            'edubot_sms_provider',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_sms_api_key',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_sms_sender_id',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        // WhatsApp settings
        register_setting(
            'edubot_api_settings',
            'edubot_whatsapp_provider',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'meta',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_whatsapp_token',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_whatsapp_phone_id',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_whatsapp_use_templates',
            [
                'type' => 'boolean',
                'sanitize_callback' => function($value) { return $value ? 1 : 0; },
                'default' => 0,
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_whatsapp_template_namespace',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_whatsapp_template_name',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );
        
        register_setting(
            'edubot_api_settings',
            'edubot_whatsapp_template_language',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'en',
            ]
        );
    }
    
    /**
     * Render API settings page
     * 
     * @since 1.4.0
     * @return void
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $settings_updated = false;
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_wpnonce'])) {
            if (wp_verify_nonce($_POST['_wpnonce'], 'edubot_api_nonce')) {
                $this->handle_form_submission();
                $settings_updated = true;
            }
        }
        
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'facebook';
        
        // Enqueue jQuery and add our scripts inline
        wp_enqueue_script('jquery');
        wp_enqueue_style('common');
        
        // Add inline CSS
        echo '<style>' . $this->get_page_styles() . '</style>';
        
        // Prepare localized data for JavaScript
        $localized_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('test_api_connection'),
        ];
        
        // Add inline JavaScript with localized data
        echo '<script type="text/javascript">';
        echo "\n";
        echo 'var edubot = ' . wp_json_encode($localized_data) . ";\n";
        echo $this->get_page_javascript();
        echo "\n";
        echo '</script>';
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php $this->render_notices($settings_updated); ?>
            
            <div class="edubot-settings-container">
                <h2 class="nav-tab-wrapper">
                    <a href="?page=edubot-api-settings&tab=facebook" 
                       class="nav-tab <?php echo $active_tab === 'facebook' ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons dashicons-facebook"></span> Facebook
                    </a>
                    <a href="?page=edubot-api-settings&tab=google" 
                       class="nav-tab <?php echo $active_tab === 'google' ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons dashicons-format-image"></span> Google Ads
                    </a>
                    <a href="?page=edubot-api-settings&tab=tiktok" 
                       class="nav-tab <?php echo $active_tab === 'tiktok' ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons dashicons-video-alt"></span> TikTok
                    </a>
                    <a href="?page=edubot-api-settings&tab=linkedin" 
                       class="nav-tab <?php echo $active_tab === 'linkedin' ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons dashicons-share"></span> LinkedIn
                    </a>
                    <a href="?page=edubot-api-settings&tab=email" 
                       class="nav-tab <?php echo $active_tab === 'email' ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons dashicons-email"></span> Email
                    </a>
                    <a href="?page=edubot-api-settings&tab=sms" 
                       class="nav-tab <?php echo $active_tab === 'sms' ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons dashicons-phone"></span> SMS
                    </a>
                    <a href="?page=edubot-api-settings&tab=whatsapp" 
                       class="nav-tab <?php echo $active_tab === 'whatsapp' ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons dashicons-phone"></span> WhatsApp
                    </a>
                </h2>
                
                <form method="post" action="" class="edubot-settings-form">
                    <?php wp_nonce_field('edubot_api_nonce'); ?>
                    
                    <div class="tab-content">
                        <?php
                        switch ($active_tab) {
                            case 'google':
                                $this->render_google_settings();
                                break;
                            case 'tiktok':
                                $this->render_tiktok_settings();
                                break;
                            case 'linkedin':
                                $this->render_linkedin_settings();
                                break;
                            case 'email':
                                $this->render_email_settings();
                                break;
                            case 'sms':
                                $this->render_sms_settings();
                                break;
                            case 'whatsapp':
                                $this->render_whatsapp_settings();
                                break;
                            case 'facebook':
                            default:
                                $this->render_facebook_settings();
                        }
                        ?>
                    </div>
                    
                    <div class="form-actions">
                        <?php submit_button('Save Settings', 'primary', 'submit', true); ?>
                        <button type="button" class="button button-secondary test-connection-btn">
                            Test Connection
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Facebook settings section
     * 
     * @since 1.4.0
     * @return void
     */
    private function render_facebook_settings() {
        $app_id = get_option('edubot_facebook_app_id', '');
        $app_secret = get_option('edubot_facebook_app_secret', '');
        $access_token = get_option('edubot_facebook_access_token', '');
        $is_configured = !empty($app_id) && !empty($access_token);
        ?>
        <div class="edubot-settings-section">
            <h3>Facebook Ads Integration</h3>
            <p class="description">
                Connect your Facebook Ads account to track conversions and measure campaign performance.
                <a href="https://developers.facebook.com/" target="_blank" rel="noopener">View Documentation</a>
            </p>
            
            <div class="setting-group">
                <label for="edubot_facebook_app_id">
                    <strong>App ID</strong>
                    <?php if ($is_configured) : ?>
                        <span class="status-indicator connected" title="Connected">✓</span>
                    <?php endif; ?>
                </label>
                <input type="text" id="edubot_facebook_app_id" name="edubot_facebook_app_id" 
                       value="<?php echo esc_attr($app_id); ?>" class="regular-text">
                <p class="description">Your Facebook App ID from developers.facebook.com</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_facebook_app_secret"><strong>App Secret</strong></label>
                <input type="password" id="edubot_facebook_app_secret" name="edubot_facebook_app_secret" 
                       value="<?php echo esc_attr($app_secret); ?>" class="regular-text">
                <p class="description">Your Facebook App Secret (kept secure)</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_facebook_access_token"><strong>Access Token</strong></label>
                <textarea id="edubot_facebook_access_token" name="edubot_facebook_access_token" 
                          rows="3" class="large-text code"><?php echo esc_textarea($access_token); ?></textarea>
                <p class="description">Your long-lived access token for API requests</p>
            </div>
            
            <div class="api-info-box">
                <h4>Setup Instructions</h4>
                <ol>
                    <li>Go to <a href="https://developers.facebook.com/" target="_blank">developers.facebook.com</a></li>
                    <li>Create a new app or select existing one</li>
                    <li>Add "Facebook Login" and "Conversions API" products</li>
                    <li>Generate an access token with <code>ads_management</code> permission</li>
                    <li>Copy App ID, App Secret, and Access Token below</li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Google Ads settings section
     * 
     * @since 1.4.0
     * @return void
     */
    private function render_google_settings() {
        $client_id = get_option('edubot_google_client_id', '');
        $client_secret = get_option('edubot_google_client_secret', '');
        $refresh_token = get_option('edubot_google_refresh_token', '');
        $is_configured = !empty($client_id) && !empty($refresh_token);
        ?>
        <div class="edubot-settings-section">
            <h3>Google Ads Integration</h3>
            <p class="description">
                Connect your Google Ads account to track conversions and measure campaign performance.
                <a href="https://developers.google.com/google-ads" target="_blank" rel="noopener">View Documentation</a>
            </p>
            
            <div class="setting-group">
                <label for="edubot_google_client_id">
                    <strong>Client ID</strong>
                    <?php if ($is_configured) : ?>
                        <span class="status-indicator connected" title="Connected">✓</span>
                    <?php endif; ?>
                </label>
                <input type="text" id="edubot_google_client_id" name="edubot_google_client_id" 
                       value="<?php echo esc_attr($client_id); ?>" class="regular-text">
                <p class="description">Your Google OAuth 2.0 Client ID</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_google_client_secret"><strong>Client Secret</strong></label>
                <input type="password" id="edubot_google_client_secret" name="edubot_google_client_secret" 
                       value="<?php echo esc_attr($client_secret); ?>" class="regular-text">
                <p class="description">Your Google OAuth 2.0 Client Secret (kept secure)</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_google_refresh_token"><strong>Refresh Token</strong></label>
                <textarea id="edubot_google_refresh_token" name="edubot_google_refresh_token" 
                          rows="3" class="large-text code"><?php echo esc_textarea($refresh_token); ?></textarea>
                <p class="description">Your OAuth 2.0 Refresh Token</p>
            </div>
            
            <div class="api-info-box">
                <h4>Setup Instructions</h4>
                <ol>
                    <li>Go to <a href="https://console.developers.google.com/" target="_blank">Google Cloud Console</a></li>
                    <li>Create a new project or select existing one</li>
                    <li>Enable Google Ads API</li>
                    <li>Create OAuth 2.0 credentials (Web application)</li>
                    <li>Authorize the application to get Refresh Token</li>
                    <li>Copy Client ID, Client Secret, and Refresh Token below</li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render TikTok settings section
     * 
     * @since 1.4.0
     * @return void
     */
    private function render_tiktok_settings() {
        $app_id = get_option('edubot_tiktok_app_id', '');
        $app_secret = get_option('edubot_tiktok_app_secret', '');
        $access_token = get_option('edubot_tiktok_access_token', '');
        $is_configured = !empty($app_id) && !empty($access_token);
        ?>
        <div class="edubot-settings-section">
            <h3>TikTok Ads Integration</h3>
            <p class="description">
                Connect your TikTok Ads account to track conversions and measure campaign performance.
                <a href="https://business.tiktok.com/" target="_blank" rel="noopener">View Documentation</a>
            </p>
            
            <div class="setting-group">
                <label for="edubot_tiktok_app_id">
                    <strong>App ID</strong>
                    <?php if ($is_configured) : ?>
                        <span class="status-indicator connected" title="Connected">✓</span>
                    <?php endif; ?>
                </label>
                <input type="text" id="edubot_tiktok_app_id" name="edubot_tiktok_app_id" 
                       value="<?php echo esc_attr($app_id); ?>" class="regular-text">
                <p class="description">Your TikTok App ID</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_tiktok_app_secret"><strong>App Secret</strong></label>
                <input type="password" id="edubot_tiktok_app_secret" name="edubot_tiktok_app_secret" 
                       value="<?php echo esc_attr($app_secret); ?>" class="regular-text">
                <p class="description">Your TikTok App Secret (kept secure)</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_tiktok_access_token"><strong>Access Token</strong></label>
                <textarea id="edubot_tiktok_access_token" name="edubot_tiktok_access_token" 
                          rows="3" class="large-text code"><?php echo esc_textarea($access_token); ?></textarea>
                <p class="description">Your TikTok API Access Token</p>
            </div>
            
            <div class="api-info-box">
                <h4>Setup Instructions</h4>
                <ol>
                    <li>Go to <a href="https://business.tiktok.com/" target="_blank">TikTok Business</a></li>
                    <li>Navigate to Developer Tools</li>
                    <li>Create a new app</li>
                    <li>Get App ID and App Secret</li>
                    <li>Generate an access token</li>
                    <li>Copy credentials below</li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render LinkedIn settings section
     * 
     * @since 1.4.0
     * @return void
     */
    private function render_linkedin_settings() {
        $client_id = get_option('edubot_linkedin_client_id', '');
        $client_secret = get_option('edubot_linkedin_client_secret', '');
        $access_token = get_option('edubot_linkedin_access_token', '');
        $is_configured = !empty($client_id) && !empty($access_token);
        ?>
        <div class="edubot-settings-section">
            <h3>LinkedIn Ads Integration</h3>
            <p class="description">
                Connect your LinkedIn Ads account to track conversions and measure campaign performance.
                <a href="https://business.linkedin.com/" target="_blank" rel="noopener">View Documentation</a>
            </p>
            
            <div class="setting-group">
                <label for="edubot_linkedin_client_id">
                    <strong>Client ID</strong>
                    <?php if ($is_configured) : ?>
                        <span class="status-indicator connected" title="Connected">✓</span>
                    <?php endif; ?>
                </label>
                <input type="text" id="edubot_linkedin_client_id" name="edubot_linkedin_client_id" 
                       value="<?php echo esc_attr($client_id); ?>" class="regular-text">
                <p class="description">Your LinkedIn OAuth 2.0 Client ID</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_linkedin_client_secret"><strong>Client Secret</strong></label>
                <input type="password" id="edubot_linkedin_client_secret" name="edubot_linkedin_client_secret" 
                       value="<?php echo esc_attr($client_secret); ?>" class="regular-text">
                <p class="description">Your LinkedIn OAuth 2.0 Client Secret (kept secure)</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_linkedin_access_token"><strong>Access Token</strong></label>
                <textarea id="edubot_linkedin_access_token" name="edubot_linkedin_access_token" 
                          rows="3" class="large-text code"><?php echo esc_textarea($access_token); ?></textarea>
                <p class="description">Your LinkedIn API Access Token</p>
            </div>
            
            <div class="api-info-box">
                <h4>Setup Instructions</h4>
                <ol>
                    <li>Go to <a href="https://www.linkedin.com/developers/" target="_blank">LinkedIn Developers</a></li>
                    <li>Create a new app</li>
                    <li>Request access to the Sign In with LinkedIn or Marketing Developer Platform</li>
                    <li>Get your Client ID and Client Secret</li>
                    <li>Generate an access token</li>
                    <li>Copy credentials below</li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Email settings section
     * 
     * @since 1.4.0
     * @return void
     */
    private function render_email_settings() {
        $email_service = get_option('edubot_email_service', 'zeptomail');
        $from_address = get_option('edubot_email_from_address', '');
        $from_name = get_option('edubot_email_from_name', '');
        $email_api_key = get_option('edubot_email_api_key', '');
        $email_domain = get_option('edubot_email_domain', '');
        $is_configured = !empty($from_address);
        ?>
        <div class="edubot-settings-section">
            <h3>Email Integration</h3>
            <p class="description">
                Configure email service for sending notifications and confirmations.
                <a href="https://support.google.com/accounts/answer/185833" target="_blank" rel="noopener">View Gmail Setup Guide</a>
            </p>
            
            <div class="setting-group">
                <label for="edubot_email_service">
                    <strong>Email Service Provider</strong>
                    <?php if ($is_configured) : ?>
                        <span class="status-indicator connected" title="Configured">✓</span>
                    <?php endif; ?>
                </label>
                <select id="edubot_email_service" name="edubot_email_service" class="regular-text email-service-select">
                    <option value="zeptomail" <?php selected($email_service, 'zeptomail'); ?>>ZeptoMail (REST API)</option>
                </select>
                <p class="description">Select your email service provider</p>
            </div>
            
            <!-- ZeptoMail REST API Configuration Fields -->
            <div class="setting-group">
                <label for="edubot_email_api_key"><strong>API Key</strong></label>
                <input type="password" id="edubot_email_api_key" name="edubot_email_api_key" 
                       value="<?php echo esc_attr($email_api_key); ?>" class="regular-text">
                <p class="description">API key for ZeptoMail</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_email_from_address"><strong>From Email Address</strong></label>
                <input type="email" id="edubot_email_from_address" name="edubot_email_from_address" 
                       value="<?php echo esc_attr($from_address); ?>" class="regular-text" placeholder="noreply@yourschool.com">
                <p class="description">The email address that will appear as the sender</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_email_from_name"><strong>From Name</strong></label>
                <input type="text" id="edubot_email_from_name" name="edubot_email_from_name" 
                       value="<?php echo esc_attr($from_name); ?>" class="regular-text" placeholder="Your School Name">
                <p class="description">The name that will appear as the sender</p>
            </div>
            
            <!-- Setup Instructions -->
            <div class="api-info-box setup-instructions-zeptomail">
                <h4>ZeptoMail REST API Setup Instructions</h4>
                <ol>
                    <li>Go to <a href="https://www.zeptomail.com/" target="_blank">ZeptoMail Dashboard</a></li>
                    <li>Navigate to Account → API Tokens</li>
                    <li>Create a new API Token</li>
                    <li>Copy the API Token and paste it in the API Key field above</li>
                    <li>Configure your sender email and name (must be verified)</li>
                    <li>Test the connection</li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render SMS settings section
     * 
     * @since 1.4.0
     * @return void
     */
    private function render_sms_settings() {
        $sms_provider = get_option('edubot_sms_provider', '');
        $sms_api_key = get_option('edubot_sms_api_key', '');
        $sms_sender_id = get_option('edubot_sms_sender_id', '');
        $is_configured = !empty($sms_provider) && !empty($sms_api_key);
        ?>
        <div class="edubot-settings-section">
            <h3>SMS Integration</h3>
            <p class="description">
                Configure SMS service for sending text message notifications.
                <a href="https://www.twilio.com/docs" target="_blank" rel="noopener">View Documentation</a>
            </p>
            
            <div class="setting-group">
                <label for="edubot_sms_provider">
                    <strong>SMS Provider</strong>
                    <?php if ($is_configured) : ?>
                        <span class="status-indicator connected" title="Connected">✓</span>
                    <?php endif; ?>
                </label>
                <select id="edubot_sms_provider" name="edubot_sms_provider" class="regular-text">
                    <option value="">None (SMS Disabled)</option>
                    <option value="twilio" <?php selected($sms_provider, 'twilio'); ?>>Twilio</option>
                    <option value="nexmo" <?php selected($sms_provider, 'nexmo'); ?>>Nexmo</option>
                </select>
                <p class="description">Select your SMS provider or leave empty to disable SMS</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_sms_api_key"><strong>API Key / Account SID</strong></label>
                <input type="password" id="edubot_sms_api_key" name="edubot_sms_api_key" 
                       value="<?php echo esc_attr($sms_api_key); ?>" class="regular-text">
                <p class="description">Twilio: Account SID | Nexmo: API Key</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_sms_sender_id"><strong>Sender ID</strong></label>
                <input type="text" id="edubot_sms_sender_id" name="edubot_sms_sender_id" 
                       value="<?php echo esc_attr($sms_sender_id); ?>" class="regular-text" placeholder="+1234567890">
                <p class="description">Your SMS sender number or ID. For Twilio use format: +1234567890</p>
            </div>
            
            <div class="api-info-box">
                <h4>Twilio Setup Instructions</h4>
                <ol>
                    <li>Create a <a href="https://www.twilio.com/console" target="_blank">Twilio account</a></li>
                    <li>Get your Account SID from the Console</li>
                    <li>Verify your sender phone number</li>
                    <li>Enter credentials below and test the connection</li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render WhatsApp settings section
     * 
     * @since 1.4.0
     * @return void
     */
    private function render_whatsapp_settings() {
        $whatsapp_provider = get_option('edubot_whatsapp_provider', 'meta');
        $whatsapp_token = get_option('edubot_whatsapp_token', '');
        $whatsapp_phone_id = get_option('edubot_whatsapp_phone_id', '');
        $use_templates = get_option('edubot_whatsapp_use_templates', 0);
        $template_namespace = get_option('edubot_whatsapp_template_namespace', '');
        $template_name = get_option('edubot_whatsapp_template_name', '');
        $template_language = get_option('edubot_whatsapp_template_language', 'en');
        $is_configured = !empty($whatsapp_token) && !empty($whatsapp_phone_id);
        ?>
        <div class="edubot-settings-section">
            <h3>WhatsApp Integration</h3>
            <p class="description">
                Connect your WhatsApp Business account for sending notifications and updates.
                <a href="https://developers.facebook.com/docs/whatsapp" target="_blank" rel="noopener">View Documentation</a>
            </p>
            
            <div class="setting-group">
                <label for="edubot_whatsapp_provider">
                    <strong>WhatsApp Provider</strong>
                    <?php if ($is_configured) : ?>
                        <span class="status-indicator connected" title="Connected">✓</span>
                    <?php endif; ?>
                </label>
                <select id="edubot_whatsapp_provider" name="edubot_whatsapp_provider" class="regular-text">
                    <option value="meta" <?php selected($whatsapp_provider, 'meta'); ?>>Meta WhatsApp Business API</option>
                    <option value="twilio" <?php selected($whatsapp_provider, 'twilio'); ?>>Twilio</option>
                </select>
                <p class="description">Select your WhatsApp provider</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_whatsapp_token"><strong>Access Token</strong></label>
                <textarea id="edubot_whatsapp_token" name="edubot_whatsapp_token" 
                          rows="3" class="large-text code"><?php echo esc_textarea($whatsapp_token); ?></textarea>
                <p class="description">For Meta: Permanent Access Token | For Twilio: Account SID:Auth Token</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_whatsapp_phone_id"><strong>Phone Number ID</strong></label>
                <input type="text" id="edubot_whatsapp_phone_id" name="edubot_whatsapp_phone_id" 
                       value="<?php echo esc_attr($whatsapp_phone_id); ?>" class="regular-text">
                <p class="description">For Meta: Phone Number ID | For Twilio: WhatsApp Number (e.g., +14155238886)</p>
            </div>
            
            <h4 style="margin-top: 25px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">WhatsApp Business API Templates</h4>
            
            <div class="notice notice-info inline" style="margin: 15px 0;">
                <p><strong>Important:</strong> WhatsApp Business API requires pre-approved templates for production messaging. Configure your approved template details below.</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_whatsapp_use_templates">
                    <input type="checkbox" id="edubot_whatsapp_use_templates" name="edubot_whatsapp_use_templates" value="1" <?php checked($use_templates, 1); ?> />
                    <strong>Enable WhatsApp Business API Templates</strong>
                </label>
                <p class="description">Enable for production use. Disable only for sandbox testing.</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_whatsapp_template_namespace"><strong>Template Namespace</strong></label>
                <input type="text" id="edubot_whatsapp_template_namespace" name="edubot_whatsapp_template_namespace" 
                       value="<?php echo esc_attr($template_namespace); ?>" class="regular-text" placeholder="your_business_namespace">
                <p class="description">Your WhatsApp Business namespace (found in Meta Business Manager)</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_whatsapp_template_name"><strong>Template Name</strong></label>
                <input type="text" id="edubot_whatsapp_template_name" name="edubot_whatsapp_template_name" 
                       value="<?php echo esc_attr($template_name); ?>" class="regular-text" placeholder="admission_confirmation">
                <p class="description">Name of your approved template (e.g., admission_confirmation)</p>
            </div>
            
            <div class="setting-group">
                <label for="edubot_whatsapp_template_language"><strong>Template Language</strong></label>
                <select id="edubot_whatsapp_template_language" name="edubot_whatsapp_template_language" class="regular-text">
                    <option value="en" <?php selected($template_language, 'en'); ?>>English (en)</option>
                    <option value="hi" <?php selected($template_language, 'hi'); ?>>Hindi (hi)</option>
                    <option value="en_US" <?php selected($template_language, 'en_US'); ?>>English US (en_US)</option>
                    <option value="en_GB" <?php selected($template_language, 'en_GB'); ?>>English UK (en_GB)</option>
                </select>
                <p class="description">Language of your approved template</p>
            </div>
            
            <div class="api-info-box">
                <h4>Meta WhatsApp Setup Instructions</h4>
                <ol>
                    <li>Go to <a href="https://business.facebook.com/" target="_blank">Meta Business Manager</a></li>
                    <li>Create a WhatsApp Business Account or link existing one</li>
                    <li>Configure a WhatsApp Phone Number</li>
                    <li>Create and approve message templates</li>
                    <li>Generate a Permanent Access Token</li>
                    <li>Copy Phone Number ID, Access Token, and template details below</li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render any admin notices
     * 
     * @since 1.4.0
     * @return void
     */
    private function render_notices($settings_updated = false) {
        if ($settings_updated || isset($_GET['settings-updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
        }
    }
    
    /**
     * Handle form submission
     * 
     * @since 1.4.0
     * @return void
     */
    private function handle_form_submission() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Log submission for debugging
        error_log('EduBot: Form submission received');
        
        // Update all settings
        $settings = [
            'edubot_facebook_app_id',
            'edubot_facebook_app_secret',
            'edubot_facebook_access_token',
            'edubot_google_client_id',
            'edubot_google_client_secret',
            'edubot_google_refresh_token',
            'edubot_tiktok_app_id',
            'edubot_tiktok_app_secret',
            'edubot_tiktok_access_token',
            'edubot_linkedin_client_id',
            'edubot_linkedin_client_secret',
            'edubot_linkedin_access_token',
            // Email settings (ZeptoMail only)
            'edubot_email_service',
            'edubot_email_from_address',
            'edubot_email_from_name',
            'edubot_email_api_key',
            'edubot_email_domain',
            // SMS settings
            'edubot_sms_provider',
            'edubot_sms_api_key',
            'edubot_sms_sender_id',
            // WhatsApp settings
            'edubot_whatsapp_provider',
            'edubot_whatsapp_token',
            'edubot_whatsapp_phone_id',
            'edubot_whatsapp_template_namespace',
            'edubot_whatsapp_template_name',
            'edubot_whatsapp_template_language',
        ];
        
        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                $value = sanitize_text_field($_POST[$setting]);
                update_option($setting, $value);
                error_log("EduBot: Updated $setting = " . substr($value, 0, 20) . (strlen($value) > 20 ? '...' : ''));
            }
        }
        
        // Handle checkbox fields separately
        if (isset($_POST['edubot_whatsapp_use_templates'])) {
            update_option('edubot_whatsapp_use_templates', 1);
        } else {
            update_option('edubot_whatsapp_use_templates', 0);
        }
        
        // Log the change
        if ($this->logger) {
            $this->logger->log_info('API settings updated by user ID: ' . get_current_user_id());
        }
        
        error_log('EduBot: Settings updated successfully');
    }
    
    /**
     * Enqueue page assets
     * 
     * @since 1.4.0
     * @return void
     */
    public function enqueue_page_assets() {
        // Load on all admin pages - the JavaScript will only activate on pages with the button
        error_log('EduBot enqueue_page_assets: called');
        
        // Inline CSS
        $css = $this->get_page_styles();
        wp_add_inline_style('common', $css);
        
        // Inline JS with localized data
        wp_enqueue_script('jquery');
        
        // Prepare localized data
        $localized_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('test_api_connection'),
        ];
        
        error_log('EduBot enqueue_page_assets: localized data prepared');
        
        // Add inline script with localized data
        $script = 'var edubot = ' . wp_json_encode($localized_data) . ';' . "\n";
        $script .= $this->get_page_javascript();
        wp_add_inline_script('jquery', $script);
        
        error_log('EduBot enqueue_page_assets: scripts enqueued');
    }
    
    /**
     * Get page CSS styles
     * 
     * @since 1.4.0
     * @return string
     */
    private function get_page_styles() {
        return <<<CSS
        .edubot-settings-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .edubot-settings-form {
            margin: 20px 0;
        }
        
        .tab-content {
            background: white;
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            min-height: 400px;
        }
        
        .edubot-settings-section {
            margin-bottom: 30px;
        }
        
        .edubot-settings-section h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .setting-group {
            margin-bottom: 20px;
        }
        
        .setting-group label {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .status-indicator {
            display: inline-block;
            margin-left: 8px;
            color: #28a745;
            font-weight: bold;
        }
        
        .setting-group input[type="text"],
        .setting-group input[type="password"],
        .setting-group textarea {
            max-width: 500px;
        }
        
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 4px;
            color: white;
            font-weight: 500;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        .toast.success {
            background: #28a745;
        }
        
        .toast.error {
            background: #dc3545;
        }
        
        .toast.info {
            background: #17a2b8;
        }
        
        .toast.hide {
            animation: slideOut 0.3s ease-out forwards;
        }
        
        .description {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .api-info-box {
            background: #f5f5f5;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }
        
        .api-info-box h4 {
            margin-top: 0;
        }
        
        .api-info-box ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .api-info-box code {
            background: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .form-actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .form-actions .button {
            margin-right: 10px;
        }
        
        .nav-tab-wrapper {
            background: white;
            border-bottom: 1px solid #ddd;
            margin: 20px 0 0 0;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
        }
        
        .nav-tab {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            margin-right: 2px;
            text-decoration: none;
            color: #666;
            border: 1px solid transparent;
            border-bottom: 3px solid transparent;
        }
        
        .nav-tab:hover {
            color: #333;
            background: #f9f9f9;
        }
        
        .nav-tab-active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: white;
        }
        
        .nav-tab .dashicons {
            width: 18px;
            height: 18px;
            font-size: 18px;
        }
        CSS;
    }
    
    private function get_page_javascript() {
        $js = "(function($) {" . "\n";
        $js .= "    'use strict';" . "\n";
        $js .= "    " . "\n";
        $js .= "    // Prevent duplicate script execution" . "\n";
        $js .= "    if (window.edubotInitialized) {" . "\n";
        $js .= "        return;" . "\n";
        $js .= "    }" . "\n";
        $js .= "    window.edubotInitialized = true;" . "\n";
        $js .= "    " . "\n";
        $js .= "    function showToast(message, type) {" . "\n";
        $js .= "        type = type || 'info';" . "\n";
        $js .= "        var \$toast = $('<div class=\"toast ' + type + '\">' + message + '</div>');" . "\n";
        $js .= "        $('body').append(\$toast);" . "\n";
        $js .= "        setTimeout(function() {" . "\n";
        $js .= "            \$toast.addClass('hide');" . "\n";
        $js .= "            setTimeout(function() {" . "\n";
        $js .= "                \$toast.remove();" . "\n";
        $js .= "            }, 300);" . "\n";
        $js .= "        }, 3000);" . "\n";
        $js .= "    }" . "\n";
        $js .= "    " . "\n";
        $js .= "    $(document).ready(function() {" . "\n";
        $js .= "        $('.setup-instructions-zeptomail').show();" . "\n";
        $js .= "    });" . "\n";
        $js .= "    " . "\n";
        $js .= "    $(document).on('click', '.test-connection-btn', function(e) {" . "\n";
        $js .= "        e.preventDefault();" . "\n";
        $js .= "        console.log('Test Connection button clicked');" . "\n";
        $js .= "        var \$btn = $(this);" . "\n";
        $js .= "        var href = $('a.nav-tab-active').attr('href');" . "\n";
        $js .= "        var tab = href ? href.split('tab=')[1] : '';" . "\n";
        $js .= "        console.log('Active href:', href);" . "\n";
        $js .= "        console.log('Tab:', tab);" . "\n";
        $js .= "        console.log('edubot object:', edubot);" . "\n";
        $js .= "        " . "\n";
        $js .= "        if (typeof edubot === 'undefined') {" . "\n";
        $js .= "            showToast('Error: edubot object not found. Please refresh the page.', 'error');" . "\n";
        $js .= "            console.error('edubot object is undefined');" . "\n";
        $js .= "            return;" . "\n";
        $js .= "        }" . "\n";
        $js .= "        " . "\n";
        $js .= "        if (!edubot.ajax_url || !edubot.nonce) {" . "\n";
        $js .= "            showToast('Error: AJAX configuration incomplete.', 'error');" . "\n";
        $js .= "            console.error('Missing AJAX config:', edubot);" . "\n";
        $js .= "            return;" . "\n";
        $js .= "        }" . "\n";
        $js .= "        " . "\n";
        $js .= "        if (!tab) {" . "\n";
        $js .= "            showToast('Error: Could not determine current tab.', 'error');" . "\n";
        $js .= "            return;" . "\n";
        $js .= "        }" . "\n";
        $js .= "        " . "\n";
        $js .= "        \$btn.prop('disabled', true).text('Testing...');" . "\n";
        $js .= "        " . "\n";
        $js .= "        var ajaxData = {" . "\n";
        $js .= "            action: 'test_api_connection'," . "\n";
        $js .= "            tab: tab," . "\n";
        $js .= "            nonce: edubot.nonce" . "\n";
        $js .= "        };" . "\n";
        $js .= "        " . "\n";
        $js .= "        console.log('Sending AJAX request with data:', ajaxData);" . "\n";
        $js .= "        " . "\n";
        $js .= "        $.ajax({" . "\n";
        $js .= "            url: edubot.ajax_url," . "\n";
        $js .= "            type: 'POST'," . "\n";
        $js .= "            data: ajaxData," . "\n";
        $js .= "            success: function(response) {" . "\n";
        $js .= "                console.log('AJAX Success Response:', response);" . "\n";
        $js .= "                if (response.success) {" . "\n";
        $js .= "                    showToast('✓ Connection successful!', 'success');" . "\n";
        $js .= "                } else {" . "\n";
        $js .= "                    showToast('✗ Connection failed: ' + (response.data || 'Unknown error'), 'error');" . "\n";
        $js .= "                }" . "\n";
        $js .= "            }," . "\n";
        $js .= "            error: function(xhr, status, error) {" . "\n";
        $js .= "                console.error('AJAX Error:', error);" . "\n";
        $js .= "                showToast('Error testing connection: ' + error, 'error');" . "\n";
        $js .= "            }," . "\n";
        $js .= "            complete: function() {" . "\n";
        $js .= "                console.log('AJAX Request Complete');" . "\n";
        $js .= "                \$btn.prop('disabled', false).text('Test Connection');" . "\n";
        $js .= "            }" . "\n";
        $js .= "        });" . "\n";
        $js .= "    });" . "\n";
        $js .= "})(jQuery);" . "\n";
        return $js;
    }
    
    /**
     * Handle test API connection request
     * 
     * @since 1.4.0
     * @return void
     */
    public function handle_test_connection() {
        error_log('EduBot AJAX: handle_test_connection called');
        
        // Verify nonce
        if (!isset($_POST['nonce'])) {
            error_log('EduBot AJAX: nonce not set in POST');
            wp_send_json_error('Nonce not provided');
        }
        
        $nonce_valid = wp_verify_nonce($_POST['nonce'], 'test_api_connection');
        error_log('EduBot AJAX: nonce verification result = ' . ($nonce_valid ? 'valid' : 'invalid'));
        
        if (!$nonce_valid) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            error_log('EduBot AJAX: user does not have manage_options capability');
            wp_send_json_error('Insufficient permissions');
        }
        
        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : '';
        error_log('EduBot AJAX: testing tab = ' . $tab);
        
        try {
            $result = $this->test_platform_connection($tab);
            error_log('EduBot AJAX: test_platform_connection result = ' . ($result ? 'true' : 'false'));
            
            if ($result) {
                wp_send_json_success('Connection successful');
            } else {
                wp_send_json_error('Connection failed - please check your credentials');
            }
        } catch (Exception $e) {
            error_log('EduBot AJAX Exception: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Test connection to a specific platform
     * 
     * @since 1.4.0
     * @param string $platform Platform name (facebook, google, tiktok, linkedin, email, sms, whatsapp)
     * @return bool True if connection successful
     */
    private function test_platform_connection($platform) {
        switch ($platform) {
            case 'facebook':
                $app_id = get_option('edubot_facebook_app_id');
                $access_token = get_option('edubot_facebook_access_token');
                return !empty($app_id) && !empty($access_token);
                
            case 'google':
                $client_id = get_option('edubot_google_client_id');
                $refresh_token = get_option('edubot_google_refresh_token');
                return !empty($client_id) && !empty($refresh_token);
                
            case 'tiktok':
                $app_id = get_option('edubot_tiktok_app_id');
                $access_token = get_option('edubot_tiktok_access_token');
                return !empty($app_id) && !empty($access_token);
                
            case 'linkedin':
                $client_id = get_option('edubot_linkedin_client_id');
                $access_token = get_option('edubot_linkedin_access_token');
                return !empty($client_id) && !empty($access_token);
                
            case 'email':
                $from_address = get_option('edubot_email_from_address');
                $api_key = get_option('edubot_email_api_key');
                return !empty($from_address) && !empty($api_key);
                
            case 'sms':
                $provider = get_option('edubot_sms_provider');
                $api_key = get_option('edubot_sms_api_key');
                return !empty($provider) && !empty($api_key);
                
            case 'whatsapp':
                $token = get_option('edubot_whatsapp_token');
                $phone_id = get_option('edubot_whatsapp_phone_id');
                return !empty($token) && !empty($phone_id);
                
            default:
                return false;
        }
    }
}

// Initialize on plugins_loaded
add_action('plugins_loaded', function() {
    if (class_exists('EduBot_Logger')) {
        EduBot_API_Settings_Page::get_instance();
    }
}, 15);
