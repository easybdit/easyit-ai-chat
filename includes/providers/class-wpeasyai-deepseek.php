<?php
/**
 * DeepSeek provider implementation.
 *
 * @package WPEasyAI
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEasyAI_DeepSeek extends WPEasyAI_Provider {

	public function chat( array $messages, string $system = '' ): string {
		$key     = $this->opts['deepseek_key'] ?? '';
		$this->require_api_key( $key, 'DeepSeek' );
		$model   = $this->opts['deepseek_model'] ?: 'deepseek-chat';
		$timeout = (int) ( $this->opts['deepseek_timeout'] ?? 30 );

		$payload_messages = [];
		if ( ! empty( $system ) ) {
			$payload_messages[] = [ 'role' => 'system', 'content' => $system ];
		}
		foreach ( $messages as $m ) {
			$payload_messages[] = [ 'role' => $m['role'], 'content' => $m['content'] ];
		}

		$data = $this->http_post(
			'https://api.deepseek.com/v1/chat/completions',
			[
				'model'       => $model,
				'messages'    => $payload_messages,
				'max_tokens'  => (int) ( $this->opts['max_tokens'] ?? 1000 ),
				'temperature' => (float) ( $this->opts['temperature'] ?? 0.7 ),
			],
			[ 'Authorization' => 'Bearer ' . $key ],
			$timeout
		);

		return $data['choices'][0]['message']['content'] ?? '';
	}

	public function health(): bool {
		try {
			$key = $this->opts['deepseek_key'] ?? '';
			$this->require_api_key( $key, 'DeepSeek' );
			$data = $this->http_get( 'https://api.deepseek.com/v1/models', [ 'Authorization' => 'Bearer ' . $key ] );
			return ! empty( $data['data'] );
		} catch ( Exception $e ) {
			return false;
		}
	}
}
