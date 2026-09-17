<?php
/**
 * Cohort-scoped Context File metabox for the admin editor.
 *
 * A facilitator adding organization-specific context previously had to leave
 * the Cohort edit screen, create or find a Context File, and pick the right
 * cohort from a `post_object` dropdown with no disambiguation if two cohorts
 * share a similar name (see docs/TASKS.md Phase 15, "context_cohort field
 * placement corrected"). This mirrors Cohort_Roster's pattern: the
 * relationship is managed from the Cohort screen, where the cohort ID is
 * already known and unambiguous, rather than from the far side of it.
 *
 * `context_cohort` itself (includes/class-acf-fields.php) is unchanged and
 * stays editable from the Context File's own edit screen too — this is an
 * additional, easier path onto the same data, not a replacement for it.
 *
 * Drag/drop wiring comes from the shared `LeadersPathDropzone` JS module
 * (assets/js/admin/dropzone.js) and `Dropzone_Markup` trait, the same ones
 * Context_Uploader uses — see docs/TASKS.md Phase 15, "drag-and-drop
 * unification."
 *
 * @package LeadersPath
 * @since   0.8.0
 */

declare(strict_types=1);

namespace LeadersPath\Admin;

/**
 * Manages the cohort-scoped context files metabox.
 *
 * @since 0.8.0
 */
class Cohort_Context {

	use Dropzone_Markup;

	/**
	 * Nonce action for the dropzone upload.
	 *
	 * @var string
	 */
	private const NONCE_ACTION = 'leaderspath_cohort_context';

	/**
	 * Initialize the class.
	 *
	 * @since 0.8.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes_leaderspath_cohort', [ $this, 'add_metabox' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_script' ] );
		add_action( 'load-post.php', [ $this, 'handle_form_submission' ] );
	}

	/**
	 * Register the context files metabox.
	 *
	 * @since 0.8.0
	 */
	public function add_metabox(): void {
		add_meta_box(
			'leaderspath_cohort_context',
			__( 'Context Files', 'leaderspath' ),
			[ $this, 'render_metabox' ],
			'leaderspath_cohort',
			'normal',
			'high'
		);
	}

	/**
	 * Render the context files metabox.
	 *
	 * @since 0.8.0
	 *
	 * @param \WP_Post $post Current post.
	 */
	public function render_metabox( \WP_Post $post ): void {
		if ( ! \LeadersPath\Admin\Cohort_Roster::can_manage_roster( $post->ID ) ) {
			echo '<p>' . esc_html__( 'You do not have permission to manage this cohort\'s context files.', 'leaderspath' ) . '</p>';
			return;
		}

		wp_nonce_field( self::NONCE_ACTION, 'leaderspath_cohort_context_nonce' );

		$this->render_notices();

		$context_files = $this->get_cohort_context_files( $post->ID );
		?>
		<table class="widefat striped leaderspath-cohort-context__table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'leaderspath' ); ?></th>
					<th><?php esc_html_e( 'Added', 'leaderspath' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $context_files ) ) : ?>
					<tr>
						<td colspan="3"><?php esc_html_e( 'No context files attached to this cohort yet.', 'leaderspath' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $context_files as $context_post ) : ?>
						<?php $this->render_context_row( $context_post ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<div id="leaderspath-cohort-context-dropzone" class="leaderspath-dropzone" style="margin-top: 12px;">
			<p class="leaderspath-dropzone__label">
				<?php esc_html_e( 'Drop a text or markdown file here to add it as context for this cohort, or', 'leaderspath' ); ?>
			</p>
			<label class="button leaderspath-dropzone__button">
				<?php esc_html_e( 'Select File', 'leaderspath' ); ?>
				<input
					type="file"
					id="leaderspath-cohort-context-file-input"
					accept=".txt,.md,.markdown"
					style="display: none;"
				/>
			</label>
			<input type="hidden" id="leaderspath-cohort-context-content" name="leaderspath_cohort_context_content" />
			<input type="hidden" id="leaderspath-cohort-context-title" name="leaderspath_cohort_context_title" />
			<p class="leaderspath-dropzone__hint">
				<?php esc_html_e( 'Text or markdown files only, up to 1 MB. Creates a new Context File scoped to this cohort — nothing here is shared with other cohorts.', 'leaderspath' ); ?>
			</p>
		</div>
		<?php
		submit_button( __( 'Add Context File', 'leaderspath' ), 'secondary', 'leaderspath_cohort_context_submit', false, [ 'id' => 'leaderspath-cohort-context-submit', 'disabled' => 'disabled', 'style' => 'margin-top: 12px;' ] );
		$this->render_dropzone_styles();
	}

	/**
	 * Render one context-file row.
	 *
	 * @since 0.8.0
	 *
	 * @param \WP_Post $context_post Context File post.
	 */
	private function render_context_row( \WP_Post $context_post ): void {
		?>
		<tr>
			<td>
				<a href="<?php echo esc_url( get_edit_post_link( $context_post->ID ) ?? '' ); ?>">
					<?php echo esc_html( $context_post->post_title ); ?>
				</a>
			</td>
			<td><?php echo esc_html( get_the_date( '', $context_post ) ); ?></td>
			<td>
				<button
					type="submit"
					name="leaderspath_detach_context"
					value="<?php echo esc_attr( (string) $context_post->ID ); ?>"
					class="button-link-delete"
					onclick="return confirm('<?php echo esc_js( __( 'Detach this context file from the cohort? The file itself is not deleted.', 'leaderspath' ) ); ?>');"
				>
					<?php esc_html_e( 'Detach', 'leaderspath' ); ?>
				</button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Get context files scoped to a cohort.
	 *
	 * @since 0.8.0
	 *
	 * @param int $cohort_id Cohort post ID.
	 * @return \WP_Post[]
	 */
	private function get_cohort_context_files( int $cohort_id ): array {
		$query = new \WP_Query(
			[
				'post_type'      => 'leaderspath_context',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'   => 'context_cohort',
						'value' => $cohort_id,
					],
				],
			]
		);

