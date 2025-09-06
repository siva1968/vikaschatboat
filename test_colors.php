<?php
/**
 * Test EduBot Color Configuration
 * This file will show what colors are being used by EduBot
 */

// WordPress environment simulation
if (!defined('ABSPATH')) {
    // Simulate WordPress functions for testing
    function get_option($option, $default = '') {
        $options = array(
            'edubot_primary_color' => '#4facfe',
            'edubot_secondary_color' => '#00f2fe'
        );
        return isset($options[$option]) ? $options[$option] : $default;
    }
    
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

echo "=== EduBot Color Configuration Test ===\n\n";

// Test the color configuration logic
class EduBot_School_Config_Test {
    public static function getInstance() {
        return new self();
    }
    
    public function get_config() {
        // Simulate config with colors
        return array(
            'school_info' => array(
                'name' => 'Epistemo Vikas Leadership School',
                'colors' => array(
                    'primary' => '#4facfe',
                    'secondary' => '#00f2fe'
                )
            )
        );
    }
}

// Simulate the exact logic from the shortcode
$school_config = EduBot_School_Config_Test::getInstance();
$config = $school_config->get_config();

$colors = array(
    'primary' => isset($config['school_info']['colors']['primary']) ? $config['school_info']['colors']['primary'] : get_option('edubot_primary_color', '#4facfe'),
    'secondary' => isset($config['school_info']['colors']['secondary']) ? $config['school_info']['colors']['secondary'] : get_option('edubot_secondary_color', '#00f2fe')
);

echo "Colors Configuration:\n";
echo "Primary Color: " . $colors['primary'] . "\n";
echo "Secondary Color: " . $colors['secondary'] . "\n\n";

// Generate the actual CSS that would be output
echo "Generated CSS for Quick Action Buttons:\n\n";
echo ".quick-action {\n";
echo "    background: " . esc_attr($colors['primary']) . ";\n";
echo "    border: 1px solid " . esc_attr($colors['primary']) . ";\n";
echo "    border-radius: 6px;\n";
echo "    padding: 10px 15px;\n";
echo "    font-size: 13px;\n";
echo "    font-weight: 500;\n";
echo "    cursor: pointer;\n";
echo "    text-align: left;\n";
echo "    color: white;\n";
echo "    transition: all 0.3s ease;\n";
echo "    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);\n";
echo "}\n\n";

echo ".quick-action:hover {\n";
echo "    background: linear-gradient(135deg, " . esc_attr($colors['primary']) . " 0%, " . esc_attr($colors['secondary']) . " 100%);\n";
echo "    transform: translateY(-1px);\n";
echo "    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);\n";
echo "}\n\n";

// Create HTML test
$html_output = '
<!DOCTYPE html>
<html>
<head>
    <title>EduBot Color Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .test-container { background: white; padding: 20px; border-radius: 8px; max-width: 400px; margin: 0 auto; }
        .quick-actions { margin-top: 10px; display: flex; flex-direction: column; gap: 5px; }
        .quick-action {
            background: ' . esc_attr($colors['primary']) . ';
            border: 1px solid ' . esc_attr($colors['primary']) . ';
            border-radius: 6px;
            padding: 10px 15px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-align: left;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .quick-action:hover {
            background: linear-gradient(135deg, ' . esc_attr($colors['primary']) . ' 0%, ' . esc_attr($colors['secondary']) . ' 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h2>EduBot Quick Action Buttons Test</h2>
        <p>Primary Color: ' . $colors['primary'] . '</p>
        <p>Secondary Color: ' . $colors['secondary'] . '</p>
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

file_put_contents('edubot_color_test.html', $html_output);
echo "HTML test file created: edubot_color_test.html\n\n";

echo "If colors are not showing:\n";
echo "1. Check browser developer tools for CSS conflicts\n";
echo "2. Clear browser cache\n";
echo "3. Check if there are more specific CSS selectors overriding\n";
echo "4. Verify WordPress is loading the shortcode properly\n";

?>
