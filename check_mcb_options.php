<?php
// Check WordPress options table for MCB settings

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'demo';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "\n=== CHECKING WP_OPTIONS TABLE FOR MCB SETTINGS ===\n\n";

// Query for all MCB-related options
$result = $conn->query("SELECT option_name, option_value FROM wp_options WHERE option_name LIKE '%mcb%' OR option_name LIKE '%myclassboard%'");

if ($result->num_rows === 0) {
    echo "❌ NO MCB OPTIONS FOUND IN wp_options\n\n";
    echo "This means MCB settings are NOT saved in the database yet.\n";
} else {
    echo "✅ Found MCB Options:\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "  • {$row['option_name']} = {$row['option_value']}\n";
    }
}

echo "\n" . str_repeat("─", 50) . "\n\n";

// Check if API integrations table has MCB config
echo "=== CHECKING API_INTEGRATIONS TABLE FOR MCB ===\n\n";

$api_result = $conn->query("
    SELECT id, provider, config FROM wp_edubot_api_integrations 
    WHERE provider LIKE '%mcb%' OR provider LIKE '%myclassboard%'
");

if ($api_result && $api_result->num_rows > 0) {
    echo "✅ MCB API Integration found in wp_edubot_api_integrations:\n\n";
    while ($row = $api_result->fetch_assoc()) {
        echo "  Provider: {$row['provider']}\n";
        echo "  Config: {$row['config']}\n\n";
    }
} else {
    echo "❌ No MCB integration found in wp_edubot_api_integrations\n\n";
}

// Check all available API integrations
echo "=== ALL API INTEGRATIONS IN DATABASE ===\n\n";

$all_apis = $conn->query("SELECT id, provider, status FROM wp_edubot_api_integrations");

if ($all_apis && $all_apis->num_rows > 0) {
    echo "Found integrations:\n";
    while ($row = $all_apis->fetch_assoc()) {
        echo "  • {$row['provider']} (Status: {$row['status']})\n";
    }
} else {
    echo "No API integrations found\n";
}

echo "\n" . str_repeat("─", 50) . "\n\n";

// Check if there's sync code implementation
echo "=== MCB SYNC IMPLEMENTATION STATUS ===\n\n";

$log_result = $conn->query("SELECT COUNT(*) as count FROM wp_edubot_mcb_sync_log");
$log_count = $log_result->fetch_assoc()['count'];

echo "Sync log records: $log_count\n";

if ($log_count === 0) {
    echo "\n⚠️  SYNC LOGGING NOT ACTIVE\n";
    echo "\nThis indicates:\n";
    echo "  1. MCB settings are configured in WordPress admin\n";
    echo "  2. BUT sync code is not implemented to write logs\n";
    echo "  3. No enquiries have been synced yet\n";
}

$conn->close();
?>
