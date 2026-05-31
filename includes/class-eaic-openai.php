<?php
/**
 * OpenAI provider implementation.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAIC_OpenAI extends EAIC_Provider {

	public function chat( array $messages, $system = '' ) {
		$key     = isset( $this->opts['openai_key'] ) ? $this->opts['openai_key'] : '';
		$this->require_api_key( $key, 'OpenAI' );
		$model   = ! empty( $this->opts['openai_model'] ) ? $this->opts['openai_model'] : 'gpt-3.5-turbo';
		$timeout = isset( $this->opts['openai_timeout'] ) ? (int) $this->opts['openai_timeout'] : 30;

		$payload_messages = array();
		if ( '' !== $system ) {
			$payload_messages[] = array( 'role' => 'system', 'content' => $system );
		}
		foreach ( $messages as $m ) {
			$payload_messages[] = array( 'role' => $m['role'], 'content' => $m['content'] );
		}

		$data = $this->http_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'model'       => $model,
				'messages'    => $payload_messages,
				'max_tokens'  => isset( $this->opts['max_tokens'] ) ? (int) $this->opts['max_tokens'] : 1000,
				'temperature' => isset( $this->opts['temperature'] ) ? (float) $this->opts['temperature'] : 0.7,
			),
			array( 'Authorization' => 'Bearer ' . $key ),
			$timeout
		);

		return isset( $data['choices'][0]['message']['content'] )
			? (string) $data['choices'][0]['message']['content']
			: '';
	}

	/**
	 * OpenAI SSE streaming.
	 * Lines: "data: {json}" or "data: [DONE]"
	 */
	public function stream_chat( array $messages, $system = '' ) {
		$key     = isset( $this->opts['openai_key'] ) ? $this->opts['openai_key'] : '';
		$this->require_api_key( $key, 'OpenAI' );
		$model   = ! empty( $this->opts['openai_model'] ) ? $this->opts['openai_model'] : 'gpt-3.5-turbo';
		$timeout = isset( $this->opts['openai_timeout'] ) ? (int) $this->opts['openai_timeout'] : 30;

		$payload_messages = array();
		if ( '' !== $system ) {
			$payload_messages[] = array( 'role' => 'system', 'content' => $system );
		}
		foreach ( $messages as $m ) {
			$payload_messages[] = array( 'role' => $m['role'], 'content' => $m['content'] );
		}

		$full_reply = '';

		$this->http_stream(
			'https://api.openai.com/v1/chat/completions',
			array(
				'model'       => $model,
				'messages'    => $payload_messages,
				'max_tokens'  => isset( $this->opts['max_tokens'] ) ? (int) $this->opts['max_tokens'] : 1000,
				'temperature' => isset( $this->opts['temperature'] ) ? (float) $this->opts['temperature'] : 0.7,
				'stream'      => true,
			),
			array( 'Authorization' => 'Bearer ' . $key ),
			$timeout,
			function ( $line ) use ( &$full_reply ) {
				// OpenAI lines start with "data: "
				if ( 0 !== strpos( $line, 'data: ' ) ) {
					return;
				}
				$json_str = substr( $line, 6 );
				if ( '[DONE]' === trim( $json_str ) ) {
					return;
				}
				$json  = json_decode( $json_str, true );
				$token = isset( $json['choices'][0]['delta']['content'] )
					? (string) $json['choices'][0]['delta']['content']
					: '';
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
			$key = isset( $this->opts['openai_key'] ) ? $this->opts['openai_key'] : '';
			$this->require_api_key( $key, 'OpenAI' );
			$data = $this->http_get(
				'https://api.openai.com/v1/models',
				array( 'Authorization' => 'Bearer ' . $key )
			);
			return ! empty( $data['data'] );
		} catch ( Exception $e ) {
			return false;
		}
	}
}
