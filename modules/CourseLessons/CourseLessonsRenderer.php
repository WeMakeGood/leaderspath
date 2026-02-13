<?php
/**
 * Core renderer for Course Lessons module.
 *
 * Pure data access and HTML generation — no Divi dependencies.
 * Reads the course_lessons ACF relationship and returns card HTML.
 *
 * @package LeadersPath\Modules\CourseLessons
 * @since   0.5.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\CourseLessons;

use LeadersPath\Modules\Shared\PostIdHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Course Lessons core renderer.
 *
 * @since 0.5.0
 */
class CourseLessonsRenderer {

	/**
	 * Render the course lessons HTML.
	 *
	 * @since 0.5.0
	 *
	 * @param array $options {
	 *     Optional. Rendering options.
	 *
	 *     @type bool   $show_heading       Whether to show the heading. Default true.
	 *     @type string $heading_text       Heading text. Default 'Lessons'.
	 *     @type bool   $show_number        Whether to show order numbers. Default true.
	 *     @type bool   $show_duration      Whether to show duration per lesson. Default true.
	 *     @type bool   $show_activity_count Whether to show activity count. Default true.
	 *     @type bool   $show_excerpt       Whether to show excerpt per lesson. Default true.
	 *     @type bool   $show_prerequisites Whether to show prerequisites section. Default false.
	 * }
	 * @return string HTML output, or empty string if no course/lessons found.
	 */
	public static function render( array $options = [] ): string {
		$options = wp_parse_args( $options, [
			'show_heading'        => true,
			'heading_text'        => __( 'Lessons', 'leaderspath' ),
			'show_number'         => true,
			'show_duration'       => true,
			'show_activity_count' => true,
			'show_excerpt'        => true,
			'show_prerequisites'  => false,
		] );

		$post_id = PostIdHelper::get_post_id( 'course' );

		if ( ! $post_id ) {
			return '';
		}

		$lessons       = self::get_data( $post_id );
		$prerequisites = $options['show_prerequisites']
			? self::get_prerequisites( $post_id )
			: [];

		return self::build_html( $lessons, $prerequisites, $options );
	}

	/**
	 * Read lesson data from the ACF relationship field.
	 *
	 * @since 0.5.0
	 *
	 * @param int $post_id Course post ID.
	 * @return array[] Array of lesson data arrays.
	 */
	public static function get_data( int $post_id ): array {
		$lesson_ids = get_field( 'course_lessons', $post_id );

		if ( ! is_array( $lesson_ids ) || empty( $lesson_ids ) ) {
			return [];
		}

		$lessons = [];
		foreach ( $lesson_ids as $lesson_id ) {
			$post = get_post( $lesson_id );
			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}

			$duration       = (int) get_field( 'lesson_total_duration', $lesson_id );
			$activities     = get_field( 'lesson_activities', $lesson_id );
			$activity_count = is_array( $activities ) ? count( $activities ) : 0;

			$lessons[] = [
				'id'             => $lesson_id,
				'title'          => get_the_title( $lesson_id ),
				'permalink'      => get_permalink( $lesson_id ),
				'duration'       => $duration,
				'activity_count' => $activity_count,
				'excerpt'        => get_the_excerpt( $post ),
			];
		}

