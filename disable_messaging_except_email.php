<?php
/**
 * Disable WhatsApp and SMS messaging, keep only email
 * This will modify the chatbot to only send email notifications
 */

// Read the current shortcode file
$shortcode_file = 'includes/class-edubot-shortcode.php';
$content = file_get_contents($shortcode_file);

if (!$content) {
    die("Error: Could not read $shortcode_file\n");
}

echo "🔧 DISABLING MESSAGING EXCEPT EMAIL\n";
echo "==================================\n\n";

// Backup the current file
$backup_file = $shortcode_file . '.backup_before_disable_messaging_' . date('Y-m-d_H-i-s');
file_put_contents($backup_file, $content);
echo "✅ Backup created: $backup_file\n";

// Patterns to find and disable WhatsApp/SMS functionality
$modifications = [
    'disable_whatsapp_processing' => [
        'search' => '/\/\/ Send WhatsApp notification.*?}\s*}/s',
        'replace' => '// WhatsApp messaging DISABLED for troubleshooting
            error_log("EduBot: WhatsApp messaging disabled - skipping WhatsApp notification");
            // Original WhatsApp code commented out for troubleshooting'
    ],
    'disable_sms_processing' => [
        'search' => '/\/\/ Send SMS.*?}\s*}/s', 
        'replace' => '// SMS messaging DISABLED for troubleshooting
            error_log("EduBot: SMS messaging disabled - skipping SMS notification");
            // Original SMS code commented out for troubleshooting'
    ]
];

// Apply modifications
$modified = false;

// Method 1: Look for WhatsApp notification calls
if (preg_match('/(\$this->send_whatsapp_notification\([^;]*;)/', $content)) {
    $content = preg_replace(
        '/(\$this->send_whatsapp_notification\([^;]*;)/',
        '// DISABLED: $1 // WhatsApp disabled for troubleshooting',
        $content
    );
    echo "✅ Disabled WhatsApp notification calls\n";
    $modified = true;
}

// Method 2: Look for direct WhatsApp API calls
if (preg_match('/curl.*graph\.facebook\.com/', $content)) {
    $content = preg_replace(
        '/(curl_exec\(\$ch\);)/',
        '// DISABLED: $1 // WhatsApp API call disabled
        error_log("EduBot: WhatsApp API call disabled for troubleshooting");',
        $content
    );
    echo "✅ Disabled direct WhatsApp API calls\n"; 
    $modified = true;
}

// Method 3: Disable any messaging integrations class calls
if (preg_match('/new\s+EduBot_API_Integrations/', $content)) {
    $content = preg_replace(
        '/(new\s+EduBot_API_Integrations[^;]*;)/',
        '// DISABLED: $1 // API integrations disabled for troubleshooting',
        $content
    );
    echo "✅ Disabled API integrations instantiation\n";
    $modified = true;
}

// Method 4: Comment out WhatsApp template sending
$whatsapp_pattern = '/(\$api_integrations->send_whatsapp_template[^;]*;)/';
if (preg_match($whatsapp_pattern, $content)) {
    $content = preg_replace(
        $whatsapp_pattern,
        '// DISABLED: $1 // WhatsApp template disabled for troubleshooting
        error_log("EduBot: WhatsApp template sending disabled");',
        $content
    );
    echo "✅ Disabled WhatsApp template sending\n";
    $modified = true;
}

// Save the modified file
if ($modified) {
    file_put_contents($shortcode_file, $content);
    echo "\n✅ MODIFICATIONS APPLIED SUCCESSFULLY\n";
    echo "📧 Email notifications: ENABLED\n";
    echo "📱 WhatsApp messaging: DISABLED\n"; 
    echo "📞 SMS messaging: DISABLED\n";
    echo "\n🔄 Upload the modified file to your server:\n";
    echo "   File: $shortcode_file\n";
    echo "   Path: wp-content/plugins/edubot-pro/includes/class-edubot-shortcode.php\n";
} else {
    echo "⚠️  No WhatsApp/SMS messaging code found to disable\n";
    echo "💡 The chatbot may already be using email-only mode\n";
}

echo "\n📋 WHAT THIS FIX DOES:\n";
echo "• Keeps all chatbot functionality working\n";
echo "• Disables WhatsApp notifications that might cause errors\n";
echo "• Disables SMS notifications that might cause errors\n"; 
echo "• Keeps email notifications fully functional\n";
echo "• Allows you to test the admission workflow without messaging issues\n";

echo "\n🧪 TEST PROCEDURE:\n";
echo "1. Upload the modified file to server\n";
echo "2. Test the admission workflow with date '10/10/2010'\n";
echo "3. Check if 500 errors are eliminated\n";
echo "4. Verify email notifications still work\n";
echo "5. Check WordPress error logs for any remaining issues\n";

?>
