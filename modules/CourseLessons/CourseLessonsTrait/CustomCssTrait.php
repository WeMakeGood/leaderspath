<?php
/**
 * Custom CSS trait for Course Lessons.
 *
 * @package LeadersPath\Modules\CourseLessons
 * @since   0.5.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\CourseLessons\CourseLessonsTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait CustomCssTrait {

	/**
	 * Get custom CSS fields from the block type registry.
	 *
	 * @since 0.5.0
	 *
	 * @return array Custom CSS field definitions.
	 */
	public static function custom_css(): array {
		$registered = \WP_Block_Type_Registry::get_instance()
			->get_registered( 'leaderspath/course-lessons' );

		if ( ! $registered ) {
			return [];
		}

		return $registered->customCssFields ?? [];
	}
}