		return $query->posts;
	}

	/**
	 * Render admin notices for the last add/detach action.
	 *
	 * Same one-time-transient, redirect-then-notice pattern Cohort_Roster
	 * uses for its own form.
	 *
	 * @since 0.8.0
	 */
	private function render_notices(): void {
		$notice_key = 'leaderspath_cohort_context_notice_' . get_current_user_id();
		$notice     = get_transient( $notice_key );

		if ( ! $notice ) {
			return;
		}

		delete_transient( $notice_key );

		printf(
			'<div class="notice notice-%s inline"><p>%s</p></div>',
			'error' === $notice['type'] ? 'error' : 'success',
			esc_html( $notice['message'] )
		);
	}

	/**
	 * Enqueue the dropzone script on the cohort edit screen.
	 *
	 * @since 0.8.0
	 *
	 * @param string $hook_suffix Admin page hook suffix.
	 */
	public function enqueue_script( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$screen = \get_current_screen();
		if ( ! $screen || 'leaderspath_cohort' !== $screen->post_type ) {
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
		var dropzone     = document.getElementById('leaderspath-cohort-context-dropzone');
		var fileInput    = document.getElementById('leaderspath-cohort-context-file-input');
		var contentField = document.getElementById('leaderspath-cohort-context-content');
		var titleField   = document.getElementById('leaderspath-cohort-context-title');
		var submitBtn    = document.getElementById('leaderspath-cohort-context-submit');

		if (!dropzone || !fileInput || !contentField || !titleField || !submitBtn || !window.LeadersPathDropzone) {
			return;
		}

		window.LeadersPathDropzone.init({
			dropzone: dropzone,
			fileInput: fileInput,
			onFileRead: function(content, file, showStatus) {
				contentField.value = content;
				titleField.value = file.name.replace(/\.[^/.]+$/, '');
				submitBtn.disabled = false;
				showStatus('Ready to add: ' + file.name, false);
			},
		});
	});
})();
JS;

		wp_add_inline_script( 'leaderspath-dropzone', $script );
	}

	/**
	 * Handle the metabox's add/detach submission.
	 *
	 * Hooked to `load-post.php` (fires before output) rather than
	 * `save_post`, matching Cohort_Roster — this is a distinct action with
	 * its own nonce, not ACF/`$_POST['acf']` data being saved.
	 *
	 * @since 0.8.0
	 */
	public function handle_form_submission(): void {
		if ( empty( $_POST['leaderspath_cohort_context_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['leaderspath_cohort_context_nonce'] ) ), self::NONCE_ACTION )
		) {
			return;
		}

		$post_id = isset( $_POST['post_ID'] ) ? (int) $_POST['post_ID'] : 0;

		if ( ! $post_id || 'leaderspath_cohort' !== get_post_type( $post_id ) ) {
			return;
		}

		if ( ! \LeadersPath\Admin\Cohort_Roster::can_manage_roster( $post_id ) ) {
			return;
		}

		$notice_key = 'leaderspath_cohort_context_notice_' . get_current_user_id();

		if ( ! empty( $_POST['leaderspath_cohort_context_submit'] ) && ! empty( $_POST['leaderspath_cohort_context_content'] ) ) {
			$title   = sanitize_text_field( wp_unslash( $_POST['leaderspath_cohort_context_title'] ?? '' ) );
			$content = wp_unslash( $_POST['leaderspath_cohort_context_content'] );

			if ( '' === trim( $title ) ) {
				$title = __( 'Untitled context file', 'leaderspath' );
			}

			$context_id = wp_insert_post(
				[
					'post_type'    => 'leaderspath_context',
					'post_title'   => $title,
					'post_content' => $content,
					'post_status'  => 'publish',
				],
				true
			);

			if ( is_wp_error( $context_id ) ) {
				set_transient(
					$notice_key,
					[ 'type' => 'error', 'message' => $context_id->get_error_message() ],
					60
				);
			} else {
				update_field( 'context_cohort', $post_id, $context_id );

				set_transient(
					$notice_key,
					[
						'type'    => 'success',
						/* translators: %s: context file title */
						'message' => sprintf( __( 'Added context file: %s', 'leaderspath' ), $title ),
					],
					60
				);
			}
		} elseif ( ! empty( $_POST['leaderspath_detach_context'] ) ) {
			$context_id = (int) $_POST['leaderspath_detach_context'];

			if ( 'leaderspath_context' === get_post_type( $context_id )
				&& (int) get_field( 'context_cohort', $context_id ) === $post_id
			) {
				update_field( 'context_cohort', '', $context_id );

				set_transient(
					$notice_key,
					[ 'type' => 'success', 'message' => __( 'Context file detached from this cohort.', 'leaderspath' ) ],
					60
				);
			}
		} else {
			return;
		}

		wp_safe_redirect( get_edit_post_link( $post_id, 'raw' ) );
		exit;
	}
}
