<?php
/**
 * MCB Manual Sync - Admin Interface
 * 
 * Adds manual sync button and status column to applications/enquiries list page
 * Allows admins to manually trigger MCB sync for any enquiry
 * 
 * @package EduBot_Pro
 * @subpackage MCB_Admin
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EduBot_MCB_Admin {
    
    /**
     * Initialize admin functionality
     */
    public static function init() {
        // Add AJAX handler for manual sync
        add_action('wp_ajax_edubot_mcb_manual_sync', array(__CLASS__, 'handle_manual_sync'));
        add_action('wp_ajax_nopriv_edubot_mcb_manual_sync', array(__CLASS__, 'handle_manual_sync'));
        
        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
        
        // Add action links to applications table
        add_filter('edubot_applications_row_actions', array(__CLASS__, 'add_sync_action'), 10, 2);
        
        // Add MCB status column
        add_filter('edubot_applications_columns', array(__CLASS__, 'add_mcb_status_column'));
        add_action('edubot_applications_column_mcb_status', array(__CLASS__, 'render_mcb_status_column'), 10, 2);
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public static function enqueue_admin_assets($hook) {
        // Only load on EduBot applications page
        if (strpos($hook, 'edubot') === false) {
            return;
        }
        
        wp_enqueue_script(
            'edubot-mcb-admin',
            plugin_dir_url(__FILE__) . '../js/edubot-mcb-admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('edubot-mcb-admin', 'edubot_mcb', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('edubot_mcb_sync'),
            'sync_text' => 'Syncing to MCB...',
            'sync_success' => 'Successfully synced to MCB!',
            'sync_failed' => 'Failed to sync. Check error logs.',
            'sync_already' => 'Already synced to MCB'
        ));
        
        // Add inline styles
        wp_enqueue_style(
            'edubot-mcb-admin',
            plugin_dir_url(__FILE__) . '../css/edubot-mcb-admin.css',
            array(),
            '1.0.0'
        );
    }
    
    /**
     * Add MCB sync action to row actions
     */
    public static function add_sync_action($actions, $application) {
        // Check if MCB integration is enabled
        if (!class_exists('EduBot_MCB_Service')) {
            return $actions;
        }
        
        $mcb_service = EduBot_MCB_Service::get_instance();
        
        // Only show button if MCB sync is enabled
        if (!$mcb_service->is_sync_enabled()) {
            return $actions;
        }
        
        // Use 'id' from applications table (primary key)
        $application_id = isset($application['id']) ? $application['id'] : 0;
        $mcb_status = isset($application['mcb_sync_status']) ? $application['mcb_sync_status'] : '';
        
        if ($application_id) {
            $sync_text = 'Sync MCB';
            $sync_class = 'sync-mcb';
            
            if ($mcb_status === 'synced') {
                $sync_text = '✓ Synced';
                $sync_class = 'synced';
            } elseif ($mcb_status === 'failed') {
                $sync_text = 'Retry MCB';
                $sync_class = 'retry-mcb';
            }
            
            $actions['mcb_sync'] = sprintf(
                '<a href="#" class="mcb-sync-btn %s" data-enquiry-id="%d" title="Sync this enquiry to MyClassBoard">%s</a>',
                $sync_class,
                $application_id,
                $sync_text
            );
        }
        
        return $actions;
    }
    
    /**
     * Add MCB status column to applications table
     */
    public static function add_mcb_status_column($columns) {
        $columns['mcb_status'] = 'MCB Status';
        return $columns;
    }
    
    /**
     * Render MCB status column content
     */
    public static function render_mcb_status_column($application_id, $application) {
        $status = isset($application['mcb_sync_status']) ? $application['mcb_sync_status'] : 'pending';
        $mcb_id = isset($application['mcb_enquiry_id']) ? $application['mcb_enquiry_id'] : '';
        
        $status_html = '';
        
        switch ($status) {
            case 'synced':
                $status_html = '<span class="badge badge-success">✓ Synced</span>';
                if ($mcb_id) {
                    $status_html .= '<br><small>ID: ' . esc_html($mcb_id) . '</small>';
                }
                break;
            case 'failed':
                $status_html = '<span class="badge badge-danger">✗ Failed</span>';
                break;
            case 'retry':
                $status_html = '<span class="badge badge-warning">↻ Retrying</span>';
                break;
            case 'pending':
            default:
                $status_html = '<span class="badge badge-secondary">⊙ Pending</span>';
        }
        
        echo $status_html;
    }
    
    /**
     * Handle AJAX manual sync request
     */
    public static function handle_manual_sync() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'edubot_mcb_sync')) {
            wp_send_json_error(array('message' => 'Security verification failed'));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        // Get enquiry ID
        $enquiry_id = isset($_POST['enquiry_id']) ? intval($_POST['enquiry_id']) : 0;
        
        if (!$enquiry_id) {
            wp_send_json_error(array('message' => 'Invalid enquiry ID'));
        }
        
        // Get MCB service
        if (!class_exists('EduBot_MCB_Service')) {
            wp_send_json_error(array('message' => 'MCB service not available'));
        }
        
        $mcb_service = EduBot_MCB_Service::get_instance();
        
        // Check if MCB is enabled
        if (!$mcb_service->is_sync_enabled()) {
            wp_send_json_error(array('message' => 'MCB sync is not enabled'));
        }
        
        // Trigger sync
        $result = $mcb_service->sync_enquiry($enquiry_id);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => $result['message'],
                'status' => $result['status'],
                'mcb_id' => $result['mcb_enquiry_id'] ?? ''
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message'],
                'error' => $result['error'] ?? ''
            ));
        }
    }
    
    /**
     * Get MCB sync status for enquiry
     */
    public static function get_sync_status($enquiry_id) {
        global $wpdb;
        
        $log = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}edubot_mcb_sync_log 
                 WHERE enquiry_id = %d 
                 ORDER BY created_at DESC 
                 LIMIT 1",
                $enquiry_id
            ),
            ARRAY_A
        );
        
        if (!$log) {
            return array('status' => 'pending', 'message' => 'Not synced yet');
        }
        
        return array(
            'status' => $log['success'] ? 'synced' : 'failed',
            'success' => $log['success'],
            'message' => $log['error_message'] ?? 'Synced successfully',
            'sync_time' => $log['created_at'],
            'retry_count' => $log['retry_count']
        );
    }
}

// Initialize on admin
if (is_admin()) {
    EduBot_MCB_Admin::init();
}
?>
