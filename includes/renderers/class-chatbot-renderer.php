<?php
/**
 * Chatbot renderer — interactive chat container for Activity sandbox or
 * Lesson Q&A. Reads chatbot_* / lesson_chatbot_* ACF fields and emits a
 * widget the frontend chatbot.js script attaches to.
 *
 * @package LeadersPath\Renderers
 * @since   0.7.0
 */

declare(strict_types=1);

namespace LeadersPath\Renderers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

class Chatbot_Renderer {

	public static function render( array $options = [], int $post_id = 0 ): string {
		$data = self::get_data( $post_id );
		if ( empty( $data ) || ! $data['enabled'] ) {
			return '';
		}

		$options = wp_parse_args( $options, [
			'show_heading' => true,
			'heading_text' => __( 'AI Sandbox', 'leaderspath' ),
			'empty_text'   => __( 'Send a message to start the conversation.', 'leaderspath' ),
		] );

		return self::build_html( $data, $options );
	}

	/**
	 * @return array{
	 *     enabled:bool,
	 *     post_id:int,
	 *     post_type:string,
	 *     allow_model_switch:bool,
	 *     default_model:string,
	 *     instructions:string
	 * }|array{}
	 */
	public static function get_data( int $override = 0 ): array {
		$activity_id = Post_Id_Helper::get_post_id( 'activity', $override );
		if ( $activity_id ) {
			$enabled = (bool) get_field( 'chatbot_enabled', $activity_id );
			return [
				'enabled'            => $enabled,
				'post_id'            => $activity_id,
				'post_type'          => 'activity',
				'allow_model_switch' => $enabled && (bool) get_field( 'chatbot_allow_model_switch', $activity_id ),
				'default_model'      => $enabled ? ( (string) get_field( 'chatbot_model', $activity_id ) ?: 'sonnet' ) : 'sonnet',
				// Learner-facing opening instructions (WYSIWYG). Rendered as the
				// first message in the chat; NOT sent to the AI. Empty for lessons.
				'instructions'       => $enabled ? (string) get_field( 'activity_instructions', $activity_id ) : '',
			];
		}

		$lesson_id = Post_Id_Helper::get_post_id( 'lesson', $override );
		if ( $lesson_id ) {
			$enabled = (bool) get_field( 'lesson_chatbot_enabled', $lesson_id );
			return [
				'enabled'            => $enabled,
				'post_id'            => $lesson_id,
				'post_type'          => 'lesson',
				'allow_model_switch' => false,
				'default_model'      => $enabled ? ( (string) get_field( 'lesson_chatbot_model', $lesson_id ) ?: 'sonnet' ) : 'sonnet',
				'instructions'       => '',
			];
		}

		return [];
	}

	private static function build_html( array $data, array $options ): string {
		$parts = '';

		if ( $options['show_heading'] && '' !== $options['heading_text'] ) {
			$parts .= sprintf(
				'<h3 class="leaderspath_chatbot__heading">%s</h3>',
				esc_html( $options['heading_text'] )
			);
		}

		// Learner-facing opening instructions, rendered as the first message.
		// Only for activities with instructions set. wp_kses_post: it's WYSIWYG.
		$instructions      = isset( $data['instructions'] ) ? (string) $data['instructions'] : '';
		$instructions_html = '';
		if ( '' !== trim( $instructions ) ) {
			$instructions_html = sprintf(
				'<div class="leaderspath_chatbot__instructions" role="note">%s</div>',
				wp_kses_post( $instructions )
			);
		}

		$parts .= sprintf(
			'<div class="leaderspath_chatbot__messages" role="log" aria-live="polite">%s<div class="leaderspath_chatbot__empty"><p>%s</p></div></div>',
			$instructions_html,
			esc_html( $options['empty_text'] )
		);

		// Stash the instructions markup so the reset control can re-render the
		// opening message client-side without a round trip. Inert until cloned.
		$instructions_template = '';
		if ( '' !== $instructions_html ) {
			$instructions_template = sprintf(
				'<template class="leaderspath_chatbot__instructions_tpl">%s</template>',
				$instructions_html
			);
		}

		$input_area_parts = '';

		if ( $data['allow_model_switch'] ) {
			$model_labels = [
				'sonnet'   => __( 'Sonnet', 'leaderspath' ),
				'haiku'    => __( 'Haiku', 'leaderspath' ),
				'opus-4.5' => __( 'Opus', 'leaderspath' ),
			];

			$options_html = '';
			foreach ( $model_labels as $value => $label ) {
				$selected      = ( $value === $data['default_model'] ) ? ' selected' : '';
				$options_html .= sprintf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $value ),
					$selected,
					esc_html( $label )
				);
			}

			$input_area_parts .= sprintf(
				'<select class="leaderspath_chatbot__model_select" aria-label="%s">%s</select>',
				esc_attr__( 'Select AI model', 'leaderspath' ),
				$options_html
			);
		}

		$send_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>';

		$input_area_parts .= sprintf(
			'<div class="leaderspath_chatbot__input_row"><textarea class="leaderspath_chatbot__input" placeholder="%s" rows="1" aria-label="%s"></textarea><button type="button" class="leaderspath_chatbot__send" aria-label="%s">%s</button></div>',
			esc_attr__( 'Type your message...', 'leaderspath' ),
			esc_attr__( 'Chat message', 'leaderspath' ),
			esc_attr__( 'Send message', 'leaderspath' ),
			$send_icon
		);

		$parts .= sprintf(
			'<div class="leaderspath_chatbot__input_area">%s</div>',
			$input_area_parts
		);

		// data-activity-id lets the lesson-page MutationObserver and the marker's
		// reset control (window.LeadersPath.resetActivity) target this instance.
		// Only meaningful for activities; empty for lessons.
		$activity_id_attr = ( 'activity' === $data['post_type'] )
			? sprintf( ' data-activity-id="%d"', $data['post_id'] )
			: '';

		return sprintf(
			'<div class="leaderspath_chatbot"><div class="leaderspath_chatbot__container" data-post-id="%d" data-post-type="%s"%s>%s%s</div></div>',
			$data['post_id'],
			esc_attr( $data['post_type'] ),
			$activity_id_attr,
			$parts,
			$instructions_template
		);
	}
}
