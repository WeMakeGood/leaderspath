<?php
/**
 * Module: Chatbot class.
 *
 * Interactive chat interface for AI-powered lesson experiences.
 *
 * @package LeadersPath\Modules\Chatbot
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\Chatbot;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * Chatbot module class.
 *
 * Renders an interactive chat interface that communicates with Claude AI.
 * Uses Theme Builder pattern with get_queried_object_id() to get current lesson.
 *
 * @since 0.1.0
 */
class Chatbot implements DependencyInterface {

	use ChatbotTrait\RenderCallbackTrait;
	use ChatbotTrait\ModuleClassnamesTrait;
	use ChatbotTrait\ModuleStylesTrait;
	use ChatbotTrait\CustomCssTrait;

	/**
	 * Loads Chatbot module and registers render callback.
	 *
	 * @since 0.1.0
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
						'render_callback' => [ self::class, 'render_callback' ],
					]
				);
			}
		);

		// Register frontend scripts (conditionally enqueued in render callback).
		add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend_scripts' ] );
	}

	/**
	 * Register frontend scripts and styles for the chatbot functionality.
	 *
	 * Scripts are registered here but only enqueued when the module renders.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_frontend_scripts(): void {
		// Register TinyMCE directly from wp-includes to avoid WordPress editor CSS.
		// Using a custom handle prevents WP hooks that add editor styles.
		wp_register_script(
			'leaderspath-tinymce',
			includes_url( 'js/tinymce/tinymce.min.js' ),
			[],
			false,
			true
		);

		// Register marked.js for Markdown parsing from CDN.
		wp_register_script(
			'marked',
			'https://cdn.jsdelivr.net/npm/marked/marked.min.js',
			[],
			'15.0.4',
			true
		);

		wp_register_script(
			'leaderspath-chatbot',
			LEADERSPATH_URL . 'assets/js/chatbot.js',
			[ 'leaderspath-tinymce', 'marked' ],
			LEADERSPATH_VERSION,
			true
		);

		wp_register_style(
			'leaderspath-chatbot',
			LEADERSPATH_URL . 'assets/css/chatbot.css',
			[],
			LEADERSPATH_VERSION
		);
	}
}
