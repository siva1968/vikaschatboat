<?php
/**
 * EduBot Pro - Multi-Source Testing Guide
 * Test enquiries from different sources with complete tracking
 */

require_once dirname(__FILE__) . '/wp-load.php';

echo "<h1>🧪 EduBot Pro - Multi-Source Testing Guide</h1>";

echo "<h2>📊 Available Sources to Test</h2>";

$sources = array(
    'chatbot' => array(
        'name' => 'WhatsApp/Web Chatbot',
        'description' => 'Enquiry submitted through the main chatbot interface',
        'code' => 'chatbot',
        'test_method' => 'Visit http://localhost/demo/ and submit form'
    ),
    'application_form' => array(
        'name' => 'Application Form',
        'description' => 'Enquiry submitted through dedicated application form',
        'code' => 'application_form',
        'test_method' => 'Submit through application form page'
    ),
    'direct_api' => array(
        'name' => 'Direct API Call',
        'description' => 'Enquiry submitted via REST API (for integrations)',
        'code' => 'direct_api',
        'test_method' => 'Use curl or Postman to test API'
    ),
    'manual' => array(
        'name' => 'Manual Entry',
        'description' => 'Enquiry manually created by admin in dashboard',
        'code' => 'manual',
        'test_method' => 'Create from admin panel'
    ),
    'import' => array(
        'name' => 'Bulk Import',
        'description' => 'Enquiries imported from CSV or batch upload',
        'code' => 'import',
        'test_method' => 'Upload CSV with multiple enquiries'
    )
);

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Source</th>";
echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Description</th>";
echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>How to Test</th>";
echo "</tr>";

