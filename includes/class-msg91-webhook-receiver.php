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

        // ── Extract message text ────────────────────────────────
        // Priority:
        //   1. Interactive button reply → use button ID as the selection value
        //   2. Interactive list reply   → use list row ID as the selection value
        //   3. Plain text field
        $message_text = '';
        $msg_type     = strtolower( $data['type'] ?? 'text' );

        if ( $msg_type === 'interactive' ) {
            $interactive = $data['interactive'] ?? array();
            $int_type    = strtolower( $interactive['type'] ?? '' );

            if ( $int_type === 'button_reply' ) {
                // User tapped a reply button — use the button ID as the message
                $message_text = trim( $interactive['button_reply']['id'] ?? $interactive['button_reply']['title'] ?? '' );
                error_log( "EduBot MSG91 Webhook: button_reply from {$phone}: id={$message_text}" );

            } elseif ( $int_type === 'list_reply' ) {
                // User selected a list item
                $message_text = trim( $interactive['list_reply']['id'] ?? $interactive['list_reply']['title'] ?? '' );
                error_log( "EduBot MSG91 Webhook: list_reply from {$phone}: id={$message_text}" );
            }
        }

        // Fall back to plain text if still empty
        if ( empty( $message_text ) ) {
            $message_text = trim( $data['text'] ?? $data['content'] ?? '' );
        }

        if ( empty( $message_text ) ) {
            error_log( 'EduBot MSG91 Webhook: empty message text for ' . $phone );
            return;
        }

        error_log( "EduBot MSG91 Webhook: inbound from {$phone} [{$msg_type}]: {$message_text}" );

        // Get or create a stable session_id keyed to the phone number
        $session_id = $this->get_session_id( $phone );

        // If user replied with a number, resolve it to the stored option value
        $message_text = $this->resolve_numbered_input( $session_id, $message_text );

        // Run through chatbot engine — returns array with 'message' and optionally 'options'
        $chatbot = $this->get_chatbot_response( $session_id, $message_text, $phone );

        $response_text = $chatbot['message'] ?? '';
        $options       = $chatbot['options'] ?? array();

        if ( empty( $response_text ) ) {
            error_log( 'EduBot MSG91 Webhook: chatbot returned empty response' );
            $response_text = 'Thank you for reaching out! Our team will get back to you shortly.';
            $options       = array();
        }

        $reply_mode = $option_count === 0 ? 'text'
                    : ( $option_count <= 3  ? "interactive buttons({$option_count})"
                    : ( $option_count <= 10 ? "interactive list({$option_count})"
                    : "numbered text({$option_count})" ) );
        error_log( "EduBot MSG91 Webhook: sending {$reply_mode} reply to {$phone}" );

        // Route to the right send method based on option count:
        //   1–3  → interactive reply buttons
        //   4–10 → interactive list message
        //  11+   → numbered text fallback (WhatsApp list rows cap at 10)
        //   0    → plain text
        $option_count = count( $options );

        if ( $option_count >= 1 && $option_count <= 3 ) {
            $this->delete_pending_options( $session_id );
            $this->send_interactive_reply( $phone, $response_text, $options );
        } elseif ( $option_count >= 4 && $option_count <= 10 ) {
            $this->delete_pending_options( $session_id );
            $this->send_list_reply( $phone, $response_text, $options );
        } elseif ( $option_count > 10 ) {
            // Build numbered list appended to the message body
            $menu = $response_text . "\n";
            foreach ( $options as $i => $opt ) {
                $label  = $opt['text'] ?? $opt['label'] ?? ( 'Option ' . ( $i + 1 ) );
                $menu  .= ( $i + 1 ) . '. ' . wp_strip_all_tags( $label ) . "\n";
            }
            $menu .= "\nReply with the number of your choice.";
            // Store options so the next plain-text "1"/"2" can be resolved
            $this->store_pending_options( $session_id, $options );
            $this->send_text_reply( $phone, trim( $menu ) );
        } else {
            $this->delete_pending_options( $session_id );
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
    // Reply via MSG91 interactive LIST (4-10 options)
    // ─────────────────────────────────────────────────────────────

    /**
     * Send a WhatsApp interactive list message (supports 4-10 items).
     * Ref: https://docs.msg91.com/whatsapp/interactive-whatsapp-with-list
     */
    private function send_list_reply( string $phone, string $body_text, array $options ): void {
        $creds = $this->get_msg91_credentials();
        if ( ! $creds ) return;

        $phone = $this->normalise_phone( $phone );

        // Build row objects — id ≤ 200 chars, title ≤ 24 chars
        $rows = array();
        foreach ( array_slice( $options, 0, 10 ) as $idx => $opt ) {
            $label    = isset( $opt['text'] )  ? $opt['text']  : ( $opt['label'] ?? "Option {$idx}" );
            $row_id   = isset( $opt['value'] ) ? $opt['value'] : "opt_{$idx}";
            $row_id   = substr( sanitize_key( $row_id ), 0, 200 );
            $title    = mb_substr( wp_strip_all_tags( $label ), 0, 24 );
            $rows[]   = array(
                'id'    => $row_id,
                'title' => $title,
            );
        }

        $payload = array(
            'recipient_number'  => $phone,
            'integrated_number' => $creds['whatsapp_phone_id'],
            'content_type'      => 'interactive',
            'interactive'       => array(
                'type' => 'list',
                'body' => array(
                    'text' => $body_text,
                ),
                'action' => array(
                    'button'   => 'View Options',
                    'sections' => array(
                        array(
                            'title' => 'Choose an option',
                            'rows'  => $rows,
                        ),
                    ),
                ),
            ),
        );

        $this->post_to_msg91( $creds['whatsapp_token'], $payload );
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Store the option list for a session so a subsequent "1" / "2" reply
     * can be resolved to the correct option value (used for 11+ item menus).
     */
    private function store_pending_options( string $session_id, array $options ): void {
        set_transient( 'edubot_pending_opts_' . $session_id, $options, HOUR_IN_SECONDS );
    }

    /** Remove stored pending options (called after interactive/list send so numbers don't bleed). */
    private function delete_pending_options( string $session_id ): void {
        delete_transient( 'edubot_pending_opts_' . $session_id );
    }

    /**
     * If $message_text is a plain integer and there are pending options
     * for this session, return the option's value/text instead.
     * Otherwise return $message_text unchanged.
     */
    private function resolve_numbered_input( string $session_id, string $message_text ): string {
        $trimmed = trim( $message_text );
        if ( ! ctype_digit( $trimmed ) ) {
            return $message_text;
        }
        $pending = get_transient( 'edubot_pending_opts_' . $session_id );
        if ( ! is_array( $pending ) || empty( $pending ) ) {
            return $message_text;
        }
        $index = (int) $trimmed - 1; // convert 1-based to 0-based
        if ( $index < 0 || $index >= count( $pending ) ) {
            return $message_text;
        }
        $opt = $pending[ $index ];
        // Use 'value' as the canonical selection identifier, fall back to 'text'
        $resolved = $opt['value'] ?? $opt['text'] ?? $opt['label'] ?? $trimmed;
        error_log( "EduBot MSG91 Webhook: resolved numbered input '{$trimmed}' → '{$resolved}' for session {$session_id}" );
        return (string) $resolved;
    }

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
