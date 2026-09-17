<?php
/**
 * Cohort enrollment and access-chain resolution.
 *
 * Commerce-agnostic by design: everything here operates on user meta and
 * `leaderspath_cohort`/CPT/ACF data, never on `WC_Order`/`WC_Product`. This
 * is the split described in docs/TASKS.md Phase 14 ("Architectural
 * requirement... a first-class, multi-caller operation") — WooCommerce is
 * one caller of `create_cohort()`, not the thing this class is coupled to.
 * A different commerce backend, WS Form, WP-CLI, or an MCP tool can all
 * call the same methods here without this file ever knowing WooCommerce
 * exists.
 *
 * Split out of class-woocommerce.php (0.7.0), which now holds only the
 * genuinely commerce-specific pile (product checkbox, order hooks, pricing)
 * and calls into this class rather than the reverse.
 *
 * @package LeadersPath
 * @since   0.7.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

/**
 * Enrollment, roster, and cohort-instance creation.
 *
 * @since 0.7.0
 */
class Enrollment {

	/**
	 * User meta key for enrollment tracking.
	 *
	 * Stores an array of leaderspath_cohort post IDs the user is enrolled in.
	 *
	 * @var string
	 */
	public const ENROLLMENT_META_KEY = 'leaderspath_enrollments';

	// -------------------------------------------------------------------------
	// Cohort Creation (the multi-caller operation)
	// -------------------------------------------------------------------------

