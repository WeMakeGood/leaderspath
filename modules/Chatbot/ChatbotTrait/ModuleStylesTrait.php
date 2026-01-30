<?php
/**
 * Chatbot::module_styles()
 *
 * @package LeadersPath\Modules\Chatbot
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\Chatbot\ChatbotTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\FrontEnd\Module\Style;
use LeadersPath\Modules\Chatbot\Chatbot;

/**
 * Module styles trait for Chatbot.
 *
 * @since 0.1.0
 */
trait ModuleStylesTrait {

	/**
	 * Generate styles for Chatbot Module.
	 *
	 * This function uses Style::add() wrapper which is required for Divi 5 modules.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args {
	 *     Styles arguments.
	 *
	 *     @type string $id            Module ID.
	 *     @type string $name          Module name.
	 *     @type array  $attrs         Block attributes.
	 *     @type object $settings      Module settings.
	 *     @type string $orderClass    Module order class.
	 *     @type object $elements      ModuleElements instance.
	 *     @type int    $orderIndex    Module order index.
	 *     @type string $storeInstance Store instance.
	 * }
	 *
	 * @return void
	 */
	public static function module_styles( array $args ): void {
		$attrs       = $args['attrs'] ?? [];
		$elements    = $args['elements'];
		$order_class = $args['orderClass'] ?? '';

		Style::add(
			[
				'id'            => $args['id'],
				'name'          => $args['name'],
				'orderIndex'    => $args['orderIndex'],
				'storeInstance' => $args['storeInstance'],
				'styles'        => [
					// Module container styles.
					$elements->style(
						[
							'attrName'   => 'module',
							'styleProps' => [
								'disabledOn' => [
									'disabledModuleVisibility' => $args['settings']->disabledModuleVisibility ?? null,
								],
							],
						]
					),

					// Module title styles.
					$elements->style(
						[
							'attrName' => 'title',
						]
					),

					// Empty state message styles.
					$elements->style(
						[
							'attrName' => 'emptyState',
						]
					),

					// Messages container styles.
					$elements->style(
						[
							'attrName' => 'messages',
						]
					),

					// User bubble styles.
					$elements->style(
						[
							'attrName' => 'userBubble',
						]
					),

					// Assistant bubble styles.
					$elements->style(
						[
							'attrName' => 'assistantBubble',
						]
					),

					// Input field styles.
					$elements->style(
						[
							'attrName' => 'input',
						]
					),

					// Send button styles.
					$elements->style(
						[
							'attrName' => 'sendButton',
						]
					),

					// Custom CSS.
					CssStyle::style(
						[
							'selector'  => $order_class,
							'attr'      => $attrs['css'] ?? [],
							'cssFields' => Chatbot::custom_css(),
						]
					),
				],
			]
		);
	}
}
