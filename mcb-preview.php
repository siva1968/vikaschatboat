<?php
/**
 * MCB Data Preview - Debug Tool
 * 
 * Shows exactly what data will be sent to MCB when sync button is clicked
 * WITHOUT submitting to the actual MCB API
 * 
 * Usage: Place this file in your WordPress root and access:
 * http://localhost/demo/mcb-preview.php?enquiry_id=1
 */

// Load WordPress
require_once('wp-load.php');

// Check if user is logged in and is admin
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Access denied. Admin access required.');
}

// Get enquiry ID from URL
$enquiry_id = isset($_GET['enquiry_id']) ? intval($_GET['enquiry_id']) : 0;

if (!$enquiry_id) {
    wp_die('No enquiry ID provided. Usage: ?enquiry_id=1');
}

// Ensure EduBot plugin is loaded
$plugin_path = ABSPATH . 'wp-content/plugins/edubot-pro/edubot-pro.php';
if (!function_exists('run_edubot_pro')) {
    // Load the main plugin file if not already loaded
    if (file_exists($plugin_path)) {
        require_once($plugin_path);
    }
}

// Load MCB Service directly with all dependencies
$mcb_service_includes = array(
    'class-edubot-constants.php',
    'class-myclassboard-integration.php',
    'class-edubot-mcb-service.php'
);

$plugin_includes_path = ABSPATH . 'wp-content/plugins/edubot-pro/includes/';

foreach ($mcb_service_includes as $file) {
    $file_path = $plugin_includes_path . $file;
    if (file_exists($file_path)) {
        require_once($file_path);
    }
}

if (!class_exists('EduBot_MCB_Service')) {
    wp_die('Error: EduBot_MCB_Service class could not be loaded. Required files missing from ' . $plugin_includes_path);
}

// Get the MCB Service instance
$mcb_service = EduBot_MCB_Service::get_instance();

// Check if MCB is enabled
if (!$mcb_service->is_sync_enabled()) {
    wp_die('MCB sync is not enabled in settings.');
}

// Get preview data (WITHOUT submitting to MCB)
$preview = $mcb_service->preview_mcb_data($enquiry_id);

