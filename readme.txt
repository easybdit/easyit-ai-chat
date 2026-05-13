=== EasyIT AI Chat ===
Contributors: muradbd
Tags: chatbot, ai chat, ai assistant, conversational ai, llm
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A unified AI chatbot shortcode that connects to your own self-hosted or third-party AI model API. Free, open source, no tracking.

== Description ==

**EasyIT AI Chat** adds an AI-powered chatbot to any page or post with one shortcode: `[easyai]`. You bring your own API key (or run a self-hosted model locally for free). The plugin acts as a thin connector — it does not provide any AI service of its own.

= Supported AI provider APIs =

This plugin can be configured to talk to any of the following third-party APIs (you bring your own key / your own server). The plugin is an independent client and is not affiliated with, endorsed by, or sponsored by any of these providers.

* Self-hosted models via the Ollama-compatible API (run Llama, Mistral, Gemma, Qwen and other open models on your own server — completely private, completely free).
* OpenAI-compatible chat completions API (GPT-4o, GPT-4o-mini, GPT-4-turbo, GPT-3.5-turbo).
* Anthropic Messages API (Claude family models).
* DeepSeek API (DeepSeek-Chat, DeepSeek-Reasoner).

= Key Features =

* One shortcode, multiple back-ends. Switch with a single attribute: `[easyai provider="anthropic"]`.
* Familiar chat UI — sidebar with conversation history, code blocks with copy button, lightweight markdown rendering, dark-mode friendly.
* Conversation memory — sessions are saved per logged-in user, or per guest (cookie-scoped, never cross-user).
* Custom system prompt — set a global prompt in settings or override per shortcode.
* Test Connection button on every provider tab — verify your key/URL before going live.
* Rate limiting — built-in per-user / per-guest throttle to prevent abuse.
* Privacy notice — optional, configurable, links to your site's Privacy Policy.
* No telemetry. No external calls except to the API endpoint you choose.
* GPL-2.0-or-later, source on GitHub.

= Shortcode usage =

`[easyai]`
`[easyai provider="openai" title="Support Bot" height="500"]`
`[easyai provider="ollama" system_prompt="You are a helpful gardening assistant."]`

Attributes: `provider`, `title`, `placeholder`, `system_prompt`, `height`.

= Privacy =

When the user sends a message, the message and the prior conversation are forwarded to whichever API endpoint you configured. The message text is also stored in your own database so the conversation can resume. Nothing is sent to the plugin author. You should mention the chosen API provider in your site's Privacy Policy. See the **Privacy Notice** toggle in settings to display a small notice inside the chat itself.

= External services this plugin can connect to =

This plugin does NOT contact any service by default. After you configure a provider in settings and a user sends a message, the user's message and conversation history are sent to **only** the endpoint you select:

**OpenAI API** (used only if you configure an OpenAI key)
* Endpoint: `https://api.openai.com/v1/chat/completions`
* Data sent: chat messages, model name, temperature, max tokens
* Terms of Use: https://openai.com/policies/row-terms-of-use
* Privacy Policy: https://openai.com/policies/row-privacy-policy

**Anthropic API** (used only if you configure an Anthropic key)
* Endpoint: `https://api.anthropic.com/v1/messages`
* Data sent: chat messages, model name, temperature, max tokens, system prompt
* Terms of Use: https://www.anthropic.com/legal/consumer-terms
* Privacy Policy: https://www.anthropic.com/legal/privacy

**DeepSeek API** (used only if you configure a DeepSeek key)
* Endpoint: `https://api.deepseek.com/v1/chat/completions`
* Data sent: chat messages, model name, temperature, max tokens
* Terms of Use: https://chat.deepseek.com/downloads/DeepSeek%20Terms%20of%20Use.html
* Privacy Policy: https://chat.deepseek.com/downloads/DeepSeek%20Privacy%20Policy.html

**Ollama (self-hosted)** (used only if you configure an Ollama URL)
* Endpoint: whatever URL you supply in settings (typically `http://localhost:11434`)
* Data sent: chat messages, model name
* No third party involved — calls your own server. Only set this to a trusted server URL.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install from the Plugins screen.
2. Activate.
3. Go to **EasyIT AI Chat → Settings** and configure at least one provider.
4. Click **Test Connection** to verify.
5. Add `[easyai]` to any page, post, or widget.

== Frequently Asked Questions ==

= Do I need an API key? =

For the OpenAI, Anthropic, or DeepSeek connectors — yes, you bring your own key. For the self-hosted (Ollama-compatible) connector, no key is needed; you just need an Ollama server reachable from your site.

= Where can I run a self-hosted model? =

Locally on the same server as your site, or on any machine reachable via HTTP. See https://ollama.com for installation instructions.

= Does the plugin store conversations? =

Yes, in two custom tables in your own database. They are deleted on uninstall. Guest sessions use a cookie token and are not linked to any personal data.

= Can I use it without saving any history? =

A "no-storage" mode is on the roadmap. For now you can clear conversations via the trash-can icon in the sidebar.

= Will it slow my site down? =

Frontend assets (~25 KB CSS + ~15 KB JS) load only on pages that use the `[easyai]` shortcode.

= Is it really free? =

Yes — GPL-2.0-or-later. The only costs you pay are to whichever third-party API you choose (self-hosting is free).

= Are you affiliated with OpenAI, Anthropic, DeepSeek, or Ollama? =

No. This plugin is an independent open-source connector. The respective trademarks belong to their owners and are referenced only to describe API compatibility.

== Screenshots ==

1. The chat interface with sidebar, conversation history, and code-block copy button.
2. Settings page — provider tabs with Test Connection.
3. General settings — system prompt, privacy notice, provider badge.

== Changelog ==

= 1.0.3 =
* Updated readme.txt to clarify the plugin is an independent connector and to comply with trademark / naming guidelines.
* Added an "External services" section in readme.txt documenting every endpoint, the data sent, and links to each provider's Terms & Privacy Policy.
* Added an `Update URI` header to prevent slug-hijacking from third-party update channels.
* Replaced the chat-based Anthropic health check with a lightweight 1-token call to minimise API cost.
* Fixed dbDelta schema — replaced `ENUM` with `VARCHAR(20)` (ENUM is not reliably supported by dbDelta on upgrades).
* Hardened the guest cookie: explicit `SameSite=Lax`, `HttpOnly`, `Secure` on HTTPS.
* Added missing `index.php` silence files in every directory.
* Bumped "Tested up to" to 6.8.

= 1.0.2 =
* Renamed plugin and folder to comply with WordPress.org trademark guidelines.
* All exception messages now escaped before being thrown.
* All direct database queries paired with object-cache reads/writes.
* All AJAX handlers verify nonce before reading `$_POST`.
* Removed deprecated `load_plugin_textdomain()` call (handled automatically by WP 4.6+).
* All view-scoped variables prefixed to avoid global namespace collisions.
* Excluded development files (`.gitignore`, `.github/`) from the production zip.

= 1.0.1 =
* Initial public release.

== Upgrade Notice ==

= 1.0.3 =
WordPress.org compliance, security hardening and dbDelta fix. Recommended for all users.

= 1.0.2 =
Security and WordPress.org compliance update. Recommended for all users.
