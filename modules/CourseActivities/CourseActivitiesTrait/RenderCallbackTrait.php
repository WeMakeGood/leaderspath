<?php
/**
 * CourseActivities::render_callback()
 *
 * Displays Course activities from the course_activities relationship field.
 *
 * @package LeadersPath\Modules\CourseActivities
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\CourseActivities\CourseActivitiesTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// phpcs:disable ET.Sniffs.ValidVariableName.UsedPropertyNotSnakeCase -- WP uses snakeCase in \WP_Block_Parser_Block

use ET\Builder\Packages\Module\Module;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;
use LeadersPath\Modules\CourseActivities\CourseActivities;

/**
 * Render callback trait for CourseActivities.
 *
 * Displays activities for the current Course as an ordered list with links.
 *
 * @since 0.1.0
 */
trait RenderCallbackTrait {

	/**
	 * Get the current course post ID.
	 *
	 * Uses get_queried_object_id() for Theme Builder templates on frontend.
	 * Falls back to finding the first Course for VB preview context.
	 *
	 * @since 0.1.0
	 *
	 * @return int Post ID, or 0 if not found.
	 */
	public static function get_course_id(): int {
		$post_id   = get_queried_object_id();
		$post_type = $post_id ? get_post_type( $post_id ) : '';

		// If we have a valid Course, use it.
		if ( $post_id && 'leaderspath_course' === $post_type ) {
			return (int) $post_id;
		}

		// Fallback for VB/REST context: get first Course as sample data.
		$sample_courses = get_posts(
			[
				'post_type'      => 'leaderspath_course',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		if ( ! empty( $sample_courses ) ) {
			return (int) $sample_courses[0]->ID;
		}

		return 0;
	}

	/**
	 * Get course activities from ACF relationship field.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, array{id: int, title: string, url: string}> Array of activity data.
	 */
	public static function get_activities(): array {
		$post_id    = self::get_course_id();
		$activities = [];

		if ( ! $post_id ) {
			return $activities;
		}

		$related_activities = get_field( 'course_activities', $post_id );

		if ( ! is_array( $related_activities ) || empty( $related_activities ) ) {
			return $activities;
		}

		foreach ( $related_activities as $activity ) {
			if ( ! $activity instanceof \WP_Post ) {
				continue;
			}

			$activities[] = [
				'id'    => $activity->ID,
				'title' => get_the_title( $activity ),
				'url'   => get_permalink( $activity ),
			];
		}

		return $activities;
	}

	/**
	 * Course Activities module render callback which outputs server side rendered HTML.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs    Block attributes that were saved by VB.
	 * @param string         $content  Block content.
	 * @param \WP_Block      $block    Parsed block object that being rendered.
	 * @param ModuleElements $elements ModuleElements instance.
	 *
	 * @return string HTML rendered of Course Activities module.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ): string {
		$activities = self::get_activities();

		// Render title using elements->render().
		$title = $elements->render(
			[
				'attrName' => 'title',
			]
		);

		// Build content based on whether we have activities.
		if ( empty( $activities ) ) {
			$items_html = self::render_empty_state( $attrs, $elements );
		} else {
			$items_html = self::render_activities_list( $attrs, $elements, $activities );
		}

		// Content container.
		$content_html = HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-course-activities__content',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $title . $items_html,
			]
		);

		$parent       = BlockParserStore::get_parent( $block->parsed_block['id'], $block->parsed_block['storeInstance'] );
		$parent_attrs = $parent->attrs ?? [];

		return Module::render(
			[
				// FE only.
				'orderIndex'         => $block->parsed_block['orderIndex'],
				'storeInstance'      => $block->parsed_block['storeInstance'],

				// VB equivalent.
				'attrs'              => $attrs,
				'elements'           => $elements,
				'id'                 => $block->parsed_block['id'],
				'name'               => $block->block_type->name,
				'moduleCategory'     => $block->block_type->category,
				'classnamesFunction' => [ CourseActivities::class, 'module_classnames' ],
				'stylesComponent'    => [ CourseActivities::class, 'module_styles' ],
				'parentAttrs'        => $parent_attrs,
				'parentId'           => $parent->id ?? '',
				'parentName'         => $parent->blockName ?? '',
				'children'           => [
					ElementComponents::component(
						[
							'attrs'         => $attrs['module']['decoration'] ?? [],
							'id'            => $block->parsed_block['id'],
							'orderIndex'    => $block->parsed_block['orderIndex'],
							'storeInstance' => $block->parsed_block['storeInstance'],
						]
					),
					$content_html,
				],
			]
		);
	}

	/**
	 * Render the empty state message.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs    Module attributes.
	 * @param ModuleElements $elements ModuleElements instance.
	 *
	 * @return string HTML for empty state.
	 */
	private static function render_empty_state( array $attrs, $elements ): string {
		$empty_message = $elements->render(
			[
				'attrName' => 'emptyState',
			]
		);

		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-course-activities__empty',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $empty_message,
			]
		);
	}

	/**
	 * Render the activities list.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs      Module attributes.
	 * @param ModuleElements $elements   ModuleElements instance.
	 * @param array          $activities Array of activity data.
	 *
	 * @return string HTML for activities list.
	 */
	private static function render_activities_list( array $attrs, $elements, array $activities ): string {
		$items_html = '';

		foreach ( $activities as $index => $activity ) {
			$number     = $index + 1;
			$link_html  = HTMLUtility::render(
				[
					'tag'               => 'a',
					'tagEscaped'        => true,
					'attributes'        => [
						'class' => 'leaderspath-course-activities__link',
						'href'  => esc_url( $activity['url'] ),
					],
					'childrenSanitizer' => 'esc_html',
					'children'          => $activity['title'],
				]
			);

			$number_html = HTMLUtility::render(
				[
					'tag'               => 'span',
					'tagEscaped'        => true,
					'attributes'        => [
						'class' => 'leaderspath-course-activities__number',
					],
					'childrenSanitizer' => 'esc_html',
					'children'          => (string) $number,
				]
			);

			$items_html .= HTMLUtility::render(
				[
					'tag'               => 'li',
					'tagEscaped'        => true,
					'attributes'        => [
						'class' => 'leaderspath-course-activities__item',
					],
					'childrenSanitizer' => 'et_core_esc_previously',
					'children'          => $number_html . $link_html,
				]
			);
		}

		return HTMLUtility::render(
			[
				'tag'               => 'ol',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-course-activities__list',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $items_html,
			]
		);
	}
}
