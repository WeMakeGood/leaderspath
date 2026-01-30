<?php
/**
 * Skills List Module.
 *
 * Displays skills associated with the current lesson.
 *
 * @package LeadersPath\Modules\SkillsList
 * @since   0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\SkillsList;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * Skills List Module class.
 *
 * @since 0.1.0
 */
class SkillsList implements DependencyInterface {

	use SkillsListTrait\RenderCallbackTrait;
	use SkillsListTrait\ModuleClassnamesTrait;
	use SkillsListTrait\ModuleStylesTrait;
	use SkillsListTrait\CustomCssTrait;

	/**
	 * Loads the module and registers it with Divi.
	 *
	 * @since 0.1.0
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
						'render_callback' => [ self::class, 'render_callback' ],
					]
				);
			}
		);
	}
}
