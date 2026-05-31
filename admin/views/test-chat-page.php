<?php
/**
 * Admin view: Test Chat page.
 *
 * Variables in scope (provided by EAIC_Admin::render_test_chat()):
 *
 * @var array $eaic_opts Plugin options.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $eaic_opts ) || ! is_array( $eaic_opts ) ) {
	$eaic_opts = EAIC_Options::defaults();
}

$eaic_provider    = sanitize_key( $eaic_opts['default_provider'] );
$eaic_title       = sanitize_text_field( $eaic_opts['chat_title'] );
$eaic_placeholder = sanitize_text_field( $eaic_opts['placeholder_text'] );
$eaic_providers   = array(
	'ollama'    => 'Ollama',
	'openai'    => 'OpenAI',
	'anthropic' => 'Anthropic',
	'deepseek'  => 'DeepSeek',
	'gemini'    => 'Gemini',
);
?>
<div class="wrap eaic-test-page">

	<div class="eaic-test-hero">
		<div class="eaic-test-hero-left">
			<div class="eaic-test-hero-icon">💬</div>
			<div>
				<h1><?php esc_html_e( 'Test Chat', 'easyit-ai-chat' ); ?></h1>
				<p><?php esc_html_e( 'Test your AI providers directly from the dashboard', 'easyit-ai-chat' ); ?></p>
			</div>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=eaic' ) ); ?>" class="eaic-back-btn">
			← <?php esc_html_e( 'Settings', 'easyit-ai-chat' ); ?>
		</a>
	</div>

	<div class="eaic-test-chat-wrap">
		<div class="eaic-page-wrap">
			<div class="eaic-widget"
				data-provider="<?php echo esc_attr( $eaic_provider ); ?>"
				data-system-prompt="<?php echo esc_attr( $eaic_opts['system_prompt'] ); ?>">

				<div class="eaic-sidebar">
					<div class="eaic-sidebar-header">
						<button class="eaic-new-chat-btn" type="button">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
							<?php esc_html_e( 'New Chat', 'easyit-ai-chat' ); ?>
						</button>
					</div>
					<div class="eaic-sessions-list"></div>
					<div class="eaic-sidebar-footer">
						<select class="eaic-provider-select" aria-label="<?php esc_attr_e( 'AI Provider', 'easyit-ai-chat' ); ?>">
							<?php foreach ( $eaic_providers as $eaic_slug => $eaic_label ) : ?>
								<option value="<?php echo esc_attr( $eaic_slug ); ?>" <?php selected( $eaic_provider, $eaic_slug ); ?>>
									<?php echo esc_html( $eaic_label ); ?>
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
						<span class="eaic-session-title"><?php echo esc_html( $eaic_title ); ?></span>
						<button class="eaic-delete-session-btn" type="button" title="<?php esc_attr_e( 'Delete conversation', 'easyit-ai-chat' ); ?>">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
						</button>
					</div>

					<div class="eaic-messages" role="log" aria-live="polite">
						<div class="eaic-welcome">
							<div class="eaic-welcome-icon">🤖</div>
							<h3 class="eaic-welcome-title"><?php echo esc_html( $eaic_title ); ?></h3>
							<p class="eaic-welcome-sub"><?php esc_html_e( 'How can I help you today?', 'easyit-ai-chat' ); ?></p>
						</div>
					</div>

					<?php if ( ! empty( $eaic_opts['privacy_notice'] ) ) : ?>
						<div class="eaic-privacy">
							🔒 <?php esc_html_e( 'Conversations are saved. See our', 'easyit-ai-chat' ); ?>
							<a href="<?php echo esc_url( get_privacy_policy_url() ? get_privacy_policy_url() : '#' ); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Privacy Policy', 'easyit-ai-chat' ); ?>
							</a>.
						</div>
					<?php endif; ?>

					<div class="eaic-input-area">
						<div class="eaic-input-wrap">
							<textarea class="eaic-input" rows="1" maxlength="4000"
								placeholder="<?php echo esc_attr( $eaic_placeholder ); ?>"
								aria-label="<?php echo esc_attr( $eaic_placeholder ); ?>"></textarea>
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
	</div>

</div>
