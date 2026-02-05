<?php
/**
 * CourseMeta::render_callback()
 *
 * Displays Course metadata (duration, difficulty, activity count).
 * Uses ServerSideRender pattern - same PHP renders for both VB and frontend.
 *
 * @package LeadersPath\Modules\CourseMeta
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\CourseMeta\CourseMetaTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// phpcs:disable ET.Sniffs.ValidVariableName.UsedPropertyNotSnakeCase -- WP uses snakeCase in \WP_Block_Parser_Block

use ET\Builder\Packages\Module\Module;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;
use LeadersPath\Modules\CourseMeta\CourseMeta;

/**
 * Render callback trait for CourseMeta.
 *
 * Displays metadata for the current Course:
 * - Duration (from ACF field)
 * - Difficulty level (beginner/intermediate/advanced)
 * - Activity count (from course_activities relationship)
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
	 * Get course metadata from ACF fields.
	 *
	 * @since 0.1.0
	 *
	 * @return array{duration: string, difficulty: string, activity_count: int, course_title: string}
	 */
	public static function get_course_meta(): array {
		$post_id = self::get_course_id();

		if ( ! $post_id ) {
			return [
				'duration'       => '',
				'difficulty'     => '',
				'activity_count' => 0,
				'course_title'   => '',
			];
		}

		$activities = get_field( 'course_activities', $post_id );

		return [
			'duration'       => get_field( 'course_total_duration', $post_id ) ?: '',
			'difficulty'     => get_field( 'course_difficulty', $post_id ) ?: '',
			'activity_count' => is_array( $activities ) ? count( $activities ) : 0,
			'course_title'   => get_the_title( $post_id ),
		];
	}

	/**
	 * Course Meta module render callback which outputs server side rendered HTML.
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
	 * @return string HTML rendered of Course Meta module.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ): string {
		$course_meta = self::get_course_meta();

		// Render title using elements->render().
		$title = $elements->render(
			[
				'attrName' => 'title',
			]
		);

		// Build sections based on visibility toggles.
		$duration_section   = self::render_duration( $attrs, $elements, $course_meta );
		$difficulty_section = self::render_difficulty( $attrs, $elements, $course_meta );
		$activities_section = self::render_activity_count( $attrs, $elements, $course_meta );

		// Content container.
		$content_html = HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-course-meta__content',
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
				'classnamesFunction' => [ CourseMeta::class, 'module_classnames' ],
				'stylesComponent'    => [ CourseMeta::class, 'module_styles' ],
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
	 * @param array          $course_meta Course metadata.
	 *
	 * @return string HTML for duration section.
	 */
	private static function render_duration( array $attrs, $elements, array $course_meta ): string {
		$show_duration = $attrs['duration']['advanced']['show']['desktop']['value'] ?? 'on';

		if ( 'on' !== $show_duration || empty( $course_meta['duration'] ) ) {
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
					'class' => 'leaderspath-course-meta__value',
				],
				'childrenSanitizer' => 'esc_html',
				'children'          => $course_meta['duration'],
			]
		);

		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-course-meta__section leaderspath-course-meta__duration',
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
	 * @param array          $course_meta Course metadata.
	 *
	 * @return string HTML for difficulty section.
	 */
	private static function render_difficulty( array $attrs, $elements, array $course_meta ): string {
		$show_difficulty = $attrs['difficulty']['advanced']['show']['desktop']['value'] ?? 'on';

		if ( 'on' !== $show_difficulty || empty( $course_meta['difficulty'] ) ) {
			return '';
		}

		$difficulty_name = self::$difficulty_names[ $course_meta['difficulty'] ] ?? ucfirst( $course_meta['difficulty'] );

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
					'class' => 'leaderspath-course-meta__value leaderspath-course-meta__badge leaderspath-course-meta__badge--' . esc_attr( $course_meta['difficulty'] ),
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
					'class' => 'leaderspath-course-meta__section leaderspath-course-meta__difficulty',
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
	 * @param array          $course_meta Course metadata.
	 *
	 * @return string HTML for activity count section.
	 */
	private static function render_activity_count( array $attrs, $elements, array $course_meta ): string {
		$show_activities = $attrs['activities']['advanced']['show']['desktop']['value'] ?? 'on';

		if ( 'on' !== $show_activities ) {
			return '';
		}

		$count = $course_meta['activity_count'];
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
					'class' => 'leaderspath-course-meta__value',
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
					'class' => 'leaderspath-course-meta__section leaderspath-course-meta__activities',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $label . $value,
			]
		);
	}
}
