<?php

/**
 * Query Builder Class
 * 
 * Encapsulates all database query construction and execution.
 * Extracted from EduBot_Database_Manager for testability and maintainability.
 * 
 * Single Responsibility: Build and execute queries only.
 * 
 * @package EduBot_Pro
 * @subpackage Queries
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EduBot_Query_Builder implements EduBot_Query_Builder_Interface {

    /**
     * WordPress database object
     * 
     * @var wpdb
     */
    protected $wpdb;

    /**
     * Initialize Query Builder
     * 
     * @param wpdb $wpdb WordPress database object (optional, defaults to global $wpdb)
     */
    public function __construct($wpdb = null) {
        if ($wpdb !== null) {
            $this->wpdb = $wpdb;
        } else {
            global $wpdb;
            $this->wpdb = $wpdb;
        }
    }

    /**
     * Get applications with filters and pagination
     * 
     * @param array $filters Filters to apply
     * @param int $limit Items per page
     * @param int $offset Starting position
     * @return array Application records
     */
    public function get_applications($filters = array(), $limit = 20, $offset = 0) {
        $table = $this->wpdb->prefix . 'edubot_enquiries';

        $where_clause = "WHERE 1=1";
        $where_values = array();

        // Apply filters
        if (!empty($filters['status'])) {
            $where_clause .= " AND status = %s";
            $where_values[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $where_clause .= " AND created_at >= %s";
            $where_values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where_clause .= " AND created_at <= %s";
            $where_values[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $where_clause .= " AND (enquiry_number LIKE %s OR student_name LIKE %s OR parent_name LIKE %s OR email LIKE %s)";
            $search_term = '%' . $this->wpdb->esc_like($filters['search']) . '%';
            array_push($where_values, $search_term, $search_term, $search_term, $search_term);
        }

        // Build query with pagination
        $query = "SELECT * FROM $table $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_values[] = $limit;
        $where_values[] = $offset;

        // Execute with prepared statement
        if (empty($where_values)) {
            return $this->wpdb->get_results($query, ARRAY_A) ?: array();
        } else {
            $prepared_query = $this->wpdb->prepare($query, $where_values);
            return $this->wpdb->get_results($prepared_query, ARRAY_A) ?: array();
        }
    }

    /**
     * Count applications with filters
     * 
     * @param array $filters Filters to apply
     * @return int Count of matching applications
     */
    public function count_applications($filters = array()) {
        $table = $this->wpdb->prefix . 'edubot_enquiries';

        $where_clause = "WHERE 1=1";
        $where_values = array();

        // Apply filters
        if (!empty($filters['status'])) {
            $where_clause .= " AND status = %s";
            $where_values[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $where_clause .= " AND created_at >= %s";
            $where_values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where_clause .= " AND created_at <= %s";
            $where_values[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $where_clause .= " AND (enquiry_number LIKE %s OR student_name LIKE %s OR parent_name LIKE %s OR email LIKE %s)";
            $search_term = '%' . $this->wpdb->esc_like($filters['search']) . '%';
            array_push($where_values, $search_term, $search_term, $search_term, $search_term);
        }

        $query = "SELECT COUNT(*) FROM $table $where_clause";

        if (empty($where_values)) {
            return (int)$this->wpdb->get_var($query);
        } else {
            return (int)$this->wpdb->get_var($this->wpdb->prepare($query, $where_values));
        }
    }

    /**
     * Get enquiry by ID
     * 
     * @param int $id Enquiry ID
     * @return array|null Enquiry data
     */
    public function get_enquiry($id) {
        $table = $this->wpdb->prefix . 'edubot_enquiries';
        
        $enquiry = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id),
            ARRAY_A
        );

        return $enquiry ?: null;
    }

    /**
     * Get enquiries with filters and pagination
     * 
     * @param array $filters Filters to apply
     * @param int $limit Items per page
     * @param int $offset Starting position
     * @return array Enquiry records
     */
    public function get_enquiries($filters = array(), $limit = 20, $offset = 0) {
        $table = $this->wpdb->prefix . 'edubot_enquiries';

        $where_clause = "WHERE 1=1";
        $where_values = array();

        // Apply filters
        if (!empty($filters['status'])) {
            $where_clause .= " AND status = %s";
            $where_values[] = $filters['status'];
        }

        if (!empty($filters['source'])) {
            $where_clause .= " AND source = %s";
            $where_values[] = $filters['source'];
        }

        if (!empty($filters['date_from'])) {
            $where_clause .= " AND created_at >= %s";
            $where_values[] = $filters['date_from'];
        }

        if (!empty($filters['search'])) {
            $where_clause .= " AND (enquiry_number LIKE %s OR student_name LIKE %s OR email LIKE %s)";
            $search_term = '%' . $this->wpdb->esc_like($filters['search']) . '%';
            array_push($where_values, $search_term, $search_term, $search_term);
        }

        $query = "SELECT * FROM $table $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_values[] = $limit;
        $where_values[] = $offset;

        if (empty($where_values)) {
            return $this->wpdb->get_results($query, ARRAY_A) ?: array();
        } else {
            return $this->wpdb->get_results($this->wpdb->prepare($query, $where_values), ARRAY_A) ?: array();
        }
    }

    /**
     * Count enquiries with filters
     * 
     * @param array $filters Filters to apply
     * @return int Count of matching enquiries
     */
    public function count_enquiries($filters = array()) {
        $table = $this->wpdb->prefix . 'edubot_enquiries';

        $where_clause = "WHERE 1=1";
        $where_values = array();

        if (!empty($filters['status'])) {
            $where_clause .= " AND status = %s";
            $where_values[] = $filters['status'];
        }

        if (!empty($filters['source'])) {
            $where_clause .= " AND source = %s";
            $where_values[] = $filters['source'];
        }

        if (!empty($filters['date_from'])) {
            $where_clause .= " AND created_at >= %s";
            $where_values[] = $filters['date_from'];
        }

        $query = "SELECT COUNT(*) FROM $table $where_clause";

        if (empty($where_values)) {
            return (int)$this->wpdb->get_var($query);
        } else {
            return (int)$this->wpdb->get_var($this->wpdb->prepare($query, $where_values));
        }
    }

    /**
     * Save enquiry to database
     * 
     * @param array $data Enquiry data
     * @return int|false Enquiry ID or false
     */
    public function save_enquiry($data) {
        $table = $this->wpdb->prefix . 'edubot_enquiries';

        $result = $this->wpdb->insert($table, $data);
        return $result !== false ? $this->wpdb->insert_id : false;
    }

    /**
     * Update enquiry data
     * 
     * @param int $id Enquiry ID
     * @param array $data Data to update
     * @param array $where_conditions Where conditions
     * @return int|false Rows affected or false
     */
    public function update_enquiry($id, $data, $where_conditions = array()) {
        $table = $this->wpdb->prefix . 'edubot_enquiries';
        
        $where = array_merge(array('id' => $id), $where_conditions);
        return $this->wpdb->update($table, $data, $where);
    }

    /**
     * Delete enquiry
     * 
     * @param int $id Enquiry ID
     * @return int|false Rows affected or false
     */
    public function delete_enquiry($id) {
        $table = $this->wpdb->prefix . 'edubot_enquiries';
        return $this->wpdb->delete($table, array('id' => $id), array('%d'));
    }

    /**
     * Get enquiries by notification status
     * 
     * @param string $notification_type Type of notification (whatsapp_sent, email_sent, sms_sent)
     * @param int $status Status value (0 or 1)
     * @param int $limit Limit results
     * @return array Enquiries
     */
    public function get_by_notification_status($notification_type, $status = 0, $limit = 100) {
        $table = $this->wpdb->prefix . 'edubot_enquiries';
        $valid_types = array('whatsapp_sent', 'email_sent', 'sms_sent');

        if (!in_array($notification_type, $valid_types)) {
            return array();
        }

        $query = "SELECT * FROM $table WHERE $notification_type = %d ORDER BY created_at DESC LIMIT %d";
        
        return $this->wpdb->get_results(
            $this->wpdb->prepare($query, $status, $limit),
            ARRAY_A
        ) ?: array();
    }
}
