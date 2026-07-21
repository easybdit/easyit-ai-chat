<?php
/**
 * Product Finder — AI context and search engine.
 *
 * @package EasyIT_AI_Chat
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class EAIC_Finder_AI {

	/**
	 * System prompt for the product finder bot.
	 *
	 * @param string $store_name Site name.
	 * @return string
	 */
	public static function system_prompt( $store_name = '' ) {
		$store = $store_name ?: get_bloginfo( 'name' );

		$prompt = "You are a smart shopping assistant for {$store}. Your job is to help customers find the right products.\n\n"
			. "When a customer describes what they are looking for, extract search criteria and respond with:\n"
			. "1. A friendly conversational reply.\n"
			. "2. A JSON search block on a new line in this EXACT format:\n"
			. '{"search":{"keyword":"<main keyword>","category":"<category name or empty>","max_price":<number or 0>,"min_price":<number or 0>,"attribute":{"<attr_name>":"<value>"}}}' . "\n\n"
			. "Rules:\n"
			. "- Always include the JSON block when you understand what the customer wants.\n"
			. "- Use empty string \"\" for category if not mentioned.\n"
			. "- Use 0 for price if not mentioned.\n"
			. "- Attribute can be empty object {} if not mentioned.\n"
			. "- Keep conversational response SHORT (1-2 sentences before the JSON).\n"
			. "- If the customer's request is too vague, ask one clarifying question WITHOUT the JSON.\n"
			. "- After products are shown, help the customer compare or choose.\n"
			. "- Never make up products — only recommend from what is returned to you.\n"
			. '- When product results are shown to you, refer to them naturally in your response.';

		return apply_filters( 'eaic_finder_system_prompt', $prompt );
	}

	/**
	 * Parse search parameters from AI response.
	 *
	 * @param string $response AI text.
	 * @return array|null Parsed search params or null.
	 */
	public static function parse_search( $response ) {
		if ( preg_match( '/\{"search"\s*:\s*\{[^}]+\}\s*\}/s', $response, $m ) ) {
			$data = json_decode( $m[0], true );
			if ( isset( $data['search'] ) && is_array( $data['search'] ) ) {
				return $data['search'];
			}
		}
		return null;
	}

	/**
	 * Strip JSON search block from AI response.
	 *
	 * @param string $response Raw AI response.
	 * @return string Clean text.
	 */
	public static function clean_response( $response ) {
		return trim( preg_replace( '/\{"search"\s*:\s*\{[^}]+\}\s*\}/s', '', $response ) );
	}

	/**
	 * Execute WooCommerce product search from parsed params.
	 *
	 * @param array $params From parse_search().
	 * @param int   $limit  Max products to return.
	 * @return array Array of product data for rendering.
	 */
	public static function search_products( $params, $limit = 4 ) {
		$keyword   = sanitize_text_field( $params['keyword']   ?? '' );
		$category  = sanitize_text_field( $params['category']  ?? '' );
		$max_price = (float) ( $params['max_price'] ?? 0 );
		$min_price = (float) ( $params['min_price'] ?? 0 );
		$attribute = is_array( $params['attribute'] ?? null ) ? $params['attribute'] : array();

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'relevance',
			's'              => $keyword,
		);

		// Category filter.
		if ( $category ) {
			$term = get_term_by( 'name', $category, 'product_cat' );
			if ( ! $term ) {
				$term = get_term_by( 'slug', sanitize_title( $category ), 'product_cat' );
			}
			if ( $term ) {
				$args['tax_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				);
			}
		}

		// Price filter.
		if ( $max_price > 0 || $min_price > 0 ) {
			$args['meta_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'key'     => '_price',
				'value'   => array( $min_price ?: 0, $max_price ?: 999999 ),
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			);
		}

		// Product attribute filter.
		foreach ( $attribute as $attr_name => $attr_val ) {
			$taxonomy = wc_attribute_taxonomy_name( sanitize_title( $attr_name ) );
			if ( taxonomy_exists( $taxonomy ) ) {
				$term = get_term_by( 'name', $attr_val, $taxonomy );
				if ( $term ) {
					$args['tax_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $term->term_id,
					);
				}
			}
		}

		$query    = new WP_Query( $args );
		$products = array();

		foreach ( $query->posts as $post ) {
			$product = wc_get_product( $post->ID );
			if ( ! $product || ! $product->is_visible() ) { continue; }

			$image_id  = $product->get_image_id();
			$image_url = $image_id
				? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' )
				: wc_placeholder_img_src( 'woocommerce_thumbnail' );

			$products[] = array(
				'id'           => $product->get_id(),
				'name'         => $product->get_name(),
				'price'        => $product->get_price_html(),
				'price_raw'    => (float) $product->get_price(),
				'url'          => get_permalink( $product->get_id() ),
				'image'        => esc_url( $image_url ),
				'stock'        => $product->get_stock_status(),
				'stock_label'  => $product->is_in_stock() ? __( 'In Stock', 'easyit-ai-chat' ) : __( 'Out of Stock', 'easyit-ai-chat' ),
				'add_to_cart'  => $product->add_to_cart_url(),
				'short_desc'   => wp_strip_all_tags( $product->get_short_description() ),
				'rating'       => $product->get_average_rating(),
				'review_count' => $product->get_review_count(),
			);
		}

		wp_reset_postdata();

		return $products;
	}

	/**
	 * Build a text summary of found products to append to conversation.
	 * This lets the AI reference the products naturally in its next reply.
	 *
	 * @param array $products From search_products().
	 * @return string
	 */
	public static function products_context( $products ) {
		if ( empty( $products ) ) {
			return "\n\n[No matching products found in the store.]";
		}
		$text = "\n\n[Found products:]\n";
		foreach ( $products as $p ) {
			$text .= "- {$p['name']} | Price: {$p['price_raw']} | Stock: {$p['stock_label']} | URL: {$p['url']}\n";
		}
		return $text;
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
