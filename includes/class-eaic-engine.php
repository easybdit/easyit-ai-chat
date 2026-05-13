<?php
/**
 * AJAX request handler and chat engine.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires AJAX endpoints to provider, DB and rate-limit logic.
 */
class EAIC_Engine {

	const RATE_LIMIT_WINDOW = 60;
	const RATE_LIMIT_MAX    = 20;
	const MAX_MESSAGE_LEN   = 4000;

	/**
	 * Hook AJAX endpoints.
	 */
	public function __construct() {
		add_action( 'wp_ajax_eaic_send',            array( $this, 'ajax_send' ) );
		add_action( 'wp_ajax_nopriv_eaic_send',     array( $this, 'ajax_send' ) );
		add_action( 'wp_ajax_eaic_sessions',        array( $this, 'ajax_sessions' ) );
		add_action( 'wp_ajax_nopriv_eaic_sessions', array( $this, 'ajax_sessions' ) );
		add_action( 'wp_ajax_eaic_new',             array( $this, 'ajax_new_session' ) );
		add_action( 'wp_ajax_nopriv_eaic_new',      array( $this, 'ajax_new_session' ) );
		add_action( 'wp_ajax_eaic_history',         array( $this, 'ajax_history' ) );
		add_action( 'wp_ajax_nopriv_eaic_history',  array( $this, 'ajax_history' ) );
		add_action( 'wp_ajax_eaic_delete',          array( $this, 'ajax_delete' ) );
		add_action( 'wp_ajax_nopriv_eaic_delete',   array( $this, 'ajax_delete' ) );
		add_action( 'wp_ajax_eaic_health',          array( $this, 'ajax_health' ) );
	}

	/**
	 * Resolve the caller's identity (logged-in user or guest cookie).
	 *
	 * Will terminate the request if guest chat is disabled and the visitor
	 * is not logged in.
	 *
	 * @return array{0:int,1:string}
	 */
	private function get_identity() {
		$user_id     = get_current_user_id();
		$guest_token = '';

		if ( ! $user_id ) {
			$opts = EAIC_Options::all();
			if ( empty( $opts['allow_guest_chat'] ) ) {
				wp_send_json_error(
					array( 'message' => __( 'Please log in to use the chat.', 'easyit-ai-chat' ) ),
					401
				);
			}
			$cookie_name = 'eaic_guest_' . COOKIEHASH;
			if ( ! empty( $_COOKIE[ $cookie_name ] ) ) {
				$raw = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
				if ( preg_match( '/^[a-f0-9]{64}$/', $raw ) ) {
					$guest_token = $raw;
				}
			}
			if ( '' === $guest_token ) {
				$guest_token = bin2hex( random_bytes( 32 ) );
				// Use the array signature of setcookie() so we can pass SameSite.
				// Requires PHP 7.3+; this plugin requires PHP 8.0+.
				setcookie(
					$cookie_name,
					$guest_token,
					array(
						'expires'  => time() + YEAR_IN_SECONDS,
						'path'     => COOKIEPATH,
						'domain'   => COOKIE_DOMAIN,
						'secure'   => is_ssl(),
						'httponly' => true,
						'samesite' => 'Lax',
					)
				);
				// Make the just-set cookie available within the current request too.
				$_COOKIE[ $cookie_name ] = $guest_token;
			}
		}

		return array( (int) $user_id, (string) $guest_token );
	}

	/**
	 * Per-identity rate-limit gate.
	 *
	 * @param string $key Rate-limit bucket key.
	 * @return bool True when over the limit.
	 */
	private function is_rate_limited( $key ) {
		$transient = 'eaic_rl_' . md5( (string) $key );
		$count     = (int) get_transient( $transient );
		if ( $count >= self::RATE_LIMIT_MAX ) {
			return true;
		}
		set_transient( $transient, $count + 1, self::RATE_LIMIT_WINDOW );
		return false;
	}

