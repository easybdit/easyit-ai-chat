<?php
/**
 * Database abstraction layer.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEasyAI_DB {

	const SESSIONS_TABLE  = 'wpeasyai_sessions';
	const MESSAGES_TABLE  = 'wpeasyai_messages';

	public static function create_tables(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		$sessions = "CREATE TABLE {$wpdb->prefix}wpeasyai_sessions (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			uuid        VARCHAR(36)     NOT NULL,
			user_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
			guest_token VARCHAR(64)     NOT NULL DEFAULT '',
			provider    VARCHAR(32)     NOT NULL DEFAULT 'ollama',
			title       VARCHAR(255)    NOT NULL DEFAULT 'New Chat',
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uuid (uuid),
			KEY user_id (user_id),
			KEY guest_token (guest_token)
		) $charset;";

		$messages = "CREATE TABLE {$wpdb->prefix}wpeasyai_messages (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id BIGINT UNSIGNED NOT NULL,
			role       ENUM('user','assistant') NOT NULL,
			content    LONGTEXT        NOT NULL,
			created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY session_id (session_id)
		) $charset;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sessions );
		dbDelta( $messages );
	}

	/** Get all sessions for current user/guest, newest first */
	public static function get_sessions( int $user_id, string $guest_token ): array {
		global $wpdb;
		$table = $wpdb->prefix . self::SESSIONS_TABLE;
		if ( $user_id > 0 ) {
			return $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM $table WHERE user_id=%d ORDER BY updated_at DESC LIMIT 50", $user_id ),
				ARRAY_A
			) ?: [];
		}
		if ( $guest_token ) {
			return $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM $table WHERE guest_token=%s ORDER BY updated_at DESC LIMIT 20", $guest_token ),
				ARRAY_A
			) ?: [];
		}
		return [];
	}

	/** Get single session by UUID */
	public static function get_session( string $uuid ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . self::SESSIONS_TABLE;
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE uuid=%s", $uuid ),
			ARRAY_A
		);
		return $row ?: null;
	}

	/** Create new session, return uuid */
	public static function create_session( int $user_id, string $guest_token, string $provider, string $title = 'New Chat' ): string {
		global $wpdb;
		$uuid = wp_generate_uuid4();
		$wpdb->insert( $wpdb->prefix . self::SESSIONS_TABLE, [
			'uuid'        => $uuid,
			'user_id'     => $user_id,
			'guest_token' => $guest_token,
			'provider'    => $provider,
			'title'       => mb_substr( $title, 0, 255 ),
		] );
		return $uuid;
	}

	/** Update session title */
	public static function update_session_title( string $uuid, string $title ): void {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . self::SESSIONS_TABLE,
			[ 'title' => mb_substr( $title, 0, 255 ) ],
			[ 'uuid'  => $uuid ]
		);
	}

	/** Touch updated_at */
	public static function touch_session( string $uuid ): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}" . self::SESSIONS_TABLE . " SET updated_at=NOW() WHERE uuid=%s",
			$uuid
		) );
	}

	/** Delete session + messages */
	public static function delete_session( string $uuid ): void {
		global $wpdb;
		$session = self::get_session( $uuid );
		if ( ! $session ) return;
		$wpdb->delete( $wpdb->prefix . self::MESSAGES_TABLE, [ 'session_id' => $session['id'] ] );
		$wpdb->delete( $wpdb->prefix . self::SESSIONS_TABLE, [ 'uuid' => $uuid ] );
	}

	/** Get messages for session */
	public static function get_messages( string $uuid ): array {
		global $wpdb;
		$session = self::get_session( $uuid );
		if ( ! $session ) return [];
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT role, content, created_at FROM {$wpdb->prefix}" . self::MESSAGES_TABLE . " WHERE session_id=%d ORDER BY id ASC",
				$session['id']
			),
			ARRAY_A
		) ?: [];
	}

	/** Append a message */
	public static function add_message( string $uuid, string $role, string $content ): void {
		global $wpdb;
		$session = self::get_session( $uuid );
		if ( ! $session ) return;
		$wpdb->insert( $wpdb->prefix . self::MESSAGES_TABLE, [
			'session_id' => $session['id'],
			'role'       => $role,
			'content'    => $content,
		] );
		self::touch_session( $uuid );
	}

	/** Clear messages but keep session */
	public static function clear_messages( string $uuid ): void {
		global $wpdb;
		$session = self::get_session( $uuid );
		if ( ! $session ) return;
		$wpdb->delete( $wpdb->prefix . self::MESSAGES_TABLE, [ 'session_id' => $session['id'] ] );
		$wpdb->update(
			$wpdb->prefix . self::SESSIONS_TABLE,
			[ 'title' => 'New Chat' ],
			[ 'uuid' => $uuid ]
		);
	}

	/** Delete all data for user (GDPR) */
	public static function delete_user_data( int $user_id ): void {
		global $wpdb;
		$sessions = self::get_sessions( $user_id, '' );
		foreach ( $sessions as $s ) {
			self::delete_session( $s['uuid'] );
		}
	}
}
