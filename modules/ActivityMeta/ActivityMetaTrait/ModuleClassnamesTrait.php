<?php
/**
 * ActivityMeta::module_classnames()
 *
 * @package LeadersPath\Modules\ActivityMeta
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\ActivityMeta\ActivityMetaTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use ET\Builder\Packages\Module\Options\Text\TextClassnames;

/**
 * Module classnames trait for ActivityMeta.
 *
 * @since 0.1.0
 */
trait ModuleClassnamesTrait {

	/**
	 * Module classnames function for ActivityMeta module.
	 *
	 * This function is equivalent of JS function moduleClassnames located in
	 * src/components/activity-meta/module-classnames.ts.
	 *
	 * @since 0.1.0
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
