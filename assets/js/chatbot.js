/**
 * Chatbot — frontend interactivity.
 *
 * Handles:
 * - Send message via REST API (activity sandbox or lesson Q&A)
 * - SSE streaming responses with incremental text display
 * - Fallback to synchronous request/response for unsupported browsers
 * - Maintain in-memory conversation history
 * - Render user + assistant messages (markdown → HTML via marked.js)
 * - Typing indicator during API call
 * - Auto-scroll, auto-grow textarea
 * - Model switching (activity only)
 * - Container ID tracking for session continuity
 * - Inline error display
 * - Stop generating button
 *
 * @package LeadersPath
 * @since   0.6.0
 */

( function () {
	'use strict';

	var config = window.LeadersPathChatbot || {};
	var restUrl = config.restUrl || '/wp-json/leaderspath/v1/chat';
	var restStreamUrl = config.restStreamUrl || '';
	var nonce = config.nonce || '';

	// Feature detection: streaming requires ReadableStream and a stream endpoint.
	var canStream = restStreamUrl && typeof ReadableStream !== 'undefined';

	/**
	 * Initialize a single chatbot instance.
	 */
	function initChatbot( container ) {
		var messagesEl = container.querySelector( '.leaderspath_chatbot__messages' );
		var inputEl = container.querySelector( '.leaderspath_chatbot__input' );
		var sendBtn = container.querySelector( '.leaderspath_chatbot__send' );
		var modelSelect = container.querySelector( '.leaderspath_chatbot__model_select' );
		var emptyState = container.querySelector( '.leaderspath_chatbot__empty' );

		var postId = container.dataset.postId;
		var postType = container.dataset.postType;

		// State.
		var history = [];
		var containerId = null;
		var isSending = false;
		var abortController = null;

		/**
		 * Create a message element.
		 */
		function createMessage( role, content, isHtml ) {
			var messageEl = document.createElement( 'div' );
			messageEl.className = 'leaderspath_chatbot__message leaderspath_chatbot__message--' + role;

			var contentEl = document.createElement( 'div' );
			contentEl.className = 'leaderspath_chatbot__message__content';

			if ( isHtml ) {
				contentEl.innerHTML = content;
			} else {
				contentEl.textContent = content;
			}

			messageEl.appendChild( contentEl );
			return messageEl;
		}

		/**
		 * Create typing indicator.
		 */
		function createTypingIndicator() {
			var el = document.createElement( 'div' );
			el.className = 'leaderspath_chatbot__typing';
			el.setAttribute( 'aria-label', 'Thinking...' );
			el.innerHTML =
				'<span class="leaderspath_chatbot__typing_dot"></span>' +
				'<span class="leaderspath_chatbot__typing_dot"></span>' +
				'<span class="leaderspath_chatbot__typing_dot"></span>';
			return el;
		}

		/**
		 * Create inline error message.
		 */
		function createError( message ) {
			var el = document.createElement( 'div' );
			el.className = 'leaderspath_chatbot__error';
			el.setAttribute( 'role', 'alert' );
			el.textContent = message;
			return el;
		}

		/**
		 * Create stop generating button.
		 */
		function createStopButton() {
			var el = document.createElement( 'button' );
			el.className = 'leaderspath_chatbot__stop';
			el.type = 'button';
			el.textContent = 'Stop generating';
			el.addEventListener( 'click', function () {
				if ( abortController ) {
					abortController.abort();
				}
			} );
			return el;
		}

		/**
		 * Scroll messages to bottom.
		 */
		function scrollToBottom() {
			messagesEl.scrollTop = messagesEl.scrollHeight;
		}

		/**
		 * Auto-grow textarea.
		 */
		function autoGrow() {
			inputEl.style.height = 'auto';
			var maxHeight = 150;
			var newHeight = Math.min( inputEl.scrollHeight, maxHeight );
			inputEl.style.height = newHeight + 'px';
			inputEl.style.overflowY = inputEl.scrollHeight > maxHeight ? 'auto' : 'hidden';
		}

		/**
		 * Set sending state.
		 */
		function setSending( sending ) {
			isSending = sending;
			inputEl.disabled = sending;
			sendBtn.disabled = sending;

			if ( sending ) {
				sendBtn.classList.add( 'leaderspath_chatbot__send--disabled' );
			} else {
				sendBtn.classList.remove( 'leaderspath_chatbot__send--disabled' );
			}
		}

		/**
		 * Build the request body.
		 */
		function buildRequestBody( message ) {
			var body = {
				message: message,
				history: history.slice( 0, -1 ),
			};

			if ( postType === 'activity' ) {
				body.activity_id = parseInt( postId, 10 );
			} else {
				body.lesson_id = parseInt( postId, 10 );
			}

			if ( modelSelect && modelSelect.value ) {
				body.model = modelSelect.value;
			}

			if ( containerId ) {
				body.container_id = containerId;
			}

			return body;
		}

		/**
		 * Build request headers.
		 */
		function buildHeaders() {
			var headers = {
				'Content-Type': 'application/json',
			};
			if ( nonce ) {
				headers['X-WP-Nonce'] = nonce;
			}
			return headers;
		}

		/**
		 * Convert markdown to HTML using marked.js.
		 *
		 * Falls back to basic HTML escaping if marked is unavailable.
		 */
		function markdownToHtml( text ) {
			if ( typeof marked !== 'undefined' && marked.parse ) {
				return marked.parse( text );
			}
			// Fallback: escape HTML and convert newlines.
			var div = document.createElement( 'div' );
			div.textContent = text;
			return div.innerHTML.replace( /\n/g, '<br>' );
		}

		/**
		 * Parse SSE events from a text buffer.
		 *
		 * Returns an object with:
		 * - events: array of parsed {event, data} objects
		 * - remainder: unparsed buffer text (incomplete event)
		 */
		function parseSSEBuffer( buffer ) {
			var events = [];
			var blocks = buffer.split( '\n\n' );

			// Last block may be incomplete — keep as remainder.
			var remainder = blocks.pop();

			for ( var i = 0; i < blocks.length; i++ ) {
				var block = blocks[ i ].trim();
				if ( ! block ) {
					continue;
				}

				var eventType = '';
				var eventData = '';
				var lines = block.split( '\n' );

				for ( var j = 0; j < lines.length; j++ ) {
					var line = lines[ j ];
					if ( line.indexOf( 'event: ' ) === 0 ) {
						eventType = line.substring( 7 );
					} else if ( line.indexOf( 'data: ' ) === 0 ) {
						eventData = line.substring( 6 );
					}
				}

				if ( eventData ) {
					events.push( { event: eventType, data: eventData } );
				}
			}

			return { events: events, remainder: remainder };
		}

		/**
		 * Send a message via streaming SSE.
		 */
		function sendMessageStream( message, body, typing ) {
			abortController = new AbortController();

			// Create assistant message element for incremental display.
			var assistantEl = createMessage( 'assistant', '', true );
			var contentEl = assistantEl.querySelector( '.leaderspath_chatbot__message__content' );
			var accumulatedText = '';

			// Throttled markdown rendering — converts accumulated text to HTML
			// at most every 80ms to avoid excessive DOM updates while keeping
			// formatting visually current during streaming.
			var renderScheduled = false;

			function renderNow() {
				renderScheduled = false;
				if ( accumulatedText ) {
					contentEl.innerHTML = markdownToHtml( accumulatedText );
					scrollToBottom();
				}
			}

			function scheduleRender() {
				if ( ! renderScheduled ) {
					renderScheduled = true;
					requestAnimationFrame( renderNow );
				}
			}

			// Create stop button.
			var stopBtn = createStopButton();

			fetch( restStreamUrl, {
				method: 'POST',
				headers: buildHeaders(),
				credentials: 'same-origin',
				body: JSON.stringify( body ),
				signal: abortController.signal,
			} )
				.then( function ( response ) {
					// Remove typing indicator.
					if ( typing.parentNode ) {
						typing.parentNode.removeChild( typing );
					}

					if ( ! response.ok ) {
						throw new Error( 'Server error (HTTP ' + response.status + '). Please try again.' );
					}

					// Show the assistant message element and stop button.
					messagesEl.appendChild( assistantEl );
					messagesEl.appendChild( stopBtn );
					scrollToBottom();

					var reader = response.body.getReader();
					var decoder = new TextDecoder();
					var sseBuffer = '';

					function readChunk() {
						return reader.read().then( function ( result ) {
							if ( result.done ) {
								// Process any remaining buffer.
								if ( sseBuffer.trim() ) {
									var parsed = parseSSEBuffer( sseBuffer + '\n\n' );
									processEvents( parsed.events );
								}
								return;
							}

							sseBuffer += decoder.decode( result.value, { stream: true } );
							var parsed = parseSSEBuffer( sseBuffer );
							sseBuffer = parsed.remainder;

							processEvents( parsed.events );
							scrollToBottom();

							return readChunk();
						} );
					}

					function processEvents( events ) {
						for ( var i = 0; i < events.length; i++ ) {
							var evt = events[ i ];
							var data;

							try {
								data = JSON.parse( evt.data );
							} catch ( e ) {
								continue;
							}

							// Detect error events: explicit 'error' type from our PHP proxy,
							// or data payloads with error structure (from Anthropic).
							if ( evt.event === 'error' || ( data.type === 'error' && data.error ) ) {
								var errMsg = ( data.error && data.error.message ) || data.message || 'An error occurred.';
								messagesEl.appendChild( createError( errMsg ) );
								continue;
							}

							switch ( evt.event ) {
								case 'message_start':
									// Extract container ID.
									if ( data.message && data.message.container && data.message.container.id ) {
										containerId = data.message.container.id;
									}
									break;

								case 'content_block_delta':
									// Append text delta and schedule throttled markdown render.
									if ( data.delta && data.delta.type === 'text_delta' && data.delta.text ) {
										accumulatedText += data.delta.text;
										scheduleRender();
									}
									break;

								case 'done':
									// Custom [DONE] event from our PHP proxy.
									if ( data.container_id ) {
										containerId = data.container_id;
									}

									// Use content_raw from server for history.
									var rawContent = data.content_raw || accumulatedText;

									// Only record and render if there's actual content.
									if ( rawContent ) {
										history.push( {
											role: 'assistant',
											content: rawContent,
										} );
										// Final render with complete text.
										accumulatedText = rawContent;
										renderNow();
									}
									break;
							}
						}
					}

					return readChunk();
				} )
				.then( function () {
					// Stream complete.
					if ( stopBtn.parentNode ) {
						stopBtn.parentNode.removeChild( stopBtn );
					}

					// If no done event was received, still finalize.
					if ( accumulatedText && ! contentEl.innerHTML ) {
						history.push( {
							role: 'assistant',
							content: accumulatedText,
						} );
						renderNow();
					}

					// Remove empty assistant bubble (error case).
					if ( ! accumulatedText && assistantEl.parentNode ) {
						assistantEl.parentNode.removeChild( assistantEl );
					}

					scrollToBottom();
					setSending( false );
					abortController = null;
					inputEl.focus();
				} )
				.catch( function ( err ) {
					// Remove typing indicator if still present.
					if ( typing.parentNode ) {
						typing.parentNode.removeChild( typing );
					}

					// Remove stop button.
					if ( stopBtn.parentNode ) {
						stopBtn.parentNode.removeChild( stopBtn );
					}

					if ( err.name === 'AbortError' ) {
						// User cancelled — keep partial response.
						if ( accumulatedText ) {
							if ( ! assistantEl.parentNode ) {
								messagesEl.appendChild( assistantEl );
							}
							history.push( {
								role: 'assistant',
								content: accumulatedText,
							} );
							renderNow();
						}
					} else {
						// Show error — fall back to non-streaming if we haven't started.
						if ( ! accumulatedText ) {
							sendMessageSync( message, body, typing );
							return;
						}

						// Mid-stream error — show partial + error.
						messagesEl.appendChild(
							createError( err.message || 'Connection interrupted.' )
						);
					}

					scrollToBottom();
					setSending( false );
					abortController = null;
					inputEl.focus();
				} );
		}

		/**
		 * Send a message via synchronous (non-streaming) endpoint.
		 */
		function sendMessageSync( message, body, typing ) {
			fetch( restUrl, {
				method: 'POST',
				headers: buildHeaders(),
				credentials: 'same-origin',
				body: JSON.stringify( body ),
			} )
				.then( function ( response ) {
					// Remove typing indicator.
					if ( typing.parentNode ) {
						typing.parentNode.removeChild( typing );
					}

					if ( ! response.ok ) {
						return response.json()
							.then( function ( errData ) {
								throw new Error(
									errData.message || 'HTTP ' + response.status
								);
							} )
							.catch( function ( parseErr ) {
								if ( parseErr.message && parseErr.message.indexOf( 'HTTP ' ) === 0 ) {
									throw parseErr;
								}
								throw new Error( 'Server error (HTTP ' + response.status + '). Please try again.' );
							} );
					}
					return response.json();
				} )
				.then( function ( data ) {
					// Track container ID.
					if ( data.container_id ) {
						containerId = data.container_id;
					}

					// Add assistant response to history (raw markdown for API).
					history.push( {
						role: 'assistant',
						content: data.content_raw || data.content,
					} );

					// Display HTML response.
					messagesEl.appendChild(
						createMessage( 'assistant', data.content, true )
					);
					scrollToBottom();

					setSending( false );
					inputEl.focus();
				} )
				.catch( function ( err ) {
					// Remove typing indicator.
					if ( typing.parentNode ) {
						typing.parentNode.removeChild( typing );
					}

					messagesEl.appendChild(
						createError( err.message || 'An error occurred.' )
					);
					scrollToBottom();

					setSending( false );
					inputEl.focus();
				} );
		}

		/**
		 * Send a message.
		 */
		function sendMessage() {
			var message = inputEl.value.trim();
			if ( ! message || isSending ) {
				return;
			}

			// Hide empty state on first message.
			if ( emptyState ) {
				emptyState.style.display = 'none';
			}

			// Show user message.
			messagesEl.appendChild( createMessage( 'user', markdownToHtml( message ), true ) );
			scrollToBottom();

			// Add to history.
			history.push( { role: 'user', content: message } );

			// Clear input.
			inputEl.value = '';
			inputEl.style.height = 'auto';

			// Show typing indicator.
			var typing = createTypingIndicator();
			messagesEl.appendChild( typing );
			scrollToBottom();

			// Set sending state.
			setSending( true );

			// Build request body.
			var body = buildRequestBody( message );

			// Use streaming if available, otherwise fall back to sync.
			if ( canStream ) {
				sendMessageStream( message, body, typing );
			} else {
				sendMessageSync( message, body, typing );
			}
		}

		// Event listeners.
		sendBtn.addEventListener( 'click', sendMessage );

		inputEl.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' && ! e.shiftKey ) {
				e.preventDefault();
				sendMessage();
			}
		} );

		inputEl.addEventListener( 'input', autoGrow );
	}

	/**
	 * Initialize all chatbot instances on the page.
	 */
	function init() {
		var containers = document.querySelectorAll(
			'.leaderspath_chatbot__container'
		);
		containers.forEach( initChatbot );
	}

	// Initialize when DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
