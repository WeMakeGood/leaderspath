/**
 * Context Library modal — frontend interactivity.
 *
 * Handles:
 * - View button → fetch content via REST → display in modal
 * - Download button → trigger file download as .md
 * - Modal close via X, overlay click, or Escape key
 *
 * @package LeadersPath
 * @since   0.5.0
 */

( function () {
	'use strict';

	var config = window.LeadersPathContextLibrary || {};
	var restUrl = config.restUrl || '/wp-json/leaderspath/v1/context/';
	var nonce = config.nonce || '';

	/**
	 * Find the modal within a module container.
	 */
	function findModal( button ) {
		var module = button.closest( '.leaderspath_context_library' );
		if ( ! module ) {
			return null;
		}
		return module.querySelector( '.leaderspath_modal__overlay' );
	}

	/**
	 * Open modal and fetch context content.
	 */
	function openModal( modal, contextId, contextTitle ) {
		var titleEl = modal.querySelector( '.leaderspath_modal__title' );
		var contentEl = modal.querySelector( '.leaderspath_modal__content' );
		var downloadBtn = modal.querySelector( '.leaderspath_modal__download_btn' );

		titleEl.textContent = contextTitle;
		contentEl.innerHTML = '<p style="opacity:0.6;font-style:italic;">Loading&hellip;</p>';
		modal.removeAttribute( 'hidden' );
		document.body.style.overflow = 'hidden';

		// Store context ID on modal for download button.
		modal.dataset.contextId = contextId;
		modal.dataset.contextTitle = contextTitle;

		// Focus the close button.
		var closeBtn = modal.querySelector( '.leaderspath_modal__close' );
		if ( closeBtn ) {
			closeBtn.focus();
		}

		// Fetch content via REST.
		var url = restUrl + contextId + '/download';
		var headers = { 'Content-Type': 'application/json' };
		if ( nonce ) {
			headers['X-WP-Nonce'] = nonce;
		}

		fetch( url, { headers: headers, credentials: 'same-origin' } )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'HTTP ' + response.status );
				}
				return response.json();
			} )
			.then( function ( data ) {
				// Render markdown as pre-formatted text.
				// A shared markdown renderer can be added later.
				var content = data.content || '';
				contentEl.innerHTML = '';
				var pre = document.createElement( 'pre' );
				pre.className = 'leaderspath_modal__markdown';
				pre.style.whiteSpace = 'pre-wrap';
				pre.style.wordBreak = 'break-word';
				pre.textContent = content;
				contentEl.appendChild( pre );
			} )
			.catch( function ( err ) {
				contentEl.innerHTML =
					'<p style="color:#c00;">Error loading content: ' +
					err.message +
					'</p>';
			} );
	}

	/**
	 * Close modal.
	 */
	function closeModal( modal ) {
		modal.setAttribute( 'hidden', '' );
		document.body.style.overflow = '';
	}

	/**
	 * Download context file as .md.
	 */
	function downloadContext( contextId, contextTitle ) {
		var url = restUrl + contextId + '/download';
		var headers = { 'Content-Type': 'application/json' };
		if ( nonce ) {
			headers['X-WP-Nonce'] = nonce;
		}

		fetch( url, { headers: headers, credentials: 'same-origin' } )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'HTTP ' + response.status );
				}
				return response.json();
			} )
			.then( function ( data ) {
				var content = data.content || '';
				var blob = new Blob( [ content ], { type: 'text/markdown' } );
				var a = document.createElement( 'a' );
				var filename = ( contextTitle || 'context' )
					.toLowerCase()
					.replace( /[^a-z0-9]+/g, '-' )
					.replace( /^-|-$/g, '' );
				a.href = URL.createObjectURL( blob );
				a.download = filename + '.md';
				document.body.appendChild( a );
				a.click();
				document.body.removeChild( a );
				URL.revokeObjectURL( a.href );
			} )
			.catch( function ( err ) {
				/* eslint-disable-next-line no-console */
				console.error( 'LeadersPath: download failed', err );
			} );
	}

	/**
	 * Initialize event listeners via delegation.
	 */
	function init() {
		document.addEventListener( 'click', function ( e ) {
			var target = e.target;

			// View button on card.
			var viewBtn = target.closest( '.leaderspath_context_library__view' );
			if ( viewBtn ) {
				e.preventDefault();
				var modal = findModal( viewBtn );
				if ( modal ) {
					openModal(
						modal,
						viewBtn.dataset.contextId,
						viewBtn.dataset.contextTitle
					);
				}
				return;
			}

			// Download button on card.
			var dlBtn = target.closest( '.leaderspath_context_library__download' );
			if ( dlBtn ) {
				e.preventDefault();
				downloadContext(
					dlBtn.dataset.contextId,
					dlBtn.dataset.contextTitle
				);
				return;
			}

			// Modal close button.
			if ( target.closest( '.leaderspath_modal__close' ) ) {
				var closeModal2 = target.closest( '.leaderspath_modal__overlay' );
				if ( closeModal2 ) {
					closeModal( closeModal2 );
				}
				return;
			}

			// Modal overlay click (outside dialog).
			if ( target.classList.contains( 'leaderspath_modal__overlay' ) ) {
				closeModal( target );
				return;
			}

			// Modal download button.
			var modalDlBtn = target.closest( '.leaderspath_modal__download_btn' );
			if ( modalDlBtn ) {
				var modalEl = target.closest( '.leaderspath_modal__overlay' );
				if ( modalEl ) {
					downloadContext(
						modalEl.dataset.contextId,
						modalEl.dataset.contextTitle
					);
				}
				return;
			}
		} );

		// Escape key closes modal.
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				var openModals = document.querySelectorAll(
					'.leaderspath_modal__overlay:not([hidden])'
				);
				openModals.forEach( function ( modal ) {
					closeModal( modal );
				} );
			}
		} );
	}

	// Initialize when DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
