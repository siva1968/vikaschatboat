<?php

/**
 * Unit Tests for Query Builder
 * 
 * @package EduBot_Pro
 * @subpackage Tests
 */

class Test_Query_Builder {

    private $query_builder;

    public function setUp() {
        $this->query_builder = new EduBot_Query_Builder();
    }

    public function test_get_applications_returns_array() {
        $result = $this->query_builder->get_applications(array(), 10, 0);
        $this->assertIsArray($result);
    }

    public function test_count_applications_returns_integer() {
        $result = $this->query_builder->count_applications();
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function test_get_enquiry_returns_array_or_null() {
        $result = $this->query_builder->get_enquiry(99999); // Non-existent ID
        $this->assertTrue($result === null || is_array($result));
    }

    public function test_get_by_notification_status_returns_array() {
        $result = $this->query_builder->get_by_notification_status('whatsapp_sent', 0, 10);
        $this->assertIsArray($result);
    }

    public function tearDown() {
        $this->query_builder = null;
    }
}

/**
 * Unit Tests for Batch Operations
 */
class Test_Batch_Operations {

    private $batch_ops;

    public function setUp() {
        $this->batch_ops = new EduBot_Batch_Operations();
    }

    public function test_fetch_by_ids_returns_array() {
        $result = $this->batch_ops->fetch_by_ids(array(1, 2, 3));
        $this->assertIsArray($result);
    }

    public function test_batch_update_returns_array_with_counts() {
        $result = $this->batch_ops->batch_update(array());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('updated', $result);
        $this->assertArrayHasKey('failed', $result);
    }

    public function test_batch_update_notification_status_returns_int() {
        $result = $this->batch_ops->batch_update_notification_status(array(1, 2), 'whatsapp_sent', 1);
        $this->assertIsInt($result);
    }

    public function tearDown() {
        $this->batch_ops = null;
    }
}

/**
 * Unit Tests for Cache Integration
 */
class Test_Cache_Integration {

    private $cache;

    public function setUp() {
        $this->cache = new EduBot_Cache_Integration();
    }

    public function test_get_set_cache() {
        $this->cache->set_cache('test_key', array('value' => 'test'), 3600);
        $result = $this->cache->get_cache('test_key');
        $this->assertIsArray($result);
    }

    public function test_delete_cache_returns_bool() {
        $this->cache->set_cache('test_key2', 'value', 3600);
        $result = $this->cache->delete_cache('test_key2');
        $this->assertIsBool($result);
    }

    public function test_get_statistics_returns_array() {
        $result = $this->cache->get_statistics();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_keys', $result);
    }

    public function tearDown() {
        $this->cache = null;
    }
}

/**
 * Unit Tests for Exception Handling
 */
class Test_Exception_Handling {

    public function test_edubot_exception_has_http_code() {
        $exception = new EduBot_Exception('Test', 0, null, 500, array());
        $this->assertEquals(500, $exception->getHttpCode());
    }

    public function test_validation_exception_returns_400() {
        $exception = new EduBot_Validation_Exception('Invalid input');
        $this->assertEquals(400, $exception->getHttpCode());
    }

    public function test_database_exception_returns_500() {
        $exception = new EduBot_Database_Exception('DB error');
        $this->assertEquals(500, $exception->getHttpCode());
    }

    public function test_api_exception_returns_503() {
        $exception = new EduBot_API_Exception('API unavailable');
        $this->assertEquals(503, $exception->getHttpCode());
    }

    public function test_error_handler_creates_success_response() {
        $response = EduBot_Error_Handler::create_success(array('id' => 123), 'Created');
        $this->assertTrue($response['success']);
        $this->assertEquals(200, $response['code']);
    }

    public function test_error_handler_creates_error_response() {
        $response = EduBot_Error_Handler::create_error('Something failed', 500);
        $this->assertFalse($response['success']);
        $this->assertEquals(500, $response['code']);
    }
}

/**
 * Unit Tests for Database Manager
 */
class Test_Database_Manager {

    private $db;

    public function setUp() {
        $this->db = new EduBot_Database_Manager();
    }

    public function test_get_connection_stats_returns_array() {
        $result = $this->db->get_connection_stats();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('host', $result);
        $this->assertArrayHasKey('database', $result);
    }

    public function test_get_applications_returns_array() {
        $result = $this->db->get_applications(1, 10);
        $this->assertIsArray($result);
    }

    public function test_batch_operations_delegation() {
        $result = $this->db->batch_fetch_enquiries(array(1, 2, 3));
        $this->assertIsArray($result);
    }

    public function tearDown() {
        $this->db = null;
    }
}
