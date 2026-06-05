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
	 * Stream a chat request, emitting SSE chunks as tokens arrive.
	 *
	 * @param array  $messages Messages.
	 * @param string $system   Optional system prompt.
	 * @return string Full assembled reply.
	 */
	public function stream_chat( array $messages, $system = '' ) {
		$base    = isset( $this->opts['ollama_url'] ) ? rtrim( (string) $this->opts['ollama_url'], '/' ) : '';
		$url     = $base . '/api/chat';
		$model   = ! empty( $this->opts['ollama_model'] ) ? $this->opts['ollama_model'] : 'qwen2:1.5b';
		$timeout = isset( $this->opts['ollama_timeout'] ) ? max( 120, (int) $this->opts['ollama_timeout'] ) : 120;

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
				$data  = json_decode( $line, true );
				$chunk = isset( $data['message']['content'] ) ? (string) $data['message']['content'] : '';
				if ( '' !== $chunk ) {
					$full_reply .= $chunk;
					self::sse_send( 'chunk', array( 'text' => $chunk ) );
				}
			}
		);

		return $full_reply;
	}

	/**
	 * Send a chat request (non-streaming, used for title generation etc).
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
