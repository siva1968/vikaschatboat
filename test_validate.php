<?php
require '/xampp/htdocs/demo/wp-load.php';

// Test the validate_application_data method directly
$db_manager = new EduBot_Database_Manager();

$application_data = array(
    'application_number' => 'TEST123',
    'student_data' => array('name' => 'Test'),
    'conversation_log' => array('test' => 'log'),
    'status' => 'pending',
    'source' => 'test',
    'utm_data' => json_encode(array('utm_source' => 'google')),
    'gclid' => 'test_gclid',
    'fbclid' => 'test_fbclid',
    'click_id_data' => json_encode(array('click' => 'data'))
);

echo "Input keys: " . implode(', ', array_keys($application_data)) . "\n";
echo "Input has utm_data: " . (isset($application_data['utm_data']) ? 'YES' : 'NO') . "\n";
echo "Input utm_data value (first 50): '" . substr($application_data['utm_data'], 0, 50) . "'\n\n";

// Use reflection to call the private method
$reflection = new ReflectionClass('EduBot_Database_Manager');
$method = $reflection->getMethod('validate_application_data');
$method->setAccessible(true);

$validated = $method->invoke($db_manager, $application_data);

echo "\nAfter validation:\n";
echo "Output keys: " . implode(', ', array_keys($validated)) . "\n";
echo "Output has utm_data: " . (isset($validated['utm_data']) ? 'YES' : 'NO') . "\n";

if (isset($validated['utm_data'])) {
    echo "Output utm_data value (first 50): '" . substr($validated['utm_data'], 0, 50) . "'\n";
} else {
    echo "utm_data NOT in validated output!\n";
}

?>
