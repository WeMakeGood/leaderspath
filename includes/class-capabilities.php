<?php
/**
 * Capabilities and Roles registration.
 *
 * @package LeadersPath
 * @since   0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

/**
 * Manages custom capabilities and roles for LeadersPath.
 *
 * @since 0.1.0
 */
class Capabilities {

	/**
	 * Initialize capability checks.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		// Ensure capabilities are added (handles case where plugin was updated without reactivation).
		add_action( 'admin_init', [ __CLASS__, 'maybe_add_caps' ] );

		// Per-post exception letting a facilitator reach a co-facilitator's or
		// admin-seeded context file for a cohort they actually run, without
		// granting the blanket edit_others_/delete_others_ capability that
		// would expose every cohort's files. See get_facilitator_caps().
		add_filter( 'map_meta_cap', [ __CLASS__, 'grant_facilitator_own_cohort_context' ], 10, 4 );
	}

	/**
	 * Add capabilities if they're missing.
	 *
	 * This handles the case where the plugin was activated before all CPTs were defined,
	 * or when the plugin is updated without being reactivated.
	 *
	 * @since 0.1.0
	 */
	public static function maybe_add_caps(): void {
		$admin = get_role( 'administrator' );

		if ( ! $admin ) {
			return;
		}

		// Check if our capabilities are already added by testing for the activity capability.
		// This also catches the migration from leaderspath_lesson to leaderspath_activity.
		if ( ! $admin->has_cap( 'edit_leaderspath_activity' ) ) {
			// Remove old lesson capabilities if they exist (migration from lesson to activity).
			self::remove_legacy_lesson_caps();

			// Capabilities are missing, add them.
			self::add_caps();
			return;
		}

		// Check for newer capabilities that may have been added in updates.
		self::maybe_add_facilitator_caps();
		self::maybe_add_cohort_caps();
		self::maybe_add_facilitator_context_caps();
		self::maybe_add_cohort_cpt_caps();
	}

	/**
	 * Add facilitator capability if missing.
	 *
	 * This handles upgrades where the facilitator capability was added after initial activation.
	 *
	 * @since 0.1.0
	 */
	private static function maybe_add_facilitator_caps(): void {
		$admin = get_role( 'administrator' );

		// If admin already has facilitator cap, we're up to date.
		if ( $admin && $admin->has_cap( 'leaderspath_view_facilitator_content' ) ) {
			return;
		}

		// Add facilitator capability to admin.
		if ( $admin ) {
			$admin->add_cap( 'leaderspath_view_facilitator_content', true );
		}

		// Add facilitator capability to editor.
		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor->add_cap( 'leaderspath_view_facilitator_content', true );
		}

