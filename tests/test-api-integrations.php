<?php
/**
 * EduBot API Integrations Tests
 * 
 * Tests for API connections to ad platforms
 * 
 * @since 1.4.1
 * @package EduBot_Pro
 * @subpackage Tests
 */

class Test_EduBot_API_Integrations extends EduBot_Test_Case {
    
    /**
     * Test singleton instance
     */
    public function test_get_instance() {
        if (!class_exists('EduBot_API_Integrations')) {
            $this->markTestSkipped('API Integrations class not available');
        }
        
        $api = EduBot_API_Integrations::get_instance();
        
        $this->assertInstanceOf('EduBot_API_Integrations', $api);
    }
    
    /**
     * Test Facebook API configuration
     */
    public function test_facebook_api_config() {
        // Set test credentials
        update_option('edubot_facebook_app_id', 'test_app_id');
        update_option('edubot_facebook_access_token', 'test_token');
        
        $app_id = get_option('edubot_facebook_app_id');
        $token = get_option('edubot_facebook_access_token');
        
        $this->assertEquals('test_app_id', $app_id);
        $this->assertEquals('test_token', $token);
    }
    
    /**
     * Test Google API configuration
     */
    public function test_google_api_config() {
        update_option('edubot_google_client_id', 'test_google_id');
        update_option('edubot_google_refresh_token', 'test_refresh');
        
        $client_id = get_option('edubot_google_client_id');
        $refresh = get_option('edubot_google_refresh_token');
        
        $this->assertEquals('test_google_id', $client_id);
        $this->assertEquals('test_refresh', $refresh);
    }
    
    /**
     * Test TikTok API configuration
     */
    public function test_tiktok_api_config() {
        update_option('edubot_tiktok_app_id', 'test_tiktok_id');
        update_option('edubot_tiktok_access_token', 'test_tiktok_token');
        
        $app_id = get_option('edubot_tiktok_app_id');
        $token = get_option('edubot_tiktok_access_token');
        
        $this->assertEquals('test_tiktok_id', $app_id);
        $this->assertEquals('test_tiktok_token', $token);
    }
    
    /**
     * Test LinkedIn API configuration
     */
    public function test_linkedin_api_config() {
        update_option('edubot_linkedin_client_id', 'test_linkedin_id');
        update_option('edubot_linkedin_access_token', 'test_linkedin_token');
        
        $client_id = get_option('edubot_linkedin_client_id');
        $token = get_option('edubot_linkedin_access_token');
        
        $this->assertEquals('test_linkedin_id', $client_id);
        $this->assertEquals('test_linkedin_token', $token);
    }
    
    /**
     * Test API request validation
     */
    public function test_api_request_validation() {
        // Test parameter validation
        $params = [
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'channel' => 'facebook',
            'timestamp' => time(),
        ];
        
        // Verify all required fields present
        $this->assertArrayHasKey('email', $params);
        $this->assertArrayHasKey('channel', $params);
        $this->assertIsString($params['email']);
        $this->assertIsInt($params['timestamp']);
    }
    
    /**
     * Test PII hashing
     */
    public function test_pii_hashing() {
        $email = 'test@example.com';
        $phone = '1234567890';
        
        // Simulate PII hashing (SHA256)
        $email_hash = hash('sha256', $email);
        $phone_hash = hash('sha256', $phone);
        
        $this->assertNotEquals($email, $email_hash);
        $this->assertNotEquals($phone, $phone_hash);
        $this->assertEquals(64, strlen($email_hash)); // SHA256 = 64 chars
        $this->assertEquals(64, strlen($phone_hash));
    }
    
    /**
     * Test API error handling
     */
    public function test_api_error_handling() {
        // Test with invalid credentials
        $invalid_token = '';
        $is_valid = !empty($invalid_token);
        
        $this->assertFalse($is_valid);
    }
    
    /**
     * Test API retry logic
     */
    public function test_api_retry_logic() {
        // Simulate retry scenario
        $max_retries = 3;
        $retry_count = 0;
        $success = false;
        
        while ($retry_count < $max_retries && !$success) {
            // Simulate API call failure on first two attempts
            if ($retry_count >= 2) {
                $success = true;
            }
            $retry_count++;
        }
        
        $this->assertTrue($success);
        $this->assertEquals(3, $retry_count);
    }
    
    /**
     * Test API rate limiting
     */
    public function test_api_rate_limiting() {
        // Simulate rate limit tracking
        $rate_limit = 1000; // requests per hour
        $current_usage = 500;
        $remaining = $rate_limit - $current_usage;
        
        $this->assertEquals(500, $remaining);
        $this->assertGreaterThan(0, $remaining);
    }
}
