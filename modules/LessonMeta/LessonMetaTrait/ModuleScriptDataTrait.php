<?php
/**
 * Module script data trait for Lesson Meta.
 *
 * Provides script data for link, animation, and other decoration features.
 *
 * @package LeadersPath\Modules\LessonMeta
 * @since   0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonMeta\LessonMetaTrait;

use ET\Builder\Packages\Module\Options\Element\ElementScriptData;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait ModuleScriptDataTrait {

	/**
	 * Set script data for the Lesson Meta module.
	 *
	 * @since 0.4.0
	 *
	 * @param array $args Script data arguments from Divi.
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$id             = $args['id'] ?? '';
		$selector       = $args['selector'] ?? '';
		$attrs          = $args['attrs'] ?? [];
		$store_instance = $args['storeInstance'] ?? null;

		ElementScriptData::set( [
			'id'            => $id,
			'selector'      => $selector,
			'attrs'         => array_merge(
				$attrs['module']['decoration'] ?? [],
				[ 'link' => $attrs['module']['advanced']['link'] ?? [] ]
			),
			'storeInstance' => $store_instance,
		] );
	}
}
