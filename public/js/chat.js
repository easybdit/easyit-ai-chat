/* global WPEasyAIConfig */
(function () {
	'use strict';

	var cfg  = window.WPEasyAIConfig || {};
	var i18n = cfg.i18n || {};

	/* ── Markdown renderer ── */
	function esc(s) {
		return String(s)
			.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
			.replace(/"/g,'&quot;').replace(/'/g,'&#39;');
	}

	function md(text) {
		text = text.replace(/```(\w*)\n?([\s\S]*?)```/g, function (_, lang, code) {
			var label = lang || 'code';
			return '<div class="weai-code-block">'
				+ '<div class="weai-code-header"><span>' + esc(label) + '</span>'
				+ '<button class="weai-code-copy" data-code="' + esc(code.trimEnd()) + '">' + (i18n.copy || 'Copy') + '</button></div>'
				+ '<pre>' + esc(code.trimEnd()) + '</pre></div>';
		});
		text = text.replace(/`([^`]+)`/g, function (_, c) { return '<code>' + esc(c) + '</code>'; });
		text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
		text = text.replace(/\*([^*]+)\*/g, '<em>$1</em>');
		text = text.replace(/^### (.+)$/gm, '<h3>$1</h3>');
		text = text.replace(/^## (.+)$/gm,  '<h2>$1</h2>');
		text = text.replace(/^# (.+)$/gm,   '<h1>$1</h1>');
		text = text.replace(/((?:^[-*] .+\n?)+)/gm, function (block) {
			var items = block.trim().split('\n').map(function (l) { return '<li>' + l.replace(/^[-*] /, '') + '</li>'; }).join('');
			return '<ul>' + items + '</ul>';
		});
		text = text.replace(/((?:^\d+\. .+\n?)+)/gm, function (block) {
			var items = block.trim().split('\n').map(function (l) { return '<li>' + l.replace(/^\d+\. /, '') + '</li>'; }).join('');
			return '<ol>' + items + '</ol>';
		});
		text = text.split(/\n{2,}/).map(function (p) {
			p = p.trim();
			if (!p || /^<[houpl]/.test(p)) return p;
			return '<p>' + p.replace(/\n/g, '<br>') + '</p>';
		}).join('\n');
		return text;
	}

	/* ── Widget init ── */
	function initWidget(widget) {
		var provider     = widget.dataset.provider || cfg.default_provider || 'ollama';
		var systemPrompt = widget.dataset.systemPrompt || '';
		var sessionUuid  = null;

		var sessionsList = widget.querySelector('.weai-sessions-list');
		var messages     = widget.querySelector('.weai-messages');
		var input        = widget.querySelector('.weai-input');
		var sendBtn      = widget.querySelector('.weai-send-btn');
		var newChatBtn   = widget.querySelector('.weai-new-chat-btn');
		var provSelect   = widget.querySelector('.weai-provider-select');
		var toggleBtn    = widget.querySelector('.weai-toggle-sidebar');
		var deleteBtn    = widget.querySelector('.weai-delete-session-btn');
		var titleEl      = widget.querySelector('.weai-session-title');

		if (!messages || !input || !sendBtn) return;

		/* Sessions */
		function loadSessions() {
			ajax('wpeasyai_sessions', {}, function (res) {
				if (res.success) renderSessions(res.data.sessions || []);
			});
		}

		function renderSessions(sessions) {
			if (!sessions.length) {
				sessionsList.innerHTML = '<div style="padding:12px 10px;font-size:12px;color:rgba(255,255,255,.3)">' + (i18n.no_sessions || 'No conversations yet.') + '</div>';
				return;
			}
			var groups = { today: [], yesterday: [], older: [] };
			var now = new Date(), today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
			var yesterday = new Date(today); yesterday.setDate(yesterday.getDate() - 1);
			sessions.forEach(function (s) {
				var d = new Date(s.updated_at.replace(' ', 'T'));
				if (d >= today) groups.today.push(s);
				else if (d >= yesterday) groups.yesterday.push(s);
				else groups.older.push(s);
			});
			var html = '';
			function renderGroup(label, list) {
				if (!list.length) return;
				html += '<div class="weai-session-group-label">' + esc(label) + '</div>';
				list.forEach(function (s) {
					var active = s.uuid === sessionUuid ? ' weai-active' : '';
					html += '<div class="weai-session-item' + active + '" data-uuid="' + esc(s.uuid) + '">'
						+ '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" style="flex-shrink:0"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>'
						+ '<span class="weai-session-title-text">' + esc(s.title) + '</span>'
						+ '<button class="weai-session-del" data-uuid="' + esc(s.uuid) + '" title="Delete">'
						+ '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>'
						+ '</button></div>';
				});
			}
			renderGroup(i18n.today || 'Today', groups.today);
			renderGroup(i18n.yesterday || 'Yesterday', groups.yesterday);
			renderGroup('Earlier', groups.older);
			sessionsList.innerHTML = html;
		}

		function switchSession(uuid) {
			sessionUuid = uuid;
			widget.querySelectorAll('.weai-session-item').forEach(function (el) {
				el.classList.toggle('weai-active', el.dataset.uuid === uuid);
			});
			clearMessages();
			ajax('wpeasyai_history', { session: uuid }, function (res) {
				if (!res.success) return;
				if (res.data.provider) { provider = res.data.provider; if (provSelect) provSelect.value = provider; }
				if (titleEl && res.data.title) titleEl.textContent = res.data.title;
				var msgs = res.data.messages || [];
				if (msgs.length) {
					hideWelcome();
					msgs.forEach(function (m) { appendMessage(m.role, m.content, null, false); });
					scrollBottom();
				} else {
					showWelcome();
				}
			});
		}

		/* Messages */
		function clearMessages() { messages.innerHTML = ''; }

		function showWelcome() {
			if (!messages.querySelector('.weai-welcome')) {
				var w = document.createElement('div');
				w.className = 'weai-welcome';
				w.innerHTML = '<div class="weai-welcome-icon">\uD83E\uDD16</div>'
					+ '<h3 class="weai-welcome-title">' + esc(titleEl ? titleEl.dataset.orig || titleEl.textContent : 'AI Chat') + '</h3>'
					+ '<p class="weai-welcome-sub">How can I help you today?</p>';
				messages.appendChild(w);
			}
		}

		function hideWelcome() { var w = messages.querySelector('.weai-welcome'); if (w) w.remove(); }

		function appendMessage(role, content, providerLabel, scroll) {
			hideWelcome();
			var div = document.createElement('div');
			div.className = 'weai-msg weai-msg--' + (role === 'user' ? 'user' : 'assistant');
			var avatarText = role === 'user' ? (i18n.you || 'You').charAt(0) : '\uD83E\uDD16';
			var bubble = role === 'user'
				? '<div class="weai-msg-bubble">' + esc(content) + '</div>'
				: '<div class="weai-msg-bubble">' + md(content) + '</div>';
			var meta = providerLabel ? '<div class="weai-msg-meta">' + esc(providerLabel) + '</div>' : '';
			div.innerHTML = '<div class="weai-msg-avatar">' + avatarText + '</div>'
				+ '<div class="weai-msg-body">' + bubble + meta + '</div>';
			div.querySelectorAll('.weai-code-copy').forEach(function (btn) {
				btn.addEventListener('click', function () {
					if (navigator.clipboard) {
						navigator.clipboard.writeText(btn.dataset.code || '').then(function () {
							btn.textContent = i18n.copied || 'Copied!';
							setTimeout(function () { btn.textContent = i18n.copy || 'Copy'; }, 1500);
						});
					}
				});
			});
			messages.appendChild(div);
			if (scroll !== false) scrollBottom();
			return div;
		}

		function appendTyping() {
			var div = document.createElement('div');
			div.className = 'weai-msg weai-msg--assistant';
			div.innerHTML = '<div class="weai-msg-avatar">\uD83E\uDD16</div>'
				+ '<div class="weai-msg-body"><div class="weai-msg-bubble"><div class="weai-typing">'
				+ '<div class="weai-typing-dot"></div><div class="weai-typing-dot"></div><div class="weai-typing-dot"></div>'
				+ '</div></div></div>';
			messages.appendChild(div);
			scrollBottom();
			return div;
		}

		function scrollBottom() { messages.scrollTop = messages.scrollHeight; }

		/* Send */
		function send() {
			var text = input.value.trim();
			if (!text) return;
			input.value = '';
			input.style.height = '';
			sendBtn.disabled = true;
			appendMessage('user', text, null, true);
			var typing = appendTyping();
			ajax('wpeasyai_send', { message: text, provider: provider, session: sessionUuid || '', system: systemPrompt }, function (res) {
				typing.remove();
				if (!res.success) {
					appendMessage('assistant', (res.data && res.data.message) ? res.data.message : (i18n.error_generic || 'Error'), null, true);
					return;
				}
				sessionUuid = res.data.session;
				var label = cfg.show_provider_badge ? (res.data.provider + (res.data.model ? ' \u00b7 ' + res.data.model : '')) : null;
				appendMessage('assistant', res.data.reply, label, true);
				loadSessions();
			});
		}

		/* Events */
		sendBtn.addEventListener('click', send);
		input.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); if (!sendBtn.disabled) send(); }
		});
		input.addEventListener('input', function () {
			sendBtn.disabled = !input.value.trim();
			input.style.height = 'auto';
			input.style.height = Math.min(120, input.scrollHeight) + 'px';
		});

		if (newChatBtn) {
			newChatBtn.addEventListener('click', function () {
				sessionUuid = null;
				clearMessages();
				showWelcome();
				if (titleEl) titleEl.textContent = titleEl.dataset.orig || titleEl.textContent;
				loadSessions();
				input.focus();
			});
		}
		if (provSelect) provSelect.addEventListener('change', function () { provider = provSelect.value; });
		if (toggleBtn)  toggleBtn.addEventListener('click', function () { widget.classList.toggle('weai-sidebar-hidden'); });

		if (deleteBtn) {
			deleteBtn.addEventListener('click', function () {
				if (!sessionUuid) return;
				if (!confirm(i18n.delete_confirm || 'Delete this conversation?')) return;
				ajax('wpeasyai_delete', { session: sessionUuid }, function (res) {
					if (res.success) { sessionUuid = null; clearMessages(); showWelcome(); loadSessions(); }
				});
			});
		}

		sessionsList.addEventListener('click', function (e) {
			var delBtn = e.target.closest('.weai-session-del');
			if (delBtn) {
				e.stopPropagation();
				if (!confirm(i18n.delete_confirm || 'Delete?')) return;
				ajax('wpeasyai_delete', { session: delBtn.dataset.uuid }, function (res) {
					if (res.success) {
						if (delBtn.dataset.uuid === sessionUuid) { sessionUuid = null; clearMessages(); showWelcome(); }
						loadSessions();
					}
				});
				return;
			}
			var item = e.target.closest('.weai-session-item');
			if (item && item.dataset.uuid) switchSession(item.dataset.uuid);
		});

		if (titleEl) titleEl.dataset.orig = titleEl.textContent;
		loadSessions();
	}

	/* ── AJAX helper ── */
	function ajax(action, data, cb) {
		var params = new URLSearchParams();
		params.append('action', action);
		params.append('nonce',  cfg.nonce || '');
		Object.keys(data).forEach(function (k) { params.append(k, data[k]); });
		fetch(cfg.ajax_url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: params.toString(),
		})
		.then(function (r) { return r.json(); })
		.then(cb)
		.catch(function (err) {
			console.error('WPEasyAI error:', err);
			cb({ success: false, data: { message: i18n.error_generic || 'Error' } });
		});
	}

	/* ── Boot ── */
	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.weai-widget').forEach(initWidget);
	});
}());