		return $lessons;
	}

	/**
	 * Read prerequisite course data.
	 *
	 * @since 0.5.0
	 *
	 * @param int $post_id Course post ID.
	 * @return array[] Array of prerequisite course data arrays.
	 */
	public static function get_prerequisites( int $post_id ): array {
		$prereq_ids = get_field( 'course_prerequisites', $post_id );

		if ( ! is_array( $prereq_ids ) || empty( $prereq_ids ) ) {
			return [];
		}

		$prereqs = [];
		foreach ( $prereq_ids as $prereq_id ) {
			$post = get_post( $prereq_id );
			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}

			$prereqs[] = [
				'id'        => $prereq_id,
				'title'     => get_the_title( $prereq_id ),
				'permalink' => get_permalink( $prereq_id ),
			];
		}

		return $prereqs;
	}

	/**
	 * Build semantic HTML from lesson data.
	 *
	 * @since 0.5.0
	 *
	 * @param array[] $lessons       Array of lesson data arrays.
	 * @param array[] $prerequisites Array of prerequisite course data arrays.
	 * @param array   $options       Rendering options.
	 * @return string HTML output.
	 */
	private static function build_html( array $lessons, array $prerequisites, array $options ): string {
		if ( empty( $lessons ) ) {
			return sprintf(
				'<p class="leaderspath_course_lessons__empty">%s</p>',
				esc_html__( 'No lessons assigned.', 'leaderspath' )
			);
		}

		$heading = '';
		if ( $options['show_heading'] && '' !== $options['heading_text'] ) {
			$heading = sprintf(
				'<h3 class="leaderspath_course_lessons__heading">%s</h3>',
				esc_html( $options['heading_text'] )
			);
		}

		$items = '';
		foreach ( $lessons as $index => $lesson ) {
			$number = '';
			if ( $options['show_number'] ) {
				$number = sprintf(
					'<span class="leaderspath_course_lessons__number">%d</span>',
					$index + 1
				);
			}

			$title = sprintf(
				'<h4 class="leaderspath_course_lessons__title"><a href="%s">%s</a></h4>',
				esc_url( $lesson['permalink'] ),
				esc_html( $lesson['title'] )
			);

			$meta_parts = [];

			if ( $options['show_duration'] && $lesson['duration'] > 0 ) {
				$meta_parts[] = sprintf(
					'<span class="leaderspath_course_lessons__duration">%s</span>',
					esc_html(
						sprintf(
							/* translators: %d: number of minutes */
							_n( '%d minute', '%d minutes', $lesson['duration'], 'leaderspath' ),
							$lesson['duration']
						)
					)
				);
			}

			if ( $options['show_activity_count'] && $lesson['activity_count'] > 0 ) {
				$meta_parts[] = sprintf(
					'<span class="leaderspath_course_lessons__activity-count">%s</span>',
					esc_html(
						sprintf(
							/* translators: %d: number of activities */
							_n( '%d activity', '%d activities', $lesson['activity_count'], 'leaderspath' ),
							$lesson['activity_count']
						)
					)
				);
			}

			$meta = '';
			if ( ! empty( $meta_parts ) ) {
				$meta = sprintf(
					'<div class="leaderspath_course_lessons__meta">%s</div>',
					implode( '', $meta_parts )
				);
			}

			$excerpt = '';
			if ( $options['show_excerpt'] && '' !== $lesson['excerpt'] ) {
				$excerpt = sprintf(
					'<p class="leaderspath_course_lessons__excerpt">%s</p>',
					esc_html( $lesson['excerpt'] )
				);
			}

			$items .= sprintf(
				'<li class="leaderspath_course_lessons__item"><article>%s%s%s%s</article></li>',
				$number,
				$title,
				$meta,
				$excerpt
			);
		}

		$prereqs_html = '';
		if ( ! empty( $prerequisites ) ) {
			$prereq_items = '';
			foreach ( $prerequisites as $prereq ) {
				$prereq_items .= sprintf(
					'<li><a href="%s">%s</a></li>',
					esc_url( $prereq['permalink'] ),
					esc_html( $prereq['title'] )
				);
			}
			$prereqs_html = sprintf(
				'<section class="leaderspath_course_lessons__prereqs"><h4 class="leaderspath_course_lessons__prereqs-heading">%s</h4><ul class="leaderspath_course_lessons__prereqs-list">%s</ul></section>',
				esc_html__( 'Prerequisites', 'leaderspath' ),
				$prereq_items
			);
		}

		return sprintf(
			'%s<ul class="leaderspath_course_lessons__list">%s</ul>%s',
			$heading,
			$items,
			$prereqs_html
		);
	}
}
