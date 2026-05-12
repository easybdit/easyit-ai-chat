<div align="center">

# WP Easy AI Chat

**Unified AI chatbot plugin for WordPress**

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b?style=flat-square&logo=wordpress&logoColor=white)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-green?style=flat-square)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.0.0-orange?style=flat-square)](https://github.com/easybdit/wpeasyai/releases)
[![Donate](https://img.shields.io/badge/Donate-💛-yellow?style=flat-square)](https://easyit.com.bd/donate)

Connect **Ollama · OpenAI · Anthropic Claude · DeepSeek** to any WordPress page.

**Shortcode:** `[easyai]`

[Install](#-installation) · [Shortcode](#-shortcode) · [Providers](#-provider-setup) · [Roadmap](#-roadmap) · [Donate](https://easyit.com.bd/donate)

</div>

---

## ✨ Features

| | Feature | Detail |
|--|---------|--------|
| 🦙 | **Multi-provider** | Ollama (free/local), OpenAI, Anthropic Claude, DeepSeek |
| 💬 | **ChatGPT-style UI** | Dark sidebar, session list, full conversation history |
| 🗄️ | **Persistent sessions** | Saved to your own WordPress database |
| 📝 | **Markdown** | Code blocks, bold, italic, lists, headings |
| 🔀 | **Provider switcher** | Switch AI per conversation |
| 👥 | **Guest support** | Non-logged-in users via cookie sessions |
| 🔒 | **Privacy-ready** | GDPR notice, configurable data retention |
| 🚦 | **Rate limiting** | 20 req / 60s per user |
| 🧪 | **Test chat** | Test any provider from the dashboard |
| 📱 | **Responsive** | Desktop, tablet & mobile |
| 🔐 | **Secure** | Nonces, sanitization, prepared queries |

---

## 🚀 Installation

**Method 1 — ZIP upload**
1. Download [`wpeasyai-v1.0.0.zip`](https://github.com/easybdit/wpeasyai/releases/latest)
2. WP Admin → **Plugins → Add New → Upload Plugin**
3. Activate ✅

**Method 2 — Git**
```bash
cd /wp-content/plugins/
git clone https://github.com/easybdit/wpeasyai.git
```

---

## 📋 Shortcode

```
[easyai]
```

| Attribute | Default | Values |
|-----------|---------|--------|
| `provider` | *(settings)* | `ollama` · `openai` · `anthropic` · `deepseek` |
| `title` | `AI Chat` | Any text |
| `placeholder` | `Ask me anything…` | Any text |
| `system_prompt` | *(settings)* | Any text |
| `height` | `600` | Pixels |

**Examples:**
```
[easyai]
[easyai provider="openai" height="700"]
[easyai provider="anthropic" title="Claude Assistant"]
[easyai system_prompt="You are an EasyIT support agent."]
```

---

## 🔧 Provider Setup

### 🦙 Ollama — Free, No API key
```bash
ollama pull qwen2:1.5b   # lightweight
ollama pull llama3        # powerful
```
Set URL: `http://localhost:11434` or your server IP.

### ✨ OpenAI → [platform.openai.com](https://platform.openai.com/api-keys)
Models: `gpt-4o-mini`, `gpt-4o`, `gpt-3.5-turbo`

### 🎭 Anthropic → [console.anthropic.com](https://console.anthropic.com)
Models: `claude-3-5-sonnet-20241022`, `claude-3-haiku-20240307`

### 🔍 DeepSeek → [platform.deepseek.com](https://platform.deepseek.com)
Models: `deepseek-chat`, `deepseek-reasoner`

---

## 🗂️ Structure

```
wpeasyai/
├── wpeasyai.php                     ← Main plugin file
├── uninstall.php
├── readme.txt                       ← WordPress.org
├── README.md
├── CHANGELOG.md
├── LICENSE
├── admin/
│   ├── class-wpeasyai-admin.php
│   ├── assets/ (admin.css, admin.js)
│   └── views/ (settings-page.php, test-chat-page.php)
├── includes/
│   ├── class-wpeasyai-options.php
│   ├── class-wpeasyai-provider.php
│   ├── class-wpeasyai-db.php
│   ├── class-wpeasyai-engine.php
│   └── providers/
│       ├── class-wpeasyai-ollama.php
│       ├── class-wpeasyai-openai.php
│       ├── class-wpeasyai-anthropic.php
│       └── class-wpeasyai-deepseek.php
├── public/
│   ├── class-wpeasyai-public.php
│   ├── css/chat.css
│   └── js/chat.js
└── languages/
```

---

## 🛣️ Roadmap

**v1.0.0 — Free ✅**
- Multi-provider AI chat with persistent sessions
- Admin settings + test chat

**v2.0.0 — Premium 🔲**
- Floating chat bubble
- Custom AI personas per page
- File/image upload
- Conversation export (CSV/PDF)
- Analytics dashboard
- WooCommerce product assistant
- Webhook integrations

---

## 🤝 Contributing

```bash
git clone https://github.com/easybdit/wpeasyai.git
git checkout -b feature/your-feature
# Follow WordPress Coding Standards
git commit -m "feat: your change"
git push origin feature/your-feature
# Open Pull Request
```

Bug reports → [GitHub Issues](https://github.com/easybdit/wpeasyai/issues)

---

## 💛 Support & Donate

If this plugin helps your site, consider supporting development:

**[💛 Donate via easyit.com.bd/donate](https://easyit.com.bd/donate)**

---

## 📄 License

GPL-2.0-or-later · [LICENSE](LICENSE)

---

<div align="center">

Built with ❤️ by **[EasyIT](https://easyit.com.bd)** — Bangladesh 🇧🇩

📧 [muradbd.info@gmail.com](mailto:muradbd.info@gmail.com) · 🌐 [easyit.com.bd](https://easyit.com.bd) · 🐙 [github.com/easybdit](https://github.com/easybdit)

⭐ Star this repo if it helped you!

</div>
