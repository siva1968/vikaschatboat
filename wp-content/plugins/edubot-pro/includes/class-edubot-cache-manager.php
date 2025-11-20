<?php

/**
 * EduBot Pro Cache Manager
 * 
 * Implements transient-based caching for frequently accessed data.
 * Reduces database queries and improves performance by caching:
 * - Dashboard statistics
 * - API responses
 * - Analytics data
 * - Query results
 * 
 * @package    EduBot_Pro
 * @subpackage EduBot_Pro/includes
 * @since      1.4.2
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Cache Manager class for transient-based caching
 * 
 * Uses WordPress transients API for:
 * - Automatic expiration
 * - Database-backed persistence
 * - Network multisite support
 * - Site-specific caching
 * 
 * @since 1.4.2
 */
class EduBot_Cache_Manager {

    /**
     * Cache key prefix to avoid conflicts
     */
    const CACHE_PREFIX = 'edubot_';

    /**
     * Default cache expiration times (in seconds)
     */
    const CACHE_EXPIRATION = array(
        'dashboard' => 5 * MINUTE_IN_SECONDS,      // 5 minutes
        'analytics' => 10 * MINUTE_IN_SECONDS,     // 10 minutes
        'api_response' => 15 * MINUTE_IN_SECONDS,  // 15 minutes
        'statistics' => 30 * MINUTE_IN_SECONDS,    // 30 minutes
        'query_result' => 1 * HOUR_IN_SECONDS,     // 1 hour
        'configuration' => 24 * HOUR_IN_SECONDS,   // 24 hours
    );

    /**
     * Cache disabled flag (for testing/debugging)
     */
    private static $cache_disabled = false;

    /**
     * Store cached keys for bulk clearing
     */
    private static $cached_keys = array();

    /**
     * Get cached data or execute callback if cache miss
     * 
     * Usage:
     * $data = EduBot_Cache_Manager::get_or_fetch('dashboard_stats', function() {
     *     return expensive_operation();
     * }, 'dashboard');
     * 
     * @param string $cache_key Unique cache key
     * @param callable $fetch_callback Function to execute if cache miss
     * @param string $cache_type Type of cache (dashboard, analytics, api_response, etc.)
     * @return mixed Cached or fresh data
     * @since 1.4.2
     */
    public static function get_or_fetch($cache_key, $fetch_callback, $cache_type = 'query_result') {
        
        // Return fresh data if caching is disabled
        if (self::$cache_disabled) {
            return call_user_func($fetch_callback);
        }

        // Try to get from cache
        $cached_data = self::get($cache_key);
        if ($cached_data !== null) {
            return $cached_data;
        }

        // Cache miss - fetch fresh data
        $data = call_user_func($fetch_callback);

        // Cache the result
        if ($data !== null && $data !== false) {
            self::set($cache_key, $data, $cache_type);
        }

        return $data;
    }

    /**
     * Get data from cache
     * 
     * @param string $cache_key The cache key to retrieve
     * @return mixed Cached data, or null if not found
     * @since 1.4.2
     */
    public static function get($cache_key) {
        
        if (self::$cache_disabled) {
            return null;
        }

        $full_key = self::get_full_cache_key($cache_key);
        $cached = get_transient($full_key);

        // Log cache hit
        if ($cached !== false) {
            if (function_exists('EduBot_Logger')) {
                EduBot_Logger::debug('Cache hit', array(
                    'cache_key' => $cache_key,
                    'data_size' => is_string($cached) ? strlen($cached) : sizeof($cached),
                ));
            }
            return $cached;
        }

        return null;
    }

    /**
     * Set data in cache
     * 
     * @param string $cache_key The cache key to store
     * @param mixed $data The data to cache
     * @param string $cache_type Type of cache (determines expiration)
     * @return bool True if set successfully
     * @since 1.4.2
     */
    public static function set($cache_key, $data, $cache_type = 'query_result') {
        
        if (self::$cache_disabled) {
            return false;
        }

        // Get expiration time for this cache type
        $expiration = isset(self::CACHE_EXPIRATION[$cache_type]) 
            ? self::CACHE_EXPIRATION[$cache_type]
            : self::CACHE_EXPIRATION['query_result'];

        $full_key = self::get_full_cache_key($cache_key);
        
        // Store the key for bulk clearing
        self::track_cache_key($cache_key);

        // Set transient
        $result = set_transient($full_key, $data, $expiration);

        // Log cache set
        if ($result && function_exists('EduBot_Logger')) {
            EduBot_Logger::debug('Cache set', array(
                'cache_key' => $cache_key,
                'cache_type' => $cache_type,
                'expiration_seconds' => $expiration,
            ));
        }

        return $result;
    }

