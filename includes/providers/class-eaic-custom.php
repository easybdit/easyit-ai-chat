<?php
/**
 * Custom (OpenAI-compatible) provider implementation.
 *
 * @package EasyIT_AI_Chat
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles any OpenAI-compatible API endpoint configured by the admin.
 */
class EAIC_Custom extends EAIC_Provider {

	/** @var array{name:string,url:string,api_key:string,model:string,enabled:bool,timeout:int} */
	private $config;

	/**
	 * @param array $config Provider config row from eaic_options['custom_providers'].
	 */
	public function __construct( array $config ) {
		parent::__construct( array() );
		$this->config = $config;
	}

	/**
	 * Send a chat request to the custom endpoint.
	 *
	 * @param array  $messages Messages array.
	 * @param string $system   System prompt.
	 * @return string
	 * @throws Exception On missing URL or API error.
	 */
	public function chat( array $messages, $system = '' ) {
		$url     = rtrim( isset( $this->config['url'] ) ? $this->config['url'] : '', '/' );
		$key     = isset( $this->config['api_key'] ) ? $this->config['api_key'] : '';
		$model   = isset( $this->config['model'] )   ? $this->config['model']   : '';
		$timeout = isset( $this->config['timeout'] ) ? (int) $this->config['timeout'] : 30;

		if ( empty( $url ) ) {
			throw new Exception( 'Custom provider URL is not configured.' );
		}

		$payload_messages = array();
		if ( '' !== $system ) {
			$payload_messages[] = array( 'role' => 'system', 'content' => $system );
		}
		foreach ( $messages as $m ) {
			$payload_messages[] = array( 'role' => $m['role'], 'content' => $m['content'] );
		}

		$body = array(
			'model'       => $model,
			'messages'    => $payload_messages,
			'max_tokens'  => 1000,
			'temperature' => 0.7,
		);

		$data = $this->http_post(
			$url . '/chat/completions',
			$body,
			array( 'Authorization' => 'Bearer ' . $key ),
			$timeout
		);

		return isset( $data['choices'][0]['message']['content'] )
			? (string) $data['choices'][0]['message']['content']
			: '';
	}

	/**
	 * Connectivity check — tries /ping first, then falls back to /models.
	 *
	 * @return bool
	 */
	public function health() {
		$url = rtrim( isset( $this->config['url'] ) ? $this->config['url'] : '', '/' );
		$key = isset( $this->config['api_key'] ) ? $this->config['api_key'] : '';

		if ( empty( $url ) ) {
			return false;
		}

		// Try /ping first (EasyAI fast health check).
		try {
			$data = $this->http_get(
				$url . '/ping',
				array( 'Authorization' => 'Bearer ' . $key ),
				5
			);
			if ( isset( $data['status'] ) && 'ok' === $data['status'] ) {
				return true;
			}
		} catch ( Exception $e ) {
			// fall through to /models
		}

		// Fallback: try /models.
		try {
			$data = $this->http_get(
				$url . '/models',
				array( 'Authorization' => 'Bearer ' . $key ),
				5
			);
			return ! empty( $data );
		} catch ( Exception $e ) {
			return false;
		}
	}
}
