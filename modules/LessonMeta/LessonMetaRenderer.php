<?php
/**
 * Core renderer for Lesson Meta module.
 *
 * Pure data access and HTML generation — no Divi dependencies.
 * Reads ACF fields from the current lesson and returns semantic HTML.
 *
 * @package LeadersPath\Modules\LessonMeta
 * @since   0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonMeta;

use LeadersPath\Modules\Shared\PostIdHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Lesson Meta core renderer.
 *
 * @since 0.4.0
 */
class LessonMetaRenderer {

	/**
	 * Render the lesson meta HTML.
	 *
	 * @since 0.4.0
	 *
	 * @param array $options {
	 *     Optional. Rendering options for show/hide and label overrides.
	 *
	 *     @type bool   $show_duration       Whether to show duration. Default true.
	 *     @type bool   $show_difficulty      Whether to show difficulty. Default true.
	 *     @type bool   $show_activity_count  Whether to show activity count. Default true.
	 *     @type string $duration_label       Label text for duration. Default 'Duration'.
	 *     @type string $difficulty_label     Label text for difficulty. Default 'Difficulty'.
	 *     @type string $activities_label     Label text for activities. Default 'Activities'.
	 * }
	 * @return string HTML output, or empty string if no lesson found.
	 */
	public static function render( array $options = [] ): string {
		$options = wp_parse_args( $options, [
			'show_duration'       => true,
			'show_difficulty'     => true,
			'show_activity_count' => true,
			'duration_label'      => __( 'Duration', 'leaderspath' ),
			'difficulty_label'    => __( 'Difficulty', 'leaderspath' ),
			'activities_label'    => __( 'Activities', 'leaderspath' ),
		] );

		$post_id = PostIdHelper::get_post_id( 'lesson' );

		if ( ! $post_id ) {
			return '';
		}

		$data = self::get_data( $post_id );

		return self::build_html( $data, $options );
	}

	/**
	 * Read ACF fields for a lesson post.
	 *
	 * @since 0.4.0
	 *
	 * @param int $post_id Lesson post ID.
	 * @return array {
	 *     @type string $duration       Total facilitation time (e.g., "90 minutes").
	 *     @type string $difficulty      Difficulty level (beginner|intermediate|advanced).
	 *     @type int    $activity_count  Number of linked activities.
	 * }
	 */
	public static function get_data( int $post_id ): array {
		$duration   = (string) get_field( 'lesson_total_duration', $post_id );
		$difficulty = (string) get_field( 'lesson_difficulty', $post_id );
		$activities = get_field( 'lesson_activities', $post_id );

		return [
			'duration'       => $duration,
			'difficulty'     => $difficulty,
			'activity_count' => is_array( $activities ) ? count( $activities ) : 0,
		];
	}

	/**
	 * Build semantic HTML from lesson data.
	 *
	 * @since 0.4.0
	 *
	 * @param array $data    Lesson data from get_data().
	 * @param array $options Rendering options (show/hide flags + label overrides).
	 * @return string HTML output.
	 */
	private static function build_html( array $data, array $options ): string {
		$items = '';

		if ( $options['show_duration'] && '' !== $data['duration'] ) {
			$items .= sprintf(
				'<dt class="leaderspath_lesson_meta__label">%s</dt><dd class="leaderspath_lesson_meta__duration">%s</dd>',
				esc_html( $options['duration_label'] ),
				esc_html( $data['duration'] )
			);
		}

		if ( $options['show_difficulty'] && '' !== $data['difficulty'] ) {
			$level_label = self::difficulty_label( $data['difficulty'] );
			$items .= sprintf(
				'<dt class="leaderspath_lesson_meta__label">%s</dt><dd class="leaderspath_lesson_meta__difficulty" data-level="%s">%s</dd>',
				esc_html( $options['difficulty_label'] ),
				esc_attr( $data['difficulty'] ),
				esc_html( $level_label )
			);
		}

		if ( $options['show_activity_count'] && $data['activity_count'] > 0 ) {
			$items .= sprintf(
				'<dt class="leaderspath_lesson_meta__label">%s</dt><dd class="leaderspath_lesson_meta__activities">%s</dd>',
				esc_html( $options['activities_label'] ),
				esc_html(
					sprintf(
						/* translators: %d: number of activities */
						_n( '%d Activity', '%d Activities', $data['activity_count'], 'leaderspath' ),
						$data['activity_count']
					)
				)
			);
		}

		if ( '' === $items ) {
			return '';
		}

		return sprintf(
			'<dl class="leaderspath_lesson_meta__list">%s</dl>',
			$items
		);
	}

	/**
	 * Get the human-readable label for a difficulty level.
	 *
	 * @since 0.4.0
	 *
	 * @param string $value Raw difficulty value.
	 * @return string Translated label.
	 */
	private static function difficulty_label( string $value ): string {
		$labels = [
			'beginner'     => __( 'Beginner', 'leaderspath' ),
			'intermediate' => __( 'Intermediate', 'leaderspath' ),
			'advanced'     => __( 'Advanced', 'leaderspath' ),
		];

		return $labels[ $value ] ?? $value;
	}
}
