<?php
/**
 * Test script to diagnose enquiry and application creation issues
 */

// Load WordPress
require_once dirname(__FILE__) . '/wp-load.php';

echo "<h1>Enquiry and Application Creation Diagnostic</h1>";

global $wpdb;

// 1. Check table existence
echo "<h2>1. Table Status</h2>";

$tables_to_check = [
    'edubot_enquiries' => $wpdb->prefix . 'edubot_enquiries',
    'edubot_applications' => $wpdb->prefix . 'edubot_applications'
];

foreach ($tables_to_check as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    $status = $exists ? '✅ EXISTS' : '❌ MISSING';
    echo "<p><strong>$name:</strong> $status</p>";
    
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "<p style='margin-left: 20px; color: #666;'>Total records: <strong>$count</strong></p>";
    }
}

// 2. Check table structure
echo "<h2>2. Applications Table Structure</h2>";

$app_table = $wpdb->prefix . 'edubot_applications';
if ($wpdb->get_var("SHOW TABLES LIKE '$app_table'") === $app_table) {
    $columns = $wpdb->get_results("DESCRIBE $app_table");
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . esc_html($col->Field) . "</td>";
        echo "<td>" . esc_html($col->Type) . "</td>";
        echo "<td>" . esc_html($col->{'Null'}) . "</td>";
        echo "<td>" . esc_html($col->Key) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'><strong>Applications table does not exist!</strong></p>";
}

// 3. Check recent enquiries
echo "<h2>3. Recent Enquiries</h2>";

$enq_table = $wpdb->prefix . 'edubot_enquiries';
if ($wpdb->get_var("SHOW TABLES LIKE '$enq_table'") === $enq_table) {
    $recent_enq = $wpdb->get_results(
        "SELECT id, enquiry_number, student_name, email, created_at 
         FROM $enq_table 
         ORDER BY created_at DESC 
         LIMIT 5"
    );
    
    if ($recent_enq) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Enquiry #</th><th>Student</th><th>Email</th><th>Created</th></tr>";
        foreach ($recent_enq as $enq) {
            echo "<tr>";
            echo "<td>$enq->id</td>";
            echo "<td><strong>$enq->enquiry_number</strong></td>";
            echo "<td>$enq->student_name</td>";
            echo "<td>$enq->email</td>";
            echo "<td>" . date('M d, Y H:i', strtotime($enq->created_at)) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No enquiries found.</p>";
    }
} else {
    echo "<p style='color: red;'>Enquiries table does not exist!</p>";
}

// 4. Check recent applications
echo "<h2>4. Recent Applications</h2>";

if ($wpdb->get_var("SHOW TABLES LIKE '$app_table'") === $app_table) {
    $recent_apps = $wpdb->get_results(
        "SELECT id, application_number, student_data, status, created_at 
         FROM $app_table 
         ORDER BY created_at DESC 
         LIMIT 5"
    );
    
    if ($recent_apps) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Application #</th><th>Student Name</th><th>Status</th><th>Created</th></tr>";
        foreach ($recent_apps as $app) {
            $data = json_decode($app->student_data, true);
            $student_name = $data['student_name'] ?? 'N/A';
            echo "<tr>";
            echo "<td>$app->id</td>";
            echo "<td><strong>$app->application_number</strong></td>";
            echo "<td>$student_name</td>";
            echo "<td>" . ucfirst($app->status) . "</td>";
            echo "<td>" . date('M d, Y H:i', strtotime($app->created_at)) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>⚠️ No applications found in the table!</strong></p>";
    }
} else {
    echo "<p style='color: red;'>Applications table does not exist!</p>";
}

// 5. Compare recent entries
echo "<h2>5. Comparison</h2>";

if ($wpdb->get_var("SHOW TABLES LIKE '$enq_table'") === $enq_table && 
    $wpdb->get_var("SHOW TABLES LIKE '$app_table'") === $app_table) {
    
    $enq_count = $wpdb->get_var("SELECT COUNT(*) FROM $enq_table");
    $app_count = $wpdb->get_var("SELECT COUNT(*) FROM $app_table");
    
    echo "<p><strong>Enquiries:</strong> $enq_count</p>";
    echo "<p><strong>Applications:</strong> $app_count</p>";
    
    if ($enq_count > $app_count) {
        $missing = $enq_count - $app_count;
        echo "<p style='color: red; font-weight: bold;'>⚠️ $missing enquiry/enquiries NOT saved to applications table!</p>";
        
        echo "<h3>Missing Enquiries:</h3>";
        $missing_enq = $wpdb->get_results(
            "SELECT e.id, e.enquiry_number, e.student_name 
             FROM $enq_table e
             LEFT JOIN $app_table a ON e.enquiry_number = a.application_number
             WHERE a.id IS NULL
             ORDER BY e.created_at DESC"
        );
        
        if ($missing_enq) {
            echo "<ul>";
            foreach ($missing_enq as $enq) {
                echo "<li>$enq->enquiry_number - $enq->student_name</li>";
            }
            echo "</ul>";
        }
    } else if ($enq_count === $app_count) {
        echo "<p style='color: green; font-weight: bold;'>✅ All enquiries have corresponding applications!</p>";
    } else {
        echo "<p style='color: orange; font-weight: bold;'>⚠️ More applications than enquiries (unexpected)</p>";
    }
}

// 6. Check error log
echo "<h2>6. Recent Error Log (Last 20 lines)</h2>";

$log_file = ABSPATH . 'wp-content/debug.log';
if (file_exists($log_file)) {
    $lines = file($log_file);
    $recent_lines = array_slice($lines, -20);
    
    // Filter for EduBot related lines
    $edubot_lines = array_filter($recent_lines, function($line) {
        return strpos($line, 'EduBot') !== false;
    });
    
    if ($edubot_lines) {
        echo "<pre style='background: #f5f5f5; padding: 10px; overflow-x: auto; max-height: 400px;'>";
        foreach ($edubot_lines as $line) {
            echo esc_html($line);
        }
        echo "</pre>";
    } else {
        echo "<p>No EduBot entries in recent error log.</p>";
    }
} else {
    echo "<p>Debug log file not found at: $log_file</p>";
}

?>
