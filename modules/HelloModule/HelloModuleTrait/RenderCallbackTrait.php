<?php
/**
 * HelloModule::render_callback()
 *
 * @package LeadersPath\Modules\HelloModule
 * @since 0.1.0
 */

namespace LeadersPath\Modules\HelloModule\HelloModuleTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\Module\Module;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;
use LeadersPath\Modules\HelloModule\HelloModule;

/**
 * Render callback trait for HelloModule.
 *
 * @since 0.1.0
 */
trait RenderCallbackTrait {

	/**
	 * Hello module render callback which outputs server side rendered HTML on the Front-End.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs    Block attributes that were saved by VB.
	 * @param string         $content  Block content.
	 * @param \WP_Block      $block    Parsed block object that being rendered.
	 * @param ModuleElements $elements ModuleElements instance.
	 *
	 * @return string HTML rendered of Hello module.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ): string {
		// Title element.
		$title = $elements->render(
			[
				'attrName' => 'title',
			]
		);

		// Message element.
		$message = $elements->render(
			[
				'attrName' => 'message',
			]
		);

		// Content container.
		$content_html = HTMLUtility::render(
			[
				'tag'               => 'div',
				'attributes'        => [
					'class' => 'leaderspath-hello__content',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $title . $message,
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
				'classnamesFunction' => [ HelloModule::class, 'module_classnames' ],
				'stylesComponent'    => [ HelloModule::class, 'module_styles' ],
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
}
