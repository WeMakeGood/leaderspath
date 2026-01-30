<?php
/**
 * Module: Lesson Meta class.
 *
 * Displays lesson metadata including duration, learning objectives, and AI model info.
 * This module follows the Theme Builder pattern, displaying data from the current lesson.
 *
 * @package LeadersPath\Modules\LessonMeta
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonMeta;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * LessonMeta module class.
 *
 * This module displays lesson metadata pulled from ACF fields, including
 * duration, learning objectives, and the Claude AI model being used.
 * Follows the Theme Builder pattern from PostTitleModule.
 *
 * @since 0.1.0
 */
class LessonMeta implements DependencyInterface {
	use LessonMetaTrait\RenderCallbackTrait;
	use LessonMetaTrait\ModuleClassnamesTrait;
	use LessonMetaTrait\ModuleStylesTrait;
	use LessonMetaTrait\CustomCssTrait;

	/**
	 * Loads LessonMeta and registers Front-End render callback.
	 *
	 * @since 0.1.0
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
						'render_callback' => [ self::class, 'render_callback' ],
					]
				);
			}
		);
	}
}
