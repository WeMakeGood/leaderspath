<?php
/**
 * Custom CSS trait for Lesson Objectives.
 *
 * Reads customCssFields from the block type registry — defined in module.json.
 *
 * @package LeadersPath\Modules\LessonObjectives
 * @since   0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonObjectives\LessonObjectivesTrait;

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
			->get_registered( 'leaderspath/lesson-objectives' );

		if ( ! $registered ) {
			return [];
		}

		return $registered->customCssFields ?? [];
	}
}
