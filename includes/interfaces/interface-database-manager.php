<?php

/**
 * Database Manager Interface
 * 
 * Defines the contract for all database operations.
 * Enables dependency injection and testability.
 * 
 * @package EduBot_Pro
 * @subpackage Interfaces
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Interface for Database Manager
 * 
 * Separates concerns:
 * - Application operations (save, get, update, delete)
 * - Enquiry operations (fetching, filtering)
 * - Analytics operations (metrics, reports)
 */
interface EduBot_Database_Manager_Interface {

    /**
     * Save an application
     * 
     * @param array $application_data Application data to save
     * @return int|WP_Error Application ID or error
     */
    public function save_application($application_data);

    /**
     * Get applications with pagination and filters
     * 
     * @param int $page Page number
     * @param int $per_page Items per page
     * @param array $filters Filter criteria
     * @return array Applications data with pagination info
     */
    public function get_applications($page = 1, $per_page = 20, $filters = array());

    /**
     * Update application status or data
     * 
     * @param int $app_id Application ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function update_application($app_id, $data);

    /**
     * Delete an application
     * 
     * @param int $app_id Application ID
     * @return bool Success status
     */
    public function delete_application($app_id);

    /**
     * Get analytics data for date range
     * 
     * @param int $date_range Days to look back
     * @return array Analytics metrics
     */
    public function get_analytics_data($date_range = 30);

    /**
     * Batch fetch enquiries by IDs
     * 
     * @param array $ids Enquiry IDs
     * @return array Enquiry records
     */
    public function batch_fetch_enquiries($ids);

    /**
     * Batch update enquiries
     * 
     * @param array $updates Update array
     * @return array Results with counts
     */
    public function batch_update_enquiries($updates);

    /**
     * Get connection statistics
     * 
     * @return array Connection info
     */
    public function get_connection_stats();
}

/**
 * Query Builder Interface
 * 
 * Handles all query construction and execution.
 * Enables testability of query logic.
 */
interface EduBot_Query_Builder_Interface {

    /**
     * Build and execute application query
     * 
     * @param array $filters Filters to apply
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Query results
     */
    public function get_applications($filters = array(), $limit = 20, $offset = 0);

    /**
     * Count applications with filters
     * 
     * @param array $filters Filters to apply
     * @return int Count of applications
     */
    public function count_applications($filters = array());

    /**
     * Get enquiry by ID
     * 
     * @param int $id Enquiry ID
     * @return array|null Enquiry data
     */
    public function get_enquiry($id);

    /**
     * Get enquiries with filters
     * 
     * @param array $filters Filters to apply
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Enquiries
     */
    public function get_enquiries($filters = array(), $limit = 20, $offset = 0);

    /**
     * Count enquiries with filters
     * 
     * @param array $filters Filters to apply
     * @return int Count
     */
    public function count_enquiries($filters = array());
}

/**
 * Batch Operations Interface
 * 
 * Handles bulk operations to reduce database round-trips.
 */
interface EduBot_Batch_Operations_Interface {

    /**
     * Batch fetch multiple records by IDs
     * 
     * @param array $ids Record IDs
     * @return array Records
     */
    public function fetch_by_ids($ids);

    /**
     * Batch update multiple records
     * 
     * @param array $updates Update array
     * @return array Results
     */
    public function batch_update($updates);

    /**
     * Batch update notification status
     * 
     * @param array $ids Record IDs
     * @param string $field Field to update
     * @param mixed $value New value
     * @return int Rows affected
     */
    public function batch_update_field($ids, $field, $value);
}

/**
 * Cache Integration Interface
 * 
 * Handles caching layer abstraction.
 */
interface EduBot_Cache_Integration_Interface {

    /**
     * Get with cache fallback
     * 
     * @param string $cache_key Cache key
     * @param callable $callback Fallback callback
     * @param int $expiration Cache expiration in seconds
     * @return mixed Cached or fresh data
     */
    public function get_with_cache($cache_key, $callback, $expiration = 3600);

    /**
     * Set cache value
     * 
     * @param string $cache_key Cache key
     * @param mixed $value Value to cache
     * @param int $expiration Expiration in seconds
     * @return bool Success
     */
    public function set_cache($cache_key, $value, $expiration = 3600);

    /**
     * Invalidate cache by pattern
     * 
     * @param string $pattern Cache key pattern
     * @return int Invalidated count
     */
    public function invalidate_cache($pattern);
}
