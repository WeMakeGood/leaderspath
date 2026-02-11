<?php
/**
 * Core renderer for Activity Meta module.
 *
 * Pure data access and HTML generation — no Divi dependencies.
 * Reads ACF fields from the current activity and returns semantic HTML.
 *
 * @package LeadersPath\Modules\ActivityMeta
 * @since   0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\ActivityMeta;

use LeadersPath\Modules\Shared\PostIdHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Activity Meta core renderer.
 *
 * @since 0.4.0
 */
class ActivityMetaRenderer {

	/**
	 * Model display labels keyed by ACF select value.
	 */
	private const MODEL_LABELS = [
		'sonnet'   => 'Claude Sonnet',
		'haiku'    => 'Claude Haiku',
		'opus-4.5' => 'Claude Opus 4.5',
	];

	/**
	 * Render the activity meta HTML.
	 *
	 * @since 0.4.0
	 *
	 * @param array $options {
	 *     Optional. Rendering options.
	 *
	 *     @type bool   $show_duration      Whether to show duration. Default true.
	 *     @type string $duration_label     Label text for duration. Default 'Duration'.
	 *     @type bool   $show_model         Whether to show model. Default true.
	 *     @type string $model_label        Label text for model. Default 'Model'.
	 *     @type bool   $show_model_switch  Whether to show model switch indicator. Default true.
	 *     @type string $model_switch_label Label text for model switch. Default 'Model Switching'.
	 * }
	 * @return string HTML output, or empty string if no activity found.
	 */
	public static function render( array $options = [] ): string {
		$options = wp_parse_args( $options, [
			'show_duration'      => true,
			'duration_label'     => __( 'Duration', 'leaderspath' ),
			'show_model'         => true,
			'model_label'        => __( 'Model', 'leaderspath' ),
			'show_model_switch'  => true,
			'model_switch_label' => __( 'Model Switching', 'leaderspath' ),
		] );

		$post_id = PostIdHelper::get_post_id( 'activity' );

		if ( ! $post_id ) {
			return '';
		}

		$data = self::get_data( $post_id );

		return self::build_html( $data, $options );
	}

	/**
	 * Read ACF fields for an activity post.
	 *
	 * @since 0.4.0
	 *
	 * @param int $post_id Activity post ID.
	 * @return array {
	 *     @type int    $duration           Duration in minutes.
	 *     @type string $model              Model key (sonnet|haiku|opus-4.5).
	 *     @type bool   $chatbot_enabled    Whether the chatbot is enabled.
	 *     @type bool   $allow_model_switch Whether model switching is allowed.
	 * }
	 */
	public static function get_data( int $post_id ): array {
		$duration           = (int) get_field( 'activity_duration', $post_id );
		$chatbot_enabled    = (bool) get_field( 'chatbot_enabled', $post_id );
		$model              = (string) get_field( 'chatbot_model', $post_id );
		$allow_model_switch = (bool) get_field( 'chatbot_allow_model_switch', $post_id );

		return [
			'duration'           => $duration,
			'model'              => $model,
			'chatbot_enabled'    => $chatbot_enabled,
			'allow_model_switch' => $allow_model_switch,
		];
	}

	/**
	 * Build semantic HTML from activity data.
	 *
	 * @since 0.4.0
	 *
	 * @param array $data    Activity data from get_data().
	 * @param array $options Rendering options.
	 * @return string HTML output.
	 */
	private static function build_html( array $data, array $options ): string {
		$items = '';

		if ( $options['show_duration'] && $data['duration'] > 0 ) {
			$items .= sprintf(
				'<dt class="leaderspath_activity_meta__label">%s</dt><dd class="leaderspath_activity_meta__duration">%s</dd>',
				esc_html( $options['duration_label'] ),
				esc_html(
					sprintf(
						/* translators: %d: number of minutes */
						_n( '%d minute', '%d minutes', $data['duration'], 'leaderspath' ),
						$data['duration']
					)
				)
			);
		}

		if ( $options['show_model'] && $data['chatbot_enabled'] && '' !== $data['model'] ) {
			$model_label = self::MODEL_LABELS[ $data['model'] ] ?? $data['model'];
			$items .= sprintf(
				'<dt class="leaderspath_activity_meta__label">%s</dt><dd class="leaderspath_activity_meta__model" data-model="%s">%s</dd>',
				esc_html( $options['model_label'] ),
				esc_attr( $data['model'] ),
				esc_html( $model_label )
			);
		}

		if ( $options['show_model_switch'] && $data['chatbot_enabled'] ) {
			$switch_value = $data['allow_model_switch']
				? __( 'Allowed', 'leaderspath' )
				: __( 'Disabled', 'leaderspath' );
			$items .= sprintf(
				'<dt class="leaderspath_activity_meta__label">%s</dt><dd class="leaderspath_activity_meta__model-switch" data-allowed="%s">%s</dd>',
				esc_html( $options['model_switch_label'] ),
				$data['allow_model_switch'] ? 'yes' : 'no',
				esc_html( $switch_value )
			);
		}

		if ( '' === $items ) {
			return '';
		}

		return sprintf(
			'<dl class="leaderspath_activity_meta__list">%s</dl>',
			$items
		);
	}
}
