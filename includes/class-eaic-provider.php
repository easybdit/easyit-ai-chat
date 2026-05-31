<?php
/**
 * Abstract AI provider base class.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class implemented by all AI provider adapters.
 */
abstract class EAIC_Provider {

	/** @var array<string,mixed> */
	protected $opts;

	public function __construct( array $opts ) {
		$this->opts = $opts;
	}

	/**
	 * Send messages and return full reply string (non-streaming).
	 *
	 * @param array  $messages Conversation history.
	 * @param string $system   Optional system prompt.
	 * @return string
	 */
	abstract public function chat( array $messages, $system = '' );

	/**
	 * Stream response — echo SSE chunks then return full text.
	 * Default falls back to non-streaming for providers that don't override.
	 *
	 * @param array  $messages Conversation history.
	 * @param string $system   Optional system prompt.
	 * @return string Full assembled reply.
	 */
	public function stream_chat( array $messages, $system = '' ) {
		// Default: get full reply then emit as single chunk.
		$reply = $this->chat( $messages, $system );
		self::sse_send( 'chunk', array( 'text' => $reply ) );
		return $reply;
	}

	/**
	 * Health-check: returns true if provider responds.
	 *
	 * @return bool
	 */
	abstract public function health();

	/**
	 * Emit a single SSE event.
	 *
	 * @param string $event Event name.
	 * @param array  $data  Data to JSON-encode.
	 */
	public static function sse_send( $event, array $data ) {
		// 2 KB padding pushes past Apache/Nginx send-buffer thresholds so each
		// event is delivered immediately instead of waiting for the buffer to fill.
		// SSE comment lines (starting with ':') are valid per spec and ignored by browsers.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- str_repeat produces only spaces; no user data involved.
		echo ':' . str_repeat( ' ', 2048 ) . "\n";
		echo 'event: ' . esc_html( $event ) . "\n";
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo 'data: ' . wp_json_encode( $data ) . "\n\n";
		if ( ob_get_level() > 0 ) {
			ob_flush();
		}
		flush();
	}

	/**
	 * Ensure an API key is present, otherwise throw.
	 *
	 * @param string $key      The key value.
	 * @param string $provider Provider label.
	 * @return void
	 * @throws RuntimeException When the key is empty.
	 */
	protected function require_api_key( $key, $provider ) {
		if ( '' === trim( (string) $key ) ) {
			$message = sprintf(
				/* translators: %s: AI provider name e.g. "OpenAI" */
				__( 'No API key configured for %s. Please add one in EasyIT AI Chat > Settings.', 'easyit-ai-chat' ),
				$provider
			);
			throw new RuntimeException( esc_html( $message ) );
		}
	}

