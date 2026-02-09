<?php
/**
 * Module: Lesson Objectives class.
 *
 * Displays lesson learning objectives from the lesson_objectives ACF repeater field.
 * This module follows the Theme Builder pattern, displaying data from the current lesson.
 *
 * @package LeadersPath\Modules\LessonObjectives
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonObjectives;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * LessonObjectives module class.
 *
 * This module displays learning objectives pulled from the lesson_objectives
 * ACF repeater field. Each objective is displayed as a list item.
 * Follows the Theme Builder pattern from PostTitleModule.
 *
 * @since 0.1.0
 */
class LessonObjectives implements DependencyInterface {
	use LessonObjectivesTrait\RenderCallbackTrait;
	use LessonObjectivesTrait\ModuleClassnamesTrait;
	use LessonObjectivesTrait\ModuleStylesTrait;
	use LessonObjectivesTrait\CustomCssTrait;

	/**
	 * Loads LessonObjectives and registers Front-End render callback.
	 *
	 * @since 0.1.0
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
						'render_callback' => [ self::class, 'render_callback' ],
					]
				);
			}
		);
	}
}
