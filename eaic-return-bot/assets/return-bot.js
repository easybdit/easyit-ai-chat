/* EasyIT AI Chat Pro — Return Bot */
(function () {
	'use strict';

	var CFG = window.EAICReturnBot || {};

	function escHtml(s) {
		return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
	}

	function md(text) {
		if (!text) return '';
		text = escHtml(text);
		text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
		text = text.replace(/(^|\n)- (.+)/g, '$1<li>$2</li>');
		text = text.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
		var parts = text.split(/\n{2,}/);
		return parts.map(function(p) {
			p = p.trim();
			if (!p) return '';
			if (/^<(ul|ol|li)/.test(p)) return p;
			return '<p>' + p.replace(/\n/g, '<br>') + '</p>';
		}).join('');
	}

	document.querySelectorAll('.eaic-return-widget').forEach(function(root) {
		var msgs     = root.querySelector('.eaic-return-messages');
		var orderRow = root.querySelector('.eaic-return-order-row');
		var orderIn  = root.querySelector('.eaic-return-order-input');
		var emailIn  = root.querySelector('.eaic-return-order-email');
		var orderBtn = root.querySelector('.eaic-return-order-btn');
		var inputWrap= root.querySelector('.eaic-return-input-wrap');
		var textarea = root.querySelector('.eaic-return-input');
		var sendBtn  = root.querySelector('.eaic-return-send');
		var badge    = root.querySelector('.eaic-return-order-badge');

		var sessionId  = 'ret_' + Math.random().toString(36).slice(2);
		var orderId    = parseInt(root.getAttribute('data-order-id') || '0', 10);
		var orderEmail = '';
		var sending    = false;

		// Quick order selection buttons (shown for logged-in users with recent orders).
		var quickBtns = root.querySelectorAll('.eaic-return-order-quick');
		quickBtns.forEach(function(btn) {
			btn.addEventListener('click', function() {
				var selectedId = parseInt(btn.getAttribute('data-order') || '0', 10);
				if (!selectedId) return;
				orderId = selectedId;
				// Hide quick buttons + order input row.
				var quickWrap = btn.parentNode;
				if (quickWrap && quickWrap.parentNode) { quickWrap.parentNode.style.display = 'none'; }
				if (orderRow) orderRow.style.display = 'none';
				// Show badge + input.
				if (badge) { badge.textContent = '📦 Order #' + orderId; badge.style.display = 'inline-flex'; }
				if (inputWrap) inputWrap.style.display = 'flex';
				appendMsg('assistant', CFG.i18n.order_found || 'Got it! Please tell me which item(s) you want to return and why.');
				if (textarea) textarea.focus();
			});
		});

		// Pre-load order if provided via attribute.
		if (orderId) {
			if (orderRow) orderRow.style.display = 'none';
			if (badge) { badge.textContent = '📦 Order #' + orderId; badge.style.display = 'inline-flex'; }
			appendMsg('assistant', CFG.i18n.greeting || 'Hello! I can help you with a return for this order. What would you like to return?');
		} else {
			if (badge) badge.style.display = 'none';
			if (inputWrap) inputWrap.style.display = 'none';
			appendMsg('assistant', CFG.i18n.ask_order || 'Hello! To start your return, please enter your order number below.');
		}

		// Order number lookup.
		if (orderBtn) {
			orderBtn.addEventListener('click', function() {
				var val = (orderIn ? orderIn.value.trim().replace(/^#/, '') : '');
				if (!val) return;
				orderId    = parseInt(val, 10) || 0;
				orderEmail = emailIn ? emailIn.value.trim() : '';
				if (!orderId) { appendMsg('assistant', CFG.i18n.invalid_order || 'Please enter a valid order number.'); return; }
				if (orderRow) orderRow.style.display = 'none';
				if (badge) { badge.textContent = '📦 Order #' + orderId; badge.style.display = 'inline-flex'; }
				if (inputWrap) inputWrap.style.display = 'flex';
				appendMsg('assistant', CFG.i18n.order_found || 'Got it! Please tell me what you want to return and why.');
			});
			if (orderIn) {
				orderIn.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); orderBtn.click(); } });
			}
		}

		// Send message.
		if (sendBtn) sendBtn.addEventListener('click', send);
		if (textarea) {
			textarea.addEventListener('keydown', function(e) {
				if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
			});
			textarea.addEventListener('input', function() {
				this.style.height = 'auto';
				this.style.height = Math.min(this.scrollHeight, 120) + 'px';
				if (sendBtn) sendBtn.disabled = this.value.trim() === '' || sending;
			});
		}

		function send() {
			if (sending || !textarea) return;
			var text = textarea.value.trim();
			if (!text) return;

			sending = true;
			if (sendBtn) sendBtn.disabled = true;
			textarea.disabled = true;
			appendMsg('user', text);
			textarea.value = '';
			textarea.style.height = 'auto';

			var thinkNode = appendThinking();

			var body = new URLSearchParams();
			body.append('action',     'eaic_return_chat');
			body.append('nonce',      CFG.nonce);
			body.append('session_id', sessionId);
			body.append('message',    text);
			body.append('order_id',   orderId);
			body.append('email',      orderEmail);

			fetch(CFG.ajax_url, {
				method: 'POST', credentials: 'same-origin',
				headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
				body: body.toString()
			}).then(function(r) { return r.json(); }).then(function(res) {
				removeThinking(thinkNode);
				if (res && res.success) {
					appendMsg('assistant', res.data.reply, true);
					if (res.data.return_submitted) {
						showSuccess(res.data.return_request_id);
						if (textarea) textarea.disabled = true;
						if (sendBtn)  sendBtn.disabled  = true;
						sending = false;
						return;
					}
				} else {
					appendMsg('assistant', (res && res.data && res.data.message) || (CFG.i18n.error || 'Something went wrong.'));
				}
				finish();
			}).catch(function() {
				removeThinking(thinkNode);
				appendMsg('assistant', CFG.i18n.error || 'Something went wrong. Please try again.');
				finish();
			});
		}

		function finish() {
			sending = false;
			if (sendBtn)  sendBtn.disabled  = false;
			if (textarea) { textarea.disabled = false; textarea.focus(); }
		}

		function appendMsg(role, html, raw) {
			if (!msgs) return;
			var wrap = document.createElement('div');
			wrap.className = 'eaic-return-msg eaic-return-msg--' + role;
			var av = document.createElement('div');
			av.className = 'eaic-return-avatar';
			av.textContent = role === 'user' ? 'Y' : '↩';
			var bub = document.createElement('div');
			bub.className = 'eaic-return-bubble';
			bub.innerHTML = raw ? html : md(html);
			wrap.appendChild(av); wrap.appendChild(bub);
			msgs.appendChild(wrap);
			msgs.scrollTop = msgs.scrollHeight;
		}

		function appendThinking() {
			var wrap = document.createElement('div');
			wrap.className = 'eaic-return-msg eaic-return-msg--assistant eaic-return-thinking';
			var av = document.createElement('div'); av.className = 'eaic-return-avatar'; av.textContent = '↩';
			var bub = document.createElement('div'); bub.className = 'eaic-return-bubble';
			bub.innerHTML = '<div class="eaic-return-typing"><span class="eaic-return-dot"></span><span class="eaic-return-dot"></span><span class="eaic-return-dot"></span></div>';
			wrap.appendChild(av); wrap.appendChild(bub);
			msgs.appendChild(wrap);
			msgs.scrollTop = msgs.scrollHeight;
			return wrap;
		}

		function removeThinking(node) {
			if (node && node.parentNode) node.parentNode.removeChild(node);
		}

		function showSuccess(requestId) {
			var el = document.createElement('div');
			el.className = 'eaic-return-success';
			el.innerHTML = '<div class="eaic-return-success-icon">✅</div>' +
				'<strong>' + (CFG.i18n.submitted || 'Return Request Submitted') + '</strong>' +
				'<span>' + (CFG.i18n.ref || 'Reference') + ' #' + requestId + '</span>';
			if (msgs) { msgs.appendChild(el); msgs.scrollTop = msgs.scrollHeight; }
		}
	});
}());
