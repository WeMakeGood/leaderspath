<?php
/**
 * Module classnames trait for Course Lessons.
 *
 * @package LeadersPath\Modules\CourseLessons
 * @since   0.5.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\CourseLessons\CourseLessonsTrait;

use LeadersPath\Modules\Shared\ModuleClassnamesTrait as SharedModuleClassnamesTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait ModuleClassnamesTrait {
	use SharedModuleClassnamesTrait;
}
