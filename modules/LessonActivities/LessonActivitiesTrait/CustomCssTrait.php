<?php
/**
 * Custom CSS trait for Lesson Activities.
 *
 * @package LeadersPath\Modules\LessonActivities
 * @since   0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonActivities\LessonActivitiesTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait CustomCssTrait {

	/**
	 * Get custom CSS fields from the block type registry.
	 *
	 * @since 0.4.0
	 *
	 * @return array Custom CSS field definitions.
	 */
	public static function custom_css(): array {
		$registered = \WP_Block_Type_Registry::get_instance()
			->get_registered( 'leaderspath/lesson-activities' );

		if ( ! $registered ) {
			return [];
		}

		return $registered->customCssFields ?? [];
	}
}
