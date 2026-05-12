<?php
/**
 * Public-facing shortcode and assets.
 *
 * @package WPEasyAI
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEasyAI_Public {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_shortcode( 'easyai', [ $this, 'render_shortcode' ] );
		add_filter( 'body_class', [ $this, 'body_class' ] );
	}

	public function body_class( array $classes ): array {
		global $post;
		if ( $post && has_shortcode( $post->post_content, 'easyai' ) ) {
			$classes[] = 'weai-chat-page';
		}
		return $classes;
	}

	public function enqueue_assets(): void {
		wp_enqueue_style(  'wpeasyai', WPEASYAI_URL . 'public/css/chat.css', [], WPEASYAI_VERSION );
		wp_enqueue_script( 'wpeasyai', WPEASYAI_URL . 'public/js/chat.js',  [], WPEASYAI_VERSION, true );
		wp_add_inline_style( 'wpeasyai', self::page_override_css() );

		$opts = WPEasyAI_Options::all();
		wp_localize_script( 'wpeasyai', 'WPEasyAIConfig', [
			'ajax_url'            => admin_url( 'admin-ajax.php' ),
			'nonce'               => wp_create_nonce( 'wpeasyai_nonce' ),
			'default_provider'    => esc_js( $opts['default_provider'] ),
			'show_provider_badge' => (bool) $opts['show_provider_badge'],
			'privacy_notice'      => (bool) $opts['privacy_notice'],
			'is_logged_in'        => is_user_logged_in(),
			'i18n'                => [
				'new_chat'       => __( 'New Chat',                                         'wpeasyai' ),
				'thinking'       => __( 'Thinking\u2026',                                   'wpeasyai' ),
				'error_generic'  => __( 'Something went wrong. Please try again.',          'wpeasyai' ),
				'error_empty'    => __( 'Please type a message first.',                     'wpeasyai' ),
				'delete_confirm' => __( 'Delete this conversation?',                        'wpeasyai' ),
				'privacy_text'   => __( 'Conversations are saved. See our Privacy Policy.', 'wpeasyai' ),
				'copied'         => __( 'Copied!',                                          'wpeasyai' ),
				'copy'           => __( 'Copy',                                             'wpeasyai' ),
				'you'            => __( 'You',                                              'wpeasyai' ),
				'ai'             => __( 'AI',                                               'wpeasyai' ),
				'no_sessions'    => __( 'No conversations yet.',                            'wpeasyai' ),
				'today'          => __( 'Today',                                            'wpeasyai' ),
				'yesterday'      => __( 'Yesterday',                                        'wpeasyai' ),
			],
		] );
	}

	public function render_shortcode( $atts ): string {
		$opts = WPEasyAI_Options::all();
		$atts = shortcode_atts( [
			'provider'      => $opts['default_provider'],
			'title'         => $opts['chat_title'],
			'placeholder'   => $opts['placeholder_text'],
			'system_prompt' => $opts['system_prompt'],
			'height'        => 600,
		], $atts, 'easyai' );

		$provider      = sanitize_key( $atts['provider'] );
		$title         = sanitize_text_field( $atts['title'] );
		$placeholder   = sanitize_text_field( $atts['placeholder'] );
		$system_prompt = sanitize_textarea_field( $atts['system_prompt'] );
		$height        = max( 300, absint( $atts['height'] ) );

		$providers = [
			'ollama'    => 'Ollama',
			'openai'    => 'OpenAI',
			'anthropic' => 'Anthropic',
			'deepseek'  => 'DeepSeek',
		];

		ob_start();
		?>
<div class="weai-page-wrap">
<div class="weai-widget"
	data-provider="<?php echo esc_attr( $provider ); ?>"
	data-system-prompt="<?php echo esc_attr( $system_prompt ); ?>"
	style="--weai-msg-height:<?php echo esc_attr( $height ); ?>px">

	<div class="weai-sidebar">
		<div class="weai-sidebar-header">
			<button class="weai-new-chat-btn" type="button">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
				<?php esc_html_e( 'New Chat', 'wpeasyai' ); ?>
			</button>
		</div>
		<div class="weai-sessions-list"></div>
		<div class="weai-sidebar-footer">
			<select class="weai-provider-select">
				<?php foreach ( $providers as $slug => $label ) : ?>
				<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $provider, $slug ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<div class="weai-main">
		<div class="weai-topbar">
			<button class="weai-toggle-sidebar" type="button" title="<?php esc_attr_e( 'Toggle sidebar', 'wpeasyai' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
			</button>
			<span class="weai-session-title"><?php echo esc_html( $title ); ?></span>
			<button class="weai-delete-session-btn" type="button" title="<?php esc_attr_e( 'Delete conversation', 'wpeasyai' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
			</button>
		</div>

		<div class="weai-messages" role="log" aria-live="polite">
			<div class="weai-welcome">
				<div class="weai-welcome-icon">🤖</div>
				<h3 class="weai-welcome-title"><?php echo esc_html( $title ); ?></h3>
				<p class="weai-welcome-sub"><?php esc_html_e( 'How can I help you today?', 'wpeasyai' ); ?></p>
			</div>
		</div>

		<?php if ( ! empty( $opts['privacy_notice'] ) ) : ?>
		<div class="weai-privacy">
			🔒 <?php esc_html_e( 'Conversations are saved. See our', 'wpeasyai' ); ?>
			<a href="<?php echo esc_url( get_privacy_policy_url() ?: '#' ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Privacy Policy', 'wpeasyai' ); ?>
			</a>.
		</div>
		<?php endif; ?>

		<div class="weai-input-area">
			<div class="weai-input-wrap">
				<textarea class="weai-input" rows="1" maxlength="4000"
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
					aria-label="<?php echo esc_attr( $placeholder ); ?>"></textarea>
				<button class="weai-send-btn" type="button" disabled
					aria-label="<?php esc_attr_e( 'Send', 'wpeasyai' ); ?>">
					<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
				</button>
			</div>
			<p class="weai-hint"><?php esc_html_e( 'Enter to send · Shift+Enter for new line', 'wpeasyai' ); ?></p>
		</div>
	</div>

</div>
</div>
		<?php
		return ob_get_clean();
	}

	private static function page_override_css(): string {
		return '
			body.weai-chat-page .site-main,
			body.weai-chat-page .content-area,
			body.weai-chat-page main#main,
			body.weai-chat-page .entry-content,
			body.weai-chat-page .post-content,
			body.weai-chat-page .page-content,
			body.weai-chat-page article,
			body.weai-chat-page .hentry {
				max-width: 100% !important;
				width: 100% !important;
				padding-left: 0 !important;
				padding-right: 0 !important;
				float: none !important;
			}
			body.weai-chat-page .wp-site-blocks,
			body.weai-chat-page .is-layout-constrained > * {
				max-width: 100% !important;
				padding-left: 16px !important;
				padding-right: 16px !important;
			}
			body.weai-chat-page .entry-title,
			body.weai-chat-page .page-title,
			body.weai-chat-page h1.wp-block-post-title,
			body.weai-chat-page .page-header { display: none !important; }
		';
	}
}
