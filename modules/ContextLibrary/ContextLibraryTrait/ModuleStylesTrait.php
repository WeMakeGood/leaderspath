<?php
/**
 * Module styles trait for Context Library.
 *
 * @package LeadersPath\Modules\ContextLibrary
 * @since   0.5.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\ContextLibrary\ContextLibraryTrait;

use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Options\Css\CssStyle;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait ModuleStylesTrait {

	/**
	 * Generate styles for the Context Library module.
	 *
	 * @since 0.5.0
	 *
	 * @param array $args Style arguments from Divi.
	 * @return void
	 */
	public static function module_styles( array $args ): void {
		$attrs       = $args['attrs'] ?? [];
		$elements    = $args['elements'];
		$settings    = $args['settings'] ?? [];
		$order_class = $args['orderClass'] ?? '';

		$grid_display  = $attrs['grid']['decoration']['layout']['desktop']['value']['display'] ?? 'grid';
		$grid_selector = "{$order_class} .leaderspath_context_library__grid";

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

				$elements->style( [ 'attrName' => 'grid' ] ),

				// Display property — Layout panel doesn't generate it.
				[
					[
						'selector'    => $grid_selector,
						'declaration' => "display: {$grid_display};",
					],
				],

				$elements->style( [ 'attrName' => 'heading' ] ),
				$elements->style( [ 'attrName' => 'card' ] ),
				$elements->style( [ 'attrName' => 'cardTitle' ] ),
				$elements->style( [ 'attrName' => 'cardDesc' ] ),
				$elements->style( [ 'attrName' => 'cardMeta' ] ),
				$elements->style( [ 'attrName' => 'viewButton' ] ),
				$elements->style( [ 'attrName' => 'downloadButton' ] ),

				CssStyle::style( [
					'selector'  => $order_class,
					'attr'      => $attrs['css'] ?? [],
					'cssFields' => self::custom_css(),
				] ),
			],
		] );
	}
}
