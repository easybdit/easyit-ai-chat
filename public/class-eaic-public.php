<?php
/**
 * Public-facing shortcode and assets.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the [eaic_chat] shortcode and enqueues frontend assets.
 */
class EAIC_Public {

	/**
	 * Boot hooks.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( 'eaic_chat', array( $this, 'render_shortcode' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_action( 'wp_footer', array( $this, 'render_floating_widget' ), 99 );
	}

	/**
	 * Adds a body class on pages containing the shortcode, so we can apply
	 * full-width overrides for the chat layout.
	 *
	 * @param array $classes Existing classes.
	 * @return array
	 */
	public function body_class( $classes ) {
		global $post;
		if ( ! is_array( $classes ) ) {
			$classes = array();
		}
		if ( $post && has_shortcode( $post->post_content, 'eaic_chat' ) ) {
			$classes[] = 'eaic-chat-page';
		}
		return $classes;
	}

	/**
	 * Enqueue CSS/JS on the frontend.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
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

		$opts = EAIC_Options::all();

		// Reuse the admin's i18n strings to keep them in sync.
		$admin_i18n = class_exists( 'EAIC_Admin' ) ? EAIC_Admin::frontend_i18n() : array();

		wp_localize_script(
			'eaic-public',
			'EAICConfig',
			array(
				'ajax_url'                => admin_url( 'admin-ajax.php' ),
				'nonce'                   => wp_create_nonce( 'eaic_nonce' ),
				'default_provider'        => $opts['default_provider'],
				'show_provider_badge'     => (bool) $opts['show_provider_badge'],
				'privacy_notice'          => (bool) $opts['privacy_notice'],
				'is_logged_in'            => is_user_logged_in(),
				'welcome_message_enabled'     => (bool) $opts['welcome_message_enabled'],
				'welcome_message_text'        => $opts['welcome_message_text'],
				'ai_avatar_url'               => $opts['ai_avatar_url'],
				'voice_input_enabled'         => (bool) $opts['voice_input_enabled'],
				'export_enabled'              => (bool) $opts['export_enabled'],
				'gdpr_gate_enabled'           => (bool) $opts['gdpr_gate_enabled'],
				'gdpr_gate_text'              => $opts['gdpr_gate_text'],
				'gdpr_gate_btn_text'          => $opts['gdpr_gate_btn_text'],
				'captcha_enabled'             => (bool) $opts['captcha_enabled'],
				'max_message_length'          => max( 50, (int) $opts['max_message_length'] ),
				'suggested_questions_enabled' => (bool) $opts['suggested_questions_enabled'],
				'suggested_questions'         => array_slice(
					array_values( array_filter( array_map( 'trim', explode( "\n", $opts['suggested_questions'] ) ) ) ),
					0, 6
				),
				'i18n'                        => $admin_i18n,
			)
		);
	}

	/**
	 * Render [eaic_chat] shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$opts = EAIC_Options::all();
		$atts = shortcode_atts(
			array(
				'provider'      => $opts['default_provider'],
				'title'         => $opts['chat_title'],
				'placeholder'   => $opts['placeholder_text'],
				'system_prompt' => $opts['system_prompt'],
				'height'        => 600,
				'profile'       => '',
			),
			$atts,
			'eaic_chat'
		);

		// Apply bot profile overrides if profile slug matches.
		if ( ! empty( $atts['profile'] ) && ! empty( $opts['bot_profiles'] ) ) {
			$profile_slug = sanitize_key( $atts['profile'] );
			foreach ( $opts['bot_profiles'] as $prof ) {
				if ( isset( $prof['slug'] ) && $prof['slug'] === $profile_slug ) {
					if ( ! empty( $prof['provider'] ) )      { $atts['provider']      = $prof['provider']; }
					if ( ! empty( $prof['title'] ) )         { $atts['title']         = $prof['title']; }
					if ( ! empty( $prof['placeholder'] ) )   { $atts['placeholder']   = $prof['placeholder']; }
					if ( ! empty( $prof['system_prompt'] ) ) { $atts['system_prompt'] = $prof['system_prompt']; }
					break;
				}
			}
		}

		$color_accent  = $opts['color_accent']  ?: '#4f46e5';
		$color_user_bg = $opts['color_user_bg'] ?: '#1a56db';
		$color_bot_bg  = $opts['color_bot_bg']  ?: '#f3f4f6';

		$provider      = sanitize_key( $atts['provider'] );
		$title         = sanitize_text_field( $atts['title'] );
		$placeholder   = sanitize_text_field( $atts['placeholder'] );
		$system_prompt = sanitize_textarea_field( $atts['system_prompt'] );
		$height        = max( 300, absint( $atts['height'] ) );
		$lock_prompt   = ! empty( $opts['lock_system_prompt'] );

		// Access restriction — return early with message if not allowed.
		$restriction = $opts['access_restriction'] ?? 'everyone';
		if ( 'logged_in' === $restriction && ! is_user_logged_in() ) {
			return '<p class="eaic-access-denied">' . esc_html__( 'You must be logged in to use this chat.', 'easyit-ai-chat' ) . '</p>';
		}
		if ( 'specific_roles' === $restriction ) {
			if ( ! is_user_logged_in() ) {
				return '<p class="eaic-access-denied">' . esc_html__( 'You must be logged in to use this chat.', 'easyit-ai-chat' ) . '</p>';
			}
			$current_user  = wp_get_current_user();
			$allowed_roles = is_array( $opts['allowed_roles'] ) ? $opts['allowed_roles'] : array();
			if ( ! array_intersect( (array) $current_user->roles, $allowed_roles ) ) {
				return '<p class="eaic-access-denied">' . esc_html__( 'You do not have permission to use this chat.', 'easyit-ai-chat' ) . '</p>';
			}
		}

		// Captcha generation.
		$eaic_captcha = null;
		if ( ! empty( $opts['captcha_enabled'] ) ) {
			$eaic_captcha = EAIC_Engine::generate_captcha();
		}

		$providers = array(
			'ollama'    => 'Ollama',
			'openai'    => 'OpenAI',
			'anthropic' => 'Anthropic',
			'deepseek'  => 'DeepSeek',
			'gemini'    => 'Gemini',
		);
		if ( ! empty( $opts['custom_providers'] ) && is_array( $opts['custom_providers'] ) ) {
			foreach ( $opts['custom_providers'] as $i => $cp ) {
				if ( ! empty( $cp['enabled'] ) ) {
					$providers[ 'custom_' . ( $i + 1 ) ] = isset( $cp['name'] ) && $cp['name'] ? $cp['name'] : 'Custom ' . ( $i + 1 );
				}
			}
		}

		ob_start();
		$eaic_need_style = ( $color_accent  !== '#4f46e5' )
		                || ( $color_user_bg !== '#1a56db' )
		                || ( $color_bot_bg  !== '#f3f4f6' );
		if ( $eaic_need_style ) :
			?>
<style>.eaic-widget{--c-accent:<?php echo esc_attr( $color_accent ); ?> !important;--c-accent2:<?php echo esc_attr( $color_accent ); ?> !important;--c-user-bg:<?php echo esc_attr( $color_user_bg ); ?> !important;--c-bot-bg:<?php echo esc_attr( $color_bot_bg ); ?> !important;}</style>
<?php
		endif;
		?>
<div class="eaic-page-wrap">
	<div class="eaic-widget"
		data-provider="<?php echo esc_attr( $provider ); ?>"
		<?php if ( ! $lock_prompt ) : ?>data-system-prompt="<?php echo esc_attr( $system_prompt ); ?>"<?php endif; ?>
		data-msg-height="<?php echo esc_attr( (string) $height ); ?>">

		<div class="eaic-sidebar">
			<div class="eaic-sidebar-header">
				<button class="eaic-new-chat-btn" type="button">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
					<?php esc_html_e( 'New Chat', 'easyit-ai-chat' ); ?>
				</button>
				<div class="eaic-search-wrap">
					<svg class="eaic-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
					<input type="text" class="eaic-search-input" placeholder="<?php esc_attr_e( 'Search…', 'easyit-ai-chat' ); ?>" autocomplete="off" aria-label="<?php esc_attr_e( 'Search conversations', 'easyit-ai-chat' ); ?>">
				</div>
			</div>
			<div class="eaic-sessions-list"></div>
			<div class="eaic-sidebar-footer">
				<select class="eaic-provider-select" aria-label="<?php esc_attr_e( 'AI Provider', 'easyit-ai-chat' ); ?>">
					<?php foreach ( $providers as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $provider, $slug ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="eaic-main">
			<div class="eaic-topbar">
				<button class="eaic-toggle-sidebar" type="button" title="<?php esc_attr_e( 'Toggle sidebar', 'easyit-ai-chat' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
				</button>
				<span class="eaic-session-title"><?php echo esc_html( $title ); ?></span>
				<button class="eaic-delete-session-btn" type="button" title="<?php esc_attr_e( 'Delete conversation', 'easyit-ai-chat' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
				</button>
				<?php if ( ! empty( $opts['export_enabled'] ) ) : ?>
				<button class="eaic-export-btn" type="button" title="<?php esc_attr_e( 'Export conversation', 'easyit-ai-chat' ); ?>" aria-label="<?php esc_attr_e( 'Export conversation', 'easyit-ai-chat' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
				</button>
				<?php endif; ?>
				<button class="eaic-fullscreen-btn" type="button" title="<?php esc_attr_e( 'Fullscreen', 'easyit-ai-chat' ); ?>" aria-label="<?php esc_attr_e( 'Toggle fullscreen', 'easyit-ai-chat' ); ?>">
					<svg class="eaic-fs-expand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
					<svg class="eaic-fs-compress" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true" style="display:none"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="10" y1="14" x2="3" y2="21"/><line x1="21" y1="3" x2="14" y2="10"/></svg>
				</button>
			</div>

			<div class="eaic-messages" role="log" aria-live="polite">
				<div class="eaic-welcome">
					<div class="eaic-welcome-icon">🤖</div>
					<h3 class="eaic-welcome-title"><?php echo esc_html( $title ); ?></h3>
					<p class="eaic-welcome-sub"><?php esc_html_e( 'How can I help you today?', 'easyit-ai-chat' ); ?></p>
				</div>
			</div>

			<?php if ( ! empty( $opts['privacy_notice'] ) ) : ?>
				<div class="eaic-privacy">
					🔒 <?php esc_html_e( 'Conversations are saved. See our', 'easyit-ai-chat' ); ?>
					<a href="<?php echo esc_url( get_privacy_policy_url() ? get_privacy_policy_url() : '#' ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Privacy Policy', 'easyit-ai-chat' ); ?>
					</a>.
				</div>
			<?php endif; ?>

			<?php if ( $eaic_captcha ) : ?>
			<div class="eaic-captcha-bar">
				<span class="eaic-captcha-question"><?php echo esc_html( $eaic_captcha['question'] ); ?> = ?</span>
				<input type="number" class="eaic-captcha-input" placeholder="<?php esc_attr_e( 'Answer', 'easyit-ai-chat' ); ?>" min="0" max="99">
				<input type="hidden" class="eaic-captcha-token" value="<?php echo esc_attr( $eaic_captcha['token'] ); ?>">
				<span class="eaic-captcha-status"></span>
			</div>
			<?php endif; ?>

			<div class="eaic-input-area">
				<div class="eaic-input-wrap">
					<textarea class="eaic-input" rows="1" maxlength="<?php echo esc_attr( (string) max( 50, (int) $opts['max_message_length'] ) ); ?>"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						aria-label="<?php echo esc_attr( $placeholder ); ?>"></textarea>
					<?php if ( ! empty( $opts['voice_input_enabled'] ) ) : ?>
					<button class="eaic-mic-btn" type="button" style="display:none"
						title="<?php esc_attr_e( 'Voice input', 'easyit-ai-chat' ); ?>"
						aria-label="<?php esc_attr_e( 'Start voice input', 'easyit-ai-chat' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
					</button>
					<?php endif; ?>
					<button class="eaic-send-btn" type="button" disabled
						aria-label="<?php esc_attr_e( 'Send', 'easyit-ai-chat' ); ?>">
						<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
					</button>
				</div>
				<p class="eaic-hint"><?php esc_html_e( 'Enter to send · Shift+Enter for new line', 'easyit-ai-chat' ); ?></p>
			</div>
		</div>

	</div>
</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the simple floating chat widget in wp_footer.
	 *
	 * @return void
	 */
	public function render_floating_widget() {
		$opts = EAIC_Options::all();
		if ( empty( $opts['floating_widget_enabled'] ) ) {
			return;
		}

		$position = in_array( $opts['floating_widget_position'], array( 'bottom-right', 'bottom-left' ), true )
			? $opts['floating_widget_position'] : 'bottom-right';
		$label    = sanitize_text_field( $opts['floating_widget_label'] ?: 'Chat with us' );
		$accent   = $opts['color_accent'] ?: '#4f46e5';

		$chat_html = do_shortcode( '[eaic_chat height="400"]' );
		?>
<div class="eaic-floating-wrap eaic-floating-<?php echo esc_attr( $position ); ?>" style="--eaic-float-accent:<?php echo esc_attr( $accent ); ?>">
	<div class="eaic-float-panel" id="eaic-float-panel" aria-hidden="true">
		<div class="eaic-float-header">
			<span class="eaic-float-title"><?php echo esc_html( $label ); ?></span>
			<button type="button" class="eaic-float-close" aria-label="<?php esc_attr_e( 'Close chat', 'easyit-ai-chat' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
			</button>
		</div>
		<div class="eaic-float-body"><?php echo $chat_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	</div>
	<button type="button" class="eaic-float-btn" id="eaic-float-btn"
		aria-label="<?php echo esc_attr( $label ); ?>" aria-expanded="false">
		<svg class="eaic-float-icon-chat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
		<svg class="eaic-float-icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="24" height="24" aria-hidden="true" style="display:none"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
	</button>
</div>
		<?php
	}
}