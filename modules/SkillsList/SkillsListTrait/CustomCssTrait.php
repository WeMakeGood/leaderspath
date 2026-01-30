<?php
/**
 * SkillsList::custom_css()
 *
 * @package LeadersPath\Modules\SkillsList
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\SkillsList\SkillsListTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Custom CSS trait for SkillsList.
 *
 * @since 0.1.0
 */
trait CustomCssTrait {

	/**
	 * Get custom CSS fields for Skills List Module.
	 *
	 * Retrieves custom CSS fields from the registered block type.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, array<string, string>> Custom CSS fields.
	 */
	public static function custom_css(): array {
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( 'leaderspath/skills-list' );

		return $block_type->customCssFields ?? [];
	}
}
