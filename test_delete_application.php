<?php
/**
 * Test Delete Application Function
 * Debug file to test the delete functionality
 */

// Load WordPress
require_once(__DIR__ . '/wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access Denied');
}

global $wpdb;

echo "<h1>Delete Application Test</h1>";

// Get a sample application to delete
$table = $wpdb->prefix . 'edubot_enquiries';
$sample = $wpdb->get_row("SELECT * FROM $table LIMIT 1");

if (!$sample) {
    echo "<p style='color: red;'>No applications found to test delete</p>";
    echo "<p>First create an application through the chatbot before testing delete.</p>";
    exit;
}

echo "<h2>Sample Application Found:</h2>";
echo "<ul>";
echo "<li><strong>ID:</strong> " . $sample->id . "</li>";
echo "<li><strong>Number:</strong> " . $sample->enquiry_number . "</li>";
echo "<li><strong>Name:</strong> " . $sample->student_name . "</li>";
echo "<li><strong>Email:</strong> " . $sample->email . "</li>";
echo "</ul>";

// Try to delete
echo "<h2>Attempting Delete...</h2>";

$result = $wpdb->delete(
    $table,
    array('id' => $sample->id),
    array('%d')
);

if ($result !== false) {
    echo "<p style='color: green;'><strong>✅ SUCCESS!</strong> Record deleted from database</p>";
    
    // Verify deletion
    $verify = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE id = %d", $sample->id));
    if ($verify == 0) {
        echo "<p style='color: green;'>✅ Verified: Record no longer exists in database</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Warning: Record still exists</p>";
    }
} else {
    echo "<p style='color: red;'><strong>❌ FAILED!</strong></p>";
    echo "<p>Error: " . $wpdb->last_error . "</p>";
}

// Show database structure
echo "<h2>Table Structure:</h2>";
$columns = $wpdb->get_results("DESCRIBE $table");
echo "<ul>";
foreach ($columns as $col) {
    echo "<li>" . $col->Field . " (" . $col->Type . ")</li>";
}
echo "</ul>";

// Show AJAX function code
echo "<h2>AJAX Handler Code:</h2>";
echo "<pre>" . htmlspecialchars(file_get_contents(WP_CONTENT_DIR . '/plugins/edubot-pro/admin/class-edubot-admin.php', false, null, 3290, 50)) . "</pre>";

?>
