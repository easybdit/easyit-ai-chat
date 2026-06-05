=== EasyIT AI Chat — Chatbot for OpenAI, Claude, DeepSeek, Gemini & Ollama ===
Contributors: muradbd
Tags: chatbot, ai chatbot, openai, gemini, ollama
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 2.3.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI chatbot for WordPress — OpenAI, Claude, Gemini, DeepSeek & Ollama via one shortcode. 30+ features. Bring your own API keys.

== Description ==

**EasyIT AI Chat** is the most feature-rich free AI chatbot plugin for WordPress. Drop in `[eaic_chat]` on any page to add a fully-featured ChatGPT-style assistant — powered by OpenAI, Anthropic Claude, Google Gemini, DeepSeek, or a 100% free local Ollama model. No coding required.

You own your data, you control your keys. No tracking, no telemetry, no subscription — ever.

> 🌐 Website: [easyit.com.bd](https://easyit.com.bd)
> 📺 Tutorials: [youtube.com/@easybdit](https://www.youtube.com/@easybdit)
> 💬 Community: [facebook.com/easybdit](https://www.facebook.com/easybdit)

= ✨ Supported AI Providers =

* 🦙 **Ollama** — Run open-source models (Llama, Mistral, Gemma, Qwen, etc.) on your own server. 100% private, 100% free.
* 🤖 **OpenAI (ChatGPT)** — GPT-4o, GPT-4o-mini, GPT-4.1, o3, o4-mini and more.
* 🧠 **Anthropic (Claude)** — Claude 3.7 Sonnet, Claude 3.5 Sonnet, Claude 3.5 Haiku, Claude 3 Opus.
* 🔍 **DeepSeek** — DeepSeek-Chat, DeepSeek-Reasoner.
* ✦ **Google Gemini** — Gemini 2.5 Pro, Gemini 2.5 Flash, Gemini 2.0 Flash, Gemini 1.5 Flash.
* 🔧 **Custom Providers** — Any OpenAI-compatible endpoint (LM Studio, custom gateway, proxies, etc.).

= 🚀 Core Features =

* **One shortcode, any provider** — `[eaic_chat provider="gemini"]` — switch provider per page, no settings change needed
* **ChatGPT-style UI** — Sidebar with conversation history, code blocks with copy button, markdown rendering
* **Auto-title sessions** — First message auto-generates a meaningful conversation title via the AI
* **Conversation memory** — Sessions saved per logged-in user or guest (cookie-scoped, never cross-user)
* **Custom system prompt** — Set globally in settings or override per shortcode
* **Lock system prompt** — Prevent front-end prompt injection; admin-configured prompt only
* **Test Connection** button — Verify your API key or Ollama URL before going live
* **Rate limiting** — Per-user, per-session, and per-IP throttle to prevent abuse
* **Data retention** — Auto-purge old conversations after a configurable number of days
* **Context window control** — Choose how many previous messages to include per AI request (1–20)
* **Privacy notice** — Optional configurable notice linking to your Privacy Policy
* **Lightweight** — Assets load only on pages using the shortcode
* **No telemetry** — Zero external calls except to the AI provider you choose
* **Open source** — GPL-2.0-or-later, fully auditable

= 💬 Chat UX & Interaction =

* **Typing indicator** — Smooth 3-dot animated bubble while AI is responding (accent-color matched)
* **Stop & Regenerate** — Cancel a response mid-stream with the ⏹ Stop button; re-run with 🔄 Regenerate
* **Message timestamps** — HH:MM time shown next to every message label
* **Copy & Download message** — Copy or download any AI response with one click; buttons always visible below each message
* **Read aloud (TTS)** — 🔊 Speaker button reads any AI message using the browser's built-in Speech Synthesis API
* **Voice input** — 🎤 Microphone button transcribes speech directly into the input field (Web Speech API)
* **Fullscreen mode** — Expand the chat widget to full-screen overlay; press Escape to exit
* **Message feedback** — 👍 / 👎 buttons below AI responses; ratings stored in your DB
* **Session search** — Search box in the sidebar filters conversations by title in real time

= 🎨 Customization =

* **Welcome message** — Custom AI greeting bubble when a new chat starts (supports markdown)
* **Suggested questions** — Clickable chip buttons that send a question instantly
* **Custom AI avatar** — Replace the 🤖 emoji with any image from your Media Library
* **Color customization** — Accent, user bubble, and bot bubble colors via color pickers; reset to default in one click
* **Floating chat widget** — Fixed launcher button on every page; configurable position (bottom-right / bottom-left) and label
* **Bot profiles** — Save named configurations (provider, title, system prompt) and load with `[eaic_chat profile="slug"]`
* **Multiple providers per site** — Different providers on different pages using shortcode attributes

= 📊 Analytics & Insights =

* **Analytics dashboard** — Total conversations, total messages, messages today, active chats (7 days), most-used provider
* **7-day bar chart** — Pure CSS message chart — no external libraries
* **Feedback stats** — Helpful / Not helpful rating counts from user feedback

= 🔧 Developer & Admin Tools =

* **Shortcode Builder** — Visual admin page: configure provider, title, height, system prompt — shortcode updates live, copy with one click
* **Conversation export** — Download any conversation as a `.txt` file
* **Webhook support** — POST to any URL after each AI response; JSON payload with session UUID, message, provider, timestamp; optional HMAC-SHA256 signature header
* **Configurable message length** — Set max characters per user message (50–4000)

= 🔒 Security Suite (v2.0) =

* **Access restriction** — Allow everyone, logged-in users only, or specific WordPress user roles
* **IP blocklist** — Block specific IPv4 / IPv6 addresses from sending messages
* **Word filter** — Block or warn when a message contains banned words; configurable action
* **Prompt injection detection** — Auto-detect and block jailbreak patterns ("ignore all previous instructions", "DAN mode", etc.)
* **No-storage mode** — Opt out of saving conversations to the database entirely (GDPR-strict setups)
* **Anti-bot math captcha** — Simple arithmetic challenge before the first message; no external API or cookies required
* **Abuse alert email** — Get an email notification when the rate limit is exceeded; configurable recipient
* **GDPR consent gate** — Show an accept banner before the chat activates; consent stored in a browser cookie (365 days)

= 📚 Knowledge Base (RAG) =

* **Document upload** — Upload PDF files to a private Knowledge Base; the AI answers questions using only your document data
* **Semantic + keyword search** — Queries are matched against document chunks via Ollama embeddings with automatic keyword fallback
* **Accurate counting** — "How many X" queries scan every chunk for an exact count — not just the top-K semantic results
* **Context-aware answers** — Matched chunks are injected into the system prompt so the AI gives specific, document-grounded answers
* **Admin test tool** — Test Query UI lets you inspect which chunks are retrieved for any query before going live
* **Re-process on demand** — Change embedding model or chunk settings and reprocess any document without re-uploading

= 🛒 WooCommerce Add-on (Pro) =

Looking for WooCommerce features? **EasyIT AI Chat Pro** adds:

* **Order Status Bot** — Customers check order status by chatting. Guests verify via order number + billing email.
* **Product Q&A Bot** — AI answers questions about any product (price, stock, description). Smart keyword search included.
* **Floating Smart Widget** — Context-aware chat launcher that appears on all pages automatically.
* **Lead Capture** — Collect visitor name and email before chat starts.

= 🔧 Shortcode Examples =

**Basic usage:**
`[eaic_chat]`

**Specify a provider:**
`[eaic_chat provider="gemini" title="Support Bot" height="500"]`

**Custom system prompt:**
`[eaic_chat provider="ollama" system_prompt="You are a helpful gardening assistant."]`

**Load a saved bot profile:**
`[eaic_chat profile="support-bot"]`

**Full example:**
`[eaic_chat provider="openai" title="Ask Anything" placeholder="Type your question…" height="600" system_prompt="You are a helpful assistant for our website."]`

**Available attributes:** `provider`, `title`, `placeholder`, `system_prompt`, `height`, `profile`

💡 Use the **Shortcode Builder** (EasyIT AI Chat → Shortcode Builder) to generate shortcodes visually — no typing required.

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

By default yes — in custom database tables in your own database. All data is deleted when you uninstall the plugin. Guest sessions use a cookie token and are never linked to personal data. You can enable **No-Storage Mode** in Settings → Security to disable all DB writes entirely.

= Can I disable conversation history? =

Yes. Enable **No-Storage Mode** in Settings → Security → No-Storage Mode. Messages will not be saved to the database at all — ideal for GDPR-strict setups.

= Will it slow down my site? =

No. Frontend assets (~25 KB CSS + ~15 KB JS) only load on pages where the `[eaic_chat]` shortcode is used.

= Can I use multiple providers on the same site? =

Yes — use the `provider` attribute to specify different providers on different pages: `[eaic_chat provider="openai"]` on one page and `[eaic_chat provider="gemini"]` on another.

= Is this plugin really free? =

Yes — GPL-2.0-or-later. The only costs are to your chosen AI provider. Ollama is completely free.

= Does it work with WooCommerce? =

WooCommerce-specific bots (Order Status Bot, Product Q&A Bot, Floating Widget) are available in **EasyIT AI Chat Pro**.

= How do I restrict the chat to logged-in users only? =

Go to **Settings → Security → Access Restriction** and select "Logged-in users only" or "Specific user roles". Guests will see a "You must be logged in" message instead of the chat.

= Can I block abusive users? =

Yes. Use **IP Blocklist** (Settings → Security) to block specific IP addresses. Enable the **Word Filter** to block banned words or phrases. Turn on **Abuse Alert** to receive an email when the rate limit is exceeded.

= Does the floating widget work without WooCommerce? =

Yes — the Floating Chat Widget in the free plugin works on any WordPress site. Enable it in Settings → UI, choose position (bottom-right or bottom-left), set a label, and it appears on every page automatically.

= Can I have different chatbots on different pages? =

Yes. Use Bot Profiles (Settings → Profiles) to save named configurations, then load them per page: `[eaic_chat profile="support-bot"]` on one page and `[eaic_chat profile="sales-bot"]` on another. Each profile has its own provider, title, and system prompt.

= How does the webhook work? =

After each AI response completes, the plugin sends a non-blocking POST request to your configured URL with a JSON body containing: `session_uuid`, `user_message`, `ai_response`, `provider`, `timestamp`, and `site_url`. Optionally add a secret key to receive a `X-EAIC-Signature` HMAC-SHA256 header for verification. Compatible with Zapier, Make (Integromat), n8n, and any HTTP endpoint.

= Does voice input work on all browsers? =

Voice input uses the browser's Web Speech API which is supported in Chrome, Edge, and Safari. It requires HTTPS. Firefox does not support this API.

= Is the math captcha accessible? =

The captcha is a simple arithmetic question (e.g., "3 + 7 = ?") that appears above the chat input. It requires no external service, no cookies, and no images — just basic arithmetic. It is solved once per page load.

= Where can I get support? =

* 📺 Video tutorials: [youtube.com/@easybdit](https://www.youtube.com/@easybdit)
* 💬 Facebook: [facebook.com/easybdit](https://www.facebook.com/easybdit)
* 🌐 Website: [easyit.com.bd](https://easyit.com.bd)
* 📧 Email: support@easyit.com.bd
* 🐛 Bug reports: WordPress.org support forum

== Screenshots ==

1. Chat interface — conversation history sidebar, user and AI messages, timestamps, copy and read-aloud buttons.
2. Settings page — provider tabs, API keys, Test Connection button, UI customization, and Security Suite.
3. Analytics dashboard — total conversations, messages, 7-day bar chart, feedback ratings.
4. Shortcode Builder — visual builder generates shortcode live; copy with one click.
5. Floating chat widget — fixed launcher button that opens a slide-up chat panel on every page.
6. Security settings — Access Restriction, IP Blocklist, Word Filter, Captcha, and more.

== Changelog ==

= 2.3.4 =
* Fix: Plugin Check compliance — replaced move_uploaded_file() with wp_handle_upload(), escaped exception output, added phpcs:ignore for set_time_limit() and table-name DB queries, prefixed view variables with eaic_ namespace.

= 2.3.3 =
* Fix: Ollama now streams tokens in real time — first word appears within seconds instead of waiting for the full response to complete.

= 2.3.2 =
* Fix: RAG count queries ("how many X") now use keyword-matched chunks as context, preventing wrong items from appearing when the embedding model returns poor results.
* Fix: Embedding timeout reduced from 60 s to 8 s so keyword fallback kicks in quickly when the embedding model is slow or misconfigured.
* New: Copy and Download buttons added to every AI response message — always visible, no hover required.
* Fix: Plugin version bump ensures browsers load updated JS/CSS (cache-bust).

= 2.3.1 =
* Fix: RAG document processing — per-chunk embedding wrapped in try/catch so one bad chunk no longer aborts the whole document.
* Fix: PHP `set_time_limit` extended during processing to prevent timeout on large PDFs.
* Fix: Stuck documents in "processing" state are reset to "error" on next admin page load so they can be re-processed.
* Fix: DB Setup button now reliably creates tables when RAG is first enabled.

= 2.3.0 =
* New: **Knowledge Base (RAG)** — upload PDF documents; the AI answers questions using only your document data (Retrieval-Augmented Generation).
* New: Semantic search via Ollama embeddings with automatic keyword fallback when embeddings are unavailable.
* New: Accurate "how many X" counting — scans all chunks with plural/singular stemming for exact results.
* New: Admin Test Query UI — inspect which document chunks are retrieved for any query.
* New: Document status auto-polling — admin page refreshes processing status without a manual reload.
* New: Configurable chunk size, chunk overlap, top-K results, similarity threshold, and embedding model.

= 2.1.1 =
* Fix: Custom provider settings now save reliably (JSON decode fallback prevents silent data loss on save).
* Fix: Settings page stays on the active tab after saving (sessionStorage-based tab restore).
* Fix: Custom provider slugs (custom_1, custom_2 …) are now accepted as the Default Provider.

= 2.1.0 =
* New: Custom Providers — add any OpenAI-compatible API endpoint (LM Studio, custom gateway, Ollama proxy, etc.) via Settings → Custom tab. Use with `[eaic_chat provider="custom_1"]`. Includes per-provider Test Connection button, Enable/Disable toggle, configurable URL, API key, model, and timeout.

= 2.0.1 =
* Bug fixes from code audit: fixed critical array key mismatch in feedback ownership check (feedback now correctly accepts/rejects based on actual session owner), fixed missing closing div in AI Avatar settings card (cosmetic HTML issue), improved no-storage mode (no longer creates orphan session records in DB), made `frontend_i18n()` a static method, updated Anthropic default model, fixed `Tested up to` header (6.8), improved copy button error handling in Shortcode Builder.

= 2.0.0 =
* **Major release — Security Suite.** Eight new security features in Settings → Security:
* **Access Restriction** — Allow everyone, logged-in users only, or specific user roles.
* **IP Blocklist** — Block specific IP addresses from using the chat.
* **Word Filter** — Block or warn when a message contains banned words/phrases (case-insensitive, one per line).
* **Prompt Injection Detection** — Detect and block common jailbreak/injection patterns automatically.
* **No-Storage Mode** — Opt out of saving conversations to the database entirely (GDPR-strict setups).
* **Anti-Bot Math Captcha** — Simple arithmetic challenge before the first message. No external API required.
* **Abuse Alert Email** — Get an email when the rate limit is exceeded. Configurable recipient.
* **Message Length Limit** — Configure max characters per user message (50–4000, default 4000).

= 1.0.31 =
* Added Webhook Support — configure a webhook URL in Settings → Webhook. After each AI response, the plugin sends a non-blocking POST to your URL with JSON payload: session_uuid, user_message, ai_response, provider, timestamp. Optional HMAC-SHA256 secret key adds an X-EAIC-Signature header for verification. Compatible with Zapier, Make (Integromat), n8n, and any HTTP endpoint.

= 1.0.30 =
* Added Multiple Bot Profiles — create named configurations (slug, name, provider, title, system prompt) in Settings → Profiles. Load any profile with `[eaic_chat profile="your-slug"]`. Profiles appear in the Shortcode Builder for one-click selection.

= 1.0.29 =
* Added Shortcode Builder — a new admin page (EasyIT AI Chat → Shortcode Builder) lets you configure the chatbot visually. Choose provider, title, placeholder, height, and system prompt with dropdowns and inputs. The shortcode updates live and a Copy button puts it on your clipboard instantly. No typing required.

= 1.0.28 =
* Added Context Length Control — a new "Context Window (messages)" setting (1–20, default 10) controls how many previous messages are sent to the AI with each request. Lower values reduce token cost; higher values give the AI more conversation memory. Find it in Settings → AI Behavior.

= 1.0.27 =
* Added Read Aloud (TTS) — a speaker button appears on hover over any AI message. Click to have the browser read the message aloud using the Web Speech API (SpeechSynthesis). Click again to stop. No external service required — uses the built-in browser engine.

= 1.0.26 =
* Added GDPR Consent Gate — optionally show a consent banner before the chat activates. Users must click an accept button before chatting. Consent is stored in a browser cookie (365 days). Configurable message text and button label. Enable in Settings → UI.

= 1.0.25 =
* Added Fullscreen Mode — an expand button in the chat topbar switches the widget to full-screen overlay. Press Escape or click again to exit. Works on any page without layout changes.

= 1.0.24 =
* Added Session Search — a search box in the chat sidebar lets users filter conversations by title in real time. No extra server requests — filtering is client-side.

= 1.0.23 =
* Added Message Feedback — 👍/👎 buttons appear below each AI response. Ratings are stored in your database and visible in the Analytics dashboard (Helpful / Not helpful counts). Feedback resets if you regenerate a response.

= 1.0.22 =
* Added Copy Message — hover any AI response to reveal a copy button. Copies the plain text of the message to the clipboard. Shows a checkmark confirmation on success.

= 1.0.21 =
* Added Message Timestamps — each chat message now shows the time it was sent (HH:MM format) next to the sender label. Timestamps are preserved when loading conversation history.

= 1.0.20 =
* Added Stop & Regenerate — the Send button turns into a red Stop button while the AI is responding. Click Stop to cancel the stream at any time. After a response completes, a Regenerate button appears below the last AI message to re-run the same query.

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

= 2.3.2 =
Fix: RAG count queries now show correct items. Embedding timeout reduced for faster responses. New Copy & Download buttons on AI messages.

= 2.3.0 =
New: Knowledge Base (RAG) — upload PDF documents and let the AI answer questions from your own data.

= 2.0.0 =
Major release: 8 new security features — Access Restriction, IP Blocklist, Word Filter, Prompt Injection Detection, No-Storage Mode, Math Captcha, Abuse Alert, Message Length Limit.

= 1.0.31 =
New: Webhook support — POST to any URL after each AI response. JSON payload + optional HMAC signature. Settings → Webhook.

= 1.0.30 =
New: Multiple Bot Profiles — named configurations loaded via profile="slug" shortcode attribute.

= 1.0.29 =
New: Shortcode Builder admin page — configure chatbot visually, get shortcode instantly. No typing required.

= 1.0.28 =
New: Context window setting (1–20 messages) to control AI memory per request. Lowers token cost on long chats.

= 1.0.27 =
New: Read Aloud (TTS) — speaker button on AI messages, uses browser SpeechSynthesis, no external service.

= 1.0.26 =
New: GDPR Consent Gate — cookie-based consent banner before chat starts. Configurable text. Enable in Settings → UI.

= 1.0.25 =
New: Fullscreen mode — expand button in topbar, Escape to exit.

= 1.0.24 =
New: Session search box in sidebar — filter conversations by title, client-side, instant.

= 1.0.23 =
New: Message feedback (👍/👎) on AI responses. Ratings tracked in DB and shown in Analytics.

= 1.0.22 =
New: Copy button on AI messages — hover to reveal, click to copy to clipboard.

= 1.0.21 =
New: Message timestamps (HH:MM) displayed next to each message label.

= 1.0.20 =
New: Stop button cancels AI response mid-stream; Regenerate button re-runs the last query.

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