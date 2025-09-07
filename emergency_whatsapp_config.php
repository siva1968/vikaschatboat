<?php
// Emergency WhatsApp Configuration for EduBot Pro  
// Upload this to your WordPress root directory and run once
// Instructions: Upload to https://stage.epistemo.in/ and visit the URL once

// Your verified WhatsApp settings
$whatsapp_config = array(
    'access_token' => 'EAASeCKYjY2sBPfLljPAnLtWsXwUzCzPZAd92PfUIqaScZAFjpM9fK3UhLzxxt4OhgzLYpRpWZAlmVjZCSpTV19FcJXRZALTtHlbtjCqNfp5BLdLmXZBzW90c4v4REIko62w6QguwNMWXN1qITGK9D1su8YeILdogvDPeJTOIjdBrC2VgnzKKOLWKOAOT2n2wZDZD',
    'phone_number_id' => '614525638411206',
    'template_name' => 'admission_confirmation',
    'template_namespace' => '9eb1f1dc_68e7_42f1_802a_dbc7582c5c3a',
    'business_name' => 'Sampoorna Digi Branding PVT LTD',
    'business_phone' => '+91 81794 76486'
);

// Include WordPress
require_once('wp-config.php');
require_once('wp-includes/wp-db.php');
require_once('wp-includes/functions.php');

echo "=== EduBot Pro WhatsApp Configuration Update ===\n";

// Update WordPress options
foreach ($whatsapp_config as $key => $value) {
    $option_name = 'edubot_whatsapp_' . $key;
    $result = update_option($option_name, $value);
    
    if ($result) {
        echo "✅ Updated: $option_name\n";
    } else {
        echo "❌ Failed: $option_name\n";
    }
}

// Also try alternative option names
$alt_options = array(
    'edubot_api_whatsapp_token' => $whatsapp_config['access_token'],
    'edubot_api_whatsapp_phone_id' => $whatsapp_config['phone_number_id'],
    'edubot_whatsapp_enabled' => '1',
    'edubot_notifications_whatsapp' => '1'
);

foreach ($alt_options as $key => $value) {
    $result = update_option($key, $value);
    echo $result ? "✅ Updated: $key\n" : "❌ Failed: $key\n";
}

echo "\n=== Configuration Complete ===\n";
echo "Please delete this file after running!\n";
?>
