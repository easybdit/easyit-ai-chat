/**
 * Product Q&A Bot — front-end.
 *
 * Multi-instance: initialises every .eaic-product-bot container on the page.
 * Pre-chat lead form: collects optional name + email before first message.
 * XSS-safe: user/AI text via textContent; only http/https markdown links
 * rendered as <a> elements via safeRender().
 */
( function () {
	'use strict';

	var cfg = window.EAIC_PRODUCT || {};

	/* ── Markdown link renderer ─────────────────────────────────────── */
	function safeRender( text ) {
		var fragment = document.createDocumentFragment();
		var parts    = text.split( /(\[[^\]]+\]\(https?:\/\/[^)]+\))/g );
		parts.forEach( function ( part ) {
			var match = part.match( /^\[([^\]]+)\]\((https?:\/\/[^)]+)\)$/ );
			if ( match ) {
				var a = document.createElement( 'a' );
				a.textContent = match[1];
				a.href        = match[2];
				a.target      = '_blank';
				a.rel         = 'noopener noreferrer';
				fragment.appendChild( a );
			} else {
				fragment.appendChild( document.createTextNode( part ) );
			}
		} );
		return fragment;
	}

	/* ── Lead form helper ───────────────────────────────────────────── */
	function saveLead( sessionId, name, email, productId ) {
		if ( ! cfg.ajax_url || ! cfg.lead_nonce ) { return; }
		var body = new URLSearchParams();
		body.append( 'action',     'eaic_save_lead' );
		body.append( 'nonce',      cfg.lead_nonce );
		body.append( 'session_id', sessionId );
		body.append( 'name',       name );
		body.append( 'email',      email );
		body.append( 'context',    'product' );
		body.append( 'product_id', productId );
		fetch( cfg.ajax_url, {
			method:      'POST',
			credentials: 'same-origin',
			headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
			body:        body.toString()
		} );
	}

	/* ── Pre-chat lead form ─────────────────────────────────────────── */
	function initLeadForm( container, sessionId, productId, onDone ) {
		var storageKey = 'eaic_lead_pb';

		// Lead form disabled via Pro Settings.
		if ( ! cfg.lead_form_enabled ) {
			onDone( cfg.user_name || '' );
			return;
		}

		// Logged-in user — skip form, use WP name.
		if ( cfg.is_logged_in ) {
			if ( cfg.user_name ) {
				saveLead( sessionId, cfg.user_name, '', productId );
			}
			onDone( cfg.user_name || '' );
			return;
		}

		// Already submitted this session — use stored name.
		var stored = localStorage.getItem( storageKey );
		if ( stored !== null ) {
			onDone( stored );
			return;
		}

		/* Build overlay */
		var overlay = document.createElement( 'div' );
		overlay.className = 'eaic-pb-lead-overlay';
		overlay.innerHTML =
			'<div class="eaic-lead-inner">' +
				'<div class="eaic-lead-icon">👋</div>' +
				'<p class="eaic-lead-title">' + ( cfg.lead_form_title || 'Quick intro' ) + '</p>' +
				'<p class="eaic-lead-sub">' + ( cfg.lead_form_sub || 'Optional — helps us personalise your experience' ) + '</p>' +
				'<input type="text"  class="eaic-lead-name"  autocomplete="given-name" placeholder="Your name (optional)"  />' +
				'<input type="email" class="eaic-lead-email" autocomplete="email"      placeholder="Email (optional)"       />' +
				'<button type="button" class="eaic-lead-btn">Start Chatting →</button>' +
				'<button type="button" class="eaic-lead-skip">Skip &amp; chat anonymously</button>' +
			'</div>';

		container.appendChild( overlay );

		function dismiss( name ) {
			localStorage.setItem( storageKey, name );
			overlay.classList.add( 'eaic-lead-overlay--out' );
			setTimeout( function () {
				if ( overlay.parentNode ) { overlay.parentNode.removeChild( overlay ); }
				onDone( name );
			}, 280 );
		}

		function submit() {
			var name  = ( overlay.querySelector( '.eaic-lead-name' ).value  || '' ).trim();
			var email = ( overlay.querySelector( '.eaic-lead-email' ).value || '' ).trim();
			saveLead( sessionId, name, email, productId );
			dismiss( name );
		}

		overlay.querySelector( '.eaic-lead-btn' ).addEventListener( 'click', submit );
		overlay.querySelector( '.eaic-lead-skip' ).addEventListener( 'click', function () {
			dismiss( '' );
		} );
		overlay.querySelectorAll( 'input' ).forEach( function ( inp ) {
			inp.addEventListener( 'keydown', function ( e ) {
				if ( 'Enter' === e.key ) { submit(); }
			} );
		} );

		/* Focus first field */
		setTimeout( function () {
			var inp = overlay.querySelector( '.eaic-lead-name' );
			if ( inp ) { inp.focus(); }
		}, 50 );
	}

	/* ── Per-container initialiser ──────────────────────────────────── */
	function initBot( container ) {
		var logEl   = container.querySelector( '.eaic-pb-log' );
		var msgEl   = container.querySelector( '.eaic-pb-msg-input' );
		var sendBtn = container.querySelector( '.eaic-pb-send-btn' );

		if ( ! sendBtn || ! logEl || ! msgEl ) { return; }

		var productId = parseInt( container.getAttribute( 'data-product-id' ) || '0', 10 );
		var sessionId = 'pb_' + Math.random().toString( 36 ).substr( 2, 9 ) + Date.now().toString( 36 );
		var userName  = '';

		/* Disable input until lead form is done */
		msgEl.disabled  = true;
		sendBtn.disabled = true;

		function makeRow( role ) {
			var row    = document.createElement( 'div' );
			row.className = 'eaic-pb-msg eaic-pb-msg--' + role;
			var avatar = document.createElement( 'div' );
			avatar.className   = 'eaic-pb-avatar';
			avatar.textContent = role === 'user' ? 'You' : '🛍️';
			row.appendChild( avatar );
			return row;
		}

		function removeWelcome() {
			var w = logEl.querySelector( '.eaic-pb-welcome' );
			if ( w ) { logEl.removeChild( w ); }
		}

		function showWelcome() {
			var welcome = document.createElement( 'div' );
			welcome.className = 'eaic-pb-welcome';

			var greeting = userName
				? 'Hi ' + userName.split( ' ' )[0] + '! What can I help you find?'
				: 'Ask about our products';

			welcome.innerHTML =
				'<div class="eaic-pb-welcome-icon">🛍️</div>' +
				'<div class="eaic-pb-welcome-title">' + greeting + '</div>' +
				'<div>Price, availability, features — just ask!</div>' +
				'<div class="eaic-pb-chips">' +
					'<button class="eaic-pb-chip" type="button">What\'s on sale?</button>' +
					'<button class="eaic-pb-chip" type="button">Is this in stock?</button>' +
					'<button class="eaic-pb-chip" type="button">Tell me more</button>' +
				'</div>';
			logEl.appendChild( welcome );

			welcome.querySelectorAll( '.eaic-pb-chip' ).forEach( function ( chip ) {
				chip.addEventListener( 'click', function () { send( chip.textContent ); } );
			} );

			msgEl.disabled  = false;
			sendBtn.disabled = false;
			msgEl.focus();
		}

		function addLine( role, text ) {
			removeWelcome();
			var row    = makeRow( role );
			var bubble = document.createElement( 'div' );
			bubble.className   = 'eaic-pb-bubble';
			bubble.textContent = text;
			row.appendChild( bubble );
			logEl.appendChild( row );
			logEl.scrollTop = logEl.scrollHeight;
		}

		function addThinking() {
			removeWelcome();
			var row      = makeRow( 'assistant' );
			var thinking = document.createElement( 'div' );
			thinking.className = 'eaic-pb-thinking';
			thinking.innerHTML =
				'<div class="eaic-pb-dot"></div>' +
				'<div class="eaic-pb-dot"></div>' +
				'<div class="eaic-pb-dot"></div>';
			row.appendChild( thinking );
			logEl.appendChild( row );
			logEl.scrollTop = logEl.scrollHeight;
			return { row: row, node: thinking };
		}

		function replaceThinking( obj, text ) {
			var bubble = document.createElement( 'div' );
			bubble.className = 'eaic-pb-bubble';
			bubble.appendChild( safeRender( text ) );
			obj.row.replaceChild( bubble, obj.node );
			logEl.scrollTop = logEl.scrollHeight;
		}

		function send( message ) {
			message = ( message || msgEl.value || '' ).trim();
			if ( ! message ) { return; }

			addLine( 'user', message );
			msgEl.value      = '';
			sendBtn.disabled = true;

			var thinkingObj = addThinking();

			var body = new URLSearchParams();
			body.append( 'action',     'eaic_product_chat' );
			body.append( 'nonce',      cfg.nonce );
			body.append( 'session_id', sessionId );
			body.append( 'message',    message );
			body.append( 'product_id', productId );
			body.append( 'user_name',  userName );

			fetch( cfg.ajax_url, {
				method:      'POST',
				credentials: 'same-origin',
				headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
				body:        body.toString()
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					var reply = ( res && res.data && res.data.reply )
						? res.data.reply
						: 'Something went wrong. Please try again.';
					replaceThinking( thinkingObj, reply );
				} )
				.catch( function () {
					replaceThinking( thinkingObj, 'Network error. Please try again.' );
				} )
				.finally( function () {
					sendBtn.disabled = false;
					msgEl.focus();
				} );
		}

		sendBtn.addEventListener( 'click', function () { send(); } );
		msgEl.addEventListener( 'keydown', function ( e ) {
			if ( 'Enter' === e.key ) { send(); }
		} );

		/* Lead form → then welcome */
		initLeadForm( container, sessionId, productId, function ( name ) {
			userName = name;
			showWelcome();
		} );
	}

	/* ── Boot ───────────────────────────────────────────────────────── */
	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.eaic-product-bot' ).forEach( function ( container ) {
			initBot( container );
		} );
	} );
}() );
