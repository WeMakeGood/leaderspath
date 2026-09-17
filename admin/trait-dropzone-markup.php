<?php
/**
 * Shared dropzone CSS for admin metaboxes.
 *
 * Backs Context_Uploader and Cohort_Context — same visual language (dashed
 * border, active-drag state, success/error status line) so the two
 * unrelated-looking metaboxes read as one interaction pattern. Kept as
 * inline `<style>` output (not an enqueued stylesheet) to match how both
 * classes already rendered their CSS before unification — introducing a
 * new enqueued asset for ~30 lines of CSS wasn't worth the extra HTTP
 * request/cache-busting surface.
 *
 * @package LeadersPath
 * @since   0.8.0
 */

declare(strict_types=1);

namespace LeadersPath\Admin;

/**
 * Shared dropzone markup/CSS, used by any metabox rendering a
 * LeadersPathDropzone-backed drag-and-drop zone.
 *
 * @since 0.8.0
 */
trait Dropzone_Markup {

	/**
	 * Print the shared dropzone CSS.
	 *
	 * Safe to call from multiple metaboxes on the same screen — the class
	 * names are identical by design, so a duplicate `<style>` block is
	 * harmless (last one wins, same rules either way).
	 *
	 * @since 0.8.0
	 */
	private function render_dropzone_styles(): void {
		?>
		<style>
			.leaderspath-dropzone {
				border: 2px dashed #c3c4c7;
				border-radius: 4px;
				padding: 16px;
				text-align: center;
				transition: border-color 0.2s, background-color 0.2s;
			}
			.leaderspath-dropzone--active {
				border-color: #2271b1;
				background-color: #f0f6fc;
			}
			.leaderspath-dropzone__label {
				margin: 0 0 8px;
				color: #50575e;
			}
			.leaderspath-dropzone__hint {
				margin: 8px 0 0;
				font-size: 12px;
				color: #787c82;
			}
			.leaderspath-dropzone__status {
				margin: 8px 0 0;
				font-size: 12px;
				font-weight: 600;
			}
			.leaderspath-dropzone__status--success {
				color: #00a32a;
			}
			.leaderspath-dropzone__status--error {
				color: #d63638;
			}
		</style>
		<?php
	}
}
