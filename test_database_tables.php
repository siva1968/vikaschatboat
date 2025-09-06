<?php
/**
 * Quick test to check if edubot database tables exist
 */

echo "Checking EduBot Database Tables...\n\n";

global $wpdb;

$tables_to_check = array(
    'edubot_applications',
    'edubot_school_configs', 
    'edubot_analytics',
    'edubot_sessions'
);

foreach ($tables_to_check as $table) {
    $table_name = $wpdb->prefix . $table;
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    
    if ($table_exists) {
        echo "✅ Table '$table_name' exists\n";
        
        // Count records
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo "   Records: $count\n";
        
        if ($table === 'edubot_applications' && $count > 0) {
            // Show recent applications
            echo "   Recent applications:\n";
            $recent = $wpdb->get_results("SELECT application_number, status, created_at FROM $table_name ORDER BY created_at DESC LIMIT 3");
            foreach ($recent as $app) {
                echo "   - {$app->application_number} ({$app->status}) - {$app->created_at}\n";
            }
        }
    } else {
        echo "❌ Table '$table_name' does not exist\n";
    }
    echo "\n";
}

echo "Database check complete.\n";
echo "\nIf tables are missing, you may need to:\n";
echo "1. Deactivate and reactivate the plugin\n";  
echo "2. Or run the table creation manually\n";

?>
