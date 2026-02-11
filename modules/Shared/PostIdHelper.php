<?php
/**
 * Shared helper for resolving CPT post IDs in Divi 5 module context.
 *
 * Handles three rendering contexts:
 * 1. Frontend singular view — get_the_ID() returns the viewed post.
 * 2. Theme Builder layout — Divi overrides globals; ET_Post_Stack restores
 *    the true queried post, and ET_Theme_Builder_Layout detects TB context.
 * 3. VB / REST preview — No real post context; falls back to sample data.
 *
 * @package LeadersPath\Modules\Shared
 * @since   0.4.0
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
	 * Resolution order (mirrors Divi's DynamicContentPosts pattern):
	 * 1. Theme Builder + singular → ET_Post_Stack::get_main_post_id()
	 * 2. Regular singular         → get_the_ID()
	 * 3. Fallback                 → first published post of CPT (sample data)
	 *
	 * @since 0.4.0
	 *
	 * @param string $cpt_slug CPT slug without the `leaderspath_` prefix
	 *                         (e.g., 'activity', 'lesson', 'course').
	 * @return int Post ID, or 0 if no posts exist.
	 */
	public static function get_post_id( string $cpt_slug ): int {
		$post_id = 0;

		// Theme Builder context: Divi pushes layout post onto globals,
		// ET_Post_Stack reads from $wp_query->post to get the real one.
		if (
			class_exists( '\ET_Theme_Builder_Layout' )
			&& \ET_Theme_Builder_Layout::is_theme_builder_layout()
			&& is_singular()
		) {
			$post_id = class_exists( '\ET_Post_Stack' )
				? \ET_Post_Stack::get_main_post_id()
				: 0;
		} else {
			// Regular singular context — globals are trustworthy.
			$post_id = (int) get_the_ID();
		}

		$post_type = $post_id ? get_post_type( $post_id ) : '';

		// If we have a valid post of the expected CPT, use it.
		if ( $post_id && "leaderspath_{$cpt_slug}" === $post_type ) {
			return $post_id;
		}

		// Fallback for VB/REST context: first published post as sample data.
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
