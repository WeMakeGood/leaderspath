<?php
/**
 * Module classnames trait for Chatbot.
 *
 * @package LeadersPath\Modules\Chatbot
 * @since   0.6.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\Chatbot\ChatbotTrait;

use LeadersPath\Modules\Shared\ModuleClassnamesTrait as SharedModuleClassnamesTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait ModuleClassnamesTrait {
	use SharedModuleClassnamesTrait;
}
