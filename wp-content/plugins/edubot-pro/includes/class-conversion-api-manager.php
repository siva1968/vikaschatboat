<?php
/**
 * Conversion API Manager Class
 * 
 * Manages integration with multiple platform conversion APIs:
 * - Facebook Conversions API
 * - Google Ads Conversion API
 * - TikTok Events API
 * - LinkedIn Conversions API
 * 
 * Handles payload formatting, request sending, error handling, and retry logic.
 * 
 * @since 1.3.3
 * @package EduBot_Pro
 * @subpackage Integrations
 */

class EduBot_Conversion_API_Manager {
    
    /**
     * API providers
     * 
     * @var array
     */
    private $providers = [];
    
    /**
     * EduBot logger instance
     * 
     * @var EduBot_Logger
     */
    private $logger;
    
    /**
     * API logs table name
     * 
     * @var string
     */
    private $logs_table;
    
    /**
     * WordPress database instance
     * 
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * Configuration settings
     * 
     * @var array
     */
    private $config = [];
    
    /**
     * Constructor
     * 
     * @param EduBot_Logger $logger Logger instance
     */
    public function __construct($logger = null) {
        global $wpdb;
        
        $this->wpdb = $wpdb;
        $this->logs_table = $wpdb->prefix . 'edubot_api_logs';
        
        if ($logger instanceof EduBot_Logger) {
            $this->logger = $logger;
        } else {
            $this->logger = new EduBot_Logger();
        }
        
        // Initialize configuration from constants/options
        $this->config = [
            'facebook' => [
                'access_token' => defined('EDUBOT_FACEBOOK_CONV_TOKEN') 
                    ? EDUBOT_FACEBOOK_CONV_TOKEN 
                    : get_option('edubot_facebook_conv_token'),
                'enabled' => defined('EDUBOT_FACEBOOK_CONV_ENABLED') 
                    ? EDUBOT_FACEBOOK_CONV_ENABLED 
                    : get_option('edubot_facebook_conv_enabled', false)
            ],
            'google' => [
                'customer_id' => defined('EDUBOT_GOOGLE_CONV_CUSTOMER_ID') 
                    ? EDUBOT_GOOGLE_CONV_CUSTOMER_ID 
                    : get_option('edubot_google_conv_customer_id'),
                'conversion_action_id' => defined('EDUBOT_GOOGLE_CONV_ACTION_ID') 
                    ? EDUBOT_GOOGLE_CONV_ACTION_ID 
                    : get_option('edubot_google_conv_action_id'),
                'api_key' => defined('EDUBOT_GOOGLE_CONV_API_KEY') 
                    ? EDUBOT_GOOGLE_CONV_API_KEY 
                    : get_option('edubot_google_conv_api_key'),
                'enabled' => defined('EDUBOT_GOOGLE_CONV_ENABLED') 
                    ? EDUBOT_GOOGLE_CONV_ENABLED 
                    : get_option('edubot_google_conv_enabled', false)
            ],
            'tiktok' => [
                'access_token' => defined('EDUBOT_TIKTOK_CONV_TOKEN') 
                    ? EDUBOT_TIKTOK_CONV_TOKEN 
                    : get_option('edubot_tiktok_conv_token'),
                'pixel_id' => defined('EDUBOT_TIKTOK_PIXEL_ID') 
                    ? EDUBOT_TIKTOK_PIXEL_ID 
                    : get_option('edubot_tiktok_pixel_id'),
                'enabled' => defined('EDUBOT_TIKTOK_CONV_ENABLED') 
                    ? EDUBOT_TIKTOK_CONV_ENABLED 
                    : get_option('edubot_tiktok_conv_enabled', false)
            ],
            'linkedin' => [
                'access_token' => defined('EDUBOT_LINKEDIN_CONV_TOKEN') 
                    ? EDUBOT_LINKEDIN_CONV_TOKEN 
                    : get_option('edubot_linkedin_conv_token'),
                'conversion_id' => defined('EDUBOT_LINKEDIN_CONV_ID') 
                    ? EDUBOT_LINKEDIN_CONV_ID 
                    : get_option('edubot_linkedin_conv_id'),
                'enabled' => defined('EDUBOT_LINKEDIN_CONV_ENABLED') 
                    ? EDUBOT_LINKEDIN_CONV_ENABLED 
                    : get_option('edubot_linkedin_conv_enabled', false)
            ]
        ];
    }
    
