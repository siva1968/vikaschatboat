<?php
/**
 * Test WhatsApp Template Replacement
 * This script tests if the WhatsApp template matches your expected format
 */

// Your expected format
$expected_format = "Admission Enquiry Confirmation
Dear John Smith,

Thank you for your enquiry at ABC International School. Your enquiry number is ENQ20250906001 for Grade Grade 10.

We have received your application on 06/09/2025 and will contact you within 24-48 hours with the next steps.

Best regards,
Admissions Team
Reply STOP to unsubscribe";

// Current template
$template = "Admission Enquiry Confirmation
Dear {parent_name},

Thank you for your enquiry at {school_name}. Your enquiry number is {enquiry_number} for Grade {grade}.

We have received your application on {submission_date} and will contact you within 24-48 hours with the next steps.

Best regards,
Admissions Team
Reply STOP to unsubscribe";

// Test data
$placeholders = [
    '{school_name}' => 'ABC International School',
    '{parent_name}' => 'John Smith',
    '{student_name}' => 'John Smith Jr.',
    '{enquiry_number}' => 'ENQ20250906001',
    '{grade}' => 'Grade 10',
    '{board}' => 'CBSE',
    '{academic_year}' => '2025-26',
    '{submission_date}' => '06/09/2025',
    '{phone}' => '+919876543210',
    '{email}' => 'john@example.com'
];

// Replace placeholders
$generated_message = str_replace(array_keys($placeholders), array_values($placeholders), $template);

echo "<h1>WhatsApp Template Test</h1>\n";
echo "<h2>Expected Format:</h2>\n";
echo "<pre>" . htmlspecialchars($expected_format) . "</pre>\n";

echo "<h2>Generated Message:</h2>\n";
echo "<pre>" . htmlspecialchars($generated_message) . "</pre>\n";

echo "<h2>Comparison:</h2>\n";
if (trim($generated_message) === trim($expected_format)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "<strong>✅ PERFECT MATCH!</strong><br>";
    echo "The generated message matches your expected format exactly.";
    echo "</div>\n";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ DIFFERENCE FOUND</strong><br>";
    echo "The generated message differs from expected format.";
    echo "</div>\n";
    
    // Show character-by-character comparison
    echo "<h3>Character-by-Character Analysis:</h3>\n";
    $expected_chars = str_split(trim($expected_format));
    $generated_chars = str_split(trim($generated_message));
    $max_len = max(count($expected_chars), count($generated_chars));
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Position</th><th>Expected</th><th>Generated</th><th>Match</th></tr>\n";
    
    $differences = 0;
    for ($i = 0; $i < $max_len; $i++) {
        $expected_char = isset($expected_chars[$i]) ? $expected_chars[$i] : '';
        $generated_char = isset($generated_chars[$i]) ? $generated_chars[$i] : '';
        $match = $expected_char === $generated_char;
        
        if (!$match) {
            $differences++;
            if ($differences <= 10) { // Show only first 10 differences
                $style = "background-color: #ffcccc;";
                echo "<tr style='{$style}'>";
                echo "<td>{$i}</td>";
                echo "<td>" . htmlspecialchars($expected_char ?: '[END]') . "</td>";
                echo "<td>" . htmlspecialchars($generated_char ?: '[END]') . "</td>";
                echo "<td>❌</td>";
                echo "</tr>\n";
            }
        }
    }
    echo "</table>\n";
    
    if ($differences > 10) {
        echo "<p><em>... and " . ($differences - 10) . " more differences</em></p>\n";
    }
}

echo "<hr>\n";
echo "<h2>Template Configuration Check:</h2>\n";

// Check if this matches the WordPress option format
echo "<p><strong>Template for WordPress:</strong></p>\n";
echo "<textarea rows='10' cols='80' readonly>" . htmlspecialchars($template) . "</textarea>\n";

echo "<p><em>Copy the above template and paste it in: <strong>Admin > EduBot Pro > School Settings > WhatsApp Message Template</strong></em></p>\n";

echo "<hr>\n";
echo "<p><em>Test completed on: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
