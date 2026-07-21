<?php
/**
 * Return Bot — database layer.
 *
 * @package EasyIT_AI_Chat
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class EAIC_Return_DB {

	const TABLE    = 'eaic_return_chats';
	const REQUESTS = 'eaic_return_requests';
	const GROUP    = 'eaic_pro';

	public static function create_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$chats   = $wpdb->prefix . self::TABLE;
		$reqs    = $wpdb->prefix . self::REQUESTS;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( "CREATE TABLE {$chats} (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id VARCHAR(64)     NOT NULL DEFAULT '',
			user_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
			order_id   BIGINT UNSIGNED NOT NULL DEFAULT 0,
			role       VARCHAR(20)     NOT NULL DEFAULT 'user',
			message    LONGTEXT        NOT NULL,
			created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY session_id (session_id),
			KEY order_id (order_id),
			KEY created_at (created_at)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$reqs} (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id    BIGINT UNSIGNED NOT NULL,
			user_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
			session_id  VARCHAR(64)     NOT NULL DEFAULT '',
			items       LONGTEXT        NOT NULL,
			reason      VARCHAR(128)    NOT NULL DEFAULT '',
			status      VARCHAR(32)     NOT NULL DEFAULT 'pending',
			notes       TEXT            NOT NULL DEFAULT '',
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY order_id (order_id),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset};" );
	}

	public static function log( $session_id, $user_id, $order_id, $role, $message ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'session_id' => (string) $session_id,
				'user_id'    => (int) $user_id,
				'order_id'   => (int) $order_id,
				'role'       => in_array( $role, array( 'user', 'assistant' ), true ) ? $role : 'user',
				'message'    => (string) $message,
			),
			array( '%s', '%d', '%d', '%s', '%s' )
		);
	}

	public static function recent( $session_id, $limit = 8 ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT role, message FROM {$table} WHERE session_id = %s ORDER BY id DESC LIMIT %d",
			$session_id, $limit
		), ARRAY_A );
		return array_reverse( is_array( $rows ) ? $rows : array() );
	}

	public static function save_request( $order_id, $user_id, $session_id, $items, $reason ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$wpdb->prefix . self::REQUESTS,
			array(
				'order_id'   => (int) $order_id,
				'user_id'    => (int) $user_id,
				'session_id' => (string) $session_id,
				'items'      => wp_json_encode( $items ),
				'reason'     => mb_substr( (string) $reason, 0, 128 ),
				'status'     => 'pending',
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	public static function get_requests( $limit = 50, $offset = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . self::REQUESTS;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$limit, $offset
		), ARRAY_A );
	}

	public static function count_requests_by_status( $status = 'pending' ) {
		global $wpdb;
		$table = $wpdb->prefix . self::REQUESTS;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT COUNT(*) FROM {$table} WHERE status = %s",
			$status
		) );
	}

	public static function purge_old( $days = 90 ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $since ) );
	}
}
