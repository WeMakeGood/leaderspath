<?php
/**
 * Render callback trait for Lesson Meta module.
 *
 * Wraps the core LessonMetaRenderer in Divi's Module::render() framework.
 *
 * @package LeadersPath\Modules\LessonMeta
 * @since   0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonMeta\LessonMetaTrait;

use LeadersPath\Modules\LessonMeta\LessonMetaRenderer;
use ET\Builder\Packages\Module\Module;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait RenderCallbackTrait {

	use ModuleClassnamesTrait;
	use ModuleStylesTrait;
	use ModuleScriptDataTrait;
	use CustomCssTrait;

	/**
	 * Divi render callback.
	 *
	 * @since 0.4.0
	 *
	 * @param array     $attrs    Block attributes.
	 * @param string    $content  Inner content (unused for current-post modules).
	 * @param \WP_Block $block    Parsed block object.
	 * @param object    $elements ModuleElements instance.
	 * @return string Rendered HTML.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ): string {
		// Extract content attrs for the core renderer.
		// group-items values live at: content.innerContent.desktop.value.{subName}
		$content_values = $attrs['content']['innerContent']['desktop']['value'] ?? [];

		$renderer_options = [
			'show_duration'       => ( $content_values['showDuration'] ?? 'on' ) === 'on',
			'show_difficulty'     => ( $content_values['showDifficulty'] ?? 'on' ) === 'on',
			'show_activity_count' => ( $content_values['showActivities'] ?? 'on' ) === 'on',
			'duration_label'      => $content_values['durationLabel'] ?? __( 'Duration', 'leaderspath' ),
			'difficulty_label'    => $content_values['difficultyLabel'] ?? __( 'Difficulty', 'leaderspath' ),
			'activities_label'    => $content_values['activitiesLabel'] ?? __( 'Activities', 'leaderspath' ),
		];

		$core_html = LessonMetaRenderer::render( $renderer_options );

		if ( '' === $core_html ) {
			return '';
		}

		$parent       = BlockParserStore::get_parent(
			$block->parsed_block['id'],
			$block->parsed_block['storeInstance']
		);
		$parent_attrs = $parent->attrs ?? [];

		return Module::render( [
			'orderIndex'          => $block->parsed_block['orderIndex'],
			'storeInstance'       => $block->parsed_block['storeInstance'],
			'attrs'               => $attrs,
			'elements'            => $elements,
			'id'                  => $block->parsed_block['id'],
			'name'                => $block->block_type->name,
			'moduleCategory'      => $block->block_type->category,
			'classnamesFunction'  => [ self::class, 'module_classnames' ],
			'stylesComponent'     => [ self::class, 'module_styles' ],
			'scriptDataComponent' => [ self::class, 'module_script_data' ],
			'parentAttrs'         => $parent_attrs,
			'parentId'            => $parent->id ?? '',
			'parentName'          => $parent->blockName ?? '',
			'children'            => [
				$elements->style_components( [ 'attrName' => 'module' ] ),
				ElementComponents::component( [
					'attrs'         => $attrs['module']['decoration'] ?? [],
					'id'            => $block->parsed_block['id'],
					'orderIndex'    => $block->parsed_block['orderIndex'],
					'storeInstance' => $block->parsed_block['storeInstance'],
				] ),
				$core_html,
			],
		] );
	}
}
