<?php
/**
 * Return Bot — AJAX handler.
 *
 * @package EasyIT_AI_Chat
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class EAIC_Return_Ajax {

	const RATE_MAX    = 20;
	const RATE_WINDOW = 60;

	public function __construct() {
		add_action( 'wp_ajax_eaic_return_chat',        array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_eaic_return_chat', array( $this, 'handle' ) );
	}

	public function handle() {
		check_ajax_referer( 'eaic_return_nonce', 'nonce' );

		$session_id = sanitize_text_field( wp_unslash( $_POST['session_id'] ?? '' ) );
		$message    = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
		$order_id   = absint( $_POST['order_id'] ?? 0 );
		$email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

		if ( ! $session_id || ! $message ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'easyit-ai-chat' ) ), 400 );
		}

		// Rate limiting.
		$user_id  = get_current_user_id();
		$rate_key = $user_id > 0 ? 'ret_u' . $user_id : 'ret_s' . md5( $session_id );
		$count    = (int) get_transient( 'eaic_rl_' . md5( $rate_key ) );
		if ( $count >= self::RATE_MAX ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please wait.', 'easyit-ai-chat' ) ), 429 );
		}
		set_transient( 'eaic_rl_' . md5( $rate_key ), $count + 1, self::RATE_WINDOW );

		// Verify order ownership. Logged-in users must own the order; guests
		// must prove ownership via billing email (same constant-time check
		// used by the Order Status Bot's EAIC_Order_Verify::owns_order()) —
		// never trust a bare order number typed by an anonymous visitor.
		if ( $order_id ) {
			if ( $user_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order || (int) $order->get_customer_id() !== $user_id ) {
					wp_send_json_error( array( 'message' => __( 'Order not found.', 'easyit-ai-chat' ) ), 404 );
				}
			} elseif ( ! class_exists( 'EAIC_Order_Verify' ) || ! EAIC_Order_Verify::owns_order( $order_id, $email ) ) {
				// Same generic message whether the order doesn't exist or the
				// email doesn't match — avoids leaking which order numbers are valid.
				wp_send_json_error( array( 'message' => __( 'Order not found. Please check your order number and email.', 'easyit-ai-chat' ) ), 404 );
			}
		}

		// Build context.
		$order_data  = $order_id ? EAIC_Return_AI::get_returnable_items( $order_id ) : null;
		if ( $order_id && ! $order_data ) {
			wp_send_json_error( array( 'message' => __( 'This order is not eligible for return. Only completed or processing orders can be returned.', 'easyit-ai-chat' ) ), 400 );
		}

		// Customer name (for personalization).
		$customer_name = '';
		if ( $order_id && $order_data ) {
			$order = wc_get_order( $order_id );
			$customer_name = $order ? $order->get_billing_first_name() : '';
		} elseif ( $user_id ) {
			$customer_name = get_user_meta( $user_id, 'first_name', true ) ?: '';
		}

		$system   = $order_data
			? EAIC_Return_AI::system_prompt( $order_data, $customer_name )
			: __( 'You are a return assistant. Ask the customer for their order number to proceed.', 'easyit-ai-chat' );

		// Build message history.
		$history  = EAIC_Return_DB::recent( $session_id );
		$messages = array();
		foreach ( $history as $row ) {
			$messages[] = array( 'role' => $row['role'], 'content' => $row['message'] );
		}
		$messages[] = array( 'role' => 'user', 'content' => $message );

		// Log user message.
		EAIC_Return_DB::log( $session_id, $user_id, $order_id, 'user', $message );

		// Ask AI.
		$raw_reply = EAIC_Return_AI::ask( $messages, $system );
		if ( ! $raw_reply ) {
			wp_send_json_error( array( 'message' => __( 'AI provider error. Please try again.', 'easyit-ai-chat' ) ), 500 );
		}

		// Check for return action.
		$action        = EAIC_Return_AI::parse_action( $raw_reply );
		$clean_reply   = EAIC_Return_AI::clean_response( $raw_reply );
		$return_request_id = 0;

		if ( $action && $order_id ) {
			$reason_label = EAIC_Return_AI::REASONS[ $action['reason'] ] ?? $action['reason'];
			$return_request_id = EAIC_Return_DB::save_request(
				$order_id,
				$user_id,
				$session_id,
				$action['items'] ?? array(),
				$reason_label
			);
			EAIC_Return_AI::add_order_note( $order_id, $return_request_id, $action['items'] ?? array(), $reason_label );

			// Append confirmation to reply.
			$clean_reply .= "\n\n✅ " . sprintf(
				/* translators: %d: return request ID */
				__( 'Return request #%d has been submitted. Our team will review it within 1–2 business days and contact you at your billing email.', 'easyit-ai-chat' ),
				$return_request_id
			);
		}

		// Log assistant reply.
		EAIC_Return_DB::log( $session_id, $user_id, $order_id, 'assistant', $clean_reply );

		wp_send_json_success( array(
			'reply'             => wp_kses_post( $clean_reply ),
			'return_submitted'  => $return_request_id > 0,
			'return_request_id' => $return_request_id,
		) );
	}
}