	/**
	 * Validate a URL string and its scheme.
	 *
	 * @param string $url The URL to validate.
	 * @return void
	 * @throws RuntimeException When the URL is invalid.
	 */
	protected function validate_url( $url ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$message = sprintf(
				/* translators: %s: the invalid URL string */
				__( 'Invalid URL: %s', 'easyit-ai-chat' ),
				$url
			);
			throw new RuntimeException( esc_html( $message ) );
		}
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			throw new RuntimeException(
				esc_html__( 'Invalid URL scheme. Only http and https are allowed.', 'easyit-ai-chat' )
			);
		}
	}

	/**
	 * Perform a JSON POST request (blocking, returns full response).
	 *
	 * @param string $url     Endpoint URL.
	 * @param array  $body    Request body.
	 * @param array  $headers Extra headers.
	 * @param int    $timeout Timeout in seconds.
	 * @return array
	 * @throws RuntimeException On transport or HTTP errors.
	 */
	protected function http_post( $url, array $body, array $headers = array(), $timeout = 30 ) {
		$this->validate_url( $url );

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array_merge( array( 'Content-Type' => 'application/json' ), $headers ),
				'body'    => wp_json_encode( $body ),
				'timeout' => (int) $timeout,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( esc_html( $response->get_error_message() ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code >= 400 ) {
			$msg = '';
			if ( is_array( $data ) ) {
				if ( isset( $data['error']['message'] ) ) {
					$msg = (string) $data['error']['message'];
				} elseif ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
					$msg = $data['error'];
				}
			}
			if ( '' === $msg ) {
				/* translators: %d: HTTP status code */
				$msg = sprintf( __( 'HTTP error %d', 'easyit-ai-chat' ), (int) $code );
			}
			throw new RuntimeException( esc_html( $msg ) );
		}

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Stream an HTTP POST line by line, calling $callback for each line.
	 * Used by providers that support SSE/NDJSON streaming.
	 *
	 * @param string   $url      Endpoint URL.
	 * @param array    $body     Request body (stream:true already set by caller).
	 * @param array    $headers  Extra headers.
	 * @param int      $timeout  Timeout in seconds.
	 * @param callable $callback Called with each raw response line.
	 * @return void
	 * @throws RuntimeException On transport errors.
	 */
	protected function http_stream( $url, array $body, array $headers, $timeout, callable $callback ) {
		$this->validate_url( $url );

		if ( ! function_exists( 'curl_init' ) ) {
			throw new RuntimeException( esc_html__( 'cURL extension is required for streaming.', 'easyit-ai-chat' ) );
		}

		$curl_headers = array( 'Content-Type: application/json' );
		foreach ( $headers as $k => $v ) {
			$curl_headers[] = $k . ': ' . $v;
		}

		$buffer      = '';
		$http_status = 200;
		$error_body  = '';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init -- wp_remote_post() buffers the full response and cannot support real-time SSE streaming.
		$ch = curl_init();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt_array
		curl_setopt_array( $ch, array(
			CURLOPT_URL            => $url,
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => wp_json_encode( $body ),
			CURLOPT_HTTPHEADER     => $curl_headers,
			CURLOPT_TIMEOUT        => (int) $timeout,
			CURLOPT_RETURNTRANSFER => false,
			CURLOPT_HEADERFUNCTION => function ( $ch, $header ) use ( &$http_status ) {
				if ( preg_match( '/^HTTP\/\S+\s+(\d+)/', $header, $m ) ) {
					$http_status = (int) $m[1];
				}
				return strlen( $header );
			},
			CURLOPT_WRITEFUNCTION  => function ( $ch, $data ) use ( $callback, &$buffer, &$http_status, &$error_body ) {
				if ( $http_status >= 400 ) {
					$error_body .= $data;
					return strlen( $data );
				}
				$buffer .= $data;
				while ( false !== ( $pos = strpos( $buffer, "\n" ) ) ) {
					$line   = rtrim( substr( $buffer, 0, $pos ), "\r" );
					$buffer = substr( $buffer, $pos + 1 );
					if ( '' !== $line ) {
						$callback( $line );
						if ( ob_get_level() > 0 ) {
							ob_flush();
						}
						flush();
					}
				}
				return strlen( $data );
			},
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
		) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec
		curl_exec( $ch );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_errno
		$errno = curl_errno( $ch );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_error
		$error = curl_error( $ch );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
		curl_close( $ch );

		if ( $errno ) {
			throw new RuntimeException( esc_html( $error ) );
		}

		if ( $http_status >= 400 ) {
			$err_data = json_decode( $error_body, true );
			$msg      = is_array( $err_data ) && isset( $err_data['error']['message'] )
				? $err_data['error']['message']
				/* translators: %d: HTTP status code number */
				: sprintf( __( 'HTTP error %d', 'easyit-ai-chat' ), $http_status );
			throw new RuntimeException( esc_html( $msg ) );
		}
	}

	/**
	 * Perform a JSON GET request.
	 *
	 * @param string $url     Endpoint URL.
	 * @param array  $headers Extra headers.
	 * @param int    $timeout Timeout in seconds.
	 * @return array
	 * @throws RuntimeException On transport errors.
	 */
	protected function http_get( $url, array $headers = array(), $timeout = 15 ) {
		$this->validate_url( $url );

		$response = wp_remote_get(
			$url,
			array(
				'headers' => $headers,
				'timeout' => (int) $timeout,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( esc_html( $response->get_error_message() ) );
		}

		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : array();
	}
}
