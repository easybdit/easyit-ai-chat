<?php
/**
 * Database abstraction layer.
 *
 * Direct DB queries are unavoidable here because the plugin owns custom tables
 * (eaic_sessions, eaic_messages). Each query is properly prepared and the
 * results are cached using the WordPress object cache where it makes sense.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom table CRUD layer for chat sessions and messages.
 */
class EAIC_DB {

	const SESSIONS_TABLE = 'eaic_sessions';
	const MESSAGES_TABLE = 'eaic_messages';
	const FEEDBACK_TABLE = 'eaic_feedback';
	const CACHE_GROUP    = 'eaic';

	/**
	 * Create the plugin tables on activation.
	 *
	 * NOTE: We deliberately do not use ENUM for the `role` column because
	 * dbDelta() does not reliably parse ENUM definitions and the table can
	 * silently fail to upgrade on later releases. VARCHAR(20) is used and
	 * the role value is enforced in PHP before every insert (see add_message()).
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$sessions_table  = $wpdb->prefix . self::SESSIONS_TABLE;
		$messages_table  = $wpdb->prefix . self::MESSAGES_TABLE;

		$sessions = "CREATE TABLE {$sessions_table} (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			uuid        VARCHAR(36)     NOT NULL,
			user_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
			guest_token VARCHAR(64)     NOT NULL DEFAULT '',
			provider    VARCHAR(32)     NOT NULL DEFAULT 'ollama',
			title       VARCHAR(255)    NOT NULL DEFAULT 'New Chat',
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY uuid (uuid),
			KEY user_id (user_id),
			KEY guest_token (guest_token)
		) {$charset_collate};";

		$messages = "CREATE TABLE {$messages_table} (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id BIGINT UNSIGNED NOT NULL,
			role       VARCHAR(20)     NOT NULL DEFAULT 'user',
			content    LONGTEXT        NOT NULL,
			created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY session_id (session_id)
		) {$charset_collate};";

		$feedback_table = $wpdb->prefix . self::FEEDBACK_TABLE;
		$feedback = "CREATE TABLE {$feedback_table} (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_uuid VARCHAR(36)     NOT NULL,
			msg_index    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			rating       TINYINT         NOT NULL DEFAULT 1,
			created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY session_msg (session_uuid, msg_index),
			KEY session_uuid (session_uuid)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sessions );
		dbDelta( $messages );
		dbDelta( $feedback );
	}

	/**
	 * Get sessions for a user or guest.
	 *
	 * @param int    $user_id     User ID (0 if guest).
	 * @param string $guest_token Guest cookie token.
	 * @return array
	 */
	public static function get_sessions( $user_id, $guest_token ) {
		global $wpdb;
		$user_id = (int) $user_id;

		$cache_key = $user_id > 0
			? 'sessions_u_' . $user_id
			: 'sessions_g_' . md5( (string) $guest_token );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : array();
		}

		$table = $wpdb->prefix . self::SESSIONS_TABLE;

