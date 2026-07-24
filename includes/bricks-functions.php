<?php
/**
 * Global `lp_*` functions callable from Bricks Builder.
 *
 * Bricks' `{echo:}` dynamic tag and condition system call PHP by bare global
 * function name — they cannot reference a namespaced static method. These are
 * therefore thin global adapters over the plugin's namespaced logic; they hold
 * no business logic of their own beyond argument marshalling and render-safe
 * defaults.
 *
 * Registration/whitelisting lives in class-bricks-integration.php.
 * Contract: docs/bricks-integration.md.
 *
 * @package LeadersPath
 * @since   0.7.0
 */

declare(strict_types=1);

use LeadersPath\Includes\WooCommerce;
use LeadersPath\Renderers\Post_Id_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( ! function_exists( 'lp_user_is_enrolled' ) ) {
	/**
	 * Whether the current user is enrolled in a cohort.
	 *
	 * Gating function — used by the "user is enrolled" Element Condition. Fails
	 * closed: returns false for logged-out users, an unresolvable cohort, or a
	 * non-cohort post. Does NOT fall back to a sample post (unlike display
	 * helpers) — a security check must never guess its subject.
	 *
	 * @param int|null $cohort_id Cohort product ID. Null → resolve the current
	 *                            queried object, which must be a cohort product.
	 * @return bool
	 */
	function lp_user_is_enrolled( ?int $cohort_id = null ): bool {
		if ( ! class_exists( WooCommerce::class ) ) {
			return false;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		if ( null === $cohort_id || $cohort_id <= 0 ) {
			$cohort_id = (int) get_queried_object_id();
		}

		// Must be an actual cohort product — no sample fallback for gating.
		if ( $cohort_id <= 0 || ! WooCommerce::is_cohort_product( $cohort_id ) ) {
			return false;
		}

		return WooCommerce::is_user_enrolled( $user_id, $cohort_id );
	}
}

if ( ! function_exists( 'lp_enrolled_cohorts' ) ) {
	/**
	 * The current user's enrolled cohort IDs, optionally filtered by phase.
	 *
	 * Feeds the dashboard's per-phase query loops (pass the result as `post__in`).
	 * Returned as a comma-separated string when echoed, but as an array when
	 * called in PHP — Bricks `{echo:}` stringifies arrays awkwardly, so callers
	 * that need the list for a query should call the PHP function directly.
	 *
	 * @param string $phase '' (all), 'active', 'upcoming', or 'completed'.
	 * @return array<int> Cohort product IDs.
	 */
	function lp_enrolled_cohorts( string $phase = '' ): array {
		if ( ! class_exists( WooCommerce::class ) ) {
			return [];
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return [];
		}

		$cohorts = WooCommerce::get_user_enrollments( $user_id );
		if ( empty( $cohorts ) ) {
			return [];
		}

		$phase = strtolower( trim( $phase ) );
		if ( '' === $phase ) {
			return array_values( array_map( 'intval', $cohorts ) );
		}

		$filtered = [];
		foreach ( $cohorts as $cohort_id ) {
			$cohort_id = (int) $cohort_id;
			if ( WooCommerce::get_cohort_phase( $cohort_id ) === $phase ) {
				$filtered[] = $cohort_id;
			}
		}

		return $filtered;
	}
}

if ( ! function_exists( 'lp_is_last_activity' ) ) {
	/**
	 * Whether an activity is the last in its lesson.
	 *
	 * Drives the lesson page's right-arrow dimmed state. Returns '1'/'' (string)
	 * so it works directly in `{echo:}` and empty-checks. This is a display
	 * helper, so it may resolve the current activity via Post_Id_Helper
	 * (including its sample fallback) for builder previews.
	 *
	 * @param int|null $activity_id Activity post ID. Null → current activity.
	 * @return string '1' if last (or lesson unresolvable), '' otherwise.
	 */
	function lp_is_last_activity( ?int $activity_id = null ): string {
		$activity_id = Post_Id_Helper::get_post_id( 'activity', (int) $activity_id );
		if ( $activity_id <= 0 ) {
			return '';
		}

		// Find the lesson that lists this activity, then check ordering.
		$lessons = get_posts(
			[
				'post_type'      => 'leaderspath_lesson',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'     => 'lesson_activities',
						'value'   => '"' . $activity_id . '"',
						'compare' => 'LIKE',
					],
				],
			]
		);

		if ( empty( $lessons ) ) {
			// No parent lesson found — treat as last so navigation doesn't dead-end
			// on a phantom "next".
			return '1';
		}

		$activities = get_field( 'lesson_activities', (int) $lessons[0] );
		if ( ! is_array( $activities ) || empty( $activities ) ) {
			return '1';
		}

		$activities = array_map( 'intval', $activities );
		$last       = (int) end( $activities );

		return ( $last === $activity_id ) ? '1' : '';
	}
}
