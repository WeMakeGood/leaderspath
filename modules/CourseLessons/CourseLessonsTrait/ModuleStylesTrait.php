<?php
/**
 * Module styles trait for Course Lessons.
 *
 * @package LeadersPath\Modules\CourseLessons
 * @since   0.5.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\CourseLessons\CourseLessonsTrait;

use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Options\Css\CssStyle;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait ModuleStylesTrait {

	/**
	 * Generate styles for the Course Lessons module.
	 *
	 * @since 0.5.0
	 *
	 * @param array $args Style arguments from Divi.
	 * @return void
	 */
	public static function module_styles( array $args ): void {
		$attrs       = $args['attrs'] ?? [];
		$elements    = $args['elements'];
		$settings    = $args['settings'] ?? [];
		$order_class = $args['orderClass'] ?? '';

		$list_display  = $attrs['list']['decoration']['layout']['desktop']['value']['display'] ?? 'flex';
		$list_selector = "{$order_class} .leaderspath_course_lessons__list";

		Style::add( [
			'id'            => $args['id'],
			'name'          => $args['name'],
			'orderIndex'    => $args['orderIndex'],
			'storeInstance' => $args['storeInstance'],
			'styles'        => [
				$elements->style( [
					'attrName'   => 'module',
					'styleProps' => [
						'disabledOn' => [
							'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
						],
					],
				] ),

				$elements->style( [ 'attrName' => 'list' ] ),

				// Display property — Layout panel doesn't generate it.
				[
					[
						'selector'    => $list_selector,
						'declaration' => "display: {$list_display};",
					],
				],

				$elements->style( [ 'attrName' => 'heading' ] ),
				$elements->style( [ 'attrName' => 'title' ] ),
				$elements->style( [ 'attrName' => 'number' ] ),
				$elements->style( [ 'attrName' => 'difficulty' ] ),
				$elements->style( [ 'attrName' => 'duration' ] ),
				$elements->style( [ 'attrName' => 'activityCount' ] ),
				$elements->style( [ 'attrName' => 'excerpt' ] ),

				CssStyle::style( [
					'selector'  => $order_class,
					'attr'      => $attrs['css'] ?? [],
					'cssFields' => self::custom_css(),
				] ),
			],
		] );
	}
}
