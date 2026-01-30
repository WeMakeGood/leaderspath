<?php
/**
 * Module: Context Library class.
 *
 * Displays context files associated with the current lesson.
 *
 * @package LeadersPath\Modules\ContextLibrary
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\ContextLibrary;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * Context Library module class.
 *
 * Renders a list of context files from the current lesson's ACF relationship field.
 * Uses Theme Builder pattern with get_queried_object_id() to get current lesson.
 *
 * @since 0.1.0
 */
class ContextLibrary implements DependencyInterface {

	use ContextLibraryTrait\RenderCallbackTrait;
	use ContextLibraryTrait\ModuleClassnamesTrait;
	use ContextLibraryTrait\ModuleStylesTrait;
	use ContextLibraryTrait\CustomCssTrait;

	/**
	 * Loads Context Library module and registers render callback.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function load(): void {
		$module_json_folder_path = LEADERSPATH_MODULES_JSON_PATH . 'context-library/';

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

		// Enqueue frontend scripts.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );
	}

	/**
	 * Enqueue frontend scripts for the modal functionality.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_frontend_scripts(): void {
		wp_register_script(
			'leaderspath-context-modal',
			LEADERSPATH_URL . 'assets/js/context-modal.js',
			[],
			LEADERSPATH_VERSION,
			true
		);

		wp_register_style(
			'leaderspath-context-modal',
			LEADERSPATH_URL . 'assets/css/context-modal.css',
			[],
			LEADERSPATH_VERSION
		);
	}
}
