<?php
/**
 * Attribution Tracker Class
 * 
 * Manages multi-touch attribution tracking for enquiries.
 * Records touchpoints, reconstructs customer journeys, and calculates
 * channel credit based on different attribution models.
 * 
 * @since 1.3.3
 * @package EduBot_Pro
 * @subpackage Analytics
 */

class EduBot_Attribution_Tracker {
    
    /**
     * Database table name for attribution sessions
     * 
     * @var string
     */
    private $sessions_table;
    
    /**
     * Database table name for attribution touchpoints
     * 
     * @var string
     */
    private $touchpoints_table;
    
    /**
     * Database table name for attribution journeys
     * 
     * @var string
     */
    private $journeys_table;
    
    /**
     * WordPress database instance
     * 
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * EduBot logger instance
     * 
     * @var EduBot_Logger
     */
    private $logger;
    
    /**
     * Constructor
     * 
     * @param EduBot_Logger $logger Logger instance
     */
    public function __construct($logger = null) {
        global $wpdb;
        
        $this->wpdb = $wpdb;
        $this->sessions_table = $wpdb->prefix . 'edubot_attribution_sessions';
        $this->touchpoints_table = $wpdb->prefix . 'edubot_attribution_touchpoints';
        $this->journeys_table = $wpdb->prefix . 'edubot_attribution_journeys';
        
        if ($logger instanceof EduBot_Logger) {
            $this->logger = $logger;
        } else {
            $this->logger = new EduBot_Logger();
        }
    }
    
