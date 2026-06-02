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
			'anthropic_model'     => 'claude-3-5-sonnet-20241022',
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
			'color_accent'                 => '#4f46e5',
			'color_user_bg'                => '#1a56db',
			'color_bot_bg'                 => '#f3f4f6',
			'voice_input_enabled'          => false,
			'export_enabled'               => false,
			'floating_widget_enabled'      => false,
			'floating_widget_position'     => 'bottom-right',
			'floating_widget_label'        => 'Chat with us',
			'gdpr_gate_enabled'            => false,
			'gdpr_gate_text'               => 'This chat uses AI services. By continuing, you agree to our Privacy Policy.',
			'gdpr_gate_btn_text'           => 'I Accept & Continue',
			'context_messages'             => 10,
			'bot_profiles'                 => array(),
			'custom_providers'             => array(),
			'webhook_url'                  => '',
			'webhook_secret'               => '',
			// v2.0.0 — Security
			'access_restriction'           => 'everyone',  // everyone | logged_in | specific_roles
			'allowed_roles'                => array( 'subscriber', 'contributor', 'author', 'editor', 'administrator' ),
			'ip_blocklist'                 => '',
			'word_filter_enabled'          => false,
			'word_filter_words'            => '',
			'word_filter_action'           => 'block',     // block | warn
			'disable_storage'              => false,
			'captcha_enabled'              => false,
			'abuse_alert_enabled'          => false,
			'abuse_alert_email'            => '',
			'prompt_injection_detect'      => false,
			'prompt_injection_action'      => 'block',     // block | warn
			'max_message_length'           => 4000,
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
