<?php
/**
 * Lesson Activities Divi 5 module registration.
 *
 * @package LeadersPath\Modules\LessonActivities
 * @since   0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonActivities;

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Lesson Activities module.
 *
 * Displays an ordered list of activities linked to a lesson, with links.
 *
 * @since 0.4.0
 */
class LessonActivities implements DependencyInterface {

	use LessonActivitiesTrait\RenderCallbackTrait;

	/**
	 * Register the module with Divi's module system.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function load(): void {
		$module_json_folder_path = LEADERSPATH_MODULES_JSON_PATH . 'lesson-activities/';

		add_action(
			'init',
			function () use ( $module_json_folder_path ) {
				ModuleRegistration::register_module(
					$module_json_folder_path,
					[
						'render_callback' => [ LessonActivities::class, 'render_callback' ],
					]
				);
			}
		);
	}
}
