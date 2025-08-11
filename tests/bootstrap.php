<?php
/**
 * PHPUnit Bootstrap for EduBot Pro Plugin Tests
 */

// Define test environment
define('EDUBOT_PRO_TEST_ENV', true);

// WordPress test suite requires these
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
    require dirname(__FILE__) . '/../edubot-pro.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

require $_tests_dir . '/includes/bootstrap.php';
