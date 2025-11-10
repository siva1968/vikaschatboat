<?php
/**
 * Inspect actual button HTML being rendered
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

global $wpdb;
$app_table = $wpdb->prefix . 'edubot_applications';

echo "=== Checking Actual Button HTML ===\n\n";

// Get one application
$app = $wpdb->get_row("SELECT * FROM {$app_table} LIMIT 1", ARRAY_A);

if (!$app) {
    echo "No apps\n";
    exit;
}

echo "Application ID: {$app['id']}\n\n";

// Simulate the filter
EduBot_MCB_Admin::init();

$actions = array();
$result = apply_filters('edubot_applications_row_actions', $actions, $app);

echo "Filter result count: " . count($result) . "\n\n";

foreach ($result as $key => $html) {
    echo "Action: $key\n";
    echo "HTML: $html\n\n";
    
    // Parse the HTML to check attributes
    if (preg_match('/data-enquiry-id=["\']?(\d+)["\']?/', $html, $matches)) {
        echo "Extracted data-enquiry-id: {$matches[1]}\n\n";
    }
}

?>
