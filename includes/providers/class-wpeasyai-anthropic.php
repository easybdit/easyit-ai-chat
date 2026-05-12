<?php
/**
 * Anthropic (Claude) provider implementation.
 *
 * @package WPEasyAI
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEasyAI_Anthropic extends WPEasyAI_Provider {

	public function chat( array $messages, string $system = '' ): string {
		$key     = $this->opts['anthropic_key'] ?? '';
		$this->require_api_key( $key, 'Anthropic' );
		$model   = $this->opts['anthropic_model'] ?: 'claude-3-haiku-20240307';
		$timeout = (int) ( $this->opts['anthropic_timeout'] ?? 30 );

		$payload_messages = [];
		foreach ( $messages as $m ) {
			if ( in_array( $m['role'], [ 'user', 'assistant' ], true ) ) {
				$payload_messages[] = [ 'role' => $m['role'], 'content' => $m['content'] ];
			}
		}

		$body = [
			'model'       => $model,
			'max_tokens'  => (int) ( $this->opts['max_tokens'] ?? 1000 ),
			'temperature' => (float) ( $this->opts['temperature'] ?? 0.7 ),
			'messages'    => $payload_messages,
		];
		if ( ! empty( $system ) ) {
			$body['system'] = $system;
		}

		$data = $this->http_post(
			'https://api.anthropic.com/v1/messages',
			$body,
			[
				'x-api-key'         => $key,
				'anthropic-version' => '2023-06-01',
			],
			$timeout
		);

		return $data['content'][0]['text'] ?? '';
	}

	public function health(): bool {
		try {
			$this->chat( [ [ 'role' => 'user', 'content' => 'Hi' ] ], '' );
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}
}
