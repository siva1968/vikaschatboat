<?php
/**
 * MSG91 WhatsApp Webhook Receiver
 *
 * Handles inbound WhatsApp messages received via MSG91 webhook,
 * routes them through the EduBot chatbot engine, and replies
 * using the MSG91 outbound text API.
 *
 * Webhook URL to configure in MSG91 → WhatsApp → Webhook:
 *   POST  https://yoursite.com/wp-json/edubot/v1/msg91-webhook
 *
 * @package EduBot_Pro
 * @subpackage Integrations
 * @version 1.0.0
 */

class EduBot_MSG91_Webhook_Receiver {

    /** MSG91 outbound message API */
    const API_URL = 'https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/';

    // ─────────────────────────────────────────────────────────────
    // Entry point (registered as REST callback)
    // ─────────────────────────────────────────────────────────────

    /**
     * Handle POST from MSG91 webhook
     */
    public function handle_webhook( WP_REST_Request $request ) {
        $raw  = $request->get_body();
        $data = json_decode( $raw, true );

        error_log( 'EduBot MSG91 Webhook: received payload: ' . $raw );

        if ( empty( $data ) ) {
            return new WP_REST_Response( array( 'error' => 'Empty payload' ), 400 );
        }

        try {
            $this->process_payload( $data );
        } catch ( Exception $e ) {
            error_log( 'EduBot MSG91 Webhook exception: ' . $e->getMessage() );
        }

        // Always return 200 so MSG91 does not retry
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    // ─────────────────────────────────────────────────────────────
    // Payload processing
    // ─────────────────────────────────────────────────────────────

    private function process_payload( array $data ) {
        $direction    = strtolower( $data['direction']   ?? '' );
        $webhook_type = strtolower( $data['webhookType'] ?? '' );
        $event_name   = strtolower( $data['eventName']   ?? '' );

        // Only handle inbound (user → us) messages
        $is_inbound = ( $direction === 'inbound' )
                   || ( strpos( $webhook_type, 'inbound' ) !== false )
                   || ( strpos( $event_name, 'received' ) !== false )
                   || ( strpos( $event_name, 'inbound' ) !== false );

        if ( ! $is_inbound ) {
            error_log( 'EduBot MSG91 Webhook: skipping non-inbound event: direction=' . $direction . ' webhookType=' . $webhook_type );
            return;
        }

        // Extract phone (customerNumber includes country code, e.g. 919959125333)
        $phone = sanitize_text_field( $data['customerNumber'] ?? '' );
        if ( empty( $phone ) ) {
            error_log( 'EduBot MSG91 Webhook: missing customerNumber' );
            return;
        }

        // Extract message text: MSG91 puts it in `text` for text messages
        $message_text = trim( $data['text'] ?? $data['content'] ?? '' );
        if ( empty( $message_text ) ) {
            error_log( 'EduBot MSG91 Webhook: empty message text for ' . $phone );
            return;
        }

        error_log( "EduBot MSG91 Webhook: inbound from {$phone}: {$message_text}" );

        // Get or create a stable session_id keyed to the phone number
        $session_id = $this->get_session_id( $phone );

        // Run through chatbot engine — returns array with 'message' and optionally 'options'
        $chatbot = $this->get_chatbot_response( $session_id, $message_text, $phone );

        $response_text = $chatbot['message'] ?? '';
        $options       = $chatbot['options'] ?? array();

        if ( empty( $response_text ) ) {
            error_log( 'EduBot MSG91 Webhook: chatbot returned empty response' );
            $response_text = 'Thank you for reaching out! Our team will get back to you shortly.';
            $options       = array();
        }

        // Send interactive buttons when options exist (max 3 per MSG91 limit)
        if ( ! empty( $options ) ) {
            $this->send_interactive_reply( $phone, $response_text, array_slice( $options, 0, 3 ) );
        } else {
            $this->send_text_reply( $phone, $response_text );
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Session management
    // ─────────────────────────────────────────────────────────────

    /**
     * Return a stable, engine-compatible session ID for a phone number.
     * Format: wa + 18 hex chars (total 20, within the 10-40 char limit).
     */
    private function get_session_id( string $phone ): string {
        return 'wa' . substr( md5( $phone ), 0, 18 );
    }

    // ─────────────────────────────────────────────────────────────
    // Chatbot engine
    // ─────────────────────────────────────────────────────────────

    /**
     * Run the chatbot engine and return array:
     *   [ 'message' => string, 'options' => array ]
     */
    private function get_chatbot_response( string $session_id, string $message_text, string $phone ): array {
        try {
            $engine = new EduBot_Chatbot_Engine();
            $result = $engine->process_message( $message_text, $session_id );

            // Debug: log what the engine returned
            error_log( 'EduBot MSG91 Webhook: engine result type=' . gettype( $result ) . ' class=' . ( is_object( $result ) ? get_class( $result ) : 'n/a' ) . ' is_wp_error=' . ( is_wp_error( $result ) ? 'YES' : 'NO' ) );

            if ( is_wp_error( $result ) ) {
                error_log( 'EduBot MSG91 Webhook: chatbot WP_Error: ' . $result->get_error_message() );
                return array( 'message' => '', 'options' => array() );
            }

            if ( is_array( $result ) ) {
                $msg = $result['message'] ?? $result['response'] ?? '';
                // The engine may store a WP_Error inside 'message' — unwrap it
                if ( is_wp_error( $msg ) ) {
                    error_log( 'EduBot MSG91 Webhook: engine message is WP_Error: ' . $msg->get_error_message() );
                    $msg = '';
                }
                return array(
                    'message' => (string) $msg,
                    'options' => $result['options'] ?? array(),
                );
            }

            return array( 'message' => (string) $result, 'options' => array() );

        } catch ( \Throwable $e ) {
            error_log( 'EduBot MSG91 Webhook: chatbot exception (' . get_class( $e ) . '): ' . $e->getMessage() );
            return array( 'message' => '', 'options' => array() );
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Reply via MSG91 text API
    // ─────────────────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────
    // Load MSG91 credentials helper
    // ─────────────────────────────────────────────────────────────

    private function get_msg91_credentials(): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'edubot_api_integrations';
        $row   = $wpdb->get_row(
            "SELECT whatsapp_token, whatsapp_phone_id FROM {$table} WHERE whatsapp_provider = 'msg91' LIMIT 1",
            ARRAY_A
        );
        if ( empty( $row['whatsapp_token'] ) || empty( $row['whatsapp_phone_id'] ) ) {
            error_log( 'EduBot MSG91 Webhook: MSG91 credentials not found in DB (provider=msg91)' );
            return null;
        }
        return $row;
    }

    private function normalise_phone( string $phone ): string {
        $phone = ltrim( preg_replace( '/[^0-9+]/', '', $phone ), '+' );
        if ( strlen( $phone ) === 10 ) {
            $phone = '91' . $phone;
        }
        return $phone;
    }

    private function post_to_msg91( string $authkey, array $payload ): void {
        $response = wp_remote_post( self::API_URL, array(
            'headers' => array(
                'authkey'      => $authkey,
                'content-type' => 'application/json',
                'accept'       => 'application/json',
            ),
            'body'    => wp_json_encode( $payload ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( 'EduBot MSG91 API Error: ' . $response->get_error_message() );
            return;
        }

        $status = wp_remote_retrieve_response_code( $response );
        $body   = wp_remote_retrieve_body( $response );
        error_log( "EduBot MSG91 Reply HTTP {$status}: {$body}" );
    }

    // ─────────────────────────────────────────────────────────────
    // Reply via MSG91 text API
    // ─────────────────────────────────────────────────────────────

    /**
     * Send a plain-text reply using MSG91 "Send Message (once Session Started)" API.
     *
     * Endpoint: POST /whatsapp-outbound-message/?integrated_number=&recipient_number=&content_type=text
     * Body:     { "text": "message content" }
     * Ref: https://docs.msg91.com/whatsapp/send-message-in-text
     */
    private function send_text_reply( string $phone, string $message ): void {
        $creds = $this->get_msg91_credentials();
        if ( ! $creds ) return;

        $phone  = $this->normalise_phone( $phone );
        $chunks = $this->split_message( $message );

        foreach ( $chunks as $chunk ) {
            $url = add_query_arg( array(
                'integrated_number' => $creds['whatsapp_phone_id'],
                'recipient_number'  => $phone,
                'content_type'      => 'text',
            ), self::API_URL );

            $response = wp_remote_post( $url, array(
                'headers' => array(
                    'authkey'      => $creds['whatsapp_token'],
                    'content-type' => 'application/json',
                    'accept'       => 'application/json',
                ),
                'body'    => wp_json_encode( array( 'text' => $chunk ) ),
                'timeout' => 30,
            ) );

            if ( is_wp_error( $response ) ) {
                error_log( 'EduBot MSG91 Text Reply Error: ' . $response->get_error_message() );
                return;
            }

            $status = wp_remote_retrieve_response_code( $response );
            $body   = wp_remote_retrieve_body( $response );
            error_log( "EduBot MSG91 Text Reply HTTP {$status}: {$body}" );
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Reply via MSG91 interactive buttons API
    // ─────────────────────────────────────────────────────────────

    /**
     * Send an interactive button message (max 3 options).
     *
     * Each $option must have at minimum a 'text' key.
     * Uses MSG91 interactive buttons API:
     * https://docs.msg91.com/whatsapp/interactive-whatsapp-buttons
     */
    private function send_interactive_reply( string $phone, string $body_text, array $options ): void {
        $creds = $this->get_msg91_credentials();
        if ( ! $creds ) return;

        $phone = $this->normalise_phone( $phone );

        // Build button objects — MSG91 requires id (≤256 chars) and title (≤20 chars)
        $buttons = array();
        foreach ( array_slice( $options, 0, 3 ) as $idx => $opt ) {
            $label     = isset( $opt['text'] )  ? $opt['text']  : ( $opt['label'] ?? "Option {$idx}" );
            $btn_id    = isset( $opt['value'] ) ? $opt['value'] : "opt_{$idx}";
            // Truncate: id ≤ 256, title ≤ 20 chars
            $btn_id    = substr( sanitize_key( $btn_id ), 0, 256 );
            $title     = mb_substr( wp_strip_all_tags( $label ), 0, 20 );
            $buttons[] = array(
                'type'  => 'reply',
                'reply' => array(
                    'id'    => $btn_id,
                    'title' => $title,
                ),
            );
        }

        $payload = array(
            'recipient_number'  => $phone,
            'integrated_number' => $creds['whatsapp_phone_id'],
            'content_type'      => 'interactive',
            'interactive'       => array(
                'type'   => 'button',
                'body'   => array(
                    'text' => $body_text,
                ),
                'action' => array(
                    'buttons' => $buttons,
                ),
            ),
        );

        $this->post_to_msg91( $creds['whatsapp_token'], $payload );
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function split_message( string $message, int $limit = 4000 ): array {
        if ( strlen( $message ) <= $limit ) {
            return array( $message );
        }

        $chunks  = array();
        $current = '';

        foreach ( explode( "\n", $message ) as $line ) {
            if ( strlen( $current ) + strlen( $line ) + 1 > $limit ) {
                if ( $current !== '' ) {
                    $chunks[] = $current;
                }
                $current = $line;
            } else {
                $current .= ( $current !== '' ? "\n" : '' ) . $line;
            }
        }

        if ( $current !== '' ) {
            $chunks[] = $current;
        }

        return $chunks;
    }
}