	/**
	 * Create a cohort instance.
	 *
	 * The single, canonical cohort-creation operation — every caller (a WC
	 * order hook, a REST endpoint for WS Form or any HTTP client, a WP-CLI
	 * command, a future MCP tool) runs through this exact method, so there
	 * is no way for "a cohort created via checkout" and "a cohort created by
	 * an admin running a CLI command" to drift apart. See docs/TASKS.md
	 * Phase 14, "cohort creation as a first-class, multi-caller operation."
	 *
	 * Deliberately takes plain values, not a `WC_Order`/`WC_Product` — no
	 * commerce-specific type crosses into this method. A caller translates
	 * its own domain object into these args first.
	 *
	 * @since 0.7.0
	 *
	 * @param array $args {
	 *     Cohort creation arguments.
	 *
	 *     @type int    $offering_id           Product (or other catalog offering) ID the
	 *                                          cohort was purchased from. Used to seed
	 *                                          `cohort_courses` (copying the offering's own
	 *                                          `cohort_courses` field) when `$course_id` isn't
	 *                                          given, and (for single-org) as the default
	 *                                          owner-resolution context. Superseded by
	 *                                          `$course_id` for callers (e.g. WS Form) that
	 *                                          submit a single course choice directly rather
	 *                                          than bundling courses on a catalog product.
	 *     @type int    $course_id             Optional. A single `leaderspath_course` ID to
	 *                                          seed `cohort_courses` with directly — the real
	 *                                          path for a form that submits one course choice
	 *                                          per purchase. Takes precedence over
	 *                                          `$offering_id`'s copy-from-product behavior.
	 *     @type int    $owner_user_id         WP user ID with roster-management authority.
	 *                                          For single-org: the purchaser. For
	 *                                          mixed-org: the facilitator (see `$is_mixed`).
	 *     @type string $org_name              Organization name, or a group description for
	 *                                          a mixed cohort. Optional.
	 *     @type int    $seats                 Total seats, from the purchased
	 *                                          product/variation.
	 *     @type string $requested_start_date  Optional. `Y-m-d`. Seeds `cohort_start_date`;
	 *                                          never written back to afterward.
	 *     @type bool   $is_mixed              Whether this is a mixed-organization cohort.
	 *                                          Determines the owner default when
	 *                                          `$owner_user_id` isn't explicitly the
	 *                                          purchaser — see class docblock in
	 *                                          docs/TASKS.md Phase 14, `cohort_owner`. Note:
	 *                                          `create_cohort()` itself is only ever called
	 *                                          from the organization-purchase path — a mixed
	 *                                          cohort is a standing post that individual
	 *                                          buyers join via the (not yet built)
	 *                                          `add_to_cohort()`, not created per-purchase.
	 *                                          This arg exists for that eventual caller and
	 *                                          for WP-CLI ad hoc use; every real caller today
	 *                                          passes `false`.
	 *     @type int    $facilitator_user_id   Optional. WP user ID of the assigned
	 *                                          facilitator. For a mixed cohort with no
	 *                                          `owner_user_id` given, this also becomes
	 *                                          the owner.
	 *     @type int    $source_record_id      Optional. Traceability only — never read by
	 *                                          access-control or roster-management logic.
	 *                                          The originating order/submission ID, whatever
	 *                                          the caller's backend.
	 *     @type string $source_url            Optional. A direct admin link to the
	 *                                          originating record, alongside the ID above.
	 *     @type string $source                Caller identifier for traceability/debugging
	 *                                          (e.g. 'woocommerce', 'ws_form', 'wp_cli',
	 *                                          'mcp'). Not stored on the post; logged via
	 *                                          the `leaderspath_cohort_created` action.
	 * }
	 * @return int|\WP_Error Cohort post ID, or WP_Error on failure.
	 */
	public static function create_cohort( array $args ) {
		$offering_id   = isset( $args['offering_id'] ) ? (int) $args['offering_id'] : 0;
		$course_id     = isset( $args['course_id'] ) ? (int) $args['course_id'] : 0;
		$owner_id      = isset( $args['owner_user_id'] ) ? (int) $args['owner_user_id'] : 0;
		$facilitator   = isset( $args['facilitator_user_id'] ) ? (int) $args['facilitator_user_id'] : 0;
		$is_mixed      = ! empty( $args['is_mixed'] );
		$org_name      = isset( $args['org_name'] ) ? sanitize_text_field( (string) $args['org_name'] ) : '';
		$seats         = isset( $args['seats'] ) ? max( 1, (int) $args['seats'] ) : 1;
		$start_date    = isset( $args['requested_start_date'] ) ? sanitize_text_field( (string) $args['requested_start_date'] ) : '';
		$source_record = isset( $args['source_record_id'] ) ? (int) $args['source_record_id'] : 0;
		$source_url    = isset( $args['source_url'] ) ? esc_url_raw( (string) $args['source_url'] ) : '';
		$source        = isset( $args['source'] ) ? sanitize_text_field( (string) $args['source'] ) : 'unknown';

		// Owner resolution: mixed-org defaults to the facilitator (no
		// participant has standing over anyone else's enrollment — each
		// bought only their own seat); single-org defaults to whichever
		// user was actually passed as the owner (the purchaser, from the
		// caller's perspective). See docs/TASKS.md Phase 14, `cohort_owner`.
		if ( ! $owner_id && $is_mixed && $facilitator ) {
			$owner_id = $facilitator;
		}

		if ( ! $owner_id ) {
			return new \WP_Error(
				'leaderspath_cohort_no_owner',
				__( 'A cohort must have an owner (the purchaser, or the facilitator for a mixed-organization cohort).', 'leaderspath' )
			);
		}

		$title = $org_name
			? sprintf(
				/* translators: %s: organization name */
				__( '%s — Cohort', 'leaderspath' ),
				$org_name
			)
			: __( 'Untitled Cohort', 'leaderspath' );

		$post_id = wp_insert_post(
			[
				'post_type'   => 'leaderspath_cohort',
				'post_title'  => $title,
				'post_status' => 'publish',
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_field( 'cohort_org_name', $org_name, $post_id );
		update_field( 'cohort_owner', $owner_id, $post_id );
		update_field( 'cohort_seats', $seats, $post_id );
		update_field( 'cohort_payment_status', 'pending_payment', $post_id );
		update_field( 'cohort_access_closed', false, $post_id );

		// create_cohort() only ever runs on the organization-purchase path
		// (a mixed cohort is a standing post individual buyers join via
		// add_to_cohort(), not created per-purchase) — so the term is
		// always 'organization' here, not derived from $is_mixed. See
		// docs/TASKS.md Phase 14.
		wp_set_object_terms( $post_id, 'organization', 'leaderspath_cohort_type' );

		if ( $facilitator ) {
			update_field( 'cohort_facilitator', $facilitator, $post_id );
		}

		if ( $source_record ) {
			update_field( 'cohort_source_record_id', $source_record, $post_id );
		}

		if ( $source_url ) {
			// Plain post meta, not an ACF field — the Cohort edit screen
			// renders this as a button (ACF_Fields::render_cohort_source_link_button())
			// rather than exposing the raw URL as a field value.
			update_post_meta( $post_id, 'cohort_source_url', $source_url );
		}

		// A directly-submitted course choice (the real path for a form that
		// submits one course per purchase) takes precedence over copying
		// from a catalog product's own bundled list.
		if ( $course_id && 'leaderspath_course' === get_post_type( $course_id ) ) {
			update_field( 'cohort_courses', [ $course_id ], $post_id );
		} elseif ( $offering_id ) {
			// Courses copied from the offering at creation — not a discretionary
			// "assignment" step, the product already fixes what a package
			// bundles. Stays independently editable on the cohort afterward.
			$courses = get_field( 'cohort_courses', $offering_id );
			if ( is_array( $courses ) && ! empty( $courses ) ) {
				update_field( 'cohort_courses', $courses, $post_id );
			}
		}

		// Requested start date seeds the instance's real start date — an
		// intake input, never written back to from here afterward.
		if ( $start_date ) {
			update_field( 'cohort_start_date', $start_date, $post_id );
		}

		// Owner is always enrolled as a participant, filling one of the
		// purchased seats — existing correct behavior for the purchaser
		// case, extended uniformly to the mixed-cohort facilitator-as-owner
		// case too (a facilitator who is also the owner still doesn't fill
		// a seat there; see docs/TASKS.md — owner and participant diverge
		// only for mixed cohorts, and that divergence is handled by callers
		// passing $is_mixed rather than by this method assuming enrollment
		// always follows ownership).
		if ( ! $is_mixed ) {
			self::enroll_user( $owner_id, $post_id );
		}

		/**
		 * Fires after a cohort instance is created.
		 *
		 * @since 0.7.0
		 *
		 * @param int    $post_id The new leaderspath_cohort post ID.
		 * @param array  $args    The original arguments passed to create_cohort().
		 * @param string $source  Caller identifier (e.g. 'woocommerce', 'wp_cli').
		 */
		do_action( 'leaderspath_cohort_created', $post_id, $args, $source );

		return $post_id;
	}

	/**
	 * Update a cohort's payment status as its source order progresses.
	 *
	 * The ongoing half of the two-event boundary described in docs/TASKS.md
	 * Phase 14 — `create_cohort()` is the one-time creation event; this is
	 * the repeatable status-change event, called every time the underlying
	 * order/PO's state changes (e.g. `on-hold` → `completed`, or refunded).
	 * Deliberately does **not** touch `cohort_access_closed` or enrollment —
	 * see `docs/TASKS.md`, "Refund/cancellation must NOT auto-revoke access."
	 *
	 * @since 0.7.0
	 *
	 * @param int    $cohort_id Cohort post ID.
	 * @param string $status    New payment status ('pending_payment' or 'paid').
	 * @return bool True on success, false if the cohort doesn't exist.
	 */
	public static function set_cohort_payment_status( int $cohort_id, string $status ): bool {
		if ( 'leaderspath_cohort' !== get_post_type( $cohort_id ) ) {
			return false;
		}

		update_field( 'cohort_payment_status', $status, $cohort_id );

		/**
		 * Fires after a cohort's payment status changes.
		 *
		 * @since 0.7.0
		 *
		 * @param int    $cohort_id Cohort post ID.
		 * @param string $status    New payment status.
		 */
		do_action( 'leaderspath_cohort_payment_status_changed', $cohort_id, $status );

		return true;
	}

	// -------------------------------------------------------------------------
	// Enrollment System
	// -------------------------------------------------------------------------

	/**
	 * Enroll a user in a cohort.
	 *
	 * @since 0.4.0
	 *
	 * @param int $user_id   WordPress user ID.
	 * @param int $cohort_id leaderspath_cohort post ID.
	 * @return bool True on success.
	 */
	public static function enroll_user( int $user_id, int $cohort_id ): bool {
		$enrollments = get_user_meta( $user_id, self::ENROLLMENT_META_KEY, true );

		if ( ! is_array( $enrollments ) ) {
			$enrollments = [];
		}

		// Already enrolled.
		if ( in_array( $cohort_id, $enrollments, true ) ) {
			return true;
		}

		$enrollments[] = $cohort_id;
		update_user_meta( $user_id, self::ENROLLMENT_META_KEY, $enrollments );
		update_user_meta( $user_id, "leaderspath_enrollment_{$cohort_id}_date", current_time( 'mysql' ) );

		// Grant student role if user doesn't have it.
		$user = get_userdata( $user_id );
		if ( $user && ! in_array( 'leaderspath_student', $user->roles, true ) ) {
			$user->add_role( 'leaderspath_student' );
		}

		/**
		 * Fires after a user is enrolled in a cohort.
		 *
		 * @since 0.4.0
		 *
		 * @param int $user_id   WordPress user ID.
		 * @param int $cohort_id leaderspath_cohort post ID.
		 */
		do_action( 'leaderspath_user_enrolled', $user_id, $cohort_id );

		return true;
	}

	/**
	 * Unenroll a user from a cohort.
	 *
	 * @since 0.4.0
	 *
	 * @param int $user_id   WordPress user ID.
	 * @param int $cohort_id leaderspath_cohort post ID.
	 * @return bool True on success.
	 */
	public static function unenroll_user( int $user_id, int $cohort_id ): bool {
		$enrollments = get_user_meta( $user_id, self::ENROLLMENT_META_KEY, true );

		if ( ! is_array( $enrollments ) ) {
			return true; // Nothing to remove.
		}

		$key = array_search( $cohort_id, $enrollments, true );
		if ( false === $key ) {
			return true; // Not enrolled.
		}

		unset( $enrollments[ $key ] );
		$enrollments = array_values( $enrollments ); // Re-index.

		if ( empty( $enrollments ) ) {
			delete_user_meta( $user_id, self::ENROLLMENT_META_KEY );
		} else {
			update_user_meta( $user_id, self::ENROLLMENT_META_KEY, $enrollments );
		}

		delete_user_meta( $user_id, "leaderspath_enrollment_{$cohort_id}_date" );

		/**
		 * Fires after a user is unenrolled from a cohort.
		 *
		 * @since 0.4.0
		 *
		 * @param int $user_id   WordPress user ID.
		 * @param int $cohort_id leaderspath_cohort post ID.
		 */
		do_action( 'leaderspath_user_unenrolled', $user_id, $cohort_id );

		return true;
	}

	// -------------------------------------------------------------------------
	// Roster (seat invites)
	// -------------------------------------------------------------------------

	/**
	 * Invite someone to a cohort seat by email.
	 *
	 * No custom invite-token system — see docs/TASKS.md Phase 14, "Roster
	 * invites use stock WordPress account-creation + password-reset." An
	 * invite is a single, immediate action: ensure a WP account exists for
	 * the email (creating one now if not, role `leaderspath_student`),
	 * enroll it right away via `enroll_user()`, then hand off entirely to
	 * WordPress's own native password-reset flow to get them a working
	 * password. There is no separate "pending" state to track — the roster
	 * entry *is* the enrollment from the moment of invite; "pending" is just
	 * native WP user state (`user_registered`, no login yet).
	 *
	 * Only the *email content* is customized (via a request-scoped
	 * `wp_new_user_notification_email` filter, removed immediately after
	 * sending — this must never become a permanent site-wide override of
	 * every new-user email), not the underlying link/token mechanism.
	 *
	 * Callers (the admin roster metabox, and later the WC My Account
	 * self-service screen) are responsible for their own authorization
	 * check — this method does not itself verify the caller may manage this
	 * cohort's roster. See Admin_Cohort_Roster::can_manage_roster().
	 *
	 * @since 0.7.0
	 *
	 * @param int    $cohort_id leaderspath_cohort post ID.
	 * @param string $email     Email address to invite.
	 * @return int|\WP_Error WP user ID on success, or WP_Error (seat cap
	 *                        reached, invalid email, account creation failed,
	 *                        or the cohort doesn't exist).
	 */
	public static function invite_to_cohort( int $cohort_id, string $email ) {
		if ( 'leaderspath_cohort' !== get_post_type( $cohort_id ) ) {
			return new \WP_Error( 'leaderspath_invalid_cohort', __( 'That cohort does not exist.', 'leaderspath' ) );
		}

		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'leaderspath_invalid_email', __( 'Enter a valid email address.', 'leaderspath' ) );
		}

		$seats     = (int) get_field( 'cohort_seats', $cohort_id );
		$enrollees = self::get_cohort_enrollees( $cohort_id );

		$existing_user = get_user_by( 'email', $email );

		// Already on the roster — nothing to do, not an error (idempotent,
		// matching enroll_user()'s own "already enrolled" behavior).
		if ( $existing_user && in_array( $existing_user->ID, $enrollees, true ) ) {
			return $existing_user->ID;
		}

		if ( $seats > 0 && count( $enrollees ) >= $seats ) {
			return new \WP_Error(
				'leaderspath_cohort_full',
				sprintf(
					/* translators: %d: seat count */
					__( 'This cohort\'s %d seats are already filled.', 'leaderspath' ),
					$seats
				)
			);
		}

		$user_id = self::find_or_create_user_by_email( $email );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$is_new_account = ! $existing_user;

		self::enroll_user( $user_id, $cohort_id );

		// Scoped, temporary email customization — added and removed around
		// this one send only. A permanent filter here would rewrite every
		// new-user notification site-wide, not just cohort invites.
		$customize_email = function ( array $email_data ) use ( $cohort_id ) {
			return self::customize_invite_email( $email_data, $cohort_id );
		};

		add_filter( 'wp_new_user_notification_email', $customize_email );
		wp_new_user_notification( $user_id, null, 'user' );
		remove_filter( 'wp_new_user_notification_email', $customize_email );

		/**
		 * Fires after someone is invited to a cohort.
		 *
		 * @since 0.7.0
		 *
		 * @param int  $user_id        WordPress user ID (new or existing).
		 * @param int  $cohort_id      leaderspath_cohort post ID.
		 * @param bool $is_new_account Whether a new account was created for this invite.
		 */
		do_action( 'leaderspath_cohort_invite_sent', $user_id, $cohort_id, $is_new_account );

		return $user_id;
	}

