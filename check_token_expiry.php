<?php
/**
 * WhatsApp Access Token Checker
 * Checks if your access token is permanent and provides token information
 */

$ACCESS_TOKEN = 'EAASeCKYjY2sBPfLljPAnLtWsXwUzCzPZAd92PfUIqaScZAFjpM9fK3UhLzxxt4OhgzLYpRpWZAlmVjZCSpTV19FcJXRZALTtHlbtjCqNfp5BLdLmXZBzW90c4v4REIko62w6QguwNMWXN1qITGK9D1su8YeILdogvDPeJTOIjdBrC2VgnzKKOLWKOAOT2n2wZDZD';

echo "=== WhatsApp Access Token Analysis ===\n";
echo "Token: " . substr($ACCESS_TOKEN, 0, 20) . "...\n";
echo "Test Date: " . date('Y-m-d H:i:s') . "\n";
echo "======================================\n\n";

/**
 * Check token information and expiry
 */
function check_token_info($access_token) {
    echo "Step 1: Checking Token Information\n";
    echo "==================================\n";
    
    // Use Facebook's debug token endpoint
    $debug_url = "https://graph.facebook.com/debug_token";
    $params = array(
        'input_token' => $access_token,
        'access_token' => $access_token
    );
    
    $url = $debug_url . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        echo "❌ Network Error: {$curl_error}\n";
        return false;
    }
    
    echo "Response Code: {$http_code}\n";
    
    if ($http_code === 200) {
        $token_info = json_decode($response, true);
        
        if (isset($token_info['data'])) {
            $data = $token_info['data'];
            
            echo "✅ Token Information Retrieved:\n";
            echo "------------------------------\n";
            
            // App ID
            if (isset($data['app_id'])) {
                echo "📱 App ID: {$data['app_id']}\n";
            }
            
            // Token validity
            if (isset($data['is_valid'])) {
                echo "✅ Token Valid: " . ($data['is_valid'] ? 'Yes' : 'No') . "\n";
            }
            
            // Application name
            if (isset($data['application'])) {
                echo "🏷️  Application: {$data['application']}\n";
            }
            
            // User ID (for user tokens) or empty for app tokens
            if (isset($data['user_id'])) {
                echo "👤 User ID: {$data['user_id']}\n";
            }
            
            // Scopes/Permissions
            if (isset($data['scopes']) && is_array($data['scopes'])) {
                echo "🔐 Permissions: " . implode(', ', $data['scopes']) . "\n";
            }
            
            // Most important: Expiry information
            if (isset($data['expires_at'])) {
                if ($data['expires_at'] == 0) {
                    echo "⏰ Expiry: ✅ PERMANENT TOKEN (Never expires)\n";
                    echo "🎉 This is perfect for production use!\n";
                } else {
                    $expiry_date = date('Y-m-d H:i:s', $data['expires_at']);
                    $time_left = $data['expires_at'] - time();
                    
                    echo "⏰ Expiry: ❌ TEMPORARY TOKEN\n";
                    echo "📅 Expires: {$expiry_date}\n";
                    
                    if ($time_left > 0) {
                        $hours_left = round($time_left / 3600, 1);
                        echo "⏳ Time Left: {$hours_left} hours\n";
                        echo "⚠️  You need to generate a permanent token!\n";
                    } else {
                        echo "🚨 TOKEN HAS EXPIRED!\n";
                    }
                }
            } else {
                echo "⏰ Expiry: Information not available\n";
            }
            
            // Token type analysis
            if (isset($data['type'])) {
                echo "🏷️  Token Type: {$data['type']}\n";
            }
            
            echo "\n";
            return $data;
        }
    } else {
        echo "❌ Failed to get token information\n";
        echo "Response: {$response}\n";
    }
    
    return false;
}

/**
 * Test current token functionality
 */
function test_token_functionality($access_token) {
    echo "Step 2: Testing Token Functionality\n";
    echo "===================================\n";
    
    // Test with your phone number ID
    $phone_number_id = '614525638411206';
    $test_url = "https://graph.facebook.com/v17.0/{$phone_number_id}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $test_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "WhatsApp API Test: ";
    if ($http_code === 200) {
        echo "✅ Working\n";
        
        $phone_info = json_decode($response, true);
        if (isset($phone_info['verified_name'])) {
            echo "Business Name: {$phone_info['verified_name']}\n";
        }
        if (isset($phone_info['display_phone_number'])) {
            echo "Phone Number: {$phone_info['display_phone_number']}\n";
        }
        
        return true;
    } else {
        echo "❌ Failed (Code: {$http_code})\n";
        echo "Response: {$response}\n";
        return false;
    }
}

// Run the checks
$token_data = check_token_info($ACCESS_TOKEN);
echo "\n";
test_token_functionality($ACCESS_TOKEN);

echo "\n" . str_repeat("=", 60) . "\n";
echo "RECOMMENDATIONS:\n";
echo str_repeat("=", 60) . "\n";

if ($token_data && isset($token_data['expires_at'])) {
    if ($token_data['expires_at'] == 0) {
        echo "✅ EXCELLENT: You have a permanent token!\n";
        echo "🚀 This token will never expire automatically.\n";
        echo "💡 Keep it secure and use it for production.\n\n";
        
        echo "Token Security Tips:\n";
        echo "- Store securely (EduBot already encrypts it)\n";
        echo "- Don't share publicly\n";
        echo "- Monitor in Meta Business Manager\n";
        echo "- Can be revoked/regenerated if needed\n";
    } else {
        echo "⚠️  WARNING: You have a temporary token!\n";
        echo "🔄 You MUST generate a permanent token for production.\n\n";
        
        echo "How to Generate Permanent Token:\n";
        echo "================================\n";
        echo "1. Go to Meta Business Manager: https://business.facebook.com/\n";
        echo "2. Navigate to Business Settings → System Users\n";
        echo "3. Create or select a system user\n";
        echo "4. Add WhatsApp Business Management permissions\n";
        echo "5. Generate Access Token → Select 'Never expires'\n";
        echo "6. Copy the permanent token\n";
        echo "7. Update in EduBot Pro → API Integrations\n\n";
        
        if (isset($token_data['expires_at']) && $token_data['expires_at'] > time()) {
            $hours_left = round(($token_data['expires_at'] - time()) / 3600, 1);
            echo "⏰ Current token expires in {$hours_left} hours\n";
            echo "🚨 Replace it before expiry to avoid service interruption!\n";
        }
    }
} else {
    echo "❓ Could not determine token expiry status.\n";
    echo "💡 Recommended: Generate a permanent token to be safe.\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "For production use, always use PERMANENT tokens from System Users!\n";
echo str_repeat("=", 60) . "\n";

?>
