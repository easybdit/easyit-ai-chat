<?php
/**
 * Plugin options/settings manager.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralised access to plugin options with safe defaults.
 */
class EAIC_Options {

	const OPTION_KEY = 'eaic_options';

	/**
	 * Default values for every setting.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'default_provider'    => 'ollama',
			'ollama_url'          => 'http://localhost:11434',
			'ollama_model'        => 'qwen2:1.5b',
			'ollama_timeout'      => 300,
			'openai_key'          => '',
			'openai_model'        => 'gpt-4o-mini',
			'openai_timeout'      => 30,
			'anthropic_key'       => '',
			'anthropic_model'     => 'claude-3-5-haiku-20241022',
			'anthropic_timeout'   => 30,
			'deepseek_key'        => '',
			'deepseek_model'      => 'deepseek-chat',
			'deepseek_timeout'    => 30,
			'gemini_key'          => '',
			'gemini_model'        => 'gemini-2.0-flash',
			'gemini_timeout'      => 30,
			'system_prompt'       => 'You are a helpful AI assistant.',
			'temperature'         => 0.7,
			'max_tokens'          => 1000,
			'chat_title'          => 'AI Chat',
			'placeholder_text'    => 'Ask me anything...',
			'show_provider_badge'  => true,
			'allow_guest_chat'     => true,
			'privacy_notice'       => true,
			'data_retention_days'  => 90,
			'rate_limit_window'    => 60,
			'rate_limit_max'       => 20,
			'rate_limit_ip_max'    => 60,
			'allowed_providers'    => array( 'ollama', 'openai', 'anthropic', 'deepseek', 'gemini' ),
			'lock_system_prompt'      => false,
			'welcome_message_enabled'      => false,
			'welcome_message_text'         => 'Hello! How can I help you today?',
			'suggested_questions_enabled'  => false,
			'suggested_questions'          => "What can you help me with?\nTell me about your features\nHow do I get started?",
			'ai_avatar_url'                => '',
		);
	}

	/**
	 * Get all options merged with defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function all() {
		$saved = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	/**
	 * Get a single option value.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default_value Fallback if not set.
	 * @return mixed
	 */
	public static function get( $key, $default_value = null ) {
		$opts = self::all();
		return isset( $opts[ $key ] ) ? $opts[ $key ] : $default_value;
	}
}
