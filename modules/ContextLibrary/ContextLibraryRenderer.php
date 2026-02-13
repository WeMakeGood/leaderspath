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
	 * Generic document SVG icon for context file cards.
	 */
	private const DOCUMENT_ICON = '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor"><path d="M6 2c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6H6zm7 7V3.5L18.5 9H13z"/></svg>';

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
			$version     = (string) get_field( 'context_version', $context_id );

			// Get category from taxonomy (replaces old hardcoded context_file_type ACF field).
			$category_label = '';
			$terms          = get_the_terms( $context_id, 'leaderspath_context_cat' );
			if ( is_array( $terms ) && ! empty( $terms ) ) {
				$category_label = $terms[0]->name;
			}

			$files[] = [
				'id'             => $context_id,
				'title'          => get_the_title( $context_id ),
				'description'    => $description,
				'category_label' => $category_label,
				'version'        => $version,
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
					'<div class="leaderspath_context_library__card_icon">%s</div>',
					self::DOCUMENT_ICON
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
				$meta_parts = '';
				if ( '' !== $file['category_label'] ) {
					$meta_parts .= sprintf(
						'<span class="leaderspath_context_library__badge">%s</span>',
						esc_html( $file['category_label'] )
					);
				}
				if ( '' !== $file['version'] ) {
					$meta_parts .= sprintf(
						'<span class="leaderspath_context_library__version">v%s</span>',
						esc_html( $file['version'] )
					);
				}
				if ( '' !== $meta_parts ) {
					$meta = sprintf(
						'<div class="leaderspath_context_library__card_meta">%s</div>',
						$meta_parts
					);
				}
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

}
