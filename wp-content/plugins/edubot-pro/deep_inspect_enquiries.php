<?php
/**
 * Deep Database Inspection - Check Actual Enquiry Data
 */

require_once('/xampp/htdocs/demo/wp-load.php');

global $wpdb;
$table = $wpdb->prefix . 'edubot_enquiries';

// Get the last 3 enquiries
$enquiries = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC LIMIT 3", ARRAY_A);

echo "<h1>📊 Database Enquiry Records</h1>";
echo "<hr>";

if (!empty($enquiries)) {
    foreach ($enquiries as $idx => $enq) {
        echo "<h2>Record #" . ($idx + 1) . " - " . $enq['enquiry_number'] . "</h2>";
        echo "<table style='border-collapse: collapse; width: 100%; margin: 15px 0; font-size: 13px;'>";
        echo "<tr style='background: #f0f0f0;'><td style='padding: 8px; border: 1px solid #ccc;'><strong>Field</strong></td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'><strong>Value</strong></td>";
        echo "<td style='padding: 8px; border: 1px solid #ccc;'><strong>Status</strong></td></tr>";
        
        foreach ($enq as $key => $value) {
            // Skip large fields for readability
            if (in_array($key, ['utm_data', 'click_id_data', 'notes'])) {
                continue;
            }
            
            $display_value = $value === null || $value === '' ? '(empty)' : $value;
            if (strlen($display_value) > 100) {
                $display_value = substr($display_value, 0, 100) . '...';
            }
            
            $status = ($value === null || $value === '' || $value === '0000-00-00') ? '❌ EMPTY' : '✅ SET';
            
            echo "<tr>";
            echo "<td style='padding: 8px; border: 1px solid #ccc;'><strong>{$key}</strong></td>";
            echo "<td style='padding: 8px; border: 1px solid #ccc;'>{$display_value}</td>";
            echo "<td style='padding: 8px; border: 1px solid #ccc;'>{$status}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Parse utm_data if exists
        if (!empty($enq['utm_data'])) {
            $utm = json_decode($enq['utm_data'], true);
            echo "<h3>📊 UTM Data</h3>";
            echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
            print_r($utm);
            echo "</pre>";
        }
        
        echo "<hr>";
    }
} else {
    echo "<p>❌ No enquiries found in database</p>";
}

?>
