<?php
/**
 * Module: Activity Meta class.
 *
 * Displays activity metadata including duration, learning objectives, and AI model info.
 * This module follows the Theme Builder pattern, displaying data from the current activity.
 *
 * @package LeadersPath\Modules\ActivityMeta
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\ActivityMeta;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * ActivityMeta module class.
 *
 * This module displays activity metadata pulled from ACF fields, including
 * duration, learning objectives, and the Claude AI model being used.
 * Follows the Theme Builder pattern from PostTitleModule.
 *
 * @since 0.1.0
 */
class ActivityMeta implements DependencyInterface {
	use ActivityMetaTrait\RenderCallbackTrait;
	use ActivityMetaTrait\ModuleClassnamesTrait;
	use ActivityMetaTrait\ModuleStylesTrait;
	use ActivityMetaTrait\CustomCssTrait;

	/**
	 * Loads ActivityMeta and registers Front-End render callback.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function load(): void {
		$module_json_folder_path = LEADERSPATH_MODULES_JSON_PATH . 'activity-meta/';

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
