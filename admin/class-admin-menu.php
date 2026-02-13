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
	 * Desired submenu order, keyed by slug.
	 *
	 * Entries not listed here are appended at the end.
	 *
	 * @var array<string, int>
	 */
	private const SUBMENU_ORDER = [
		'leaderspath'                                                                 => 0,  // Dashboard.
		'edit.php?post_type=leaderspath_course'                                       => 1,  // Courses.
		'edit.php?post_type=leaderspath_lesson'                                       => 2,  // Lessons.
		'edit.php?post_type=leaderspath_activity'                                     => 3,  // Activities.
		'edit-tags.php?taxonomy=leaderspath_topic&post_type=leaderspath_activity'      => 4,  // Topics.
		'edit.php?post_type=leaderspath_context'                                      => 5,  // Context Files.
		'edit-tags.php?taxonomy=leaderspath_context_cat&post_type=leaderspath_context' => 6,  // Context Categories.
		'edit.php?post_type=leaderspath_skill'                                        => 7,  // Skills.
		'edit-tags.php?taxonomy=leaderspath_skill_cat&post_type=leaderspath_skill'     => 8,  // Skill Categories.
		'leaderspath-settings'                                                        => 99, // Settings (always last).
	];

	/**
	 * Initialize the class.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 5 );
		add_action( 'admin_menu', [ $this, 'reorder_submenu' ], 999 );
		add_filter( 'parent_file', [ $this, 'highlight_taxonomy_parent' ] );
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
			'edit_leaderspath_lessons', // Capability to view menu.
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
			'edit_leaderspath_lessons',
			self::MENU_SLUG, // Same slug as parent = replaces default submenu item.
			[ $this, 'render_dashboard' ]
		);

		// Taxonomy management pages.
		// WordPress only auto-adds taxonomy submenus when the CPT uses
		// show_in_menu => true. Our CPTs use a custom menu slug, so we
		// must register taxonomy pages manually.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Topics', 'leaderspath' ),
			__( 'Topics', 'leaderspath' ),
			'manage_categories',
			'edit-tags.php?taxonomy=leaderspath_topic&post_type=leaderspath_activity'
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Context Categories', 'leaderspath' ),
			__( 'Context Categories', 'leaderspath' ),
			'manage_categories',
			'edit-tags.php?taxonomy=leaderspath_context_cat&post_type=leaderspath_context'
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Skill Categories', 'leaderspath' ),
			__( 'Skill Categories', 'leaderspath' ),
			'manage_categories',
			'edit-tags.php?taxonomy=leaderspath_skill_cat&post_type=leaderspath_skill'
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
								<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=leaderspath_lesson' ) ); ?>"><?php esc_html_e( 'Lessons', 'leaderspath' ); ?></a></td>
								<td><?php echo esc_html( (string) $counts['lessons'] ); ?></td>
							</tr>
							<tr>
								<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=leaderspath_activity' ) ); ?>"><?php esc_html_e( 'Activities', 'leaderspath' ); ?></a></td>
								<td><?php echo esc_html( (string) $counts['activities'] ); ?></td>
							</tr>
							<tr>
								<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=leaderspath_course' ) ); ?>"><?php esc_html_e( 'Courses', 'leaderspath' ); ?></a></td>
								<td><?php echo esc_html( (string) $counts['courses'] ); ?></td>
							</tr>
							<tr>
								<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=leaderspath_context' ) ); ?>"><?php esc_html_e( 'Context Files', 'leaderspath' ); ?></a></td>
								<td><?php echo esc_html( (string) $counts['context_files'] ); ?></td>
							</tr>
							<tr>
								<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=leaderspath_skill' ) ); ?>"><?php esc_html_e( 'Skills', 'leaderspath' ); ?></a></td>
								<td><?php echo esc_html( (string) $counts['skills'] ); ?></td>
							</tr>
							<?php if ( class_exists( 'WooCommerce' ) ) : ?>
							<tr>
								<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>"><?php esc_html_e( 'Cohorts', 'leaderspath' ); ?></a></td>
								<td><?php echo esc_html( (string) $counts['cohorts'] ); ?></td>
							</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<div class="leaderspath-dashboard__quick-actions">
					<h3><?php esc_html_e( 'Quick Actions', 'leaderspath' ); ?></h3>
					<p>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=leaderspath_lesson' ) ); ?>" class="button button-primary"><?php esc_html_e( 'New Lesson', 'leaderspath' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=leaderspath_activity' ) ); ?>" class="button"><?php esc_html_e( 'New Activity', 'leaderspath' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=leaderspath_context' ) ); ?>" class="button"><?php esc_html_e( 'New Context File', 'leaderspath' ); ?></a>
						<?php if ( class_exists( 'WooCommerce' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product' ) ); ?>" class="button"><?php esc_html_e( 'New Cohort', 'leaderspath' ); ?></a>
						<?php endif; ?>
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
		$counts = [
			'lessons'       => (int) wp_count_posts( 'leaderspath_lesson' )->publish,
			'activities'    => (int) wp_count_posts( 'leaderspath_activity' )->publish,
			'courses'       => (int) wp_count_posts( 'leaderspath_course' )->publish,
			'context_files' => (int) wp_count_posts( 'leaderspath_context' )->publish,
			'skills'        => (int) wp_count_posts( 'leaderspath_skill' )->publish,
			'cohorts'       => 0,
		];

		if ( class_exists( 'WooCommerce' ) ) {
			$cohort_query = new \WP_Query( [
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'   => \LeadersPath\Includes\WooCommerce::COHORT_META_KEY,
						'value' => 'yes',
					],
				],
			] );
			$counts['cohorts'] = $cohort_query->found_posts;
		}

		return $counts;
	}

	/**
	 * Ensure the LeadersPath menu is highlighted when editing taxonomy terms.
	 *
	 * WordPress highlights the parent menu based on the CPT's show_in_menu
	 * value. Since our CPTs use a custom slug, taxonomy pages would not
	 * highlight the correct parent without this filter.
	 *
	 * @since 0.6.0
	 *
	 * @param string $parent_file The current parent file.
	 * @return string Modified parent file.
	 */
	public function highlight_taxonomy_parent( string $parent_file ): string {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return $parent_file;
		}

		$leaderspath_taxonomies = [
			'leaderspath_topic',
			'leaderspath_context_cat',
			'leaderspath_skill_cat',
		];

		if ( in_array( $screen->taxonomy, $leaderspath_taxonomies, true ) ) {
			return self::MENU_SLUG;
		}

		return $parent_file;
	}

	/**
	 * Sort LeadersPath submenu items into a logical order.
	 *
	 * Runs at priority 999 so all CPT and settings submenus have been registered.
	 *
	 * @since 0.6.0
	 */
	public function reorder_submenu(): void {
		global $submenu;

		if ( empty( $submenu[ self::MENU_SLUG ] ) ) {
			return;
		}

		usort(
			$submenu[ self::MENU_SLUG ],
			function ( array $a, array $b ): int {
				// Submenu item slug is at index 2.
				$order_a = self::SUBMENU_ORDER[ $a[2] ] ?? 50;
				$order_b = self::SUBMENU_ORDER[ $b[2] ] ?? 50;

				return $order_a <=> $order_b;
			}
		);
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
