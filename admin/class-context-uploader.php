<?php
/**
 * Context File uploader for the admin editor.
 *
 * Adds a drag-and-drop / file-select upload zone to the Context File edit
 * screen. When a text file is uploaded, its content is injected into the
 * WordPress editor (post_content) and the post slug is set from the filename
 * if not already set.
 *
 * Drag/drop wiring comes from the shared `LeadersPathDropzone` JS module
 * (assets/js/admin/dropzone.js) and `Dropzone_Markup` trait — see
 * docs/TASKS.md Phase 15, "drag-and-drop unification" — so this and
 * Cohort_Context share one interaction pattern instead of two hand-copied
 * near-duplicates.
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

	use Dropzone_Markup;

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
		<div id="leaderspath-context-dropzone" class="leaderspath-dropzone">
			<p class="leaderspath-dropzone__label">
				<?php esc_html_e( 'Drop a file here or', 'leaderspath' ); ?>
			</p>
			<label class="button leaderspath-dropzone__button">
				<?php esc_html_e( 'Select File', 'leaderspath' ); ?>
				<input
					type="file"
					id="leaderspath-context-file-input"
					accept=".txt,.md,.markdown,.json,.xml,.csv,.yaml,.yml,.html,.htm,.css,.js,.ts,.py,.php,.rb,.sh,.sql,.log,.cfg,.conf,.ini,.env,.toml"
					style="display: none;"
				/>
			</label>
			<p class="leaderspath-dropzone__hint">
				<?php esc_html_e( 'Text files only. Content will replace the editor.', 'leaderspath' ); ?>
			</p>
		</div>
		<?php
		$this->render_dropzone_styles();
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

		wp_enqueue_script(
			'leaderspath-dropzone',
			LEADERSPATH_URL . 'assets/js/admin/dropzone.js',
			[],
			LEADERSPATH_VERSION,
			true
		);

		$script = <<<'JS'
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		var dropzone = document.getElementById('leaderspath-context-dropzone');
		var fileInput = document.getElementById('leaderspath-context-file-input');

		if (!dropzone || !fileInput || !window.LeadersPathDropzone) {
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
			var currentSlug = slugInput ? slugInput.value : '';

			// On new posts, the slug field is empty.
			// On existing posts, it has a value — don't overwrite.
			if (currentSlug) {
				return;
			}

			var slug = filename
				.replace(/\.[^/.]+$/, '')
				.toLowerCase()
				.replace(/[^a-z0-9-]/g, '-')
				.replace(/-+/g, '-')
				.replace(/^-|-$/g, '');

			if (!slug) {
				return;
			}

			var titleInput = document.getElementById('title');
			if (titleInput && !titleInput.value) {
				var title = filename.replace(/\.[^/.]+$/, '');
				titleInput.value = title;
				var titleEvent = new Event('input', { bubbles: true });
				titleInput.dispatchEvent(titleEvent);
			}

			if (slugInput) {
				slugInput.value = slug;
			}

			var newSlugInput = document.getElementById('new-post-slug');
			if (newSlugInput) {
				newSlugInput.value = slug;
			}
		}

		window.LeadersPathDropzone.init({
			dropzone: dropzone,
			fileInput: fileInput,
			onFileRead: function(content, file, showStatus) {
				if (setEditorContent(content)) {
					setSlugFromFilename(file.name);
					showStatus('Imported: ' + file.name, false);
				} else {
					showStatus('Could not find the editor. Try switching to Text mode.', true);
				}
			},
		});
	});
})();
JS;

		wp_add_inline_script( 'leaderspath-dropzone', $script );
	}
}
