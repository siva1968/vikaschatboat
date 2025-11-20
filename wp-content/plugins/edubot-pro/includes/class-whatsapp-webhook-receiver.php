<?php
/**
 * WhatsApp Webhook Receiver
 * 
 * Handles incoming WhatsApp messages from Meta Business API
 * Processes user messages and maintains conversation state
 * 
 * @package EduBot_Pro
 * @subpackage Integrations
 * @version 1.0.0
 */

class EduBot_WhatsApp_Webhook_Receiver {
    
    /**
     * Handle incoming webhook from Meta WhatsApp Business API
     * 
     * Endpoint: POST /wp-json/edubot/v1/whatsapp-webhook
     */
    public function handle_webhook( WP_REST_Request $request ) {
        error_log( 'EduBot: WhatsApp webhook received' );
        
        try {
            // Verify webhook token
            if ( !$this->verify_webhook_token( $request ) ) {
                error_log( 'EduBot: Invalid webhook token' );
                return new WP_REST_Response( array( 'error' => 'Invalid token' ), 401 );
            }
            
            $data = $request->get_json_params();
            error_log( 'EduBot: Webhook data: ' . print_r( $data, true ) );
            
            // Handle subscription confirmation (Meta sends this for webhook verification)
            if ( isset( $data['subscribe'] ) ) {
                return new WP_REST_Response( array( 'success' => true ) );
            }
            
            // Handle status updates (message delivery, read status)
            if ( isset( $data['statuses'] ) && is_array( $data['statuses'] ) ) {
                $this->handle_status_updates( $data['statuses'] );
                return new WP_REST_Response( array( 'success' => true ) );
            }
            
            // Handle incoming messages
            if ( isset( $data['messages'] ) && is_array( $data['messages'] ) ) {
                foreach ( $data['messages'] as $message_data ) {
                    $this->process_incoming_message( $message_data );
                }
                return new WP_REST_Response( array( 'success' => true ) );
            }
            
            return new WP_REST_Response( array( 'success' => true ) );
            
        } catch ( Exception $e ) {
            error_log( 'EduBot: Webhook error: ' . $e->getMessage() );
            return new WP_REST_Response( array( 'error' => $e->getMessage() ), 500 );
        }
    }
    
    /**
     * Verify webhook token from Meta
     */
    private function verify_webhook_token( WP_REST_Request $request ) {
        $token = $request->get_param( 'token' );
        $expected_token = get_option( 'edubot_whatsapp_webhook_token', '' );
        
        if ( empty( $expected_token ) ) {
            error_log( 'EduBot: Webhook token not configured' );
            return false;
        }
        
        if ( empty( $token ) ) {
            error_log( 'EduBot: No token provided in webhook' );
            return false;
        }
        
        return hash_equals( $token, $expected_token );
    }
    
    /**
     * Process incoming message from user
     */
    private function process_incoming_message( $message_data ) {
        $phone = $message_data['from'] ?? '';
        $message_id = $message_data['id'] ?? '';
        $timestamp = $message_data['timestamp'] ?? '';
        $type = $message_data['type'] ?? 'text';
        
        if ( empty( $phone ) || empty( $message_id ) ) {
            error_log( 'EduBot: Missing phone or message ID' );
            return;
        }
        
        // Extract message content based on type
        $message_text = $this->extract_message_content( $message_data, $type );
        
        if ( empty( $message_text ) ) {
            error_log( 'EduBot: Could not extract message content' );
            return;
        }
        
        error_log( "EduBot: Processing message from {$phone}: {$message_text}" );
        
        // Extract platform and campaign from tracking info in message
        $platform_data = $this->extract_platform_from_message( $message_text );
        
        // Get or create session for this phone
        $session_id = $this->get_or_create_session( $phone, $platform_data );
        
        if ( empty( $session_id ) ) {
            error_log( 'EduBot: Could not create session for phone: ' . $phone );
            return;
        }
        
        // Store incoming message
        EduBot_WhatsApp_Session_Manager::store_message( $session_id, 'user', $message_text, $message_id );
        
        // Get chatbot response
        $response = $this->get_chatbot_response( $session_id, $message_text, $phone );
        
        if ( empty( $response ) ) {
            error_log( 'EduBot: No response from chatbot' );
            return;
        }
        
        // Send response back to user
        $this->send_whatsapp_response( $phone, $response, $session_id );
    }
    
