<?php
/**
 * Lead capture — DB layer.
 *
 * Stores name + email submitted via the pre-chat form shown by the
 * WooCommerce bot widgets (Order Status Bot / Product Q&A Bot / etc).
 * Used by the Analytics dashboard.
 *
 * @package EasyIT_AI_Chat
 * @since   2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAIC_Lead_DB {

	const TABLE = 'eaic_leads';

	/**
	 * Create the leads table (idempotent — dbDelta is safe to call repeatedly).
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;

		$table           = $wpdb->prefix . self::TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id         bigint(20) UNSIGNED   NOT NULL AUTO_INCREMENT,
			session_id varchar(64)           NOT NULL DEFAULT '',
			user_id    bigint(20) UNSIGNED   NOT NULL DEFAULT 0,
			name       varchar(100)          NOT NULL DEFAULT '',
			email      varchar(200)          NOT NULL DEFAULT '',
			context    varchar(20)           NOT NULL DEFAULT '',
			product_id bigint(20) UNSIGNED   NOT NULL DEFAULT 0,
			created_at datetime              NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_session (session_id),
			KEY idx_email   (email(20)),
			KEY idx_created (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Save a lead record.
	 *
	 * @param string $session_id Client-generated session id (bot widget scoped).
	 * @param int    $user_id    Logged-in user id, or 0 for guests.
	 * @param string $name       Visitor name.
	 * @param string $email      Visitor email.
	 * @param string $context    'order' | 'product' | 'general'.
	 * @param int    $product_id Related product id, if any.
	 * @return int Insert id.
	 */
	public static function save( $session_id, $user_id, $name, $email, $context = '', $product_id = 0 ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'session_id' => mb_substr( sanitize_text_field( $session_id ), 0, 64 ),
				'user_id'    => absint( $user_id ),
				'name'       => mb_substr( sanitize_text_field( $name ), 0, 100 ),
				'email'      => mb_substr( sanitize_email( $email ), 0, 200 ),
				'context'    => mb_substr( sanitize_text_field( $context ), 0, 20 ),
				'product_id' => absint( $product_id ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%d', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Retrieve leads for the Analytics dashboard.
	 *
	 * @param array $args { limit: int, offset: int }.
	 * @return array
	 */
	public static function get_leads( $args = array() ) {
		global $wpdb;

		$limit  = isset( $args['limit'] )  ? absint( $args['limit'] )  : 25;
		$offset = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'SELECT * FROM ' . $wpdb->prefix . self::TABLE . ' ORDER BY created_at DESC LIMIT %d OFFSET %d',
				$limit,
				$offset
			),
			ARRAY_A
		);
	}

	/**
	 * Total number of leads captured.
	 *
	 * @return int
	 */
	public static function count_total() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . self::TABLE );
	}

	/**
	 * Number of leads captured in the last N days.
	 *
	 * @param int $days Lookback window.
	 * @return int
	 */
	public static function count_since( $days = 7 ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'SELECT COUNT(*) FROM ' . $wpdb->prefix . self::TABLE . ' WHERE created_at >= %s',
				gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) )
			)
		);
	}
}
