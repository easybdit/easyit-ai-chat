=== EasyIT AI Chat — Chatbot for OpenAI, Claude, DeepSeek, Gemini & Ollama ===
Contributors: muradbd
Tags: chatbot, ai chatbot, openai, gemini, ollama
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.19
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
* 🤖 **OpenAI (ChatGPT)** — GPT-4o, GPT-4o-mini, GPT-4.1, o3, o4-mini and more.
* 🧠 **Anthropic (Claude)** — Claude 3.7 Sonnet, Claude 3.5 Sonnet, Claude 3.5 Haiku, Claude 3 Opus.
* 🔍 **DeepSeek** — DeepSeek-Chat, DeepSeek-Reasoner.
* ✦ **Google Gemini** — Gemini 2.5 Pro, Gemini 2.5 Flash, Gemini 2.0 Flash, Gemini 1.5 Flash.

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

= 🛒 WooCommerce Add-on (Pro) =

Looking for WooCommerce features? **EasyIT AI Chat Pro** adds:

* **Order Status Bot** — Customers check order status by chatting. Guests verify via order number + billing email.
* **Product Q&A Bot** — AI answers questions about any product (price, stock, description). Smart keyword search included.
* **Floating Smart Widget** — Context-aware chat launcher that appears on all pages automatically.
* **Lead Capture** — Collect visitor name and email before chat starts.

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

== External Services ==

This plugin connects to external AI services depending on which provider you configure. No data is transmitted unless a user actively sends a chat message.

= OpenAI =
Used when OpenAI is selected as the AI provider.
* Service URL: https://api.openai.com/
* Terms of Use: https://openai.com/policies/row-terms-of-use
* Privacy Policy: https://openai.com/policies/row-privacy-policy

= Anthropic (Claude) =
Used when Anthropic is selected as the AI provider.
* Service URL: https://api.anthropic.com/
* Terms of Use: https://www.anthropic.com/legal/consumer-terms
* Privacy Policy: https://www.anthropic.com/legal/privacy

= DeepSeek =
Used when DeepSeek is selected as the AI provider.
* Service URL: https://api.deepseek.com/
* Terms of Use: https://chat.deepseek.com/downloads/DeepSeek%20Terms%20of%20Use.html
* Privacy Policy: https://chat.deepseek.com/downloads/DeepSeek%20Privacy%20Policy.html

= Google Gemini =
Used when Google Gemini is selected as the AI provider.
* Service URL: https://generativelanguage.googleapis.com/
* Terms of Use: https://ai.google.dev/gemini-api/terms
* Privacy Policy: https://policies.google.com/privacy

= Ollama =
Used when Ollama is selected. Calls your own self-hosted Ollama server URL.
No third-party service is involved unless you point it at a remote server.

**Data sent to external services:** The user's chat message and recent conversation history (last 10 messages). No personal data beyond what the user types is transmitted.

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

= Does it work with WooCommerce? =

WooCommerce-specific bots (Order Status Bot, Product Q&A Bot, Floating Widget) are available in **EasyIT AI Chat Pro**.

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

= 1.0.19 =
* Improved Typing Indicator — replaced the hourglass thinking bubble with a smooth 3-dot animated typing indicator (classic messaging-app style). Dots use the configured accent color and animate with a staggered bounce. Timer appears after 5 seconds for slow responses.

= 1.0.18 =
* Added Analytics Dashboard — new admin page (EasyIT AI Chat → Analytics) showing total conversations, total messages, messages today, active chats this week, most used provider, and a 7-day message bar chart. All data from your own database, zero external tracking.

= 1.0.17 =
* Added Floating Chat Widget — a fixed launcher button that appears on every page. Click to open a slide-up chat panel. Configurable position (bottom-right / bottom-left), label, and uses your accent color. Enable in Settings → UI. No WooCommerce required.

= 1.0.16 =
* Added Conversation Export — a download button in the chat topbar lets users save the current conversation as a .txt file. Enable in Settings → UI.

= 1.0.15 =
* Added Voice Input — enable a microphone button in the input area. Uses the browser's Web Speech API (Chrome, Edge, Safari). Speech is transcribed directly into the text field. Enable in Settings → UI. Requires HTTPS.

= 1.0.14 =
* Added Color Customization — set Accent, User message, and AI message bubble colors via color pickers in Settings → UI. Changes apply instantly with a reset-to-default button for each color.

= 1.0.13 =
* Added Custom AI Avatar — upload any image from the WordPress Media Library to replace the default 🤖 emoji in all AI message bubbles. Configurable in Settings → UI.

= 1.0.12 =
* Added Suggested Questions — display clickable question chips below the welcome area. Clicking a chip sends it instantly. Up to 6 chips, one per line. Configurable in Settings → UI.

= 1.0.11 =
* Added Welcome Message — enable a custom AI greeting bubble that appears when a new chat session starts. Configurable in Settings → UI. Supports markdown. Not stored or sent to the AI.

= 1.0.10 =
* Added "Upgrade to Pro →" link in the plugins list for easy access to the Pro version.

= 1.0.9 =
* Fixed fatal error on activation — Freemius SDK vendor files now included in the plugin package.

= 1.0.8 =
* Renamed Freemius helper function to eaic_fs() for consistency with plugin prefix.

= 1.0.7 =
* WooCommerce bots moved to EasyIT AI Chat Pro add-on.
* Updated default models: GPT-4o-mini (OpenAI), Claude 3.5 Haiku (Anthropic), Gemini 2.0 Flash (Gemini).
* Added newer model examples in settings: GPT-4.1, o3, o4-mini, Claude 3.7 Sonnet, Gemini 2.5 Pro/Flash.

= 1.0.6 =
* Minor bug fixes and stability improvements.

= 1.0.5 =
* Minor bug fixes and stability improvements.

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

= 1.0.19 =
Improved typing indicator: smooth 3-dot animation replaces the hourglass bubble for a cleaner, more familiar chat UX.

= 1.0.18 =
New feature: Analytics Dashboard — total chats, messages, 7-day chart, top provider. Admin only.

= 1.0.17 =
New feature: Floating Chat Widget — fixed launcher button on every page, opens a slide-up chat panel.

= 1.0.16 =
New feature: Conversation Export — download button saves current chat as a .txt file.

= 1.0.15 =
New feature: Voice Input — microphone button with Web Speech API, transcribes speech into the chat input.

= 1.0.14 =
New feature: Color Customization — accent, user bubble, and AI bubble colors via color pickers in Settings → UI.

= 1.0.13 =
New feature: Custom AI Avatar — replace the default robot emoji with your own image via Settings → UI.

= 1.0.12 =
New feature: Suggested Questions — clickable chips that send a question instantly when clicked.

= 1.0.11 =
New feature: Welcome Message — show a custom AI greeting when chat opens. Enable in Settings → UI.

= 1.0.10 =
Adds a convenient "Upgrade to Pro" link in the plugins list.

= 1.0.9 =
Critical fix: resolves fatal error on activation. All users should update immediately.

= 1.0.8 =
Minor maintenance update. Recommended for all users.

= 1.0.7 =
WooCommerce bots moved to EasyIT AI Chat Pro. Default models updated to latest versions (GPT-4o-mini, Claude 3.5 Haiku, Gemini 2.0 Flash).

= 1.0.4 =
Adds Google Gemini support, auto-title sessions, data retention cron, and per-IP rate limiting. Recommended for all users.

= 1.0.3 =
The shortcode has been renamed from `[easyai]` to `[eaic_chat]`. If you used `[easyai]` on any pages, please update them after upgrading.

= 1.0.2 =
Security and WordPress.org compliance update. Recommended for all users.