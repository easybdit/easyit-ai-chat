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

class EAIC_Engine {

	const MAX_MESSAGE_LEN       = 4000;
	const MAX_SYSTEM_PROMPT_LEN = 500;

	public function __construct() {
		add_action( 'wp_ajax_eaic_send',                    array( $this, 'ajax_send' ) );
		add_action( 'wp_ajax_nopriv_eaic_send',             array( $this, 'ajax_send' ) );
		add_action( 'wp_ajax_eaic_stream',                  array( $this, 'ajax_stream' ) );
		add_action( 'wp_ajax_nopriv_eaic_stream',           array( $this, 'ajax_stream' ) );
		add_action( 'wp_ajax_eaic_sessions',                array( $this, 'ajax_sessions' ) );
		add_action( 'wp_ajax_nopriv_eaic_sessions',         array( $this, 'ajax_sessions' ) );
		add_action( 'wp_ajax_eaic_new',                     array( $this, 'ajax_new_session' ) );
		add_action( 'wp_ajax_nopriv_eaic_new',              array( $this, 'ajax_new_session' ) );
		add_action( 'wp_ajax_eaic_history',                 array( $this, 'ajax_history' ) );
		add_action( 'wp_ajax_nopriv_eaic_history',          array( $this, 'ajax_history' ) );
		add_action( 'wp_ajax_eaic_delete',                  array( $this, 'ajax_delete' ) );
		add_action( 'wp_ajax_nopriv_eaic_delete',           array( $this, 'ajax_delete' ) );
		add_action( 'wp_ajax_eaic_health',                  array( $this, 'ajax_health' ) );
		add_action( 'wp_ajax_eaic_test_custom_provider',   array( $this, 'ajax_test_custom_provider' ) );
		add_action( 'wp_ajax_eaic_feedback',                array( $this, 'ajax_feedback' ) );
		add_action( 'wp_ajax_nopriv_eaic_feedback',         array( $this, 'ajax_feedback' ) );
	}

