<?php
/**
 * MyClassBoard Settings Admin Page
 * 
 * Provides admin interface to configure MyClassBoard API integration
 * 
 * @package EduBot_Pro
 * @subpackage Admin
 * @version 1.0.0
 */

class EduBot_MCB_Settings_Page {

    /**
     * Menu slug
     */
    const MENU_SLUG = 'edubot-mcb-settings';

    /**
     * Initialize
     */
    public function __construct() {
        // Use priority 11 to ensure parent menu exists (EduBot Pro creates at priority 10)
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 11 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edubot-pro',
            'MyClassBoard Integration',
            'MyClassBoard Settings',
            'manage_options',
            self::MENU_SLUG,
            array( $this, 'render_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'edubot_mcb_group', 'edubot_mcb_settings', array(
            'type'              => 'object',
            'sanitize_callback' => array( $this, 'sanitize_settings' ),
            'show_in_rest'      => false,
        ) );
    }

    /**
     * Sanitize settings
     * 
     * CRITICAL: Merges new input with existing settings to prevent data loss
     * This ensures that fields not in the current form submission are preserved
     * 
     * @param array $input Raw input from form
     * @return array Sanitized settings with preserved data
     */
    public function sanitize_settings( $input ) {
        if ( ! is_array( $input ) ) {
            return $input;
        }

        // CRITICAL: Get existing settings from database BEFORE processing new input
        // This ensures we preserve any fields not in the current form submission
        $existing_settings = get_option( 'edubot_mcb_settings' );
        if ( ! is_array( $existing_settings ) ) {
            $existing_settings = array();
        }

        // Sanitize all input fields
        $sanitized = array(
            'enabled'               => isset( $input['enabled'] ) ? 1 : 0,
            'api_key'               => sanitize_text_field( $input['api_key'] ?? '' ),
            'access_token'          => sanitize_text_field( $input['access_token'] ?? '' ),
            'api_url'               => esc_url( $input['api_url'] ?? 'https://corp.myclassboard.com/api/EnquiryService/SaveEnquiryDetails' ),
            'organization_id'       => sanitize_text_field( $input['organization_id'] ?? '21' ),
            'branch_id'             => sanitize_text_field( $input['branch_id'] ?? '113' ),
            'sync_enabled'          => isset( $input['sync_enabled'] ) ? 1 : 0,
            'sync_new_enquiries'    => isset( $input['sync_new_enquiries'] ) ? 1 : 0,
            'sync_updates'          => isset( $input['sync_updates'] ) ? 1 : 0,
            'auto_sync'             => isset( $input['auto_sync'] ) ? 1 : 0,
            'test_mode'             => isset( $input['test_mode'] ) ? 1 : 0,
            'debug_mode'            => isset( $input['debug_mode'] ) ? 1 : 0,
            'timeout'               => intval( $input['timeout'] ?? 65 ),
            'retry_attempts'        => intval( $input['retry_attempts'] ?? 3 ),
        );

        // CRITICAL: Handle lead_source_mapping separately
        // If submitted in form: sanitize new values
        // If NOT submitted: use existing values
        if ( isset( $input['lead_source_mapping'] ) && is_array( $input['lead_source_mapping'] ) ) {
            // Form submitted lead source mapping - sanitize it
            $sanitized['lead_source_mapping'] = array_filter(
                array_map( 'sanitize_text_field', $input['lead_source_mapping'] )
            );
            
            // If all values were empty, restore from existing
            if ( empty( $sanitized['lead_source_mapping'] ) ) {
                $sanitized['lead_source_mapping'] = $existing_settings['lead_source_mapping'] ?? array();
            }
        } else {
            // Form did NOT submit lead source mapping - preserve existing values
            $sanitized['lead_source_mapping'] = $existing_settings['lead_source_mapping'] ?? array();
        }

        // Ensure we always have lead source mapping (never empty)
        if ( empty( $sanitized['lead_source_mapping'] ) ) {
            $sanitized['lead_source_mapping'] = $this->get_default_lead_source_mapping();
        }

        // Handle academic_year_rows → rebuild academic_year_mapping
        if ( isset( $input['academic_year_rows'] ) && is_array( $input['academic_year_rows'] ) ) {
            $year_map = array();
            foreach ( $input['academic_year_rows'] as $row ) {
                $yr  = trim( sanitize_text_field( $row['year'] ?? '' ) );
                $yid = trim( sanitize_text_field( $row['id'] ?? '' ) );
                if ( $yr !== '' && $yid !== '' ) {
                    $year_map[ $yr ] = $yid;
                }
            }
            $sanitized['academic_year_mapping'] = ! empty( $year_map ) ? $year_map : ( $existing_settings['academic_year_mapping'] ?? array() );
        } else {
            $sanitized['academic_year_mapping'] = $existing_settings['academic_year_mapping'] ?? array();
        }

        $sanitized['default_academic_year'] = sanitize_text_field( $input['default_academic_year'] ?? ( $existing_settings['default_academic_year'] ?? '2026-27' ) );

        return $sanitized;
    }

    /**
     * Get default lead source mapping
     * 
     * @return array Default 29 lead sources
     */
    private function get_default_lead_source_mapping() {
        return array(
            'chatbot'              => '273',
            'whatsapp'             => '273',
            'website'              => '231',
            'email'                => '286',
            'google_search'        => '269',
            'google_display'       => '270',
            'google_call_ads'      => '275',
            'facebook'             => '272',
            'facebook_lead'        => '271',
            'instagram'            => '268',
            'linkedin'             => '267',
            'youtube'              => '446',
            'referral'             => '92',
            'friends'              => '92',
            'existing_parent'      => '232',
            'word_of_mouth'        => '448',
            'events'               => '234',
            'walkin'               => '250',
            'ebook'                => '274',
            'newsletter'           => '447',
            'newspaper'            => '84',
            'hoardings'            => '85',
            'leaflets'             => '86',
            'organic'              => '280',
            'others'               => '233',
            'unknown'              => '280',
            'default'              => '280',
        );
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts( $hook ) {
        if ( strpos( $hook, self::MENU_SLUG ) === false ) {
            return;
        }

        wp_enqueue_style( 'edubot-admin' );
        wp_enqueue_script( 'edubot-mcb-admin' );

        wp_localize_script( 'edubot-mcb-admin', 'EduBotMCB', array(
            'nonce' => wp_create_nonce( 'edubot_mcb_nonce' ),
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
        ) );
    }

    /**
     * Render settings page
     */
    public function render_page() {
        $integration = new EduBot_MyClassBoard_Integration();
        $settings = $integration->get_settings();
        $stats = $integration->get_sync_stats();
        ?>
        <div class="wrap">
            <h1>MyClassBoard Integration Settings</h1>
            <p class="description">Configure MyClassBoard (MCB) CRM integration to automatically sync EduBot enquiries.</p>

            <div class="edubot-container">
                <div class="edubot-main">
                    <!-- Tabs -->
                    <nav class="nav-tab-wrapper">
                        <a href="#tab-settings" class="nav-tab nav-tab-active">Settings</a>
                        <a href="#tab-status" class="nav-tab">Sync Status</a>
                        <a href="#tab-mapping" class="nav-tab">Lead Source Mapping</a>
                        <a href="#tab-academic" class="nav-tab">Academic Years</a>
                        <a href="#tab-logs" class="nav-tab">Sync Logs</a>
                    </nav>

                    <!-- TAB: Settings -->
                    <div id="tab-settings" class="tab-content active">
                        <form method="post" action="options.php" class="edubot-form">
                            <?php settings_fields( 'edubot_mcb_group' ); ?>

                            <table class="form-table edubot-form-table">
                                <!-- Enable Integration -->
                                <tr>
                                    <th scope="row">
                                        <label for="edubot_mcb_enabled">Enable MCB Integration</label>
                                    </th>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" name="edubot_mcb_settings[enabled]" 
                                                   id="edubot_mcb_enabled" value="1" 
                                                   <?php checked( $settings['enabled'], 1 ); ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <p class="description">Enable or disable MyClassBoard integration</p>
                                    </td>
                                </tr>

                                <!-- Organization ID -->
                                <tr>
                                    <th scope="row">
                                        <label for="edubot_mcb_org_id">Organization ID</label>
                                    </th>
                                    <td>
                                        <input type="text" name="edubot_mcb_settings[organization_id]" 
                                               id="edubot_mcb_org_id" 
                                               value="<?php echo esc_attr( $settings['organization_id'] ); ?>" 
                                               class="regular-text">
                                        <p class="description">Your MyClassBoard Organization ID (typically: 21)</p>
                                    </td>
                                </tr>

                                <!-- Branch ID -->
                                <tr>
                                    <th scope="row">
                                        <label for="edubot_mcb_branch_id">Branch ID</label>
                                    </th>
                                    <td>
                                        <input type="text" name="edubot_mcb_settings[branch_id]" 
                                               id="edubot_mcb_branch_id" 
                                               value="<?php echo esc_attr( $settings['branch_id'] ); ?>" 
                                               class="regular-text">
                                        <p class="description">Your MyClassBoard Branch ID (typically: 113)</p>
                                    </td>
                                </tr>

                                <!-- API Key (Hidden) -->
                                <tr>
                                    <th scope="row">
                                        <label for="edubot_mcb_api_key">API Key</label>
                                    </th>
                                    <td>
                                        <input type="password" name="edubot_mcb_settings[api_key]" 
                                               id="edubot_mcb_api_key" 
                                               value="<?php echo esc_attr( $settings['api_key'] ); ?>" 
                                               class="regular-text">
                                        <p class="description">Your MyClassBoard API key (kept secure)</p>
                                    </td>
                                </tr>

                                <!-- Access Token -->
                                <tr>
                                    <th scope="row">
                                        <label for="edubot_mcb_access_token">Access Token</label>
                                    </th>
                                    <td>
                                        <input type="password" name="edubot_mcb_settings[access_token]" 
                                               id="edubot_mcb_access_token" 
                                               value="<?php echo esc_attr( isset( $settings['access_token'] ) ? $settings['access_token'] : '' ); ?>" 
                                               class="regular-text">
                                        <p class="description">Your MyClassBoard API access token (kept secure)</p>
                                    </td>
                                </tr>

                                <!-- API URL Endpoint -->
                                <tr>
                                    <th scope="row">
                                        <label for="edubot_mcb_api_url">API URL Endpoint</label>
                                    </th>
                                    <td>
                                        <input type="url" name="edubot_mcb_settings[api_url]" 
                                               id="edubot_mcb_api_url" 
                                               value="<?php echo esc_attr( isset( $settings['api_url'] ) ? $settings['api_url'] : 'https://corp.myclassboard.com/api/EnquiryService/SaveEnquiryDetails' ); ?>" 
                                               class="regular-text" 
                                               placeholder="https://corp.myclassboard.com/api/EnquiryService/SaveEnquiryDetails">
                                        <p class="description">MyClassBoard API endpoint URL (default: https://corp.myclassboard.com/api/EnquiryService/SaveEnquiryDetails)</p>
                                    </td>
                                </tr>

                                <!-- Enable Sync -->
                                <tr>
                                    <th scope="row">
                                        <label for="edubot_mcb_sync_enabled">Enable Data Sync</label>
                                    </th>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" name="edubot_mcb_settings[sync_enabled]" 
                                                   id="edubot_mcb_sync_enabled" value="1" 
                                                   <?php checked( $settings['sync_enabled'], 1 ); ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <p class="description">Enable automatic synchronization of enquiries to MCB</p>
                                    </td>
                                </tr>

                                <!-- Sync New Enquiries -->
                                <tr>
                                    <th scope="row">
                                        <label for="edubot_mcb_sync_new">Sync New Enquiries</label>
                                    </th>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" name="edubot_mcb_settings[sync_new_enquiries]" 
                                                   id="edubot_mcb_sync_new" value="1" 
                                                   <?php checked( $settings['sync_new_enquiries'], 1 ); ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <p class="description">Automatically sync newly created enquiries to MCB</p>
                                    </td>
                                </tr>

                                <!-- Auto Sync -->
                                <tr>
                                    <th scope="row">
                                        <label for="edubot_mcb_auto_sync">Auto-Sync Mode</label>
                                    </th>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" name="edubot_mcb_settings[auto_sync]" 
                                                   id="edubot_mcb_auto_sync" value="1" 
                                                   <?php checked( $settings['auto_sync'], 1 ); ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <p class="description">Sync immediately on enquiry creation (vs. batch sync)</p>
                                    </td>
                                </tr>

                                <!-- Test Mode -->
                                <tr>
                                    <th scope="row">
                                        <label for="edubot_mcb_test_mode">Test Mode</label>
                                    </th>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" name="edubot_mcb_settings[test_mode]" 
                                                   id="edubot_mcb_test_mode" value="1" 
                                                   <?php checked( $settings['test_mode'], 1 ); ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <p class="description">Enable test mode (logs sync attempts without sending)</p>
                                    </td>
                                </tr>

                                <!-- Debug Mode -->
                                <tr>
                                    <th scope="row">
                                        <label for="edubot_mcb_debug_mode">Debug Mode</label>
                                    </th>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" name="edubot_mcb_settings[debug_mode]" 
                                                   id="edubot_mcb_debug_mode" value="1" 
                                                   <?php checked( isset( $settings['debug_mode'] ) ? $settings['debug_mode'] : 0, 1 ); ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <p class="description">Enable debug mode to show MCB preview button in applications list</p>
                                    </td>
                                </tr>

                                <!-- Timeout -->
                                <tr>
                                    <th scope="row">
                                        <label for="edubot_mcb_timeout">API Timeout (seconds)</label>
                                    </th>
                                    <td>
                                        <input type="number" name="edubot_mcb_settings[timeout]" 
                                               id="edubot_mcb_timeout" 
                                               value="<?php echo esc_attr( $settings['timeout'] ); ?>" 
                                               min="10" max="300" step="5" 
                                               class="small-text">
                                        <p class="description">Maximum time to wait for MCB API response</p>
                                    </td>
                                </tr>

                                <!-- Retry Attempts -->
                                <tr>
                                    <th scope="row">
                                        <label for="edubot_mcb_retry">Retry Attempts</label>
                                    </th>
                                    <td>
                                        <input type="number" name="edubot_mcb_settings[retry_attempts]" 
                                               id="edubot_mcb_retry" 
                                               value="<?php echo esc_attr( $settings['retry_attempts'] ); ?>" 
                                               min="1" max="10" step="1" 
                                               class="small-text">
                                        <p class="description">Number of times to retry failed API calls</p>
                                    </td>
                                </tr>
                            </table>

                            <?php submit_button( 'Save MCB Settings', 'primary' ); ?>
                        </form>
                    </div>

                    <!-- TAB: Sync Status -->
                    <div id="tab-status" class="tab-content">
                        <div class="edubot-card">
                            <h3>Synchronization Status</h3>
                            <div class="edubot-stats-grid">
                                <div class="stat-box">
                                    <div class="stat-number"><?php echo intval( $stats['total'] ); ?></div>
                                    <div class="stat-label">Total Syncs</div>
                                </div>
                                <div class="stat-box success">
                                    <div class="stat-number"><?php echo intval( $stats['successful'] ); ?></div>
                                    <div class="stat-label">Successful</div>
                                </div>
                                <div class="stat-box error">
                                    <div class="stat-number"><?php echo intval( $stats['failed'] ); ?></div>
                                    <div class="stat-label">Failed</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-number"><?php echo intval( $stats['today'] ); ?></div>
                                    <div class="stat-label">Today</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-number"><?php echo floatval( $stats['success_rate'] ); ?>%</div>
                                    <div class="stat-label">Success Rate</div>
                                </div>
                            </div>
                        </div>

                        <div class="edubot-card">
                            <h3>System Status</h3>
                            <table class="widefat striped">
                                <tbody>
                                    <tr>
                                        <td><strong>MCB Integration</strong></td>
                                        <td><?php echo $settings['enabled'] ? '✅ Enabled' : '❌ Disabled'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Sync Status</strong></td>
                                        <td><?php echo $settings['sync_enabled'] ? '✅ Active' : '⏸ Inactive'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Auto Sync</strong></td>
                                        <td><?php echo $settings['auto_sync'] ? '✅ On' : '⏸ Off'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Test Mode</strong></td>
                                        <td><?php echo $settings['test_mode'] ? '🧪 Enabled' : '✅ Disabled'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Organization ID</strong></td>
                                        <td><code><?php echo esc_html( $settings['organization_id'] ); ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Branch ID</strong></td>
                                        <td><code><?php echo esc_html( $settings['branch_id'] ); ?></code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB: Lead Source Mapping -->
                    <div id="tab-mapping" class="tab-content">
                        <div class="edubot-card">
                            <h3>Lead Source Mapping Configuration</h3>
                            <p class="description">Map EduBot lead sources to MyClassBoard QueryContactSourceID values</p>

                            <form method="post" action="options.php" class="edubot-form">
                                <?php settings_fields( 'edubot_mcb_group' ); ?>

                                <table class="form-table edubot-form-table edubot-mapping-table">
                                    <thead>
                                        <tr>
                                            <th>EduBot Source</th>
                                            <th>MCB Source ID</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // ====== COMPLETE LEAD SOURCE MAPPING (29 SOURCES) ======
                                        $sources = array(
                                            // ====== DIGITAL/CHATBOT (7) ======
                                            'chatbot'       => array('Chat Bot', 'Enquiries from chatbot conversations'),
                                            'whatsapp'      => array('WhatsApp', 'WhatsApp lead generation'),
                                            'website'       => array('Website', 'Direct website form submissions'),
                                            'email'         => array('Email', 'Email inquiries'),
                                            
                                            // ====== SEARCH & DISPLAY (3) ======
                                            'google_search' => array('Google Search', 'Google Search campaigns'),
                                            'google_display'=> array('Google Display', 'Google Display advertising'),
                                            'google_call_ads'=>array('Google Call Ads', 'Google Call Ads campaigns'),
                                            
                                            // ====== SOCIAL MEDIA (5) ======
                                            'facebook'      => array('Facebook', 'Facebook lead campaigns'),
                                            'facebook_lead' => array('Facebook Lead', 'Facebook Lead Ads'),
                                            'instagram'     => array('Instagram', 'Instagram lead campaigns'),
                                            'linkedin'      => array('LinkedIn', 'LinkedIn advertising'),
                                            'youtube'       => array('YouTube', 'YouTube advertising'),
                                            
                                            // ====== REFERRAL (4) ======
                                            'referral'      => array('Referral', 'Friends and referrals'),
                                            'friends'       => array('Friends (Alias)', 'Direct friend recommendations'),
                                            'existing_parent'=>array('Existing Parent', 'Current parent referrals'),
                                            'word_of_mouth' => array('Word of Mouth', 'Verbal recommendations'),
                                            
                                            // ====== EVENTS & WALK-IN (2) ======
                                            'events'        => array('Events', 'Campus events and open days'),
                                            'walkin'        => array('Walk-In', 'Direct walk-in inquiries'),
                                            
                                            // ====== CONTENT (2) ======
                                            'ebook'         => array('E-book', 'E-book and guide downloads'),
                                            'newsletter'    => array('Newsletter', 'Newsletter signups'),
                                            
                                            // ====== TRADITIONAL (3) ======
                                            'newspaper'     => array('News Paper', 'Newspaper advertisements'),
                                            'hoardings'     => array('Hoardings', 'Outdoor advertising'),
                                            'leaflets'      => array('Leaflets', 'Printed leaflets'),
                                            
                                            // ====== OTHER (2) ======
                                            'organic'       => array('Organic', 'Organic/untagged sources (default)'),
                                            'others'        => array('Others', 'Other unmapped sources'),
                                        );

                                        foreach ( $sources as $key => $data ): 
                                            list($label, $description) = $data;
                                            $value = $settings['lead_source_mapping'][$key] ?? '';
                                        ?>
                                            <tr>
                                                <td><strong><?php echo esc_html( $label ); ?></strong></td>
                                                <td>
                                                    <input type="text" 
                                                           name="edubot_mcb_settings[lead_source_mapping][<?php echo esc_attr( $key ); ?>]" 
                                                           value="<?php echo esc_attr( $value ); ?>" 
                                                           class="small-text"
                                                           placeholder="MCB Source ID">
                                                </td>
                                                <td class="description">
                                                    <?php echo esc_html( $description ); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <?php submit_button( 'Save Lead Source Mapping', 'primary' ); ?>
                            </form>

                            <div class="notice notice-info inline">
                                <p><strong>Note:</strong> These IDs are specific to your MyClassBoard configuration. Contact your MCB administrator if unsure.</p>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: Academic Years -->
                    <div id="tab-academic" class="tab-content">
                        <div class="edubot-card">
                            <h3>Academic Year Configuration</h3>
                            <p class="description">Map academic year labels to MyClassBoard <strong>AcademicYearID</strong> values. The active year is offered to parents in the chatbot and used when syncing enquiries to MCB.</p>

                            <form method="post" action="options.php" class="edubot-form">
                                <?php settings_fields( 'edubot_mcb_group' ); ?>

                                <!-- Default / Active Year -->
                                <table class="form-table edubot-form-table" style="margin-bottom:0">
                                    <tr>
                                        <th scope="row"><label for="edubot_mcb_default_year">Default Academic Year</label></th>
                                        <td>
                                            <?php
                                            $year_mapping    = $settings['academic_year_mapping'] ?? array();
                                            $default_year    = $settings['default_academic_year'] ?? '2026-27';
                                            $year_options    = array_keys( $year_mapping );
                                            if ( empty( $year_options ) ) $year_options = array( '2025-26', '2026-27', '2027-28' );
                                            ?>
                                            <select name="edubot_mcb_settings[default_academic_year]" id="edubot_mcb_default_year">
                                                <?php foreach ( $year_options as $yr ): ?>
                                                    <option value="<?php echo esc_attr( $yr ); ?>" <?php selected( $default_year, $yr ); ?>>
                                                        <?php echo esc_html( $yr ); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="description">This year is used as the fallback when no year is detected from the chatbot conversation.</p>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Year → MCB ID mapping table -->
                                <h4 style="margin:20px 0 6px;">Year → MCB AcademicYearID Mapping</h4>
                                <p class="description" style="margin-bottom:10px;">Each row maps an academic year string to the corresponding ID in MyClassBoard. Add new rows for future years.</p>

                                <table class="form-table edubot-form-table edubot-mapping-table" id="mcb-year-table">
                                    <thead>
                                        <tr>
                                            <th>Academic Year Label</th>
                                            <th>MCB AcademicYearID</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="mcb-year-tbody">
                                        <?php
                                        $default_mapping = array(
                                            '2020-21' => '11', '2021-22' => '12',
                                            '2022-23' => '13', '2023-24' => '14',
                                            '2024-25' => '15', '2025-26' => '16',
                                            '2026-27' => '17', '2027-28' => '18',
                                        );
                                        if ( empty( $year_mapping ) ) $year_mapping = $default_mapping;
                                        $row_i = 0;
                                        foreach ( $year_mapping as $yr => $yr_id ):
                                        ?>
                                        <tr class="mcb-year-row">
                                            <td>
                                                <input type="text"
                                                       name="edubot_mcb_settings[academic_year_rows][<?php echo $row_i; ?>][year]"
                                                       value="<?php echo esc_attr( $yr ); ?>"
                                                       class="regular-text"
                                                       placeholder="e.g. 2026-27">
                                            </td>
                                            <td>
                                                <input type="text"
                                                       name="edubot_mcb_settings[academic_year_rows][<?php echo $row_i; ?>][id]"
                                                       value="<?php echo esc_attr( $yr_id ); ?>"
                                                       class="small-text"
                                                       placeholder="MCB ID">
                                            </td>
                                            <td>
                                                <button type="button" class="button button-small mcb-remove-year" style="color:red;">✕ Remove</button>
                                            </td>
                                        </tr>
                                        <?php $row_i++; endforeach; ?>
                                    </tbody>
                                </table>

                                <p style="margin-top:10px;">
                                    <button type="button" class="button" id="mcb-add-year">+ Add Year</button>
                                </p>

                                <?php submit_button( 'Save Academic Year Settings', 'primary' ); ?>
                            </form>
                        </div>
                    </div>

                    <!-- TAB: Sync Logs -->
                    <div id="tab-logs" class="tab-content">
                        <div class="edubot-card">
                            <h3>Recent Synchronization Logs</h3>
                            <?php $logs = $integration->get_recent_sync_logs( 50 ); ?>
                            
                            <?php if ( ! empty( $logs ) ): ?>
                                <table class="widefat striped">
                                    <thead>
                                        <tr>
                                            <th>Enquiry #</th>
                                            <th>Student Name</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Error</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $logs as $log ): ?>
                                            <tr class="<?php echo $log['success'] ? 'sync-success' : 'sync-error'; ?>">
                                                <td><code><?php echo esc_html( $log['enquiry_number'] ); ?></code></td>
                                                <td><?php echo esc_html( $log['student_name'] ); ?></td>
                                                <td><?php echo esc_html( $log['email'] ); ?></td>
                                                <td>
                                                    <?php echo $log['success'] ? '✅ Synced' : '❌ Failed'; ?>
                                                </td>
                                                <td>
                                                    <?php echo $log['error_message'] ? esc_html( $log['error_message'] ) : '—'; ?>
                                                </td>
                                                <td><?php echo esc_html( $log['created_at'] ); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="notice notice-info inline"><strong>No sync logs yet.</strong> Logs will appear here after the first sync.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="edubot-sidebar">
                    <div class="edubot-card">
                        <h3>Integration Status</h3>
                        <div class="status-indicator <?php echo $settings['enabled'] && $settings['sync_enabled'] ? 'active' : 'inactive'; ?>">
                            <span class="dot"></span>
                            <?php echo $settings['enabled'] && $settings['sync_enabled'] ? 'Active' : 'Inactive'; ?>
                        </div>
                    </div>

                    <div class="edubot-card">
                        <h3>Quick Links</h3>
                        <ul class="quick-links">
                            <li><a href="#tab-settings">⚙️ Settings</a></li>
                            <li><a href="#tab-status">📊 Sync Status</a></li>
                            <li><a href="#tab-mapping">🔗 Lead Mapping</a></li>
                            <li><a href="#tab-logs">📋 View Logs</a></li>
                        </ul>
                    </div>

                    <div class="edubot-card">
                        <h3>Need Help?</h3>
                        <p>For support with MyClassBoard integration:</p>
                        <ul class="help-links">
                            <li><a href="https://myclassboard.com/support" target="_blank">MCB Support</a></li>
                            <li><a href="<?php echo admin_url( 'admin.php?page=edubot-documentation' ); ?>">EduBot Docs</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .edubot-container {
                display: flex;
                gap: 20px;
            }
            .edubot-main {
                flex: 1;
            }
            .edubot-sidebar {
                width: 300px;
            }
            .nav-tab-wrapper {
                background: #fff;
                border-bottom: 1px solid #ddd;
                padding: 0;
                margin: 20px 0 0 0;
            }
            .nav-tab {
                display: inline-block;
                padding: 12px 20px;
                margin: 0;
                border: 1px solid #ddd;
                border-bottom: none;
                margin-right: 2px;
                background: #f5f5f5;
                text-decoration: none;
                color: #333;
                cursor: pointer;
            }
            .nav-tab:hover {
                background: #efefef;
            }
            .nav-tab.nav-tab-active {
                background: #fff;
                border-top: 3px solid #0073aa;
                border-bottom-color: #fff;
            }
            .tab-content {
                display: none;
                background: #fff;
                border: 1px solid #ddd;
                border-top: none;
                padding: 20px;
            }
            .tab-content.active {
                display: block;
            }
            .edubot-card {
                background: #fff;
                border: 1px solid #ddd;
                padding: 20px;
                margin-bottom: 20px;
                border-radius: 5px;
            }
            .edubot-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
                margin: 15px 0;
            }
            .stat-box {
                background: #f5f5f5;
                padding: 20px;
                text-align: center;
                border-radius: 5px;
                border-left: 4px solid #0073aa;
            }
            .stat-box.success {
                border-left-color: #46b450;
            }
            .stat-box.error {
                border-left-color: #dc3545;
            }
            .stat-number {
                font-size: 24px;
                font-weight: bold;
                color: #333;
            }
            .stat-label {
                font-size: 12px;
                color: #666;
                margin-top: 5px;
            }
            .status-indicator {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px;
                background: #f5f5f5;
                border-radius: 5px;
                font-weight: bold;
            }
            .status-indicator.active {
                background: #d4edda;
                color: #155724;
            }
            .status-indicator.inactive {
                background: #f8d7da;
                color: #721c24;
            }
            .dot {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                display: inline-block;
                background: currentColor;
            }
            .switch {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 24px;
            }
            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: 0.4s;
                border-radius: 24px;
            }
            .slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: 0.4s;
                border-radius: 50%;
            }
            input:checked + .slider {
                background-color: #0073aa;
            }
            input:checked + .slider:before {
                transform: translateX(26px);
            }
            .quick-links, .help-links {
                list-style: none;
                padding: 0;
            }
            .quick-links li, .help-links li {
                padding: 8px 0;
                border-bottom: 1px solid #eee;
            }
            .quick-links li:last-child, .help-links li:last-child {
                border-bottom: none;
            }
            .quick-links a, .help-links a {
                text-decoration: none;
                color: #0073aa;
            }
            .sync-success {
                background: #f0f8f4;
            }
            .sync-error {
                background: #f8f3f0;
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                // Tab switching
                $('.nav-tab').click(function(e) {
                    e.preventDefault();
                    var target = $(this).attr('href');
                    $('.tab-content').removeClass('active');
                    $('.nav-tab').removeClass('nav-tab-active');
                    $(target).addClass('active');
                    $(this).addClass('nav-tab-active');
                });

                // ── Academic Year Tab ─────────────────────────────────────
                function reindexYearRows() {
                    $('#mcb-year-tbody .mcb-year-row').each(function(i) {
                        $(this).find('input').each(function() {
                            var name = $(this).attr('name');
                            if (name) {
                                $(this).attr('name', name.replace(/\[\d+\]/, '[' + i + ']'));
                            }
                        });
                    });
                    syncDefaultYearDropdown();
                }

                function syncDefaultYearDropdown() {
                    var current = $('#edubot_mcb_default_year').val();
                    var options = '';
                    $('#mcb-year-tbody .mcb-year-row').each(function() {
                        var yr = $(this).find('input:first').val().trim();
                        if (yr) {
                            var sel = (yr === current) ? ' selected' : '';
                            options += '<option value="' + yr + '"' + sel + '>' + yr + '</option>';
                        }
                    });
                    $('#edubot_mcb_default_year').html(options);
                }

                // Add new row
                $('#mcb-add-year').click(function() {
                    var count = $('#mcb-year-tbody .mcb-year-row').length;
                    var row = '<tr class="mcb-year-row">' +
                        '<td><input type="text" name="edubot_mcb_settings[academic_year_rows][' + count + '][year]" value="" class="regular-text" placeholder="e.g. 2028-29"></td>' +
                        '<td><input type="text" name="edubot_mcb_settings[academic_year_rows][' + count + '][id]" value="" class="small-text" placeholder="MCB ID"></td>' +
                        '<td><button type="button" class="button button-small mcb-remove-year" style="color:red;">✕ Remove</button></td>' +
                        '</tr>';
                    $('#mcb-year-tbody').append(row);
                    reindexYearRows();
                });

                // Remove row
                $(document).on('click', '.mcb-remove-year', function() {
                    if ($('#mcb-year-tbody .mcb-year-row').length <= 1) {
                        alert('You must keep at least one academic year.');
                        return;
                    }
                    $(this).closest('tr').remove();
                    reindexYearRows();
                });

                // Also sync dropdown when user types a year label
                $(document).on('input', '#mcb-year-tbody input:first-child', syncDefaultYearDropdown);
            });
        </script>
        <?php
    }
}

// Initialize if not already done
if ( ! class_exists( 'EduBot_MCB_Settings_Page' ) ) {
    // Will be instantiated in main plugin file
}
