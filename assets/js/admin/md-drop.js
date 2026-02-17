/**
 * Markdown file drop handler for TinyMCE editors.
 *
 * Allows users to drag-and-drop .md files onto any TinyMCE editor —
 * both the standard WordPress post editor and ACF WYSIWYG fields.
 * The file is read client-side, converted to HTML via marked.js,
 * and inserted into the editor.
 *
 * Architecture note: WordPress has two layers of drag-and-drop interception
 * that we must defeat for .md files:
 *
 * 1. EditorUploader (media-views.js) — listens on $(document) for dragover,
 *    shows an `.uploader-editor` overlay on `.wp-editor-wrap`, then handles
 *    the drop on that overlay to open the media workflow. This operates in
 *    the PARENT document.
 *
 * 2. TinyMCE paste plugin — blocks file drops inside the iframe when
 *    paste_data_images is false.
 *
 * We handle both by intercepting on the parent document (capture phase)
 * for the main post editor, and on the iframe document for ACF WYSIWYG.
 *
 * @package LeadersPath
 * @since   0.8.0
 */
(function () {
	'use strict';

	var markedLib = null;

	/**
	 * Configure marked for safe, predictable output.
	 */
	function getMarkedInstance() {
		if ( markedLib ) {
			return markedLib;
		}

		if ( typeof marked === 'undefined' ) {
			return null;
		}

		// Strip raw HTML blocks from Markdown to prevent XSS.
		marked.use({
			renderer: {
				html: function () {
					return '';
				}
			}
		});

		markedLib = marked;
		return markedLib;
	}

	/**
	 * Check if a drag event contains a .md file.
	 *
	 * During dragover we can only check dataTransfer.types (not filenames),
	 * so this returns true if Files are present. During drop we can check
	 * the actual filename.
	 */
	function hasMdFile( files ) {
		if ( ! files || files.length === 0 ) {
			return false;
		}
		for ( var i = 0; i < files.length; i++ ) {
			if ( /\.(?:md|markdown)$/i.test( files[ i ].name ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the first .md file from a FileList.
	 */
	function getMdFile( files ) {
		for ( var i = 0; i < files.length; i++ ) {
			if ( /\.(?:md|markdown)$/i.test( files[ i ].name ) ) {
				return files[ i ];
			}
		}
		return null;
	}

	/**
	 * Convert markdown and insert into a TinyMCE editor.
	 */
	function importMarkdown( editor, mdFile ) {
		var lib = getMarkedInstance();
		if ( ! lib ) {
			return;
		}

		// Size guard: 1 MB.
		if ( mdFile.size > 1024 * 1024 ) {
			editor.notificationManager.open({
				text: 'Markdown file too large. Maximum size is 1 MB.',
				type: 'error',
				timeout: 5000
			});
			return;
		}

		var reader = new FileReader();
		reader.onload = function ( event ) {
			var html = lib.parse( event.target.result );

			editor.focus();
			editor.insertContent( html );
			editor.undoManager.add();

			editor.notificationManager.open({
				text: 'Imported: ' + mdFile.name,
				type: 'success',
				timeout: 3000
			});
		};
		reader.onerror = function () {
			editor.notificationManager.open({
				text: 'Error reading file.',
				type: 'error',
				timeout: 5000
			});
		};
		reader.readAsText( mdFile );
	}

	/**
	 * Find the TinyMCE editor instance for a .wp-editor-wrap element.
	 *
	 * WordPress wraps each editor in #wp-{id}-wrap. The TinyMCE instance
	 * ID matches the inner ID.
	 */
	function getEditorForWrap( wrap ) {
		if ( typeof tinymce === 'undefined' ) {
			return null;
		}

		// The wrap ID is "wp-{editorId}-wrap".
		var wrapId = wrap.id || '';
		var match = wrapId.match( /^wp-(.+)-wrap$/ );
		if ( ! match ) {
			return null;
		}

		return tinymce.get( match[1] ) || null;
	}

	/**
	 * Track whether we're in a .md drag so dragover knows to suppress.
	 *
	 * We can't inspect filenames during dragover (only during drop), but
	 * we CAN check dataTransfer.types for 'Files'. We suppress the WP
	 * overlay for ALL file drags over .wp-editor-wrap elements, but only
	 * actually handle the drop if it's .md. Non-.md drops will be silently
	 * ignored (the user just needs to drop again — a minor tradeoff vs.
	 * accidentally opening the media uploader for .md files).
	 */

	/**
	 * Set up parent-document handlers for the standard WordPress post editor.
	 *
	 * These capture-phase listeners on document run BEFORE the jQuery
	 * delegated handlers that EditorUploader binds on $(document), preventing
	 * the `.uploader-editor` overlay from appearing for file drags.
	 */
	function setupParentDocumentHandlers() {
		// Suppress the EditorUploader overlay for file drags over editor wraps.
		document.addEventListener( 'dragover', function ( e ) {
			if ( ! e.dataTransfer || ! e.dataTransfer.types ) {
				return;
			}
			if ( e.dataTransfer.types.indexOf( 'Files' ) === -1 ) {
				return;
			}

			// Only intercept drags over a .wp-editor-wrap (or its children).
			var wrap = e.target.closest
				? e.target.closest( '.wp-editor-wrap' )
				: null;

			if ( ! wrap ) {
				return;
			}

			// Check that there's a TinyMCE editor active for this wrap.
			var editor = getEditorForWrap( wrap );
			if ( ! editor ) {
				return;
			}

			e.preventDefault();
			e.stopImmediatePropagation();
		}, true );

		// Handle the actual drop on .wp-editor-wrap.
		document.addEventListener( 'drop', function ( e ) {
			var files = e.dataTransfer && e.dataTransfer.files;
			if ( ! files || files.length === 0 ) {
				return;
			}

			// Only intercept drops over a .wp-editor-wrap.
			var wrap = e.target.closest
				? e.target.closest( '.wp-editor-wrap' )
				: null;

			if ( ! wrap ) {
				return;
			}

			var mdFile = getMdFile( files );
			if ( ! mdFile ) {
				return; // Not .md — let WordPress handle it.
			}

			var editor = getEditorForWrap( wrap );
			if ( ! editor ) {
				return;
			}

			e.preventDefault();
			e.stopImmediatePropagation();

			importMarkdown( editor, mdFile );
		}, true );
	}

	/**
	 * Set up iframe-level handlers for ACF WYSIWYG editors.
	 *
	 * ACF WYSIWYG fields don't live inside .wp-editor-wrap, so they
	 * aren't affected by the EditorUploader. But TinyMCE's paste plugin
	 * still blocks file drops inside the iframe. Capture-phase handlers
	 * on the iframe document run before the paste plugin.
	 *
	 * @param {tinymce.Editor} editor TinyMCE editor instance.
	 */
	function setupIframeDrop( editor ) {
		var doc = editor.getDoc();
		if ( ! doc ) {
			return;
		}

		if ( ! getMarkedInstance() ) {
			return;
		}

		// Allow drops by preventing default dragover (capture phase).
		doc.addEventListener( 'dragover', function ( e ) {
			if ( ! e.dataTransfer || ! e.dataTransfer.types ) {
				return;
			}
			if ( e.dataTransfer.types.indexOf( 'Files' ) === -1 ) {
				return;
			}

			e.preventDefault();
			e.stopImmediatePropagation();
		}, true );

		// Handle drop inside iframe (capture phase).
		doc.addEventListener( 'drop', function ( e ) {
			var files = e.dataTransfer && e.dataTransfer.files;
			if ( ! files || files.length === 0 ) {
				return;
			}

			var mdFile = getMdFile( files );
			if ( ! mdFile ) {
				return;
			}

			e.preventDefault();
			e.stopImmediatePropagation();

			importMarkdown( editor, mdFile );
		}, true );
	}

	// ---------------------------------------------------------------
	// Initialization
	// ---------------------------------------------------------------

	// Track which editors have been set up to avoid double-binding.
	var initialized = {};

	function initEditor( editor ) {
		if ( initialized[ editor.id ] ) {
			return;
		}
		initialized[ editor.id ] = true;

		// Check if this editor lives inside a .wp-editor-wrap (standard WP editor).
		// If so, the parent document handlers cover it — no iframe binding needed.
		var container = editor.getContainer();
		if ( container && container.closest && container.closest( '.wp-editor-wrap' ) ) {
			return;
		}

		// ACF WYSIWYG or other non-wrapped editors: bind on the iframe.
		setupIframeDrop( editor );
	}

	// Set up parent document handlers once for all .wp-editor-wrap editors.
	setupParentDocumentHandlers();

	// Hook into TinyMCE for standard WordPress editors.
	if ( typeof tinymce !== 'undefined' ) {
		tinymce.on( 'AddEditor', function ( e ) {
			e.editor.on( 'init', function () {
				initEditor( e.editor );
			});
		});
	}

	// Hook into ACF for WYSIWYG fields.
	if ( typeof acf !== 'undefined' ) {
		acf.addAction( 'wysiwyg_tinymce_init', function ( editor ) {
			initEditor( editor );
		});
	}
})();