    /**
     * Send conversion event to platforms
     * 
     * @param int $enquiry_id Enquiry ID
     * @param array $enquiry_data Enquiry data
     * @param array $utm_data UTM/tracking data
     * 
     * @return array Results from each platform
     */
    public function send_conversion_event($enquiry_id, $enquiry_data = [], $utm_data = []) {
        
        if (!$enquiry_id) {
            return ['success' => false, 'error' => 'Invalid enquiry ID'];
        }
        
        $results = [
            'enquiry_id' => $enquiry_id,
            'timestamp' => current_time('mysql'),
            'platforms' => []
        ];
        
        // Prepare conversion data
        $conversion_data = $this->prepare_conversion_data($enquiry_id, $enquiry_data, $utm_data);
        
        // Send to each enabled platform
        if ($this->config['facebook']['enabled']) {
            $results['platforms']['facebook'] = $this->send_facebook_conversion($conversion_data);
        }
        
        if ($this->config['google']['enabled']) {
            $results['platforms']['google'] = $this->send_google_conversion($conversion_data);
        }
        
        if ($this->config['tiktok']['enabled']) {
            $results['platforms']['tiktok'] = $this->send_tiktok_conversion($conversion_data);
        }
        
        if ($this->config['linkedin']['enabled']) {
            $results['platforms']['linkedin'] = $this->send_linkedin_conversion($conversion_data);
        }
        
        return $results;
    }
    
    /**
     * Prepare conversion data for all platforms
     * 
     * @param int $enquiry_id Enquiry ID
     * @param array $enquiry_data Enquiry data
     * @param array $utm_data UTM data
     * 
     * @return array Standardized conversion data
     */
    private function prepare_conversion_data($enquiry_id, $enquiry_data, $utm_data) {
        
        $conversion_data = [
            'enquiry_id' => $enquiry_id,
            'event_time' => time(),
            'event_id' => 'lead_' . $enquiry_id . '_' . time(),
            'user_data' => $this->extract_user_data($enquiry_data),
            'click_ids' => $this->extract_click_ids($utm_data),
            'utm_data' => $utm_data,
            'event_value' => 0,
            'currency' => 'USD'
        ];
        
        return $conversion_data;
    }
    
    /**
     * Extract user data for PII hashing
     * 
     * @param array $enquiry_data Enquiry data
     * 
     * @return array User data with hashed PII
     */
    private function extract_user_data($enquiry_data) {
        
        $user_data = [
            'email' => isset($enquiry_data['email']) ? $enquiry_data['email'] : '',
            'phone' => isset($enquiry_data['phone']) ? $enquiry_data['phone'] : '',
            'first_name' => isset($enquiry_data['student_name']) ? explode(' ', $enquiry_data['student_name'])[0] : '',
            'last_name' => isset($enquiry_data['student_name']) ? end(explode(' ', $enquiry_data['student_name'])) : '',
            'city' => isset($enquiry_data['city']) ? $enquiry_data['city'] : '',
            'state' => isset($enquiry_data['state']) ? $enquiry_data['state'] : '',
            'country' => 'IN' // Default to India
        ];
        
        // Hash sensitive data
        $user_data['email_hashed'] = !empty($user_data['email']) 
            ? hash('sha256', strtolower(trim($user_data['email']))) 
            : '';
        
        $user_data['phone_hashed'] = !empty($user_data['phone']) 
            ? hash('sha256', preg_replace('/[^0-9]/', '', $user_data['phone'])) 
            : '';
        
        $user_data['first_name_hashed'] = !empty($user_data['first_name']) 
            ? hash('sha256', strtolower(trim($user_data['first_name']))) 
            : '';
        
        $user_data['last_name_hashed'] = !empty($user_data['last_name']) 
            ? hash('sha256', strtolower(trim($user_data['last_name']))) 
            : '';
        
        return $user_data;
    }
    