    /**
     * Delete cache by key
     * 
     * @param string $cache_key The cache key to delete
     * @return bool True if deleted successfully
     * @since 1.4.2
     */
    public static function delete($cache_key) {
        
        $full_key = self::get_full_cache_key($cache_key);
        $result = delete_transient($full_key);

        if ($result && function_exists('EduBot_Logger')) {
            EduBot_Logger::debug('Cache deleted', array(
                'cache_key' => $cache_key,
            ));
        }

        return $result;
    }

    /**
     * Clear cache by pattern
     * 
     * Usage:
     * EduBot_Cache_Manager::clear_by_pattern('dashboard_*')
     * EduBot_Cache_Manager::clear_by_pattern('analytics_*')
     * 
     * @param string $pattern Pattern to match (supports * wildcard)
     * @return int Number of cache entries cleared
     * @since 1.4.2
     */
    public static function clear_by_pattern($pattern) {
        
        global $wpdb;
        
        // Convert pattern to SQL LIKE clause
        $pattern = str_replace('*', '%', $pattern);
        $prefix = self::CACHE_PREFIX;
        $site_prefix = self::get_transient_prefix();
        $like_pattern = "{$site_prefix}{$prefix}{$pattern}";

        // Find matching transients
        $query = $wpdb->prepare(
            "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
            $like_pattern
        );

        $matching = $wpdb->get_col($query);
        $count = 0;

        foreach ($matching as $option_name) {
            // Remove 'transient_' prefix to get cache key
            $transient_key = str_replace('transient_', '', $option_name);
            if (delete_transient($transient_key)) {
                $count++;
            }
        }

        if ($count > 0 && function_exists('EduBot_Logger')) {
            EduBot_Logger::debug('Cache cleared by pattern', array(
                'pattern' => $pattern,
                'entries_cleared' => $count,
            ));
        }

        return $count;
    }

    /**
     * Clear all EduBot cache
     * 
     * @return int Number of cache entries cleared
     * @since 1.4.2
     */
    public static function clear_all() {
        return self::clear_by_pattern('*');
    }

    /**
     * Disable caching (useful for testing)
     * 
     * @since 1.4.2
     */
    public static function disable() {
        self::$cache_disabled = true;
    }

    /**
     * Enable caching
     * 
     * @since 1.4.2
     */
    public static function enable() {
        self::$cache_disabled = false;
    }

    /**
     * Check if caching is enabled
     * 
     * @return bool True if caching is enabled
     * @since 1.4.2
     */
    public static function is_enabled() {
        return !self::$cache_disabled;
    }

