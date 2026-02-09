<?php
/**
 * Register all modules with dependency tree.
 *
 * @package LeadersPath\Modules
 * @since 0.1.0
 */

namespace LeadersPath\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use LeadersPath\Modules\HelloModule\HelloModule;
use LeadersPath\Modules\ActivityMeta\ActivityMeta;
use LeadersPath\Modules\ContextLibrary\ContextLibrary;
use LeadersPath\Modules\SkillsList\SkillsList;
use LeadersPath\Modules\Chatbot\Chatbot;
use LeadersPath\Modules\LessonMeta\LessonMeta;
use LeadersPath\Modules\LessonObjectives\LessonObjectives;
use LeadersPath\Modules\LessonActivities\LessonActivities;

/**
 * Register LeadersPath modules with Divi's dependency tree.
 *
 * This hook fires when Divi is registering modules, allowing us to add
 * our custom modules to the Visual Builder.
 *
 * @since 0.1.0
 */
add_action(
	'divi_module_library_modules_dependency_tree',
	function ( $dependency_tree ) {
		$dependency_tree->add_dependency( new HelloModule() );
		$dependency_tree->add_dependency( new ActivityMeta() );
		$dependency_tree->add_dependency( new ContextLibrary() );
		$dependency_tree->add_dependency( new SkillsList() );
		$dependency_tree->add_dependency( new Chatbot() );
		$dependency_tree->add_dependency( new LessonMeta() );
		$dependency_tree->add_dependency( new LessonObjectives() );
		$dependency_tree->add_dependency( new LessonActivities() );
	}
);
