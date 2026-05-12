=== WP Easy AI Chat ===
Contributors:      easybdit
Donate link:       https://easyit.com.bd/donate
Tags:              ai, chatbot, ollama, openai, chatgpt, anthropic, claude, deepseek, chat
Requires at least: 6.0
Tested up to:      6.7
Requires PHP:      8.0
Stable tag:        1.0.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Unified AI chatbot for WordPress. Connect Ollama, OpenAI, Anthropic (Claude) and DeepSeek with one shortcode: [easyai]

== Description ==

**WP Easy AI Chat** embeds a fully-featured, ChatGPT-style AI chatbot on any WordPress page using a single shortcode `[easyai]`. Switch between four AI providers without touching code.

= Key Features =

* **Multi-provider** — Ollama (free, local), OpenAI (GPT-3.5/4), Anthropic (Claude), DeepSeek
* **ChatGPT-style UI** — dark sidebar, conversation history, session management
* **Persistent sessions** — saved to your own WordPress database
* **Markdown rendering** — bold, italic, code blocks, lists, headings
* **Provider switcher** — switch AI provider per conversation
* **Guest support** — allow non-logged-in visitors to chat
* **Privacy-ready** — GDPR notice, configurable data retention
* **Rate limiting** — built-in per-user request throttling
* **Admin test chat** — test all providers from the dashboard
* **Fully responsive** — desktop, tablet and mobile

= Shortcode =

`[easyai]`

With options:

`[easyai provider="openai" height="600" title="My Assistant"]`

= Shortcode Attributes =

* `provider` — `ollama` | `openai` | `anthropic` | `deepseek`
* `title` — Widget heading text
* `placeholder` — Input placeholder text
* `system_prompt` — Override AI system/persona prompt
* `height` — Messages area height in pixels (default: 600)

== Installation ==

1. Upload the `wpeasyai` folder to `/wp-content/plugins/`
2. Activate via **Plugins → Installed Plugins**
3. Go to **WP Easy AI → Settings**
4. Configure your AI provider
5. Add `[easyai]` to any page

== Frequently Asked Questions ==

= Do I need an API key? =
Only for OpenAI, Anthropic, and DeepSeek. Ollama is completely free and self-hosted.

= Can guests use the chat? =
Yes, enable "Allow Guest Chat" in Settings → General.

= Is conversation history stored on my server? =
Yes — in your WordPress database. Nothing sent to third parties except your configured AI provider.

= Works with Gutenberg / Elementor / Divi? =
Yes, use a Shortcode block or shortcode element.

== Screenshots ==

1. Public chat page — ChatGPT-style UI with dark sidebar
2. Admin settings — tabbed provider configuration
3. Admin test chat — test providers from the dashboard
4. Mobile responsive layout

== Changelog ==

= 1.0.0 =
* Initial release
* Ollama, OpenAI, Anthropic (Claude), DeepSeek support
* ChatGPT-style UI with persistent sessions
* Guest chat, rate limiting, GDPR notice
* Admin settings + test chat page

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade steps required.
