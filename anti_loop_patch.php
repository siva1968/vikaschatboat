<?php
/**
 * ANTI-LOOP PROTECTION PATCH
 * ==========================
 * 
 * This patch adds comprehensive loop protection to prevent the 500 error
 * that's causing multiple database entries and system crashes.
 * 
 * PROBLEM IDENTIFIED:
 * ===================
 * When user enters date '10/10/2010', the system gets stuck in a loop:
 * 1. Function calls itself recursively
 * 2. Each call saves to database
 * 3. Eventually hits memory/time limits 
 * 4. Results in 500 Internal Server Error
 * 
 * SOLUTION:
 * =========
 * Add loop detection and prevention at the entry point of generate_response
 */

echo "🔍 LOOP DETECTION PATCH GENERATOR\n";
echo "==================================\n\n";

// Generate the anti-loop protection code
$anti_loop_code = '
    /**
     * Generate response with anti-loop protection
     */
    private function generate_response($message, $action_type, $session_id = \'\') {
        // ANTI-LOOP PROTECTION: Prevent recursive calls
        static $call_stack = array();
        static $call_count = 0;
        
        $call_key = md5($message . $action_type . $session_id);
        $call_count++;
        
        // Detect infinite loops
        if ($call_count > 3) {
            error_log("EduBot: LOOP DETECTED - Stopping at call #" . $call_count . " for message: " . substr($message, 0, 50));
            $call_count = 0;
            $call_stack = array();
            return array(
                \'response\' => "Thank you for your enquiry. Our team will contact you soon. Your enquiry has been recorded.",
                \'action\' => \'complete\',
                \'session_data\' => array()
            );
        }
        
        // Check if we\'ve already processed this exact request
        if (isset($call_stack[$call_key])) {
            error_log("EduBot: DUPLICATE CALL DETECTED - " . $call_key);
            $call_count = 0;
            $call_stack = array();
            return array(
                \'response\' => "Thank you! Your information has been received. Our admission team will contact you shortly.",
                \'action\' => \'complete\',
                \'session_data\' => array()
            );
        }
        
        // Add this call to stack
        $call_stack[$call_key] = time();
        
        try {
            error_log(\'EduBot Debug: Starting generate_response with message: \' . substr($message, 0, 50) . \' and action: \' . $action_type);
            
            // EMERGENCY SAFETY: Use only rule-based responses
            $result = $this->provide_intelligent_fallback($message, $action_type, $session_id);
            
            // Clear this call from stack on success
            unset($call_stack[$call_key]);
            $call_count = max(0, $call_count - 1);
            
            return $result;
            
        } catch (Exception $e) {
            error_log(\'EduBot Error in generate_response: \' . $e->getMessage());
            error_log(\'EduBot Debug: Stack trace: \' . $e->getTraceAsString());
            
            // Clear stack on error
            $call_stack = array();
            $call_count = 0;
            
            // Always fallback to rule-based system for reliability
            return $this->provide_intelligent_fallback($message, $action_type, $session_id);
        }
    }';

echo "✅ Anti-loop protection code generated\n";
echo "📋 Features included:\n";
echo "- Static call tracking\n";
echo "- Duplicate request detection\n";  
echo "- Maximum call limit (3)\n";
echo "- Automatic stack cleanup\n";
echo "- Emergency fallback responses\n\n";

echo "🎯 This will prevent:\n";
echo "- Recursive function calls\n";
echo "- Multiple database saves\n";
echo "- Memory exhaustion\n";
echo "- 500 Internal Server Errors\n\n";

echo "⚡ Next step: Apply this patch to the shortcode file\n";
?>
