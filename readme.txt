=== EasyIT AI Chat ===
Contributors:      easybdit
Tags:              ai, chatbot, ollama, openai, chatgpt, anthropic, claude, deepseek, chat, artificial intelligence
Requires at least: 6.0
Tested up to:      6.7
Requires PHP:      8.0
Stable tag:        1.0.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Unified AI chatbot for WordPress. Connect Ollama, OpenAI, Anthropic (Claude), and DeepSeek with one shortcode.

== Description ==

**EasyIT AI Chat** lets you embed a fully-featured, ChatGPT-style AI chatbot on any WordPress page using a single shortcode. Switch between four AI providers without touching code.

= Key Features =

* **Multi-provider** — Ollama (free, local), OpenAI (GPT-3.5/4), Anthropic (Claude), DeepSeek
* **ChatGPT-style UI** — dark sidebar, conversation history, session management
* **Persistent sessions** — conversations saved to your database per user or guest
* **Markdown rendering** — bold, italic, code blocks, lists, headings
* **Provider switcher** — users can switch AI provider per conversation
* **Guest support** — allow non-logged-in visitors to chat (with cookie tracking)
* **Privacy-ready** — GDPR privacy notice with link to your Privacy Policy page
* **Rate limiting** — built-in per-user request throttling
* **Admin test chat** — test all providers directly from your WordPress dashboard
* **Fully responsive** — works on desktop, tablet and mobile

= Shortcode Usage =

Basic:
`[easyit_ai_chat]`

With options:
`[easyit_ai_chat provider="openai" height="600" title="My Assistant"]`

All attributes:

* `provider` — `ollama` | `openai` | `anthropic` | `deepseek` (default: your settings)
* `title` — Widget heading text
* `placeholder` — Input placeholder text
* `system_prompt` — Override the AI system/persona prompt for this widget
* `height` — Messages area height in pixels (default: 600)

= Provider Setup =

**Ollama** (free, runs locally or on your server) — no API key needed. Install from [ollama.com](https://ollama.com), pull a model (`ollama pull qwen2:1.5b`), and point the plugin to your server URL.

**OpenAI** — requires an API key from [platform.openai.com](https://platform.openai.com). Supports GPT-3.5, GPT-4o, and all chat models.

**Anthropic (Claude)** — requires an API key from [console.anthropic.com](https://console.anthropic.com). Supports Claude 3 Haiku, Sonnet, and Opus.

**DeepSeek** — requires an API key from [platform.deepseek.com](https://platform.deepseek.com).

= Privacy =

This plugin stores conversation history in your own WordPress database. No data is sent to third-party services other than the AI provider you configure. Guest sessions are tracked via a first-party cookie. See the plugin's Settings → General tab to configure data retention.

== Installation ==

1. Upload the `easyit-ai-chat` folder to `/wp-content/plugins/`
2. Activate the plugin via **Plugins → Installed Plugins**
3. Navigate to **EasyIT AI Chat → Settings** in your WordPress admin
4. Configure your chosen AI provider (URL/API key, model)
5. Add `[easyit_ai_chat]` to any page or post

== Frequently Asked Questions ==

= Do I need an API key? =
Only for OpenAI, Anthropic, and DeepSeek. Ollama is completely free and self-hosted — no API key required.

= Can I use multiple providers on the same site? =
Yes. Each shortcode instance can specify its own provider attribute. You can have different providers on different pages.

= Can guests (non-logged-in users) use the chat? =
Yes, if you enable "Allow Guest Chat" in Settings → General. Guest sessions are tracked with a first-party browser cookie.

= How do I give the AI a specific personality? =
Set a System Prompt in Settings → General, or use the `system_prompt` attribute on any individual shortcode.

= Is conversation history stored on my server? =
Yes. All messages are stored in your WordPress database (`wp_easyit_ai_messages` and `wp_easyit_ai_sessions` tables). No data is sent to any third party other than your configured AI provider.

= How do I delete user data (GDPR)? =
Conversation data is tied to the WordPress user ID. You can set a data retention period in Settings → General to auto-purge old conversations.

= Will this work with page builders (Elementor, Divi, Gutenberg)? =
Yes. The shortcode works in any context that supports shortcodes, including Gutenberg (as a Shortcode block), Elementor, Divi, and WPBakery.

= Can I embed multiple chat widgets on one page? =
Yes, each `[easyit_ai_chat]` shortcode creates an independent widget with its own session.

== Screenshots ==

1. Public chat page — ChatGPT-style UI with dark sidebar and conversation history
2. Admin settings page — tabbed provider configuration
3. Admin test chat — test your AI providers directly from the dashboard
4. Mobile view — responsive layout adapts to small screens

== Changelog ==

= 1.0.0 =
* Initial release
* Support for Ollama, OpenAI (ChatGPT), Anthropic (Claude), DeepSeek
* ChatGPT-style UI with dark sidebar, session list, and persistent conversations
* Multi-turn conversation with full database persistence
* Provider switcher per session
* Markdown rendering (bold, italic, code blocks, lists, headings)
* Admin settings page with tabbed provider configuration
* Test-connection button per provider
* Admin test-chat page
* Guest chat support with cookie-based session tracking
* Rate limiting per user/guest
* GDPR-friendly privacy notice
* Fully responsive layout

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade steps required.
