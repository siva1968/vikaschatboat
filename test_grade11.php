<?php
// Test Grade 11 handling
$test_inputs = array(
    'Grade 11',
    'Grade 11 Science', 
    'Grade 11 Commerce',
    'Grade 11 Humanities',
    'grade 11',
    'grade 11 science'
);

echo "Testing Grade 11 extraction:\n";
foreach ($test_inputs as $input) {
    echo "Input: '$input' - ";
    
    // Test Grade 11 with streams
    $message_lower = strtolower($input);
    if (preg_match('/grade\s*11\s*science/i', $message_lower)) {
        echo 'Grade 11 Science';
    } elseif (preg_match('/grade\s*11\s*commerce/i', $message_lower)) {
        echo 'Grade 11 Commerce';
    } elseif (preg_match('/grade\s*11\s*humanities/i', $message_lower)) {
        echo 'Grade 11 Humanities';
    } elseif (preg_match('/grade\s*(\d+)/i', $input, $matches)) {
        echo 'Grade ' . $matches[1];
    } else {
        echo 'No match';
    }
    
    // Test if should show stream options
    $grade = 'Grade 11'; // Simulated result
    if ($grade === 'Grade 11' && !preg_match('/\b(science|commerce|humanities)\b/i', $message_lower)) {
        echo ' -> Should show stream options';
    }
    
    echo "\n";
}
?>
