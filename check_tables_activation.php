<?php
// Check if all EduBot tables are created after plugin activation

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'demo';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$result = $conn->query('SHOW TABLES LIKE "wp_edubot%"');
$edubot_tables = [];
while ($row = $result->fetch_row()) {
    $edubot_tables[] = $row[0];
}

$required_tables = [
    'wp_edubot_enquiries',
    'wp_edubot_applications',
    'wp_edubot_api_integrations',
    'wp_edubot_api_logs',
    'wp_edubot_visitors',
    'wp_edubot_visitor_analytics',
    'wp_edubot_conversions',
    'wp_edubot_school_configs',
    'wp_edubot_mcb_settings',
    'wp_edubot_mcb_sync_log',
    'wp_edubot_logs',
    'wp_edubot_attribution_journeys',
    'wp_edubot_attribution_sessions',
    'wp_edubot_attribution_touchpoints',
    'wp_edubot_report_schedules'
];

echo "\n=== EDUBOT TABLES ACTIVATION CHECK ===\n\n";

echo "Found Tables (" . count($edubot_tables) . "):\n";
foreach ($edubot_tables as $table) {
    echo "  ✅ $table\n";
}

echo "\n";
$missing = [];
foreach ($required_tables as $table) {
    if (!in_array($table, $edubot_tables)) {
        $missing[] = $table;
    }
}

if (!empty($missing)) {
    echo "Missing Tables (" . count($missing) . "):\n";
    foreach ($missing as $table) {
        echo "  ❌ $table\n";
    }
} else {
    echo "Missing Tables: 0 ✅\n";
}

echo "\n";
echo "Total Required: " . count($required_tables) . "\n";
echo "Total Found: " . count($edubot_tables) . "\n";
echo "Status: " . (count($missing) === 0 ? "✅ ALL TABLES CREATED" : "❌ MISSING TABLES") . "\n\n";

$conn->close();
?>
