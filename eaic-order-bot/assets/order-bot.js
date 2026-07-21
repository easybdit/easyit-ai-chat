/**
 * Order Status Bot — front-end.
 *
 * Multi-instance: initialises every .eaic-order-bot container on the page.
 * Pre-chat lead form: collects optional name + email before first message.
 * XSS-safe: user/AI text set via textContent, never innerHTML.
 */
( function () {
	'use strict';

	var cfg = window.EAIC_ORDER || {};

	/* ── Lead form helper ───────────────────────────────────────────── */
	function saveLead( sessionId, name, email ) {
		if ( ! cfg.ajax_url || ! cfg.lead_nonce ) { return; }
		var body = new URLSearchParams();
		body.append( 'action',     'eaic_save_lead' );
		body.append( 'nonce',      cfg.lead_nonce );
		body.append( 'session_id', sessionId );
		body.append( 'name',       name );
		body.append( 'email',      email );
		body.append( 'context',    'order' );
		fetch( cfg.ajax_url, {
			method:      'POST',
			credentials: 'same-origin',
			headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
			body:        body.toString()
		} );
	}

	/* ── Pre-chat lead form ─────────────────────────────────────────── */
	function initLeadForm( container, sessionId, onDone ) {
		var storageKey = 'eaic_lead_ob';

		if ( cfg.is_logged_in ) {
			if ( cfg.user_name ) {
				saveLead( sessionId, cfg.user_name, '' );
			}
			onDone( cfg.user_name || '' );
			return;
		}

		var stored = localStorage.getItem( storageKey );
		if ( stored !== null ) {
			onDone( stored );
			return;
		}

		var overlay = document.createElement( 'div' );
		overlay.className = 'eaic-ob-lead-overlay';
		overlay.innerHTML =
			'<div class="eaic-lead-inner">' +
				'<div class="eaic-lead-icon">📦</div>' +
				'<p class="eaic-lead-title">Quick intro</p>' +
				'<p class="eaic-lead-sub">Optional — helps us personalise your experience</p>' +
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
			saveLead( sessionId, name, email );
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

		setTimeout( function () {
			var inp = overlay.querySelector( '.eaic-lead-name' );
			if ( inp ) { inp.focus(); }
		}, 50 );
	}

	/* ── Per-container initialiser ──────────────────────────────────── */
	function initBot( container ) {
		var logEl   = container.querySelector( '.eaic-ob-log' );
		var msgEl   = container.querySelector( '.eaic-ob-msg-input' );
		var sendBtn = container.querySelector( '.eaic-ob-send-btn' );
		var orderEl = container.querySelector( '.eaic-ob-order-id' );
		var emailEl = container.querySelector( '.eaic-ob-order-email' );

		if ( ! sendBtn || ! logEl || ! msgEl ) { return; }

		var sessionId = 'ob_' + Math.random().toString( 36 ).substr( 2, 9 ) + Date.now().toString( 36 );
		var userName  = '';

		msgEl.disabled   = true;
		sendBtn.disabled = true;

		function makeRow( role ) {
			var row    = document.createElement( 'div' );
			row.className = 'eaic-ob-msg eaic-ob-msg--' + role;
			var avatar = document.createElement( 'div' );
			avatar.className   = 'eaic-ob-avatar';
			avatar.textContent = role === 'user' ? 'You' : '📦';
			row.appendChild( avatar );
			return row;
		}

		function removeWelcome() {
			var w = logEl.querySelector( '.eaic-ob-welcome' );
			if ( w ) { logEl.removeChild( w ); }
		}

		function showWelcome() {
			var greeting = userName
				? 'Hi ' + userName.split( ' ' )[0] + '! Enter your order details above to begin.'
				: 'Track your order';

			var welcome = document.createElement( 'div' );
			welcome.className = 'eaic-ob-welcome';
			welcome.innerHTML =
				'<div class="eaic-ob-welcome-icon">📦</div>' +
				'<div class="eaic-ob-welcome-title">' + greeting + '</div>' +
				'<div>Enter your order number and email above, then ask anything.</div>';
			logEl.appendChild( welcome );

			msgEl.disabled   = false;
			sendBtn.disabled = false;
			msgEl.focus();
		}

		function addLine( role, text ) {
			removeWelcome();
			var row    = makeRow( role );
			var bubble = document.createElement( 'div' );
			bubble.className   = 'eaic-ob-bubble';
			bubble.textContent = text;
			row.appendChild( bubble );
			logEl.appendChild( row );
			logEl.scrollTop = logEl.scrollHeight;
		}

		function addThinking() {
			removeWelcome();
			var row      = makeRow( 'assistant' );
			var thinking = document.createElement( 'div' );
			thinking.className = 'eaic-ob-thinking';
			thinking.innerHTML =
				'<div class="eaic-ob-dot"></div>' +
				'<div class="eaic-ob-dot"></div>' +
				'<div class="eaic-ob-dot"></div>';
			row.appendChild( thinking );
			logEl.appendChild( row );
			logEl.scrollTop = logEl.scrollHeight;
			return { row: row, node: thinking };
		}

		function replaceThinking( obj, text ) {
			var bubble = document.createElement( 'div' );
			bubble.className   = 'eaic-ob-bubble';
			bubble.textContent = text;
			obj.row.replaceChild( bubble, obj.node );
			logEl.scrollTop = logEl.scrollHeight;
		}

		function send() {
			var message = ( msgEl.value || '' ).trim();
			if ( ! message ) { return; }

			addLine( 'user', message );
			msgEl.value      = '';
			sendBtn.disabled = true;

			var thinkingObj = addThinking();

			var body = new URLSearchParams();
			body.append( 'action',     'eaic_order_chat' );
			body.append( 'nonce',      cfg.nonce );
			body.append( 'session_id', sessionId );
			body.append( 'message',    message );
			body.append( 'order_id',   orderEl ? ( orderEl.value || '' ).trim() : '' );
			body.append( 'email',      emailEl ? ( emailEl.value || '' ).trim() : '' );
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

		sendBtn.addEventListener( 'click', send );
		msgEl.addEventListener( 'keydown', function ( e ) {
			if ( 'Enter' === e.key ) { send(); }
		} );

		initLeadForm( container, sessionId, function ( name ) {
			userName = name;
			showWelcome();
		} );
	}

	/* ── Boot ───────────────────────────────────────────────────────── */
	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.eaic-order-bot' ).forEach( function ( container ) {
			initBot( container );
		} );
	} );
}() );