	/**
	 * Remove someone from a cohort's roster.
	 *
	 * Thin wrapper over `unenroll_user()` — kept as its own method so the
	 * roster UI has a name that matches what it's doing ("remove this
	 * person from this cohort"), and so future roster-specific bookkeeping
	 * (e.g. a removal reason) has an obvious home that isn't `unenroll_user()`
	 * itself, which is the lower-level primitive also used by other callers
	 * (e.g. `cohort_access_closed` does NOT use this — see docs/TASKS.md,
	 * "Refund/cancellation must NOT auto-revoke access").
	 *
	 * @since 0.7.0
	 *
	 * @param int $cohort_id leaderspath_cohort post ID.
	 * @param int $user_id   WordPress user ID to remove.
	 * @return bool True on success.
	 */
	public static function remove_from_cohort( int $cohort_id, int $user_id ): bool {
		return self::unenroll_user( $user_id, $cohort_id );
	}

	/**
	 * Customize the new-user notification email for a cohort invite.
	 *
	 * The stock WP email ("a password reset was requested for your
	 * account") reads oddly as a stranger's first message from the site —
	 * this replaces the subject/body with cohort-specific framing while
	 * leaving `$email_data['message']`'s actual reset link untouched (it's
	 * already been built by wp_new_user_notification() before this filter
	 * runs).
	 *
	 * @since 0.7.0
	 *
	 * @param array<string, string> $email_data {@see wp_new_user_notification_email}.
	 * @param int                   $cohort_id  leaderspath_cohort post ID.
	 * @return array<string, string> Modified email data.
	 */
	private static function customize_invite_email( array $email_data, int $cohort_id ): array {
		$org_name    = get_field( 'cohort_org_name', $cohort_id );
		$cohort_name = $org_name ?: get_the_title( $cohort_id );

		$facilitator_id   = (int) get_field( 'cohort_facilitator', $cohort_id );
		$facilitator_user = $facilitator_id ? get_userdata( $facilitator_id ) : false;

		// Preserve the reset link WP core already built into the message —
		// only replace the framing text before it, not the link itself.
		$link_start = strpos( $email_data['message'], 'http' );
		$reset_link = false !== $link_start ? substr( $email_data['message'], $link_start ) : $email_data['message'];

		$intro = $facilitator_user
			? sprintf(
				/* translators: 1: organization/cohort name, 2: facilitator display name */
				__( "You've been invited to join %1\$s's LeadersPath cohort, facilitated by %2\$s.", 'leaderspath' ),
				$cohort_name,
				$facilitator_user->display_name
			)
			: sprintf(
				/* translators: %s: organization/cohort name */
				__( "You've been invited to join %s's LeadersPath cohort.", 'leaderspath' ),
				$cohort_name
			);

		$email_data['subject'] = '[%s] ' . sprintf(
			/* translators: %s: organization/cohort name */
			__( 'You\'re invited to %s', 'leaderspath' ),
			$cohort_name
		);

		$email_data['message'] = $intro . "\r\n\r\n"
			. __( 'Set your password to get started:', 'leaderspath' ) . "\r\n\r\n"
			. $reset_link;

		return $email_data;
	}

