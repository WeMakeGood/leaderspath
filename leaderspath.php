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
require LEADERSPATH_PATH . 'admin/class-admin-menu.php';
require LEADERSPATH_PATH . 'admin/class-settings.php';
require LEADERSPATH_PATH . 'admin/class-admin-columns.php';
require LEADERSPATH_PATH . 'includes/class-claude-api.php';
require LEADERSPATH_PATH . 'includes/class-rest-api.php';
require LEADERSPATH_PATH . 'includes/class-skill-processor.php';

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
new LeadersPath\Admin\Settings();
new LeadersPath\Admin\Admin_Columns();
new LeadersPath\Includes\REST_API();
new LeadersPath\Includes\Skill_Processor();

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

	// Create default taxonomy terms.
	LeadersPath\Includes\Taxonomies::create_default_terms();

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