		if ( $user_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM {$table} WHERE user_id = %d ORDER BY updated_at DESC LIMIT 50",
					$user_id
				),
				ARRAY_A
			);
		} elseif ( '' !== (string) $guest_token ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM {$table} WHERE guest_token = %s ORDER BY updated_at DESC LIMIT 20",
					$guest_token
				),
				ARRAY_A
			);
		} else {
			$rows = array();
		}

		$rows = is_array( $rows ) ? $rows : array();
		wp_cache_set( $cache_key, $rows, self::CACHE_GROUP, MINUTE_IN_SECONDS );

		return $rows;
	}

	/**
	 * Get one session by UUID.
	 *
	 * @param string $uuid Session UUID.
	 * @return array|null
	 */
	public static function get_session( $uuid ) {
		global $wpdb;

		$cache_key = 'session_' . md5( (string) $uuid );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : null;
		}

		$table = $wpdb->prefix . self::SESSIONS_TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$table} WHERE uuid = %s",
				$uuid
			),
			ARRAY_A
		);

		$row = $row ? $row : null;
		wp_cache_set( $cache_key, $row, self::CACHE_GROUP, MINUTE_IN_SECONDS );

		return $row;
	}

	/**
	 * Create a new session.
	 *
	 * @param int    $user_id     User ID (0 if guest).
	 * @param string $guest_token Guest cookie token.
	 * @param string $provider    Provider slug.
	 * @param string $title       Initial title.
	 * @return string The new session UUID.
	 */
	public static function create_session( $user_id, $guest_token, $provider, $title = 'New Chat' ) {
		global $wpdb;
		$uuid = wp_generate_uuid4();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$wpdb->prefix . self::SESSIONS_TABLE,
			array(
				'uuid'        => $uuid,
				'user_id'     => (int) $user_id,
				'guest_token' => (string) $guest_token,
				'provider'    => (string) $provider,
				'title'       => mb_substr( (string) $title, 0, 255 ),
			),
			array( '%s', '%d', '%s', '%s', '%s' )
		);

		self::invalidate_session_list_cache( (int) $user_id, (string) $guest_token );

		return $uuid;
	}

	/**
	 * Update the title of a session.
	 *
	 * @param string $uuid  Session UUID.
	 * @param string $title New title.
	 * @return void
	 */
	public static function update_session_title( $uuid, $title ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->prefix . self::SESSIONS_TABLE,
			array( 'title' => mb_substr( (string) $title, 0, 255 ) ),
			array( 'uuid' => $uuid ),
			array( '%s' ),
			array( '%s' )
		);

		self::invalidate_session_cache( (string) $uuid );
	}

	/**
	 * Touch a session's updated_at timestamp.
	 *
	 * @param string $uuid Session UUID.
	 * @return void
	 */
	public static function touch_session( $uuid ) {
		global $wpdb;
		$table = $wpdb->prefix . self::SESSIONS_TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$table} SET updated_at = NOW() WHERE uuid = %s",
				$uuid
			)
		);

		self::invalidate_session_cache( (string) $uuid );
	}

	/**
	 * Delete a session and its messages.
	 *
	 * @param string $uuid Session UUID.
	 * @return void
	 */
	public static function delete_session( $uuid ) {
		global $wpdb;
		$session = self::get_session( $uuid );
		if ( ! $session ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->prefix . self::MESSAGES_TABLE,
			array( 'session_id' => (int) $session['id'] ),
			array( '%d' )
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->prefix . self::SESSIONS_TABLE,
			array( 'uuid' => (string) $uuid ),
			array( '%s' )
		);

		self::invalidate_session_cache( (string) $uuid );
		self::invalidate_session_messages_cache( (string) $uuid );
		self::invalidate_session_list_cache( (int) $session['user_id'], (string) $session['guest_token'] );
	}

	/**
	 * Get all messages for a session.
	 *
	 * @param string $uuid Session UUID.
	 * @return array
	 */
	public static function get_messages( $uuid ) {
		global $wpdb;
		$session = self::get_session( $uuid );
		if ( ! $session ) {
			return array();
		}

		$cache_key = 'messages_' . md5( (string) $uuid );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : array();
		}

		$table = $wpdb->prefix . self::MESSAGES_TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT role, content, created_at FROM {$table} WHERE session_id = %d ORDER BY id ASC",
				(int) $session['id']
			),
			ARRAY_A
		);

		$rows = is_array( $rows ) ? $rows : array();
		wp_cache_set( $cache_key, $rows, self::CACHE_GROUP, MINUTE_IN_SECONDS );

		return $rows;
	}

	/**
	 * Insert a single message.
	 *
	 * The $role argument is validated here (PHP-side allow-list) because we
	 * no longer rely on a MySQL ENUM column. Any value other than 'user' or
	 * 'assistant' is coerced to 'user'.
	 *
	 * @param string $uuid    Session UUID.
	 * @param string $role    'user' or 'assistant'.
	 * @param string $content Message content.
	 * @return void
	 */
	public static function add_message( $uuid, $role, $content ) {
		global $wpdb;

		// Enforce role allow-list (replaces the previous ENUM column constraint).
		$role = in_array( $role, array( 'user', 'assistant' ), true ) ? $role : 'user';

		$session = self::get_session( $uuid );
		if ( ! $session ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$wpdb->prefix . self::MESSAGES_TABLE,
			array(
				'session_id' => (int) $session['id'],
				'role'       => $role,
				'content'    => (string) $content,
			),
			array( '%d', '%s', '%s' )
		);

		self::invalidate_session_messages_cache( (string) $uuid );
		self::touch_session( $uuid );
	}

	/**
	 * Delete sessions (and their messages) not updated within $days days.
	 *
	 * Called by the daily wp-cron job.
	 *
	 * @param int $days Retention window in days (minimum 1).
	 * @return int Number of sessions deleted.
	 */
	public static function delete_expired_sessions( $days ) {
		global $wpdb;
		$days           = max( 1, (int) $days );
		$sessions_table = $wpdb->prefix . self::SESSIONS_TABLE;
		$messages_table = $wpdb->prefix . self::MESSAGES_TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$expired_ids = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id FROM {$sessions_table} WHERE updated_at < DATE_SUB( NOW(), INTERVAL %d DAY )",
				$days
			)
		);

		if ( empty( $expired_ids ) ) {
			return 0;
		}

		$id_list = implode( ',', array_map( 'absint', $expired_ids ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DELETE FROM {$messages_table} WHERE session_id IN ({$id_list})" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = (int) $wpdb->query( "DELETE FROM {$sessions_table} WHERE id IN ({$id_list})" );

		return $deleted;
	}

	/**
	 * Wipe data for a single user.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public static function delete_user_data( $user_id ) {
		$sessions = self::get_sessions( (int) $user_id, '' );
		foreach ( $sessions as $s ) {
			self::delete_session( $s['uuid'] );
		}
	}

	/**
	 * Invalidate cache for a single session.
	 *
	 * @param string $uuid Session UUID.
	 * @return void
	 */
	private static function invalidate_session_cache( $uuid ) {
		wp_cache_delete( 'session_' . md5( (string) $uuid ), self::CACHE_GROUP );
	}

	/**
	 * Invalidate cache for a session's message list.
	 *
	 * @param string $uuid Session UUID.
	 * @return void
	 */
	private static function invalidate_session_messages_cache( $uuid ) {
		wp_cache_delete( 'messages_' . md5( (string) $uuid ), self::CACHE_GROUP );
	}

	/**
	 * Invalidate the cached list of sessions for a user/guest.
	 *
	 * @param int    $user_id     User ID.
	 * @param string $guest_token Guest token.
	 * @return void
	 */
	private static function invalidate_session_list_cache( $user_id, $guest_token ) {
		if ( (int) $user_id > 0 ) {
			wp_cache_delete( 'sessions_u_' . (int) $user_id, self::CACHE_GROUP );
		}
		if ( '' !== (string) $guest_token ) {
			wp_cache_delete( 'sessions_g_' . md5( (string) $guest_token ), self::CACHE_GROUP );
		}
	}

	/**
	 * Return aggregate stats for the analytics dashboard.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_stats() {
		$cache_key = 'eaic_stats';
		$cached    = wp_cache_get( $cache_key, 'eaic' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$st = $wpdb->prefix . self::SESSIONS_TABLE;
		$mt = $wpdb->prefix . self::MESSAGES_TABLE;

		$stats = array(
			'total_sessions'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$st}" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			'total_messages'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$mt}" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			'messages_today'   => (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				"SELECT COUNT(*) FROM {$mt} WHERE DATE(created_at) = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				current_time( 'Y-m-d' )
			) ),
			'active_this_week' => (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				"SELECT COUNT(DISTINCT session_id) FROM {$mt} WHERE created_at >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) )
			) ),
			'top_provider'     => $wpdb->get_var( "SELECT provider FROM {$st} GROUP BY provider ORDER BY COUNT(*) DESC LIMIT 1" ) ?: '—', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		);

		wp_cache_set( $cache_key, $stats, 'eaic', 300 );
		return $stats;
	}

	/**
	 * Return message counts grouped by day for the last N days.
	 *
	 * @param int $days Number of days to look back.
	 * @return array<string,int> Keyed by Y-m-d date string.
	 */
	public static function get_messages_per_day( $days = 7 ) {
		$cache_key = 'eaic_msgs_per_day_' . (int) $days;
		$cached    = wp_cache_get( $cache_key, 'eaic' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$mt    = $wpdb->prefix . self::MESSAGES_TABLE;
		$since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			"SELECT DATE(created_at) AS day, COUNT(*) AS cnt FROM {$mt} WHERE created_at >= %s GROUP BY DATE(created_at) ORDER BY day ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$since
		), ARRAY_A );

		$result = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$result[ gmdate( 'Y-m-d', strtotime( "-{$i} days" ) ) ] = 0;
		}
		foreach ( (array) $rows as $row ) {
			if ( isset( $result[ $row['day'] ] ) ) {
				$result[ $row['day'] ] = (int) $row['cnt'];
			}
		}

		wp_cache_set( $cache_key, $result, 'eaic', 300 );
		return $result;
	}

	/**
	 * Save or update a thumbs-up/down rating for an AI message.
	 *
	 * @param string $session_uuid Session UUID.
	 * @param int    $msg_index    Zero-based index of the AI message within the session.
	 * @param int    $rating       1 (thumbs up) or -1 (thumbs down).
	 * @return bool
	 */
	public static function save_feedback( $session_uuid, $msg_index, $rating ) {
		global $wpdb;
		$table  = $wpdb->prefix . self::FEEDBACK_TABLE;
		$rating = ( (int) $rating >= 0 ) ? 1 : -1;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var( $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT id FROM {$table} WHERE session_uuid = %s AND msg_index = %d",
			$session_uuid,
			(int) $msg_index
		) );

		if ( $existing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array( 'rating' => $rating ),
				array( 'id' => (int) $existing ),
				array( '%d' ),
				array( '%d' )
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table,
				array(
					'session_uuid' => (string) $session_uuid,
					'msg_index'    => (int) $msg_index,
					'rating'       => $rating,
				),
				array( '%s', '%d', '%d' )
			);
		}
		return true;
	}

	/**
	 * Get total thumbs-up and thumbs-down counts.
	 *
	 * @return array{ thumbs_up: int, thumbs_down: int }
	 */
	public static function get_feedback_stats() {
		$cache_key = 'eaic_feedback_stats';
		$cached    = wp_cache_get( $cache_key, 'eaic' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::FEEDBACK_TABLE;

		$up   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE rating = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$down = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE rating = -1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		$result = array(
			'thumbs_up'   => $up,
			'thumbs_down' => $down,
		);

		wp_cache_set( $cache_key, $result, 'eaic', 300 );
		return $result;
	}
}
