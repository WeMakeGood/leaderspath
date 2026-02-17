<?php
/**
 * Context File uploader for the admin editor.
 *
 * Adds a drag-and-drop / file-select upload zone to the Context File edit
 * screen. When a text file is uploaded, its content is injected into the
 * WordPress editor (post_content) and the post slug is set from the filename
 * if not already set.
 *
 * @package LeadersPath
 * @since   0.7.0
 */

declare(strict_types=1);

namespace LeadersPath\Admin;

/**
 * Manages the context file upload metabox.
 *
 * @since 0.7.0
 */
class Context_Uploader {

	/**
	 * Initialize the class.
	 *
	 * @since 0.7.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes_leaderspath_context', [ $this, 'add_metabox' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_script' ] );
		add_filter( 'user_can_richedit', [ $this, 'disable_visual_editor' ] );
	}

	/**
	 * Disable the Visual (TinyMCE) editor for Context Files.
	 *
	 * Context files are always markdown/plain text. The visual editor
	 * mangles whitespace and formatting, so we force text-only mode.
	 *
	 * @since 0.7.0
	 *
	 * @param bool $can Whether the user can use the rich editor.
	 * @return bool
	 */
	public function disable_visual_editor( bool $can ): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $can;
		}

		$screen = \get_current_screen();

		if ( $screen && 'leaderspath_context' === $screen->post_type ) {
			return false;
		}

		return $can;
	}

	/**
	 * Register the upload metabox.
	 *
	 * @since 0.7.0
	 */
	public function add_metabox(): void {
		add_meta_box(
			'leaderspath_context_upload',
			__( 'Import from File', 'leaderspath' ),
			[ $this, 'render_metabox' ],
			'leaderspath_context',
			'side',
			'high'
		);
	}

	/**
	 * Render the upload metabox.
	 *
	 * @since 0.7.0
	 *
	 * @param \WP_Post $post Current post.
	 */
	public function render_metabox( \WP_Post $post ): void {
		?>
		<div id="leaderspath-context-dropzone" class="leaderspath-context-dropzone">
			<p class="leaderspath-context-dropzone__label">
				<?php esc_html_e( 'Drop a file here or', 'leaderspath' ); ?>
			</p>
			<label class="button leaderspath-context-dropzone__button">
				<?php esc_html_e( 'Select File', 'leaderspath' ); ?>
				<input
					type="file"
					id="leaderspath-context-file-input"
					accept=".txt,.md,.markdown,.json,.xml,.csv,.yaml,.yml,.html,.htm,.css,.js,.ts,.py,.php,.rb,.sh,.sql,.log,.cfg,.conf,.ini,.env,.toml"
					style="display: none;"
				/>
			</label>
			<p class="leaderspath-context-dropzone__hint">
				<?php esc_html_e( 'Text files only. Content will replace the editor.', 'leaderspath' ); ?>
			</p>
		</div>
		<style>
			.leaderspath-context-dropzone {
				border: 2px dashed #c3c4c7;
				border-radius: 4px;
				padding: 16px;
				text-align: center;
				transition: border-color 0.2s, background-color 0.2s;
			}
			.leaderspath-context-dropzone--active {
				border-color: #2271b1;
				background-color: #f0f6fc;
			}
			.leaderspath-context-dropzone__label {
				margin: 0 0 8px;
				color: #50575e;
			}
			.leaderspath-context-dropzone__hint {
				margin: 8px 0 0;
				font-size: 12px;
				color: #787c82;
			}
			.leaderspath-context-dropzone__status {
				margin: 8px 0 0;
				font-size: 12px;
				font-weight: 600;
			}
			.leaderspath-context-dropzone__status--success {
				color: #00a32a;
			}
			.leaderspath-context-dropzone__status--error {
				color: #d63638;
			}
		</style>
		<?php
	}

	/**
	 * Enqueue the upload script on the context file edit screen.
	 *
	 * @since 0.7.0
	 *
	 * @param string $hook_suffix Admin page hook suffix.
	 */
	public function enqueue_script( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$screen = \get_current_screen();
		if ( ! $screen || 'leaderspath_context' !== $screen->post_type ) {
			return;
		}

		$script = <<<'JS'
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		var dropzone = document.getElementById('leaderspath-context-dropzone');
		var fileInput = document.getElementById('leaderspath-context-file-input');

		if (!dropzone || !fileInput) {
			return;
		}

		/**
		 * Set the content in the WordPress editor textarea.
		 *
		 * The visual editor is disabled for context files, so we
		 * always write directly to the plain-text textarea.
		 */
		function setEditorContent(content) {
			var textarea = document.getElementById('content');
			if (textarea) {
				textarea.value = content;
				// Trigger change so WordPress marks the post as dirty.
				var event = new Event('input', { bubbles: true });
				textarea.dispatchEvent(event);
				return true;
			}

			return false;
		}

		/**
		 * Set the post slug from the filename.
		 *
		 * Only sets the slug if it is currently empty (new post).
		 */
		function setSlugFromFilename(filename) {
			var slugInput = document.getElementById('post_name');
			var editSlugBox = document.getElementById('edit-slug-box');

			// Only set slug if it's empty or we're on a new post.
			// Check the actual slug input or the URL sample.
			var currentSlug = '';
			if (slugInput) {
				currentSlug = slugInput.value;
			}

			// On new posts, the slug field is empty.
			// On existing posts, it has a value — don't overwrite.
			if (currentSlug) {
				return;
			}

			// Remove extension and sanitize for slug.
			var slug = filename
				.replace(/\.[^/.]+$/, '') // Remove extension.
				.toLowerCase()
				.replace(/[^a-z0-9-]/g, '-') // Non-alphanumeric to hyphens.
				.replace(/-+/g, '-')          // Collapse consecutive hyphens.
				.replace(/^-|-$/g, '');       // Trim leading/trailing hyphens.

			if (!slug) {
				return;
			}

			// Set the title if empty (new post).
			var titleInput = document.getElementById('title');
			if (titleInput && !titleInput.value) {
				// Use filename without extension as title, preserving original casing.
				var title = filename.replace(/\.[^/.]+$/, '');
				titleInput.value = title;
				// Trigger change for the permalink generator.
				var titleEvent = new Event('input', { bubbles: true });
				titleInput.dispatchEvent(titleEvent);
			}

			// Set the slug directly if the input exists.
			if (slugInput) {
				slugInput.value = slug;
			}

			// Also try the new-post-slug input (appears on unsaved posts).
			var newSlugInput = document.getElementById('new-post-slug');
			if (newSlugInput) {
				newSlugInput.value = slug;
			}
		}

		/**
		 * Show status message in the dropzone.
		 */
		function showStatus(message, isError) {
			// Remove any existing status.
			var existing = dropzone.querySelector('.leaderspath-context-dropzone__status');
			if (existing) {
				existing.remove();
			}

			var status = document.createElement('p');
			status.className = 'leaderspath-context-dropzone__status';
			status.className += isError
				? ' leaderspath-context-dropzone__status--error'
				: ' leaderspath-context-dropzone__status--success';
			status.textContent = message;
			dropzone.appendChild(status);
		}

		/**
		 * Process the selected/dropped file.
		 */
		function processFile(file) {
			// Basic size limit: 1 MB.
			if (file.size > 1024 * 1024) {
				showStatus('File too large. Maximum size is 1 MB.', true);
				return;
			}

			var reader = new FileReader();
			reader.onload = function(e) {
				var content = e.target.result;

				if (setEditorContent(content)) {
					setSlugFromFilename(file.name);
					showStatus('Imported: ' + file.name, false);
				} else {
					showStatus('Could not find the editor. Try switching to Text mode.', true);
				}
			};
			reader.onerror = function() {
				showStatus('Error reading file.', true);
			};
			reader.readAsText(file);
		}

		// File input change handler.
		fileInput.addEventListener('change', function(e) {
			if (e.target.files && e.target.files[0]) {
				processFile(e.target.files[0]);
				// Reset input so the same file can be selected again.
				e.target.value = '';
			}
		});

		// Drag and drop handlers.
		dropzone.addEventListener('dragover', function(e) {
			e.preventDefault();
			e.stopPropagation();
			dropzone.classList.add('leaderspath-context-dropzone--active');
		});

		dropzone.addEventListener('dragleave', function(e) {
			e.preventDefault();
			e.stopPropagation();
			dropzone.classList.remove('leaderspath-context-dropzone--active');
		});

		dropzone.addEventListener('drop', function(e) {
			e.preventDefault();
			e.stopPropagation();
			dropzone.classList.remove('leaderspath-context-dropzone--active');

			if (e.dataTransfer.files && e.dataTransfer.files[0]) {
				processFile(e.dataTransfer.files[0]);
			}
		});
	});
})();
JS;

		wp_register_script( 'leaderspath-context-uploader', '', [], LEADERSPATH_VERSION, true );
		wp_enqueue_script( 'leaderspath-context-uploader' );
		wp_add_inline_script( 'leaderspath-context-uploader', $script );
	}
}