    /**
     * Extract platform click IDs from UTM data
     * 
     * @param array $utm_data UTM data
     * 
     * @return array Click IDs
     */
    private function extract_click_ids($utm_data) {
        
        $click_ids = [
            'gclid' => isset($utm_data['gclid']) ? $utm_data['gclid'] : '',
            'fbclid' => isset($utm_data['fbclid']) ? $utm_data['fbclid'] : '',
            'msclkid' => isset($utm_data['msclkid']) ? $utm_data['msclkid'] : '',
            'ttclid' => isset($utm_data['ttclid']) ? $utm_data['ttclid'] : '',
            'li_fat_id' => isset($utm_data['li_fat_id']) ? $utm_data['li_fat_id'] : ''
        ];
        
        return array_filter($click_ids);
    }
    
    /**
     * Send conversion to Facebook Conversions API
     * 
     * @param array $conversion_data Prepared conversion data
     * 
     * @return array API response
     */
    private function send_facebook_conversion($conversion_data) {
        
        $token = $this->config['facebook']['access_token'];
        
        if (!$token) {
            return ['success' => false, 'error' => 'Facebook API token not configured'];
        }
        
        $pixel_id = defined('EDUBOT_FACEBOOK_PIXEL_ID') 
            ? EDUBOT_FACEBOOK_PIXEL_ID 
            : get_option('edubot_facebook_pixel_id');
        
        if (!$pixel_id) {
            return ['success' => false, 'error' => 'Facebook Pixel ID not configured'];
        }
        
        $payload = [
            'data' => [
                [
                    'event_name' => 'Lead',
                    'event_time' => $conversion_data['event_time'],
                    'event_id' => $conversion_data['event_id'],
                    'user_data' => [
                        'em' => $conversion_data['user_data']['email_hashed'],
                        'ph' => $conversion_data['user_data']['phone_hashed'],
                        'fn' => $conversion_data['user_data']['first_name_hashed'],
                        'ln' => $conversion_data['user_data']['last_name_hashed'],
                        'ct' => isset($conversion_data['user_data']['city']) 
                            ? hash('sha256', strtolower($conversion_data['user_data']['city'])) 
                            : '',
                        'st' => isset($conversion_data['user_data']['state']) 
                            ? hash('sha256', strtolower($conversion_data['user_data']['state'])) 
                            : ''
                    ],
                    'custom_data' => [
                        'value' => $conversion_data['event_value'],
                        'currency' => $conversion_data['currency'],
                        'content_type' => 'lead'
                    ],
                    'action_source' => 'website'
                ]
            ],
            'access_token' => $token
        ];
        
        // Remove empty values
        $payload['data'][0]['user_data'] = array_filter($payload['data'][0]['user_data']);
        
        return $this->send_api_request(
            'facebook',
            'conversions',
            $payload,
            $conversion_data
        );
    }
    
    /**
     * Send conversion to Google Ads Conversion API
     * 
     * @param array $conversion_data Prepared conversion data
     * 
     * @return array API response
     */
    private function send_google_conversion($conversion_data) {
        
        $customer_id = $this->config['google']['customer_id'];
        $conversion_action_id = $this->config['google']['conversion_action_id'];
        $api_key = $this->config['google']['api_key'];
        
        if (!$api_key || !$customer_id || !$conversion_action_id) {
            return ['success' => false, 'error' => 'Google API configuration incomplete'];
        }
        
        $gclid = isset($conversion_data['click_ids']['gclid']) 
            ? $conversion_data['click_ids']['gclid'] 
            : '';
        
        if (!$gclid) {
            return ['success' => false, 'error' => 'No gclid found for Google conversion'];
        }
        
        $payload = [
            'conversions' => [
                [
                    'gclid' => $gclid,
                    'conversion_action' => 'customers/' . $customer_id . '/conversionActions/' . $conversion_action_id,
                    'conversion_date_time' => date('Y-m-d H:i:s', $conversion_data['event_time']),
                    'conversion_value' => $conversion_data['event_value'],
                    'currency_code' => $conversion_data['currency'],
                    'user_identifiers' => [
                        [
                            'hashed_email' => $conversion_data['user_data']['email_hashed']
                        ]
                    ]
                ]
            ],
            'partial_failure' => true
        ];
        
        // Remove empty identifiers
        $payload['conversions'][0]['user_identifiers'] = array_filter(
            $payload['conversions'][0]['user_identifiers'],
            function($identifier) {
                return !empty(current($identifier));
            }
        );
        
        return $this->send_api_request(
            'google',
            'conversions',
            $payload,
            $conversion_data,
            'https://googleads.googleapis.com/v14/customers/' . $customer_id . '/conversionUploads:process',
            ['Authorization' => 'Bearer ' . $api_key]
        );
    }
    
