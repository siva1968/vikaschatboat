<?php
/**
 * AI Validator Admin Page Registration
 * 
 * Registers the AI Validator settings page in WordPress admin menu
 * under EduBot Pro → AI Validator Settings
 * 
 * @package EduBot_Pro
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EduBot_AI_Admin_Page {

    /**
     * Initialize admin page
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_page' ), 20 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Register admin menu page
     */
    public function register_admin_page() {
        add_submenu_page(
            'edubot-pro',                                   // Parent menu slug
            __( 'AI Validator Settings', 'edubot-pro' ),   // Page title
            __( 'AI Validator', 'edubot-pro' ),            // Menu title
            'manage_options',                              // Capability
            'edubot-ai-validator-settings',                // Menu slug
            array( $this, 'render_settings_page' )         // Callback
        );
    }

    /**
     * Register WordPress settings
     */
    public function register_settings() {
        register_setting(
            'edubot_ai_validator_settings',                // Option group
            'edubot_ai_validator_settings',                // Option name
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'show_in_rest'      => false,
            )
        );

        // Add settings section
        add_settings_section(
            'edubot_ai_validator_section',
            __( '🤖 AI Validator Configuration', 'edubot-pro' ),
            array( $this, 'section_callback' ),
            'edubot_ai_validator_settings'
        );
    }

    /**
     * Section callback
     */
    public function section_callback() {
        echo '<p>' . esc_html__( 'Configure AI model for intelligent input validation', 'edubot-pro' ) . '</p>';
    }

    /**
     * Sanitize settings
     * 
     * @param array $settings Settings to sanitize
     * @return array Sanitized settings
     */
    public function sanitize_settings( $settings ) {
        if ( ! is_array( $settings ) ) {
            return array();
        }

        global $edubot_ai_validator;
        if ( isset( $edubot_ai_validator ) ) {
            return $edubot_ai_validator->update_settings( $settings );
        }

        return $settings;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'edubot-pro' ) );
        }

        // Include settings page template
        include EDUBOT_PRO_PLUGIN_PATH . 'includes/views/admin-ai-validator-settings.php';
    }
}

// Initialize admin page registration
new EduBot_AI_Admin_Page();
