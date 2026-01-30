<?php
/**
 * Chatbot::render_callback()
 *
 * @package LeadersPath\Modules\Chatbot
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\Chatbot\ChatbotTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// phpcs:disable ET.Sniffs.ValidVariableName.UsedPropertyNotSnakeCase -- WP uses snakeCase in \WP_Block_Parser_Block

use ET\Builder\Packages\Module\Module;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;
use LeadersPath\Modules\Chatbot\Chatbot;

/**
 * Render callback trait for Chatbot.
 *
 * @since 0.1.0
 */
trait RenderCallbackTrait {

	/**
	 * Get the current lesson post ID.
	 *
	 * Uses get_queried_object_id() for Theme Builder templates,
	 * with get_the_ID() as fallback.
	 *
	 * @since 0.1.0
	 *
	 * @return int Post ID, or 0 if not found.
	 */
	public static function get_lesson_id(): int {
		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		return (int) $post_id;
	}

	/**
	 * Check if chatbot is enabled for the current lesson.
	 *
	 * @since 0.1.0
	 *
	 * @param int $lesson_id Lesson post ID.
	 * @return bool Whether chatbot is enabled.
	 */
	public static function is_chatbot_enabled( int $lesson_id ): bool {
		if ( ! $lesson_id ) {
			return false;
		}

		return (bool) get_field( 'chatbot_enabled', $lesson_id );
	}

	/**
	 * Get chatbot configuration for a lesson.
	 *
	 * @since 0.1.0
	 *
	 * @param int $lesson_id Lesson post ID.
	 * @return array{model: string, allow_model_switch: bool, max_tokens: int, temperature: float}
	 */
	public static function get_chatbot_config( int $lesson_id ): array {
		return [
			'model'              => get_field( 'chatbot_model', $lesson_id ) ?: 'sonnet',
			'allow_model_switch' => (bool) get_field( 'chatbot_allow_model_switch', $lesson_id ),
			'max_tokens'         => (int) ( get_field( 'chatbot_max_tokens', $lesson_id ) ?: 4096 ),
			'temperature'        => (float) ( get_field( 'chatbot_temperature', $lesson_id ) ?? 0.7 ),
		];
	}