	/**
	 * Find an existing WP user by email, or create a new `leaderspath_student`
	 * account.
	 *
	 * Extracted from `invite_to_cohort()` — the same "does this email have an
	 * account, if not make one" operation is also needed by the WS Form
	 * cohort-purchase hook (`class-ws-form-integration.php`), where the
	 * *owner* needs to exist before `create_cohort()` runs, not after a
	 * cohort already exists to invite them into. One implementation, two
	 * callers, rather than duplicating account-creation logic.
	 *
	 * @since 0.7.0
	 *
	 * @param string $email Email address (assumed already validated/sanitized
	 *                       by the caller).
	 * @return int|\WP_Error WP user ID, or WP_Error on account-creation failure.
	 */
	public static function find_or_create_user_by_email( string $email ) {
		$existing_user = get_user_by( 'email', $email );

		if ( $existing_user ) {
			return $existing_user->ID;
		}

		return wp_insert_user( [
			'user_login' => self::generate_username_from_email( $email ),
			'user_email' => $email,
			'user_pass'  => wp_generate_password( 24 ),
			'role'       => 'leaderspath_student',
		] );
	}

	/**
	 * Generate a unique username from an email address.
	 *
	 * @since 0.7.0
	 *
	 * @param string $email Email address.
	 * @return string A unique username.
	 */
	private static function generate_username_from_email( string $email ): string {
		$base     = sanitize_user( current( explode( '@', $email ) ), true );
		$base     = $base ?: 'learner';
		$username = $base;
		$suffix   = 1;

		while ( username_exists( $username ) ) {
			$username = $base . $suffix;
			++$suffix;
		}

		return $username;
	}

