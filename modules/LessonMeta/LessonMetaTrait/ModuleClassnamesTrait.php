<?php
/**
 * Module classnames trait for Lesson Meta.
 *
 * Delegates to the shared ModuleClassnamesTrait since all LeadersPath
 * modules use identical classname logic.
 *
 * @package LeadersPath\Modules\LessonMeta
 * @since   0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonMeta\LessonMetaTrait;

use LeadersPath\Modules\Shared\ModuleClassnamesTrait as SharedModuleClassnamesTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait ModuleClassnamesTrait {
	use SharedModuleClassnamesTrait;
}
