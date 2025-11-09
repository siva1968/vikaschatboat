<?php
// Check if MCB sync details are logged in wp_edubot_mcb_sync_log

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'demo';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "\n=== MCB SYNC LOG TABLE STATUS ===\n\n";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'wp_edubot_mcb_sync_log'");
if ($result->num_rows === 0) {
    echo "❌ Table wp_edubot_mcb_sync_log DOES NOT EXIST\n";
    $conn->close();
    exit;
}

echo "✅ Table wp_edubot_mcb_sync_log EXISTS\n\n";

// Get table structure
echo "📋 TABLE SCHEMA:\n";
echo "─────────────────────────────────────────\n";
$schema = $conn->query("SHOW COLUMNS FROM wp_edubot_mcb_sync_log");
while ($col = $schema->fetch_assoc()) {
    echo "  • {$col['Field']} ({$col['Type']}) - Default: " . ($col['Default'] ?? 'NULL') . "\n";
}

echo "\n";

// Count records
$count_result = $conn->query("SELECT COUNT(*) as total FROM wp_edubot_mcb_sync_log");
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];

echo "📊 RECORDS IN TABLE:\n";
echo "─────────────────────────────────────────\n";
echo "Total sync log entries: $total_records\n";

if ($total_records === 0) {
    echo "\n⚠️  NO SYNC LOGS FOUND\n";
    echo "This means either:\n";
    echo "  1. No enquiries have been synced yet\n";
    echo "  2. MCB sync has not been triggered\n";
    echo "  3. Sync logging is not implemented\n";
} else {
    echo "\n📝 RECENT SYNC LOGS:\n";
    echo "─────────────────────────────────────────\n";
    
    $logs = $conn->query("
        SELECT 
            s.id,
            s.enquiry_id,
            e.enquiry_number,
            e.student_name,
            s.success,
            s.error_message,
            s.retry_count,
            s.created_at
        FROM wp_edubot_mcb_sync_log s
        LEFT JOIN wp_edubot_enquiries e ON s.enquiry_id = e.id
        ORDER BY s.created_at DESC
        LIMIT 10
    ");
    
    while ($log = $logs->fetch_assoc()) {
        echo "\n  ID: {$log['id']}\n";
        echo "  Enquiry: {$log['enquiry_number']} - {$log['student_name']}\n";
        echo "  Status: " . ($log['success'] ? "✅ SUCCESS" : "❌ FAILED") . "\n";
        if ($log['error_message']) {
            echo "  Error: {$log['error_message']}\n";
        }
        echo "  Retries: {$log['retry_count']}\n";
        echo "  Logged: {$log['created_at']}\n";
        echo "  ─────────────────────────\n";
    }
}

// Check enquiry MCB status
echo "\n📨 ENQUIRIES WITH MCB STATUS:\n";
echo "─────────────────────────────────────────\n";

$enquiries = $conn->query("
    SELECT 
        id,
        enquiry_number,
        student_name,
        mcb_sync_status,
        mcb_enquiry_id,
        mcb_query_code,
        created_at
    FROM wp_edubot_enquiries
    WHERE mcb_sync_status IS NOT NULL
    ORDER BY created_at DESC
    LIMIT 10
");

$enq_count = $enquiries->num_rows;
echo "Enquiries with MCB status: $enq_count\n";

if ($enq_count === 0) {
    echo "\n⚠️  NO ENQUIRIES WITH MCB STATUS\n";
    echo "This means:\n";
    echo "  • No enquiries have been synced to MCB yet\n";
    echo "  • MCB sync may not be enabled or triggered\n";
} else {
    while ($enq = $enquiries->fetch_assoc()) {
        echo "\n  Enquiry: {$enq['enquiry_number']}\n";
        echo "  Student: {$enq['student_name']}\n";
        echo "  MCB Status: {$enq['mcb_sync_status']}\n";
        echo "  MCB ID: {$enq['mcb_enquiry_id']}\n";
        echo "  Query Code: {$enq['mcb_query_code']}\n";
        echo "  Created: {$enq['created_at']}\n";
        echo "  ─────────────────────────\n";
    }
}

// Check MCB settings
echo "\n⚙️  MCB CONFIGURATION:\n";
echo "─────────────────────────────────────────\n";

$options_to_check = [
    'edubot_mcb_enabled' => 'MCB Enabled',
    'edubot_mcb_org_id' => 'Organization ID',
    'edubot_mcb_branch_id' => 'Branch ID',
    'edubot_mcb_auto_sync' => 'Auto-sync Enabled',
    'edubot_mcb_api_timeout' => 'API Timeout',
    'edubot_mcb_retry_attempts' => 'Retry Attempts'
];

$conn->select_db('demo');
foreach ($options_to_check as $option => $label) {
    $result = $conn->query("SELECT option_value FROM wp_options WHERE option_name = '$option'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $value = $row['option_value'];
        echo "  • $label: $value\n";
    } else {
        echo "  • $label: NOT SET\n";
    }
}

echo "\n";

$conn->close();
?>