?>
<!DOCTYPE html>
<html>
<head>
    <title>MCB Data Preview</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #0073aa;
            padding-bottom: 10px;
        }
        h2 {
            color: #0073aa;
            margin-top: 30px;
            font-size: 1.3em;
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .data-section {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #0073aa;
            margin: 15px 0;
            border-radius: 3px;
            overflow-x: auto;
        }
        .data-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .data-row:last-child {
            border-bottom: none;
        }
        .data-label {
            font-weight: bold;
            color: #0073aa;
            min-width: 200px;
            word-break: break-word;
        }
        .data-value {
            color: #333;
            flex: 1;
            word-break: break-all;
            padding-left: 20px;
        }
        .data-value.empty {
            color: #999;
            font-style: italic;
        }
        .section-title {
            background: #0073aa;
            color: white;
            padding: 10px 15px;
            margin: 20px 0 15px 0;
            border-radius: 3px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table thead {
            background: #0073aa;
            color: white;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table tbody tr:hover {
            background: #f5f5f5;
        }
        .highlight {
            background: #fff3cd;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 0;
            background: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: #005a87;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #0073aa;
            padding: 15px;
            margin: 15px 0;
            border-radius: 3px;
        }
        .warning-box {
            background: #fff8e5;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 15px 0;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 MCB Data Preview</h1>
        <p>This shows exactly what data will be sent to MCB when the sync button is clicked.</p>

        <?php if ($preview['success']) { ?>
            <div class="status success">
                ✓ MCB Data Ready for Enquiry: <strong><?php echo esc_html($preview['enquiry_number']); ?></strong>
            </div>

            <!-- Student Information -->
            <h2>👤 Student Information</h2>
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Student Name</strong></td>
                        <td><?php echo esc_html($preview['mcb_data']['StudentName'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Parent Name</strong></td>
                        <td><?php echo esc_html($preview['mcb_data']['ParentName'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Email</strong></td>
                        <td><?php echo esc_html($preview['mcb_data']['ParentEmailID'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Phone</strong></td>
                        <td><?php echo esc_html($preview['mcb_data']['ParentMobileNo'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Date of Birth</strong></td>
                        <td><?php echo esc_html($preview['mcb_data']['DateOfBirth'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Gender</strong></td>
                        <td><?php echo esc_html($preview['mcb_data']['Gender'] ?? 'N/A'); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Academic Information -->
            <h2>🎓 Academic Information</h2>
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Grade/Class</strong></td>
                        <td><?php echo esc_html($preview['mcb_data']['ClassID'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Board</strong></td>
                        <td><?php echo esc_html($preview['mcb_data']['BoardID'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Academic Year</strong></td>
                        <td><?php echo esc_html($preview['mcb_data']['AcademicYear'] ?? 'N/A'); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- MCB Configuration -->
            <h2>⚙️ MCB Configuration</h2>
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Organization ID</strong></td>
                        <td><?php echo esc_html($preview['mcb_data']['OrgID'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Branch ID</strong></td>
                        <td><?php echo esc_html($preview['mcb_data']['BranchID'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Enquiry ID</strong></td>
                        <td><?php echo esc_html($preview['mcb_data']['EnquiryID'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Lead Source ID</strong></td>
                        <td><?php echo esc_html($preview['mcb_data']['LeadSourceID'] ?? 'N/A'); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Marketing Attribution Data -->
            <h2>📊 Marketing Attribution Data</h2>
            <div class="info-box">
                <strong>This data will be sent to MCB for campaign tracking:</strong>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Marketing Parameter</th>
                        <th>Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>utm_source</strong></td>
                        <td><?php echo esc_html($preview['marketing_data']['utm_source'] ?? '(empty)'); ?></td>
                        <td><?php echo !empty($preview['marketing_data']['utm_source']) ? '<span class="highlight">✓ Captured</span>' : '<span style="color: #999;">Not captured</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>utm_medium</strong></td>
                        <td><?php echo esc_html($preview['marketing_data']['utm_medium'] ?? '(empty)'); ?></td>
                        <td><?php echo !empty($preview['marketing_data']['utm_medium']) ? '<span class="highlight">✓ Captured</span>' : '<span style="color: #999;">Not captured</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>utm_campaign</strong></td>
                        <td><?php echo esc_html($preview['marketing_data']['utm_campaign'] ?? '(empty)'); ?></td>
                        <td><?php echo !empty($preview['marketing_data']['utm_campaign']) ? '<span class="highlight">✓ Captured</span>' : '<span style="color: #999;">Not captured</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>gclid (Google)</strong></td>
                        <td><?php echo esc_html($preview['marketing_data']['gclid'] ?? '(empty)'); ?></td>
                        <td><?php echo !empty($preview['marketing_data']['gclid']) ? '<span class="highlight">✓ Captured</span>' : '<span style="color: #999;">Not captured</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>fbclid (Facebook)</strong></td>
                        <td><?php echo esc_html($preview['marketing_data']['fbclid'] ?? '(empty)'); ?></td>
                        <td><?php echo !empty($preview['marketing_data']['fbclid']) ? '<span class="highlight">✓ Captured</span>' : '<span style="color: #999;">Not captured</span>'; ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Complete JSON Payload -->
            <h2>📋 Complete MCB Payload (JSON)</h2>
            <div class="code-block">
                <pre><?php echo esc_html(wp_json_encode($preview['mcb_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
            </div>

            <div class="info-box">
                <strong>Note:</strong> This is exactly what will be sent to MCB when you click the "Sync MCB" button. No actual API call has been made. This is a preview only.
            </div>

            <button class="btn" onclick="window.history.back()">← Back</button>

        <?php } else { ?>
            <div class="status error">
                ✗ Error: <?php echo esc_html($preview['message']); ?>
            </div>
            <button class="btn" onclick="window.history.back()">← Back</button>
        <?php } ?>
    </div>
</body>
</html>
