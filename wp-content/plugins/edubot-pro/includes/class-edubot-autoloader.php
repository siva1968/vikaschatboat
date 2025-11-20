<?php

/**
 * EduBot Pro Autoloader
 * Handles automatic loading of plugin classes
 */
class EduBot_Autoloader {

    /**
     * Class name to file mapping
     */
    private static $class_map = array(
        // Core infrastructure
        'EduBot_Core' => 'includes/class-edubot-core.php',
        'EduBot_Loader' => 'includes/class-edubot-loader.php',
        'EduBot_i18n' => 'includes/class-edubot-i18n.php',
        'EduBot_Activator' => 'includes/class-edubot-activator.php',
        'EduBot_Deactivator' => 'includes/class-edubot-deactivator.php',
        
        // Admin classes
        'EduBot_Admin' => 'admin/class-edubot-admin.php',
        
        // Public classes
        'EduBot_Public' => 'public/class-edubot-public.php',
        
        // Business logic classes
        'EduBot_Database_Manager' => 'includes/class-database-manager.php',
        'EduBot_Security_Manager' => 'includes/class-security-manager.php',
        'EduBot_School_Config' => 'includes/class-school-config.php',
        'EduBot_Chatbot_Engine' => 'includes/class-chatbot-engine.php',
        'EduBot_API_Integrations' => 'includes/class-api-integrations.php',
        'EduBot_Notification_Manager' => 'includes/class-notification-manager.php',
        'EduBot_Branding_Manager' => 'includes/class-branding-manager.php',
        'EduBot_Shortcode' => 'includes/class-edubot-shortcode.php',
        'Edubot_Academic_Config' => 'includes/class-edubot-academic-config.php',
        
        // Analytics classes
        'EduBot_Visitor_Analytics' => 'includes/class-visitor-analytics.php',
        'EduBot_Analytics_AJAX' => 'includes/class-analytics-ajax.php',
        'EduBot_Analytics_Migration' => 'includes/class-analytics-migration.php',
        
        // Migration classes
        'EduBot_Enquiries_Migration' => 'includes/class-enquiries-migration.php',
        
        // Workflow Management (Enhanced v1.2.0)
        'EduBot_Session_Manager' => 'includes/class-edubot-session-manager.php',
        'EduBot_Workflow_Manager' => 'includes/class-edubot-workflow-manager.php',
        
        // WhatsApp Ad Integration (New v1.6.0)
        'EduBot_WhatsApp_Ad_Link_Generator' => 'includes/class-whatsapp-ad-link-generator.php',
        'EduBot_WhatsApp_Session_Manager' => 'includes/class-whatsapp-session-manager.php',
        'EduBot_WhatsApp_Webhook_Receiver' => 'includes/class-whatsapp-webhook-receiver.php',
        
        // Utility classes
        'EduBot_Logger' => 'includes/class-edubot-logger.php',
        'EduBot_Plugin_Validator' => 'includes/class-plugin-validator.php',
        'EduBot_Health_Check' => 'includes/class-edubot-health-check.php',
        'EduBot_Error_Handler' => 'includes/class-edubot-error-handler.php'
    );

    /**
     * Register the autoloader
     */
    public static function register() {
        spl_autoload_register(array(__CLASS__, 'load_class'));
    }

    /**
     * Load a class file with enhanced security
     */
    public static function load_class($class_name) {
        // Only handle EduBot classes with stricter validation
        if (!preg_match('/^EduBot_[A-Za-z_]+$/', $class_name)) {
            return;
        }

        // Check if we have a mapping for this class (whitelist approach)
        if (isset(self::$class_map[$class_name])) {
            $file_path = EDUBOT_PRO_PLUGIN_PATH . self::$class_map[$class_name];
            
            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }

        // Try to autoload based on naming convention (only for whitelisted patterns)
        $allowed_prefixes = array('EduBot_Admin', 'EduBot_Public', 'EduBot_Core');
        $is_allowed = false;
        
        foreach ($allowed_prefixes as $prefix) {
            if (strpos($class_name, $prefix) === 0) {
                $is_allowed = true;
                break;
            }
        }
        
        if (!$is_allowed) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("EduBot Pro: Class '{$class_name}' not in whitelist");
            }
            return;
        }

        $file_name = self::class_name_to_file_name($class_name);
        $possible_paths = array(
            'includes/' . $file_name,
            'admin/' . $file_name,
            'public/' . $file_name
        );

        foreach ($possible_paths as $path) {
            $file_path = EDUBOT_PRO_PLUGIN_PATH . $path;
            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }

        // Log missing class for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("EduBot Pro: Unable to autoload class '{$class_name}'");
        }
    }

    /**
     * Convert class name to file name
     */
    private static function class_name_to_file_name($class_name) {
        // Remove EduBot_ prefix and convert to lowercase with hyphens
        $name = substr($class_name, 7); // Remove 'EduBot_'
        $name = strtolower($name);
        $name = str_replace('_', '-', $name);
        return 'class-edubot-' . $name . '.php';
    }

    /**
     * Check if all required classes can be loaded
     */
    public static function validate_classes() {
        $required_classes = array(
            'EduBot_Core',
            'EduBot_Loader',
            'EduBot_Database_Manager',
            'EduBot_Security_Manager'
        );

        $missing = array();
        foreach ($required_classes as $class) {
            if (!class_exists($class)) {
                self::load_class($class);
                if (!class_exists($class)) {
                    $missing[] = $class;
                }
            }
        }

        return $missing;
    }

    /**
     * Get all available classes
     */
    public static function get_available_classes() {
        $available = array();
        foreach (self::$class_map as $class => $file) {
            $file_path = EDUBOT_PRO_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                $available[] = $class;
            }
        }
        return $available;
    }
}
