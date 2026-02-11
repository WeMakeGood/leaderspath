<?php
/**
 * Lesson Meta Divi 5 module registration.
 *
 * @package LeadersPath\Modules\LessonMeta
 * @since   0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonMeta;

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Lesson Meta module.
 *
 * Displays duration, difficulty, and activity count for a lesson.
 *
 * @since 0.4.0
 */
class LessonMeta implements DependencyInterface {

	use LessonMetaTrait\RenderCallbackTrait;

	/**
	 * Register the module with Divi's module system.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function load(): void {
		$module_json_folder_path = LEADERSPATH_MODULES_JSON_PATH . 'lesson-meta/';

		add_action(
			'init',
			function () use ( $module_json_folder_path ) {
				ModuleRegistration::register_module(
					$module_json_folder_path,
					[
						'render_callback' => [ LessonMeta::class, 'render_callback' ],
					]
				);
			}
		);
	}
}
