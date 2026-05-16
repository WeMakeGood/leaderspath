<?php
/**
 * Resolves the current CPT post ID for renderer output.
 *
 * @package LeadersPath\Renderers
 * @since   0.7.0
 */

declare(strict_types=1);

namespace LeadersPath\Renderers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

class Post_Id_Helper {

	/**
	 * Resolve a post ID for the given LeadersPath CPT slug.
	 *
	 * Resolution order:
	 *   1. Explicit override (caller passes a post ID).
	 *   2. Current queried object, if it matches the expected CPT.
	 *   3. Sample fallback — the most recent published post of the CPT,
	 *      so builder previews and admin contexts have something to show.
	 *
	 * @param string $cpt_slug CPT slug without the `leaderspath_` prefix
	 *                         (e.g. 'activity', 'lesson', 'course').
	 * @param int    $override Explicit post ID override. 0 means "auto".
	 */
	public static function get_post_id( string $cpt_slug, int $override = 0 ): int {
		$expected = "leaderspath_{$cpt_slug}";

		if ( $override > 0 && get_post_type( $override ) === $expected ) {
			return $override;
		}

		$queried = (int) get_queried_object_id();
		if ( ! $queried ) {
			$queried = (int) get_the_ID();
		}

		if ( $queried && get_post_type( $queried ) === $expected ) {
			return $queried;
		}

		$sample = get_posts(
			[
				'post_type'      => $expected,
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			]
		);

		return ! empty( $sample ) ? (int) $sample[0] : 0;
	}
}
