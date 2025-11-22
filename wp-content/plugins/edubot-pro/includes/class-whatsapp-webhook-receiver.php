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
     * Handle incoming webhook from Meta WhatsApp Business API v24.0
     * 
     * Endpoint: POST /wp-json/edubot/v1/whatsapp-webhook
     * Supports enhanced webhook format with entry array structure
     */
    public function handle_webhook( WP_REST_Request $request ) {
        error_log( 'EduBot: WhatsApp webhook received (v24.0)' );
        
        try {
            // Check if this is a forwarded request from our standalone webhook
            $user_agent = $request->get_header( 'User-Agent' );
            $is_forwarded = strpos( $user_agent, 'WhatsApp-Webhook-Forwarder' ) !== false;
            
            // Verify webhook signature for security (v24.0 enhancement)
            // Skip verification for forwarded requests from our trusted webhook forwarder
            if ( !$is_forwarded && !$this->verify_webhook_signature( $request ) ) {
                error_log( 'EduBot: Invalid webhook signature' );
                return new WP_REST_Response( array( 'error' => 'Invalid signature' ), 401 );
            }
            
            if ( $is_forwarded ) {
                error_log( 'EduBot: Processing forwarded WhatsApp webhook from standalone file' );
            }
            
            $data = $request->get_json_params();
            error_log( 'EduBot: Webhook data (v24.0): ' . print_r( $data, true ) );
            
            // v24.0 uses 'entry' array structure
            if ( isset( $data['entry'] ) && is_array( $data['entry'] ) ) {
                foreach ( $data['entry'] as $entry ) {
                    $this->process_webhook_entry( $entry );
                }
                return new WP_REST_Response( array( 'success' => true ) );
            }
            
            // Legacy format support (for backward compatibility)
            if ( isset( $data['messages'] ) && is_array( $data['messages'] ) ) {
                foreach ( $data['messages'] as $message_data ) {
                    $this->process_incoming_message( $message_data );
                }
                return new WP_REST_Response( array( 'success' => true ) );
            }
            
            // Handle status updates (message delivery, read status)
            if ( isset( $data['statuses'] ) && is_array( $data['statuses'] ) ) {
                $this->handle_status_updates( $data['statuses'] );
                return new WP_REST_Response( array( 'success' => true ) );
            }
            
            return new WP_REST_Response( array( 'success' => true ) );
            
        } catch ( Exception $e ) {
            error_log( 'EduBot: Webhook error: ' . $e->getMessage() );
            return new WP_REST_Response( array( 'error' => $e->getMessage() ), 500 );
        }
    }

    /**
     * Process webhook entry (v24.0 format)
     */
    private function process_webhook_entry( $entry ) {
        if ( !isset( $entry['changes'] ) || !is_array( $entry['changes'] ) ) {
            return;
        }
        
        foreach ( $entry['changes'] as $change ) {
            $field = $change['field'] ?? '';
            $value = $change['value'] ?? array();
            
            switch ( $field ) {
                case 'messages':
                    if ( isset( $value['messages'] ) && is_array( $value['messages'] ) ) {
                        foreach ( $value['messages'] as $message_data ) {
                            $this->process_incoming_message( $message_data, $value );
                        }
                    }
                    break;
                    
                case 'message_template_status_update':
                    $this->handle_template_status_update( $value );
                    break;
                    
                case 'account_alerts':
                    $this->handle_account_alerts( $value );
                    break;
                    
                case 'business_capability_update':
                    $this->handle_capability_update( $value );
                    break;
                    
                default:
                    error_log( 'EduBot: Unknown webhook field: ' . $field );
            }
        }
    }

    /**
     * Verify webhook signature for enhanced security (v24.0)
     */
    private function verify_webhook_signature( WP_REST_Request $request ) {
        $app_secret = get_option( 'edubot_whatsapp_app_secret', '' );
        
        // If no app secret configured, fall back to token verification
        if ( empty( $app_secret ) ) {
            return $this->verify_webhook_token( $request );
        }
        
        $signature = $request->get_header( 'X-Hub-Signature-256' );
        if ( empty( $signature ) ) {
            error_log( 'EduBot: No signature header found' );
            return false;
        }
        
        $payload = $request->get_body();
        $expected_signature = 'sha256=' . hash_hmac( 'sha256', $payload, $app_secret );
        
        if ( !hash_equals( $expected_signature, $signature ) ) {
            error_log( 'EduBot: Signature mismatch' );
            return false;
        }
        
        return true;
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
     * Process incoming message from user (Enhanced for v24.0)
     */
    private function process_incoming_message( $message_data, $webhook_value = array() ) {
        $phone = $message_data['from'] ?? '';
        
        // Normalize phone number - remove spaces and special characters (Meta API requirement)
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        $message_id = $message_data['id'] ?? '';
        $timestamp = $message_data['timestamp'] ?? '';
        $type = $message_data['type'] ?? 'text';
        
        if ( empty( $phone ) || empty( $message_id ) ) {
            error_log( 'EduBot: Missing phone or message ID' );
            return;
        }
        
        // Extract message content based on type (supports v24.0 interactive messages)
        $message_text = $this->extract_message_content_v24( $message_data, $type );
        
        if ( empty( $message_text ) ) {
            error_log( 'EduBot: Could not extract message content for type: ' . $type );
            return;
        }
        
        // Extract additional context from webhook value (v24.0)
        $message_context = $this->extract_message_context( $message_data, $webhook_value );
        
        error_log( "EduBot: Processing message from {$phone}: {$message_text}" );
        
        // Extract platform and campaign from tracking info in message
        $platform_data = $this->extract_platform_from_message( $message_text );
        
        // Get or create session for this phone
        $session_id = $this->get_or_create_session( $phone, $platform_data );
        
        if ( empty( $session_id ) ) {
            error_log( 'EduBot: Could not create session for phone: ' . $phone );
            return;
        }
        
        // Store incoming message in both systems for full compatibility
        try {
            // Load WhatsApp session manager if not already loaded
            if ( !class_exists( 'EduBot_WhatsApp_Session_Manager' ) ) {
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-whatsapp-session-manager.php';
            }
            EduBot_WhatsApp_Session_Manager::store_message( $session_id, 'user', $message_text, $message_id );
        } catch ( Exception $e ) {
            error_log( 'EduBot WhatsApp: Could not store message in WhatsApp session: ' . $e->getMessage() );
        }
        
        // Also store in main session manager (same as web chatbot)
        if ( !class_exists( 'EduBot_Session_Manager' ) ) {
            require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-session-manager.php';
        }
        $session_manager = EduBot_Session_Manager::getInstance();
        $session_manager->update_session_data( $session_id, '_last_message', $message_text );
        $session_manager->update_session_data( $session_id, '_last_message_time', current_time( 'mysql' ) );
        
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
     * Extract message content based on message type (Enhanced for v24.0)
     */
    private function extract_message_content_v24( $message_data, $type ) {
        switch ( $type ) {
            case 'text':
                return $message_data['text']['body'] ?? '';
            
            case 'button':
                return $message_data['button']['text'] ?? '';
            
            case 'interactive':
                return $this->extract_interactive_content( $message_data['interactive'] ?? array() );
            
            case 'location':
                $location = $message_data['location'] ?? array();
                return sprintf( '[LOCATION: %s, %s]', 
                    $location['latitude'] ?? '0', 
                    $location['longitude'] ?? '0'
                );
            
            case 'contacts':
                $contacts = $message_data['contacts'] ?? array();
                if ( !empty( $contacts[0]['name']['formatted_name'] ) ) {
                    return '[CONTACT: ' . $contacts[0]['name']['formatted_name'] . ']';
                }
                return '[CONTACT]';
            
            case 'reaction':
                $reaction = $message_data['reaction'] ?? array();
                return '[REACTION: ' . ($reaction['emoji'] ?? '👍') . ']';
            
            case 'order':
                return '[ORDER: ' . ($message_data['order']['product_items'][0]['product_retailer_id'] ?? 'Unknown') . ']';
            
            case 'system':
                return '[SYSTEM: ' . ($message_data['system']['body'] ?? 'System message') . ']';
            
            case 'image':
            case 'document':
            case 'audio':
            case 'video':
            case 'sticker':
                return $this->extract_media_content( $message_data, $type );
            
            default:
                error_log( 'EduBot: Unknown message type: ' . $type );
                return '[UNKNOWN_TYPE: ' . strtoupper( $type ) . ']';
        }
    }

    /**
     * Extract interactive message content (v24.0)
     */
    private function extract_interactive_content( $interactive_data ) {
        if ( isset( $interactive_data['button_reply'] ) ) {
            $button = $interactive_data['button_reply'];
            return $button['title'] ?? ($button['id'] ?? '[BUTTON]');
        }
        
        if ( isset( $interactive_data['list_reply'] ) ) {
            $list = $interactive_data['list_reply'];
            return $list['title'] ?? ($list['id'] ?? '[LIST_ITEM]');
        }
        
        if ( isset( $interactive_data['nfm_reply'] ) ) {
            // Native Flow Messages (v24.0 feature)
            return '[FLOW: ' . ($interactive_data['nfm_reply']['name'] ?? 'Unknown') . ']';
        }
        
        return '[INTERACTIVE]';
    }

    /**
     * Extract media message content with caption (v24.0)
     */
    private function extract_media_content( $message_data, $type ) {
        $media_data = $message_data[$type] ?? array();
        $caption = $media_data['caption'] ?? '';
        
        if ( !empty( $caption ) ) {
            return $caption;
        }
        
        // Return media type indicator if no caption
        return '[' . strtoupper( $type ) . ']';
    }

    /**
     * Extract message context from webhook data (v24.0)
     */
    private function extract_message_context( $message_data, $webhook_value ) {
        $context = array(
            'profile_name' => '',
            'wa_id' => '',
            'forwarded' => false,
            'frequently_forwarded' => false,
            'from_business' => false
        );
        
        // Extract profile information
        if ( isset( $webhook_value['contacts'] ) && is_array( $webhook_value['contacts'] ) ) {
            foreach ( $webhook_value['contacts'] as $contact ) {
                if ( $contact['wa_id'] === ($message_data['from'] ?? '') ) {
                    $context['profile_name'] = $contact['profile']['name'] ?? '';
                    $context['wa_id'] = $contact['wa_id'];
                    break;
                }
            }
        }
        
        // Check message context
        if ( isset( $message_data['context'] ) ) {
            $msg_context = $message_data['context'];
            $context['forwarded'] = $msg_context['forwarded'] ?? false;
            $context['frequently_forwarded'] = $msg_context['frequently_forwarded'] ?? false;
            $context['from_business'] = isset( $msg_context['from'] );
        }
        
        return $context;
    }

    /**
     * Handle template status updates (v24.0)
     */
    private function handle_template_status_update( $value ) {
        $event = $value['event'] ?? '';
        $message_template_id = $value['message_template_id'] ?? '';
        $message_template_name = $value['message_template_name'] ?? '';
        
        error_log( "EduBot: Template status update - Event: {$event}, Template: {$message_template_name}" );
        
        // Store template status in database for monitoring
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'edubot_whatsapp_template_status',
            array(
                'template_id' => $message_template_id,
                'template_name' => $message_template_name,
                'event' => $event,
                'status_data' => wp_json_encode( $value ),
                'created_at' => current_time( 'mysql' )
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Handle account alerts (v24.0)
     */
    private function handle_account_alerts( $value ) {
        $alert_type = $value['alert_type'] ?? '';
        error_log( "EduBot: Account alert received - Type: {$alert_type}" );
        
        // Log important account alerts
        if ( in_array( $alert_type, array( 'RATE_LIMIT', 'TEMPLATE_LIMIT', 'ACCOUNT_FLAGGED' ) ) ) {
            EduBot_Admin::log_api_request_to_db(
                'whatsapp_account_alert',
                'WEBHOOK',
                'meta_business_api',
                array( 'alert_type' => $alert_type ),
                $value,
                200,
                'warning',
                'WhatsApp account alert: ' . $alert_type
            );
        }
    }

    /**
     * Handle business capability updates (v24.0)
     */
    private function handle_capability_update( $value ) {
        error_log( 'EduBot: Business capability update received: ' . wp_json_encode( $value ) );
        
        // Update stored capabilities if needed
        $capabilities = get_option( 'edubot_whatsapp_capabilities', array() );
        $capabilities[ $value['capability'] ?? 'unknown' ] = $value;
        update_option( 'edubot_whatsapp_capabilities', $capabilities );
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
        
        // NEW SIMPLE: Try to extract [Campaign: Campaign Name] format
        if ( preg_match( '/\[Campaign:\s*([^\]]+?)\]/', $message_text, $matches ) ) {
            $campaign_name = trim( $matches[1] );
            
            // Load campaign manager to get campaign details
            if ( !class_exists( 'EduBot_WhatsApp_Campaign_Manager' ) ) {
                require_once plugin_dir_path( __FILE__ ) . 'class-whatsapp-campaign-manager.php';
            }
            
            $campaign = EduBot_WhatsApp_Campaign_Manager::get_campaign( $campaign_name );
            
            if ( $campaign ) {
                // Extract UTM data from campaign configuration
                $utm_source = str_replace( '_ads', '', $campaign['platform'] );
                $utm_campaign = sanitize_title( $campaign_name );
                
                $data['platform_source'] = $utm_source;
                $data['campaign_name'] = $campaign_name;
                $data['utm_source'] = $utm_source;
                $data['utm_medium'] = 'whatsapp_click_to_chat';
                $data['utm_campaign'] = $utm_campaign;
                
                error_log( 'EduBot: Extracted campaign from simple format - Campaign: ' . $campaign_name . ', Platform: ' . $utm_source );
                
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
        $message_text = $this->extract_message_content_v24( $message_data, $message_data['type'] ?? 'text' );
        if ( !empty( $message_text ) ) {
            $tracking_data = $this->extract_platform_from_message( $message_text );
            if ( !empty( $tracking_data['platform_source'] ) && $tracking_data['platform_source'] !== 'unknown' ) {
                $data = array_merge( $data, $tracking_data );
            }
        }
        
        return $data;
    }
    
    /**
     * Get or create session for phone number (UNIFIED with web chatbot)
     */
    private function get_or_create_session( $phone, $platform_data = array() ) {
        // Load the same session manager used by web chatbot
        if ( !class_exists( 'EduBot_Session_Manager' ) ) {
            require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-session-manager.php';
        }
        
        // Load WhatsApp session manager
        if ( !class_exists( 'EduBot_WhatsApp_Session_Manager' ) ) {
            require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-whatsapp-session-manager.php';
        }
        
        $session_manager = EduBot_Session_Manager::getInstance();
        
        // Try to find existing WhatsApp session first
        $whatsapp_session = EduBot_WhatsApp_Session_Manager::get_session_by_phone( $phone );
        
        if ( $whatsapp_session && !empty( $whatsapp_session['session_id'] ) ) {
            $session_id = $whatsapp_session['session_id'];
            
            // Ensure this session exists in the main session manager too
            $main_session = $session_manager->get_session( $session_id );
            if ( !$main_session ) {
                // Initialize in main session manager to maintain compatibility
                error_log( "EduBot WhatsApp: Initializing existing WhatsApp session {$session_id} in main session manager" );
                $session_manager->init_session( $session_id, 'admission' );
            }
            
            // Always store platform information when new campaign data is available
            if ( !empty( $platform_data ) ) {
                error_log( "EduBot WhatsApp: Storing platform data in existing session {$session_id}" );
                foreach ( $platform_data as $key => $value ) {
                    if ( !empty( $value ) ) {
                        $session_manager->update_session_data( $session_id, '_' . $key, $value );
                        error_log( "EduBot WhatsApp: Updated _{$key} = {$value} in session {$session_id}" );
                    }
                }
                
                // Also ensure basic platform info is set
                $session_manager->update_session_data( $session_id, '_platform', 'whatsapp' );
            }
            
            return $session_id;
        }
        
        // Generate session ID compatible with both systems
        $session_id = 'whatsapp_' . uniqid( '', true );
        
        // Create session in main session manager (same as web chatbot)
        $main_session_data = $session_manager->init_session( $session_id, 'admission' );
        
        // Store platform information in main session
        $session_manager->update_session_data( $session_id, '_platform', 'whatsapp' );
        $session_manager->update_session_data( $session_id, '_phone', $phone );
        
        if ( !empty( $platform_data ) ) {
            foreach ( $platform_data as $key => $value ) {
                $session_manager->update_session_data( $session_id, '_' . $key, $value );
            }
        }
        
        // Create parallel WhatsApp session for attribution tracking
        $ad_params = array(
            'source' => $platform_data['platform_source'] ?? 'whatsapp_direct',
            'medium' => 'whatsapp',
            'campaign' => $platform_data['campaign_name'] ?? 'unknown',
            'utm_source' => $platform_data['utm_source'] ?? '',
            'utm_campaign' => $platform_data['utm_campaign'] ?? ''
        );
        
        try {
            EduBot_WhatsApp_Session_Manager::create_session_with_id( $session_id, $phone, $ad_params );
        } catch ( Exception $e ) {
            error_log( "EduBot WhatsApp: Could not create WhatsApp session tracking: " . $e->getMessage() );
        }
        
        error_log( "EduBot WhatsApp: Created unified session {$session_id} for phone {$phone}" );
        
        return $session_id;
    }
    
    /**
     * Get chatbot response using EXACT same workflow as web chatbot
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
            
            // Load ALL dependencies exactly as web chatbot does
            if ( !class_exists( 'EduBot_Session_Manager' ) ) {
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-session-manager.php';
            }
            if ( !class_exists( 'EduBot_API_Integrations' ) ) {
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-school-config.php';
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-security-manager.php';
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-api-integrations.php';
            }
            if ( !class_exists( 'EduBot_Workflow_Manager' ) ) {
                require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-workflow-manager.php';
            }
            
            // Initialize workflow manager exactly as web chatbot does
            $workflow_manager = new EduBot_Workflow_Manager();
            
            // Store phone in session for workflow manager (critical for notifications)
            $session_manager = EduBot_Session_Manager::getInstance();
            $session_manager->update_session_data( $session_id, '_whatsapp_phone', $phone );
            $session_manager->update_session_data( $session_id, '_phone_for_notifications', $phone );
            $session_manager->update_session_data( $session_id, '_notification_method', 'whatsapp' );
            $session_manager->update_session_data( $session_id, '_contact_phone', $phone );
            
            error_log( "EduBot WhatsApp: Processing message through unified workflow manager for session {$session_id}" );
            
            // Process message through workflow manager with EXACT same flow as web
            $workflow_response = $workflow_manager->process_user_input( $message_text, $session_id );
            
            if ( empty( $workflow_response ) ) {
                error_log( 'EduBot WhatsApp: Empty response from workflow manager, using fallback' );
                $workflow_response = "Hello! I'm here to help you with school admissions. " .
                                   "Could you please provide your child's name and the grade you're interested in?";
            }
            
            error_log( "EduBot WhatsApp: Workflow response (first 200 chars): " . substr( $workflow_response, 0, 200 ) );
            
            // CRITICAL: Check if workflow completed and trigger notifications (matching web chatbot exactly)
            if ( strpos( $workflow_response, 'Admission Enquiry Submitted Successfully' ) !== false ) {
                error_log( "EduBot WhatsApp: Workflow completion detected, notifications should have been sent automatically" );
                
                // Extract enquiry number from response for logging
                if ( preg_match( '/ENQ\d+/', $workflow_response, $matches ) ) {
                    $enquiry_number = $matches[0];
                    error_log( "EduBot WhatsApp: Successfully completed workflow for enquiry {$enquiry_number} with notifications" );
                }
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
            error_log( 'EduBot WhatsApp: Exception in get_chatbot_response: ' . $e->getMessage() );
            error_log( 'EduBot WhatsApp: Stack trace: ' . $e->getTraceAsString() );
            
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
            
            return "Hello! I'm here to help you with school admissions. " .
                   "Could you please provide your child's name and the grade you're interested in?";
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
                
                // Use same API method as web chatbot (with v24.0 support)
                $result = $api_integrations->send_meta_whatsapp( $phone, $msg, array() );
                
                if ( !$result ) {
                    error_log( "EduBot WhatsApp: Failed to send message {$message_num} to {$phone}" );
                    
                    // Log failed message
                    EduBot_Admin::log_api_request_to_db(
                        'whatsapp_send_failed',
                        'POST',
                        'meta_whatsapp_api_v24',
                        array('phone' => $phone, 'message' => substr($msg, 0, 100)),
                        array(),
                        500,
                        'error',
                        'Failed to send WhatsApp message (v24.0)'
                    );
                } else {
                    $success_count++;
                    error_log( "EduBot WhatsApp: Message {$message_num} sent successfully to {$phone}" );
                    
                    // Store outgoing message in both systems
                    try {
                        // Load WhatsApp session manager if not already loaded
                        if ( !class_exists( 'EduBot_WhatsApp_Session_Manager' ) ) {
                            require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-whatsapp-session-manager.php';
                        }
                        EduBot_WhatsApp_Session_Manager::store_message( $session_id, 'bot', $msg );
                    } catch ( Exception $e ) {
                        error_log( 'EduBot WhatsApp: Could not store outgoing message in WhatsApp session: ' . $e->getMessage() );
                    }
                    
                    // Store in main session manager too
                    if ( !class_exists( 'EduBot_Session_Manager' ) ) {
                        require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-session-manager.php';
                    }
                    $session_manager = EduBot_Session_Manager::getInstance();
                    $session_manager->update_session_data( $session_id, '_last_response', $msg );
                    $session_manager->update_session_data( $session_id, '_last_response_time', current_time( 'mysql' ) );
                    
                    // Log successful message
                    EduBot_Admin::log_api_request_to_db(
                        'whatsapp_send_success',
                        'POST',
                        'meta_whatsapp_api_v24',
                        array('phone' => $phone, 'message' => substr($msg, 0, 100)),
                        $result,
                        200,
                        'success',
                        'WhatsApp message sent successfully (v24.0)'
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
     * Process message called from standalone webhook file
     */
    public function process_message( $message_data, $metadata = array() ) {
        error_log( 'EduBot WhatsApp: Processing message via standalone webhook: ' . json_encode( $message_data ) );
        
        // Call the existing process_incoming_message method with proper webhook value format
        $webhook_value = array( 'metadata' => $metadata );
        $this->process_incoming_message( $message_data, $webhook_value );
    }
    
    /**
     * Verify webhook (GET request from Meta)
     * 
     * Meta sends: ?hub.mode=subscribe&hub.challenge=XXXX&hub.verify_token=token
     */
    public function verify_webhook_get( WP_REST_Request $request ) {
        // Meta sends hub.mode, hub.challenge, hub.verify_token
        // WordPress REST API preserves the dot notation in parameter names
        $mode = $request->get_param( 'hub.mode' );
        $challenge = $request->get_param( 'hub.challenge' );
        $verify_token = $request->get_param( 'hub.verify_token' );
        
        $expected_token = get_option( 'edubot_whatsapp_webhook_token', '' );
        
        error_log( 'EduBot: Webhook verification - Mode: ' . $mode . ', Token: ' . $verify_token . ', Expected: ' . $expected_token );
        
        if ( $mode === 'subscribe' && $verify_token === $expected_token && !empty( $expected_token ) ) {
            error_log( 'EduBot: Webhook verified by Meta successfully' );
            // Return challenge without any WordPress formatting
            status_header( 200 );
            header( 'Content-Type: text/plain' );
            echo $challenge;
            exit;
        }
        
        error_log( 'EduBot: Webhook verification failed - Mode: ' . $mode . ', Token match: ' . ( $verify_token === $expected_token ? 'yes' : 'no' ) );
        return new WP_REST_Response( array( 'error' => 'Verification failed' ), 403 );
    }
}
