<?php
/**
 * EduBot Pro Plugin Validation Script
 * Run this script to validate plugin functionality and compliance
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EduBot_Pro_Validator {
    
    private $errors = array();
    private $warnings = array();
    private $passes = array();
    
    public function __construct() {
        add_action('init', array($this, 'run_validation'));
    }
    
    /**
     * Run comprehensive plugin validation
     */
    public function run_validation() {
        if (!current_user_can('manage_options') || !isset($_GET['edubot_validate'])) {
            return;
        }
        
        $this->validate_plugin_structure();
        $this->validate_security_measures();
        $this->validate_database_setup();
        $this->validate_class_methods();
        $this->validate_hooks_and_actions();
        $this->validate_admin_interface();
        $this->validate_shortcodes();
        $this->validate_internationalization();
        
        $this->display_validation_results();
    }
    
    /**
     * Validate plugin file structure
     */
    private function validate_plugin_structure() {
        $required_files = array(
            'edubot-pro.php' => 'Main plugin file',
            'readme.txt' => 'WordPress.org readme file',
            'uninstall.php' => 'Uninstall cleanup script',
            'languages/edubot-pro.pot' => 'Translation template',
            'includes/class-edubot-core.php' => 'Core plugin class',
            'includes/class-edubot-activator.php' => 'Activation handler',
            'includes/class-edubot-deactivator.php' => 'Deactivation handler',
            'admin/class-edubot-admin.php' => 'Admin interface class',
            'admin/views/dashboard.php' => 'Admin dashboard view',
            'admin/views/school-settings.php' => 'School settings view',
            'admin/views/api-integrations.php' => 'API settings view',
            'admin/views/analytics.php' => 'Analytics view',
            'admin/views/applications-list.php' => 'Applications list view',
            'public/class-edubot-public.php' => 'Public interface class',
            'assets/css/frontend.css' => 'Frontend styles',
            'assets/js/frontend.js' => 'Frontend scripts'
        );
        
        foreach ($required_files as $file => $description) {
            $file_path = EDUBOT_PRO_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                $this->passes[] = "✓ $description found: $file";
            } else {
                $this->errors[] = "✗ Missing $description: $file";
            }
        }
    }
    
    /**
     * Validate security measures
     */
    private function validate_security_measures() {
        // Check for direct access prevention
        $php_files = glob(EDUBOT_PRO_PLUGIN_PATH . '**/*.php');
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            if (strpos($content, "defined('ABSPATH')") !== false || strpos($content, 'ABSPATH') !== false) {
                $this->passes[] = "✓ Direct access protection in: " . basename($file);
            } else {
                $this->warnings[] = "⚠ Missing direct access protection in: " . basename($file);
            }
        }
        
        // Check for nonce usage
        if (class_exists('EduBot_Admin')) {
            $reflection = new ReflectionClass('EduBot_Admin');
            $methods = $reflection->getMethods();
            $nonce_found = false;
            
            foreach ($methods as $method) {
                $method_content = $reflection->getMethod($method->getName())->getDocComment();
                if (strpos($method_content, 'nonce') !== false) {
                    $nonce_found = true;
                    break;
                }
            }
            
            if ($nonce_found) {
                $this->passes[] = "✓ Nonce verification implemented";
            } else {
                $this->warnings[] = "⚠ Nonce verification not clearly documented";
            }
        }
    }
    
    /**
     * Validate database setup
     */
    private function validate_database_setup() {
        global $wpdb;
        
        $required_tables = array(
            'edubot_conversations' => 'Chat conversations',
            'edubot_applications' => 'Student applications',
            'edubot_analytics' => 'Analytics data',
            'edubot_schools' => 'School configurations',
            'edubot_notifications' => 'Notification logs'
        );
        
        foreach ($required_tables as $table => $description) {
            $full_table_name = $wpdb->prefix . $table;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
            
            if ($table_exists) {
                $this->passes[] = "✓ Database table exists: $description ($full_table_name)";
            } else {
                $this->errors[] = "✗ Missing database table: $description ($full_table_name)";
            }
        }
    }
    
    /**
     * Validate required class methods
     */
    private function validate_class_methods() {
        $class_methods = array(
            'EduBot_Database_Manager' => array('cleanup_old_analytics', 'create_backup'),
            'EduBot_Notification_Manager' => array('send_scheduled_followups'),
            'EduBot_School_Config' => array('get_school_id', 'get_config'),
            'EduBot_Core' => array('run', 'load_dependencies'),
            'EduBot_Admin' => array('display_admin_page', 'enqueue_styles')
        );
        
        foreach ($class_methods as $class_name => $methods) {
            if (class_exists($class_name)) {
                $this->passes[] = "✓ Class exists: $class_name";
                
                foreach ($methods as $method) {
                    if (method_exists($class_name, $method)) {
                        $this->passes[] = "✓ Method exists: $class_name::$method";
                    } else {
                        $this->errors[] = "✗ Missing method: $class_name::$method";
                    }
                }
            } else {
                $this->errors[] = "✗ Missing class: $class_name";
            }
        }
    }
    
    /**
     * Validate hooks and actions
     */
    private function validate_hooks_and_actions() {
        $required_hooks = array(
            'wp_ajax_edubot_chatbot_response' => 'AJAX chatbot response',
            'wp_ajax_nopriv_edubot_chatbot_response' => 'AJAX chatbot response (public)',
            'wp_ajax_edubot_submit_application' => 'AJAX application submission',
            'wp_ajax_nopriv_edubot_submit_application' => 'AJAX application submission (public)'
        );
        
        foreach ($required_hooks as $hook => $description) {
            if (has_action($hook)) {
                $this->passes[] = "✓ Hook registered: $description ($hook)";
            } else {
                $this->errors[] = "✗ Missing hook: $description ($hook)";
            }
        }
    }
    
    /**
     * Validate admin interface
     */
    private function validate_admin_interface() {
        if (is_admin()) {
            // Check if admin menu is added
            global $menu, $submenu;
            $menu_found = false;
            
            foreach ($menu as $menu_item) {
                if (isset($menu_item[2]) && strpos($menu_item[2], 'edubot-pro') !== false) {
                    $menu_found = true;
                    break;
                }
            }
            
            if ($menu_found) {
                $this->passes[] = "✓ Admin menu registered";
            } else {
                $this->errors[] = "✗ Admin menu not found";
            }
        }
    }
    
    /**
     * Validate shortcodes
     */
    private function validate_shortcodes() {
        $required_shortcodes = array(
            'edubot_chatbot' => 'Chatbot widget',
            'edubot_application_form' => 'Application form'
        );
        
        foreach ($required_shortcodes as $shortcode => $description) {
            if (shortcode_exists($shortcode)) {
                $this->passes[] = "✓ Shortcode registered: $description ([$shortcode])";
            } else {
                $this->errors[] = "✗ Missing shortcode: $description ([$shortcode])";
            }
        }
    }
    
    /**
     * Validate internationalization
     */
    private function validate_internationalization() {
        // Check if textdomain is loaded
        if (is_textdomain_loaded('edubot-pro')) {
            $this->passes[] = "✓ Text domain loaded: edubot-pro";
        } else {
            $this->warnings[] = "⚠ Text domain not loaded: edubot-pro";
        }
        
        // Check for translation files
        $pot_file = EDUBOT_PRO_PLUGIN_PATH . 'languages/edubot-pro.pot';
        if (file_exists($pot_file)) {
            $this->passes[] = "✓ Translation template exists";
        } else {
            $this->errors[] = "✗ Missing translation template";
        }
    }
    
    /**
     * Display validation results
     */
    private function display_validation_results() {
        echo '<div class="wrap">';
        echo '<h1>EduBot Pro Validation Results</h1>';
        
        // Summary
        echo '<div class="edubot-validation-summary">';
        echo '<h2>Summary</h2>';
        echo '<p><strong>Passes:</strong> ' . count($this->passes) . '</p>';
        echo '<p><strong>Warnings:</strong> ' . count($this->warnings) . '</p>';
        echo '<p><strong>Errors:</strong> ' . count($this->errors) . '</p>';
        echo '</div>';
        
        // Passes
        if (!empty($this->passes)) {
            echo '<div class="notice notice-success">';
            echo '<h3>✓ Validation Passes</h3>';
            echo '<ul>';
            foreach ($this->passes as $pass) {
                echo '<li>' . esc_html($pass) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        // Warnings
        if (!empty($this->warnings)) {
            echo '<div class="notice notice-warning">';
            echo '<h3>⚠ Warnings</h3>';
            echo '<ul>';
            foreach ($this->warnings as $warning) {
                echo '<li>' . esc_html($warning) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        // Errors
        if (!empty($this->errors)) {
            echo '<div class="notice notice-error">';
            echo '<h3>✗ Errors</h3>';
            echo '<ul>';
            foreach ($this->errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        // WordPress Standards Compliance
        $compliance_score = count($this->passes) / (count($this->passes) + count($this->warnings) + count($this->errors)) * 100;
        
        echo '<div class="edubot-compliance-score">';
        echo '<h2>WordPress Standards Compliance</h2>';
        echo '<div style="background: ' . ($compliance_score >= 90 ? '#46b450' : ($compliance_score >= 75 ? '#ffb900' : '#dc3232')) . '; color: white; padding: 20px; text-align: center; font-size: 18px; border-radius: 4px;">';
        echo round($compliance_score, 1) . '% Compliant';
        echo '</div>';
        
        if ($compliance_score >= 90) {
            echo '<p style="color: #46b450; font-weight: bold;">✓ Excellent! Plugin meets WordPress standards.</p>';
        } elseif ($compliance_score >= 75) {
            echo '<p style="color: #ffb900; font-weight: bold;">⚠ Good, but some improvements needed.</p>';
        } else {
            echo '<p style="color: #dc3232; font-weight: bold;">✗ Critical issues need to be addressed.</p>';
        }
        echo '</div>';
        
        echo '</div>';
        
        // Add some CSS
        echo '<style>
        .edubot-validation-summary {
            background: #f1f1f1;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .edubot-compliance-score {
            margin: 30px 0;
            text-align: center;
        }
        .notice ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .notice li {
            margin-bottom: 5px;
        }
        </style>';
        
        exit; // Stop execution after validation
    }
}

// Initialize validator
new EduBot_Pro_Validator();
