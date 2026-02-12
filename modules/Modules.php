<?php
/**
 * Register all LeadersPath Divi 5 modules with the dependency tree.
 *
 * Also registers VB and frontend asset bundles.
 *
 * @package LeadersPath\Modules
 * @since   0.7.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use LeadersPath\Modules\LessonMeta\LessonMeta;
use LeadersPath\Modules\LessonObjectives\LessonObjectives;
use LeadersPath\Modules\LessonActivities\LessonActivities;
use LeadersPath\Modules\ActivityMeta\ActivityMeta;
use LeadersPath\Modules\CourseLessons\CourseLessons;
use LeadersPath\Modules\ContextLibrary\ContextLibrary;
use LeadersPath\Modules\SkillsList\SkillsList;
use LeadersPath\Modules\Chatbot\Chatbot;

/**
 * Register modules with Divi's dependency tree.
 */
add_action(
	'divi_module_library_modules_dependency_tree',
	function ( $dependency_tree ) {
		$dependency_tree->add_dependency( new LessonMeta() );
		$dependency_tree->add_dependency( new LessonObjectives() );
		$dependency_tree->add_dependency( new LessonActivities() );
		$dependency_tree->add_dependency( new ActivityMeta() );
		$dependency_tree->add_dependency( new CourseLessons() );
		$dependency_tree->add_dependency( new ContextLibrary() );
		$dependency_tree->add_dependency( new SkillsList() );
		$dependency_tree->add_dependency( new Chatbot() );
	}
);

/**
 * Register VB bundle (JS + CSS) for the Visual Builder.
 */
add_action(
	'divi_visual_builder_assets_before_enqueue_scripts',
	function () {
		if ( ! function_exists( 'et_builder_d5_enabled' ) || ! et_builder_d5_enabled() ) {
			return;
		}

		if ( ! function_exists( 'et_core_is_fb_enabled' ) || ! et_core_is_fb_enabled() ) {
			return;
		}

		$url = LEADERSPATH_URL;

		// VB JavaScript bundle.
		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
			[
				'name'    => 'leaderspath-builder-bundle',
				'version' => LEADERSPATH_VERSION,
				'script'  => [
					'src'                => "{$url}scripts/bundle.js",
					'deps'               => [ 'divi-module-library', 'divi-vendor-wp-hooks' ],
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
				],
			]
		);

		// VB CSS bundle (module styles for preview parity).
		if ( file_exists( LEADERSPATH_PATH . 'styles/vb-bundle.css' ) ) {
			\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
				[
					'name'    => 'leaderspath-builder-vb-style',
					'version' => LEADERSPATH_VERSION,
					'style'   => [
						'src'                => "{$url}styles/vb-bundle.css",
						'deps'               => [],
						'enqueue_top_window' => false,
						'enqueue_app_window' => true,
					],
				]
			);
		}
	}
);

/**
 * Register frontend CSS for module display.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! file_exists( LEADERSPATH_PATH . 'styles/bundle.css' ) ) {
			return;
		}

		wp_enqueue_style(
			'leaderspath-modules',
			LEADERSPATH_URL . 'styles/bundle.css',
			[],
			LEADERSPATH_VERSION
		);
	}
);
