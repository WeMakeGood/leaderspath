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

		$grid_layout   = $attrs['grid']['decoration']['layout']['desktop']['value'] ?? [];
		$grid_display  = $grid_layout['display'] ?? 'grid';
		$grid_columns  = $grid_layout['gridColumnCount'] ?? '3';
		$grid_selector = "{$order_class} .leaderspath_context_library__grid";

		// Build grid declarations — Layout panel generates gap but NOT display or grid-template-columns.
		$grid_declaration = "display: {$grid_display};";
		if ( 'grid' === $grid_display ) {
			$grid_declaration .= " grid-template-columns: repeat({$grid_columns}, 1fr);";
		}

		// Icon color and size — stored in cardIcon.advanced, applied manually.
		$icon_color    = $attrs['cardIcon']['advanced']['color']['desktop']['value'] ?? '';
		$icon_size     = $attrs['cardIcon']['advanced']['size']['desktop']['value'] ?? '';
		$icon_selector = "{$order_class} .leaderspath_context_library__card_icon";

		$icon_declarations = '';
		if ( '' !== $icon_color ) {
			$icon_declarations .= "color: {$icon_color};";
		}
		if ( '' !== $icon_size ) {
			$icon_declarations .= " font-size: {$icon_size};";
		}

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

				// Display + grid-template-columns — Layout panel doesn't generate these.
				[
					[
						'selector'    => $grid_selector,
						'declaration' => $grid_declaration,
					],
				],

				$elements->style( [ 'attrName' => 'heading' ] ),
				$elements->style( [ 'attrName' => 'card' ] ),

				// Icon color + size — uses font-size so SVGs scale via 1em width/height.
				'' !== $icon_declarations ? [
					[
						'selector'    => $icon_selector,
						'declaration' => $icon_declarations,
					],
				] : [],

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
