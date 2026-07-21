<?php
/**
 * Order Status Bot — Database layer.
 *
 * Custom table for chat logs. Indexed on session_id + created_at
 * so it scales to millions of rows. Old rows auto-purged via cron.
 *
 * @package EasyIT_AI_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAIC_Order_DB {

	const DB_VERSION = '1.0.0';

	/**
	 * Returns the prefixed table name.
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'eaic_order_chats';
	}

	/**
	 * Create / upgrade the table. Call on plugin activation.
	 */
	public static function install() {
		global $wpdb;

		$table           = self::table();
		$charset_collate = $wpdb->get_charset_collate();

		// dbDelta needs this exact loose formatting.
		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id VARCHAR(64) NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			order_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			role VARCHAR(16) NOT NULL,
			message LONGTEXT NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY session_idx (session_id, created_at),
			KEY order_idx (order_id),
			KEY created_idx (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'eaic_order_db_version', self::DB_VERSION );
	}

	/**
	 * Insert one chat line. Message is sanitized by the caller.
	 *
	 * @param string $session_id Session identifier.
	 * @param int    $user_id    Logged-in user id, or 0 for guest.
	 * @param int    $order_id   Related order id, or 0.
	 * @param string $role       'user' or 'assistant'.
	 * @param string $message    Already-sanitized message text.
	 */
	public static function log( $session_id, $user_id, $order_id, $role, $message ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			self::table(),
			array(
				'session_id' => substr( $session_id, 0, 64 ),
				'user_id'    => absint( $user_id ),
				'order_id'   => absint( $order_id ),
				'role'       => in_array( $role, array( 'user', 'assistant' ), true ) ? $role : 'user',
				'message'    => $message,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Fetch the last N messages of a session, oldest first.
	 * Used to give the AI short context without blowing up tokens.
	 *
	 * @param string $session_id Session identifier.
	 * @param int    $limit      How many recent rows.
	 * @return array
	 */
	public static function recent( $session_id, $limit = 8 ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
				'SELECT role, message FROM ' . self::table() .
				' WHERE session_id = %s ORDER BY id DESC LIMIT %d',
				$session_id,
				absint( $limit )
			),
			ARRAY_A
		);

		return array_reverse( (array) $rows );
	}

	/**
	 * Cron callback: delete logs older than the retention window.
	 */
	public static function purge_old() {
		global $wpdb;

		$days = (int) apply_filters( 'eaic_log_retention_days', 90 );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
				'DELETE FROM ' . self::table() . ' WHERE created_at < %s',
				gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) )
			)
		);
	}
}
