<?php
/**
 * ContextLibrary::render_callback()
 *
 * @package LeadersPath\Modules\ContextLibrary
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\ContextLibrary\ContextLibraryTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// phpcs:disable ET.Sniffs.ValidVariableName.UsedPropertyNotSnakeCase -- WP uses snakeCase in \WP_Block_Parser_Block

use ET\Builder\Packages\Module\Module;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;
use LeadersPath\Modules\ContextLibrary\ContextLibrary;

/**
 * Render callback trait for ContextLibrary.
 *
 * @since 0.1.0
 */
trait RenderCallbackTrait {

	/**
	 * File type display names mapping.
	 *
	 * @var array<string, string>
	 */
	private static array $file_type_labels = [
		'system_prompt'   => 'System Prompt',
		'knowledge_base'  => 'Knowledge Base',
		'instructions'    => 'Instructions',
		'other'           => 'Context',
	];

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
	 * Get context files for the current lesson.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, array{id: int, title: string, description: string, file_type: string, content: string}>
	 */
	public static function get_context_files(): array {
		$post_id       = self::get_lesson_id();
		$context_files = [];

		if ( ! $post_id ) {
			return $context_files;
		}

		$context_ids = get_field( 'chatbot_context_files', $post_id ) ?: [];

		foreach ( $context_ids as $context_id ) {
			$post = get_post( $context_id );

			if ( ! $post ) {
				continue;
			}

			$context_files[] = [
				'id'          => $post->ID,
				'title'       => $post->post_title,
				'description' => get_field( 'context_description', $post->ID ) ?: '',
				'file_type'   => get_field( 'context_file_type', $post->ID ) ?: 'other',
				'content'     => $post->post_content,
			];
		}

		return $context_files;
	}

