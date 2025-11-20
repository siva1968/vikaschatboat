<?php
/**
 * MCB Sync Integration Hooks
 * 
 * Integrates MCB sync service into the EduBot enquiry submission workflow
 * Triggers automatic sync when enquiries are created or updated
 * 
 * @package EduBot_Pro
 * @subpackage MCB_Integration
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EduBot_MCB_Integration {
    
    /**
     * Initialize MCB integration hooks
     */
    public static function init() {
        // Hook into enquiry submission
        add_action('edubot_after_enquiry_saved', array(__CLASS__, 'handle_enquiry_sync'), 10, 2);
        
        // Hook into workflow manager submission
        add_action('edubot_enquiry_submitted', array(__CLASS__, 'sync_after_submission'), 10, 1);
        
        // Schedule async sync for failed enquiries
        add_action('edubot_mcb_retry_sync', array(__CLASS__, 'handle_retry_sync'), 10, 2);
        
        // Add async retry scheduler
        if (!wp_next_scheduled('edubot_mcb_retry_failed')) {
            wp_schedule_event(time(), 'hourly', 'edubot_mcb_retry_failed');
        }
        add_action('edubot_mcb_retry_failed', array(__CLASS__, 'retry_failed_syncs'));
    }
    
    /**
     * Handle enquiry sync after submission
     * 
     * Called when enquiry is saved via process_enquiry_submission
     * 
     * @param int $enquiry_id - The enquiry ID that was saved
     * @param array $enquiry_data - The enquiry data
     */
    public static function handle_enquiry_sync($enquiry_id, $enquiry_data) {
        $mcb_service = EduBot_MCB_Service::get_instance();
        
        // Check if auto-sync is enabled
        if (!$mcb_service->is_auto_sync_enabled()) {
            return;
        }
        
        // Check if sync is enabled
        if (!$mcb_service->is_sync_enabled()) {
            return;
        }
        
        // Perform sync (non-blocking)
        $result = $mcb_service->sync_enquiry($enquiry_id);
        
        // Log sync attempt
        error_log(
            'MCB Auto-sync: Enquiry #' . $enquiry_id . ' - ' . 
            ($result['success'] ? 'SUCCESS' : 'FAILED: ' . $result['message'])
        );
    }
    
    /**
     * Handle sync after enquiry submitted via workflow
     * 
     * Called from workflow manager's process_enquiry_submission
     * 
     * @param int $enquiry_id - The enquiry ID
     */
    public static function sync_after_submission($enquiry_id) {
        $mcb_service = EduBot_MCB_Service::get_instance();
        
        if (!$mcb_service->is_auto_sync_enabled() || !$mcb_service->is_sync_enabled()) {
            return;
        }
        
        $result = $mcb_service->sync_enquiry($enquiry_id);
        
        // Store result in transient for admin notification
        if (!$result['success']) {
            set_transient(
                'edubot_mcb_sync_error_' . $enquiry_id,
                $result['message'],
                HOUR_IN_SECONDS
            );
        }
    }
    
    /**
     * Handle manual retry of failed sync
     * 
     * @param int $sync_log_id - Sync log ID
     * @param int $retry_count - Retry count
     */
    public static function handle_retry_sync($sync_log_id, $retry_count = 0) {
        $mcb_service = EduBot_MCB_Service::get_instance();
        $mcb_service->retry_failed_sync($sync_log_id, $retry_count);
    }
    
    /**
     * Retry failed syncs (runs hourly)
     * 
     * Called by WP-Cron hourly to retry failed MCB syncs
     */
    public static function retry_failed_syncs() {
        global $wpdb;
        
        $mcb_service = EduBot_MCB_Service::get_instance();
        
        if (!$mcb_service->is_sync_enabled()) {
            return;
        }
        
        // Get failed syncs that haven't exceeded max retries
        $max_retries = 3;
        $failed = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, enquiry_id, retry_count FROM {$wpdb->prefix}edubot_mcb_sync_log
                 WHERE success = 0 AND retry_count < %d
                 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 ORDER BY created_at DESC
                 LIMIT 10",
                $max_retries
            )
        );
        
        foreach ($failed as $sync_log) {
            $mcb_service->retry_failed_sync($sync_log->id, $sync_log->retry_count);
        }
    }
    
    /**
     * Get sync status for an enquiry
     * 
     * @param int $enquiry_id - Enquiry ID
     * @return array - Status information
     */
    public static function get_enquiry_sync_status($enquiry_id) {
        global $wpdb;
        
        $sync_log = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}edubot_mcb_sync_log 
                 WHERE enquiry_id = %d 
                 ORDER BY created_at DESC 
                 LIMIT 1",
                $enquiry_id
            ),
            ARRAY_A
        );
        
        if (!$sync_log) {
            return array('status' => 'not_synced', 'message' => 'No sync attempt recorded');
        }
        
        return array(
            'status' => $sync_log['success'] ? 'synced' : 'failed',
            'success' => $sync_log['success'],
            'message' => $sync_log['error_message'] ?? 'Synced successfully',
            'sync_time' => $sync_log['created_at'],
            'retry_count' => $sync_log['retry_count'],
            'response' => json_decode($sync_log['response_data'], true)
        );
    }
}

// Initialize on admin or frontend
if (is_admin() || did_action('wp_loaded')) {
    EduBot_MCB_Integration::init();
}
?>
