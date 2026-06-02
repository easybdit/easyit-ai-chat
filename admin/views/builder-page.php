<?php
/**
 * Admin view: Shortcode Builder page.
 *
 * @package EasyIT_AI_Chat
 * @since   1.0.29
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$eaic_providers = array(
	'ollama'    => 'Ollama',
	'openai'    => 'OpenAI',
	'anthropic' => 'Anthropic (Claude)',
	'deepseek'  => 'DeepSeek',
	'gemini'    => 'Google Gemini',
);
?>
<div class="wrap eaic-settings-wrap">

	<div class="eaic-hero">
		<div class="eaic-hero-left">
			<div class="eaic-hero-icon">🔧</div>
			<div>
				<div class="eaic-hero-title"><?php esc_html_e( 'Shortcode Builder', 'easyit-ai-chat' ); ?></div>
				<div class="eaic-hero-sub"><?php esc_html_e( 'Configure your chatbot and copy the shortcode — no typing required.', 'easyit-ai-chat' ); ?></div>
			</div>
		</div>
		<div class="eaic-hero-badge">v<?php echo esc_html( EAIC_VERSION ); ?></div>
	</div>

	<div class="eaic-builder-wrap">

		<!-- Controls -->
		<div class="eaic-card eaic-builder-controls">
			<div class="eaic-card-title"><span class="icon">⚙️</span> <?php esc_html_e( 'Configure', 'easyit-ai-chat' ); ?></div>

			<div class="eaic-field-grid">

				<div class="eaic-field">
					<label class="eaic-label" for="eab-provider"><?php esc_html_e( 'AI Provider', 'easyit-ai-chat' ); ?></label>
					<select id="eab-provider" class="eab-input">
						<option value=""><?php esc_html_e( '— Use site default —', 'easyit-ai-chat' ); ?></option>
						<?php foreach ( $eaic_providers as $eaic_slug => $eaic_label ) : ?>
							<option value="<?php echo esc_attr( $eaic_slug ); ?>"><?php echo esc_html( $eaic_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="eaic-field">
					<label class="eaic-label" for="eab-title"><?php esc_html_e( 'Chat Title', 'easyit-ai-chat' ); ?></label>
					<input id="eab-title" type="text" class="eab-input" placeholder="<?php esc_attr_e( 'e.g. Support Bot', 'easyit-ai-chat' ); ?>">
				</div>

				<div class="eaic-field">
					<label class="eaic-label" for="eab-placeholder"><?php esc_html_e( 'Input Placeholder', 'easyit-ai-chat' ); ?></label>
					<input id="eab-placeholder" type="text" class="eab-input" placeholder="<?php esc_attr_e( 'e.g. Ask me anything...', 'easyit-ai-chat' ); ?>">
				</div>

				<div class="eaic-field">
					<label class="eaic-label" for="eab-height"><?php esc_html_e( 'Message Area Height (px)', 'easyit-ai-chat' ); ?></label>
					<input id="eab-height" type="number" min="200" max="900" class="eab-input" placeholder="600">
				</div>

				<?php if ( ! empty( $eaic_opts['bot_profiles'] ) ) : ?>
				<div class="eaic-field">
					<label class="eaic-label" for="eab-profile"><?php esc_html_e( 'Bot Profile', 'easyit-ai-chat' ); ?></label>
					<select id="eab-profile" class="eab-input">
						<option value=""><?php esc_html_e( '— None —', 'easyit-ai-chat' ); ?></option>
						<?php foreach ( $eaic_opts['bot_profiles'] as $eaic_prof ) : ?>
							<option value="<?php echo esc_attr( $eaic_prof['slug'] ); ?>"><?php echo esc_html( $eaic_prof['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>

			</div>

			<div class="eaic-field">
				<label class="eaic-label" for="eab-system"><?php esc_html_e( 'System Prompt (override)', 'easyit-ai-chat' ); ?></label>
				<textarea id="eab-system" class="eab-input" rows="3" placeholder="<?php esc_attr_e( 'Optional. Leave empty to use the global system prompt.', 'easyit-ai-chat' ); ?>"></textarea>
				<div class="eaic-field-desc"><?php esc_html_e( 'This overrides the global system prompt for this specific widget only.', 'easyit-ai-chat' ); ?></div>
			</div>
		</div>

		<!-- Preview + Copy -->
		<div class="eaic-card eaic-builder-output">
			<div class="eaic-card-title"><span class="icon">📋</span> <?php esc_html_e( 'Your Shortcode', 'easyit-ai-chat' ); ?></div>

			<div class="eaic-builder-preview-wrap">
				<code id="eab-output" class="eaic-builder-preview">[eaic_chat]</code>
				<button type="button" id="eab-copy-btn" class="button button-primary eaic-builder-copy-btn">
					<?php esc_html_e( 'Copy Shortcode', 'easyit-ai-chat' ); ?>
				</button>
			</div>

			<div class="eaic-builder-usage">
				<div class="eaic-card-title" style="margin-top:20px"><span class="icon">📖</span> <?php esc_html_e( 'How to use', 'easyit-ai-chat' ); ?></div>
				<ol style="margin:8px 0 0 18px;padding:0;line-height:1.8">
					<li><?php esc_html_e( 'Edit any page or post in WordPress.', 'easyit-ai-chat' ); ?></li>
					<li><?php esc_html_e( 'Add a Shortcode block (or Classic Editor shortcode area).', 'easyit-ai-chat' ); ?></li>
					<li><?php esc_html_e( 'Paste the shortcode and save.', 'easyit-ai-chat' ); ?></li>
				</ol>
				<p style="margin-top:12px;font-size:12px;color:#6b7280">
					<?php esc_html_e( 'Example:', 'easyit-ai-chat' ); ?>
					<code>[eaic_chat provider="gemini" title="Support Bot" height="500"]</code>
				</p>
			</div>
		</div>

	</div>

</div>

<style>
.eaic-builder-wrap { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media (max-width:800px) { .eaic-builder-wrap { grid-template-columns:1fr; } }
.eaic-builder-preview-wrap { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.eaic-builder-preview {
	flex:1;
	display:block;
	background:#f3f4f6;
	border:1.5px solid #e5e7eb;
	border-radius:8px;
	padding:12px 16px;
	font-size:14px;
	font-family:Consolas,monospace;
	color:#1d2027;
	word-break:break-all;
	min-height:44px;
}
.eaic-builder-copy-btn { flex-shrink:0; }
</style>

<script>
(function(){
	var inputs = document.querySelectorAll('.eab-input');
	var output = document.getElementById('eab-output');
	var copyBtn = document.getElementById('eab-copy-btn');

	function build() {
		var provider    = document.getElementById('eab-provider').value;
		var title       = document.getElementById('eab-title').value.trim();
		var placeholder = document.getElementById('eab-placeholder').value.trim();
		var height      = document.getElementById('eab-height').value.trim();
		var system      = document.getElementById('eab-system').value.trim();
		var profileEl   = document.getElementById('eab-profile');
		var profile     = profileEl ? profileEl.value : '';

		var sc = '[eaic_chat';
		if (profile)      { sc += ' profile="' + profile + '"'; }
		else {
			if (provider)    { sc += ' provider="' + provider + '"'; }
		}
		if (title)        { sc += ' title="' + title.replace(/"/g,'&quot;') + '"'; }
		if (placeholder)  { sc += ' placeholder="' + placeholder.replace(/"/g,'&quot;') + '"'; }
		if (height && parseInt(height,10) !== 600) { sc += ' height="' + parseInt(height,10) + '"'; }
		if (system && !profile) { sc += ' system_prompt="' + system.replace(/"/g,'&quot;') + '"'; }
		sc += ']';
		output.textContent = sc;
	}

	for (var i = 0; i < inputs.length; i++) {
		inputs[i].addEventListener('input', build);
	}

	function showCopied() {
		copyBtn.textContent = '✅ <?php esc_html_e( 'Copied!', 'easyit-ai-chat' ); ?>';
		setTimeout(function(){ copyBtn.textContent = '<?php esc_html_e( 'Copy Shortcode', 'easyit-ai-chat' ); ?>'; }, 1800);
	}
	function fallbackCopy(text) {
		try {
			var ta = document.createElement('textarea');
			ta.value = text; ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
			document.body.appendChild(ta); ta.focus(); ta.select();
			document.execCommand('copy');
			document.body.removeChild(ta);
			showCopied();
		} catch(e) { /* silent fail */ }
	}
	copyBtn.addEventListener('click', function(){
		var text = output.textContent;
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(showCopied).catch(function(){ fallbackCopy(text); });
		} else {
			fallbackCopy(text);
		}
	});
}());
</script>
