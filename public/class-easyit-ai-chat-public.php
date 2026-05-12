<?php
/**
 * Public-facing shortcode and assets.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class EasyIT_AI_Chat_Public {

	private static bool $config_printed = false;

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_shortcode( 'easyit_ai_chat',  [ $this, 'render_shortcode' ] );
		add_filter( 'body_class',         [ $this, 'body_class' ] );
	}

	public function body_class( array $classes ): array {
		global $post;
		if ( $post && has_shortcode( $post->post_content, 'easyit_ai_chat' ) ) {
			$classes[] = 'eai-chat-page';
		}
		return $classes;
	}

	public function enqueue_assets(): void {
		wp_enqueue_style(  'easyit-ai-chat', EASYIT_AI_CHAT_URL . 'public/css/chat.css', [], EASYIT_AI_CHAT_VERSION );
		wp_enqueue_script( 'easyit-ai-chat', EASYIT_AI_CHAT_URL . 'public/js/chat.js',  [], EASYIT_AI_CHAT_VERSION, true );

		// Add page-level override CSS as inline style (proper WP way, avoids raw <style> tags).
		wp_add_inline_style( 'easyit-ai-chat', self::page_override_css() );
		$opts = EasyIT_AI_Chat_Options::all();
		wp_localize_script( 'easyit-ai-chat', 'EasyITAIChatConfig', [
			'ajax_url'            => admin_url( 'admin-ajax.php' ),
			'nonce'               => wp_create_nonce( 'easyit_ai_chat_nonce' ),
			'default_provider'    => esc_js( $opts['default_provider'] ),
			'show_provider_badge' => (bool) $opts['show_provider_badge'],
			'privacy_notice'      => (bool) $opts['privacy_notice'],
			'is_logged_in'        => is_user_logged_in(),
			'i18n'                => [
				'new_chat'       => __( 'New Chat',                                        'easyit-ai-chat' ),
				'thinking'       => __( 'Thinking…',                                       'easyit-ai-chat' ),
				'error_generic'  => __( 'Something went wrong. Please try again.',         'easyit-ai-chat' ),
				'error_empty'    => __( 'Please type a message first.',                    'easyit-ai-chat' ),
				'delete_confirm' => __( 'Delete this conversation?',                       'easyit-ai-chat' ),
				'privacy_text'   => __( 'Conversations are saved. See our Privacy Policy.','easyit-ai-chat' ),
				'copied'         => __( 'Copied!',                                         'easyit-ai-chat' ),
				'copy'           => __( 'Copy',                                            'easyit-ai-chat' ),
				'you'            => __( 'You',                                             'easyit-ai-chat' ),
				'ai'             => __( 'AI',                                              'easyit-ai-chat' ),
				'no_sessions'    => __( 'No conversations yet.',                           'easyit-ai-chat' ),
				'today'          => __( 'Today',                                           'easyit-ai-chat' ),
				'yesterday'      => __( 'Yesterday',                                       'easyit-ai-chat' ),
			],
		] );
	}

	public function render_shortcode( $atts ): string {
		$opts = EasyIT_AI_Chat_Options::all();
		$atts = shortcode_atts( [
			'provider'      => $opts['default_provider'],
			'title'         => $opts['chat_title'],
			'placeholder'   => $opts['placeholder_text'],
			'system_prompt' => $opts['system_prompt'],
			'height'        => 600,
		], $atts, 'easyit_ai_chat' );

		$provider      = sanitize_key( $atts['provider'] );
		$title         = sanitize_text_field( $atts['title'] );
		$placeholder   = sanitize_text_field( $atts['placeholder'] );
		$system_prompt = sanitize_textarea_field( $atts['system_prompt'] );
		$height        = max( 300, absint( $atts['height'] ) );

		$providers = [ 'ollama' => 'Ollama', 'openai' => 'OpenAI', 'anthropic' => 'Anthropic', 'deepseek' => 'DeepSeek' ];

		ob_start();

		// Page-override CSS is added via wp_add_inline_style() in enqueue_assets().
		?>
<div class="eai-page-wrap">
<div class="eai-widget"
	 data-provider="<?php echo esc_attr( $provider ); ?>"
	 data-system-prompt="<?php echo esc_attr( $system_prompt ); ?>"
	 style="--eai-msg-height:<?php echo esc_attr( $height ); ?>px">

	<!-- SIDEBAR -->
	<div class="eai-sidebar">
		<div class="eai-sidebar-header">
			<button class="eai-new-chat-btn" type="button">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
				<?php esc_html_e( 'New Chat', 'easyit-ai-chat' ); ?>
			</button>
		</div>
		<div class="eai-sessions-list"></div>
		<div class="eai-sidebar-footer">
			<select class="eai-provider-select">
				<?php foreach ( $providers as $slug => $label ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $provider, $slug ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div><!-- /.eai-sidebar -->

	<!-- MAIN PANEL -->
	<div class="eai-main">
		<div class="eai-topbar">
			<button class="eai-toggle-sidebar" type="button" title="<?php esc_attr_e( 'Toggle sidebar', 'easyit-ai-chat' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
			</button>
			<span class="eai-session-title"><?php echo esc_html( $title ); ?></span>
			<button class="eai-delete-session-btn" type="button" title="<?php esc_attr_e( 'Delete conversation', 'easyit-ai-chat' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
			</button>
		</div>

		<div class="eai-messages" role="log" aria-live="polite">
			<div class="eai-welcome">
				<div class="eai-welcome-icon">🤖</div>
				<h3 class="eai-welcome-title"><?php echo esc_html( $title ); ?></h3>
				<p class="eai-welcome-sub"><?php esc_html_e( 'How can I help you today?', 'easyit-ai-chat' ); ?></p>
			</div>
		</div>

		<?php if ( ! empty( $opts['privacy_notice'] ) ) : ?>
		<div class="eai-privacy">
			🔒 <?php esc_html_e( 'Conversations are saved. See our', 'easyit-ai-chat' ); ?>
			<a href="<?php echo esc_url( get_privacy_policy_url() ?: '#' ); ?>" target="_blank" rel="noopener">
				<?php esc_html_e( 'Privacy Policy', 'easyit-ai-chat' ); ?>
			</a>.
		</div>
		<?php endif; ?>

		<div class="eai-input-area">
			<div class="eai-input-wrap">
				<textarea class="eai-input" rows="1" maxlength="4000"
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
					aria-label="<?php echo esc_attr( $placeholder ); ?>"></textarea>
				<button class="eai-send-btn" type="button" disabled
					aria-label="<?php esc_attr_e( 'Send', 'easyit-ai-chat' ); ?>">
					<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
				</button>
			</div>
			<p class="eai-hint"><?php esc_html_e( 'Enter to send · Shift+Enter for new line', 'easyit-ai-chat' ); ?></p>
		</div>
	</div><!-- /.eai-main -->

</div><!-- /.eai-widget -->
</div><!-- /.eai-page-wrap -->
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns the CSS that overrides theme content-column constraints on chat pages.
	 * Delivered via wp_add_inline_style() — no raw <style> tags in shortcode output.
	 *
	 * @return string
	 */
	private static function page_override_css(): string {
		return '
			body.eai-chat-page .site-main,
			body.eai-chat-page .content-area,
			body.eai-chat-page main#main,
			body.eai-chat-page .entry-content,
			body.eai-chat-page .post-content,
			body.eai-chat-page .page-content,
			body.eai-chat-page article,
			body.eai-chat-page .hentry {
				max-width: 100% !important;
				width: 100% !important;
				padding-left: 0 !important;
				padding-right: 0 !important;
				float: none !important;
			}
			body.eai-chat-page .wp-site-blocks,
			body.eai-chat-page .is-layout-constrained > * {
				max-width: 100% !important;
				padding-left: 16px !important;
				padding-right: 16px !important;
			}
			body.eai-chat-page .entry-title,
			body.eai-chat-page .page-title,
			body.eai-chat-page h1.wp-block-post-title,
			body.eai-chat-page .page-header { display: none !important; }
		';
	}
}