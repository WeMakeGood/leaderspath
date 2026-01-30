<?php
/**
 * SkillsList::module_classnames()
 *
 * @package LeadersPath\Modules\SkillsList
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\SkillsList\SkillsListTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\Module\Options\Text\TextClassnames;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;

/**
 * Module classnames trait for SkillsList.
 *
 * @since 0.1.0
 */
trait ModuleClassnamesTrait {

	/**
	 * Generate module classnames for the Skills List module.
	 *
	 * This function is equivalent to JS function moduleClassnames located in
	 * src/components/skills-list/module-classnames.ts.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type object $classnamesInstance Instance of ET\Builder\Packages\Module\Layout\Components\Classnames.
	 *     @type array  $attrs              Module attributes.
	 * }
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$classnames_instance = $args['classnamesInstance'];
		$attrs               = $args['attrs'] ?? [];

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
						[ 'link' => $attrs['module']['advanced']['link'] ?? [] ]
					),
				]
			)
		);
	}
}
