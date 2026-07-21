<?php
/**
 * Product Q&A Bot — Database layer.
 *
 * @package EasyIT_AI_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAIC_Product_DB {

	const DB_VERSION = '1.0.0';

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'eaic_product_chats';
	}

	public static function install() {
		global $wpdb;

		$table           = self::table();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id VARCHAR(64) NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			product_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			role VARCHAR(16) NOT NULL,
			message LONGTEXT NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY session_idx (session_id, created_at),
			KEY product_idx (product_id),
			KEY created_idx (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'eaic_product_db_version', self::DB_VERSION );
	}

	public static function log( $session_id, $user_id, $product_id, $role, $message ) {
		global $wpdb;

		$wpdb->insert(
			self::table(),
			array(
				'session_id' => substr( $session_id, 0, 64 ),
				'user_id'    => absint( $user_id ),
				'product_id' => absint( $product_id ),
				'role'       => in_array( $role, array( 'user', 'assistant' ), true ) ? $role : 'user',
				'message'    => $message,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s' )
		);
	}

	public static function recent( $session_id, $limit = 8 ) {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT role, message FROM ' . self::table() .
				' WHERE session_id = %s ORDER BY id DESC LIMIT %d',
				$session_id,
				absint( $limit )
			),
			ARRAY_A
		);

		return array_reverse( (array) $rows );
	}

	public static function purge_old() {
		global $wpdb;

		$days = (int) apply_filters( 'eaic_product_log_retention_days', 90 );
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . self::table() . ' WHERE created_at < %s',
				gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) )
			)
		);
	}
}
