=== EasyIT AI Chat ===
Contributors: easyit
Tags: chatbot, ollama, openai, anthropic, deepseek
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Unified AI chatbot for your site. Connect Ollama, OpenAI, Anthropic (Claude), or DeepSeek with one shortcode. Free, open-source, no tracking.

== Description ==

**EasyIT AI Chat** lets you add an AI-powered chatbot to any page or post with a single shortcode: `[easyai]`. Pick whichever AI provider fits your budget and privacy needs — you bring your own keys (or run Ollama locally for free).

= Supported AI Providers =

* **Ollama** — run open models (Llama, Mistral, Gemma, Qwen, etc.) on your own server. Completely private, completely free.
* **OpenAI** — GPT-4o, GPT-4o-mini, GPT-4-turbo, GPT-3.5-turbo.
* **Anthropic (Claude)** — Claude 3.5 Sonnet, Claude 3.5 Haiku, Claude 3 Opus.
* **DeepSeek** — DeepSeek-Chat, DeepSeek-Reasoner.

= Key Features =

* **One shortcode, four providers.** Switch with a single attribute: `[easyai provider="anthropic"]`.
* **ChatGPT-style UI** — sidebar with conversation history, code blocks with a copy button, lightweight markdown rendering, dark-mode friendly.
* **Conversation memory** — sessions are saved per logged-in user, or per guest (cookie-scoped, never cross-user).
* **Custom system prompt** — set a global prompt in settings or override per shortcode.
* **Test Connection** button on every provider tab — verify your key/URL before going live.
* **Rate limiting** — built-in per-user / per-guest throttle to prevent abuse.
* **Privacy notice** — optional, configurable, links to your site's Privacy Policy.
* **No telemetry.** No external calls except to the AI provider you choose.
* **GPL-2.0-or-later**, source on GitHub.

= Shortcode usage =

`[easyai]`
`[easyai provider="openai" title="Support Bot" height="500"]`
`[easyai provider="ollama" system_prompt="You are a helpful gardening assistant."]`

Attributes: `provider`, `title`, `placeholder`, `system_prompt`, `height`.

= Privacy =

When the user sends a message, the message and the prior conversation are forwarded to whichever provider you configured. The message text is also stored in your own database so the conversation can resume. Nothing is sent to the plugin author. You should mention the chosen provider in your site's Privacy Policy. See the **Privacy Notice** toggle in settings to display a small notice inside the chat itself.

= Third-party services =

This plugin can optionally send user messages to one of the following services, depending on which provider you select in settings:

* **OpenAI** — Terms: https://openai.com/policies/row-terms-of-use — Privacy: https://openai.com/policies/row-privacy-policy
* **Anthropic** — Terms: https://www.anthropic.com/legal/consumer-terms — Privacy: https://www.anthropic.com/legal/privacy
* **DeepSeek** — Terms: https://chat.deepseek.com/downloads/DeepSeek%20Terms%20of%20Use.html — Privacy: https://chat.deepseek.com/downloads/DeepSeek%20Privacy%20Policy.html
* **Ollama** — calls your own self-hosted Ollama URL (no third party involved by default).

No data is transmitted unless you have configured a provider and a user sends a message.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install from the Plugins screen.
2. Activate.
3. Go to **EasyIT AI Chat → Settings** and configure at least one provider.
4. Click **Test Connection** to verify.
5. Add `[easyai]` to any page, post, or widget.

== Frequently Asked Questions ==

= Do I need an API key? =

For OpenAI, Anthropic, or DeepSeek — yes, you bring your own key. For Ollama, no key is needed; you just need an Ollama server reachable from your site.

= Where can I run Ollama? =

Locally on the same server as your site, or on any machine reachable via HTTP. See https://ollama.com for installation.

= Does the plugin store conversations? =

Yes, in two custom tables in your own database. They are deleted on uninstall. Guest sessions use a cookie token and are not linked to any personal data.

= Can I use it without saving any history? =

A "no-storage" mode is on the roadmap. For now you can clear conversations via the trash-can icon in the sidebar.

= Will it slow my site down? =

Frontend assets (~25 KB CSS + ~15 KB JS) load only on pages that use the `[easyai]` shortcode.

= Is it really free? =

Yes — GPL-2.0-or-later. The only costs you pay are to whichever AI provider you choose (Ollama is free).

== Screenshots ==

1. The chat interface with sidebar, conversation history, and code-block copy button.
2. Settings page — provider tabs with Test Connection.
3. General settings — system prompt, privacy notice, provider badge.

== Changelog ==

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

= 1.0.2 =
Security and WordPress.org compliance update. Recommended for all users.
