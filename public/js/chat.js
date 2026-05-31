/**
 * EasyIT AI Chat — frontend logic (streaming).
 *
 * @package EasyIT_AI_Chat
 */
( function () {
	'use strict';

	if ( typeof window.EAICConfig === 'undefined' ) { return; }

	var CFG  = window.EAICConfig;
	var I18N = ( CFG && CFG.i18n ) || {};

	/* ------------------------------------------------------------------ */
	/* Helpers                                                              */
	/* ------------------------------------------------------------------ */

	function t( key, fallback ) {
		return Object.prototype.hasOwnProperty.call( I18N, key ) ? I18N[ key ] : fallback;
	}

	function escapeHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;'  )
			.replace( />/g, '&gt;'  )
			.replace( /"/g, '&quot;')
			.replace( /'/g, '&#039;');
	}

	function escapeAttr( str ) { return escapeHtml( str ); }

	/* ------------------------------------------------------------------ */
	/* Markdown renderer (unchanged)                                        */
	/* ------------------------------------------------------------------ */

	function renderMarkdown( src ) {
		if ( ! src ) { return ''; }
		var text = String( src );

		var codeBlocks = [];
		text = text.replace( /```(\w+)?\n?([\s\S]*?)```/g, function ( _m, lang, code ) {
			codeBlocks.push( { lang: ( lang || '' ).toLowerCase(), code: code.replace( /\n$/, '' ) } );
			return '\u0000CODEBLOCK' + ( codeBlocks.length - 1 ) + '\u0000';
		} );

		text = escapeHtml( text );
		text = text.replace( /`([^`\n]+?)`/g, '<code class="eaic-inline-code">$1</code>' );
		text = text.replace( /\*\*([^*]+)\*\*/g, '<strong>$1</strong>' );
		text = text.replace( /(^|[^*])\*([^*\n]+)\*/g, '$1<em>$2</em>' );
		text = text.replace( /\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, function ( _m, label, url ) {
			return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + label + '</a>';
		} );

		text = text.replace( /^###\s+(.+)$/gm, '<h3>$1</h3>' );
		text = text.replace( /^##\s+(.+)$/gm,  '<h2>$1</h2>' );
		text = text.replace( /^#\s+(.+)$/gm,   '<h1>$1</h1>' );

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

		// Merge sibling <ol> tags split by intervening <ul> sub-lists so
		// numbering continues (1, 2, 3…) instead of resetting to 1 each time.
		text = text.replace( /<\/ol>((?:\s*<ul>[\s\S]*?<\/ul>)*\s*)<ol>/g, '$1' );

		var parts = text.split( /\n{2,}/ ).map( function ( block ) {
			block = block.trim();
			if ( ! block ) { return ''; }
			if ( /^<(h\d|ul|ol|pre|blockquote)/.test( block ) ) { return block; }
			return '<p>' + block.replace( /\n/g, '<br>' ) + '</p>';
		} );
		text = parts.join( '\n' );

		text = text.replace( /\u0000CODEBLOCK(\d+)\u0000/g, function ( _m, i ) {
			var block    = codeBlocks[ Number( i ) ];
			var langAttr = block.lang ? ' data-lang="' + escapeAttr( block.lang ) + '"' : '';
			var langLbl  = block.lang
				? '<span class="eaic-code-lang">' + escapeHtml( block.lang ) + '</span>'
				: '<span class="eaic-code-lang"></span>';
			return (
				'<div class="eaic-code-block"' + langAttr + '>' +
					'<div class="eaic-code-header">' + langLbl +
						'<button type="button" class="eaic-copy-code">' + escapeHtml( t( 'copy', 'Copy' ) ) + '</button>' +
					'</div>' +
					'<pre><code>' + escapeHtml( block.code ) + '</code></pre>' +
				'</div>'
			);
		} );

		return text;
	}

	/* ------------------------------------------------------------------ */
	/* Date groups                                                          */
	/* ------------------------------------------------------------------ */

	function formatDateGroup( iso ) {
		if ( ! iso ) { return t( 'earlier', 'Earlier' ); }
		var d = new Date( iso.replace( ' ', 'T' ) );
		if ( isNaN( d.getTime() ) ) { return t( 'earlier', 'Earlier' ); }
		var now           = new Date();
		var startOfToday  = new Date( now.getFullYear(), now.getMonth(), now.getDate() );
		var startOfYest   = new Date( startOfToday.getTime() - 86400000 );
		if ( d >= startOfToday ) { return t( 'today',     'Today' ); }
		if ( d >= startOfYest  ) { return t( 'yesterday', 'Yesterday' ); }
		return t( 'earlier', 'Earlier' );
	}

	/* ------------------------------------------------------------------ */
	/* Classic AJAX helper (used for non-send actions)                      */
	/* ------------------------------------------------------------------ */

	function ajax( action, data ) {
		var body = new URLSearchParams();
		body.append( 'action', action );
		body.append( 'nonce',  CFG.nonce );
		if ( data ) {
			Object.keys( data ).forEach( function ( k ) {
				if ( data[ k ] !== undefined && data[ k ] !== null ) {
					body.append( k, data[ k ] );
				}
			} );
		}
		return fetch( CFG.ajax_url, {
			method:      'POST',
			credentials: 'same-origin',
			headers:     { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body:        body.toString()
		} ).then( function ( res ) {
			return res.json().catch( function () {
				return { success: false, data: { message: t( 'error_generic', 'Something went wrong.' ) } };
			} );
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Avatar helper                                                        */
	/* ------------------------------------------------------------------ */

	function buildAssistantAvatar() {
		var el = document.createElement( 'div' );
		el.className = 'eaic-msg-avatar';
		if ( CFG.ai_avatar_url ) {
			var img = document.createElement( 'img' );
			img.src       = CFG.ai_avatar_url;
			img.alt       = t( 'ai', 'AI' );
			img.className = 'eaic-avatar-img';
			el.appendChild( img );
		} else {
			el.textContent = '🤖';
		}
		return el;
	}

	/* ------------------------------------------------------------------ */
	/* Widget                                                               */
	/* ------------------------------------------------------------------ */

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
		this.elExportBtn    = root.querySelector( '.eaic-export-btn' );

		if ( this.elMessages && this.msgHeight ) {
			this.elMessages.style.maxHeight = this.msgHeight + 'px';
		}

		this.currentSession = '';
		this.sending        = false;
		this._streamTimer   = null; // elapsed-time ticker

		this.bind();
		this.initVoice();
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
			this.elSendBtn.addEventListener( 'click', function () { self.send(); } );
		}

		if ( this.elNewBtn ) {
			this.elNewBtn.addEventListener( 'click', function () { self.newChat(); } );
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
			this.elDeleteBtn.addEventListener( 'click', function () { self.deleteCurrent(); } );
		}

		if ( this.elExportBtn ) {
			this.elExportBtn.addEventListener( 'click', function () { self.exportConversation(); } );
		}

		// Copy-code buttons (delegated).
		if ( this.elMessages ) {
			this.elMessages.addEventListener( 'click', function ( e ) {
				var btn = e.target.closest && e.target.closest( '.eaic-copy-code' );
				if ( ! btn ) { return; }
				var block = btn.closest( '.eaic-code-block' );
				if ( ! block ) { return; }
				var codeEl = block.querySelector( 'pre code' );
				if ( ! codeEl ) { return; }
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
						self.fallbackCopy( text ); done();
					} );
				} else {
					self.fallbackCopy( text ); done();
				}
			} );
		}

		// Session list (delegated).
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
				if ( ! item ) { return; }
				var uuid = item.getAttribute( 'data-uuid' );
				if ( uuid && uuid !== self.currentSession ) {
					self.loadHistory( uuid );
				}
			} );
		}
	};

	/* ------------------------------------------------------------------ */
	/* Send — uses SSE streaming via fetch + ReadableStream                 */
	/* ------------------------------------------------------------------ */

	Widget.prototype.send = function () {
		if ( this.sending || ! this.elInput ) { return; }
		var text = this.elInput.value.trim();
		if ( ! text ) { return; }

		this.sending = true;
		if ( this.elSendBtn ) { this.elSendBtn.disabled = true; }
		if ( this.elInput   ) { this.elInput.disabled   = true; }

		this.appendMessage( 'user', text );
		this.elInput.value = '';
		this.autoresize();

		// Show thinking bubble with elapsed timer.
		var thinkNode = this.appendThinking();
		var elapsed   = 0;
		var self      = this;

		this._streamTimer = setInterval( function () {
			elapsed++;
			self.updateThinkingTimer( thinkNode, elapsed );
		}, 1000 );

		// Build POST body for streaming endpoint.
		var body = new URLSearchParams();
		body.append( 'action',   'eaic_stream' );
		body.append( 'nonce',    CFG.nonce );
		body.append( 'message',  text );
		body.append( 'provider', this.provider );
		body.append( 'session',  this.currentSession );
		body.append( 'system',   this.systemPrompt );

		var streamText   = '';
		var contentEl    = null;
		var sessionSaved = false;

		fetch( CFG.ajax_url, {
			method:      'POST',
			credentials: 'same-origin',
			headers:     { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body:        body.toString()
		} ).then( function ( response ) {
			if ( ! response.ok ) {
				throw new Error( 'HTTP ' + response.status );
			}
			var reader  = response.body.getReader();
			var decoder = new TextDecoder();
			var buffer  = '';

			function pump() {
				return reader.read().then( function ( result ) {
					if ( result.done ) { return; }
					buffer += decoder.decode( result.value, { stream: true } );

					// Process complete SSE lines from buffer.
					var lines = buffer.split( '\n' );
					buffer = lines.pop(); // keep incomplete last line

					var eventName = '';
					lines.forEach( function ( line ) {
						line = line.trim();
						if ( 0 === line.indexOf( 'event: ' ) ) {
							eventName = line.slice( 7 ).trim();
						} else if ( 0 === line.indexOf( 'data: ' ) ) {
							var json;
							try { json = JSON.parse( line.slice( 6 ) ); } catch ( e ) { return; }

							if ( 'session' === eventName && json.session ) {
								if ( ! sessionSaved ) {
									self.currentSession = json.session;
									sessionSaved = true;
								}
							}

							if ( 'chunk' === eventName && json.text ) {
								streamText += json.text;
								if ( ! contentEl ) {
									// First token — replace thinking bubble with real content.
									self.clearTimer();
									if ( thinkNode && thinkNode.parentNode ) {
										thinkNode.parentNode.removeChild( thinkNode );
									}
									var msgNode = self.appendMessage( 'assistant', '', {} );
									contentEl   = msgNode ? msgNode.querySelector( '.eaic-msg-content' ) : null;
								}
								if ( contentEl ) {
									contentEl.innerHTML = renderMarkdown( streamText );
									// Streaming cursor.
									var cursor = document.createElement( 'span' );
									cursor.className = 'eaic-cursor';
									contentEl.appendChild( cursor );
									self.scrollToBottom();
								}
							}

							if ( 'done' === eventName ) {
								self.clearTimer();
								// Remove cursor, final render.
								if ( contentEl ) {
									contentEl.innerHTML = renderMarkdown( streamText );
									self.scrollToBottom();
								}
								// Update session title in topbar.
								if ( json.title && self.elSessionTitle ) {
									self.elSessionTitle.textContent = json.title;
								}
								self.loadSessions();
								self.finishSend();
							}

							if ( 'error' === eventName ) {
								self.clearTimer();
								if ( thinkNode && thinkNode.parentNode ) {
									thinkNode.parentNode.removeChild( thinkNode );
								}
								self.renderError( json.message || t( 'error_generic', 'Something went wrong.' ) );
								self.finishSend();
							}
						}
					} );

					return pump();
				} );
			}

			return pump();
		} ).catch( function ( err ) {
			self.clearTimer();
			if ( thinkNode && thinkNode.parentNode ) {
				thinkNode.parentNode.removeChild( thinkNode );
			}
			self.renderError( err.message || t( 'error_generic', 'Something went wrong.' ) );
			self.finishSend();
		} );
	};

	Widget.prototype.finishSend = function () {
		this.sending = false;
		if ( this.elSendBtn ) { this.elSendBtn.disabled = false; }
		if ( this.elInput   ) {
			this.elInput.disabled = false;
			this.elInput.focus();
		}
	};

	Widget.prototype.clearTimer = function () {
		if ( this._streamTimer ) {
			clearInterval( this._streamTimer );
			this._streamTimer = null;
		}
	};

	/* ------------------------------------------------------------------ */
	/* Thinking bubble with elapsed timer                                   */
	/* ------------------------------------------------------------------ */

	Widget.prototype.appendThinking = function () {
		if ( ! this.elMessages ) { return null; }
		this.clearWelcome();

		var wrap   = document.createElement( 'div' );
		wrap.className = 'eaic-msg eaic-msg--assistant';

		var avatar = buildAssistantAvatar();

		var body = document.createElement( 'div' );
		body.className = 'eaic-msg-body';

		var label = document.createElement( 'div' );
		label.className = 'eaic-msg-label';
		label.textContent = t( 'ai', 'AI' );

		var content = document.createElement( 'div' );
		content.className = 'eaic-msg-content eaic-thinking-bubble';
		content.innerHTML =
			'<div class="eaic-typing">' +
				'<span class="eaic-typing-dot"></span>' +
				'<span class="eaic-typing-dot"></span>' +
				'<span class="eaic-typing-dot"></span>' +
			'</div>' +
			'<span class="eaic-thinking-timer"></span>';

		body.appendChild( label );
		body.appendChild( content );
		wrap.appendChild( avatar );
		wrap.appendChild( body );
		this.elMessages.appendChild( wrap );
		this.scrollToBottom();
		return wrap;
	};

	Widget.prototype.updateThinkingTimer = function ( node, seconds ) {
		if ( ! node ) { return; }
		var timerEl = node.querySelector( '.eaic-thinking-timer' );
		if ( timerEl ) { timerEl.textContent = seconds >= 5 ? seconds + 's' : ''; }
	};

	/* ------------------------------------------------------------------ */
	/* Message rendering                                                    */
	/* ------------------------------------------------------------------ */

	Widget.prototype.appendMessage = function ( role, content, opts ) {
		if ( ! this.elMessages ) { return null; }
		this.clearWelcome();
		opts = opts || {};

		var wrap = document.createElement( 'div' );
		wrap.className = 'eaic-msg eaic-msg--' + ( 'user' === role ? 'user' : 'assistant' );

		var avatar;
		if ( 'user' === role ) {
			avatar = document.createElement( 'div' );
			avatar.className   = 'eaic-msg-avatar';
			avatar.textContent = t( 'you', 'You' ).charAt( 0 ) || 'U';
		} else {
			avatar = buildAssistantAvatar();
		}

		var body = document.createElement( 'div' );
		body.className = 'eaic-msg-body';

		var label = document.createElement( 'div' );
		label.className = 'eaic-msg-label';
		label.textContent = 'user' === role ? t( 'you', 'You' ) : t( 'ai', 'AI' );

		if ( 'user' !== role && CFG.show_provider_badge && opts.provider ) {
			var badge = document.createElement( 'span' );
			badge.className = 'eaic-provider-badge';
			badge.textContent = opts.provider;
			label.appendChild( badge );
		}

		var content_el = document.createElement( 'div' );
		content_el.className = 'eaic-msg-content';

		if ( 'user' === role ) {
			content_el.innerHTML = '<p>' + escapeHtml( content ).replace( /\n/g, '<br>' ) + '</p>';
		} else if ( content ) {
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

	Widget.prototype.renderError = function ( message ) {
		this.clearWelcome();
		var wrap = document.createElement( 'div' );
		wrap.className = 'eaic-msg eaic-msg-error';
		wrap.innerHTML = '<div class="eaic-msg-body"><div class="eaic-msg-content"><p>⚠️ ' + escapeHtml( message ) + '</p></div></div>';
		this.elMessages.appendChild( wrap );
		this.scrollToBottom();
	};

	Widget.prototype.resetMessages = function ( title ) {
		if ( ! this.elMessages ) { return; }
		this.elMessages.innerHTML = '';
		var w = document.createElement( 'div' );
		w.className = 'eaic-welcome';
		w.innerHTML =
			'<div class="eaic-welcome-icon">🤖</div>' +
			'<h3 class="eaic-welcome-title">' + escapeHtml( title || ( this.elSessionTitle ? this.elSessionTitle.textContent : 'AI Chat' ) ) + '</h3>' +
			'<p class="eaic-welcome-sub">' + escapeHtml( t( 'no_sessions', '' ) || 'How can I help you today?' ) + '</p>';
		this.elMessages.appendChild( w );
		this.appendWelcomeBubble();
		this.renderSuggestedQuestions();
	};

	Widget.prototype.appendWelcomeBubble = function () {
		if ( ! this.elMessages ) { return; }
		if ( ! CFG.welcome_message_enabled || ! CFG.welcome_message_text ) { return; }

		var wrap = document.createElement( 'div' );
		wrap.className = 'eaic-msg eaic-msg--assistant eaic-welcome-bubble';

		var avatar = buildAssistantAvatar();

		var body = document.createElement( 'div' );
		body.className = 'eaic-msg-body';

		var label = document.createElement( 'div' );
		label.className = 'eaic-msg-label';
		label.textContent = t( 'ai', 'AI' );

		var content = document.createElement( 'div' );
		content.className = 'eaic-msg-content';
		content.innerHTML = renderMarkdown( CFG.welcome_message_text );

		body.appendChild( label );
		body.appendChild( content );
		wrap.appendChild( avatar );
		wrap.appendChild( body );
		this.elMessages.appendChild( wrap );
	};

	/* ------------------------------------------------------------------ */
	/* Session list                                                         */
	/* ------------------------------------------------------------------ */

	Widget.prototype.loadSessions = function () {
		var self = this;
		if ( ! this.elSessionsList ) { return; }
		ajax( 'eaic_sessions', {} ).then( function ( res ) {
			if ( ! res || ! res.success ) { self.renderSessions( [] ); return; }
			self.renderSessions( ( res.data && res.data.sessions ) || [] );
			if ( ! self.currentSession &&
				! self.elMessages.querySelector( '.eaic-welcome-bubble' ) &&
				! self.elMessages.querySelector( '.eaic-suggested-chips' ) ) {
				self.appendWelcomeBubble();
				self.renderSuggestedQuestions();
			}
		} ).catch( function () { self.renderSessions( [] ); } );
	};

	Widget.prototype.renderSessions = function ( sessions ) {
		if ( ! this.elSessionsList ) { return; }
		if ( ! sessions || sessions.length === 0 ) {
			this.elSessionsList.innerHTML =
				'<div class="eaic-sessions-empty">' + escapeHtml( t( 'no_sessions', 'No conversations yet.' ) ) + '</div>';
			return;
		}
		var groups = {};
		var order  = [ t( 'today', 'Today' ), t( 'yesterday', 'Yesterday' ), t( 'earlier', 'Earlier' ) ];
		order.forEach( function ( g ) { groups[ g ] = []; } );
		sessions.forEach( function ( s ) {
			var g = formatDateGroup( s.updated_at || s.created_at );
			if ( ! groups[ g ] ) { groups[ g ] = []; }
			groups[ g ].push( s );
		} );

		var html = '';
		var self = this;
		order.forEach( function ( g ) {
			if ( ! groups[ g ] || groups[ g ].length === 0 ) { return; }
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
		if ( ! this.elSessionsList ) { return; }
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

	/* ------------------------------------------------------------------ */
	/* Session actions                                                      */
	/* ------------------------------------------------------------------ */

	Widget.prototype.newChat = function () {
		this.currentSession = '';
		this.resetMessages();
		this.markActive();
		if ( this.elInput ) { this.elInput.focus(); }
	};

	Widget.prototype.loadHistory = function ( uuid ) {
		var self = this;
		ajax( 'eaic_history', { session: uuid } ).then( function ( res ) {
			if ( ! res || ! res.success ) { return; }
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
		if ( ! this.currentSession ) { return; }
		if ( ! window.confirm( t( 'delete_confirm', 'Delete this conversation?' ) ) ) { return; }
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

	/* ------------------------------------------------------------------ */
	/* Utilities                                                            */
	/* ------------------------------------------------------------------ */

	Widget.prototype.fallbackCopy = function ( text ) {
		try {
			var ta = document.createElement( 'textarea' );
			ta.value = text;
			ta.style.position = 'fixed';
			ta.style.opacity  = '0';
			document.body.appendChild( ta );
			ta.select();
			document.execCommand( 'copy' );
			document.body.removeChild( ta );
		} catch ( e ) { /* no-op */ }
	};

	Widget.prototype.autoresize = function () {
		if ( ! this.elInput ) { return; }
		this.elInput.style.height = 'auto';
		this.elInput.style.height = Math.min( this.elInput.scrollHeight, 200 ) + 'px';
	};

	Widget.prototype.scrollToBottom = function () {
		if ( this.elMessages ) {
			this.elMessages.scrollTop = this.elMessages.scrollHeight;
		}
	};

	Widget.prototype.clearWelcome = function () {
		var w = this.elMessages && this.elMessages.querySelector( '.eaic-welcome' );
		if ( w && w.parentNode ) { w.parentNode.removeChild( w ); }
		var chips = this.elMessages && this.elMessages.querySelector( '.eaic-suggested-chips' );
		if ( chips && chips.parentNode ) { chips.parentNode.removeChild( chips ); }
	};

	Widget.prototype.renderSuggestedQuestions = function () {
		if ( ! this.elMessages ) { return; }
		if ( ! CFG.suggested_questions_enabled || ! CFG.suggested_questions || ! CFG.suggested_questions.length ) { return; }

		var self = this;
		var wrap = document.createElement( 'div' );
		wrap.className = 'eaic-suggested-chips';

		CFG.suggested_questions.forEach( function ( q ) {
			q = String( q ).trim();
			if ( ! q ) { return; }
			var btn = document.createElement( 'button' );
			btn.type      = 'button';
			btn.className = 'eaic-chip';
			btn.textContent = q;
			btn.addEventListener( 'click', function () {
				if ( self.sending ) { return; }
				if ( self.elInput ) {
					self.elInput.value = q;
					self.autoresize();
					if ( self.elSendBtn ) { self.elSendBtn.disabled = false; }
				}
				self.send();
			} );
			wrap.appendChild( btn );
		} );

		if ( wrap.children.length > 0 ) {
			this.elMessages.appendChild( wrap );
		}
	};

	/* ------------------------------------------------------------------ */
	/* Export conversation                                                  */
	/* ------------------------------------------------------------------ */

	Widget.prototype.exportConversation = function () {
		if ( ! this.elMessages ) { return; }
		var rows = this.elMessages.querySelectorAll( '.eaic-msg--user, .eaic-msg--assistant' );
		if ( ! rows || ! rows.length ) { return; }

		var title = ( this.elSessionTitle && this.elSessionTitle.textContent.trim() ) || 'Chat';
		var lines = [];
		lines.push( '=== ' + title + ' ===' );
		lines.push( 'Exported: ' + new Date().toLocaleString() );
		lines.push( '' );

		rows.forEach( function ( msg ) {
			if ( msg.classList.contains( 'eaic-welcome-bubble' ) ) { return; }
			var isUser   = msg.classList.contains( 'eaic-msg--user' );
			var content  = msg.querySelector( '.eaic-msg-content' );
			if ( ! content ) { return; }
			var text = ( content.innerText || content.textContent || '' ).trim();
			if ( ! text ) { return; }
			lines.push( ( isUser ? 'You' : 'AI' ) + ': ' + text );
			lines.push( '' );
		} );

		if ( lines.length <= 3 ) { return; }

		var blob = new Blob( [ lines.join( '\n' ) ], { type: 'text/plain;charset=utf-8' } );
		var url  = URL.createObjectURL( blob );
		var a    = document.createElement( 'a' );
		a.href     = url;
		a.download = title.replace( /[^a-z0-9\-_ ]/gi, '' ).trim().replace( /\s+/g, '-' ).toLowerCase() + '.txt';
		document.body.appendChild( a );
		a.click();
		document.body.removeChild( a );
		URL.revokeObjectURL( url );
	};

	/* ------------------------------------------------------------------ */
	/* Voice input                                                          */
	/* ------------------------------------------------------------------ */

	Widget.prototype.initVoice = function () {
		if ( ! CFG.voice_input_enabled ) { return; }
		var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
		if ( ! SR ) { return; }

		var micBtn = this.root.querySelector( '.eaic-mic-btn' );
		if ( ! micBtn ) { return; }
		micBtn.style.display = '';

		var self        = this;
		var recognition = new SR();
		var listening   = false;

		recognition.continuous     = false;
		recognition.interimResults = false;
		recognition.lang           = document.documentElement.lang || 'en-US';

		micBtn.addEventListener( 'click', function () {
			if ( self.sending ) { return; }
			if ( listening ) { recognition.stop(); return; }
			try { recognition.start(); } catch ( e ) { /* already started */ }
		} );

		recognition.onstart = function () {
			listening = true;
			micBtn.classList.add( 'eaic-mic-active' );
		};

		recognition.onresult = function ( e ) {
			var transcript = e.results[ 0 ][ 0 ].transcript;
			if ( self.elInput ) {
				self.elInput.value = ( self.elInput.value + ' ' + transcript ).trim();
				self.autoresize();
				if ( self.elSendBtn ) {
					self.elSendBtn.disabled = self.elInput.value.trim() === '' || self.sending;
				}
				self.elInput.focus();
			}
		};

		recognition.onend = function () {
			listening = false;
			micBtn.classList.remove( 'eaic-mic-active' );
		};

		recognition.onerror = function () {
			listening = false;
			micBtn.classList.remove( 'eaic-mic-active' );
		};
	};

	/* ------------------------------------------------------------------ */
	/* Boot                                                                 */
	/* ------------------------------------------------------------------ */

	function bootFloating() {
		var btn   = document.getElementById( 'eaic-float-btn' );
		var panel = document.getElementById( 'eaic-float-panel' );
		if ( ! btn || ! panel ) { return; }

		var iconChat  = btn.querySelector( '.eaic-float-icon-chat' );
		var iconClose = btn.querySelector( '.eaic-float-icon-close' );

		function openPanel() {
			panel.classList.add( 'eaic-float-open' );
			panel.setAttribute( 'aria-hidden', 'false' );
			btn.setAttribute( 'aria-expanded', 'true' );
			btn.classList.add( 'eaic-float-is-open' );
			if ( iconChat  ) { iconChat.style.display  = 'none'; }
			if ( iconClose ) { iconClose.style.display = ''; }
		}

		function closePanel() {
			panel.classList.remove( 'eaic-float-open' );
			panel.setAttribute( 'aria-hidden', 'true' );
			btn.setAttribute( 'aria-expanded', 'false' );
			btn.classList.remove( 'eaic-float-is-open' );
			if ( iconChat  ) { iconChat.style.display  = ''; }
			if ( iconClose ) { iconClose.style.display = 'none'; }
		}

		btn.addEventListener( 'click', function () {
			if ( panel.classList.contains( 'eaic-float-open' ) ) { closePanel(); } else { openPanel(); }
		} );

		var closeBtn = panel.querySelector( '.eaic-float-close' );
		if ( closeBtn ) { closeBtn.addEventListener( 'click', closePanel ); }

		document.addEventListener( 'click', function ( e ) {
			if ( ! e.target.closest ) { return; }
			if ( e.target.closest( '.eaic-floating-wrap' ) ) { return; }
			if ( panel.classList.contains( 'eaic-float-open' ) ) { closePanel(); }
		} );
	}

	function boot() {
		var widgets = document.querySelectorAll( '.eaic-widget' );
		var i;
		for ( i = 0; i < widgets.length; i++ ) {
			/* eslint-disable no-new */
			new Widget( widgets[ i ] );
			/* eslint-enable no-new */
		}
		bootFloating();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
