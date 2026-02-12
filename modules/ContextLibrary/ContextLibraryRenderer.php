<?php
/**
 * Core renderer for Context Library module.
 *
 * Pure data access and HTML generation — no Divi dependencies.
 * Reads the chatbot_context_files ACF relationship and returns card grid HTML.
 *
 * @package LeadersPath\Modules\ContextLibrary
 * @since   0.5.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\ContextLibrary;

use LeadersPath\Modules\Shared\PostIdHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Context Library core renderer.
 *
 * @since 0.5.0
 */
class ContextLibraryRenderer {

	/**
	 * Human-readable labels for context file types.
	 */
	private const TYPE_LABELS = [
		'system_prompt'  => 'System Prompt',
		'knowledge_base' => 'Knowledge Base',
		'instructions'   => 'Instructions',
		'examples'       => 'Examples',
		'other'          => 'Other',
	];

	/**
	 * Render the context library HTML.
	 *
	 * @since 0.5.0
	 *
	 * @param array $options {
	 *     Optional. Rendering options.
	 *
	 *     @type bool   $show_heading     Whether to show the heading. Default true.
	 *     @type string $heading_text     Heading text. Default 'Context Library'.
	 *     @type bool   $show_icon        Whether to show file type icon. Default true.
	 *     @type bool   $show_description Whether to show description preview. Default true.
	 *     @type bool   $show_meta        Whether to show type badge + version. Default true.
	 *     @type bool   $show_view        Whether to show view button. Default true.
	 *     @type bool   $show_download    Whether to show download button. Default true.
	 * }
	 * @return string HTML output, or empty string if no activity/context files found.
	 */
	public static function render( array $options = [] ): string {
		$options = wp_parse_args( $options, [
			'show_heading'     => true,
			'heading_text'     => __( 'Context Library', 'leaderspath' ),
			'show_icon'        => true,
			'show_description' => true,
			'show_meta'        => true,
			'show_view'        => true,
			'show_download'    => true,
		] );

		$post_id = PostIdHelper::get_post_id( 'activity' );

		if ( ! $post_id ) {
			return '';
		}

		$context_files = self::get_data( $post_id );

		return self::build_html( $context_files, $options );
	}

	/**
	 * Read context file data from the ACF relationship field.
	 *
	 * @since 0.5.0
	 *
	 * @param int $post_id Activity post ID.
	 * @return array[] Array of context file data arrays.
	 */
	public static function get_data( int $post_id ): array {
		$context_ids = get_field( 'chatbot_context_files', $post_id );

		if ( ! is_array( $context_ids ) || empty( $context_ids ) ) {
			return [];
		}

		$files = [];
		foreach ( $context_ids as $context_id ) {
			$post = get_post( $context_id );
			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}

			$description = (string) get_field( 'context_description', $context_id );
			$file_type   = (string) get_field( 'context_file_type', $context_id );
			$version     = (string) get_field( 'context_version', $context_id );

			$files[] = [
				'id'          => $context_id,
				'title'       => get_the_title( $context_id ),
				'description' => $description,
				'file_type'   => $file_type ?: 'other',
				'type_label'  => self::TYPE_LABELS[ $file_type ] ?? self::TYPE_LABELS['other'],
				'version'     => $version,
			];
		}

