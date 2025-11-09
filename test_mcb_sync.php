<?php
// Test MCB sync by creating a sample enquiry programmatically

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'demo';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "\n=== TESTING MCB SYNC IMPLEMENTATION ===\n\n";

// Create a test enquiry
$test_enquiry = array(
    'enquiry_number' => 'ENQ' . date('Y') . rand(10000, 99999),
    'student_name' => 'Test Student MCB',
    'grade' => 'Grade 5',
    'board' => 'CBSE',
    'academic_year' => '2026-27',
    'parent_name' => 'Mr. Test Parent',
    'email' => 'test@example.com',
    'phone' => '+919876543210',
    'date_of_birth' => '2015-05-10',
    'ip_address' => '127.0.0.1',
    'user_agent' => 'Mozilla/5.0 Test',
    'utm_data' => json_encode(['utm_source' => 'chatbot']),
    'source' => 'chatbot',
    'status' => 'pending',
    'created_at' => date('Y-m-d H:i:s')
);

echo "📝 Creating test enquiry:\n";
echo "─────────────────────────────────────────\n";
echo "Enquiry Number: {$test_enquiry['enquiry_number']}\n";
echo "Student: {$test_enquiry['student_name']}\n";
echo "Grade: {$test_enquiry['grade']}\n";
echo "Board: {$test_enquiry['board']}\n";
echo "Parent: {$test_enquiry['parent_name']}\n";
echo "Email: {$test_enquiry['email']}\n";
echo "Phone: {$test_enquiry['phone']}\n\n";

// Insert test enquiry
$cols = implode(', ', array_keys($test_enquiry));
$vals = implode(', ', array_map(function($v) use ($conn) { return "'" . $conn->real_escape_string($v) . "'"; }, array_values($test_enquiry)));

$sql = "INSERT INTO wp_edubot_enquiries ($cols) VALUES ($vals)";
$result = $conn->query($sql);

if (!$result) {
    die("❌ Failed to insert test enquiry: " . $conn->error . "\n");
}

$enquiry_id = $conn->insert_id;
echo "✅ Test enquiry created with ID: $enquiry_id\n\n";

// Give time for sync to process (if async)
echo "⏳ Waiting 3 seconds for sync processing...\n";
sleep(3);

// Check if sync log was created
echo "\n📊 Checking MCB sync logs:\n";
echo "─────────────────────────────────────────\n";

$sync_log = $conn->query(
    "SELECT * FROM wp_edubot_mcb_sync_log WHERE enquiry_id = $enquiry_id ORDER BY created_at DESC LIMIT 1"
);

if ($sync_log && $sync_log->num_rows > 0) {
    echo "✅ SYNC LOG FOUND!\n\n";
    
    $log = $sync_log->fetch_assoc();
    
    echo "Log ID: {$log['id']}\n";
    echo "Status: " . ($log['success'] ? "✅ SUCCESS" : "❌ FAILED") . "\n";
    echo "Error: " . ($log['error_message'] ?? 'None') . "\n";
    echo "Retry Count: {$log['retry_count']}\n";
    echo "Created: {$log['created_at']}\n\n";
    
    if ($log['success']) {
        echo "Request Data:\n";
        $request = json_decode($log['request_data'], true);
        foreach ($request as $key => $value) {
            echo "  • $key: $value\n";
        }
        
        echo "\nResponse Data:\n";
        $response = json_decode($log['response_data'], true);
        foreach ($response as $key => $value) {
            echo "  • $key: $value\n";
        }
    }
    
} else {
    echo "⚠️  NO SYNC LOG FOUND\n\n";
    echo "This could mean:\n";
    echo "  1. MCB sync code is not being triggered\n";
    echo "  2. The WordPress action hook is not firing\n";
    echo "  3. MCB sync is disabled\n\n";
    
    // Check if MCB is enabled
    $mcb_check = $conn->query("SELECT option_value FROM wp_options WHERE option_name = 'edubot_mcb_settings'");
    if ($mcb_check && $mcb_check->num_rows > 0) {
        $row = $mcb_check->fetch_assoc();
        $settings = unserialize($row['option_value']);
        echo "MCB Settings:\n";
        echo "  • Enabled: " . ($settings['enabled'] ? "YES" : "NO") . "\n";
        echo "  • Sync Enabled: " . ($settings['sync_enabled'] ? "YES" : "NO") . "\n";
        echo "  • Auto-sync: " . ($settings['auto_sync'] ? "YES" : "NO") . "\n";
    }
}

// Check enquiry MCB status
echo "\n📨 Enquiry MCB Status:\n";
echo "─────────────────────────────────────────\n";

$enq = $conn->query("SELECT mcb_sync_status, mcb_enquiry_id, mcb_query_code FROM wp_edubot_enquiries WHERE id = $enquiry_id");

if ($enq && $enq->num_rows > 0) {
    $e = $enq->fetch_assoc();
    echo "MCB Sync Status: {$e['mcb_sync_status']}\n";
    echo "MCB Enquiry ID: {$e['mcb_enquiry_id']}\n";
    echo "MCB Query Code: {$e['mcb_query_code']}\n";
} else {
    echo "Enquiry not found\n";
}

echo "\n" . str_repeat("─", 50) . "\n\n";

echo "📊 SUMMARY:\n";
echo "─────────────────────────────────────────\n";

$total_logs = $conn->query("SELECT COUNT(*) as count FROM wp_edubot_mcb_sync_log")->fetch_assoc()['count'];
$successful = $conn->query("SELECT COUNT(*) as count FROM wp_edubot_mcb_sync_log WHERE success = 1")->fetch_assoc()['count'];
$failed = $conn->query("SELECT COUNT(*) as count FROM wp_edubot_mcb_sync_log WHERE success = 0")->fetch_assoc()['count'];

echo "Total sync logs: $total_logs\n";
echo "  ✅ Successful: $successful\n";
echo "  ❌ Failed: $failed\n\n";

if ($total_logs > 0) {
    echo "✅ MCB SYNC IS WORKING!\n";
} else {
    echo "⚠️  MCB sync may not be implemented\n";
}

echo "\n";

$conn->close();
?>
