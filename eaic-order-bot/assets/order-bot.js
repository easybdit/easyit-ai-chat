/**
 * Order Status Bot — front-end.
 *
 * No framework, no innerHTML for user/AI text (XSS-safe via textContent).
 * Talks to admin-ajax with the localized nonce + session.
 */
( function () {
	'use strict';

	var cfg = window.EAIC_ORDER || {};
	var logEl, msgEl, sendBtn, orderEl, emailEl;

	function el( id ) {
		return document.getElementById( id );
	}

	function makeRow( role ) {
		var row = document.createElement( 'div' );
		row.className = 'eaic-ob-msg eaic-ob-msg--' + role;

		var avatar = document.createElement( 'div' );
		avatar.className = 'eaic-ob-avatar';
		avatar.textContent = role === 'user' ? 'You' : '📦';
		row.appendChild( avatar );

		return row;
	}

	function removeWelcome() {
		var w = logEl.querySelector( '.eaic-ob-welcome' );
		if ( w ) { logEl.removeChild( w ); }
	}

	function addLine( role, text ) {
		removeWelcome();

		var row    = makeRow( role );
		var bubble = document.createElement( 'div' );
		bubble.className = 'eaic-ob-bubble';
		// textContent — never innerHTML — so AI/user text can't inject markup.
		bubble.textContent = text;
		row.appendChild( bubble );
		logEl.appendChild( row );
		logEl.scrollTop = logEl.scrollHeight;
		return bubble;
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
		bubble.className = 'eaic-ob-bubble';
		bubble.textContent = text;
		obj.row.replaceChild( bubble, obj.node );
		logEl.scrollTop = logEl.scrollHeight;
	}

	function send() {
		var message = ( msgEl.value || '' ).trim();
		if ( ! message ) {
			return;
		}

		addLine( 'user', message );
		msgEl.value = '';
		sendBtn.disabled = true;

		var thinkingObj = addThinking();

		var body = new URLSearchParams();
		body.append( 'action',     'eaic_order_chat' );
		body.append( 'nonce',      cfg.nonce );
		body.append( 'session_id', cfg.session_id );
		body.append( 'message',    message );
		body.append( 'order_id',   ( orderEl.value || '' ).trim() );
		body.append( 'email',      ( emailEl.value || '' ).trim() );

		fetch( cfg.ajax_url, {
			method:      'POST',
			credentials: 'same-origin',
			headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
			body:        body.toString()
		} )
			.then( function ( r ) {
				return r.json();
			} )
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

	document.addEventListener( 'DOMContentLoaded', function () {
		logEl   = el( 'eaic-order-bot-log' );
		msgEl   = el( 'eaic-order-msg' );
		sendBtn = el( 'eaic-order-send' );
		orderEl = el( 'eaic-order-id' );
		emailEl = el( 'eaic-order-email' );

		if ( ! sendBtn ) {
			return;
		}

		var welcome = document.createElement( 'div' );
		welcome.className = 'eaic-ob-welcome';
		welcome.innerHTML =
			'<div class="eaic-ob-welcome-icon">📦</div>' +
			'<div class="eaic-ob-welcome-title">Track your order</div>' +
			'<div>Enter your order number and email above, then ask anything.</div>';
		logEl.appendChild( welcome );

		sendBtn.addEventListener( 'click', send );
		msgEl.addEventListener( 'keydown', function ( e ) {
			if ( 'Enter' === e.key ) {
				send();
			}
		} );
	} );
}() );
