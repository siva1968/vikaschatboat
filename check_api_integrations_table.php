<?php
/**
 * Check API Integrations Table
 */

require_once('D:/xampp/htdocs/demo/wp-load.php');

echo "=== CHECKING API INTEGRATIONS TABLE ===\n\n";

global $wpdb;
$table = $wpdb->prefix . 'edubot_api_integrations';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
echo "Table '$table' exists: " . ($table_exists ? 'YES' : 'NO') . "\n\n";

if ($table_exists) {
    // Get all records
    $records = $wpdb->get_results("SELECT * FROM $table");

    if (empty($records)) {
        echo "⚠️  TABLE IS EMPTY!\n\n";
        echo "This is why ZeptoMail settings are not being used.\n";
        echo "Settings are saved to WordPress options but not to this table.\n\n";
    } else {
        echo "Found " . count($records) . " record(s):\n\n";

        foreach ($records as $record) {
            echo "Site ID: " . $record->site_id . "\n";
            echo "Email Provider: " . ($record->email_provider ?? 'NULL') . "\n";
            echo "Email API Key: " . (!empty($record->email_api_key) ? substr($record->email_api_key, 0, 20) . '...' : 'NULL') . "\n";
            echo "Email From Address: " . ($record->email_from_address ?? 'NULL') . "\n";
            echo "Email From Name: " . ($record->email_from_name ?? 'NULL') . "\n";
            echo "WhatsApp Provider: " . ($record->whatsapp_provider ?? 'NULL') . "\n";
            echo "SMS Provider: " . ($record->sms_provider ?? 'NULL') . "\n";
            echo "Created: " . ($record->created_at ?? 'NULL') . "\n";
            echo "Updated: " . ($record->updated_at ?? 'NULL') . "\n";
            echo "\n";
        }
    }

    echo "WORDPRESS OPTIONS (Fallback):\n";
    echo str_repeat("-", 60) . "\n";
    echo "edubot_email_service: " . get_option('edubot_email_service', 'NOT SET') . "\n";
    echo "edubot_email_api_key: " . (!empty(get_option('edubot_email_api_key')) ? 'CONFIGURED' : 'NOT SET') . "\n";
    echo "edubot_email_from_address: " . get_option('edubot_email_from_address', 'NOT SET') . "\n";
    echo "edubot_email_from_name: " . get_option('edubot_email_from_name', 'NOT SET') . "\n\n";

    echo "HOW EduBot_API_Migration::get_api_settings() WORKS:\n";
    echo str_repeat("=", 60) . "\n";
    echo "1. First checks wp_edubot_api_integrations table\n";
    echo "2. If record exists, uses table values\n";
    echo "3. For missing fields, falls back to WordPress options\n";
    echo "4. If no record exists, uses ONLY WordPress options\n\n";

    if (empty($records)) {
        echo "CURRENT STATUS: No record in table\n";
        echo "System should be using WordPress options as fallback\n\n";

        $email_service = get_option('edubot_email_service', 'NOT SET');
        $email_api_key = get_option('edubot_email_api_key', '');

        if ($email_service === 'NOT SET' || empty($email_api_key)) {
            echo "❌ PROBLEM: WordPress options are also incomplete!\n";
            echo "   - Email Service: " . $email_service . "\n";
            echo "   - API Key: " . (empty($email_api_key) ? 'MISSING' : 'Present') . "\n\n";
            echo "This means when you click 'Save Email Settings' in admin:\n";
            echo "- Data is NOT being saved to WordPress options\n";
            echo "- Data is NOT being saved to API integrations table\n\n";
            echo "Need to check why the save operation is failing!\n";
        } else {
            echo "✓ WordPress options ARE configured:\n";
            echo "  - Email Service: $email_service\n";
            echo "  - API Key: Present\n\n";
            echo "System SHOULD be using these values.\n";
            echo "If still not working, the issue is elsewhere.\n";
        }
    } else {
        $record = $records[0];
        if (empty($record->email_provider) || $record->email_provider === 'NULL') {
            echo "❌ PROBLEM: Record exists but email_provider is NULL/empty\n";
            echo "System will fall back to WordPress option:\n";
            echo "  get_option('edubot_email_service'): " . get_option('edubot_email_service', 'NOT SET') . "\n\n";
        }
    }
} else {
    echo "❌ TABLE DOESN'T EXIST!\n";
    echo "The table wp_edubot_api_integrations was not created during activation.\n";
    echo "System should fall back to WordPress options entirely.\n\n";

    echo "WordPress Options:\n";
    echo "edubot_email_service: " . get_option('edubot_email_service', 'NOT SET') . "\n";
    echo "edubot_email_api_key: " . (!empty(get_option('edubot_email_api_key')) ? 'CONFIGURED' : 'NOT SET') . "\n";
}

echo "\n=== CHECK COMPLETE ===\n";
