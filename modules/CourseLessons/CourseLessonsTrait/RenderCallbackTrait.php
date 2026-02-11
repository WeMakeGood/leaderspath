<?php
/**
 * Render callback trait for Course Lessons module.
 *
 * @package LeadersPath\Modules\CourseLessons
 * @since   0.5.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\CourseLessons\CourseLessonsTrait;

use LeadersPath\Modules\CourseLessons\CourseLessonsRenderer;
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
	 * @since 0.5.0
	 *
	 * @param array     $attrs    Block attributes.
	 * @param string    $content  Inner content (unused for current-post modules).
	 * @param \WP_Block $block    Parsed block object.
	 * @param object    $elements ModuleElements instance.
	 * @return string Rendered HTML.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ): string {
		$content_values = $attrs['content']['innerContent']['desktop']['value'] ?? [];

		$renderer_options = [
			'show_heading'        => ( $content_values['showHeading'] ?? 'on' ) === 'on',
			'heading_text'        => $content_values['headingText'] ?? __( 'Lessons', 'leaderspath' ),
			'show_number'         => ( $content_values['showNumber'] ?? 'on' ) === 'on',
			'show_duration'       => ( $content_values['showDuration'] ?? 'on' ) === 'on',
			'show_difficulty'     => ( $content_values['showDifficulty'] ?? 'on' ) === 'on',
			'show_activity_count' => ( $content_values['showActivityCount'] ?? 'on' ) === 'on',
			'show_excerpt'        => ( $content_values['showExcerpt'] ?? 'on' ) === 'on',
			'show_prerequisites'  => ( $content_values['showPrerequisites'] ?? 'off' ) === 'on',
		];

		$core_html = CourseLessonsRenderer::render( $renderer_options );

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
