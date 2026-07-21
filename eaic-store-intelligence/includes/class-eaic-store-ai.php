<?php
/**
 * Store Intelligence — AI data layer.
 *
 * Converts natural language admin questions to safe WooCommerce data queries.
 *
 * @package EasyIT_AI_Chat
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class EAIC_Store_AI {

	/**
	 * System prompt for the store intelligence bot.
	 *
	 * @param string $store_name Site name.
	 * @return string
	 */
	public static function system_prompt( $store_name = '' ) {
		$store = $store_name ?: get_bloginfo( 'name' );
		$today = current_time( 'Y-m-d' );

		return apply_filters( 'eaic_store_system_prompt', <<<PROMPT
You are an intelligent business assistant for the WooCommerce store "{$store}". Today is {$today}.
You have access to live store data that will be provided to you in each message.

Your capabilities:
- Sales & revenue analysis (today, this week, this month, custom period)
- Order status breakdowns
- Top-selling products
- Low stock / out-of-stock alerts
- Customer insights (new customers, top spenders)
- Store health summary

When answering:
- Be concise, professional, and data-driven.
- Format numbers clearly (use currency symbols, commas for thousands).
- Highlight important trends or concerns.
- If data shows something urgent (low stock, pending orders), mention it proactively.
- ONLY answer based on the data provided. Never make up numbers.

When you need data to answer a question, output a JSON query block on a new line:
{"query":{"type":"<type>","period":"<today|week|month|year|custom>","date_from":"<Y-m-d or empty>","date_to":"<Y-m-d or empty>","limit":<number>}}

Query types available: revenue, orders, top_products, low_stock, customers, summary
PROMPT
		);
	}

	/**
	 * Parse query request from AI response.
	 *
	 * @param string $response AI text.
	 * @return array|null
	 */
	public static function parse_query( $response ) {
		if ( preg_match( '/\{"query"\s*:\s*\{[^}]+\}\s*\}/s', $response, $m ) ) {
			$data = json_decode( $m[0], true );
			if ( isset( $data['query']['type'] ) ) {
				return $data['query'];
			}
		}
		return null;
	}

	/**
	 * Strip JSON query block from AI response.
	 *
	 * @param string $response Raw AI response.
	 * @return string
	 */
	public static function clean_response( $response ) {
		return trim( preg_replace( '/\{"query"\s*:\s*\{[^}]+\}\s*\}/s', '', $response ) );
	}

	/**
	 * Execute a safe WooCommerce data query.
	 *
	 * @param array $query Parsed query params.
	 * @return string Formatted data text.
	 */
	public static function execute_query( $query ) {
		$type   = sanitize_key( $query['type']    ?? 'summary' );
		$period = sanitize_key( $query['period']   ?? 'today' );
		$limit  = min( 20, max( 1, absint( $query['limit'] ?? 5 ) ) );

		$date_range = self::resolve_period( $period, $query['date_from'] ?? '', $query['date_to'] ?? '' );

		switch ( $type ) {
			case 'revenue':  return self::query_revenue( $date_range );
			case 'orders':   return self::query_orders( $date_range );
			case 'top_products': return self::query_top_products( $date_range, $limit );
			case 'low_stock':    return self::query_low_stock( $limit );
			case 'customers':    return self::query_customers( $date_range, $limit );
			case 'summary': default: return self::query_summary();
		}
	}

	// ─── Private query helpers ───────────────────────────────────────────────

	private static function resolve_period( $period, $date_from, $date_to ) {
		$today = current_time( 'Y-m-d' );
		switch ( $period ) {
			case 'today':
				return array( 'after' => $today . ' 00:00:00', 'before' => $today . ' 23:59:59' );
			case 'week':
				return array( 'after' => gmdate( 'Y-m-d', strtotime( '-7 days', current_time( 'timestamp' ) ) ) . ' 00:00:00' );
			case 'month':
				return array( 'after' => current_time( 'Y-m' ) . '-01 00:00:00' );
			case 'year':
				return array( 'after' => current_time( 'Y' ) . '-01-01 00:00:00' );
			case 'custom':
				$range = array();
				if ( $date_from ) { $range['after']  = sanitize_text_field( $date_from ) . ' 00:00:00'; }
				if ( $date_to )   { $range['before'] = sanitize_text_field( $date_to )   . ' 23:59:59'; }
				return $range;
			default:
				return array( 'after' => $today . ' 00:00:00' );
		}
	}

	private static function query_revenue( $date_range ) {
		$args = array_merge( array(
			'status'      => array( 'wc-completed', 'wc-processing' ),
			'return'      => 'objects',
			'limit'       => -1,
		), array_filter( array(
			'date_created' => self::format_date_range( $date_range ),
		) ) );

		$orders  = wc_get_orders( $args );
		$total   = array_sum( array_map( function( $o ) { return (float) $o->get_total(); }, $orders ) );
		$count   = count( $orders );
		$avg     = $count > 0 ? $total / $count : 0;

		return sprintf(
			"Revenue: %s | Orders: %d | Average order value: %s",
			wc_price( $total ), $count, wc_price( $avg )
		);
	}

	private static function query_orders( $date_range ) {
		$statuses = wc_get_order_statuses();
		$lines    = array();
		foreach ( $statuses as $slug => $label ) {
			$args = array(
				'status' => array( $slug ),
				'return' => 'ids',
				'limit'  => -1,
			);
			$date_created = self::format_date_range( $date_range );
			if ( $date_created ) { $args['date_created'] = $date_created; }
			$count = count( wc_get_orders( $args ) );
			if ( $count > 0 ) {
				$lines[] = "{$label}: {$count}";
			}
		}
		return empty( $lines ) ? 'No orders found for this period.' : implode( ' | ', $lines );
	}

	private static function query_top_products( $date_range, $limit = 5 ) {
		global $wpdb;
		$after  = $date_range['after']  ?? '';
		$before = $date_range['before'] ?? '';

		$date_sql = '';
		if ( $after )  { $date_sql .= $wpdb->prepare( ' AND p.post_date >= %s', $after ); }
		if ( $before ) { $date_sql .= $wpdb->prepare( ' AND p.post_date <= %s', $before ); }

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT oi.order_item_name AS name, SUM(oim.meta_value) AS qty
			 FROM {$wpdb->prefix}woocommerce_order_items oi
			 JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
			 JOIN {$wpdb->posts} p ON oi.order_id = p.ID
			 WHERE oi.order_item_type = 'line_item'
			   AND oim.meta_key = '_qty'
			   AND p.post_type = 'shop_order'
			   AND p.post_status IN ('wc-completed','wc-processing')
			   {$date_sql}
			 GROUP BY oi.order_item_name
			 ORDER BY qty DESC
			 LIMIT %d",
			$limit
		) );
		// phpcs:enable

		if ( empty( $rows ) ) { return 'No sales data found for this period.'; }
		$lines = array();
		foreach ( $rows as $i => $r ) {
			$lines[] = ( $i + 1 ) . '. ' . esc_html( $r->name ) . ' — ' . absint( $r->qty ) . ' sold';
		}
		return implode( "\n", $lines );
	}

	private static function query_low_stock( $limit = 10 ) {
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array( 'key' => '_manage_stock', 'value' => 'yes' ),
				array( 'key' => '_stock_status', 'value' => 'instock' ),
				array( 'key' => '_stock', 'value' => get_option( 'woocommerce_notify_low_stock_amount', 2 ), 'compare' => '<=', 'type' => 'NUMERIC' ),
			),
		);
		$query = new WP_Query( $args );
		if ( ! $query->have_posts() ) { return 'No low-stock products found. 👍'; }
		$lines = array();
		foreach ( $query->posts as $p ) {
			$product = wc_get_product( $p->ID );
			if ( $product ) {
				$lines[] = '⚠️ ' . $product->get_name() . ' — Stock: ' . $product->get_stock_quantity();
			}
		}
		wp_reset_postdata();
		return empty( $lines ) ? 'No low-stock products.' : implode( "\n", $lines );
	}

	private static function query_customers( $date_range, $limit = 5 ) {
		$after = $date_range['after'] ?? gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );

		$args = array(
			'date_registered' => '>' . $after,
			'number'          => $limit,
			'role'            => 'customer',
			'orderby'         => 'registered',
			'order'           => 'DESC',
		);
		$users = get_users( $args );
		if ( empty( $users ) ) { return 'No new customers found for this period.'; }
		$lines = array( 'New customers: ' . count( $users ) );
		foreach ( $users as $u ) {
			$lines[] = '• ' . esc_html( $u->display_name ) . ' (' . esc_html( $u->user_email ) . ')';
		}
		return implode( "\n", $lines );
	}

	private static function query_summary() {
		$today_revenue = self::query_revenue( self::resolve_period( 'today', '', '' ) );
		$month_revenue = self::query_revenue( self::resolve_period( 'month', '', '' ) );
		$pending_count = count( wc_get_orders( array( 'status' => array( 'wc-pending', 'wc-on-hold' ), 'return' => 'ids', 'limit' => -1 ) ) );
		$low_stock_count = ( new WP_Query( array(
			'post_type' => 'product', 'posts_per_page' => -1, 'fields' => 'ids',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array( 'key' => '_manage_stock', 'value' => 'yes' ),
				array( 'key' => '_stock_status', 'value' => 'instock' ),
				array( 'key' => '_stock', 'value' => get_option( 'woocommerce_notify_low_stock_amount', 2 ), 'compare' => '<=', 'type' => 'NUMERIC' ),
			),
		) ) )->found_posts;

		return "TODAY: {$today_revenue}\nTHIS MONTH: {$month_revenue}\nPending/On-hold orders: {$pending_count}\nLow stock products: {$low_stock_count}";
	}

	private static function format_date_range( $range ) {
		if ( empty( $range ) ) { return ''; }
		if ( isset( $range['after'] ) && isset( $range['before'] ) ) {
			return $range['after'] . '...' . $range['before'];
		}
		if ( isset( $range['after'] ) ) {
			return '>' . $range['after'];
		}
		return '';
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
