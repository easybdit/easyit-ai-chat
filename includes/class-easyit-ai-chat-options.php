<?php
/**
 * Plugin options/settings manager.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class EasyIT_AI_Chat_Options {
	const OPTION_KEY = 'easyit_ai_chat_options';

	public static function defaults(): array {
		return [
			'default_provider'    => 'ollama',
			'ollama_url'          => 'http://localhost:11434',
			'ollama_model'        => 'qwen2:1.5b',
			'ollama_timeout'      => 60,
			'openai_key'          => '',
			'openai_model'        => 'gpt-3.5-turbo',
			'openai_timeout'      => 30,
			'anthropic_key'       => '',
			'anthropic_model'     => 'claude-3-haiku-20240307',
			'anthropic_timeout'   => 30,
			'deepseek_key'        => '',
			'deepseek_model'      => 'deepseek-chat',
			'deepseek_timeout'    => 30,
			'system_prompt'       => 'You are a helpful AI assistant.',
			'temperature'         => 0.7,
			'max_tokens'          => 1000,
			'chat_title'          => 'AI Chat',
			'placeholder_text'    => 'Ask me anything…',
			'show_provider_badge' => true,
			'allow_guest_chat'    => true,
			'save_guest_history'  => true,
			'privacy_notice'      => true,
			'data_retention_days' => 90,
		];
	}

	public static function all(): array {
		$saved = get_option( self::OPTION_KEY, [] );
		return wp_parse_args( is_array( $saved ) ? $saved : [], self::defaults() );
	}

	public static function get( string $key, $default = null ) {
		$opts = self::all();
		return $opts[ $key ] ?? $default;
	}
}
