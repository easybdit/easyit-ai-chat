/* EasyIT AI Chat Pro — Store Intelligence Bot */
(function () {
	'use strict';
	var CFG = window.EAICStorBot || {};

	function escHtml(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
	function md(text) {
		if (!text) return '';
		text = String(text).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
		text = text.replace(/\*\*([^*]+)\*\*/g,'<strong>$1</strong>');
		text = text.replace(/`([^`]+)`/g,'<code style="background:#f1f5f9;padding:2px 5px;border-radius:3px;font-family:monospace">$1</code>');
		var parts = text.split(/\n{2,}/);
		return parts.map(function(p){
			p=p.trim(); if(!p) return '';
			if(/^- /.test(p)){
				var items=p.split(/\n/).map(function(l){return '<li>'+l.replace(/^- /,'')+'</li>';}).join('');
				return '<ul style="margin:4px 0 4px 18px;padding:0">'+items+'</ul>';
			}
			return '<p>'+p.replace(/\n/g,'<br>')+'</p>';
		}).join('');
	}

	var root    = document.querySelector('.eaic-store-bot-wrap');
	if (!root) return;
	var msgs    = root.querySelector('.eaic-store-messages');
	var textarea= root.querySelector('.eaic-store-input');
	var sendBtn = root.querySelector('.eaic-store-send');
	var quickBtns=root.querySelectorAll('.eaic-store-quick-btn');
	var sessionId = 'store_' + Math.random().toString(36).slice(2);
	var sending   = false;

	// Greeting.
	appendMsg('assistant', CFG.i18n.greeting || "Hello! 👋 I'm your store assistant. Ask me anything about your WooCommerce store — sales, orders, products, customers.");

	// Quick suggestion buttons.
	quickBtns.forEach(function(btn) {
		btn.addEventListener('click', function() {
			if (textarea) { textarea.value = btn.getAttribute('data-q') || btn.textContent; resize(); updateBtn(); textarea.focus(); }
		});
	});

	if (sendBtn) sendBtn.addEventListener('click', send);
	if (textarea) {
		textarea.addEventListener('keydown', function(e){ if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();} });
		textarea.addEventListener('input', function(){ resize(); updateBtn(); });
	}

	function updateBtn(){ if(sendBtn) sendBtn.disabled = !(textarea&&textarea.value.trim())||sending; }
	function resize(){ if(!textarea) return; textarea.style.height='auto'; textarea.style.height=Math.min(textarea.scrollHeight,120)+'px'; }

	function send() {
		if (sending||!textarea) return;
		var text = textarea.value.trim(); if(!text) return;
		sending=true; updateBtn(); textarea.disabled=true;
		appendMsg('user', escHtml(text));
		textarea.value=''; resize();

		var thinkNode = appendThinking();
		var body = new URLSearchParams();
		body.append('action',     'eaic_store_ask');
		body.append('nonce',      CFG.nonce);
		body.append('session_id', sessionId);
		body.append('message',    text);

		fetch(CFG.ajax_url, {
			method:'POST', credentials:'same-origin',
			headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
			body:body.toString()
		}).then(function(r){return r.json();}).then(function(res){
			removeNode(thinkNode);
			if (res&&res.success) { appendMsg('assistant', res.data.reply, true); }
			else { appendMsg('assistant', escHtml((res&&res.data&&res.data.message)||CFG.i18n.error||'Something went wrong.')); }
			finish();
		}).catch(function(){
			removeNode(thinkNode);
			appendMsg('assistant', escHtml(CFG.i18n.error||'Something went wrong. Please try again.'));
			finish();
		});
	}

	function finish() { sending=false; if(textarea){textarea.disabled=false;textarea.focus();} updateBtn(); }

	function appendMsg(role, content, raw) {
		if (!msgs) return;
		var wrap=document.createElement('div'); wrap.className='eaic-store-msg eaic-store-msg--'+role;
		var av=document.createElement('div'); av.className='eaic-store-avatar'; av.textContent=role==='user'?'👤':'🏪';
		var bub=document.createElement('div'); bub.className='eaic-store-bubble';
		bub.innerHTML = raw ? content : md(content);
		wrap.appendChild(av); wrap.appendChild(bub);
		msgs.appendChild(wrap); msgs.scrollTop=msgs.scrollHeight;
	}

	function appendThinking() {
		var wrap=document.createElement('div'); wrap.className='eaic-store-msg eaic-store-msg--assistant';
		var av=document.createElement('div'); av.className='eaic-store-avatar'; av.textContent='🏪';
		var bub=document.createElement('div'); bub.className='eaic-store-bubble';
		bub.innerHTML='<div class="eaic-store-typing"><span class="eaic-store-dot"></span><span class="eaic-store-dot"></span><span class="eaic-store-dot"></span></div>';
		wrap.appendChild(av); wrap.appendChild(bub);
		msgs.appendChild(wrap); msgs.scrollTop=msgs.scrollHeight;
		return wrap;
	}
	function removeNode(n){if(n&&n.parentNode)n.parentNode.removeChild(n);}
}());
