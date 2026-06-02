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
if ( ! empty( $eaic_opts['custom_providers'] ) && is_array( $eaic_opts['custom_providers'] ) ) {
	foreach ( $eaic_opts['custom_providers'] as $eaic_ci => $eaic_cp ) {
		if ( ! empty( $eaic_cp['enabled'] ) ) {
			$eaic_slug                      = 'custom_' . ( $eaic_ci + 1 );
			$eaic_provider_map[ $eaic_slug ] = '🔧 ' . ( ! empty( $eaic_cp['name'] ) ? $eaic_cp['name'] : $eaic_slug );
		}
	}
}

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
				<button type="button" class="eaic-tab-btn" data-tab="profiles"><span class="eaic-tab-icon">🤖</span> <?php esc_html_e( 'Profiles', 'easyit-ai-chat' ); ?></button>
				<button type="button" class="eaic-tab-btn" data-tab="webhook"><span class="eaic-tab-icon">🔗</span> <?php esc_html_e( 'Webhook', 'easyit-ai-chat' ); ?></button>
				<button type="button" class="eaic-tab-btn" data-tab="custom"><span class="eaic-tab-icon">🔧</span> <?php esc_html_e( 'Custom', 'easyit-ai-chat' ); ?></button>
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
						<label class="eaic-label" for="eaic-context-messages"><?php esc_html_e( 'Context Window (messages)', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-context-messages" type="number" min="1" max="20" name="<?php echo esc_attr( $eaic_option_key ); ?>[context_messages]" value="<?php echo (int) $eaic_opts['context_messages']; ?>">
						<div class="eaic-field-desc"><?php esc_html_e( 'How many previous messages to include in each AI request (1–20). Higher = more context, higher token cost.', 'easyit-ai-chat' ); ?></div>
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
					<div class="eaic-card-title"><span class="icon">💬</span> <?php esc_html_e( 'Floating Widget', 'easyit-ai-chat' ); ?></div>
					<label class="eaic-check-row">
						<input type="checkbox" name="<?php echo esc_attr( $eaic_option_key ); ?>[floating_widget_enabled]" value="1" <?php checked( $eaic_opts['floating_widget_enabled'] ); ?>>
						<div>
							<div class="eaic-check-label"><?php esc_html_e( 'Enable Floating Chat Button', 'easyit-ai-chat' ); ?></div>
							<div class="eaic-check-desc"><?php esc_html_e( 'Show a fixed chat launcher on every page. No WooCommerce required.', 'easyit-ai-chat' ); ?></div>
						</div>
					</label>
					<div class="eaic-field-grid" style="margin-top:12px">
						<div class="eaic-field">
							<label class="eaic-label" for="eaic-float-position"><?php esc_html_e( 'Position', 'easyit-ai-chat' ); ?></label>
							<select id="eaic-float-position" name="<?php echo esc_attr( $eaic_option_key ); ?>[floating_widget_position]">
								<option value="bottom-right" <?php selected( $eaic_opts['floating_widget_position'], 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right', 'easyit-ai-chat' ); ?></option>
								<option value="bottom-left"  <?php selected( $eaic_opts['floating_widget_position'], 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'easyit-ai-chat' ); ?></option>
							</select>
						</div>
						<div class="eaic-field">
							<label class="eaic-label" for="eaic-float-label"><?php esc_html_e( 'Button Label / Tooltip', 'easyit-ai-chat' ); ?></label>
							<input id="eaic-float-label" type="text" name="<?php echo esc_attr( $eaic_option_key ); ?>[floating_widget_label]" value="<?php echo esc_attr( $eaic_opts['floating_widget_label'] ); ?>" placeholder="Chat with us">
						</div>
					</div>
				</div>

				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🎙️</span> <?php esc_html_e( 'Voice Input', 'easyit-ai-chat' ); ?></div>
					<label class="eaic-check-row">
						<input type="checkbox" name="<?php echo esc_attr( $eaic_option_key ); ?>[voice_input_enabled]" value="1" <?php checked( $eaic_opts['voice_input_enabled'] ); ?>>
						<div>
							<div class="eaic-check-label"><?php esc_html_e( 'Enable Voice Input', 'easyit-ai-chat' ); ?></div>
							<div class="eaic-check-desc"><?php esc_html_e( 'Show a microphone button in the input area. Uses the browser\'s built-in Web Speech API (Chrome, Edge, Safari). Requires HTTPS.', 'easyit-ai-chat' ); ?></div>
						</div>
					</label>
				</div>

				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">⬇️</span> <?php esc_html_e( 'Conversation Export', 'easyit-ai-chat' ); ?></div>
					<label class="eaic-check-row">
						<input type="checkbox" name="<?php echo esc_attr( $eaic_option_key ); ?>[export_enabled]" value="1" <?php checked( $eaic_opts['export_enabled'] ); ?>>
						<div>
							<div class="eaic-check-label"><?php esc_html_e( 'Enable Export Button', 'easyit-ai-chat' ); ?></div>
							<div class="eaic-check-desc"><?php esc_html_e( 'Show a download button in the chat topbar. Users can export the current conversation as a .txt file.', 'easyit-ai-chat' ); ?></div>
						</div>
					</label>
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
				</div><!-- /.eaic-card avatar -->

				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🍪</span> <?php esc_html_e( 'GDPR Consent Gate', 'easyit-ai-chat' ); ?></div>
					<label class="eaic-check-row">
						<input type="checkbox" name="<?php echo esc_attr( $eaic_option_key ); ?>[gdpr_gate_enabled]" value="1" <?php checked( $eaic_opts['gdpr_gate_enabled'] ); ?>>
						<div>
							<div class="eaic-check-label"><?php esc_html_e( 'Require consent before chat starts', 'easyit-ai-chat' ); ?></div>
							<div class="eaic-check-desc"><?php esc_html_e( 'Shows a consent banner. User must accept before chatting. Consent stored in cookie for 365 days.', 'easyit-ai-chat' ); ?></div>
						</div>
					</label>
					<div class="eaic-field" style="margin-top:12px">
						<label class="eaic-label"><?php esc_html_e( 'Consent Message', 'easyit-ai-chat' ); ?></label>
						<textarea name="<?php echo esc_attr( $eaic_option_key ); ?>[gdpr_gate_text]" rows="2" style="width:100%"><?php echo esc_textarea( $eaic_opts['gdpr_gate_text'] ); ?></textarea>
					</div>
					<div class="eaic-field">
						<label class="eaic-label"><?php esc_html_e( 'Accept Button Text', 'easyit-ai-chat' ); ?></label>
						<input type="text" name="<?php echo esc_attr( $eaic_option_key ); ?>[gdpr_gate_btn_text]" value="<?php echo esc_attr( $eaic_opts['gdpr_gate_btn_text'] ); ?>" style="max-width:300px">
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

				<!-- Access Restriction -->
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">👤</span> <?php esc_html_e( 'Access Restriction', 'easyit-ai-chat' ); ?></div>
					<div class="eaic-field">
						<label class="eaic-label"><?php esc_html_e( 'Who can use the chat?', 'easyit-ai-chat' ); ?></label>
						<select name="<?php echo esc_attr( $eaic_option_key ); ?>[access_restriction]" id="eaic-access-restriction">
							<option value="everyone" <?php selected( $eaic_opts['access_restriction'], 'everyone' ); ?>><?php esc_html_e( 'Everyone (guests + logged-in)', 'easyit-ai-chat' ); ?></option>
							<option value="logged_in" <?php selected( $eaic_opts['access_restriction'], 'logged_in' ); ?>><?php esc_html_e( 'Logged-in users only', 'easyit-ai-chat' ); ?></option>
							<option value="specific_roles" <?php selected( $eaic_opts['access_restriction'], 'specific_roles' ); ?>><?php esc_html_e( 'Specific user roles', 'easyit-ai-chat' ); ?></option>
						</select>
					</div>
					<?php
					$eaic_all_roles = wp_roles()->get_names();
					?>
					<div class="eaic-field" id="eaic-roles-wrap" style="<?php echo $eaic_opts['access_restriction'] === 'specific_roles' ? '' : 'display:none'; ?>">
						<label class="eaic-label"><?php esc_html_e( 'Allowed Roles', 'easyit-ai-chat' ); ?></label>
						<div style="display:flex;flex-wrap:wrap;gap:10px;">
							<?php foreach ( $eaic_all_roles as $eaic_role_slug => $eaic_role_name ) : ?>
							<label style="display:flex;align-items:center;gap:5px;font-size:13px">
								<input type="checkbox" name="<?php echo esc_attr( $eaic_option_key ); ?>[allowed_roles][]" value="<?php echo esc_attr( $eaic_role_slug ); ?>" <?php checked( in_array( $eaic_role_slug, (array) $eaic_opts['allowed_roles'], true ) ); ?>>
								<?php echo esc_html( translate_user_role( $eaic_role_name ) ); ?>
							</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- IP Blocklist -->
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🚫</span> <?php esc_html_e( 'IP Blocklist', 'easyit-ai-chat' ); ?></div>
					<div class="eaic-field">
						<label class="eaic-label"><?php esc_html_e( 'Blocked IP Addresses', 'easyit-ai-chat' ); ?></label>
						<textarea name="<?php echo esc_attr( $eaic_option_key ); ?>[ip_blocklist]" rows="4" style="width:100%;font-family:monospace"><?php echo esc_textarea( $eaic_opts['ip_blocklist'] ); ?></textarea>
						<div class="eaic-field-desc"><?php esc_html_e( 'One IP per line (IPv4 or IPv6). Blocked IPs cannot send messages.', 'easyit-ai-chat' ); ?></div>
					</div>
				</div>

				<!-- Word Filter -->
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🔤</span> <?php esc_html_e( 'Word Filter', 'easyit-ai-chat' ); ?></div>
					<label class="eaic-check-row">
						<input type="checkbox" name="<?php echo esc_attr( $eaic_option_key ); ?>[word_filter_enabled]" value="1" <?php checked( $eaic_opts['word_filter_enabled'] ); ?>>
						<div>
							<div class="eaic-check-label"><?php esc_html_e( 'Enable Word Filter', 'easyit-ai-chat' ); ?></div>
							<div class="eaic-check-desc"><?php esc_html_e( 'Block or warn when a message contains banned words.', 'easyit-ai-chat' ); ?></div>
						</div>
					</label>
					<div class="eaic-field" style="margin-top:12px">
						<label class="eaic-label"><?php esc_html_e( 'Banned Words', 'easyit-ai-chat' ); ?></label>
						<textarea name="<?php echo esc_attr( $eaic_option_key ); ?>[word_filter_words]" rows="4" style="width:100%"><?php echo esc_textarea( $eaic_opts['word_filter_words'] ); ?></textarea>
						<div class="eaic-field-desc"><?php esc_html_e( 'One word or phrase per line. Case-insensitive.', 'easyit-ai-chat' ); ?></div>
					</div>
					<div class="eaic-field">
						<label class="eaic-label"><?php esc_html_e( 'Action', 'easyit-ai-chat' ); ?></label>
						<select name="<?php echo esc_attr( $eaic_option_key ); ?>[word_filter_action]">
							<option value="block" <?php selected( $eaic_opts['word_filter_action'], 'block' ); ?>><?php esc_html_e( 'Block — reject message silently', 'easyit-ai-chat' ); ?></option>
							<option value="warn"  <?php selected( $eaic_opts['word_filter_action'], 'warn' );  ?>><?php esc_html_e( 'Warn — show error to user', 'easyit-ai-chat' ); ?></option>
						</select>
					</div>
				</div>

				<!-- Prompt Injection Detection -->
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🧠</span> <?php esc_html_e( 'Prompt Injection Detection', 'easyit-ai-chat' ); ?></div>
					<label class="eaic-check-row">
						<input type="checkbox" name="<?php echo esc_attr( $eaic_option_key ); ?>[prompt_injection_detect]" value="1" <?php checked( $eaic_opts['prompt_injection_detect'] ); ?>>
						<div>
							<div class="eaic-check-label"><?php esc_html_e( 'Detect prompt injection attempts', 'easyit-ai-chat' ); ?></div>
							<div class="eaic-check-desc"><?php esc_html_e( 'Detects common patterns like "ignore all previous instructions", "act as", "jailbreak" etc.', 'easyit-ai-chat' ); ?></div>
						</div>
					</label>
					<div class="eaic-field" style="margin-top:12px">
						<label class="eaic-label"><?php esc_html_e( 'Action', 'easyit-ai-chat' ); ?></label>
						<select name="<?php echo esc_attr( $eaic_option_key ); ?>[prompt_injection_action]">
							<option value="block" <?php selected( $eaic_opts['prompt_injection_action'], 'block' ); ?>><?php esc_html_e( 'Block — reject message', 'easyit-ai-chat' ); ?></option>
							<option value="warn"  <?php selected( $eaic_opts['prompt_injection_action'], 'warn' );  ?>><?php esc_html_e( 'Warn — show notice to user', 'easyit-ai-chat' ); ?></option>
						</select>
					</div>
				</div>

				<!-- No-Storage Mode -->
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🗑️</span> <?php esc_html_e( 'No-Storage Mode', 'easyit-ai-chat' ); ?></div>
					<label class="eaic-check-row">
						<input type="checkbox" name="<?php echo esc_attr( $eaic_option_key ); ?>[disable_storage]" value="1" <?php checked( $eaic_opts['disable_storage'] ); ?>>
						<div>
							<div class="eaic-check-label"><?php esc_html_e( 'Do not save conversations', 'easyit-ai-chat' ); ?></div>
							<div class="eaic-check-desc"><?php esc_html_e( 'Messages are not written to the database. Conversation history and session sidebar will be empty. Good for GDPR-strict setups.', 'easyit-ai-chat' ); ?></div>
						</div>
					</label>
				</div>

				<!-- Math Captcha -->
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🧮</span> <?php esc_html_e( 'Anti-Bot Captcha', 'easyit-ai-chat' ); ?></div>
					<label class="eaic-check-row">
						<input type="checkbox" name="<?php echo esc_attr( $eaic_option_key ); ?>[captcha_enabled]" value="1" <?php checked( $eaic_opts['captcha_enabled'] ); ?>>
						<div>
							<div class="eaic-check-label"><?php esc_html_e( 'Enable math captcha', 'easyit-ai-chat' ); ?></div>
							<div class="eaic-check-desc"><?php esc_html_e( 'Show a simple arithmetic question before first message. No external API required.', 'easyit-ai-chat' ); ?></div>
						</div>
					</label>
				</div>

				<!-- Abuse Alert -->
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">📧</span> <?php esc_html_e( 'Abuse Alert', 'easyit-ai-chat' ); ?></div>
					<label class="eaic-check-row">
						<input type="checkbox" name="<?php echo esc_attr( $eaic_option_key ); ?>[abuse_alert_enabled]" value="1" <?php checked( $eaic_opts['abuse_alert_enabled'] ); ?>>
						<div>
							<div class="eaic-check-label"><?php esc_html_e( 'Email admin when rate limit is exceeded', 'easyit-ai-chat' ); ?></div>
							<div class="eaic-check-desc"><?php esc_html_e( 'Sends a one-time email when a user hits the rate limit. Good for detecting abuse early.', 'easyit-ai-chat' ); ?></div>
						</div>
					</label>
					<div class="eaic-field" style="margin-top:12px">
						<label class="eaic-label"><?php esc_html_e( 'Alert Email', 'easyit-ai-chat' ); ?></label>
						<input type="email" name="<?php echo esc_attr( $eaic_option_key ); ?>[abuse_alert_email]" value="<?php echo esc_attr( $eaic_opts['abuse_alert_email'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" style="max-width:320px;width:100%">
						<div class="eaic-field-desc"><?php esc_html_e( 'Leave empty to use the WordPress admin email.', 'easyit-ai-chat' ); ?></div>
					</div>
				</div>

				<!-- Message Length -->
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">📏</span> <?php esc_html_e( 'Message Length Limit', 'easyit-ai-chat' ); ?></div>
					<div class="eaic-field">
						<label class="eaic-label" for="eaic-max-msg-len"><?php esc_html_e( 'Max characters per message', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-max-msg-len" type="number" min="50" max="4000" name="<?php echo esc_attr( $eaic_option_key ); ?>[max_message_length]" value="<?php echo (int) $eaic_opts['max_message_length']; ?>" style="max-width:120px">
						<div class="eaic-field-desc"><?php esc_html_e( 'Limit the maximum length of user messages (50–4000 characters). Default: 4000.', 'easyit-ai-chat' ); ?></div>
					</div>
				</div>

			</div><!-- /#eaic-panel-security -->

			<!-- WEBHOOK -->
			<div class="eaic-panel" id="eaic-panel-webhook">
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🔗</span> <?php esc_html_e( 'Webhook', 'easyit-ai-chat' ); ?></div>
					<p class="eaic-field-desc" style="margin-bottom:12px"><?php esc_html_e( 'Send a POST request to a URL every time an AI response completes. Useful for logging, CRM integration, or Zapier/Make automation.', 'easyit-ai-chat' ); ?></p>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-webhook-url"><?php esc_html_e( 'Webhook URL', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-webhook-url" type="url" name="<?php echo esc_attr( EAIC_Options::OPTION_KEY ); ?>[webhook_url]" value="<?php echo esc_url( $eaic_opts['webhook_url'] ); ?>" placeholder="https://your-site.com/webhook" style="max-width:500px;width:100%">
						<div class="eaic-field-desc"><?php esc_html_e( 'Leave empty to disable. POST payload: session_uuid, user_message, ai_response, provider, timestamp.', 'easyit-ai-chat' ); ?></div>
					</div>

					<div class="eaic-field">
						<label class="eaic-label" for="eaic-webhook-secret"><?php esc_html_e( 'Secret Key (optional)', 'easyit-ai-chat' ); ?></label>
						<input id="eaic-webhook-secret" type="text" name="<?php echo esc_attr( EAIC_Options::OPTION_KEY ); ?>[webhook_secret]" value="<?php echo esc_attr( $eaic_opts['webhook_secret'] ); ?>" placeholder="<?php esc_attr_e( 'Optional HMAC secret', 'easyit-ai-chat' ); ?>" style="max-width:400px;width:100%">
						<div class="eaic-field-desc"><?php esc_html_e( 'If set, a HMAC-SHA256 signature of the payload is sent in the X-EAIC-Signature header.', 'easyit-ai-chat' ); ?></div>
					</div>
				</div>
			</div><!-- /#eaic-panel-webhook -->

			<!-- BOT PROFILES -->
			<div class="eaic-panel" id="eaic-panel-profiles">
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🤖</span> <?php esc_html_e( 'Bot Profiles', 'easyit-ai-chat' ); ?></div>
					<p class="eaic-field-desc" style="margin-bottom:12px"><?php esc_html_e( 'Save named configurations and load them with [eaic_chat profile="your-slug"]. Slugs must be lowercase letters/numbers/hyphens.', 'easyit-ai-chat' ); ?></p>

					<input type="hidden" id="eaic-profiles-json" name="<?php echo esc_attr( EAIC_Options::OPTION_KEY ); ?>[bot_profiles]" value="<?php echo esc_attr( wp_json_encode( $eaic_opts['bot_profiles'] ) ); ?>">

					<div id="eaic-profiles-list">
						<!-- Profiles rendered by JS -->
					</div>
					<button type="button" id="eaic-add-profile" class="button" style="margin-top:10px">
						+ <?php esc_html_e( 'Add Profile', 'easyit-ai-chat' ); ?>
					</button>
				</div>
			</div><!-- /#eaic-panel-profiles -->

			<!-- CUSTOM PROVIDERS -->
			<div class="eaic-panel" id="eaic-panel-custom">
				<div class="eaic-card">
					<div class="eaic-card-title"><span class="icon">🔧</span> <?php esc_html_e( 'Custom Providers', 'easyit-ai-chat' ); ?></div>
					<p class="eaic-field-desc" style="margin-bottom:14px">
						<?php esc_html_e( 'Add any OpenAI-compatible API endpoint (e.g. a local Ollama proxy, LM Studio, or a custom LLM gateway). Use it with', 'easyit-ai-chat' ); ?>
						<code>[eaic_chat provider="custom_1"]</code>.
					</p>

					<input type="hidden" id="eaic-custom-providers-json"
						name="<?php echo esc_attr( EAIC_Options::OPTION_KEY ); ?>[custom_providers]"
						value="<?php echo esc_attr( wp_json_encode( isset( $eaic_opts['custom_providers'] ) ? $eaic_opts['custom_providers'] : array() ) ); ?>">

					<div id="eaic-custom-providers-list">
						<!-- Rendered by JS -->
					</div>

					<button type="button" id="eaic-add-custom-provider" class="button" style="margin-top:12px">
						+ <?php esc_html_e( 'Add Provider', 'easyit-ai-chat' ); ?>
					</button>
				</div>
			</div><!-- /#eaic-panel-custom -->

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
