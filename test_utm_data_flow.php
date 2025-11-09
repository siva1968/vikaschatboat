#!/usr/bin/env php
<?php
/**
 * DEBUG: Test UTM Data Capture and Retrieval
 * 
 * This script simulates the application submission flow to diagnose
 * why marketing/UTM data isn't being saved to the database
 */

// Load WordPress
require_once 'wp-load.php';

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  DEBUG: UTM Data Capture Flow Analysis                           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Test 1: Check if session is started
echo "TEST 1: Session Status\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$session_status = session_status();
echo "Session Status: " . ($session_status === PHP_SESSION_NONE ? "NOT STARTED" : ($session_status === PHP_SESSION_ACTIVE ? "ACTIVE" : "DISABLED")) . "\n";
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
    echo "Session started manually\n";
}
echo "\n";

// Test 2: Check cookies
echo "TEST 2: Existing Cookies\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$utm_params = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid');
$found_cookies = false;

foreach ($utm_params as $param) {
    $cookie_name = 'edubot_' . $param;
    if (isset($_COOKIE[$cookie_name])) {
        echo "✅ Cookie found: {$cookie_name} = " . $_COOKIE[$cookie_name] . "\n";
        $found_cookies = true;
    }
}

if (!$found_cookies) {
    echo "❌ No UTM cookies found\n";
}
echo "\n";

// Test 3: Check session variables
echo "TEST 3: Session Variables\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$found_session = false;

foreach ($utm_params as $param) {
    $session_key = 'edubot_' . $param;
    if (isset($_SESSION[$session_key])) {
        echo "✅ Session var: {$session_key} = " . $_SESSION[$session_key] . "\n";
        $found_session = true;
    }
}

if (!$found_session) {
    echo "❌ No UTM session variables found\n";
}
echo "\n";

// Test 4: Simulate get_utm_data() logic
echo "TEST 4: Simulate get_utm_data() Function\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$utm_data = array();
foreach ($utm_params as $param) {
    // Priority 1: Check current request
    if (isset($_GET[$param])) {
        $utm_data[$param] = sanitize_text_field($_GET[$param]);
        echo "  Found in \$_GET: {$param}\n";
    }
    // Priority 2: Fallback to POST data
    elseif (isset($_POST[$param])) {
        $utm_data[$param] = sanitize_text_field($_POST[$param]);
        echo "  Found in \$_POST: {$param}\n";
    }
    // Priority 3: Check session
    elseif (isset($_SESSION['edubot_' . $param])) {
        $utm_data[$param] = sanitize_text_field($_SESSION['edubot_' . $param]);
        echo "  Found in SESSION: {$param}\n";
    }
    // Priority 4: Check cookies
    elseif (isset($_COOKIE['edubot_' . $param])) {
        $utm_data[$param] = sanitize_text_field($_COOKIE['edubot_' . $param]);
        echo "  Found in COOKIE: {$param} = " . $_COOKIE['edubot_' . $param] . "\n";
    }
}

if (empty($utm_data)) {
    echo "❌ NO UTM DATA RETRIEVED\n";
} else {
    echo "\n✅ UTM Data Retrieved:\n";
    foreach ($utm_data as $key => $value) {
        echo "   - {$key}: {$value}\n";
    }
}
echo "\n";

// Test 5: Check database applications table structure
echo "TEST 5: Database Applications Table\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
global $wpdb;

$table = $wpdb->prefix . 'edubot_applications';
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$table}");

$marketing_columns = array('utm_data', 'gclid', 'fbclid', 'click_id_data');
foreach ($marketing_columns as $col) {
    $found = false;
    foreach ($columns as $column) {
        if ($column->Field === $col) {
            echo "✅ Column exists: {$col} (Type: {$column->Type})\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "❌ Column MISSING: {$col}\n";
    }
}
echo "\n";

// Test 6: Check latest application in database
echo "TEST 6: Latest Application in Database\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$latest_app = $wpdb->get_row("SELECT * FROM {$table} ORDER BY id DESC LIMIT 1");

if ($latest_app) {
    echo "Latest Application ID: {$latest_app->id}\n";
    echo "Application Number: {$latest_app->application_number}\n";
    echo "UTM Data: " . (!empty($latest_app->utm_data) ? $latest_app->utm_data : "❌ EMPTY") . "\n";
    echo "GClid: " . (!empty($latest_app->gclid) ? $latest_app->gclid : "❌ EMPTY") . "\n";
    echo "FBClid: " . (!empty($latest_app->fbclid) ? $latest_app->fbclid : "❌ EMPTY") . "\n";
    echo "Click ID Data: " . (!empty($latest_app->click_id_data) ? $latest_app->click_id_data : "❌ EMPTY") . "\n";
} else {
    echo "❌ No applications found in database\n";
}
echo "\n";

// Test 7: Check WordPress error log for UTM capture attempts
echo "TEST 7: Check WordPress Debug Log\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$log_file = WP_CONTENT_DIR . '/debug.log';
if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    $lines = explode("\n", $log_content);
    $lines = array_reverse($lines);
    
    echo "Last 20 lines from debug.log related to UTM:\n";
    $count = 0;
    foreach ($lines as $line) {
        if (stripos($line, 'utm') !== false || stripos($line, 'get_utm_data') !== false || stripos($line, 'capture_utm') !== false) {
            echo "  {$line}\n";
            $count++;
            if ($count >= 20) break;
        }
    }
    
    if ($count === 0) {
        echo "  ❌ No UTM-related log entries found\n";
    }
} else {
    echo "  ⚠️  Debug log not found at {$log_file}\n";
}
echo "\n";

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Analysis Complete                                             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";
