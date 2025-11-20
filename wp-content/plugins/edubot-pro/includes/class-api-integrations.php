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
     * Security manager instance
     */
    private $security_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->school_config = EduBot_School_Config::getInstance();
        $this->security_manager = new EduBot_Security_Manager();
    }

    /**
     * Get AI response from OpenAI with security enhancements
     */
    public function get_ai_response($message, $context = '') {
        // Input validation
        if (empty($message) || strlen($message) > 4000) {
            return new WP_Error('invalid_input', 'Message is required and must be under 4000 characters');
        }

        // Security checks
        if ($this->security_manager->is_malicious_content($message)) {
            error_log('EduBot: Malicious content detected in AI request');
            return new WP_Error('security_violation', 'Content violates security policies');
        }

        // Rate limiting
        $user_identifier = $this->get_user_identifier();
        if (!$this->security_manager->check_rate_limit($user_identifier . '_ai_requests', 20, 3600)) {
            return new WP_Error('rate_limit_exceeded', 'Too many AI requests. Please try again later.');
        }

        $api_keys = $this->school_config->get_api_keys();
        
        if (empty($api_keys['openai_key'])) {
            return new WP_Error('missing_api_key', 'OpenAI API key not configured');
        }

        $config = $this->school_config->get_config();
        $model = isset($config['chatbot_settings']['ai_model']) ? 
            sanitize_text_field($config['chatbot_settings']['ai_model']) : 'gpt-3.5-turbo';
        
        // Validate model name
        $allowed_models = array('gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo-preview');
        if (!in_array($model, $allowed_models)) {
            $model = 'gpt-3.5-turbo';
        }
        
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
                    'content' => sanitize_text_field($message)
                )
            ),
            'max_tokens' => 500,
            'temperature' => 0.7
        );

        $response = $this->make_openai_request($data, $api_keys['openai_key']);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            $ai_response = trim($response['choices'][0]['message']['content']);
            
            // Additional security check on AI response
            if ($this->security_manager->is_malicious_content($ai_response)) {
                error_log('EduBot: AI generated potentially malicious content');
                return new WP_Error('ai_security_violation', 'AI response flagged by security filters');
            }
            
            return $ai_response;
        }

        return new WP_Error('ai_error', 'Failed to get AI response');
    }

    /**
     * Get user identifier for rate limiting
     */
    private function get_user_identifier() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
        return md5($ip . $user_agent);
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
     * Make OpenAI API request with enhanced security and logging
     */
    private function make_openai_request($data, $api_key) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        // Log API request attempt
        if (class_exists('EduBot_Admin')) {
            EduBot_Admin::log_api_request_to_db('openai', 'chat_completion', 
                array('model' => $data['model'], 'max_tokens' => $data['max_tokens'] ?? null), 
                null, null, null, null, 0);
        }
        
        // Validate API key format (more flexible for modern OpenAI keys)
        if (!preg_match('/^sk-[a-zA-Z0-9_\-\.]{32,}$/', $api_key)) {
            error_log('EduBot: Invalid OpenAI API key format: ' . substr($api_key, 0, 10) . '...' . substr($api_key, -5));
            
            // Log validation failure
            if (class_exists('EduBot_Admin')) {
                EduBot_Admin::log_api_request_to_db('openai', 'chat_completion', 
                    array('api_key_prefix' => substr($api_key, 0, 6)), 
                    null, false, 400, 'Invalid API key format', 0);
            }
            
            return new WP_Error('invalid_api_key', 'Invalid API key format. Key should start with "sk-" and be at least 35 characters long.');
        }
        
        $headers = array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
            'User-Agent: EduBot-Pro-WordPress-Plugin/1.0'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, wp_json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('EduBot OpenAI cURL Error: ' . $error);
            
            // Log cURL error
            if (class_exists('EduBot_Admin')) {
                EduBot_Admin::log_api_request_to_db('openai', 'chat_completion', 
                    $data, null, false, 0, 'cURL error: ' . $error, 0);
            }
            
            return new WP_Error('curl_error', 'Network error occurred');
        }

        if ($http_code !== 200) {
            error_log('EduBot OpenAI HTTP Error: ' . $http_code . ' - ' . substr($response, 0, 200));
            
            // Log HTTP error
            if (class_exists('EduBot_Admin')) {
                EduBot_Admin::log_api_request_to_db('openai', 'chat_completion', 
                    $data, 
                    array('http_code' => $http_code, 'response_preview' => substr($response, 0, 200)), 
                    false, $http_code, 'HTTP error: ' . $http_code, 0);
            }
            
            return new WP_Error('api_error', 'API request failed with status: ' . $http_code);
        }

        $decoded_response = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('EduBot OpenAI JSON Error: ' . json_last_error_msg());
            
            // Log JSON error
            if (class_exists('EduBot_Admin')) {
                EduBot_Admin::log_api_request_to_db('openai', 'chat_completion', 
                    $data, 
                    array('json_error' => json_last_error_msg(), 'raw_response' => substr($response, 0, 300)), 
                    false, 200, 'JSON decode error: ' . json_last_error_msg(), 0);
            }
            
            return new WP_Error('json_error', 'Invalid JSON response');
        }

        // Log successful response
        if (class_exists('EduBot_Admin')) {
            EduBot_Admin::log_api_request_to_db('openai', 'chat_completion', 
                $data, 
                array(
                    'response_size' => strlen($response),
                    'choices_count' => isset($decoded_response['choices']) ? count($decoded_response['choices']) : 0,
                    'usage' => $decoded_response['usage'] ?? null
                ), 
                true, 200, null, 0);
        }

        return $decoded_response;
    }

    /**
     * Test OpenAI connection with comprehensive validation
     */
    public function test_openai_connection($api_key) {
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => 'OpenAI API key is required'
            );
        }
        
        // Validate API key format
        if (!preg_match('/^sk-[a-zA-Z0-9_\-\.]{32,}$/', $api_key)) {
            return array(
                'success' => false,
                'message' => 'Invalid OpenAI API key format. Key should start with "sk-" and be at least 35 characters long.'
            );
        }
        
        $data = array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => 'Test connection - please respond with "Connection successful"'
                )
            ),
            'max_tokens' => 50,
            'temperature' => 0.1
        );

        $response = $this->make_openai_request($data, $api_key);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'OpenAI API Error: ' . $response->get_error_message()
            );
        }
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'message' => 'OpenAI connection successful! Response: ' . trim($response['choices'][0]['message']['content'])
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Invalid response from OpenAI API'
        );
    }

    /**
     * Test WhatsApp connection with comprehensive validation
     */
    public function test_whatsapp_connection($token, $provider, $phone_id = '') {
        if (empty($token)) {
            return array(
                'success' => false,
                'message' => 'WhatsApp token is required'
            );
        }
        
        if (empty($provider)) {
            return array(
                'success' => false,
                'message' => 'WhatsApp provider is required'
            );
        }
        
        switch ($provider) {
            case 'meta':
                return $this->test_meta_whatsapp($token, $phone_id);
            case 'twilio':
                return $this->test_twilio_whatsapp($token);
            default:
                return array(
                    'success' => false,
                    'message' => 'Unsupported WhatsApp provider: ' . $provider
                );
        }
    }
    
    /**
     * Test Meta WhatsApp Business API
     */
    private function test_meta_whatsapp($token, $phone_id) {
        if (empty($phone_id)) {
            return array(
                'success' => false,
                'message' => 'Phone ID is required for Meta WhatsApp API'
            );
        }
        
        $url = "https://graph.facebook.com/v17.0/{$phone_id}";
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Network error: ' . $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code === 200 && isset($data['id'])) {
            return array(
                'success' => true,
                'message' => 'Meta WhatsApp API connection successful! Phone number: ' . ($data['display_phone_number'] ?? 'N/A')
            );
        }
        
        $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
        return array(
            'success' => false,
            'message' => 'Meta WhatsApp API error: ' . $error_message
        );
    }
    
    /**
     * Test Twilio WhatsApp API
     */
    private function test_twilio_whatsapp($token) {
        // Extract Account SID and Auth Token from the token (format: SID:TOKEN)
        $credentials = explode(':', $token);
        if (count($credentials) !== 2) {
            return array(
                'success' => false,
                'message' => 'Invalid Twilio token format. Expected: AccountSID:AuthToken'
            );
        }
        
        list($account_sid, $auth_token) = $credentials;
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}.json";
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($account_sid . ':' . $auth_token),
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Network error: ' . $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code === 200 && isset($data['sid'])) {
            return array(
                'success' => true,
                'message' => 'Twilio WhatsApp API connection successful! Account: ' . ($data['friendly_name'] ?? $data['sid'])
            );
        }
        
        $error_message = isset($data['message']) ? $data['message'] : 'Authentication failed';
        return array(
            'success' => false,
            'message' => 'Twilio WhatsApp API error: ' . $error_message
        );
    }

    /**
     * Test email connection with comprehensive validation
     */
    public function test_email_connection($settings) {
        if (empty($settings['provider'])) {
            return array(
                'success' => false,
                'message' => 'Email provider is required'
            );
        }
        
        switch ($settings['provider']) {
            case 'smtp':
                return $this->test_smtp_connection($settings);
            case 'sendgrid':
                return $this->test_sendgrid_connection($settings);
            case 'mailgun':
                return $this->test_mailgun_connection($settings);
            case 'zeptomail':
                return $this->test_zeptomail_connection($settings);
            default:
                return array(
                    'success' => false,
                    'message' => 'Unsupported email provider: ' . $settings['provider']
                );
        }
    }
    
    /**
     * Test SMTP connection
     */
    private function test_smtp_connection($settings) {
        if (empty($settings['host']) || empty($settings['username'])) {
            return array(
                'success' => false,
                'message' => 'SMTP host and username are required'
            );
        }
        
        $host = $settings['host'];
        $port = isset($settings['port']) ? intval($settings['port']) : 587;
        
        // Test basic connectivity with multiple ports for ZeptoMail
        $ports_to_try = array($port);
        if ($host === 'smtp.zeptomail.in') {
            $ports_to_try = array_unique(array($port, 587, 25, 2525, 465));
        }
        
        $last_error = '';
        foreach ($ports_to_try as $test_port) {
            $connection = @fsockopen($host, $test_port, $errno, $errstr, 15);
            if ($connection) {
                fclose($connection);
                
                // Update settings with working port if different
                if ($test_port !== $port) {
                    $settings['port'] = $test_port;
                }
                
                // If we have PHPMailer, try a more comprehensive test
                if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                    return $this->test_phpmailer_smtp($settings);
                }
                
                return array(
                    'success' => true,
                    'message' => "SMTP server {$host}:{$test_port} is reachable. Note: Authentication not tested without PHPMailer."
                );
            }
            $last_error = $errstr;
        }
        
        // All ports failed
        $ports_tested = implode(', ', $ports_to_try);
        return array(
            'success' => false,
            'message' => "Cannot connect to SMTP server {$host} on any of these ports: {$ports_tested}. Last error: {$last_error}. This may be due to server firewall restrictions."
        );
    }
    
    /**
     * Test SMTP with PHPMailer
     */
    private function test_phpmailer_smtp($settings) {
        require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
        require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
        require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // For ZeptoMail, try multiple ports
        $ports_to_try = array();
        if ($settings['host'] === 'smtp.zeptomail.in') {
            $ports_to_try = array(587, 465); // Known working ports from test
        } else {
            $ports_to_try = array(isset($settings['port']) ? intval($settings['port']) : 587);
        }
        
        $last_error = '';
        
        foreach ($ports_to_try as $port) {
            try {
                $mail->isSMTP();
                $mail->Host = $settings['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $settings['username'];
                $mail->Password = $settings['password'] ?? '';
                $mail->Port = $port;
                $mail->Timeout = 20; // Reasonable timeout
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                
                // ZeptoMail specific configuration
                if ($settings['host'] === 'smtp.zeptomail.in') {
                    $mail->Encoding = 'base64';
                    if ($port == 465) {
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    } else {
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    }
                    $mail->CharSet = 'UTF-8';
                    $mail->isHTML(true);
                } else {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }
                
                // Test connection without sending
                if ($mail->smtpConnect()) {
                    $mail->smtpClose();
                    return array(
                        'success' => true,
                        'message' => "SMTP connection successful on port {$port}! Authentication verified."
                    );
                } else {
                    $last_error = "Port {$port}: Connection failed";
                    error_log("EduBot: SMTP test failed on port {$port} for {$settings['host']}");
                }
                
            } catch (Exception $e) {
                $last_error = "Port {$port}: " . $e->getMessage();
                error_log("EduBot: SMTP exception on port {$port}: " . $e->getMessage());
                continue; // Try next port
            }
        }
        
        // If we get here, all ports failed
        return array(
            'success' => false,
            'message' => "SMTP connection failed on all tested ports. Last error: {$last_error}. " . 
                        "Please verify your credentials and that your server can connect to {$settings['host']}."
        );
    }
    
    /**
     * Test network connectivity and diagnose issues
     */
    private function diagnose_network_connectivity($host, $port) {
        $diagnostics = array();
        
        // Test DNS resolution
        $ip = gethostbyname($host);
        if ($ip === $host) {
            $diagnostics[] = "❌ DNS resolution failed for {$host}";
        } else {
            $diagnostics[] = "✅ DNS resolved {$host} to {$ip}";
        }
        
        // Test basic connectivity with different timeouts
        $timeouts = array(5, 10, 15, 30);
        foreach ($timeouts as $timeout) {
            $start_time = microtime(true);
            $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
            $end_time = microtime(true);
            $duration = round(($end_time - $start_time) * 1000, 2);
            
            if ($connection) {
                fclose($connection);
                $diagnostics[] = "✅ Connected to {$host}:{$port} in {$duration}ms (timeout: {$timeout}s)";
                break;
            } else {
                $diagnostics[] = "❌ Failed to connect in {$timeout}s timeout - {$errstr} ({$errno})";
            }
        }
        
        return $diagnostics;
    }

    /**
     * Test SendGrid API
     */
    private function test_sendgrid_connection($settings) {
        if (empty($settings['api_key'])) {
            return array(
                'success' => false,
                'message' => 'SendGrid API key is required'
            );
        }
        
        $response = wp_remote_get('https://api.sendgrid.com/v3/user/account', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $settings['api_key'],
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Network error: ' . $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code === 200) {
            return array(
                'success' => true,
                'message' => 'SendGrid API connection successful! Account: ' . ($data['username'] ?? 'N/A')
            );
        }
        
        $error_message = isset($data['errors'][0]['message']) ? $data['errors'][0]['message'] : 'Authentication failed';
        return array(
            'success' => false,
            'message' => 'SendGrid API error: ' . $error_message
        );
    }
    
    /**
     * Test Mailgun API
     */
    private function test_mailgun_connection($settings) {
        if (empty($settings['api_key']) || empty($settings['domain'])) {
            return array(
                'success' => false,
                'message' => 'Mailgun API key and domain are required'
            );
        }
        
        $domain = $settings['domain'];
        $response = wp_remote_get("https://api.mailgun.net/v3/{$domain}", array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('api:' . $settings['api_key']),
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Network error: ' . $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code === 200) {
            return array(
                'success' => true,
                'message' => 'Mailgun API connection successful! Domain: ' . $domain
            );
        }
        
        $error_message = isset($data['message']) ? $data['message'] : 'Authentication failed';
        return array(
            'success' => false,
            'message' => 'Mailgun API error: ' . $error_message
        );
    }

    /**
     * Test ZeptoMail API
     */
    private function test_zeptomail_connection($settings) {
        if (empty($settings['api_key'])) {
            return array(
                'success' => false,
                'message' => 'ZeptoMail API key is required'
            );
        }
        
        // Test ZeptoMail API with actual email send (most reliable test)
        $test_data = array(
            'from' => array(
                'address' => !empty($settings['from_address']) ? $settings['from_address'] : 'test@example.com'
            ),
            'to' => array(
                array(
                    'email_address' => array(
                        'address' => !empty($settings['from_address']) ? $settings['from_address'] : 'test@example.com',
                        'name' => 'ZeptoMail Test'
                    )
                )
            ),
            'subject' => 'ZeptoMail API Test - ' . date('Y-m-d H:i:s'),
            'htmlbody' => '<div><b>ZeptoMail API Test Email</b><br>This is a test email to verify API connectivity.<br>Timestamp: ' . date('Y-m-d H:i:s') . '</div>'
        );

        $response = wp_remote_post('https://api.zeptomail.in/v1.1/email', array(
            'headers' => array(
                'accept' => 'application/json',
                'authorization' => 'Zoho-enczapikey ' . $settings['api_key'],
                'content-type' => 'application/json',
                'cache-control' => 'no-cache'
            ),
            'body' => wp_json_encode($test_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Network error: ' . $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // ZeptoMail returns 201 for successful email send
        if ($status_code === 200 || $status_code === 201) {
            $message = 'ZeptoMail API connection successful!';
            if (isset($data['data'][0]['message'])) {
                $message .= ' Response: ' . $data['data'][0]['message'];
            }
            if (isset($data['request_id'])) {
                $message .= ' (Request ID: ' . substr($data['request_id'], -8) . ')';
            }
            return array(
                'success' => true,
                'message' => $message
            );
        } elseif ($status_code === 401) {
            return array(
                'success' => false,
                'message' => 'ZeptoMail API authentication failed. Please check your API key.'
            );
        } else {
            $error_message = 'HTTP ' . $status_code;
            if (isset($data['message'])) {
                $error_message .= ': ' . $data['message'];
            }
            if (isset($data['details'])) {
                $error_message .= ' - Details: ' . print_r($data['details'], true);
            }
            return array(
                'success' => false,
                'message' => 'ZeptoMail API error: ' . $error_message
            );
        }
    }

    /**
     * Send WhatsApp message
     */
    public function send_whatsapp($phone, $message) {
        $start_time = microtime(true);
        $api_keys = $this->school_config->get_api_keys();
        
        if (empty($api_keys['whatsapp_provider']) || empty($api_keys['whatsapp_token'])) {
            // Log missing configuration
            EduBot_Admin::log_api_request_to_db(
                'whatsapp_config_missing',
                'POST',
                'send_whatsapp',
                array('phone' => $phone, 'message_length' => strlen($message)),
                array(),
                400,
                'error',
                'WhatsApp provider or token not configured'
            );
            return false;
        }

        $provider = $api_keys['whatsapp_provider'];
        error_log("EduBot API Integrations: Sending WhatsApp via {$provider} to {$phone}");

        switch ($provider) {
            case 'meta':
                $result = $this->send_meta_whatsapp($phone, $message, $api_keys);
                break;
                
            case 'twilio':
                $result = $this->send_twilio_whatsapp($phone, $message, $api_keys);
                break;
                
            default:
                $result = $this->send_generic_whatsapp($phone, $message, $api_keys);
                break;
        }
        
        $duration = round((microtime(true) - $start_time) * 1000, 2);
        
        // Log the result
        EduBot_Admin::log_api_request_to_db(
            'whatsapp_send',
            'POST',
            $provider . '_whatsapp_api',
            array(
                'phone' => $phone, 
                'provider' => $provider,
                'message_length' => strlen($message),
                'duration_ms' => $duration
            ),
            $result ? array('success' => true, 'result' => $result) : array(),
            $result ? 200 : 500,
            $result ? 'success' : 'error',
            $result ? 'WhatsApp message sent successfully' : 'WhatsApp message send failed'
        );
        
        return $result;
    }

    /**
     * Send Meta WhatsApp Business message (v24.0)
     * Supports interactive messages, media, templates, and enhanced security
     */
    public function send_meta_whatsapp($phone, $message, $api_keys) {
        $phone_id = $api_keys['whatsapp_phone_id'] ?? get_option('edubot_whatsapp_phone_id', '');
        $access_token = $api_keys['whatsapp_token'] ?? get_option('edubot_whatsapp_token', '');
        $app_secret = $api_keys['whatsapp_app_secret'] ?? get_option('edubot_whatsapp_app_secret', '');
        
        if (empty($phone_id) || empty($access_token)) {
            error_log('EduBot WhatsApp Error: Missing phone ID or access token');
            return false;
        }
        
        // Updated to Meta Business API v24.0
        $url = "https://graph.facebook.com/v24.0/{$phone_id}/messages";
        
        // Format phone number (ensure international format)
        $formatted_phone = $this->format_whatsapp_phone($phone);
        if (!$formatted_phone) {
            error_log('EduBot WhatsApp Error: Invalid phone number format: ' . $phone);
            return false;
        }
        
        // Handle different message types (v24.0 supports more formats)
        $data = $this->prepare_whatsapp_message_data($formatted_phone, $message);
        if (!$data) {
            error_log('EduBot WhatsApp Error: Failed to prepare message data');
            return false;
        }

        // Enhanced headers for v24.0 with security features
        $headers = array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json',
            'User-Agent' => 'EduBot-WhatsApp-Client/2.0',
            'X-WhatsApp-Client-Version' => '24.0'
        );
        
        // Add webhook signature if app secret is available (security enhancement)
        if (!empty($app_secret)) {
            $payload = wp_json_encode($data);
            $signature = 'sha256=' . hash_hmac('sha256', $payload, $app_secret);
            $headers['X-Hub-Signature-256'] = $signature;
        }
        
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => wp_json_encode($data),
            'timeout' => 30,
            'sslverify' => true
        ));

        if (is_wp_error($response)) {
            error_log('EduBot WhatsApp Error: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        error_log("EduBot WhatsApp Response: Status {$status_code}, Body: {$response_body}");
        
        if ($status_code === 200) {
            $result = json_decode($response_body, true);
            if (isset($result['messages'][0]['id'])) {
                error_log('EduBot WhatsApp: Message sent successfully, ID: ' . $result['messages'][0]['id']);
                return $result;
            }
        }
        
        // Enhanced error handling for v24.0
        $this->handle_whatsapp_error_response($status_code, $response_body);
        return false;
    }

    /**
     * Format WhatsApp phone number to international format (v24.0 requirement)
     */
    private function format_whatsapp_phone($phone) {
        // Remove all non-digits
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Must start with country code (no leading +)
        if (strlen($phone) < 10) {
            return false;
        }
        
        // If doesn't start with country code, assume India (+91)
        if (!preg_match('/^(1|44|33|49|81|86|91|55|61|7|39|34|31|46|47|45|358|351|420|48|40|30|90|966|971|60|65|852|886|82)/', $phone)) {
            // Default to India if 10 digits
            if (strlen($phone) == 10) {
                $phone = '91' . $phone;
            }
        }
        
        return $phone;
    }

    /**
     * Prepare WhatsApp message data for v24.0 API
     * Supports text, interactive buttons, lists, media, and templates
     */
    private function prepare_whatsapp_message_data($phone, $message) {
        $base_data = array(
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone
        );
        
        // Handle different message types
        if (is_array($message)) {
            return $this->prepare_structured_message($base_data, $message);
        } else {
            // Simple text message with interactive buttons for common responses
            return $this->prepare_smart_text_message($base_data, $message);
        }
    }

    /**
     * Prepare structured message (templates, interactive, media)
     */
    private function prepare_structured_message($base_data, $message) {
        switch ($message['type'] ?? 'text') {
            case 'template':
                return array_merge($base_data, array(
                    'type' => 'template',
                    'template' => $message['template']
                ));
                
            case 'interactive':
                return array_merge($base_data, array(
                    'type' => 'interactive',
                    'interactive' => $message['interactive']
                ));
                
            case 'media':
                return $this->prepare_media_message($base_data, $message);
                
            default:
                return array_merge($base_data, array(
                    'type' => 'text',
                    'text' => array('body' => $message['text'] ?? '')
                ));
        }
    }

    /**
     * Prepare smart text message with contextual quick replies
     */
    private function prepare_smart_text_message($base_data, $message_text) {
        // Check if message suggests interactive elements
        if ($this->should_add_quick_replies($message_text)) {
            return array_merge($base_data, array(
                'type' => 'interactive',
                'interactive' => array(
                    'type' => 'button',
                    'body' => array('text' => $message_text),
                    'action' => array(
                        'buttons' => array(
                            array('type' => 'reply', 'reply' => array('id' => 'more_info', 'title' => 'More Info')),
                            array('type' => 'reply', 'reply' => array('id' => 'talk_human', 'title' => 'Talk to Human')),
                            array('type' => 'reply', 'reply' => array('id' => 'main_menu', 'title' => 'Main Menu'))
                        )
                    )
                )
            ));
        }
        
        // Simple text message
        return array_merge($base_data, array(
            'type' => 'text',
            'text' => array('body' => $message_text)
        ));
    }

    /**
     * Prepare media message (images, documents, audio, video)
     */
    private function prepare_media_message($base_data, $message) {
        $media_type = $message['media_type'] ?? 'document';
        $media_data = array(
            'type' => $media_type,
            $media_type => array(
                'id' => $message['media_id'] ?? '',
                'caption' => $message['caption'] ?? ''
            )
        );
        
        return array_merge($base_data, $media_data);
    }

    /**
     * Determine if message should include quick reply buttons
     */
    private function should_add_quick_replies($message_text) {
        $triggers = array(
            'information', 'details', 'help', 'options', 'choose', 'select',
            'admission', 'fees', 'contact', 'visit', 'schedule', 'application'
        );
        
        $message_lower = strtolower($message_text);
        foreach ($triggers as $trigger) {
            if (strpos($message_lower, $trigger) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Enhanced error handling for Meta Business API v24.0
     */
    private function handle_whatsapp_error_response($status_code, $response_body) {
        $error_data = json_decode($response_body, true);
        
        if (isset($error_data['error'])) {
            $error = $error_data['error'];
            $error_message = "Meta WhatsApp API Error [{$status_code}]: ";
            $error_message .= $error['message'] ?? 'Unknown error';
            
            if (isset($error['error_subcode'])) {
                $error_message .= " (Subcode: {$error['error_subcode']})";
            }
            
            // Log specific error types with solutions
            switch ($error['code'] ?? 0) {
                case 100: // API_UNKNOWN
                    error_log($error_message . " - Check API version and endpoint");
                    break;
                case 190: // ACCESS_TOKEN_ERROR
                    error_log($error_message . " - Invalid or expired access token");
                    break;
                case 131031: // RATE_LIMITED
                    error_log($error_message . " - Rate limited, implement retry logic");
                    break;
                case 131026: // MESSAGE_UNDELIVERABLE
                    error_log($error_message . " - Phone number may be invalid or blocked");
                    break;
                default:
                    error_log($error_message);
            }
        } else {
            error_log("EduBot WhatsApp Error: HTTP {$status_code} - {$response_body}");
        }
    }

    /**
     * Send Twilio WhatsApp message
     */
    private function send_twilio_whatsapp($phone, $message, $api_keys) {
        $credentials = explode(':', $api_keys['whatsapp_token']);
        if (count($credentials) !== 2) {
            return false;
        }
        
        list($account_sid, $auth_token) = $credentials;
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
        
        // Handle both template messages and text messages
        if (is_array($message) && isset($message['type']) && $message['type'] === 'template') {
            // Twilio template message (Content SID)
            $data = array(
                'From' => 'whatsapp:' . $api_keys['whatsapp_phone_id'],
                'To' => 'whatsapp:' . $phone,
                'ContentSid' => $message['template']['name'], // Template SID for Twilio
                'ContentVariables' => json_encode($message['template']['variables'] ?? array())
            );
        } else {
            // Free-form text message
            $data = array(
                'From' => 'whatsapp:' . $api_keys['whatsapp_phone_id'],
                'To' => 'whatsapp:' . $phone,
                'Body' => is_string($message) ? $message : (string) $message
            );
        }

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($account_sid . ':' . $auth_token),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => $data,
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('EduBot Twilio WhatsApp Error: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        return $status_code === 201;
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
     * Send email
     * Now uses wp_edubot_api_integrations table with fallback to WordPress options
     */
    public function send_email($to, $subject, $message, $headers = array()) {
        // Get API settings from table (with fallback to options)
        $api_settings = EduBot_API_Migration::get_api_settings();
        
        $email_provider = $api_settings['email_provider'] ?? '';
        
        // If no provider set, try to get from school config API keys
        if (empty($email_provider)) {
            $api_keys = $this->school_config->get_api_keys();
            $email_provider = $api_keys['email_service'] ?? '';
        }
        
        if (empty($email_provider)) {
            error_log('EduBot: No email provider configured');
            return wp_mail($to, $subject, $message, $headers);
        }

        // Build API keys array from table (or fallback to options)
        $api_keys = array(
            'email_api_key' => $api_settings['email_api_key'] ?? '',
            'email_from_address' => $api_settings['email_from_address'] ?? '',
            'email_from_name' => $api_settings['email_from_name'] ?? ''
        );

        switch ($email_provider) {
            case 'sendgrid':
                return $this->send_sendgrid_email($to, $subject, $message, $api_keys);
                
            case 'mailgun':
                return $this->send_mailgun_email($to, $subject, $message, $api_keys);
                
            case 'zeptomail':
                return $this->send_zeptomail_email($to, $subject, $message, $api_keys);
                
            default:
                error_log('EduBot: Unknown email provider: ' . $email_provider);
                return wp_mail($to, $subject, $message, $headers);
        }
    }

    /**
     * Send email via SendGrid
     */
    private function send_sendgrid_email($to, $subject, $message, $api_keys) {
        $config = $this->school_config->get_config();
        $from_email = $config['school_info']['contact_info']['email'];
        $from_name = $config['school_info']['name'];
        
        $data = array(
            'personalizations' => array(
                array(
                    'to' => array(
                        array('email' => $to)
                    )
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

        $response = wp_remote_post('https://api.sendgrid.com/v3/mail/send', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_keys['email_api_key'],
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode($data),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('EduBot SendGrid Error: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        return $status_code === 202;
    }

    /**
     * Send email via Mailgun
     */
    private function send_mailgun_email($to, $subject, $message, $api_keys) {
        $config = $this->school_config->get_config();
        $from_email = $config['school_info']['contact_info']['email'];
        $from_name = $config['school_info']['name'];
        $domain = $api_keys['email_domain'];
        
        $data = array(
            'from' => "{$from_name} <{$from_email}>",
            'to' => $to,
            'subject' => $subject,
            'text' => $message
        );

        $response = wp_remote_post("https://api.mailgun.net/v3/{$domain}/messages", array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('api:' . $api_keys['email_api_key'])
            ),
            'body' => $data,
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('EduBot Mailgun Error: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        return $status_code === 200;
    }

    /**
     * Send email via ZeptoMail
     */
    private function send_zeptomail_email($to, $subject, $message, $api_keys) {
        // Use from_email from WordPress options or school config
        $from_email = $api_keys['email_from_address'] ?? '';
        $from_name = $api_keys['email_from_name'] ?? '';
        
        // Fallback to school config if not set in options
        if (empty($from_email)) {
            try {
                $config = $this->school_config->get_config();
                $from_email = $config['school_info']['contact_info']['email'] ?? '';
                $from_name = $config['school_info']['name'] ?? '';
            } catch (Exception $e) {
                error_log('EduBot ZeptoMail: Could not get school config - ' . $e->getMessage());
            }
        }
        
        if (empty($from_email)) {
            error_log('EduBot ZeptoMail: No from_email configured');
            return false;
        }
        
        if (empty($api_keys['email_api_key'])) {
            error_log('EduBot ZeptoMail: No API key configured');
            return false;
        }
        
        // Handle HTML vs plain text
        $body_key = (stripos($message, '<') !== false && stripos($message, '>') !== false) ? 'htmlbody' : 'textbody';
        
        $data = array(
            'from' => array(
                'address' => $from_email,
                'name' => !empty($from_name) ? $from_name : 'Administrator'
            ),
            'to' => array(
                array(
                    'email_address' => array(
                        'address' => $to
                    )
                )
            ),
            'subject' => $subject,
            $body_key => $message
        );

        error_log('EduBot ZeptoMail: Sending email from ' . $from_email . ' to ' . $to);
        
        $response = wp_remote_post('https://api.zeptomail.in/v1.1/email', array(
            'headers' => array(
                'accept' => 'application/json',
                'authorization' => 'Zoho-enczapikey ' . $api_keys['email_api_key'],
                'content-type' => 'application/json',
                'cache-control' => 'no-cache'
            ),
            'body' => wp_json_encode($data),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('EduBot ZeptoMail Error: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        error_log('EduBot ZeptoMail: HTTP Status ' . $status_code);
        
        // ZeptoMail returns 201 for successful email send, 200 for other operations
        if ($status_code !== 200 && $status_code !== 201) {
            error_log('EduBot ZeptoMail API Error: HTTP ' . $status_code . ' - Response: ' . $response_body);
            return false;
        }

        // Log successful send
        $response_data = json_decode($response_body, true);
        if (isset($response_data['request_id'])) {
            error_log('EduBot ZeptoMail: Email sent successfully. Request ID: ' . $response_data['request_id']);
        } else {
            error_log('EduBot ZeptoMail: Email sent. Response: ' . $response_body);
        }

        return true;
    }

    /**
     * Validate email using AI
     *
     * @param string $email The email to validate
     * @return array Array with 'valid' boolean and 'corrected' email if applicable
     */
    public function validate_email_with_ai($email) {
        $api_keys = $this->school_config->get_api_keys();

        if (empty($api_keys['openai_key'])) {
            // Fallback to basic validation if AI not available
            return array(
                'valid' => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                'corrected' => null,
                'method' => 'regex'
            );
        }

        $prompt = "Analyze this email input: '{$email}'\n\n" .
                 "Task: Determine if this is a valid email address or if the user made a typo.\n\n" .
                 "Common errors to detect:\n" .
                 "- Missing @ symbol (e.g., 'emailgmail.com' should be 'email@gmail.com')\n" .
                 "- Wrong symbol instead of @ (e.g., '%', 'at', '#')\n" .
                 "- Spacing issues\n" .
                 "- Domain typos (e.g., 'gmial.com' should be 'gmail.com')\n\n" .
                 "Respond in this EXACT JSON format only:\n" .
                 "{\"valid\": true/false, \"corrected\": \"corrected@email.com\" or null, \"issue\": \"description of issue\" or null}\n\n" .
                 "Examples:\n" .
                 "Input: 'prasadmasinagmail.com' → {\"valid\": false, \"corrected\": \"prasadmasina@gmail.com\", \"issue\": \"Missing @ symbol\"}\n" .
                 "Input: 'prasad%gmail.com' → {\"valid\": false, \"corrected\": \"prasad@gmail.com\", \"issue\": \"Wrong symbol % instead of @\"}\n" .
                 "Input: 'test@gmail.com' → {\"valid\": true, \"corrected\": null, \"issue\": null}";

        $config = $this->school_config->get_config();
        $model = isset($config['chatbot_settings']['ai_model']) ?
            sanitize_text_field($config['chatbot_settings']['ai_model']) : 'gpt-3.5-turbo';

        $data = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are an email validation expert. Always respond with valid JSON only.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 150,
            'temperature' => 0.1
        );

        $response = $this->make_openai_request($data, $api_keys['openai_key']);

        if (is_wp_error($response)) {
            // Fallback to regex validation
            return array(
                'valid' => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                'corrected' => null,
                'method' => 'regex_fallback'
            );
        }

        if ($response && isset($response['choices'][0]['message']['content'])) {
            $ai_response = trim($response['choices'][0]['message']['content']);

            // Parse JSON response
            $validation_result = json_decode($ai_response, true);

            if ($validation_result && isset($validation_result['valid'])) {
                $validation_result['method'] = 'ai';
                return $validation_result;
            }
        }

        // Fallback if AI response is invalid
        return array(
            'valid' => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
            'corrected' => null,
            'method' => 'regex_fallback'
        );
    }

    /**
     * Validate phone number using AI
     *
     * @param string $phone The phone number to validate
     * @return array Array with 'valid' boolean and 'corrected' phone if applicable
     */
    public function validate_phone_with_ai($phone) {
        $api_keys = $this->school_config->get_api_keys();

        if (empty($api_keys['openai_key'])) {
            // Fallback to basic validation
            $phone_clean = preg_replace('/[^\d+]/', '', $phone);
            $valid = preg_match('/^(\+?91)?[6-9]\d{9}$/', $phone_clean);
            return array(
                'valid' => $valid,
                'corrected' => null,
                'method' => 'regex'
            );
        }

        $prompt = "Analyze this phone input: '{$phone}'\n\n" .
                 "Task: Determine if this is a valid Indian mobile number (10 digits, starting with 6-9).\n\n" .
                 "Common errors to detect:\n" .
                 "- Missing digits (e.g., 9 digits instead of 10)\n" .
                 "- Extra digits\n" .
                 "- Wrong starting digit (should be 6, 7, 8, or 9)\n" .
                 "- Alphanumeric characters mixed in\n" .
                 "- Spacing or formatting issues\n\n" .
                 "Respond in this EXACT JSON format only:\n" .
                 "{\"valid\": true/false, \"corrected\": \"+919876543210\" or null, \"issue\": \"description\" or null, \"digit_count\": number}\n\n" .
                 "Examples:\n" .
                 "Input: '9866133566' → {\"valid\": true, \"corrected\": \"+919866133566\", \"issue\": null, \"digit_count\": 10}\n" .
                 "Input: '986613356' → {\"valid\": false, \"corrected\": null, \"issue\": \"Only 9 digits, needs 10\", \"digit_count\": 9}\n" .
                 "Input: '5866133566' → {\"valid\": false, \"corrected\": null, \"issue\": \"Must start with 6, 7, 8, or 9\", \"digit_count\": 10}";

        $config = $this->school_config->get_config();
        $model = isset($config['chatbot_settings']['ai_model']) ?
            sanitize_text_field($config['chatbot_settings']['ai_model']) : 'gpt-3.5-turbo';

        $data = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a phone number validation expert for Indian mobile numbers. Always respond with valid JSON only.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 150,
            'temperature' => 0.1
        );

        $response = $this->make_openai_request($data, $api_keys['openai_key']);

        if (is_wp_error($response)) {
            // Fallback to regex validation
            $phone_clean = preg_replace('/[^\d+]/', '', $phone);
            $valid = preg_match('/^(\+?91)?[6-9]\d{9}$/', $phone_clean);
            return array(
                'valid' => $valid,
                'corrected' => null,
                'method' => 'regex_fallback'
            );
        }

        if ($response && isset($response['choices'][0]['message']['content'])) {
            $ai_response = trim($response['choices'][0]['message']['content']);

            // Parse JSON response
            $validation_result = json_decode($ai_response, true);

            if ($validation_result && isset($validation_result['valid'])) {
                $validation_result['method'] = 'ai';
                return $validation_result;
            }
        }

        // Fallback if AI response is invalid
        $phone_clean = preg_replace('/[^\d+]/', '', $phone);
        $valid = preg_match('/^(\+?91)?[6-9]\d{9}$/', $phone_clean);
        return array(
            'valid' => $valid,
            'corrected' => null,
            'method' => 'regex_fallback'
        );
    }
}
