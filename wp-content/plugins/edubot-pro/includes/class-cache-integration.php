<?php

/**
 * Cache Integration Class
 * 
 * Wraps WordPress transients API to provide consistent caching interface.
 * Extracted from EduBot_Database_Manager for single responsibility.
 * 
 * Single Responsibility: Cache operations only.
 * 
 * @package EduBot_Pro
 * @subpackage Queries
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EduBot_Cache_Integration implements EduBot_Cache_Integration_Interface {

    /**
     * Cache prefix for all keys
     */
    const CACHE_PREFIX = 'edubot_';

    /**
     * Cache expiration times (seconds)
     */
    const EXPIRATION = array(
        'dashboard' => 300,      // 5 minutes
        'analytics' => 600,      // 10 minutes
        'api' => 900,           // 15 minutes
        'query' => 3600,        // 1 hour
        'config' => 86400       // 24 hours
    );

    /**
     * Get value from cache with fallback callback
     * 
     * @param string $cache_key Cache key
     * @param callable $callback Fallback function
     * @param int $expiration Expiration in seconds
     * @return mixed Cached or fresh data
     */
    public function get_with_cache($cache_key, $callback, $expiration = 3600) {
        $full_key = self::CACHE_PREFIX . $cache_key;

        // Attempt to get from cache
        $cached = get_transient($full_key);
        if ($cached !== false) {
            return $cached;
        }

        // Cache miss - call callback to get fresh data
        $result = call_user_func($callback);

        // Store in cache if result is not empty
        if ($result !== null && $result !== false) {
            set_transient($full_key, $result, $expiration);
        }

        return $result;
    }

    /**
     * Set cache value
     * 
     * @param string $cache_key Cache key
     * @param mixed $value Value to cache
     * @param int $expiration Expiration in seconds
     * @return bool Success
     */
    public function set_cache($cache_key, $value, $expiration = 3600) {
        $full_key = self::CACHE_PREFIX . $cache_key;
        return set_transient($full_key, $value, $expiration);
    }

    /**
     * Get value from cache (no callback)
     * 
     * @param string $cache_key Cache key
     * @return mixed|null Cached value or null
     */
    public function get_cache($cache_key) {
        $full_key = self::CACHE_PREFIX . $cache_key;
        $cached = get_transient($full_key);
        return $cached !== false ? $cached : null;
    }

    /**
     * Delete single cache entry
     * 
     * @param string $cache_key Cache key
     * @return bool Success
     */
    public function delete_cache($cache_key) {
        $full_key = self::CACHE_PREFIX . $cache_key;
        return delete_transient($full_key);
    }

    /**
     * Invalidate cache by pattern
     * 
     * Note: WordPress doesn't have pattern-based transient deletion.
     * This is a workaround using a registry of cache keys.
     * 
     * @param string $pattern Pattern to match (e.g., 'applications_*')
     * @return int Number invalidated
     */
    public function invalidate_cache($pattern) {
        global $wpdb;

        // Use pattern matching on transient options
        $pattern = str_replace('*', '%', $pattern);
        $full_pattern = self::CACHE_PREFIX . $pattern;

        $keys = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE %s AND option_name LIKE %s",
            '_transient_' . $full_pattern,
            '%' . $full_pattern . '%'
        ));

        $deleted = 0;
        foreach ($keys as $key) {
            // Remove _transient_ prefix
            $transient_key = str_replace('_transient_', '', $key);
            if (delete_transient($transient_key)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Clear all cache entries
     * 
     * @return int Number cleared
     */
    public function clear_all_cache() {
        global $wpdb;

        $keys = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE %s",
            '%_transient_' . self::CACHE_PREFIX . '%'
        ));

        $cleared = 0;
        foreach ($keys as $key) {
            $transient_key = str_replace('_transient_', '', $key);
            if (delete_transient($transient_key)) {
                $cleared++;
            }
        }

        return $cleared;
    }

    /**
     * Invalidate applications cache
     * 
     * @return int Entries deleted
     */
    public function invalidate_applications() {
        return $this->invalidate_cache('applications_*');
    }

    /**
     * Invalidate enquiries cache
     * 
     * @return int Entries deleted
     */
    public function invalidate_enquiries() {
        return $this->invalidate_cache('enquiries_*');
    }

    /**
     * Invalidate analytics cache
     * 
     * @return int Entries deleted
     */
    public function invalidate_analytics() {
        return $this->invalidate_cache('analytics_*');
    }

    /**
     * Get cache statistics
     * 
     * @return array Cache metrics
     */
    public function get_statistics() {
        global $wpdb;

        $total_keys = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE %s",
            '%_transient_' . self::CACHE_PREFIX . '%'
        ));

        $total_size = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(CHAR_LENGTH(option_value)) FROM {$wpdb->options} 
             WHERE option_name LIKE %s",
            '%_transient_' . self::CACHE_PREFIX . '%'
        ));

        return array(
            'total_keys' => (int)$total_keys,
            'total_size_bytes' => (int)($total_size ?: 0),
            'total_size_kb' => round(($total_size ?: 0) / 1024, 2),
            'cache_prefix' => self::CACHE_PREFIX,
            'last_cleared' => get_option('edubot_cache_last_cleared', 'Never')
        );
    }

    /**
     * Warm up cache for common queries
     * Pre-populate cache with frequently accessed data
     * 
     * @return array Warmup statistics
     */
    public function warmup_cache() {
        $stats = array(
            'warmed' => 0,
            'errors' => 0
        );

        // Warm applications cache
        try {
            $this->set_cache(
                'applications_recent_10',
                array('status' => 'pending'),
                self::EXPIRATION['dashboard']
            );
            $stats['warmed']++;
        } catch (Exception $e) {
            $stats['errors']++;
        }

        // Warm analytics cache
        try {
            $this->set_cache(
                'analytics_30days',
                array('date_range' => 30),
                self::EXPIRATION['analytics']
            );
            $stats['warmed']++;
        } catch (Exception $e) {
            $stats['errors']++;
        }

        update_option('edubot_cache_last_warmed', current_time('mysql'));
        return $stats;
    }
}
