<?php
/**
 * Admin Menu configuration.
 *
 * Creates the unified LeadersPath admin menu structure.
 *
 * @package LeadersPath
 * @since   0.2.0
 */

declare(strict_types=1);

namespace LeadersPath\Admin;

/**
 * Manages the LeadersPath admin menu.
 *
 * @since 0.2.0
 */
class Admin_Menu {

	/**
	 * Menu slug for the LeadersPath menu.
	 *
	 * @var string
	 */
	public const MENU_SLUG = 'leaderspath';

	/**
	 * Initialize the class.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 5 );
	}

	/**
	 * Register the top-level LeadersPath menu.
	 *
	 * This creates the parent menu that all CPTs and settings will nest under.
	 * Priority 5 ensures this runs before CPT menu registration.
	 *
	 * @since 0.2.0
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'LeadersPath', 'leaderspath' ),
			__( 'LeadersPath', 'leaderspath' ),
			'edit_leaderspath_courses', // Capability to view menu.
			self::MENU_SLUG,
			[ $this, 'render_dashboard' ],
			'dashicons-welcome-learn-more',
			25
		);

		// Add Dashboard as explicit submenu so it appears in the menu list.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Dashboard', 'leaderspath' ),
			__( 'Dashboard', 'leaderspath' ),
			'edit_leaderspath_courses',
			self::MENU_SLUG, // Same slug as parent = replaces default submenu item.
			[ $this, 'render_dashboard' ]
		);
	}

	/**
	 * Render the dashboard page.
	 *
	 * @since 0.2.0
	 */
	public function render_dashboard(): void {
		$counts = $this->get_content_counts();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'LeadersPath', 'leaderspath' ); ?></h1>

			<div class="leaderspath-dashboard">
				<div class="leaderspath-dashboard__welcome">
					<h2><?php esc_html_e( 'Welcome to LeadersPath', 'leaderspath' ); ?></h2>
					<p><?php esc_html_e( 'AI-powered interactive learning platform with Claude chatbot integration.', 'leaderspath' ); ?></p>
				</div>

				<div class="leaderspath-dashboard__stats">
					<h3><?php esc_html_e( 'Content Overview', 'leaderspath' ); ?></h3>
					<table class="widefat striped">
						<tbody>
							<tr>
								<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=leaderspath_course' ) ); ?>"><?php esc_html_e( 'Courses', 'leaderspath' ); ?></a></td>
								<td><?php echo esc_html( (string) $counts['courses'] ); ?></td>
							</tr>
							<tr>
								<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=leaderspath_activity' ) ); ?>"><?php esc_html_e( 'Activities', 'leaderspath' ); ?></a></td>
								<td><?php echo esc_html( (string) $counts['activities'] ); ?></td>
							</tr>
							<tr>
								<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=leaderspath_cohort' ) ); ?>"><?php esc_html_e( 'Cohorts', 'leaderspath' ); ?></a></td>
								<td><?php echo esc_html( (string) $counts['cohorts'] ); ?></td>
							</tr>
							<tr>
								<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=leaderspath_context' ) ); ?>"><?php esc_html_e( 'Context Files', 'leaderspath' ); ?></a></td>
								<td><?php echo esc_html( (string) $counts['context_files'] ); ?></td>
							</tr>
							<tr>
								<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=leaderspath_skill' ) ); ?>"><?php esc_html_e( 'Skills', 'leaderspath' ); ?></a></td>
								<td><?php echo esc_html( (string) $counts['skills'] ); ?></td>
							</tr>
						</tbody>
					</table>
				</div>

				<div class="leaderspath-dashboard__quick-actions">
					<h3><?php esc_html_e( 'Quick Actions', 'leaderspath' ); ?></h3>
					<p>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=leaderspath_course' ) ); ?>" class="button button-primary"><?php esc_html_e( 'New Course', 'leaderspath' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=leaderspath_activity' ) ); ?>" class="button"><?php esc_html_e( 'New Activity', 'leaderspath' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=leaderspath_context' ) ); ?>" class="button"><?php esc_html_e( 'New Context File', 'leaderspath' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=leaderspath-settings' ) ); ?>" class="button"><?php esc_html_e( 'Settings', 'leaderspath' ); ?></a>
					</p>
				</div>
			</div>

			<style>
				.leaderspath-dashboard {
					max-width: 800px;
					margin-top: 20px;
				}
				.leaderspath-dashboard__welcome {
					background: #fff;
					border: 1px solid #c3c4c7;
					border-left: 4px solid #2271b1;
					padding: 12px 20px;
					margin-bottom: 20px;
				}
				.leaderspath-dashboard__welcome h2 {
					margin-top: 0;
				}
				.leaderspath-dashboard__welcome p {
					margin-bottom: 0;
				}
				.leaderspath-dashboard__stats {
					margin-bottom: 20px;
				}
				.leaderspath-dashboard__stats table {
					max-width: 400px;
				}
				.leaderspath-dashboard__stats td:last-child {
					text-align: right;
					font-weight: 600;
				}
				.leaderspath-dashboard__quick-actions .button {
					margin-right: 8px;
					margin-bottom: 8px;
				}
			</style>
		</div>
		<?php
	}

	/**
	 * Get counts of all LeadersPath content types.
	 *
	 * @since 0.2.0
	 *
	 * @return array<string, int> Content counts.
	 */
	private function get_content_counts(): array {
		return [
			'courses'       => (int) wp_count_posts( 'leaderspath_course' )->publish,
			'activities'    => (int) wp_count_posts( 'leaderspath_activity' )->publish,
			'cohorts'       => (int) wp_count_posts( 'leaderspath_cohort' )->publish,
			'context_files' => (int) wp_count_posts( 'leaderspath_context' )->publish,
			'skills'        => (int) wp_count_posts( 'leaderspath_skill' )->publish,
		];
	}

	/**
	 * Get the menu slug.
	 *
	 * @since 0.2.0
	 *
	 * @return string The menu slug.
	 */
	public static function get_menu_slug(): string {
		return self::MENU_SLUG;
	}
}
