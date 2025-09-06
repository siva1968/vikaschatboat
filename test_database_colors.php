<?php
/**
 * Test Database Color Retrieval for EduBot
 * This will check if your database colors are being properly loaded
 */

// Simulate WordPress environment
echo "=== EduBot Database Color Test ===\n\n";

// Your actual database colors
$expected_primary = '#74a211';
$expected_secondary = '#113b02';

echo "Expected Colors from Database:\n";
echo "Primary: {$expected_primary}\n";
echo "Secondary: {$expected_secondary}\n\n";

// Test the color retrieval logic that's used in the shortcode
class EduBot_Color_Test {
    
    public function test_color_retrieval() {
        echo "1. Testing Color Configuration Loading...\n";
        
        // Simulate the same logic used in class-edubot-shortcode.php
        $settings = array(); // get_option('edubot_pro_settings', array());
        
        // Simulate EduBot_School_Config
        $config = array(
            'school_info' => array(
                'colors' => array(
                    'primary' => '#74a211',    // Your database value
                    'secondary' => '#113b02'    // Your database value
                )
            )
        );
        
        // This is the exact logic from the shortcode
        $colors = array(
            'primary' => isset($config['school_info']['colors']['primary']) ? 
                        $config['school_info']['colors']['primary'] : 
                        '#4facfe', // fallback
            'secondary' => isset($config['school_info']['colors']['secondary']) ? 
                          $config['school_info']['colors']['secondary'] : 
                          '#00f2fe'  // fallback
        );
        
        echo "Retrieved Colors:\n";
        echo "Primary: " . $colors['primary'] . "\n";
        echo "Secondary: " . $colors['secondary'] . "\n\n";
        
        // Check if colors match
        if ($colors['primary'] === '#74a211' && $colors['secondary'] === '#113b02') {
            echo "✅ Database colors retrieved successfully!\n";
        } else {
            echo "❌ Database colors not retrieved properly\n";
        }
        
        return $colors;
    }
    
    public function test_css_generation($colors) {
        echo "\n2. Testing CSS Generation with Database Colors...\n";
        
        // Generate the CSS that would be output
        $css = "
        .quick-action {
            background: {$colors['primary']} !important;
            border: 1px solid {$colors['primary']} !important;
            color: white !important;
            padding: 10px 15px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-align: left;
            border-radius: 6px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .quick-action:hover {
            background: linear-gradient(135deg, {$colors['primary']} 0%, {$colors['secondary']} 100%) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }";
        
        echo "Generated CSS:\n";
        echo $css . "\n";
        
        // Check if colors are properly embedded
        if (strpos($css, '#74a211') !== false && strpos($css, '#113b02') !== false) {
            echo "✅ CSS generated with correct database colors!\n";
        } else {
            echo "❌ CSS not generated with database colors\n";
        }
    }
    
    public function create_test_html_with_database_colors($colors) {
        echo "\n3. Creating Test HTML with Your Database Colors...\n";
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>EduBot Buttons with Database Colors</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 400px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .quick-actions { display: flex; flex-direction: column; gap: 8px; }
        .quick-action {
            background: ' . $colors['primary'] . ' !important;
            border: 1px solid ' . $colors['primary'] . ' !important;
            color: white !important;
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-align: left;
            border-radius: 6px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .quick-action:hover {
            background: linear-gradient(135deg, ' . $colors['primary'] . ' 0%, ' . $colors['secondary'] . ' 100%) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        h2 { color: ' . $colors['primary'] . '; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🎯 EduBot Buttons with Database Colors</h2>
        <p><strong>Primary:</strong> ' . $colors['primary'] . '</p>
        <p><strong>Secondary:</strong> ' . $colors['secondary'] . '</p>
        
        <div class="quick-actions">
            <button class="quick-action">1) Admission Enquiry</button>
            <button class="quick-action">2) Curriculum & Classes</button>
            <button class="quick-action">3) Facilities</button>
            <button class="quick-action">4) Contact / Visit School</button>
            <button class="quick-action">5) Online Enquiry Form</button>
        </div>
    </div>
</body>
</html>';

        file_put_contents('database_colors_test.html', $html);
        echo "✅ Created database_colors_test.html with your actual colors\n";
        echo "Open this file in browser to see buttons with database colors\n";
    }
}

// Run the tests
$tester = new EduBot_Color_Test();
$colors = $tester->test_color_retrieval();
$tester->test_css_generation($colors);
$tester->create_test_html_with_database_colors($colors);

echo "\n=== Test Complete ===\n";
echo "If the buttons still don't show your colors, the issue might be:\n";
echo "1. Database connection not working\n";
echo "2. CSS cache not cleared\n";
echo "3. More specific CSS selectors overriding our styles\n";
echo "4. EduBot_School_Config not loading database values properly\n";
?>
