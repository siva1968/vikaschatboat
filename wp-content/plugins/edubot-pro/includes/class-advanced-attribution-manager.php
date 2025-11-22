<?php
/**
 * Advanced Attribution Manager
 * 
 * Handles comprehensive attribution tracking including:
 * - Multi-touch attribution journeys
 * - Cross-session tracking
 * - Marketing touchpoint recording
 * - Conversion attribution modeling
 * 
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class EduBot_Advanced_Attribution_Manager {
    
    private static $instance = null;
    private $wpdb;
    
    // Attribution models
    const MODEL_FIRST_TOUCH = 'first_touch';
    const MODEL_LAST_TOUCH = 'last_touch';
    const MODEL_LINEAR = 'linear';
    const MODEL_TIME_DECAY = 'time_decay';
    const MODEL_POSITION_BASED = 'position_based';
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Hook into WordPress init
        add_action('init', array($this, 'init_tracking'));
        add_action('wp_loaded', array($this, 'track_page_visit'));
        
        // Hook into EduBot events for conversion tracking
        add_action('edubot_enquiry_created', array($this, 'handle_enquiry_conversion'), 10, 2);
        add_action('edubot_application_saved', array($this, 'handle_application_conversion'), 10, 2);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize tracking on page load
     */
    public function init_tracking() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Track UTM parameters and referrer data
        $this->capture_utm_parameters();
        $this->track_referrer();
    }
    
    /**
     * Track page visit as touchpoint
     */
    public function track_page_visit() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return; // Don't track admin pages
        }
        
        $session_key = $this->get_or_create_session();
        
        // Record this page visit as a touchpoint
        $this->record_touchpoint(array(
            'session_key' => $session_key,
            'source' => $this->get_traffic_source(),
            'medium' => $this->get_traffic_medium(),
            'campaign' => $this->get_campaign_name(),
            'page_url' => $_SERVER['REQUEST_URI'] ?? '',
            'page_title' => get_the_title(),
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'device_type' => $this->detect_device_type()
        ));
    }
    
    /**
     * Record a marketing touchpoint
     */
    public function record_touchpoint($data) {
        $touchpoint_data = array(
            'session_id' => $data['session_key'] ?? null,
            'enquiry_id' => $data['enquiry_id'] ?? null,
            'source' => sanitize_text_field($data['source'] ?? 'direct'),
            'medium' => sanitize_text_field($data['medium'] ?? 'none'),
            'campaign' => sanitize_text_field($data['campaign'] ?? ''),
            'platform_click_id' => sanitize_text_field($data['click_id'] ?? ''),
            'timestamp' => current_time('mysql'),
            'position_in_journey' => $this->get_journey_position($data['session_key']),
            'page_title' => sanitize_text_field($data['page_title'] ?? ''),
            'page_url' => sanitize_text_field($data['page_url'] ?? ''),
            'referrer' => sanitize_text_field($data['referrer'] ?? ''),
            'device_type' => sanitize_text_field($data['device_type'] ?? 'desktop'),
            'attribution_weight' => 1.0, // Will be calculated later
            'created_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'edubot_attribution_touchpoints',
            $touchpoint_data,
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%f', '%s')
        );
        
        if ($result !== false) {
            error_log("EduBot Attribution: Touchpoint recorded - Source: {$data['source']}, Medium: {$data['medium']}");
            return $this->wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Create attribution session
     */
    public function create_attribution_session($enquiry_id, $utm_data = array()) {
        $session_key = $this->get_or_create_session();
        
        // Get first and last touchpoints
        $first_touchpoint = $this->get_first_touchpoint($session_key);
        $last_touchpoint = $this->get_last_touchpoint($session_key);
        
        $session_data = array(
            'enquiry_id' => $enquiry_id,
            'user_session_key' => $session_key,
            'first_touch_source' => $first_touchpoint['source'] ?? 'direct',
            'first_touch_timestamp' => $first_touchpoint['timestamp'] ?? current_time('mysql'),
            'last_touch_source' => $last_touchpoint['source'] ?? 'direct',
            'last_touch_timestamp' => $last_touchpoint['timestamp'] ?? current_time('mysql'),
            'total_touchpoints' => $this->count_session_touchpoints($session_key),
            'attribution_model' => self::MODEL_LAST_TOUCH, // Default model
            'journey_json' => json_encode($this->get_journey_data($session_key)),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'edubot_attribution_sessions',
            $session_data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result !== false) {
            error_log("EduBot Attribution: Session created for enquiry {$enquiry_id}");
            return $this->wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Track conversion event
     */
    public function track_conversion($enquiry_id, $conversion_type, $value = 0) {
        // Get the attribution session for this enquiry
        $attribution_session = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT * FROM {$this->wpdb->prefix}edubot_attribution_sessions 
            WHERE enquiry_id = %d
        ", $enquiry_id), ARRAY_A);
        
        if (!$attribution_session) {
            // Create attribution session if it doesn't exist
            $this->create_attribution_session($enquiry_id);
        }
        
        // Record the conversion
        $conversion_data = array(
            'enquiry_id' => $enquiry_id,
            'conversion_type' => sanitize_text_field($conversion_type),
            'conversion_value' => floatval($value),
            'platform' => 'whatsapp', // Default platform
            'campaign_id' => $this->get_session_campaign_id($enquiry_id),
            'converted_at' => current_time('mysql'),
            'created_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'edubot_conversions',
            $conversion_data,
            array('%d', '%s', '%f', '%s', '%s', '%s', '%s')
        );
        
        if ($result !== false) {
            error_log("EduBot Attribution: Conversion tracked - Type: {$conversion_type}, Value: {$value}");
            
            // Create or update journey
            $this->create_attribution_journey($enquiry_id);
            
            return $this->wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Create complete attribution journey
     */
    public function create_attribution_journey($enquiry_id) {
        // Get all touchpoints for this enquiry's session
        $session = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT * FROM {$this->wpdb->prefix}edubot_attribution_sessions 
            WHERE enquiry_id = %d
        ", $enquiry_id), ARRAY_A);
        
        if (!$session) {
            return false;
        }
        
        $touchpoints = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT * FROM {$this->wpdb->prefix}edubot_attribution_touchpoints 
            WHERE session_id = %s 
            ORDER BY timestamp ASC
        ", $session['user_session_key']), ARRAY_A);
        
        // Calculate journey metrics
        $journey_path = array();
        $total_time = 0;
        
        if (!empty($touchpoints)) {
            $first_touch = strtotime($touchpoints[0]['timestamp']);
            $last_touch = strtotime(end($touchpoints)['timestamp']);
            $total_time = ($last_touch - $first_touch) / 60; // Convert to minutes
            
            foreach ($touchpoints as $tp) {
                $journey_path[] = $tp['source'] . '/' . $tp['medium'];
            }
        }
        
        $journey_data = array(
            'enquiry_id' => $enquiry_id,
            'journey_path' => json_encode($journey_path),
            'journey_length' => count($touchpoints),
            'total_time_minutes' => $total_time,
            'first_touch_source' => $session['first_touch_source'],
            'last_touch_source' => $session['last_touch_source'],
            'conversion_value' => $this->get_conversion_value($enquiry_id),
            'attribution_model' => $session['attribution_model'],
            'calculated_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'edubot_attribution_journeys',
            $journey_data,
            array('%d', '%s', '%d', '%f', '%s', '%s', '%f', '%s', '%s')
        );
        
        if ($result !== false) {
            error_log("EduBot Attribution: Journey created for enquiry {$enquiry_id} - {$journey_data['journey_length']} touchpoints");
            return $this->wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get or create session key
     */
    private function get_or_create_session() {
        if (!isset($_SESSION['edubot_attribution_session'])) {
            $_SESSION['edubot_attribution_session'] = 'attr_' . uniqid() . '_' . time();
        }
        return $_SESSION['edubot_attribution_session'];
    }
    
    /**
     * Capture UTM parameters
     */
    private function capture_utm_parameters() {
        $utm_params = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content');
        
        foreach ($utm_params as $param) {
            if (isset($_GET[$param])) {
                $_SESSION['edubot_' . $param] = sanitize_text_field($_GET[$param]);
            }
        }
    }
    
    /**
     * Track referrer information
     */
    private function track_referrer() {
        if (!empty($_SERVER['HTTP_REFERER']) && !isset($_SESSION['edubot_referrer'])) {
            $_SESSION['edubot_referrer'] = $_SERVER['HTTP_REFERER'];
        }
    }
    
    /**
     * Get traffic source
     */
    private function get_traffic_source() {
        // Check UTM source first
        if (isset($_SESSION['edubot_utm_source'])) {
            return $_SESSION['edubot_utm_source'];
        }
        
        // Check referrer
        if (isset($_SESSION['edubot_referrer'])) {
            $referrer = $_SESSION['edubot_referrer'];
            
            // Social media sources
            if (strpos($referrer, 'facebook.com') !== false) return 'facebook';
            if (strpos($referrer, 'instagram.com') !== false) return 'instagram';
            if (strpos($referrer, 'twitter.com') !== false) return 'twitter';
            if (strpos($referrer, 'linkedin.com') !== false) return 'linkedin';
            if (strpos($referrer, 'youtube.com') !== false) return 'youtube';
            
            // Search engines
            if (strpos($referrer, 'google.com') !== false) return 'google';
            if (strpos($referrer, 'bing.com') !== false) return 'bing';
            if (strpos($referrer, 'yahoo.com') !== false) return 'yahoo';
            
            // Other referrers
            return 'referral';
        }
        
        return 'direct';
    }
    
    /**
     * Get traffic medium
     */
    private function get_traffic_medium() {
        if (isset($_SESSION['edubot_utm_medium'])) {
            return $_SESSION['edubot_utm_medium'];
        }
        
        $source = $this->get_traffic_source();
        
        // Infer medium from source
        if (in_array($source, array('facebook', 'instagram', 'twitter', 'linkedin', 'youtube'))) {
            return 'social';
        }
        
        if (in_array($source, array('google', 'bing', 'yahoo'))) {
            return 'organic';
        }
        
        if ($source === 'referral') {
            return 'referral';
        }
        
        return 'none';
    }
    
    /**
     * Get campaign name
     */
    private function get_campaign_name() {
        return $_SESSION['edubot_utm_campaign'] ?? '';
    }
    
    /**
     * Detect device type
     */
    private function detect_device_type() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/Mobile|Android|iPhone|iPad/', $user_agent)) {
            return 'mobile';
        }
        
        if (preg_match('/Tablet|iPad/', $user_agent)) {
            return 'tablet';
        }
        
        return 'desktop';
    }
    
    /**
     * Handle enquiry conversion event
     */
    public function handle_enquiry_conversion($enquiry_id, $conversion_type = 'enquiry') {
        error_log("EduBot Attribution: Handling enquiry conversion - ID: {$enquiry_id}, Type: {$conversion_type}");
        
        // Create attribution session for this enquiry
        $session_id = $this->create_attribution_session($enquiry_id);
        
        // Track the conversion
        $conversion_id = $this->track_conversion($enquiry_id, $conversion_type, 0);
        
        error_log("EduBot Attribution: Enquiry conversion tracked - Session: {$session_id}, Conversion: {$conversion_id}");
        
        return $conversion_id;
    }
    
    /**
     * Handle application conversion event
     */
    public function handle_application_conversion($application_id, $application_data) {
        error_log("EduBot Attribution: Handling application conversion - ID: {$application_id}");
        
        // Get the enquiry ID from application data or find related enquiry
        $enquiry_id = null;
        
        // Try to find enquiry by application number or other identifiers
        if (isset($application_data['application_number'])) {
            $enquiry = $this->wpdb->get_row($this->wpdb->prepare("
                SELECT id FROM {$this->wpdb->prefix}edubot_enquiries 
                WHERE enquiry_number = %s OR phone = %s OR email = %s
                ORDER BY created_at DESC LIMIT 1
            ", 
            $application_data['application_number'],
            $application_data['phone'] ?? '',
            $application_data['email'] ?? ''
            ), ARRAY_A);
            
            if ($enquiry) {
                $enquiry_id = $enquiry['id'];
            }
        }
        
        if (!$enquiry_id) {
            // Create a new enquiry entry for this application if none found
            $enquiry_data = array(
                'enquiry_number' => $application_data['application_number'] ?? 'APP_' . $application_id,
                'student_name' => $application_data['student_name'] ?? '',
                'email' => $application_data['email'] ?? '',
                'phone' => $application_data['phone'] ?? '',
                'message' => 'Application submission',
                'status' => 'application_submitted',
                'source' => 'application_form',
                'created_at' => current_time('mysql')
            );
            
            $result = $this->wpdb->insert(
                $this->wpdb->prefix . 'edubot_enquiries',
                $enquiry_data
            );
            
            if ($result !== false) {
                $enquiry_id = $this->wpdb->insert_id;
                error_log("EduBot Attribution: Created enquiry {$enquiry_id} for application {$application_id}");
            }
        }
        
        if ($enquiry_id) {
            // Create attribution session if it doesn't exist
            $existing_session = $this->wpdb->get_var($this->wpdb->prepare("
                SELECT session_id FROM {$this->wpdb->prefix}edubot_attribution_sessions 
                WHERE enquiry_id = %d
            ", $enquiry_id));
            
            if (!$existing_session) {
                $this->create_attribution_session($enquiry_id);
            }
            
            // Track the application conversion
            $conversion_id = $this->track_conversion($enquiry_id, 'application', 100); // Application has higher value
            
            error_log("EduBot Attribution: Application conversion tracked - Enquiry: {$enquiry_id}, Conversion: {$conversion_id}");
            
            return $conversion_id;
        }
        
        error_log("EduBot Attribution: Could not find or create enquiry for application {$application_id}");
        return false;
    }
    
    /**
     * Helper methods
     */
    private function get_journey_position($session_key) {
        return $this->wpdb->get_var($this->wpdb->prepare("
            SELECT COUNT(*) + 1 FROM {$this->wpdb->prefix}edubot_attribution_touchpoints 
            WHERE session_id = %s
        ", $session_key));
    }
    
    private function get_first_touchpoint($session_key) {
        return $this->wpdb->get_row($this->wpdb->prepare("
            SELECT * FROM {$this->wpdb->prefix}edubot_attribution_touchpoints 
            WHERE session_id = %s ORDER BY timestamp ASC LIMIT 1
        ", $session_key), ARRAY_A);
    }
    
    private function get_last_touchpoint($session_key) {
        return $this->wpdb->get_row($this->wpdb->prepare("
            SELECT * FROM {$this->wpdb->prefix}edubot_attribution_touchpoints 
            WHERE session_id = %s ORDER BY timestamp DESC LIMIT 1
        ", $session_key), ARRAY_A);
    }
    
    private function count_session_touchpoints($session_key) {
        return $this->wpdb->get_var($this->wpdb->prepare("
            SELECT COUNT(*) FROM {$this->wpdb->prefix}edubot_attribution_touchpoints 
            WHERE session_id = %s
        ", $session_key));
    }
    
    private function get_journey_data($session_key) {
        return $this->wpdb->get_results($this->wpdb->prepare("
            SELECT source, medium, campaign, timestamp FROM {$this->wpdb->prefix}edubot_attribution_touchpoints 
            WHERE session_id = %s ORDER BY timestamp ASC
        ", $session_key), ARRAY_A);
    }
    
    private function get_session_campaign_id($enquiry_id) {
        // Get campaign from the session or touchpoints
        $session = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT user_session_key FROM {$this->wpdb->prefix}edubot_attribution_sessions 
            WHERE enquiry_id = %d
        ", $enquiry_id), ARRAY_A);
        
        if ($session) {
            $touchpoint = $this->wpdb->get_row($this->wpdb->prepare("
                SELECT campaign FROM {$this->wpdb->prefix}edubot_attribution_touchpoints 
                WHERE session_id = %s AND campaign != '' 
                ORDER BY timestamp DESC LIMIT 1
            ", $session['user_session_key']), ARRAY_A);
            
            return $touchpoint['campaign'] ?? null;
        }
        
        return null;
    }
    
    private function get_conversion_value($enquiry_id) {
        $conversion = $this->wpdb->get_row($this->wpdb->prepare("
            SELECT conversion_value FROM {$this->wpdb->prefix}edubot_conversions 
            WHERE enquiry_id = %d ORDER BY converted_at DESC LIMIT 1
        ", $enquiry_id), ARRAY_A);
        
        return floatval($conversion['conversion_value'] ?? 0);
    }
}

// Initialize the Advanced Attribution Manager
EduBot_Advanced_Attribution_Manager::getInstance();
