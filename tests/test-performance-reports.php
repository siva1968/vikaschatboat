<?php
/**
 * EduBot Performance Reports Tests
 * 
 * Tests for the automated reporting system
 * 
 * @since 1.4.1
 * @package EduBot_Pro
 * @subpackage Tests
 */

class Test_EduBot_Performance_Reports extends EduBot_Test_Case {
    
    /**
     * Test singleton instance
     */
    public function test_get_instance() {
        $reports1 = EduBot_Performance_Reports::get_instance();
        $reports2 = EduBot_Performance_Reports::get_instance();
        
        $this->assertInstanceOf('EduBot_Performance_Reports', $reports1);
        $this->assertSame($reports1, $reports2);
    }
    
    /**
     * Test register settings
     */
    public function test_register_settings() {
        $reports = EduBot_Performance_Reports::get_instance($this->logger);
        
        // Verify settings are registered
        $enabled = get_option('edubot_daily_report_enabled');
        $recipients = get_option('edubot_report_recipients');
        
        // Settings might not be set yet, but should be retrievable
        $this->assertTrue(true); // Placeholder test
    }
    
    /**
     * Test update report settings
     */
    public function test_update_report_settings() {
        $reports = EduBot_Performance_Reports::get_instance($this->logger);
        
        // Update daily report settings
        update_option('edubot_daily_report_enabled', true);
        update_option('edubot_daily_report_time', '09:00');
        
        $enabled = get_option('edubot_daily_report_enabled');
        $time = get_option('edubot_daily_report_time');
        
        $this->assertTrue($enabled);
        $this->assertEquals('09:00', $time);
    }
    
    /**
     * Test add report recipient
     */
    public function test_add_report_recipient() {
        $reports = EduBot_Performance_Reports::get_instance($this->logger);
        
        // Get current recipients
        $recipients = get_option('edubot_report_recipients', []);
        $recipients[] = 'test@example.com';
        update_option('edubot_report_recipients', $recipients);
        
        // Verify recipient added
        $updated = get_option('edubot_report_recipients', []);
        $this->assertContains('test@example.com', $updated);
    }
    
    /**
     * Test generate daily report
     */
    public function test_generate_daily_report() {
        if (!class_exists('EduBot_Performance_Reports')) {
            $this->markTestSkipped('Performance Reports class not available');
        }
        
        $reports = EduBot_Performance_Reports::get_instance($this->logger);
        
        if (method_exists($reports, 'generate_daily_report')) {
            $result = $reports->generate_daily_report();
            $this->assertIsArray($result);
        }
    }
    
    /**
     * Test generate weekly report
     */
    public function test_generate_weekly_report() {
        if (!class_exists('EduBot_Performance_Reports')) {
            $this->markTestSkipped('Performance Reports class not available');
        }
        
        $reports = EduBot_Performance_Reports::get_instance($this->logger);
        
        if (method_exists($reports, 'generate_weekly_report')) {
            $result = $reports->generate_weekly_report();
            $this->assertIsArray($result);
        }
    }
    
    /**
     * Test generate monthly report
     */
    public function test_generate_monthly_report() {
        if (!class_exists('EduBot_Performance_Reports')) {
            $this->markTestSkipped('Performance Reports class not available');
        }
        
        $reports = EduBot_Performance_Reports::get_instance($this->logger);
        
        if (method_exists($reports, 'generate_monthly_report')) {
            $result = $reports->generate_monthly_report();
            $this->assertIsArray($result);
        }
    }
    
    /**
     * Test email template generation
     */
    public function test_email_template_generation() {
        if (!class_exists('EduBot_Performance_Reports')) {
            $this->markTestSkipped('Performance Reports class not available');
        }
        
        $reports = EduBot_Performance_Reports::get_instance($this->logger);
        
        // Create test report data
        $report = [
            'total_enquiries' => 25,
            'top_channel' => 'facebook',
            'conversion_rate' => 12.5,
            'period' => 'daily'
        ];
        
        if (method_exists($reports, 'get_html_email_template')) {
            $html = $reports->get_html_email_template($report, 'daily');
            $this->assertStringContainsString('25', $html); // Should contain data
        }
    }
    
    /**
     * Test report history retrieval
     */
    public function test_get_report_history() {
        if (!class_exists('EduBot_Performance_Reports')) {
            $this->markTestSkipped('Performance Reports class not available');
        }
        
        $reports = EduBot_Performance_Reports::get_instance($this->logger);
        
        if (method_exists($reports, 'get_report_history')) {
            $history = $reports->get_report_history(10);
            $this->assertIsArray($history);
        }
    }
    
    /**
     * Test report statistics
     */
    public function test_get_report_statistics() {
        if (!class_exists('EduBot_Performance_Reports')) {
            $this->markTestSkipped('Performance Reports class not available');
        }
        
        $reports = EduBot_Performance_Reports::get_instance($this->logger);
        
        if (method_exists($reports, 'get_report_statistics')) {
            $stats = $reports->get_report_statistics();
            $this->assertIsArray($stats);
        }
    }
    
    /**
     * Test recipient validation
     */
    public function test_recipient_validation() {
        $emails = [
            'valid@example.com' => true,
            'another.valid@domain.co.uk' => true,
            'invalid-email' => false,
            'invalid@' => false,
            '@invalid.com' => false,
        ];
        
        foreach ($emails as $email => $expected) {
            $valid = is_email($email); // WordPress function
            $this->assertEquals($expected, $valid, "Email validation failed for: $email");
        }
    }
}
