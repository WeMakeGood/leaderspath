<?php
/**
 * LessonMeta::module_styles()
 *
 * @package LeadersPath\Modules\LessonMeta
 * @since 0.1.0
 */

namespace LeadersPath\Modules\LessonMeta\LessonMetaTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\Module\Options\Css\CssStyle;

/**
 * Module styles trait for LessonMeta.
 *
 * @since 0.1.0
 */
trait ModuleStylesTrait {

	/**
	 * Generate styles for the module.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type string $id                 Module ID.
	 *     @type string $name               Module name.
	 *     @type array  $attrs              Module attributes.
	 *     @type string $selector           Module CSS selector.
	 *     @type object $settings           Module settings.
	 *     @type object $elements           ModuleElements instance.
	 *     @type array  $defaultPrintedStyleAttrs Default printed style attributes.
	 * }
	 *
	 * @return void
	 */
	public static function module_styles( array $args ): void {
		$attrs    = $args['attrs'] ?? [];
		$elements = $args['elements'];

		// Render style components for module.
		$elements->style(
			[
				'attrName'   => 'module',
				'styleProps' => [
					'disabledOn' => [
						'disabledModuleVisibility' => $args['settings']['disabledModuleVisibility'] ?? null,
					],
				],
			]
		);

		// Render title styles.
		$elements->style(
			[
				'attrName' => 'title',
			]
		);

		// Custom CSS.
		CssStyle::style(
			[
				'selector'  => $args['selector'] ?? '',
				'attr'      => $attrs['css'] ?? [],
				'cssFields' => $args['cssFields'] ?? [],
			]
		);
	}
}
