<?php
/**
 * CourseObjectives::custom_css()
 *
 * @package LeadersPath\Modules\CourseObjectives
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\CourseObjectives\CourseObjectivesTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Custom CSS trait for CourseObjectives.
 *
 * @since 0.1.0
 */
trait CustomCssTrait {

	/**
	 * Get custom CSS fields.
	 *
	 * This function is equivalent of JS const cssFields located in
	 * src/components/course-objectives/custom-css.ts.
	 *
	 * @since 0.1.0
	 *
	 * @return array Custom CSS fields from registered block type.
	 */
	public static function custom_css(): array {
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( 'leaderspath/course-objectives' );

		return $block_type->customCssFields ?? [];
	}
}
