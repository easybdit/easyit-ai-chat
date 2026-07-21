<?php
/**
 * Order Status Bot — AJAX endpoint.
 *
 * Every request passes the full security chain, in order:
 *   1. nonce       — request came from our page.
 *   2. identity    — who is this (user id or guest session).
 *   3. ratelimit   — per-identity throttle.
 *   4. sanitize    — clean every input.
 *   5. ownership   — verify access to any order before reading it.
 *   6. escape      — escape everything on the way out.
 *
 * @package EasyIT_AI_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAIC_Order_Ajax {

	const NONCE_ACTION = 'eaic_order_bot';
	const RL_LIMIT     = 20;  // messages.
	const RL_WINDOW    = 60;  // seconds.

	public static function init() {
		add_action( 'wp_ajax_eaic_order_chat', array( __CLASS__, 'handle' ) );
		add_action( 'wp_ajax_nopriv_eaic_order_chat', array( __CLASS__, 'handle' ) );
	}

	public static function handle() {

		// 1. NONCE -----------------------------------------------------
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Session expired. Please refresh.', 'easyit-ai-chat' ) ), 403 );
		}

		// 2. IDENTITY --------------------------------------------------
		$user_id    = get_current_user_id();
		$session_id = isset( $_POST['session_id'] )
			? sanitize_text_field( wp_unslash( $_POST['session_id'] ) )
			: '';
		if ( '' === $session_id || strlen( $session_id ) > 64 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid session.', 'easyit-ai-chat' ) ), 400 );
		}
		$identity = $user_id ? 'u' . $user_id : 's' . $session_id;

		// 3. RATE LIMIT ------------------------------------------------
		if ( self::is_rate_limited( $identity ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many messages. Please wait a moment.', 'easyit-ai-chat' ) ), 429 );
		}

		// 4. SANITIZE --------------------------------------------------
		$message = isset( $_POST['message'] )
			? sanitize_textarea_field( wp_unslash( $_POST['message'] ) )
			: '';
		$claimed_email = isset( $_POST['email'] )
			? sanitize_email( wp_unslash( $_POST['email'] ) )
			: '';
		$order_number = isset( $_POST['order_id'] )
			? absint( $_POST['order_id'] )
			: 0;

		if ( '' === $message || strlen( $message ) > 2000 ) {
			wp_send_json_error( array( 'message' => __( 'Message is empty or too long.', 'easyit-ai-chat' ) ), 400 );
		}

		// 5. OWNERSHIP -------------------------------------------------
		// Collect ONLY the order ids this visitor is verified to see.
		$allowed_ids = EAIC_Order_Verify::visible_order_ids(); // logged-in user's own.

		if ( $order_number ) {
			// Guest (or user) asking about a specific order: must match email.
			if ( EAIC_Order_Verify::owns_order( $order_number, $claimed_email ) ) {
				$allowed_ids[] = $order_number;
			}
			// If it doesn't match, we silently add nothing — no leak.
		}

		$allowed_ids = array_unique( array_map( 'absint', $allowed_ids ) );

		// Build context only from allowed orders.
		$order_data = '';
		foreach ( $allowed_ids as $oid ) {
			$order_data .= EAIC_Order_AI::order_context( $oid ) . "\n";
		}

		// Optional user name from pre-chat form.
		$user_name = isset( $_POST['user_name'] )
			? sanitize_text_field( wp_unslash( $_POST['user_name'] ) )
			: '';
		if ( '' === $user_name && $user_id ) {
			$user_name = wp_get_current_user()->display_name;
		}

		// Log the user's message.
		$primary_oid = $allowed_ids ? reset( $allowed_ids ) : 0;
		EAIC_Order_DB::log( $session_id, $user_id, $primary_oid, 'user', $message );

		// Ask the AI.
		$system  = EAIC_Order_AI::system_prompt( $order_data, $user_name );
		$history = EAIC_Order_DB::recent( $session_id, 8 );
		$reply   = EAIC_Order_AI::ask( $system, $history, $message );

		// Log the assistant reply.
		EAIC_Order_DB::log( $session_id, $user_id, $primary_oid, 'assistant', $reply );

		// 6. ESCAPE ----------------------------------------------------
		wp_send_json_success(
			array(
				'reply'    => wp_kses_post( $reply ),
				'verified' => ! empty( $allowed_ids ),
			)
		);
	}

	/**
	 * Per-identity rate limit. Uses object cache (Redis) if available,
	 * falls back to transients otherwise.
	 */
	private static function is_rate_limited( $identity ) {
		$key   = 'eaic_rl_' . md5( $identity );
		$count = wp_cache_get( $key, 'eaic' );

		if ( false === $count ) {
			$count = (int) get_transient( $key );
		}

		if ( $count >= self::RL_LIMIT ) {
			return true;
		}

		$count++;
		wp_cache_set( $key, $count, 'eaic', self::RL_WINDOW );
		set_transient( $key, $count, self::RL_WINDOW );
		return false;
	}
}
