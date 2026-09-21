<?php
/**
 * Registers the LeadersPath chatbot shortcode.
 *
 * Display of CPT fields (Lesson Meta, Activity Meta, Course Lessons, etc.)
 * is left to the page builder — Bricks Builder query loops read ACF directly
 * and produce the markup. The chatbot is the one surface that needs to ship
 * its own widget HTML, JS, and asset wiring, so it remains a shortcode.
 *
 * @package LeadersPath\Includes
 * @since   0.7.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

use LeadersPath\Renderers\Chatbot_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

class Shortcodes {

	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
	}

	public function register(): void {
		add_shortcode( 'leaderspath_chatbot', [ $this, 'chatbot' ] );
	}

	/**
	 * Register (but don't enqueue) frontend assets. The chatbot shortcode
	 * enqueues them when it actually renders.
	 */
	public function register_assets(): void {
		wp_register_style(
			'leaderspath',
			LEADERSPATH_URL . 'assets/css/leaderspath.css',
			[],
			LEADERSPATH_VERSION
		);

		// Handle 'marked' (not 'leaderspath-marked') so this registration is
		// shared with MD_Drop's admin-side enqueue of the same file, rather
		// than the asset being registered twice under two handles.
		wp_register_script(
			'marked',
			LEADERSPATH_URL . 'assets/js/vendor/marked.umd.js',
			[],
			'17.0.2',
			true
		);

		// DOMPurify sanitizes marked.js's output before it's ever set as
		// innerHTML — marked.js itself has no `sanitize` option in this
		// version (removed upstream in v5+) and passes raw HTML straight
		// through unchanged. Required for both the streaming assistant text
		// and the learner's own pasted message (chatbot.js's only two
		// markdownToHtml() callers) — neither has a server-side sanitization
		// pass to fall back on.
		wp_register_script(
			'dompurify',
			LEADERSPATH_URL . 'assets/js/vendor/purify.min.js',
			[],
			'3.4.15',
			true
		);

		wp_register_script(
			'leaderspath-chatbot',
			LEADERSPATH_URL . 'assets/js/chatbot.js',
			[ 'marked', 'dompurify' ],
			LEADERSPATH_VERSION,
			true
		);
	}

	public function chatbot( $atts ): string {
		$atts = shortcode_atts(
			[
				'show_heading' => 'yes',
				'heading_text' => '',
				'empty_text'   => '',
				'post_id'      => 0,
				'class'        => '',
				'id'           => '',
				// A CSS length ('600px', '40rem') sets a fixed height. '100%'
				// (or 'fill') makes the widget a flex item that fills its
				// host container — the host still needs its own height/flex
				// chain (see docs/bricks-integration.md) for that to resolve
				// against anything; this only handles the widget's own side.
				'height'         => '',
				// Layout debug aid — an integer repeat count. Replaces the
				// opening instructions with that many repetitions of long
				// placeholder text, to check scroll/height CSS against a tall
				// message without a real conversation. Never sent to the AI;
				// remove from the template once layout work is done.
				'debug_length'   => 0,
			],
			is_array( $atts ) ? $atts : [],
			'leaderspath_chatbot'
		);

		$options = [
			'show_heading' => $this->bool( $atts['show_heading'] ),
		];
		if ( '' !== $atts['heading_text'] ) {
			$options['heading_text'] = $atts['heading_text'];
		}
		if ( '' !== $atts['empty_text'] ) {
			$options['empty_text'] = $atts['empty_text'];
		}
		if ( (int) $atts['debug_length'] > 0 ) {
			$options['debug_length'] = (int) $atts['debug_length'];
		}

		// Resolve the post to render. Prefer the explicit `post_id`; fall back to
		// `id` as an alias (matches the design-doc `id="{activity_id}"` convention
		// and how Bricks routes the loop item's ID into the shortcode). A numeric
		// `id` is treated as the post; a non-numeric `id` stays a cosmetic wrapper.
		$post_id = (int) $atts['post_id'];
		if ( 0 === $post_id && is_numeric( $atts['id'] ) ) {
			$post_id = (int) $atts['id'];
		}

		$html = Chatbot_Renderer::render( $options, $post_id );
		if ( '' === $html ) {
			return '';
		}

		$this->enqueue_chatbot_assets();

		return $this->wrap( $html, $atts );
	}

	/**
	 * Parse a yes/no/true/false/on/off/1/0 attribute. Defaults to false.
	 */
	private function bool( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return in_array( strtolower( (string) $value ), [ 'yes', 'true', 'on', '1', 1 ], true );
	}

	/**
	 * Wrap rendered content in the standard outer div with class/id from
	 * shortcode attributes.
	 */
	private function wrap( string $html, array $atts ): string {
		$classes = [ 'leaderspath_chatbot' ];
		if ( ! empty( $atts['class'] ) ) {
			$classes[] = (string) $atts['class'];
		}

		// Only emit a cosmetic wrapper id when `id` is non-numeric. A numeric
		// `id` is consumed as the post to render (see chatbot()), not an HTML id —
		// and numeric ids repeated across loop items would be invalid anyway.
		$id_attr = '';
		if ( ! empty( $atts['id'] ) && ! is_numeric( $atts['id'] ) ) {
			$id_attr = sprintf( ' id="%s"', esc_attr( (string) $atts['id'] ) );
		}

		$style_attr = $this->height_style( (string) $atts['height'] );

		return sprintf(
			'<div class="%s"%s%s>%s</div>',
			esc_attr( implode( ' ', $classes ) ),
			$id_attr,
			$style_attr,
			$html
		);
	}

	/**
	 * Build the style="" attribute for the `height` shortcode attribute.
	 *
	 * '100%'/'fill' makes the wrapper a flex item that fills whatever the
	 * host gives it: flex:1 alone, no height. flex's flex-basis (0% by
	 * default in the `flex: 1` shorthand) overrides an explicit height on
	 * the same element for a flex item's main-axis sizing — confirmed live:
	 * combining height:100% (or any literal height) with flex:1 on one
	 * element made it grow unbounded instead of filling its parent, and
	 * removing the height (flex:1 by itself) fixed it. min-height:0 lets it
	 * shrink instead of pushing the page taller — see
	 * .leaderspath_chatbot__container's own comment in leaderspath.css.
	 * Any other non-empty value is a literal CSS length: a fixed height,
	 * with no flex (a fixed-size element doesn't need to grow).
	 */
	private function height_style( string $height ): string {
		if ( '' === trim( $height ) ) {
			return '';
		}

		if ( in_array( strtolower( trim( $height ) ), [ '100%', 'fill' ], true ) ) {
			$css = 'flex:1;min-height:0;';
		} else {
			$css = sprintf( 'height:%s;', trim( $height ) );
		}

		return sprintf( ' style="%s"', esc_attr( $css ) );
	}

	private function enqueue_chatbot_assets(): void {
		wp_enqueue_style( 'leaderspath' );
		wp_enqueue_script( 'leaderspath-chatbot' );

		if ( ! wp_script_is( 'leaderspath-chatbot', 'done' ) ) {
			wp_localize_script(
				'leaderspath-chatbot',
				'LeadersPathChatbot',
				[
					'restUrl'        => rest_url( 'leaderspath/v1/chat' ),
					'restStreamUrl'  => rest_url( 'leaderspath/v1/chat/stream' ),
					'restWarmUrl'    => rest_url( 'leaderspath/v1/chat/warm' ),
					'restUploadUrl'  => rest_url( 'leaderspath/v1/chat/upload' ),
					'maxUploadBytes' => \LeadersPath\Admin\Settings::get_chat_upload_max_bytes(),
					'nonce'          => wp_create_nonce( 'wp_rest' ),
				]
			);
		}
	}
}
