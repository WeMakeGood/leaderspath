<?php
/**
 * Custom Taxonomies registration.
 *
 * @package LeadersPath
 * @since   0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

/**
 * Registers all custom taxonomies for LeadersPath.
 *
 * @since 0.1.0
 */
class Taxonomies {

	/**
	 * Initialize the class.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_taxonomies' ] );
	}

	/**
	 * Register all custom taxonomies.
	 *
	 * @since 0.1.0
	 */
	public function register_taxonomies(): void {
		$this->register_topic();
		$this->register_context_category();
		$this->register_skill_category();
		$this->register_cohort_type();
	}

	/**
	 * Register the Topic taxonomy.
	 *
	 * Applies to Activities and Lessons.
	 *
	 * @since 0.1.0
	 */
	private function register_topic(): void {
		$labels = [
			'name'                       => _x( 'Topics', 'Taxonomy general name', 'leaderspath' ),
			'singular_name'              => _x( 'Topic', 'Taxonomy singular name', 'leaderspath' ),
			'search_items'               => __( 'Search Topics', 'leaderspath' ),
			'popular_items'              => __( 'Popular Topics', 'leaderspath' ),
			'all_items'                  => __( 'All Topics', 'leaderspath' ),
			'parent_item'                => __( 'Parent Topic', 'leaderspath' ),
			'parent_item_colon'          => __( 'Parent Topic:', 'leaderspath' ),
			'edit_item'                  => __( 'Edit Topic', 'leaderspath' ),
			'view_item'                  => __( 'View Topic', 'leaderspath' ),
			'update_item'                => __( 'Update Topic', 'leaderspath' ),
			'add_new_item'               => __( 'Add New Topic', 'leaderspath' ),
			'new_item_name'              => __( 'New Topic Name', 'leaderspath' ),
			'separate_items_with_commas' => __( 'Separate topics with commas', 'leaderspath' ),
			'add_or_remove_items'        => __( 'Add or remove topics', 'leaderspath' ),
			'choose_from_most_used'      => __( 'Choose from the most used topics', 'leaderspath' ),
			'not_found'                  => __( 'No topics found.', 'leaderspath' ),
			'no_terms'                   => __( 'No topics', 'leaderspath' ),
			'menu_name'                  => __( 'Topics', 'leaderspath' ),
			'items_list_navigation'      => __( 'Topics list navigation', 'leaderspath' ),
			'items_list'                 => __( 'Topics list', 'leaderspath' ),
			'back_to_items'              => __( '&larr; Back to Topics', 'leaderspath' ),
		];

		$args = [
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_rest'       => true,
			'rest_base'          => 'topics',
			'rest_namespace'     => 'wp/v2',
			'hierarchical'       => true,
			'rewrite'            => [ 'slug' => 'topic', 'with_front' => false, 'hierarchical' => true ],
			'show_admin_column'  => true,
		];

		register_taxonomy(
			'leaderspath_topic',
			[ 'leaderspath_activity', 'leaderspath_lesson' ],
			$args
		);
	}

	/**
	 * Register the Context Category taxonomy.
	 *
	 * Applies to Context Files.
	 *
	 * @since 0.1.0
	 */
	private function register_context_category(): void {
		$labels = [
			'name'                       => _x( 'Context Categories', 'Taxonomy general name', 'leaderspath' ),
			'singular_name'              => _x( 'Context Category', 'Taxonomy singular name', 'leaderspath' ),
			'search_items'               => __( 'Search Context Categories', 'leaderspath' ),
			'popular_items'              => __( 'Popular Context Categories', 'leaderspath' ),
			'all_items'                  => __( 'All Context Categories', 'leaderspath' ),
			'parent_item'                => __( 'Parent Context Category', 'leaderspath' ),
			'parent_item_colon'          => __( 'Parent Context Category:', 'leaderspath' ),
			'edit_item'                  => __( 'Edit Context Category', 'leaderspath' ),
			'view_item'                  => __( 'View Context Category', 'leaderspath' ),
			'update_item'                => __( 'Update Context Category', 'leaderspath' ),
			'add_new_item'               => __( 'Add New Context Category', 'leaderspath' ),
			'new_item_name'              => __( 'New Context Category Name', 'leaderspath' ),
			'separate_items_with_commas' => __( 'Separate context categories with commas', 'leaderspath' ),
			'add_or_remove_items'        => __( 'Add or remove context categories', 'leaderspath' ),
			'choose_from_most_used'      => __( 'Choose from the most used context categories', 'leaderspath' ),
			'not_found'                  => __( 'No context categories found.', 'leaderspath' ),
			'no_terms'                   => __( 'No context categories', 'leaderspath' ),
			'menu_name'                  => __( 'Categories', 'leaderspath' ),
			'items_list_navigation'      => __( 'Context categories list navigation', 'leaderspath' ),
			'items_list'                 => __( 'Context categories list', 'leaderspath' ),
			'back_to_items'              => __( '&larr; Back to Context Categories', 'leaderspath' ),
		];

		$args = [
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => true,
			'rest_base'          => 'context-categories',
			'rest_namespace'     => 'wp/v2',
			'hierarchical'       => true,
			'rewrite'            => false,
			'show_admin_column'  => true,
		];

		register_taxonomy(
			'leaderspath_context_cat',
			[ 'leaderspath_context' ],
			$args
		);
	}