	/**
	 * Chatbot module render callback which outputs server side rendered HTML on the Front-End.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs    Block attributes that were saved by VB.
	 * @param string         $content  Block content.
	 * @param \WP_Block      $block    Parsed block object that being rendered.
	 * @param ModuleElements $elements ModuleElements instance.
	 *
	 * @return string HTML rendered of Chatbot module.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ): string {
		$lesson_id = self::get_lesson_id();
		$enabled   = self::is_chatbot_enabled( $lesson_id );

		// Render module title.
		$title = $elements->render(
			[
				'attrName' => 'title',
			]
		);

		// Build content based on whether chatbot is enabled.
		if ( ! $enabled || ! $lesson_id ) {
			$content_html = self::render_disabled_state( $attrs, $elements );
		} else {
			$content_html = self::render_chat_interface( $attrs, $elements, $lesson_id );
		}

		// Main content container.
		$inner_html = HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-chatbot__content',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $title . $content_html,
			]
		);

		$parent       = BlockParserStore::get_parent( $block->parsed_block['id'], $block->parsed_block['storeInstance'] );
		$parent_attrs = $parent->attrs ?? [];

		return Module::render(
			[
				// FE only.
				'orderIndex'         => $block->parsed_block['orderIndex'],
				'storeInstance'      => $block->parsed_block['storeInstance'],

				// VB equivalent.
				'attrs'              => $attrs,
				'elements'           => $elements,
				'id'                 => $block->parsed_block['id'],
				'name'               => $block->block_type->name,
				'moduleCategory'     => $block->block_type->category,
				'classnamesFunction' => [ Chatbot::class, 'module_classnames' ],
				'stylesComponent'    => [ Chatbot::class, 'module_styles' ],
				'parentAttrs'        => $parent_attrs,
				'parentId'           => $parent->id ?? '',
				'parentName'         => $parent->blockName ?? '',
				'children'           => [
					ElementComponents::component(
						[
							'attrs'         => $attrs['module']['decoration'] ?? [],
							'id'            => $block->parsed_block['id'],
							'orderIndex'    => $block->parsed_block['orderIndex'],
							'storeInstance' => $block->parsed_block['storeInstance'],
						]
					),
					$inner_html,
				],
			]
		);
	}

	/**
	 * Render the disabled/empty state message.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs    Module attributes.
	 * @param ModuleElements $elements ModuleElements instance.
	 *
	 * @return string HTML for disabled state.
	 */
	private static function render_disabled_state( array $attrs, $elements ): string {
		$empty_message = $elements->render(
			[
				'attrName' => 'emptyState',
			]
		);

		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-chatbot__disabled',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $empty_message,
			]
		);
	}

	/**
	 * Render the active chat interface.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs     Module attributes.
	 * @param ModuleElements $elements  ModuleElements instance.
	 * @param int            $lesson_id Lesson post ID.
	 *
	 * @return string HTML for chat interface.
	 */
	private static function render_chat_interface( array $attrs, $elements, int $lesson_id ): string {
		$config = self::get_chatbot_config( $lesson_id );

		// Enqueue frontend scripts and pass configuration.
		wp_enqueue_script( 'leaderspath-chatbot' );
		wp_enqueue_style( 'leaderspath-chatbot' );

		// Get placeholder and button text from module attributes.
		$placeholder_text = $attrs['input']['innerContent']['desktop']['value']
			?? __( 'Type your message...', 'leaderspath' );
		$send_button_text = $attrs['sendButton']['innerContent']['desktop']['value']
			?? __( 'Send', 'leaderspath' );

		// Generate unique ID for TinyMCE instance.
		$input_id = 'leaderspath-chatbot-input-' . $lesson_id;

		// Pass configuration to JavaScript.
		wp_localize_script(
			'leaderspath-chatbot',
			'leaderspathChatbot',
			[
				'apiUrl'      => rest_url( 'leaderspath/v1/chat' ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'lessonId'    => $lesson_id,
				'inputId'     => $input_id,
				'model'       => $config['model'],
				'placeholder' => $placeholder_text,
				'sendLabel'   => $send_button_text,
				'errorMsg'    => __( 'Something went wrong. Please try again.', 'leaderspath' ),
				'loadingMsg'  => __( 'Thinking...', 'leaderspath' ),
			]
		);

		// Build chat interface HTML.
		$messages_area = HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class'     => 'leaderspath-chatbot__messages',
					'aria-live' => 'polite',
					'role'      => 'log',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => '', // Messages inserted by JavaScript.
			]
		);

		// Input field - div for TinyMCE initialization.
		$input_field = HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'id'               => $input_id,
					'class'            => 'leaderspath-chatbot__input',
					'data-placeholder' => esc_attr( $placeholder_text ),
					'aria-label'       => __( 'Chat message', 'leaderspath' ),
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => '',
			]
		);

		// Send button.
		$send_button = HTMLUtility::render(
			[
				'tag'               => 'button',
				'tagEscaped'        => true,
				'attributes'        => [
					'type'  => 'submit',
					'class' => 'leaderspath-chatbot__send et_pb_button',
				],
				'childrenSanitizer' => 'esc_html',
				'children'          => $send_button_text,
			]
		);

		// Form container.
		$form = HTMLUtility::render(
			[
				'tag'               => 'form',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-chatbot__form',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $input_field . $send_button,
			]
		);

		// Chat container with data attributes.
		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class'          => 'leaderspath-chatbot__chat',
					'data-lesson-id' => (string) $lesson_id,
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $messages_area . $form,
			]
		);
	}
}
