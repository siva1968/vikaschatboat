<?php
// Quick debug script to check what admin is getting
require_once('wp-config.php');
require_once('wp-load.php');
require_once('includes/class-database-manager.php');

// Simulate what the admin does
$database_manager = new EduBot_Database_Manager();
$page = 1;
$filters = array();

echo "<h2>Debug: Simulating Admin Page Load</h2>";

try {
    $applications_data = $database_manager->get_applications($page, 20, $filters);
    
    echo "<h3>Raw Database Manager Response:</h3>";
    echo "<pre>";
    print_r($applications_data);
    echo "</pre>";
    
    // Simulate the admin processing
    $applications = $applications_data['applications'] ?? array();
    $total_applications = $applications_data['total_records'] ?? 0;
    $total_pages = $applications_data['total_pages'] ?? 1;
    
    echo "<h3>Processed Variables:</h3>";
    echo "<p><strong>Applications Count:</strong> " . count($applications) . "</p>";
    echo "<p><strong>Total Applications:</strong> " . $total_applications . "</p>";
    echo "<p><strong>Total Pages:</strong> " . $total_pages . "</p>";
    
    echo "<h3>Applications Array:</h3>";
    echo "<pre>";
    print_r($applications);
    echo "</pre>";
    
    // Check the condition that determines if data shows
    $has_applications = isset($applications) && !empty($applications);
    echo "<h3>Display Logic:</h3>";
    echo "<p><strong>isset(\$applications):</strong> " . (isset($applications) ? 'true' : 'false') . "</p>";
    echo "<p><strong>!empty(\$applications):</strong> " . (!empty($applications) ? 'true' : 'false') . "</p>";
    echo "<p><strong>Will show data:</strong> " . ($has_applications ? 'YES' : 'NO') . "</p>";
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
