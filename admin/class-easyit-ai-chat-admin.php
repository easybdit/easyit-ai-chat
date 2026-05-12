<?php
/**
 * Admin area: menus, settings, assets.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class EasyIT_AI_Chat_Admin {

	public function __construct() {
		add_action( 'admin_menu',            [ $this, 'add_menu' ] );
		add_action( 'admin_init',            [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_filter( 'plugin_action_links_' . EASYIT_AI_CHAT_BASENAME, [ $this, 'action_links' ] );
	}

	public function add_menu(): void {
		add_menu_page( 'EasyIT AI Chat', 'EasyIT AI Chat', 'manage_options', 'easyit-ai-chat',
			[ $this, 'render_settings' ], 'dashicons-format-chat', 81 );
		add_submenu_page( 'easyit-ai-chat', 'Settings', 'Settings', 'manage_options',
			'easyit-ai-chat', [ $this, 'render_settings' ] );
		add_submenu_page( 'easyit-ai-chat', 'Test Chat', 'Test Chat', 'manage_options',
			'laraveeai-test-chat', [ $this, 'render_test_chat' ] );
	}

	public function register_settings(): void {
		register_setting( 'easyit_ai_chat_group', EasyIT_AI_Chat_Options::OPTION_KEY, [
			'sanitize_callback' => [ $this, 'sanitize' ],
		] );
	}

	public function sanitize( $input ): array {
		$c = EasyIT_AI_Chat_Options::all();
		return [
			'default_provider'    => in_array( $input['default_provider'] ?? '', ['ollama','openai','anthropic','deepseek'], true ) ? $input['default_provider'] : $c['default_provider'],
			'ollama_url'          => esc_url_raw( $input['ollama_url'] ?? $c['ollama_url'] ),
			'ollama_model'        => sanitize_text_field( $input['ollama_model'] ?? $c['ollama_model'] ),
			'ollama_timeout'      => absint( $input['ollama_timeout'] ?? $c['ollama_timeout'] ),
			'openai_key'          => sanitize_text_field( $input['openai_key'] ?? '' ),
			'openai_model'        => sanitize_text_field( $input['openai_model'] ?? $c['openai_model'] ),
			'openai_timeout'      => absint( $input['openai_timeout'] ?? $c['openai_timeout'] ),
			'anthropic_key'       => sanitize_text_field( $input['anthropic_key'] ?? '' ),
			'anthropic_model'     => sanitize_text_field( $input['anthropic_model'] ?? $c['anthropic_model'] ),
			'anthropic_timeout'   => absint( $input['anthropic_timeout'] ?? $c['anthropic_timeout'] ),
			'deepseek_key'        => sanitize_text_field( $input['deepseek_key'] ?? '' ),
			'deepseek_model'      => sanitize_text_field( $input['deepseek_model'] ?? $c['deepseek_model'] ),
			'deepseek_timeout'    => absint( $input['deepseek_timeout'] ?? $c['deepseek_timeout'] ),
			'system_prompt'       => sanitize_textarea_field( $input['system_prompt'] ?? $c['system_prompt'] ),
			'temperature'         => min( 2.0, max( 0.0, (float)( $input['temperature'] ?? $c['temperature'] ) ) ),
			'max_tokens'          => max( 100, min( 8000, absint( $input['max_tokens'] ?? $c['max_tokens'] ) ) ),
			'chat_title'          => sanitize_text_field( $input['chat_title'] ?? $c['chat_title'] ),
			'placeholder_text'    => sanitize_text_field( $input['placeholder_text'] ?? $c['placeholder_text'] ),
			'show_provider_badge' => ! empty( $input['show_provider_badge'] ),
			'allow_guest_chat'    => ! empty( $input['allow_guest_chat'] ),
			'save_guest_history'  => ! empty( $input['save_guest_history'] ),
			'privacy_notice'      => ! empty( $input['privacy_notice'] ),
			'data_retention_days' => max( 1, absint( $input['data_retention_days'] ?? $c['data_retention_days'] ) ),
		];
	}

	public function enqueue_assets( string $hook ): void {
		$pages = [ 'toplevel_page_easyit-ai-chat', 'easyit-ai-chat_page_laraveeai-test-chat' ];
		if ( ! in_array( $hook, $pages, true ) ) return;

		wp_enqueue_style(  'laraveeai-admin', EASYIT_AI_CHAT_URL . 'admin/assets/admin.css', [], EASYIT_AI_CHAT_VERSION );
		wp_enqueue_script( 'laraveeai-admin', EASYIT_AI_CHAT_URL . 'admin/assets/admin.js', ['jquery'], EASYIT_AI_CHAT_VERSION, true );
		wp_localize_script( 'laraveeai-admin', 'EasyITAIChatAdmin', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'easyit_ai_chat_admin_nonce' ),
			'i18n'     => [ 'testing' => 'Testing…', 'error' => 'Request failed.' ],
		] );

		// Also enqueue public chat assets on test-chat page (wp_enqueue_scripts doesn't fire in admin)
		if ( 'easyit-ai-chat_page_laraveeai-test-chat' === $hook ) {
			$opts = EasyIT_AI_Chat_Options::all();
			wp_enqueue_style(  'easyit-ai-chat', EASYIT_AI_CHAT_URL . 'public/css/chat.css', [], EASYIT_AI_CHAT_VERSION );
			wp_enqueue_script( 'easyit-ai-chat', EASYIT_AI_CHAT_URL . 'public/js/chat.js',  [], EASYIT_AI_CHAT_VERSION, true );
			wp_localize_script( 'easyit-ai-chat', 'EasyITAIChatConfig', $this->chat_config( $opts ) );
		}
	}

	private function chat_config( array $opts ): array {
		return [
			'ajax_url'            => admin_url( 'admin-ajax.php' ),
			'nonce'               => wp_create_nonce( 'easyit_ai_chat_nonce' ),
			'default_provider'    => esc_js( $opts['default_provider'] ),
			'show_provider_badge' => (bool) $opts['show_provider_badge'],
			'privacy_notice'      => (bool) $opts['privacy_notice'],
			'is_logged_in'        => is_user_logged_in(),
			'i18n'                => [
				'new_chat'       => 'New Chat',
				'thinking'       => 'Thinking…',
				'error_generic'  => 'Something went wrong. Please try again.',
				'error_empty'    => 'Please type a message first.',
				'delete_confirm' => 'Delete this conversation?',
				'privacy_text'   => 'Conversations are saved. See our Privacy Policy.',
				'copied'         => 'Copied!',
				'copy'           => 'Copy',
				'you'            => 'You',
				'ai'             => 'AI',
				'no_sessions'    => 'No conversations yet.',
				'today'          => 'Today',
				'yesterday'      => 'Yesterday',
			],
		];
	}

	public function action_links( array $links ): array {
		array_unshift( $links, '<a href="' . admin_url( 'admin.php?page=easyit-ai-chat' ) . '">Settings</a>' );
		return $links;
	}

	public function render_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) return;
		$opts = EasyIT_AI_Chat_Options::all();
		require EASYIT_AI_CHAT_DIR . 'admin/views/settings-page.php';
	}

	public function render_test_chat(): void {
		if ( ! current_user_can( 'manage_options' ) ) return;
		$opts = EasyIT_AI_Chat_Options::all();
		require EASYIT_AI_CHAT_DIR . 'admin/views/test-chat-page.php';
	}
}
