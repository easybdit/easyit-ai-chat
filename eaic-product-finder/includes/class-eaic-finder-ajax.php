<?php
/**
 * Product Finder — AJAX handler.
 *
 * @package EasyIT_AI_Chat
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class EAIC_Finder_Ajax {

	const RATE_MAX    = 20;
	const RATE_WINDOW = 60;

	public function __construct() {
		add_action( 'wp_ajax_eaic_finder_chat',        array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_eaic_finder_chat', array( $this, 'handle' ) );
	}

	public function handle() {
		check_ajax_referer( 'eaic_finder_nonce', 'nonce' );

		$session_id = sanitize_text_field( wp_unslash( $_POST['session_id'] ?? '' ) );
		$message    = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

		if ( ! $session_id || ! $message ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'easyit-ai-chat' ) ), 400 );
		}

		// Rate limiting.
		$user_id  = get_current_user_id();
		$rate_key = $user_id > 0 ? 'fnd_u' . $user_id : 'fnd_s' . md5( $session_id );
		$count    = (int) get_transient( 'eaic_rl_' . md5( $rate_key ) );
		if ( $count >= self::RATE_MAX ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please wait.', 'easyit-ai-chat' ) ), 429 );
		}
		set_transient( 'eaic_rl_' . md5( $rate_key ), $count + 1, self::RATE_WINDOW );

		// Build message history from transient (no separate DB table for finder).
		$history_key = 'eaic_finder_' . md5( $session_id );
		$history     = get_transient( $history_key ) ?: array();

		$system   = EAIC_Finder_AI::system_prompt();
		$messages = $history;
		$messages[] = array( 'role' => 'user', 'content' => $message );

		// First AI call — to extract search params.
		$raw_reply = EAIC_Finder_AI::ask( $messages, $system );
		if ( ! $raw_reply ) {
			wp_send_json_error( array( 'message' => __( 'AI provider error. Please try again.', 'easyit-ai-chat' ) ), 500 );
		}

		$search_params = EAIC_Finder_AI::parse_search( $raw_reply );
		$products      = array();
		$clean_reply   = EAIC_Finder_AI::clean_response( $raw_reply );

		if ( $search_params ) {
			// Execute product search.
			$products = EAIC_Finder_AI::search_products( $search_params );

			if ( ! empty( $products ) ) {
				// Second AI call — let AI reference actual products.
				$messages_with_products = $messages;
				$messages_with_products[] = array( 'role' => 'assistant', 'content' => $raw_reply . EAIC_Finder_AI::products_context( $products ) );
				$messages_with_products[] = array( 'role' => 'user', 'content' => 'Please present these products to me.' );
				$refined_reply = EAIC_Finder_AI::ask( $messages_with_products, $system );
				if ( $refined_reply ) {
					$clean_reply = EAIC_Finder_AI::clean_response( $refined_reply );
				}
			} else {
				$clean_reply .= ' ' . __( "I couldn't find exact matches, but here are some alternatives:", 'easyit-ai-chat' );
				// Fallback: search by keyword only.
				$products = EAIC_Finder_AI::search_products( array( 'keyword' => $search_params['keyword'] ?? '' ) );
			}
		}

		// Save conversation history (last 8 turns).
		$history[] = array( 'role' => 'user',      'content' => $message );
		$history[] = array( 'role' => 'assistant',  'content' => $clean_reply );
		if ( count( $history ) > 16 ) { $history = array_slice( $history, -16 ); }
		set_transient( $history_key, $history, HOUR_IN_SECONDS );

		wp_send_json_success( array(
			'reply'    => wp_kses_post( $clean_reply ),
			'products' => $products,
		) );
	}
}
