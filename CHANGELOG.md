# Changelog

All notable changes to **EasyIT AI Chat** are documented here.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) · Versioning: [SemVer](https://semver.org)

---

## [1.0.0] — 2025-05-12

### Added
- Initial public release by [EasyIT](https://github.com/easybdit)
- Multi-provider support: Ollama, OpenAI (ChatGPT), Anthropic (Claude), DeepSeek
- ChatGPT-style UI with dark sidebar, session list, conversation history
- Persistent multi-turn conversations stored in WordPress database
- Provider switcher per conversation session
- Markdown rendering: bold, italic, inline code, fenced code blocks, lists, headings
- Copy-to-clipboard on code blocks
- Guest chat support via first-party cookie sessions
- Rate limiting: 20 requests / 60 seconds per user or guest
- GDPR-friendly privacy notice with link to Privacy Policy page
- Configurable data retention period (auto-purge old conversations)
- Admin settings page with tabbed provider configuration
- Test Connection button per provider
- Admin Test Chat page to verify providers from the dashboard
- `[easyit_ai_chat]` shortcode with attributes: `provider`, `title`, `placeholder`, `system_prompt`, `height`
- Fully responsive layout (desktop, tablet, mobile)
- `uninstall.php` removes all tables, options, and transients on plugin deletion
- GPL-2.0-or-later license
- WordPress.org compliant readme.txt
- Full PHPDoc on all classes and methods

---

## [Unreleased] — Premium v2.0.0

### Planned
- Floating chat bubble widget
- Custom AI personas per page/post
- File and image upload support
- Conversation export (CSV / PDF)
- Analytics dashboard
- Webhook / Zapier integrations
- WooCommerce product assistant mode
- WPML / Polylang multilingual support
- Priority email support
