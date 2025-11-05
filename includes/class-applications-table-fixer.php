<?php
/**
 * Automatic fix hook - Ensures applications table exists
 * Call this from plugin update/upgrade process
 */

if (!function_exists('edubot_ensure_applications_table')) {
    
    function edubot_ensure_applications_table() {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'edubot_applications';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_applications}'") === $table_applications;
        
        if (!$table_exists) {
            error_log('EduBot: Applications table missing, creating...');
            
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE IF NOT EXISTS $table_applications (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                site_id BIGINT(20) NOT NULL,
                application_number VARCHAR(50) NOT NULL,
                student_data LONGTEXT NOT NULL,
                custom_fields_data LONGTEXT,
                conversation_log LONGTEXT,
                status VARCHAR(50) DEFAULT 'pending',
                source VARCHAR(50) DEFAULT 'chatbot',
                ip_address VARCHAR(45),
                user_agent TEXT,
                utm_data LONGTEXT,
                whatsapp_sent TINYINT(1) DEFAULT 0,
                email_sent TINYINT(1) DEFAULT 0,
                sms_sent TINYINT(1) DEFAULT 0,
                follow_up_scheduled DATETIME,
                assigned_to BIGINT(20),
                priority VARCHAR(20) DEFAULT 'normal',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY application_number (application_number),
                KEY site_id (site_id),
                KEY status (status),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            if ($wpdb->query($sql) === false) {
                error_log('EduBot: Failed to create applications table: ' . $wpdb->last_error);
                return false;
            }
            
            error_log('EduBot: Applications table created successfully');
            
            // Migrate existing enquiries to applications
            edubot_migrate_enquiries_to_applications();
            
            return true;
        }
        
        return true;
    }
    
    function edubot_migrate_enquiries_to_applications() {
        global $wpdb;
        
        $enquiries_table = $wpdb->prefix . 'edubot_enquiries';
        $applications_table = $wpdb->prefix . 'edubot_applications';
        
        // Get enquiries without corresponding applications
        $missing = $wpdb->get_results(
            "SELECT e.id, e.enquiry_number, e.student_name, e.date_of_birth, e.grade, 
                    e.board, e.academic_year, e.parent_name, e.email, e.phone, e.address, 
                    e.gender, e.ip_address, e.user_agent, e.utm_data, e.created_at
             FROM $enquiries_table e
             LEFT JOIN $applications_table a ON e.enquiry_number = a.application_number
             WHERE a.id IS NULL"
        );
        
        if (empty($missing)) {
            error_log('EduBot: No enquiries to migrate');
            return;
        }
        
        $migrated = 0;
        foreach ($missing as $enquiry) {
            $student_data = array(
                'student_name' => $enquiry->student_name,
                'date_of_birth' => $enquiry->date_of_birth,
                'grade' => $enquiry->grade,
                'educational_board' => $enquiry->board,
                'academic_year' => $enquiry->academic_year,
                'parent_name' => $enquiry->parent_name,
                'email' => $enquiry->email,
                'phone' => $enquiry->phone,
                'address' => $enquiry->address,
                'gender' => $enquiry->gender
            );
            
            $result = $wpdb->insert(
                $applications_table,
                array(
                    'site_id' => get_current_blog_id(),
                    'application_number' => $enquiry->enquiry_number,
                    'student_data' => wp_json_encode($student_data),
                    'status' => 'pending',
                    'source' => 'chatbot',
                    'ip_address' => $enquiry->ip_address,
                    'user_agent' => $enquiry->user_agent,
                    'utm_data' => $enquiry->utm_data,
                    'conversation_log' => wp_json_encode(array()),
                    'created_at' => $enquiry->created_at
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result !== false) {
                $migrated++;
                error_log('EduBot: Migrated enquiry ' . $enquiry->enquiry_number);
            } else {
                error_log('EduBot: Failed to migrate enquiry ' . $enquiry->enquiry_number . ': ' . $wpdb->last_error);
            }
        }
        
        error_log("EduBot: Migration complete - $migrated enquiries migrated to applications");
    }
}

// Only register the hook, don't check capabilities yet
add_action('plugins_loaded', function() {
    if (function_exists('edubot_ensure_applications_table')) {
        edubot_ensure_applications_table();
    }
}, 5);

?>
