<?php
// Check MCB sync status

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'demo';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "\n=== MCB SYNC STATUS REPORT ===\n\n";

// 1. Check MCB settings in wp_options
echo "1️⃣  MCB SETTINGS IN WP_OPTIONS:\n";
echo "─────────────────────────────────────────\n";

$result = $conn->query("SELECT option_value FROM wp_options WHERE option_name = 'edubot_mcb_settings'");

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $mcb_settings = unserialize($row['option_value']);
    
    echo "✅ MCB Settings Found:\n\n";
    echo "  • Enabled: " . ($mcb_settings['enabled'] ? "YES" : "NO") . "\n";
    echo "  • Sync Enabled: " . ($mcb_settings['sync_enabled'] ? "YES" : "NO") . "\n";
    echo "  • Auto-sync: " . ($mcb_settings['auto_sync'] ? "YES" : "NO") . "\n";
    echo "  • Organization ID: {$mcb_settings['organization_id']}\n";
    echo "  • Branch ID: {$mcb_settings['branch_id']}\n";
    echo "  • Timeout: {$mcb_settings['timeout']} seconds\n";
    echo "  • Retry Attempts: {$mcb_settings['retry_attempts']}\n";
    echo "  • API URL: {$mcb_settings['api_url']}\n";
} else {
    echo "❌ MCB Settings NOT FOUND\n";
}

echo "\n";

// 2. Check sync logs
echo "2️⃣  MCB SYNC LOGS:\n";
echo "─────────────────────────────────────────\n";

$log_count = $conn->query("SELECT COUNT(*) as count FROM wp_edubot_mcb_sync_log");
$count_row = $log_count->fetch_assoc();
$total_logs = $count_row['count'];

echo "Total sync log records: $total_logs\n";

if ($total_logs > 0) {
    echo "\n✅ SYNC LOGS ARE BEING RECORDED!\n\n";
    echo "Recent syncs:\n";
    
    $recent = $conn->query("
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
        LIMIT 5
    ");
    
    while ($log = $recent->fetch_assoc()) {
        echo "\n  ID: {$log['id']}\n";
        echo "  Enquiry: {$log['enquiry_number']} ({$log['student_name']})\n";
        echo "  Status: " . ($log['success'] ? "✅ SUCCESS" : "❌ FAILED") . "\n";
        if ($log['error_message']) {
            echo "  Error: {$log['error_message']}\n";
        }
        echo "  Retries: {$log['retry_count']}\n";
        echo "  Time: {$log['created_at']}\n";
    }
} else {
    echo "⚠️  NO SYNC LOGS RECORDED YET\n\n";
    echo "This means:\n";
    echo "  • Settings are configured ✅\n";
    echo "  • But sync code may not be implemented yet ❌\n";
    echo "  • Or no enquiries have been submitted since sync was enabled\n";
}

echo "\n";

// 3. Check enquiries with MCB status
echo "3️⃣  ENQUIRIES WITH MCB STATUS:\n";
echo "─────────────────────────────────────────\n";

$enq_result = $conn->query("
    SELECT COUNT(*) as count 
    FROM wp_edubot_enquiries 
    WHERE mcb_sync_status IS NOT NULL
");
$enq_row = $enq_result->fetch_assoc();
$enq_count = $enq_row['count'];

echo "Enquiries synced to MCB: $enq_count\n";

if ($enq_count > 0) {
    echo "\n✅ ENQUIRIES ARE BEING SYNCED!\n\n";
    
    $synced_enq = $conn->query("
        SELECT 
            enquiry_number,
            student_name,
            mcb_sync_status,
            mcb_enquiry_id,
            mcb_query_code,
            created_at
        FROM wp_edubot_enquiries
        WHERE mcb_sync_status IS NOT NULL
        ORDER BY created_at DESC
        LIMIT 5
    ");
    
    while ($enq = $synced_enq->fetch_assoc()) {
        echo "  • {$enq['enquiry_number']} ({$enq['student_name']})\n";
        echo "    Status: {$enq['mcb_sync_status']}\n";
        echo "    MCB ID: {$enq['mcb_enquiry_id']}\n";
    }
} else {
    echo "⚠️  NO ENQUIRIES SYNCED YET\n";
}

echo "\n" . str_repeat("─", 50) . "\n";

echo "\n📊 OVERALL STATUS:\n";
echo "─────────────────────────────────────────\n";

if ($mcb_settings['sync_enabled'] && $mcb_settings['auto_sync']) {
    echo "✅ MCB Sync is ENABLED and AUTO-SYNC is ON\n";
    
    if ($total_logs > 0) {
        echo "✅ Sync logs ARE being recorded\n";
        echo "✅ System is WORKING - test by submitting an enquiry\n";
    } else {
        echo "⚠️  Sync logs NOT yet recorded\n";
        echo "   This is normal if no enquiries submitted since enabling\n";
        echo "   OR sync code needs to be implemented\n";
    }
} else {
    echo "❌ MCB Sync is NOT fully enabled\n";
}

echo "\n";

$conn->close();
?>
