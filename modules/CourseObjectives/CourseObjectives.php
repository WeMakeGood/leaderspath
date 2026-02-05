<?php
/**
 * Module: Course Objectives class.
 *
 * Displays course learning objectives from the course_objectives ACF repeater field.
 * This module follows the Theme Builder pattern, displaying data from the current course.
 *
 * @package LeadersPath\Modules\CourseObjectives
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\CourseObjectives;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * CourseObjectives module class.
 *
 * This module displays learning objectives pulled from the course_objectives
 * ACF repeater field. Each objective is displayed as a list item.
 * Follows the Theme Builder pattern from PostTitleModule.
 *
 * @since 0.1.0
 */
class CourseObjectives implements DependencyInterface {
	use CourseObjectivesTrait\RenderCallbackTrait;
	use CourseObjectivesTrait\ModuleClassnamesTrait;
	use CourseObjectivesTrait\ModuleStylesTrait;
	use CourseObjectivesTrait\CustomCssTrait;

	/**
	 * Loads CourseObjectives and registers Front-End render callback.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function load(): void {
		$module_json_folder_path = LEADERSPATH_MODULES_JSON_PATH . 'course-objectives/';

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
