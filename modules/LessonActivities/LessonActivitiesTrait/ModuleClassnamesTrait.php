<?php
/**
 * Module classnames trait for Lesson Activities.
 *
 * @package LeadersPath\Modules\LessonActivities
 * @since   0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\LessonActivities\LessonActivitiesTrait;

use LeadersPath\Modules\Shared\ModuleClassnamesTrait as SharedModuleClassnamesTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait ModuleClassnamesTrait {
	use SharedModuleClassnamesTrait;
}
