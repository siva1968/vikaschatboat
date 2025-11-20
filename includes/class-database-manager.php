<?php

/**
 * Handle database operations
 */
class EduBot_Database_Manager {

    /**
     * Save application to database with enhanced security
     */
    public function save_application($application_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';
        $site_id = get_current_blog_id();

        error_log("EduBot save_application - Input data keys: " . implode(', ', array_keys($application_data)));
        error_log("EduBot save_application - Has utm_data in input: " . (isset($application_data['utm_data']) ? 'YES' : 'NO'));

        // Validate and sanitize input data
        $validated_data = $this->validate_application_data($application_data);
        if (is_wp_error($validated_data)) {
            return $validated_data;
        }

        error_log("EduBot save_application - Validated data keys: " . implode(', ', array_keys($validated_data)));
        error_log("EduBot save_application - Has utm_data after validation: " . (isset($validated_data['utm_data']) ? 'YES' : 'NO'));

        $data = array(
            'site_id' => $site_id,
            'application_number' => sanitize_text_field($validated_data['application_number']),
            'student_data' => wp_json_encode($validated_data['student_data']),
            'conversation_log' => wp_json_encode($validated_data['conversation_log']),
            'status' => sanitize_text_field($validated_data['status']),
            'source' => sanitize_text_field($validated_data['source']),
            'ip_address' => sanitize_text_field($this->get_client_ip()),
            'user_agent' => sanitize_text_field($this->get_user_agent()),
            'utm_data' => isset($validated_data['utm_data']) ? $validated_data['utm_data'] : null,
            'gclid' => isset($validated_data['gclid']) ? sanitize_text_field($validated_data['gclid']) : null,
            'fbclid' => isset($validated_data['fbclid']) ? sanitize_text_field($validated_data['fbclid']) : null,
            'click_id_data' => isset($validated_data['click_id_data']) ? $validated_data['click_id_data'] : null
        );

        error_log("EduBot save_application - Data array utm_data value (first 50 chars): '" . substr($data['utm_data'] ?? 'NULL', 0, 50) . "'");

        $formats = array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
        
        // DEBUG: Show exact data being inserted
        error_log("========================================");
        error_log("EduBot: About to INSERT application");
        error_log("Table: " . $table);
        error_log("utm_data: " . ($data['utm_data'] ?? 'NULL'));
        error_log("gclid: " . ($data['gclid'] ?? 'NULL'));
        error_log("fbclid: " . ($data['fbclid'] ?? 'NULL'));
        error_log("click_id_data: " . ($data['click_id_data'] ?? 'NULL'));
        error_log("========================================");

        $result = $wpdb->insert($table, $data, $formats);
        
        // DEBUG: Show result
        error_log("EduBot: INSERT result = " . ($result !== false ? 'SUCCESS' : 'FAILED'));
        if ($result === false) {
            error_log("EduBot: INSERT error: " . $wpdb->last_error);
        }

        if ($result !== false) {
            // Log successful application save
            do_action('edubot_application_saved', $wpdb->insert_id, $data);
            return $wpdb->insert_id;
        }

        // Log failed application save
        error_log('EduBot: Failed to save application data');
        return new WP_Error('save_failed', 'Failed to save application data');
    }

    /**
     * Validate application data before saving
     */
    private function validate_application_data($data) {
        $errors = array();

        // Validate required fields
        if (empty($data['application_number'])) {
            $errors[] = 'Application number is required';
        }

        if (empty($data['student_data']) || !is_array($data['student_data'])) {
            $errors[] = 'Valid student data is required';
        }

        // Validate student data structure
        if (isset($data['student_data'])) {
            $student_data = $data['student_data'];
            
            // Validate application number (critical)
            if (empty($student_data['student_name'])) {
                error_log('EduBot: Validation - student_name is empty, will use "Not Provided"');
            }

            // Validate email format (only if provided)
            if (isset($student_data['email']) && !empty($student_data['email'])) {
                if (!is_email($student_data['email'])) {
                    $errors[] = 'Invalid email format: ' . $student_data['email'];
                }
            }

            // Validate phone number format
            if (isset($student_data['phone']) && !empty($student_data['phone'])) {
                $phone = preg_replace('/[^0-9+\-\s\(\)]/', '', $student_data['phone']);
                if (strlen($phone) < 10) {
                    $errors[] = 'Invalid phone number format';
                }
                $student_data['phone'] = $phone;
            }

            // Sanitize text fields
            foreach ($student_data as $key => $value) {
                if (is_string($value)) {
                    $student_data[$key] = sanitize_text_field($value);
                }
            }

            $data['student_data'] = $student_data;
        }

        // Validate conversation log
        if (!empty($data['conversation_log']) && !is_array($data['conversation_log'])) {
            $errors[] = 'Invalid conversation log format';
        }

        // Set defaults
        $data['status'] = isset($data['status']) ? $data['status'] : 'pending';
        $data['source'] = isset($data['source']) ? $data['source'] : 'chatbot';

        if (!empty($errors)) {
            $error_message = implode(', ', $errors);
            error_log('EduBot: Application validation failed: ' . $error_message);
            return new WP_Error('validation_failed', $error_message);
        }

        return $data;
    }

