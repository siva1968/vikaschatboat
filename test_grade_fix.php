<?php
// Simulate the grade matching problem
$message = "Grade 11";
$message_lower = strtolower($message);

// Simulate available grades (this is what's causing the issue)
$available_grades = array(
    'grade_1' => 'Grade 1',
    'grade_2' => 'Grade 2', 
    'grade_11' => 'Grade 11',
    'grade_12' => 'Grade 12'
);

echo "Testing message: '$message'\n";
echo "Message lowercase: '$message_lower'\n\n";

echo "OLD METHOD (buggy):\n";
foreach ($available_grades as $grade_key => $grade_name) {
    $grade_name_lower = strtolower($grade_name);
    echo "Checking against: '$grade_name' (lowercase: '$grade_name_lower')\n";
    if (stripos($message_lower, $grade_name_lower) !== false) {
        echo "MATCH FOUND: $grade_name (This is the bug!)\n";
        break;
    }
}

echo "\nNEW METHOD (fixed):\n";
// Sort grades by length (longer first)
$sorted_grades = $available_grades;
uksort($sorted_grades, function($a, $b) {
    return strlen($b) - strlen($a);
});

foreach ($sorted_grades as $grade_key => $grade_name) {
    $grade_name_lower = strtolower($grade_name);
    echo "Checking against: '$grade_name' (lowercase: '$grade_name_lower')\n";
    
    // Check for exact matches first
    if ($message_lower === $grade_name_lower) {
        echo "EXACT MATCH: $grade_name\n";
        break;
    }
    
    // Check for word boundary matches
    if (preg_match('/\b' . preg_quote($grade_name_lower, '/') . '\b/', $message_lower)) {
        echo "WORD BOUNDARY MATCH: $grade_name\n";
        break;
    }
}
?>
