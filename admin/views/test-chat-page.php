<?php
/**
 * Admin view: Test Chat page.
 *
 * Renders the widget HTML directly using the public class render method.
 *
 * @package WPEasyAI
 * @since   1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! is_array( $opts ) ) $opts = WPEasyAI_Options::defaults();

$provider    = sanitize_key( $opts['default_provider'] );
$title       = sanitize_text_field( $opts['chat_title'] );
$placeholder = sanitize_text_field( $opts['placeholder_text'] );
$providers   = [
	'ollama'    => 'Ollama',
	'openai'    => 'OpenAI',
	'anthropic' => 'Anthropic',
	'deepseek'  => 'DeepSeek',
];
?>
<div class="wrap weai-test-page">

	<div class="weai-test-hero">
		<div class="weai-test-hero-left">
			<div class="weai-test-hero-icon">💬</div>
			<div>
				<h1><?php esc_html_e( 'Test Chat', 'wpeasyai' ); ?></h1>
				<p><?php esc_html_e( 'Test your AI providers directly from the dashboard', 'wpeasyai' ); ?></p>
			</div>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpeasyai' ) ); ?>" class="weai-back-btn">
			&larr; <?php esc_html_e( 'Settings', 'wpeasyai' ); ?>
		</a>
	</div>

	<div class="weai-test-chat-wrap">
	<div class="weai-page-wrap">
	<div class="weai-widget"
		data-provider="<?php echo esc_attr( $provider ); ?>"
		data-system-prompt="<?php echo esc_attr( $opts['system_prompt'] ); ?>"
		style="--weai-msg-height:520px">

		<div class="weai-sidebar">
			<div class="weai-sidebar-header">
				<button class="weai-new-chat-btn" type="button">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
					<?php esc_html_e( 'New Chat', 'wpeasyai' ); ?>
				</button>
			</div>
			<div class="weai-sessions-list"></div>
			<div class="weai-sidebar-footer">
				<select class="weai-provider-select">
					<?php foreach ( $providers as $slug => $label ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $provider, $slug ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="weai-main">
			<div class="weai-topbar">
				<button class="weai-toggle-sidebar" type="button" title="<?php esc_attr_e( 'Toggle sidebar', 'wpeasyai' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
				</button>
				<span class="weai-session-title"><?php echo esc_html( $title ); ?></span>
				<button class="weai-delete-session-btn" type="button" title="<?php esc_attr_e( 'Delete conversation', 'wpeasyai' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
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
						<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
					</button>
				</div>
				<p class="weai-hint"><?php esc_html_e( 'Enter to send · Shift+Enter for new line', 'wpeasyai' ); ?></p>
			</div>
		</div>

	</div>
	</div>
	</div>

</div>