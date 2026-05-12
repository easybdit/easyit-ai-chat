/**
 * EasyIT AI Chat — frontend logic.
 *
 * Reads its runtime config from window.EAICConfig (localised in
 * EAIC_Public::enqueue_assets()) and drives every .eaic-widget instance
 * present on the page.
 *
 * No external dependencies (no jQuery, no markdown library, no XHR libs).
 *
 * @package EasyIT_AI_Chat
 */
( function () {
	'use strict';

	if ( typeof window.EAICConfig === 'undefined' ) {
		return;
	}

	var CFG = window.EAICConfig;
	var I18N = ( CFG && CFG.i18n ) || {};

	/* ------------------------------------------------------------------ *
	 * Small helpers
	 * ------------------------------------------------------------------ */

	function t( key, fallback ) {
		return Object.prototype.hasOwnProperty.call( I18N, key ) ? I18N[ key ] : fallback;
	}

	function escapeHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	function escapeAttr( str ) {
		return escapeHtml( str );
	}

	/**
	 * Very small markdown renderer.
	 *
	 * Supports: fenced code blocks, inline code, bold, italic, links,
	 * unordered/ordered lists, headings, paragraphs, line breaks.
	 * Anything more complex is left as paragraphs.
	 *
	 * @param {string} src Raw assistant text.
	 * @return {string} Sanitised HTML.
	 */
	function renderMarkdown( src ) {
		if ( ! src ) {
			return '';
		}
		var text = String( src );

		// 1. Extract fenced code blocks first so their contents aren't touched.
		var codeBlocks = [];
		text = text.replace( /```(\w+)?\n?([\s\S]*?)```/g, function ( _m, lang, code ) {
			codeBlocks.push( { lang: ( lang || '' ).toLowerCase(), code: code.replace( /\n$/, '' ) } );
			return '\u0000CODEBLOCK' + ( codeBlocks.length - 1 ) + '\u0000';
		} );

		// 2. Escape the rest.
		text = escapeHtml( text );

		// 3. Inline code.
		text = text.replace( /`([^`\n]+?)`/g, '<code class="eaic-inline-code">$1</code>' );

		// 4. Bold / italic.
		text = text.replace( /\*\*([^*]+)\*\*/g, '<strong>$1</strong>' );
		text = text.replace( /(^|[^*])\*([^*\n]+)\*/g, '$1<em>$2</em>' );

		// 5. Links [label](url) — only http(s) allowed.
		text = text.replace( /\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, function ( _m, label, url ) {
			return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + label + '</a>';
		} );

		// 6. Headings.
		text = text.replace( /^###\s+(.+)$/gm, '<h3>$1</h3>' );
		text = text.replace( /^##\s+(.+)$/gm,  '<h2>$1</h2>' );
		text = text.replace( /^#\s+(.+)$/gm,   '<h1>$1</h1>' );

		// 7. Lists — group consecutive list lines.
		text = text.replace( /(?:^|\n)((?:[ \t]*[-*][ \t].+(?:\n|$))+)/g, function ( _m, block ) {
			var items = block.trim().split( /\n/ ).map( function ( line ) {
				return '<li>' + line.replace( /^[ \t]*[-*][ \t]/, '' ) + '</li>';
			} ).join( '' );
			return '\n<ul>' + items + '</ul>\n';
		} );
		text = text.replace( /(?:^|\n)((?:[ \t]*\d+\.[ \t].+(?:\n|$))+)/g, function ( _m, block ) {
			var items = block.trim().split( /\n/ ).map( function ( line ) {
				return '<li>' + line.replace( /^[ \t]*\d+\.[ \t]/, '' ) + '</li>';
			} ).join( '' );
			return '\n<ol>' + items + '</ol>\n';
		} );

		// 8. Paragraphs from blank-line separated blocks.
		var parts = text.split( /\n{2,}/ ).map( function ( block ) {
			block = block.trim();
			if ( ! block ) {
				return '';
			}
			if ( /^<(h\d|ul|ol|pre|blockquote)/.test( block ) ) {
				return block;
			}
			return '<p>' + block.replace( /\n/g, '<br>' ) + '</p>';
		} );
		text = parts.join( '\n' );

		// 9. Restore fenced code blocks with copy buttons.
		text = text.replace( /\u0000CODEBLOCK(\d+)\u0000/g, function ( _m, i ) {
			var block = codeBlocks[ Number( i ) ];
			var langAttr = block.lang ? ' data-lang="' + escapeAttr( block.lang ) + '"' : '';
			var langLabel = block.lang ? '<span class="eaic-code-lang">' + escapeHtml( block.lang ) + '</span>' : '<span class="eaic-code-lang"></span>';
			return (
				'<div class="eaic-code-block"' + langAttr + '>' +
					'<div class="eaic-code-header">' +
						langLabel +
						'<button type="button" class="eaic-copy-code">' +
							escapeHtml( t( 'copy', 'Copy' ) ) +
						'</button>' +
					'</div>' +
					'<pre><code>' + escapeHtml( block.code ) + '</code></pre>' +
				'</div>'
			);
		} );

		return text;
	}

	function formatDateGroup( iso ) {
		if ( ! iso ) {
			return t( 'earlier', 'Earlier' );
		}
		// MySQL DATETIME is "YYYY-MM-DD HH:MM:SS" in UTC for the DB —
		// but stored via CURRENT_TIMESTAMP (server time). Parse leniently.
		var safe = iso.replace( ' ', 'T' );
		var d = new Date( safe );
		if ( isNaN( d.getTime() ) ) {
			return t( 'earlier', 'Earlier' );
		}
		var now = new Date();
		var startOfToday = new Date( now.getFullYear(), now.getMonth(), now.getDate() );
		var startOfYesterday = new Date( startOfToday.getTime() - 86400000 );
		if ( d >= startOfToday ) {
			return t( 'today', 'Today' );
		}
		if ( d >= startOfYesterday ) {
			return t( 'yesterday', 'Yesterday' );
		}
		return t( 'earlier', 'Earlier' );
	}

	/**
	 * POST to admin-ajax. Returns the parsed response JSON or throws.
	 *
	 * @param {string} action WP AJAX action (without nonce).
	 * @param {Object} data   Extra form fields.
	 * @return {Promise<Object>} The decoded response.
	 */
	function ajax( action, data ) {
		var body = new URLSearchParams();
		body.append( 'action', action );
		body.append( 'nonce', CFG.nonce );
		if ( data ) {
			Object.keys( data ).forEach( function ( k ) {
				if ( data[ k ] !== undefined && data[ k ] !== null ) {
					body.append( k, data[ k ] );
				}
			} );
		}
		return fetch( CFG.ajax_url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} ).then( function ( res ) {
			return res.json().catch( function () {
				return { success: false, data: { message: t( 'error_generic', 'Something went wrong.' ) } };
			} );
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Widget instance
	 * ------------------------------------------------------------------ */

	function Widget( root ) {
		this.root           = root;
		this.provider       = root.getAttribute( 'data-provider' ) || CFG.default_provider || 'ollama';
		this.systemPrompt   = root.getAttribute( 'data-system-prompt' ) || '';
		this.msgHeight      = parseInt( root.getAttribute( 'data-msg-height' ), 10 ) || 600;

		this.elSidebar      = root.querySelector( '.eaic-sidebar' );
		this.elSessionsList = root.querySelector( '.eaic-sessions-list' );
		this.elNewBtn       = root.querySelector( '.eaic-new-chat-btn' );
		this.elProviderSel  = root.querySelector( '.eaic-provider-select' );
		this.elToggleSide   = root.querySelector( '.eaic-toggle-sidebar' );
		this.elSessionTitle = root.querySelector( '.eaic-session-title' );
		this.elDeleteBtn    = root.querySelector( '.eaic-delete-session-btn' );
		this.elMessages     = root.querySelector( '.eaic-messages' );
		this.elInput        = root.querySelector( '.eaic-input' );
		this.elSendBtn      = root.querySelector( '.eaic-send-btn' );

		if ( this.elMessages && this.msgHeight ) {
			this.elMessages.style.maxHeight = this.msgHeight + 'px';
		}

		this.currentSession = '';
		this.sending        = false;

		this.bind();
		this.loadSessions();
	}

	Widget.prototype.bind = function () {
		var self = this;

		if ( this.elInput ) {
			this.elInput.addEventListener( 'input', function () {
				self.autoresize();
				if ( self.elSendBtn ) {
					self.elSendBtn.disabled = self.elInput.value.trim() === '' || self.sending;
				}
			} );
			this.elInput.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' && ! e.shiftKey ) {
					e.preventDefault();
					self.send();
				}
			} );
		}

		if ( this.elSendBtn ) {
			this.elSendBtn.addEventListener( 'click', function () {
				self.send();
			} );
		}

		if ( this.elNewBtn ) {
			this.elNewBtn.addEventListener( 'click', function () {
				self.newChat();
			} );
		}

		if ( this.elProviderSel ) {
			this.elProviderSel.addEventListener( 'change', function () {
				self.provider = self.elProviderSel.value;
				self.root.setAttribute( 'data-provider', self.provider );
			} );
		}

		if ( this.elToggleSide ) {
			this.elToggleSide.addEventListener( 'click', function () {
				self.root.classList.toggle( 'eaic-sidebar-open' );
			} );
		}

		if ( this.elDeleteBtn ) {
			this.elDeleteBtn.addEventListener( 'click', function () {
				self.deleteCurrent();
			} );
		}

		// Delegated click on the messages area (copy code).
		if ( this.elMessages ) {
			this.elMessages.addEventListener( 'click', function ( e ) {
				var btn = e.target.closest && e.target.closest( '.eaic-copy-code' );
				if ( ! btn ) {
					return;
				}
				var block = btn.closest( '.eaic-code-block' );
				if ( ! block ) {
					return;
				}
				var codeEl = block.querySelector( 'pre code' );
				if ( ! codeEl ) {
					return;
				}
				var text = codeEl.innerText;
				var done = function () {
					var prev = btn.textContent;
					btn.textContent = t( 'copied', 'Copied!' );
					btn.classList.add( 'is-copied' );
					setTimeout( function () {
						btn.textContent = prev;
						btn.classList.remove( 'is-copied' );
					}, 1500 );
				};
				if ( navigator.clipboard && navigator.clipboard.writeText ) {
					navigator.clipboard.writeText( text ).then( done ).catch( function () {
						self.fallbackCopy( text );
						done();
					} );
				} else {
					self.fallbackCopy( text );
					done();
				}
			} );
		}

		// Delegated click on the sessions list (switch / delete).
		if ( this.elSessionsList ) {
			this.elSessionsList.addEventListener( 'click', function ( e ) {
				var delBtn = e.target.closest && e.target.closest( '.eaic-session-del' );
				if ( delBtn ) {
					e.stopPropagation();
					var uuidDel = delBtn.getAttribute( 'data-uuid' );
					if ( uuidDel && window.confirm( t( 'delete_confirm', 'Delete this conversation?' ) ) ) {
						self.deleteSession( uuidDel );
					}
					return;
				}
				var item = e.target.closest && e.target.closest( '.eaic-session-item' );
				if ( ! item ) {
					return;
				}
				var uuid = item.getAttribute( 'data-uuid' );
				if ( uuid && uuid !== self.currentSession ) {
					self.loadHistory( uuid );
				}
			} );
		}
	};

	Widget.prototype.fallbackCopy = function ( text ) {
		try {
			var ta = document.createElement( 'textarea' );
			ta.value = text;
			ta.style.position = 'fixed';
			ta.style.opacity = '0';
			document.body.appendChild( ta );
			ta.select();
			document.execCommand( 'copy' );
			document.body.removeChild( ta );
		} catch ( e ) {
			// no-op
		}
	};

	Widget.prototype.autoresize = function () {
		if ( ! this.elInput ) {
			return;
		}
		this.elInput.style.height = 'auto';
		var max = 200;
		this.elInput.style.height = Math.min( this.elInput.scrollHeight, max ) + 'px';
	};

	Widget.prototype.scrollToBottom = function () {
		if ( this.elMessages ) {
			this.elMessages.scrollTop = this.elMessages.scrollHeight;
		}
	};

	Widget.prototype.clearWelcome = function () {
		var w = this.elMessages && this.elMessages.querySelector( '.eaic-welcome' );
		if ( w && w.parentNode ) {
			w.parentNode.removeChild( w );
		}
	};

	Widget.prototype.appendMessage = function ( role, content, opts ) {
		if ( ! this.elMessages ) {
			return null;
		}
		this.clearWelcome();
		opts = opts || {};

		var wrap = document.createElement( 'div' );
		wrap.className = 'eaic-msg eaic-msg--' + ( role === 'user' ? 'user' : 'assistant' );

		var avatar = document.createElement( 'div' );
		avatar.className = 'eaic-msg-avatar';
		avatar.textContent = role === 'user' ? ( t( 'you', 'You' ).charAt( 0 ) || 'U' ) : '🤖';

		var body = document.createElement( 'div' );
		body.className = 'eaic-msg-body';

		var label = document.createElement( 'div' );
		label.className = 'eaic-msg-label';
		label.textContent = role === 'user' ? t( 'you', 'You' ) : t( 'ai', 'AI' );
		if ( role !== 'user' && CFG.show_provider_badge && opts.provider ) {
			var badge = document.createElement( 'span' );
			badge.className = 'eaic-provider-badge';
			badge.textContent = opts.provider;
			label.appendChild( badge );
		}

		var content_el = document.createElement( 'div' );
		content_el.className = 'eaic-msg-content';
		if ( opts.typing ) {
			content_el.innerHTML = '<span class="eaic-typing"><span class="eaic-typing-dot"></span><span class="eaic-typing-dot"></span><span class="eaic-typing-dot"></span></span>';
		} else if ( role === 'user' ) {
			content_el.innerHTML = '<p>' + escapeHtml( content ).replace( /\n/g, '<br>' ) + '</p>';
		} else {
			content_el.innerHTML = renderMarkdown( content );
		}

		body.appendChild( label );
		body.appendChild( content_el );
		wrap.appendChild( avatar );
		wrap.appendChild( body );
		this.elMessages.appendChild( wrap );
		this.scrollToBottom();
		return wrap;
	};

	Widget.prototype.replaceMessageContent = function ( node, role, content, opts ) {
		if ( ! node ) {
			return;
		}
		opts = opts || {};
		var content_el = node.querySelector( '.eaic-msg-content' );
		if ( ! content_el ) {
			return;
		}
		if ( role === 'user' ) {
			content_el.innerHTML = '<p>' + escapeHtml( content ).replace( /\n/g, '<br>' ) + '</p>';
		} else {
			content_el.innerHTML = renderMarkdown( content );
		}
		if ( opts.provider && CFG.show_provider_badge ) {
			var label = node.querySelector( '.eaic-msg-label' );
			if ( label && ! label.querySelector( '.eaic-provider-badge' ) ) {
				var badge = document.createElement( 'span' );
				badge.className = 'eaic-provider-badge';
				badge.textContent = opts.provider;
				label.appendChild( badge );
			}
		}
		this.scrollToBottom();
	};

	Widget.prototype.renderError = function ( message ) {
		this.clearWelcome();
		var wrap = document.createElement( 'div' );
		wrap.className = 'eaic-msg eaic-msg-error';
		wrap.innerHTML = '<div class="eaic-msg-body"><div class="eaic-msg-content"><p>⚠️ ' + escapeHtml( message ) + '</p></div></div>';
		this.elMessages.appendChild( wrap );
		this.scrollToBottom();
	};

	Widget.prototype.resetMessages = function ( title ) {
		if ( ! this.elMessages ) {
			return;
		}
		this.elMessages.innerHTML = '';
		var w = document.createElement( 'div' );
		w.className = 'eaic-welcome';
		w.innerHTML = '<div class="eaic-welcome-icon">🤖</div>' +
			'<h3 class="eaic-welcome-title">' + escapeHtml( title || ( this.elSessionTitle ? this.elSessionTitle.textContent : 'AI Chat' ) ) + '</h3>' +
			'<p class="eaic-welcome-sub">' + escapeHtml( t( 'no_sessions', '' ) || 'How can I help you today?' ) + '</p>';
		this.elMessages.appendChild( w );
	};

	/* -------- session list -------- */

	Widget.prototype.loadSessions = function () {
		var self = this;
		if ( ! this.elSessionsList ) {
			return;
		}
		ajax( 'eaic_sessions', {} ).then( function ( res ) {
			if ( ! res || ! res.success ) {
				self.renderSessions( [] );
				return;
			}
			self.renderSessions( ( res.data && res.data.sessions ) || [] );
		} ).catch( function () {
			self.renderSessions( [] );
		} );
	};

	Widget.prototype.renderSessions = function ( sessions ) {
		if ( ! this.elSessionsList ) {
			return;
		}
		if ( ! sessions || sessions.length === 0 ) {
			this.elSessionsList.innerHTML = '<div class="eaic-sessions-empty">' + escapeHtml( t( 'no_sessions', 'No conversations yet.' ) ) + '</div>';
			return;
		}
		var groups = {};
		var order = [ t( 'today', 'Today' ), t( 'yesterday', 'Yesterday' ), t( 'earlier', 'Earlier' ) ];
		order.forEach( function ( g ) { groups[ g ] = []; } );

		sessions.forEach( function ( s ) {
			var g = formatDateGroup( s.updated_at || s.created_at );
			if ( ! groups[ g ] ) {
				groups[ g ] = [];
			}
			groups[ g ].push( s );
		} );

		var html = '';
		var self = this;
		order.forEach( function ( g ) {
			if ( ! groups[ g ] || groups[ g ].length === 0 ) {
				return;
			}
			html += '<div class="eaic-session-group-label">' + escapeHtml( g ) + '</div>';
			groups[ g ].forEach( function ( s ) {
				var active = ( s.uuid === self.currentSession ) ? ' eaic-active' : '';
				html +=
					'<div class="eaic-session-item' + active + '" data-uuid="' + escapeAttr( s.uuid ) + '" title="' + escapeAttr( s.title || '' ) + '">' +
						'<span class="eaic-session-name">' + escapeHtml( s.title || 'New Chat' ) + '</span>' +
						'<button type="button" class="eaic-session-del" data-uuid="' + escapeAttr( s.uuid ) + '" aria-label="Delete">' +
							'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>' +
						'</button>' +
					'</div>';
			} );
		} );
		this.elSessionsList.innerHTML = html;
	};

	Widget.prototype.markActive = function () {
		if ( ! this.elSessionsList ) {
			return;
		}
		var items = this.elSessionsList.querySelectorAll( '.eaic-session-item' );
		var i;
		for ( i = 0; i < items.length; i++ ) {
			if ( items[ i ].getAttribute( 'data-uuid' ) === this.currentSession ) {
				items[ i ].classList.add( 'eaic-active' );
			} else {
				items[ i ].classList.remove( 'eaic-active' );
			}
		}
	};

	/* -------- send / history / new / delete -------- */

	Widget.prototype.send = function () {
		if ( this.sending || ! this.elInput ) {
			return;
		}
		var text = this.elInput.value.trim();
		if ( ! text ) {
			return;
		}

		this.sending = true;
		if ( this.elSendBtn ) {
			this.elSendBtn.disabled = true;
		}
		if ( this.elInput ) {
			this.elInput.disabled = true;
		}

		this.appendMessage( 'user', text );
		this.elInput.value = '';
		this.autoresize();

		var typingNode = this.appendMessage( 'assistant', '', { typing: true } );

		var self = this;
		ajax( 'eaic_send', {
			message:  text,
			provider: this.provider,
			session:  this.currentSession,
			system:   this.systemPrompt
		} ).then( function ( res ) {
			self.sending = false;
			if ( self.elInput ) {
				self.elInput.disabled = false;
				self.elInput.focus();
			}
			if ( res && res.success ) {
				var data = res.data || {};
				self.replaceMessageContent( typingNode, 'assistant', data.reply || '', { provider: data.provider } );
				if ( data.session && data.session !== self.currentSession ) {
					self.currentSession = data.session;
				}
				self.loadSessions();
			} else {
				var msg = ( res && res.data && res.data.message ) ? res.data.message : t( 'error_generic', 'Something went wrong.' );
				if ( typingNode && typingNode.parentNode ) {
					typingNode.parentNode.removeChild( typingNode );
				}
				self.renderError( msg );
			}
		} ).catch( function () {
			self.sending = false;
			if ( self.elInput ) {
				self.elInput.disabled = false;
			}
			if ( typingNode && typingNode.parentNode ) {
				typingNode.parentNode.removeChild( typingNode );
			}
			self.renderError( t( 'error_generic', 'Something went wrong.' ) );
		} );
	};

	Widget.prototype.newChat = function () {
		this.currentSession = '';
		this.resetMessages();
		this.markActive();
		if ( this.elInput ) {
			this.elInput.focus();
		}
	};

	Widget.prototype.loadHistory = function ( uuid ) {
		var self = this;
		ajax( 'eaic_history', { session: uuid } ).then( function ( res ) {
			if ( ! res || ! res.success ) {
				return;
			}
			var data = res.data || {};
			self.currentSession = uuid;
			if ( self.elSessionTitle && data.title ) {
				self.elSessionTitle.textContent = data.title;
			}
			if ( data.provider && self.elProviderSel ) {
				self.elProviderSel.value = data.provider;
				self.provider = data.provider;
			}
			self.elMessages.innerHTML = '';
			( data.messages || [] ).forEach( function ( m ) {
				self.appendMessage( m.role, m.content, { provider: data.provider } );
			} );
			if ( ! data.messages || data.messages.length === 0 ) {
				self.resetMessages( data.title );
			}
			self.markActive();
		} );
	};

	Widget.prototype.deleteCurrent = function () {
		if ( ! this.currentSession ) {
			return;
		}
		if ( ! window.confirm( t( 'delete_confirm', 'Delete this conversation?' ) ) ) {
			return;
		}
		this.deleteSession( this.currentSession );
	};

	Widget.prototype.deleteSession = function ( uuid ) {
		var self = this;
		ajax( 'eaic_delete', { session: uuid } ).then( function ( res ) {
			if ( res && res.success ) {
				if ( uuid === self.currentSession ) {
					self.currentSession = '';
					self.resetMessages();
				}
				self.loadSessions();
			}
		} );
	};

	/* ------------------------------------------------------------------ *
	 * Boot
	 * ------------------------------------------------------------------ */

	function boot() {
		var widgets = document.querySelectorAll( '.eaic-widget' );
		var i;
		for ( i = 0; i < widgets.length; i++ ) {
			/* eslint-disable no-new */
			new Widget( widgets[ i ] );
			/* eslint-enable no-new */
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
