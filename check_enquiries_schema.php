<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'demo';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Get enquiries table columns
$result = $conn->query("SHOW COLUMNS FROM wp_edubot_enquiries");

echo "wp_edubot_enquiries columns:\n";
echo "─────────────────────────────────────────\n";

while ($row = $result->fetch_assoc()) {
    echo "  • {$row['Field']} ({$row['Type']})\n";
}

$conn->close();
?>
