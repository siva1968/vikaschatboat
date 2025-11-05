<?php
/**
 * Force create missing applications table
 */

require_once dirname(__FILE__) . '/wp-load.php';

global $wpdb;

$table_applications = $wpdb->prefix . 'edubot_applications';
$charset_collate = $wpdb->get_charset_collate();

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_applications}'") === $table_applications;

if ($table_exists) {
    echo "<p style='color: green;'><strong>✅ Applications table already exists!</strong></p>";
    
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_applications");
    echo "<p>Total records: <strong>$count</strong></p>";
} else {
    echo "<p><strong>Creating applications table...</strong></p>";
    
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
    
    if ($wpdb->query($sql) !== false) {
        echo "<p style='color: green;'><strong>✅ Applications table created successfully!</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>❌ Failed to create applications table!</strong></p>";
        echo "<p>Error: " . $wpdb->last_error . "</p>";
        echo "<p>SQL: $sql</p>";
    }
}

// Now migrate existing enquiries to applications table
echo "<h2>Migration: Syncing Enquiries to Applications</h2>";

$enquiries_table = $wpdb->prefix . 'edubot_enquiries';

// Get enquiries that don't have corresponding applications
$missing = $wpdb->get_results(
    "SELECT e.id, e.enquiry_number, e.student_name, e.date_of_birth, e.grade, e.board, e.academic_year, e.parent_name, e.email, e.phone, e.address, e.gender, e.ip_address, e.user_agent, e.utm_data
     FROM $enquiries_table e
     LEFT JOIN $table_applications a ON e.enquiry_number = a.application_number
     WHERE a.id IS NULL"
);

if (!empty($missing)) {
    echo "<p>Found <strong>" . count($missing) . "</strong> enquiries missing from applications table.</p>";
    
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
            $table_applications,
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
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result !== false) {
            $migrated++;
            echo "<p style='color: green;'>✅ Migrated: " . esc_html($enquiry->enquiry_number) . " - " . esc_html($enquiry->student_name) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed: " . esc_html($enquiry->enquiry_number) . " - " . $wpdb->last_error . "</p>";
        }
    }
    
    echo "<p style='color: green; font-weight: bold;'>✅ Migrated $migrated enquiries to applications table</p>";
} else {
    echo "<p style='color: green;'><strong>✅ All enquiries already have corresponding applications!</strong></p>";
}

// Final verification
echo "<h2>Final Verification</h2>";

$enq_count = $wpdb->get_var("SELECT COUNT(*) FROM $enquiries_table");
$app_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_applications");

echo "<p><strong>Enquiries:</strong> $enq_count</p>";
echo "<p><strong>Applications:</strong> $app_count</p>";

if ($enq_count === $app_count) {
    echo "<p style='color: green; font-weight: bold;'>✅ All enquiries have corresponding applications!</p>";
} elseif ($app_count > $enq_count) {
    echo "<p style='color: orange;'>⚠️ More applications than enquiries</p>";
} else {
    echo "<p style='color: red;'>❌ Some enquiries missing from applications table</p>";
}

?>