    /**
     * Extract message content based on message type
     */
    private function extract_message_content( $message_data, $type ) {
        switch ( $type ) {
            case 'text':
                return $message_data['text']['body'] ?? '';
            
            case 'button':
                return $message_data['button']['text'] ?? '';
            
            case 'interactive':
                if ( isset( $message_data['interactive']['button_reply'] ) ) {
                    return $message_data['interactive']['button_reply']['title'] ?? '';
                }
                if ( isset( $message_data['interactive']['list_reply'] ) ) {
                    return $message_data['interactive']['list_reply']['title'] ?? '';
                }
                break;
            
            case 'image':
            case 'document':
            case 'audio':
            case 'video':
                return '[' . strtoupper( $type ) . ']';
            
            default:
                return '';
        }
        
        return '';
    }
    
    /**
     * Extract platform and campaign information from message tracking data (UPDATED v1.7.0)
     * 
     * Supports both:
     * 1. NEW: Tracking codes - "[Track: CAMP_5_FB_1731330000]"
     * 2. LEGACY: Source format - "[Source: FACEBOOK | Campaign: 2026 Admissions | ...]"
     */
    private function extract_platform_from_message( $message_text ) {
        $data = array(
            'platform_source' => 'unknown',
            'campaign_name' => 'unknown',
            'campaign_id' => null,
            'tracking_code' => '',
            'utm_source' => '',
            'utm_campaign' => ''
        );
        
        // NEW: Try to extract tracking code first (v1.7.0+)
        // Format: [Track: CAMP_5_FB_1731330000]
        if ( preg_match( '/\[Track:\s*([A-Z0-9_]+)\]/', $message_text, $matches ) ) {
            $tracking_code = $matches[1];
            
            // Look up campaign from tracking code
            $campaign = EduBot_WhatsApp_Ad_Link_Generator::get_campaign_by_tracking_code( $tracking_code );
            
            if ( $campaign ) {
                $data['platform_source'] = $campaign->platform;
                $data['campaign_name'] = $campaign->utm_campaign;
                $data['campaign_id'] = $campaign->campaign_id;
                $data['tracking_code'] = $tracking_code;
                $data['utm_source'] = $campaign->utm_source;
                $data['utm_campaign'] = $campaign->utm_campaign;
                
                // Update last message time for this tracking code
                EduBot_WhatsApp_Ad_Link_Generator::update_tracking_last_message( $tracking_code );
                
                error_log( 'EduBot: Extracted campaign from tracking code - Platform: ' . $data['platform_source'] . ', Campaign: ' . $data['campaign_name'] . ', Code: ' . $tracking_code );
                
                return $data;
            }
        }
        
        // LEGACY: Try to extract [Source: PLATFORM | Campaign: NAME | utm_...]
        // Kept for backwards compatibility
        if ( preg_match( '/\[Source:\s*(\w+(?:\/\w+)?)\s*\|\s*Campaign:\s*([^\|]+?)\s*\|\s*utm_source=([^&\]]+)(?:&utm_medium=([^&\]]+))?(?:&utm_campaign=([^\]]+))?\]/', $message_text, $matches ) ) {
            
            // Extract platform (FACEBOOK -> facebook)
            $platform_display = strtoupper( $matches[1] );
            $platform_source = strtolower( str_replace( '/X', '', $matches[1] ) ); // TWITTER/X -> twitter
            
            $data['platform_source'] = $platform_source;
            $data['campaign_name'] = trim( $matches[2] );
            $data['utm_source'] = urldecode( $matches[3] );
            $data['utm_campaign'] = isset( $matches[5] ) ? urldecode( $matches[5] ) : '';
            
            error_log( 'EduBot: Extracted platform tracking (legacy) - Platform: ' . $data['platform_source'] . ', Campaign: ' . $data['campaign_name'] );
            
            return $data;
        }
        
        error_log( 'EduBot: No tracking data found in message - Using unknown source' );
        
        return $data;
    }
    
    /**
     * Extract platform data from message metadata
     */
    private function extract_platform_data( $message_data ) {
        $data = array(
            'platform_source' => 'whatsapp_direct',
            'campaign_name' => 'unknown',
            'utm_source' => '',
            'utm_campaign' => ''
        );
        
        // Check for webhook context or referrer data
        if ( isset( $message_data['context'] ) ) {
            $context = $message_data['context'];
            
            // Extract referrer information if available
            if ( isset( $context['referred_product'] ) ) {
                $data['platform_source'] = 'whatsapp_business_discovery';
                $data['campaign_name'] = 'business_discovery';
            }
            
            if ( isset( $context['forwarded'] ) && $context['forwarded'] ) {
                $data['platform_source'] = 'whatsapp_forwarded';
                $data['campaign_name'] = 'forwarded_message';
            }
        }
        
        // Check message text for tracking codes
        $message_text = $this->extract_message_content( $message_data, $message_data['type'] ?? 'text' );
        if ( !empty( $message_text ) ) {
            $tracking_data = $this->extract_tracking_data( $message_text );
            if ( !empty( $tracking_data['platform_source'] ) && $tracking_data['platform_source'] !== 'unknown' ) {
                $data = array_merge( $data, $tracking_data );
            }
        }
        
        return $data;
    }
    
