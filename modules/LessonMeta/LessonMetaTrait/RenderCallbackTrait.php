<?php
/**
 * LessonMeta::render_callback()
 *
 * @package LeadersPath\Modules\LessonMeta
 * @since 0.1.0
 */

namespace LeadersPath\Modules\LessonMeta\LessonMetaTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

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
		// Use get_queried_object_id() for theme builder templates, fallback to get_the_ID().
		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		$output = '';

		// Title element.
		$title = $elements->render(
			[
				'attrName' => 'title',
			]
		);

		// Get visibility settings from attributes.
		$show_duration   = self::get_toggle_value( $attrs, 'showDuration', 'on' );
		$show_objectives = self::get_toggle_value( $attrs, 'showObjectives', 'on' );
		$show_model      = self::get_toggle_value( $attrs, 'showModel', 'on' );

		// Get label values.
		$duration_label   = self::get_inner_content( $attrs, 'durationLabel', 'Duration:' );
		$objectives_label = self::get_inner_content( $attrs, 'objectivesLabel', 'Learning Objectives:' );
		$model_label      = self::get_inner_content( $attrs, 'modelLabel', 'AI Model:' );

		// Build sections.
		$sections = '';

		// Duration section.
		if ( 'on' === $show_duration ) {
			$sections .= self::render_duration_section( $post_id, $duration_label );
		}

		// Objectives section.
		if ( 'on' === $show_objectives ) {
			$sections .= self::render_objectives_section( $post_id, $objectives_label );
		}

		// Model section (only if chatbot is enabled).
		if ( 'on' === $show_model ) {
			$sections .= self::render_model_section( $post_id, $model_label );
		}

		// Content container.
		$content_html = HTMLUtility::render(
			[
				'tag'               => 'div',
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__content',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $title . $sections,
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
	 * Get toggle value from attributes.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $attrs   Block attributes.
	 * @param string $name    Attribute name.
	 * @param string $default Default value.
	 *
	 * @return string Toggle value ('on' or 'off').
	 */
	private static function get_toggle_value( array $attrs, string $name, string $default = 'on' ): string {
		return $attrs[ $name ]['innerContent']['desktop']['value'] ?? $default;
	}

	/**
	 * Get inner content value from attributes.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $attrs   Block attributes.
	 * @param string $name    Attribute name.
	 * @param string $default Default value.
	 *
	 * @return string Content value.
	 */
	private static function get_inner_content( array $attrs, string $name, string $default = '' ): string {
		return $attrs[ $name ]['innerContent']['desktop']['value'] ?? $default;
	}

	/**
	 * Render duration section.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $label   Label text.
	 *
	 * @return string HTML for duration section.
	 */
	private static function render_duration_section( int $post_id, string $label ): string {
		$duration = get_field( 'lesson_duration', $post_id );

		if ( empty( $duration ) ) {
			return '';
		}

		$duration_text = sprintf(
			/* translators: %d: number of minutes */
			_n( '%d minute', '%d minutes', (int) $duration, 'leaderspath' ),
			(int) $duration
		);

		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__section leaderspath-lesson-meta__duration',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => HTMLUtility::render(
					[
						'tag'               => 'span',
						'attributes'        => [
							'class' => 'leaderspath-lesson-meta__label',
						],
						'childrenSanitizer' => 'esc_html',
						'children'          => $label,
					]
				) . HTMLUtility::render(
					[
						'tag'               => 'span',
						'attributes'        => [
							'class' => 'leaderspath-lesson-meta__value',
						],
						'childrenSanitizer' => 'esc_html',
						'children'          => $duration_text,
					]
				),
			]
		);
	}

	/**
	 * Render objectives section.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $label   Label text.
	 *
	 * @return string HTML for objectives section.
	 */
	private static function render_objectives_section( int $post_id, string $label ): string {
		$objectives = get_field( 'lesson_objectives', $post_id );

		if ( empty( $objectives ) || ! is_array( $objectives ) ) {
			return '';
		}

		$list_items = '';
		foreach ( $objectives as $objective ) {
			$objective_text = $objective['objective'] ?? '';
			if ( ! empty( $objective_text ) ) {
				$list_items .= HTMLUtility::render(
					[
						'tag'               => 'li',
						'childrenSanitizer' => 'esc_html',
						'children'          => $objective_text,
					]
				);
			}
		}

		if ( empty( $list_items ) ) {
			return '';
		}

		$list = HTMLUtility::render(
			[
				'tag'               => 'ul',
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
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__section leaderspath-lesson-meta__objectives',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => HTMLUtility::render(
					[
						'tag'               => 'span',
						'attributes'        => [
							'class' => 'leaderspath-lesson-meta__label',
						],
						'childrenSanitizer' => 'esc_html',
						'children'          => $label,
					]
				) . $list,
			]
		);
	}

	/**
	 * Render model section.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $label   Label text.
	 *
	 * @return string HTML for model section.
	 */
	private static function render_model_section( int $post_id, string $label ): string {
		$chatbot_enabled = get_field( 'chatbot_enabled', $post_id );

		if ( ! $chatbot_enabled ) {
			return '';
		}

		$model      = get_field( 'chatbot_model', $post_id );
		$model_name = self::$model_names[ $model ] ?? ucfirst( $model );

		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'attributes'        => [
					'class' => 'leaderspath-lesson-meta__section leaderspath-lesson-meta__model',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => HTMLUtility::render(
					[
						'tag'               => 'span',
						'attributes'        => [
							'class' => 'leaderspath-lesson-meta__label',
						],
						'childrenSanitizer' => 'esc_html',
						'children'          => $label,
					]
				) . HTMLUtility::render(
					[
						'tag'               => 'span',
						'attributes'        => [
							'class' => 'leaderspath-lesson-meta__value leaderspath-lesson-meta__badge',
						],
						'childrenSanitizer' => 'esc_html',
						'children'          => $model_name,
					]
				),
			]
		);
	}
}
