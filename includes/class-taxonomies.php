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
	 * Create default taxonomy terms.
	 *
	 * Called on plugin activation.
	 *
	 * @since 0.1.0
	 */
	public static function create_default_terms(): void {
		// Context Categories.
		$context_categories = [
			'Organization Profile',
			'Brand Guidelines',
			'Process Documentation',
			'Technical Specifications',
			'Example Content',
		];

		foreach ( $context_categories as $term ) {
			if ( ! term_exists( $term, 'leaderspath_context_cat' ) ) {
				wp_insert_term( $term, 'leaderspath_context_cat' );
			}
		}

		// Skill Categories.
		$skill_categories = [
			'Content Generation',
			'Data Analysis',
			'Research',
			'Code Generation',
			'Communication',
		];

		foreach ( $skill_categories as $term ) {
			if ( ! term_exists( $term, 'leaderspath_skill_cat' ) ) {
				wp_insert_term( $term, 'leaderspath_skill_cat' );
			}
		}
	}
}
