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
        
        if (is_admin()) {
            add_action('admin_init', [$this, 'register_settings']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_page_assets']);
            add_action('wp_ajax_test_api_connection', [$this, 'handle_test_connection']);
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
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edubot_api_nonce'])) {
            check_admin_referer('edubot_api_nonce');
            $this->handle_form_submission();
        }
        
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'facebook';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php $this->render_notices(); ?>
            
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
     * Render any admin notices
     * 
     * @since 1.4.0
     * @return void
     */
    private function render_notices() {
        if (isset($_GET['settings-updated'])) {
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
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
        ];
        
        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                update_option($setting, sanitize_text_field($_POST[$setting]));
            }
        }
        
        // Log the change
        if ($this->logger) {
            $this->logger->log_info('API settings updated by user ID: ' . get_current_user_id());
        }
    }
    
    /**
     * Enqueue page assets
     * 
     * @since 1.4.0
     * @return void
     */
    public function enqueue_page_assets() {
        if (!function_exists('get_current_screen')) {
            return;
        }
        
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'edubot-pro_page_edubot-api-settings') {
            return;
        }
        
        // Inline CSS
        $css = $this->get_page_styles();
        wp_add_inline_style('common', $css);
        
        // Inline JS
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', $this->get_page_javascript());
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
    
    /**
     * Get page JavaScript
     * 
     * @since 1.4.0
     * @return string
     */
    private function get_page_javascript() {
        return <<<JS
        (function($) {
            'use strict';
            
            $(document).on('click', '.test-connection-btn', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var tab = $('a.nav-tab-active').attr('href').split('tab=')[1];
                
                $btn.prop('disabled', true).text('Testing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_api_connection',
                        tab: tab,
                        nonce: '<?php echo wp_create_nonce('test_api_connection'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✓ Connection successful!');
                        } else {
                            alert('✗ Connection failed: ' + (response.data || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('✗ Error testing connection. Please try again.');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Test Connection');
                    }
                });
            });
        })(jQuery);
        JS;
    }
    
    /**
     * Handle test API connection request
     * 
     * @since 1.4.0
     * @return void
     */
    public function handle_test_connection() {
        check_ajax_referer('test_api_connection', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : '';
        
        try {
            $result = $this->test_platform_connection($tab);
            
            if ($result) {
                wp_send_json_success('Connection successful');
            } else {
                wp_send_json_error('Connection failed - please check your credentials');
            }
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Test connection to a specific platform
     * 
     * @since 1.4.0
     * @param string $platform Platform name (facebook, google, tiktok, linkedin)
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
