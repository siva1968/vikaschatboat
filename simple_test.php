<?php
echo "Testing Multi-Flow System...\n";

// Test basic class loading
try {
    include_once 'includes/class-edubot-flow-manager.php';
    echo "✅ Flow Manager class loaded successfully\n";
    
    // Test class instantiation
    $flow_manager = new EduBot_Flow_Manager();
    echo "✅ Flow Manager instantiated successfully\n";
    
    // Test available flows
    $flows = $flow_manager->get_available_flows();
    echo "✅ Available flows: " . count($flows) . "\n";
    
    foreach ($flows as $type => $config) {
        echo "   - {$type}: {$config['name']}\n";
    }
    
} catch (Error $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\nBasic functionality test complete.\n";
?>
