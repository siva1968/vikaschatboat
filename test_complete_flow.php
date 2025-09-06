<?php
// Test complete Grade 11 flow
echo "=== Testing Grade 11 Complete Flow ===\n\n";

function testGradeExtraction($message) {
    $message_lower = strtolower($message);
    
    // Check for Grade 11 with streams first (more specific matches)
    if (preg_match('/grade\s*11\s*science/i', $message_lower)) {
        return 'Grade 11 Science';
    }
    if (preg_match('/grade\s*11\s*commerce/i', $message_lower)) {
        return 'Grade 11 Commerce';
    }
    if (preg_match('/grade\s*11\s*humanities/i', $message_lower)) {
        return 'Grade 11 Humanities';
    }
    
    // Extract grade numbers (but check Grade 11 without streams separately)
    if (preg_match('/grade\s*(\d+)/i', $message, $matches)) {
        return 'Grade ' . $matches[1];
    }
    
    return 'No match';
}

function testGrade11Flow($input) {
    echo "User Input: '$input'\n";
    
    $grade = testGradeExtraction($input);
    echo "Extracted Grade: $grade\n";
    
    $message_lower = strtolower($input);
    
    // Special handling for Grade 11 - show stream options if user just typed "Grade 11"
    if ($grade === 'Grade 11' && !preg_match('/\b(science|commerce|humanities)\b/i', $message_lower)) {
        echo "Action: Show Grade 11 stream options\n";
        echo "Response: ✅ Grade 11 Selected! Please choose your stream:\n";
        echo "• Grade 11 Science\n• Grade 11 Commerce\n• Grade 11 Humanities\n";
    } else {
        echo "Action: Proceed to board selection\n";
        echo "Response: ✅ Grade Selected: $grade - Now choose curriculum board\n";
    }
    
    echo "\n";
}

// Test various inputs
$test_cases = array(
    'Grade 11',
    'grade 11',
    'Grade 11 Science',
    'grade 11 commerce',
    'Grade 11 Humanities',
    'Grade 1',
    'Grade 10'
);

foreach ($test_cases as $test) {
    testGrade11Flow($test);
}
?>
