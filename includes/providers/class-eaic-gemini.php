<?php
/**
 * Google Gemini provider implementation.
 *
 * Uses the Gemini generateContent REST API (v1beta).
 * The API key is passed as a query parameter; the request body
 * uses the Gemini content/parts schema rather than the OpenAI
 * messages schema.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Google Gemini provider.
 */
class EAIC_Gemini extends EAIC_Provider {

	const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

	/**
	 * Send a chat request.
	 *
	 * @param array  $messages Conversation history (OpenAI-style role/content pairs).
	 * @param string $system   System prompt.
	 * @return string
	 */
	public function chat( array $messages, $system = '' ) {
		$key     = isset( $this->opts['gemini_key'] )     ? $this->opts['gemini_key']     : '';
		$model   = ! empty( $this->opts['gemini_model'] ) ? $this->opts['gemini_model']   : 'gemini-1.5-flash';
		$timeout = isset( $this->opts['gemini_timeout'] ) ? (int) $this->opts['gemini_timeout'] : 30;
		$this->require_api_key( $key, 'Gemini' );

		// Convert OpenAI-style messages to Gemini contents format.
		// Gemini uses "model" for the assistant role.
		$contents = array();
		foreach ( $messages as $m ) {
			$role       = 'assistant' === $m['role'] ? 'model' : 'user';
			$contents[] = array(
				'role'  => $role,
				'parts' => array( array( 'text' => (string) $m['content'] ) ),
			);
		}

		$body = array(
			'contents'         => $contents,
			'generationConfig' => array(
				'temperature'     => isset( $this->opts['temperature'] ) ? (float) $this->opts['temperature'] : 0.7,
				'maxOutputTokens' => isset( $this->opts['max_tokens'] )  ? (int) $this->opts['max_tokens']   : 1000,
			),
		);

		if ( '' !== $system ) {
			$body['system_instruction'] = array(
				'parts' => array( array( 'text' => $system ) ),
			);
		}

		$url  = self::BASE_URL . rawurlencode( $model ) . ':generateContent?key=' . rawurlencode( $key );
		$data = $this->gemini_post( $url, $body, $timeout );

		return isset( $data['candidates'][0]['content']['parts'][0]['text'] )
			? (string) $data['candidates'][0]['content']['parts'][0]['text']
			: '';
	}

	/**
	 * Connectivity check — list available models.
	 *
	 * @return bool
	 */
	public function health() {
		try {
			$key = isset( $this->opts['gemini_key'] ) ? $this->opts['gemini_key'] : '';
			$this->require_api_key( $key, 'Gemini' );
			$url  = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . rawurlencode( $key );
			$data = $this->gemini_get( $url );
			return ! empty( $data['models'] );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Perform a POST request to a Gemini endpoint.
	 *
	 * Gemini URLs embed the API key as a query parameter and use colons in
	 * path segments (e.g. ":generateContent"), which can confuse PHP's
	 * filter_var FILTER_VALIDATE_URL. We therefore bypass the base-class URL
	 * validator and do a lightweight scheme-only check instead.
	 *
	 * @param string $url     Full Gemini endpoint URL (key already appended).
	 * @param array  $body    Request body.
	 * @param int    $timeout Timeout in seconds.
	 * @return array Decoded JSON response.
	 * @throws RuntimeException On transport or HTTP errors.
	 */
	private function gemini_post( $url, array $body, $timeout ) {
		if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			throw new RuntimeException( esc_html__( 'Invalid Gemini endpoint URL.', 'easyit-ai-chat' ) );
		}

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
				'timeout' => (int) $timeout,
			)
		);

		return $this->parse_gemini_response( $response );
	}

	/**
	 * Perform a GET request to a Gemini endpoint.
	 *
	 * @param string $url Full endpoint URL.
	 * @return array
	 * @throws RuntimeException On transport or HTTP errors.
	 */
	private function gemini_get( $url ) {
		if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			throw new RuntimeException( esc_html__( 'Invalid Gemini endpoint URL.', 'easyit-ai-chat' ) );
		}

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
		return $this->parse_gemini_response( $response );
	}

	/**
	 * Parse a WP_HTTP response from the Gemini API.
	 *
	 * @param array|\WP_Error $response WP HTTP response.
	 * @return array
	 * @throws RuntimeException On WP_Error or HTTP >= 400.
	 */
	private function parse_gemini_response( $response ) {
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( esc_html( $response->get_error_message() ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code >= 400 ) {
			$msg = is_array( $data ) && isset( $data['error']['message'] )
				? (string) $data['error']['message']
				/* translators: %d: HTTP status code */
				: sprintf( __( 'HTTP error %d', 'easyit-ai-chat' ), (int) $code );
			throw new RuntimeException( esc_html( $msg ) );
		}

		return is_array( $data ) ? $data : array();
	}
}
