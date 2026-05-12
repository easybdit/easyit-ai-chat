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

	/**
	 * Plugin options snapshot.
	 *
	 * @var array<string,mixed>
	 */
	protected $opts;

	/**
	 * Constructor.
	 *
	 * @param array $opts Plugin options.
	 */
	public function __construct( array $opts ) {
		$this->opts = $opts;
	}

	/**
	 * Send messages to provider and return reply string.
	 *
	 * @param array  $messages Conversation history.
	 * @param string $system   Optional system prompt.
	 * @return string
	 */
	abstract public function chat( array $messages, $system = '' );

	/**
	 * Health-check: returns true if provider responds.
	 *
	 * @return bool
	 */
	abstract public function health();

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
	 * Perform a JSON POST request.
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
