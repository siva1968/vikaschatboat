<?php

/**
 * Handle notifications (email, WhatsApp, SMS)
 */
class EduBot_Notification_Manager {

    /**
     * School configuration
     */
    private $school_config;

    /**
     * API integrations
     */
    private $api_integrations;

    /**
     * Database manager
     */
    private $database_manager;

    /**
     * Security manager
     */
    private $security_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->school_config = EduBot_School_Config::getInstance();
        $this->api_integrations = new EduBot_API_Integrations();
        $this->database_manager = new EduBot_Database_Manager();
        $this->security_manager = new EduBot_Security_Manager();
    }

    /**
     * Send all application notifications with security validation
     */
    public function send_application_notifications($application_id, $user_data) {
        // Validate inputs
        if (empty($application_id) || !is_array($user_data)) {
            error_log('EduBot Notification: Invalid parameters for sending notifications');
            return false;
        }

        // Validate application ID
        $application_id = absint($application_id);
        if ($application_id <= 0) {
            error_log('EduBot Notification: Invalid application ID');
            return false;
        }

        // Rate limiting for notifications
        if (!$this->security_manager->check_rate_limit('notifications_' . $application_id, 5, 3600)) {
            error_log('EduBot Notification: Rate limit exceeded for application ' . $application_id);
            return false;
        }

        // Sanitize user data
        $user_data = $this->sanitize_user_data($user_data);
        
        $config = $this->school_config->get_config();
        $notification_settings = isset($config['notification_settings']) ? $config['notification_settings'] : array();

        $results = array();

        try {
            // Send parent notifications
            if (!empty($notification_settings['parent_notifications'])) {
                $results['parent'] = $this->send_parent_notifications($application_id, $user_data);
            }

            // Send admin notifications
            if (!empty($notification_settings['admin_notifications'])) {
                $results['admin'] = $this->send_admin_notifications($application_id, $user_data);
            }

            return $results;
        } catch (Exception $e) {
            error_log('EduBot Notification Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sanitize user data for notifications
     */
    private function sanitize_user_data($user_data) {
        $sanitized = array();
        
        foreach ($user_data as $key => $value) {
            $key = sanitize_key($key);
            
            if (is_string($value)) {
                // Special handling for email
                if ($key === 'email') {
                    $sanitized[$key] = sanitize_email($value);
                } else {
                    $sanitized[$key] = sanitize_text_field($value);
                }
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Send notifications to parents
     */
    private function send_parent_notifications($application_id, $user_data) {
        $config = $this->school_config->get_config();
        $notification_settings = $config['notification_settings'];

        // Get application details
        $application = $this->database_manager->get_application($application_id);
        if (!$application) {
            return false;
        }

        // Prepare message variables
        $variables = array_merge($user_data, array(
            'application_number' => $application['application_number'],
            'submission_date' => date('F j, Y', strtotime($application['created_at']))
        ));

        // Send email notification
        if ($notification_settings['email_enabled'] && !empty($user_data['email'])) {
            $this->send_parent_email($user_data['email'], $variables);
            $this->database_manager->update_notification_status($application_id, 'email');
        }

        // Send WhatsApp notification
        if ($notification_settings['whatsapp_enabled'] && !empty($user_data['phone'])) {
            $this->send_parent_whatsapp($user_data['phone'], $variables);
            $this->database_manager->update_notification_status($application_id, 'whatsapp');
        }

        // Send SMS notification
        if ($notification_settings['sms_enabled'] && !empty($user_data['phone'])) {
            $this->send_parent_sms($user_data['phone'], $variables);
            $this->database_manager->update_notification_status($application_id, 'sms');
        }
    }

    /**
     * Send email to parent with security validation
     */
    private function send_parent_email($email, $variables) {
        // Validate email address
        if (!is_email($email)) {
            error_log('EduBot Notification: Invalid email address for parent notification');
            return false;
        }

        // Security check for email content
        foreach ($variables as $key => $value) {
            if (is_string($value) && $this->security_manager->is_malicious_content($value)) {
                error_log('EduBot Notification: Malicious content detected in email variables');
                return false;
            }
        }

        $subject = $this->school_config->get_message('email_subject', $variables);
        $message = $this->school_config->get_message('email_template', $variables);
        
        // Sanitize email content
        $subject = sanitize_text_field($subject);
        $message = wp_kses_post($message);
        
        $config = $this->school_config->get_config();
        $from_email = isset($config['school_info']['contact_info']['email']) ? 
            sanitize_email($config['school_info']['contact_info']['email']) : 
            get_option('admin_email');
        $from_name = isset($config['school_info']['name']) ? 
            sanitize_text_field($config['school_info']['name']) : 
            get_bloginfo('name');

        // Validate from email
        if (!is_email($from_email)) {
            $from_email = get_option('admin_email');
        }

        $headers = array(
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $from_email
        );

        return $this->api_integrations->send_email($email, $subject, $message, $headers);
    }

    /**
     * Send WhatsApp to parent with security validation
     */
    private function send_parent_whatsapp($phone, $variables) {
        // Validate phone number format
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            error_log('EduBot Notification: Invalid phone number for WhatsApp notification');
            return false;
        }

        // Security check for message content
        foreach ($variables as $key => $value) {
            if (is_string($value) && $this->security_manager->is_malicious_content($value)) {
                error_log('EduBot Notification: Malicious content detected in WhatsApp variables');
                return false;
            }
        }

        $message = $this->school_config->get_message('whatsapp_template', $variables);
        
        // Sanitize message content
        $message = sanitize_text_field($message);
        
        // Validate message length (WhatsApp limit)
        if (strlen($message) > 4096) {
            $message = substr($message, 0, 4096);
        }

        return $this->api_integrations->send_whatsapp_message($phone, $message);
    }

    /**
     * Send SMS to parent with security validation
     */
    private function send_parent_sms($phone, $variables) {
        // Validate phone number format
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            error_log('EduBot Notification: Invalid phone number for SMS notification');
            return false;
        }

        // Security check for variables
        foreach ($variables as $key => $value) {
            if (is_string($value) && $this->security_manager->is_malicious_content($value)) {
                error_log('EduBot Notification: Malicious content detected in SMS variables');
                return false;
            }
        }

        // Build SMS message with length limit
        $message = sprintf(
            "Dear %s, your admission application for %s has been received. Application #: %s. We'll contact you within 24-48 hours. - %s",
            sanitize_text_field($variables['parent_name']),
            sanitize_text_field($variables['student_name']),
            sanitize_text_field($variables['application_number']),
            sanitize_text_field($variables['school_name'])
        );

        // SMS length limit (160 characters per message, safe limit 140)
        if (strlen($message) > 140) {
            $message = substr($message, 0, 137) . '...';
        }

        return $this->api_integrations->send_sms($phone, $message);
    }

    /**
     * Send notifications to admin with security validation
     */
    private function send_admin_notifications($application_id, $user_data) {
        $config = $this->school_config->get_config();
        $application = $this->database_manager->get_application($application_id);

        if (!$application) {
            error_log('EduBot Notification: Application not found for admin notification');
            return false;
        }

        $admin_email = isset($config['school_info']['contact_info']['email']) ? 
            $config['school_info']['contact_info']['email'] : '';
        
        // Fallback to WordPress admin email
        if (empty($admin_email) || !is_email($admin_email)) {
            $admin_email = get_option('admin_email');
        }

        if (!is_email($admin_email)) {
            error_log('EduBot Notification: No valid admin email found');
            return false;
        }

        $school_name = isset($config['school_info']['name']) ? 
            sanitize_text_field($config['school_info']['name']) : 
            get_bloginfo('name');

        $subject = sprintf('[%s] New Admission Application - %s', 
            $school_name, 
            sanitize_text_field($application['application_number'])
        );

        $message = $this->build_admin_notification_message($application, $user_data);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $school_name . ' System <' . $admin_email . '>'
        );

        return $this->api_integrations->send_email($admin_email, $subject, $message, $headers);
    }

    /**
     * Build admin notification message with security
     */
    private function build_admin_notification_message($application, $user_data) {
        $config = $this->school_config->get_config();
        $school_name = isset($config['school_info']['name']) ? 
            sanitize_text_field($config['school_info']['name']) : 
            get_bloginfo('name');
        
        $message = '<h2>New Admission Application</h2>';
        $message .= '<p><strong>Application Number:</strong> ' . esc_html($application['application_number']) . '</p>';
        $message .= '<p><strong>Submitted:</strong> ' . esc_html(date('F j, Y g:i A', strtotime($application['created_at']))) . '</p>';
        $message .= '<p><strong>Source:</strong> ' . esc_html(ucfirst($application['source'])) . '</p>';
        
        $message .= '<h3>Student Information</h3>';
        $message .= '<ul>';
        $message .= '<li><strong>Name:</strong> ' . (isset($user_data['student_name']) ? esc_html($user_data['student_name']) : 'N/A') . '</li>';
        $message .= '<li><strong>Grade:</strong> ' . (isset($user_data['grade']) ? esc_html($user_data['grade']) : 'N/A') . '</li>';
        $message .= '<li><strong>Date of Birth:</strong> ' . (isset($user_data['date_of_birth']) ? esc_html($user_data['date_of_birth']) : 'N/A') . '</li>';
        $message .= '<li><strong>Gender:</strong> ' . (isset($user_data['gender']) ? esc_html($user_data['gender']) : 'N/A') . '</li>';
        $message .= '<li><strong>Academic Year:</strong> ' . (isset($user_data['academic_year']) ? esc_html($user_data['academic_year']) : 'N/A') . '</li>';
        $message .= '<li><strong>Educational Board:</strong> ' . (isset($user_data['educational_board']) ? esc_html($user_data['educational_board']) : 'N/A') . '</li>';
        $message .= '</ul>';
        
        $message .= '<h3>Parent/Guardian Information</h3>';
        $message .= '<ul>';
        $message .= '<li><strong>Name:</strong> ' . (isset($user_data['parent_name']) ? esc_html($user_data['parent_name']) : 'N/A') . '</li>';
        $message .= '<li><strong>Phone:</strong> ' . (isset($user_data['phone']) ? esc_html($user_data['phone']) : 'N/A') . '</li>';
        $message .= '<li><strong>Email:</strong> ' . (isset($user_data['email']) ? esc_html($user_data['email']) : 'N/A') . '</li>';
        $message .= '<li><strong>Address:</strong> ' . (isset($user_data['address']) ? esc_html($user_data['address']) : 'N/A') . '</li>';
        $message .= '</ul>';

        // Add additional information if available
        if (!empty($user_data['previous_school']) || !empty($user_data['transfer_reason']) || !empty($user_data['special_requirements'])) {
            $message .= '<h3>Additional Information</h3>';
            $message .= '<ul>';
            
            if (!empty($user_data['previous_school'])) {
                $message .= '<li><strong>Previous School:</strong> ' . esc_html($user_data['previous_school']) . '</li>';
            }
            
            if (!empty($user_data['transfer_reason'])) {
                $message .= '<li><strong>Transfer Reason:</strong> ' . esc_html($user_data['transfer_reason']) . '</li>';
            }
            
            if (!empty($user_data['special_requirements'])) {
                $message .= '<li><strong>Special Requirements:</strong> ' . esc_html($user_data['special_requirements']) . '</li>';
            }
            
            $message .= '</ul>';
        }

        // Add custom fields if any
        $custom_fields_data = array();
        foreach ($user_data as $key => $value) {
            if (strpos($key, 'custom_') === 0 && !empty($value)) {
                $field_label = ucwords(str_replace(array('custom_', '_'), array('', ' '), $key));
                $custom_fields_data[$field_label] = $value;
            }
        }

        if (!empty($custom_fields_data)) {
            $message .= '<h3>Custom Fields</h3>';
            $message .= '<ul>';
            foreach ($custom_fields_data as $label => $value) {
                $message .= '<li><strong>' . esc_html($label) . ':</strong> ' . esc_html($value) . '</li>';
            }
            $message .= '</ul>';
        }

        // Add action buttons/links if admin panel is available
        $admin_url = admin_url('admin.php?page=edubot-pro-applications&application_id=' . $application['id']);
        $message .= '<h3>Actions</h3>';
        $message .= '<p><a href="' . esc_url($admin_url) . '" style="background-color: #0073aa; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;">View Application in Admin Panel</a></p>';
        
        $message .= '<hr>';
        $message .= '<p><small>This is an automated notification from ' . esc_html($school_name) . ' admission system.</small></p>';

        return $message;
    }

    /**
     * Send follow-up notifications with enhanced security
     */
    public function send_follow_up_notifications() {
        // Get applications that need follow-up
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';
        $site_id = get_current_blog_id();

        $config = $this->school_config->get_config();
        $follow_up_delay = isset($config['automation_settings']['follow_up_delay']) ? 
            absint($config['automation_settings']['follow_up_delay']) : 24;

        // Validate follow-up delay (between 1 and 168 hours)
        if ($follow_up_delay < 1 || $follow_up_delay > 168) {
            $follow_up_delay = 24;
        }

        $applications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE site_id = %d 
             AND status = 'pending' 
             AND follow_up_scheduled IS NULL 
             AND created_at < DATE_SUB(NOW(), INTERVAL %d HOUR)
             LIMIT 50",
            $site_id, $follow_up_delay
        ), ARRAY_A);

        if (empty($applications)) {
            return true;
        }

        foreach ($applications as $application) {
            try {
                $this->send_follow_up_notification($application);
                
                // Mark follow-up as sent
                $wpdb->update(
                    $table,
                    array('follow_up_scheduled' => current_time('mysql')),
                    array('id' => $application['id']),
                    array('%s'),
                    array('%d')
                );
            } catch (Exception $e) {
                error_log('EduBot Follow-up Error: ' . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Send individual follow-up notification with security validation
     */
    private function send_follow_up_notification($application) {
        $user_data = json_decode($application['student_data'], true);
        
        if (!is_array($user_data)) {
            error_log('EduBot: Invalid user data for follow-up notification');
            return false;
        }

        $config = $this->school_config->get_config();

        if (empty($user_data['email']) && empty($user_data['phone'])) {
            return false;
        }

        // Sanitize data
        $user_data = $this->sanitize_user_data($user_data);

        $variables = array_merge($user_data, array(
            'application_number' => sanitize_text_field($application['application_number']),
            'school_name' => isset($config['school_info']['name']) ? 
                sanitize_text_field($config['school_info']['name']) : 
                get_bloginfo('name')
        ));

        $follow_up_message = sprintf(
            "Dear %s, This is a follow-up regarding your admission application (#%s) for %s at %s. Our admissions team will contact you soon. If you have any questions, please feel free to contact us. Thank you for your interest!",
            $variables['parent_name'],
            $variables['application_number'],
            $variables['student_name'],
            $variables['school_name']
        );

        // Send email follow-up
        if (!empty($user_data['email']) && is_email($user_data['email'])) {
            $subject = sprintf('[%s] Follow-up: Admission Application %s', 
                $variables['school_name'], 
                $variables['application_number']
            );

            $this->api_integrations->send_email($user_data['email'], $subject, $follow_up_message);
        }

        // Send WhatsApp follow-up if enabled
        if (!empty($config['notification_settings']['whatsapp_enabled']) && 
            !empty($user_data['phone'])) {
            $this->api_integrations->send_whatsapp_message($user_data['phone'], $follow_up_message);
        }

        return true;
    }

    /**
     * Test notification sending with security validation
     */
    public function test_notification($type, $recipient, $test_data = array()) {
        // Validate inputs
        $allowed_types = array('email', 'whatsapp', 'sms');
        if (!in_array($type, $allowed_types)) {
            return false;
        }

        // Validate recipient based on type
        if ($type === 'email' && !is_email($recipient)) {
            return false;
        }

        if (in_array($type, array('whatsapp', 'sms'))) {
            $phone = preg_replace('/[^0-9+]/', '', $recipient);
            if (strlen($phone) < 10 || strlen($phone) > 15) {
                return false;
            }
            $recipient = $phone;
        }

        $config = $this->school_config->get_config();
        $school_name = isset($config['school_info']['name']) ? 
            sanitize_text_field($config['school_info']['name']) : 
            get_bloginfo('name');

        switch ($type) {
            case 'email':
                $subject = 'Test Email from ' . $school_name;
                $message = 'This is a test email to verify email configuration is working correctly.';
                return $this->api_integrations->send_email($recipient, $subject, $message);

            case 'whatsapp':
                $message = 'Test message from ' . $school_name . '. WhatsApp integration is working correctly.';
                return $this->api_integrations->send_whatsapp_message($recipient, $message);

            case 'sms':
                $message = 'Test SMS from ' . $school_name . '. SMS integration is working correctly.';
                return $this->api_integrations->send_sms($recipient, $message);

            default:
                return false;
        }
    }

    /**
     * Send scheduled follow-up notifications (cron callback)
     */
    public static function send_scheduled_followups() {
        // Create instance to access non-static methods
        $notification_manager = new self();
        
        try {
            $result = $notification_manager->send_follow_up_notifications();
            error_log("EduBot Pro: Follow-up notifications processed: " . ($result ? 'success' : 'no pending notifications'));
            return $result;
        } catch (Exception $e) {
            error_log("EduBot Pro: Follow-up notifications failed: " . $e->getMessage());
            return false;
        }
    }
}
