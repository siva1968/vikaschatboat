<?php
require '/xampp/htdocs/demo/wp-load.php';

// Create test data with utm_data
$db_manager = new EduBot_Database_Manager();

$application_data = array(
    'application_number' => 'DEBUG2-' . time(),
    'student_data' => array(
        'student_name' => 'Debug2',
        'email' => 'debug2@test.com',
        'phone' => '9999999999',
        'parent_name' => 'Parent',
        'date_of_birth' => '2010-05-15',
        'grade' => 'X',
        'address' => 'Test'
    ),
    'conversation_log' => array('source' => 'test'),
    'status' => 'pending',
    'source' => 'test',
    'utm_data' => json_encode(array('utm_source' => 'google')),
    'gclid' => 'test_gclid',
    'fbclid' => 'test_fbclid',
    'click_id_data' => json_encode(array('test' => 'data'))
);

echo "<p>Calling save_application...</p>";
$result = $db_manager->save_application($application_data);

echo "<p>Result: $result</p>";

// Now check debug.log
echo "<p>Checking debug log...</p>";

$log_file = '/xampp/htdocs/demo/wp-content/debug.log';
if (file_exists($log_file)) {
    $lines = array_slice(file($log_file), -10);
    echo "<pre>";
    foreach ($lines as $line) {
        if (strpos($line, 'EduBot') !== false) {
            echo $line;
        }
    }
    echo "</pre>";
} else {
    echo "<p>Log file not found at: {$log_file}</p>";
    
    // Try to enable it
    echo "<p>Log file may be in wp-admin/includes/upgrade.php or error_log() goes to syslog</p>";
    
    // Check php.ini setting for error_log
    echo "<p>PHP error_log setting: " . ini_get('error_log') . "</p>";
}

?>
