<?php
/**
 * Return Bot — AI context builder.
 *
 * @package EasyIT_AI_Chat
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class EAIC_Return_AI {

	/**
	 * Allowed return reasons.
	 */
	const REASONS = array(
		'wrong_size'      => 'Wrong size or fit',
		'defective'       => 'Product is defective or damaged',
		'not_as_described'=> 'Not as described',
		'wrong_item'      => 'Wrong item received',
		'changed_mind'    => 'Changed my mind',
		'other'           => 'Other reason',
	);

	/**
	 * Build order context for return conversation.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return array|null Array of returnable items or null if ineligible.
	 */
	public static function get_returnable_items( $order_id ) {
		$order = wc_get_order( (int) $order_id );
		if ( ! $order ) { return null; }

		$eligible_statuses = apply_filters( 'eaic_return_eligible_statuses', array( 'completed', 'processing' ) );
		if ( ! in_array( $order->get_status(), $eligible_statuses, true ) ) {
			return null;
		}

		$items = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			$items[] = array(
				'item_id'  => $item_id,
				'name'     => $item->get_name(),
				'qty'      => $item->get_quantity(),
				'total'    => wc_price( $item->get_total() ),
				'sku'      => $product ? $product->get_sku() : '',
			);
		}

		return array(
			'order_id'   => $order->get_id(),
			'order_num'  => $order->get_order_number(),
			'status'     => wc_get_order_status_name( $order->get_status() ),
			'date'       => wc_format_datetime( $order->get_date_created() ),
			'total'      => $order->get_formatted_order_total(),
			'items'      => $items,
		);
	}

	/**
	 * Build system prompt for return conversation.
	 *
	 * @param array  $order_data From get_returnable_items().
	 * @param string $customer_name Optional first name.
	 * @return string
	 */
	public static function system_prompt( $order_data, $customer_name = '' ) {
		$greeting = $customer_name ? "The customer's name is {$customer_name}." : '';
		$reasons  = implode( ', ', array_values( self::REASONS ) );

		$items_text = '';
		foreach ( $order_data['items'] as $item ) {
			$items_text .= "- {$item['name']} (Qty: {$item['qty']}, Total: {$item['total']})\n";
		}

		$prompt = <<<PROMPT
You are a helpful return and refund assistant for an online store. {$greeting}

The customer is inquiring about a return for:
Order #{$order_data['order_num']} | Status: {$order_data['status']} | Date: {$order_data['date']} | Total: {$order_data['total']}

Order Items:
{$items_text}

Your job is to:
1. Ask which item(s) the customer wants to return (if not already stated).
2. Ask the reason for the return. Valid reasons: {$reasons}.
3. Once you have both the item(s) and reason, confirm the return details and tell the customer the return request will be submitted.
4. Be empathetic, professional, and concise.

Rules:
- NEVER promise a specific refund amount or timeline unless data is shown to you.
- NEVER make up information about store policies you don't know.
- When you have all required information (item + reason), end your message with exactly this JSON on a new line: {"action":"submit_return","items":[<item names as array>],"reason":"<reason key from: {$reasons}>"}
- Do not include the JSON until you have confirmed all details with the customer.
PROMPT;

		return apply_filters( 'eaic_return_system_prompt', $prompt, $order_data );
	}

	/**
	 * Parse AI response to extract return action JSON if present.
	 *
	 * @param string $response AI text response.
	 * @return array|null Parsed action or null.
	 */
	public static function parse_action( $response ) {
		if ( preg_match( '/\{"action"\s*:\s*"submit_return"[^}]+\}/s', $response, $m ) ) {
			$data = json_decode( $m[0], true );
			if ( is_array( $data ) && isset( $data['action'] ) && 'submit_return' === $data['action'] ) {
				return $data;
			}
		}
		return null;
	}

	/**
	 * Strip the JSON action from AI response text before showing to user.
	 *
	 * @param string $response Raw AI response.
	 * @return string Clean text.
	 */
	public static function clean_response( $response ) {
		return trim( preg_replace( '/\{"action"\s*:\s*"submit_return"[^}]+\}/s', '', $response ) );
	}

	/**
	 * Add return request note to the WooCommerce order.
	 *
	 * @param int    $order_id WC order ID.
	 * @param int    $request_id Return request DB ID.
	 * @param array  $items Item names.
	 * @param string $reason Reason label.
	 * @return void
	 */
	public static function add_order_note( $order_id, $request_id, $items, $reason ) {
		$order = wc_get_order( (int) $order_id );
		if ( ! $order ) { return; }
		$items_str = implode( ', ', (array) $items );
		$note = sprintf(
			__( '⚠️ Return Request #%d submitted via chat. Items: %s. Reason: %s. Awaiting admin review.', 'easyit-ai-chat' ),
			$request_id, $items_str, $reason
		);
		$order->add_order_note( $note );
	}

	/**
	 * Delegate AI call to the free plugin.
	 *
	 * @param array  $messages Chat history.
	 * @param string $system   System prompt.
	 * @return string
	 */
	public static function ask( $messages, $system ) {
		return (string) apply_filters( 'eaic_send_to_provider', '', array(
			'messages' => $messages,
			'system'   => $system,
		) );
	}
}
