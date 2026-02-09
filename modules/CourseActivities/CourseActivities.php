<?php
/**
 * Module: Course Activities class.
 *
 * Displays the ordered list of activities for a course from the course_activities
 * ACF relationship field. This module follows the Theme Builder pattern.
 *
 * @package LeadersPath\Modules\CourseActivities
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\CourseActivities;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * CourseActivities module class.
 *
 * This module displays an ordered list of activities from the course_activities
 * ACF relationship field. Each activity is displayed as a linked list item.
 * Follows the Theme Builder pattern from PostTitleModule.
 *
 * @since 0.1.0
 */
class CourseActivities implements DependencyInterface {
	use CourseActivitiesTrait\RenderCallbackTrait;
	use CourseActivitiesTrait\ModuleClassnamesTrait;
	use CourseActivitiesTrait\ModuleStylesTrait;
	use CourseActivitiesTrait\CustomCssTrait;

	/**
	 * Loads CourseActivities and registers Front-End render callback.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function load(): void {
		$module_json_folder_path = LEADERSPATH_MODULES_JSON_PATH . 'course-activities/';

		add_action(
			'init',
			function () use ( $module_json_folder_path ) {
				ModuleRegistration::register_module(
					$module_json_folder_path,
					[
						'render_callback' => [ self::class, 'render_callback' ],
					]
				);
			}
		);
	}
}
