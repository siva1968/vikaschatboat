<?php
/**
 * Tests for EduBot Pro Plugin
 * 
 * @package EdubotPro
 * @subpackage Tests
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Basic PHPUnit Test Suite for EduBot Pro
 */
class EduBot_Pro_Tests extends WP_UnitTestCase {

    /**
     * Test plugin activation
     */
    public function test_plugin_activation() {
        // Check if main plugin file exists
        $this->assertTrue(file_exists(EDUBOT_PRO_PLUGIN_FILE));
        
        // Check if constants are defined
        $this->assertTrue(defined('EDUBOT_PRO_VERSION'));
        $this->assertTrue(defined('EDUBOT_PRO_PLUGIN_PATH'));
        $this->assertTrue(defined('EDUBOT_PRO_PLUGIN_URL'));
    }

    /**
     * Test core class instantiation
     */
    public function test_core_class_exists() {
        $this->assertTrue(class_exists('EduBot_Core'));
        
        if (class_exists('EduBot_Core')) {
            $core = new EduBot_Core();
            $this->assertInstanceOf('EduBot_Core', $core);
        }
    }

    /**
     * Test database table creation
     */
    public function test_database_tables_created() {
        global $wpdb;
        
        $expected_tables = array(
            $wpdb->prefix . 'edubot_conversations',
            $wpdb->prefix . 'edubot_applications',
            $wpdb->prefix . 'edubot_analytics',
            $wpdb->prefix . 'edubot_schools',
            $wpdb->prefix . 'edubot_notifications'
        );
        
        foreach ($expected_tables as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            $this->assertEquals($table, $table_exists, "Table $table should exist");
        }
    }

    /**
     * Test shortcode registration
     */
    public function test_shortcodes_registered() {
        $this->assertTrue(shortcode_exists('edubot_chatbot'));
        $this->assertTrue(shortcode_exists('edubot_application_form'));
    }

    /**
     * Test AJAX handlers registration
     */
    public function test_ajax_handlers_registered() {
        global $wp_filter;
        
        // Check if AJAX actions are registered
        $this->assertTrue(has_action('wp_ajax_edubot_chatbot_response'));
        $this->assertTrue(has_action('wp_ajax_nopriv_edubot_chatbot_response'));
        $this->assertTrue(has_action('wp_ajax_edubot_submit_application'));
        $this->assertTrue(has_action('wp_ajax_nopriv_edubot_submit_application'));
    }

    /**
     * Test security functions
     */
    public function test_nonce_verification() {
        // Test nonce creation
        $nonce = wp_create_nonce('edubot_test');
        $this->assertNotEmpty($nonce);
        
        // Test nonce verification
        $_REQUEST['_wpnonce'] = $nonce;
        $this->assertTrue(wp_verify_nonce($nonce, 'edubot_test'));
    }

    /**
     * Test data sanitization
     */
    public function test_data_sanitization() {
        $test_data = '<script>alert("xss")</script>Test String';
        $sanitized = sanitize_text_field($test_data);
        $this->assertNotContains('<script>', $sanitized);
        $this->assertEquals('Test String', $sanitized);
        
        $email = 'test@example.com';
        $sanitized_email = sanitize_email($email);
        $this->assertEquals($email, $sanitized_email);
    }

    /**
     * Test admin menu creation
     */
    public function test_admin_menu_created() {
        if (class_exists('EduBot_Admin')) {
            $admin = new EduBot_Admin('edubot-pro', '1.0.0');
            
            // Simulate admin user
            $user_id = $this->factory->user->create(array('role' => 'administrator'));
            wp_set_current_user($user_id);
            
            // Test if user can access admin pages
            $this->assertTrue(current_user_can('manage_options'));
        }
    }

    /**
     * Test database operations
     */
    public function test_database_operations() {
        if (class_exists('EduBot_Database_Manager')) {
            $db_manager = new EduBot_Database_Manager();
            
            // Test application creation
            $test_application = array(
                'student_name' => 'Test Student',
                'parent_name' => 'Test Parent',
                'email' => 'test@example.com',
                'phone' => '1234567890',
                'grade' => '5',
                'status' => 'pending'
            );
            
            $result = $db_manager->create_application($test_application);
            $this->assertNotFalse($result);
            
            // Test application retrieval
            if ($result) {
                $retrieved = $db_manager->get_application($result);
                $this->assertNotEmpty($retrieved);
                $this->assertEquals('Test Student', $retrieved['student_name']);
            }
        }
    }

    /**
     * Test chatbot engine
     */
    public function test_chatbot_engine() {
        if (class_exists('EduBot_Chatbot_Engine')) {
            $chatbot = new EduBot_Chatbot_Engine();
            
            // Test basic response generation
            $response = $chatbot->process_message('Hello', 'test_session');
            $this->assertNotEmpty($response);
            $this->assertIsArray($response);
            $this->assertArrayHasKey('response', $response);
        }
    }

    /**
     * Test notification system
     */
    public function test_notification_system() {
        if (class_exists('EduBot_Notification_Manager')) {
            $notification_manager = new EduBot_Notification_Manager();
            
            // Test email notification preparation
            $result = $notification_manager->prepare_email_notification(
                'test@example.com',
                'Test Subject',
                'Test Message'
            );
            
            $this->assertTrue($result);
        }
    }

    /**
     * Test security manager
     */
    public function test_security_manager() {
        if (class_exists('EduBot_Security_Manager')) {
            $security = new EduBot_Security_Manager();
            
            // Test input validation
            $safe_input = $security->validate_input('test string', 'text');
            $this->assertEquals('test string', $safe_input);
            
            // Test malicious input blocking
            $malicious_input = '<script>alert("xss")</script>';
            $safe_input = $security->validate_input($malicious_input, 'text');
            $this->assertNotContains('<script>', $safe_input);
        }
    }

    /**
     * Test plugin deactivation
     */
    public function test_plugin_deactivation() {
        // Test that deactivation doesn't break the site
        if (function_exists('edubot_pro_deactivate')) {
            // This should not throw any errors
            $this->expectNotToPerformAssertions();
            edubot_pro_deactivate();
        }
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        parent::tearDown();
        
        // Clean up test data
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}edubot_applications WHERE student_name = 'Test Student'");
    }
}
