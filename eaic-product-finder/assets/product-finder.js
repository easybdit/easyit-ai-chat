/* EasyIT AI Chat Pro — Product Finder Bot */
(function () {
	'use strict';
	var CFG = window.EAICProductFinder || {};

	function escHtml(s) {
		return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
	}
	function md(text) {
		if (!text) return '';
		text = String(text);
		text = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
		text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
		var parts = text.split(/\n{2,}/);
		return parts.map(function(p) {
			p = p.trim(); if (!p) return '';
			return '<p>' + p.replace(/\n/g,'<br>') + '</p>';
		}).join('');
	}
	function stars(rating) {
		var full = Math.round(parseFloat(rating) || 0);
		var s = '';
		for (var i = 0; i < 5; i++) s += i < full ? '★' : '☆';
		return s;
	}

	document.querySelectorAll('.eaic-finder-widget').forEach(function(root) {
		var msgs    = root.querySelector('.eaic-finder-messages');
		var textarea= root.querySelector('.eaic-finder-input');
		var sendBtn = root.querySelector('.eaic-finder-send');
		var chips   = root.querySelector('.eaic-finder-chips');
		var sessionId = 'fnd_' + Math.random().toString(36).slice(2);
		var sending   = false;

		// Suggested chips → fill input.
		if (chips) {
			chips.querySelectorAll('.eaic-finder-chip').forEach(function(chip) {
				chip.addEventListener('click', function() {
					if (textarea) { textarea.value = chip.textContent; resize(); updateBtn(); }
				});
			});
		}

		appendMsg('assistant', CFG.i18n.greeting || "Hi! Tell me what you're looking for and I'll find the best products for you. 🛍️");

		if (sendBtn) sendBtn.addEventListener('click', send);
		if (textarea) {
			textarea.addEventListener('keydown', function(e) { if (e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();} });
			textarea.addEventListener('input', function() { resize(); updateBtn(); });
		}

		function updateBtn() { if (sendBtn) sendBtn.disabled = !(textarea && textarea.value.trim()) || sending; }
		function resize()    { if (!textarea) return; textarea.style.height='auto'; textarea.style.height=Math.min(textarea.scrollHeight,120)+'px'; }

		function send() {
			if (sending || !textarea) return;
			var text = textarea.value.trim(); if (!text) return;
			sending = true; updateBtn();
			textarea.disabled = true;
			appendMsg('user', escHtml(text));
			textarea.value = ''; resize();
			if (chips) chips.style.display = 'none';

			var thinkNode = appendThinking();
			var body = new URLSearchParams();
			body.append('action',     'eaic_finder_chat');
			body.append('nonce',      CFG.nonce);
			body.append('session_id', sessionId);
			body.append('message',    text);

			fetch(CFG.ajax_url, {
				method: 'POST', credentials: 'same-origin',
				headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
				body: body.toString()
			}).then(function(r){return r.json();}).then(function(res) {
				removeNode(thinkNode);
				if (res && res.success) {
					appendMsg('assistant', res.data.reply, true);
					if (res.data.products && res.data.products.length) {
						appendCards(res.data.products);
					}
				} else {
					appendMsg('assistant', escHtml((res&&res.data&&res.data.message)||CFG.i18n.error||'Something went wrong.'));
				}
				finish();
			}).catch(function() {
				removeNode(thinkNode);
				appendMsg('assistant', escHtml(CFG.i18n.error||'Something went wrong. Please try again.'));
				finish();
			});
		}

		function finish() {
			sending = false;
			if (textarea) { textarea.disabled = false; textarea.focus(); }
			updateBtn();
		}

		function appendMsg(role, content, raw) {
			if (!msgs) return;
			var wrap = document.createElement('div');
			wrap.className = 'eaic-finder-msg eaic-finder-msg--' + role;
			var av = document.createElement('div'); av.className = 'eaic-finder-avatar'; av.textContent = role==='user'?'Y':'🔍';
			var bub = document.createElement('div'); bub.className = 'eaic-finder-bubble';
			bub.innerHTML = raw ? content : md(content);
			wrap.appendChild(av); wrap.appendChild(bub);
			msgs.appendChild(wrap); msgs.scrollTop = msgs.scrollHeight;
		}

		function appendThinking() {
			var wrap = document.createElement('div'); wrap.className = 'eaic-finder-msg eaic-finder-msg--assistant';
			var av = document.createElement('div'); av.className = 'eaic-finder-avatar'; av.textContent = '🔍';
			var bub = document.createElement('div'); bub.className = 'eaic-finder-bubble';
			bub.innerHTML = '<div class="eaic-finder-typing"><span class="eaic-finder-dot"></span><span class="eaic-finder-dot"></span><span class="eaic-finder-dot"></span></div>';
			wrap.appendChild(av); wrap.appendChild(bub);
			msgs.appendChild(wrap); msgs.scrollTop = msgs.scrollHeight;
			return wrap;
		}

		function appendCards(products) {
			if (!msgs || !products.length) return;
			var grid = document.createElement('div'); grid.className = 'eaic-finder-cards';
			products.forEach(function(p) {
				var inStock = p.stock === 'instock';
				var ratingHtml = parseFloat(p.rating) > 0
					? '<div class="eaic-finder-card-rating">' + stars(p.rating) + ' (' + p.review_count + ')</div>'
					: '';
				var card = document.createElement('div'); card.className = 'eaic-finder-card';
				card.innerHTML =
					'<a href="'+escHtml(p.url)+'" target="_blank"><img class="eaic-finder-card-img" src="'+escHtml(p.image)+'" alt="'+escHtml(p.name)+'" loading="lazy"></a>' +
					'<div class="eaic-finder-card-body">' +
						'<a href="'+escHtml(p.url)+'" target="_blank" class="eaic-finder-card-name">'+escHtml(p.name)+'</a>' +
						'<div class="eaic-finder-card-price">'+p.price+'</div>' +
						'<span class="eaic-finder-card-stock eaic-finder-card-stock--'+(inStock?'instock':'outofstock')+'">'+escHtml(p.stock_label)+'</span>' +
						ratingHtml +
						(p.short_desc ? '<div class="eaic-finder-card-desc">'+escHtml(p.short_desc)+'</div>' : '') +
					'</div>' +
					'<div class="eaic-finder-card-footer">' +
						(inStock
							? '<a href="'+escHtml(p.add_to_cart)+'?add-to-cart='+p.id+'" class="eaic-finder-card-btn">🛒 '+(CFG.i18n.add_cart||'Add to Cart')+'</a>'
							: '<span class="eaic-finder-card-btn eaic-finder-card-btn--disabled">'+(CFG.i18n.out_stock||'Out of Stock')+'</span>'
						) +
					'</div>';
				grid.appendChild(card);
			});
			msgs.appendChild(grid);
			msgs.scrollTop = msgs.scrollHeight;
		}

		function removeNode(n) { if (n && n.parentNode) n.parentNode.removeChild(n); }
	});
}());
