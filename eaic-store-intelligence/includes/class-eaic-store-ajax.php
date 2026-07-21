<?php
/**
 * Store Intelligence — AJAX handler (admin-only).
 *
 * @package EasyIT_AI_Chat
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class EAIC_Store_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_eaic_store_ask', array( $this, 'handle' ) );
	}

	public function handle() {
		check_ajax_referer( 'eaic_store_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorised.', 'easyit-ai-chat' ) ), 403 );
		}

		$session_id = sanitize_text_field( wp_unslash( $_POST['session_id'] ?? '' ) );
		$message    = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

		if ( ! $session_id || ! $message ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'easyit-ai-chat' ) ), 400 );
		}

		// Load conversation history from transient.
		$history_key = 'eaic_store_' . md5( get_current_user_id() . '_' . $session_id );
		$history     = get_transient( $history_key ) ?: array();

		$system   = EAIC_Store_AI::system_prompt();
		$messages = $history;
		$messages[] = array( 'role' => 'user', 'content' => $message );

		// First AI call — get query request.
		$raw_reply = EAIC_Store_AI::ask( $messages, $system );
		if ( ! $raw_reply ) {
			wp_send_json_error( array( 'message' => __( 'AI provider error. Please try again.', 'easyit-ai-chat' ) ), 500 );
		}

		$query_params = EAIC_Store_AI::parse_query( $raw_reply );
		$clean_reply  = EAIC_Store_AI::clean_response( $raw_reply );

		if ( $query_params ) {
			// Execute safe WC query.
			$data = EAIC_Store_AI::execute_query( $query_params );

			// Second AI call — let AI format the data into a natural response.
			$messages_with_data   = $messages;
			$messages_with_data[] = array( 'role' => 'assistant', 'content' => $raw_reply . "\n\n[STORE DATA]\n" . $data );
			$messages_with_data[] = array( 'role' => 'user',      'content' => 'Please present this data clearly.' );

			$refined = EAIC_Store_AI::ask( $messages_with_data, $system );
			if ( $refined ) {
				$clean_reply = EAIC_Store_AI::clean_response( $refined );
			}
		}

		// Save conversation (last 20 turns).
		$history[] = array( 'role' => 'user',      'content' => $message );
		$history[] = array( 'role' => 'assistant',  'content' => $clean_reply );
		if ( count( $history ) > 20 ) { $history = array_slice( $history, -20 ); }
		set_transient( $history_key, $history, 2 * HOUR_IN_SECONDS );

		wp_send_json_success( array(
			'reply' => wp_kses_post( $clean_reply ),
		) );
	}
}
