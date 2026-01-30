<?php
/**
 * LessonMeta::render_callback()
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
 * @since 0.1.0
 */
trait RenderCallbackTrait {

	/**
	 * Model display names mapping.
	 *
	 * @var array<string, string>
	 */
	private static array $model_names = [
		'sonnet'   => 'Claude Sonnet',
		'haiku'    => 'Claude Haiku',
		'opus-4.5' => 'Claude Opus',
	];

	/**
	 * Get the current lesson post ID.
	 *
	 * Uses get_queried_object_id() for Theme Builder templates,
	 * with get_the_ID() as fallback.
	 *
	 * @since 0.1.0
	 *
	 * @return int Post ID, or 0 if not found.
	 */
	public static function get_lesson_id(): int {
		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		return (int) $post_id;
	}

	/**
	 * Get lesson meta data from ACF fields.
	 *
	 * @since 0.1.0
	 *
	 * @return array{duration: int, objectives: array, model: string, chatbot_enabled: bool}
	 */
	public static function get_lesson_meta(): array {
		$post_id = self::get_lesson_id();

		if ( ! $post_id ) {
			return [
				'duration'        => 0,
				'objectives'      => [],
				'model'           => '',
				'chatbot_enabled' => false,
			];
		}

		return [
			'duration'        => (int) ( get_field( 'lesson_duration', $post_id ) ?: 0 ),
			'objectives'      => get_field( 'lesson_objectives', $post_id ) ?: [],
			'model'           => get_field( 'chatbot_model', $post_id ) ?: '',
			'chatbot_enabled' => (bool) get_field( 'chatbot_enabled', $post_id ),
		];
	}

	/**
	 * Lesson Meta module render callback which outputs server side rendered HTML on the Front-End.
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
		$objectives_section = self::render_objectives( $attrs, $elements, $lesson_meta );
		$model_section      = self::render_model( $attrs, $elements, $lesson_meta );

		// Content container.
		$content_html = HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__content',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $title . $duration_section . $objectives_section . $model_section,
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
		// Check visibility toggle.
		$show_duration = $attrs['duration']['advanced']['show']['desktop']['value'] ?? 'on';

		if ( 'on' !== $show_duration || empty( $lesson_meta['duration'] ) ) {
			return '';
		}

		$duration_text = sprintf(
			/* translators: %d: number of minutes */
			_n( '%d minute', '%d minutes', $lesson_meta['duration'], 'leaderspath' ),
			$lesson_meta['duration']
		);

		// Render label using elements->render().
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
				'children'          => $duration_text,
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
	 * Render the objectives section.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs       Module attributes.
	 * @param ModuleElements $elements    ModuleElements instance.
	 * @param array          $lesson_meta Lesson metadata.
	 *
	 * @return string HTML for objectives section.
	 */
	private static function render_objectives( array $attrs, $elements, array $lesson_meta ): string {
		// Check visibility toggle.
		$show_objectives = $attrs['objectives']['advanced']['show']['desktop']['value'] ?? 'on';

		if ( 'on' !== $show_objectives || empty( $lesson_meta['objectives'] ) ) {
			return '';
		}

		$list_items = '';
		foreach ( $lesson_meta['objectives'] as $objective ) {
			$objective_text = $objective['objective'] ?? '';
			if ( ! empty( $objective_text ) ) {
				$list_items .= HTMLUtility::render(
					[
						'tag'               => 'li',
						'tagEscaped'        => true,
						'childrenSanitizer' => 'esc_html',
						'children'          => $objective_text,
					]
				);
			}
		}

		if ( empty( $list_items ) ) {
			return '';
		}

		// Render label using elements->render().
		$label = $elements->render(
			[
				'attrName' => 'objectivesLabel',
			]
		);

		$list = HTMLUtility::render(
			[
				'tag'               => 'ul',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__list',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $list_items,
			]
		);

		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__section leaderspath-lesson-meta__objectives',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $label . $list,
			]
		);
	}

	/**
	 * Render the AI model section.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs       Module attributes.
	 * @param ModuleElements $elements    ModuleElements instance.
	 * @param array          $lesson_meta Lesson metadata.
	 *
	 * @return string HTML for model section.
	 */
	private static function render_model( array $attrs, $elements, array $lesson_meta ): string {
		// Check visibility toggle.
		$show_model = $attrs['model']['advanced']['show']['desktop']['value'] ?? 'on';

		// Only show if chatbot is enabled and model is set.
		if ( 'on' !== $show_model || ! $lesson_meta['chatbot_enabled'] || empty( $lesson_meta['model'] ) ) {
			return '';
		}

		$model_name = self::$model_names[ $lesson_meta['model'] ] ?? ucfirst( $lesson_meta['model'] );

		// Render label using elements->render().
		$label = $elements->render(
			[
				'attrName' => 'modelLabel',
			]
		);

		$badge = HTMLUtility::render(
			[
				'tag'               => 'span',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__value leaderspath-lesson-meta__badge',
				],
				'childrenSanitizer' => 'esc_html',
				'children'          => $model_name,
			]
		);

		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__section leaderspath-lesson-meta__model',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $label . $badge,
			]
		);
	}
}
