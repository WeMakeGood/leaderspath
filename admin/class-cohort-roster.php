<?php
/**
 * Cohort roster metabox for the admin editor.
 *
 * The admin-side surface for Enrollment::invite_to_cohort() / remove_from_cohort()
 * (see docs/TASKS.md Phase 14, "Roster invites"). Deliberately scoped to
 * wp-admin for this pass — the WooCommerce My Account self-service screen
 * for purchasers is a separate, later build using the same Enrollment
 * methods, not duplicated logic.
 *
 * @package LeadersPath
 * @since   0.7.0
 */

declare(strict_types=1);

namespace LeadersPath\Admin;

use LeadersPath\Includes\Enrollment;

/**
 * Manages the cohort roster metabox.
 *
 * @since 0.7.0
 */
class Cohort_Roster {

	/**
	 * Nonce action for the roster form.
	 *
	 * @var string
	 */
	private const NONCE_ACTION = 'leaderspath_cohort_roster';

	/**
	 * Initialize the class.
	 *
	 * @since 0.7.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes_leaderspath_cohort', [ $this, 'add_metabox' ] );
		add_action( 'load-post.php', [ $this, 'handle_form_submission' ] );
	}

	/**
	 * Whether the current user may manage a given cohort's roster.
	 *
	 * Purchaser (`cohort_owner`) and the assigned facilitator
	 * (`cohort_facilitator`) can manage the roster — not admin-only. Admins
	 * and editors (anyone who can edit others' posts) can manage any
	 * roster, same bypass pattern used throughout `Enrollment`.
	 *
	 * @since 0.7.0
	 *
	 * @param int $cohort_id leaderspath_cohort post ID.
	 * @param int $user_id   WordPress user ID. Defaults to the current user.
	 * @return bool
	 */
	public static function can_manage_roster( int $cohort_id, int $user_id = 0 ): bool {
		$user_id = $user_id ?: get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'edit_others_posts' ) ) {
			return true;
		}

		$owner       = (int) get_field( 'cohort_owner', $cohort_id );
		$facilitator = (int) get_field( 'cohort_facilitator', $cohort_id );

		return $user_id === $owner || $user_id === $facilitator;
	}

	/**
	 * Register the roster metabox.
	 *
	 * Registered for every user who can view the edit screen; visibility of
	 * its *contents* (versus a "you can't manage this roster" notice) is
	 * decided in render_metabox() via can_manage_roster() — matches how
	 * WordPress itself handles metaboxes it can't fully hide per-capability.
	 *
	 * @since 0.7.0
	 */
	public function add_metabox(): void {
		add_meta_box(
			'leaderspath_cohort_roster',
			__( 'Roster', 'leaderspath' ),
			[ $this, 'render_metabox' ],
			'leaderspath_cohort',
			'normal',
			'high'
		);
	}

	/**
	 * Render the roster metabox.
	 *
	 * @since 0.7.0
	 *
	 * @param \WP_Post $post Current post.
	 */
	public function render_metabox( \WP_Post $post ): void {
		if ( ! self::can_manage_roster( $post->ID ) ) {
			echo '<p>' . esc_html__( 'You do not have permission to manage this cohort\'s roster.', 'leaderspath' ) . '</p>';
			return;
		}

		$seats     = (int) get_field( 'cohort_seats', $post->ID );
		$enrollees = Enrollment::get_cohort_enrollees( $post->ID );
		$owner_id  = (int) get_field( 'cohort_owner', $post->ID );

		wp_nonce_field( self::NONCE_ACTION, 'leaderspath_cohort_roster_nonce' );

		$this->render_notices();
		?>
		<p class="leaderspath-roster__seats">
			<strong><?php esc_html_e( 'Seats:', 'leaderspath' ); ?></strong>
			<?php
			if ( $seats > 0 ) {
				printf(
					/* translators: 1: filled seats, 2: total seats */
					esc_html__( '%1$d / %2$d filled', 'leaderspath' ),
					count( $enrollees ),
					$seats
				);
			} else {
				echo esc_html( (string) count( $enrollees ) );
			}
			?>
		</p>

		<table class="widefat striped leaderspath-roster__table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'leaderspath' ); ?></th>
					<th><?php esc_html_e( 'Email', 'leaderspath' ); ?></th>
					<th><?php esc_html_e( 'Role', 'leaderspath' ); ?></th>
					<th><?php esc_html_e( 'Joined', 'leaderspath' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $enrollees ) ) : ?>
					<tr>
						<td colspan="5"><?php esc_html_e( 'No one on the roster yet.', 'leaderspath' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $enrollees as $user_id ) : ?>
						<?php $this->render_roster_row( $post->ID, $user_id, $owner_id ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<?php if ( 0 === $seats || count( $enrollees ) < $seats ) : ?>
			<div class="leaderspath-roster__invite">
				<input
					type="email"
					name="leaderspath_invite_email"
					placeholder="<?php esc_attr_e( 'teammate@example.org', 'leaderspath' ); ?>"
					class="regular-text"
				/>
				<?php submit_button( __( 'Send Invite', 'leaderspath' ), 'secondary', 'leaderspath_invite_submit', false ); ?>
			</div>
		<?php else : ?>
			<p><em><?php esc_html_e( 'All seats are filled.', 'leaderspath' ); ?></em></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render one roster row.
	 *
	 * @since 0.7.0
	 *
	 * @param int $cohort_id Cohort post ID.
	 * @param int $user_id   Roster member's WP user ID.
	 * @param int $owner_id  The cohort's owner user ID, to label the row.
	 */
	private function render_roster_row( int $cohort_id, int $user_id, int $owner_id ): void {
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		// `session_tokens` only reflects an *active* login session, not
		// lifetime login history — it's cleared once every session for that
		// user expires or is destroyed, so someone who logged in once and
		// let their session lapse will read the same as never having logged
		// in at all. Good enough for "does this look like an untouched
		// invite," not a durable login-history record.
		$has_logged_in = '' !== get_user_meta( $user_id, 'session_tokens', true );
		$joined        = get_user_meta( $user_id, "leaderspath_enrollment_{$cohort_id}_date", true );
		?>
		<tr>
			<td><?php echo esc_html( $user->display_name ); ?></td>
			<td><?php echo esc_html( $user->user_email ); ?></td>
			<td>
				<?php
				if ( $user_id === $owner_id ) {
					esc_html_e( 'Owner', 'leaderspath' );
				} elseif ( ! $has_logged_in ) {
					echo '<span class="leaderspath-roster__pending">' . esc_html__( 'Invited — awaiting first login', 'leaderspath' ) . '</span>';
				} else {
					esc_html_e( 'Participant', 'leaderspath' );
				}
				?>
			</td>
			<td>
				<?php
				echo $joined
					? esc_html( date_i18n( get_option( 'date_format' ), strtotime( (string) $joined ) ) )
					: '—';
				?>
			</td>
			<td>
				<?php if ( $user_id !== $owner_id ) : ?>
					<button
						type="submit"
						name="leaderspath_remove_user"
						value="<?php echo esc_attr( (string) $user_id ); ?>"
						class="button-link-delete"
						onclick="return confirm('<?php echo esc_js( __( 'Remove this person from the cohort?', 'leaderspath' ) ); ?>');"
					>
						<?php esc_html_e( 'Remove', 'leaderspath' ); ?>
					</button>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render admin notices for the last invite/removal action, stashed in a
	 * one-time transient (the standard WP redirect-then-notice pattern, so
	 * the notice survives the redirect after form submission).
	 *
	 * @since 0.7.0
	 */
	private function render_notices(): void {
		$notice_key = 'leaderspath_roster_notice_' . get_current_user_id();
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
	 * Handle the roster form's invite/remove submission.
	 *
	 * Hooked to `load-post.php` (fires before any output, so redirecting is
	 * safe) rather than `save_post`, since this isn't post-data being saved
	 * through ACF/`$_POST['acf']` — it's a distinct action with its own
	 * nonce, same separation `class-admin-columns.php`'s quick-edit save
	 * uses for its own custom fields.
	 *
	 * @since 0.7.0
	 */
	public function handle_form_submission(): void {
		if ( empty( $_POST['leaderspath_cohort_roster_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['leaderspath_cohort_roster_nonce'] ) ), self::NONCE_ACTION )
		) {
			return;
		}

		$post_id = isset( $_POST['post_ID'] ) ? (int) $_POST['post_ID'] : 0;

		if ( ! $post_id || 'leaderspath_cohort' !== get_post_type( $post_id ) ) {
			return;
		}

		if ( ! self::can_manage_roster( $post_id ) ) {
			return;
		}

		$notice_key = 'leaderspath_roster_notice_' . get_current_user_id();

		if ( ! empty( $_POST['leaderspath_invite_submit'] ) && ! empty( $_POST['leaderspath_invite_email'] ) ) {
			$email  = sanitize_email( wp_unslash( $_POST['leaderspath_invite_email'] ) );
			$result = Enrollment::invite_to_cohort( $post_id, $email );

			set_transient(
				$notice_key,
				is_wp_error( $result )
					? [ 'type' => 'error', 'message' => $result->get_error_message() ]
					: [
						'type'    => 'success',
						/* translators: %s: invited email address */
						'message' => sprintf( __( 'Invited %s.', 'leaderspath' ), $email ),
					],
				60
			);
		} elseif ( ! empty( $_POST['leaderspath_remove_user'] ) ) {
			$remove_id = (int) $_POST['leaderspath_remove_user'];
			Enrollment::remove_from_cohort( $post_id, $remove_id );

			set_transient(
				$notice_key,
				[ 'type' => 'success', 'message' => __( 'Removed from the roster.', 'leaderspath' ) ],
				60
			);
		} else {
			return;
		}

		wp_safe_redirect( get_edit_post_link( $post_id, 'raw' ) );
		exit;
	}
}
