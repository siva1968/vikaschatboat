<?php
/**
 * WhatsApp Ad Integration - Integration Points
 * 
 * Add these code snippets to your main plugin file to activate
 * the WhatsApp ad integration functionality
 * 
 * @package EduBot_Pro
 */

// ============================================================================
// STEP 1: Add to includes/class-edubot-activator.php
// ============================================================================

/**
 * Add these methods to class-edubot-activator.php in the activate() method
 */

// Add to the SQL creation in activate() static method:
global $wpdb;
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

// Create ad campaigns table
dbDelta(self::sql_ad_campaigns());

// Create WhatsApp sessions table
dbDelta(self::sql_whatsapp_sessions());

// Create contacts table
dbDelta(self::sql_contacts());

// Create WhatsApp messages table
dbDelta(self::sql_whatsapp_messages());

// Create ad link metadata table
dbDelta(self::sql_ad_link_metadata());

/**
 * SQL for ad campaigns table
 */
private static function sql_ad_campaigns() {
    global $wpdb;
    $table = $wpdb->prefix . 'edubot_ad_campaigns';
    $charset_collate = $wpdb->get_charset_collate();
    
    return "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        source varchar(100) NOT NULL,
        grades longtext,
        whatsapp_link longtext,
        status varchar(50) DEFAULT 'active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_source (source),
        KEY idx_status (status)
    ) $charset_collate;";
}

/**
 * SQL for WhatsApp sessions table
 */
private static function sql_whatsapp_sessions() {
    global $wpdb;
    $table = $wpdb->prefix . 'edubot_whatsapp_sessions';
    $charset_collate = $wpdb->get_charset_collate();
    
    return "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        session_id varchar(255) NOT NULL UNIQUE,
        contact_id bigint(20),
        phone varchar(20) NOT NULL,
        campaign_id bigint(20),
        source varchar(100),
        campaign varchar(255),
        medium varchar(100),
        utm_source varchar(255),
        utm_medium varchar(255),
        utm_campaign varchar(255),
        state varchar(50) DEFAULT 'greeting',
        data longtext,
        ip_address varchar(45),
        user_agent longtext,
        started_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        completed_at datetime,
        PRIMARY KEY (id),
        UNIQUE KEY idx_session_id (session_id),
        KEY idx_phone (phone),
        KEY idx_contact_id (contact_id),
        KEY idx_campaign_id (campaign_id),
        KEY idx_source (source),
        KEY idx_completed (completed_at)
    ) $charset_collate;";
}

/**
 * SQL for contacts table
 */
private static function sql_contacts() {
    global $wpdb;
    $table = $wpdb->prefix . 'edubot_contacts';
    $charset_collate = $wpdb->get_charset_collate();
    
    return "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        phone varchar(20) NOT NULL UNIQUE,
        name varchar(255),
        email varchar(255),
        source varchar(100),
        status varchar(50) DEFAULT 'active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        last_contacted_at datetime,
        PRIMARY KEY (id),
        KEY idx_phone (phone),
        KEY idx_email (email),
        KEY idx_source (source)
    ) $charset_collate;";
}

/**
 * SQL for WhatsApp messages table
 */
private static function sql_whatsapp_messages() {
    global $wpdb;
    $table = $wpdb->prefix . 'edubot_whatsapp_messages';
    $charset_collate = $wpdb->get_charset_collate();
    
    return "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        session_id varchar(255) NOT NULL,
        sender varchar(50) NOT NULL,
        message longtext NOT NULL,
        message_id varchar(255),
        delivery_status varchar(50),
        delivery_timestamp datetime,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_session_id (session_id),
        KEY idx_sender (sender),
        KEY idx_created (created_at)
    ) $charset_collate;";
}

/**
 * SQL for ad link metadata table
 */
private static function sql_ad_link_metadata() {
    global $wpdb;
    $table = $wpdb->prefix . 'edubot_ad_link_metadata';
    $charset_collate = $wpdb->get_charset_collate();
    
    return "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        source varchar(100),
        campaign varchar(255),
        medium varchar(100),
        content varchar(255),
        grades longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_source (source),
        KEY idx_campaign (campaign)
    ) $charset_collate;";
}

// ============================================================================
// STEP 2: Add to main plugin file (edubot-pro.php)
// ============================================================================

// Register REST endpoints for WhatsApp
add_action('rest_api_init', function() {
    
    // Handle incoming webhook messages (POST)
    register_rest_route('edubot/v1', '/whatsapp-webhook', array(
        'methods' => 'POST',
        'callback' => function($request) {
            // Verify token
            $token = $request->get_param('token');
            $expected = get_option('edubot_whatsapp_webhook_token', '');
            
            if (!hash_equals($token, $expected)) {
                return new WP_REST_Response(array('error' => 'Invalid token'), 401);
            }
            
            $receiver = new EduBot_WhatsApp_Webhook_Receiver();
            return $receiver->handle_webhook($request);
        },
        'permission_callback' => '__return_true'
    ));
    
    // Handle webhook verification (GET)
    register_rest_route('edubot/v1', '/whatsapp-webhook', array(
        'methods' => 'GET',
        'callback' => function($request) {
            $receiver = new EduBot_WhatsApp_Webhook_Receiver();
            return $receiver->verify_webhook_get($request);
        },
        'permission_callback' => '__return_true'
    ));
});