    /**
     * Initialize attribution tracking for a new session
     * 
     * @param int $enquiry_id Enquiry ID
     * @param array $utm_data Initial UTM/tracking data
     * 
     * @return int|false Session ID or false on failure
     */
    public function initialize_session($enquiry_id, $utm_data = []) {
        
        if (!$enquiry_id) {
            $this->logger->log('Attribution session initialization failed: Invalid enquiry ID', 'error');
            return false;
        }
        
        $session_key = $this->generate_session_key();
        
        // Extract first-touch source
        $first_touch_source = isset($utm_data['utm_source']) 
            ? sanitize_text_field($utm_data['utm_source']) 
            : 'direct';
        
        // Check if session already exists for this enquiry
        $existing = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT session_id FROM $this->sessions_table WHERE enquiry_id = %d",
                $enquiry_id
            )
        );
        
        if ($existing) {
            return $existing;
        }
        
        // Create new session
        $result = $this->wpdb->insert(
            $this->sessions_table,
            [
                'enquiry_id' => $enquiry_id,
                'user_session_key' => $session_key,
                'first_touch_source' => $first_touch_source,
                'first_touch_timestamp' => current_time('mysql'),
                'last_touch_source' => $first_touch_source,
                'last_touch_timestamp' => current_time('mysql'),
                'total_touchpoints' => 1
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%d']
        );
        
        if (!$result) {
            $this->logger->log(
                'Attribution session creation failed: ' . $this->wpdb->last_error,
                'error'
            );
            return false;
        }
        
        $session_id = $this->wpdb->insert_id;
        
        $this->logger->log(
            sprintf('Attribution session initialized: ID=%d, Enquiry=%d', $session_id, $enquiry_id),
            'info'
        );
        
        // Record first touchpoint
        $this->record_touchpoint($session_id, $enquiry_id, $utm_data);
        
        return $session_id;
    }
    
    /**
     * Record a new touchpoint in the customer journey
     * 
     * @param int $session_id Session ID
     * @param int $enquiry_id Enquiry ID
     * @param array $utm_data UTM and tracking data
     * 
     * @return int|false Touchpoint ID or false on failure
     */
    public function record_touchpoint($session_id, $enquiry_id, $utm_data = []) {
        
        if (!$session_id || !$enquiry_id) {
            return false;
        }
        
        // Get current touchpoint position
        $position = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM $this->touchpoints_table WHERE session_id = %d",
                $session_id
            )
        ) + 1;
        
        // Extract touchpoint data
        $touchpoint_data = [
            'session_id' => $session_id,
            'enquiry_id' => $enquiry_id,
            'source' => isset($utm_data['utm_source']) ? sanitize_text_field($utm_data['utm_source']) : 'direct',
            'medium' => isset($utm_data['utm_medium']) ? sanitize_text_field($utm_data['utm_medium']) : 'organic',
            'campaign' => isset($utm_data['utm_campaign']) ? sanitize_text_field($utm_data['utm_campaign']) : '',
            'platform_click_id' => $this->extract_platform_click_id($utm_data),
            'timestamp' => current_time('mysql'),
            'position_in_journey' => $position,
            'page_title' => isset($_SERVER['HTTP_REFERER']) ? wp_title('') : '',
            'page_url' => isset($_SERVER['REQUEST_URI']) ? sanitize_url($_SERVER['REQUEST_URI']) : '',
            'referrer' => isset($_SERVER['HTTP_REFERER']) ? sanitize_url($_SERVER['HTTP_REFERER']) : '',
            'device_type' => $this->get_device_type(),
            'attribution_weight' => 100.00
        ];
        
        // Insert touchpoint record
        $result = $this->wpdb->insert(
            $this->touchpoints_table,
            $touchpoint_data,
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%f']
        );
        
        if (!$result) {
            $this->logger->log(
                'Touchpoint recording failed: ' . $this->wpdb->last_error,
                'error'
            );
            return false;
        }
        
        $touchpoint_id = $this->wpdb->insert_id;
        
        // Update session stats
        $this->update_session_stats($session_id, $utm_data);
        
        return $touchpoint_id;
    }
    
    /**
     * Update session statistics after recording touchpoint
     * 
     * @param int $session_id Session ID
     * @param array $utm_data Latest UTM data
     * 
     * @return bool True if successful
     */
    private function update_session_stats($session_id, $utm_data = []) {
        
        $last_touch_source = isset($utm_data['utm_source']) 
            ? sanitize_text_field($utm_data['utm_source']) 
            : 'direct';
        
        $touchpoint_count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM $this->touchpoints_table WHERE session_id = %d",
                $session_id
            )
        );
        
        $result = $this->wpdb->update(
            $this->sessions_table,
            [
                'last_touch_source' => $last_touch_source,
                'last_touch_timestamp' => current_time('mysql'),
                'total_touchpoints' => $touchpoint_count,
                'updated_at' => current_time('mysql')
            ],
            ['session_id' => $session_id],
            ['%s', '%s', '%d', '%s'],
            ['%d']
        );
        
        return (bool)$result;
    }
    
    /**
     * Calculate attribution for an enquiry using specified model
     * 
     * @param int $enquiry_id Enquiry ID
     * @param string $model Attribution model: 'first-touch', 'last-touch', 'linear', 'time-decay'
     * 
     * @return array|false Attribution data or false on failure
     */
    public function calculate_attribution($enquiry_id, $model = 'last-touch') {
        
        if (!$enquiry_id) {
            return false;
        }
        
        // Validate model
        $valid_models = ['first-touch', 'last-touch', 'linear', 'time-decay', 'u-shaped'];
        if (!in_array($model, $valid_models, true)) {
            $model = 'last-touch';
        }
        
        // Get session and touchpoints
        $session = $this->get_session($enquiry_id);
        if (!$session) {
            return false;
        }
        
        $touchpoints = $this->get_touchpoints($session->session_id);
        if (empty($touchpoints)) {
            return false;
        }
        
        // Calculate weights based on model
        $weighted_touchpoints = $this->apply_attribution_model(
            $touchpoints,
            $model
        );
        
        // Build journey path
        $journey_path = implode(
            ' > ',
            wp_list_pluck($touchpoints, 'source')
        );
        
        // Calculate total journey time
        $first_time = strtotime($touchpoints[0]->timestamp);
        $last_time = strtotime($touchpoints[count($touchpoints) - 1]->timestamp);
        $total_minutes = round(($last_time - $first_time) / 60);
        
        // Update touchpoint weights
        foreach ($weighted_touchpoints as $touchpoint) {
            $this->wpdb->update(
                $this->touchpoints_table,
                ['attribution_weight' => $touchpoint['weight']],
                ['touchpoint_id' => $touchpoint['id']],
                ['%f'],
                ['%d']
            );
        }
        
        // Store attribution journey
        $journey_data = [
            'enquiry_id' => $enquiry_id,
            'journey_path' => $journey_path,
            'journey_length' => count($touchpoints),
            'total_time_minutes' => max(0, $total_minutes),
            'first_touch_source' => $touchpoints[0]->source,
            'last_touch_source' => $touchpoints[count($touchpoints) - 1]->source,
            'attribution_model' => $model,
            'calculated_at' => current_time('mysql')
        ];
        
        // Insert or update journey
        $journey_id = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT journey_id FROM $this->journeys_table WHERE enquiry_id = %d",
                $enquiry_id
            )
        );
        
        if ($journey_id) {
            $this->wpdb->update(
                $this->journeys_table,
                $journey_data,
                ['journey_id' => $journey_id]
            );
        } else {
            $this->wpdb->insert(
                $this->journeys_table,
                $journey_data
            );
        }
        
        // Update session model
        $this->wpdb->update(
            $this->sessions_table,
            ['attribution_model' => $model],
            ['session_id' => $session->session_id]
        );
        
        return $journey_data;
    }
    
    /**
     * Apply attribution model to touchpoints
     * 
     * @param array $touchpoints Array of touchpoint objects
     * @param string $model Attribution model
     * 
     * @return array Touchpoints with assigned weights
     */
    private function apply_attribution_model($touchpoints, $model) {
        
        $count = count($touchpoints);
        $weighted = [];
        
        switch ($model) {
            case 'first-touch':
                // 100% to first touchpoint
                foreach ($touchpoints as $index => $touchpoint) {
                    $weight = ($index === 0) ? 100.0 : 0.0;
                    $weighted[] = [
                        'id' => $touchpoint->touchpoint_id,
                        'weight' => $weight
                    ];
                }
                break;
                
            case 'last-touch':
                // 100% to last touchpoint
                foreach ($touchpoints as $index => $touchpoint) {
                    $weight = ($index === $count - 1) ? 100.0 : 0.0;
                    $weighted[] = [
                        'id' => $touchpoint->touchpoint_id,
                        'weight' => $weight
                    ];
                }
                break;
                
            case 'linear':
                // Equal weight to all
                $weight = 100.0 / $count;
                foreach ($touchpoints as $touchpoint) {
                    $weighted[] = [
                        'id' => $touchpoint->touchpoint_id,
                        'weight' => $weight
                    ];
                }
                break;
                
            case 'time-decay':
                // More weight to recent touchpoints
                for ($i = 0; $i < $count; $i++) {
                    // Exponential weighting: later touchpoints get exponentially more weight
                    $weight = (($i + 1) / $count) * 100.0;
                    $weighted[] = [
                        'id' => $touchpoints[$i]->touchpoint_id,
                        'weight' => $weight
                    ];
                }
                // Normalize to sum to 100
                $total = array_sum(wp_list_pluck($weighted, 'weight'));
                foreach ($weighted as &$item) {
                    $item['weight'] = ($item['weight'] / $total) * 100;
                }
                unset($item);
                break;
                
            case 'u-shaped':
                // 40% first, 40% last, 20% middle
                foreach ($touchpoints as $index => $touchpoint) {
                    if ($index === 0) {
                        $weight = 40.0;
                    } elseif ($index === $count - 1) {
                        $weight = 40.0;
                    } else {
                        $weight = 20.0 / max(1, $count - 2);
                    }
                    $weighted[] = [
                        'id' => $touchpoint->touchpoint_id,
                        'weight' => $weight
                    ];
                }
                // Normalize to sum to 100
                $total = array_sum(wp_list_pluck($weighted, 'weight'));
                foreach ($weighted as &$item) {
                    $item['weight'] = ($item['weight'] / $total) * 100;
                }
                unset($item);
                break;
        }
        
        return $weighted;
    }
    
    /**
     * Get attribution session for an enquiry
     * 
     * @param int $enquiry_id Enquiry ID
     * 
     * @return object|null Session object or null if not found
     */
    public function get_session($enquiry_id) {
        
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $this->sessions_table WHERE enquiry_id = %d",
                $enquiry_id
            )
        );
    }
    
    /**
     * Get all touchpoints for a session
     * 
     * @param int $session_id Session ID
     * 
     * @return array Array of touchpoint objects
     */
    public function get_touchpoints($session_id) {
        
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM $this->touchpoints_table 
                WHERE session_id = %d 
                ORDER BY position_in_journey ASC",
                $session_id
            )
        );
    }
    
    /**
     * Get full attribution journey for an enquiry
     * 
     * @param int $enquiry_id Enquiry ID
     * 
     * @return array|null Journey data or null if not found
     */
    public function get_journey($enquiry_id) {
        
        $journey = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $this->journeys_table WHERE enquiry_id = %d",
                $enquiry_id
            ),
            ARRAY_A
        );
        
        if (!$journey) {
            return null;
        }
        
        // Parse journey path into array
        $journey['journey_path_array'] = array_map(
            'trim',
            explode('>', $journey['journey_path'])
        );
        
        return $journey;
    }
    
    /**
     * Get channel credit breakdown
     * 
     * @param string $channel Channel/source name (e.g., 'facebook', 'google')
     * @param string $model Attribution model
     * @param string $date_from Start date (YYYY-MM-DD)
     * @param string $date_to End date (YYYY-MM-DD)
     * 
     * @return array Credit breakdown data
     */
    public function get_channel_credit($channel = null, $model = 'last-touch', $date_from = null, $date_to = null) {
        
        $where_parts = ['attribution_model = %s'];
        $where_values = [$model];
        
        if ($channel) {
            $where_parts[] = 'FIND_IN_SET(%s, journey_path)';
            $where_values[] = sanitize_text_field($channel);
        }
        
        if ($date_from) {
            $where_parts[] = 'calculated_at >= %s';
            $where_values[] = sanitize_text_field($date_from);
        }
        
        if ($date_to) {
            $where_parts[] = 'calculated_at <= %s';
            $where_values[] = sanitize_text_field($date_to);
        }
        
        $where_clause = implode(' AND ', $where_parts);
        
        $query = $this->wpdb->prepare(
            "SELECT 
                journey_path,
                COUNT(*) as total_enquiries,
                SUM(CASE WHEN last_touch_source = %s THEN 1 ELSE 0 END) as attributed_enquiries
            FROM $this->journeys_table
            WHERE $where_clause
            GROUP BY journey_path",
            array_merge([$channel ? $channel : 'direct'], $where_values)
        );
        
        return $this->wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Extract platform click ID from UTM data
     * 
     * @param array $utm_data UTM data array
     * 
     * @return string Click ID or empty string
     */
    private function extract_platform_click_id($utm_data) {
        
        $click_id_params = [
            'gclid',
            'fbclid',
            'msclkid',
            'ttclid',
            'li_fat_id',
            'twclid',
            'igshid',
            'yclid',
            'wbraid',
            'gbraid'
        ];
        
        foreach ($click_id_params as $param) {
            if (isset($utm_data[$param]) && !empty($utm_data[$param])) {
                return sanitize_text_field($utm_data[$param]);
            }
        }
        
        return '';
    }
    
    /**
     * Generate unique session key
     * 
     * @return string Session key
     */
    private function generate_session_key() {
        return 'attr_' . bin2hex(random_bytes(16)) . '_' . time();
    }
    
    /**
     * Get device type
     * 
     * @return string Device type (mobile, tablet, desktop)
     */
    private function get_device_type() {
        
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return 'unknown';
        }
        
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        
        if (preg_match('/mobile|android|iphone|ipod/', $user_agent)) {
            return 'mobile';
        } elseif (preg_match('/tablet|ipad/', $user_agent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
    
    /**
     * Clean up old attribution data
     * 
     * @param int $days_to_keep Number of days to keep (default: 90)
     * 
     * @return int Number of records deleted
     */
    public function cleanup_old_data($days_to_keep = 90) {
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days_to_keep days"));
        
        // Get sessions to delete
        $old_sessions = $this->wpdb->get_col(
            $this->wpdb->prepare(
                "SELECT session_id FROM $this->sessions_table WHERE created_at < %s",
                $cutoff_date
            )
        );
        
        if (empty($old_sessions)) {
            return 0;
        }
        
        $session_ids = implode(',', array_map('intval', $old_sessions));
        
        // Delete touchpoints
        $this->wpdb->query(
            "DELETE FROM $this->touchpoints_table WHERE session_id IN ($session_ids)"
        );
        
        // Delete journeys
        $journeys_deleted = $this->wpdb->get_col(
            "SELECT enquiry_id FROM $this->journeys_table WHERE enquiry_id IN 
            (SELECT DISTINCT enquiry_id FROM $this->sessions_table WHERE session_id IN ($session_ids))"
        );
        
        if (!empty($journeys_deleted)) {
            $journey_ids = implode(',', array_map('intval', $journeys_deleted));
            $this->wpdb->query(
                "DELETE FROM $this->journeys_table WHERE enquiry_id IN ($journey_ids)"
            );
        }
        
        // Delete sessions
        $deleted = $this->wpdb->query(
            "DELETE FROM $this->sessions_table WHERE session_id IN ($session_ids)"
        );
        
        $this->logger->log(
            sprintf('Cleaned up %d old attribution records older than %s', $deleted, $cutoff_date),
            'info'
        );
        
        return $deleted;
    }
}
?>
