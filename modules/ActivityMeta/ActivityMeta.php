<?php
/**
 * Activity Meta Divi 5 module registration.
 *
 * @package LeadersPath\Modules\ActivityMeta
 * @since   0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\ActivityMeta;

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Activity Meta module.
 *
 * Displays duration, model, and model switching info for an activity.
 *
 * @since 0.4.0
 */
class ActivityMeta implements DependencyInterface {

	use ActivityMetaTrait\RenderCallbackTrait;

	/**
	 * Register the module with Divi's module system.
	 *
	 * @since 0.4.0
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
						'render_callback' => [ ActivityMeta::class, 'render_callback' ],
					]
				);
			}
		);
	}
}
