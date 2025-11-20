<?php
/**
 * EduBot Logger Tests
 * 
 * Tests for the logging system
 * 
 * @since 1.4.1
 * @package EduBot_Pro
 * @subpackage Tests
 */

class Test_EduBot_Logger extends EduBot_Test_Case {
    
    /**
     * Test singleton instance
     */
    public function test_get_instance() {
        $logger1 = EduBot_Logger::get_instance();
        $logger2 = EduBot_Logger::get_instance();
        
        $this->assertInstanceOf('EduBot_Logger', $logger1);
        $this->assertSame($logger1, $logger2);
    }
    
    /**
     * Test log info message
     */
    public function test_log_info() {
        $logger = EduBot_Logger::get_instance();
        
        $result = $logger->log_info('Test info message');
        
        $this->assertTrue($result || is_int($result)); // Should return success
    }
    
    /**
     * Test log error
     */
    public function test_log_error() {
        $logger = EduBot_Logger::get_instance();
        
        $result = $logger->log_error('Test error message', [
            'error_code' => 'TEST_001',
            'details' => 'Test error details'
        ]);
        
        $this->assertTrue($result || is_int($result));
    }
    
    /**
     * Test log warning
     */
    public function test_log_warning() {
        $logger = EduBot_Logger::get_instance();
        
        $result = $logger->log_warning('Test warning', ['level' => 'warning']);
        
        $this->assertTrue($result || is_int($result));
    }
    
    /**
     * Test log with context data
     */
    public function test_log_with_context() {
        $logger = EduBot_Logger::get_instance();
        
        $context = [
            'user_id' => 1,
            'email' => 'test@example.com',
            'action' => 'conversion_tracked'
        ];
        
        $result = $logger->log_info('User action', $context);
        
        $this->assertTrue($result || is_int($result));
    }
    
    /**
     * Test log database access
     */
    public function test_log_database_storage() {
        global $wpdb;
        
        $logger = EduBot_Logger::get_instance();
        $logger->log_info('Database test message');
        
        // Verify entry in database
        $table = $wpdb->prefix . 'edubot_logs';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        
        $this->assertGreaterThan(0, $count);
    }
    
    /**
     * Test log cleanup
     */
    public function test_log_cleanup() {
        global $wpdb;
        
        $logger = EduBot_Logger::get_instance();
        $table = $wpdb->prefix . 'edubot_logs';
        
        // Insert old log entries
        $old_date = date('Y-m-d H:i:s', strtotime('-91 days'));
        $wpdb->insert(
            $table,
            [
                'level' => 'info',
                'message' => 'Old log entry',
                'created_at' => $old_date,
            ],
            ['%s', '%s', '%s']
        );
        
        // Run cleanup
        if (method_exists($logger, 'cleanup_old_logs')) {
            $logger->cleanup_old_logs();
            
            // Verify old entry removed
            $count = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
            );
            
            $this->assertEquals(0, $count);
        }
    }
}
