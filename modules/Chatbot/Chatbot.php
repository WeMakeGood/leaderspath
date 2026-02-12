<?php
/**
 * Chatbot Divi 5 module registration.
 *
 * @package LeadersPath\Modules\Chatbot
 * @since   0.6.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\Chatbot;

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Chatbot module.
 *
 * Interactive chat interface connected to the Claude API.
 * Works on both Activity (sandbox) and Lesson (Q&A) pages.
 *
 * @since 0.6.0
 */
class Chatbot implements DependencyInterface {

	use ChatbotTrait\RenderCallbackTrait;

	/**
	 * Register the module with Divi's module system.
	 *
	 * @since 0.6.0
	 *
	 * @return void
	 */
	public function load(): void {
		$module_json_folder_path = LEADERSPATH_MODULES_JSON_PATH . 'chatbot/';

		add_action(
			'init',
			function () use ( $module_json_folder_path ) {
				ModuleRegistration::register_module(
					$module_json_folder_path,
					[
						'render_callback' => [ Chatbot::class, 'render_callback' ],
					]
				);
			}
		);
	}
}