foreach ($sources as $code => $source) {
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 10px;'><strong>" . $source['name'] . "</strong><br><code style='color: #007cba;'>" . $source['code'] . "</code></td>";
    echo "<td style='border: 1px solid #ddd; padding: 10px;'>" . $source['description'] . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 10px;'>" . $source['test_method'] . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>🧪 Test Methods</h2>";

echo "<h3>Method 1: Test Chatbot Source (EASIEST) ✅</h3>";
echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
echo "<ol>";
echo "<li><strong>Go to Chatbot:</strong> <a href='http://localhost/demo/' target='_blank' class='button'>http://localhost/demo/</a></li>";
echo "<li><strong>Fill Form:</strong>";
echo "  <ul>";
echo "    <li>Student Name: Test Student 1</li>";
echo "    <li>Email: test1@example.com</li>";
echo "    <li>Phone: +919866133566</li>";
echo "    <li>Grade: Grade 5</li>";
echo "    <li>Board: CBSE</li>";
echo "  </ul>";
echo "</li>";
echo "<li><strong>Click Submit</strong></li>";
echo "<li><strong>Verify:</strong> Enquiry saved with source = 'chatbot'</li>";
echo "</ol>";
echo "</div>";

echo "<h3>Method 2: Test with URL Parameters (WITH TRACKING) 📊</h3>";
echo "<div style='background: #e7f3ff; padding: 15px; border: 1px solid #b3d9ff; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>Track different campaigns and UTM parameters:</strong></p>";
echo "<p>Visit chatbot with UTM parameters:</p>";

$test_urls = array(
    array(
        'name' => 'Google Ads',
        'url' => 'http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025',
        'description' => 'Simulates traffic from Google Ads'
    ),
    array(
        'name' => 'Facebook',
        'url' => 'http://localhost/demo/?utm_source=facebook&utm_medium=social&utm_campaign=fb_ads_nov',
        'description' => 'Simulates Facebook ad traffic'
    ),
    array(
        'name' => 'Email Campaign',
        'url' => 'http://localhost/demo/?utm_source=email&utm_medium=newsletter&utm_campaign=parent_outreach',
        'description' => 'Simulates email newsletter click'
    ),
    array(
        'name' => 'Organic',
        'url' => 'http://localhost/demo/?utm_source=organic_search&utm_medium=search&utm_campaign=seo',
        'description' => 'Simulates organic search traffic'
    ),
    array(
        'name' => 'Direct',
        'url' => 'http://localhost/demo/?utm_source=direct&utm_medium=direct&utm_campaign=direct_visit',
        'description' => 'Simulates direct website visit'
    )
);

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Campaign</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>URL</th>";
echo "</tr>";

foreach ($test_urls as $url_test) {
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>" . $url_test['name'] . "</strong><br><small>" . $url_test['description'] . "</small></td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>";
    echo "<a href='" . htmlspecialchars($url_test['url']) . "' target='_blank' style='color: #007cba; text-decoration: underline; font-size: 12px;'>";
    echo htmlspecialchars($url_test['url']);
    echo "</a>";
    echo "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

echo "<h3>Method 3: Test Direct API (FOR DEVELOPERS) 💻</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>Using curl to submit enquiry via API:</strong></p>";
echo "<pre style='background: #f5f5f5; padding: 10px; overflow-x: auto; border-radius: 5px;'>";
echo htmlspecialchars('curl -X POST http://localhost/demo/wp-json/edubot/v1/enquiry \\
  -H "Content-Type: application/json" \\
  -d \'{
    "student_name": "API Test Student",
    "email": "api@test.com",
    "phone": "+919876543210",
    "grade": "Grade 6",
    "board": "CBSE",
    "source": "direct_api",
    "utm_source": "api_integration",
    "utm_medium": "direct"
  }\'');
echo "</pre>";
echo "</div>";

echo "<h3>Method 4: Create Form-Based Test Page</h3>";
echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>Batch test multiple sources:</strong></p>";
echo "<form method='POST' style='background: white; padding: 15px; border-radius: 5px;'>";

echo "<p>";
echo "<label><strong>Select Source to Test:</strong></label><br>";
echo "<select name='test_source' style='width: 100%; padding: 8px; font-size: 14px;'>";
foreach ($sources as $code => $source) {
    echo "<option value='" . $code . "'>" . $source['name'] . " (" . $code . ")</option>";
}
echo "</select>";
echo "</p>";

echo "<p>";
echo "<label><strong>Student Name:</strong></label><br>";
echo "<input type='text' name='student_name' value='Test Student' style='width: 100%; padding: 8px;' />";
echo "</p>";

echo "<p>";
echo "<label><strong>Email:</strong></label><br>";
echo "<input type='email' name='email' value='test@example.com' style='width: 100%; padding: 8px;' />";
echo "</p>";

echo "<p>";
echo "<label><strong>Phone:</strong></label><br>";
echo "<input type='tel' name='phone' value='+919866133566' style='width: 100%; padding: 8px;' />";
echo "</p>";

echo "<p>";
echo "<label><strong>Grade:</strong></label><br>";
echo "<input type='text' name='grade' value='Grade 5' style='width: 100%; padding: 8px;' />";
echo "</p>";

echo "<p>";
echo "<label><strong>Board:</strong></label><br>";
echo "<input type='text' name='board' value='CBSE' style='width: 100%; padding: 8px;' />";
echo "</p>";

echo "<p>";
echo "<button type='submit' class='button button-primary' style='padding: 10px 20px; font-size: 14px;'>Create Test Enquiry</button>";
echo "</p>";

echo "</form>";
echo "</div>";

// Process test enquiry creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_source'])) {
    global $wpdb;
    
    $source = sanitize_text_field($_POST['test_source']);
    $student_name = sanitize_text_field($_POST['student_name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $grade = sanitize_text_field($_POST['grade']);
    $board = sanitize_text_field($_POST['board']);
    
    // Generate enquiry number
    $enquiry_number = 'ENQ' . date('YdHis');
    
    // Insert into database
    $result = $wpdb->insert(
        $wpdb->prefix . 'edubot_enquiries',
        array(
            'enquiry_number' => $enquiry_number,
            'student_name' => $student_name,
            'email' => $email,
            'phone' => $phone,
            'grade' => $grade,
            'board' => $board,
            'source' => $source,
            'status' => 'new',
            'created_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    if ($result) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
        echo "<p style='color: #155724; font-size: 16px;'><strong>✅ Test Enquiry Created Successfully!</strong></p>";
        echo "<ul>";
        echo "<li><strong>Enquiry Number:</strong> " . htmlspecialchars($enquiry_number) . "</li>";
        echo "<li><strong>Student:</strong> " . htmlspecialchars($student_name) . "</li>";
        echo "<li><strong>Source:</strong> <code>" . htmlspecialchars($source) . "</code></li>";
        echo "<li><strong>Email:</strong> " . htmlspecialchars($email) . "</li>";
        echo "<li><strong>Phone:</strong> " . htmlspecialchars($phone) . "</li>";
        echo "</ul>";
        echo "<p><a href='http://localhost/demo/debug_log_viewer.php' class='button'>View Debug Log</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0;'>";
        echo "<p style='color: #721c24;'><strong>❌ Error creating enquiry:</strong> " . esc_html($wpdb->last_error) . "</p>";
        echo "</div>";
    }
}

echo "<h2>📋 Testing Checklist</h2>";
echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
echo "<ul>";
echo "<li><input type='checkbox'> Test chatbot source</li>";
echo "<li><input type='checkbox'> Test with Google Ads UTM params</li>";
echo "<li><input type='checkbox'> Test with Facebook UTM params</li>";
echo "<li><input type='checkbox'> Test with Email UTM params</li>";
echo "<li><input type='checkbox'> Verify each enquiry saves with correct source</li>";
echo "<li><input type='checkbox'> Check WhatsApp notifications sent</li>";
echo "<li><input type='checkbox'> Check email notifications sent</li>";
echo "<li><input type='checkbox'> View debug logs for all tests</li>";
echo "</ul>";
echo "</div>";

echo "<h2>📊 View Test Results</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border: 1px solid #b3d9ff; border-radius: 5px;'>";
echo "<p>After testing, view all enquiries and their sources:</p>";
echo "<a href='http://localhost/demo/view_enquiries_by_source.php' class='button button-primary'>View Enquiries by Source</a>";
echo "<a href='http://localhost/demo/debug_log_viewer.php' class='button'>View Debug Log</a>";
echo "</div>";

?>
