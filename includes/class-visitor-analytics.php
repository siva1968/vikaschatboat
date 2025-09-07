<?php

/**
 * EduBot Visitor Analytics Manager
 * Handles tracking of marketing parameters, visitor data, and return customers for 30 days
 */
class EduBot_Visitor_Analytics {

    private $table_name;
    private $visitor_table;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'edubot_visitor_analytics';
        $this->visitor_table = $wpdb->prefix . 'edubot_visitors';
        
        // Hook into WordPress init to start tracking
        add_action('init', array($this, 'init_tracking'));
        add_action('wp_footer', array($this, 'inject_tracking_script'));
        
        // Hook into chatbot interactions
        add_action('edubot_conversation_started', array($this, 'track_conversation_start'));
        add_action('edubot_application_submitted', array($this, 'track_application_conversion'));
    }

    /**
     * Initialize visitor tracking
     */
    public function init_tracking() {
        // Only track on frontend and chatbot pages
        if (is_admin() && !wp_doing_ajax()) {
            return;
        }

        $this->start_visitor_session();
        $this->capture_marketing_parameters();
        $this->track_page_view();
    }

    /**
     * Start or continue visitor session
     */
    private function start_visitor_session() {
        $visitor_id = $this->get_or_create_visitor_id();
        $session_id = $this->get_or_create_session_id();
        
        // Update last activity
        $this->update_visitor_activity($visitor_id, $session_id);
        
        // Set cookies for tracking (30 days)
        if (!headers_sent()) {
            setcookie('edubot_visitor_id', $visitor_id, time() + (30 * 24 * 60 * 60), '/');
            setcookie('edubot_session_id', $session_id, time() + (30 * 24 * 60 * 60), '/');
        }
    }

    /**
     * Get or create unique visitor ID
     */
    private function get_or_create_visitor_id() {
        // Check if visitor ID exists in cookie
        if (isset($_COOKIE['edubot_visitor_id'])) {
            return sanitize_text_field($_COOKIE['edubot_visitor_id']);
        }

        // Generate new visitor ID
        $visitor_id = 'visitor_' . wp_generate_password(16, false);
        
        // Store visitor data
        $this->store_visitor_data($visitor_id);
        
        return $visitor_id;
    }

    /**
     * Get or create session ID
     */
    private function get_or_create_session_id() {
        // Check if session ID exists and is recent (less than 30 minutes)
        if (isset($_COOKIE['edubot_session_id'])) {
            $session_id = sanitize_text_field($_COOKIE['edubot_session_id']);
            
            // Check if session is still active
            if ($this->is_session_active($session_id)) {
                return $session_id;
            }
        }

        // Generate new session ID
        return 'session_' . wp_generate_password(20, false);
    }

    /**
     * Check if session is still active (within 30 minutes)
     */
    private function is_session_active($session_id) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE session_id = %s 
             AND timestamp > DATE_SUB(NOW(), INTERVAL 30 MINUTE)",
            $session_id
        ));
        
        return $result > 0;
    }

    /**
     * Store visitor data
     */
    private function store_visitor_data($visitor_id) {
        global $wpdb;
        
        $visitor_data = array(
            'visitor_id' => $visitor_id,
            'site_id' => get_current_blog_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $this->get_user_agent(),
            'browser' => $this->get_browser_info(),
            'device_type' => $this->get_device_type(),
            'operating_system' => $this->get_operating_system(),
            'first_visit' => current_time('mysql'),
            'last_activity' => current_time('mysql'),
            'visit_count' => 1,
            'is_returning' => 0
        );

        // Check if visitor exists (by IP and User Agent fingerprint)
        $existing_visitor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->visitor_table} 
             WHERE ip_address = %s 
             AND user_agent = %s 
             AND site_id = %d 
             AND first_visit > DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $visitor_data['ip_address'],
            $visitor_data['user_agent'],
            $visitor_data['site_id']
        ));

        if ($existing_visitor) {
            // Update existing visitor
            $visitor_data['is_returning'] = 1;
            $visitor_data['visit_count'] = $existing_visitor->visit_count + 1;
            $visitor_data['first_visit'] = $existing_visitor->first_visit;
            
            $wpdb->update(
                $this->visitor_table,
                $visitor_data,
                array('id' => $existing_visitor->id),
                array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d'),
                array('%d')
            );
        } else {
            // Insert new visitor
            $wpdb->insert(
                $this->visitor_table,
                $visitor_data,
                array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d')
            );
        }
    }

    /**
     * Update visitor activity
     */
    private function update_visitor_activity($visitor_id, $session_id) {
        global $wpdb;
        
        $wpdb->update(
            $this->visitor_table,
            array('last_activity' => current_time('mysql')),
            array('visitor_id' => $visitor_id),
            array('%s'),
            array('%s')
        );
    }

    /**
     * Capture marketing parameters from URL - Enterprise Enhanced
     */
    private function capture_marketing_parameters() {
        $marketing_params = array();
        
        // Standard UTM parameters
        $utm_params = array(
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'
        );
        
        // Platform-specific click IDs
        $click_ids = array(
            'gclid',    // Google Ads
            'fbclid',   // Facebook Ads
            'msclkid',  // Microsoft Ads (Bing)
            'ttclid',   // TikTok Ads
            'li_fat_id', // LinkedIn Ads
            'twclid',   // Twitter Ads
            'igshid',   // Instagram
            'yclid',    // Yandex
            'wbraid',   // Google Ads enhanced conversions
            'gbraid'    // Google Ads privacy sandbox
        );
        
        // Custom parameters (configurable)
        $custom_params = array(
            'ref', 'referrer', 'source', 'medium', 'campaign', 'ad_group', 
            'keyword', 'placement', 'creative', 'target', 'adset',
            'affiliate', 'partner', 'promo', 'discount', 'coupon'
        );
        
        // Capture all parameter types
        $all_params = array_merge($utm_params, $click_ids, $custom_params);
        
        foreach ($all_params as $param) {
            if (isset($_GET[$param]) && !empty($_GET[$param])) {
                $marketing_params[$param] = sanitize_text_field($_GET[$param]);
            }
        }
        
        // Enhanced referrer analysis
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $referrer_url = esc_url_raw($_SERVER['HTTP_REFERER']);
            $referrer_domain = parse_url($referrer_url, PHP_URL_HOST);
            
            $marketing_params['http_referrer'] = $referrer_url;
            $marketing_params['referrer_domain'] = $referrer_domain;
            
            // Classify referrer source
            $marketing_params['referrer_type'] = $this->classify_referrer_source($referrer_domain);
        }
        
        // Add advanced browser fingerprinting
        $marketing_params['browser_fingerprint'] = $this->generate_browser_fingerprint();
        
        // Landing page context
        $marketing_params['landing_page'] = esc_url_raw($_SERVER['REQUEST_URI']);
        $marketing_params['landing_page_title'] = get_the_title();
        
        // Capture timestamp with timezone
        $marketing_params['capture_timestamp'] = current_time('mysql');
        $marketing_params['capture_timezone'] = get_option('timezone_string', 'UTC');
        
        // Store marketing parameters if any exist
        if (!empty($marketing_params)) {
            $this->store_marketing_data($marketing_params);
        }
    }

    /**
     * Store marketing data
     */
    private function store_marketing_data($marketing_params) {
        $visitor_id = isset($_COOKIE['edubot_visitor_id']) ? sanitize_text_field($_COOKIE['edubot_visitor_id']) : '';
        $session_id = isset($_COOKIE['edubot_session_id']) ? sanitize_text_field($_COOKIE['edubot_session_id']) : '';
        
        if (empty($visitor_id) || empty($session_id)) {
            return;
        }

        $this->log_analytics_event('marketing_params_captured', array(
            'marketing_data' => $marketing_params,
            'page_url' => esc_url_raw($_SERVER['REQUEST_URI']),
            'timestamp' => current_time('mysql')
        ), $visitor_id, $session_id);
    }

    /**
     * Track page view
     */
    private function track_page_view() {
        $visitor_id = isset($_COOKIE['edubot_visitor_id']) ? sanitize_text_field($_COOKIE['edubot_visitor_id']) : '';
        $session_id = isset($_COOKIE['edubot_session_id']) ? sanitize_text_field($_COOKIE['edubot_session_id']) : '';
        
        if (empty($visitor_id) || empty($session_id)) {
            return;
        }

        $page_data = array(
            'page_url' => esc_url_raw($_SERVER['REQUEST_URI']),
            'page_title' => get_the_title(),
            'timestamp' => current_time('mysql')
        );

        $this->log_analytics_event('page_view', $page_data, $visitor_id, $session_id);
    }

    /**
     * Track conversation start
     */
    public function track_conversation_start($data) {
        $visitor_id = isset($_COOKIE['edubot_visitor_id']) ? sanitize_text_field($_COOKIE['edubot_visitor_id']) : '';
        $session_id = isset($_COOKIE['edubot_session_id']) ? sanitize_text_field($_COOKIE['edubot_session_id']) : '';
        
        if (empty($visitor_id) || empty($session_id)) {
            return;
        }

        $this->log_analytics_event('conversation_started', array(
            'chatbot_session_id' => isset($data['session_id']) ? $data['session_id'] : '',
            'initial_message' => isset($data['message']) ? sanitize_text_field($data['message']) : '',
            'timestamp' => current_time('mysql')
        ), $visitor_id, $session_id);
    }

    /**
     * Track application conversion
     */
    public function track_application_conversion($data) {
        $visitor_id = isset($_COOKIE['edubot_visitor_id']) ? sanitize_text_field($_COOKIE['edubot_visitor_id']) : '';
        $session_id = isset($_COOKIE['edubot_session_id']) ? sanitize_text_field($_COOKIE['edubot_session_id']) : '';
        
        if (empty($visitor_id) || empty($session_id)) {
            return;
        }

        $this->log_analytics_event('application_submitted', array(
            'application_id' => isset($data['application_id']) ? $data['application_id'] : '',
            'student_grade' => isset($data['grade']) ? sanitize_text_field($data['grade']) : '',
            'conversion_time' => $this->calculate_conversion_time($session_id),
            'timestamp' => current_time('mysql')
        ), $visitor_id, $session_id);
    }

    /**
     * Calculate conversion time for session
     */
    private function calculate_conversion_time($session_id) {
        global $wpdb;
        
        $first_interaction = $wpdb->get_var($wpdb->prepare(
            "SELECT MIN(timestamp) FROM {$this->table_name} 
             WHERE session_id = %s",
            $session_id
        ));
        
        if ($first_interaction) {
            $start_time = strtotime($first_interaction);
            $current_time = time();
            return round(($current_time - $start_time) / 60, 2); // Return minutes
        }
        
        return 0;
    }

    /**
     * Log analytics event
     */
    private function log_analytics_event($event_type, $event_data, $visitor_id, $session_id) {
        global $wpdb;
        
        $data = array(
            'site_id' => get_current_blog_id(),
            'visitor_id' => $visitor_id,
            'session_id' => $session_id,
            'event_type' => $event_type,
            'event_data' => wp_json_encode($event_data),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $this->get_user_agent(),
            'timestamp' => current_time('mysql')
        );

        $wpdb->insert(
            $this->table_name,
            $data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Inject tracking script for frontend interactions
     */
    public function inject_tracking_script() {
        if (is_admin()) {
            return;
        }
        ?>
        <script>
        (function() {
            // Track time on page
            var startTime = Date.now();
            var visitorId = '<?php echo isset($_COOKIE['edubot_visitor_id']) ? esc_js($_COOKIE['edubot_visitor_id']) : ''; ?>';
            var sessionId = '<?php echo isset($_COOKIE['edubot_session_id']) ? esc_js($_COOKIE['edubot_session_id']) : ''; ?>';
            
            // Track page unload
            window.addEventListener('beforeunload', function() {
                if (visitorId && sessionId) {
                    var timeOnPage = Math.round((Date.now() - startTime) / 1000);
                    
                    // Send beacon for tracking
                    if (navigator.sendBeacon) {
                        var formData = new FormData();
                        formData.append('action', 'edubot_track_time_on_page');
                        formData.append('visitor_id', visitorId);
                        formData.append('session_id', sessionId);
                        formData.append('time_on_page', timeOnPage);
                        formData.append('page_url', window.location.pathname);
                        
                        navigator.sendBeacon('<?php echo admin_url('admin-ajax.php'); ?>', formData);
                    }
                }
            });
            
            // Track scroll depth
            var maxScroll = 0;
            window.addEventListener('scroll', function() {
                var scrollPercent = Math.round((window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100);
                if (scrollPercent > maxScroll) {
                    maxScroll = scrollPercent;
                }
            });
            
            // Track clicks on chatbot elements
            document.addEventListener('click', function(e) {
                if (e.target.closest('.edubot-chat-widget') || e.target.closest('[class*="edubot"]')) {
                    if (visitorId && sessionId) {
                        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                            action: 'edubot_track_chatbot_interaction',
                            visitor_id: visitorId,
                            session_id: sessionId,
                            interaction_type: 'click',
                            element: e.target.tagName.toLowerCase(),
                            timestamp: new Date().toISOString()
                        });
                    }
                }
            });
        })();
        </script>
        <?php
    }

    /**
     * Get visitor analytics data
     */
    public function get_visitor_analytics($date_range = 30) {
        global $wpdb;
        
        $date_from = date('Y-m-d', strtotime("-{$date_range} days"));
        $site_id = get_current_blog_id();

        $analytics = array();

        // Total visitors
        $analytics['total_visitors'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT visitor_id) FROM {$this->visitor_table} 
             WHERE site_id = %d AND first_visit >= %s",
            $site_id, $date_from
        ));

        // New vs returning visitors
        $visitor_types = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                CASE WHEN is_returning = 1 THEN 'returning' ELSE 'new' END as visitor_type,
                COUNT(DISTINCT visitor_id) as count
             FROM {$this->visitor_table} 
             WHERE site_id = %d AND last_activity >= %s
             GROUP BY is_returning",
            $site_id, $date_from
        ), ARRAY_A);

        $analytics['visitor_types'] = $visitor_types;

        // Top traffic sources
        $analytics['traffic_sources'] = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.marketing_data.utm_source')) as source,
                COUNT(*) as count
             FROM {$this->table_name} 
             WHERE site_id = %d 
             AND event_type = 'marketing_params_captured'
             AND timestamp >= %s
             AND JSON_EXTRACT(event_data, '$.marketing_data.utm_source') IS NOT NULL
             GROUP BY source
             ORDER BY count DESC
             LIMIT 10",
            $site_id, $date_from
        ), ARRAY_A);

        // Conversion funnel
        $analytics['conversion_funnel'] = array(
            'visitors' => $analytics['total_visitors'],
            'conversation_starts' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM {$this->table_name} 
                 WHERE site_id = %d AND event_type = 'conversation_started' AND timestamp >= %s",
                $site_id, $date_from
            )),
            'applications' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM {$this->table_name} 
                 WHERE site_id = %d AND event_type = 'application_submitted' AND timestamp >= %s",
                $site_id, $date_from
            ))
        );

        // Calculate conversion rates
        if ($analytics['conversion_funnel']['visitors'] > 0) {
            $analytics['conversation_rate'] = round(($analytics['conversion_funnel']['conversation_starts'] / $analytics['conversion_funnel']['visitors']) * 100, 2);
            $analytics['application_rate'] = round(($analytics['conversion_funnel']['applications'] / $analytics['conversion_funnel']['visitors']) * 100, 2);
        } else {
            $analytics['conversation_rate'] = 0;
            $analytics['application_rate'] = 0;
        }

        return $analytics;
    }

    /**
     * Get device and browser information
     */
    private function get_browser_info() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (strpos($user_agent, 'Chrome') !== false) return 'Chrome';
        if (strpos($user_agent, 'Firefox') !== false) return 'Firefox';
        if (strpos($user_agent, 'Safari') !== false) return 'Safari';
        if (strpos($user_agent, 'Edge') !== false) return 'Edge';
        if (strpos($user_agent, 'Opera') !== false) return 'Opera';
        
        return 'Other';
    }

    private function get_device_type() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/Mobile|Android|iPhone|iPad/', $user_agent)) {
            if (preg_match('/iPad/', $user_agent)) return 'Tablet';
            return 'Mobile';
        }
        
        return 'Desktop';
    }

    private function get_operating_system() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (strpos($user_agent, 'Windows') !== false) return 'Windows';
        if (strpos($user_agent, 'Mac') !== false) return 'macOS';
        if (strpos($user_agent, 'Linux') !== false) return 'Linux';
        if (strpos($user_agent, 'Android') !== false) return 'Android';
        if (strpos($user_agent, 'iOS') !== false) return 'iOS';
        
        return 'Other';
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

    /**
     * Get user agent
     */
    private function get_user_agent() {
        return sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
    }

    /**
     * Clean up old analytics data (run daily) - Static method for cron
     */
    public static function cleanup_old_analytics_static() {
        global $wpdb;
        
        $visitor_analytics_table = $wpdb->prefix . 'edubot_visitor_analytics';
        $visitors_table = $wpdb->prefix . 'edubot_visitors';
        
        // Delete analytics data older than 30 days
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$visitor_analytics_table} WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        ));
        
        // Delete visitor data older than 30 days
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$visitors_table} WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        ));
    }

    /**
     * Clean up old analytics data (instance method)
     */
    public function cleanup_old_analytics() {
        self::cleanup_old_analytics_static();
    }
    
    /**
     * Get conversion attribution summary for dashboard
     */
    public function get_conversion_attribution_summary($date_range = 30) {
        global $wpdb;
        
        $date_from = date('Y-m-d', strtotime("-{$date_range} days"));
        $site_id = get_current_blog_id();
        
        // Get total conversions with attribution
        $total_conversions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE site_id = %d AND event_type = 'application_converted' AND timestamp >= %s",
            $site_id, $date_from
        ));
        
        // Get conversions by source (first-touch attribution)
        $conversions_by_source = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.first_touch_source')) as source,
                COUNT(*) as conversions,
                AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.conversion_time_minutes')) AS UNSIGNED)) as avg_time
             FROM {$this->table_name}
             WHERE site_id = %d AND event_type = 'application_converted' AND timestamp >= %s
             GROUP BY source
             ORDER BY conversions DESC",
            $site_id, $date_from
        ), ARRAY_A);
        
        // Get attribution model comparison (first vs last touch)
        $attribution_comparison = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.first_touch_source')) as first_touch,
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.last_touch_source')) as last_touch,
                COUNT(*) as count
             FROM {$this->table_name}
             WHERE site_id = %d AND event_type = 'application_converted' AND timestamp >= %s
             GROUP BY first_touch, last_touch
             ORDER BY count DESC
             LIMIT 10",
            $site_id, $date_from
        ), ARRAY_A);
        
        return array(
            'total_conversions' => $total_conversions,
            'conversions_by_source' => $conversions_by_source,
            'attribution_comparison' => $attribution_comparison,
            'date_range' => $date_range
        );
    }
    
    /**
     * Classify referrer source type
     */
    private function classify_referrer_source($domain) {
        if (empty($domain)) return 'direct';
        
        // Search engines
        $search_engines = ['google.', 'bing.', 'yahoo.', 'duckduckgo.', 'yandex.', 'baidu.'];
        foreach ($search_engines as $engine) {
            if (strpos($domain, $engine) !== false) return 'search';
        }
        
        // Social media platforms
        $social_platforms = [
            'facebook.', 'instagram.', 'twitter.', 'linkedin.', 'tiktok.', 
            'youtube.', 'snapchat.', 'pinterest.', 'reddit.', 'whatsapp.'
        ];
        foreach ($social_platforms as $platform) {
            if (strpos($domain, $platform) !== false) return 'social';
        }
        
        // Email platforms
        $email_platforms = ['gmail.', 'outlook.', 'yahoo.', 'mail.'];
        foreach ($email_platforms as $platform) {
            if (strpos($domain, $platform) !== false) return 'email';
        }
        
        return 'referral';
    }
    
    /**
     * Generate advanced browser fingerprint
     */
    private function generate_browser_fingerprint() {
        $fingerprint_data = array(
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
            'ip_address' => $this->get_client_ip(),
            'date' => date('Y-m-d') // Same day grouping
        );
        
        return md5(serialize($fingerprint_data));
    }
    
    /**
     * Store attribution data with business contact linking
     */
    public function link_attribution_to_contact($phone = '', $email = '') {
        $visitor_id = isset($_COOKIE['edubot_visitor_id']) ? sanitize_text_field($_COOKIE['edubot_visitor_id']) : '';
        
        if (empty($visitor_id)) return;
        
        global $wpdb;
        
        // Update visitor record with business contact information
        $update_data = array();
        if (!empty($phone)) $update_data['phone'] = sanitize_text_field($phone);
        if (!empty($email)) $update_data['email'] = sanitize_email($email);
        
        if (!empty($update_data)) {
            $wpdb->update(
                $this->visitor_table,
                $update_data,
                array('visitor_id' => $visitor_id),
                array('%s'),
                array('%s')
            );
            
            // Log the contact linking event
            $session_id = isset($_COOKIE['edubot_session_id']) ? sanitize_text_field($_COOKIE['edubot_session_id']) : '';
            $this->log_analytics_event('contact_linked', array(
                'phone' => $phone,
                'email' => $email,
                'linked_timestamp' => current_time('mysql')
            ), $visitor_id, $session_id);
        }
    }
    
    /**
     * Get attribution data for a contact (phone or email)
     */
    public function get_contact_attribution($phone = '', $email = '') {
        global $wpdb;
        
        $where_clause = array();
        $where_values = array();
        
        if (!empty($phone)) {
            $where_clause[] = "phone = %s";
            $where_values[] = sanitize_text_field($phone);
        }
        
        if (!empty($email)) {
            $where_clause[] = "email = %s";  
            $where_values[] = sanitize_email($email);
        }
        
        if (empty($where_clause)) return null;
        
        $query = "SELECT v.*, va.event_data as marketing_data 
                  FROM {$this->visitor_table} v
                  LEFT JOIN {$this->table_name} va ON v.visitor_id = va.visitor_id 
                  WHERE (" . implode(' OR ', $where_clause) . ")
                  AND va.event_type = 'marketing_params_captured'
                  ORDER BY v.first_visit ASC, va.timestamp ASC";
        
        return $wpdb->get_results($wpdb->prepare($query, $where_values));
    }
    
    /**
     * Enhanced application conversion tracking with full attribution
     */
    public function track_enhanced_application_conversion($application_data) {
        $visitor_id = isset($_COOKIE['edubot_visitor_id']) ? sanitize_text_field($_COOKIE['edubot_visitor_id']) : '';
        $session_id = isset($_COOKIE['edubot_session_id']) ? sanitize_text_field($_COOKIE['edubot_session_id']) : '';
        
        if (empty($visitor_id)) return;
        
        // Link attribution to contact information
        $phone = isset($application_data['phone']) ? $application_data['phone'] : '';
        $email = isset($application_data['email']) ? $application_data['email'] : '';
        
        if (!empty($phone) || !empty($email)) {
            $this->link_attribution_to_contact($phone, $email);
        }
        
        // Get full attribution journey
        $attribution_journey = $this->get_visitor_journey($visitor_id);
        
        // Track the conversion with complete attribution data
        $this->log_analytics_event('application_converted', array(
            'application_id' => $application_data['application_id'] ?? '',
            'application_number' => $application_data['application_number'] ?? '',
            'student_name' => $application_data['student_name'] ?? '',
            'grade' => $application_data['grade'] ?? '',
            'phone' => $phone,
            'email' => $email,
            'conversion_value' => 1, // Each application has value of 1
            'conversion_time_minutes' => $this->calculate_conversion_time($session_id),
            'attribution_journey' => json_encode($attribution_journey),
            'first_touch_source' => $attribution_journey['first_touch']['utm_source'] ?? 'direct',
            'last_touch_source' => $attribution_journey['last_touch']['utm_source'] ?? 'direct',
            'conversion_timestamp' => current_time('mysql')
        ), $visitor_id, $session_id);
        
        error_log('EduBot Attribution: Application converted with full tracking - ' . 
                 ($application_data['application_number'] ?? 'Unknown'));
    }
    
    /**
     * Get complete visitor journey for attribution analysis
     */
    private function get_visitor_journey($visitor_id) {
        global $wpdb;
        
        // Get all marketing touchpoints for this visitor
        $touchpoints = $wpdb->get_results($wpdb->prepare(
            "SELECT event_data, timestamp FROM {$this->table_name} 
             WHERE visitor_id = %s 
             AND event_type = 'marketing_params_captured'
             ORDER BY timestamp ASC",
            $visitor_id
        ));
        
        $journey = array(
            'first_touch' => null,
            'last_touch' => null,
            'all_touchpoints' => array(),
            'total_touchpoints' => 0
        );
        
        if (!empty($touchpoints)) {
            $journey['total_touchpoints'] = count($touchpoints);
            
            foreach ($touchpoints as $touchpoint) {
                $data = json_decode($touchpoint->event_data, true);
                $marketing_data = isset($data['marketing_data']) ? $data['marketing_data'] : array();
                
                $journey['all_touchpoints'][] = array(
                    'timestamp' => $touchpoint->timestamp,
                    'marketing_data' => $marketing_data
                );
            }
            
            // Set first and last touch
            $journey['first_touch'] = $journey['all_touchpoints'][0]['marketing_data'] ?? array();
            $journey['last_touch'] = end($journey['all_touchpoints'])['marketing_data'] ?? array();
        }
        
        return $journey;
    }
}

// Initialize the visitor analytics system
new EduBot_Visitor_Analytics();
