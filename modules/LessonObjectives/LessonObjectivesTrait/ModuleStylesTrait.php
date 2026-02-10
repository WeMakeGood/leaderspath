<?php
/**
 * LessonObjectives::module_styles()
 *
 * @package LeadersPath\Modules\LessonObjectives
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonObjectives\LessonObjectivesTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use LeadersPath\Modules\LessonObjectives\LessonObjectives;

/**
 * Module styles trait for LessonObjectives.
 *
 * @since 0.1.0
 */
trait ModuleStylesTrait {

	/**
	 * LessonObjectives Module's style components.
	 *
	 * This function is equivalent of JS function ModuleStyles located in
	 * src/components/lesson-objectives/styles.tsx.
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
		$attrs    = $args['attrs'] ?? [];
		$elements = $args['elements'];
		$settings = $args['settings'] ?? [];

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
								'disabledOn' => [
									'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
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

					// Empty State.
					$elements->style(
						[
							'attrName' => 'emptyState',
						]
					),

					// List layout.
					$elements->style(
						[
							'attrName' => 'list',
						]
					),

					// Item text.
					$elements->style(
						[
							'attrName' => 'item',
						]
					),

					// Custom CSS - must be last so it can override module styles.
					CssStyle::style(
						[
							'selector'  => $args['orderClass'] ?? '',
							'attr'      => $attrs['css'] ?? [],
							'cssFields' => LessonObjectives::custom_css(),
						]
					),
				],
			]
		);
	}
}