	/**
	 * Check if a user is enrolled in a specific cohort.
	 *
	 * @since 0.4.0
	 *
	 * @param int $user_id   WordPress user ID.
	 * @param int $cohort_id leaderspath_cohort post ID.
	 * @return bool True if enrolled.
	 */
	public static function is_user_enrolled( int $user_id, int $cohort_id ): bool {
		$enrollments = get_user_meta( $user_id, self::ENROLLMENT_META_KEY, true );

		if ( ! is_array( $enrollments ) ) {
			return false;
		}

		return in_array( $cohort_id, $enrollments, true );
	}

	/**
	 * Get all cohort IDs a user is enrolled in.
	 *
	 * @since 0.4.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array<int> Array of leaderspath_cohort post IDs.
	 */
	public static function get_user_enrollments( int $user_id ): array {
		$enrollments = get_user_meta( $user_id, self::ENROLLMENT_META_KEY, true );

		if ( ! is_array( $enrollments ) ) {
			return [];
		}

		return array_map( 'intval', $enrollments );
	}

	/**
	 * Get all users enrolled in a specific cohort.
	 *
	 * @since 0.4.0
	 *
	 * @param int $cohort_id leaderspath_cohort post ID.
	 * @return array<int> Array of user IDs.
	 */
	public static function get_cohort_enrollees( int $cohort_id ): array {
		// enroll_user() pushes $cohort_id as a genuine int onto the
		// enrollments array, so PHP serializes each element as `i:{id};`,
		// not a quoted string. Found by testing (not by inspection): a
		// prior version of this query matched `"{id}"` — the format ACF
		// relationship fields use for post-ID arrays — which never matched
		// a real enrollment row, so this method silently returned empty for
		// every cohort until now.
		$users = get_users( [
			'meta_key'     => self::ENROLLMENT_META_KEY,
			'meta_value'   => sprintf( 'i:%d;', $cohort_id ),
			'meta_compare' => 'LIKE',
			'fields'       => 'ID',
		] );

		return array_map( 'intval', $users );
	}

