<?php

/**
 * Handle external API integrations
 */
class EduBot_API_Integrations {

    /**
     * School configuration
     */
    private $school_config;

    /**
     * Constructor
     */
    public function __construct() {
        $this->school_config = new EduBot_School_Config();
    }

    /**
     * Get AI response from OpenAI
     */
    public function get_ai_response($message, $context = '') {
        $api_keys = $this->school_config->get_api_keys();
        
        if (empty($api_keys['openai_key'])) {
            return false;
        }

        $config = $this->school_config->get_config();
        $model = isset($config['chatbot_settings']['ai_model']) ? $config['chatbot_settings']['ai_model'] : 'gpt-3.5-turbo';
        
        $system_prompt = $this->build_system_prompt($context);
        
        $data = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $system_prompt
                ),
                array(
                    'role' => 'user',
                    'content' => $message
                )
            ),
            'max_tokens' => 500,
            'temperature' => 0.7
        );

        $response = $this->make_openai_request($data, $api_keys['openai_key']);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        }

        return false;
    }

    /**
     * Build system prompt for AI
     */
    private function build_system_prompt($context) {
        $config = $this->school_config->get_config();
        $school_name = $config['school_info']['name'];
        $grades = implode(', ', $config['form_settings']['grades']);
        $boards = implode(', ', $config['form_settings']['boards']);
        
        $prompt = "You are an AI admission assistant for {$school_name}. ";
        $prompt .= "Your role is to help prospective parents and students with admission inquiries. ";
        $prompt .= "Available grades: {$grades}. ";
        $prompt .= "Educational boards: {$boards}. ";
        $prompt .= "Be helpful, friendly, and informative. ";
        $prompt .= "If you don't know specific information about the school, politely ask them to contact the school directly. ";
        $prompt .= "Always encourage them to complete the admission application if they seem interested. ";
        $prompt .= "Keep responses concise and helpful. ";
        
        if (!empty($context)) {
            $prompt .= "Additional context: " . $context;
        }

        return $prompt;
    }

    /**
     * Make OpenAI API request
     */
    private function make_openai_request($data, $api_key) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $headers = array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('EduBot OpenAI cURL Error: ' . $error);
            return false;
        }

        if ($http_code !== 200) {
            error_log('EduBot OpenAI HTTP Error: ' . $http_code . ' - ' . $response);
            return false;
        }

        $decoded_response = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('EduBot OpenAI JSON Error: ' . json_last_error_msg());
            return false;
        }

        return $decoded_response;
    }

    /**
     * Send WhatsApp message
     */
    public function send_whatsapp_message($phone, $message) {
        $api_keys = $this->school_config->get_api_keys();
        
        if (empty($api_keys['whatsapp_token']) || empty($api_keys['whatsapp_provider'])) {
            return false;
        }

        switch ($api_keys['whatsapp_provider']) {
            case 'twilio':
                return $this->send_twilio_whatsapp($phone, $message, $api_keys);
                
            case '360dialog':
                return $this->send_360dialog_whatsapp($phone, $message, $api_keys);
                
            case 'wati':
                return $this->send_wati_whatsapp($phone, $message, $api_keys);
                
            default:
                return $this->send_generic_whatsapp($phone, $message, $api_keys);
        }
    }

    /**
     * Send WhatsApp via Twilio
     */
    private function send_twilio_whatsapp($phone, $message, $api_keys) {
        $account_sid = $api_keys['whatsapp_token'];
        $auth_token = isset($api_keys['twilio_auth_token']) ? $api_keys['twilio_auth_token'] : '';
        $from = isset($api_keys['whatsapp_phone_id']) ? $api_keys['whatsapp_phone_id'] : '';

        if (empty($auth_token) || empty($from)) {
            return false;
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
        
        $data = array(
            'From' => 'whatsapp:' . $from,
            'To' => 'whatsapp:' . $phone,
            'Body' => $message
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, $account_sid . ':' . $auth_token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_code === 201;
    }

    /**
     * Send WhatsApp via 360Dialog
     */
    private function send_360dialog_whatsapp($phone, $message, $api_keys) {
        $api_key = $api_keys['whatsapp_token'];
        $url = 'https://waba.360dialog.io/v1/messages';
        
        $data = array(
            'to' => $phone,
            'type' => 'text',
            'text' => array(
                'body' => $message
            )
        );

        $headers = array(
            'D360-API-KEY: ' . $api_key,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_code === 200;
    }

    /**
     * Send email
     */
    public function send_email($to, $subject, $message, $headers = array()) {
        $api_keys = $this->school_config->get_api_keys();
        $email_service = isset($api_keys['email_service']) ? $api_keys['email_service'] : 'smtp';

        switch ($email_service) {
            case 'sendgrid':
                return $this->send_sendgrid_email($to, $subject, $message, $api_keys);
                
            case 'mailgun':
                return $this->send_mailgun_email($to, $subject, $message, $api_keys);
                
            case 'ses':
                return $this->send_ses_email($to, $subject, $message, $api_keys);
                
            default:
                return $this->send_smtp_email($to, $subject, $message, $headers, $api_keys);
        }
    }

    /**
     * Send email via SMTP
     */
    private function send_smtp_email($to, $subject, $message, $headers, $api_keys) {
        // Configure SMTP settings if provided
        if (!empty($api_keys['smtp_host'])) {
            add_action('phpmailer_init', function($phpmailer) use ($api_keys) {
                $phpmailer->isSMTP();
                $phpmailer->Host = $api_keys['smtp_host'];
                $phpmailer->SMTPAuth = true;
                $phpmailer->Port = isset($api_keys['smtp_port']) ? $api_keys['smtp_port'] : 587;
                $phpmailer->Username = $api_keys['smtp_username'];
                $phpmailer->Password = $api_keys['smtp_password'];
                $phpmailer->SMTPSecure = $phpmailer->Port == 465 ? 'ssl' : 'tls';
            });
        }

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Send email via SendGrid
     */
    private function send_sendgrid_email($to, $subject, $message, $api_keys) {
        if (empty($api_keys['email_api_key'])) {
            return false;
        }

        $config = $this->school_config->get_config();
        $from_email = $config['school_info']['contact_info']['email'];
        $from_name = $config['school_info']['name'];

        $data = array(
            'personalizations' => array(
                array(
                    'to' => array(array('email' => $to))
                )
            ),
            'from' => array(
                'email' => $from_email,
                'name' => $from_name
            ),
            'subject' => $subject,
            'content' => array(
                array(
                    'type' => 'text/plain',
                    'value' => $message
                )
            )
        );

        $headers = array(
            'Authorization: Bearer ' . $api_keys['email_api_key'],
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_code === 202;
    }

    /**
     * Test OpenAI connection
     */
    public function test_openai_connection($api_key) {
        $data = array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => 'Hello, this is a test message.'
                )
            ),
            'max_tokens' => 10
        );

        $response = $this->make_openai_request($data, $api_key);
        return $response !== false;
    }

    /**
     * Test WhatsApp connection
     */
    public function test_whatsapp_connection($token, $provider) {
        // This would depend on the specific provider's test endpoint
        // For now, we'll just validate the token format
        return !empty($token) && strlen($token) > 10;
    }

    /**
     * Test email connection
     */
    public function test_email_connection($settings) {
        if ($settings['provider'] === 'smtp') {
            if (empty($settings['host']) || empty($settings['username'])) {
                return false;
            }
            
            // Test SMTP connection
            $smtp = fsockopen($settings['host'], $settings['port'], $errno, $errstr, 10);
            if ($smtp) {
                fclose($smtp);
                return true;
            }
            return false;
        }
        
        // For API-based services, check if API key is provided
        return !empty($settings['api_key']);
    }

    /**
     * Generic WhatsApp sender (for custom APIs)
     */
    private function send_generic_whatsapp($phone, $message, $api_keys) {
        // This method can be customized for other WhatsApp providers
        // or custom API implementations
        return false;
    }

    /**
     * Send SMS
     */
    public function send_sms($phone, $message) {
        $api_keys = $this->school_config->get_api_keys();
        
        if (empty($api_keys['sms_provider']) || empty($api_keys['sms_api_key'])) {
            return false;
        }

        switch ($api_keys['sms_provider']) {
            case 'twilio':
                return $this->send_twilio_sms($phone, $message, $api_keys);
                
            case 'textlocal':
                return $this->send_textlocal_sms($phone, $message, $api_keys);
                
            case 'msg91':
                return $this->send_msg91_sms($phone, $message, $api_keys);
                
            default:
                return false;
        }
    }

    /**
     * Send SMS via Twilio
     */
    private function send_twilio_sms($phone, $message, $api_keys) {
        $account_sid = $api_keys['sms_api_key'];
        $auth_token = isset($api_keys['twilio_auth_token']) ? $api_keys['twilio_auth_token'] : '';
        $from = isset($api_keys['sms_sender_id']) ? $api_keys['sms_sender_id'] : '';

        if (empty($auth_token) || empty($from)) {
            return false;
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
        
        $data = array(
            'From' => $from,
            'To' => $phone,
            'Body' => $message
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, $account_sid . ':' . $auth_token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_code === 201;
    }

    /**
     * Send SMS via TextLocal
     */
    private function send_textlocal_sms($phone, $message, $api_keys) {
        $api_key = $api_keys['sms_api_key'];
        $sender = isset($api_keys['sms_sender_id']) ? $api_keys['sms_sender_id'] : 'SCHOOL';

        $data = array(
            'apikey' => $api_key,
            'numbers' => $phone,
            'message' => $message,
            'sender' => $sender
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.textlocal.in/send/');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $decoded = json_decode($response, true);
            return isset($decoded['status']) && $decoded['status'] === 'success';
        }

        return false;
    }
}
