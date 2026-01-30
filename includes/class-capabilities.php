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
	 * All custom post type capability bases.
	 *
	 * @var array<string>
	 */
	private const CPT_CAPS = [
		'leaderspath_lesson',
		'leaderspath_course',
		'leaderspath_cohort',
		'leaderspath_context',
		'leaderspath_skill',
	];

	/**
	 * Custom capabilities for frontend features.
	 *
	 * @var array<string>
	 */
	private const CUSTOM_CAPS = [
		'leaderspath_access_lessons',
		'leaderspath_access_chatbot',
		'leaderspath_view_context',
		'leaderspath_download_context',
	];

	/**
	 * Get all primitive capabilities for a post type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $cap_base The capability base (e.g., 'leaderspath_lesson').
	 * @return array<string> Array of capabilities.
	 */
	public static function get_cpt_caps( string $cap_base ): array {
		$plural = $cap_base . 's';

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
		foreach ( self::CPT_CAPS as $cap_base ) {
			foreach ( self::get_cpt_caps( $cap_base ) as $cap ) {
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
	 * Editors can manage Lessons and Courses, but only read Cohorts, Context, and Skills.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, bool> Capabilities with true values.
	 */
	public static function get_editor_caps(): array {
		$caps = [];

		// Full access to Lessons and Courses.
		foreach ( [ 'leaderspath_lesson', 'leaderspath_course' ] as $cap_base ) {
			foreach ( self::get_cpt_caps( $cap_base ) as $cap ) {
				$caps[ $cap ] = true;
			}
		}

		// Read-only for Cohorts, Context, Skills.
		foreach ( [ 'leaderspath_cohort', 'leaderspath_context', 'leaderspath_skill' ] as $cap_base ) {
			$caps[ "read_{$cap_base}" ] = true;
			$caps[ "read_private_{$cap_base}s" ] = true;
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
			'read'                        => true,
			'leaderspath_access_lessons'  => true,
			'leaderspath_access_chatbot'  => true,
			'leaderspath_view_context'    => true,
			'leaderspath_download_context' => true,
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
