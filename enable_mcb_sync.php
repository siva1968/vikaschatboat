<?php
// Enable MCB sync and set default configuration

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'demo';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "\n=== ENABLING MCB SYNC ===\n\n";

// Define MCB settings
$settings = [
    'edubot_mcb_enabled' => '1',
    'edubot_mcb_org_id' => '21',
    'edubot_mcb_branch_id' => '113',
    'edubot_mcb_api_timeout' => '65',
    'edubot_mcb_retry_attempts' => '3',
    'edubot_mcb_auto_sync' => '1'
];

$updated = 0;
$errors = [];

foreach ($settings as $option_name => $option_value) {
    // Check if option exists
    $result = $conn->query("SELECT option_id FROM wp_options WHERE option_name = '$option_name'");
    
    if ($result->num_rows > 0) {
        // Update existing
        $sql = "UPDATE wp_options SET option_value = '$option_value' WHERE option_name = '$option_name'";
    } else {
        // Insert new
        $sql = "INSERT INTO wp_options (option_name, option_value, autoload) VALUES ('$option_name', '$option_value', 'yes')";
    }
    
    if ($conn->query($sql)) {
        $updated++;
        echo "✅ {$option_name} = {$option_value}\n";
    } else {
        $errors[] = "Failed to set {$option_name}: " . $conn->error;
        echo "❌ {$option_name}: " . $conn->error . "\n";
    }
}

echo "\n📊 Summary:\n";
echo "─────────────────────────────────────────\n";
echo "Settings updated: $updated/6\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  • $error\n";
    }
}

// Verify settings were set
echo "\n✔️  VERIFICATION:\n";
echo "─────────────────────────────────────────\n";

foreach ($settings as $option_name => $expected_value) {
    $result = $conn->query("SELECT option_value FROM wp_options WHERE option_name = '$option_name'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $actual_value = $row['option_value'];
        $status = ($actual_value === $expected_value) ? "✅" : "❌";
        echo "$status {$option_name} = {$actual_value}\n";
    } else {
        echo "❌ {$option_name} = NOT FOUND\n";
    }
}

echo "\n🎉 MCB Sync Configuration Enabled!\n\n";

$conn->close();
?>
