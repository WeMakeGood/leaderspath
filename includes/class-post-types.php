<?php
/**
 * Custom Post Types registration.
 *
 * @package LeadersPath
 * @since   0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

/**
 * Registers all custom post types for LeadersPath.
 *
 * @since 0.1.0
 */
class Post_Types {

	/**
	 * Initialize the class.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_types' ] );
	}

	/**
	 * Register all custom post types.
	 *
	 * @since 0.1.0
	 */
	public function register_post_types(): void {
		$this->register_activity();
		$this->register_lesson();
		$this->register_course();
		$this->register_context();
		$this->register_skill();
	}

	/**
	 * Register the Activity post type.
	 *
	 * Activities are AI sandbox experiments within a facilitated Lesson.
	 *
	 * @since 0.1.0
	 */
	private function register_activity(): void {
		$labels = [
			'name'                  => _x( 'Activities', 'Post type general name', 'leaderspath' ),
			'singular_name'         => _x( 'Activity', 'Post type singular name', 'leaderspath' ),
			'menu_name'             => _x( 'Activities', 'Admin Menu text', 'leaderspath' ),
			'name_admin_bar'        => _x( 'Activity', 'Add New on Toolbar', 'leaderspath' ),
			'add_new'               => __( 'Add New', 'leaderspath' ),
			'add_new_item'          => __( 'Add New Activity', 'leaderspath' ),
			'new_item'              => __( 'New Activity', 'leaderspath' ),
			'edit_item'             => __( 'Edit Activity', 'leaderspath' ),
			'view_item'             => __( 'View Activity', 'leaderspath' ),
			'all_items'             => __( 'All Activities', 'leaderspath' ),
			'search_items'          => __( 'Search Activities', 'leaderspath' ),
			'parent_item_colon'     => __( 'Parent Activities:', 'leaderspath' ),
			'not_found'             => __( 'No activities found.', 'leaderspath' ),
			'not_found_in_trash'    => __( 'No activities found in Trash.', 'leaderspath' ),
			'featured_image'        => _x( 'Activity Cover Image', 'Overrides the "Featured Image" phrase', 'leaderspath' ),
			'set_featured_image'    => _x( 'Set cover image', 'Overrides the "Set featured image" phrase', 'leaderspath' ),
			'remove_featured_image' => _x( 'Remove cover image', 'Overrides the "Remove featured image" phrase', 'leaderspath' ),
			'use_featured_image'    => _x( 'Use as cover image', 'Overrides the "Use as featured image" phrase', 'leaderspath' ),
			'archives'              => _x( 'Activity archives', 'The post type archive label', 'leaderspath' ),
			'insert_into_item'      => _x( 'Insert into activity', 'Overrides the "Insert into post" phrase', 'leaderspath' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this activity', 'Overrides the "Uploaded to this post" phrase', 'leaderspath' ),
			'filter_items_list'     => _x( 'Filter activities list', 'Screen reader text', 'leaderspath' ),
			'items_list_navigation' => _x( 'Activities list navigation', 'Screen reader text', 'leaderspath' ),
			'items_list'            => _x( 'Activities list', 'Screen reader text', 'leaderspath' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => \LeadersPath\Admin\Admin_Menu::MENU_SLUG,
			'show_in_rest'        => true,
			'rest_base'           => 'activities',
			'rest_namespace'      => 'wp/v2',
			'query_var'           => true,
			'rewrite'             => [ 'slug' => 'activity', 'with_front' => false ],
			'capability_type'     => [ 'leaderspath_activity', 'leaderspath_activities' ],
			'map_meta_cap'        => true,
			'has_archive'         => true,
			'hierarchical'        => false,
			'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ],
		];

		register_post_type( 'leaderspath_activity', $args );
	}

	/**
	 * Register the Lesson post type.
	 *
	 * Lessons are atomic teaching units within a Course, containing Activities.
	 *
	 * @since 0.3.0
	 */
	private function register_lesson(): void {
		$labels = [
			'name'                  => _x( 'Lessons', 'Post type general name', 'leaderspath' ),
			'singular_name'         => _x( 'Lesson', 'Post type singular name', 'leaderspath' ),
			'menu_name'             => _x( 'Lessons', 'Admin Menu text', 'leaderspath' ),
			'name_admin_bar'        => _x( 'Lesson', 'Add New on Toolbar', 'leaderspath' ),
			'add_new'               => __( 'Add New', 'leaderspath' ),
			'add_new_item'          => __( 'Add New Lesson', 'leaderspath' ),
			'new_item'              => __( 'New Lesson', 'leaderspath' ),
			'edit_item'             => __( 'Edit Lesson', 'leaderspath' ),
			'view_item'             => __( 'View Lesson', 'leaderspath' ),
			'all_items'             => __( 'All Lessons', 'leaderspath' ),
			'search_items'          => __( 'Search Lessons', 'leaderspath' ),
			'parent_item_colon'     => __( 'Parent Lessons:', 'leaderspath' ),
			'not_found'             => __( 'No lessons found.', 'leaderspath' ),
			'not_found_in_trash'    => __( 'No lessons found in Trash.', 'leaderspath' ),
			'featured_image'        => _x( 'Lesson Cover Image', 'Overrides the "Featured Image" phrase', 'leaderspath' ),
			'set_featured_image'    => _x( 'Set cover image', 'Overrides the "Set featured image" phrase', 'leaderspath' ),
			'remove_featured_image' => _x( 'Remove cover image', 'Overrides the "Remove featured image" phrase', 'leaderspath' ),
			'use_featured_image'    => _x( 'Use as cover image', 'Overrides the "Use as featured image" phrase', 'leaderspath' ),
			'archives'              => _x( 'Lesson archives', 'The post type archive label', 'leaderspath' ),
			'insert_into_item'      => _x( 'Insert into lesson', 'Overrides the "Insert into post" phrase', 'leaderspath' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this lesson', 'Overrides the "Uploaded to this post" phrase', 'leaderspath' ),
			'filter_items_list'     => _x( 'Filter lessons list', 'Screen reader text', 'leaderspath' ),
			'items_list_navigation' => _x( 'Lessons list navigation', 'Screen reader text', 'leaderspath' ),
			'items_list'            => _x( 'Lessons list', 'Screen reader text', 'leaderspath' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => \LeadersPath\Admin\Admin_Menu::MENU_SLUG,
			'show_in_rest'        => true,
			'rest_base'           => 'lessons',
			'rest_namespace'      => 'wp/v2',
			'query_var'           => true,
			'rewrite'             => [ 'slug' => 'lesson', 'with_front' => false ],
			'capability_type'     => [ 'leaderspath_lesson', 'leaderspath_lessons' ],
			'map_meta_cap'        => true,
			'has_archive'         => true,
			'hierarchical'        => false,
			'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ],
		];

		register_post_type( 'leaderspath_lesson', $args );
	}

	/**
	 * Register the Course post type.
	 *
	 * Courses are reusable curriculum structures containing Lessons.
	 *
	 * @since 0.3.0
	 */
	private function register_course(): void {
		$labels = [
			'name'                  => _x( 'Courses', 'Post type general name', 'leaderspath' ),
			'singular_name'         => _x( 'Course', 'Post type singular name', 'leaderspath' ),
			'menu_name'             => _x( 'Courses', 'Admin Menu text', 'leaderspath' ),
			'name_admin_bar'        => _x( 'Course', 'Add New on Toolbar', 'leaderspath' ),
			'add_new'               => __( 'Add New', 'leaderspath' ),
			'add_new_item'          => __( 'Add New Course', 'leaderspath' ),
			'new_item'              => __( 'New Course', 'leaderspath' ),
			'edit_item'             => __( 'Edit Course', 'leaderspath' ),
			'view_item'             => __( 'View Course', 'leaderspath' ),
			'all_items'             => __( 'All Courses', 'leaderspath' ),
			'search_items'          => __( 'Search Courses', 'leaderspath' ),
			'parent_item_colon'     => __( 'Parent Courses:', 'leaderspath' ),
			'not_found'             => __( 'No courses found.', 'leaderspath' ),
			'not_found_in_trash'    => __( 'No courses found in Trash.', 'leaderspath' ),
			'archives'              => _x( 'Course archives', 'The post type archive label', 'leaderspath' ),
			'insert_into_item'      => _x( 'Insert into course', 'Overrides the "Insert into post" phrase', 'leaderspath' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this course', 'Overrides the "Uploaded to this post" phrase', 'leaderspath' ),
			'filter_items_list'     => _x( 'Filter courses list', 'Screen reader text', 'leaderspath' ),
			'items_list_navigation' => _x( 'Courses list navigation', 'Screen reader text', 'leaderspath' ),
			'items_list'            => _x( 'Courses list', 'Screen reader text', 'leaderspath' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => \LeadersPath\Admin\Admin_Menu::MENU_SLUG,
			'show_in_rest'        => true,
			'rest_base'           => 'courses',
			'rest_namespace'      => 'wp/v2',
			'query_var'           => true,
			'rewrite'             => [ 'slug' => 'course', 'with_front' => false ],
			'capability_type'     => [ 'leaderspath_course', 'leaderspath_courses' ],
			'map_meta_cap'        => true,
			'has_archive'         => true,
			'hierarchical'        => false,
			'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ],
		];

		register_post_type( 'leaderspath_course', $args );
	}

	/**
	 * Register the Context File post type.
	 *
	 * @since 0.1.0
	 */
	private function register_context(): void {
		$labels = [
			'name'                  => _x( 'Context Files', 'Post type general name', 'leaderspath' ),
			'singular_name'         => _x( 'Context File', 'Post type singular name', 'leaderspath' ),
			'menu_name'             => _x( 'Context Files', 'Admin Menu text', 'leaderspath' ),
			'name_admin_bar'        => _x( 'Context File', 'Add New on Toolbar', 'leaderspath' ),
			'add_new'               => __( 'Add New', 'leaderspath' ),
			'add_new_item'          => __( 'Add New Context File', 'leaderspath' ),
			'new_item'              => __( 'New Context File', 'leaderspath' ),
			'edit_item'             => __( 'Edit Context File', 'leaderspath' ),
			'view_item'             => __( 'View Context File', 'leaderspath' ),
			'all_items'             => __( 'Context Files', 'leaderspath' ),
			'search_items'          => __( 'Search Context Files', 'leaderspath' ),
			'parent_item_colon'     => __( 'Parent Context Files:', 'leaderspath' ),
			'not_found'             => __( 'No context files found.', 'leaderspath' ),
			'not_found_in_trash'    => __( 'No context files found in Trash.', 'leaderspath' ),
			'archives'              => _x( 'Context File archives', 'The post type archive label', 'leaderspath' ),
			'insert_into_item'      => _x( 'Insert into context file', 'Overrides the "Insert into post" phrase', 'leaderspath' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this context file', 'Overrides the "Uploaded to this post" phrase', 'leaderspath' ),
			'filter_items_list'     => _x( 'Filter context files list', 'Screen reader text', 'leaderspath' ),
			'items_list_navigation' => _x( 'Context files list navigation', 'Screen reader text', 'leaderspath' ),
			'items_list'            => _x( 'Context files list', 'Screen reader text', 'leaderspath' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => true,
			'exclude_from_search' => false, // Required for ACF relationship field search.
			'show_ui'             => true,
			'show_in_menu'        => \LeadersPath\Admin\Admin_Menu::MENU_SLUG,
			'show_in_rest'        => true,
			'rest_base'           => 'context-files',
			'rest_namespace'      => 'wp/v2',
			'query_var'           => true,
			'rewrite'             => false,
			'capability_type'     => [ 'leaderspath_context', 'leaderspath_contexts' ],
			'map_meta_cap'        => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'supports'            => [ 'title', 'editor', 'revisions', 'custom-fields' ],
		];

		register_post_type( 'leaderspath_context', $args );
	}

	/**
	 * Register the Skill post type.
	 *
	 * @since 0.1.0
	 */
	private function register_skill(): void {
		$labels = [
			'name'                  => _x( 'Skills', 'Post type general name', 'leaderspath' ),
			'singular_name'         => _x( 'Skill', 'Post type singular name', 'leaderspath' ),
			'menu_name'             => _x( 'Skills', 'Admin Menu text', 'leaderspath' ),
			'name_admin_bar'        => _x( 'Skill', 'Add New on Toolbar', 'leaderspath' ),
			'add_new'               => __( 'Add New', 'leaderspath' ),
			'add_new_item'          => __( 'Add New Skill', 'leaderspath' ),
			'new_item'              => __( 'New Skill', 'leaderspath' ),
			'edit_item'             => __( 'Edit Skill', 'leaderspath' ),
			'view_item'             => __( 'View Skill', 'leaderspath' ),
			'all_items'             => __( 'Skills', 'leaderspath' ),
			'search_items'          => __( 'Search Skills', 'leaderspath' ),
			'parent_item_colon'     => __( 'Parent Skills:', 'leaderspath' ),
			'not_found'             => __( 'No skills found.', 'leaderspath' ),
			'not_found_in_trash'    => __( 'No skills found in Trash.', 'leaderspath' ),
			'archives'              => _x( 'Skill archives', 'The post type archive label', 'leaderspath' ),
			'insert_into_item'      => _x( 'Insert into skill', 'Overrides the "Insert into post" phrase', 'leaderspath' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this skill', 'Overrides the "Uploaded to this post" phrase', 'leaderspath' ),
			'filter_items_list'     => _x( 'Filter skills list', 'Screen reader text', 'leaderspath' ),
			'items_list_navigation' => _x( 'Skills list navigation', 'Screen reader text', 'leaderspath' ),
			'items_list'            => _x( 'Skills list', 'Screen reader text', 'leaderspath' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => true,
			'exclude_from_search' => false, // Required for ACF relationship field search.
			'show_ui'             => true,
			'show_in_menu'        => \LeadersPath\Admin\Admin_Menu::MENU_SLUG,
			'show_in_rest'        => true,
			'rest_base'           => 'skills',
			'rest_namespace'      => 'wp/v2',
			'query_var'           => true,
			'rewrite'             => false,
			'capability_type'     => [ 'leaderspath_skill', 'leaderspath_skills' ],
			'map_meta_cap'        => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'supports'            => [ 'title', 'revisions', 'custom-fields' ],
		];

		register_post_type( 'leaderspath_skill', $args );
	}
}
