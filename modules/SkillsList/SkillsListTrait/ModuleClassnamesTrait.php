<?php
/**
 * Module classnames trait for Skills List.
 *
 * @package LeadersPath\Modules\SkillsList
 * @since   0.5.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\SkillsList\SkillsListTrait;

use LeadersPath\Modules\Shared\ModuleClassnamesTrait as SharedModuleClassnamesTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait ModuleClassnamesTrait {
	use SharedModuleClassnamesTrait;
}