	/**
	 * Get the cohort phase based on start and end dates.
	 *
	 * Distinct from `cohort_payment_status` — this is the pedagogical
	 * timeline (upcoming/active/completed), not the commitment/billing
	 * state. See docs/TASKS.md Phase 14, "Creation trigger and payment
	 * status."
	 *
	 * @since 0.4.0
	 *
	 * @param int $cohort_id leaderspath_cohort post ID.
	 * @return string One of 'upcoming', 'active', or 'completed'.
	 */
	public static function get_cohort_phase( int $cohort_id ): string {
		$start_date = get_field( 'cohort_start_date', $cohort_id );
		$end_date   = get_field( 'cohort_end_date', $cohort_id );
		$today      = current_time( 'Y-m-d' );

		if ( ! empty( $start_date ) && $today < $start_date ) {
			return 'upcoming';
		}

		if ( ! empty( $end_date ) && $today > $end_date ) {
			return 'completed';
		}

		return 'active';
	}

	// -------------------------------------------------------------------------
	// Prerequisite Resolution
	// -------------------------------------------------------------------------

	/**
	 * Get all prerequisite courses for a cohort.
	 *
	 * Aggregates the `course_prerequisites` from every course linked to
	 * the cohort, de-duplicates, and excludes courses that are already
	 * included in the cohort itself (a course can't be both required AND
	 * delivered by the same cohort).
	 *
	 * @since 0.5.0
	 *
	 * @param int $cohort_id leaderspath_cohort post ID.
	 * @return array<int> Unique prerequisite course post IDs.
	 */
	public static function get_cohort_prerequisites( int $cohort_id ): array {
		$cohort_courses = get_field( 'cohort_courses', $cohort_id );

		if ( ! is_array( $cohort_courses ) || empty( $cohort_courses ) ) {
			return [];
		}

		$prerequisites = [];

		foreach ( $cohort_courses as $course_id ) {
			$course_prereqs = get_field( 'course_prerequisites', $course_id );

			if ( is_array( $course_prereqs ) ) {
				foreach ( $course_prereqs as $prereq_id ) {
					$prerequisites[] = (int) $prereq_id;
				}
			}
		}

		// De-duplicate and exclude courses already in the cohort.
		$prerequisites = array_unique( $prerequisites );
		$prerequisites = array_values(
			array_diff( $prerequisites, array_map( 'intval', $cohort_courses ) )
		);

		return $prerequisites;
	}

