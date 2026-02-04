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
		if ( $admin->has_cap( 'edit_leaderspath_activity' ) ) {
			return;
		}

		// Remove old lesson capabilities if they exist (migration from lesson to activity).
		self::remove_legacy_lesson_caps();

		// Capabilities are missing, add them.
		self::add_caps();
	}

	/**
	 * Remove legacy leaderspath_lesson capabilities.
	 *
	 * This is a one-time migration from the old lesson terminology to activity.
	 *
	 * @since 0.2.0
	 */
	private static function remove_legacy_lesson_caps(): void {
		$legacy_caps = self::get_cpt_caps( 'leaderspath_lesson', 'leaderspath_lessons' );
		$legacy_caps[] = 'leaderspath_access_lessons';

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
	 * Editors can manage Activities and Courses, but only read Cohorts, Context, and Skills.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, bool> Capabilities with true values.
	 */
	public static function get_editor_caps(): array {
		$caps = [];

		// Full access to Activities and Courses.
		$editor_full_access = [
			'leaderspath_activity' => 'leaderspath_activities',
			'leaderspath_course'   => 'leaderspath_courses',
		];
		foreach ( $editor_full_access as $cap_base => $plural ) {
			foreach ( self::get_cpt_caps( $cap_base, $plural ) as $cap ) {
				$caps[ $cap ] = true;
			}
		}

		// Read-only for Cohorts, Context, Skills.
		$editor_read_only = [
			'leaderspath_cohort'  => 'leaderspath_cohorts',
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
