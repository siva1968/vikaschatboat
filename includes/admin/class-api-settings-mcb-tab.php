<?php
/**
 * MyClassBoard Integration - API Settings Tab
 * 
 * Integrates MCB settings into the existing API Settings page
 * Add this to class-api-settings-page.php to enable MCB tab
 * 
 * @package EduBot_Pro
 * @subpackage Admin
 * @version 1.0.0
 */

// This file provides MCB settings integration for the API Settings page

/**
 * INTEGRATION INSTRUCTIONS:
 * 
 * Add these method calls to class-api-settings-page.php:
 * 
 * 1. In render_page() method, add MCB tab to nav-tab-wrapper:
 *    <a href="?page=edubot-api-settings&tab=myclassboard" 
 *       class="nav-tab <?php echo $active_tab === 'myclassboard' ? 'nav-tab-active' : ''; ?>">
 *        <span class="dashicons dashicons-businessman"></span> MyClassBoard
 *    </a>
 * 
 * 2. In render_page() switch statement, add:
 *    case 'myclassboard':
 *        $this->render_myclassboard_settings();
 *        break;
 * 
 * 3. Register MCB settings in register_settings():
 *    (Copy the code from mcb_register_settings() method below)
 * 
 * 4. Add these new methods to class-api-settings-page.php
 */

/**
 * MCB Settings Registration
 */
public function mcb_register_settings() {
    register_setting(
        'edubot_api_settings',
        'edubot_mcb_enabled',
        [
            'type' => 'boolean',
            'sanitize_callback' => function($value) { return $value ? 1 : 0; },
            'default' => 0,
        ]
    );
    
    register_setting(
        'edubot_api_settings',
        'edubot_mcb_org_id',
        [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '21',
        ]
    );
    
    register_setting(
        'edubot_api_settings',
        'edubot_mcb_branch_id',
        [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '113',
        ]
    );
    
    register_setting(
        'edubot_api_settings',
        'edubot_mcb_api_timeout',
        [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 65,
        ]
    );
    
    register_setting(
        'edubot_api_settings',
        'edubot_mcb_retry_attempts',
        [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 3,
        ]
    );
    
    register_setting(
        'edubot_api_settings',
        'edubot_mcb_auto_sync',
        [
            'type' => 'boolean',
            'sanitize_callback' => function($value) { return $value ? 1 : 0; },
            'default' => 1,
        ]
    );
}

/**
 * Render MCB Settings Tab
 */