	/**
	 * Get all lessons belonging to a cohort, via its linked courses.
	 *
	 * Walks the access chain one level: cohort → cohort_courses → each course's
	 * course_lessons. De-duplicated, order preserved (course order, then lesson
	 * order within each course). Used to scope the "current lesson" picker and
	 * by display surfaces that list a cohort's lessons.
	 *
	 * @since 0.7.0
	 *
	 * @param int $cohort_id leaderspath_cohort post ID.
	 * @return array<int> Unique lesson post IDs.
	 */
	public static function get_cohort_lessons( int $cohort_id ): array {
		$cohort_courses = get_field( 'cohort_courses', $cohort_id );

		if ( ! is_array( $cohort_courses ) || empty( $cohort_courses ) ) {
			return [];
		}

		$lessons = [];

		foreach ( $cohort_courses as $course_id ) {
			$course_lessons = get_field( 'course_lessons', $course_id );

			if ( is_array( $course_lessons ) ) {
				foreach ( $course_lessons as $lesson_id ) {
					$lessons[] = (int) $lesson_id;
				}
			}
		}

		return array_values( array_unique( $lessons ) );
	}

	/**
	 * Get the cohorts a user facilitates.
	 *
	 * Used to scope a facilitator's Context File access — both which cohort
	 * they may assign to a new/edited file, and which existing cohort-owned
	 * files show in their admin list.
	 *
	 * @since 0.7.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array<int> leaderspath_cohort post IDs this user facilitates.
	 */
	public static function get_facilitator_cohorts( int $user_id ): array {
		$cohorts = get_posts( [
			'post_type'      => 'leaderspath_cohort',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'meta_query'     => [
				[
					'key'   => 'cohort_facilitator',
					'value' => $user_id,
				],
			],
			'fields'         => 'ids',
		] );

		return array_map( 'intval', $cohorts );
	}

	// -------------------------------------------------------------------------
	// Access Chain Resolution
	// -------------------------------------------------------------------------

