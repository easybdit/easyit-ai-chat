<?php if ( ! defined( 'ABSPATH' ) ) exit; if ( ! is_array( $opts ) ) $opts = WPEasyAI_Options::defaults(); ?>
<style>
/* ── EasyIT AI Chat Settings Page Styles ── */
.weai-settings-wrap { max-width: 900px; }
.weai-settings-wrap .weai-hero {
	display: flex; align-items: center; justify-content: space-between;
	background: linear-gradient(135deg, #0f3460 0%, #1a1a2e 100%);
	border-radius: 12px; padding: 20px 28px; margin-bottom: 24px; gap: 16px;
	box-shadow: 0 4px 20px rgba(15,52,96,.3);
}
.weai-settings-wrap .weai-hero-left { display: flex; align-items: center; gap: 16px; }
.weai-settings-wrap .weai-hero-icon {
	width: 52px; height: 52px; background: rgba(255,255,255,.12); border-radius: 12px;
	display: flex; align-items: center; justify-content: center; font-size: 26px;
	border: 1px solid rgba(255,255,255,.15);
}
.weai-settings-wrap .weai-hero-title { color: #fff; font-size: 20px; font-weight: 700; margin: 0; }
.weai-settings-wrap .weai-hero-sub { color: rgba(255,255,255,.6); font-size: 13px; margin: 3px 0 0; }
.weai-settings-wrap .weai-hero-badge {
	background: rgba(255,255,255,.1); color: rgba(255,255,255,.8);
	border: 1px solid rgba(255,255,255,.2); border-radius: 20px;
	padding: 5px 14px; font-size: 12px; font-weight: 600; white-space: nowrap;
}

/* Tabs */
.weai-tab-nav {
	display: flex; gap: 4px; padding: 4px; background: #f3f4f6;
	border-radius: 10px; margin-bottom: 20px; flex-wrap: wrap;
}
.weai-tab-btn {
	display: flex; align-items: center; gap: 6px; padding: 8px 16px;
	border-radius: 7px; border: none; background: transparent; cursor: pointer;
	font-size: 13px; font-weight: 500; color: #6b7280; transition: all .15s;
	white-space: nowrap;
}
.weai-tab-btn:hover { background: rgba(255,255,255,.7); color: #111827; }
.weai-tab-btn.active { background: #fff; color: #111827; box-shadow: 0 1px 3px rgba(0,0,0,.12); }
.weai-tab-btn .weai-tab-icon { font-size: 15px; }

/* Panels */
.weai-panel { display: none; }
.weai-panel.active { display: block; }

/* Form card */
.weai-card {
	background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
	padding: 24px 28px; margin-bottom: 16px;
}
.weai-card-title {
	display: flex; align-items: center; gap: 10px;
	font-size: 15px; font-weight: 700; color: #111827;
	margin: 0 0 20px; padding-bottom: 14px;
	border-bottom: 1px solid #f0f0f0;
}
.weai-card-title .icon { font-size: 20px; }

/* Field rows */
.weai-field { margin-bottom: 18px; }
.weai-field:last-child { margin-bottom: 0; }
.weai-label {
	display: block; font-size: 13px; font-weight: 600; color: #374151;
	margin-bottom: 6px;
}
.weai-label .req { color: #ef4444; margin-left: 2px; }
.weai-field input[type="text"],
.weai-field input[type="url"],
.weai-field input[type="password"],
.weai-field input[type="number"],
.weai-field select,
.weai-field textarea {
	width: 100%; padding: 9px 13px; border: 1.5px solid #e5e7eb;
	border-radius: 8px; font-size: 13px; color: #111827; background: #fff;
	transition: border-color .15s, box-shadow .15s; outline: none;
	font-family: inherit;
}
.weai-field input[type="text"]:focus,
.weai-field input[type="url"]:focus,
.weai-field input[type="password"]:focus,
.weai-field input[type="number"]:focus,
.weai-field select:focus,
.weai-field textarea:focus {
	border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,.1);
}
.weai-field input[type="number"] { max-width: 120px; }
.weai-field-desc { font-size: 11.5px; color: #9ca3af; margin-top: 5px; }

/* Test connection row */
.weai-test-row {
	display: flex; align-items: center; gap: 12px;
	margin-top: 20px; padding-top: 18px; border-top: 1px solid #f3f4f6;
}
.weai-test-btn-styled {
	display: inline-flex; align-items: center; gap: 6px;
	padding: 8px 18px; background: #f8f9fa; border: 1.5px solid #e5e7eb;
	border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600;
	color: #374151; transition: all .15s;
}
.weai-test-btn-styled:hover { background: #f0f0f0; border-color: #d1d5db; }
.weai-test-result { font-size: 13px; font-weight: 500; }

/* Checkbox rows */
.weai-check-row {
	display: flex; align-items: flex-start; gap: 10px;
	padding: 12px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px;
	margin-bottom: 10px; cursor: pointer; transition: border-color .15s, background .15s;
}
.weai-check-row:hover { border-color: #c7d2fe; background: #fafafa; }
.weai-check-row input[type="checkbox"] { margin-top: 2px; flex-shrink: 0; width: 16px; height: 16px; }
.weai-check-label { font-size: 13px; font-weight: 600; color: #111827; margin-bottom: 2px; }
.weai-check-desc  { font-size: 12px; color: #9ca3af; }

/* Shortcode card */
.weai-shortcode-card {
	background: linear-gradient(135deg, #f8f9ff 0%, #fef9ff 100%);
	border: 1.5px solid #e0e7ff; border-radius: 10px; padding: 16px;
	margin-bottom: 14px;
}
.weai-shortcode-card h4 { font-size: 12px; font-weight: 700; color: #4f46e5; text-transform: uppercase; letter-spacing: .06em; margin: 0 0 10px; }
.weai-sc-item {
	background: #fff; border: 1px solid #e0e7ff; border-radius: 7px;
	padding: 8px 12px; margin-bottom: 7px; font-family: Consolas, monospace;
	font-size: 12px; color: #4f46e5; cursor: pointer; transition: background .15s;
	word-break: break-all;
}
.weai-sc-item:hover { background: #eef2ff; }
.weai-sc-item:last-child { margin-bottom: 0; }

/* Save button */
.weai-save-btn {
	display: inline-flex; align-items: center; gap: 8px;
	padding: 11px 28px; background: linear-gradient(135deg, #4f46e5, #7c3aed);
	color: #fff; border: none; border-radius: 9px; font-size: 14px; font-weight: 600;
	cursor: pointer; transition: all .15s; box-shadow: 0 4px 14px rgba(79,70,229,.35);
}
.weai-save-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(79,70,229,.4); }
.weai-save-btn:active { transform: translateY(0); }

/* Layout */
.weai-settings-layout { display: flex; gap: 20px; align-items: flex-start; }
.weai-settings-main { flex: 1; min-width: 0; }
.weai-settings-aside { width: 220px; flex-shrink: 0; }
@media (max-width: 900px) { .weai-settings-layout { flex-direction: column; } .weai-settings-aside { width: 100%; } }

/* Provider status dots */
.weai-status-dot {
	width: 8px; height: 8px; border-radius: 50%; display: inline-block;
	margin-left: 4px; background: #e5e7eb;
}
.weai-status-dot.ok  { background: #22c55e; }
.weai-status-dot.err { background: #ef4444; }
</style>

<div class="wrap weai-settings-wrap">

<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
<div class="notice notice-success is-dismissible" style="border-radius:8px;margin-bottom:16px">
	<p>✅ <strong>Settings saved.</strong> Your configuration has been updated.</p>
</div>
<?php endif; ?>

<!-- Hero -->
<div class="weai-hero">
	<div class="weai-hero-left">
		<div class="weai-hero-icon">🤖</div>
		<div>
			<div class="weai-hero-title">EasyIT AI Chat</div>
			<div class="weai-hero-sub">Unified AI chatbot — Ollama · OpenAI · Anthropic · DeepSeek</div>
		</div>
	</div>
	<div class="weai-hero-badge">v<?php echo esc_html( WPEASYAI_VERSION ); ?></div>
</div>

<form method="post" action="options.php">
<?php settings_fields( 'wpeasyai_group' ); ?>

<div class="weai-settings-layout">
<div class="weai-settings-main">

<!-- Tab nav -->
<div class="weai-tab-nav">
	<button type="button" class="weai-tab-btn active" data-tab="ollama">
		<span class="weai-tab-icon">🦙</span> Ollama
	</button>
	<button type="button" class="weai-tab-btn" data-tab="openai">
		<span class="weai-tab-icon">✨</span> OpenAI
	</button>
	<button type="button" class="weai-tab-btn" data-tab="anthropic">
		<span class="weai-tab-icon">🎭</span> Anthropic
	</button>
	<button type="button" class="weai-tab-btn" data-tab="deepseek">
		<span class="weai-tab-icon">🔍</span> DeepSeek
	</button>
	<button type="button" class="weai-tab-btn" data-tab="general">
		<span class="weai-tab-icon">⚙️</span> General
	</button>
	<button type="button" class="weai-tab-btn" data-tab="ui">
		<span class="weai-tab-icon">🎨</span> UI
	</button>
</div>

<!-- ── OLLAMA ── -->
<div class="weai-panel active" id="weai-panel-ollama">
<div class="weai-card">
	<div class="weai-card-title"><span class="icon">🦙</span> Ollama — Self-Hosted, Free</div>

	<div class="weai-field">
		<label class="weai-label">Default Provider</label>
		<select name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[default_provider]">
			<?php foreach(['ollama'=>'🦙 Ollama','openai'=>'✨ OpenAI','anthropic'=>'🎭 Anthropic','deepseek'=>'🔍 DeepSeek'] as $k=>$v): ?>
			<option value="<?php echo esc_attr($k); ?>" <?php selected($opts['default_provider'],$k); ?>><?php echo esc_html($v); ?></option>
			<?php endforeach; ?>
		</select>
		<div class="weai-field-desc">The provider used when no provider is specified in the shortcode.</div>
	</div>

	<div class="weai-field">
		<label class="weai-label">Ollama Server URL</label>
		<input type="url" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[ollama_url]" value="<?php echo esc_attr($opts['ollama_url']); ?>" placeholder="http://localhost:11434">
		<div class="weai-field-desc">The base URL of your Ollama server, e.g. <code>http://localhost:11434</code></div>
	</div>

	<div class="weai-field">
		<label class="weai-label">Model</label>
		<input type="text" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[ollama_model]" value="<?php echo esc_attr($opts['ollama_model']); ?>" placeholder="qwen2:1.5b">
		<div class="weai-field-desc">e.g. <code>qwen2:1.5b</code>, <code>llama3</code>, <code>mistral</code>, <code>deepseek-r1</code></div>
	</div>

	<div class="weai-field">
		<label class="weai-label">Timeout (seconds)</label>
		<input type="number" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[ollama_timeout]" value="<?php echo (int)$opts['ollama_timeout']; ?>" min="10" max="300">
		<div class="weai-field-desc">Increase for large models or slow hardware.</div>
	</div>

	<div class="weai-test-row">
		<button type="button" class="weai-test-btn-styled weai-test-btn" data-provider="ollama">
			🔌 Test Connection
		</button>
		<span class="weai-test-result" id="weai-test-ollama"></span>
	</div>
</div>
</div>

<!-- ── OPENAI ── -->
<div class="weai-panel" id="weai-panel-openai">
<div class="weai-card">
	<div class="weai-card-title"><span class="icon">✨</span> OpenAI (ChatGPT)</div>

	<div class="weai-field">
		<label class="weai-label">API Key <span class="req">*</span></label>
		<input type="password" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[openai_key]" value="<?php echo esc_attr($opts['openai_key']); ?>" placeholder="sk-...">
		<div class="weai-field-desc">Get your key at <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a></div>
	</div>

	<div class="weai-field">
		<label class="weai-label">Model</label>
		<input type="text" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[openai_model]" value="<?php echo esc_attr($opts['openai_model']); ?>" placeholder="gpt-3.5-turbo">
		<div class="weai-field-desc">e.g. <code>gpt-3.5-turbo</code>, <code>gpt-4o-mini</code>, <code>gpt-4o</code></div>
	</div>

	<div class="weai-field">
		<label class="weai-label">Timeout (seconds)</label>
		<input type="number" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[openai_timeout]" value="<?php echo (int)$opts['openai_timeout']; ?>" min="10" max="120">
	</div>

	<div class="weai-test-row">
		<button type="button" class="weai-test-btn-styled weai-test-btn" data-provider="openai">
			🔌 Test Connection
		</button>
		<span class="weai-test-result" id="weai-test-openai"></span>
	</div>
</div>
</div>

<!-- ── ANTHROPIC ── -->
<div class="weai-panel" id="weai-panel-anthropic">
<div class="weai-card">
	<div class="weai-card-title"><span class="icon">🎭</span> Anthropic (Claude)</div>

	<div class="weai-field">
		<label class="weai-label">API Key <span class="req">*</span></label>
		<input type="password" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[anthropic_key]" value="<?php echo esc_attr($opts['anthropic_key']); ?>" placeholder="sk-ant-...">
		<div class="weai-field-desc">Get your key at <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a></div>
	</div>

	<div class="weai-field">
		<label class="weai-label">Model</label>
		<input type="text" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[anthropic_model]" value="<?php echo esc_attr($opts['anthropic_model']); ?>" placeholder="claude-3-haiku-20240307">
		<div class="weai-field-desc">e.g. <code>claude-3-haiku-20240307</code>, <code>claude-3-5-sonnet-20241022</code></div>
	</div>

	<div class="weai-field">
		<label class="weai-label">Timeout (seconds)</label>
		<input type="number" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[anthropic_timeout]" value="<?php echo (int)$opts['anthropic_timeout']; ?>" min="10" max="120">
	</div>

	<div class="weai-test-row">
		<button type="button" class="weai-test-btn-styled weai-test-btn" data-provider="anthropic">
			🔌 Test Connection
		</button>
		<span class="weai-test-result" id="weai-test-anthropic"></span>
	</div>
</div>
</div>

<!-- ── DEEPSEEK ── -->
<div class="weai-panel" id="weai-panel-deepseek">
<div class="weai-card">
	<div class="weai-card-title"><span class="icon">🔍</span> DeepSeek</div>

	<div class="weai-field">
		<label class="weai-label">API Key <span class="req">*</span></label>
		<input type="password" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[deepseek_key]" value="<?php echo esc_attr($opts['deepseek_key']); ?>" placeholder="sk-...">
		<div class="weai-field-desc">Get your key at <a href="https://platform.deepseek.com/" target="_blank">platform.deepseek.com</a></div>
	</div>

	<div class="weai-field">
		<label class="weai-label">Model</label>
		<input type="text" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[deepseek_model]" value="<?php echo esc_attr($opts['deepseek_model']); ?>" placeholder="deepseek-chat">
		<div class="weai-field-desc">e.g. <code>deepseek-chat</code>, <code>deepseek-reasoner</code></div>
	</div>

	<div class="weai-field">
		<label class="weai-label">Timeout (seconds)</label>
		<input type="number" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[deepseek_timeout]" value="<?php echo (int)$opts['deepseek_timeout']; ?>" min="10" max="120">
	</div>

	<div class="weai-test-row">
		<button type="button" class="weai-test-btn-styled weai-test-btn" data-provider="deepseek">
			🔌 Test Connection
		</button>
		<span class="weai-test-result" id="weai-test-deepseek"></span>
	</div>
</div>
</div>

<!-- ── GENERAL ── -->
<div class="weai-panel" id="weai-panel-general">
<div class="weai-card">
	<div class="weai-card-title"><span class="icon">⚙️</span> General Settings</div>

	<div class="weai-field">
		<label class="weai-label">System Prompt</label>
		<textarea name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[system_prompt]" rows="4" style="resize:vertical"><?php echo esc_textarea($opts['system_prompt']); ?></textarea>
		<div class="weai-field-desc">The default personality/role for the AI. Can be overridden per shortcode.</div>
	</div>

	<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
	<div class="weai-field">
		<label class="weai-label">Temperature</label>
		<input type="number" step="0.1" min="0" max="2" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[temperature]" value="<?php echo (float)$opts['temperature']; ?>">
		<div class="weai-field-desc">0 = focused, 1 = balanced, 2 = creative</div>
	</div>
	<div class="weai-field">
		<label class="weai-label">Max Tokens</label>
		<input type="number" min="100" max="8000" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[max_tokens]" value="<?php echo (int)$opts['max_tokens']; ?>">
		<div class="weai-field-desc">Max response length (100–8000)</div>
	</div>
	</div>

	<div class="weai-field">
		<label class="weai-label">Data Retention (days)</label>
		<input type="number" min="1" max="3650" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[data_retention_days]" value="<?php echo (int)$opts['data_retention_days']; ?>">
		<div class="weai-field-desc">Auto-delete conversations older than this many days.</div>
	</div>
</div>

<div class="weai-card">
	<div class="weai-card-title"><span class="icon">🔐</span> Access & Privacy</div>

	<label class="weai-check-row">
		<input type="checkbox" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[allow_guest_chat]" value="1" <?php checked($opts['allow_guest_chat']); ?>>
		<div>
			<div class="weai-check-label">Allow Guest Chat</div>
			<div class="weai-check-desc">Non-logged-in visitors can use the chatbot. Uses cookies to track sessions.</div>
		</div>
	</label>

	<label class="weai-check-row">
		<input type="checkbox" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[privacy_notice]" value="1" <?php checked($opts['privacy_notice']); ?>>
		<div>
			<div class="weai-check-label">Show Privacy Notice</div>
			<div class="weai-check-desc">Display a "Conversations are saved" notice with link to your Privacy Policy.</div>
		</div>
	</label>
</div>
</div>

<!-- ── UI ── -->
<div class="weai-panel" id="weai-panel-ui">
<div class="weai-card">
	<div class="weai-card-title"><span class="icon">🎨</span> UI Customisation</div>

	<div class="weai-field">
		<label class="weai-label">Chat Widget Title</label>
		<input type="text" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[chat_title]" value="<?php echo esc_attr($opts['chat_title']); ?>" placeholder="AI Chat">
	</div>

	<div class="weai-field">
		<label class="weai-label">Input Placeholder</label>
		<input type="text" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[placeholder_text]" value="<?php echo esc_attr($opts['placeholder_text']); ?>" placeholder="Ask me anything…">
	</div>

	<label class="weai-check-row">
		<input type="checkbox" name="<?php echo WPEasyAI_Options::OPTION_KEY; ?>[show_provider_badge]" value="1" <?php checked($opts['show_provider_badge']); ?>>
		<div>
			<div class="weai-check-label">Show Provider Badge</div>
			<div class="weai-check-desc">Display provider name and model below each AI response.</div>
		</div>
	</label>
</div>
</div>

<!-- Save -->
<div style="margin-top:8px">
	<button type="submit" class="weai-save-btn">💾 Save Settings</button>
</div>

</div><!-- /.weai-settings-main -->

<!-- Aside -->
<div class="weai-settings-aside">

	<div class="weai-shortcode-card">
		<h4>📋 Shortcodes</h4>
		<div class="weai-sc-item" title="Click to copy">[wpeasyai]</div>
		<div class="weai-sc-item" title="Click to copy">[wpeasyai provider="openai"]</div>
		<div class="weai-sc-item" title="Click to copy">[wpeasyai provider="anthropic" height="600"]</div>
		<div class="weai-sc-item" title="Click to copy">[wpeasyai title="Custom Title"]</div>
	</div>

	<div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin-bottom:14px">
		<div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:10px">🔗 Quick Links</div>
		<a href="<?php echo admin_url('admin.php?page=laraveweai-test-chat'); ?>" style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:8px">
			💬 Test Chat
		</a>
		<a href="<?php echo admin_url('admin.php?page=wpeasyai'); ?>" style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:#f3f4f6;color:#374151;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500">
			⚙️ Settings
		</a>
	</div>

	<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:14px">
		<div style="font-size:12px;font-weight:700;color:#92400e;margin-bottom:6px">💡 Tips</div>
		<div style="font-size:12px;color:#78350f;line-height:1.6">
			• Use <code>provider="ollama"</code> on any shortcode to override the default.<br>
			• Set a <em>system prompt</em> to give your AI a persona.<br>
			• Ollama is free — just install it locally.
		</div>
	</div>

</div><!-- /.weai-settings-aside -->
</div><!-- /.weai-settings-layout -->

</form>
</div>

<script>
(function(){
	// Tab switching
	var tabs   = document.querySelectorAll('.weai-tab-btn');
	var panels = document.querySelectorAll('.weai-panel');
	tabs.forEach(function(btn){
		btn.addEventListener('click', function(){
			var tab = btn.dataset.tab;
			tabs.forEach(function(b){ b.classList.remove('active'); });
			panels.forEach(function(p){ p.classList.remove('active'); });
			btn.classList.add('active');
			var panel = document.getElementById('weai-panel-' + tab);
			if (panel) panel.classList.add('active');
			if (history.replaceState) history.replaceState(null,'','#'+tab);
		});
	});
	// Restore from hash
	var hash = location.hash.replace('#','');
	if (hash) {
		var btn = document.querySelector('.weai-tab-btn[data-tab="'+hash+'"]');
		if (btn) btn.click();
	}

	// Copy shortcodes
	document.querySelectorAll('.weai-sc-item').forEach(function(el){
		el.addEventListener('click', function(){
			var text = el.textContent.trim();
			if (navigator.clipboard) {
				navigator.clipboard.writeText(text).then(function(){
					var orig = el.textContent;
					el.textContent = '✅ Copied!';
					setTimeout(function(){ el.textContent = orig; }, 1400);
				});
			}
		});
	});

	// Test connection buttons (using WP AJAX via admin.js)
})();
</script>
