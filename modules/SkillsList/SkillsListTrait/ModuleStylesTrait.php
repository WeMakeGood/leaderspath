<?php
/**
 * SkillsList::module_styles()
 *
 * @package LeadersPath\Modules\SkillsList
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\SkillsList\SkillsListTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\Module\Module;
use ET\Builder\Packages\Module\Options\Css\CssStyle;

/**
 * Module styles trait for SkillsList.
 *
 * @since 0.1.0
 */
trait ModuleStylesTrait {

	/**
	 * Skills List Module's style components.
	 *
	 * This function is equivalent to JS function ModuleStyles located in
	 * src/components/skills-list/styles.tsx.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type string         $id                Module ID.
	 *     @type string         $name              Module name.
	 *     @type array          $attrs             Module attributes.
	 *     @type callable       $selector          Selector function.
	 *     @type object         $elements          Module elements.
	 *     @type array          $settings          Module settings.
	 *     @type string         $orderClass        Order class name.
	 *     @type string         $storeInstance     Store instance.
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

		// Module.
		$elements->style(
			[
				'attrName'   => 'module',
				'styleProps' => [
					'disabledOn' => [
						'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
					],
				],
			]
		);

		// Title.
		$elements->style(
			[
				'attrName' => 'title',
			]
		);

		// Empty state.
		$elements->style(
			[
				'attrName' => 'emptyState',
			]
		);

		// Item container.
		$elements->style(
			[
				'attrName' => 'item',
			]
		);

		// Item title.
		$elements->style(
			[
				'attrName' => 'itemTitle',
			]
		);

		// Item description.
		$elements->style(
			[
				'attrName' => 'itemDescription',
			]
		);

		// Item badge.
		$elements->style(
			[
				'attrName' => 'itemBadge',
			]
		);

		// Download button.
		$elements->style(
			[
				'attrName' => 'downloadButton',
			]
		);

		// Custom CSS.
		CssStyle::style(
			[
				'selector'  => $order_class,
				'attr'      => $attrs['css'] ?? [],
				'cssFields' => self::custom_css(),
			]
		);
	}
}
