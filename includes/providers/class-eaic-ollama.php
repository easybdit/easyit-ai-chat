<?php
/**
 * Ollama provider implementation.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ollama (self-hosted) provider.
 */
class EAIC_Ollama extends EAIC_Provider {

	/**
	 * Send a chat request.
	 *
	 * @param array  $messages Messages.
	 * @param string $system   System prompt.
	 * @return string
	 */
	public function chat( array $messages, $system = '' ) {
		$base    = isset( $this->opts['ollama_url'] ) ? rtrim( (string) $this->opts['ollama_url'], '/' ) : '';
		$url     = $base . '/api/chat';
		$model   = ! empty( $this->opts['ollama_model'] ) ? $this->opts['ollama_model'] : 'qwen2:1.5b';
		$timeout = isset( $this->opts['ollama_timeout'] ) ? (int) $this->opts['ollama_timeout'] : 60;

		$payload_messages = array();
		if ( '' !== $system ) {
			$payload_messages[] = array( 'role' => 'system', 'content' => $system );
		}
		foreach ( $messages as $m ) {
			$payload_messages[] = array( 'role' => $m['role'], 'content' => $m['content'] );
		}

		$data = $this->http_post(
			$url,
			array(
				'model'    => $model,
				'messages' => $payload_messages,
				'stream'   => false,
			),
			array(),
			$timeout
		);

		return isset( $data['message']['content'] ) ? (string) $data['message']['content'] : '';
	}

	/**
	 * Connectivity check.
	 *
	 * @return bool
	 */
	public function health() {
		try {
			$base = isset( $this->opts['ollama_url'] ) ? rtrim( (string) $this->opts['ollama_url'], '/' ) : '';
			$url  = $base . '/api/tags';
			$data = $this->http_get( $url );
			return isset( $data['models'] );
		} catch ( Exception $e ) {
			return false;
		}
	}
}
