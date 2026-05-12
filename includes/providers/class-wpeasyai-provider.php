<?php
/**
 * Abstract AI provider base class.
 *
 * @package WPEasyAI
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

abstract class WPEasyAI_Provider {

	protected array $opts;

	public function __construct( array $opts ) {
		$this->opts = $opts;
	}

	abstract public function chat( array $messages, string $system = '' ): string;
	abstract public function health(): bool;

	protected function require_api_key( string $key, string $provider ): void {
		if ( empty( trim( $key ) ) ) {
			throw new RuntimeException(
				esc_html(
					sprintf(
						/* translators: %s: AI provider name e.g. "OpenAI" */
						__( 'No API key configured for %s. Please add one in EasyIT AI Chat > Settings.', 'wpeasyai' ),
						$provider
					)
				)
			);
		}
	}

	protected function validate_url( string $url ): void {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			throw new RuntimeException(
				esc_html(
					sprintf(
						/* translators: %s: the invalid URL string */
						__( 'Invalid URL: %s', 'wpeasyai' ),
						$url
					)
				)
			);
		}
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			throw new RuntimeException(
				esc_html__( 'Invalid URL scheme. Only http and https are allowed.', 'wpeasyai' )
			);
		}
	}

	protected function http_post( string $url, array $body, array $headers = [], int $timeout = 30 ): array {
		$this->validate_url( $url );
		$response = wp_remote_post( $url, [
			'headers' => array_merge( [ 'Content-Type' => 'application/json' ], $headers ),
			'body'    => wp_json_encode( $body ),
			'timeout' => $timeout,
		] );
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( esc_html( $response->get_error_message() ) );
		}
		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );
		if ( $code >= 400 ) {
			$msg = $data['error']['message'] ?? $data['error'] ?? "HTTP $code";
			throw new RuntimeException( esc_html( (string) $msg ) );
		}
		return is_array( $data ) ? $data : [];
	}

	protected function http_get( string $url, array $headers = [], int $timeout = 15 ): array {
		$this->validate_url( $url );
		$response = wp_remote_get( $url, [
			'headers' => $headers,
			'timeout' => $timeout,
		] );
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( esc_html( $response->get_error_message() ) );
		}
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : [];
	}
}