<?php
/**
 * Product Q&A Bot — AJAX endpoint.
 *
 * Products are public, so no ownership check is needed.
 * Security chain:
 *   1. nonce       — request came from our page.
 *   2. identity    — who is this.
 *   3. rate limit  — per-identity throttle.
 *   4. sanitize    — clean every input.
 *   5. product lookup — search WooCommerce products.
 *   6. escape      — escape everything on the way out.
 *
 * @package EasyIT_AI_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAIC_Product_Ajax {

	const NONCE_ACTION = 'eaic_product_bot';
	const RL_LIMIT     = 20;
	const RL_WINDOW    = 60;

	public static function init() {
		add_action( 'wp_ajax_eaic_product_chat',        array( __CLASS__, 'handle' ) );
		add_action( 'wp_ajax_nopriv_eaic_product_chat', array( __CLASS__, 'handle' ) );
	}

	public static function handle() {

		// 1. NONCE --------------------------------------------------------
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Session expired. Please refresh.', 'easyit-ai-chat' ) ), 403 );
		}

		// 2. IDENTITY -----------------------------------------------------
		$user_id    = get_current_user_id();
		$session_id = isset( $_POST['session_id'] )
			? sanitize_text_field( wp_unslash( $_POST['session_id'] ) )
			: '';
		if ( '' === $session_id || strlen( $session_id ) > 64 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid session.', 'easyit-ai-chat' ) ), 400 );
		}
		$identity = $user_id ? 'u' . $user_id : 's' . $session_id;

		// 3. RATE LIMIT ---------------------------------------------------
		if ( self::is_rate_limited( $identity ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many messages. Please wait a moment.', 'easyit-ai-chat' ) ), 429 );
		}

		// 4. SANITIZE -----------------------------------------------------
		$message    = isset( $_POST['message'] )
			? sanitize_textarea_field( wp_unslash( $_POST['message'] ) )
			: '';
		$product_id = isset( $_POST['product_id'] )
			? absint( $_POST['product_id'] )
			: 0;

		if ( '' === $message || strlen( $message ) > 2000 ) {
			wp_send_json_error( array( 'message' => __( 'Message is empty or too long.', 'easyit-ai-chat' ) ), 400 );
		}

		// 5. PRODUCT LOOKUP -----------------------------------------------
		$product_data   = '';
		$primary_pid    = 0;

		if ( $product_id ) {
			// Specific product requested via shortcode attribute or JS.
			$ctx = EAIC_Product_AI::product_context( $product_id );
			if ( $ctx ) {
				$product_data = $ctx;
				$primary_pid  = $product_id;
			}
		} else {
			// Search by message keywords.
			$products = EAIC_Product_AI::search_products( $message, 4 );

			// Fallback: generic question → list recent published products.
			if ( empty( $products ) ) {
				$products = wc_get_products( array(
					'status'  => 'publish',
					'limit'   => 6,
					'orderby' => 'date',
					'order'   => 'DESC',
				) );
			}

			foreach ( $products as $p ) {
				$ctx = EAIC_Product_AI::product_context( $p->get_id() );
				if ( $ctx ) {
					$product_data .= $ctx . "\n---\n";
					if ( ! $primary_pid ) {
						$primary_pid = $p->get_id();
					}
				}
			}
		}

		// Optional user name from pre-chat form (for AI personalisation).
		$user_name = isset( $_POST['user_name'] )
			? sanitize_text_field( wp_unslash( $_POST['user_name'] ) )
			: '';
		if ( '' === $user_name && $user_id ) {
			$user_name = wp_get_current_user()->display_name;
		}

		// Log user message.
		EAIC_Product_DB::log( $session_id, $user_id, $primary_pid, 'user', $message );

		// Ask the AI.
		$system  = EAIC_Product_AI::system_prompt( $product_data, $user_name );
		$history = EAIC_Product_DB::recent( $session_id, 8 );
		$reply   = EAIC_Product_AI::ask( $system, $history, $message );

		// Log assistant reply.
		EAIC_Product_DB::log( $session_id, $user_id, $primary_pid, 'assistant', $reply );

		// 6. ESCAPE -------------------------------------------------------
		wp_send_json_success( array( 'reply' => wp_kses_post( $reply ) ) );
	}

	private static function is_rate_limited( $identity ) {
		$key   = 'eaic_prl_' . md5( $identity );
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
