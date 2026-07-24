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

		wp_register_script(
			'leaderspath-marked',
			LEADERSPATH_URL . 'assets/js/vendor/marked.umd.js',
			[],
			'17.0.2',
			true
		);

		wp_register_script(
			'leaderspath-chatbot',
			LEADERSPATH_URL . 'assets/js/chatbot.js',
			[ 'leaderspath-marked' ],
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

		return sprintf(
			'<div class="%s"%s>%s</div>',
			esc_attr( implode( ' ', $classes ) ),
			$id_attr,
			$html
		);
	}

	private function enqueue_chatbot_assets(): void {
		wp_enqueue_style( 'leaderspath' );
		wp_enqueue_script( 'leaderspath-chatbot' );

		if ( ! wp_script_is( 'leaderspath-chatbot', 'done' ) ) {
			wp_localize_script(
				'leaderspath-chatbot',
				'LeadersPathChatbot',
				[
					'restUrl'       => rest_url( 'leaderspath/v1/chat' ),
					'restStreamUrl' => rest_url( 'leaderspath/v1/chat/stream' ),
					'nonce'         => wp_create_nonce( 'wp_rest' ),
				]
			);
		}
	}
}
