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
    const API_URL = 'https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/bulk/';

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

        // Run through chatbot engine
        $response_text = $this->get_chatbot_response( $session_id, $message_text, $phone );

        if ( empty( $response_text ) ) {
            error_log( 'EduBot MSG91 Webhook: chatbot returned empty response' );
            $response_text = 'Thank you for reaching out! Our team will get back to you shortly.';
        }

        // Send reply back via MSG91
        $this->send_text_reply( $phone, $response_text );
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

    private function get_chatbot_response( string $session_id, string $message_text, string $phone ): string {
        try {
            $engine = new EduBot_Chatbot_Engine();
            $result = $engine->process_message( $message_text, $session_id );

            if ( is_wp_error( $result ) ) {
                error_log( 'EduBot MSG91 Webhook: chatbot WP_Error: ' . $result->get_error_message() );
                return '';
            }

            if ( is_array( $result ) ) {
                return $result['message'] ?? $result['response'] ?? '';
            }

            return (string) $result;

        } catch ( Exception $e ) {
            error_log( 'EduBot MSG91 Webhook: chatbot exception: ' . $e->getMessage() );
            return '';
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Reply via MSG91 text API
    // ─────────────────────────────────────────────────────────────

    /**
     * Send a freeform text reply to the user via MSG91.
     *
     * Uses the MSG91 outbound bulk API with content_type=text.
     * This works when the conversation is still within the 24-hour
     * session window opened by the user's inbound message.
     */
    private function send_text_reply( string $phone, string $message ) {
        global $wpdb;

        // Load MSG91 credentials from wp_edubot_api_integrations
        $table = $wpdb->prefix . 'edubot_api_integrations';
        $row   = $wpdb->get_row(
            "SELECT whatsapp_token, whatsapp_phone_id FROM {$table} WHERE whatsapp_provider = 'msg91' LIMIT 1",
            ARRAY_A
        );

        if ( empty( $row['whatsapp_token'] ) || empty( $row['whatsapp_phone_id'] ) ) {
            error_log( 'EduBot MSG91 Webhook: MSG91 credentials not found in DB (provider=msg91)' );
            return false;
        }

        $authkey           = $row['whatsapp_token'];
        $integrated_number = $row['whatsapp_phone_id'];

        // Normalise phone: strip leading + 
        $phone = ltrim( preg_replace( '/[^0-9+]/', '', $phone ), '+' );
        if ( strlen( $phone ) === 10 ) {
            $phone = '91' . $phone;
        }

        // Split long messages (WhatsApp 4096-char limit)
        $chunks = $this->split_message( $message );

        foreach ( $chunks as $chunk ) {
            $payload = array(
                'integrated_number' => $integrated_number,
                'content_type'      => 'text',
                'payload'           => array(
                    'to'                => $phone,
                    'type'              => 'text',
                    'messaging_product' => 'whatsapp',
                    'text'              => array(
                        'body' => $chunk,
                    ),
                ),
            );

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
                error_log( 'EduBot MSG91 Reply Error: ' . $response->get_error_message() );
                return false;
            }

            $status = wp_remote_retrieve_response_code( $response );
            $body   = wp_remote_retrieve_body( $response );
            error_log( "EduBot MSG91 Reply HTTP {$status}: {$body}" );
        }

        return true;
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
