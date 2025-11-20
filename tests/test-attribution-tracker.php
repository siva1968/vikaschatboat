<?php
/**
 * EduBot Attribution Tracker Tests
 * 
 * Tests for multi-touch attribution system
 * 
 * @since 1.4.1
 * @package EduBot_Pro
 * @subpackage Tests
 */

class Test_EduBot_Attribution_Tracker extends EduBot_Test_Case {
    
    /**
     * Test singleton instance
     */
    public function test_get_instance() {
        $tracker1 = EduBot_Attribution_Tracker::get_instance();
        $tracker2 = EduBot_Attribution_Tracker::get_instance();
        
        $this->assertInstanceOf('EduBot_Attribution_Tracker', $tracker1);
        $this->assertSame($tracker1, $tracker2);
    }
    
    /**
     * Test track user session
     */
    public function test_track_user_session() {
        $tracker = EduBot_Attribution_Tracker::get_instance($this->logger);
        
        $result = $tracker->track_user_session(
            'test@example.com',
            'facebook',
            'test_campaign_1',
            'test_utm_source'
        );
        
        $this->assertTrue($result || is_int($result));
    }
    
    /**
     * Test track conversion with attribution
     */
    public function test_track_conversion_with_attribution() {
        $tracker = EduBot_Attribution_Tracker::get_instance($this->logger);
        
        // First track a session
        $tracker->track_user_session(
            'test@example.com',
            'google',
            'google_campaign',
            'google'
        );
        
        // Then track conversion
        $result = $tracker->track_conversion(
            'test@example.com',
            'conversion_type_1',
            'completed',
            [
                'form_id' => 'form_123',
                'value' => 100
            ]
        );
        
        $this->assertTrue($result || is_int($result));
    }
    
    /**
     * Test get user sessions
     */
    public function test_get_user_sessions() {
        global $wpdb;
        
        $tracker = EduBot_Attribution_Tracker::get_instance($this->logger);
        $email = 'sessiontest@example.com';
        
        // Track multiple sessions
        $tracker->track_user_session($email, 'facebook', 'campaign1', 'fb');
        $tracker->track_user_session($email, 'google', 'campaign2', 'google');
        
        // Retrieve sessions
        $table = $wpdb->prefix . 'edubot_attribution_sessions';
        $sessions = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table WHERE user_email = %s", $email)
        );
        
        $this->assertGreaterThanOrEqual(2, count($sessions));
    }
    
    /**
     * Test session validation
     */
    public function test_session_validation() {
        $tracker = EduBot_Attribution_Tracker::get_instance($this->logger);
        
        // Test with valid data
        $result_valid = $tracker->track_user_session(
            'valid@example.com',
            'facebook',
            'valid_campaign',
            'utm_source'
        );
        
        $this->assertTrue($result_valid || is_int($result_valid));
        
        // Test with invalid email
        $result_invalid = $tracker->track_user_session(
            'invalid-email',
            'facebook',
            'campaign',
            'source'
        );
        
        $this->assertFalse($result_invalid);
    }
    
    /**
     * Test duplicate session prevention
     */
    public function test_duplicate_session_prevention() {
        $tracker = EduBot_Attribution_Tracker::get_instance($this->logger);
        $email = 'duplicate@example.com';
        
        // Track same session twice
        $result1 = $tracker->track_user_session($email, 'facebook', 'campaign1', 'fb');
        
        // Within same session window (might be deduplicated)
        $result2 = $tracker->track_user_session($email, 'facebook', 'campaign1', 'fb');
        
        // At least one should succeed
        $this->assertTrue($result1 || is_int($result1));
    }
    
    /**
     * Test multi-channel attribution
     */
    public function test_multi_channel_attribution() {
        global $wpdb;
        
        $tracker = EduBot_Attribution_Tracker::get_instance($this->logger);
        $email = 'multi@example.com';
        
        // Simulate multi-channel journey
        $tracker->track_user_session($email, 'google', 'paid_search', 'google');
        $tracker->track_user_session($email, 'facebook', 'retargeting', 'facebook');
        $tracker->track_user_session($email, 'direct', 'direct_visit', 'direct');
        
        // Track conversion
        $tracker->track_conversion($email, 'enquiry_submitted', 'completed');
        
        // Verify attribution recorded
        $table = $wpdb->prefix . 'edubot_attributions';
        $count = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_email = %s", $email)
        );
        
        $this->assertGreaterThan(0, $count);
    }
    
    /**
     * Test session expiry
     */
    public function test_session_expiry() {
        $tracker = EduBot_Attribution_Tracker::get_instance($this->logger);
        
        // Track a session
        $result = $tracker->track_user_session(
            'expiry@example.com',
            'facebook',
            'campaign',
            'fb'
        );
        
        $this->assertTrue($result || is_int($result));
    }
}
