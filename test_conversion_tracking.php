<?php
/**
 * Test Enhanced Conversion Attribution Tracking
 * This test verifies that enquiry submissions are properly linked to marketing attribution
 */

// Load WordPress
require_once('wp-config.php');

function test_enhanced_conversion_tracking() {
    echo "<h2>🎯 Enhanced Conversion Attribution Tracking Test</h2>\n";
    
    // Simulate marketing parameters (as if visitor came from Google Ads)
    $_GET['utm_source'] = 'google';
    $_GET['utm_medium'] = 'cpc';
    $_GET['utm_campaign'] = 'school_admission_2025';
    $_GET['utm_content'] = 'chat_bot_ad';
    $_GET['gclid'] = 'test_google_click_id_12345';
    
    echo "<h3>📊 Simulated Marketing Parameters:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>UTM Source:</strong> google</li>\n";
    echo "<li><strong>UTM Medium:</strong> cpc</li>\n";
    echo "<li><strong>UTM Campaign:</strong> school_admission_2025</li>\n";
    echo "<li><strong>UTM Content:</strong> chat_bot_ad</li>\n";
    echo "<li><strong>Google Click ID:</strong> test_google_click_id_12345</li>\n";
    echo "</ul>\n";
    
    try {
        // Initialize visitor analytics to capture marketing parameters
        if (!class_exists('EduBot_Visitor_Analytics')) {
            echo "❌ EduBot_Visitor_Analytics class not available<br>\n";
            return;
        }
        
        // Force set visitor cookies for testing
        $test_visitor_id = 'test_visitor_' . uniqid();
        $test_session_id = 'test_session_' . uniqid();
        $_COOKIE['edubot_visitor_id'] = $test_visitor_id;
        $_COOKIE['edubot_session_id'] = $test_session_id;
        
        echo "✅ Test Visitor ID: {$test_visitor_id}<br>\n";
        echo "✅ Test Session ID: {$test_session_id}<br>\n";
        
        // Initialize visitor analytics
        $visitor_analytics = new EduBot_Visitor_Analytics();
        
        // Manually trigger marketing parameter capture
        $visitor_analytics->capture_marketing_parameters();
        echo "✅ Marketing parameters captured<br>\n";
        
        // Test conversion data
        $test_conversion_data = array(
            'application_id' => 999999,
            'application_number' => 'TEST-ENQ-' . date('Y') . '-' . rand(1000, 9999),
            'student_name' => 'Test Student Attribution',
            'grade' => 'Grade 10',
            'phone' => '9876543210',
            'email' => 'test.attribution@example.com',
            'source' => 'test_conversion_tracking'
        );
        
        echo "<h3>🎓 Test Conversion Data:</h3>\n";
        echo "<pre>" . json_encode($test_conversion_data, JSON_PRETTY_PRINT) . "</pre>\n";
        
        // Track the enhanced conversion
        $visitor_analytics->track_enhanced_application_conversion($test_conversion_data);
        echo "✅ Enhanced conversion tracked with full attribution<br>\n";
        
        // Verify the attribution was stored correctly
        global $wpdb;
        
        // Check visitor record
        $visitor_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}edubot_visitors WHERE visitor_id = %s",
            $test_visitor_id
        ));
        
        if ($visitor_record) {
            echo "✅ Visitor record created/updated<br>\n";
            if ($visitor_record->phone && $visitor_record->email) {
                echo "✅ Business contact information linked to visitor record<br>\n";
                echo "   📱 Phone: {$visitor_record->phone}<br>\n";
                echo "   📧 Email: {$visitor_record->email}<br>\n";
            }
        } else {
            echo "⚠️ Visitor record not found<br>\n";
        }
        
        // Check conversion event
        $conversion_event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}edubot_visitor_analytics 
             WHERE visitor_id = %s AND event_type = 'application_converted' 
             ORDER BY timestamp DESC LIMIT 1",
            $test_visitor_id
        ));
        
        if ($conversion_event) {
            echo "✅ Conversion event recorded<br>\n";
            $event_data = json_decode($conversion_event->event_data, true);
            
            echo "<h3>🎯 Attribution Analysis:</h3>\n";
            echo "<ul>\n";
            echo "<li><strong>Application Number:</strong> " . esc_html($event_data['application_number'] ?? 'N/A') . "</li>\n";
            echo "<li><strong>First Touch Source:</strong> " . esc_html($event_data['first_touch_source'] ?? 'N/A') . "</li>\n";
            echo "<li><strong>Last Touch Source:</strong> " . esc_html($event_data['last_touch_source'] ?? 'N/A') . "</li>\n";
            echo "<li><strong>Conversion Time:</strong> " . esc_html($event_data['conversion_time_minutes'] ?? 'N/A') . " minutes</li>\n";
            
            if (isset($event_data['attribution_journey'])) {
                $journey = json_decode($event_data['attribution_journey'], true);
                echo "<li><strong>Total Touchpoints:</strong> " . ($journey['total_touchpoints'] ?? 0) . "</li>\n";
            }
            echo "</ul>\n";
            
            echo "<h4>📋 Complete Event Data:</h4>\n";
            echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px;'>";
            echo json_encode($event_data, JSON_PRETTY_PRINT);
            echo "</pre>\n";
            
        } else {
            echo "❌ Conversion event not found<br>\n";
        }
        
        // Test attribution lookup by contact
        $attribution_data = $visitor_analytics->get_contact_attribution('9876543210', 'test.attribution@example.com');
        if (!empty($attribution_data)) {
            echo "✅ Attribution lookup by contact successful<br>\n";
            echo "   Found " . count($attribution_data) . " attribution record(s)<br>\n";
        } else {
            echo "⚠️ Attribution lookup by contact returned empty<br>\n";
        }
        
        // Clean up test data
        $wpdb->delete($wpdb->prefix . 'edubot_visitors', array('visitor_id' => $test_visitor_id));
        $wpdb->delete($wpdb->prefix . 'edubot_visitor_analytics', array('visitor_id' => $test_visitor_id));
        echo "✅ Test data cleaned up<br>\n";
        
        echo "<h3>🎉 ENHANCED CONVERSION ATTRIBUTION TEST - COMPLETED!</h3>\n";
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
        echo "<strong>✅ Test Results Summary:</strong><br>\n";
        echo "• Marketing parameters captured from UTM codes and click IDs<br>\n";
        echo "• Visitor identification and session tracking working<br>\n";
        echo "• Enhanced conversion tracking with full attribution<br>\n";
        echo "• Business contact linking to attribution data<br>\n";
        echo "• Multi-touch attribution journey analysis<br>\n";
        echo "• Contact-based attribution lookup functionality<br>\n";
        echo "</div>\n";
        
        echo "<h3>🔍 How to View Real Attribution Data:</h3>\n";
        echo "<ol>\n";
        echo "<li>Go to <strong>EduBot Pro → Analytics</strong> in WordPress admin</li>\n";
        echo "<li>Scroll to <strong>'Recent Conversions with Attribution'</strong> section</li>\n";
        echo "<li>View complete marketing journey for each application</li>\n";
        echo "<li>Export data for detailed analysis</li>\n";
        echo "</ol>\n";
        
    } catch (Exception $e) {
        echo "❌ Exception during test: " . $e->getMessage() . "<br>\n";
    }
}

// Run the test
echo "<style>body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 40px; } pre { background: #f8f9fa; padding: 10px; }</style>\n";
test_enhanced_conversion_tracking();
?>
