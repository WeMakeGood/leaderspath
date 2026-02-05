<?php
/**
 * Module: Course Meta class.
 *
 * Displays course metadata including duration, difficulty, and activity count.
 * This module follows the Theme Builder pattern, displaying data from the current course.
 *
 * @package LeadersPath\Modules\CourseMeta
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\CourseMeta;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * CourseMeta module class.
 *
 * This module displays course metadata pulled from ACF fields, including
 * duration, difficulty level, and the count of activities in the course.
 * Follows the Theme Builder pattern from PostTitleModule.
 *
 * @since 0.1.0
 */
class CourseMeta implements DependencyInterface {
	use CourseMetaTrait\RenderCallbackTrait;
	use CourseMetaTrait\ModuleClassnamesTrait;
	use CourseMetaTrait\ModuleStylesTrait;
	use CourseMetaTrait\CustomCssTrait;

	/**
	 * Loads CourseMeta and registers Front-End render callback.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function load(): void {
		$module_json_folder_path = LEADERSPATH_MODULES_JSON_PATH . 'course-meta/';

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
