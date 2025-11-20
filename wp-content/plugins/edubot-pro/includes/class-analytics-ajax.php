<?php

/**
 * EduBot Visitor Analytics AJAX Handlers
 * Handles AJAX requests for visitor tracking
 */
class EduBot_Analytics_AJAX {

    public function __construct() {
        // AJAX handlers for both logged in and non-logged in users
        add_action('wp_ajax_edubot_track_time_on_page', array($this, 'track_time_on_page'));
        add_action('wp_ajax_nopriv_edubot_track_time_on_page', array($this, 'track_time_on_page'));
        
        add_action('wp_ajax_edubot_track_chatbot_interaction', array($this, 'track_chatbot_interaction'));
        add_action('wp_ajax_nopriv_edubot_track_chatbot_interaction', array($this, 'track_chatbot_interaction'));
        
        add_action('wp_ajax_edubot_track_scroll_depth', array($this, 'track_scroll_depth'));
        add_action('wp_ajax_nopriv_edubot_track_scroll_depth', array($this, 'track_scroll_depth'));
        
        add_action('wp_ajax_edubot_get_visitor_analytics', array($this, 'get_visitor_analytics'));
    }

    /**
     * Track time spent on page
     */
    public function track_time_on_page() {
        $visitor_id = sanitize_text_field($_POST['visitor_id'] ?? '');
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $time_on_page = intval($_POST['time_on_page'] ?? 0);
        $page_url = esc_url_raw($_POST['page_url'] ?? '');

        if (empty($visitor_id) || empty($session_id)) {
            wp_send_json_error('Missing required parameters');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'edubot_visitor_analytics';

        $event_data = array(
            'time_on_page' => $time_on_page,
            'page_url' => $page_url,
            'timestamp' => current_time('mysql')
        );

        $result = $wpdb->insert(
            $table_name,
            array(
                'site_id' => get_current_blog_id(),
                'visitor_id' => $visitor_id,
                'session_id' => $session_id,
                'event_type' => 'time_on_page',
                'event_data' => wp_json_encode($event_data),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result !== false) {
            wp_send_json_success('Time tracking recorded');
        } else {
            wp_send_json_error('Failed to record time tracking');
        }
    }

    /**
     * Track chatbot interactions
     */
    public function track_chatbot_interaction() {
        $visitor_id = sanitize_text_field($_POST['visitor_id'] ?? '');
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $interaction_type = sanitize_text_field($_POST['interaction_type'] ?? '');
        $element = sanitize_text_field($_POST['element'] ?? '');
        $message = sanitize_text_field($_POST['message'] ?? '');

        if (empty($visitor_id) || empty($session_id) || empty($interaction_type)) {
            wp_send_json_error('Missing required parameters');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'edubot_visitor_analytics';

        $event_data = array(
            'interaction_type' => $interaction_type,
            'element' => $element,
            'message' => $message,
            'timestamp' => current_time('mysql')
        );

        $result = $wpdb->insert(
            $table_name,
            array(
                'site_id' => get_current_blog_id(),
                'visitor_id' => $visitor_id,
                'session_id' => $session_id,
                'event_type' => 'chatbot_interaction',
                'event_data' => wp_json_encode($event_data),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result !== false) {
            wp_send_json_success('Interaction tracked');
        } else {
            wp_send_json_error('Failed to track interaction');
        }
    }

    /**
     * Track scroll depth
     */
    public function track_scroll_depth() {
        $visitor_id = sanitize_text_field($_POST['visitor_id'] ?? '');
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $scroll_depth = intval($_POST['scroll_depth'] ?? 0);
        $page_url = esc_url_raw($_POST['page_url'] ?? '');

        if (empty($visitor_id) || empty($session_id)) {
            wp_send_json_error('Missing required parameters');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'edubot_visitor_analytics';

        $event_data = array(
            'scroll_depth' => $scroll_depth,
            'page_url' => $page_url,
            'timestamp' => current_time('mysql')
        );

        $result = $wpdb->insert(
            $table_name,
            array(
                'site_id' => get_current_blog_id(),
                'visitor_id' => $visitor_id,
                'session_id' => $session_id,
                'event_type' => 'scroll_depth',
                'event_data' => wp_json_encode($event_data),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result !== false) {
            wp_send_json_success('Scroll depth tracked');
        } else {
            wp_send_json_error('Failed to track scroll depth');
        }
    }

    /**
     * Get visitor analytics data (admin only)
     */
    public function get_visitor_analytics() {
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $date_range = intval($_POST['date_range'] ?? 30);
        $analytics_class = new EduBot_Visitor_Analytics();
        $analytics_data = $analytics_class->get_visitor_analytics($date_range);

        wp_send_json_success($analytics_data);
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

// Initialize AJAX handlers
new EduBot_Analytics_AJAX();