	/**
	 * Build the right provider object.
	 *
	 * @param string $slug Provider slug.
	 * @return EAIC_Provider
	 */
	private function get_provider( $slug ) {
		$opts = EAIC_Options::all();
		switch ( $slug ) {
			case 'openai':
				return new EAIC_OpenAI( $opts );
			case 'anthropic':
				return new EAIC_Anthropic( $opts );
			case 'deepseek':
				return new EAIC_DeepSeek( $opts );
			case 'ollama':
			default:
				return new EAIC_Ollama( $opts );
		}
	}

	/**
	 * Validate a UUIDv4 string.
	 *
	 * @param string $uuid Value to check.
	 * @return bool
	 */
	private function is_valid_uuid( $uuid ) {
		return (bool) preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', (string) $uuid );
	}

	/**
	 * Ownership check for a session.
	 *
	 * @param array  $session     Session row.
	 * @param int    $user_id     Current user ID.
	 * @param string $guest_token Current guest cookie token.
	 * @return bool
	 */
	private function can_access_session( array $session, $user_id, $guest_token ) {
		if ( (int) $user_id > 0 ) {
			return (int) $session['user_id'] === (int) $user_id;
		}
		return '' !== (string) $guest_token && $session['guest_token'] === $guest_token;
	}

	/**
	 * POST /eaic_send  — submit a chat message.
	 *
	 * @return void
	 */
	public function ajax_send() {
		check_ajax_referer( 'eaic_nonce', 'nonce' );
		list( $user_id, $guest_token ) = $this->get_identity();

		$rate_key = $user_id > 0 ? 'u' . $user_id : 'g' . $guest_token;
		if ( $this->is_rate_limited( $rate_key ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Too many requests. Please wait a moment.', 'easyit-ai-chat' ) ),
				429
			);
		}

		// Nonce verified above by check_ajax_referer().
		$message  = isset( $_POST['message'] )  ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) )  : '';
		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) )            : 'ollama';
		$uuid     = isset( $_POST['session'] )  ? sanitize_text_field( wp_unslash( $_POST['session'] ) )      : '';
		$system   = isset( $_POST['system'] )   ? sanitize_textarea_field( wp_unslash( $_POST['system'] ) )   : '';

		if ( '' === $message ) {
			wp_send_json_error( array( 'message' => __( 'Empty message.', 'easyit-ai-chat' ) ), 400 );
		}
		if ( mb_strlen( $message ) > self::MAX_MESSAGE_LEN ) {
			wp_send_json_error( array( 'message' => __( 'Message too long.', 'easyit-ai-chat' ) ), 400 );
		}

		if ( '' !== $uuid && $this->is_valid_uuid( $uuid ) ) {
			$session = EAIC_DB::get_session( $uuid );
			if ( ! $session || ! $this->can_access_session( $session, $user_id, $guest_token ) ) {
				$uuid = '';
			}
		} else {
			$uuid = '';
		}

		if ( '' === $uuid ) {
			$uuid = EAIC_DB::create_session( $user_id, $guest_token, $provider, mb_substr( $message, 0, 40 ) );
		}

		$rows     = EAIC_DB::get_messages( $uuid );
		$messages = array();
		foreach ( $rows as $row ) {
			$messages[] = array( 'role' => $row['role'], 'content' => $row['content'] );
		}
		$messages[] = array( 'role' => 'user', 'content' => $message );

		if ( 0 === count( $rows ) ) {
			EAIC_DB::update_session_title( $uuid, mb_substr( $message, 0, 50 ) );
		}

		$opts          = EAIC_Options::all();
		$system_prompt = '' !== $system ? $system : ( isset( $opts['system_prompt'] ) ? $opts['system_prompt'] : '' );

		try {
			$ai_reply = $this->get_provider( $provider )->chat( $messages, $system_prompt );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}

		EAIC_DB::add_message( $uuid, 'user',      $message  );
		EAIC_DB::add_message( $uuid, 'assistant', $ai_reply );

		wp_send_json_success(
			array(
				'reply'    => $ai_reply,
				'session'  => $uuid,
				'provider' => $provider,
				'model'    => isset( $opts[ $provider . '_model' ] ) ? $opts[ $provider . '_model' ] : '',
			)
		);
	}

	/**
	 * POST /eaic_sessions  — list sessions for the caller.
	 *
	 * @return void
	 */
	public function ajax_sessions() {
		check_ajax_referer( 'eaic_nonce', 'nonce' );
		list( $user_id, $guest_token ) = $this->get_identity();
		wp_send_json_success( array( 'sessions' => EAIC_DB::get_sessions( $user_id, $guest_token ) ) );
	}

	/**
	 * POST /eaic_new  — create an empty session.
	 *
	 * @return void
	 */
	public function ajax_new_session() {
		check_ajax_referer( 'eaic_nonce', 'nonce' );
		list( $user_id, $guest_token ) = $this->get_identity();

		// Nonce verified above by check_ajax_referer().
		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : 'ollama';
		$uuid     = EAIC_DB::create_session( $user_id, $guest_token, $provider, __( 'New Chat', 'easyit-ai-chat' ) );
		wp_send_json_success( array( 'session' => $uuid ) );
	}

	/**
	 * POST /eaic_history  — fetch a session's messages.
	 *
	 * @return void
	 */
	public function ajax_history() {
		check_ajax_referer( 'eaic_nonce', 'nonce' );
		list( $user_id, $guest_token ) = $this->get_identity();

		// Nonce verified above by check_ajax_referer().
		$uuid = isset( $_POST['session'] ) ? sanitize_text_field( wp_unslash( $_POST['session'] ) ) : '';
		if ( ! $this->is_valid_uuid( $uuid ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid session.', 'easyit-ai-chat' ) ), 400 );
		}

		$session = EAIC_DB::get_session( $uuid );
		if ( ! $session || ! $this->can_access_session( $session, $user_id, $guest_token ) ) {
			wp_send_json_error( array( 'message' => __( 'Session not found.', 'easyit-ai-chat' ) ), 404 );
		}

		wp_send_json_success(
			array(
				'messages' => EAIC_DB::get_messages( $uuid ),
				'provider' => $session['provider'],
				'title'    => $session['title'],
			)
		);
	}

	/**
	 * POST /eaic_delete  — delete a session.
	 *
	 * @return void
	 */
	public function ajax_delete() {
		check_ajax_referer( 'eaic_nonce', 'nonce' );
		list( $user_id, $guest_token ) = $this->get_identity();

		// Nonce verified above by check_ajax_referer().
		$uuid = isset( $_POST['session'] ) ? sanitize_text_field( wp_unslash( $_POST['session'] ) ) : '';
		if ( ! $this->is_valid_uuid( $uuid ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid session.', 'easyit-ai-chat' ) ), 400 );
		}

		$session = EAIC_DB::get_session( $uuid );
		if ( ! $session || ! $this->can_access_session( $session, $user_id, $guest_token ) ) {
			wp_send_json_error( array( 'message' => __( 'Session not found.', 'easyit-ai-chat' ) ), 404 );
		}

		EAIC_DB::delete_session( $uuid );
		wp_send_json_success( array( 'done' => true ) );
	}

	/**
	 * POST /eaic_health  — admin-only provider connectivity test.
	 *
	 * @return void
	 */
	public function ajax_health() {
		check_ajax_referer( 'eaic_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorised.', 'easyit-ai-chat' ) ), 403 );
		}

		// Nonce verified above by check_ajax_referer().
		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : 'ollama';

		try {
			$ok = $this->get_provider( $provider )->health();
			if ( $ok ) {
				wp_send_json_success( array( 'message' => '✅ ' . __( 'Connected successfully!', 'easyit-ai-chat' ) ) );
			} else {
				wp_send_json_error( array( 'message' => '❌ ' . __( 'Connection failed. Check your settings.', 'easyit-ai-chat' ) ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => '❌ ' . $e->getMessage() ) );
		}
	}
}
