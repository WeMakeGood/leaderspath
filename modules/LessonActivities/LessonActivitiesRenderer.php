<?php
/**
 * Core renderer for Lesson Activities module.
 *
 * Pure data access and HTML generation — no Divi dependencies.
 * Reads the lesson_activities ACF relationship and returns card HTML.
 *
 * @package LeadersPath\Modules\LessonActivities
 * @since   0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonActivities;

use LeadersPath\Modules\Shared\PostIdHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Lesson Activities core renderer.
 *
 * @since 0.4.0
 */
class LessonActivitiesRenderer {

	/**
	 * Render the lesson activities HTML.
	 *
	 * @since 0.4.0
	 *
	 * @param array $options {
	 *     Optional. Rendering options.
	 *
	 *     @type bool   $show_heading    Whether to show the heading. Default true.
	 *     @type string $heading_text    Heading text. Default 'Activities'.
	 *     @type bool   $show_duration   Whether to show duration per activity. Default true.
	 *     @type bool   $show_excerpt    Whether to show excerpt per activity. Default true.
	 *     @type bool   $show_number     Whether to show order numbers. Default true.
	 * }
	 * @return string HTML output, or empty string if no lesson/activities found.
	 */
	public static function render( array $options = [] ): string {
		$options = wp_parse_args( $options, [
			'show_heading'  => true,
			'heading_text'  => __( 'Activities', 'leaderspath' ),
			'show_duration' => true,
			'show_excerpt'  => true,
			'show_number'   => true,
		] );

		$post_id = PostIdHelper::get_post_id( 'lesson' );

		if ( ! $post_id ) {
			return '';
		}

		$activities = self::get_data( $post_id );

		return self::build_html( $activities, $options );
	}

	/**
	 * Read activity data from the ACF relationship field.
	 *
	 * @since 0.4.0
	 *
	 * @param int $post_id Lesson post ID.
	 * @return array[] Array of activity data arrays.
	 */
	public static function get_data( int $post_id ): array {
		$activity_ids = get_field( 'lesson_activities', $post_id );

		if ( ! is_array( $activity_ids ) || empty( $activity_ids ) ) {
			return [];
		}

		$activities = [];
		foreach ( $activity_ids as $activity_id ) {
			$post = get_post( $activity_id );
			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}

			$duration = (int) get_field( 'activity_duration', $activity_id );

			$activities[] = [
				'id'        => $activity_id,
				'title'     => get_the_title( $activity_id ),
				'permalink' => get_permalink( $activity_id ),
				'duration'  => $duration,
				'excerpt'   => get_the_excerpt( $post ),
			];
		}

		return $activities;
	}

	/**
	 * Build semantic HTML from activity data.
	 *
	 * @since 0.4.0
	 *
	 * @param array[] $activities Array of activity data arrays.
	 * @param array   $options    Rendering options.
	 * @return string HTML output.
	 */
	private static function build_html( array $activities, array $options ): string {
		if ( empty( $activities ) ) {
			return sprintf(
				'<p class="leaderspath_lesson_activities__empty">%s</p>',
				esc_html__( 'No activities assigned.', 'leaderspath' )
			);
		}

		$heading = '';
		if ( $options['show_heading'] && '' !== $options['heading_text'] ) {
			$heading = sprintf(
				'<h3 class="leaderspath_lesson_activities__heading">%s</h3>',
				esc_html( $options['heading_text'] )
			);
		}

		$items = '';
		foreach ( $activities as $index => $activity ) {
			$number = '';
			if ( $options['show_number'] ) {
				$number = sprintf(
					'<span class="leaderspath_lesson_activities__number">%d</span>',
					$index + 1
				);
			}

			$title = sprintf(
				'<h4 class="leaderspath_lesson_activities__title"><a href="%s">%s</a></h4>',
				esc_url( $activity['permalink'] ),
				esc_html( $activity['title'] )
			);

			$duration = '';
			if ( $options['show_duration'] && $activity['duration'] > 0 ) {
				$duration = sprintf(
					'<span class="leaderspath_lesson_activities__duration">%s</span>',
					esc_html(
						sprintf(
							/* translators: %d: number of minutes */
							_n( '%d minute', '%d minutes', $activity['duration'], 'leaderspath' ),
							$activity['duration']
						)
					)
				);
			}

			$excerpt = '';
			if ( $options['show_excerpt'] && '' !== $activity['excerpt'] ) {
				$excerpt = sprintf(
					'<p class="leaderspath_lesson_activities__excerpt">%s</p>',
					esc_html( $activity['excerpt'] )
				);
			}

			$items .= sprintf(
				'<li class="leaderspath_lesson_activities__item"><article>%s%s%s%s</article></li>',
				$number,
				$title,
				$duration,
				$excerpt
			);
		}

		return sprintf(
			'%s<ul class="leaderspath_lesson_activities__list">%s</ul>',
			$heading,
			$items
		);
	}
}
