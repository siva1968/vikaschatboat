<?php
/**
 * View Enquiries by Source - Dashboard
 */

require_once dirname(__FILE__) . '/wp-load.php';

global $wpdb;

echo "<h1>📊 Enquiries by Source - Dashboard</h1>";

echo "<h2>📈 Source Distribution</h2>";

// Get enquiries grouped by source
$query = "SELECT source, COUNT(*) as count FROM {$wpdb->prefix}edubot_enquiries GROUP BY source ORDER BY count DESC";
$results = $wpdb->get_results($query);

if ($results) {
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #007cba; color: white;'>";
    echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Source</th>";
    echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: center;'>Count</th>";
    echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Percentage</th>";
    echo "</tr>";
    
    $total = 0;
    foreach ($results as $row) {
        $total += $row->count;
    }
    
    $colors = array('chatbot' => '#28a745', 'application_form' => '#007cba', 'direct_api' => '#ffc107', 'manual' => '#6c757d', 'import' => '#e83e8c');
    
    foreach ($results as $row) {
        $percentage = ($total > 0) ? round(($row->count / $total) * 100, 1) : 0;
        $color = isset($colors[$row->source]) ? $colors[$row->source] : '#999';
        $bar_width = $percentage * 3;
        
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 10px;'><strong>" . htmlspecialchars($row->source) . "</strong></td>";
        echo "<td style='border: 1px solid #ddd; padding: 10px; text-align: center;'><strong>" . $row->count . "</strong></td>";
        echo "<td style='border: 1px solid #ddd; padding: 10px;'>";
        echo "<div style='background: " . $color . "; width: " . $bar_width . "px; height: 20px; border-radius: 3px; display: inline-block;'></div>";
        echo " " . $percentage . "%";
        echo "</td>";
        echo "</tr>";
    }
    
    echo "<tr style='background: #f0f0f0;'>";
    echo "<td style='border: 1px solid #ddd; padding: 10px;'><strong>TOTAL</strong></td>";
    echo "<td style='border: 1px solid #ddd; padding: 10px; text-align: center;'><strong>" . $total . "</strong></td>";
    echo "<td style='border: 1px solid #ddd; padding: 10px;'><strong>100%</strong></td>";
    echo "</tr>";
    
    echo "</table>";
} else {
    echo "<p style='color: #999;'>No enquiries found</p>";
}

echo "<h2>📝 Recent Enquiries by Source</h2>";

$sources = array('chatbot', 'application_form', 'direct_api', 'manual', 'import');

foreach ($sources as $source) {
    $query = "SELECT * FROM {$wpdb->prefix}edubot_enquiries WHERE source = %s ORDER BY created_at DESC LIMIT 5";
    $enquiries = $wpdb->get_results($wpdb->prepare($query, $source));
    
    if ($enquiries) {
        echo "<h3 style='color: #007cba;'>Source: <code>" . htmlspecialchars($source) . "</code></h3>";
        
        echo "<table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Enquiry #</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Student</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Email</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Phone</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Date</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Status</th>";
        echo "</tr>";
        
        foreach ($enquiries as $enquiry) {
            echo "<tr>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'><code>" . htmlspecialchars($enquiry->enquiry_number) . "</code></td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($enquiry->student_name) . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($enquiry->email) . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($enquiry->phone) . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars(substr($enquiry->created_at, 0, 10)) . "</td>";
            
            $status_color = ($enquiry->status === 'new') ? 'green' : 'orange';
            echo "<td style='border: 1px solid #ddd; padding: 8px; color: " . $status_color . ";'><strong>" . htmlspecialchars($enquiry->status) . "</strong></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
}

echo "<h2>🔄 Actions</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<a href='http://localhost/demo/test_different_sources.php' class='button button-primary'>Back to Testing</a>";
echo "<a href='http://localhost/demo/debug_log_viewer.php' class='button'>View Debug Log</a>";
echo "</div>";

?>
