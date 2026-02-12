<?php
/**
 * Module styles trait for Chatbot.
 *
 * @package LeadersPath\Modules\Chatbot
 * @since   0.6.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\Chatbot\ChatbotTrait;

use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Options\Css\CssStyle;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait ModuleStylesTrait {

	/**
	 * Generate styles for the Chatbot module.
	 *
	 * @since 0.6.0
	 *
	 * @param array $args Style arguments from Divi.
	 * @return void
	 */
	public static function module_styles( array $args ): void {
		$attrs       = $args['attrs'] ?? [];
		$elements    = $args['elements'];
		$settings    = $args['settings'] ?? [];
		$order_class = $args['orderClass'] ?? '';

		Style::add( [
			'id'            => $args['id'],
			'name'          => $args['name'],
			'orderIndex'    => $args['orderIndex'],
			'storeInstance' => $args['storeInstance'],
			'styles'        => [
				$elements->style( [
					'attrName'   => 'module',
					'styleProps' => [
						'disabledOn' => [
							'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
						],
					],
				] ),

				$elements->style( [ 'attrName' => 'container' ] ),
				$elements->style( [ 'attrName' => 'heading' ] ),
				$elements->style( [ 'attrName' => 'userMessage' ] ),
				$elements->style( [ 'attrName' => 'assistantMessage' ] ),
				$elements->style( [ 'attrName' => 'inputField' ] ),
				$elements->style( [ 'attrName' => 'sendButton' ] ),

				CssStyle::style( [
					'selector'  => $order_class,
					'attr'      => $attrs['css'] ?? [],
					'cssFields' => self::custom_css(),
				] ),
			],
		] );
	}
}
