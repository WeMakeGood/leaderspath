<?php
/**
 * LessonObjectives::custom_css()
 *
 * @package LeadersPath\Modules\LessonObjectives
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonObjectives\LessonObjectivesTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Custom CSS trait for LessonObjectives.
 *
 * @since 0.1.0
 */
trait CustomCssTrait {

	/**
	 * Get custom CSS fields.
	 *
	 * This function is equivalent of JS const cssFields located in
	 * src/components/lesson-objectives/custom-css.ts.
	 *
	 * @since 0.1.0
	 *
	 * @return array Custom CSS fields from registered block type.
	 */
	public static function custom_css(): array {
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( 'leaderspath/lesson-objectives' );

		return $block_type->customCssFields ?? [];
	}
}
