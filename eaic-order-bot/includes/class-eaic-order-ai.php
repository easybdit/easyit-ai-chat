<?php
/**
 * Order Status Bot — AI handler.
 *
 * Takes a user question + the order data they are allowed to see,
 * builds a tight context, and asks the AI to answer. The AI only
 * FORMATS data we give it — all status/price/tracking values come
 * from WooCommerce, never from the model's imagination.
 *
 * @package EasyIT_AI_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAIC_Order_AI {

	/**
	 * Build a plain-text snapshot of one order for the AI.
	 * Only safe, non-sensitive fields. No full address, no card data.
	 *
	 * @param int $order_id Order id (already ownership-verified).
	 * @return string
	 */
	public static function order_context( $order_id ) {
		$order = wc_get_order( absint( $order_id ) );
		if ( ! $order ) {
			return '';
		}

		$items = array();
		foreach ( $order->get_items() as $item ) {
			$items[] = $item->get_name() . ' x' . $item->get_quantity();
		}

		$lines = array(
			'Order #'  => $order->get_order_number(),
			'Status'   => wc_get_order_status_name( $order->get_status() ),
			'Date'     => wc_format_datetime( $order->get_date_created() ),
			'Total'    => wp_strip_all_tags( $order->get_formatted_order_total() ),
			'Items'    => implode( ', ', $items ),
		);

		// Tracking, if a shipping plugin stored it (filterable).
		$tracking = apply_filters( 'eaic_order_tracking', '', $order_id );
		if ( $tracking ) {
			$lines['Tracking'] = $tracking;
		}

		$out = '';
		foreach ( $lines as $label => $value ) {
			$out .= $label . ': ' . $value . "\n";
		}
		return $out;
	}

	/**
	 * Compose the system prompt. The anti-hallucination rule lives here.
	 *
	 * @param string $order_data Concatenated order context(s).
	 * @return string
	 */
	public static function system_prompt( $order_data ) {
		$base  = "You are an order-status assistant for an online store. ";
		$base .= "Answer ONLY using the order data provided below. ";
		$base .= "If the data does not contain the answer, say you are not sure ";
		$base .= "and suggest contacting support — never invent order numbers, ";
		$base .= "dates, prices, or tracking. Be concise and friendly.\n\n";

		if ( $order_data ) {
			$base .= "ORDER DATA:\n" . $order_data;
		} else {
			$base .= "ORDER DATA: (none available — the visitor is not verified)";
		}

		// Reuse the plugin's existing Pro prompt filter.
		return apply_filters( 'eaic_pro_system_prompt', $base, $order_data );
	}

	/**
	 * Send the conversation to the configured AI provider.
	 * Reuses the plugin's existing provider layer via the
	 * eaic_send_to_provider filter (wired to EAIC_Engine).
	 *
	 * The provider's chat() returns a plain string, with the system
	 * prompt passed separately — so we forward both unchanged.
	 *
	 * @param string $system   System prompt.
	 * @param array  $history  Recent messages [ ['role'=>..,'message'=>..], .. ].
	 * @param string $user_msg New user message (sanitized).
	 * @return string AI reply (plain text).
	 */
	public static function ask( $system, $history, $user_msg ) {
		$messages = array();
		foreach ( $history as $row ) {
			$messages[] = array(
				'role'    => 'assistant' === $row['role'] ? 'assistant' : 'user',
				'content' => $row['message'],
			);
		}
		$messages[] = array(
			'role'    => 'user',
			'content' => $user_msg,
		);

		/**
		 * Delegate to the existing provider layer. The hook returns a
		 * string reply, or a WP_Error on failure. Default null means
		 * "no handler wired" — treated as an error below.
		 */
		$reply = apply_filters(
			'eaic_send_to_provider',
			null,
			array(
				'system'   => $system,
				'messages' => $messages,
			)
		);

		if ( ! is_string( $reply ) || '' === $reply ) {
			return __( "Sorry, I couldn't reach the assistant right now. Please try again.", 'easyit-ai-chat' );
		}

		return $reply;
	}
}