    /**
     * Get client IP address safely
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // Handle comma-separated IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }

    /**
     * Get user agent safely
     */
    private function get_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? 
            substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 255) : '';
    }

    /**
     * Get application by ID (only from edubot_enquiries table)
     */
    public function get_application($application_id) {
        global $wpdb;
        
        // Remove 'enq_' or 'app_' prefix if present
        $actual_id = $application_id;
        $source_table = null;
        
        if (strpos($application_id, 'enq_') === 0) {
            $actual_id = str_replace('enq_', '', $application_id);
            $source_table = $wpdb->prefix . 'edubot_enquiries';
        } elseif (strpos($application_id, 'app_') === 0) {
            $actual_id = str_replace('app_', '', $application_id);
            $source_table = $wpdb->prefix . 'edubot_applications';
        }
        
        // Try to get from wp_edubot_applications first (newer form submissions with UTM data)
        if ($source_table !== $wpdb->prefix . 'edubot_enquiries') {
            $app_table = $wpdb->prefix . 'edubot_applications';
            $application = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $app_table WHERE id = %d",
                $actual_id
            ), ARRAY_A);
            
            if ($application) {
                // Already has the structure we need from wp_edubot_applications
                return array(
                    'id' => 'app_' . $application['id'],
                    'application_number' => $application['application_number'],
                    'student_data' => $application['student_data'],
                    'status' => $application['status'],
                    'source' => $application['source'] ?? 'application_form',
                    'created_at' => $application['created_at'],
                    'ip_address' => $application['ip_address'] ?? null,
                    'user_agent' => $application['user_agent'] ?? null,
                    'utm_data' => $application['utm_data'] ?? null,
                    'gclid' => $application['gclid'] ?? null,
                    'fbclid' => $application['fbclid'] ?? null,
                    'click_id_data' => $application['click_id_data'] ?? null
                );
            }
        }
        
        // Fallback to wp_edubot_enquiries (older chatbot submissions)
        $enquiry_table = $wpdb->prefix . 'edubot_enquiries';
        
        $enquiry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $enquiry_table WHERE id = %d",
            $actual_id
        ), ARRAY_A);
        
        if ($enquiry) {
            // Convert enquiry to application format
            return array(
                'id' => 'enq_' . $enquiry['id'],
                'application_number' => $enquiry['enquiry_number'],
                'student_data' => json_encode(array(
                    'student_name' => $enquiry['student_name'],
                    'date_of_birth' => $enquiry['date_of_birth'] ?? '',
                    'grade' => $enquiry['grade'] ?? '',
                    'educational_board' => $enquiry['board'] ?? '',
                    'academic_year' => $enquiry['academic_year'] ?? '',
                    'parent_name' => $enquiry['parent_name'],
                    'email' => $enquiry['email'],
                    'phone' => $enquiry['phone'],
                    'address' => $enquiry['address'] ?? '',
                    'gender' => $enquiry['gender'] ?? ''
                )),
                'status' => $enquiry['status'],
                'source' => $enquiry['source'] ?? 'chatbot',
                'created_at' => $enquiry['created_at'],
                'ip_address' => $enquiry['ip_address'] ?? null,
                'user_agent' => $enquiry['user_agent'] ?? null,
                'utm_data' => $enquiry['utm_data'] ?? null,
                'gclid' => $enquiry['gclid'] ?? null,
                'fbclid' => $enquiry['fbclid'] ?? null,
                'click_id_data' => $enquiry['click_id_data'] ?? null,
                'whatsapp_sent' => $enquiry['whatsapp_sent'] ?? 0,
                'email_sent' => $enquiry['email_sent'] ?? 0,
                'sms_sent' => $enquiry['sms_sent'] ?? 0
            );
        }
        
        return null;
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
     * Update application status (only in edubot_enquiries table)
     */
    public function update_application_status($application_id, $status) {
        global $wpdb;

        // Remove 'enq_' prefix if present
        if (strpos($application_id, 'enq_') === 0) {
            $actual_id = str_replace('enq_', '', $application_id);
        } else {
            $actual_id = $application_id;
        }
        
        $table = $wpdb->prefix . 'edubot_enquiries';
        
        return $wpdb->update(
            $table,
            array('status' => $status),
            array('id' => $actual_id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Delete application from database (only from edubot_enquiries table)
     */
    public function delete_application($application_id) {
        global $wpdb;

        // Log the deletion for audit purposes
        error_log("EduBot: Deleting enquiry ID {$application_id}");

        // Remove 'enq_' prefix if present
        if (strpos($application_id, 'enq_') === 0) {
            $actual_id = str_replace('enq_', '', $application_id);
        } else {
            $actual_id = $application_id;
        }
        
        $table = $wpdb->prefix . 'edubot_enquiries';
        
        $result = $wpdb->delete(
            $table,
            array('id' => $actual_id),
            array('%d')
        );

        if ($result !== false) {
            error_log("EduBot: Successfully deleted enquiry ID {$application_id}");
        } else {
            error_log("EduBot: Failed to delete enquiry ID {$application_id}: " . $wpdb->last_error);
        }

        return $result !== false;
    }

    /**
     * Get applications with pagination (only from edubot_enquiries table)
     */
    public function get_applications($page = 1, $per_page = 20, $filters = array()) {
        global $wpdb;
        
        // Only use enquiries table to show enquiries
        $enquiries_table = $wpdb->prefix . 'edubot_enquiries';
        
        $all_applications = array();

        // Get from enquiries table only
        if ($wpdb->get_var("SHOW TABLES LIKE '$enquiries_table'") == $enquiries_table) {
            $all_applications = $this->get_from_enquiries_table(0, $filters);
        }

        // Sort by created_at descending
        usort($all_applications, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        // Apply pagination
        $total_records = count($all_applications);
        $offset = ($page - 1) * $per_page;
        $paginated_applications = array_slice($all_applications, $offset, $per_page);

        return array(
            'applications' => $paginated_applications,
            'total_records' => $total_records,
            'total_pages' => ceil($total_records / $per_page),
            'current_page' => $page
        );
    }



    /**
     * Get applications from enquiries table (where chatbot saves)
     */
    private function get_from_enquiries_table($site_id, $filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_enquiries';

        $where_clause = "WHERE 1=1"; // No site_id in enquiries table
        $where_values = array();

        // Add filters for enquiries table
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
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $query = "SELECT * FROM $table $where_clause ORDER BY created_at DESC";
        if (empty($where_values)) {
            $enquiries = $wpdb->get_results($query, ARRAY_A);
        } else {
            $enquiries = $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
        }

        // Convert to standard format
        $formatted_applications = array();
        foreach ($enquiries as $enquiry) {
            $formatted_applications[] = array(
                'id' => 'enq_' . $enquiry['id'], // Prefix to avoid ID conflicts
                'application_number' => $enquiry['enquiry_number'],
                'student_name' => $enquiry['student_name'] ?? 'N/A',
                'parent_name' => $enquiry['parent_name'] ?? 'N/A', 
                'grade' => $enquiry['grade'] ?? 'N/A',
                'educational_board' => $enquiry['board'] ?? 'N/A',
                'academic_year' => $enquiry['academic_year'] ?? 'N/A',
                'email' => $enquiry['email'] ?? 'N/A',
                'phone' => $enquiry['phone'] ?? 'N/A',
                'created_at' => $enquiry['created_at'],
                'status' => $enquiry['status'],
                'source' => 'chatbot',
                'student_data' => json_encode(array(
                    'student_name' => $enquiry['student_name'] ?? '',
                    'date_of_birth' => $enquiry['date_of_birth'] ?? '',
                    'grade' => $enquiry['grade'] ?? '',
                    'educational_board' => $enquiry['board'] ?? '',
                    'academic_year' => $enquiry['academic_year'] ?? '',
                    'parent_name' => $enquiry['parent_name'] ?? '',
                    'email' => $enquiry['email'] ?? '',
                    'phone' => $enquiry['phone'] ?? '',
                    'address' => $enquiry['address'] ?? '',
                    'gender' => $enquiry['gender'] ?? ''
                )),
                // Add new tracking fields
                'ip_address' => $enquiry['ip_address'] ?? null,
                'user_agent' => $enquiry['user_agent'] ?? null,
                'utm_data' => $enquiry['utm_data'] ?? null,
                'whatsapp_sent' => $enquiry['whatsapp_sent'] ?? 0,
                'email_sent' => $enquiry['email_sent'] ?? 0,
                'sms_sent' => $enquiry['sms_sent'] ?? 0
            );
        }

        return $formatted_applications;
    }

    /**
     * Get analytics data (from edubot_enquiries table only)
     */
    public function get_analytics_data($date_range = 30) {
        global $wpdb;
        $enquiries_table = $wpdb->prefix . 'edubot_enquiries';
        $analytics_table = $wpdb->prefix . 'edubot_analytics';
        $site_id = get_current_blog_id();

        $date_from = date('Y-m-d', strtotime("-{$date_range} days"));

        // Total enquiries
        $total_applications = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $enquiries_table WHERE created_at >= %s",
            $date_from
        ));

        // Enquiries by status
        $applications_by_status = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count FROM $enquiries_table 
             WHERE created_at >= %s 
             GROUP BY status",
            $date_from
        ), ARRAY_A);

        // Check if analytics table exists
        $analytics_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$analytics_table'") == $analytics_table;
        
        // Conversion rate (completed enquiries / total sessions)
        if ($analytics_table_exists) {
            $analytics_columns = $wpdb->get_col("SHOW COLUMNS FROM $analytics_table");
            $has_site_id = in_array('site_id', $analytics_columns);
            
            if ($has_site_id) {
                $total_sessions = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT session_id) FROM $analytics_table 
                     WHERE site_id = %d AND timestamp >= %s",
                    $site_id, $date_from
                ));
            } else {
                $total_sessions = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT session_id) FROM $analytics_table 
                     WHERE timestamp >= %s",
                    $date_from
                ));
            }
        } else {
            // Fallback: estimate sessions based on unique enquiries per day
            $total_sessions = $total_applications > 0 ? $total_applications : 1;
        }

        $conversion_rate = $total_sessions > 0 ? round(($total_applications / $total_sessions) * 100, 2) : 0;

        // Average completion time
        if ($analytics_table_exists) {
            if ($has_site_id) {
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
            } else {
                $avg_completion_time = $wpdb->get_var($wpdb->prepare(
                    "SELECT AVG(completion_minutes) FROM (
                        SELECT TIMESTAMPDIFF(MINUTE, 
                            (SELECT MIN(timestamp) FROM $analytics_table a2 
                             WHERE a2.session_id = a1.session_id),
                            MAX(timestamp)
                         ) as completion_minutes
                         FROM $analytics_table a1 
                         WHERE timestamp >= %s 
                         GROUP BY session_id
                         HAVING completion_minutes > 0
                     ) as session_times",
                    $date_from
                ));
            }
        } else {
            // Fallback: assume average 5 minutes completion time
            $avg_completion_time = 5.0;
        }

        // Applications over time
        $applications_over_time = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM $enquiries_table 
             WHERE created_at >= %s 
             GROUP BY DATE(created_at) 
             ORDER BY date",
            $date_from
        ), ARRAY_A);

        // Grade distribution - check if student_data column exists, fallback to grade column
        $columns = $wpdb->get_col("SHOW COLUMNS FROM $enquiries_table");
        if (in_array('student_data', $columns)) {
            // Use JSON extraction if student_data exists
            $grade_distribution = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    COALESCE(
                        JSON_UNQUOTE(JSON_EXTRACT(student_data, '$.grade')), 
                        grade
                    ) as grade,
                    COUNT(*) as count
                 FROM $enquiries_table 
                 WHERE created_at >= %s 
                 GROUP BY COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(student_data, '$.grade')), 
                    grade
                 )",
                $date_from
            ), ARRAY_A);
        } else {
            // Use grade column directly
            $grade_distribution = $wpdb->get_results($wpdb->prepare(
                "SELECT grade, COUNT(*) as count
                 FROM $enquiries_table 
                 WHERE created_at >= %s AND grade IS NOT NULL
                 GROUP BY grade",
                $date_from
            ), ARRAY_A);
        }

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
     * Clean up old sessions with enhanced security
     */
    public function cleanup_old_sessions($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_sessions';
        $site_id = get_current_blog_id();

        // Validate input
        $days = absint($days);
        if ($days < 1 || $days > 365) {
            $days = 30; // Default fallback
        }

        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE site_id = %d AND last_activity < %s",
            $site_id, $date_threshold
        ));

        if ($result !== false) {
            error_log("EduBot: Cleaned up {$result} old sessions (older than {$days} days)");
        }

        return $result;
    }

    /**
     * Clean up old analytics data with enhanced security
     */
    public function cleanup_old_analytics($days = 90) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_analytics';
        $site_id = get_current_blog_id();

        // Validate input
        $days = absint($days);
        if ($days < 7 || $days > 365) {
            $days = 90; // Default fallback
        }

        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE site_id = %d AND timestamp < %s",
            $site_id, $date_threshold
        ));

        if ($result !== false) {
            error_log("EduBot: Cleaned up {$result} old analytics records (older than {$days} days)");
        }

        return $result;
    }

    /**
     * Export applications to CSV with security validation
     */
    public function export_applications_csv($filters = array()) {
        // Security check - verify user capability
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access.');
        }
        
        // Verify nonce for CSRF protection
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'edubot_export_applications')) {
            wp_die('Security check failed.');
        }
        
        $applications = $this->get_applications(1, 10000, $filters);
        
        $filename = 'edubot_applications_' . date('Y-m-d') . '.csv';
        
        // Sanitize filename
        $filename = sanitize_file_name($filename);
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . esc_attr($filename) . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fputs($output, "\xEF\xBB\xBF");
        
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
            
            // Sanitize and validate data before export
            fputcsv($output, array(
                esc_html($app['application_number']),
                isset($student_data['student_name']) ? esc_html($student_data['student_name']) : '',
                isset($student_data['grade']) ? esc_html($student_data['grade']) : '',
                isset($student_data['parent_name']) ? esc_html($student_data['parent_name']) : '',
                isset($student_data['phone']) ? esc_html($student_data['phone']) : '',
                isset($student_data['email']) ? sanitize_email($student_data['email']) : '',
                esc_html($app['status']),
                esc_html($app['created_at'])
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
     * Works with both applications and enquiries tables
     */
    public function update_notification_status($id, $notification_type, $status = 1, $table_type = 'enquiries') {
        global $wpdb;
        
        // Determine which table to use
        $table = ($table_type === 'applications') 
            ? $wpdb->prefix . 'edubot_applications'
            : $wpdb->prefix . 'edubot_enquiries';
        
        $site_id = get_current_blog_id();

        $field_map = array(
            'whatsapp' => 'whatsapp_sent',
            'email' => 'email_sent',
            'sms' => 'sms_sent'
        );

        if (!isset($field_map[$notification_type])) {
            return false;
        }

        // Check if site_id column exists in the table
        $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
        $has_site_id = in_array('site_id', $columns);
        
        if ($has_site_id) {
            // Use site_id if it exists (for legacy applications table)
            return $wpdb->update(
                $table,
                array($field_map[$notification_type] => $status),
                array('id' => $id, 'site_id' => $site_id),
                array('%d'),
                array('%d', '%d')
            );
        } else {
            // Use only id for enquiries table (no site_id)
            return $wpdb->update(
                $table,
                array($field_map[$notification_type] => $status),
                array('id' => $id),
                array('%d'),
                array('%d')
            );
        }
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

    /**
     * Ensure all required database tables exist
     * This method can be called to create missing tables
     */
    public function ensure_tables_exist() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $missing_tables = array();
        
        // Check which tables are missing
        $required_tables = array(
            'edubot_school_configs',
            'edubot_applications', 
            'edubot_analytics',
            'edubot_sessions',
            'edubot_security_log',
            'edubot_visitor_analytics',
            'edubot_visitors'
        );
        
        foreach ($required_tables as $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
            
            if ($table_exists != $full_table_name) {
                $missing_tables[] = $table_name;
            }
        }
        
        if (empty($missing_tables)) {
            return array('success' => true, 'message' => 'All tables exist');
        }
        
        // Create missing tables
        $created_tables = $this->create_missing_tables($missing_tables, $charset_collate);
        
        return array(
            'success' => !empty($created_tables),
            'missing_tables' => $missing_tables,
            'created_tables' => $created_tables,
            'message' => count($created_tables) . ' tables created successfully'
        );
    }
    
    /**
     * Create missing database tables
     */
    private function create_missing_tables($missing_tables, $charset_collate) {
        global $wpdb;
        
        $created_tables = array();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($missing_tables as $table_name) {
            $sql = $this->get_table_sql($table_name, $charset_collate);
            if ($sql) {
                $result = dbDelta($sql);
                if (!empty($result)) {
                    $created_tables[] = $table_name;
                    error_log("EduBot Pro: Created missing table: " . $wpdb->prefix . $table_name);
                }
            }
        }
        
        return $created_tables;
    }
    
    /**
     * Get SQL for creating specific table
     */
    private function get_table_sql($table_name, $charset_collate) {
        global $wpdb;
        
        switch ($table_name) {
            case 'edubot_visitor_analytics':
                $table = $wpdb->prefix . 'edubot_visitor_analytics';
                return "CREATE TABLE $table (
                    id bigint(20) NOT NULL AUTO_INCREMENT,
                    site_id bigint(20) NOT NULL,
                    visitor_id varchar(255) NOT NULL,
                    session_id varchar(255) NOT NULL,
                    page_url text NOT NULL,
                    referrer_url text,
                    user_agent text,
                    ip_address varchar(45),
                    country varchar(100),
                    city varchar(100),
                    device_type varchar(50),
                    browser varchar(50),
                    os varchar(50),
                    screen_resolution varchar(20),
                    time_on_page int(11) DEFAULT 0,
                    interactions_count int(11) DEFAULT 0,
                    conversion_event varchar(100),
                    utm_source varchar(100),
                    utm_medium varchar(100),
                    utm_campaign varchar(100),
                    timestamp datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY site_id (site_id),
                    KEY visitor_id (visitor_id),
                    KEY session_id (session_id),
                    KEY timestamp (timestamp),
                    KEY conversion_event (conversion_event)
                ) $charset_collate;";
                
            case 'edubot_visitors':
                $table = $wpdb->prefix . 'edubot_visitors';
                return "CREATE TABLE $table (
                    id bigint(20) NOT NULL AUTO_INCREMENT,
                    site_id bigint(20) NOT NULL,
                    visitor_id varchar(255) NOT NULL,
                    email varchar(255),
                    phone varchar(20),
                    name varchar(255),
                    first_visit datetime DEFAULT CURRENT_TIMESTAMP,
                    last_activity datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    total_visits int(11) DEFAULT 1,
                    total_time_spent int(11) DEFAULT 0,
                    pages_visited int(11) DEFAULT 0,
                    interactions_count int(11) DEFAULT 0,
                    lead_score int(11) DEFAULT 0,
                    status varchar(50) DEFAULT 'anonymous',
                    is_returning tinyint(1) DEFAULT 0,
                    marketing_source varchar(100),
                    conversion_status varchar(50) DEFAULT 'none',
                    notes longtext,
                    custom_data longtext,
                    PRIMARY KEY (id),
                    UNIQUE KEY visitor_id (visitor_id, site_id),
                    KEY site_id (site_id),
                    KEY email (email),
                    KEY status (status),
                    KEY is_returning (is_returning),
                    KEY last_activity (last_activity),
                    KEY marketing_source (marketing_source)
                ) $charset_collate;";
                
            case 'edubot_school_configs':
                $table = $wpdb->prefix . 'edubot_school_configs';
                return "CREATE TABLE $table (
                    id bigint(20) NOT NULL AUTO_INCREMENT,
                    site_id bigint(20) NOT NULL,
                    school_name varchar(255) NOT NULL,
                    config_data longtext NOT NULL,
                    api_keys_encrypted longtext,
                    branding_settings longtext,
                    academic_structure longtext,
                    board_settings longtext,
                    academic_year_settings longtext,
                    status varchar(20) DEFAULT 'active',
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY site_id (site_id)
                ) $charset_collate;";
                
            case 'edubot_applications':
                $table = $wpdb->prefix . 'edubot_applications';
                return "CREATE TABLE $table (
                    id bigint(20) NOT NULL AUTO_INCREMENT,
                    site_id bigint(20) NOT NULL,
                    application_number varchar(50) NOT NULL,
                    student_data longtext NOT NULL,
                    custom_fields_data longtext,
                    conversation_log longtext,
                    status varchar(50) DEFAULT 'pending',
                    source varchar(50) DEFAULT 'chatbot',
                    ip_address varchar(45),
                    user_agent text,
                    utm_data longtext,
                    whatsapp_sent tinyint(1) DEFAULT 0,
                    email_sent tinyint(1) DEFAULT 0,
                    sms_sent tinyint(1) DEFAULT 0,
                    follow_up_scheduled datetime,
                    assigned_to bigint(20),
                    priority varchar(20) DEFAULT 'normal',
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY application_number (application_number),
                    KEY site_id (site_id),
                    KEY status (status),
                    KEY created_at (created_at)
                ) $charset_collate;";
                
            case 'edubot_analytics':
                $table = $wpdb->prefix . 'edubot_analytics';
                return "CREATE TABLE $table (
                    id bigint(20) NOT NULL AUTO_INCREMENT,
                    site_id bigint(20) NOT NULL,
                    session_id varchar(255) NOT NULL,
                    event_type varchar(50) NOT NULL,
                    event_data longtext,
                    ip_address varchar(45),
                    user_agent text,
                    timestamp datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY site_id (site_id),
                    KEY session_id (session_id),
                    KEY event_type (event_type),
                    KEY timestamp (timestamp)
                ) $charset_collate;";
                
            case 'edubot_sessions':
                $table = $wpdb->prefix . 'edubot_sessions';
                return "CREATE TABLE $table (
                    id bigint(20) NOT NULL AUTO_INCREMENT,
                    site_id bigint(20) NOT NULL,
                    session_id varchar(255) NOT NULL,
                    user_data longtext,
                    conversation_state longtext,
                    current_step varchar(100),
                    started_at datetime DEFAULT CURRENT_TIMESTAMP,
                    last_activity datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    completed_at datetime,
                    status varchar(50) DEFAULT 'active',
                    ip_address varchar(45),
                    user_agent text,
                    PRIMARY KEY (id),
                    UNIQUE KEY session_id (session_id),
                    KEY site_id (site_id),
                    KEY status (status),
                    KEY last_activity (last_activity)
                ) $charset_collate;";
                
            case 'edubot_security_log':
                $table = $wpdb->prefix . 'edubot_security_log';
                return "CREATE TABLE $table (
                    id bigint(20) NOT NULL AUTO_INCREMENT,
                    site_id bigint(20) NOT NULL,
                    event_type varchar(100) NOT NULL,
                    ip_address varchar(45) NOT NULL,
                    user_agent text,
                    details longtext,
                    severity varchar(20) DEFAULT 'medium',
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY site_id (site_id),
                    KEY event_type (event_type),
                    KEY ip_address (ip_address),
                    KEY created_at (created_at),
                    KEY severity (severity)
                ) $charset_collate;";
                
            default:
                return false;
        }
    }

    /**
     * Get enquiry by enquiry number
     */
    public function get_enquiry_by_number($enquiry_number) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_enquiries';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE enquiry_number = %s",
            $enquiry_number
        ), ARRAY_A);
    }
    
    /**
     * Get enquiries with notification status
     */
    public function get_enquiries_by_notification_status($notification_type, $status = 0, $limit = 100) {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_enquiries';
        $valid_types = array('whatsapp_sent', 'email_sent', 'sms_sent');
        
        if (!in_array($notification_type, $valid_types)) {
            return array();
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE $notification_type = %d ORDER BY created_at DESC LIMIT %d",
            $status, $limit
        ), ARRAY_A);
    }
}
