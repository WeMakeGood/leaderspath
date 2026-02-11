<?php
/**
 * Lesson Objectives Divi 5 module registration.
 *
 * @package LeadersPath\Modules\LessonObjectives
 * @since   0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonObjectives;

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Lesson Objectives module.
 *
 * Displays an ordered list of learning objectives for a lesson.
 *
 * @since 0.4.0
 */
class LessonObjectives implements DependencyInterface {

	use LessonObjectivesTrait\RenderCallbackTrait;

	/**
	 * Register the module with Divi's module system.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function load(): void {
		$module_json_folder_path = LEADERSPATH_MODULES_JSON_PATH . 'lesson-objectives/';

		add_action(
			'init',
			function () use ( $module_json_folder_path ) {
				ModuleRegistration::register_module(
					$module_json_folder_path,
					[
						'render_callback' => [ LessonObjectives::class, 'render_callback' ],
					]
				);
			}
		);
	}
}
