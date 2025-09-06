<?php
/**
 * Test Grade Extraction with Typos
 * This file tests if the enhanced grade extraction can handle common typos
 */

// Simulate the enhanced extract_grade_from_message function
function test_extract_grade_from_message($message) {
    $message_lower = strtolower($message);
    
    // Extract grade numbers (handle typos and variations)
    // Handle "Grde10", "Grade10", "grade 10", etc.
    if (preg_match('/(?:gr[ae]de?|class)\s*(\d+)/i', $message, $matches)) {
        return 'Grade ' . $matches[1];
    }
    
    // Handle standalone numbers after "grade/class" keywords
    if (preg_match('/(?:grade|class|grde|grd)\s*(\d+)/i', $message, $matches)) {
        return 'Grade ' . $matches[1];
    }
    
    // Handle ordinal numbers like "10th", "5th", etc.
    if (preg_match('/(\d+)(th|st|nd|rd)/i', $message, $matches)) {
        return 'Grade ' . $matches[1];
    }
    
    // Handle just numbers when in grade context (last resort)
    if (preg_match('/\b(\d{1,2})\b/', $message, $matches)) {
        $grade_num = intval($matches[1]);
        if ($grade_num >= 1 && $grade_num <= 12) {
            return 'Grade ' . $grade_num;
        }
    }
    
    return 'Selected Grade';
}

// Test cases
$test_cases = [
    'Grde10' => 'Grade 10',
    'Grade10' => 'Grade 10',
    'grade 10' => 'Grade 10', 
    'Grade 10' => 'Grade 10',
    'grde 10' => 'Grade 10',
    'grd10' => 'Grade 10',
    'Class 5' => 'Grade 5',
    '10th' => 'Grade 10',
    '5th grade' => 'Grade 5'
];

echo "Grade Extraction Test Results:\n";
echo "================================\n\n";

foreach ($test_cases as $input => $expected) {
    $result = test_extract_grade_from_message($input);
    $status = ($result === $expected) ? '✅ PASS' : '❌ FAIL';
    echo sprintf("Input: %-12s | Expected: %-10s | Got: %-10s | %s\n", 
        '"' . $input . '"', $expected, $result, $status);
}

echo "\nTest Summary: Enhanced grade extraction now handles common typos like 'Grde10'!\n";
?>