// ============================================================================
// STEP 3: Add to admin/class-edubot-admin.php
// ============================================================================

// Load WhatsApp ad integration admin page
public function admin_init() {
    // ... existing code ...
    
    // Load WhatsApp Ad Integration page
    if (is_admin() && current_user_can('manage_options')) {
        require_once EDUBOT_PRO_PLUGIN_PATH . 'admin/pages/whatsapp-ad-integration.php';
    }
}

// Add AJAX handlers
add_action('wp_ajax_edubot_generate_whatsapp_link', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied', 403);
    }
    
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'edubot_whatsapp_nonce')) {
        wp_send_json_error('Nonce verification failed', 403);
    }
    
    $campaign = sanitize_text_field($_POST['campaign'] ?? '');
    $source = sanitize_text_field($_POST['source'] ?? '');
    $grades = sanitize_text_field($_POST['grades'] ?? '');
    
    if (empty($campaign) || empty($source)) {
        wp_send_json_error('Missing required parameters');
    }
    
    // Generate link
    $link = EduBot_WhatsApp_Ad_Link_Generator::generate_whatsapp_link(array(
        'source' => $source,
        'campaign' => $campaign,
        'grades' => $grades
    ));
    
    // Create campaign in database
    $campaign_id = EduBot_WhatsApp_Ad_Link_Generator::create_campaign(array(
        'name' => $campaign,
        'source' => $source,
        'grades' => $grades,
        'link' => $link
    ));
    
    wp_send_json_success(array(
        'link' => $link,
        'campaign_id' => $campaign_id
    ));
});

add_action('wp_ajax_edubot_generate_webhook_token', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied', 403);
    }
    
    $token = wp_generate_password(32, false);
    update_option('edubot_whatsapp_webhook_token', $token);
    
    wp_send_json_success(array(
        'token' => $token
    ));
});

// ============================================================================
// STEP 4: Update Autoloader
// ============================================================================

// Add to includes/class-edubot-autoloader.php in the file list:
'class-whatsapp-ad-link-generator.php',
'class-whatsapp-session-manager.php',
'class-whatsapp-webhook-receiver.php',

// ============================================================================
// STEP 5: Update Main Plugin File Hook System
// ============================================================================

// In edubot-pro.php, add to the init hooks:

// Initialize WhatsApp integration
add_action('plugins_loaded', function() {
    if (class_exists('EduBot_WhatsApp_Session_Manager')) {
        error_log('EduBot: WhatsApp integration loaded');
    }
});

// ============================================================================
// USAGE EXAMPLES
// ============================================================================

/*

// Example 1: Generate a WhatsApp link
$link = EduBot_WhatsApp_Ad_Link_Generator::generate_whatsapp_link(array(
    'phone' => '+919876543210',
    'source' => 'facebook_ads',
    'campaign' => 'Summer Admissions 2025',
    'grades' => 'Grade 1'
));

// Example 2: Get active campaigns
$campaigns = EduBot_WhatsApp_Ad_Link_Generator::get_active_campaigns();
foreach ($campaigns as $campaign) {
    echo $campaign->name;
}

// Example 3: Get campaign statistics
$stats = EduBot_WhatsApp_Ad_Link_Generator::get_campaign_stats($campaign_id);
echo "Clicks: " . $stats['clicks'];
echo "Sessions: " . $stats['sessions'];
echo "Conversions: " . $stats['completions'];
echo "Rate: " . $stats['conversion_rate'] . "%";

// Example 4: Get session by phone
$session = EduBot_WhatsApp_Session_Manager::get_session_by_phone('9876543210');
echo "Session ID: " . $session['session_id'];
echo "State: " . $session['state'];

// Example 5: Update session data
EduBot_WhatsApp_Session_Manager::update_session_data($session_id, array(
    'student_name' => 'John Smith',
    'grade' => 'Grade 1'
));

// Example 6: Get session messages
$messages = EduBot_WhatsApp_Session_Manager::get_session_messages($session_id);
foreach ($messages as $msg) {
    echo $msg['sender'] . ": " . $msg['message'];
}

// Example 7: Complete session
EduBot_WhatsApp_Session_Manager::complete_session($session_id, array(
    'application_number' => 'ENQ2025001234',
    'status' => 'submitted'
));

*/

// ============================================================================
// END OF INTEGRATION POINTS
// ============================================================================