		// Create facilitator role if it doesn't exist.
		if ( ! get_role( 'leaderspath_facilitator' ) ) {
			add_role(
				'leaderspath_facilitator',
				__( 'LeadersPath Facilitator', 'leaderspath' ),
				self::get_facilitator_caps()
			);
		}
	}

	/**
	 * Add cohort management capability if missing.
	 *
	 * Handles upgrades where the cohort capability was added after initial activation.
	 *
	 * @since 0.4.0
	 */
	private static function maybe_add_cohort_caps(): void {
		$admin = get_role( 'administrator' );

		// If admin already has cohort cap, we're up to date.
		if ( $admin && $admin->has_cap( 'leaderspath_manage_cohorts' ) ) {
			return;
		}

		if ( $admin ) {
			$admin->add_cap( 'leaderspath_manage_cohorts', true );
		}

		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor->add_cap( 'leaderspath_manage_cohorts', true );
		}
	}

	/**
	 * Add facilitator Context File capabilities if missing.
	 *
	 * Handles upgrades where facilitators gained cohort-owned Context File
	 * access after the facilitator role already existed — maybe_add_facilitator_caps()
	 * only sets get_facilitator_caps() on the role at *creation*, so an
	 * existing facilitator role needs its own backfill check here.
	 *
	 * @since 0.7.0
	 */
	private static function maybe_add_facilitator_context_caps(): void {
		$facilitator = get_role( 'leaderspath_facilitator' );

		if ( ! $facilitator || $facilitator->has_cap( 'edit_leaderspath_context' ) ) {
			return;
		}

		foreach ( self::get_facilitator_caps() as $cap => $grant ) {
			$facilitator->add_cap( $cap, $grant );
		}
	}

	/**
	 * Add the new `leaderspath_cohort` CPT capabilities if missing.
	 *
	 * Handles upgrades where the Cohort CPT (purchase-time instance — see
	 * Post_Types::register_cohort()) was added after admin/editor roles
	 * already existed on the install. Full grant for admin, full operational
	 * access for editor — see get_editor_caps()'s comment for why cohorts
	 * join the full-access list rather than the read-only one.
	 *
	 * @since 0.7.0
	 */
	private static function maybe_add_cohort_cpt_caps(): void {
		$admin = get_role( 'administrator' );

		if ( $admin && ! $admin->has_cap( 'edit_leaderspath_cohort' ) ) {
			foreach ( self::get_cpt_caps( 'leaderspath_cohort', 'leaderspath_cohorts' ) as $cap ) {
				$admin->add_cap( $cap, true );
			}
		}

		$editor = get_role( 'editor' );

		if ( $editor && ! $editor->has_cap( 'edit_leaderspath_cohort' ) ) {
			foreach ( self::get_cpt_caps( 'leaderspath_cohort', 'leaderspath_cohorts' ) as $cap ) {
				$editor->add_cap( $cap, true );
			}
		}
	}

	/**
	 * Remove legacy capabilities from prior naming conventions.
	 *
	 * Handles migrations:
	 * - leaderspath_lesson → leaderspath_activity (v0.2.0)
	 * - leaderspath_cohort removed, old leaderspath_course → leaderspath_lesson (v0.3.0)
	 *
	 * NOTE (0.7.0): `leaderspath_cohort` exists again as of this version, as a
	 * genuinely new CPT with a different meaning — a purchase-time cohort
	 * instance (facilitator/dates/roster), not the old v0.3.0-era post type
	 * this method's cleanup refers to. The capability *names* collide
	 * (`edit_leaderspath_cohort`, etc.) but this method only ever runs once,
	 * gated on an admin missing `edit_leaderspath_activity` — already false
	 * on every real install past v0.3.0 — so it can't strip the new CPT's
	 * caps. Left as historical record, not a live conflict.
	 *
	 * @since 0.2.0
	 */
	private static function remove_legacy_lesson_caps(): void {
		// Old lesson→activity migration caps.
		$legacy_caps = self::get_cpt_caps( 'leaderspath_lesson', 'leaderspath_lessons' );
		$legacy_caps[] = 'leaderspath_access_lessons';

		// Old cohort caps (cohort CPT renamed to course in v0.3.0).
		$legacy_caps = array_merge(
			$legacy_caps,
			self::get_cpt_caps( 'leaderspath_cohort', 'leaderspath_cohorts' )
		);

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( $legacy_caps as $cap ) {
				$admin->remove_cap( $cap );
			}
		}

		$editor = get_role( 'editor' );
		if ( $editor ) {
			foreach ( $legacy_caps as $cap ) {
				$editor->remove_cap( $cap );
			}
		}
	}

	/**
	 * Custom post type capability bases with their plurals.
	 *
	 * Maps singular capability base to plural form for proper English pluralization.
	 *
	 * @var array<string, string>
	 */
	private const CPT_CAPS = [
		'leaderspath_activity' => 'leaderspath_activities',
		'leaderspath_lesson'   => 'leaderspath_lessons',
		'leaderspath_course'   => 'leaderspath_courses',
		'leaderspath_cohort'   => 'leaderspath_cohorts',
		'leaderspath_context'  => 'leaderspath_contexts',
		'leaderspath_skill'    => 'leaderspath_skills',
	];

	/**
	 * Custom capabilities for frontend features.
	 *
	 * @var array<string>
	 */
	private const CUSTOM_CAPS = [
		'leaderspath_access_activities',
		'leaderspath_access_chatbot',
		'leaderspath_view_context',
		'leaderspath_download_context',
		'leaderspath_view_facilitator_content',
		'leaderspath_manage_cohorts',
	];

	/**
	 * Get all primitive capabilities for a post type.
	 *
	 * @since 0.1.0
	 *
	 * @param string      $cap_base The capability base (e.g., 'leaderspath_activity').
	 * @param string|null $plural   Optional. The plural form. Defaults to lookup from CPT_CAPS or appending 's'.
	 * @return array<string> Array of capabilities.
	 */
	public static function get_cpt_caps( string $cap_base, ?string $plural = null ): array {
		// Use provided plural, lookup from CPT_CAPS map, or fall back to simple 's' suffix.
		if ( null === $plural ) {
			$plural = self::CPT_CAPS[ $cap_base ] ?? $cap_base . 's';
		}

		return [
			// Primitive caps for this post type.
			"edit_{$cap_base}",
			"read_{$cap_base}",
			"delete_{$cap_base}",
			"edit_{$plural}",
			"edit_others_{$plural}",
			"publish_{$plural}",
			"read_private_{$plural}",
			"delete_{$plural}",
			"delete_private_{$plural}",
			"delete_published_{$plural}",
			"delete_others_{$plural}",
			"edit_private_{$plural}",
			"edit_published_{$plural}",
		];
	}

	/**
	 * Get all capabilities for administrator role.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, bool> Capabilities with true values.
	 */
	public static function get_admin_caps(): array {
		$caps = [];

		// Add all CPT capabilities.
		foreach ( self::CPT_CAPS as $cap_base => $plural ) {
			foreach ( self::get_cpt_caps( $cap_base, $plural ) as $cap ) {
				$caps[ $cap ] = true;
			}
		}

		// Add custom feature capabilities.
		foreach ( self::CUSTOM_CAPS as $cap ) {
			$caps[ $cap ] = true;
		}

		return $caps;
	}

	/**
	 * Get capabilities for editor role.
	 *
	 * Editors can manage Activities and Lessons, but only read Courses, Context, and Skills.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, bool> Capabilities with true values.
	 */
	public static function get_editor_caps(): array {
		$caps = [];

		// Full access to Activities, Lessons, and Cohorts. Cohorts join this
		// list (not the read-only curriculum-content list below) because
		// editors already hold leaderspath_manage_cohorts for the WC product
		// side of cohort operations — this extends that same operational
		// role to the cohort instance itself (facilitator assignment, dates,
		// roster), not curriculum content editors merely oversee.
		$editor_full_access = [
			'leaderspath_activity' => 'leaderspath_activities',
			'leaderspath_lesson'   => 'leaderspath_lessons',
			'leaderspath_cohort'   => 'leaderspath_cohorts',
		];
		foreach ( $editor_full_access as $cap_base => $plural ) {
			foreach ( self::get_cpt_caps( $cap_base, $plural ) as $cap ) {
				$caps[ $cap ] = true;
			}
		}

		// Read-only for Courses, Context, Skills.
		$editor_read_only = [
			'leaderspath_course'  => 'leaderspath_courses',
			'leaderspath_context' => 'leaderspath_contexts',
			'leaderspath_skill'   => 'leaderspath_skills',
		];
		foreach ( $editor_read_only as $cap_base => $plural ) {
			$caps[ "read_{$cap_base}" ]      = true;
			$caps[ "read_private_{$plural}" ] = true;
		}

		// Custom feature capabilities.
		foreach ( self::CUSTOM_CAPS as $cap ) {
			$caps[ $cap ] = true;
		}

		return $caps;
	}

	/**
	 * Get capabilities for student role.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, bool> Capabilities with true values.
	 */
	public static function get_student_caps(): array {
		return [
			'read'                          => true,
			'leaderspath_access_activities' => true,
			'leaderspath_access_chatbot'    => true,
			'leaderspath_view_context'      => true,
			'leaderspath_download_context'  => true,
		];
	}

	/**
	 * Get capabilities for facilitator role.
	 *
	 * Facilitators have all student capabilities plus access to facilitator-only content.
	 *
	 * Context File access is deliberately narrower than a full CPT grant: it
	 * excludes edit_others_/delete_others_/read_private_leaderspath_contexts.
	 * WordPress's own primitive-cap check would otherwise let a facilitator
	 * open *any* context file — including another cohort's confidential
	 * material — by post ID alone. Cross-cohort access to a file they didn't
	 * author (e.g. an admin-seeded starter module for their own cohort) is
	 * instead granted per-post by the map_meta_cap filter below, which checks
	 * actual cohort ownership rather than a blanket capability. The
	 * acf/validate_value gate in ACF_Fields is what stops them writing an
	 * unscoped or another-cohort's value even if they do reach the editor.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, bool> Capabilities with true values.
	 */
	public static function get_facilitator_caps(): array {
		return array_merge(
			self::get_student_caps(),
			[
				'leaderspath_view_facilitator_content' => true,
				'edit_leaderspath_context'             => true,
				'edit_leaderspath_contexts'            => true,
				'publish_leaderspath_contexts'         => true,
				'delete_leaderspath_context'           => true,
				'read_leaderspath_context'             => true,
			]
		);
	}

	/**
	 * Grant a facilitator edit/delete access to a specific context file they
	 * didn't author, when it's scoped to a cohort they facilitate.
	 *
	 * Without edit_others_leaderspath_contexts (deliberately withheld — see
	 * get_facilitator_caps()), WordPress's own meta-cap mapping already
	 * blocks a facilitator from opening a context file authored by someone
	 * else. This filter is the narrow, per-post exception: an admin-seeded
	 * starter module or a co-facilitator's file for a cohort this user
	 * actually runs. Anything outside that stays blocked with no extra code.
	 *
	 * @since 0.7.0
	 *
	 * @param array<string> $caps    Required primitive capabilities.
	 * @param string        $cap     Requested meta capability.
	 * @param int           $user_id User being checked.
	 * @param array<int>    $args    [0] => post ID being checked, for meta caps.
	 * @return array<string> Modified required capabilities.
	 */
	public static function grant_facilitator_own_cohort_context( array $caps, string $cap, int $user_id, array $args ): array {
		if ( ! in_array( $cap, [ 'edit_post', 'delete_post', 'read_post' ], true ) || empty( $args[0] ) ) {
			return $caps;
		}

		$post = get_post( (int) $args[0] );

		if ( ! $post || 'leaderspath_context' !== $post->post_type ) {
			return $caps;
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! in_array( 'leaderspath_facilitator', (array) $user->roles, true ) ) {
			return $caps;
		}

		// Own post — WordPress's own mapping (edit_leaderspath_context, etc.)
		// already covers this correctly; nothing to add or override.
		if ( (int) $post->post_author === $user_id ) {
			return $caps;
		}

		// From here on this filter is authoritative for facilitators: it must
		// both grant (their own cohort's files, authored by someone else —
		// an admin-seeded starter module, a co-facilitator's edit) and deny —
		// because WordPress's default read_post mapping falls back to the
		// generic "read" capability for any published, non-private post of a
		// publicly-queryable CPT (this one is, for ACF relationship search),
		// which every logged-in user holds. Left alone, that default would
		// let a facilitator read another cohort's confidential file by post
		// ID alone. An impossible capability forces a hard deny instead of
		// silently falling through to that default.
		$deny = [ 'do_not_allow' ];

		$file_cohort = get_field( 'context_cohort', $post->ID );
		if ( empty( $file_cohort ) ) {
			// Unscoped/public file — a facilitator has no standing access to
			// it (they can't create public files either; see get_facilitator_caps()).
			return $deny;
		}

		$facilitator_cohorts = Enrollment::get_facilitator_cohorts( $user_id );

		if ( in_array( (int) $file_cohort, $facilitator_cohorts, true ) ) {
			// Their cohort — grant via a capability every logged-in user
			// holds, standing in for whichever primitive cap WP core's own
			// author-based branching would otherwise require.
			return [ 'exist' ];
		}

		return $deny;
	}

	/**
	 * Add capabilities to roles on plugin activation.
	 *
	 * @since 0.1.0
	 */
	public static function add_caps(): void {
		// Add capabilities to Administrator.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::get_admin_caps() as $cap => $grant ) {
				$admin->add_cap( $cap, $grant );
			}
		}

		// Add capabilities to Editor.
		$editor = get_role( 'editor' );
		if ( $editor ) {
			foreach ( self::get_editor_caps() as $cap => $grant ) {
				$editor->add_cap( $cap, $grant );
			}
		}

		// Create LeadersPath Student role if it doesn't exist.
		if ( ! get_role( 'leaderspath_student' ) ) {
			add_role(
				'leaderspath_student',
				__( 'LeadersPath Student', 'leaderspath' ),
				self::get_student_caps()
			);
		}

		// Create LeadersPath Facilitator role if it doesn't exist.
		if ( ! get_role( 'leaderspath_facilitator' ) ) {
			add_role(
				'leaderspath_facilitator',
				__( 'LeadersPath Facilitator', 'leaderspath' ),
				self::get_facilitator_caps()
			);
		}
	}

	/**
	 * Remove capabilities from roles on plugin deactivation.
	 *
	 * @since 0.1.0
	 */
	public static function remove_caps(): void {
		// Remove capabilities from Administrator.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::get_admin_caps() as $cap => $grant ) {
				$admin->remove_cap( $cap );
			}
		}

		// Remove capabilities from Editor.
		$editor = get_role( 'editor' );
		if ( $editor ) {
			foreach ( self::get_editor_caps() as $cap => $grant ) {
				$editor->remove_cap( $cap );
			}
		}

		// Note: We don't remove the student role on deactivation to preserve user assignments.
		// The role can be removed on uninstall if desired.
	}
}