    /**
     * Get or create session for phone number
     */
    private function get_or_create_session( $phone, $platform_data = array() ) {
        // Try to find existing active session
        $session = EduBot_WhatsApp_Session_Manager::get_session_by_phone( $phone );
        
        if ( $session ) {
            // Update platform source if we have new data
            if ( !empty( $platform_data['platform_source'] ) ) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'edubot_whatsapp_sessions',
                    array( 'platform_source' => $platform_data['platform_source'] ),
                    array( 'session_id' => $session['session_id'] ),
                    array( '%s' ),
                    array( '%d' )
                );
            }
            return $session['session_id'];
        }
        
        // Create new session with ad params from metadata if available
        $ad_params = array(
            'source' => $platform_data['platform_source'] ?? 'whatsapp_direct',
            'medium' => 'whatsapp',
            'campaign' => $platform_data['campaign_name'] ?? 'unknown',
            'utm_source' => $platform_data['utm_source'] ?? '',
            'utm_campaign' => $platform_data['utm_campaign'] ?? ''
        );
        
        $session_id = EduBot_WhatsApp_Session_Manager::create_session( $phone, $ad_params );
        
        // Store platform source
        if ( !empty( $platform_data['platform_source'] ) && !empty( $session_id ) ) {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'edubot_whatsapp_sessions',
                array( 'platform_source' => $platform_data['platform_source'] ),
                array( 'session_id' => $session_id ),
                array( '%s' ),
                array( '%d' )
            );
        }
        
        return $session_id;
    }
    
    /**
     * Get chatbot response for user message using same workflow as web chatbot
     */
    private function get_chatbot_response( $session_id, $message_text, $phone ) {
        try {
            // Log API request for WhatsApp interaction
            EduBot_Admin::log_api_request_to_db(
                'whatsapp_incoming',
                'POST',
                'whatsapp_webhook',
                array('phone' => $phone, 'message' => substr($message_text, 0, 100)),
                array('session_id' => $session_id),
                200,
                'success',
                'WhatsApp message received'
            );
            
            // Load workflow manager - same as web chatbot
            if (!class_exists('EduBot_Workflow_Manager')) {
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-workflow-manager.php';
            }
            
            $workflow_manager = new EduBot_Workflow_Manager();
            
            // Process message through workflow manager (includes AI validation)
            $workflow_response = $workflow_manager->process_user_input($message_text, $session_id);
            
            if (empty($workflow_response)) {
                error_log('EduBot WhatsApp: Empty response from workflow manager');
                $workflow_response = 'Thank you for your message. Our team will get back to you soon!';
            }
            
            // Log successful response
            EduBot_Admin::log_api_request_to_db(
                'whatsapp_outgoing',
                'POST',
                'whatsapp_response',
                array('phone' => $phone, 'response' => substr($workflow_response, 0, 100)),
                array('session_id' => $session_id),
                200,
                'success',
                'WhatsApp response sent'
            );
            
            return $workflow_response;
            
        } catch ( Exception $e ) {
            error_log( 'EduBot: Exception getting WhatsApp chatbot response: ' . $e->getMessage() );
            
            // Log error
            EduBot_Admin::log_api_request_to_db(
                'whatsapp_error',
                'POST',
                'whatsapp_response',
                array('phone' => $phone, 'error' => $e->getMessage()),
                array(),
                500,
                'error',
                'WhatsApp response error: ' . $e->getMessage()
            );
            
            return 'Thank you for your message. Our team will get back to you soon!';
        }
    }
    
    /**
     * Send response back to user via WhatsApp with comprehensive logging
     */
    private function send_whatsapp_response( $phone, $message, $session_id ) {
        try {
            // Get API integrations
            if ( !class_exists( 'EduBot_API_Integrations' ) ) {
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-api-integrations.php';
            }
            $api_integrations = new EduBot_API_Integrations();
            
            // Split long messages for WhatsApp limits
            $messages = $this->split_long_messages( $message );
            
            $success_count = 0;
            $total_messages = count( $messages );
            
            foreach ( $messages as $index => $msg ) {
                $message_num = $index + 1;
                error_log( "EduBot WhatsApp: Sending message {$message_num}/{$total_messages} to {$phone}" );
                
                $result = $api_integrations->send_whatsapp( $phone, $msg );
                
                if ( !$result ) {
                    error_log( "EduBot: Failed to send WhatsApp message {$message_num} to {$phone}" );
                    
                    // Log failed message
                    EduBot_Admin::log_api_request_to_db(
                        'whatsapp_send_failed',
                        'POST',
                        'meta_whatsapp_api',
                        array('phone' => $phone, 'message' => substr($msg, 0, 100)),
                        array(),
                        500,
                        'error',
                        'Failed to send WhatsApp message'
                    );
                } else {
                    $success_count++;
                    error_log( "EduBot: WhatsApp message {$message_num} sent successfully to {$phone}" );
                    
                    // Store outgoing message
                    EduBot_WhatsApp_Session_Manager::store_message( $session_id, 'bot', $msg );
                    
                    // Log successful message
                    EduBot_Admin::log_api_request_to_db(
                        'whatsapp_send_success',
                        'POST',
                        'meta_whatsapp_api',
                        array('phone' => $phone, 'message' => substr($msg, 0, 100)),
                        $result,
                        200,
                        'success',
                        'WhatsApp message sent successfully'
                    );
                }
                
                // Add small delay between messages to avoid rate limiting
                if ( $index < $total_messages - 1 ) {
                    usleep( 500000 ); // 0.5 second delay
                }
            }
            
            error_log( "EduBot WhatsApp: Sent {$success_count}/{$total_messages} messages successfully to {$phone}" );
            
        } catch ( Exception $e ) {
            error_log( 'EduBot: Exception sending WhatsApp response: ' . $e->getMessage() );
            
            // Log exception
            EduBot_Admin::log_api_request_to_db(
                'whatsapp_exception',
                'POST',
                'whatsapp_send',
                array('phone' => $phone, 'error' => $e->getMessage()),
                array(),
                500,
                'error',
                'WhatsApp send exception: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Split long messages into chunks (WhatsApp limit is ~4096 chars)
     */
    private function split_long_messages( $message, $limit = 4000 ) {
        if ( strlen( $message ) <= $limit ) {
            return array( $message );
        }
        
        $messages = array();
        $current = '';
        $lines = explode( "\n", $message );
        
        foreach ( $lines as $line ) {
            if ( strlen( $current ) + strlen( $line ) + 1 > $limit ) {
                if ( !empty( $current ) ) {
                    $messages[] = $current;
                    $current = '';
                }
            }
            $current .= ( $current ? "\n" : '' ) . $line;
        }
        
        if ( !empty( $current ) ) {
            $messages[] = $current;
        }
        
        return $messages;
    }
    
    /**
     * Handle message status updates (delivery, read)
     */
    private function handle_status_updates( $statuses ) {
        global $wpdb;
        
        $messages_table = $wpdb->prefix . 'edubot_whatsapp_messages';
        
        foreach ( $statuses as $status_data ) {
            $message_id = $status_data['id'] ?? '';
            $status = $status_data['status'] ?? '';
            $timestamp = $status_data['timestamp'] ?? '';
            
            if ( empty( $message_id ) || empty( $status ) ) {
                continue;
            }
            
            // Check if table exists
            if ( $wpdb->get_var( "SHOW TABLES LIKE '$messages_table'" ) != $messages_table ) {
                continue;
            }
            
            // Update message status
            $wpdb->update(
                $messages_table,
                array(
                    'delivery_status' => sanitize_text_field( $status ),
                    'delivery_timestamp' => date( 'Y-m-d H:i:s', $timestamp ),
                    'updated_at' => current_time( 'mysql' )
                ),
                array( 'message_id' => $message_id ),
                array( '%s', '%s', '%s' ),
                array( '%s' )
            );
        }
    }
    
    /**
     * Verify webhook (GET request from Meta)
     * 
     * Meta sends: ?hub.mode=subscribe&hub.challenge=XXXX&hub.verify_token=token
     */
    public function verify_webhook_get( WP_REST_Request $request ) {
        $mode = $request->get_param( 'hub_mode' );
        $challenge = $request->get_param( 'hub_challenge' );
        $verify_token = $request->get_param( 'hub_verify_token' );
        
        $expected_token = get_option( 'edubot_whatsapp_webhook_token', '' );
        
        if ( $mode === 'subscribe' && $verify_token === $expected_token ) {
            error_log( 'EduBot: Webhook verified by Meta' );
            return $challenge;
        }
        
        error_log( 'EduBot: Webhook verification failed' );
        return new WP_REST_Response( array( 'error' => 'Invalid token' ), 403 );
    }
}
