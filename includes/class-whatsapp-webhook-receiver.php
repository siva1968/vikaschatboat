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
        
        // Get or create session for this phone
        $session_id = $this->get_or_create_session( $phone );
        
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
     * Get or create session for phone number
     */
    private function get_or_create_session( $phone ) {
        // Try to find existing active session
        $session = EduBot_WhatsApp_Session_Manager::get_session_by_phone( $phone );
        
        if ( $session ) {
            return $session['session_id'];
        }
        
        // Create new session with ad params from metadata if available
        $ad_params = array(
            'source' => 'whatsapp_direct',
            'medium' => 'whatsapp'
        );
        
        return EduBot_WhatsApp_Session_Manager::create_session( $phone, $ad_params );
    }
    
    /**
     * Get chatbot response for user message
     */
    private function get_chatbot_response( $session_id, $message_text, $phone ) {
        try {
            // Get current session state
            $session_data = EduBot_WhatsApp_Session_Manager::get_session_data( $session_id );
            
            // Use existing chatbot engine or workflow manager
            $chatbot_engine = new EduBot_Chatbot_Engine();
            
            // Process message
            $response_data = $chatbot_engine->process_message(
                $message_text,
                $session_id,
                $phone
            );
            
            if ( is_wp_error( $response_data ) ) {
                error_log( 'EduBot: Chatbot error: ' . $response_data->get_error_message() );
                return 'Sorry, I encountered an error. Please try again.';
            }
            
            // Update session state if provided
            if ( isset( $response_data['session_data'] ) ) {
                EduBot_WhatsApp_Session_Manager::update_session_data( $session_id, $response_data['session_data'] );
            }
            
            // Return message
            return $response_data['message'] ?? $response_data['response'] ?? '';
            
        } catch ( Exception $e ) {
            error_log( 'EduBot: Exception getting chatbot response: ' . $e->getMessage() );
            return 'Thank you for your message. Our team will get back to you soon!';
        }
    }
    
    /**
     * Send response back to user via WhatsApp
     */
    private function send_whatsapp_response( $phone, $message, $session_id ) {
        try {
            // Get API integrations
            $api_integrations = new EduBot_API_Integrations();
            
            // Split long messages
            $messages = $this->split_long_messages( $message );
            
            foreach ( $messages as $msg ) {
                $result = $api_integrations->send_whatsapp( $phone, $msg );
                
                if ( !$result ) {
                    error_log( "EduBot: Failed to send WhatsApp response to {$phone}" );
                } else {
                    error_log( "EduBot: WhatsApp message sent to {$phone}" );
                    // Store outgoing message
                    EduBot_WhatsApp_Session_Manager::store_message( $session_id, 'bot', $msg );
                }
            }
            
        } catch ( Exception $e ) {
            error_log( 'EduBot: Exception sending WhatsApp response: ' . $e->getMessage() );
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
