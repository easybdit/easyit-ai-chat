<?php
/**
 * Admin view: Settings page.
 *
 * Variables in scope (provided by EAIC_Admin::render_settings()):
 *
 * @var array $eaic_opts Plugin options merged with defaults.
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

$eaic_provider_map = array(
	'ollama'    => '🦙 Ollama',
	'openai'    => '✨ OpenAI',
	'anthropic' => '🎭 Anthropic',
	'deepseek'  => '🔍 DeepSeek',
	'gemini'    => '✦ Gemini',
);

$eaic_option_key = EAIC_Options::OPTION_KEY;
?>
<div class="wrap eaic-settings-wrap">

	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- "settings-updated" is set by core after options.php saves; no action is taken on it.
	if ( isset( $_GET['settings-updated'] ) ) :
		?>
	<div class="notice notice-success is-dismissible">
		<p>✅ <strong><?php esc_html_e( 'Settings saved.', 'easyit-ai-chat' ); ?></strong>
		<?php esc_html_e( 'Your configuration has been updated.', 'easyit-ai-chat' ); ?></p>
	</div>
	<?php endif; ?>

	<div class="eaic-hero">
		<div class="eaic-hero-left">
			<div class="eaic-hero-icon">🤖</div>
			<div>
				<div class="eaic-hero-title"><?php esc_html_e( 'EasyIT AI Chat', 'easyit-ai-chat' ); ?></div>
				<div class="eaic-hero-sub"><?php esc_html_e( 'Unified AI chatbot — Ollama · OpenAI · Anthropic · DeepSeek · Gemini', 'easyit-ai-chat' ); ?></div>
			</div>
		</div>
		<div class="eaic-hero-badge">v<?php echo esc_html( EAIC_VERSION ); ?></div>
	</div>

	<form method="post" action="options.php">
		<?php settings_fields( 'eaic_group' ); ?>

		<div class="eaic-settings-layout">
		<div class="eaic-settings-main">

			<div class="eaic-tab-nav">
				<button type="button" class="eaic-tab-btn active" data-tab="ollama"><span class="eaic-tab-icon">🦙</span> <?php esc_html_e( 'Ollama', 'easyit-ai-chat' ); ?></button>
				<button type="button" class="eaic-tab-btn" data-tab="openai"><span class="eaic-tab-icon">✨</span> <?php esc_html_e( 'OpenAI', 'easyit-ai-chat' ); ?></button>
				<button type="button" class="eaic-tab-btn" data-tab="anthropic"><span class="eaic-tab-icon">🎭</span> <?php esc_html_e( 'Anthropic', 'easyit-ai-chat' ); ?></button>
				<button type="button" class="eaic-tab-btn" data-tab="deepseek"><span class="eaic-tab-icon">🔍</span> <?php esc_html_e( 'DeepSeek', 'easyit-ai-chat' ); ?></button>
				<button type="button" class="eaic-tab-btn" data-tab="gemini"><span class="eaic-tab-icon">✦</span> <?php esc_html_e( 'Gemini', 'easyit-ai-chat' ); ?></button>
				<button type="button" class="eaic-tab-btn" data-tab="general"><span class="eaic-tab-icon">⚙️</span> <?php esc_html_e( 'General', 'easyit-ai-chat' ); ?></button>
				<button type="button" class="eaic-tab-btn" data-tab="ui"><span class="eaic-tab-icon">🎨</span> <?php esc_html_e( 'UI', 'easyit-ai-chat' ); ?></button>
				<button type="button" class="eaic-tab-btn" data-tab="security"><span class="eaic-tab-icon">🔒</span> <?php esc_html_e( 'Security', 'easyit-ai-chat' ); ?></button>
			</div>

			<!-- OLLAMA -->
			<div class="eaic-panel active" id="eaic-panel-ollama">
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🦙</span> <?php esc_html_e( 'Ollama — Self-Hosted, Free', 'easyit-ai-chat' ); ?></div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-default-provider"><?php esc_html_e( 'Default Provider', 'easyit-ai-chat' ); ?></label>
						<select id="eaic-default-provider" name="<?php echo esc_attr( $eaic_option_key ); ?>[default_provider]">
							<?php foreach ( $eaic_provider_map as $eaic_slug => $eaic_label ) : ?>
								<option value="<?php echo esc_attr( $eaic_slug ); ?>" <?php selected( $eaic_opts['default_provider'], $eaic_slug ); ?>><?php echo esc_html( $eaic_label ); ?></option>
							<?php endforeach; ?>
						</select>
						<div class="eaic-field-desc"><?php esc_html_e( 'Used when no provider is specified in the shortcode.', 'easyit-ai-chat' ); ?></div>
					</div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-ollama-url"><?php esc_html_e( 'Ollama Server URL', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-ollama-url" type="url" name="<?php echo esc_attr( $eaic_option_key ); ?>[ollama_url]" value="<?php echo esc_attr( $eaic_opts['ollama_url'] ); ?>" placeholder="http://localhost:11434">
						<div class="eaic-field-desc"><?php esc_html_e( 'e.g.', 'easyit-ai-chat' ); ?> <code>http://localhost:11434</code></div>
					</div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-ollama-model"><?php esc_html_e( 'Model', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-ollama-model" type="text" name="<?php echo esc_attr( $eaic_option_key ); ?>[ollama_model]" value="<?php echo esc_attr( $eaic_opts['ollama_model'] ); ?>" placeholder="qwen2:1.5b">
						<div class="eaic-field-desc"><?php esc_html_e( 'e.g.', 'easyit-ai-chat' ); ?> <code>qwen2:1.5b</code>, <code>llama3</code>, <code>mistral</code></div>
					</div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-ollama-timeout"><?php esc_html_e( 'Timeout (seconds)', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-ollama-timeout" type="number" name="<?php echo esc_attr( $eaic_option_key ); ?>[ollama_timeout]" value="<?php echo (int) $eaic_opts['ollama_timeout']; ?>" min="10" max="300">
						<div class="eaic-field-desc"><?php esc_html_e( 'Increase for large models or slow hardware.', 'easyit-ai-chat' ); ?></div>
					</div>

					<div class="eaic-test-row">
						<button type="button" class="eaic-test-btn-styled eaic-test-btn" data-provider="ollama">🔌 <?php esc_html_e( 'Test Connection', 'easyit-ai-chat' ); ?></button>
						<span class="eaic-test-result" id="eaic-test-ollama"></span>
					</div>
				</div>
			</div>

			<!-- OPENAI -->
			<div class="eaic-panel" id="eaic-panel-openai">
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">✨</span> <?php esc_html_e( 'OpenAI', 'easyit-ai-chat' ); ?></div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-openai-key"><?php esc_html_e( 'API Key', 'easyit-ai-chat' ); ?> <span class="req">*</span></label>
						<input id="eaic-openai-key" type="password" name="<?php echo esc_attr( $eaic_option_key ); ?>[openai_key]" value="<?php echo esc_attr( $eaic_opts['openai_key'] ); ?>" placeholder="sk-...">
						<div class="eaic-field-desc"><?php esc_html_e( 'Get your key at', 'easyit-ai-chat' ); ?> <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">platform.openai.com</a></div>
					</div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-openai-model"><?php esc_html_e( 'Model', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-openai-model" type="text" name="<?php echo esc_attr( $eaic_option_key ); ?>[openai_model]" value="<?php echo esc_attr( $eaic_opts['openai_model'] ); ?>" placeholder="gpt-4o-mini">
						<div class="eaic-field-desc"><?php esc_html_e( 'e.g.', 'easyit-ai-chat' ); ?> <code>gpt-4o-mini</code>, <code>gpt-4o</code>, <code>gpt-4.1</code>, <code>o3</code>, <code>o4-mini</code></div>
					</div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-openai-timeout"><?php esc_html_e( 'Timeout (seconds)', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-openai-timeout" type="number" name="<?php echo esc_attr( $eaic_option_key ); ?>[openai_timeout]" value="<?php echo (int) $eaic_opts['openai_timeout']; ?>" min="10" max="120">
					</div>

					<div class="eaic-test-row">
						<button type="button" class="eaic-test-btn-styled eaic-test-btn" data-provider="openai">🔌 <?php esc_html_e( 'Test Connection', 'easyit-ai-chat' ); ?></button>
						<span class="eaic-test-result" id="eaic-test-openai"></span>
					</div>
				</div>
			</div>

			<!-- ANTHROPIC -->
			<div class="eaic-panel" id="eaic-panel-anthropic">
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🎭</span> <?php esc_html_e( 'Anthropic (Claude)', 'easyit-ai-chat' ); ?></div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-anthropic-key"><?php esc_html_e( 'API Key', 'easyit-ai-chat' ); ?> <span class="req">*</span></label>
						<input id="eaic-anthropic-key" type="password" name="<?php echo esc_attr( $eaic_option_key ); ?>[anthropic_key]" value="<?php echo esc_attr( $eaic_opts['anthropic_key'] ); ?>" placeholder="sk-ant-...">
						<div class="eaic-field-desc"><?php esc_html_e( 'Get your key at', 'easyit-ai-chat' ); ?> <a href="https://console.anthropic.com/" target="_blank" rel="noopener noreferrer">console.anthropic.com</a></div>
					</div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-anthropic-model"><?php esc_html_e( 'Model', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-anthropic-model" type="text" name="<?php echo esc_attr( $eaic_option_key ); ?>[anthropic_model]" value="<?php echo esc_attr( $eaic_opts['anthropic_model'] ); ?>" placeholder="claude-3-5-haiku-20241022">
						<div class="eaic-field-desc"><?php esc_html_e( 'e.g.', 'easyit-ai-chat' ); ?> <code>claude-3-5-haiku-20241022</code>, <code>claude-3-5-sonnet-20241022</code>, <code>claude-3-7-sonnet-20250219</code></div>
					</div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-anthropic-timeout"><?php esc_html_e( 'Timeout (seconds)', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-anthropic-timeout" type="number" name="<?php echo esc_attr( $eaic_option_key ); ?>[anthropic_timeout]" value="<?php echo (int) $eaic_opts['anthropic_timeout']; ?>" min="10" max="120">
					</div>

					<div class="eaic-test-row">
						<button type="button" class="eaic-test-btn-styled eaic-test-btn" data-provider="anthropic">🔌 <?php esc_html_e( 'Test Connection', 'easyit-ai-chat' ); ?></button>
						<span class="eaic-test-result" id="eaic-test-anthropic"></span>
					</div>
				</div>
			</div>

			<!-- DEEPSEEK -->
			<div class="eaic-panel" id="eaic-panel-deepseek">
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🔍</span> <?php esc_html_e( 'DeepSeek', 'easyit-ai-chat' ); ?></div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-deepseek-key"><?php esc_html_e( 'API Key', 'easyit-ai-chat' ); ?> <span class="req">*</span></label>
						<input id="eaic-deepseek-key" type="password" name="<?php echo esc_attr( $eaic_option_key ); ?>[deepseek_key]" value="<?php echo esc_attr( $eaic_opts['deepseek_key'] ); ?>" placeholder="sk-...">
						<div class="eaic-field-desc"><?php esc_html_e( 'Get your key at', 'easyit-ai-chat' ); ?> <a href="https://platform.deepseek.com/" target="_blank" rel="noopener noreferrer">platform.deepseek.com</a></div>
					</div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-deepseek-model"><?php esc_html_e( 'Model', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-deepseek-model" type="text" name="<?php echo esc_attr( $eaic_option_key ); ?>[deepseek_model]" value="<?php echo esc_attr( $eaic_opts['deepseek_model'] ); ?>" placeholder="deepseek-chat">
						<div class="eaic-field-desc"><?php esc_html_e( 'e.g.', 'easyit-ai-chat' ); ?> <code>deepseek-chat</code>, <code>deepseek-reasoner</code></div>
					</div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-deepseek-timeout"><?php esc_html_e( 'Timeout (seconds)', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-deepseek-timeout" type="number" name="<?php echo esc_attr( $eaic_option_key ); ?>[deepseek_timeout]" value="<?php echo (int) $eaic_opts['deepseek_timeout']; ?>" min="10" max="120">
					</div>

					<div class="eaic-test-row">
						<button type="button" class="eaic-test-btn-styled eaic-test-btn" data-provider="deepseek">🔌 <?php esc_html_e( 'Test Connection', 'easyit-ai-chat' ); ?></button>
						<span class="eaic-test-result" id="eaic-test-deepseek"></span>
					</div>
				</div>
			</div>

			<!-- GEMINI -->
			<div class="eaic-panel" id="eaic-panel-gemini">
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">✦</span> <?php esc_html_e( 'Google Gemini', 'easyit-ai-chat' ); ?></div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-gemini-key"><?php esc_html_e( 'API Key', 'easyit-ai-chat' ); ?> <span class="req">*</span></label>
						<input id="eaic-gemini-key" type="password" name="<?php echo esc_attr( $eaic_option_key ); ?>[gemini_key]" value="<?php echo esc_attr( $eaic_opts['gemini_key'] ); ?>" placeholder="AIza...">
						<div class="eaic-field-desc"><?php esc_html_e( 'Get your key at', 'easyit-ai-chat' ); ?> <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer">aistudio.google.com</a></div>
					</div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-gemini-model"><?php esc_html_e( 'Model', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-gemini-model" type="text" name="<?php echo esc_attr( $eaic_option_key ); ?>[gemini_model]" value="<?php echo esc_attr( $eaic_opts['gemini_model'] ); ?>" placeholder="gemini-2.0-flash">
						<div class="eaic-field-desc"><?php esc_html_e( 'e.g.', 'easyit-ai-chat' ); ?> <code>gemini-2.0-flash</code>, <code>gemini-2.5-flash</code>, <code>gemini-2.5-pro</code>, <code>gemini-1.5-flash</code></div>
					</div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-gemini-timeout"><?php esc_html_e( 'Timeout (seconds)', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-gemini-timeout" type="number" name="<?php echo esc_attr( $eaic_option_key ); ?>[gemini_timeout]" value="<?php echo (int) $eaic_opts['gemini_timeout']; ?>" min="10" max="120">
					</div>

					<div class="eaic-test-row">
						<button type="button" class="eaic-test-btn-styled eaic-test-btn" data-provider="gemini">🔌 <?php esc_html_e( 'Test Connection', 'easyit-ai-chat' ); ?></button>
						<span class="eaic-test-result" id="eaic-test-gemini"></span>
					</div>
				</div>
			</div>

			<!-- GENERAL -->
			<div class="eaic-panel" id="eaic-panel-general">
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">⚙️</span> <?php esc_html_e( 'General Settings', 'easyit-ai-chat' ); ?></div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-system-prompt"><?php esc_html_e( 'System Prompt', 'easyit-ai-chat' ); ?></label>
						<textarea id="eaic-system-prompt" name="<?php echo esc_attr( $eaic_option_key ); ?>[system_prompt]" rows="4"><?php echo esc_textarea( $eaic_opts['system_prompt'] ); ?></textarea>
						<div class="eaic-field-desc"><?php esc_html_e( 'Default AI persona. Can be overridden per shortcode.', 'easyit-ai-chat' ); ?></div>
					</div>

					<div class="eaic-field-grid">
						<div class="eaic-field">
							<label class="eaic-label" for="eaic-temperature"><?php esc_html_e( 'Temperature', 'easyit-ai-chat' ); ?></label>
							<input id="eaic-temperature" type="number" step="0.1" min="0" max="2" name="<?php echo esc_attr( $eaic_option_key ); ?>[temperature]" value="<?php echo esc_attr( (string) (float) $eaic_opts['temperature'] ); ?>">
							<div class="eaic-field-desc"><?php esc_html_e( '0 = focused · 1 = balanced · 2 = creative', 'easyit-ai-chat' ); ?></div>
						</div>
						<div class="eaic-field">
							<label class="eaic-label" for="eaic-max-tokens"><?php esc_html_e( 'Max Tokens', 'easyit-ai-chat' ); ?></label>
							<input id="eaic-max-tokens" type="number" min="100" max="8000" name="<?php echo esc_attr( $eaic_option_key ); ?>[max_tokens]" value="<?php echo (int) $eaic_opts['max_tokens']; ?>">
							<div class="eaic-field-desc"><?php esc_html_e( 'Max response length (100–8000)', 'easyit-ai-chat' ); ?></div>
						</div>
					</div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-retention"><?php esc_html_e( 'Data Retention (days)', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-retention" type="number" min="1" max="3650" name="<?php echo esc_attr( $eaic_option_key ); ?>[data_retention_days]" value="<?php echo (int) $eaic_opts['data_retention_days']; ?>">
						<div class="eaic-field-desc"><?php esc_html_e( 'Auto-delete conversations older than this many days. Requires cron to be running.', 'easyit-ai-chat' ); ?></div>
					</div>
				</div>

				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🔐</span> <?php esc_html_e( 'Access & Privacy', 'easyit-ai-chat' ); ?></div>

					<label class="eaic-check-row">
						<input type="checkbox" name="<?php echo esc_attr( $eaic_option_key ); ?>[allow_guest_chat]" value="1" <?php checked( $eaic_opts['allow_guest_chat'] ); ?>>
						<div>
							<div class="eaic-check-label"><?php esc_html_e( 'Allow Guest Chat', 'easyit-ai-chat' ); ?></div>
							<div class="eaic-check-desc"><?php esc_html_e( 'Non-logged-in visitors can use the chatbot. Uses cookies to track sessions.', 'easyit-ai-chat' ); ?></div>
						</div>
					</label>

					<label class="eaic-check-row">
						<input type="checkbox" name="<?php echo esc_attr( $eaic_option_key ); ?>[privacy_notice]" value="1" <?php checked( $eaic_opts['privacy_notice'] ); ?>>
						<div>
							<div class="eaic-check-label"><?php esc_html_e( 'Show Privacy Notice', 'easyit-ai-chat' ); ?></div>
							<div class="eaic-check-desc"><?php esc_html_e( 'Display a "Conversations are saved" notice with link to your Privacy Policy.', 'easyit-ai-chat' ); ?></div>
						</div>
					</label>
				</div>
			</div>

			<!-- UI -->
			<div class="eaic-panel" id="eaic-panel-ui">
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🎨</span> <?php esc_html_e( 'UI Customisation', 'easyit-ai-chat' ); ?></div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-chat-title"><?php esc_html_e( 'Chat Widget Title', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-chat-title" type="text" name="<?php echo esc_attr( $eaic_option_key ); ?>[chat_title]" value="<?php echo esc_attr( $eaic_opts['chat_title'] ); ?>" placeholder="AI Chat">
					</div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-placeholder"><?php esc_html_e( 'Input Placeholder', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-placeholder" type="text" name="<?php echo esc_attr( $eaic_option_key ); ?>[placeholder_text]" value="<?php echo esc_attr( $eaic_opts['placeholder_text'] ); ?>" placeholder="<?php esc_attr_e( 'Ask me anything…', 'easyit-ai-chat' ); ?>">
					</div>

					<label class="eaic-check-row">
						<input type="checkbox" name="<?php echo esc_attr( $eaic_option_key ); ?>[show_provider_badge]" value="1" <?php checked( $eaic_opts['show_provider_badge'] ); ?>>
						<div>
							<div class="eaic-check-label"><?php esc_html_e( 'Show Provider Badge', 'easyit-ai-chat' ); ?></div>
							<div class="eaic-check-desc"><?php esc_html_e( 'Display provider name and model below each AI response.', 'easyit-ai-chat' ); ?></div>
						</div>
					</label>

					<label class="eaic-check-row">
						<input type="checkbox" id="eaic-welcome-enabled" name="<?php echo esc_attr( $eaic_option_key ); ?>[welcome_message_enabled]" value="1" <?php checked( $eaic_opts['welcome_message_enabled'] ); ?>>
						<div>
							<div class="eaic-check-label"><?php esc_html_e( 'Show Welcome Message', 'easyit-ai-chat' ); ?></div>
							<div class="eaic-check-desc"><?php esc_html_e( 'Display a custom AI message bubble when a new chat session starts.', 'easyit-ai-chat' ); ?></div>
						</div>
					</label>

					<div id="eaic-welcome-text-wrap" <?php echo $eaic_opts['welcome_message_enabled'] ? '' : 'style="display:none"'; ?>>
						<div class="eaic-field" style="margin-top:8px">
							<textarea id="eaic-welcome-text" name="<?php echo esc_attr( $eaic_option_key ); ?>[welcome_message_text]" rows="3" placeholder="<?php esc_attr_e( 'Hello! How can I help you today?', 'easyit-ai-chat' ); ?>"><?php echo esc_textarea( $eaic_opts['welcome_message_text'] ); ?></textarea>
							<div class="eaic-field-desc"><?php esc_html_e( 'Supports markdown. Shown as an AI bubble on every new chat — not stored or sent to the AI.', 'easyit-ai-chat' ); ?></div>
						</div>
					</div>

					<label class="eaic-check-row" style="margin-top:8px">
						<input type="checkbox" id="eaic-sq-enabled" name="<?php echo esc_attr( $eaic_option_key ); ?>[suggested_questions_enabled]" value="1" <?php checked( $eaic_opts['suggested_questions_enabled'] ); ?>>
						<div>
							<div class="eaic-check-label"><?php esc_html_e( 'Show Suggested Questions', 'easyit-ai-chat' ); ?></div>
							<div class="eaic-check-desc"><?php esc_html_e( 'Display clickable question chips below the welcome area. Clicking a chip sends it instantly.', 'easyit-ai-chat' ); ?></div>
						</div>
					</label>

					<div id="eaic-sq-wrap" <?php echo $eaic_opts['suggested_questions_enabled'] ? '' : 'style="display:none"'; ?>>
						<div class="eaic-field" style="margin-top:8px">
							<textarea id="eaic-sq-text" name="<?php echo esc_attr( $eaic_option_key ); ?>[suggested_questions]" rows="4" placeholder="<?php esc_attr_e( 'One question per line…', 'easyit-ai-chat' ); ?>"><?php echo esc_textarea( $eaic_opts['suggested_questions'] ); ?></textarea>
							<div class="eaic-field-desc"><?php esc_html_e( 'One question per line. Maximum 6 chips displayed.', 'easyit-ai-chat' ); ?></div>
						</div>
					</div>
				</div>

				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🎨</span> <?php esc_html_e( 'Colors', 'easyit-ai-chat' ); ?></div>
					<div class="eaic-field-grid">
						<div class="eaic-field">
							<label class="eaic-label" for="eaic-color-accent"><?php esc_html_e( 'Accent / Primary Color', 'easyit-ai-chat' ); ?></label>
							<div style="display:flex;align-items:center;gap:8px;">
								<input type="color" id="eaic-color-accent" name="<?php echo esc_attr( $eaic_option_key ); ?>[color_accent]" value="<?php echo esc_attr( $eaic_opts['color_accent'] ); ?>">
								<button type="button" class="button eaic-color-reset" data-target="eaic-color-accent" data-default="#4f46e5"><?php esc_html_e( 'Reset', 'easyit-ai-chat' ); ?></button>
							</div>
							<div class="eaic-field-desc"><?php esc_html_e( 'Send button, chips, borders, focus rings.', 'easyit-ai-chat' ); ?></div>
						</div>
						<div class="eaic-field">
							<label class="eaic-label" for="eaic-color-user-bg"><?php esc_html_e( 'User Message Color', 'easyit-ai-chat' ); ?></label>
							<div style="display:flex;align-items:center;gap:8px;">
								<input type="color" id="eaic-color-user-bg" name="<?php echo esc_attr( $eaic_option_key ); ?>[color_user_bg]" value="<?php echo esc_attr( $eaic_opts['color_user_bg'] ); ?>">
								<button type="button" class="button eaic-color-reset" data-target="eaic-color-user-bg" data-default="#1a56db"><?php esc_html_e( 'Reset', 'easyit-ai-chat' ); ?></button>
							</div>
							<div class="eaic-field-desc"><?php esc_html_e( 'User message bubble background.', 'easyit-ai-chat' ); ?></div>
						</div>
						<div class="eaic-field">
							<label class="eaic-label" for="eaic-color-bot-bg"><?php esc_html_e( 'AI Message Color', 'easyit-ai-chat' ); ?></label>
							<div style="display:flex;align-items:center;gap:8px;">
								<input type="color" id="eaic-color-bot-bg" name="<?php echo esc_attr( $eaic_option_key ); ?>[color_bot_bg]" value="<?php echo esc_attr( $eaic_opts['color_bot_bg'] ); ?>">
								<button type="button" class="button eaic-color-reset" data-target="eaic-color-bot-bg" data-default="#f3f4f6"><?php esc_html_e( 'Reset', 'easyit-ai-chat' ); ?></button>
							</div>
							<div class="eaic-field-desc"><?php esc_html_e( 'AI message bubble background.', 'easyit-ai-chat' ); ?></div>
						</div>
					</div>
				</div>

				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🤖</span> <?php esc_html_e( 'AI Avatar', 'easyit-ai-chat' ); ?></div>
					<div class="eaic-field">
						<label class="eaic-label"><?php esc_html_e( 'Custom Avatar Image', 'easyit-ai-chat' ); ?></label>
						<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
							<?php $eaic_avatar = $eaic_opts['ai_avatar_url']; ?>
							<img id="eaic-avatar-preview"
								src="<?php echo esc_url( $eaic_avatar ); ?>"
								alt="<?php esc_attr_e( 'Avatar preview', 'easyit-ai-chat' ); ?>"
								style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid #e5e7eb;<?php echo $eaic_avatar ? '' : 'display:none;'; ?>">
							<input type="hidden" id="eaic-avatar-url" name="<?php echo esc_attr( $eaic_option_key ); ?>[ai_avatar_url]" value="<?php echo esc_attr( $eaic_avatar ); ?>">
							<button type="button" id="eaic-avatar-upload-btn" class="button"><?php esc_html_e( 'Upload Image', 'easyit-ai-chat' ); ?></button>
							<button type="button" id="eaic-avatar-remove-btn" class="button" <?php echo $eaic_avatar ? '' : 'style="display:none"'; ?>><?php esc_html_e( 'Remove', 'easyit-ai-chat' ); ?></button>
						</div>
						<div class="eaic-field-desc"><?php esc_html_e( 'Default: 🤖 emoji. Recommended: 48×48 px PNG/JPG with transparent or round background.', 'easyit-ai-chat' ); ?></div>
					</div>
				</div>
			</div>

			<!-- SECURITY -->
			<div class="eaic-panel" id="eaic-panel-security">
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🚦</span> <?php esc_html_e( 'Rate Limiting', 'easyit-ai-chat' ); ?></div>

					<div class="eaic-field-grid">
						<div class="eaic-field">
							<label class="eaic-label" for="eaic-rl-window"><?php esc_html_e( 'Window (seconds)', 'easyit-ai-chat' ); ?></label>
							<input id="eaic-rl-window" type="number" min="10" max="3600" name="<?php echo esc_attr( $eaic_option_key ); ?>[rate_limit_window]" value="<?php echo (int) $eaic_opts['rate_limit_window']; ?>">
							<div class="eaic-field-desc"><?php esc_html_e( 'Rolling time window for rate limits.', 'easyit-ai-chat' ); ?></div>
						</div>
						<div class="eaic-field">
							<label class="eaic-label" for="eaic-rl-max"><?php esc_html_e( 'Max per user/session', 'easyit-ai-chat' ); ?></label>
							<input id="eaic-rl-max" type="number" min="1" max="1000" name="<?php echo esc_attr( $eaic_option_key ); ?>[rate_limit_max]" value="<?php echo (int) $eaic_opts['rate_limit_max']; ?>">
							<div class="eaic-field-desc"><?php esc_html_e( 'Max requests per logged-in user or guest cookie.', 'easyit-ai-chat' ); ?></div>
						</div>
						<div class="eaic-field">
							<label class="eaic-label" for="eaic-rl-ip"><?php esc_html_e( 'Max per IP', 'easyit-ai-chat' ); ?></label>
							<input id="eaic-rl-ip" type="number" min="1" max="1000" name="<?php echo esc_attr( $eaic_option_key ); ?>[rate_limit_ip_max]" value="<?php echo (int) $eaic_opts['rate_limit_ip_max']; ?>">
							<div class="eaic-field-desc"><?php esc_html_e( 'Hard cap across all sessions from one IP address.', 'easyit-ai-chat' ); ?></div>
						</div>
					</div>
				</div>

				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🛡️</span> <?php esc_html_e( 'Provider & Prompt Controls', 'easyit-ai-chat' ); ?></div>

					<div class="eaic-field">
						<label class="eaic-label"><?php esc_html_e( 'Allowed Providers', 'easyit-ai-chat' ); ?></label>
						<div>
							<?php
							$eaic_allowed = isset( $eaic_opts['allowed_providers'] ) && is_array( $eaic_opts['allowed_providers'] )
								? $eaic_opts['allowed_providers']
								: array_keys( $eaic_provider_map );
							foreach ( $eaic_provider_map as $eaic_slug => $eaic_label ) :
							?>
							<label class="eaic-check-row">
								<input type="checkbox"
									name="<?php echo esc_attr( $eaic_option_key ); ?>[allowed_providers][]"
									value="<?php echo esc_attr( $eaic_slug ); ?>"
									<?php checked( in_array( $eaic_slug, $eaic_allowed, true ) ); ?>>
								<div>
									<div class="eaic-check-label"><?php echo esc_html( $eaic_label ); ?></div>
								</div>
							</label>
							<?php endforeach; ?>
						</div>
						<div class="eaic-field-desc"><?php esc_html_e( 'Visitors may only use checked providers. Server rejects requests for all others.', 'easyit-ai-chat' ); ?></div>
					</div>

					<label class="eaic-check-row">
						<input type="checkbox"
							name="<?php echo esc_attr( $eaic_option_key ); ?>[lock_system_prompt]"
							value="1"
							<?php checked( $eaic_opts['lock_system_prompt'] ); ?>>
						<div>
							<div class="eaic-check-label"><?php esc_html_e( 'Lock System Prompt', 'easyit-ai-chat' ); ?></div>
							<div class="eaic-check-desc"><?php esc_html_e( 'Ignore any system prompt sent by the browser. Only the admin-configured prompt above is used. Recommended for public sites to prevent prompt-injection attacks.', 'easyit-ai-chat' ); ?></div>
						</div>
					</label>
				</div>

			</div><!-- /#eaic-panel-security -->

			<div class="eaic-save-row">
				<button type="submit" class="eaic-save-btn">💾 <?php esc_html_e( 'Save Settings', 'easyit-ai-chat' ); ?></button>
			</div>

		</div><!-- /.eaic-settings-main -->

		<div class="eaic-settings-aside">
				<div class="eaic-shortcode-card">
				<h4>📋 <?php esc_html_e( 'Shortcodes', 'easyit-ai-chat' ); ?></h4>
				<div class="eaic-sc-item">[eaic_chat]</div>
				<div class="eaic-sc-item">[eaic_chat provider="openai"]</div>
				<div class="eaic-sc-item">[eaic_chat provider="anthropic" height="600"]</div>
				<div class="eaic-sc-item">[eaic_chat provider="gemini"]</div>
				<div class="eaic-sc-item">[eaic_chat title="Custom Title"]</div>
				</div>

			<div class="eaic-quick-links">
				<div class="eaic-quick-links-title">🔗 <?php esc_html_e( 'Quick Links', 'easyit-ai-chat' ); ?></div>
				<a class="eaic-quick-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=eaic-test-chat' ) ); ?>">
					💬 <?php esc_html_e( 'Test Chat', 'easyit-ai-chat' ); ?>
				</a>
				<a class="eaic-quick-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=eaic' ) ); ?>">
					⚙️ <?php esc_html_e( 'Settings', 'easyit-ai-chat' ); ?>
				</a>
			</div>

			<div class="eaic-tips-card">
				<div class="eaic-tips-title">💡 <?php esc_html_e( 'Tips', 'easyit-ai-chat' ); ?></div>
				<div class="eaic-tips-body">
					• <?php esc_html_e( 'Use provider="gemini" on any shortcode to use Google Gemini.', 'easyit-ai-chat' ); ?><br>
					• <?php esc_html_e( 'Set a system prompt to give your AI a persona.', 'easyit-ai-chat' ); ?><br>
					• <?php esc_html_e( 'Ollama is free — just install it locally.', 'easyit-ai-chat' ); ?>
				</div>
			</div>
		</div><!-- /.eaic-settings-aside -->

		</div><!-- /.eaic-settings-layout -->
	</form>
</div>