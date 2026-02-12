<?php
/**
 * Render callback trait for Chatbot module.
 *
 * @package LeadersPath\Modules\Chatbot
 * @since   0.6.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\Chatbot\ChatbotTrait;

use LeadersPath\Modules\Chatbot\ChatbotRenderer;
use ET\Builder\Packages\Module\Module;
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
	 * @since 0.6.0
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
			'show_heading' => ( $content_values['showHeading'] ?? 'on' ) === 'on',
			'heading_text' => $content_values['headingText'] ?? __( 'AI Sandbox', 'leaderspath' ),
			'empty_text'   => $content_values['emptyText'] ?? __( 'Send a message to start the conversation.', 'leaderspath' ),
		];

		$core_html = ChatbotRenderer::render( $renderer_options );

		if ( '' === $core_html ) {
			return '';
		}

		// Enqueue frontend chatbot script when module is rendered.
		self::enqueue_frontend_assets();

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

	/**
	 * Enqueue frontend JS for chatbot interaction.
	 *
	 * @since 0.6.0
	 *
	 * @return void
	 */
	private static function enqueue_frontend_assets(): void {
		if ( wp_script_is( 'leaderspath-chatbot', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_script(
			'leaderspath-chatbot',
			LEADERSPATH_URL . 'assets/js/chatbot.js',
			[],
			LEADERSPATH_VERSION,
			true
		);

		wp_localize_script(
			'leaderspath-chatbot',
			'LeadersPathChatbot',
			[
				'restUrl' => rest_url( 'leaderspath/v1/chat' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			]
		);
	}
}
