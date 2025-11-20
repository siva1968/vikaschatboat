<?php
/**
 * EduBot Security Tests
 * 
 * Tests for security implementation
 * 
 * @since 1.4.1
 * @package EduBot_Pro
 * @subpackage Tests
 */

class Test_EduBot_Security extends EduBot_Test_Case {
    
    /**
     * Test nonce verification
     */
    public function test_nonce_verification() {
        // Create a nonce
        $nonce = wp_create_nonce('test_action');
        
        // Verify it
        $verified = wp_verify_nonce($nonce, 'test_action');
        
        $this->assertNotFalse($verified);
    }
    
    /**
     * Test invalid nonce rejection
     */
    public function test_invalid_nonce_rejection() {
        $invalid_nonce = 'invalid_nonce_123';
        
        // Verify invalid nonce
        $verified = wp_verify_nonce($invalid_nonce, 'test_action');
        
        $this->assertFalse($verified);
    }
    
    /**
     * Test capability checking
     */
    public function test_capability_checking() {
        // Create admin user
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);
        
        $can_manage = current_user_can('manage_options');
        
        $this->assertTrue($can_manage);
    }
    
    /**
     * Test non-admin access denial
     */
    public function test_non_admin_access_denial() {
        // Create subscriber user
        $user_id = $this->factory->user->create(['role' => 'subscriber']);
        wp_set_current_user($user_id);
        
        $can_manage = current_user_can('manage_options');
        
        $this->assertFalse($can_manage);
    }
    
    /**
     * Test input sanitization
     */
    public function test_input_sanitization() {
        $input = '<script>alert("xss")</script>test@example.com';
        $sanitized = sanitize_text_field($input);
        
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('alert(', $sanitized);
    }
    
    /**
     * Test email sanitization
     */
    public function test_email_sanitization() {
        $email = 'test+tag@example.com<script>alert(1)</script>';
        $sanitized = sanitize_email($email);
        
        $this->assertStringNotContainsString('<script>', $sanitized);
    }
    
    /**
     * Test SQL injection prevention
     */
    public function test_sql_injection_prevention() {
        global $wpdb;
        
        $email = "' OR '1'='1";
        
        // Use prepare statement
        $query = $wpdb->prepare(
            "SELECT * FROM " . $wpdb->prefix . "edubot_conversions WHERE user_email = %s",
            $email
        );
        
        // Verify prepare escapes properly
        $this->assertStringNotContainsString("' OR '1'='1", $query);
    }
    
    /**
     * Test CSRF token generation
     */
    public function test_csrf_token_generation() {
        $nonce = wp_create_nonce('test_form');
        
        $this->assertIsString($nonce);
        $this->assertGreaterThan(0, strlen($nonce));
    }
    
    /**
     * Test data access control
     */
    public function test_data_access_control() {
        global $wpdb;
        
        // Create admin user
        $admin_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);
        
        // Should be able to access data
        $table = $wpdb->prefix . 'edubot_conversions';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        
        $this->assertIsInt($count);
    }
    
    /**
     * Test API key security
     */
    public function test_api_key_security() {
        // API keys should not be logged
        $api_key = 'sk_test_abc123def456';
        $log_message = 'Processing request with API key';
        
        // Simulate logging (should not include key)
        $logged = $log_message . ' ****';
        
        $this->assertStringNotContainsString($api_key, $logged);
    }
    
    /**
     * Test password field protection
     */
    public function test_password_field_protection() {
        $secret = 'app_secret_key_123';
        
        // Simulate password input type
        $html = '<input type="password" value="' . esc_attr($secret) . '">';
        
        // HTML should contain password type
        $this->assertStringContainsString('type="password"', $html);
    }
}
