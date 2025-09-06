<?php
/**
 * Diagnostic Tool for EduBot Color Loading
 * This will help identify where the color values are coming from
 */

echo "=== EduBot Color Loading Diagnostic ===\n\n";

// Check WordPress options first
$wp_primary = get_option('edubot_primary_color', 'NOT SET');
$wp_secondary = get_option('edubot_secondary_color', 'NOT SET');

echo "1. WordPress Options Check:\n";
echo "   edubot_primary_color: {$wp_primary}\n";
echo "   edubot_secondary_color: {$wp_secondary}\n\n";

// Check database table
global $wpdb;
$table = $wpdb->prefix . 'edubot_school_configs';
$site_id = get_current_blog_id();

echo "2. Database Table Check:\n";
echo "   Table: {$table}\n";
echo "   Site ID: {$site_id}\n";

$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
echo "   Table exists: " . ($table_exists ? 'YES' : 'NO') . "\n\n";

if ($table_exists) {
    $config_data = $wpdb->get_var($wpdb->prepare(
        "SELECT config_data FROM $table WHERE site_id = %d AND status = 'active'",
        $site_id
    ));
    
    echo "3. Database Config Data:\n";
    if ($config_data) {
        echo "   Raw config found: " . strlen($config_data) . " characters\n";
        
        $decoded = json_decode($config_data, true);
        if ($decoded && isset($decoded['school_info']['colors'])) {
            echo "   Database Primary: " . ($decoded['school_info']['colors']['primary'] ?? 'NOT SET') . "\n";
            echo "   Database Secondary: " . ($decoded['school_info']['colors']['secondary'] ?? 'NOT SET') . "\n";
        } else {
            echo "   Colors not found in database config\n";
        }
    } else {
        echo "   No config data found in database\n";
    }
} else {
    echo "3. Database table does not exist\n";
}

echo "\n4. EduBot School Config Loading Test:\n";

if (class_exists('EduBot_School_Config')) {
    try {
        $school_config = EduBot_School_Config::getInstance();
        $config = $school_config->get_config();
        
        echo "   School Config loaded successfully\n";
        echo "   Primary from config: " . ($config['school_info']['colors']['primary'] ?? 'NOT SET') . "\n";
        echo "   Secondary from config: " . ($config['school_info']['colors']['secondary'] ?? 'NOT SET') . "\n";
        
        // Test the exact logic from shortcode
        $colors = array(
            'primary' => isset($config['school_info']['colors']['primary']) ? $config['school_info']['colors']['primary'] : get_option('edubot_primary_color', '#4facfe'),
            'secondary' => isset($config['school_info']['colors']['secondary']) ? $config['school_info']['colors']['secondary'] : get_option('edubot_secondary_color', '#00f2fe')
        );
        
        echo "\n5. Final Color Resolution (Shortcode Logic):\n";
        echo "   Final Primary: {$colors['primary']}\n";
        echo "   Final Secondary: {$colors['secondary']}\n";
        
        // Check if these match your expected values
        if ($colors['primary'] === '#74a211' && $colors['secondary'] === '#113b02') {
            echo "   ✅ Colors match your expected database values!\n";
        } else {
            echo "   ❌ Colors do NOT match expected values (#74a211, #113b02)\n";
        }
        
    } catch (Exception $e) {
        echo "   Error loading school config: " . $e->getMessage() . "\n";
    }
} else {
    echo "   EduBot_School_Config class not found\n";
}

echo "\n6. Recommended Actions:\n";
if ($wp_primary !== 'NOT SET' || $wp_secondary !== 'NOT SET') {
    echo "   ⚠️  WordPress options are set and may override database values\n";
    echo "   Consider clearing WordPress options or updating database config\n";
}

if (!$table_exists) {
    echo "   ⚠️  Database table missing - colors will use WordPress options or defaults\n";
}

echo "\n7. Quick Fix Options:\n";
echo "   Option A: Update WordPress options to your colors\n";
echo "   Option B: Ensure database has correct color values\n";
echo "   Option C: Force colors in CSS with !important\n";

?>
