<?php
/**
 * Anthropic Messages API provider implementation.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAIC_Anthropic extends EAIC_Provider {

	public function chat( array $messages, $system = '' ) {
		$key     = isset( $this->opts['anthropic_key'] ) ? $this->opts['anthropic_key'] : '';
		$this->require_api_key( $key, 'Anthropic' );
		$model   = ! empty( $this->opts['anthropic_model'] ) ? $this->opts['anthropic_model'] : 'claude-3-haiku-20240307';
		$timeout = isset( $this->opts['anthropic_timeout'] ) ? (int) $this->opts['anthropic_timeout'] : 30;

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
	 * Anthropic SSE streaming.
	 * Lines: "event: content_block_delta" + "data: {...delta...}"
	 */
	public function stream_chat( array $messages, $system = '' ) {
		$key     = isset( $this->opts['anthropic_key'] ) ? $this->opts['anthropic_key'] : '';
		$this->require_api_key( $key, 'Anthropic' );
		$model   = ! empty( $this->opts['anthropic_model'] ) ? $this->opts['anthropic_model'] : 'claude-3-haiku-20240307';
		$timeout = isset( $this->opts['anthropic_timeout'] ) ? (int) $this->opts['anthropic_timeout'] : 30;

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
			'stream'      => true,
		);
		if ( '' !== $system ) {
			$body['system'] = $system;
		}

		$full_reply = '';

		$this->http_stream(
			'https://api.anthropic.com/v1/messages',
			$body,
			array(
				'x-api-key'         => $key,
				'anthropic-version' => '2023-06-01',
			),
			$timeout,
			function ( $line ) use ( &$full_reply ) {
				if ( 0 !== strpos( $line, 'data: ' ) ) {
					return;
				}
				$json  = json_decode( substr( $line, 6 ), true );
				$token = isset( $json['delta']['text'] ) ? (string) $json['delta']['text'] : '';
				if ( '' !== $token ) {
					$full_reply .= $token;
					self::sse_send( 'chunk', array( 'text' => $token ) );
				}
			}
		);

		return $full_reply;
	}

	public function health() {
		try {
			$key   = isset( $this->opts['anthropic_key'] ) ? $this->opts['anthropic_key'] : '';
			$this->require_api_key( $key, 'Anthropic' );
			$model = ! empty( $this->opts['anthropic_model'] ) ? $this->opts['anthropic_model'] : 'claude-3-haiku-20240307';

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
