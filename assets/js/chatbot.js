/**
 * Chatbot — frontend interactivity.
 *
 * Handles:
 * - Send message via REST API (activity sandbox or lesson Q&A)
 * - Maintain in-memory conversation history
 * - Render user + assistant messages
 * - Typing indicator during API call
 * - Auto-scroll, auto-grow textarea
 * - Model switching (activity only)
 * - Container ID tracking for session continuity
 * - Inline error display
 *
 * @package LeadersPath
 * @since   0.6.0
 */

( function () {
	'use strict';

	var config = window.LeadersPathChatbot || {};
	var restUrl = config.restUrl || '/wp-json/leaderspath/v1/chat';
	var nonce = config.nonce || '';

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
			messagesEl.appendChild( createMessage( 'user', message, false ) );
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
			var body = {
				message: message,
				history: history.slice( 0, -1 ), // Exclude current message (already in 'message' param).
			};

			if ( postType === 'activity' ) {
				body.activity_id = parseInt( postId, 10 );
			} else {
				body.lesson_id = parseInt( postId, 10 );
			}

			// Include model selection if available.
			if ( modelSelect && modelSelect.value ) {
				body.model = modelSelect.value;
			}

			// Include container ID for session continuity.
			if ( containerId ) {
				body.container_id = containerId;
			}

			// Send request.
			var headers = {
				'Content-Type': 'application/json',
			};
			if ( nonce ) {
				headers['X-WP-Nonce'] = nonce;
			}

			fetch( restUrl, {
				method: 'POST',
				headers: headers,
				credentials: 'same-origin',
				body: JSON.stringify( body ),
			} )
				.then( function ( response ) {
					// Remove typing indicator.
					if ( typing.parentNode ) {
						typing.parentNode.removeChild( typing );
					}

					if ( ! response.ok ) {
						return response.json().then( function ( errData ) {
							throw new Error(
								errData.message || 'HTTP ' + response.status
							);
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
