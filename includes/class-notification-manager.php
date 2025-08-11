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
     * Constructor
     */
    public function __construct() {
        $this->school_config = new EduBot_School_Config();
        $this->api_integrations = new EduBot_API_Integrations();
        $this->database_manager = new EduBot_Database_Manager();
    }

    /**
     * Send all application notifications
     */
    public function send_application_notifications($application_id, $user_data) {
        $config = $this->school_config->get_config();
        $notification_settings = $config['notification_settings'];

        // Send parent notifications
        if ($notification_settings['parent_notifications']) {
            $this->send_parent_notifications($application_id, $user_data);
        }

        // Send admin notifications
        if ($notification_settings['admin_notifications']) {
            $this->send_admin_notifications($application_id, $user_data);
        }
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
     * Send email to parent
     */
    private function send_parent_email($email, $variables) {
        $subject = $this->school_config->get_message('email_subject', $variables);
        $message = $this->school_config->get_message('email_template', $variables);
        
        $config = $this->school_config->get_config();
        $from_email = $config['school_info']['contact_info']['email'];
        $from_name = $config['school_info']['name'];

        $headers = array(
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Content-Type: text/plain; charset=UTF-8'
        );

        return $this->api_integrations->send_email($email, $subject, $message, $headers);
    }

    /**
     * Send WhatsApp to parent
     */
    private function send_parent_whatsapp($phone, $variables) {
        $message = $this->school_config->get_message('whatsapp_template', $variables);
        return $this->api_integrations->send_whatsapp_message($phone, $message);
    }

    /**
     * Send SMS to parent
     */
    private function send_parent_sms($phone, $variables) {
        // Use a shorter version of the message for SMS
        $message = sprintf(
            "Dear %s, your admission application for %s has been received. Application #: %s. We'll contact you within 24-48 hours. - %s",
            $variables['parent_name'],
            $variables['student_name'],
            $variables['application_number'],
            $variables['school_name']
        );

        return $this->api_integrations->send_sms($phone, $message);
    }

    /**
     * Send notifications to admin
     */
    private function send_admin_notifications($application_id, $user_data) {
        $config = $this->school_config->get_config();
        $application = $this->database_manager->get_application($application_id);

        $admin_email = $config['school_info']['contact_info']['email'];
        $admin_emails = get_option('admin_email');
        
        // Use admin email from WordPress if school email not set
        if (empty($admin_email)) {
            $admin_email = $admin_emails;
        }

        if (!empty($admin_email)) {
            $subject = sprintf('[%s] New Admission Application - %s', 
                $config['school_info']['name'], 
                $application['application_number']
            );

            $message = $this->build_admin_notification_message($application, $user_data);

            $headers = array(
                'Content-Type: text/html; charset=UTF-8'
            );

            $this->api_integrations->send_email($admin_email, $subject, $message, $headers);
        }
    }

    /**
     * Build admin notification message
     */
    private function build_admin_notification_message($application, $user_data) {
        $config = $this->school_config->get_config();
        
        $message = '<h2>New Admission Application</h2>';
        $message .= '<p><strong>Application Number:</strong> ' . $application['application_number'] . '</p>';
        $message .= '<p><strong>Submitted:</strong> ' . date('F j, Y g:i A', strtotime($application['created_at'])) . '</p>';
        $message .= '<p><strong>Source:</strong> ' . ucfirst($application['source']) . '</p>';
        
        $message .= '<h3>Student Information</h3>';
        $message .= '<ul>';
        $message .= '<li><strong>Name:</strong> ' . (isset($user_data['student_name']) ? $user_data['student_name'] : 'N/A') . '</li>';
        $message .= '<li><strong>Grade:</strong> ' . (isset($user_data['grade']) ? $user_data['grade'] : 'N/A') . '</li>';
        $message .= '<li><strong>Date of Birth:</strong> ' . (isset($user_data['date_of_birth']) ? $user_data['date_of_birth'] : 'N/A') . '</li>';
        $message .= '<li><strong>Academic Year:</strong> ' . (isset($user_data['academic_year']) ? $user_data['academic_year'] : 'N/A') . '</li>';
        $message .= '</ul>';
        
        $message .= '<h3>Parent/Guardian Information</h3>';
        $message .= '<ul>';
        $message .= '<li><strong>Name:</strong> ' . (isset($user_data['parent_name']) ? $user_data['parent_name'] : 'N/A') . '</li>';
        $message .= '<li><strong>Phone:</strong> ' . (isset($user_data['phone']) ? $user_data['phone'] : 'N/A') . '</li>';
        $message .= '<li><strong>Email:</strong> ' . (isset($user_data['email']) ? $user_data['email'] : 'N/A') . '</li>';
        $message .= '<li><strong>Address:</strong> ' . (isset($user_data['address']) ? $user_data['address'] : 'N/A') . '</li>';
        $message .= '</ul>';

        // Add custom fields if any
        $custom_fields_data = array();
        foreach ($user_data as $key => $value) {
            if (strpos($key, 'custom_') === 0 && !empty($value)) {
                $field_label = ucwords(str_replace(array('custom_', '_'), array('', ' '), $key));
                $custom_fields_data[$field_label] = $value;
            }
        }

        if (!empty($custom_fields_data)) {
            $message .= '<h3>Additional Information</h3>';
            $message .= '<ul>';
            foreach ($custom_fields_data as $label => $value) {
                $message .= '<li><strong>' . $label . ':</strong> ' . $value . '</li>';
            }
            $message .= '</ul>';
        }

        // Add admin panel link
        $admin_url = admin_url('admin.php?page=edubot-applications&application=' . $application['id']);
        $message .= '<p><a href="' . $admin_url . '" style="background-color: #0073aa; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;">View Application in Admin Panel</a></p>';

        return $message;
    }

    /**
     * Send follow-up notifications
     */
    public function send_follow_up_notifications() {
        // Get applications that need follow-up
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_applications';
        $site_id = get_current_blog_id();

        $config = $this->school_config->get_config();
        $follow_up_delay = isset($config['automation_settings']['follow_up_delay']) ? $config['automation_settings']['follow_up_delay'] : 24;

        $applications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE site_id = %d 
             AND status = 'pending' 
             AND follow_up_scheduled IS NULL 
             AND created_at < DATE_SUB(NOW(), INTERVAL %d HOUR)",
            $site_id, $follow_up_delay
        ), ARRAY_A);

        foreach ($applications as $application) {
            $this->send_follow_up_notification($application);
            
            // Mark follow-up as sent
            $wpdb->update(
                $table,
                array('follow_up_scheduled' => current_time('mysql')),
                array('id' => $application['id']),
                array('%s'),
                array('%d')
            );
        }
    }

    /**
     * Send individual follow-up notification
     */
    private function send_follow_up_notification($application) {
        $user_data = json_decode($application['student_data'], true);
        $config = $this->school_config->get_config();

        if (empty($user_data['email']) && empty($user_data['phone'])) {
            return false;
        }

        $variables = array_merge($user_data, array(
            'application_number' => $application['application_number']
        ));

        $follow_up_message = sprintf(
            "Dear %s, This is a follow-up regarding your admission application (#%s) for %s at %s. Our admissions team will contact you soon. If you have any questions, please feel free to contact us. Thank you for your interest!",
            $user_data['parent_name'],
            $application['application_number'],
            $user_data['student_name'],
            $variables['school_name']
        );

        // Send email follow-up
        if (!empty($user_data['email'])) {
            $subject = sprintf('[%s] Follow-up: Admission Application %s', 
                $variables['school_name'], 
                $application['application_number']
            );

            $this->api_integrations->send_email($user_data['email'], $subject, $follow_up_message);
        }

        // Send WhatsApp follow-up if enabled
        if ($config['notification_settings']['whatsapp_enabled'] && !empty($user_data['phone'])) {
            $this->api_integrations->send_whatsapp_message($user_data['phone'], $follow_up_message);
        }
    }

    /**
     * Send custom notification
     */
    public function send_custom_notification($application_id, $message, $channels = array('email')) {
        $application = $this->database_manager->get_application($application_id);
        if (!$application) {
            return false;
        }

        $user_data = json_decode($application['student_data'], true);
        $config = $this->school_config->get_config();

        $variables = array_merge($user_data, array(
            'application_number' => $application['application_number']
        ));

        // Replace variables in message
        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        $success = true;

        // Send via requested channels
        if (in_array('email', $channels) && !empty($user_data['email'])) {
            $subject = sprintf('[%s] Update on Application %s', 
                $variables['school_name'], 
                $application['application_number']
            );
            
            if (!$this->api_integrations->send_email($user_data['email'], $subject, $message)) {
                $success = false;
            }
        }

        if (in_array('whatsapp', $channels) && !empty($user_data['phone'])) {
            if (!$this->api_integrations->send_whatsapp_message($user_data['phone'], $message)) {
                $success = false;
            }
        }

        if (in_array('sms', $channels) && !empty($user_data['phone'])) {
            if (!$this->api_integrations->send_sms($user_data['phone'], $message)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Schedule reminder notifications
     */
    public function schedule_reminders() {
        if (!wp_next_scheduled('edubot_follow_up_check')) {
            wp_schedule_event(time(), 'hourly', 'edubot_follow_up_check');
        }
    }

    /**
     * Test notification sending
     */
    public function test_notification($type, $recipient, $test_data = array()) {
        $config = $this->school_config->get_config();
        
        $test_variables = array_merge(array(
            'school_name' => $config['school_info']['name'],
            'student_name' => 'Test Student',
            'parent_name' => 'Test Parent',
            'grade' => 'I',
            'application_number' => 'TEST123',
            'phone' => $recipient,
            'email' => $recipient
        ), $test_data);

        switch ($type) {
            case 'email':
                $subject = 'Test Email from ' . $config['school_info']['name'];
                $message = 'This is a test email to verify email configuration is working correctly.';
                return $this->api_integrations->send_email($recipient, $subject, $message);

            case 'whatsapp':
                $message = 'Test message from ' . $config['school_info']['name'] . '. WhatsApp integration is working correctly.';
                return $this->api_integrations->send_whatsapp_message($recipient, $message);

            case 'sms':
                $message = 'Test SMS from ' . $config['school_info']['name'] . '. SMS integration is working correctly.';
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
