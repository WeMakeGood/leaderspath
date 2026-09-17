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
	var restWarmUrl = config.restWarmUrl || '';
	var restUploadUrl = config.restUploadUrl || '';
	var nonce = config.nonce || '';

	// Feature detection: streaming requires ReadableStream and a stream endpoint.
	var canStream = restStreamUrl && typeof ReadableStream !== 'undefined';

	// Client-side mirror of the server-side allowlist/cap in class-rest-api.php
	// (ALLOWED_UPLOAD_MIME_TYPES / MAX_UPLOAD_BYTES). This check is purely for
	// fast UX (instant rejection message) — the server remains authoritative
	// and re-validates every upload regardless of what the client allowed
	// through.
	var ALLOWED_UPLOAD_MIME_TYPES = [
		'text/plain',
		'text/markdown',
		'text/csv',
		'application/json',
		'image/png',
		'image/jpeg',
		'image/gif',
		'image/webp',
		'application/pdf',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'application/vnd.openxmlformats-officedocument.presentationml.presentation',
	];
	// 30MB app-level cap (not Anthropic's own 500MB Files API limit) — must match
	// REST_API::MAX_UPLOAD_BYTES.
	var MAX_UPLOAD_BYTES = 30 * 1024 * 1024;

	/**
	 * Initialize a single chatbot instance.
	 */
	function initChatbot( container ) {
		// Init-once guard. On the lesson page every activity's chatbot is
		// pre-rendered and stays in the DOM; the MutationObserver may call this
		// again when a hidden section is revealed. Never re-initialize — that
		// would discard the activity's persisted conversation. Hidden != reset.
		if ( container.dataset.lpInit === '1' ) {
			return;
		}
		container.dataset.lpInit = '1';

		var messagesEl = container.querySelector( '.leaderspath_chatbot__messages' );
		var inputEl = container.querySelector( '.leaderspath_chatbot__input' );
		var sendBtn = container.querySelector( '.leaderspath_chatbot__send' );
		var modelSelect = container.querySelector( '.leaderspath_chatbot__model_select' );
		var emptyState = container.querySelector( '.leaderspath_chatbot__empty' );

		// Upload UI only exists in the DOM for skills-enabled activities (see
		// class-chatbot-renderer.php) — these will be null otherwise, guarded
		// below wherever they're used.
		var hasSkills = container.dataset.hasSkills === '1';
		var attachBtn = container.querySelector( '.leaderspath_chatbot__attach' );
		var fileInput = container.querySelector( '.leaderspath_chatbot__file_input' );

		var postId = container.dataset.postId;
		var postType = container.dataset.postType;

		// Capture the empty-state text now, so reset() can rebuild it after
		// clearing the messages container.
		var emptyText = ( emptyState && emptyState.textContent )
			? emptyState.textContent.trim()
			: 'Send a message to start the conversation.';

		// State.
		var history = [];
		var containerId = null;
		var isSending = false;
		var abortController = null;
		// Single-use: one attached file per message, cleared after send. The
		// container itself persists across turns via containerId, so a
		// previously-uploaded file may already be reachable to Claude on later
		// turns too — but this flag specifically signals "attach to THIS turn".
		var attachedFileId = null;
		var attachedFileName = '';

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
		 * Create typing indicator with a live status label.
		 *
		 * The label surfaces what the AI is actually doing (thinking, running
		 * code, reading context) while it works — turning a silent wait into
		 * visible progress. This is on purpose for LeadersPath: showing the AI's
		 * real activity is part of the demonstration, not just a loading state.
		 */
		function createTypingIndicator() {
			var el = document.createElement( 'div' );
			el.className = 'leaderspath_chatbot__typing';
			el.setAttribute( 'aria-label', 'Working' );
			el.setAttribute( 'aria-live', 'polite' );
			el.innerHTML =
				'<span class="leaderspath_chatbot__typing_dots">' +
				'<span class="leaderspath_chatbot__typing_dot"></span>' +
				'<span class="leaderspath_chatbot__typing_dot"></span>' +
				'<span class="leaderspath_chatbot__typing_dot"></span>' +
				'</span>' +
				'<span class="leaderspath_chatbot__typing_status"></span>';
			return el;
		}

		/**
		 * Update a typing indicator's status label (e.g. "Running code…").
		 *
		 * @param {Element} typingEl The typing indicator element.
		 * @param {string}  label    Status text, or '' to clear.
		 */
		function setTypingStatus( typingEl, label ) {
			if ( ! typingEl ) {
				return;
			}
			var statusEl = typingEl.querySelector( '.leaderspath_chatbot__typing_status' );
			if ( statusEl ) {
				statusEl.textContent = label || '';
			}
			if ( label ) {
				typingEl.setAttribute( 'aria-label', label );
			}
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
		 * Create retry indicator element.
		 */
		function createRetryIndicator( attempt, max ) {
			var el = document.createElement( 'div' );
			el.className = 'leaderspath_chatbot__retry';
			el.setAttribute( 'aria-label', 'Retrying' );
			el.innerHTML =
				'<span class="leaderspath_chatbot__typing_dot"></span>' +
				'<span class="leaderspath_chatbot__retry_text">Retrying\u2026 (attempt ' + attempt + ' of ' + max + ')</span>';
			return el;
		}

		/**
		 * Remove any existing retry indicator.
		 */
		function removeRetryIndicator() {
			var existing = messagesEl.querySelector( '.leaderspath_chatbot__retry' );
			if ( existing && existing.parentNode ) {
				existing.parentNode.removeChild( existing );
			}
		}

		/**
		 * Create a "Try again" button for retrying the last message.
		 */
		function createTryAgainButton() {
			var el = document.createElement( 'button' );
			el.className = 'leaderspath_chatbot__try_again';
			el.type = 'button';
			el.textContent = 'Try again';
			el.addEventListener( 'click', function () {
				// Remove this button.
				if ( el.parentNode ) {
					el.parentNode.removeChild( el );
				}

				// Remove the error message above it.
				var prevEl = el.previousElementSibling;
				if ( prevEl && prevEl.classList.contains( 'leaderspath_chatbot__error' ) ) {
					prevEl.parentNode.removeChild( prevEl );
				}

				// Pop the last user message from history and re-send.
				if ( history.length > 0 && history[ history.length - 1 ].role === 'user' ) {
					var lastMessage = history.pop();
					inputEl.value = lastMessage.content;
					sendMessage();
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

			if ( attachedFileId ) {
				body.attached_file_id = attachedFileId;
			}

			return body;
		}

		/**
		 * Build request headers.
		 *
		 * @param {boolean} [json] True (default) for the JSON chat endpoints.
		 *   False for the multipart upload endpoint, where the browser must
		 *   set its own Content-Type (with the multipart boundary) — setting
		 *   it here would break the upload.
		 */
		function buildHeaders( json ) {
			var headers = {};
			if ( json !== false ) {
				headers['Content-Type'] = 'application/json';
			}
			if ( nonce ) {
				headers['X-WP-Nonce'] = nonce;
			}
			return headers;
		}

		/**
		 * Escape HTML special characters (used only in the no-libraries
		 * fallback path below, where nothing parses markdown at all).
		 */
		function escapeHtml( text ) {
			var div = document.createElement( 'div' );
			div.textContent = text;
			return div.innerHTML;
		}

		/**
		 * Convert markdown to HTML using marked.js, then sanitize the
		 * result with DOMPurify.
		 *
		 * marked.js (this vendored build has no `sanitize` option — removed
		 * upstream in v5+) passes raw HTML straight through unchanged, e.g.
		 * marked.parse('<img src=x onerror="...">') returns the tag
		 * untouched. This function has no server-side equivalent of
		 * markdown_to_html()'s `html_input: strip` + wp_kses_post()
		 * defense-in-depth, so it holds that line itself — for both the
		 * streaming assistant text and the learner's own message (the only
		 * two callers, chatbot.js:356 and :741).
		 *
		 * Sanitizing marked's *output* (rather than escaping the raw input
		 * before parsing) is deliberate: escaping first double-escapes
		 * anything marked.js already escapes itself, like code block
		 * contents, so a pasted `<script>` inside a fenced code block would
		 * render as the literal string "&lt;script&gt;" instead of
		 * "<script>". Sanitizing afterward avoids that.
		 */
		function markdownToHtml( text ) {
			if ( typeof marked !== 'undefined' && marked.parse ) {
				var html = marked.parse( text );
				return ( typeof DOMPurify !== 'undefined' )
					? DOMPurify.sanitize( html )
					: escapeHtml( text ).replace( /\n/g, '<br>' );
			}
			// Fallback: escape HTML and convert newlines.
			return escapeHtml( text ).replace( /\n/g, '<br>' );
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

			// Keep the typing indicator up until the first character actually
			// arrives, then swap it for the message bubble. A connected-but-silent
			// stream (container provisioning, code execution before any text) would
			// otherwise show an empty bubble that looks stalled/broken.
			var bubbleShown = false;
			function showBubble() {
				if ( bubbleShown ) {
					return;
				}
				bubbleShown = true;
				if ( typing.parentNode ) {
					typing.parentNode.removeChild( typing );
				}
				messagesEl.appendChild( assistantEl );
				messagesEl.appendChild( stopBtn );
				scrollToBottom();
			}

			fetch( restStreamUrl, {
				method: 'POST',
				headers: buildHeaders(),
				credentials: 'same-origin',
				body: JSON.stringify( body ),
				signal: abortController.signal,
			} )
				.then( function ( response ) {
					if ( ! response.ok ) {
						// Remove the typing indicator before surfacing the error.
						if ( typing.parentNode ) {
							typing.parentNode.removeChild( typing );
						}
						throw new Error( 'Server error (HTTP ' + response.status + '). Please try again.' );
					}

					// Do NOT show the bubble yet — wait for the first text delta
					// (see showBubble). The typing indicator stays up meanwhile.

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

							// Handle retry events from server — show retry indicator.
							if ( evt.event === 'retry' ) {
								removeRetryIndicator();
								messagesEl.appendChild( createRetryIndicator( data.attempt || 2, data.max || 3 ) );
								scrollToBottom();
								continue;
							}

							// Detect error events: explicit 'error' type from our PHP proxy,
							// or data payloads with error structure (from Anthropic).
							if ( evt.event === 'error' || ( data.type === 'error' && data.error ) ) {
								removeRetryIndicator();
								// Clear the typing indicator if still up (error before any text).
								if ( typing.parentNode ) {
									typing.parentNode.removeChild( typing );
								}
								var errMsg = ( data.error && data.error.message ) || data.message || 'An error occurred.';
								messagesEl.appendChild( createError( errMsg ) );
								messagesEl.appendChild( createTryAgainButton() );
								continue;
							}

							switch ( evt.event ) {
								case 'message_start':
									// Extract container ID.
									if ( data.message && data.message.container && data.message.container.id ) {
										containerId = data.message.container.id;
									}
									break;

								case 'content_block_start':
									// Surface what the AI is doing while it works. A
									// skilled activity spends most of its "wait" here:
									// thinking, then running code (reading its skill and
									// context files), before any text streams. Showing
									// that turns silent dots into visible progress.
									var blockType = data.content_block && data.content_block.type;
									if ( blockType === 'thinking' ) {
										setTypingStatus( typing, 'Thinking…' );
									} else if ( blockType === 'server_tool_use' ) {
										// The specific server tool the model invoked.
										var toolName = data.content_block.name || '';
										if ( toolName === 'web_search' ) {
											setTypingStatus( typing, 'Searching the web…' );
										} else if ( toolName === 'web_fetch' ) {
											setTypingStatus( typing, 'Reading a web page…' );
										} else {
											setTypingStatus( typing, 'Running code…' );
										}
									} else if (
										blockType &&
										blockType.indexOf( 'code_execution_tool_result' ) !== -1
									) {
										setTypingStatus( typing, 'Reading reference material…' );
									}
									break;

								case 'content_block_delta':
									// Append text delta and schedule throttled markdown render.
									if ( data.delta && data.delta.type === 'text_delta' && data.delta.text ) {
										// First real character: swap the typing indicator
										// for the message bubble now.
										showBubble();
										accumulatedText += data.delta.text;
										scheduleRender();
									}
									break;

								case 'done':
									// Custom [DONE] event from our PHP proxy.
									removeRetryIndicator();
									if ( data.container_id ) {
										containerId = data.container_id;
									}

									// Use content_raw from server for history.
									var rawContent = data.content_raw || accumulatedText;

									// Only record and render if there's actual content.
									if ( rawContent ) {
										// Ensure the bubble is shown (e.g. if content
										// arrived via content_raw without a text delta).
										showBubble();
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

					// If no done event was received but text arrived, finalize.
					if ( accumulatedText ) {
						showBubble();
						if ( ! contentEl.innerHTML ) {
							history.push( {
								role: 'assistant',
								content: accumulatedText,
							} );
							renderNow();
						}
					}

					// Stream ended with no content at all: clear the typing
					// indicator so it doesn't hang (bubble was never shown).
					if ( ! accumulatedText && typing.parentNode ) {
						typing.parentNode.removeChild( typing );
					}

					// Defensive: remove the bubble if it was shown but stayed empty.
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
		 * HTTP status codes that are safe to retry.
		 */
		var retryableHttpCodes = [ 500, 502, 503, 529 ];
		var syncRetryDelays = [ 1000, 3000 ]; // ms

		/**
		 * Send a message via synchronous (non-streaming) endpoint.
		 */
		function sendMessageSync( message, body, typing ) {
			var attempt = 0;
			var maxAttempts = syncRetryDelays.length + 1; // 3 total

			function attemptFetch() {
				fetch( restUrl, {
					method: 'POST',
					headers: buildHeaders(),
					credentials: 'same-origin',
					body: JSON.stringify( body ),
				} )
					.then( function ( response ) {
						if ( ! response.ok ) {
							// Check if retryable.
							if ( retryableHttpCodes.indexOf( response.status ) !== -1 && attempt < syncRetryDelays.length ) {
								var delay = syncRetryDelays[ attempt ];
								attempt++;

								// Swap typing indicator for retry indicator.
								if ( typing.parentNode ) {
									typing.parentNode.removeChild( typing );
								}
								removeRetryIndicator();
								messagesEl.appendChild( createRetryIndicator( attempt + 1, maxAttempts ) );
								scrollToBottom();

								setTimeout( attemptFetch, delay );
								return null; // Signal: retry scheduled.
							}

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
						if ( data === null ) {
							return; // Retry scheduled, skip processing.
						}

						// Remove typing / retry indicators.
						if ( typing.parentNode ) {
							typing.parentNode.removeChild( typing );
						}
						removeRetryIndicator();

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
						// Remove typing / retry indicators.
						if ( typing.parentNode ) {
							typing.parentNode.removeChild( typing );
						}
						removeRetryIndicator();

						messagesEl.appendChild(
							createError( err.message || 'An error occurred.' )
						);
						messagesEl.appendChild( createTryAgainButton() );
						scrollToBottom();

						setSending( false );
						inputEl.focus();
					} );
			}

			attemptFetch();
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

			// Build request body (reads attachedFileId if set).
			var body = buildRequestBody( message );

			// Single-use: clear the attachment now that it's included in this
			// turn's request body. Also removes the "attached" chip from the UI.
			if ( attachedFileId ) {
				clearAttachedFile();
			}

			// Use streaming if available, otherwise fall back to sync.
			if ( canStream ) {
				sendMessageStream( message, body, typing );
			} else {
				sendMessageSync( message, body, typing );
			}
		}

		/**
		 * Reset this activity's conversation to a clean slate.
		 *
		 * Scoped to THIS instance only — other activities' conversations are
		 * untouched. Clears history + container ID (so the API session restarts),
		 * removes rendered messages, and re-shows the opening instructions and
		 * empty state. Not a page reload; no fetch.
		 */
		function resetChat() {
			if ( isSending && abortController ) {
				abortController.abort();
			}

			history = [];
			containerId = null;
			isSending = false;

			// A fresh container means any file uploaded into the old one is no
			// longer reachable — drop the pending attachment too.
			if ( hasSkills ) {
				clearAttachedFile();
			}

			// Clear all rendered messages.
			messagesEl.innerHTML = '';

			// Re-render the opening instructions from the stashed template, if any.
			var tpl = container.querySelector( '.leaderspath_chatbot__instructions_tpl' );
			if ( tpl && tpl.content ) {
				messagesEl.appendChild( tpl.content.cloneNode( true ) );
			}

			// Restore the empty state (re-create it — original node was cleared).
			emptyState = document.createElement( 'div' );
			emptyState.className = 'leaderspath_chatbot__empty';
			var emptyMsg = document.createElement( 'p' );
			emptyMsg.textContent = emptyText;
			emptyState.appendChild( emptyMsg );
			messagesEl.appendChild( emptyState );

			// Re-enable input.
			setSending( false );
			inputEl.value = '';
			inputEl.style.height = 'auto';
			scrollToBottom();
		}

		// Expose this instance's reset for the marker's reset control (a Bricks
		// element outside the widget) via window.LeadersPath.resetActivity(id).
		if ( postType === 'activity' && container.dataset.activityId ) {
			registerInstance( container.dataset.activityId, { reset: resetChat } );
		}

		// Cache pre-warm: on first focus of the input (before the first message),
		// fire a fire-and-forget request that writes the prompt cache during the
		// learner's "reading/thinking" time, so the first real message is a cache
		// hit instead of processing ~28K tokens of context cold. Once per instance.
		var warmSent = false;
		function warmCache() {
			if ( warmSent || ! restWarmUrl ) {
				return;
			}
			warmSent = true;

			var warmBody = {};
			if ( postType === 'activity' ) {
				warmBody.activity_id = parseInt( postId, 10 );
			} else {
				warmBody.lesson_id = parseInt( postId, 10 );
			}

			// Fire-and-forget: failures are harmless (a cold first message still
			// works). keepalive lets it survive a quick navigation.
			fetch( restWarmUrl, {
				method: 'POST',
				headers: buildHeaders(),
				credentials: 'same-origin',
				body: JSON.stringify( warmBody ),
				keepalive: true,
			} ).catch( function () {} );
		}

		/**
		 * Remove the "file attached" chip and clear attachment state.
		 */
		function clearAttachedFile() {
			attachedFileId = null;
			attachedFileName = '';
			var chip = container.querySelector( '.leaderspath_chatbot__attachment_chip' );
			if ( chip && chip.parentNode ) {
				chip.parentNode.removeChild( chip );
			}
			if ( fileInput ) {
				fileInput.value = '';
			}
		}

		/**
		 * Show a small removable chip above the input row indicating a file
		 * is attached and will be sent with the next message.
		 */
		function showAttachedFileChip( filename ) {
			var existing = container.querySelector( '.leaderspath_chatbot__attachment_chip' );
			if ( existing && existing.parentNode ) {
				existing.parentNode.removeChild( existing );
			}

			var chip = document.createElement( 'div' );
			chip.className = 'leaderspath_chatbot__attachment_chip';

			var label = document.createElement( 'span' );
			label.textContent = '📎 ' + filename + ' — will be sent with your next message';
			chip.appendChild( label );

			var removeBtn = document.createElement( 'button' );
			removeBtn.type = 'button';
			removeBtn.className = 'leaderspath_chatbot__attachment_chip_remove';
			removeBtn.setAttribute( 'aria-label', 'Remove attached file' );
			removeBtn.textContent = '×';
			removeBtn.addEventListener( 'click', clearAttachedFile );
			chip.appendChild( removeBtn );

			var inputArea = container.querySelector( '.leaderspath_chatbot__input_area' );
			if ( inputArea ) {
				inputArea.insertBefore( chip, inputArea.firstChild );
			}
		}

		/**
		 * Validate a File client-side against the same allowlist/size cap the
		 * server enforces. UX-only — never authoritative; the server
		 * re-validates every upload regardless.
		 */
		function validateFileForUpload( file ) {
			if ( file.size > MAX_UPLOAD_BYTES ) {
				return 'That file is too large. The maximum size is 30MB.';
			}
			if ( file.type && ALLOWED_UPLOAD_MIME_TYPES.indexOf( file.type ) === -1 ) {
				return 'This file type is not supported.';
			}
			return null;
		}

		/**
		 * Upload a file to /chat/upload and, on success, store its file_id to
		 * attach to the next message sent.
		 */
		function uploadFile( file ) {
			if ( ! restUploadUrl || ! file ) {
				return;
			}

			var validationError = validateFileForUpload( file );
			if ( validationError ) {
				messagesEl.appendChild( createError( validationError ) );
				scrollToBottom();
				return;
			}

			var formData = new FormData();
			formData.append( 'activity_id', parseInt( postId, 10 ) );
			formData.append( 'file', file );

			fetch( restUploadUrl, {
				method: 'POST',
				headers: buildHeaders( false ),
				credentials: 'same-origin',
				body: formData,
			} )
				.then( function ( response ) {
					return response.json().then( function ( data ) {
						if ( ! response.ok ) {
							throw new Error( data.message || 'File upload failed.' );
						}
						return data;
					} );
				} )
				.then( function ( data ) {
					attachedFileId = data.file_id;
					attachedFileName = file.name;
					showAttachedFileChip( file.name );
				} )
				.catch( function ( err ) {
					messagesEl.appendChild( createError( err.message || 'File upload failed.' ) );
					scrollToBottom();
				} );
		}

		// File upload UI wiring — only present in the DOM for skills-enabled
		// activities (see class-chatbot-renderer.php), so guard on both the
		// per-instance flag and the elements actually existing (defensive, in
		// case markup and JS ever drift).
		if ( hasSkills && attachBtn && fileInput ) {
			attachBtn.addEventListener( 'click', function () {
				fileInput.click();
			} );

			fileInput.addEventListener( 'change', function ( e ) {
				if ( e.target.files && e.target.files[ 0 ] ) {
					uploadFile( e.target.files[ 0 ] );
				}
			} );

			// Drag-and-drop onto the whole widget container.
			container.addEventListener( 'dragover', function ( e ) {
				e.preventDefault();
				container.classList.add( 'leaderspath_chatbot--dragover' );
			} );

			container.addEventListener( 'dragleave', function ( e ) {
				// Only clear when actually leaving the container (not entering
				// a child element), otherwise the class flickers.
				if ( ! container.contains( e.relatedTarget ) ) {
					container.classList.remove( 'leaderspath_chatbot--dragover' );
				}
			} );

			container.addEventListener( 'drop', function ( e ) {
				e.preventDefault();
				container.classList.remove( 'leaderspath_chatbot--dragover' );
				if ( e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[ 0 ] ) {
					uploadFile( e.dataTransfer.files[ 0 ] );
				}
			} );
		}

		// Event listeners.
		sendBtn.addEventListener( 'click', sendMessage );

		inputEl.addEventListener( 'focus', warmCache );

		inputEl.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' && ! e.shiftKey ) {
				e.preventDefault();
				sendMessage();
			}
		} );

		inputEl.addEventListener( 'input', autoGrow );

		// A reset button rendered inside the widget (optional) also works.
		var innerReset = container.querySelector( '.leaderspath_chatbot__reset' );
		if ( innerReset ) {
			innerReset.addEventListener( 'click', resetChat );
		}
	}

	// Registry of initialized activity instances, keyed by activity id, so the
	// lesson-page marker's reset control can reach a specific chatbot.
	var instances = {};

	function registerInstance( activityId, api ) {
		instances[ String( activityId ) ] = api;
	}

	/**
	 * Public API for the lesson page (Bricks marker elements call these).
	 */
	var publicApi = {
		/**
		 * Reset a specific activity's conversation. Called by the Activity
		 * Marker's "Start over" control.
		 *
		 * @param {number|string} activityId
		 * @return {boolean} true if an instance was found and reset.
		 */
		resetActivity: function ( activityId ) {
			var inst = instances[ String( activityId ) ];
			if ( inst && typeof inst.reset === 'function' ) {
				inst.reset();
				return true;
			}
			return false;
		},

		/**
		 * Ensure the chatbot for a given container/activity is initialized.
		 * Safe to call repeatedly (init-once guarded).
		 *
		 * @param {Element} container
		 */
		ensureInit: function ( container ) {
			if ( container ) {
				initChatbot( container );
			}
		},
	};

	// Merge onto any existing namespace (config object shares window.LeadersPath* ).
	window.LeadersPath = window.LeadersPath || {};
	window.LeadersPath.resetActivity = publicApi.resetActivity;
	window.LeadersPath.ensureInit = publicApi.ensureInit;

	/**
	 * Initialize all chatbot instances on the page.
	 */
	function init() {
		var containers = document.querySelectorAll(
			'.leaderspath_chatbot__container'
		);
		containers.forEach( initChatbot );

		observeLessonWorkspace();
	}

	/**
	 * Lesson page: watch the workspace for activity sections becoming active,
	 * and initialize that section's chatbot the FIRST time it is shown.
	 *
	 * Init-once only: revealing a previously-hidden section must not reset its
	 * conversation. A section is considered active when it gains the `is-active`
	 * class (added by the Bricks Interaction). The workspace opts in by carrying
	 * a `data-lp-workspace` attribute (set in the Bricks template).
	 */
	function observeLessonWorkspace() {
		if ( typeof MutationObserver === 'undefined' ) {
			return;
		}

		var workspace = document.querySelector( '[data-lp-workspace]' );
		if ( ! workspace ) {
			return; // Not a lesson page — nothing to observe.
		}

		var observer = new MutationObserver( function ( mutations ) {
			for ( var i = 0; i < mutations.length; i++ ) {
				var target = mutations[ i ].target;
				if (
					target.nodeType === 1 &&
					target.classList &&
					target.classList.contains( 'is-active' )
				) {
					var containerEl = target.querySelector(
						'.leaderspath_chatbot__container'
					);
					if ( containerEl ) {
						initChatbot( containerEl ); // init-once guarded
					}
				}
			}
		} );

		observer.observe( workspace, {
			subtree: true,
			attributes: true,
			attributeFilter: [ 'class' ],
		} );
	}

	// Initialize when DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
