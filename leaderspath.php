<?php
/**
 * Plugin Name:     LeadersPath
 * Plugin URI:      https://leaderspath.wemakegood.org
 * Description:     AI-powered learning community plugin with interactive chatbot lessons.
 * Author:          Christopher Frazier
 * Author URI:      https://wemakegood.org
 * Text Domain:     leaderspath
 * Domain Path:     /languages
 * Version:         0.1.0
 * Requires PHP:    8.2
 * Requires at least: 6.4
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package LeadersPath
 * @copyright 2026 Managed Word, LLC (dba Make Good)
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Plugin constants.
 */
define( 'LEADERSPATH_VERSION', '0.1.0' );
define( 'LEADERSPATH_PATH', plugin_dir_path( __FILE__ ) );
define( 'LEADERSPATH_URL', plugin_dir_url( __FILE__ ) );
define( 'LEADERSPATH_MODULES_JSON_PATH', LEADERSPATH_PATH . 'modules-json/' );

/**
 * Composer autoloader.
 */
if ( file_exists( LEADERSPATH_PATH . 'vendor/autoload.php' ) ) {
	require LEADERSPATH_PATH . 'vendor/autoload.php';
}

/**
 * Load Divi 5 modules registration.
 */
require LEADERSPATH_PATH . 'modules/Modules.php';

/**
 * Enqueue Visual Builder scripts and styles.
 *
 * @since 0.1.0
 */
function leaderspath_enqueue_vb_scripts(): void {
	// Only load when Divi 5 is enabled and Visual Builder is active.
	if ( ! function_exists( 'et_builder_d5_enabled' ) || ! et_builder_d5_enabled() ) {
		return;
	}

	if ( ! function_exists( 'et_core_is_fb_enabled' ) || ! et_core_is_fb_enabled() ) {
		return;
	}

	// Check if PackageBuildManager class exists (Divi 5).
	if ( ! class_exists( '\ET\Builder\VisualBuilder\Assets\PackageBuildManager' ) ) {
		return;
	}

	\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
		[
			'name'    => 'leaderspath-builder-bundle-script',
			'version' => LEADERSPATH_VERSION,
			'script'  => [
				'src'                => LEADERSPATH_URL . 'scripts/bundle.js',
				'deps'               => [
					'divi-module-library',
					'divi-vendor-wp-hooks',
				],
				'enqueue_top_window' => false,
				'enqueue_app_window' => true,
			],
		]
	);

	\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
		[
			'name'    => 'leaderspath-builder-vb-bundle-style',
			'version' => LEADERSPATH_VERSION,
			'style'   => [
				'src'                => LEADERSPATH_URL . 'styles/bundle.css',
				'deps'               => [],
				'enqueue_top_window' => false,
				'enqueue_app_window' => true,
			],
		]
	);
}
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'leaderspath_enqueue_vb_scripts' );

/**
 * Enqueue frontend scripts and styles.
 *
 * @since 0.1.0
 */
function leaderspath_enqueue_frontend_scripts(): void {
	$style_file = LEADERSPATH_PATH . 'styles/bundle.css';

	if ( file_exists( $style_file ) ) {
		wp_enqueue_style(
			'leaderspath-bundle-style',
			LEADERSPATH_URL . 'styles/bundle.css',
			[],
			LEADERSPATH_VERSION
		);
	}
}
add_action( 'wp_enqueue_scripts', 'leaderspath_enqueue_frontend_scripts' );

/**
 * Plugin activation hook.
 *
 * @since 0.1.0
 */
function leaderspath_activate(): void {
	// Flush rewrite rules on activation.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'leaderspath_activate' );

/**
 * Plugin deactivation hook.
 *
 * @since 0.1.0
 */
function leaderspath_deactivate(): void {
	// Flush rewrite rules on deactivation.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'leaderspath_deactivate' );
