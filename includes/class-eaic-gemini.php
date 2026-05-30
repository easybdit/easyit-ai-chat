<?php
/**
 * Google Gemini provider implementation.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAIC_Gemini extends EAIC_Provider {

	const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

	public function chat( array $messages, $system = '' ) {
		$key     = isset( $this->opts['gemini_key'] )     ? $this->opts['gemini_key']     : '';
		$model   = ! empty( $this->opts['gemini_model'] ) ? $this->opts['gemini_model']   : 'gemini-1.5-flash';
		$timeout = isset( $this->opts['gemini_timeout'] ) ? (int) $this->opts['gemini_timeout'] : 30;
		$this->require_api_key( $key, 'Gemini' );

		$contents = array();
		foreach ( $messages as $m ) {
			$role       = 'assistant' === $m['role'] ? 'model' : 'user';
			$contents[] = array(
				'role'  => $role,
				'parts' => array( array( 'text' => (string) $m['content'] ) ),
			);
		}

		$body = array(
			'contents'         => $contents,
			'generationConfig' => array(
				'temperature'     => isset( $this->opts['temperature'] ) ? (float) $this->opts['temperature'] : 0.7,
				'maxOutputTokens' => isset( $this->opts['max_tokens'] )  ? (int) $this->opts['max_tokens']   : 1000,
			),
		);
		if ( '' !== $system ) {
			$body['system_instruction'] = array(
				'parts' => array( array( 'text' => $system ) ),
			);
		}

		$url  = self::BASE_URL . rawurlencode( $model ) . ':generateContent?key=' . rawurlencode( $key );
		$data = $this->gemini_post( $url, $body, $timeout );

		return isset( $data['candidates'][0]['content']['parts'][0]['text'] )
			? (string) $data['candidates'][0]['content']['parts'][0]['text']
			: '';
	}

	/**
	 * Gemini streaming — uses streamGenerateContent endpoint.
	 * Returns NDJSON lines, each a partial candidate.
	 */
	public function stream_chat( array $messages, $system = '' ) {
		$key     = isset( $this->opts['gemini_key'] )     ? $this->opts['gemini_key']     : '';
		$model   = ! empty( $this->opts['gemini_model'] ) ? $this->opts['gemini_model']   : 'gemini-1.5-flash';
		$timeout = isset( $this->opts['gemini_timeout'] ) ? (int) $this->opts['gemini_timeout'] : 30;
		$this->require_api_key( $key, 'Gemini' );

		$contents = array();
		foreach ( $messages as $m ) {
			$role       = 'assistant' === $m['role'] ? 'model' : 'user';
			$contents[] = array(
				'role'  => $role,
				'parts' => array( array( 'text' => (string) $m['content'] ) ),
			);
		}

		$body = array(
			'contents'         => $contents,
			'generationConfig' => array(
				'temperature'     => isset( $this->opts['temperature'] ) ? (float) $this->opts['temperature'] : 0.7,
				'maxOutputTokens' => isset( $this->opts['max_tokens'] )  ? (int) $this->opts['max_tokens']   : 1000,
			),
		);
		if ( '' !== $system ) {
			$body['system_instruction'] = array(
				'parts' => array( array( 'text' => $system ) ),
			);
		}

		// streamGenerateContent returns SSE lines prefixed with "data: "
		$url        = self::BASE_URL . rawurlencode( $model ) . ':streamGenerateContent?alt=sse&key=' . rawurlencode( $key );
		$full_reply = '';

		$this->gemini_stream( $url, $body, $timeout, function ( $line ) use ( &$full_reply ) {
			if ( 0 !== strpos( $line, 'data: ' ) ) {
				return;
			}
			$json  = json_decode( substr( $line, 6 ), true );
			$token = isset( $json['candidates'][0]['content']['parts'][0]['text'] )
				? (string) $json['candidates'][0]['content']['parts'][0]['text']
				: '';
			if ( '' !== $token ) {
				$full_reply .= $token;
				self::sse_send( 'chunk', array( 'text' => $token ) );
			}
		} );

		return $full_reply;
	}

	public function health() {
		try {
			$key = isset( $this->opts['gemini_key'] ) ? $this->opts['gemini_key'] : '';
			$this->require_api_key( $key, 'Gemini' );
			$url  = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . rawurlencode( $key );
			$data = $this->gemini_get( $url );
			return ! empty( $data['models'] );
		} catch ( Exception $e ) {
			return false;
		}
	}

	// --- Gemini-specific HTTP helpers (bypass filter_var for colon in path) ---

	private function gemini_post( $url, array $body, $timeout ) {
		if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			throw new RuntimeException( esc_html__( 'Invalid Gemini endpoint URL.', 'easyit-ai-chat' ) );
		}
		$response = wp_remote_post(
			$url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
				'timeout' => (int) $timeout,
			)
		);
		return $this->parse_gemini_response( $response );
	}

	private function gemini_get( $url ) {
		if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			throw new RuntimeException( esc_html__( 'Invalid Gemini endpoint URL.', 'easyit-ai-chat' ) );
		}
		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
		return $this->parse_gemini_response( $response );
	}

	private function gemini_stream( $url, array $body, $timeout, callable $callback ) {
		if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			throw new RuntimeException( esc_html__( 'Invalid Gemini endpoint URL.', 'easyit-ai-chat' ) );
		}

		$context = stream_context_create( array(
			'http' => array(
				'method'        => 'POST',
				'header'        => "Content-Type: application/json\r\n",
				'content'       => wp_json_encode( $body ),
				'timeout'       => (int) $timeout,
				'ignore_errors' => true,
			),
			'ssl' => array(
				'verify_peer'      => true,
				'verify_peer_name' => true,
			),
		) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$stream = @fopen( $url, 'r', false, $context );
		if ( ! $stream ) {
			throw new RuntimeException( esc_html__( 'Failed to open Gemini stream.', 'easyit-ai-chat' ) );
		}

		while ( ! feof( $stream ) ) {
			$line = fgets( $stream, 4096 );
			if ( false === $line ) {
				break;
			}
			$line = rtrim( $line, "\r\n" );
			if ( '' !== $line ) {
				$callback( $line );
			}
		}
		fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

	private function parse_gemini_response( $response ) {
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( esc_html( $response->get_error_message() ) );
		}
		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code >= 400 ) {
			$msg = is_array( $data ) && isset( $data['error']['message'] )
				? (string) $data['error']['message']
				/* translators: %d: HTTP status code */
				: sprintf( __( 'HTTP error %d', 'easyit-ai-chat' ), (int) $code );
			throw new RuntimeException( esc_html( $msg ) );
		}
		return is_array( $data ) ? $data : array();
	}
}
