<?php
/**
 * Shared helper for resolving CPT post IDs.
 *
 * Used by all modules that need to determine which post to render data for.
 * Falls back to the first published post of the given CPT when no
 * specific post context is available (e.g., in Theme Builder or VB preview).
 *
 * @package LeadersPath\Modules\Shared
 * @since 0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\Shared;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Post ID helper for LeadersPath CPT modules.
 *
 * @since 0.4.0
 */
class PostIdHelper {

	/**
	 * Get the current post ID for a given CPT, with fallback.
	 *
	 * Checks if the currently queried object is the expected CPT.
	 * If not (e.g., in VB/REST context), returns the first published
	 * post of that CPT as sample data.
	 *
	 * @since 0.4.0
	 *
	 * @param string $cpt_slug CPT slug without the `leaderspath_` prefix
	 *                         (e.g., 'activity', 'lesson', 'course').
	 * @return int Post ID, or 0 if no posts exist.
	 */
	public static function get_post_id( string $cpt_slug ): int {
		$post_id   = get_queried_object_id();
		$post_type = $post_id ? get_post_type( $post_id ) : '';

		// If we have a valid post of the expected CPT, use it.
		if ( $post_id && "leaderspath_{$cpt_slug}" === $post_type ) {
			return (int) $post_id;
		}

		// Fallback for VB/REST context: get first published post as sample data.
		$sample = get_posts(
			[
				'post_type'      => "leaderspath_{$cpt_slug}",
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		return ! empty( $sample ) ? (int) $sample[0]->ID : 0;
	}
}
