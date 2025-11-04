<?php
/**
 * Admin Dashboard JavaScript Handler
 * 
 * Handles chart interactions, data updates, and export functionality
 * for the analytics dashboard.
 * 
 * @since 1.3.3
 * @package EduBot_Pro
 * @subpackage Admin/JS
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Register and enqueue scripts
add_action('admin_enqueue_scripts', function($hook) {
    // Only load on dashboard page
    if (strpos($hook, 'edubot-dashboard') === false) {
        return;
    }
    
    // Chart.js library
    wp_enqueue_script(
        'chart-js',
        'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
        [],
        '3.9.1',
        true
    );
    
    // Export library (html2pdf for PDF export)
    wp_enqueue_script(
        'html2pdf-js',
        'https://cdn.jsdelivr.net/npm/html2pdf@0.10.1/dist/html2pdf.bundle.min.js',
        [],
        '0.10.1',
        true
    );
    
    // Dashboard specific scripts
    wp_enqueue_script(
        'edubot-dashboard-js',
        plugin_dir_url(__FILE__) . 'dashboard.js',
        ['chart-js', 'html2pdf-js'],
        '1.3.3',
        true
    );
    
    // Inline script for localization
    wp_add_inline_script('edubot-dashboard-js', 'var edubot_dashboard_config = ' . wp_json_encode([
        'nonce' => wp_create_nonce('edubot_dashboard_nonce'),
        'ajax_url' => admin_url('admin-ajax.php'),
        'period' => isset($_GET['dashboard_period']) ? sanitize_text_field($_GET['dashboard_period']) : 'month'
    ]) . ';');
});
?>
