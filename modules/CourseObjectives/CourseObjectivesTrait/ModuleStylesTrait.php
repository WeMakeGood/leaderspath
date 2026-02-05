<?php
/**
 * CourseObjectives::module_styles()
 *
 * @package LeadersPath\Modules\CourseObjectives
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\CourseObjectives\CourseObjectivesTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use LeadersPath\Modules\CourseObjectives\CourseObjectives;

/**
 * Module styles trait for CourseObjectives.
 *
 * @since 0.1.0
 */
trait ModuleStylesTrait {

	/**
	 * CourseObjectives Module's style components.
	 *
	 * This function is equivalent of JS function ModuleStyles located in
	 * src/components/course-objectives/styles.tsx.
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
											'selector' => "{$order_class} .leaderspath-course-objectives__content",
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

					// Empty State.
					$elements->style(
						[
							'attrName' => 'emptyState',
						]
					),

					// Custom CSS - must be last so it can override module styles.
					CssStyle::style(
						[
							'selector'  => $order_class,
							'attr'      => $attrs['css'] ?? [],
							'cssFields' => CourseObjectives::custom_css(),
						]
					),
				],
			]
		);
	}
}
