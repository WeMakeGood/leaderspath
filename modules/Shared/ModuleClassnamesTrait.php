<?php
/**
 * Shared Module Classnames trait for all LeadersPath Divi 5 modules.
 *
 * Adds text option classnames and element classnames based on
 * module attributes. All modules use identical classname logic.
 *
 * JS equivalent: src/components/shared/module-classnames.ts
 *
 * @package LeadersPath\Modules\Shared
 * @since 0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\Shared;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use ET\Builder\Packages\Module\Options\Text\TextClassnames;

/**
 * Shared module classnames trait.
 *
 * @since 0.4.0
 */
trait ModuleClassnamesTrait {

	/**
	 * Generate classnames for any LeadersPath module.
	 *
	 * @since 0.4.0
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type object $classnamesInstance Instance of ET\Builder\Packages\Module\Layout\Components\Classnames.
	 *     @type array  $attrs              Block attributes data that being rendered.
	 * }
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$classnames_instance = $args['classnamesInstance'];
		$attrs               = $args['attrs'];

		// Add text option classnames.
		$classnames_instance->add(
			TextClassnames::text_options_classnames( $attrs['module']['advanced']['text'] ?? [] ),
			true
		);

		// Add element classnames.
		$classnames_instance->add(
			ElementClassnames::classnames(
				[
					'attrs' => array_merge(
						$attrs['module']['decoration'] ?? [],
						[
							'link' => $attrs['module']['advanced']['link'] ?? [],
						]
					),
				]
			)
		);
	}
}