public function render_myclassboard_settings() {
    $mcb_enabled = get_option('edubot_mcb_enabled', 0);
    $org_id = get_option('edubot_mcb_org_id', '21');
    $branch_id = get_option('edubot_mcb_branch_id', '113');
    $timeout = get_option('edubot_mcb_api_timeout', 65);
    $retries = get_option('edubot_mcb_retry_attempts', 3);
    $auto_sync = get_option('edubot_mcb_auto_sync', 1);
    ?>
    
    <form method="post" action="options.php" class="edubot-settings-form">
        <?php 
        settings_fields('edubot_api_settings');
        wp_nonce_field('edubot_api_nonce');
        ?>
        
        <div class="edubot-settings-section">
            <h3>📱 MyClassBoard Integration</h3>
            <p class="description">Configure MyClassBoard CRM synchronization settings</p>
            
            <!-- Enable/Disable Integration -->
            <div class="setting-group">
                <label for="edubot_mcb_enabled">
                    <input type="checkbox" id="edubot_mcb_enabled" name="edubot_mcb_enabled" 
                           value="1" <?php checked($mcb_enabled, 1); ?> />
                    <span>Enable MyClassBoard Integration</span>
                </label>
                <p class="description">Check to enable synchronization of enquiries to MyClassBoard</p>
            </div>
            
            <!-- Organization ID -->
            <div class="setting-group">
                <label for="edubot_mcb_org_id">Organization ID</label>
                <input type="text" id="edubot_mcb_org_id" name="edubot_mcb_org_id" 
                       value="<?php echo esc_attr($org_id); ?>" class="regular-text" />
                <p class="description">MyClassBoard Organization ID (typically 21)</p>
            </div>
            
            <!-- Branch ID -->
            <div class="setting-group">
                <label for="edubot_mcb_branch_id">Branch ID</label>
                <input type="text" id="edubot_mcb_branch_id" name="edubot_mcb_branch_id" 
                       value="<?php echo esc_attr($branch_id); ?>" class="regular-text" />
                <p class="description">MyClassBoard Branch ID (typically 113)</p>
            </div>
            
            <!-- API Timeout -->
            <div class="setting-group">
                <label for="edubot_mcb_api_timeout">API Timeout (seconds)</label>
                <input type="number" id="edubot_mcb_api_timeout" name="edubot_mcb_api_timeout" 
                       value="<?php echo esc_attr($timeout); ?>" min="10" max="300" class="small-text" />
                <p class="description">How long to wait for API response (10-300 seconds, default: 65)</p>
            </div>
            
            <!-- Retry Attempts -->
            <div class="setting-group">
                <label for="edubot_mcb_retry_attempts">Retry Attempts</label>
                <input type="number" id="edubot_mcb_retry_attempts" name="edubot_mcb_retry_attempts" 
                       value="<?php echo esc_attr($retries); ?>" min="1" max="10" class="small-text" />
                <p class="description">Number of retry attempts on sync failure (1-10, default: 3)</p>
            </div>
            
            <!-- Auto Sync -->
            <div class="setting-group">
                <label for="edubot_mcb_auto_sync">
                    <input type="checkbox" id="edubot_mcb_auto_sync" name="edubot_mcb_auto_sync" 
                           value="1" <?php checked($auto_sync, 1); ?> />
                    <span>Auto-Sync on Enquiry Creation</span>
                </label>
                <p class="description">Automatically sync enquiries to MyClassBoard when created</p>
            </div>
        </div>
        
        <!-- Advanced Settings -->
        <div class="edubot-settings-section">
            <h3>⚙️ Advanced Settings</h3>
            
            <div class="setting-group">
                <h4>API Endpoint</h4>
                <code>https://corp.myclassboard.com/api/EnquiryService/SaveEnquiryDetails</code>
                <p class="description">MyClassBoard API endpoint (read-only)</p>
            </div>
            
            <div class="setting-group">
                <h4>Lead Source Mapping</h4>
                <p class="description">The following lead sources are automatically mapped:</p>
                <ul style="margin-left: 20px;">
                    <li>Chatbot → 273</li>
                    <li>Website → 231</li>
                    <li>Facebook → 272</li>
                    <li>Google Search → 269</li>
                    <li>Instagram → 268</li>
                    <li>LinkedIn → 267</li>
                    <li>WhatsApp → 273</li>
                    <li>Referral → 92</li>
                    <li>Email → 286</li>
                    <li>Walk-In → 250</li>
                    <li>Organic → 280</li>
                    <li>Display → 270</li>
                </ul>
            </div>
        </div>
        
        <!-- Information -->
        <div class="edubot-settings-section" style="background: #f0f7ff; border: 1px solid #b3d9ff; padding: 15px;">
            <h3>ℹ️ Information</h3>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li><strong>Status:</strong> Sync logs and statistics available in dedicated dashboard</li>
                <li><strong>Manual Sync:</strong> Sync individual enquiries anytime from the dashboard</li>
                <li><strong>Error Handling:</strong> Failed syncs are automatically retried</li>
                <li><strong>Audit Trail:</strong> All sync attempts are logged with full details</li>
                <li><strong>Grade Mapping:</strong> Grades are automatically converted to MyClassBoard class IDs</li>
            </ul>
        </div>
        
        <?php submit_button('Save MCB Settings', 'primary', 'submit', true); ?>
    </form>
    
    <?php
}

/**
 * Test MCB Connection
 */
public function test_myclassboard_connection() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'test_api_connection')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    $org_id = get_option('edubot_mcb_org_id', '21');
    $branch_id = get_option('edubot_mcb_branch_id', '113');
    
    // Test data
    $test_data = [
        'OrganisationID' => $org_id,
        'BranchID' => $branch_id,
        'StudentName' => 'Test Student',
        'FatherMobile' => '+919999999999',
        'FatherEmailID' => 'test@example.com',
        'Class' => 'X',
        'MobileNo' => '+919999999999'
    ];
    
    $response = wp_remote_post(
        'https://corp.myclassboard.com/api/EnquiryService/SaveEnquiryDetails',
        [
            'body' => wp_json_encode($test_data),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => get_option('edubot_mcb_api_timeout', 65),
        ]
    );
    
    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => 'Connection failed: ' . $response->get_error_message()
        ]);
    }
    
    $status = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status === 200) {
        wp_send_json_success([
            'message' => 'Connection successful!',
            'response' => json_decode($body)
        ]);
    } else {
        wp_send_json_error([
            'message' => "Connection failed (HTTP $status)",
            'response' => $body
        ]);
    }
}
?>
