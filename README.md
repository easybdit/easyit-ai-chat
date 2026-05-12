<div align="center">

<img src="https://avatars.githubusercontent.com/u/283857424?s=80&v=4" width="80" height="80" style="border-radius:16px" alt="EasyIT">

# EasyIT AI Chat

**Unified AI chatbot plugin for WordPress**

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b?style=flat-square&logo=wordpress&logoColor=white)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-green?style=flat-square)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.0.0-orange?style=flat-square)](https://github.com/easybdit/easyit-ai-chat/releases)
[![Organization](https://img.shields.io/badge/By-EasyIT-0f3460?style=flat-square)](https://github.com/easybdit)

Connect **Ollama · OpenAI · Anthropic Claude · DeepSeek** to any WordPress page with one shortcode.

[Installation](#-installation) · [Shortcode](#-shortcode-reference) · [Providers](#-provider-setup) · [Roadmap](#-roadmap) · [Contributing](#-contributing)

</div>

---

## 📸 Preview

| Public Chat Page | Admin Settings | Admin Test Chat |
|:---:|:---:|:---:|
| ChatGPT-style UI with dark sidebar | Tabbed provider config | Test any provider instantly |

---

## ✨ Features

| Feature | Description |
|---------|-------------|
| 🦙 **Multi-provider** | Ollama (free/local), OpenAI, Anthropic Claude, DeepSeek |
| 💬 **ChatGPT-style UI** | Dark sidebar, session list, full conversation history |
| 🗄️ **Persistent sessions** | Conversations saved to your WordPress database |
| 📝 **Markdown rendering** | Code blocks, bold, italic, lists, headings |
| 🔀 **Provider switcher** | Switch AI provider per conversation |
| 👥 **Guest support** | Non-logged-in users via cookie-based sessions |
| 🔒 **Privacy-ready** | GDPR notice, configurable data retention |
| 🚦 **Rate limiting** | 20 req/60s per user/guest |
| 🧪 **Admin test chat** | Test providers from your dashboard |
| 📱 **Responsive** | Desktop, tablet & mobile |
| 🔐 **Secure** | Nonce verification, input sanitization, prepared queries |

---

## 🚀 Installation

### Method 1 — Upload ZIP *(recommended)*
1. Download [`easyit-ai-chat-v1.0.0.zip`](https://github.com/easybdit/easyit-ai-chat/releases/latest)
2. WordPress Admin → **Plugins → Add New → Upload Plugin**
3. Choose the ZIP → **Install Now** → **Activate**

### Method 2 — Git Clone
```bash
cd /path/to/wp-content/plugins/
git clone https://github.com/easybdit/easyit-ai-chat.git
```
Then activate from **Plugins → Installed Plugins**.

### Method 3 — Manual
Extract the ZIP into `/wp-content/plugins/easyit-ai-chat/` and activate.

---

## ⚙️ Quick Setup

1. Go to **WordPress Admin → EasyIT AI Chat → Settings**
2. Pick your provider tab (Ollama, OpenAI, Anthropic, or DeepSeek)
3. Enter the server URL or API key + model name
4. Click **Test Connection** to verify
5. Click **Save Settings**
6. Add `[easyit_ai_chat]` to any page or post

---

## 📋 Shortcode Reference

```
[easyit_ai_chat]
```

| Attribute | Default | Options |
|-----------|---------|---------|
| `provider` | *(settings default)* | `ollama` \| `openai` \| `anthropic` \| `deepseek` |
| `title` | `AI Chat` | Any text |
| `placeholder` | `Ask me anything…` | Any text |
| `system_prompt` | *(settings default)* | Any text |
| `height` | `600` | Integer (pixels) |

**Examples:**
```
[easyit_ai_chat]

[easyit_ai_chat provider="openai" height="700"]

[easyit_ai_chat provider="anthropic" title="Claude Assistant"]

[easyit_ai_chat provider="ollama" system_prompt="You are a helpful support agent for EasyIT."]
```

---

## 🔧 Provider Setup

### 🦙 Ollama — Free & Local
No API key. Runs on your server or local machine.

```bash
# Install Ollama: https://ollama.com
ollama pull qwen2:1.5b    # fast & lightweight
ollama pull llama3         # powerful
ollama pull mistral        # balanced
```

Set **Ollama URL** in settings to `http://localhost:11434` (or your server IP).

### ✨ OpenAI (ChatGPT)
1. Get API key → [platform.openai.com/api-keys](https://platform.openai.com/api-keys)
2. Recommended models: `gpt-4o-mini`, `gpt-4o`, `gpt-3.5-turbo`

### 🎭 Anthropic (Claude)
1. Get API key → [console.anthropic.com](https://console.anthropic.com)
2. Recommended models: `claude-3-5-sonnet-20241022`, `claude-3-haiku-20240307`

### 🔍 DeepSeek
1. Get API key → [platform.deepseek.com](https://platform.deepseek.com)
2. Recommended models: `deepseek-chat`, `deepseek-reasoner`

---

## 🗂️ Project Structure

```
easyit-ai-chat/
├── easyit-ai-chat.php              ← Main plugin entry point
├── uninstall.php                   ← Cleanup on plugin deletion
├── readme.txt                      ← WordPress.org readme
├── README.md                       ← This file
├── CHANGELOG.md                    ← Version history
├── LICENSE                         ← GPL-2.0-or-later
│
├── admin/
│   ├── class-easyit-ai-chat-admin.php   ← Admin menus, settings, enqueue
│   ├── assets/
│   │   ├── admin.css
│   │   └── admin.js
│   └── views/
│       ├── settings-page.php            ← Tabbed settings UI
│       └── test-chat-page.php           ← Admin test chat page
│
├── includes/
│   ├── class-easyit-ai-chat-options.php     ← Settings manager
│   ├── class-easyit-ai-chat-provider.php    ← Abstract provider base
│   ├── class-easyit-ai-chat-db.php          ← Database layer
│   ├── class-easyit-ai-chat-engine.php      ← AJAX handlers
│   └── providers/
│       ├── class-easyit-ai-chat-ollama.php
│       ├── class-easyit-ai-chat-openai.php
│       ├── class-easyit-ai-chat-anthropic.php
│       └── class-easyit-ai-chat-deepseek.php
│
├── public/
│   ├── class-easyit-ai-chat-public.php  ← Shortcode + enqueue
│   ├── css/chat.css                     ← Widget styles
│   └── js/chat.js                       ← Widget JavaScript
│
└── languages/                           ← i18n (.po/.mo files)
```

---

## 🔐 Security

- ✅ All AJAX requests verified with `check_ajax_referer()`
- ✅ All `$_POST` input sanitized (`sanitize_text_field`, `sanitize_key`, etc.)
- ✅ All DB queries use `$wpdb->prepare()` or safe WP methods
- ✅ Session ownership verified before any data access
- ✅ Rate limiting: 20 requests / 60 seconds per user or guest
- ✅ API keys stored server-side only, never sent to browser
- ✅ All output escaped (`esc_html`, `esc_attr`, `esc_url`, `esc_js`)
- ✅ `wp_die()` called after all JSON error responses

---

## 🛣️ Roadmap

### ✅ v1.0.0 — Free (Current)
- Multi-provider: Ollama, OpenAI, Anthropic, DeepSeek
- ChatGPT-style UI with persistent sessions
- Guest chat, rate limiting, GDPR notice
- Admin settings + test chat

### 🔲 v2.0.0 — Premium *(coming soon)*
- Floating chat bubble widget
- Custom AI personas per page/post
- File & image upload support
- Conversation export (CSV / PDF)
- Analytics dashboard
- Webhook / Zapier integrations
- WooCommerce product assistant
- WPML / Polylang multilingual
- Priority support

---

## 🤝 Contributing

Contributions are welcome!

```bash
# 1. Fork this repo on GitHub
# 2. Clone your fork
git clone https://github.com/YOUR-USERNAME/easyit-ai-chat.git

# 3. Create a feature branch
git checkout -b feature/your-feature-name

# 4. Make changes following WordPress Coding Standards
# https://developer.wordpress.org/coding-standards/

# 5. Commit with a clear message
git commit -m "Add: description of your change"

# 6. Push and open a Pull Request
git push origin feature/your-feature-name
```

### Reporting Bugs
Please use [GitHub Issues](https://github.com/easybdit/easyit-ai-chat/issues) and include:
- WordPress version
- PHP version
- Plugin version
- Steps to reproduce
- Expected vs actual behaviour

---

## 📄 License

**GPL-2.0-or-later** — see [LICENSE](LICENSE)

This plugin is free software. You can redistribute and/or modify it under the terms of the GNU General Public License (version 2 or later).

---

## 👨‍💻 About EasyIT

Built with ❤️ by **[EasyIT](https://github.com/easybdit)** — Bangladesh 🇧🇩

> Making AI accessible for every WordPress site.

---

<div align="center">
  <sub>⭐ Star this repo if it helped you!</sub>
</div>
