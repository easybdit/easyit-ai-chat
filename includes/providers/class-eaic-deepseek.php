<?php
/**
 * DeepSeek provider implementation.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DeepSeek (OpenAI-compatible) provider.
 */
class EAIC_DeepSeek extends EAIC_Provider {

	/**
	 * Send a chat request.
	 *
	 * @param array  $messages Messages.
	 * @param string $system   System prompt.
	 * @return string
	 */
	public function chat( array $messages, $system = '' ) {
		$key     = isset( $this->opts['deepseek_key'] ) ? $this->opts['deepseek_key'] : '';
		$this->require_api_key( $key, 'DeepSeek' );
		$model   = ! empty( $this->opts['deepseek_model'] ) ? $this->opts['deepseek_model'] : 'deepseek-chat';
		$timeout = isset( $this->opts['deepseek_timeout'] ) ? (int) $this->opts['deepseek_timeout'] : 30;

		$payload_messages = array();
		if ( '' !== $system ) {
			$payload_messages[] = array( 'role' => 'system', 'content' => $system );
		}
		foreach ( $messages as $m ) {
			$payload_messages[] = array( 'role' => $m['role'], 'content' => $m['content'] );
		}

		$data = $this->http_post(
			'https://api.deepseek.com/v1/chat/completions',
			array(
				'model'       => $model,
				'messages'    => $payload_messages,
				'max_tokens'  => isset( $this->opts['max_tokens'] ) ? (int) $this->opts['max_tokens'] : 1000,
				'temperature' => isset( $this->opts['temperature'] ) ? (float) $this->opts['temperature'] : 0.7,
			),
			array( 'Authorization' => 'Bearer ' . $key ),
			$timeout
		);

		return isset( $data['choices'][0]['message']['content'] ) ? (string) $data['choices'][0]['message']['content'] : '';
	}

	/**
	 * Connectivity check.
	 *
	 * @return bool
	 */
	public function health() {
		try {
			$key = isset( $this->opts['deepseek_key'] ) ? $this->opts['deepseek_key'] : '';
			$this->require_api_key( $key, 'DeepSeek' );
			$data = $this->http_get(
				'https://api.deepseek.com/v1/models',
				array( 'Authorization' => 'Bearer ' . $key )
			);
			return ! empty( $data['data'] );
		} catch ( Exception $e ) {
			return false;
		}
	}
}