		return $files;
	}

	/**
	 * Build semantic HTML from context file data.
	 *
	 * @since 0.5.0
	 *
	 * @param array[] $files   Array of context file data arrays.
	 * @param array   $options Rendering options.
	 * @return string HTML output.
	 */
	private static function build_html( array $files, array $options ): string {
		$heading = '';
		if ( $options['show_heading'] && '' !== $options['heading_text'] ) {
			$heading = sprintf(
				'<h3 class="leaderspath_context_library__heading">%s</h3>',
				esc_html( $options['heading_text'] )
			);
		}

		if ( empty( $files ) ) {
			return sprintf(
				'%s<p class="leaderspath_context_library__empty">%s</p>',
				$heading,
				esc_html__( 'No context files configured for this activity.', 'leaderspath' )
			);
		}

		$cards = '';
		foreach ( $files as $file ) {
			$icon = '';
			if ( $options['show_icon'] ) {
				$icon = sprintf(
					'<div class="leaderspath_context_library__card_icon" data-type="%s">%s</div>',
					esc_attr( $file['file_type'] ),
					self::get_type_icon( $file['file_type'] )
				);
			}

			$title = sprintf(
				'<h4 class="leaderspath_context_library__card_title">%s</h4>',
				esc_html( $file['title'] )
			);

			$desc = '';
			if ( $options['show_description'] && '' !== $file['description'] ) {
				$truncated = wp_trim_words( $file['description'], 20, '&hellip;' );
				$desc = sprintf(
					'<p class="leaderspath_context_library__card_desc">%s</p>',
					esc_html( $truncated )
				);
			}

			$meta = '';
			if ( $options['show_meta'] ) {
				$meta_parts = sprintf(
					'<span class="leaderspath_context_library__badge" data-type="%s">%s</span>',
					esc_attr( $file['file_type'] ),
					esc_html( $file['type_label'] )
				);
				if ( '' !== $file['version'] ) {
					$meta_parts .= sprintf(
						'<span class="leaderspath_context_library__version">v%s</span>',
						esc_html( $file['version'] )
					);
				}
				$meta = sprintf(
					'<div class="leaderspath_context_library__card_meta">%s</div>',
					$meta_parts
				);
			}

			$actions = '';
			$action_parts = '';
			if ( $options['show_view'] ) {
				$action_parts .= sprintf(
					'<button type="button" class="leaderspath_context_library__view" data-context-id="%d" data-context-title="%s">%s</button>',
					$file['id'],
					esc_attr( $file['title'] ),
					esc_html__( 'View', 'leaderspath' )
				);
			}
			if ( $options['show_download'] ) {
				$action_parts .= sprintf(
					'<button type="button" class="leaderspath_context_library__download" data-context-id="%d" data-context-title="%s">%s</button>',
					$file['id'],
					esc_attr( $file['title'] ),
					esc_html__( 'Download', 'leaderspath' )
				);
			}
			if ( '' !== $action_parts ) {
				$actions = sprintf(
					'<div class="leaderspath_context_library__actions">%s</div>',
					$action_parts
				);
			}

			$cards .= sprintf(
				'<li class="leaderspath_context_library__item"><article class="leaderspath_context_library__card">%s%s%s%s%s</article></li>',
				$icon,
				$title,
				$desc,
				$meta,
				$actions
			);
		}

		$modal = self::build_modal_html();

		return sprintf(
			'%s<ul class="leaderspath_context_library__grid">%s</ul>%s',
			$heading,
			$cards,
			$modal
		);
	}

	/**
	 * Build the modal container HTML (hidden by default).
	 *
	 * @since 0.5.0
	 *
	 * @return string Modal HTML.
	 */
	private static function build_modal_html(): string {
		return sprintf(
			'<div class="leaderspath_modal__overlay" hidden>
				<div class="leaderspath_modal__dialog" role="dialog" aria-modal="true">
					<header class="leaderspath_modal__header">
						<h2 class="leaderspath_modal__title"></h2>
						<button type="button" class="leaderspath_modal__close" aria-label="%s">&times;</button>
					</header>
					<div class="leaderspath_modal__body">
						<div class="leaderspath_modal__content"></div>
					</div>
					<footer class="leaderspath_modal__footer">
						<button type="button" class="leaderspath_modal__download_btn">%s</button>
					</footer>
				</div>
			</div>',
			esc_attr__( 'Close', 'leaderspath' ),
			esc_html__( 'Download Full Content', 'leaderspath' )
		);
	}

	/**
	 * Get SVG icon markup for a context file type.
	 *
	 * @since 0.5.0
	 *
	 * @param string $type Context file type.
	 * @return string SVG markup.
	 */
	private static function get_type_icon( string $type ): string {
		$icons = [
			'system_prompt'  => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor"><path d="M17 7h-4v2h4c1.65 0 3 1.35 3 3s-1.35 3-3 3h-4v2h4c2.76 0 5-2.24 5-5s-2.24-5-5-5zm-6 8H7c-1.65 0-3-1.35-3-3s1.35-3 3-3h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-2zm-3-4h8v2H8v-2z"/></svg>',
			'knowledge_base' => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zm0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5z"/></svg>',
			'instructions'   => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>',
			'examples'       => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor"><path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>',
			'other'          => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor"><path d="M6 2c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6H6zm7 7V3.5L18.5 9H13z"/></svg>',
		];

		return $icons[ $type ] ?? $icons['other'];
	}
}
