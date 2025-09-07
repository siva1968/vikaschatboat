<?php
/**
 * EduBot Pro - Analytics Diagnostic Script
 * 
 * Test analytics functionality and database queries
 * Access via: yourdomain.com/wp-content/plugins/AI ChatBoat/test_analytics.php
 */

// WordPress environment setup
if (!defined('ABSPATH')) {
    // Try to load WordPress
    $wp_load_paths = array(
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php'
    );
    
    foreach ($wp_load_paths as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            require_once __DIR__ . '/' . $path;
            break;
        }
    }
    
    if (!defined('ABSPATH')) {
        die('WordPress environment not found. Please run this from WordPress admin or place in correct directory.');
    }
}

// Security check - only admins can run this
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Administrator privileges required.');
}

echo "<h1>EduBot Pro - Analytics Diagnostic</h1>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . " UTC</p>";

global $wpdb;
$enquiries_table = $wpdb->prefix . 'edubot_enquiries';
$analytics_table = $wpdb->prefix . 'edubot_analytics';

echo "<h2>1. Class Availability Check</h2>";

// Check if required classes exist
$required_classes = array(
    'EduBot_Database_Manager',
    'EduBot_Visitor_Analytics'
);

foreach ($required_classes as $class) {
    $exists = class_exists($class);
    $status = $exists ? '✅ Available' : '❌ Missing';
    echo "<p><strong>{$class}:</strong> {$status}</p>";
}

echo "<h2>2. Database Tables Check</h2>";

// Check tables
$tables_to_check = array(
    'edubot_enquiries' => $enquiries_table,
    'edubot_analytics' => $analytics_table
);

foreach ($tables_to_check as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") == $table;
    $status = $exists ? '✅ EXISTS' : '❌ MISSING';
    echo "<p><strong>{$name}:</strong> {$status}</p>";
}

echo "<h2>3. Sample Data Check</h2>";

// Check if there's any data
$enquiry_count = $wpdb->get_var("SELECT COUNT(*) FROM {$enquiries_table}");
echo "<p><strong>Total Enquiries:</strong> {$enquiry_count}</p>";

if ($enquiry_count > 0) {
    // Show sample enquiry
    $sample = $wpdb->get_row("SELECT * FROM {$enquiries_table} ORDER BY created_at DESC LIMIT 1", ARRAY_A);
    echo "<p><strong>Latest Enquiry ID:</strong> {$sample['id']}</p>";
    echo "<p><strong>Created:</strong> {$sample['created_at']}</p>";
    echo "<p><strong>Has Click IDs:</strong> " . (!empty($sample['gclid']) || !empty($sample['fbclid']) ? 'Yes' : 'No') . "</p>";
}

echo "<h2>4. Analytics Query Test</h2>";

try {
    if (class_exists('EduBot_Database_Manager')) {
        $db_manager = new EduBot_Database_Manager();
        
        echo "<p><strong>Testing get_analytics_data method...</strong></p>";
        $analytics = $db_manager->get_analytics_data(30);
        
        echo "<ul>";
        echo "<li><strong>Total Applications:</strong> " . ($analytics['total_applications'] ?? 'N/A') . "</li>";
        echo "<li><strong>Conversion Rate:</strong> " . ($analytics['conversion_rate'] ?? 'N/A') . "%</li>";
        echo "<li><strong>Avg Completion Time:</strong> " . ($analytics['avg_completion_time'] ?? 'N/A') . " minutes</li>";
        echo "<li><strong>Total Sessions:</strong> " . ($analytics['total_sessions'] ?? 'N/A') . "</li>";
        echo "</ul>";
        
        // Status breakdown
        if (!empty($analytics['applications_by_status'])) {
            echo "<p><strong>Status Breakdown:</strong></p>";
            echo "<ul>";
            foreach ($analytics['applications_by_status'] as $status) {
                echo "<li>{$status['status']}: {$status['count']}</li>";
            }
            echo "</ul>";
        }
        
        // Grade distribution
        if (!empty($analytics['grade_distribution'])) {
            echo "<p><strong>Grade Distribution:</strong></p>";
            echo "<ul>";
            foreach ($analytics['grade_distribution'] as $grade) {
                echo "<li>" . ($grade['grade'] ?: 'No Grade') . ": {$grade['count']}</li>";
            }
            echo "</ul>";
        }
        
        echo "<p style='color: green;'>✅ Analytics method working correctly!</p>";
        
    } else {
        echo "<p style='color: red;'>❌ EduBot_Database_Manager class not available</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Analytics Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<h2>5. Visitor Analytics Test</h2>";

try {
    if (class_exists('EduBot_Visitor_Analytics')) {
        $visitor_analytics = new EduBot_Visitor_Analytics();
        
        echo "<p><strong>Testing visitor analytics...</strong></p>";
        
        // Check if method exists
        if (method_exists($visitor_analytics, 'get_visitor_analytics')) {
            $visitor_data = $visitor_analytics->get_visitor_analytics(30);
            
            echo "<ul>";
            echo "<li><strong>Total Visitors:</strong> " . ($visitor_data['total_visitors'] ?? 'N/A') . "</li>";
            echo "<li><strong>New Visitors:</strong> " . ($visitor_data['new_visitors'] ?? 'N/A') . "</li>";
            echo "<li><strong>Returning Visitors:</strong> " . ($visitor_data['returning_visitors'] ?? 'N/A') . "</li>";
            echo "<li><strong>Bounce Rate:</strong> " . ($visitor_data['bounce_rate'] ?? 'N/A') . "%</li>";
            echo "</ul>";
            
            echo "<p style='color: green;'>✅ Visitor analytics working!</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ get_visitor_analytics method not found</p>";
        }
        
    } else {
        echo "<p style='color: orange;'>⚠️ EduBot_Visitor_Analytics class not available</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Visitor Analytics Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<h2>6. Direct SQL Test</h2>";

// Test basic queries
try {
    echo "<p><strong>Testing direct SQL queries...</strong></p>";
    
    // Test enquiries query
    $result = $wpdb->get_results("SELECT created_at, status FROM {$enquiries_table} ORDER BY created_at DESC LIMIT 5", ARRAY_A);
    
    if ($result) {
        echo "<p style='color: green;'>✅ Direct enquiries query successful</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Created At</th><th>Status</th></tr>";
        foreach ($result as $row) {
            echo "<tr><td>{$row['created_at']}</td><td>{$row['status']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No enquiry data found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ SQL Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Diagnostic Summary:</strong></p>";
echo "<ul>";
echo "<li>If classes are missing, check plugin file includes</li>";
echo "<li>If tables are missing, run database migration</li>";
echo "<li>If queries fail, check database permissions</li>";
echo "<li>If no data exists, test enquiry submission</li>";
echo "</ul>";

echo "<p style='color: #666; font-size: 12px;'>Diagnostic completed at " . date('Y-m-d H:i:s') . " UTC</p>";
?>
