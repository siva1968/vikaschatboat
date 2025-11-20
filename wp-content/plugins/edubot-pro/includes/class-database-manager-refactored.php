<?php

/**
 * Database Manager - Refactored
 * 
 * PHASE 3 REFACTORING: Delegates to focused classes
 * - Query Building → EduBot_Query_Builder
 * - Batch Operations → EduBot_Batch_Operations
 * - Caching → EduBot_Cache_Integration
 * - Validation → kept here (thin layer)
 * 
 * Reduced from 1507 lines to 500 lines through delegation
 * Maintains backward compatibility - all original methods preserved
 * 
 * @package EduBot_Pro
 * @subpackage Database
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EduBot_Database_Manager implements EduBot_Database_Manager_Interface {

    /**
     * Query Builder instance
     * @var EduBot_Query_Builder
     */
    protected $query_builder;

    /**
     * Batch Operations instance
     * @var EduBot_Batch_Operations
     */
    protected $batch_operations;

    /**
     * Cache Integration instance
     * @var EduBot_Cache_Integration
     */
    protected $cache_integration;

    /**
     * Initialize Database Manager with dependencies
     * 
     * @param EduBot_Query_Builder $query_builder Query builder
     * @param EduBot_Batch_Operations $batch_operations Batch operations
     * @param EduBot_Cache_Integration $cache_integration Cache integration
     */
    public function __construct(
        EduBot_Query_Builder $query_builder = null,
        EduBot_Batch_Operations $batch_operations = null,
        EduBot_Cache_Integration $cache_integration = null
    ) {
        $this->query_builder = $query_builder ?: new EduBot_Query_Builder();
        $this->batch_operations = $batch_operations ?: new EduBot_Batch_Operations();
        $this->cache_integration = $cache_integration ?: new EduBot_Cache_Integration();
    }

    /**
     * ========================================================================
     * APPLICATION OPERATIONS
     * ========================================================================
     */

    /**
     * Save application to database
     * 
     * @param array $application_data Application data
     * @return int|WP_Error Application ID or error
     */
    public function save_application($application_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';
        $site_id = get_current_blog_id();

        // Validate data
        $validated = $this->validate_application_data($application_data);
        if (is_wp_error($validated)) {
            return $validated;
        }

        $data = array(
            'site_id' => $site_id,
            'application_number' => sanitize_text_field($validated['application_number']),
            'student_data' => wp_json_encode($validated['student_data']),
            'conversation_log' => wp_json_encode($validated['conversation_log']),
            'status' => sanitize_text_field($validated['status']),
            'source' => sanitize_text_field($validated['source']),
            'ip_address' => sanitize_text_field($this->get_client_ip()),
            'user_agent' => sanitize_text_field($this->get_user_agent())
        );

        $result = $wpdb->insert($table, $data, array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));

        if ($result !== false) {
            $this->cache_integration->invalidate_applications();
            do_action('edubot_application_saved', $wpdb->insert_id, $data);
            return $wpdb->insert_id;
        }

        if (function_exists('EduBot_Logger')) {
            EduBot_Logger::error('Failed to save application', array('data' => $data));
        }
        return new WP_Error('save_failed', 'Failed to save application data');
    }

    /**
     * Get applications with filters and pagination
     * 
     * @param int $page Page number
     * @param int $per_page Items per page
     * @param array $filters Filters to apply
     * @return array Applications data with pagination
     */
    public function get_applications($page = 1, $per_page = 20, $filters = array()) {
        // Try cache first
        $cache_key = 'applications_' . md5(json_encode(array($page, $per_page, $filters)));
        $cached = $this->cache_integration->get_cache($cache_key);
        if ($cached !== null) {
            return $cached;
        }

        // Cache miss - fetch and cache
        $result = $this->fetch_applications($page, $per_page, $filters);
        if (!empty($result)) {
            $this->cache_integration->set_cache($cache_key, $result, 300); // 5 min cache
        }
        return $result;
    }

    /**
     * Fetch applications directly (cache bypass)
     * 
     * @param int $page Page number
     * @param int $per_page Items per page
     * @param array $filters Filters
     * @return array Results
     */
    public function fetch_applications($page = 1, $per_page = 20, $filters = array()) {
        $offset = ($page - 1) * $per_page;
        $apps = $this->query_builder->get_applications($filters, $per_page, $offset);
        $total = $this->query_builder->count_applications($filters);

        return array(
            'applications' => $apps,
            'total_records' => $total,
            'total_pages' => ceil($total / $per_page),
            'current_page' => $page
        );
    }

    /**
     * Update application
     * 
     * @param int $app_id Application ID
     * @param array $data Data to update
     * @return int|WP_Error Rows affected or error
     */
    public function update_application($app_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';

        $result = $wpdb->update($table, $data, array('id' => $app_id), null, array('%d'));
        if ($result !== false) {
            $this->cache_integration->invalidate_applications();
            return $result;
        }

        return new WP_Error('update_failed', 'Failed to update application');
    }

    /**
     * Delete application
     * 
     * @param int $app_id Application ID
     * @return bool|WP_Error Success or error
     */
    public function delete_application($app_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';

        $result = $wpdb->delete($table, array('id' => $app_id), array('%d'));
        if ($result !== false) {
            $this->cache_integration->invalidate_applications();
            return true;
        }

        return new WP_Error('delete_failed', 'Failed to delete application');
    }

    /**
     * ========================================================================
     * ANALYTICS OPERATIONS
     * ========================================================================
     */

    /**
     * Get analytics data
     * 
     * @param int $date_range Days to look back
     * @return array Analytics metrics
     */
    public function get_analytics_data($date_range = 30) {
        $cache_key = 'analytics_' . $date_range;
        $cached = $this->cache_integration->get_cache($cache_key);
        if ($cached !== null) {
            return $cached;
        }

        $date_from = date('Y-m-d', strtotime("-{$date_range} days"));
        $date_to = date('Y-m-d');

        $metrics = $this->batch_operations->get_batch_analytics($date_from, $date_to);

        if (!empty($metrics)) {
            $this->cache_integration->set_cache($cache_key, $metrics, 600); // 10 min
        }

        return $metrics;
    }

    /**
     * ========================================================================
     * BATCH OPERATIONS - Delegate to batch operations class
     * ========================================================================
     */

    /**
     * Batch fetch enquiries by IDs
     * 
     * @param array $ids Enquiry IDs
     * @return array Enquiry records
     */
    public function batch_fetch_enquiries($ids) {
        return $this->batch_operations->fetch_by_ids($ids);
    }

    /**
     * Batch update enquiries
     * 
     * @param array $updates Updates array
     * @return array Results
     */
    public function batch_update_enquiries($updates) {
        return $this->batch_operations->batch_update($updates);
    }

    /**
     * Batch update notification status
     * 
     * @param array $ids IDs to update
     * @param string $type Notification type
     * @param int $status Status value
     * @return int Rows affected
     */
    public function batch_update_notification_status($ids, $type, $status = 1) {
        return $this->batch_operations->batch_update_notification_status($ids, $type, $status);
    }

    /**
     * Batch fetch with computed fields
     * 
     * @param array $ids IDs to fetch
     * @return array Results with computed fields
     */
    public function batch_fetch_enquiries_with_status($ids) {
        return $this->batch_operations->fetch_with_computed_fields($ids);
    }

    /**
     * Get enquiries by notification status
     * 
     * @param string $type Notification type
     * @param int $status Status
     * @param int $limit Limit
     * @return array Results
     */
    public function get_enquiries_by_notification_status($type, $status = 0, $limit = 100) {
        return $this->query_builder->get_by_notification_status($type, $status, $limit);
    }

    /**
     * ========================================================================
     * UTILITY OPERATIONS
     * ========================================================================
     */

    /**
     * Get connection statistics
     * 
     * @return array Connection info
     */
    public function get_connection_stats() {
        global $wpdb;
        return array(
            'host' => defined('DB_HOST') ? DB_HOST : 'unknown',
            'database' => defined('DB_NAME') ? DB_NAME : 'unknown',
            'charset' => $wpdb->charset,
            'collate' => $wpdb->collate,
            'queries_executed' => isset($wpdb->queries) ? count($wpdb->queries) : 0,
            'last_error' => $wpdb->last_error ?: 'None',
            'connection_status' => $wpdb->dbh ? 'Active' : 'Inactive'
        );
    }

    /**
     * ========================================================================
     * PRIVATE HELPER METHODS
     * ========================================================================
     */

    /**
     * Validate application data
     * 
     * @param array $data Data to validate
     * @return array|WP_Error Validated data or error
     */
    private function validate_application_data($data) {
        $errors = array();

        if (empty($data['application_number'])) {
            $errors[] = 'Application number required';
        }

        if (empty($data['student_data']) || !is_array($data['student_data'])) {
            $errors[] = 'Valid student data required';
        } else {
            $student = $data['student_data'];
            if (isset($student['email']) && !empty($student['email'])) {
                if (!is_email($student['email'])) {
                    $errors[] = 'Invalid email format';
                }
            }
        }

        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(', ', $errors));
        }

        $data['status'] = isset($data['status']) ? $data['status'] : 'pending';
        $data['source'] = isset($data['source']) ? $data['source'] : 'chatbot';

        return $data;
    }

    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }

    /**
     * Get user agent
     * 
     * @return string User agent
     */
    private function get_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : 'Unknown';
    }
}
