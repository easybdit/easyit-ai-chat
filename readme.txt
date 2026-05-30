=== EasyIT AI Chat — Chatbot for OpenAI, Claude, DeepSeek, Gemini & Ollama ===
Contributors: easyit
Tags: chatbot, ai chatbot, openai, claude, gemini
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI chatbot for WordPress — add ChatGPT, Claude, Gemini, DeepSeek or local Ollama to any page with one shortcode. Bring your own API keys.

== Description ==

**EasyIT AI Chat** is the easiest way to add an AI chatbot to any WordPress page or post. Add a ChatGPT-style assistant powered by OpenAI, Anthropic Claude, Google Gemini, DeepSeek, or a free local Ollama model — just drop in `[eaic_chat]`, no coding required.

Choose from the world's best AI providers, or run a local model for free with Ollama. You own your data, you control your keys. No tracking, no telemetry.

> 🌐 Website: [easyit.com.bd](https://easyit.com.bd)
> 📺 Tutorials: [youtube.com/@easybdit](https://www.youtube.com/@easybdit)
> 💬 Community: [facebook.com/easybdit](https://www.facebook.com/easybdit)

= ✨ Supported AI Providers =

* 🦙 **Ollama** — Run open-source models (Llama, Mistral, Gemma, Qwen, etc.) on your own server. 100% private, 100% free.
* 🤖 **OpenAI (ChatGPT)** — GPT-4o, GPT-4o-mini, GPT-4-turbo, GPT-3.5-turbo.
* 🧠 **Anthropic (Claude)** — Claude 3.5 Sonnet, Claude 3.5 Haiku, Claude 3 Opus.
* 🔍 **DeepSeek** — DeepSeek-Chat, DeepSeek-Reasoner.
* ✦ **Google Gemini** — Gemini 1.5 Flash, Gemini 1.5 Pro, Gemini 2.0 Flash.

= 🚀 Key Features =

* **One shortcode, any provider** — Switch provider with a single attribute: `[eaic_chat provider="gemini"]`
* **ChatGPT-style UI** — Sidebar with conversation history, code blocks with copy button, markdown rendering, dark-mode friendly
* **Auto-title sessions** — First message automatically generates a meaningful conversation title via the AI
* **Conversation memory** — Sessions saved per logged-in user or per guest (cookie-scoped, never cross-user)
* **Custom system prompt** — Set a global prompt in settings or override per shortcode
* **Lock system prompt** — Prevent front-end prompt injection; admin-configured prompt only
* **Test Connection** button — Verify your API key or Ollama URL before going live
* **Rate limiting** — Per-user, per-session, and per-IP throttle to prevent abuse
* **Data retention** — Auto-purge old conversations after a configurable number of days
* **Privacy notice** — Optional configurable notice linking to your Privacy Policy
* **Lightweight** — Assets load only on pages using the shortcode (~25 KB CSS + ~15 KB JS)
* **No telemetry** — Zero external calls except to the AI provider you choose
* **Open source** — GPL-2.0-or-later, fully auditable

= 🔧 Shortcode Examples =

**Basic usage:**
`[eaic_chat]`

**With a specific provider:**
`[eaic_chat provider="gemini" title="Support Bot" height="500"]`

**With a custom system prompt:**
`[eaic_chat provider="ollama" system_prompt="You are a helpful gardening assistant."]`

**Available attributes:** `provider`, `title`, `placeholder`, `system_prompt`, `height`

= 🔒 Privacy =

When a user sends a message, it is forwarded to your configured AI provider along with the conversation history. Messages are also stored in your own database so conversations can resume. **Nothing is sent to the plugin author.** You should mention your chosen provider in your site's Privacy Policy.

= 🌐 Third-Party Services =

Depending on which provider you configure, user messages may be sent to:

* **OpenAI** — [Terms](https://openai.com/policies/row-terms-of-use) · [Privacy](https://openai.com/policies/row-privacy-policy)
* **Anthropic** — [Terms](https://www.anthropic.com/legal/consumer-terms) · [Privacy](https://www.anthropic.com/legal/privacy)
* **DeepSeek** — [Terms](https://chat.deepseek.com/downloads/DeepSeek%20Terms%20of%20Use.html) · [Privacy](https://chat.deepseek.com/downloads/DeepSeek%20Privacy%20Policy.html)
* **Google Gemini** — [Terms](https://ai.google.dev/gemini-api/terms) · [Privacy](https://policies.google.com/privacy)
* **Ollama** — Calls your own self-hosted Ollama server. No third party involved by default.

No data is transmitted unless you have configured a provider and a user sends a message.

== Installation ==

**Automatic Installation (Recommended)**

1. Go to **Plugins → Add New** in your WordPress dashboard
2. Search for **EasyIT AI Chat**
3. Click **Install Now** then **Activate**

**Manual Installation**

1. Download the plugin zip file
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip and click **Install Now**
4. Activate the plugin

**Setup**

1. Go to **EasyIT AI Chat → Settings**
2. Choose your preferred AI provider and enter your API key
3. Click **Test Connection** to verify everything works
4. Add `[eaic_chat]` to any page, post, or widget area

== Frequently Asked Questions ==

= Do I need an API key? =

For **OpenAI**, **Anthropic**, **DeepSeek**, and **Google Gemini** — yes, you need your own API key. For **Ollama** — no key needed, just a running Ollama server.

= How do I get an API key? =

* OpenAI: [platform.openai.com](https://platform.openai.com)
* Anthropic: [console.anthropic.com](https://console.anthropic.com)
* DeepSeek: [platform.deepseek.com](https://platform.deepseek.com)
* Google Gemini: [aistudio.google.com](https://aistudio.google.com/app/apikey)

= Where can I run Ollama? =

Locally on your server, or any machine reachable via HTTP. Visit [ollama.com](https://ollama.com) for installation instructions.

= Does the plugin store conversations? =

Yes — in two custom database tables in your own database. All data is deleted when you uninstall the plugin. Guest sessions use a cookie token and are never linked to personal data.

= Can I disable conversation history? =

A "no-storage" mode is on the roadmap. Currently you can clear conversations using the trash icon in the chat sidebar.

= Will it slow down my site? =

No. Frontend assets (~25 KB CSS + ~15 KB JS) only load on pages where the `[eaic_chat]` shortcode is used.

= Can I use multiple providers on the same site? =

Yes — use the `provider` attribute to specify different providers on different pages: `[eaic_chat provider="openai"]` on one page and `[eaic_chat provider="gemini"]` on another.

= Is this plugin really free? =

Yes — GPL-2.0-or-later. The only costs are to your chosen AI provider. Ollama is completely free.

= Where can I get support? =

* 📺 Video tutorials: [youtube.com/@easybdit](https://www.youtube.com/@easybdit)
* 💬 Facebook: [facebook.com/easybdit](https://www.facebook.com/easybdit)
* 🌐 Website: [easyit.com.bd](https://easyit.com.bd)
* 📧 Email: support@easyit.com.bd
* 🐛 Bug reports: WordPress.org support forum

== Screenshots ==

1. The chat interface — conversation history sidebar, user and AI messages, and a clean ChatGPT-style layout.
2. Settings page — provider tabs (Ollama, OpenAI, Anthropic, DeepSeek, Gemini), server URL, model, and Test Connection button.
3. Adding the chatbot to any page — just drop the [eaic_chat] shortcode into a block.

== Changelog ==

= 1.0.4 =
* Added Google Gemini provider (Gemini 1.5 Flash, Gemini 1.5 Pro, Gemini 2.0 Flash).
* Added auto-title generation — first message generates a meaningful session title via the active AI provider.
* Added data retention cron — sessions older than the configured number of days are purged automatically.
* Added per-IP rate limiting as a secondary hard cap alongside the existing per-user/session limit.
* Added Lock System Prompt setting to prevent front-end prompt injection on public sites.
* Improved guest cookie security: SameSite=Lax attribute now set via PHP 8.0 array signature.
* Rate limit window and max values are now configurable in Settings → Security.

= 1.0.3 =
* Renamed shortcode from `[easyai]` to `[eaic_chat]` to use the plugin's `eaic` prefix (WordPress.org review feedback).

= 1.0.2 =
* Renamed plugin and folder to comply with WordPress.org trademark guidelines.
* All exception messages now escaped before being thrown.
* All direct database queries paired with object-cache reads/writes.
* All AJAX handlers verify nonce before reading `$_POST`.
* Removed deprecated `load_plugin_textdomain()` call (handled automatically since WP 4.6+).
* All view-scoped variables prefixed to avoid global namespace collisions.
* Excluded development files from production zip.

= 1.0.1 =
* Initial public release.

== Upgrade Notice ==

= 1.0.4 =
Adds Google Gemini support, auto-title sessions, data retention cron, and per-IP rate limiting. Recommended for all users.

= 1.0.3 =
The shortcode has been renamed from `[easyai]` to `[eaic_chat]`. If you used `[easyai]` on any pages, please update them after upgrading.

= 1.0.2 =
Security and WordPress.org compliance update. Recommended for all users.