	/**
	 * Context Library module render callback which outputs server side rendered HTML on the Front-End.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs    Block attributes that were saved by VB.
	 * @param string         $content  Block content.
	 * @param \WP_Block      $block    Parsed block object that being rendered.
	 * @param ModuleElements $elements ModuleElements instance.
	 *
	 * @return string HTML rendered of Context Library module.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ): string {
		$context_files = self::get_context_files();

		// Enqueue modal scripts if we have files and view button is enabled.
		$show_view_button = $attrs['contextFiles']['advanced']['showViewButton']['desktop']['value'] ?? 'on';
		if ( ! empty( $context_files ) && 'on' === $show_view_button ) {
			wp_enqueue_script( 'leaderspath-context-modal' );
			wp_enqueue_style( 'leaderspath-context-modal' );

			// Pass content data to JavaScript.
			wp_localize_script(
				'leaderspath-context-modal',
				'leaderspathContextFiles',
				array_map(
					function ( $file ) {
						return [
							'id'      => $file['id'],
							'title'   => esc_html( $file['title'] ),
							'content' => wp_kses_post( $file['content'] ),
						];
					},
					$context_files
				)
			);
		}

		// Render module title.
		$title = $elements->render(
			[
				'attrName' => 'title',
			]
		);

		// Build content based on whether we have context files.
		if ( empty( $context_files ) ) {
			$items_html = self::render_empty_state( $attrs, $elements );
		} else {
			$items_html = self::render_context_items( $attrs, $elements, $context_files );
		}

		// Content container.
		$content_html = HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-context-library__content',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $title . $items_html,
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
				'classnamesFunction' => [ ContextLibrary::class, 'module_classnames' ],
				'stylesComponent'    => [ ContextLibrary::class, 'module_styles' ],
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
					$content_html,
				],
			]
		);
	}

	/**
	 * Render the empty state message.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs    Module attributes.
	 * @param ModuleElements $elements ModuleElements instance.
	 *
	 * @return string HTML for empty state.
	 */
	private static function render_empty_state( array $attrs, $elements ): string {
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
					'class' => 'leaderspath-context-library__empty',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $empty_message,
			]
		);
	}

	/**
	 * Render the context file items grid.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs         Module attributes.
	 * @param ModuleElements $elements      ModuleElements instance.
	 * @param array          $context_files Context file data.
	 *
	 * @return string HTML for context items grid.
	 */
	private static function render_context_items( array $attrs, $elements, array $context_files ): string {
		// Get visibility toggles.
		$show_description    = $attrs['contextFiles']['advanced']['showDescription']['desktop']['value'] ?? 'on';
		$show_file_type      = $attrs['contextFiles']['advanced']['showFileType']['desktop']['value'] ?? 'on';
		$show_view_button    = $attrs['contextFiles']['advanced']['showViewButton']['desktop']['value'] ?? 'on';
		$show_download_button = $attrs['contextFiles']['advanced']['showDownloadButton']['desktop']['value'] ?? 'on';

		$items_html = '';

		foreach ( $context_files as $file ) {
			$items_html .= self::render_single_item(
				$attrs,
				$elements,
				$file,
				$show_description,
				$show_file_type,
				$show_view_button,
				$show_download_button
			);
		}

		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-context-library__grid',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $items_html,
			]
		);
	}

	/**
	 * Render a single context file item card.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs               Module attributes.
	 * @param ModuleElements $elements            ModuleElements instance.
	 * @param array          $file                Single context file data.
	 * @param string         $show_description    Show description toggle value.
	 * @param string         $show_file_type      Show file type toggle value.
	 * @param string         $show_view_button    Show view button toggle value.
	 * @param string         $show_download_button Show download button toggle value.
	 *
	 * @return string HTML for single item card.
	 */
	private static function render_single_item(
		array $attrs,
		$elements,
		array $file,
		string $show_description,
		string $show_file_type,
		string $show_view_button,
		string $show_download_button
	): string {
		// Item title.
		$title_html = HTMLUtility::render(
			[
				'tag'               => 'h4',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-context-library__item-title',
				],
				'childrenSanitizer' => 'esc_html',
				'children'          => $file['title'],
			]
		);

		// File type badge.
		$badge_html = '';
		if ( 'on' === $show_file_type ) {
			$file_type_label = self::$file_type_labels[ $file['file_type'] ] ?? self::$file_type_labels['other'];
			$badge_html      = HTMLUtility::render(
				[
					'tag'               => 'span',
					'tagEscaped'        => true,
					'attributes'        => [
						'class' => 'leaderspath-context-library__item-badge',
					],
					'childrenSanitizer' => 'esc_html',
					'children'          => $file_type_label,
				]
			);
		}

		// Description.
		$description_html = '';
		if ( 'on' === $show_description && ! empty( $file['description'] ) ) {
			$description_html = HTMLUtility::render(
				[
					'tag'               => 'p',
					'tagEscaped'        => true,
					'attributes'        => [
						'class' => 'leaderspath-context-library__item-description',
					],
					'childrenSanitizer' => 'esc_html',
					'children'          => $file['description'],
				]
			);
		}

		// Header section (title + badge).
		$header_html = HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-context-library__item-header',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $title_html . $badge_html,
			]
		);

		// Buttons.
		$buttons_html = self::render_item_buttons(
			$file,
			$show_view_button,
			$show_download_button
		);

		// Assemble card.
		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class'        => 'leaderspath-context-library__item',
					'data-file-id' => (string) $file['id'],
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $header_html . $description_html . $buttons_html,
			]
		);
	}

	/**
	 * Render the action buttons for a context file item.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $file                 Context file data.
	 * @param string $show_view_button     Show view button toggle value.
	 * @param string $show_download_button Show download button toggle value.
	 *
	 * @return string HTML for buttons container.
	 */
	private static function render_item_buttons(
		array $file,
		string $show_view_button,
		string $show_download_button
	): string {
		$buttons = '';

		// View Content button.
		if ( 'on' === $show_view_button ) {
			$buttons .= HTMLUtility::render(
				[
					'tag'               => 'button',
					'tagEscaped'        => true,
					'attributes'        => [
						'type'         => 'button',
						'class'        => 'leaderspath-context-library__button leaderspath-context-library__view-button et_pb_button',
						'data-file-id' => (string) $file['id'],
					],
					'childrenSanitizer' => 'esc_html',
					'children'          => __( 'View Content', 'leaderspath' ),
				]
			);
		}

		// Download button.
		if ( 'on' === $show_download_button ) {
			$download_url = rest_url( 'leaderspath/v1/context/' . $file['id'] . '/download' );
			$buttons     .= HTMLUtility::render(
				[
					'tag'               => 'a',
					'tagEscaped'        => true,
					'attributes'        => [
						'href'     => esc_url( $download_url ),
						'class'    => 'leaderspath-context-library__button leaderspath-context-library__download-button et_pb_button',
						'target'   => '_blank',
						'rel'      => 'noopener noreferrer',
						'download' => sanitize_file_name( $file['title'] . '.md' ),
					],
					'childrenSanitizer' => 'esc_html',
					'children'          => __( 'Download', 'leaderspath' ),
				]
			);
		}

		if ( empty( $buttons ) ) {
			return '';
		}

		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-context-library__item-buttons',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $buttons,
			]
		);
	}
}
