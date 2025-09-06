<?php
$message = 'Grade 11';
echo "Testing: $message\n";
if (preg_match('/grade\s*(\d+)/i', $message, $matches)) {
    echo "Match found:\n";
    print_r($matches);
    echo "Result would be: Grade " . $matches[1] . "\n";
} else {
    echo "No match found\n";
}

// Test if there are multiple digits being captured
$message2 = 'Grade 11';
echo "\nTesting full digit capture: $message2\n";
if (preg_match('/grade\s*(\d+)/i', $message2, $matches)) {
    echo "Full match: '" . $matches[0] . "'\n";
    echo "Captured group: '" . $matches[1] . "'\n";
    echo "Length of captured: " . strlen($matches[1]) . "\n";
}
?>
