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
use LeadersPath\Modules\LessonMeta\LessonMeta;

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
		$dependency_tree->add_dependency( new LessonMeta() );
	}
);