	// -----------------------------------------------------------------------
	// Identity & rate limiting
	// -----------------------------------------------------------------------

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
				$_COOKIE[ $cookie_name ] = $guest_token;
			}
		}

		return array( (int) $user_id, (string) $guest_token );
	}

	private function is_rate_limited( $key, $max, $window ) {
		$transient = 'eaic_rl_' . md5( (string) $key );
		$count     = (int) get_transient( $transient );
		if ( $count >= (int) $max ) {
			return true;
		}
		set_transient( $transient, $count + 1, (int) $window );
		return false;
	}

	private function get_client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return (string) apply_filters( 'eaic_client_ip', $ip );
	}

	// -----------------------------------------------------------------------
	// Provider factory
	// -----------------------------------------------------------------------

	private function get_provider( $slug ) {
		$opts = EAIC_Options::all();

		if ( 0 === strpos( $slug, 'custom_' ) ) {
			$index            = (int) substr( $slug, 7 ) - 1; // 'custom_1' → index 0
			$custom_providers = isset( $opts['custom_providers'] ) && is_array( $opts['custom_providers'] )
				? $opts['custom_providers']
				: array();
			if ( isset( $custom_providers[ $index ] ) ) {
				return new EAIC_Custom( $custom_providers[ $index ] );
			}
			throw new \RuntimeException( 'Custom provider not found: ' . esc_html( $slug ) );
		}

		switch ( $slug ) {
			case 'openai':    return new EAIC_OpenAI( $opts );
			case 'anthropic': return new EAIC_Anthropic( $opts );
			case 'deepseek':  return new EAIC_DeepSeek( $opts );
			case 'gemini':    return new EAIC_Gemini( $opts );
			case 'ollama':
			default:          return new EAIC_Ollama( $opts );
		}
	}

	/**
	 * Return slugs of all enabled custom providers.
	 *
	 * @param array $opts Plugin options.
	 * @return string[]
	 */
	private function custom_provider_slugs( array $opts ) {
		$slugs = array();
		if ( ! empty( $opts['custom_providers'] ) && is_array( $opts['custom_providers'] ) ) {
			foreach ( $opts['custom_providers'] as $i => $cp ) {
				if ( ! empty( $cp['enabled'] ) ) {
					$slugs[] = 'custom_' . ( $i + 1 );
				}
			}
		}
		return $slugs;
	}


	/**
	 * Public bridge for add-on modules (e.g. Order Status Bot).
	 *
	 * @param string $system   System prompt.
	 * @param array  $messages [ ['role'=>'user','content'=>..], .. ].
	 * @return string
	 */
	public function run_completion( $system, array $messages ) {
		$slug = (string) EAIC_Options::get( 'default_provider' );
		try {
			return (string) $this->get_provider( $slug )->chat( $messages, (string) $system );
		} catch ( \Throwable $e ) {
			return '';
		}
	}

	

	// -----------------------------------------------------------------------
	// Shared input validation / session resolution
	// -----------------------------------------------------------------------

	/**
	 * Parse + validate the common POST fields for both send & stream.
	 * Returns array of [$message, $provider, $uuid, $system_prompt, $is_first].
	 * Calls wp_send_json_error and exits on failure.
	 */
	private function resolve_request( $user_id, $guest_token ) {
		$opts = EAIC_Options::all();

		// Rate limiting.
		$rl_window = max( 10, (int) $opts['rate_limit_window'] );
		$rl_max    = max( 1,  (int) $opts['rate_limit_max'] );
		$rate_key  = $user_id > 0 ? 'u' . $user_id : 'g' . $guest_token;
		if ( $this->is_rate_limited( $rate_key, $rl_max, $rl_window ) ) {
			$this->maybe_send_abuse_alert( $opts );
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please wait a moment.', 'easyit-ai-chat' ) ), 429 );
		}

		$rl_ip_max = max( 1, (int) $opts['rate_limit_ip_max'] );
		$client_ip = $this->get_client_ip();
		if ( '' !== $client_ip && $this->is_rate_limited( 'ip_' . $client_ip, $rl_ip_max, $rl_window ) ) {
			$this->maybe_send_abuse_alert( $opts );
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please wait a moment.', 'easyit-ai-chat' ) ), 429 );
		}

		// ---- v2.0.0 Security checks ----

		// A. IP Blocklist.
		if ( ! empty( $opts['ip_blocklist'] ) && '' !== $client_ip ) {
			$blocked_ips = array_filter( array_map( 'trim', explode( "\n", $opts['ip_blocklist'] ) ) );
			if ( in_array( $client_ip, $blocked_ips, true ) ) {
				wp_send_json_error( array( 'message' => __( 'Access denied.', 'easyit-ai-chat' ) ), 403 );
			}
		}

		// C. User Role Restriction.
		$restriction = $opts['access_restriction'] ?? 'everyone';
		if ( 'logged_in' === $restriction && ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in to use this chat.', 'easyit-ai-chat' ) ), 403 );
		}
		if ( 'specific_roles' === $restriction && is_user_logged_in() ) {
			$current_user  = wp_get_current_user();
			$allowed_roles = is_array( $opts['allowed_roles'] ) ? $opts['allowed_roles'] : array();
			if ( ! array_intersect( (array) $current_user->roles, $allowed_roles ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to use this chat.', 'easyit-ai-chat' ) ), 403 );
			}
		}
		if ( 'specific_roles' === $restriction && ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in to use this chat.', 'easyit-ai-chat' ) ), 403 );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by caller (check_ajax_referer / wp_verify_nonce) before resolve_request() is invoked.
		$message  = isset( $_POST['message'] )  ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) )           : 'ollama'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$uuid     = isset( $_POST['session'] )  ? sanitize_text_field( wp_unslash( $_POST['session'] ) )     : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$allowed_providers = isset( $opts['allowed_providers'] ) && is_array( $opts['allowed_providers'] )
			? $opts['allowed_providers']
			: array( 'ollama', 'openai', 'anthropic', 'deepseek', 'gemini' );
		$allowed_providers = array_merge( $allowed_providers, $this->custom_provider_slugs( $opts ) );
		if ( ! in_array( $provider, $allowed_providers, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Provider not allowed.', 'easyit-ai-chat' ) ), 400 );
		}

		if ( '' === $message ) {
			wp_send_json_error( array( 'message' => __( 'Empty message.', 'easyit-ai-chat' ) ), 400 );
		}

		// H. Message Length (configurable).
		$max_msg_len = max( 50, (int) ( $opts['max_message_length'] ?? self::MAX_MESSAGE_LEN ) );
		if ( mb_strlen( $message ) > $max_msg_len ) {
			wp_send_json_error( array( 'message' => __( 'Message too long.', 'easyit-ai-chat' ) ), 400 );
		}

		// B. Word Filter.
		if ( ! empty( $opts['word_filter_enabled'] ) && ! empty( $opts['word_filter_words'] ) ) {
			$banned = array_filter( array_map( 'trim', explode( "\n", $opts['word_filter_words'] ) ) );
			foreach ( $banned as $word ) {
				if ( '' !== $word && mb_stripos( $message, $word ) !== false ) {
					if ( 'warn' === ( $opts['word_filter_action'] ?? 'block' ) ) {
						wp_send_json_error( array( 'message' => __( 'Your message contains a word that is not allowed.', 'easyit-ai-chat' ) ), 400 );
					} else {
						// Silent block — return fake OK to not reveal filter.
						wp_send_json_error( array( 'message' => __( 'Message could not be sent.', 'easyit-ai-chat' ) ), 400 );
					}
				}
			}
		}

		// G. Prompt Injection Detection.
		if ( ! empty( $opts['prompt_injection_detect'] ) ) {
			$injection_patterns = array(
				'/ignore\s+(all\s+)?(previous|prior|above|earlier)\s+(instructions?|prompts?|rules?)/i',
				'/you\s+are\s+now\s+(a\s+)?/i',
				'/act\s+as\s+(if\s+(you\s+are|you\'re)\s+)?a\s+/i',
				'/disregard\s+(all\s+)?(your|previous|prior)\s+/i',
				'/forget\s+(everything|all|your\s+instructions)/i',
				'/jailbreak/i',
				'/DAN\s+mode/i',
				'/\[SYSTEM\]/i',
				'/pretend\s+(you\s+are|to\s+be)\s+/i',
				'/override\s+(your\s+)?(safety|instructions|guidelines)/i',
			);
			foreach ( $injection_patterns as $pattern ) {
				if ( preg_match( $pattern, $message ) ) {
					if ( 'warn' === ( $opts['prompt_injection_action'] ?? 'block' ) ) {
						wp_send_json_error( array( 'message' => __( 'Suspicious message pattern detected. Please rephrase.', 'easyit-ai-chat' ) ), 400 );
					} else {
						wp_send_json_error( array( 'message' => __( 'Message could not be sent.', 'easyit-ai-chat' ) ), 400 );
					}
					break;
				}
			}
		}

		// E. Math Captcha verification (first message only).
		if ( ! empty( $opts['captcha_enabled'] ) ) {
			$cap_token  = isset( $_POST['cap_token'] )  ? sanitize_text_field( wp_unslash( $_POST['cap_token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$cap_answer = isset( $_POST['cap_answer'] ) ? absint( wp_unslash( $_POST['cap_answer'] ) )             : -1; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! $this->verify_captcha( $cap_token, $cap_answer ) ) {
				wp_send_json_error( array( 'message' => __( 'Incorrect captcha answer. Please try again.', 'easyit-ai-chat' ) ), 400 );
			}
		}

		// Session resolution.
		$disable_storage = ! empty( $opts['disable_storage'] );
		if ( $disable_storage ) {
			// No-storage mode: generate a temporary UUID, skip all DB session operations.
			$uuid             = $this->is_valid_uuid( $uuid ) ? $uuid : wp_generate_uuid4();
			$is_first_message = true;
			$rows             = array();
		} else {
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
			$rows             = EAIC_DB::get_messages( $uuid );
			$is_first_message = 0 === count( $rows );
		}
		$ctx_limit        = max( 1, (int) EAIC_Options::get( 'context_messages', 10 ) );
		// Keep only the last N messages for context window.
		if ( count( $rows ) > $ctx_limit ) {
			$rows = array_slice( $rows, -$ctx_limit );
		}
		$messages = array();
		foreach ( $rows as $row ) {
			$messages[] = array( 'role' => $row['role'], 'content' => $row['content'] );
		}
		$messages[] = array( 'role' => 'user', 'content' => $message );

		// System prompt.
		if ( ! empty( $opts['lock_system_prompt'] ) ) {
			$system_prompt = isset( $opts['system_prompt'] ) ? (string) $opts['system_prompt'] : '';
		} else {
			$system = isset( $_POST['system'] ) ? sanitize_textarea_field( wp_unslash( $_POST['system'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( mb_strlen( $system ) > self::MAX_SYSTEM_PROMPT_LEN ) {
				$system = mb_substr( $system, 0, self::MAX_SYSTEM_PROMPT_LEN );
			}
			$system_prompt = '' !== $system ? $system : ( isset( $opts['system_prompt'] ) ? $opts['system_prompt'] : '' );
		}

		return array( $message, $provider, $uuid, $messages, $system_prompt, $is_first_message, $opts );
	}

	// -----------------------------------------------------------------------
	// AJAX: classic (non-streaming) send — kept for fallback
	// -----------------------------------------------------------------------

	public function ajax_send() {
		check_ajax_referer( 'eaic_nonce', 'nonce' );
		list( $user_id, $guest_token ) = $this->get_identity();
		list( $message, $provider, $uuid, $messages, $system_prompt, $is_first_message, $opts )
			= $this->resolve_request( $user_id, $guest_token );

		try {
			$ai_reply = $this->get_provider( $provider )->chat( $messages, $system_prompt );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}

		$new_title = null;
		if ( ! EAIC_Options::get( 'disable_storage', false ) ) {
			EAIC_DB::add_message( $uuid, 'user',      $message );
			EAIC_DB::add_message( $uuid, 'assistant', $ai_reply );
			if ( $is_first_message ) {
				$new_title = $this->generate_title( $provider, $message );
				EAIC_DB::update_session_title( $uuid, $new_title );
			}
		}

		wp_send_json_success( array(
			'reply'    => $ai_reply,
			'session'  => $uuid,
			'provider' => $provider,
			'model'    => isset( $opts[ $provider . '_model' ] ) ? $opts[ $provider . '_model' ] : '',
			'title'    => $new_title,
		) );
	}

	// -----------------------------------------------------------------------
	// AJAX: SSE streaming endpoint
	// -----------------------------------------------------------------------

	public function ajax_stream() {
		// Verify nonce manually — can't use check_ajax_referer because we
		// need to set SSE headers BEFORE any output, and nonce failure needs
		// to emit an SSE error event, not a JSON response.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'eaic_nonce' ) ) {
			// Send as SSE error so JS can handle it gracefully.
			$this->sse_headers();
			EAIC_Provider::sse_send( 'error', array( 'message' => __( 'Security check failed.', 'easyit-ai-chat' ) ) );
			EAIC_Provider::sse_send( 'done',  array( 'session' => '', 'title' => null ) );
			exit;
		}

		list( $user_id, $guest_token ) = $this->get_identity();
		list( $message, $provider, $uuid, $messages, $system_prompt, $is_first_message, $opts )
			= $this->resolve_request( $user_id, $guest_token );

		// Now we're safe — output SSE headers.
		$this->sse_headers();

		// Send session UUID immediately so JS can track the session before
		// the first token arrives.
		EAIC_Provider::sse_send( 'session', array( 'session' => $uuid ) );

		$ai_reply = '';
		try {
			$ai_reply = $this->get_provider( $provider )->stream_chat( $messages, $system_prompt );
		} catch ( Exception $e ) {
			EAIC_Provider::sse_send( 'error', array( 'message' => $e->getMessage() ) );
			EAIC_Provider::sse_send( 'done',  array( 'session' => $uuid, 'title' => null ) );
			exit;
		}

		// Persist to DB (skip entirely in no-storage mode).
		$new_title = null;
		if ( ! $disable_storage ) {
			EAIC_DB::add_message( $uuid, 'user',      $message );
			EAIC_DB::add_message( $uuid, 'assistant', $ai_reply );
			if ( $is_first_message ) {
				$new_title = $this->generate_title( $provider, $message );
				EAIC_DB::update_session_title( $uuid, $new_title );
			}
		}

		// Final event — JS knows streaming is complete.
		EAIC_Provider::sse_send( 'done', array(
			'session'  => $uuid,
			'provider' => $provider,
			'model'    => isset( $opts[ $provider . '_model' ] ) ? $opts[ $provider . '_model' ] : '',
			'title'    => $new_title,
		) );

		// Fire webhook asynchronously (after SSE response).
		$this->fire_webhook( $uuid, $message, $ai_reply, $provider );

		exit;
	}

	/**
	 * Send a POST request to the configured webhook URL.
	 *
	 * @param string $session_uuid Session UUID.
	 * @param string $user_message User message.
	 * @param string $ai_response  AI response.
	 * @param string $provider     Provider slug.
	 * @return void
	 */
	private function fire_webhook( $session_uuid, $user_message, $ai_response, $provider ) {
		$url = EAIC_Options::get( 'webhook_url', '' );
		if ( ! $url ) { return; }

		$payload = wp_json_encode( array(
			'session_uuid' => $session_uuid,
			'user_message' => $user_message,
			'ai_response'  => $ai_response,
			'provider'     => $provider,
			'timestamp'    => gmdate( 'c' ),
			'site_url'     => get_site_url(),
		) );

		$headers = array( 'Content-Type' => 'application/json' );

		$secret = EAIC_Options::get( 'webhook_secret', '' );
		if ( $secret ) {
			$headers['X-EAIC-Signature'] = 'sha256=' . hash_hmac( 'sha256', $payload, $secret );
		}

		wp_remote_post( $url, array(
			'body'      => $payload,
			'headers'   => $headers,
			'timeout'   => 5,
			'blocking'  => false, // fire and forget
		) );
	}

	/**
	 * Send abuse alert email (once per IP per rate-limit window).
	 *
	 * @param array $opts Plugin options.
	 * @return void
	 */
	private function maybe_send_abuse_alert( $opts ) {
		if ( empty( $opts['abuse_alert_enabled'] ) ) { return; }
		$ip         = $this->get_client_ip();
		$alert_key  = 'eaic_abuse_alerted_' . md5( $ip );
		if ( get_transient( $alert_key ) ) { return; } // already sent recently
		set_transient( $alert_key, 1, max( 10, (int) ( $opts['rate_limit_window'] ?? 60 ) ) );
		$to      = ! empty( $opts['abuse_alert_email'] ) ? $opts['abuse_alert_email'] : get_option( 'admin_email' );
		// translators: %s: site name.
		$subject = sprintf( __( '[%s] Chat Rate Limit Exceeded', 'easyit-ai-chat' ), get_bloginfo( 'name' ) );
		// translators: 1: IP address, 2: site URL, 3: date and time.
		$body    = sprintf(
			__( "The IP address %1\$s has exceeded the chat rate limit on %2\$s.\n\nTime: %3\$s\n\nYou may add this IP to the blocklist in EasyIT AI Chat → Settings → Security.", 'easyit-ai-chat' ),
			$ip,
			get_site_url(),
			current_time( 'Y-m-d H:i:s' )
		);
		wp_mail( $to, $subject, $body );
	}

	/**
	 * Generate a signed math captcha token.
	 *
	 * @return array{ question: string, token: string }
	 */
	public static function generate_captcha() {
		$a   = wp_rand( 1, 9 );
		$b   = wp_rand( 1, 9 );
		$ans = $a + $b;
		$exp = time() + 600; // 10 minutes
		$data = implode( '|', array( $a, $b, $ans, $exp ) );
		$sig  = hash_hmac( 'sha256', $data, wp_salt( 'auth' ) );
		return array(
			'question' => "{$a} + {$b}",
			'token'    => $data . '|' . $sig,
		);
	}

	/**
	 * Verify a math captcha token + answer.
	 *
	 * @param string $token  Signed token from generate_captcha().
	 * @param int    $answer User-provided answer.
	 * @return bool
	 */
	private function verify_captcha( $token, $answer ) {
		if ( '' === $token ) { return false; }
		$parts = explode( '|', $token );
		if ( 5 !== count( $parts ) ) { return false; }
		list( $a, $b, $stored_ans, $exp, $sig ) = $parts;
		if ( time() > (int) $exp ) { return false; }
		$expected_sig = hash_hmac( 'sha256', implode( '|', array( $a, $b, $stored_ans, $exp ) ), wp_salt( 'auth' ) );
		if ( ! hash_equals( $expected_sig, $sig ) ) { return false; }
		return ( (int) $answer === (int) $stored_ans );
	}

	/**
	 * Set SSE response headers.
	 */
	private function sse_headers() {
		// Disable output buffering completely.
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		// Prevent WP/PHP caching or compression from buffering our stream.
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', '1' ); // phpcs:ignore
		}
		@ini_set( 'zlib.output_compression', '0' ); // phpcs:ignore
		@ini_set( 'implicit_flush', '1' );           // phpcs:ignore
		ob_implicit_flush( true );

		header( 'Content-Type: text/event-stream; charset=UTF-8' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Content-Encoding: identity' ); // Prevent Apache mod_deflate from buffering the stream.
		header( 'X-Accel-Buffering: no' );      // Nginx: disable proxy buffering.
		header( 'Connection: keep-alive' );

		// Prime the connection: send a padding comment immediately so the browser
		// opens the stream and begins reading before the first real event arrives.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- str_repeat produces only spaces; no user data involved.
		echo ':' . str_repeat( ' ', 4096 ) . "\n\n";
		flush();
	}

	// -----------------------------------------------------------------------
	// Auto-title
	// -----------------------------------------------------------------------

	private function generate_title( $provider_slug, $first_message ) {
		$prompt = sprintf(
			'Create a short 4-6 word title for a chat that starts with: "%s". Reply with the title only — no quotes, no trailing punctuation.',
			mb_substr( $first_message, 0, 200 )
		);

		try {
			$raw   = $this->get_provider( $provider_slug )->chat(
				array( array( 'role' => 'user', 'content' => $prompt ) ),
				''
			);
			$title = trim( wp_strip_all_tags( (string) $raw ) );
			$title = trim( $title, '"\'„"«»' );
			return '' !== $title ? mb_substr( $title, 0, 80 ) : mb_substr( $first_message, 0, 50 );
		} catch ( Exception $e ) {
			return mb_substr( $first_message, 0, 50 );
		}
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function is_valid_uuid( $uuid ) {
		return (bool) preg_match(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
			(string) $uuid
		);
	}

	private function can_access_session( array $session, $user_id, $guest_token ) {
		if ( (int) $user_id > 0 ) {
			return (int) $session['user_id'] === (int) $user_id;
		}
		return '' !== (string) $guest_token && $session['guest_token'] === $guest_token;
	}

	// -----------------------------------------------------------------------
	// Other AJAX endpoints (unchanged)
	// -----------------------------------------------------------------------

	public function ajax_sessions() {
		check_ajax_referer( 'eaic_nonce', 'nonce' );
		list( $user_id, $guest_token ) = $this->get_identity();
		wp_send_json_success( array( 'sessions' => EAIC_DB::get_sessions( $user_id, $guest_token ) ) );
	}

	public function ajax_new_session() {
		check_ajax_referer( 'eaic_nonce', 'nonce' );
		list( $user_id, $guest_token ) = $this->get_identity();

		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : 'ollama';

		$opts              = EAIC_Options::all();
		$allowed_providers = isset( $opts['allowed_providers'] ) && is_array( $opts['allowed_providers'] )
			? $opts['allowed_providers']
			: array( 'ollama', 'openai', 'anthropic', 'deepseek', 'gemini' );
		$allowed_providers = array_merge( $allowed_providers, $this->custom_provider_slugs( $opts ) );
		if ( ! in_array( $provider, $allowed_providers, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Provider not allowed.', 'easyit-ai-chat' ) ), 400 );
		}

		$uuid = EAIC_DB::create_session( $user_id, $guest_token, $provider, __( 'New Chat', 'easyit-ai-chat' ) );
		wp_send_json_success( array( 'session' => $uuid ) );
	}

	public function ajax_history() {
		check_ajax_referer( 'eaic_nonce', 'nonce' );
		list( $user_id, $guest_token ) = $this->get_identity();

		$uuid = isset( $_POST['session'] ) ? sanitize_text_field( wp_unslash( $_POST['session'] ) ) : '';
		if ( ! $this->is_valid_uuid( $uuid ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid session.', 'easyit-ai-chat' ) ), 400 );
		}

		$session = EAIC_DB::get_session( $uuid );
		if ( ! $session || ! $this->can_access_session( $session, $user_id, $guest_token ) ) {
			wp_send_json_error( array( 'message' => __( 'Session not found.', 'easyit-ai-chat' ) ), 404 );
		}

		wp_send_json_success( array(
			'messages' => EAIC_DB::get_messages( $uuid ),
			'provider' => $session['provider'],
			'title'    => $session['title'],
		) );
	}

	public function ajax_delete() {
		check_ajax_referer( 'eaic_nonce', 'nonce' );
		list( $user_id, $guest_token ) = $this->get_identity();

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

	public function ajax_health() {
		check_ajax_referer( 'eaic_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorised.', 'easyit-ai-chat' ) ), 403 );
		}

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

	/**
	 * Test a custom provider using form values (before saving).
	 *
	 * @return void
	 */
	public function ajax_test_custom_provider() {
		check_ajax_referer( 'eaic_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorised.', 'easyit-ai-chat' ) ), 403 );
		}

		$url   = isset( $_POST['url'] )     ? esc_url_raw( wp_unslash( $_POST['url'] ) )             : '';
		$key   = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
		$model = isset( $_POST['model'] )   ? sanitize_text_field( wp_unslash( $_POST['model'] ) )   : '';

		if ( empty( $url ) ) {
			wp_send_json_error( array( 'message' => '❌ ' . __( 'URL is required.', 'easyit-ai-chat' ) ) );
		}

		$provider = new EAIC_Custom( array(
			'url'     => $url,
			'api_key' => $key,
			'model'   => $model,
			'timeout' => 15,
			'enabled' => true,
		) );

		try {
			$ok = $provider->health();
			if ( $ok ) {
				wp_send_json_success( array( 'message' => '✅ ' . __( 'Connected successfully!', 'easyit-ai-chat' ) ) );
			} else {
				wp_send_json_error( array( 'message' => '❌ ' . __( 'Connection failed. Check URL and API key.', 'easyit-ai-chat' ) ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => '❌ ' . $e->getMessage() ) );
		}
	}

	public function ajax_feedback() {
		check_ajax_referer( 'eaic_nonce', 'nonce' );

		$session_uuid = isset( $_POST['session'] )   ? sanitize_text_field( wp_unslash( $_POST['session'] ) ) : '';
		$msg_index    = isset( $_POST['msg_index'] ) ? absint( wp_unslash( $_POST['msg_index'] ) ) : 0;
		$rating_raw   = isset( $_POST['rating'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['rating'] ) ) : 1;
		$rating       = $rating_raw >= 0 ? 1 : -1;

		if ( ! $session_uuid ) {
			wp_send_json_error( array( 'message' => 'Missing session.' ), 400 );
		}

		// Verify ownership.
		list( $fb_user_id, $fb_guest ) = $this->get_identity();
		$session  = EAIC_DB::get_session( $session_uuid );
		if ( ! $session ) {
			wp_send_json_error( array( 'message' => 'Session not found.' ), 404 );
		}
		$owned = ( $fb_user_id > 0 && (int) $session['user_id'] === $fb_user_id )
		      || ( '' !== $fb_guest && $session['guest_token'] === $fb_guest );
		if ( ! $owned ) {
			wp_send_json_error( array( 'message' => 'Not authorised.' ), 403 );
		}

		EAIC_DB::save_feedback( $session_uuid, $msg_index, $rating );
		wp_send_json_success( array( 'rating' => $rating ) );
	}
}
