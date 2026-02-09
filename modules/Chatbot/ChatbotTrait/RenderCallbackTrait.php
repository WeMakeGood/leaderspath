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
 * Supports two modes:
 * - Activity Sandbox: AI configured to demonstrate specific behaviors (on Activity pages)
 * - Lesson Q&A: Helpful assistant for lesson content questions (on Lesson pages)
 *
 * @since 0.1.0
 */
trait RenderCallbackTrait {

	/**
	 * Get the current post context (Activity or Lesson).
	 *
	 * Uses get_queried_object_id() for Theme Builder templates,
	 * with get_the_ID() as fallback.
	 *
	 * @since 0.1.0
	 *
	 * @return array{post_id: int, post_type: string, mode: string} Context info.
	 */
	public static function get_chatbot_context(): array {
		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$post_id   = (int) $post_id;
		$post_type = get_post_type( $post_id );

		// Determine mode based on post type.
		$mode = 'none';
		if ( 'leaderspath_activity' === $post_type ) {
			$mode = 'activity'; // Activity sandbox mode.
		} elseif ( 'leaderspath_lesson' === $post_type ) {
			$mode = 'lesson'; // Lesson Q&A mode.
		}

		return [
			'post_id'   => $post_id,
			'post_type' => $post_type ?: '',
			'mode'      => $mode,
		];
	}

	/**
	 * Get the current activity post ID.
	 *
	 * @since 0.1.0
	 * @deprecated Use get_chatbot_context() instead.
	 *
	 * @return int Post ID, or 0 if not found.
	 */
	public static function get_activity_id(): int {
		$context = self::get_chatbot_context();
		return 'activity' === $context['mode'] ? $context['post_id'] : 0;
	}

	/**
	 * Check if chatbot is enabled for the current context.
	 *
	 * @since 0.1.0
	 *
	 * @param array $context Chatbot context from get_chatbot_context().
	 * @return bool Whether chatbot is enabled.
	 */
	public static function is_chatbot_enabled( array $context ): bool {
		if ( ! $context['post_id'] || 'none' === $context['mode'] ) {
			return false;
		}

		if ( 'activity' === $context['mode'] ) {
			return (bool) get_field( 'chatbot_enabled', $context['post_id'] );
		}

		if ( 'lesson' === $context['mode'] ) {
			return (bool) get_field( 'lesson_chatbot_enabled', $context['post_id'] );
		}

		return false;
	}

	/**
	 * Get chatbot configuration for the current context.
	 *
	 * @since 0.1.0
	 *
	 * @param array $context Chatbot context from get_chatbot_context().
	 * @return array{model: string, allow_model_switch: bool, max_tokens: int, temperature: float}
	 */
	public static function get_chatbot_config( array $context ): array {
		if ( 'activity' === $context['mode'] ) {
			return [
				'model'              => get_field( 'chatbot_model', $context['post_id'] ) ?: 'sonnet',
				'allow_model_switch' => (bool) get_field( 'chatbot_allow_model_switch', $context['post_id'] ),
				'max_tokens'         => (int) ( get_field( 'chatbot_max_tokens', $context['post_id'] ) ?: 4096 ),
				'temperature'        => (float) ( get_field( 'chatbot_temperature', $context['post_id'] ) ?? 0.7 ),
			];
		}

		if ( 'lesson' === $context['mode'] ) {
			return [
				'model'              => get_field( 'lesson_chatbot_model', $context['post_id'] ) ?: 'sonnet',
				'allow_model_switch' => false, // Lesson Q&A doesn't support model switching.
				'max_tokens'         => (int) ( get_field( 'lesson_chatbot_max_tokens', $context['post_id'] ) ?: 4096 ),
				'temperature'        => (float) ( get_field( 'lesson_chatbot_temperature', $context['post_id'] ) ?? 0.7 ),
			];
		}

		// Default config.
		return [
			'model'              => 'sonnet',
			'allow_model_switch' => false,
			'max_tokens'         => 4096,
			'temperature'        => 0.7,
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
		$context = self::get_chatbot_context();
		$enabled = self::is_chatbot_enabled( $context );

		// Render module title.
		$title = $elements->render(
			[
				'attrName' => 'title',
			]
		);

		// Build content based on whether chatbot is enabled.
		if ( ! $enabled || 'none' === $context['mode'] ) {
			$content_html = self::render_disabled_state( $attrs, $elements );
		} else {
			$content_html = self::render_chat_interface( $attrs, $elements, $context );
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
	 * @param array          $attrs   Module attributes.
	 * @param ModuleElements $elements ModuleElements instance.
	 * @param array          $context Chatbot context.
	 *
	 * @return string HTML for chat interface.
	 */
	private static function render_chat_interface( array $attrs, $elements, array $context ): string {
		$config = self::get_chatbot_config( $context );

		// Enqueue frontend scripts and pass configuration.
		wp_enqueue_script( 'leaderspath-chatbot' );
		wp_enqueue_style( 'leaderspath-chatbot' );

		// Get placeholder and button text from module attributes.
		$placeholder_text = $attrs['input']['innerContent']['desktop']['value']
			?? __( 'Type your message...', 'leaderspath' );
		$send_button_text = $attrs['sendButton']['innerContent']['desktop']['value']
			?? __( 'Send', 'leaderspath' );

		// Generate unique ID for TinyMCE instance.
		$input_id = 'leaderspath-chatbot-input-' . $context['post_id'];

		// Build JS configuration based on mode.
		$js_config = [
			'apiUrl'      => rest_url( 'leaderspath/v1/chat' ),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'mode'        => $context['mode'],
			'inputId'     => $input_id,
			'model'       => $config['model'],
			'placeholder' => $placeholder_text,
			'sendLabel'   => $send_button_text,
			'errorMsg'    => __( 'Something went wrong. Please try again.', 'leaderspath' ),
			'loadingMsg'  => __( 'Thinking...', 'leaderspath' ),
		];

		// Add context-specific IDs.
		if ( 'activity' === $context['mode'] ) {
			$js_config['activityId'] = $context['post_id'];
		} elseif ( 'lesson' === $context['mode'] ) {
			$js_config['lessonId'] = $context['post_id'];
		}

		// Pass configuration to JavaScript.
		wp_localize_script( 'leaderspath-chatbot', 'leaderspathChatbot', $js_config );

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

		// Data attributes for the chat container.
		$data_attrs = [
			'class'     => 'leaderspath-chatbot__chat',
			'data-mode' => $context['mode'],
		];

		if ( 'activity' === $context['mode'] ) {
			$data_attrs['data-activity-id'] = (string) $context['post_id'];
		} elseif ( 'lesson' === $context['mode'] ) {
			$data_attrs['data-lesson-id'] = (string) $context['post_id'];
		}

		// Chat container with data attributes.
		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => $data_attrs,
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $messages_area . $form,
			]
		);
	}
}
