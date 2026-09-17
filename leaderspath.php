<?php
/**
 * Plugin Name:     LeadersPath
 * Plugin URI:      https://leaderspath.wemakegood.org
 * Description:     AI-powered learning community plugin with interactive chatbot lessons.
 * Author:          Christopher Frazier
 * Author URI:      https://wemakegood.org
 * Text Domain:     leaderspath
 * Domain Path:     /languages
 * Version:         0.7.0
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
define( 'LEADERSPATH_VERSION', '0.7.0' );
define( 'LEADERSPATH_PATH', plugin_dir_path( __FILE__ ) );
define( 'LEADERSPATH_URL', plugin_dir_url( __FILE__ ) );

/**
 * Composer autoloader.
 */
if ( file_exists( LEADERSPATH_PATH . 'vendor/autoload.php' ) ) {
	require LEADERSPATH_PATH . 'vendor/autoload.php';
}

/**
 * Load core classes.
 */
require LEADERSPATH_PATH . 'includes/class-post-types.php';
require LEADERSPATH_PATH . 'includes/class-taxonomies.php';
require LEADERSPATH_PATH . 'includes/class-capabilities.php';
require LEADERSPATH_PATH . 'includes/class-acf-fields.php';
// Enrollment is commerce-agnostic (see class-enrollment.php) and loaded
// unconditionally — cohort access/roster logic must not depend on
// WooCommerce being active. Required before class-woocommerce.php, which
// calls into it, and before class-cohort-rewrite.php, which does too.
require LEADERSPATH_PATH . 'includes/class-enrollment.php';
require LEADERSPATH_PATH . 'includes/class-cohort-rewrite.php';
// CLI_Commands self-guards on WP_CLI and self-registers; no `new` needed.
require LEADERSPATH_PATH . 'includes/class-cli-commands.php';
require LEADERSPATH_PATH . 'admin/class-admin-menu.php';
require LEADERSPATH_PATH . 'admin/class-settings.php';
require LEADERSPATH_PATH . 'admin/class-admin-columns.php';
require LEADERSPATH_PATH . 'admin/trait-dropzone-markup.php';
require LEADERSPATH_PATH . 'admin/class-context-uploader.php';
require LEADERSPATH_PATH . 'admin/class-cohort-roster.php';
require LEADERSPATH_PATH . 'admin/class-cohort-context.php';
require LEADERSPATH_PATH . 'admin/class-md-drop.php';
require LEADERSPATH_PATH . 'includes/class-claude-api.php';
require LEADERSPATH_PATH . 'includes/class-rest-api.php';
require LEADERSPATH_PATH . 'includes/class-skill-processor.php';

/**
 * Load renderers and the shortcode layer that wraps them.
 */
require LEADERSPATH_PATH . 'includes/renderers/class-post-id-helper.php';
require LEADERSPATH_PATH . 'includes/renderers/class-chatbot-renderer.php';
require LEADERSPATH_PATH . 'includes/class-shortcodes.php';

/**
 * Bricks Builder integration: global lp_* functions + condition/echo
 * registration. Functions load unconditionally (Bricks calls them by global
 * name); WooCommerce-dependent ones guard with class_exists at call time.
 */
require LEADERSPATH_PATH . 'includes/bricks-functions.php';
require LEADERSPATH_PATH . 'includes/class-bricks-integration.php';

/**
 * Initialize core functionality.
 *
 * Note: Admin_Menu must be initialized before Post_Types so the menu exists
 * when CPTs register with show_in_menu.
 */
new LeadersPath\Admin\Admin_Menu();
new LeadersPath\Includes\Post_Types();
new LeadersPath\Includes\Taxonomies();
new LeadersPath\Includes\Capabilities();
new LeadersPath\Includes\ACF_Fields();
new LeadersPath\Includes\Cohort_Rewrite();
new LeadersPath\Admin\Settings();
new LeadersPath\Admin\Admin_Columns();
new LeadersPath\Admin\Context_Uploader();
new LeadersPath\Admin\Cohort_Roster();
new LeadersPath\Admin\Cohort_Context();
new LeadersPath\Admin\MD_Drop();
new LeadersPath\Includes\REST_API();
new LeadersPath\Includes\Skill_Processor();
new LeadersPath\Includes\Shortcodes();
new LeadersPath\Includes\Bricks_Integration();

/**
 * WooCommerce integration (deferred to plugins_loaded).
 *
 * WordPress loads plugins alphabetically, so LeadersPath loads before
 * WooCommerce. Deferring to plugins_loaded ensures the WooCommerce class
 * exists when we check for it.
 */
add_action( 'plugins_loaded', function (): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	require_once LEADERSPATH_PATH . 'includes/class-woocommerce.php';
	new LeadersPath\Includes\WooCommerce();
} );

/**
 * WS Form cohort-purchase integration (deferred to plugins_loaded).
 *
 * WS Form Pro's `wsf_field_get_objects()`/`wsf_submit_get_value()` helpers
 * only exist once WS Form Pro itself has loaded; class-ws-form-integration.php
 * also self-guards with the same function_exists() check, but requiring it
 * unconditionally at top-level would still define the class body eagerly.
 * Same deferral pattern as the WooCommerce integration above.
 */
add_action( 'plugins_loaded', function (): void {
	if ( ! function_exists( 'wsf_field_get_objects' ) ) {
		return;
	}

	require_once LEADERSPATH_PATH . 'includes/class-ws-form-integration.php';
	new LeadersPath\Includes\WS_Form_Integration();
} );

/**
 * Plugin activation hook.
 *
 * @since 0.1.0
 */
function leaderspath_activate(): void {
	// Register CPTs and taxonomies first so rewrite rules are generated.
	$post_types = new LeadersPath\Includes\Post_Types();
	$post_types->register_post_types();

	$taxonomies = new LeadersPath\Includes\Taxonomies();
	$taxonomies->register_taxonomies();

	// Add capabilities to roles.
	LeadersPath\Includes\Capabilities::add_caps();

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'leaderspath_activate' );

/**
 * Plugin deactivation hook.
 *
 * @since 0.1.0
 */
function leaderspath_deactivate(): void {
	// Remove capabilities from roles.
	LeadersPath\Includes\Capabilities::remove_caps();

	// Flush rewrite rules on deactivation.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'leaderspath_deactivate' );
