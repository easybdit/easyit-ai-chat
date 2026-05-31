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

class EAIC_Ollama extends EAIC_Provider {

	public function chat( array $messages, $system = '' ) {
		$base    = isset( $this->opts['ollama_url'] ) ? rtrim( (string) $this->opts['ollama_url'], '/' ) : '';
		$url     = $base . '/api/chat';
		$model   = ! empty( $this->opts['ollama_model'] ) ? $this->opts['ollama_model'] : 'gemma3:4b';
		$timeout = isset( $this->opts['ollama_timeout'] ) ? (int) $this->opts['ollama_timeout'] : 120;

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
	 * Streaming chat — Ollama returns NDJSON lines.
	 * Each line: {"message":{"content":"token"},"done":false}
	 */
	public function stream_chat( array $messages, $system = '' ) {
		$base    = isset( $this->opts['ollama_url'] ) ? rtrim( (string) $this->opts['ollama_url'], '/' ) : '';
		$url     = $base . '/api/chat';
		$model   = ! empty( $this->opts['ollama_model'] ) ? $this->opts['ollama_model'] : 'gemma3:4b';
		$timeout = isset( $this->opts['ollama_timeout'] ) ? (int) $this->opts['ollama_timeout'] : 120;

		$payload_messages = array();
		if ( '' !== $system ) {
			$payload_messages[] = array( 'role' => 'system', 'content' => $system );
		}
		foreach ( $messages as $m ) {
			$payload_messages[] = array( 'role' => $m['role'], 'content' => $m['content'] );
		}

		$full_reply = '';

		$this->http_stream(
			$url,
			array(
				'model'    => $model,
				'messages' => $payload_messages,
				'stream'   => true,
			),
			array(),
			$timeout,
			function ( $line ) use ( &$full_reply ) {
				$json = json_decode( $line, true );
				if ( ! is_array( $json ) ) {
					return;
				}
				$token = isset( $json['message']['content'] ) ? (string) $json['message']['content'] : '';
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
			$base = isset( $this->opts['ollama_url'] ) ? rtrim( (string) $this->opts['ollama_url'], '/' ) : '';
			$url  = $base . '/api/tags';
			$data = $this->http_get( $url );
			return isset( $data['models'] );
		} catch ( Exception $e ) {
			return false;
		}
	}
}
