<?php
/**
 * Markdown file drop support for TinyMCE editors.
 *
 * Enqueues marked.js and the md-drop handler script on admin post
 * editor screens, enabling drag-and-drop import of .md files into
 * any TinyMCE editor (standard post editor and ACF WYSIWYG fields).
 *
 * @package LeadersPath
 * @since   0.8.0
 */

declare(strict_types=1);

namespace LeadersPath\Admin;

class MD_Drop {

	/**
	 * Initialize the class.
	 *
	 * @since 0.8.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Enqueue the Markdown drop scripts on post editor screens.
	 *
	 * Only loads on post.php and post-new.php for LeadersPath CPTs
	 * where TinyMCE editors are present.
	 *
	 * @since 0.8.0
	 *
	 * @param string $hook_suffix Admin page hook suffix.
	 */
	public function enqueue_scripts( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		// Only load for LeadersPath post types.
		$our_types = [
			'leaderspath_activity',
			'leaderspath_lesson',
			'leaderspath_course',
			'leaderspath_context',
			'leaderspath_skill',
		];

		if ( ! in_array( $screen->post_type, $our_types, true ) ) {
			return;
		}

		wp_enqueue_script(
			'marked',
			LEADERSPATH_URL . 'assets/js/vendor/marked.umd.js',
			[],
			'17.0.2',
			true
		);

		wp_enqueue_script(
			'leaderspath-md-drop',
			LEADERSPATH_URL . 'assets/js/admin/md-drop.js',
			[ 'marked' ],
			LEADERSPATH_VERSION,
			true
		);
	}
}
