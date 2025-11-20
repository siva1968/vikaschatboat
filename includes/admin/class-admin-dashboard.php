<?php
/**
 * Admin Dashboard Class
 * 
 * Manages the WordPress admin dashboard for marketing analytics.
 * Provides statistics queries, data aggregation, caching, and dashboard
 * widget management.
 * 
 * @since 1.3.3
 * @package EduBot_Pro
 * @subpackage Admin
 */

class EduBot_Admin_Dashboard {
    
    /**
     * WordPress database instance
     * 
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * Enquiries table name
     * 
     * @var string
     */
    private $enquiries_table;
    
    /**
     * Cache key prefix
     * 
     * @var string
     */
    private $cache_prefix = 'edubot_dashboard_';
    
    /**
     * Cache duration in seconds (5 minutes)
     * 
     * @var int
     */
    private $cache_ttl = 300;
    
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
        $this->enquiries_table = $wpdb->prefix . 'edubot_enquiries';
        
        if ($logger instanceof EduBot_Logger) {
            $this->logger = $logger;
        } else {
            $this->logger = new EduBot_Logger();
        }
    }
    
    /**
     * Register dashboard widget
     */
    public function register_widget() {
        wp_add_dashboard_widget(
            'edubot_dashboard_widget',
            'EduBot Pro - Marketing Analytics',
            [$this, 'render_dashboard_widget']
        );
    }
    
    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        include(plugin_dir_path(__FILE__) . '../admin/templates/dashboard-widget.php');
    }
    
    /**
     * Get total enquiries for period
     * 
     * @param string $period Period: 'today', 'week', 'month', 'year'
     * 
     * @return int Total enquiries
     */
    public function get_total_enquiries($period = 'month') {
        
        $cache_key = $this->cache_prefix . 'total_' . $period;
        $cached = wp_cache_get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $date_filter = $this->get_date_filter($period);
        
        $query = "SELECT COUNT(*) as total FROM $this->enquiries_table 
                  WHERE created_at >= %s";
        
        $total = $this->wpdb->get_var(
            $this->wpdb->prepare($query, $date_filter)
        );
        
        wp_cache_set($cache_key, $total, '', $this->cache_ttl);
        
        return intval($total);
    }
    
    /**
     * Get enquiries comparison (current vs previous period)
     * 
     * @param string $period Period: 'today', 'week', 'month'
     * 
     * @return array Comparison data
     */
    public function get_enquiries_comparison($period = 'month') {
        
        $current = $this->get_total_enquiries($period);
        $previous = $this->get_previous_period_enquiries($period);
        
        if ($previous === 0) {
            $change_percent = 0;
            $change_text = 'New';
        } else {
            $change_percent = round((($current - $previous) / $previous) * 100, 1);
            $change_text = $change_percent > 0 ? '↑' : '↓';
        }
        
        return [
            'current' => $current,
            'previous' => $previous,
            'change_percent' => $change_percent,
            'change_text' => $change_text,
            'is_increase' => $change_percent >= 0
        ];
    }
    
    /**
     * Get enquiries by source with percentages
     * 
     * @param string $period Period: 'today', 'week', 'month', 'year'
     * @param int $limit Number of sources to return
     * 
     * @return array Array of sources with enquiry counts and percentages
     */
    public function get_enquiries_by_source($period = 'month', $limit = 10) {
        
        $cache_key = $this->cache_prefix . 'sources_' . $period;
        $cached = wp_cache_get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $date_filter = $this->get_date_filter($period);
        
        $query = "SELECT 
                    JSON_EXTRACT(utm_data, '$.utm_source') as source,
                    COUNT(*) as count
                  FROM $this->enquiries_table
                  WHERE created_at >= %s
                  AND utm_data IS NOT NULL
                  AND JSON_EXTRACT(utm_data, '$.utm_source') IS NOT NULL
                  GROUP BY JSON_EXTRACT(utm_data, '$.utm_source')
                  ORDER BY count DESC
                  LIMIT %d";
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($query, $date_filter, $limit),
            ARRAY_A
        );
        
        if (empty($results)) {
            return [];
        }
        
        // Calculate totals and percentages
        $total = array_sum(array_column($results, 'count'));
        
        foreach ($results as &$result) {
            $result['percentage'] = round(($result['count'] / $total) * 100, 2);
            $result['color'] = $this->get_source_color($result['source']);
        }
        unset($result);
        
        wp_cache_set($cache_key, $results, '', $this->cache_ttl);
        
        return $results;
    }
    
    /**
     * Get enquiries by campaign with performance metrics
     * 
     * @param string $period Period: 'today', 'week', 'month', 'year'
     * @param int $limit Number of campaigns to return
     * 
     * @return array Array of campaigns with metrics
     */
    public function get_enquiries_by_campaign($period = 'month', $limit = 10) {
        
        $cache_key = $this->cache_prefix . 'campaigns_' . $period;
        $cached = wp_cache_get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $date_filter = $this->get_date_filter($period);
        
        $query = "SELECT 
                    JSON_EXTRACT(utm_data, '$.utm_campaign') as campaign,
                    JSON_EXTRACT(utm_data, '$.utm_source') as source,
                    COUNT(*) as enquiries
                  FROM $this->enquiries_table
                  WHERE created_at >= %s
                  AND utm_data IS NOT NULL
                  AND JSON_EXTRACT(utm_data, '$.utm_campaign') IS NOT NULL
                  GROUP BY campaign, source
                  ORDER BY enquiries DESC
                  LIMIT %d";
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($query, $date_filter, $limit),
            ARRAY_A
        );
        
        if (empty($results)) {
            return [];
        }
        
        // Calculate total and percentages
        $total = array_sum(array_column($results, 'enquiries'));
        
        foreach ($results as &$result) {
            $result['percentage'] = round(($result['enquiries'] / $total) * 100, 1);
            $result['cost_per_enquiry'] = $this->calculate_estimated_cost($result['source']);
            $result['estimated_spend'] = $result['enquiries'] * $result['cost_per_enquiry'];
        }
        unset($result);
        
        wp_cache_set($cache_key, $results, '', $this->cache_ttl);
        
        return $results;
    }
    
    /**
     * Get enquiry trends over time
     * 
     * @param string $period Period: 'week', 'month', 'year'
     * 
     * @return array Array with dates and enquiry counts
     */
    public function get_enquiry_trends($period = 'month') {
        
        $cache_key = $this->cache_prefix . 'trends_' . $period;
        $cached = wp_cache_get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Determine grouping and date filter
        if ($period === 'week') {
            $group_format = '%Y-%m-%d';
            $days = 7;
        } elseif ($period === 'year') {
            $group_format = '%Y-%m';
            $days = 365;
        } else {
            $group_format = '%Y-%m-%d';
            $days = 30;
        }
        
        $date_filter = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        $query = "SELECT 
                    DATE_FORMAT(created_at, %s) as date,
                    COUNT(*) as count
                  FROM $this->enquiries_table
                  WHERE created_at >= %s
                  GROUP BY DATE_FORMAT(created_at, %s)
                  ORDER BY created_at ASC";
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($query, $group_format, $date_filter, $group_format),
            ARRAY_A
        );
        
        // Fill in missing dates with 0
        $results = $this->fill_missing_dates($results, $period);
        
        wp_cache_set($cache_key, $results, '', $this->cache_ttl);
        
        return $results;
    }
    
    /**
     * Get top performing sources with detailed metrics
     * 
     * @param string $period Period: 'today', 'week', 'month', 'year'
     * @param int $limit Number of sources to return
     * 
     * @return array Array of top sources with metrics
     */
    public function get_top_performing_sources($period = 'month', $limit = 5) {
        
        $cache_key = $this->cache_prefix . 'top_sources_' . $period;
        $cached = wp_cache_get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $date_filter = $this->get_date_filter($period);
        
        $query = "SELECT 
                    JSON_EXTRACT(utm_data, '$.utm_source') as source,
                    COUNT(*) as total_enquiries,
                    COUNT(DISTINCT student_email) as unique_students,
                    MIN(created_at) as first_seen,
                    MAX(created_at) as last_seen
                  FROM $this->enquiries_table
                  WHERE created_at >= %s
                  AND utm_data IS NOT NULL
                  AND JSON_EXTRACT(utm_data, '$.utm_source') IS NOT NULL
                  GROUP BY source
                  ORDER BY total_enquiries DESC
                  LIMIT %d";
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($query, $date_filter, $limit),
            ARRAY_A
        );
        
        if (empty($results)) {
            return [];
        }
        
        // Calculate conversion rate and other metrics
        foreach ($results as &$result) {
            $result['conversion_rate'] = round(
                ($result['unique_students'] / $result['total_enquiries']) * 100,
                1
            );
            $result['days_active'] = round(
                (strtotime($result['last_seen']) - strtotime($result['first_seen'])) / 86400,
                1
            );
            $result['enquiries_per_day'] = round(
                $result['total_enquiries'] / max(1, $result['days_active']),
                2
            );
        }
        unset($result);
        
        wp_cache_set($cache_key, $results, '', $this->cache_ttl);
        
        return $results;
    }
    
    /**
     * Get device breakdown statistics
     * 
     * @param string $period Period: 'today', 'week', 'month', 'year'
     * 
     * @return array Device breakdown with percentages
     */
    public function get_device_breakdown($period = 'month') {
        
        $cache_key = $this->cache_prefix . 'devices_' . $period;
        $cached = wp_cache_get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $date_filter = $this->get_date_filter($period);
        
        $query = "SELECT 
                    CASE 
                        WHEN user_agent LIKE '%Mobile%' OR user_agent LIKE '%Android%' THEN 'Mobile'
                        WHEN user_agent LIKE '%Tablet%' OR user_agent LIKE '%iPad%' THEN 'Tablet'
                        ELSE 'Desktop'
                    END as device_type,
                    COUNT(*) as count
                  FROM $this->enquiries_table
                  WHERE created_at >= %s
                  GROUP BY device_type
                  ORDER BY count DESC";
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($query, $date_filter),
            ARRAY_A
        );
        
        if (empty($results)) {
            return [];
        }
        
        // Calculate totals and percentages
        $total = array_sum(array_column($results, 'count'));
        
        foreach ($results as &$result) {
            $result['percentage'] = round(($result['count'] / $total) * 100, 2);
            $result['color'] = $this->get_device_color($result['device_type']);
        }
        unset($result);
        
        wp_cache_set($cache_key, $results, '', $this->cache_ttl);
        
        return $results;
    }
    
    /**
     * Get key performance indicators (KPIs)
     * 
     * @param string $period Period: 'today', 'week', 'month', 'year'
     * 
     * @return array KPIs
     */
    public function get_kpis($period = 'month') {
        
        $cache_key = $this->cache_prefix . 'kpis_' . $period;
        $cached = wp_cache_get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $date_filter = $this->get_date_filter($period);
        
        // Total enquiries
        $total_query = "SELECT COUNT(*) FROM $this->enquiries_table WHERE created_at >= %s";
        $total = $this->wpdb->get_var(
            $this->wpdb->prepare($total_query, $date_filter)
        );
        
        // Unique sources
        $sources_query = "SELECT COUNT(DISTINCT JSON_EXTRACT(utm_data, '$.utm_source')) 
                          FROM $this->enquiries_table 
                          WHERE created_at >= %s AND utm_data IS NOT NULL";
        $unique_sources = $this->wpdb->get_var(
            $this->wpdb->prepare($sources_query, $date_filter)
        );
        
        // Unique campaigns
        $campaigns_query = "SELECT COUNT(DISTINCT JSON_EXTRACT(utm_data, '$.utm_campaign')) 
                            FROM $this->enquiries_table 
                            WHERE created_at >= %s AND utm_data IS NOT NULL";
        $unique_campaigns = $this->wpdb->get_var(
            $this->wpdb->prepare($campaigns_query, $date_filter)
        );
        
        // Average enquiries per day
        $days = $this->get_period_days($period);
        $avg_per_day = round($total / max(1, $days), 2);
        
        $kpis = [
            'total_enquiries' => intval($total),
            'unique_sources' => intval($unique_sources),
            'unique_campaigns' => intval($unique_campaigns),
            'avg_per_day' => $avg_per_day,
            'period_days' => $days
        ];
        
        wp_cache_set($cache_key, $kpis, '', $this->cache_ttl);
        
        return $kpis;
    }
    
    /**
     * Get date filter for query
     * 
     * @param string $period Period: 'today', 'week', 'month', 'year'
     * 
     * @return string Date in 'YYYY-MM-DD HH:MM:SS' format
     */
    private function get_date_filter($period = 'month') {
        
        $days = $this->get_period_days($period);
        return date('Y-m-d H:i:s', strtotime("-$days days"));
    }
    
    /**
     * Get number of days for period
     * 
     * @param string $period Period: 'today', 'week', 'month', 'year'
     * 
     * @return int Number of days
     */
    private function get_period_days($period) {
        
        $days = [
            'today' => 1,
            'week' => 7,
            'month' => 30,
            'year' => 365
        ];
        
        return $days[$period] ?? 30;
    }
    
    /**
     * Get previous period enquiries for comparison
     * 
     * @param string $period Period: 'today', 'week', 'month', 'year'
     * 
     * @return int Previous period enquiries
     */
    private function get_previous_period_enquiries($period) {
        
        $days = $this->get_period_days($period);
        $start = date('Y-m-d H:i:s', strtotime("-" . ($days * 2) . " days"));
        $end = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        $query = "SELECT COUNT(*) FROM $this->enquiries_table 
                  WHERE created_at >= %s AND created_at < %s";
        
        return intval($this->wpdb->get_var(
            $this->wpdb->prepare($query, $start, $end)
        ));
    }
    
    /**
     * Fill in missing dates with 0 values
     * 
     * @param array $results Results with gaps
     * @param string $period Period for formatting
     * 
     * @return array Complete date range with 0 values
     */
    private function fill_missing_dates($results, $period) {
        
        if (empty($results)) {
            return [];
        }
        
        $filled = [];
        $days = $this->get_period_days($period);
        $start_date = strtotime("-$days days");
        
        for ($i = 0; $i < $days; $i++) {
            if ($period === 'year') {
                $current_date = date('Y-m', strtotime("+$i months", $start_date));
            } else {
                $current_date = date('Y-m-d', strtotime("+$i days", $start_date));
            }
            
            $found = false;
            foreach ($results as $result) {
                if ($result['date'] === $current_date) {
                    $filled[] = $result;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $filled[] = [
                    'date' => $current_date,
                    'count' => 0
                ];
            }
        }
        
        return $filled;
    }
    
    /**
     * Get color for source
     * 
     * @param string $source Source name
     * 
     * @return string Hex color code
     */
    private function get_source_color($source) {
        
        $colors = [
            'facebook' => '#1877F2',
            'google' => '#4285F4',
            'tiktok' => '#000000',
            'linkedin' => '#0A66C2',
            'twitter' => '#1DA1F2',
            'instagram' => '#E4405F',
            'email' => '#EA4335',
            'direct' => '#34A853',
            'organic' => '#FBBC04'
        ];
        
        $source_lower = strtolower(trim($source, '"'));
        
        return $colors[$source_lower] ?? $this->generate_color($source);
    }
    
    /**
     * Get color for device type
     * 
     * @param string $device Device type
     * 
     * @return string Hex color code
     */
    private function get_device_color($device) {
        
        $colors = [
            'Mobile' => '#FF6B6B',
            'Tablet' => '#4ECDC4',
            'Desktop' => '#45B7D1'
        ];
        
        return $colors[$device] ?? '#95A5A6';
    }
    
    /**
     * Generate consistent color for value
     * 
     * @param string $value Value to generate color for
     * 
     * @return string Hex color code
     */
    private function generate_color($value) {
        
        $hash = md5($value);
        $r = hexdec(substr($hash, 0, 2));
        $g = hexdec(substr($hash, 2, 2));
        $b = hexdec(substr($hash, 4, 2));
        
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    /**
     * Calculate estimated cost per enquiry
     * 
     * @param string $source Source name
     * 
     * @return float Estimated cost
     */
    private function calculate_estimated_cost($source) {
        
        // Default estimated costs per platform (configurable)
        $default_costs = [
            'facebook' => 50,
            'google' => 75,
            'tiktok' => 40,
            'linkedin' => 150,
            'twitter' => 60,
            'instagram' => 45,
            'email' => 10,
            'direct' => 0,
            'organic' => 0
        ];
        
        $source_lower = strtolower(trim($source, '"'));
        
        return $default_costs[$source_lower] ?? 50;
    }
    
    /**
     * Clear dashboard cache
     */
    public function clear_cache() {
        
        global $wp_object_cache;
        
        if (method_exists($wp_object_cache, 'flush')) {
            // Clear all dashboard caches
            $pattern = $this->cache_prefix . '%';
            wp_cache_flush();
        }
        
        $this->logger->log('Dashboard cache cleared', 'info');
    }
}
?>
