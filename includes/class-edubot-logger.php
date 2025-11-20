<?php
/**
 * Simple file-based logger for EduBot Pro
 */
class EduBot_Logger {
    /**
     * Write a log message to wp-content/uploads/edubot-logs/edubot.log
     * @param string|array $message
     * @param string $level
     * @return bool
     */
    public static function log($message, $level = 'INFO') {
        try {
            if (!function_exists('wp_upload_dir')) {
                // Fallback to error_log if WordPress functions unavailable
                error_log("[EduBot Logger] {$level}: " . print_r($message, true));
                return false;
            }

            $upload = wp_upload_dir();
            $dir = trailingslashit($upload['basedir']) . 'edubot-logs';

            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }

            $file = $dir . DIRECTORY_SEPARATOR . 'edubot.log';
            $time = date('Y-m-d H:i:s');
            $payload = is_array($message) || is_object($message) ? print_r($message, true) : $message;
            $line = "[{$time}] [{$level}] " . $payload . PHP_EOL;

            file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
            return true;
        } catch (Exception $e) {
            error_log('EduBot Logger Error: ' . $e->getMessage());
            return false;
        }
    }
}