	/**
	 * Register the Skill Category taxonomy.
	 *
	 * Applies to Skills.
	 *
	 * @since 0.1.0
	 */
	private function register_skill_category(): void {
		$labels = [
			'name'                       => _x( 'Skill Categories', 'Taxonomy general name', 'leaderspath' ),
			'singular_name'              => _x( 'Skill Category', 'Taxonomy singular name', 'leaderspath' ),
			'search_items'               => __( 'Search Skill Categories', 'leaderspath' ),
			'popular_items'              => __( 'Popular Skill Categories', 'leaderspath' ),
			'all_items'                  => __( 'All Skill Categories', 'leaderspath' ),
			'parent_item'                => __( 'Parent Skill Category', 'leaderspath' ),
			'parent_item_colon'          => __( 'Parent Skill Category:', 'leaderspath' ),
			'edit_item'                  => __( 'Edit Skill Category', 'leaderspath' ),
			'view_item'                  => __( 'View Skill Category', 'leaderspath' ),
			'update_item'                => __( 'Update Skill Category', 'leaderspath' ),
			'add_new_item'               => __( 'Add New Skill Category', 'leaderspath' ),
			'new_item_name'              => __( 'New Skill Category Name', 'leaderspath' ),
			'separate_items_with_commas' => __( 'Separate skill categories with commas', 'leaderspath' ),
			'add_or_remove_items'        => __( 'Add or remove skill categories', 'leaderspath' ),
			'choose_from_most_used'      => __( 'Choose from the most used skill categories', 'leaderspath' ),
			'not_found'                  => __( 'No skill categories found.', 'leaderspath' ),
			'no_terms'                   => __( 'No skill categories', 'leaderspath' ),
			'menu_name'                  => __( 'Categories', 'leaderspath' ),
			'items_list_navigation'      => __( 'Skill categories list navigation', 'leaderspath' ),
			'items_list'                 => __( 'Skill categories list', 'leaderspath' ),
			'back_to_items'              => __( '&larr; Back to Skill Categories', 'leaderspath' ),
		];

		$args = [
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => true,
			'rest_base'          => 'skill-categories',
			'rest_namespace'     => 'wp/v2',
			'hierarchical'       => true,
			'rewrite'            => false,
			'show_admin_column'  => true,
		];

		register_taxonomy(
			'leaderspath_skill_cat',
			[ 'leaderspath_skill' ],
			$args
		);
	}

	/**
	 * Register the Cohort Type taxonomy.
	 *
	 * Applies to Cohorts. Two flat, mutually-exclusive terms — Organization
	 * (a whole cohort purchased for one org, created via `create_cohort()`
	 * from the org-purchase form) and Mixed Group (a standing cohort that
	 * individual buyers join one at a time, via the not-yet-built
	 * `add_to_cohort()`) — not a category tree, so non-hierarchical like tags.
	 * `Enrollment::create_cohort()` always sets this term itself (currently
	 * always 'Organization' — it's the only path that creates a cohort from
	 * a purchase); see docs/TASKS.md Phase 14.
	 *
	 * @since 0.7.0
	 */
	private function register_cohort_type(): void {
		$labels = [
			'name'                       => _x( 'Cohort Types', 'Taxonomy general name', 'leaderspath' ),
			'singular_name'              => _x( 'Cohort Type', 'Taxonomy singular name', 'leaderspath' ),
			'search_items'               => __( 'Search Cohort Types', 'leaderspath' ),
			'popular_items'              => __( 'Popular Cohort Types', 'leaderspath' ),
			'all_items'                  => __( 'All Cohort Types', 'leaderspath' ),
			'edit_item'                  => __( 'Edit Cohort Type', 'leaderspath' ),
			'view_item'                  => __( 'View Cohort Type', 'leaderspath' ),
			'update_item'                => __( 'Update Cohort Type', 'leaderspath' ),
			'add_new_item'               => __( 'Add New Cohort Type', 'leaderspath' ),
			'new_item_name'              => __( 'New Cohort Type Name', 'leaderspath' ),
			'separate_items_with_commas' => __( 'Separate cohort types with commas', 'leaderspath' ),
			'add_or_remove_items'        => __( 'Add or remove cohort types', 'leaderspath' ),
			'choose_from_most_used'      => __( 'Choose from the most used cohort types', 'leaderspath' ),
			'not_found'                  => __( 'No cohort types found.', 'leaderspath' ),
			'no_terms'                   => __( 'No cohort type', 'leaderspath' ),
			'menu_name'                  => __( 'Cohort Types', 'leaderspath' ),
			'items_list_navigation'      => __( 'Cohort types list navigation', 'leaderspath' ),
			'items_list'                 => __( 'Cohort types list', 'leaderspath' ),
			'back_to_items'              => __( '&larr; Back to Cohort Types', 'leaderspath' ),
		];

		$args = [
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => true,
			'rest_base'          => 'cohort-types',
			'rest_namespace'     => 'wp/v2',
			'hierarchical'       => false,
			'rewrite'            => false,
			'show_admin_column'  => true,
		];

		register_taxonomy(
			'leaderspath_cohort_type',
			[ 'leaderspath_cohort' ],
			$args
		);

		// Fixed, code-depended-on vocabulary (Enrollment::create_cohort()
		// assigns by slug) — seeded here rather than left to an editor to
		// create, and guarded so re-running (e.g. plugin already active
		// when this shipped) doesn't error on a duplicate term.
		foreach ( [ 'organization' => __( 'Organization', 'leaderspath' ), 'mixed-group' => __( 'Mixed Group', 'leaderspath' ) ] as $slug => $name ) {
			if ( ! term_exists( $slug, 'leaderspath_cohort_type' ) ) {
				wp_insert_term( $name, 'leaderspath_cohort_type', [ 'slug' => $slug ] );
			}
		}
	}

}
