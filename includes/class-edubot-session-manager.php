<?php
/**
 * Enhanced Session Manager for EduBot Pro
 * Fixes critical workflow breaking issues with reliable session management
 */

class EduBot_Session_Manager {
    
    private static $instance = null;
    private $session_prefix = 'edubot_session_';
    private $session_timeout = 24 * HOUR_IN_SECONDS; // 24 hours
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get session data with fallback recovery
     */
    public function get_session($session_id) {
        if (empty($session_id)) {
            return null;
        }
        
        // Primary: Try transient storage (fast, reliable)
        $transient_key = $this->session_prefix . $session_id;
        $session_data = get_transient($transient_key);
        
        if ($session_data !== false) {
            $this->debug_log("Session {$session_id} retrieved from transient");
            return $session_data;
        }
        
        // Fallback: Try options storage (for recovery)
        $all_sessions = get_option('edubot_conversation_sessions', array());
        if (isset($all_sessions[$session_id])) {
            $session_data = $all_sessions[$session_id];
            
            // Migrate to transient for future requests
            set_transient($transient_key, $session_data, $this->session_timeout);
            
            $this->debug_log("Session {$session_id} recovered from options and migrated to transient");
            return $session_data;
        }
        
        $this->debug_log("No session found for {$session_id}");
        return null;
    }
    
    /**
     * Save session data reliably
     */
    public function save_session($session_id, $session_data) {
        if (empty($session_id) || empty($session_data)) {
            return false;
        }
        
        $transient_key = $this->session_prefix . $session_id;
        
        // Add metadata
        $session_data['last_updated'] = current_time('mysql');
        $session_data['session_id'] = $session_id;
        
        // Primary storage: Transients (reliable, automatic cleanup)
        $saved = set_transient($transient_key, $session_data, $this->session_timeout);
        
        if ($saved) {
            $this->debug_log("Session {$session_id} saved to transient successfully");
            
            // Backup storage: Options (for recovery)
            $this->backup_session_to_options($session_id, $session_data);
            return true;
        } else {
            $this->debug_log("Failed to save session {$session_id} to transient");
            return false;
        }
    }
    
    /**
     * Update specific session data safely
     */
    public function update_session_data($session_id, $key, $value) {
        $session_data = $this->get_session($session_id);
        
        if (!$session_data) {
            // Initialize new session if needed
            $session_data = $this->init_session($session_id, 'admission');
        }
        
        // Ensure data array exists
        if (!isset($session_data['data'])) {
            $session_data['data'] = array();
        }
        
        // Update the data
        $session_data['data'][$key] = $value;
        
        // Save back to storage
        $result = $this->save_session($session_id, $session_data);
        
        $this->debug_log("Updated {$key} = {$value} in session {$session_id}: " . ($result ? 'SUCCESS' : 'FAILED'));
        return $result;
    }
    
    /**
     * Initialize new session
     */
    public function init_session($session_id, $flow_type = 'admission') {
        $session_data = array(
            'session_id' => $session_id,
            'flow_type' => $flow_type,
            'started' => current_time('mysql'),
            'step' => 'start',
            'data' => array(),
            'version' => '2.0' // Track session format version
        );
        
        $this->save_session($session_id, $session_data);
        $this->debug_log("Initialized new session {$session_id} with flow type {$flow_type}");
        
        return $session_data;
    }
    
    /**
     * Check if session is completed
     */
    public function is_session_completed($session_id) {
        $session_data = $this->get_session($session_id);
        
        if (!$session_data) {
            return true; // No session means it's either completed or never started
        }
        
        $step = $session_data['step'] ?? '';
        return in_array($step, array('completed', 'submitted', 'finished'));
    }
    
    /**
     * Clear session data
     */
    public function clear_session($session_id) {
        $transient_key = $this->session_prefix . $session_id;
        
        // Clear from transient storage
        delete_transient($transient_key);
        
        // Clear from options backup
        $all_sessions = get_option('edubot_conversation_sessions', array());
        if (isset($all_sessions[$session_id])) {
            unset($all_sessions[$session_id]);
            update_option('edubot_conversation_sessions', $all_sessions);
        }
        
        $this->debug_log("Cleared session {$session_id} from all storage");
    }
    
    /**
     * Get all active sessions (for debugging)
     */
    public function get_active_sessions() {
        $all_sessions = get_option('edubot_conversation_sessions', array());
        $active_sessions = array();
        
        foreach ($all_sessions as $session_id => $session_data) {
            if (!$this->is_session_completed($session_id)) {
                $active_sessions[$session_id] = $session_data;
            }
        }
        
        return $active_sessions;
    }
    
    /**
     * Backup session to options storage
     */
    private function backup_session_to_options($session_id, $session_data) {
        try {
            $all_sessions = get_option('edubot_conversation_sessions', array());
            $all_sessions[$session_id] = $session_data;
            
            // Clean old sessions (older than 48 hours)
            $cutoff_time = strtotime('-48 hours');
            foreach ($all_sessions as $sid => $data) {
                if (isset($data['started']) && strtotime($data['started']) < $cutoff_time) {
                    unset($all_sessions[$sid]);
                }
            }
            
            update_option('edubot_conversation_sessions', $all_sessions);
            $this->debug_log("Backed up session {$session_id} to options");
            
        } catch (Exception $e) {
            $this->debug_log("Failed to backup session {$session_id}: " . $e->getMessage());
        }
    }
    
    /**
     * Debug logging
     */
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('EduBot Session Manager: ' . $message);
        }
    }
    
    /**
     * Cleanup old sessions (run periodically)
     */
    public function cleanup_old_sessions() {
        // This will be called by WordPress cron or manually
        $all_sessions = get_option('edubot_conversation_sessions', array());
        $cleaned = 0;
        
        $cutoff_time = strtotime('-48 hours');
        foreach ($all_sessions as $session_id => $session_data) {
            if (isset($session_data['started']) && strtotime($session_data['started']) < $cutoff_time) {
                unset($all_sessions[$session_id]);
                
                // Also clear transient if it exists
                delete_transient($this->session_prefix . $session_id);
                $cleaned++;
            }
        }
        
        if ($cleaned > 0) {
            update_option('edubot_conversation_sessions', $all_sessions);
            $this->debug_log("Cleaned up {$cleaned} old sessions");
        }
        
        return $cleaned;
    }
}
