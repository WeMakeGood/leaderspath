<?php
/**
 * Chatbot core renderer.
 *
 * Builds the chat container HTML for both Activity sandbox
 * and Lesson Q&A modes. Detects the current CPT and reads
 * the appropriate ACF fields.
 *
 * @package LeadersPath\Modules\Chatbot
 * @since   0.6.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\Chatbot;

use LeadersPath\Modules\Shared\PostIdHelper;
use ET\Builder\Framework\Utility\HTMLUtility;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

class ChatbotRenderer {

	/**
	 * Render the chatbot container.
	 *
	 * @since 0.6.0
	 *
	 * @param array $options Render options from Content tab settings.
	 * @return string HTML output, or empty string if chatbot is disabled.
	 */
	public static function render( array $options = [] ): string {
		$data = self::get_data();

		if ( empty( $data ) || ! $data['enabled'] ) {
			return '';
		}

		return self::build_html( $data, $options );
	}

	/**
	 * Get chatbot configuration data from the current post.
	 *
	 * Detects whether we're on an Activity or Lesson and reads
	 * the appropriate ACF fields.
	 *
	 * @since 0.6.0
	 *
	 * @return array{
	 *     enabled: bool,
	 *     post_id: int,
	 *     post_type: string,
	 *     allow_model_switch: bool,
	 *     default_model: string,
	 * } Configuration data, or empty array if not on a supported CPT.
	 */
	private static function get_data(): array {
		// Try activity first.
		$post_id = PostIdHelper::get_post_id( 'activity' );
		if ( $post_id ) {
			$enabled = (bool) get_field( 'chatbot_enabled', $post_id );
			return [
				'enabled'            => $enabled,
				'post_id'            => $post_id,
				'post_type'          => 'activity',
				'allow_model_switch' => $enabled && (bool) get_field( 'chatbot_allow_model_switch', $post_id ),
				'default_model'      => $enabled ? ( (string) get_field( 'chatbot_model', $post_id ) ?: 'sonnet' ) : 'sonnet',
			];
		}

		// Try lesson.
		$post_id = PostIdHelper::get_post_id( 'lesson' );
		if ( $post_id ) {
			$enabled = (bool) get_field( 'lesson_chatbot_enabled', $post_id );
			return [
				'enabled'            => $enabled,
				'post_id'            => $post_id,
				'post_type'          => 'lesson',
				'allow_model_switch' => false, // Lesson chatbot never has model switch.
				'default_model'      => $enabled ? ( (string) get_field( 'lesson_chatbot_model', $post_id ) ?: 'sonnet' ) : 'sonnet',
			];
		}

		return [];
	}

	/**
	 * Build the chat container HTML.
	 *
	 * @since 0.6.0
	 *
	 * @param array $data    Configuration data from get_data().
	 * @param array $options Render options.
	 * @return string HTML output.
	 */
	private static function build_html( array $data, array $options ): string {
		$show_heading = $options['show_heading'] ?? true;
		$heading_text = $options['heading_text'] ?? __( 'AI Sandbox', 'leaderspath' );
		$empty_text   = $options['empty_text'] ?? __( 'Send a message to start the conversation.', 'leaderspath' );

		$parts = '';

		// Heading.
		if ( $show_heading && ! empty( $heading_text ) ) {
			$parts .= HTMLUtility::render( [
				'tag'               => 'h3',
				'attributes'        => [ 'class' => 'leaderspath_chatbot__heading' ],
				'childrenSanitizer' => 'esc_html',
				'children'          => $heading_text,
			] );
		}

		// Messages area.
		$empty_state = HTMLUtility::render( [
			'tag'               => 'div',
			'attributes'        => [ 'class' => 'leaderspath_chatbot__empty' ],
			'childrenSanitizer' => 'et_core_esc_previously',
			'children'          => HTMLUtility::render( [
				'tag'               => 'p',
				'childrenSanitizer' => 'esc_html',
				'children'          => $empty_text,
			] ),
		] );

		$parts .= HTMLUtility::render( [
			'tag'        => 'div',
			'attributes' => [
				'class'     => 'leaderspath_chatbot__messages',
				'role'      => 'log',
				'aria-live' => 'polite',
			],
			'childrenSanitizer' => 'et_core_esc_previously',
			'children'          => $empty_state,
		] );

		// Input area.
		$input_area_parts = '';

		// Model selector (activity only, when switching is allowed).
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

			$input_area_parts .= HTMLUtility::render( [
				'tag'        => 'select',
				'attributes' => [
					'class'      => 'leaderspath_chatbot__model_select',
					'aria-label' => __( 'Select AI model', 'leaderspath' ),
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $options_html,
			] );
		}

		// Send icon SVG.
		$send_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>';

		// Textarea + send button row.
		$input_row = HTMLUtility::render( [
			'tag'        => 'textarea',
			'attributes' => [
				'class'       => 'leaderspath_chatbot__input',
				'placeholder' => __( 'Type your message...', 'leaderspath' ),
				'rows'        => '1',
				'aria-label'  => __( 'Chat message', 'leaderspath' ),
			],
			'childrenSanitizer' => 'esc_html',
			'children'          => '',
		] );

		$input_row .= HTMLUtility::render( [
			'tag'        => 'button',
			'attributes' => [
				'class'      => 'leaderspath_chatbot__send',
				'type'       => 'button',
				'aria-label' => __( 'Send message', 'leaderspath' ),
			],
			'childrenSanitizer' => 'et_core_esc_previously',
			'children'          => $send_icon,
		] );

		$input_area_parts .= HTMLUtility::render( [
			'tag'               => 'div',
			'attributes'        => [ 'class' => 'leaderspath_chatbot__input_row' ],
			'childrenSanitizer' => 'et_core_esc_previously',
			'children'          => $input_row,
		] );

		$parts .= HTMLUtility::render( [
			'tag'               => 'div',
			'attributes'        => [ 'class' => 'leaderspath_chatbot__input_area' ],
			'childrenSanitizer' => 'et_core_esc_previously',
			'children'          => $input_area_parts,
		] );

		// Outer container with data attributes for JS.
		return HTMLUtility::render( [
			'tag'        => 'div',
			'attributes' => [
				'class'          => 'leaderspath_chatbot__container',
				'data-post-id'   => (string) $data['post_id'],
				'data-post-type' => $data['post_type'],
			],
			'childrenSanitizer' => 'et_core_esc_previously',
			'children'          => $parts,
		] );
	}
}
