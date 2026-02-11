<?php
/**
 * Core renderer for Lesson Objectives module.
 *
 * Pure data access and HTML generation — no Divi dependencies.
 * Reads the lesson_objectives ACF repeater and returns semantic HTML.
 *
 * @package LeadersPath\Modules\LessonObjectives
 * @since   0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonObjectives;

use LeadersPath\Modules\Shared\PostIdHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Lesson Objectives core renderer.
 *
 * @since 0.4.0
 */
class LessonObjectivesRenderer {

	/**
	 * Render the lesson objectives HTML.
	 *
	 * @since 0.4.0
	 *
	 * @param array $options {
	 *     Optional. Rendering options.
	 *
	 *     @type bool   $show_heading  Whether to show the heading. Default true.
	 *     @type string $heading_text  Heading text. Default 'Learning Objectives'.
	 * }
	 * @return string HTML output, or empty string if no lesson/objectives found.
	 */
	public static function render( array $options = [] ): string {
		$options = wp_parse_args( $options, [
			'show_heading' => true,
			'heading_text' => __( 'Learning Objectives', 'leaderspath' ),
		] );

		$post_id = PostIdHelper::get_post_id( 'lesson' );

		if ( ! $post_id ) {
			return '';
		}

		$objectives = self::get_data( $post_id );

		return self::build_html( $objectives, $options );
	}

	/**
	 * Read objectives from the ACF repeater.
	 *
	 * @since 0.4.0
	 *
	 * @param int $post_id Lesson post ID.
	 * @return string[] Array of objective text strings.
	 */
	public static function get_data( int $post_id ): array {
		$rows = get_field( 'lesson_objectives', $post_id );

		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return [];
		}

		$objectives = [];
		foreach ( $rows as $row ) {
			$text = trim( (string) ( $row['objective'] ?? '' ) );
			if ( '' !== $text ) {
				$objectives[] = $text;
			}
		}

		return $objectives;
	}

	/**
	 * Build semantic HTML from objectives data.
	 *
	 * @since 0.4.0
	 *
	 * @param string[] $objectives Array of objective strings.
	 * @param array    $options    Rendering options.
	 * @return string HTML output.
	 */
	private static function build_html( array $objectives, array $options ): string {
		if ( empty( $objectives ) ) {
			return sprintf(
				'<p class="leaderspath_lesson_objectives__empty">%s</p>',
				esc_html__( 'No objectives defined.', 'leaderspath' )
			);
		}

		$heading = '';
		if ( $options['show_heading'] && '' !== $options['heading_text'] ) {
			$heading = sprintf(
				'<h3 class="leaderspath_lesson_objectives__heading">%s</h3>',
				esc_html( $options['heading_text'] )
			);
		}

		$items = '';
		foreach ( $objectives as $text ) {
			$items .= sprintf(
				'<li class="leaderspath_lesson_objectives__item">%s</li>',
				esc_html( $text )
			);
		}

		return sprintf(
			'%s<ol class="leaderspath_lesson_objectives__list">%s</ol>',
			$heading,
			$items
		);
	}
}
