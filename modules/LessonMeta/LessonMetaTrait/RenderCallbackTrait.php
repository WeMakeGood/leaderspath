<?php
/**
 * LessonMeta::render_callback()
 *
 * Displays Lesson metadata (duration, difficulty, activity count).
 * Uses ServerSideRender pattern - same PHP renders for both VB and frontend.
 *
 * @package LeadersPath\Modules\LessonMeta
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonMeta\LessonMetaTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// phpcs:disable ET.Sniffs.ValidVariableName.UsedPropertyNotSnakeCase -- WP uses snakeCase in \WP_Block_Parser_Block

use ET\Builder\Packages\Module\Module;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;
use LeadersPath\Modules\LessonMeta\LessonMeta;

/**
 * Render callback trait for LessonMeta.
 *
 * Displays metadata for the current Lesson:
 * - Duration (from ACF field)
 * - Difficulty level (beginner/intermediate/advanced)
 * - Activity count (from lesson_activities relationship)
 *
 * @since 0.1.0
 */
trait RenderCallbackTrait {

	/**
	 * Difficulty display names mapping.
	 *
	 * @var array<string, string>
	 */
	private static array $difficulty_names = [
		'beginner'     => 'Beginner',
		'intermediate' => 'Intermediate',
		'advanced'     => 'Advanced',
	];

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
	 * Get lesson metadata from ACF fields.
	 *
	 * @since 0.1.0
	 *
	 * @return array{duration: string, difficulty: string, activity_count: int, lesson_title: string}
	 */
	public static function get_lesson_meta(): array {
		$post_id = self::get_lesson_id();

		if ( ! $post_id ) {
			return [
				'duration'       => '',
				'difficulty'     => '',
				'activity_count' => 0,
				'lesson_title'   => '',
			];
		}

		$activities = get_field( 'lesson_activities', $post_id );

		return [
			'duration'       => get_field( 'lesson_total_duration', $post_id ) ?: '',
			'difficulty'     => get_field( 'lesson_difficulty', $post_id ) ?: '',
			'activity_count' => is_array( $activities ) ? count( $activities ) : 0,
			'lesson_title'   => get_the_title( $post_id ),
		];
	}

	/**
	 * Lesson Meta module render callback which outputs server side rendered HTML.
	 *
	 * This is used for BOTH VB preview (via ServerSideRender) and frontend rendering.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs    Block attributes that were saved by VB.
	 * @param string         $content  Block content.
	 * @param \WP_Block      $block    Parsed block object that being rendered.
	 * @param ModuleElements $elements ModuleElements instance.
	 *
	 * @return string HTML rendered of Lesson Meta module.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ): string {
		$lesson_meta = self::get_lesson_meta();

		// Render title using elements->render().
		$title = $elements->render(
			[
				'attrName' => 'title',
			]
		);

		// Build sections based on visibility toggles.
		$duration_section   = self::render_duration( $attrs, $elements, $lesson_meta );
		$difficulty_section = self::render_difficulty( $attrs, $elements, $lesson_meta );
		$activities_section = self::render_activity_count( $attrs, $elements, $lesson_meta );

		// Content container.
		$content_html = HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__content',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $title . $duration_section . $difficulty_section . $activities_section,
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
				'classnamesFunction' => [ LessonMeta::class, 'module_classnames' ],
				'stylesComponent'    => [ LessonMeta::class, 'module_styles' ],
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
	 * Render the duration section.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs       Module attributes.
	 * @param ModuleElements $elements    ModuleElements instance.
	 * @param array          $lesson_meta Lesson metadata.
	 *
	 * @return string HTML for duration section.
	 */
	private static function render_duration( array $attrs, $elements, array $lesson_meta ): string {
		$show_duration = $attrs['duration']['advanced']['show']['desktop']['value'] ?? 'on';

		if ( 'on' !== $show_duration || empty( $lesson_meta['duration'] ) ) {
			return '';
		}

		$label = $elements->render(
			[
				'attrName' => 'durationLabel',
			]
		);

		$value = HTMLUtility::render(
			[
				'tag'               => 'span',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__value',
				],
				'childrenSanitizer' => 'esc_html',
				'children'          => $lesson_meta['duration'],
			]
		);

		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__section leaderspath-lesson-meta__duration',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $label . $value,
			]
		);
	}

	/**
	 * Render the difficulty section.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs       Module attributes.
	 * @param ModuleElements $elements    ModuleElements instance.
	 * @param array          $lesson_meta Lesson metadata.
	 *
	 * @return string HTML for difficulty section.
	 */
	private static function render_difficulty( array $attrs, $elements, array $lesson_meta ): string {
		$show_difficulty = $attrs['difficulty']['advanced']['show']['desktop']['value'] ?? 'on';

		if ( 'on' !== $show_difficulty || empty( $lesson_meta['difficulty'] ) ) {
			return '';
		}

		$difficulty_name = self::$difficulty_names[ $lesson_meta['difficulty'] ] ?? ucfirst( $lesson_meta['difficulty'] );

		$label = $elements->render(
			[
				'attrName' => 'difficultyLabel',
			]
		);

		$badge = HTMLUtility::render(
			[
				'tag'               => 'span',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__value leaderspath-lesson-meta__badge leaderspath-lesson-meta__badge--' . esc_attr( $lesson_meta['difficulty'] ),
				],
				'childrenSanitizer' => 'esc_html',
				'children'          => $difficulty_name,
			]
		);

		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__section leaderspath-lesson-meta__difficulty',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $label . $badge,
			]
		);
	}

	/**
	 * Render the activity count section.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs       Module attributes.
	 * @param ModuleElements $elements    ModuleElements instance.
	 * @param array          $lesson_meta Lesson metadata.
	 *
	 * @return string HTML for activity count section.
	 */
	private static function render_activity_count( array $attrs, $elements, array $lesson_meta ): string {
		$show_activities = $attrs['activities']['advanced']['show']['desktop']['value'] ?? 'on';

		if ( 'on' !== $show_activities ) {
			return '';
		}

		$count = $lesson_meta['activity_count'];
		$count_text = sprintf(
			/* translators: %d: number of activities */
			_n( '%d activity', '%d activities', $count, 'leaderspath' ),
			$count
		);

		$label = $elements->render(
			[
				'attrName' => 'activitiesLabel',
			]
		);

		$value = HTMLUtility::render(
			[
				'tag'               => 'span',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__value',
				],
				'childrenSanitizer' => 'esc_html',
				'children'          => $count_text,
			]
		);

		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__section leaderspath-lesson-meta__activities',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $label . $value,
			]
		);
	}
}