    /**
     * Send conversion to TikTok Events API
     * 
     * @param array $conversion_data Prepared conversion data
     * 
     * @return array API response
     */
    private function send_tiktok_conversion($conversion_data) {
        
        $token = $this->config['tiktok']['access_token'];
        $pixel_id = $this->config['tiktok']['pixel_id'];
        
        if (!$token || !$pixel_id) {
            return ['success' => false, 'error' => 'TikTok API configuration incomplete'];
        }
        
        $ttclid = isset($conversion_data['click_ids']['ttclid']) 
            ? $conversion_data['click_ids']['ttclid'] 
            : '';
        
        $payload = [
            'pixel_code' => $pixel_id,
            'event' => 'Lead',
            'event_id' => $conversion_data['event_id'],
            'timestamp' => $conversion_data['event_time'],
            'user' => [
                'email' => $conversion_data['user_data']['email_hashed'],
                'phone_number' => $conversion_data['user_data']['phone_hashed'],
                'ttp' => $ttclid
            ],
            'properties' => [
                'value' => $conversion_data['event_value'],
                'currency' => $conversion_data['currency']
            ]
        ];
        
        // Remove empty values
        $payload['user'] = array_filter($payload['user']);
        
        return $this->send_api_request(
            'tiktok',
            'events',
            $payload,
            $conversion_data,
            'https://business-api.tiktok.com/open_api/v1.3/pixel/track/',
            ['Access-Token' => $token]
        );
    }
    
    /**
     * Send conversion to LinkedIn Conversions API
     * 
     * @param array $conversion_data Prepared conversion data
     * 
     * @return array API response
     */
    private function send_linkedin_conversion($conversion_data) {
        
        $token = $this->config['linkedin']['access_token'];
        $conversion_id = $this->config['linkedin']['conversion_id'];
        
        if (!$token || !$conversion_id) {
            return ['success' => false, 'error' => 'LinkedIn API configuration incomplete'];
        }
        
        $li_fat_id = isset($conversion_data['click_ids']['li_fat_id']) 
            ? $conversion_data['click_ids']['li_fat_id'] 
            : '';
        
        $payload = [
            'conversion' => $conversion_id,
            'conversionHappenedAt' => $conversion_data['event_time'] * 1000, // LinkedIn uses milliseconds
            'eventId' => $conversion_data['event_id'],
            'userInfo' => []
        ];
        
        // Add user identifiers if available
        if (!empty($conversion_data['user_data']['email'])) {
            $payload['userInfo']['email'] = [
                'hashedEmail' => $conversion_data['user_data']['email_hashed']
            ];
        }
        
        if (!empty($li_fat_id)) {
            $payload['userInfo']['li_fat_id'] = $li_fat_id;
        }
        
        return $this->send_api_request(
            'linkedin',
            'conversions',
            $payload,
            $conversion_data,
            'https://api.linkedin.com/v2/conversions',
            ['Authorization' => 'Bearer ' . $token]
        );
    }
    
