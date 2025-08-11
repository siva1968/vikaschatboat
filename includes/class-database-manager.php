<?php

/**
 * Handle database operations
 */
class EduBot_Database_Manager {

    /**
     * Save application to database
     */
    public function save_application($application_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';
        $site_id = get_current_blog_id();

        $data = array(
            'site_id' => $site_id,
            'application_number' => $application_data['application_number'],
            'student_data' => $application_data['student_data'],
            'conversation_log' => $application_data['conversation_log'],
            'status' => 'pending',
            'source' => 'chatbot',
            'ip_address' => isset($application_data['ip_address']) ? $application_data['ip_address'] : '',
            'user_agent' => isset($application_data['user_agent']) ? $application_data['user_agent'] : ''
        );

        $formats = array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s');

        $result = $wpdb->insert($table, $data, $formats);

        if ($result !== false) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get application by ID
     */
    public function get_application($application_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';
        $site_id = get_current_blog_id();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND site_id = %d",
            $application_id, $site_id
        ), ARRAY_A);
    }

    /**
     * Get application by application number
     */
    public function get_application_by_number($application_number) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';
        $site_id = get_current_blog_id();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE application_number = %s AND site_id = %d",
            $application_number, $site_id
        ), ARRAY_A);
    }

    /**
     * Update application status
     */
    public function update_application_status($application_id, $status) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';
        $site_id = get_current_blog_id();

        return $wpdb->update(
            $table,
            array('status' => $status),
            array('id' => $application_id, 'site_id' => $site_id),
            array('%s'),
            array('%d', '%d')
        );
    }

    /**
     * Get applications with pagination
     */
    public function get_applications($page = 1, $per_page = 20, $filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';
        $site_id = get_current_blog_id();

        $where_clause = "WHERE site_id = %d";
        $where_values = array($site_id);

        // Add filters
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
            $where_clause .= " AND (application_number LIKE %s OR student_data LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        // Count total records
        $count_query = "SELECT COUNT(*) FROM $table $where_clause";
        $total_records = $wpdb->get_var($wpdb->prepare($count_query, $where_values));

        // Get paginated results
        $offset = ($page - 1) * $per_page;
        $query = "SELECT * FROM $table $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_values[] = $per_page;
        $where_values[] = $offset;

        $applications = $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);

        return array(
            'applications' => $applications,
            'total_records' => $total_records,
            'total_pages' => ceil($total_records / $per_page),
            'current_page' => $page
        );
    }

    /**
     * Get analytics data
     */
    public function get_analytics_data($date_range = 30) {
        global $wpdb;
        $applications_table = $wpdb->prefix . 'edubot_applications';
        $analytics_table = $wpdb->prefix . 'edubot_analytics';
        $site_id = get_current_blog_id();

        $date_from = date('Y-m-d', strtotime("-{$date_range} days"));

        // Total applications
        $total_applications = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $applications_table WHERE site_id = %d AND created_at >= %s",
            $site_id, $date_from
        ));

        // Applications by status
        $applications_by_status = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count FROM $applications_table 
             WHERE site_id = %d AND created_at >= %s 
             GROUP BY status",
            $site_id, $date_from
        ), ARRAY_A);

        // Conversion rate (completed applications / total sessions)
        $total_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $analytics_table 
             WHERE site_id = %d AND timestamp >= %s",
            $site_id, $date_from
        ));

        $conversion_rate = $total_sessions > 0 ? round(($total_applications / $total_sessions) * 100, 2) : 0;

        // Average completion time
        $avg_completion_time = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(completion_minutes) FROM (
                SELECT TIMESTAMPDIFF(MINUTE, 
                    (SELECT MIN(timestamp) FROM $analytics_table a2 
                     WHERE a2.session_id = a1.session_id AND a2.site_id = %d),
                    MAX(timestamp)
                 ) as completion_minutes
                 FROM $analytics_table a1 
                 WHERE site_id = %d AND timestamp >= %s 
                 GROUP BY session_id
                 HAVING completion_minutes > 0
             ) as session_times",
            $site_id, $site_id, $date_from
        ));

        // Applications over time
        $applications_over_time = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM $applications_table 
             WHERE site_id = %d AND created_at >= %s 
             GROUP BY DATE(created_at) 
             ORDER BY date",
            $site_id, $date_from
        ), ARRAY_A);

        // Grade distribution
        $grade_distribution = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(student_data, '$.grade')) as grade,
                COUNT(*) as count
             FROM $applications_table 
             WHERE site_id = %d AND created_at >= %s 
             GROUP BY JSON_UNQUOTE(JSON_EXTRACT(student_data, '$.grade'))",
            $site_id, $date_from
        ), ARRAY_A);

        return array(
            'total_applications' => $total_applications,
            'conversion_rate' => $conversion_rate,
            'avg_completion_time' => round($avg_completion_time ?: 0, 1),
            'applications_by_status' => $applications_by_status,
            'applications_over_time' => $applications_over_time,
            'grade_distribution' => $grade_distribution,
            'total_sessions' => $total_sessions
        );
    }

    /**
     * Clean up old sessions
     */
    public function cleanup_old_sessions($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_sessions';
        $site_id = get_current_blog_id();

        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->delete(
            $table,
            array(
                'site_id' => $site_id,
                'last_activity' => array('<', $date_threshold)
            ),
            array('%d', '%s')
        );
    }

    /**
     * Clean up old analytics data
     */
    public function cleanup_old_analytics($days = 90) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_analytics';
        $site_id = get_current_blog_id();

        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->delete(
            $table,
            array(
                'site_id' => $site_id,
                'timestamp' => array('<', $date_threshold)
            ),
            array('%d', '%s')
        );
    }

    /**
     * Export applications to CSV
     */
    public function export_applications_csv($filters = array()) {
        $applications = $this->get_applications(1, 10000, $filters);
        
        $filename = 'edubot_applications_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'Application Number',
            'Student Name',
            'Grade',
            'Parent Name',
            'Phone',
            'Email',
            'Status',
            'Submitted Date'
        ));
        
        foreach ($applications['applications'] as $app) {
            $student_data = json_decode($app['student_data'], true);
            
            fputcsv($output, array(
                $app['application_number'],
                isset($student_data['student_name']) ? $student_data['student_name'] : '',
                isset($student_data['grade']) ? $student_data['grade'] : '',
                isset($student_data['parent_name']) ? $student_data['parent_name'] : '',
                isset($student_data['phone']) ? $student_data['phone'] : '',
                isset($student_data['email']) ? $student_data['email'] : '',
                $app['status'],
                $app['created_at']
            ));
        }
        
        fclose($output);
        exit;
    }

    /**
     * Get recent applications for dashboard
     */
    public function get_recent_applications($limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';
        $site_id = get_current_blog_id();

        $applications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE site_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $site_id, $limit
        ), ARRAY_A);

        // Parse student data for easier display
        foreach ($applications as &$app) {
            $student_data = json_decode($app['student_data'], true);
            $app['student_name'] = isset($student_data['student_name']) ? $student_data['student_name'] : '';
            $app['grade'] = isset($student_data['grade']) ? $student_data['grade'] : '';
            $app['parent_name'] = isset($student_data['parent_name']) ? $student_data['parent_name'] : '';
        }

        return $applications;
    }

    /**
     * Mark notifications as sent
     */
    public function update_notification_status($application_id, $notification_type, $status = 1) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';
        $site_id = get_current_blog_id();

        $field_map = array(
            'whatsapp' => 'whatsapp_sent',
            'email' => 'email_sent',
            'sms' => 'sms_sent'
        );

        if (!isset($field_map[$notification_type])) {
            return false;
        }

        return $wpdb->update(
            $table,
            array($field_map[$notification_type] => $status),
            array('id' => $application_id, 'site_id' => $site_id),
            array('%d'),
            array('%d', '%d')
        );
    }

    /**
     * Clean up old analytics data (cron callback)
     */
    public static function cron_cleanup_old_analytics() {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_analytics';
        
        // Delete analytics older than 90 days
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE timestamp < %s",
            date('Y-m-d H:i:s', strtotime('-90 days'))
        ));
        
        if ($result !== false) {
            error_log("EduBot Pro: Cleaned up $result old analytics records");
        }
        
        return $result;
    }

    /**
     * Create backup of plugin data (cron callback)
     */
    public static function create_backup() {
        global $wpdb;
        
        try {
            $tables = array(
                $wpdb->prefix . 'edubot_school_configs',
                $wpdb->prefix . 'edubot_applications',
                $wpdb->prefix . 'edubot_analytics',
                $wpdb->prefix . 'edubot_chat_sessions'
            );
            
            $backup_data = array();
            foreach ($tables as $table) {
                $data = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
                $backup_data[str_replace($wpdb->prefix, '', $table)] = $data;
            }
            
            // Store backup in options table (simplified approach)
            $backup_key = 'edubot_backup_' . date('Y_m_d');
            update_option($backup_key, $backup_data);
            
            // Keep only last 7 backups
            $old_backups = $wpdb->get_results($wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 ORDER BY option_name DESC 
                 LIMIT 999 OFFSET 7",
                'edubot_backup_%'
            ));
            
            foreach ($old_backups as $backup) {
                delete_option($backup->option_name);
            }
            
            error_log("EduBot Pro: Backup created successfully: $backup_key");
            return true;
            
        } catch (Exception $e) {
            error_log("EduBot Pro: Backup failed: " . $e->getMessage());
            return false;
        }
    }
}
