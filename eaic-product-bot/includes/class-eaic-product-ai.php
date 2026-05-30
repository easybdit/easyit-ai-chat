<?php
/**
 * Product Q&A Bot — AI handler.
 *
 * Fetches published WooCommerce product data and passes it to the
 * configured AI provider. The AI only formats data we give it —
 * prices, stock, and descriptions always come from WooCommerce.
 *
 * @package EasyIT_AI_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAIC_Product_AI {

	/**
	 * Build a plain-text snapshot of one product for the AI.
	 *
	 * @param int $product_id WooCommerce product id.
	 * @return string
	 */
	public static function product_context( $product_id ) {
		$product = wc_get_product( absint( $product_id ) );
		if ( ! $product || ! $product->is_visible() ) {
			return '';
		}

		$price = $product->is_on_sale()
			? wp_strip_all_tags( wc_price( $product->get_sale_price() ) ) .
			  ' (was ' . wp_strip_all_tags( wc_price( $product->get_regular_price() ) ) . ')'
			: wp_strip_all_tags( wc_price( $product->get_price() ) );

		$stock = $product->is_in_stock()
			? ( $product->managing_stock()
				? sprintf( 'In stock (%d available)', $product->get_stock_quantity() )
				: 'In stock' )
			: 'Out of stock';

		$cats = implode( ', ', wp_list_pluck(
			get_the_terms( $product->get_id(), 'product_cat' ) ?: array(),
			'name'
		) );

		$lines = array(
			'Product'      => $product->get_name(),
			'SKU'          => $product->get_sku() ?: 'N/A',
			'Price'        => $price,
			'Stock'        => $stock,
			'Categories'   => $cats ?: 'N/A',
			'Short desc'   => wp_strip_all_tags( $product->get_short_description() ) ?: 'N/A',
			'Description'  => wp_strip_all_tags( wp_trim_words( $product->get_description(), 80 ) ) ?: 'N/A',
		);

		// Variable product: list variations summary.
		if ( $product->is_type( 'variable' ) ) {
			$prices = $product->get_variation_prices();
			if ( ! empty( $prices['price'] ) ) {
				$min = wp_strip_all_tags( wc_price( min( $prices['price'] ) ) );
				$max = wp_strip_all_tags( wc_price( max( $prices['price'] ) ) );
				$lines['Price range'] = $min === $max ? $min : $min . ' – ' . $max;
			}
			$lines['Attributes'] = implode( '; ', array_map(
				function ( $attr ) { return $attr->get_name() . ': ' . implode( ', ', $attr->get_options() ); },
				$product->get_attributes()
			) ) ?: 'N/A';
		}

		$out = '';
		foreach ( $lines as $label => $value ) {
			$out .= $label . ': ' . $value . "\n";
		}
		return $out;
	}

	/**
	 * Search published products by keyword.
	 * Strips common stop words so "what is the price for mango" → searches "mango".
	 *
	 * @param string $query Full user message.
	 * @param int    $limit Max products.
	 * @return WC_Product[]
	 */
	public static function search_products( $query, $limit = 4 ) {
		$stop_words = array(
			'what', 'whats', 'is', 'are', 'the', 'a', 'an', 'of', 'in', 'on', 'at',
			'to', 'do', 'how', 'much', 'does', 'cost', 'price', 'about', 'tell', 'me',
			'can', 'you', 'i', 'my', 'its', 'this', 'that', 'these', 'those',
			'have', 'has', 'had', 'with', 'be', 'been', 'was', 'were', 'will', 'would',
			'your', 'our', 'their', 'any', 'some', 'like', 'stock', 'available',
			'buy', 'get', 'show', 'give', 'find', 'for', 'and', 'or', 'not',
			'it', 'his', 'her', 'we', 'they', 'from', 'by', 'as', 'up', 'if',
			'out', 'so', 'no', 'but', 'all', 'there', 'more', 'also', 'into',
		);

		$words    = preg_split( '/\s+/', strtolower( sanitize_text_field( $query ) ) );
		$keywords = array_filter( $words, function ( $w ) use ( $stop_words ) {
			return strlen( $w ) > 2 && ! in_array( $w, $stop_words, true );
		} );

		$search = implode( ' ', array_slice( array_values( $keywords ), 0, 3 ) );

		// Fallback to original query if nothing left after filtering.
		if ( '' === $search ) {
			$search = sanitize_text_field( $query );
		}

		return wc_get_products( array(
			's'       => $search,
			'status'  => 'publish',
			'limit'   => absint( $limit ),
			'orderby' => 'relevance',
		) );
	}

	/**
	 * Build the system prompt.
	 *
	 * @param string $product_data Concatenated product context(s).
	 * @return string
	 */
	public static function system_prompt( $product_data ) {
		$base  = 'You are a helpful product assistant for an online store. ';
		$base .= 'Answer questions about products using ONLY the product data provided below. ';
		$base .= 'If the data does not contain the answer, say you are not sure and suggest ';
		$base .= 'browsing the store or contacting support — never invent prices, stock levels, ';
		$base .= 'or product details. Be concise, friendly, and helpful.' . "\n\n";

		if ( $product_data ) {
			$base .= "PRODUCT DATA:\n" . $product_data;
		} else {
			$base .= 'PRODUCT DATA: (no matching products found — ask the visitor to rephrase or browse the store)';
		}

		return apply_filters( 'eaic_product_system_prompt', $base, $product_data );
	}

	/**
	 * Send the conversation to the configured AI provider.
	 *
	 * @param string $system   System prompt.
	 * @param array  $history  Recent messages.
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
