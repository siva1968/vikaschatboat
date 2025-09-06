<?php

/**
 * EduBot Pro Plugin Constants
 * Centralized constant definitions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Core plugin constants (only define if not already defined)
if (!defined('EDUBOT_PRO_VERSION')) {
    define('EDUBOT_PRO_VERSION', '1.1.0');
}

if (!defined('EDUBOT_PRO_PLUGIN_FILE')) {
    define('EDUBOT_PRO_PLUGIN_FILE', __FILE__);
}

if (!defined('EDUBOT_PRO_PLUGIN_BASENAME')) {
    define('EDUBOT_PRO_PLUGIN_BASENAME', plugin_basename(EDUBOT_PRO_PLUGIN_FILE));
}

if (!defined('EDUBOT_PRO_PLUGIN_PATH')) {
    define('EDUBOT_PRO_PLUGIN_PATH', plugin_dir_path(EDUBOT_PRO_PLUGIN_FILE));
}

if (!defined('EDUBOT_PRO_PLUGIN_URL')) {
    define('EDUBOT_PRO_PLUGIN_URL', plugin_dir_url(EDUBOT_PRO_PLUGIN_FILE));
}

if (!defined('EDUBOT_PRO_PLUGIN_DIR')) {
    define('EDUBOT_PRO_PLUGIN_DIR', dirname(EDUBOT_PRO_PLUGIN_FILE));
}

// Database constants
if (!defined('EDUBOT_PRO_DB_VERSION')) {
    define('EDUBOT_PRO_DB_VERSION', '1.1.0');
}

// Feature flags
if (!defined('EDUBOT_PRO_ENABLE_ANALYTICS')) {
    define('EDUBOT_PRO_ENABLE_ANALYTICS', true);
}

if (!defined('EDUBOT_PRO_ENABLE_VISITOR_TRACKING')) {
    define('EDUBOT_PRO_ENABLE_VISITOR_TRACKING', true);
}

if (!defined('EDUBOT_PRO_ENABLE_DEBUG_LOGGING')) {
    define('EDUBOT_PRO_ENABLE_DEBUG_LOGGING', defined('WP_DEBUG') && WP_DEBUG);
}

// Security constants
if (!defined('EDUBOT_PRO_NONCE_LIFETIME')) {
    define('EDUBOT_PRO_NONCE_LIFETIME', 12 * HOUR_IN_SECONDS);
}

if (!defined('EDUBOT_PRO_SESSION_TIMEOUT')) {
    define('EDUBOT_PRO_SESSION_TIMEOUT', 30 * MINUTE_IN_SECONDS);
}

if (!defined('EDUBOT_PRO_RATE_LIMIT_REQUESTS')) {
    define('EDUBOT_PRO_RATE_LIMIT_REQUESTS', 100);
}

if (!defined('EDUBOT_PRO_RATE_LIMIT_WINDOW')) {
    define('EDUBOT_PRO_RATE_LIMIT_WINDOW', HOUR_IN_SECONDS);
}

// Analytics constants
if (!defined('EDUBOT_PRO_ANALYTICS_RETENTION_DAYS')) {
    define('EDUBOT_PRO_ANALYTICS_RETENTION_DAYS', 30);
}

if (!defined('EDUBOT_PRO_VISITOR_COOKIE_LIFETIME')) {
    define('EDUBOT_PRO_VISITOR_COOKIE_LIFETIME', 30 * DAY_IN_SECONDS);
}

// File upload constants
if (!defined('EDUBOT_PRO_MAX_UPLOAD_SIZE')) {
    define('EDUBOT_PRO_MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
}

if (!defined('EDUBOT_PRO_ALLOWED_FILE_TYPES')) {
    define('EDUBOT_PRO_ALLOWED_FILE_TYPES', 'jpg,jpeg,png,pdf,doc,docx');
}

// API constants
if (!defined('EDUBOT_PRO_API_TIMEOUT')) {
    define('EDUBOT_PRO_API_TIMEOUT', 30);
}

if (!defined('EDUBOT_PRO_API_RETRY_ATTEMPTS')) {
    define('EDUBOT_PRO_API_RETRY_ATTEMPTS', 3);
}

// Cache constants
if (!defined('EDUBOT_PRO_CACHE_LIFETIME')) {
    define('EDUBOT_PRO_CACHE_LIFETIME', 6 * HOUR_IN_SECONDS);
}

// Minimum requirements
if (!defined('EDUBOT_PRO_MIN_WP_VERSION')) {
    define('EDUBOT_PRO_MIN_WP_VERSION', '5.0');
}

if (!defined('EDUBOT_PRO_MIN_PHP_VERSION')) {
    define('EDUBOT_PRO_MIN_PHP_VERSION', '7.4');
}

// Text domain
if (!defined('EDUBOT_PRO_TEXT_DOMAIN')) {
    define('EDUBOT_PRO_TEXT_DOMAIN', 'edubot-pro');
}

/**
 * Validate that all critical constants are defined
 */
function edubot_pro_validate_constants() {
    $required_constants = array(
        'EDUBOT_PRO_VERSION',
        'EDUBOT_PRO_PLUGIN_PATH',
        'EDUBOT_PRO_PLUGIN_URL',
        'EDUBOT_PRO_DB_VERSION'
    );
    
    $missing = array();
    foreach ($required_constants as $constant) {
        if (!defined($constant)) {
            $missing[] = $constant;
        }
    }
    
    if (!empty($missing)) {
        wp_die(
            'EduBot Pro: Critical constants are missing: ' . implode(', ', $missing),
            'Plugin Configuration Error',
            array('response' => 500)
        );
    }
}

// Validate constants on load
edubot_pro_validate_constants();
