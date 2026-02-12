<?php
/**
 * Skills List Divi 5 module registration.
 *
 * @package LeadersPath\Modules\SkillsList
 * @since   0.5.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\SkillsList;

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Skills List module.
 *
 * Displays a card grid of skills attached to an activity.
 *
 * @since 0.5.0
 */
class SkillsList implements DependencyInterface {

	use SkillsListTrait\RenderCallbackTrait;

	/**
	 * Register the module with Divi's module system.
	 *
	 * @since 0.5.0
	 *
	 * @return void
	 */
	public function load(): void {
		$module_json_folder_path = LEADERSPATH_MODULES_JSON_PATH . 'skills-list/';

		add_action(
			'init',
			function () use ( $module_json_folder_path ) {
				ModuleRegistration::register_module(
					$module_json_folder_path,
					[
						'render_callback' => [ SkillsList::class, 'render_callback' ],
					]
				);
			}
		);
	}
}
