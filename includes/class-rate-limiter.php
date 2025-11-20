<?php

/**
 * Rate Limiting for EduBot Pro
 * Prevent abuse and spam
 */
class EduBot_Rate_Limiter {

    /**
     * Check if request is rate limited
     */
    public static function is_rate_limited($identifier, $limit = null, $window = null) {
        if ($limit === null) {
            $limit = EDUBOT_PRO_RATE_LIMIT_REQUESTS;
        }
        
        if ($window === null) {
            $window = EDUBOT_PRO_RATE_LIMIT_WINDOW;
        }
        
        $key = 'edubot_rate_limit_' . md5($identifier);
        $current_count = get_transient($key);
        
        if ($current_count === false) {
            // First request in window
            set_transient($key, 1, $window);
            return false;
        }
        
        if ($current_count >= $limit) {
            return true; // Rate limited
        }
        
        // Increment counter
        set_transient($key, $current_count + 1, $window);
        return false;
    }

    /**
     * Get rate limit info
     */
    public static function get_rate_limit_info($identifier) {
        $key = 'edubot_rate_limit_' . md5($identifier);
        $current_count = get_transient($key);
        
        return array(
            'requests_made' => $current_count ? $current_count : 0,
            'limit' => EDUBOT_PRO_RATE_LIMIT_REQUESTS,
            'window' => EDUBOT_PRO_RATE_LIMIT_WINDOW,
            'remaining' => max(0, EDUBOT_PRO_RATE_LIMIT_REQUESTS - ($current_count ? $current_count : 0))
        );
    }

    /**
     * Reset rate limit for identifier
     */
    public static function reset_rate_limit($identifier) {
        $key = 'edubot_rate_limit_' . md5($identifier);
        delete_transient($key);
    }
}