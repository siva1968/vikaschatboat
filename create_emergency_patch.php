<?php
/**
 * CRITICAL: Direct Code Patch for EduBot
 * This creates a patched version of the shortcode file that bypasses AI completely
 */

echo "=== CREATING DIRECT PATCH FILE ===\n";
echo "===================================\n\n";

// Read the original shortcode file
$original_file = 'includes/class-edubot-shortcode.php';
$backup_file = 'includes/class-edubot-shortcode.php.backup';
$patched_file = 'includes/class-edubot-shortcode-PATCHED.php';

if (file_exists($original_file)) {
    echo "✅ Found original shortcode file\n";
    
    // Create backup
    if (copy($original_file, $backup_file)) {
        echo "✅ Created backup: {$backup_file}\n";
    }
    
    // Read the file content
    $content = file_get_contents($original_file);
    
    // Apply the critical fix - force rule-based responses
    $search = 'return $this->get_ai_enhanced_response($message, $session_id, $action_type);';
    $replace = 'return $this->provide_intelligent_fallback($message, $action_type, $session_id);';
    
    if (strpos($content, $search) !== false) {
        echo "✅ Found the problematic AI call\n";
        
        $patched_content = str_replace($search, $replace, $content);
        
        // Also add emergency checks at the beginning of generate_response method
        $method_search = 'private function generate_response($message, $action_type, $session_id = \'\') {';
        $emergency_check = 'private function generate_response($message, $action_type, $session_id = \'\') {
        // EMERGENCY FIX: Force rule-based responses to prevent 500 errors
        error_log(\'EduBot: Emergency mode - using rule-based responses only\');
        return $this->provide_intelligent_fallback($message, $action_type, $session_id);
        
        /* ORIGINAL CODE COMMENTED OUT TO PREVENT 500 ERRORS
';
        
        if (strpos($patched_content, $method_search) !== false) {
            $patched_content = str_replace($method_search, $emergency_check, $patched_content);
            echo "✅ Added emergency check at method start\n";
        }
        
        // Write the patched file
        if (file_put_contents($patched_file, $patched_content)) {
            echo "✅ Created patched file: {$patched_file}\n";
            echo "\n🎯 INSTRUCTIONS:\n";
            echo "================\n";
            echo "1. Upload {$patched_file} to your server\n";
            echo "2. Rename your current class-edubot-shortcode.php to class-edubot-shortcode.php.old\n";
            echo "3. Rename {$patched_file} to class-edubot-shortcode.php\n";
            echo "4. Test your chatbot - 500 error should be gone!\n\n";
            
            echo "📋 FILE OPERATIONS:\n";
            echo "===================\n";
            echo "ON YOUR SERVER:\n";
            echo "mv wp-content/plugins/edubot-pro/includes/class-edubot-shortcode.php wp-content/plugins/edubot-pro/includes/class-edubot-shortcode.php.old\n";
            echo "# Upload the patched file\n";
            echo "mv class-edubot-shortcode-PATCHED.php class-edubot-shortcode.php\n";
            
        } else {
            echo "❌ Failed to create patched file\n";
        }
    } else {
        echo "❌ Could not find the AI call to patch\n";
        echo "The file might have a different structure\n";
    }
} else {
    echo "❌ Original shortcode file not found: {$original_file}\n";
}

echo "\n=== PATCH CREATION COMPLETE ===\n";
?>
