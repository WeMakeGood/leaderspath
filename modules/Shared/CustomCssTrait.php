<?php
/**
 * Shared Custom CSS trait for all LeadersPath Divi 5 modules.
 *
 * Reads custom CSS fields from the registered block type metadata.
 * Each module using this trait must implement block_name().
 *
 * JS equivalent: src/components/{module}/custom-css.ts
 *
 * @package LeadersPath\Modules\Shared
 * @since 0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\Shared;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Shared custom CSS trait.
 *
 * @since 0.4.0
 */
trait CustomCssTrait {

	/**
	 * Get the block type name for this module.
	 *
	 * @since 0.4.0
	 *
	 * @return string Block name (e.g., 'leaderspath/lesson-activities').
	 */
	abstract protected static function block_name(): string;

	/**
	 * Get custom CSS fields from the registered block type.
	 *
	 * @since 0.4.0
	 *
	 * @return array Custom CSS fields.
	 */
	public static function custom_css(): array {
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( static::block_name() );

		return $block_type->customCssFields ?? [];
	}
}
