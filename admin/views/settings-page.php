<?php
/**
 * Admin view: Settings page.
 * All styles live in admin/assets/admin.css — no inline CSS here.
 *
 * @package WPEasyAI
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! is_array( $opts ) ) $opts = WPEasyAI_Options::defaults();
?>
<div class="wrap weai-settings-wrap">

	<?php if ( isset( $_GET['settings-updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
	<div class="notice notice-success is-dismissible" style="border-radius:8px;margin-bottom:16px">
		<p>&#x2705; <strong><?php esc_html_e( 'Settings saved.', 'wpeasyai' ); ?></strong>
		<?php esc_html_e( 'Your configuration has been updated.', 'wpeasyai' ); ?></p>
	</div>
	<?php endif; ?>

	<div class="weai-hero">
		<div class="weai-hero-left">
			<div class="weai-hero-icon">🤖</div>
			<div>
				<div class="weai-hero-title"><?php esc_html_e( 'EasyIT AI Chat', 'wpeasyai' ); ?></div>
				<div class="weai-hero-sub"><?php esc_html_e( 'Unified AI chatbot — Ollama · OpenAI · Anthropic · DeepSeek', 'wpeasyai' ); ?></div>
			</div>
		</div>
		<div class="weai-hero-badge">v<?php echo esc_html( WPEASYAI_VERSION ); ?></div>
	</div>

	<form method="post" action="options.php">
		<?php settings_fields( 'wpeasyai_group' ); ?>

		<div class="weai-settings-layout">
		<div class="weai-settings-main">

			<div class="weai-tab-nav">
				<button type="button" class="weai-tab-btn active" data-tab="ollama"><span class="weai-tab-icon">🦙</span> <?php esc_html_e( 'Ollama', 'wpeasyai' ); ?></button>
				<button type="button" class="weai-tab-btn" data-tab="openai"><span class="weai-tab-icon">✨</span> <?php esc_html_e( 'OpenAI', 'wpeasyai' ); ?></button>
				<button type="button" class="weai-tab-btn" data-tab="anthropic"><span class="weai-tab-icon">🎭</span> <?php esc_html_e( 'Anthropic', 'wpeasyai' ); ?></button>
				<button type="button" class="weai-tab-btn" data-tab="deepseek"><span class="weai-tab-icon">🔍</span> <?php esc_html_e( 'DeepSeek', 'wpeasyai' ); ?></button>
				<button type="button" class="weai-tab-btn" data-tab="general"><span class="weai-tab-icon">⚙️</span> <?php esc_html_e( 'General', 'wpeasyai' ); ?></button>
				<button type="button" class="weai-tab-btn" data-tab="ui"><span class="weai-tab-icon">🎨</span> <?php esc_html_e( 'UI', 'wpeasyai' ); ?></button>
			</div>

			<!-- ── OLLAMA ── -->
			<div class="weai-panel active" id="weai-panel-ollama">
			<div class="weai-card">
				<div class="weai-card-title"><span class="icon">🦙</span> <?php esc_html_e( 'Ollama — Self-Hosted, Free', 'wpeasyai' ); ?></div>

				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'Default Provider', 'wpeasyai' ); ?></label>
					<select name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[default_provider]">
						<?php foreach ( [ 'ollama' => '🦙 Ollama', 'openai' => '✨ OpenAI', 'anthropic' => '🎭 Anthropic', 'deepseek' => '🔍 DeepSeek' ] as $k => $v ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $opts['default_provider'], $k ); ?>><?php echo esc_html( $v ); ?></option>
						<?php endforeach; ?>
					</select>
					<div class="weai-field-desc"><?php esc_html_e( 'Used when no provider is specified in the shortcode.', 'wpeasyai' ); ?></div>
				</div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'Ollama Server URL', 'wpeasyai' ); ?></label>
					<input type="url" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[ollama_url]" value="<?php echo esc_attr( $opts['ollama_url'] ); ?>" placeholder="http://localhost:11434">
					<div class="weai-field-desc"><?php esc_html_e( 'e.g.', 'wpeasyai' ); ?> <code>http://localhost:11434</code></div>
				</div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'Model', 'wpeasyai' ); ?></label>
					<input type="text" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[ollama_model]" value="<?php echo esc_attr( $opts['ollama_model'] ); ?>" placeholder="qwen2:1.5b">
					<div class="weai-field-desc"><?php esc_html_e( 'e.g.', 'wpeasyai' ); ?> <code>qwen2:1.5b</code>, <code>llama3</code>, <code>mistral</code></div>
				</div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'Timeout (seconds)', 'wpeasyai' ); ?></label>
					<input type="number" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[ollama_timeout]" value="<?php echo (int) $opts['ollama_timeout']; ?>" min="10" max="300">
					<div class="weai-field-desc"><?php esc_html_e( 'Increase for large models or slow hardware.', 'wpeasyai' ); ?></div>
				</div>
				<div class="weai-test-row">
					<button type="button" class="weai-test-btn-styled weai-test-btn" data-provider="ollama">🔌 <?php esc_html_e( 'Test Connection', 'wpeasyai' ); ?></button>
					<span class="weai-test-result" id="weai-test-ollama"></span>
				</div>
			</div>
			</div>

			<!-- ── OPENAI ── -->
			<div class="weai-panel" id="weai-panel-openai">
			<div class="weai-card">
				<div class="weai-card-title"><span class="icon">✨</span> <?php esc_html_e( 'OpenAI', 'wpeasyai' ); ?></div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'API Key', 'wpeasyai' ); ?> <span class="req">*</span></label>
					<input type="password" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[openai_key]" value="<?php echo esc_attr( $opts['openai_key'] ); ?>" placeholder="sk-...">
					<div class="weai-field-desc"><?php esc_html_e( 'Get your key at', 'wpeasyai' ); ?> <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">platform.openai.com</a></div>
				</div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'Model', 'wpeasyai' ); ?></label>
					<input type="text" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[openai_model]" value="<?php echo esc_attr( $opts['openai_model'] ); ?>" placeholder="gpt-3.5-turbo">
					<div class="weai-field-desc"><?php esc_html_e( 'e.g.', 'wpeasyai' ); ?> <code>gpt-3.5-turbo</code>, <code>gpt-4o-mini</code>, <code>gpt-4o</code></div>
				</div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'Timeout (seconds)', 'wpeasyai' ); ?></label>
					<input type="number" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[openai_timeout]" value="<?php echo (int) $opts['openai_timeout']; ?>" min="10" max="120">
				</div>
				<div class="weai-test-row">
					<button type="button" class="weai-test-btn-styled weai-test-btn" data-provider="openai">🔌 <?php esc_html_e( 'Test Connection', 'wpeasyai' ); ?></button>
					<span class="weai-test-result" id="weai-test-openai"></span>
				</div>
			</div>
			</div>

			<!-- ── ANTHROPIC ── -->
			<div class="weai-panel" id="weai-panel-anthropic">
			<div class="weai-card">
				<div class="weai-card-title"><span class="icon">🎭</span> <?php esc_html_e( 'Anthropic (Claude)', 'wpeasyai' ); ?></div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'API Key', 'wpeasyai' ); ?> <span class="req">*</span></label>
					<input type="password" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[anthropic_key]" value="<?php echo esc_attr( $opts['anthropic_key'] ); ?>" placeholder="sk-ant-...">
					<div class="weai-field-desc"><?php esc_html_e( 'Get your key at', 'wpeasyai' ); ?> <a href="https://console.anthropic.com/" target="_blank" rel="noopener noreferrer">console.anthropic.com</a></div>
				</div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'Model', 'wpeasyai' ); ?></label>
					<input type="text" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[anthropic_model]" value="<?php echo esc_attr( $opts['anthropic_model'] ); ?>" placeholder="claude-3-haiku-20240307">
					<div class="weai-field-desc"><?php esc_html_e( 'e.g.', 'wpeasyai' ); ?> <code>claude-3-haiku-20240307</code>, <code>claude-3-5-sonnet-20241022</code></div>
				</div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'Timeout (seconds)', 'wpeasyai' ); ?></label>
					<input type="number" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[anthropic_timeout]" value="<?php echo (int) $opts['anthropic_timeout']; ?>" min="10" max="120">
				</div>
				<div class="weai-test-row">
					<button type="button" class="weai-test-btn-styled weai-test-btn" data-provider="anthropic">🔌 <?php esc_html_e( 'Test Connection', 'wpeasyai' ); ?></button>
					<span class="weai-test-result" id="weai-test-anthropic"></span>
				</div>
			</div>
			</div>

			<!-- ── DEEPSEEK ── -->
			<div class="weai-panel" id="weai-panel-deepseek">
			<div class="weai-card">
				<div class="weai-card-title"><span class="icon">🔍</span> <?php esc_html_e( 'DeepSeek', 'wpeasyai' ); ?></div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'API Key', 'wpeasyai' ); ?> <span class="req">*</span></label>
					<input type="password" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[deepseek_key]" value="<?php echo esc_attr( $opts['deepseek_key'] ); ?>" placeholder="sk-...">
					<div class="weai-field-desc"><?php esc_html_e( 'Get your key at', 'wpeasyai' ); ?> <a href="https://platform.deepseek.com/" target="_blank" rel="noopener noreferrer">platform.deepseek.com</a></div>
				</div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'Model', 'wpeasyai' ); ?></label>
					<input type="text" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[deepseek_model]" value="<?php echo esc_attr( $opts['deepseek_model'] ); ?>" placeholder="deepseek-chat">
					<div class="weai-field-desc"><?php esc_html_e( 'e.g.', 'wpeasyai' ); ?> <code>deepseek-chat</code>, <code>deepseek-reasoner</code></div>
				</div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'Timeout (seconds)', 'wpeasyai' ); ?></label>
					<input type="number" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[deepseek_timeout]" value="<?php echo (int) $opts['deepseek_timeout']; ?>" min="10" max="120">
				</div>
				<div class="weai-test-row">
					<button type="button" class="weai-test-btn-styled weai-test-btn" data-provider="deepseek">🔌 <?php esc_html_e( 'Test Connection', 'wpeasyai' ); ?></button>
					<span class="weai-test-result" id="weai-test-deepseek"></span>
				</div>
			</div>
			</div>

			<!-- ── GENERAL ── -->
			<div class="weai-panel" id="weai-panel-general">
			<div class="weai-card">
				<div class="weai-card-title"><span class="icon">⚙️</span> <?php esc_html_e( 'General Settings', 'wpeasyai' ); ?></div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'System Prompt', 'wpeasyai' ); ?></label>
					<textarea name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[system_prompt]" rows="4" style="resize:vertical"><?php echo esc_textarea( $opts['system_prompt'] ); ?></textarea>
					<div class="weai-field-desc"><?php esc_html_e( 'Default AI persona. Can be overridden per shortcode.', 'wpeasyai' ); ?></div>
				</div>
				<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'Temperature', 'wpeasyai' ); ?></label>
					<input type="number" step="0.1" min="0" max="2" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[temperature]" value="<?php echo esc_attr( (string) (float) $opts['temperature'] ); ?>">
					<div class="weai-field-desc"><?php esc_html_e( '0 = focused · 1 = balanced · 2 = creative', 'wpeasyai' ); ?></div>
				</div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'Max Tokens', 'wpeasyai' ); ?></label>
					<input type="number" min="100" max="8000" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[max_tokens]" value="<?php echo (int) $opts['max_tokens']; ?>">
					<div class="weai-field-desc"><?php esc_html_e( 'Max response length (100–8000)', 'wpeasyai' ); ?></div>
				</div>
				</div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'Data Retention (days)', 'wpeasyai' ); ?></label>
					<input type="number" min="1" max="3650" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[data_retention_days]" value="<?php echo (int) $opts['data_retention_days']; ?>">
					<div class="weai-field-desc"><?php esc_html_e( 'Auto-delete conversations older than this many days.', 'wpeasyai' ); ?></div>
				</div>
			</div>
			<div class="weai-card">
				<div class="weai-card-title"><span class="icon">🔐</span> <?php esc_html_e( 'Access & Privacy', 'wpeasyai' ); ?></div>
				<label class="weai-check-row">
					<input type="checkbox" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[allow_guest_chat]" value="1" <?php checked( $opts['allow_guest_chat'] ); ?>>
					<div>
						<div class="weai-check-label"><?php esc_html_e( 'Allow Guest Chat', 'wpeasyai' ); ?></div>
						<div class="weai-check-desc"><?php esc_html_e( 'Non-logged-in visitors can use the chatbot. Uses cookies to track sessions.', 'wpeasyai' ); ?></div>
					</div>
				</label>
				<label class="weai-check-row">
					<input type="checkbox" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[privacy_notice]" value="1" <?php checked( $opts['privacy_notice'] ); ?>>
					<div>
						<div class="weai-check-label"><?php esc_html_e( 'Show Privacy Notice', 'wpeasyai' ); ?></div>
						<div class="weai-check-desc"><?php esc_html_e( 'Display a "Conversations are saved" notice with link to your Privacy Policy.', 'wpeasyai' ); ?></div>
					</div>
				</label>
			</div>
			</div>

			<!-- ── UI ── -->
			<div class="weai-panel" id="weai-panel-ui">
			<div class="weai-card">
				<div class="weai-card-title"><span class="icon">🎨</span> <?php esc_html_e( 'UI Customisation', 'wpeasyai' ); ?></div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'Chat Widget Title', 'wpeasyai' ); ?></label>
					<input type="text" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[chat_title]" value="<?php echo esc_attr( $opts['chat_title'] ); ?>" placeholder="AI Chat">
				</div>
				<div class="weai-field">
					<label class="weai-label"><?php esc_html_e( 'Input Placeholder', 'wpeasyai' ); ?></label>
					<input type="text" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[placeholder_text]" value="<?php echo esc_attr( $opts['placeholder_text'] ); ?>" placeholder="Ask me anything&hellip;">
				</div>
				<label class="weai-check-row">
					<input type="checkbox" name="<?php echo esc_attr( WPEasyAI_Options::OPTION_KEY ); ?>[show_provider_badge]" value="1" <?php checked( $opts['show_provider_badge'] ); ?>>
					<div>
						<div class="weai-check-label"><?php esc_html_e( 'Show Provider Badge', 'wpeasyai' ); ?></div>
						<div class="weai-check-desc"><?php esc_html_e( 'Display provider name and model below each AI response.', 'wpeasyai' ); ?></div>
					</div>
				</label>
			</div>
			</div>

			<div style="margin-top:8px">
				<button type="submit" class="weai-save-btn">💾 <?php esc_html_e( 'Save Settings', 'wpeasyai' ); ?></button>
			</div>

		</div><!-- /.weai-settings-main -->

		<div class="weai-settings-aside">
			<div class="weai-shortcode-card">
				<h4>📋 <?php esc_html_e( 'Shortcodes', 'wpeasyai' ); ?></h4>
				<div class="weai-sc-item">[easyai]</div>
				<div class="weai-sc-item">[easyai provider="openai"]</div>
				<div class="weai-sc-item">[easyai provider="anthropic" height="600"]</div>
				<div class="weai-sc-item">[easyai title="Custom Title"]</div>
			</div>
			<div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin-bottom:14px">
				<div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:10px">🔗 <?php esc_html_e( 'Quick Links', 'wpeasyai' ); ?></div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpeasyai-test-chat' ) ); ?>" style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:8px">
					💬 <?php esc_html_e( 'Test Chat', 'wpeasyai' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpeasyai' ) ); ?>" style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:#f3f4f6;color:#374151;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500">
					⚙️ <?php esc_html_e( 'Settings', 'wpeasyai' ); ?>
				</a>
			</div>
			<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:14px">
				<div style="font-size:12px;font-weight:700;color:#92400e;margin-bottom:6px">💡 <?php esc_html_e( 'Tips', 'wpeasyai' ); ?></div>
				<div style="font-size:12px;color:#78350f;line-height:1.6">
					&bull; <?php esc_html_e( 'Use provider="ollama" on any shortcode to override the default.', 'wpeasyai' ); ?><br>
					&bull; <?php esc_html_e( 'Set a system prompt to give your AI a persona.', 'wpeasyai' ); ?><br>
					&bull; <?php esc_html_e( 'Ollama is free — just install it locally.', 'wpeasyai' ); ?>
				</div>
			</div>
		</div><!-- /.weai-settings-aside -->
		</div><!-- /.weai-settings-layout -->

	</form>
</div>

<script>
(function () {
	var tabs   = document.querySelectorAll('.weai-tab-btn');
	var panels = document.querySelectorAll('.weai-panel');
	tabs.forEach(function (btn) {
		btn.addEventListener('click', function () {
			var tab = btn.dataset.tab;
			tabs.forEach(function (b) { b.classList.remove('active'); });
			panels.forEach(function (p) { p.classList.remove('active'); });
			btn.classList.add('active');
			var panel = document.getElementById('weai-panel-' + tab);
			if (panel) panel.classList.add('active');
			if (history.replaceState) history.replaceState(null, '', '#' + tab);
		});
	});
	var hash = location.hash.replace('#', '');
	if (hash) {
		var btn = document.querySelector('.weai-tab-btn[data-tab="' + hash + '"]');
		if (btn) btn.click();
	}
	document.querySelectorAll('.weai-sc-item').forEach(function (el) {
		el.addEventListener('click', function () {
			var text = el.textContent.trim();
			if (navigator.clipboard) {
				navigator.clipboard.writeText(text).then(function () {
					var orig = el.textContent;
					el.textContent = '\u2705 Copied!';
					setTimeout(function () { el.textContent = orig; }, 1400);
				});
			}
		});
	});
}());
</script>
