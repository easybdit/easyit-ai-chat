<?php
/**
 * Admin area: menus, settings, assets.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires the WP admin UI: menu pages, settings, assets, action links.
 */
class EAIC_Admin {

	/**
	 * Boot hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu',            array( $this, 'add_menu' ) );
		add_action( 'admin_init',            array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . EAIC_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Register admin menus.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'EasyIT AI Chat', 'easyit-ai-chat' ),
			__( 'EasyIT AI Chat', 'easyit-ai-chat' ),
			'manage_options',
			'eaic',
			array( $this, 'render_settings' ),
			'dashicons-format-chat',
			81
		);
		add_submenu_page(
			'eaic',
			__( 'Settings', 'easyit-ai-chat' ),
			__( 'Settings', 'easyit-ai-chat' ),
			'manage_options',
			'eaic',
			array( $this, 'render_settings' )
		);
		add_submenu_page(
			'eaic',
			__( 'Test Chat', 'easyit-ai-chat' ),
			__( 'Test Chat', 'easyit-ai-chat' ),
			'manage_options',
			'eaic-test-chat',
			array( $this, 'render_test_chat' )
		);
	}

	/**
	 * Register the single options bucket.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'eaic_group',
			EAIC_Options::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => EAIC_Options::defaults(),
			)
		);
	}

	/**
	 * Sanitize the raw $_POST array coming from the settings form.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}
		$current = EAIC_Options::all();

		$all_known_providers = array( 'ollama', 'openai', 'anthropic', 'deepseek', 'gemini' );

		$default_provider = isset( $input['default_provider'] ) ? (string) $input['default_provider'] : $current['default_provider'];
		if ( ! in_array( $default_provider, $all_known_providers, true ) ) {
			$default_provider = $current['default_provider'];
		}

		// Allowed providers: at least one must remain; fall back to current if all deselected.
		if ( isset( $input['allowed_providers'] ) && is_array( $input['allowed_providers'] ) ) {
			$raw_allowed       = array_intersect( array_map( 'sanitize_key', $input['allowed_providers'] ), $all_known_providers );
			$allowed_providers = ! empty( $raw_allowed ) ? array_values( $raw_allowed ) : $current['allowed_providers'];
		} else {
			$allowed_providers = $current['allowed_providers'];
		}

		return array(
			'default_provider'    => $default_provider,
			'ollama_url'          => isset( $input['ollama_url'] )          ? esc_url_raw( $input['ollama_url'] )                            : $current['ollama_url'],
			'ollama_model'        => isset( $input['ollama_model'] )        ? sanitize_text_field( $input['ollama_model'] )                  : $current['ollama_model'],
			'ollama_timeout'      => isset( $input['ollama_timeout'] )      ? absint( $input['ollama_timeout'] )                             : $current['ollama_timeout'],
			'openai_key'          => isset( $input['openai_key'] )          ? sanitize_text_field( $input['openai_key'] )                    : '',
			'openai_model'        => isset( $input['openai_model'] )        ? sanitize_text_field( $input['openai_model'] )                  : $current['openai_model'],
			'openai_timeout'      => isset( $input['openai_timeout'] )      ? absint( $input['openai_timeout'] )                             : $current['openai_timeout'],
			'anthropic_key'       => isset( $input['anthropic_key'] )       ? sanitize_text_field( $input['anthropic_key'] )                 : '',
			'anthropic_model'     => isset( $input['anthropic_model'] )     ? sanitize_text_field( $input['anthropic_model'] )               : $current['anthropic_model'],
			'anthropic_timeout'   => isset( $input['anthropic_timeout'] )   ? absint( $input['anthropic_timeout'] )                          : $current['anthropic_timeout'],
			'deepseek_key'        => isset( $input['deepseek_key'] )        ? sanitize_text_field( $input['deepseek_key'] )                  : '',
			'deepseek_model'      => isset( $input['deepseek_model'] )      ? sanitize_text_field( $input['deepseek_model'] )                : $current['deepseek_model'],
			'deepseek_timeout'    => isset( $input['deepseek_timeout'] )    ? absint( $input['deepseek_timeout'] )                           : $current['deepseek_timeout'],
			'gemini_key'          => isset( $input['gemini_key'] )          ? sanitize_text_field( $input['gemini_key'] )                    : '',
			'gemini_model'        => isset( $input['gemini_model'] )        ? sanitize_text_field( $input['gemini_model'] )                  : $current['gemini_model'],
			'gemini_timeout'      => isset( $input['gemini_timeout'] )      ? absint( $input['gemini_timeout'] )                             : $current['gemini_timeout'],
			'system_prompt'       => isset( $input['system_prompt'] )       ? sanitize_textarea_field( $input['system_prompt'] )             : $current['system_prompt'],
			'temperature'         => isset( $input['temperature'] )         ? min( 2.0, max( 0.0, (float) $input['temperature'] ) )          : $current['temperature'],
			'max_tokens'          => isset( $input['max_tokens'] )          ? max( 100, min( 8000, absint( $input['max_tokens'] ) ) )        : $current['max_tokens'],
			'chat_title'          => isset( $input['chat_title'] )          ? sanitize_text_field( $input['chat_title'] )                    : $current['chat_title'],
			'placeholder_text'    => isset( $input['placeholder_text'] )    ? sanitize_text_field( $input['placeholder_text'] )              : $current['placeholder_text'],
			'show_provider_badge' => ! empty( $input['show_provider_badge'] ),
			'allow_guest_chat'    => ! empty( $input['allow_guest_chat'] ),
			'privacy_notice'      => ! empty( $input['privacy_notice'] ),
			'data_retention_days' => isset( $input['data_retention_days'] ) ? max( 1, absint( $input['data_retention_days'] ) )              : $current['data_retention_days'],
			'rate_limit_window'   => isset( $input['rate_limit_window'] )   ? max( 10, min( 3600, absint( $input['rate_limit_window'] ) ) )  : $current['rate_limit_window'],
			'rate_limit_max'      => isset( $input['rate_limit_max'] )      ? max( 1, min( 1000, absint( $input['rate_limit_max'] ) ) )      : $current['rate_limit_max'],
			'rate_limit_ip_max'   => isset( $input['rate_limit_ip_max'] )   ? max( 1, min( 1000, absint( $input['rate_limit_ip_max'] ) ) )   : $current['rate_limit_ip_max'],
			'allowed_providers'   => $allowed_providers,
			'lock_system_prompt'      => ! empty( $input['lock_system_prompt'] ),
			'welcome_message_enabled'     => ! empty( $input['welcome_message_enabled'] ),
			'welcome_message_text'        => isset( $input['welcome_message_text'] )        ? sanitize_textarea_field( $input['welcome_message_text'] )        : $current['welcome_message_text'],
			'suggested_questions_enabled' => ! empty( $input['suggested_questions_enabled'] ),
			'suggested_questions'         => isset( $input['suggested_questions'] )         ? sanitize_textarea_field( $input['suggested_questions'] )         : $current['suggested_questions'],
		);
	}

	/**
	 * Enqueue admin CSS/JS only on our pages.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		$is_settings  = ( 'toplevel_page_eaic' === $hook );
		$is_test_chat = ( 'easyit-ai-chat_page_eaic-test-chat' === $hook )
			|| ( 'eaic_page_eaic-test-chat' === $hook );

		if ( ! $is_settings && ! $is_test_chat ) {
			return;
		}

		wp_enqueue_style(
			'eaic-admin',
			EAIC_URL . 'admin/assets/admin.css',
			array(),
			EAIC_VERSION
		);
		wp_enqueue_script(
			'eaic-admin',
			EAIC_URL . 'admin/assets/admin.js',
			array( 'jquery' ),
			EAIC_VERSION,
			true
		);
		wp_localize_script(
			'eaic-admin',
			'EAICAdmin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'eaic_admin_nonce' ),
				'i18n'     => array(
					'testing' => __( 'Testing…', 'easyit-ai-chat' ),
					'error'   => __( 'Request failed.', 'easyit-ai-chat' ),
				),
			)
		);

		if ( $is_test_chat ) {
			$opts = EAIC_Options::all();

			wp_enqueue_style(
				'eaic-public',
				EAIC_URL . 'public/css/chat.css',
				array(),
				EAIC_VERSION
			);
			wp_enqueue_script(
				'eaic-public',
				EAIC_URL . 'public/js/chat.js',
				array(),
				EAIC_VERSION,
				true
			);
			wp_localize_script(
				'eaic-public',
				'EAICConfig',
				array(
					'ajax_url'            => admin_url( 'admin-ajax.php' ),
					'nonce'               => wp_create_nonce( 'eaic_nonce' ),
					'default_provider'    => $opts['default_provider'],
					'show_provider_badge' => (bool) $opts['show_provider_badge'],
					'privacy_notice'      => (bool) $opts['privacy_notice'],
					'is_logged_in'        => is_user_logged_in(),
					'i18n'                => $this->frontend_i18n(),
				)
			);
		}
	}

	/**
	 * Strings shared with the frontend chat widget.
	 *
	 * @return array<string,string>
	 */
	public function frontend_i18n() {
		return array(
			'new_chat'       => __( 'New Chat', 'easyit-ai-chat' ),
			'thinking'       => __( 'Thinking…', 'easyit-ai-chat' ),
			'error_generic'  => __( 'Something went wrong. Please try again.', 'easyit-ai-chat' ),
			'error_empty'    => __( 'Please type a message first.', 'easyit-ai-chat' ),
			'delete_confirm' => __( 'Delete this conversation?', 'easyit-ai-chat' ),
			'privacy_text'   => __( 'Conversations are saved. See our Privacy Policy.', 'easyit-ai-chat' ),
			'copied'         => __( 'Copied!', 'easyit-ai-chat' ),
			'copy'           => __( 'Copy', 'easyit-ai-chat' ),
			'you'            => __( 'You', 'easyit-ai-chat' ),
			'ai'             => __( 'AI', 'easyit-ai-chat' ),
			'no_sessions'    => __( 'No conversations yet.', 'easyit-ai-chat' ),
			'today'          => __( 'Today', 'easyit-ai-chat' ),
			'yesterday'      => __( 'Yesterday', 'easyit-ai-chat' ),
			'earlier'        => __( 'Earlier', 'easyit-ai-chat' ),
		);
	}

	/**
	 * Add Settings link in plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		if ( ! is_array( $links ) ) {
			$links = array();
		}
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'admin.php?page=eaic' ) ) . '">'
			. esc_html__( 'Settings', 'easyit-ai-chat' ) . '</a>'
		);
		if ( function_exists( 'eaic_fs' ) && ! eaic_fs()->can_use_premium_code() ) {
			$links[] = '<a href="' . esc_url( eaic_fs()->get_upgrade_url() ) . '" style="color:#e65f00;font-weight:600;">'
				. esc_html__( 'Upgrade to Pro →', 'easyit-ai-chat' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$eaic_opts = EAIC_Options::all();
		require EAIC_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * Render the test-chat page.
	 *
	 * @return void
	 */
	public function render_test_chat() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$eaic_opts = EAIC_Options::all();
		require EAIC_DIR . 'admin/views/test-chat-page.php';
	}
}
