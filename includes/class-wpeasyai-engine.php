<?php
/**
 * AJAX request handler and chat engine.
 *
 * @package WPEasyAI
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEasyAI_Engine {

	const RATE_LIMIT_WINDOW = 60;
	const RATE_LIMIT_MAX    = 20;
	const MAX_MESSAGE_LEN   = 4000;

	public function __construct() {
		add_action( 'wp_ajax_wpeasyai_send',            [ $this, 'ajax_send' ] );
		add_action( 'wp_ajax_nopriv_wpeasyai_send',     [ $this, 'ajax_send' ] );
		add_action( 'wp_ajax_wpeasyai_sessions',        [ $this, 'ajax_sessions' ] );
		add_action( 'wp_ajax_nopriv_wpeasyai_sessions', [ $this, 'ajax_sessions' ] );
		add_action( 'wp_ajax_wpeasyai_new',             [ $this, 'ajax_new_session' ] );
		add_action( 'wp_ajax_nopriv_wpeasyai_new',      [ $this, 'ajax_new_session' ] );
		add_action( 'wp_ajax_wpeasyai_history',         [ $this, 'ajax_history' ] );
		add_action( 'wp_ajax_nopriv_wpeasyai_history',  [ $this, 'ajax_history' ] );
		add_action( 'wp_ajax_wpeasyai_delete',          [ $this, 'ajax_delete' ] );
		add_action( 'wp_ajax_nopriv_wpeasyai_delete',   [ $this, 'ajax_delete' ] );
		add_action( 'wp_ajax_wpeasyai_health',          [ $this, 'ajax_health' ] );
	}

	private function verify_nonce(): void {
		if ( ! check_ajax_referer( 'wpeasyai_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'wpeasyai' ) ], 403 );
			wp_die();
		}
	}

	private function get_identity(): array {
		$user_id     = get_current_user_id();
		$guest_token = '';

		if ( ! $user_id ) {
			$opts = WPEasyAI_Options::all();
			if ( empty( $opts['allow_guest_chat'] ) ) {
				wp_send_json_error( [ 'message' => __( 'Please log in to use the chat.', 'wpeasyai' ) ], 401 );
				wp_die();
			}
			$cookie_name = 'lai_guest_' . COOKIEHASH;
			if ( ! empty( $_COOKIE[ $cookie_name ] ) ) {
				$raw = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
				if ( preg_match( '/^[a-f0-9]{64}$/', $raw ) ) {
					$guest_token = $raw;
				}
			}
			if ( ! $guest_token ) {
				$guest_token = bin2hex( random_bytes( 32 ) );
				setcookie( $cookie_name, $guest_token, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			}
		}

		return [ $user_id, $guest_token ];
	}

	private function is_rate_limited( string $key ): bool {
		$transient = 'weai_rl_' . md5( $key );
		$count     = (int) get_transient( $transient );
		if ( $count >= self::RATE_LIMIT_MAX ) return true;
		set_transient( $transient, $count + 1, self::RATE_LIMIT_WINDOW );
		return false;
	}

	private function get_provider( string $slug ): WPEasyAI_Provider {
		$opts = WPEasyAI_Options::all();
		return match ( $slug ) {
			'openai'    => new WPEasyAI_OpenAI( $opts ),
			'anthropic' => new WPEasyAI_Anthropic( $opts ),
			'deepseek'  => new WPEasyAI_DeepSeek( $opts ),
			default     => new WPEasyAI_Ollama( $opts ),
		};
	}

	private function is_valid_uuid( string $uuid ): bool {
		return (bool) preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid );
	}

	private function can_access_session( array $session, int $user_id, string $guest_token ): bool {
		if ( $user_id > 0 ) return (int) $session['user_id'] === $user_id;
		return ! empty( $guest_token ) && $session['guest_token'] === $guest_token;
	}

	public function ajax_send(): void {
		$this->verify_nonce();
		[ $user_id, $guest_token ] = $this->get_identity();

		$rate_key = $user_id > 0 ? 'u' . $user_id : 'g' . $guest_token;
		if ( $this->is_rate_limited( $rate_key ) ) {
			wp_send_json_error( [ 'message' => __( 'Too many requests. Please wait a moment.', 'wpeasyai' ) ], 429 );
			return;
		}

		$message  = isset( $_POST['message'] )  ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) )  : '';
		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : 'ollama';
		$uuid     = isset( $_POST['session'] )  ? sanitize_text_field( wp_unslash( $_POST['session'] ) )       : '';
		$system   = isset( $_POST['system'] )   ? sanitize_textarea_field( wp_unslash( $_POST['system'] ) )   : '';

		if ( empty( $message ) ) {
			wp_send_json_error( [ 'message' => __( 'Empty message.', 'wpeasyai' ) ], 400 );
			return;
		}
		if ( mb_strlen( $message ) > self::MAX_MESSAGE_LEN ) {
			wp_send_json_error( [ 'message' => __( 'Message too long.', 'wpeasyai' ) ], 400 );
			return;
		}

		if ( $uuid && $this->is_valid_uuid( $uuid ) ) {
			$session = WPEasyAI_DB::get_session( $uuid );
			if ( ! $session || ! $this->can_access_session( $session, $user_id, $guest_token ) ) {
				$uuid = '';
			}
		} else {
			$uuid = '';
		}

		if ( ! $uuid ) {
			$uuid = WPEasyAI_DB::create_session( $user_id, $guest_token, $provider, mb_substr( $message, 0, 40 ) );
		}

		$rows     = WPEasyAI_DB::get_messages( $uuid );
		$messages = [];
		foreach ( $rows as $row ) {
			$messages[] = [ 'role' => $row['role'], 'content' => $row['content'] ];
		}
		$messages[] = [ 'role' => 'user', 'content' => $message ];

		if ( count( $rows ) === 0 ) {
			WPEasyAI_DB::update_session_title( $uuid, mb_substr( $message, 0, 50 ) );
		}

		$opts          = WPEasyAI_Options::all();
		$system_prompt = ! empty( $system ) ? $system : ( $opts['system_prompt'] ?? '' );

		try {
			$ai_reply = $this->get_provider( $provider )->chat( $messages, $system_prompt );
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
			return;
		}

		WPEasyAI_DB::add_message( $uuid, 'user',      $message  );
		WPEasyAI_DB::add_message( $uuid, 'assistant', $ai_reply );

		wp_send_json_success( [
			'reply'    => $ai_reply,
			'session'  => $uuid,
			'provider' => $provider,
			'model'    => $opts[ $provider . '_model' ] ?? '',
		] );
	}

	public function ajax_sessions(): void {
		$this->verify_nonce();
		[ $user_id, $guest_token ] = $this->get_identity();
		wp_send_json_success( [ 'sessions' => WPEasyAI_DB::get_sessions( $user_id, $guest_token ) ] );
	}

	public function ajax_new_session(): void {
		$this->verify_nonce();
		[ $user_id, $guest_token ] = $this->get_identity();
		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : 'ollama';
		$uuid     = WPEasyAI_DB::create_session( $user_id, $guest_token, $provider, __( 'New Chat', 'wpeasyai' ) );
		wp_send_json_success( [ 'session' => $uuid ] );
	}

	public function ajax_history(): void {
		$this->verify_nonce();
		[ $user_id, $guest_token ] = $this->get_identity();

		$uuid = isset( $_POST['session'] ) ? sanitize_text_field( wp_unslash( $_POST['session'] ) ) : '';
		if ( ! $this->is_valid_uuid( $uuid ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid session.', 'wpeasyai' ) ], 400 );
			return;
		}

		$session = WPEasyAI_DB::get_session( $uuid );
		if ( ! $session || ! $this->can_access_session( $session, $user_id, $guest_token ) ) {
			wp_send_json_error( [ 'message' => __( 'Session not found.', 'wpeasyai' ) ], 404 );
			return;
		}

		wp_send_json_success( [
			'messages' => WPEasyAI_DB::get_messages( $uuid ),
			'provider' => $session['provider'],
			'title'    => $session['title'],
		] );
	}

	public function ajax_delete(): void {
		$this->verify_nonce();
		[ $user_id, $guest_token ] = $this->get_identity();

		$uuid = isset( $_POST['session'] ) ? sanitize_text_field( wp_unslash( $_POST['session'] ) ) : '';
		if ( ! $this->is_valid_uuid( $uuid ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid session.', 'wpeasyai' ) ], 400 );
			return;
		}

		$session = WPEasyAI_DB::get_session( $uuid );
		if ( ! $session || ! $this->can_access_session( $session, $user_id, $guest_token ) ) {
			wp_send_json_error( [ 'message' => __( 'Session not found.', 'wpeasyai' ) ], 404 );
			return;
		}

		WPEasyAI_DB::delete_session( $uuid );
		wp_send_json_success( [ 'done' => true ] );
	}

	public function ajax_health(): void {
		if ( ! check_ajax_referer( 'wpeasyai_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'wpeasyai' ) ], 403 );
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorised.', 'wpeasyai' ) ], 403 );
			return;
		}
		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : 'ollama';
		try {
			$ok = $this->get_provider( $provider )->health();
			if ( $ok ) {
				wp_send_json_success( [ 'message' => '&#x2705; ' . __( 'Connected successfully!', 'wpeasyai' ) ] );
			} else {
				wp_send_json_error( [ 'message' => '&#x274C; ' . __( 'Connection failed. Check your settings.', 'wpeasyai' ) ] );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => '&#x274C; ' . $e->getMessage() ] );
		}
	}
}
