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
        error_log('MCB Admin Init: Called');
        
        // Add AJAX handler for manual sync
        add_action('wp_ajax_edubot_mcb_manual_sync', array(__CLASS__, 'handle_manual_sync'));
        add_action('wp_ajax_nopriv_edubot_mcb_manual_sync', array(__CLASS__, 'handle_manual_sync'));
        
        // Add AJAX handler for MCB data preview
        add_action('wp_ajax_edubot_mcb_preview_data', array(__CLASS__, 'handle_preview_mcb_data'));
        add_action('wp_ajax_nopriv_edubot_mcb_preview_data', array(__CLASS__, 'handle_preview_mcb_data'));
        
        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
        
        // Add action links to applications table
        $hook_result = add_filter('edubot_applications_row_actions', array(__CLASS__, 'add_sync_action'), 10, 2);
        error_log('MCB Admin Init: Added filter hook - ' . ($hook_result ? 'Success' : 'Failed'));
        
        // Add MCB status column
        add_filter('edubot_applications_columns', array(__CLASS__, 'add_mcb_status_column'));
        add_action('edubot_applications_column_mcb_status', array(__CLASS__, 'render_mcb_status_column'), 10, 2);
        
        error_log('MCB Admin Init: Complete');
    }
    
    /**
     * Enqueue admin scripts and styles
     * 
     * NOTE: MCB functionality is inlined in applications-list.php template
     * This method is kept for backwards compatibility but scripts are not enqueued
     * to avoid conflicts with the inlined version
     */
    public static function enqueue_admin_assets($hook) {
        // MCB scripts are now inlined in applications-list.php template
        // This prevents duplicate code execution and data-attribute issues
        error_log('📝 MCB Admin: Scripts handled via inlined code in applications-list.php');
        
        // Only enqueue styles (JavaScript is inlined in template)
        wp_enqueue_style(
            'edubot-mcb-admin-css',
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
        
        // DEBUG: Log what's in the application array
        error_log('═════════════════════════════════════════════════════');
        error_log('MCB add_sync_action - DATA RECEIVED:');
        error_log('Available keys in $application: ' . implode(', ', array_keys($application)));
        error_log('$application[\'id\']: ' . ($application['id'] ?? 'NOT SET'));
        error_log('$application[\'application_number\']: ' . ($application['application_number'] ?? 'NOT SET'));
        error_log('$application[\'enquiry_id\']: ' . ($application['enquiry_id'] ?? 'NOT SET'));
        error_log('FULL $application array:');
        foreach ($application as $key => $val) {
            error_log('  [' . $key . '] = ' . (is_array($val) ? json_encode($val) : $val));
        }
        error_log('═════════════════════════════════════════════════════');
        
        // Use 'id' from applications table (primary key)
        // NOTE: $application['id'] is a string like 'enq_40', not an integer
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
                '<a href="#" class="mcb-sync-btn %s" data-enquiry-id="%s" title="Sync this enquiry to MyClassBoard">%s</a>',
                $sync_class,
                esc_attr($application_id),
                $sync_text
            );
            
            // Add preview button
            // NOTE: For MCB, we need to pass the enquiry_number (application_number),
            // NOT the prefixed ID. The MCB service will use this to find the actual
            // application record and link it to the enquiry.
            $actions['mcb_preview'] = sprintf(
                '<a href="#" class="mcb-preview-btn" data-enquiry-id="%s" title="Preview MCB data that will be sent">👁️ Preview</a>',
                esc_attr($application['application_number'] ?? 'N/A')
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
     * Handle AJAX preview MCB sync data (WITHOUT submitting to MCB)
     * Shows exactly what data will be sent when sync button is clicked
     */
    public static function handle_preview_mcb_data() {
        // Log entry point
        error_log('═════════════════════════════════════════════════════');
        error_log('🔵 === MCB PREVIEW AJAX HANDLER CALLED (v' . EDUBOT_PRO_VERSION . ') ===');
        error_log('═════════════════════════════════════════════════════');
        error_log('POST data keys: ' . implode(', ', array_keys($_POST)));
        error_log('action: ' . ($_POST['action'] ?? 'MISSING'));
        error_log('enquiry_id: ' . ($_POST['enquiry_id'] ?? 'MISSING'));
        error_log('nonce: ' . (isset($_POST['nonce']) ? 'PRESENT' : 'MISSING'));
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'edubot_mcb_sync')) {
            error_log('❌ NONCE VERIFICATION FAILED');
            wp_send_json_error(array('message' => 'Security verification failed'));
        }
        
        error_log('✅ Nonce verified');
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            error_log('❌ User lacks manage_options capability');
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        error_log('✅ User has manage_options');
        
        // Get enquiry ID
        $enquiry_id = isset($_POST['enquiry_id']) ? sanitize_text_field($_POST['enquiry_id']) : '';
        error_log('enquiry_id received: "' . $enquiry_id . '" (type: ' . gettype($enquiry_id) . ', length: ' . strlen($enquiry_id) . ')');
        
        if (!$enquiry_id) {
            error_log('❌ enquiry_id is empty after sanitization');
            wp_send_json_error(array('message' => 'Invalid enquiry ID'));
        }
        
        error_log('✅ enquiry_id is valid: ' . $enquiry_id);
        
        // Get MCB service
        error_log('✓ Checking if EduBot_MCB_Service exists...');
        if (!class_exists('EduBot_MCB_Service')) {
            error_log('❌ EduBot_MCB_Service class not found!');
            wp_send_json_error(array('message' => 'MCB service not available'));
        }
        
        error_log('✅ EduBot_MCB_Service found');
        $mcb_service = EduBot_MCB_Service::get_instance();
        error_log('✅ MCB Service instance obtained');
        
        // Check if MCB is enabled
        error_log('✓ Checking if MCB sync is enabled...');
        $is_enabled = $mcb_service->is_sync_enabled();
        error_log('  Result: ' . ($is_enabled ? 'YES' : 'NO'));
        
        if (!$is_enabled) {
            error_log('❌ MCB sync is NOT enabled');
            wp_send_json_error(array('message' => 'MCB sync is not enabled'));
        }
        
        error_log('✅ MCB sync is enabled');
        
        // Get preview data
        error_log('✓ Calling preview_mcb_data("' . $enquiry_id . '")...');
        $result = $mcb_service->preview_mcb_data($enquiry_id);
        error_log('✓ Result received:');
        error_log('  - success: ' . ($result['success'] ? 'true' : 'false'));
        error_log('  - message: ' . ($result['message'] ?? 'N/A'));
        
        if ($result['success']) {
            error_log('✅ SUCCESS - Returning preview data');
            wp_send_json_success(array(
                'message' => $result['message'],
                'enquiry_number' => $result['enquiry_number'],
                'mcb_data' => $result['mcb_data'],
                'marketing_data' => $result['marketing_data'],
                'enquiry_source_data' => $result['enquiry_source_data'],
                'mcb_settings' => $result['mcb_settings']
            ));
        } else {
            error_log('❌ FAILED - Returning error: ' . ($result['message'] ?? 'Unknown error'));
            wp_send_json_error(array(
                'message' => $result['message']
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
