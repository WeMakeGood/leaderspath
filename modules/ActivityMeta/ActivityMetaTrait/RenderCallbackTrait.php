<?php
/**
 * ActivityMeta::render_callback()
 *
 * Displays Activity metadata (duration, AI model info).
 * Note: Learning objectives have moved to Course level.
 *
 * @package LeadersPath\Modules\ActivityMeta
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\ActivityMeta\ActivityMetaTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// phpcs:disable ET.Sniffs.ValidVariableName.UsedPropertyNotSnakeCase -- WP uses snakeCase in \WP_Block_Parser_Block

use ET\Builder\Packages\Module\Module;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;
use LeadersPath\Modules\ActivityMeta\ActivityMeta;

/**
 * Render callback trait for ActivityMeta.
 *
 * Displays metadata for the current Activity:
 * - Duration (from ACF field)
 * - AI Model (if chatbot/sandbox is enabled)
 *
 * Note: Learning objectives are now at the Course level, not Activity level.
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
	 * Get the current activity post ID.
	 *
	 * Uses get_queried_object_id() for Theme Builder templates,
	 * with get_the_ID() as fallback.
	 *
	 * @since 0.1.0
	 *
	 * @return int Post ID, or 0 if not found.
	 */
	public static function get_activity_id(): int {
		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		return (int) $post_id;
	}

	/**
	 * Get activity metadata from ACF fields.
	 *
	 * @since 0.1.0
	 *
	 * @return array{duration: int, model: string, chatbot_enabled: bool}
	 */
	public static function get_activity_meta(): array {
		$post_id = self::get_activity_id();

		if ( ! $post_id ) {
			return [
				'duration'        => 0,
				'model'           => '',
				'chatbot_enabled' => false,
			];
		}

		return [
			'duration'        => (int) ( get_field( 'activity_duration', $post_id ) ?: 0 ),
			'model'           => get_field( 'chatbot_model', $post_id ) ?: '',
			'chatbot_enabled' => (bool) get_field( 'chatbot_enabled', $post_id ),
		];
	}

	/**
	 * Activity Meta module render callback which outputs server side rendered HTML on the Front-End.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs    Block attributes that were saved by VB.
	 * @param string         $content  Block content.
	 * @param \WP_Block      $block    Parsed block object that being rendered.
	 * @param ModuleElements $elements ModuleElements instance.
	 *
	 * @return string HTML rendered of Activity Meta module.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ): string {
		$activity_meta = self::get_activity_meta();

		// Render title using elements->render().
		$title = $elements->render(
			[
				'attrName' => 'title',
			]
		);

		// Build sections based on visibility toggles.
		$duration_section = self::render_duration( $attrs, $elements, $activity_meta );
		$model_section    = self::render_model( $attrs, $elements, $activity_meta );

		// Content container.
		$content_html = HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-activity-meta__content',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $title . $duration_section . $model_section,
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
				'classnamesFunction' => [ ActivityMeta::class, 'module_classnames' ],
				'stylesComponent'    => [ ActivityMeta::class, 'module_styles' ],
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
	 * @param array          $attrs         Module attributes.
	 * @param ModuleElements $elements      ModuleElements instance.
	 * @param array          $activity_meta Activity metadata.
	 *
	 * @return string HTML for duration section.
	 */
	private static function render_duration( array $attrs, $elements, array $activity_meta ): string {
		// Check visibility toggle.
		$show_duration = $attrs['duration']['advanced']['show']['desktop']['value'] ?? 'on';

		if ( 'on' !== $show_duration || empty( $activity_meta['duration'] ) ) {
			return '';
		}

		$duration_text = sprintf(
			/* translators: %d: number of minutes */
			_n( '%d minute', '%d minutes', $activity_meta['duration'], 'leaderspath' ),
			$activity_meta['duration']
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
					'class' => 'leaderspath-activity-meta__value',
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
					'class' => 'leaderspath-activity-meta__section leaderspath-activity-meta__duration',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $label . $value,
			]
		);
	}

	/**
	 * Render the AI model section.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs         Module attributes.
	 * @param ModuleElements $elements      ModuleElements instance.
	 * @param array          $activity_meta Activity metadata.
	 *
	 * @return string HTML for model section.
	 */
	private static function render_model( array $attrs, $elements, array $activity_meta ): string {
		// Check visibility toggle.
		$show_model = $attrs['model']['advanced']['show']['desktop']['value'] ?? 'on';

		// Only show if AI sandbox is enabled and model is set.
		if ( 'on' !== $show_model || ! $activity_meta['chatbot_enabled'] || empty( $activity_meta['model'] ) ) {
			return '';
		}

		$model_name = self::$model_names[ $activity_meta['model'] ] ?? ucfirst( $activity_meta['model'] );

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
					'class' => 'leaderspath-activity-meta__value leaderspath-activity-meta__badge',
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
					'class' => 'leaderspath-activity-meta__section leaderspath-activity-meta__model',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $label . $badge,
			]
		);
	}
}
