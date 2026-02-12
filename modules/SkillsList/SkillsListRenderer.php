<?php
/**
 * Core renderer for Skills List module.
 *
 * Pure data access and HTML generation — no Divi dependencies.
 * Reads the chatbot_skills ACF relationship and returns card grid HTML.
 *
 * @package LeadersPath\Modules\SkillsList
 * @since   0.5.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\SkillsList;

use LeadersPath\Modules\Shared\PostIdHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Skills List core renderer.
 *
 * @since 0.5.0
 */
class SkillsListRenderer {

	/**
	 * Render the skills list HTML.
	 *
	 * @since 0.5.0
	 *
	 * @param array $options {
	 *     Optional. Rendering options.
	 *
	 *     @type bool   $show_heading      Whether to show the heading. Default true.
	 *     @type string $heading_text      Heading text. Default 'Skills'.
	 *     @type bool   $show_icon         Whether to show skill icon. Default true.
	 *     @type bool   $show_description  Whether to show description preview. Default true.
	 *     @type bool   $show_meta         Whether to show compatibility + version. Default true.
	 * }
	 * @return string HTML output, or empty string if no activity/skills found.
	 */
	public static function render( array $options = [] ): string {
		$options = wp_parse_args( $options, [
			'show_heading'     => true,
			'heading_text'     => __( 'Skills', 'leaderspath' ),
			'show_icon'        => true,
			'show_description' => true,
			'show_meta'        => true,
		] );

		$post_id = PostIdHelper::get_post_id( 'activity' );

		if ( ! $post_id ) {
			return '';
		}

		$skills = self::get_data( $post_id );

		return self::build_html( $skills, $options );
	}

	/**
	 * Read skill data from the ACF relationship field.
	 *
	 * @since 0.5.0
	 *
	 * @param int $post_id Activity post ID.
	 * @return array[] Array of skill data arrays.
	 */
	public static function get_data( int $post_id ): array {
		$skill_ids = get_field( 'chatbot_skills', $post_id );

		if ( ! is_array( $skill_ids ) || empty( $skill_ids ) ) {
			return [];
		}

		$skills = [];
		foreach ( $skill_ids as $skill_id ) {
			$post = get_post( $skill_id );
			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}

			$description   = (string) get_field( 'skill_description', $skill_id );
			$compatibility = (string) get_field( 'skill_compatibility', $skill_id );
			$version       = (string) get_field( 'skill_version', $skill_id );

			$skills[] = [
				'id'            => $skill_id,
				'title'         => get_the_title( $skill_id ),
				'description'   => $description,
				'compatibility' => $compatibility,
				'version'       => $version,
			];
		}

		return $skills;
	}

	/**
	 * Build semantic HTML from skill data.
	 *
	 * @since 0.5.0
	 *
	 * @param array[] $skills  Array of skill data arrays.
	 * @param array   $options Rendering options.
	 * @return string HTML output.
	 */
	private static function build_html( array $skills, array $options ): string {
		$heading = '';
		if ( $options['show_heading'] && '' !== $options['heading_text'] ) {
			$heading = sprintf(
				'<h3 class="leaderspath_skills_list__heading">%s</h3>',
				esc_html( $options['heading_text'] )
			);
		}

		if ( empty( $skills ) ) {
			return sprintf(
				'%s<p class="leaderspath_skills_list__empty">%s</p>',
				$heading,
				esc_html__( 'No skills configured for this activity.', 'leaderspath' )
			);
		}

		$cards = '';
		foreach ( $skills as $skill ) {
			$icon = '';
			if ( $options['show_icon'] ) {
				$icon = sprintf(
					'<div class="leaderspath_skills_list__card_icon">%s</div>',
					self::get_skill_icon()
				);
			}

			$title = sprintf(
				'<h4 class="leaderspath_skills_list__card_title">%s</h4>',
				esc_html( $skill['title'] )
			);

			$desc = '';
			if ( $options['show_description'] && '' !== $skill['description'] ) {
				$truncated = wp_trim_words( $skill['description'], 20, '&hellip;' );
				$desc = sprintf(
					'<p class="leaderspath_skills_list__card_desc">%s</p>',
					esc_html( $truncated )
				);
			}

			$meta = '';
			if ( $options['show_meta'] ) {
				$meta_parts = '';
				if ( '' !== $skill['compatibility'] ) {
					$meta_parts .= sprintf(
						'<span class="leaderspath_skills_list__compat">%s</span>',
						esc_html( $skill['compatibility'] )
					);
				}
				if ( '' !== $skill['version'] ) {
					$meta_parts .= sprintf(
						'<span class="leaderspath_skills_list__version">v%s</span>',
						esc_html( $skill['version'] )
					);
				}
				if ( '' !== $meta_parts ) {
					$meta = sprintf(
						'<div class="leaderspath_skills_list__card_meta">%s</div>',
						$meta_parts
					);
				}
			}

			$cards .= sprintf(
				'<li class="leaderspath_skills_list__item"><article class="leaderspath_skills_list__card">%s%s%s%s</article></li>',
				$icon,
				$title,
				$desc,
				$meta
			);
		}

		return sprintf(
			'%s<ul class="leaderspath_skills_list__grid">%s</ul>',
			$heading,
			$cards
		);
	}

	/**
	 * Get SVG icon markup for a skill.
	 *
	 * Uses a gear/cog icon to represent executable capabilities.
	 *
	 * @since 0.5.0
	 *
	 * @return string SVG markup.
	 */
	private static function get_skill_icon(): string {
		return '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 00.12-.61l-1.92-3.32a.488.488 0 00-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.44.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96a.49.49 0 00-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 00-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>';
	}
}
