<?php
/**
 * Module: Hello Module class.
 *
 * A simple test module to verify Divi 5 module integration works.
 *
 * @package LeadersPath\Modules\HelloModule
 * @since 0.1.0
 */

namespace LeadersPath\Modules\HelloModule;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * HelloModule class.
 *
 * This is a simple test module for verifying Divi 5 module integration.
 * It demonstrates the basic structure required for a custom module.
 *
 * @since 0.1.0
 */
class HelloModule implements DependencyInterface {
	use HelloModuleTrait\RenderCallbackTrait;
	use HelloModuleTrait\ModuleClassnamesTrait;
	use HelloModuleTrait\ModuleStylesTrait;

	/**
	 * Loads HelloModule and registers Front-End render callback.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function load(): void {
		$module_json_folder_path = LEADERSPATH_MODULES_JSON_PATH . 'hello-module/';

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
