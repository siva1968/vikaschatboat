<?php

/**
 * Batch Operations Class
 * 
 * Handles all bulk database operations.
 * Extracted from EduBot_Database_Manager for single responsibility.
 * 
 * Single Responsibility: Batch operations only (N queries → 1 query).
 * 
 * @package EduBot_Pro
 * @subpackage Queries
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EduBot_Batch_Operations implements EduBot_Batch_Operations_Interface {

    /**
     * WordPress database object
     * 
     * @var wpdb
     */
    protected $wpdb;

    /**
     * Initialize Batch Operations
     * 
     * @param wpdb $wpdb WordPress database object
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
     * Batch fetch multiple enquiries by IDs
     * Reduces N queries to 1 query using IN clause
     * 
     * @param array $ids Enquiry IDs to fetch
     * @return array Enquiry records
     */
    public function fetch_by_ids($ids) {
        if (empty($ids) || !is_array($ids)) {
            return array();
        }

        $table = $this->wpdb->prefix . 'edubot_enquiries';
        $ids = array_filter($ids, 'is_numeric');
        
        if (empty($ids)) {
            return array();
        }

        // Create placeholders for IN clause
        $id_placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $query = "SELECT * FROM $table WHERE id IN ($id_placeholders) ORDER BY created_at DESC";
        
        $results = $this->wpdb->get_results($this->wpdb->prepare($query, $ids), ARRAY_A);
        return $results ?: array();
    }

    /**
     * Batch update multiple enquiries
     * Uses CASE statements to update N records in 1 query
     * 
     * Example:
     *   $updates = [
     *     123 => ['status' => 'processed'],
     *     124 => ['status' => 'pending']
     *   ];
     *   $result = $batch->batch_update($updates);
     * 
     * @param array $updates Array of [id => [field => value], ...]
     * @return array Results array with updated count and time saved
     */
    public function batch_update($updates) {
        if (empty($updates) || !is_array($updates)) {
            return array('updated' => 0, 'failed' => 0, 'time_saved' => '0ms');
        }

        $table = $this->wpdb->prefix . 'edubot_enquiries';
        $ids = array_keys($updates);
        $id_placeholders = implode(',', array_fill(0, count($ids), '%d'));

        // Build CASE statements for each field
        $case_statements = array();
        $values = array();
        
        // Get first update to determine fields
        $first_update = reset($updates);
        foreach (array_keys($first_update) as $field) {
            $field_cases = "CASE id";
            foreach ($updates as $id => $update_data) {
                if (isset($update_data[$field])) {
                    $field_cases .= " WHEN %d THEN %s";
                    $values[] = $id;
                    $values[] = $update_data[$field];
                }
            }
            $field_cases .= " ELSE $field END";
            $case_statements[$field] = $field_cases;
        }

        // Build SET clause
        $set_clause = array();
        foreach ($case_statements as $field => $case) {
            $set_clause[] = "$field = $case";
        }

        // Execute batch update in single query
        $query = "UPDATE $table SET " . implode(", ", $set_clause) . 
                 " WHERE id IN ($id_placeholders)";

        $prepared_values = array_merge($values, $ids);
        $result = $this->wpdb->query($this->wpdb->prepare($query, $prepared_values));
        
        $updated_count = $result !== false ? $result : 0;
        $time_saved = max(0, ($updated_count * 20) - 10); // Estimate 20ms per individual query

        return array(
            'updated' => $updated_count,
            'failed' => count($updates) - $updated_count,
            'time_saved' => $time_saved . 'ms'
        );
    }

    /**
     * Batch update a single field for multiple records
     * Simpler than batch_update when updating just one field
     * 
     * @param array $ids Record IDs
     * @param string $field Field to update
     * @param mixed $value New value
     * @return int Rows affected
     */
    public function batch_update_field($ids, $field, $value) {
        if (empty($ids) || !is_array($ids)) {
            return 0;
        }

        $table = $this->wpdb->prefix . 'edubot_enquiries';
        $ids = array_filter($ids, 'is_numeric');
        
        if (empty($ids)) {
            return 0;
        }

        $id_placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $query = "UPDATE $table SET $field = %s WHERE id IN ($id_placeholders)";
        
        $params = array_merge(array($value), $ids);
        $result = $this->wpdb->query($this->wpdb->prepare($query, $params));
        
        return $result !== false ? $result : 0;
    }

    /**
     * Batch update notification status for multiple enquiries
     * Common operation: mark multiple enquiries as notified
     * 
     * @param array $ids Enquiry IDs
     * @param string $notification_type 'whatsapp_sent', 'email_sent', or 'sms_sent'
     * @param int $status 1 or 0
     * @return int Rows affected
     */
    public function batch_update_notification_status($ids, $notification_type, $status = 1) {
        if (empty($ids) || !is_array($ids)) {
            return 0;
        }

        $table = $this->wpdb->prefix . 'edubot_enquiries';
        $valid_types = array('whatsapp_sent', 'email_sent', 'sms_sent');
        
        if (!in_array($notification_type, $valid_types)) {
            return 0;
        }

        $ids = array_filter($ids, 'is_numeric');
        if (empty($ids)) {
            return 0;
        }

        $id_placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $query = "UPDATE $table 
                  SET $notification_type = %d, updated_at = NOW()
                  WHERE id IN ($id_placeholders)";

        $params = array_merge(array($status), $ids);
        $result = $this->wpdb->query($this->wpdb->prepare($query, $params));
        
        return $result !== false ? $result : 0;
    }

    /**
     * Batch delete multiple records
     * 
     * @param array $ids Record IDs to delete
     * @return int Rows deleted
     */
    public function batch_delete($ids) {
        if (empty($ids) || !is_array($ids)) {
            return 0;
        }

        $table = $this->wpdb->prefix . 'edubot_enquiries';
        $ids = array_filter($ids, 'is_numeric');
        
        if (empty($ids)) {
            return 0;
        }

        $id_placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $query = "DELETE FROM $table WHERE id IN ($id_placeholders)";
        
        $result = $this->wpdb->query($this->wpdb->prepare($query, $ids));
        return $result !== false ? $result : 0;
    }

    /**
     * Fetch multiple records with combined data
     * Example: fetch enquiries with their notification status computed
     * 
     * @param array $ids Record IDs
     * @return array Records with computed fields
     */
    public function fetch_with_computed_fields($ids) {
        if (empty($ids) || !is_array($ids)) {
            return array();
        }

        $table = $this->wpdb->prefix . 'edubot_enquiries';
        $ids = array_filter($ids, 'is_numeric');
        
        if (empty($ids)) {
            return array();
        }

        $id_placeholders = implode(',', array_fill(0, count($ids), '%d'));

        // Single query with computed fields
        $query = "SELECT 
                    id,
                    enquiry_number,
                    student_name,
                    parent_name,
                    email,
                    phone,
                    grade,
                    board,
                    status,
                    created_at,
                    whatsapp_sent,
                    email_sent,
                    sms_sent,
                    (whatsapp_sent + email_sent + sms_sent) as notifications_sent,
                    CASE WHEN (whatsapp_sent + email_sent + sms_sent) > 0 THEN 'notified' ELSE 'pending' END as notification_status
                  FROM $table 
                  WHERE id IN ($id_placeholders)
                  ORDER BY created_at DESC";

        $results = $this->wpdb->get_results($this->wpdb->prepare($query, $ids), ARRAY_A);
        return $results ?: array();
    }

    /**
     * Get batch statistics for analytics
     * Get multiple analytics metrics in minimal queries
     * 
     * @param string $date_from Start date
     * @param string $date_to End date
     * @return array Analytics metrics
     */
    public function get_batch_analytics($date_from, $date_to) {
        $table = $this->wpdb->prefix . 'edubot_enquiries';

        $result = array(
            'total_count' => 0,
            'by_status' => array(),
            'by_source' => array()
        );

        // Query 1: Total + by status
        $query1 = "SELECT 
                    'total' as metric,
                    COUNT(*) as count,
                    NULL as value
                 FROM $table 
                 WHERE created_at BETWEEN %s AND %s
                 UNION ALL
                 SELECT 
                    'by_status' as metric,
                    COUNT(*) as count,
                    status as value
                 FROM $table 
                 WHERE created_at BETWEEN %s AND %s
                 GROUP BY status";

        $rows1 = $this->wpdb->get_results(
            $this->wpdb->prepare($query1, $date_from, $date_to, $date_from, $date_to),
            ARRAY_A
        );

        foreach ($rows1 as $row) {
            if ($row['metric'] === 'total') {
                $result['total_count'] = (int)$row['count'];
            } elseif ($row['metric'] === 'by_status') {
                $result['by_status'][$row['value']] = (int)$row['count'];
            }
        }

        // Query 2: By source
        $query2 = "SELECT source, COUNT(*) as count
                   FROM $table
                   WHERE created_at BETWEEN %s AND %s
                   GROUP BY source";

        $rows2 = $this->wpdb->get_results(
            $this->wpdb->prepare($query2, $date_from, $date_to),
            ARRAY_A
        );

        foreach ($rows2 as $row) {
            $result['by_source'][$row['source']] = (int)$row['count'];
        }

        return $result;
    }
}