    /**
     * Send API request with retry logic
     * 
     * @param string $provider Provider name
     * @param string $request_type Request type
     * @param array $payload Request payload
     * @param array $conversion_data Original conversion data
     * @param string $url API endpoint URL (optional)
     * @param array $headers Additional headers (optional)
     * @param int $retry_count Current retry attempt
     * 
     * @return array Response
     */
    private function send_api_request(
        $provider,
        $request_type,
        $payload,
        $conversion_data,
        $url = null,
        $headers = [],
        $retry_count = 0
    ) {
        
        if ($retry_count > 3) {
            return ['success' => false, 'error' => 'Max retries exceeded'];
        }
        
        try {
            
            $response = wp_remote_post(
                $url ?: $this->get_api_endpoint($provider),
                [
                    'headers' => array_merge(
                        ['Content-Type' => 'application/json'],
                        $headers
                    ),
                    'body' => json_encode($payload),
                    'timeout' => 10,
                    'sslverify' => true
                ]
            );
            
            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            $success = $status_code >= 200 && $status_code < 300;
            
            // Log the API request
            $this->log_api_request(
                $conversion_data['enquiry_id'],
                $provider,
                $request_type,
                $payload,
                $status_code,
                $response_body,
                $success,
                $retry_count
            );
            
            if ($success) {
                $this->logger->log(
                    sprintf('%s conversion sent successfully for enquiry %d', 
                        ucfirst($provider), 
                        $conversion_data['enquiry_id']
                    ),
                    'info'
                );
                
                return [
                    'success' => true,
                    'provider' => $provider,
                    'status_code' => $status_code
                ];
            } else {
                // Retry on server errors
                if ($status_code >= 500) {
                    sleep(2 * ($retry_count + 1)); // Exponential backoff
                    return $this->send_api_request(
                        $provider,
                        $request_type,
                        $payload,
                        $conversion_data,
                        $url,
                        $headers,
                        $retry_count + 1
                    );
                }
                
                return [
                    'success' => false,
                    'provider' => $provider,
                    'status_code' => $status_code,
                    'error' => $response_body
                ];
            }
            
        } catch (Exception $e) {
            
            // Log error
            $this->logger->log(
                sprintf('%s conversion failed: %s', ucfirst($provider), $e->getMessage()),
                'error'
            );
            
            // Retry on network errors
            if ($retry_count < 3) {
                sleep(2 * ($retry_count + 1));
                return $this->send_api_request(
                    $provider,
                    $request_type,
                    $payload,
                    $conversion_data,
                    $url,
                    $headers,
                    $retry_count + 1
                );
            }
            
            return [
                'success' => false,
                'provider' => $provider,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get API endpoint for provider
     * 
     * @param string $provider Provider name
     * 
     * @return string API endpoint URL
     */
    private function get_api_endpoint($provider) {
        
        $pixel_id = defined('EDUBOT_FACEBOOK_PIXEL_ID') 
            ? EDUBOT_FACEBOOK_PIXEL_ID 
            : get_option('edubot_facebook_pixel_id');
        
        $endpoints = [
            'facebook' => 'https://graph.facebook.com/v18.0/' . $pixel_id . '/events',
            'google' => '', // Set in send_api_request
            'tiktok' => 'https://business-api.tiktok.com/open_api/v1.3/pixel/track/',
            'linkedin' => 'https://api.linkedin.com/v2/conversions'
        ];
        
        return $endpoints[$provider] ?? '';
    }
    
    /**
     * Log API request/response
     * 
     * @param int $enquiry_id Enquiry ID
     * @param string $api_provider Provider name
     * @param string $request_type Request type
     * @param array $request_payload Request payload
     * @param int $response_status HTTP status code
     * @param string $response_payload Response body
     * @param bool $success Whether request was successful
     * @param int $retry_count Number of retries
     */
    private function log_api_request(
        $enquiry_id,
        $api_provider,
        $request_type,
        $request_payload,
        $response_status,
        $response_payload,
        $success,
        $retry_count = 0
    ) {
        
        global $wpdb;
        
        // Sanitize sensitive data from payload before logging
        $payload_copy = $request_payload;
        if (isset($payload_copy['access_token'])) {
            $payload_copy['access_token'] = '***REDACTED***';
        }
        if (isset($payload_copy['data'][0]['user_data'])) {
            // Hash already redacted personal data, but replace entirely for extra safety
            $payload_copy['data'][0]['user_data'] = '[PII_REDACTED]';
        }
        
        $wpdb->insert(
            $this->logs_table,
            [
                'enquiry_id' => $enquiry_id,
                'api_provider' => sanitize_text_field($api_provider),
                'request_type' => sanitize_text_field($request_type),
                'request_payload' => json_encode($payload_copy),
                'response_status' => $response_status,
                'response_payload' => json_encode(json_decode($response_payload ?: '{}', true)),
                'success' => $success ? 1 : 0,
                'error_message' => $success ? '' : 'HTTP ' . $response_status,
                'retry_count' => $retry_count
            ],
            ['%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%d']
        );
    }
    
    /**
     * Get API logs for enquiry
     * 
     * @param int $enquiry_id Enquiry ID
     * 
     * @return array API logs
     */
    public function get_api_logs($enquiry_id) {
        
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $this->logs_table 
                WHERE enquiry_id = %d 
                ORDER BY created_at DESC",
                $enquiry_id
            ),
            ARRAY_A
        );
    }
}
?>
