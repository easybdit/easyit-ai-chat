<?php
/**
 * Anthropic Messages API provider implementation.
 *
 * This plugin is an independent client of the Anthropic Messages API.
 * It is not affiliated with, endorsed by, or sponsored by Anthropic, PBC.
 * "Anthropic" and "Claude" are trademarks of their respective owners.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Anthropic Messages API connector.
 */
class EAIC_Anthropic extends EAIC_Provider {

	/**
	 * Send a chat request.
	 *
	 * @param array  $messages Messages.
	 * @param string $system   System prompt.
	 * @return string
	 */
	public function chat( array $messages, $system = '' ) {
		$key     = isset( $this->opts['anthropic_key'] ) ? $this->opts['anthropic_key'] : '';
		$this->require_api_key( $key, 'Anthropic' );
		$model   = ! empty( $this->opts['anthropic_model'] ) ? $this->opts['anthropic_model'] : 'claude-3-5-haiku-20241022';
		$timeout = isset( $this->opts['anthropic_timeout'] ) ? (int) $this->opts['anthropic_timeout'] : 30;

		// Anthropic only accepts user/assistant roles in the messages array.
		$payload_messages = array();
		foreach ( $messages as $m ) {
			if ( in_array( $m['role'], array( 'user', 'assistant' ), true ) ) {
				$payload_messages[] = array( 'role' => $m['role'], 'content' => $m['content'] );
			}
		}

		$body = array(
			'model'       => $model,
			'max_tokens'  => isset( $this->opts['max_tokens'] ) ? (int) $this->opts['max_tokens'] : 1000,
			'temperature' => isset( $this->opts['temperature'] ) ? (float) $this->opts['temperature'] : 0.7,
			'messages'    => $payload_messages,
		);

		if ( '' !== $system ) {
			$body['system'] = $system;
		}

		$data = $this->http_post(
			'https://api.anthropic.com/v1/messages',
			$body,
			array(
				'x-api-key'         => $key,
				'anthropic-version' => '2023-06-01',
			),
			$timeout
		);

		return isset( $data['content'][0]['text'] ) ? (string) $data['content'][0]['text'] : '';
	}

	/**
	 * Connectivity check.
	 *
	 * Uses a 1-token request to minimise API cost. Anthropic does not expose
	 * a free "list models" endpoint, so we cannot validate the key without
	 * billing the user something. One token is the cheapest probe possible.
	 *
	 * @return bool
	 */
	public function health() {
		try {
			$key = isset( $this->opts['anthropic_key'] ) ? $this->opts['anthropic_key'] : '';
			$this->require_api_key( $key, 'Anthropic' );
			$model = ! empty( $this->opts['anthropic_model'] ) ? $this->opts['anthropic_model'] : 'claude-3-5-haiku-20241022';

			$this->http_post(
				'https://api.anthropic.com/v1/messages',
				array(
					'model'      => $model,
					'max_tokens' => 1,
					'messages'   => array( array( 'role' => 'user', 'content' => 'Hi' ) ),
				),
				array(
					'x-api-key'         => $key,
					'anthropic-version' => '2023-06-01',
				),
				15
			);
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}
}