    /**
     * Get cache statistics
     * 
     * @return array Cache statistics including size and count
     * @since 1.4.2
     */
    public static function get_statistics() {
        
        global $wpdb;
        
        $site_prefix = self::get_transient_prefix();
        $like_pattern = "{$site_prefix}" . self::CACHE_PREFIX . "%";

        // Count cache entries
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE %s",
            $like_pattern
        ));

        // Estimate total size
        $size_query = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(LENGTH(option_value)) FROM $wpdb->options WHERE option_name LIKE %s",
            $like_pattern
        ));

        $total_size = $size_query ? $size_query : 0;

        return array(
            'total_entries' => (int)$count,
            'total_size_bytes' => (int)$total_size,
            'total_size_mb' => round($total_size / (1024 * 1024), 2),
            'cache_enabled' => self::is_enabled(),
        );
    }

    /**
     * Cache dashboard statistics
     * 
     * @param callable $callback Function to fetch dashboard data
     * @return array Dashboard data
     * @since 1.4.2
     */
    public static function get_dashboard_stats($callback) {
        
        return self::get_or_fetch(
            'dashboard_stats',
            $callback,
            'dashboard'
        );
    }

    /**
     * Cache analytics data
     * 
     * @param callable $callback Function to fetch analytics data
     * @param int $date_range Days to look back
     * @return array Analytics data
     * @since 1.4.2
     */
    public static function get_analytics($callback, $date_range = 30) {
        
        $cache_key = "analytics_data_{$date_range}days";
        
        return self::get_or_fetch(
            $cache_key,
            $callback,
            'analytics'
        );
    }

    /**
     * Cache applications list
     * 
     * @param callable $callback Function to fetch applications
     * @param int $page Page number
     * @param int $per_page Items per page
     * @param array $filters Applied filters
     * @return array Applications data
     * @since 1.4.2
     */
    public static function get_applications($callback, $page = 1, $per_page = 20, $filters = array()) {
        
        // Create cache key based on filters
        $filter_hash = md5(wp_json_encode($filters));
        $cache_key = "applications_page{$page}_{$per_page}_{$filter_hash}";
        
        return self::get_or_fetch(
            $cache_key,
            $callback,
            'query_result'
        );
    }

    /**
     * Cache enquiries list
     * 
     * @param callable $callback Function to fetch enquiries
     * @param int $limit Limit results
     * @param array $filters Applied filters
     * @return array Enquiries data
     * @since 1.4.2
     */
    public static function get_enquiries($callback, $limit = 100, $filters = array()) {
        
        $filter_hash = md5(wp_json_encode($filters));
        $cache_key = "enquiries_limit{$limit}_{$filter_hash}";
        
        return self::get_or_fetch(
            $cache_key,
            $callback,
            'query_result'
        );
    }

    /**
     * Cache API responses
     * 
     * @param callable $callback Function to fetch API response
     * @param string $api_endpoint API endpoint identifier
     * @param array $request_params Request parameters
     * @return mixed API response data
     * @since 1.4.2
     */
    public static function get_api_response($callback, $api_endpoint, $request_params = array()) {
        
        $param_hash = md5(wp_json_encode($request_params));
        $cache_key = "api_{$api_endpoint}_{$param_hash}";
        
        return self::get_or_fetch(
            $cache_key,
            $callback,
            'api_response'
        );
    }

    /**
     * Invalidate dashboard cache when applications change
     * 
     * @since 1.4.2
     */
    public static function invalidate_dashboard() {
        self::delete('dashboard_stats');
        self::delete('recent_applications');
    }

    /**
     * Invalidate analytics cache when data changes
     * 
     * @since 1.4.2
     */
    public static function invalidate_analytics() {
        self::clear_by_pattern('analytics_*');
    }

    /**
     * Invalidate applications cache when list changes
     * 
     * @since 1.4.2
     */
    public static function invalidate_applications() {
        self::clear_by_pattern('applications_*');
        self::invalidate_dashboard();
    }

    /**
     * Invalidate enquiries cache
     * 
     * @since 1.4.2
     */
    public static function invalidate_enquiries() {
        self::clear_by_pattern('enquiries_*');
        self::invalidate_applications();
    }

    /**
     * Get full cache key with prefix and site context
     * 
     * @param string $cache_key Base cache key
     * @return string Full cache key for transients
     * @since 1.4.2
     */
    private static function get_full_cache_key($cache_key) {
        $prefix = self::CACHE_PREFIX;
        return "{$prefix}{$cache_key}";
    }

    /**
     * Get WordPress transient prefix based on multisite setup
     * 
     * @return string Transient prefix
     * @since 1.4.2
     */
    private static function get_transient_prefix() {
        if (is_multisite()) {
            return "site_" . get_current_blog_id() . "_";
        }
        return "";
    }

    /**
     * Track cache key for bulk operations
     * 
     * @param string $cache_key The cache key to track
     * @since 1.4.2
     */
    private static function track_cache_key($cache_key) {
        if (!in_array($cache_key, self::$cached_keys)) {
            self::$cached_keys[] = $cache_key;
        }
    }

    /**
     * Warm up cache on initialization
     * 
     * Pre-populate cache with frequently accessed data to avoid
     * cold start performance hits.
     * 
     * @since 1.4.2
     */
    public static function warm_up() {
        
        if (!self::is_enabled()) {
            return;
        }

        if (function_exists('EduBot_Logger')) {
            EduBot_Logger::info('Starting cache warm-up', array());
        }

        // Warm up dashboard cache
        if (function_exists('EduBot_Database_Manager')) {
            $db_manager = new EduBot_Database_Manager();
            
            self::get_or_fetch('dashboard_stats', function() use ($db_manager) {
                return $db_manager->get_dashboard_stats();
            }, 'dashboard');
        }

        if (function_exists('EduBot_Logger')) {
            EduBot_Logger::info('Cache warm-up completed', array(
                'entries' => count(self::$cached_keys),
            ));
        }
    }

    /**
     * Get cache configuration for debugging
     * 
     * @return array Cache configuration details
     * @since 1.4.2
     */
    public static function get_configuration() {
        
        return array(
            'enabled' => self::is_enabled(),
            'prefix' => self::CACHE_PREFIX,
            'expiration_times' => self::CACHE_EXPIRATION,
            'statistics' => self::get_statistics(),
        );
    }
}
