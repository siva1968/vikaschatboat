<?php
// Test DOB validation patterns
$test_inputs = array('16/10/25', '16/10/2025', '25', 'sixteen', '16-10-2010', '2010-10-16');
foreach ($test_inputs as $input) {
    echo "Input: '$input' - ";
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $input)) {
        echo 'Valid format';
        $parts = explode('/', $input);
        $day = (int)$parts[0];
        $month = (int)$parts[1];
        $year = (int)$parts[2];
        if (checkdate($month, $day, $year)) {
            echo ' and valid date';
        } else {
            echo ' but invalid date';
        }
    } else {
        echo 'Invalid format';
    }
    echo "\n";
}
?>