	/**
	 * Check if a user can access a specific course via cohort enrollment.
	 *
	 * Admin and editor roles bypass enrollment checks.
	 *
	 * @since 0.4.0
	 *
	 * @param int $user_id   WordPress user ID.
	 * @param int $course_id LeadersPath course post ID.
	 * @return bool True if the user can access the course.
	 */
	public static function can_user_access_course( int $user_id, int $course_id ): bool {
		// Admin/Editor bypass.
		if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'edit_others_posts' ) ) {
			return true;
		}

		$enrollments = self::get_user_enrollments( $user_id );
		if ( empty( $enrollments ) ) {
			return false;
		}

		// Find published cohort instances that include this course.
		$cohorts = get_posts( [
			'post_type'      => 'leaderspath_cohort',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'     => 'cohort_courses',
					'value'   => sprintf( '"%d"', $course_id ),
					'compare' => 'LIKE',
				],
			],
			'fields'         => 'ids',
		] );

		if ( empty( $cohorts ) ) {
			return false;
		}

		// Check if user is enrolled in any of these cohorts, and that access
		// hasn't been manually closed. cohort_access_closed is deliberately
		// decoupled from refund/cancellation and checked live here, not by
		// touching the enrollment record itself — see docs/TASKS.md Phase 14,
		// "Refund/cancellation must NOT auto-revoke access."
		foreach ( $cohorts as $cohort_id ) {
			if ( in_array( $cohort_id, $enrollments, true ) && ! get_field( 'cohort_access_closed', $cohort_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a user can access a specific lesson via cohort enrollment.
	 *
	 * Resolves the chain: Lesson → Course(s) → Cohort(s) → Enrollment.
	 *
	 * @since 0.4.0
	 *
	 * @param int $user_id   WordPress user ID.
	 * @param int $lesson_id LeadersPath lesson post ID.
	 * @return bool True if the user can access the lesson.
	 */
	public static function can_user_access_lesson( int $user_id, int $lesson_id ): bool {
		// Admin/Editor bypass.
		if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'edit_others_posts' ) ) {
			return true;
		}

		// Find courses that contain this lesson.
		$courses = get_posts( [
			'post_type'      => 'leaderspath_course',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'     => 'course_lessons',
					'value'   => sprintf( '"%d"', $lesson_id ),
					'compare' => 'LIKE',
				],
			],
			'fields'         => 'ids',
		] );

		if ( empty( $courses ) ) {
			return false;
		}

		// Check if user can access any of those courses.
		foreach ( $courses as $course_id ) {
			if ( self::can_user_access_course( $user_id, $course_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a user can access a specific activity via cohort enrollment.
	 *
	 * Resolves the chain: Activity → Lesson(s) → Course(s) → Cohort(s) → Enrollment.
	 *
	 * @since 0.4.0
	 *
	 * @param int $user_id     WordPress user ID.
	 * @param int $activity_id LeadersPath activity post ID.
	 * @return bool True if the user can access the activity.
	 */
	public static function can_user_access_activity( int $user_id, int $activity_id ): bool {
		// Admin/Editor bypass.
		if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'edit_others_posts' ) ) {
			return true;
		}

		// Find lessons that contain this activity.
		$lessons = get_posts( [
			'post_type'      => 'leaderspath_lesson',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'     => 'lesson_activities',
					'value'   => sprintf( '"%d"', $activity_id ),
					'compare' => 'LIKE',
				],
			],
			'fields'         => 'ids',
		] );

		if ( empty( $lessons ) ) {
			return false;
		}

		// Check if user can access any of those lessons.
		foreach ( $lessons as $lesson_id ) {
			if ( self::can_user_access_lesson( $user_id, $lesson_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a user can access a specific context file.
	 *
	 * Two distinct cases, checked in order:
	 *
	 * 1. **Cohort-owned** (`context_cohort` set) — organization-specific
	 *    material. Access requires enrollment in that exact cohort, checked
	 *    directly; attachment to an activity/lesson doesn't matter here,
	 *    because cohort ownership is the actual confidentiality boundary.
	 * 2. **Shared/public** (`context_cohort` empty) — curriculum content
	 *    available to every cohort, exactly as context files behaved before
	 *    per-cohort scoping existed. No further check: the capability gate
	 *    in check_read_permission() (leaderspath_access_chatbot) is what
	 *    controls access, not activity/lesson attachment. A file doesn't
	 *    become confidential just because nobody has wired it into an
	 *    activity yet.
	 *
	 * @since 0.7.0
	 *
	 * @param int $user_id    WordPress user ID.
	 * @param int $context_id LeadersPath context file post ID.
	 * @return bool True if the user can access the context file.
	 */
	public static function can_user_access_context( int $user_id, int $context_id ): bool {
		// Admin/Editor bypass.
		if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'edit_others_posts' ) ) {
			return true;
		}

		$cohort_id = get_field( 'context_cohort', $context_id );

		if ( ! empty( $cohort_id ) ) {
			return self::is_user_enrolled( $user_id, (int) $cohort_id );
		}

		// No cohort — shared/public curriculum content.
		return true;
	}

	/**
	 * Check if a user can access a specific skill via cohort enrollment.
	 *
	 * Resolves the chain: Skill → Activity(ies) that reference it → ... → Enrollment.
	 * Skills only attach to Activities (chatbot_skills) — the lesson-level Q&A
	 * chatbot has no skills field, so there is no lesson-side lookup here.
	 *
	 * @since 0.7.0
	 *
	 * @param int $user_id  WordPress user ID.
	 * @param int $skill_id LeadersPath skill post ID.
	 * @return bool True if the user can access the skill.
	 */
	public static function can_user_access_skill( int $user_id, int $skill_id ): bool {
		// Admin/Editor bypass.
		if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'edit_others_posts' ) ) {
			return true;
		}

		// Activities that attach this skill.
		$activities = get_posts( [
			'post_type'      => 'leaderspath_activity',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'     => 'chatbot_skills',
					'value'   => sprintf( '"%d"', $skill_id ),
					'compare' => 'LIKE',
				],
			],
			'fields'         => 'ids',
		] );

		foreach ( $activities as $activity_id ) {
			if ( self::can_user_access_activity( $user_id, (int) $activity_id ) ) {
				return true;
			}
		}

		return false;
	}
}
