/**
 * Product Q&A Bot — front-end.
 * XSS-safe: user/AI text set via textContent, never innerHTML.
 */
( function () {
	'use strict';

	var cfg = window.EAIC_PRODUCT || {};
	var logEl, msgEl, sendBtn;

	function el( id ) { return document.getElementById( id ); }

	function makeRow( role ) {
		var row = document.createElement( 'div' );
		row.className = 'eaic-pb-msg eaic-pb-msg--' + role;

		var avatar = document.createElement( 'div' );
		avatar.className = 'eaic-pb-avatar';
		avatar.textContent = role === 'user' ? 'You' : '🛍️';
		row.appendChild( avatar );

		return row;
	}

	function removeWelcome() {
		var w = logEl.querySelector( '.eaic-pb-welcome' );
		if ( w ) { logEl.removeChild( w ); }
	}

	function addLine( role, text ) {
		removeWelcome();

		var row    = makeRow( role );
		var bubble = document.createElement( 'div' );
		bubble.className = 'eaic-pb-bubble';
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
		bubble.textContent = text;
		obj.row.replaceChild( bubble, obj.node );
		logEl.scrollTop = logEl.scrollHeight;
	}

	function send( message ) {
		message = ( message || msgEl.value || '' ).trim();
		if ( ! message ) { return; }

		addLine( 'user', message );
		msgEl.value = '';
		sendBtn.disabled = true;

		var thinkingObj = addThinking();

		var body = new URLSearchParams();
		body.append( 'action',     'eaic_product_chat' );
		body.append( 'nonce',      cfg.nonce );
		body.append( 'session_id', cfg.session_id );
		body.append( 'message',    message );
		body.append( 'product_id', cfg.product_id || 0 );

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

	document.addEventListener( 'DOMContentLoaded', function () {
		logEl   = el( 'eaic-product-bot-log' );
		msgEl   = el( 'eaic-product-msg' );
		sendBtn = el( 'eaic-product-send' );

		if ( ! sendBtn ) { return; }

		// Welcome screen with suggestion chips.
		var welcome = document.createElement( 'div' );
		welcome.className = 'eaic-pb-welcome';
		welcome.innerHTML =
			'<div class="eaic-pb-welcome-icon">🛍️</div>' +
			'<div class="eaic-pb-welcome-title">Ask about our products</div>' +
			'<div>Price, availability, features — just ask!</div>' +
			'<div class="eaic-pb-chips">' +
				'<button class="eaic-pb-chip" type="button">What\'s on sale?</button>' +
				'<button class="eaic-pb-chip" type="button">Is this in stock?</button>' +
				'<button class="eaic-pb-chip" type="button">Tell me more</button>' +
			'</div>';
		logEl.appendChild( welcome );

		// Chip click → send that text.
		welcome.querySelectorAll( '.eaic-pb-chip' ).forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				send( chip.textContent );
			} );
		} );

		sendBtn.addEventListener( 'click', function () { send(); } );
		msgEl.addEventListener( 'keydown', function ( e ) {
			if ( 'Enter' === e.key ) { send(); }
		} );
	} );
}() );
