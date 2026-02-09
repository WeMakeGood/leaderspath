<?php
/**
 * LessonObjectives::render_callback()
 *
 * Displays Lesson learning objectives from the lesson_objectives repeater field.
 *
 * @package LeadersPath\Modules\LessonObjectives
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonObjectives\LessonObjectivesTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// phpcs:disable ET.Sniffs.ValidVariableName.UsedPropertyNotSnakeCase -- WP uses snakeCase in \WP_Block_Parser_Block

use ET\Builder\Packages\Module\Module;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;
use LeadersPath\Modules\LessonObjectives\LessonObjectives;

/**
 * Render callback trait for LessonObjectives.
 *
 * Displays learning objectives for the current Lesson as a list.
 *
 * @since 0.1.0
 */
trait RenderCallbackTrait {

	/**
	 * Get the current lesson post ID.
	 *
	 * Uses get_queried_object_id() for Theme Builder templates on frontend.
	 * Falls back to finding the first Lesson for VB preview context.
	 *
	 * @since 0.1.0
	 *
	 * @return int Post ID, or 0 if not found.
	 */
	public static function get_lesson_id(): int {
		$post_id   = get_queried_object_id();
		$post_type = $post_id ? get_post_type( $post_id ) : '';

		// If we have a valid Lesson, use it.
		if ( $post_id && 'leaderspath_lesson' === $post_type ) {
			return (int) $post_id;
		}

		// Fallback for VB/REST context: get first Lesson as sample data.
		$sample_lessons = get_posts(
			[
				'post_type'      => 'leaderspath_lesson',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		if ( ! empty( $sample_lessons ) ) {
			return (int) $sample_lessons[0]->ID;
		}

		return 0;
	}

	/**
	 * Get lesson objectives from ACF repeater field.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, string> Array of objective strings.
	 */
	public static function get_objectives(): array {
		$post_id    = self::get_lesson_id();
		$objectives = [];

		if ( ! $post_id ) {
			return $objectives;
		}

		$repeater = get_field( 'lesson_objectives', $post_id );

		if ( ! is_array( $repeater ) ) {
			return $objectives;
		}

		foreach ( $repeater as $row ) {
			if ( ! empty( $row['objective'] ) ) {
				$objectives[] = $row['objective'];
			}
		}

		return $objectives;
	}

	/**
	 * Lesson Objectives module render callback which outputs server side rendered HTML.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs    Block attributes that were saved by VB.
	 * @param string         $content  Block content.
	 * @param \WP_Block      $block    Parsed block object that being rendered.
	 * @param ModuleElements $elements ModuleElements instance.
	 *
	 * @return string HTML rendered of Lesson Objectives module.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ): string {
		$objectives = self::get_objectives();

		// Render title using elements->render().
		$title = $elements->render(
			[
				'attrName' => 'title',
			]
		);

		// Build content based on whether we have objectives.
		if ( empty( $objectives ) ) {
			$items_html = self::render_empty_state( $attrs, $elements );
		} else {
			$items_html = self::render_objectives_list( $attrs, $elements, $objectives );
		}

		// Content container.
		$content_html = HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-lesson-objectives__content',
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
				'classnamesFunction' => [ LessonObjectives::class, 'module_classnames' ],
				'stylesComponent'    => [ LessonObjectives::class, 'module_styles' ],
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
					'class' => 'leaderspath-lesson-objectives__empty',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $empty_message,
			]
		);
	}

	/**
	 * Render the objectives list.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs      Module attributes.
	 * @param ModuleElements $elements   ModuleElements instance.
	 * @param array          $objectives Array of objective strings.
	 *
	 * @return string HTML for objectives list.
	 */
	private static function render_objectives_list( array $attrs, $elements, array $objectives ): string {
		$items_html = '';

		foreach ( $objectives as $objective ) {
			$items_html .= HTMLUtility::render(
				[
					'tag'               => 'li',
					'tagEscaped'        => true,
					'attributes'        => [
						'class' => 'leaderspath-lesson-objectives__item',
					],
					'childrenSanitizer' => 'esc_html',
					'children'          => $objective,
				]
			);
		}

		return HTMLUtility::render(
			[
				'tag'               => 'ul',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-lesson-objectives__list',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $items_html,
			]
		);
	}
}
