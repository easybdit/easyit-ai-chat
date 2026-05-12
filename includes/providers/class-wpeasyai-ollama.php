<?php
/**
 * Ollama provider implementation.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEasyAI_Ollama extends WPEasyAI_Provider {

	public function chat( array $messages, string $system = '' ): string {
		$url     = rtrim( $this->opts['ollama_url'], '/' ) . '/api/chat';
		$model   = $this->opts['ollama_model'] ?: 'qwen2:1.5b';
		$timeout = (int) ( $this->opts['ollama_timeout'] ?? 60 );

		$payload_messages = [];
		if ( ! empty( $system ) ) {
			$payload_messages[] = [ 'role' => 'system', 'content' => $system ];
		}
		foreach ( $messages as $m ) {
			$payload_messages[] = [ 'role' => $m['role'], 'content' => $m['content'] ];
		}

		$data = $this->http_post( $url, [
			'model'    => $model,
			'messages' => $payload_messages,
			'stream'   => false,
		], [], $timeout );

		return $data['message']['content'] ?? '';
	}

	public function health(): bool {
		try {
			$url  = rtrim( $this->opts['ollama_url'], '/' ) . '/api/tags';
			$data = $this->http_get( $url );
			return isset( $data['models'] );
		} catch ( Exception $e ) {
			return false;
		}
	}
}
