<?php
/**
 * PHPUnit Bootstrap File
 * 
 * Sets up the test environment for EduBot Pro plugin
 * 
 * @since 1.4.1
 * @package EduBot_Pro
 * @subpackage Tests
 */

// Define test constants
define('EDUBOT_PRO_TESTS_DIR', __DIR__);
define('EDUBOT_PRO_PLUGIN_PATH', dirname(dirname(__DIR__)) . '/');
define('ABSPATH', dirname(dirname(dirname(dirname(__DIR__)))) . '/wordpress/');

// Bootstrap WordPress
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/tests/bootstrap.php';

// Load plugin
require_once EDUBOT_PRO_PLUGIN_PATH . 'edubot-pro.php';

// Activate plugin
activate_plugin('edubot-pro/edubot-pro.php');

/**
 * Abstract base test case class
 */
abstract class EduBot_Test_Case extends WP_UnitTestCase {
    
    /**
     * Logger instance for tests
     * 
     * @var EduBot_Logger
     */
    protected $logger;
    
    /**
     * Setup
     */
    public function setUp(): void {
        parent::setUp();
        
        // Initialize logger
        if (class_exists('EduBot_Logger')) {
            $this->logger = EduBot_Logger::get_instance();
        }
    }
    
    /**
     * Teardown
     */
    public function tearDown(): void {
        parent::tearDown();
    }
    
    /**
     * Get test data
     * 
     * @param string $key Data key
     * @return mixed
     */
    protected function get_test_data($key) {
        $test_data = [
            'conversion' => [
                'user_email' => 'test@example.com',
                'user_phone' => '+1234567890',
                'channel' => 'facebook',
                'campaign' => 'Test Campaign',
                'status' => 'completed',
                'metadata' => ['test' => true],
            ],
            'session' => [
                'user_email' => 'test@example.com',
                'channel' => 'google',
                'campaign' => 'Google Campaign',
                'first_touch_time' => date('Y-m-d H:i:s'),
            ],
            'api_settings' => [
                'facebook_app_id' => 'test_app_id_123',
                'facebook_access_token' => 'test_token_abc123',
                'google_client_id' => 'test_google_id',
                'tiktok_app_id' => 'test_tiktok_id',
            ],
            'report' => [
                'recipient' => 'admin@test.com',
                'period' => 'daily',
                'status' => 'sent',
            ],
        ];
        
        return $test_data[$key] ?? [];
    }
}
