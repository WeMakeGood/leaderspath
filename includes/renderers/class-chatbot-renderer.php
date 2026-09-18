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
	 *     instructions:string,
	 *     has_skills:bool,
	 *     cohort_id?:int
	 * }|array{}
	 */
	public static function get_data( int $override = 0 ): array {
		// No sample-post fallback: the live chatbot must render the specific
		// activity/lesson requested, never a stand-in. A miss returns [] so the
		// widget renders nothing instead of the wrong AI configuration.
		$activity_id = Post_Id_Helper::get_post_id( 'activity', $override, false );
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
				// Skills-enabled activities get a container, which is what the
				// container_upload file-attachment mechanism requires — drives
				// whether the upload UI renders at all (see build_html()).
				'has_skills'         => $enabled && ! empty( ( new \LeadersPath\Includes\Claude_API() )->get_skills_for_api( $activity_id ) ),
				// 0 unless this page was reached via
				// /learn/{cohort}/lesson/{lesson}/ (Cohort_Rewrite) — baked into
				// the widget's markup so chatbot.js can send it on every chat
				// request from this instance (see docs/TASKS.md Phase 15,
				// "cohort context files load into activity chat"). The server
				// re-verifies enrollment before using it for anything
				// (REST_API::resolve_cohort_id()) — this is just how it travels
				// from render time into the request, not a trust boundary.
				'cohort_id'          => \LeadersPath\Includes\Cohort_Rewrite::get_current_cohort_id(),
			];
		}

		$lesson_id = Post_Id_Helper::get_post_id( 'lesson', $override, false );
		if ( $lesson_id ) {
			$enabled = (bool) get_field( 'lesson_chatbot_enabled', $lesson_id );
			return [
				'enabled'            => $enabled,
				'post_id'            => $lesson_id,
				'post_type'          => 'lesson',
				'allow_model_switch' => false,
				'default_model'      => $enabled ? ( (string) get_field( 'lesson_chatbot_model', $lesson_id ) ?: 'sonnet' ) : 'sonnet',
				'instructions'       => '',
				// Lessons have no skills/container — never eligible for uploads.
				'has_skills'         => false,
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

		// File upload UI: only for skills-enabled activities — a container_upload
		// requires a code-execution container, which this plugin only provisions
		// when skills are configured. Skill-less activities/lessons get no upload
		// markup at all (absent from the DOM, not hidden-disabled).
		$has_skills   = ! empty( $data['has_skills'] );
		$attach_html  = '';
		$privacy_html = '';

		if ( $has_skills ) {
			$attach_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>';

			$attach_html = sprintf(
				'<button type="button" class="leaderspath_chatbot__attach" aria-label="%s">%s</button><input type="file" class="leaderspath_chatbot__file_input" aria-hidden="true" hidden>',
				esc_attr__( 'Attach a file', 'leaderspath' ),
				$attach_icon
			);

			$privacy_html = sprintf(
				'<p class="leaderspath_chatbot__privacy_note">%s</p>',
				esc_html__( "Files you attach are sent to Anthropic's AI service and may be retained there for up to 30 days. Don't upload anything you wouldn't want stored briefly outside this site.", 'leaderspath' )
			);
		}

		$input_area_parts .= sprintf(
			'<div class="leaderspath_chatbot__input_row">%s<textarea class="leaderspath_chatbot__input" placeholder="%s" rows="1" aria-label="%s"></textarea><button type="button" class="leaderspath_chatbot__send" aria-label="%s">%s</button></div>%s',
			$attach_html,
			esc_attr__( 'Type your message...', 'leaderspath' ),
			esc_attr__( 'Chat message', 'leaderspath' ),
			esc_attr__( 'Send message', 'leaderspath' ),
			$send_icon,
			$privacy_html
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

		// data-has-skills lets chatbot.js decide, per widget instance, whether to
		// wire up the attach button / drop zone / file input for this container
		// (a page can host multiple chatbots with different skills configs).
		$has_skills_attr = sprintf( ' data-has-skills="%s"', $has_skills ? '1' : '0' );

		// data-cohort-id carries the learner's cohort (0 if none) into every
		// chat request chatbot.js sends from this widget instance — see
		// get_data()'s cohort_id key.
		$cohort_id_attr = sprintf( ' data-cohort-id="%d"', (int) ( $data['cohort_id'] ?? 0 ) );

		return sprintf(
			'<div class="leaderspath_chatbot"><div class="leaderspath_chatbot__container" data-post-id="%d" data-post-type="%s"%s%s%s>%s%s</div></div>',
			$data['post_id'],
			esc_attr( $data['post_type'] ),
			$activity_id_attr,
			$has_skills_attr,
			$cohort_id_attr,
			$parts,
			$instructions_template
		);
	}
}
