<?php
/**
 * ActivityMeta::module_styles()
 *
 * @package LeadersPath\Modules\ActivityMeta
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\ActivityMeta\ActivityMetaTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use LeadersPath\Modules\ActivityMeta\ActivityMeta;

/**
 * Module styles trait for ActivityMeta.
 *
 * @since 0.1.0
 */
trait ModuleStylesTrait {

	/**
	 * ActivityMeta Module's style components.
	 *
	 * This function is equivalent of JS function ModuleStyles located in
	 * src/components/activity-meta/styles.tsx.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type string         $id                Module ID. In VB, the ID of module is UUIDV4. In FE, the ID is order index.
	 *     @type string         $name              Module name.
	 *     @type array          $attrs             Module attributes.
	 *     @type string         $orderClass        Selector class name.
	 *     @type array          $settings          Custom settings.
	 *     @type ModuleElements $elements          ModuleElements instance.
	 * }
	 *
	 * @return void
	 */
	public static function module_styles( array $args ): void {
		$attrs       = $args['attrs'] ?? [];
		$elements    = $args['elements'];
		$settings    = $args['settings'] ?? [];
		$order_class = $args['orderClass'] ?? '';

		Style::add(
			[
				'id'            => $args['id'],
				'name'          => $args['name'],
				'orderIndex'    => $args['orderIndex'],
				'storeInstance' => $args['storeInstance'],
				'styles'        => [
					// Module.
					$elements->style(
						[
							'attrName'   => 'module',
							'styleProps' => [
								'disabledOn'     => [
									'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
								],
								'advancedStyles' => [
									[
										'componentName' => 'divi/text',
										'props'         => [
											'selector' => "{$order_class} .leaderspath-activity-meta__content",
											'attr'     => $attrs['module']['advanced']['text'] ?? [],
										],
									],
								],
							],
						]
					),

					// Title.
					$elements->style(
						[
							'attrName' => 'title',
						]
					),

					// Duration Label.
					$elements->style(
						[
							'attrName' => 'durationLabel',
						]
					),

					// Objectives Label.
					$elements->style(
						[
							'attrName' => 'objectivesLabel',
						]
					),

					// Model Label.
					$elements->style(
						[
							'attrName' => 'modelLabel',
						]
					),

					// Custom CSS - must be last so it can override module styles.
					CssStyle::style(
						[
							'selector'  => $order_class,
							'attr'      => $attrs['css'] ?? [],
							'cssFields' => ActivityMeta::custom_css(),
						]
					),
				],
			]
		);
	}
}
