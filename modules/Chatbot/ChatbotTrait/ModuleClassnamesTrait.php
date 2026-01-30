<?php
/**
 * Chatbot::module_classnames()
 *
 * @package LeadersPath\Modules\Chatbot
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\Chatbot\ChatbotTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\Module\Options\Text\TextClassnames;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;

/**
 * Module classnames trait for Chatbot.
 *
 * @since 0.1.0
 */
trait ModuleClassnamesTrait {

	/**
	 * Generate classnames for Chatbot Module.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args {
	 *     Classnames arguments.
	 *
	 *     @type object $classnamesInstance Instance of ET\Builder\Packages\Module\Layout\Components\Classnames.
	 *     @type array  $attrs              Block attributes.
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
