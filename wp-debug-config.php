<?php
/**
 * Enable WordPress debugging
 * Add this to wp-config.php before the line: "That's all, stop editing!"
 */

// Enable WP_DEBUG mode
define('WP_DEBUG', true);

// Enable debug logging
define('WP_DEBUG_LOG', true);

// Disable displaying errors on site (but log them)
define('WP_DEBUG_DISPLAY', false);

// Increase script memory for debugging
define('WP_MEMORY_LIMIT', '256M');

// Enable database error logging
define('SAVEQUERIES', true);
