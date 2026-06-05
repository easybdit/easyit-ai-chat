<?php
/**
 * Together AI provider implementation.
 *
 * @package EasyIT_AI_Chat
 * @since   2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Together AI (OpenAI-compatible) provider.
 *
 * Docs: https://docs.together.ai/reference/chat-completions
 */
class EAIC_Together extends EAIC_Provider {

	const BASE_URL = 'https://api.together.xyz/v1';

	/**
	 * Send a chat request.
	 *
	 * @param array  $messages Messages.
	 * @param string $system   System prompt.
	 * @return string
	 */
	public function chat( array $messages, $system = '' ) {
		$key     = isset( $this->opts['together_key'] ) ? $this->opts['together_key'] : '';
		$this->require_api_key( $key, 'Together AI' );
		$model   = ! empty( $this->opts['together_model'] ) ? $this->opts['together_model'] : 'meta-llama/Llama-3.3-70B-Instruct-Turbo';
		$timeout = isset( $this->opts['together_timeout'] ) ? (int) $this->opts['together_timeout'] : 60;

		$payload_messages = array();
		if ( '' !== $system ) {
			$payload_messages[] = array( 'role' => 'system', 'content' => $system );
		}
		foreach ( $messages as $m ) {
			$payload_messages[] = array( 'role' => $m['role'], 'content' => $m['content'] );
		}

		$data = $this->http_post(
			self::BASE_URL . '/chat/completions',
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
	 * Stream chat tokens via Together AI SSE.
	 *
	 * @param array  $messages Messages.
	 * @param string $system   System prompt.
	 * @return string Full assembled reply.
	 */
	public function stream_chat( array $messages, $system = '' ) {
		$key     = isset( $this->opts['together_key'] ) ? $this->opts['together_key'] : '';
		$this->require_api_key( $key, 'Together AI' );
		$model   = ! empty( $this->opts['together_model'] ) ? $this->opts['together_model'] : 'meta-llama/Llama-3.3-70B-Instruct-Turbo';
		$timeout = isset( $this->opts['together_timeout'] ) ? (int) $this->opts['together_timeout'] : 60;

		$payload_messages = array();
		if ( '' !== $system ) {
			$payload_messages[] = array( 'role' => 'system', 'content' => $system );
		}
		foreach ( $messages as $m ) {
			$payload_messages[] = array( 'role' => $m['role'], 'content' => $m['content'] );
		}

		$full_reply = '';

		$this->http_stream(
			self::BASE_URL . '/chat/completions',
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
				if ( 0 !== strpos( $line, 'data:' ) ) {
					return;
				}
				$json_str = trim( substr( $line, 5 ) );
				if ( '[DONE]' === $json_str || '' === $json_str ) {
					return;
				}
				$chunk = json_decode( $json_str, true );
				if ( ! is_array( $chunk ) ) {
					return;
				}
				$text = isset( $chunk['choices'][0]['delta']['content'] )
					? (string) $chunk['choices'][0]['delta']['content']
					: '';
				if ( '' !== $text ) {
					$full_reply .= $text;
					self::sse_send( 'chunk', array( 'text' => $text ) );
				}
			}
		);

		return $full_reply;
	}

	/**
	 * Connectivity check — verifies the API key is valid via /models.
	 *
	 * @return bool
	 */
	public function health() {
		try {
			$key = isset( $this->opts['together_key'] ) ? $this->opts['together_key'] : '';
			$this->require_api_key( $key, 'Together AI' );
			$data = $this->http_get(
				self::BASE_URL . '/models',
				array( 'Authorization' => 'Bearer ' . $key )
			);
			return is_array( $data ) && ! empty( $data );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Generate an image via Together AI's image generation API.
	 *
	 * @param string $prompt The image description.
	 * @return string The generated image URL.
	 * @throws RuntimeException On API error or missing key.
	 */
	public function generate_image( $prompt ) {
		$key = isset( $this->opts['together_key'] ) ? $this->opts['together_key'] : '';
		$this->require_api_key( $key, 'Together AI' );

		$model = ! empty( $this->opts['together_image_model'] )
			? $this->opts['together_image_model']
			: 'black-forest-labs/FLUX.1-schnell-Free';

		$size   = ! empty( $this->opts['together_image_size'] ) ? $this->opts['together_image_size'] : '1024x1024';
		$parts  = explode( 'x', $size );
		$width  = isset( $parts[0] ) ? (int) $parts[0] : 1024;
		$height = isset( $parts[1] ) ? (int) $parts[1] : 1024;
		$steps  = ! empty( $this->opts['together_image_steps'] ) ? (int) $this->opts['together_image_steps'] : 4;

		$data = $this->http_post(
			self::BASE_URL . '/images/generations',
			array(
				'model'           => $model,
				'prompt'          => (string) $prompt,
				'width'           => $width,
				'height'          => $height,
				'steps'           => $steps,
				'n'               => 1,
				'response_format' => 'url',
			),
			array( 'Authorization' => 'Bearer ' . $key ),
			90
		);

		if ( empty( $data['data'][0]['url'] ) ) {
			throw new RuntimeException(
				esc_html__( 'No image returned from Together AI. Check your model and API key.', 'easyit-ai-chat' )
			);
		}

		return (string) $data['data'][0]['url'];
	}
}
