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
	 *      Skipped when $allow_fallback is false.
	 *
	 * @param string $cpt_slug       CPT slug without the `leaderspath_` prefix
	 *                               (e.g. 'activity', 'lesson', 'course').
	 * @param int    $override       Explicit post ID override. 0 means "auto".
	 * @param bool   $allow_fallback Whether to use the sample-post fallback when
	 *                               nothing resolves. Pass false for live output
	 *                               (e.g. the chatbot), where serving a wrong
	 *                               post is worse than serving none. Default true
	 *                               for builder previews. Returns 0 when off and
	 *                               nothing resolves.
	 */
	public static function get_post_id( string $cpt_slug, int $override = 0, bool $allow_fallback = true ): int {
		$expected = "leaderspath_{$cpt_slug}";

		if ( $override > 0 && get_post_type( $override ) === $expected ) {
			return $override;
		}

		// Bricks query loop: resolve the current loop item directly. This is the
		// correct source inside a Bricks loop, where get_queried_object_id() /
		// get_the_ID() return the page's main post (e.g. the Lesson), not the
		// loop item (the Activity). Bricks' own API returns 0 when not looping.
		if ( class_exists( '\Bricks\Query' ) ) {
			$loop_id = (int) \Bricks\Query::get_loop_object_id();
			if ( $loop_id && get_post_type( $loop_id ) === $expected ) {
				return $loop_id;
			}
		}

		$queried = (int) get_queried_object_id();
		if ( ! $queried ) {
			$queried = (int) get_the_ID();
		}

		if ( $queried && get_post_type( $queried ) === $expected ) {
			return $queried;
		}

		if ( ! $allow_fallback ) {
			return 0;
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
