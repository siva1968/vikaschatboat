<?php
/**
 * EduBot Integration Tests
 * 
 * End-to-end tests for system workflows
 * 
 * @since 1.4.1
 * @package EduBot_Pro
 * @subpackage Tests
 */

class Test_EduBot_Integration extends EduBot_Test_Case {
    
    /**
     * Test complete conversion flow
     */
    public function test_complete_conversion_flow() {
        global $wpdb;
        
        if (!class_exists('EduBot_Attribution_Tracker')) {
            $this->markTestSkipped('Attribution classes not available');
        }
        
        $tracker = EduBot_Attribution_Tracker::get_instance($this->logger);
        $email = 'flow@example.com';
        
        // Step 1: Track session
        $session_result = $tracker->track_user_session(
            $email,
            'facebook',
            'campaign_1',
            'facebook'
        );
        $this->assertTrue($session_result || is_int($session_result));
        
        // Step 2: Track conversion
        $conversion_result = $tracker->track_conversion(
            $email,
            'enquiry_form',
            'completed'
        );
        $this->assertTrue($conversion_result || is_int($conversion_result));
        
        // Step 3: Verify in database
        $table = $wpdb->prefix . 'edubot_conversions';
        $conversion = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE user_email = %s", $email)
        );
        
        $this->assertNotNull($conversion);
    }
    
    /**
     * Test multi-touch attribution flow
     */
    public function test_multi_touch_attribution_flow() {
        global $wpdb;
        
        if (!class_exists('EduBot_Attribution_Tracker')) {
            $this->markTestSkipped('Attribution classes not available');
        }
        
        $tracker = EduBot_Attribution_Tracker::get_instance($this->logger);
        $email = 'multitouch@example.com';
        
        // User journey with multiple touchpoints
        $channels = [
            ['google', 'paid_search', 'google'],
            ['facebook', 'retargeting', 'facebook'],
            ['direct', 'direct_visit', 'direct'],
        ];
        
        foreach ($channels as $channel_data) {
            $tracker->track_user_session($email, ...$channel_data);
        }
        
        // Track final conversion
        $tracker->track_conversion($email, 'enquiry_submitted', 'completed');
        
        // Verify attribution data
        $attr_table = $wpdb->prefix . 'edubot_attributions';
        $attributions = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $attr_table WHERE user_email = %s", $email)
        );
        
        $this->assertGreaterThan(0, count($attributions));
    }
    
    /**
     * Test dashboard data retrieval flow
     */
    public function test_dashboard_data_retrieval_flow() {
        if (!class_exists('EduBot_Admin_Dashboard')) {
            $this->markTestSkipped('Dashboard class not available');
        }
        
        $dashboard = new EduBot_Admin_Dashboard($this->logger);
        
        // Get KPIs
        $kpis = $dashboard->get_kpis('month');
        $this->assertIsArray($kpis);
        
        // Get sources
        $sources = $dashboard->get_enquiries_by_source('month');
        $this->assertIsArray($sources);
        
        // Get campaigns
        $campaigns = $dashboard->get_enquiries_by_campaign('month');
        $this->assertIsArray($campaigns);
    }
    
    /**
     * Test report generation and email flow
     */
    public function test_report_generation_flow() {
        if (!class_exists('EduBot_Performance_Reports')) {
            $this->markTestSkipped('Performance Reports class not available');
        }
        
        $reports = EduBot_Performance_Reports::get_instance($this->logger);
        
        // Set recipients
        update_option('edubot_report_recipients', ['admin@test.com']);
        
        // Enable daily reports
        update_option('edubot_daily_report_enabled', true);
        
        // Generate report
        if (method_exists($reports, 'generate_daily_report')) {
            $report = $reports->generate_daily_report();
            $this->assertIsArray($report);
        }
    }
    
    /**
     * Test API credentials management flow
     */
    public function test_api_credentials_flow() {
        // Set Facebook credentials
        update_option('edubot_facebook_app_id', 'test_app_id');
        update_option('edubot_facebook_access_token', 'test_token');
        
        // Verify retrieval
        $app_id = get_option('edubot_facebook_app_id');
        $token = get_option('edubot_facebook_access_token');
        
        $this->assertEquals('test_app_id', $app_id);
        $this->assertEquals('test_token', $token);
        
        // Update credentials
        update_option('edubot_facebook_app_id', 'new_app_id');
        
        $updated_id = get_option('edubot_facebook_app_id');
        $this->assertEquals('new_app_id', $updated_id);
    }
    
    /**
     * Test admin access flow
     */
    public function test_admin_access_flow() {
        // Create admin user
        $admin_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);
        
        // Verify admin capabilities
        $this->assertTrue(current_user_can('manage_options'));
        
        // Verify can access admin pages
        $this->assertTrue(current_user_can('manage_options'));
    }
    
    /**
     * Test data persistence across operations
     */
    public function test_data_persistence() {
        global $wpdb;
        
        if (!class_exists('EduBot_Attribution_Tracker')) {
            $this->markTestSkipped('Attribution classes not available');
        }
        
        $tracker = EduBot_Attribution_Tracker::get_instance($this->logger);
        
        // Track session
        $tracker->track_user_session('persist@example.com', 'google', 'camp1', 'google');
        
        // Verify persisted
        $table = $wpdb->prefix . 'edubot_attribution_sessions';
        $session = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE user_email = %s", 'persist@example.com')
        );
        
        $this->assertNotNull($session);
        
        // Retrieve same data
        $retrieved = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE user_email = %s", 'persist@example.com')
        );
        
        $this->assertEquals($session->id, $retrieved->id);
    }
}
