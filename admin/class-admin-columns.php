<?php
/**
 * Custom admin columns for LeadersPath CPTs.
 *
 * Manages columns, sorting, and quick edit for Activities, Lessons,
 * Courses, Context Files, and Skills.
 *
 * @package LeadersPath
 * @since   0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Admin;

/**
 * Manages custom admin columns for all LeadersPath CPTs.
 *
 * @since 0.1.0
 */
class Admin_Columns {

	/**
	 * Initialize the class.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		// Activity columns.
		add_filter( 'manage_leaderspath_activity_posts_columns', [ $this, 'activity_columns' ] );
		add_action( 'manage_leaderspath_activity_posts_custom_column', [ $this, 'activity_column_content' ], 10, 2 );
		add_filter( 'manage_edit-leaderspath_activity_sortable_columns', [ $this, 'activity_sortable_columns' ] );

		// Lesson columns.
		add_filter( 'manage_leaderspath_lesson_posts_columns', [ $this, 'lesson_columns' ] );
		add_action( 'manage_leaderspath_lesson_posts_custom_column', [ $this, 'lesson_column_content' ], 10, 2 );
		add_filter( 'manage_edit-leaderspath_lesson_sortable_columns', [ $this, 'lesson_sortable_columns' ] );

		// Course columns.
		add_filter( 'manage_leaderspath_course_posts_columns', [ $this, 'course_columns' ] );
		add_action( 'manage_leaderspath_course_posts_custom_column', [ $this, 'course_column_content' ], 10, 2 );
		add_filter( 'manage_edit-leaderspath_course_sortable_columns', [ $this, 'course_sortable_columns' ] );

		// Context File columns.
		add_filter( 'manage_leaderspath_context_posts_columns', [ $this, 'context_columns' ] );
		add_action( 'manage_leaderspath_context_posts_custom_column', [ $this, 'context_column_content' ], 10, 2 );
		add_filter( 'manage_edit-leaderspath_context_sortable_columns', [ $this, 'context_sortable_columns' ] );
		add_action( 'restrict_manage_posts', [ $this, 'context_cohort_filter_dropdown' ] );
		add_action( 'pre_get_posts', [ $this, 'filter_context_files_by_cohort' ] );

		// Skill columns.
		add_filter( 'manage_leaderspath_skill_posts_columns', [ $this, 'skill_columns' ] );
		add_action( 'manage_leaderspath_skill_posts_custom_column', [ $this, 'skill_column_content' ], 10, 2 );
		add_filter( 'manage_edit-leaderspath_skill_sortable_columns', [ $this, 'skill_sortable_columns' ] );

		// Cohort (purchase-time instance) columns. Not gated on WooCommerce
		// being active — a leaderspath_cohort instance can exist regardless
		// of which commerce backend, if any, created it. This replaced the
		// old cohort-product columns on the WC product list (0.7.0): those
		// stopped making sense once a product became a reusable catalog
		// offering that many organizations can purchase — a single "Phase"
		// or "Enrollees" column on one product row can't represent multiple
		// cohort instances under it. See docs/TASKS.md Phase 14, "Cohort is
		// a purchase-time instance, not the product."
		add_filter( 'manage_leaderspath_cohort_posts_columns', [ $this, 'cohort_columns' ] );
		add_action( 'manage_leaderspath_cohort_posts_custom_column', [ $this, 'cohort_column_content' ], 10, 2 );
		add_filter( 'manage_edit-leaderspath_cohort_sortable_columns', [ $this, 'cohort_sortable_columns' ] );

		// leaderspath_cohort has no native "View" row action — it's
		// deliberately not publicly_queryable (see Post_Types::register_cohort()),
		// so WordPress has no permalink to offer one for. That left facilitators
		// with no way to reach the actual /learn/{cohort-slug}/lesson/{slug}/
		// URL the cohort resolves through (found 2026-09-18). This adds a
		// "Preview First Lesson" action instead, built from the cohort's own
		// first reachable lesson — not a generic "View" link, since there's no
		// single canonical page for a cohort to view.
		add_filter( 'post_row_actions', [ $this, 'cohort_row_actions' ], 10, 2 );

		// Handle sorting.
		add_action( 'pre_get_posts', [ $this, 'handle_sorting' ] );

		// Quick edit support.
		add_action( 'quick_edit_custom_box', [ $this, 'quick_edit_fields' ], 10, 2 );
		add_action( 'save_post', [ $this, 'save_quick_edit_data' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_quick_edit_script' ] );
	}

	// -------------------------------------------------------------------------
	// Shared Renderers
	// -------------------------------------------------------------------------

	/**
	 * Render the post slug in a code tag.
	 *
	 * @since 0.5.0
	 *
	 * @param int $post_id Post ID.
	 */
	private function render_slug( int $post_id ): void {
		$post = get_post( $post_id );
		if ( $post ) {
			echo '<code>' . esc_html( $post->post_name ) . '</code>';
		}
	}

	// -------------------------------------------------------------------------
	// Activity Columns
	// -------------------------------------------------------------------------

	/**
	 * Define custom columns for Activities.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string> Modified columns.
	 */
	public function activity_columns( array $columns ): array {
		$new_columns = [];

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			// Insert custom columns after title.
			if ( 'title' === $key ) {
				$new_columns['leaderspath_slug']     = __( 'Slug', 'leaderspath' );
				$new_columns['leaderspath_duration'] = __( 'Duration', 'leaderspath' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render content for custom Activity columns.
	 *
	 * @since 0.1.0
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function activity_column_content( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'leaderspath_slug':
				$this->render_slug( $post_id );
				break;

			case 'leaderspath_duration':
				$this->render_activity_duration( $post_id );
				break;
		}
	}

	/**
	 * Define sortable columns for Activities.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, string> $columns Sortable columns.
	 * @return array<string, string> Modified sortable columns.
	 */
	public function activity_sortable_columns( array $columns ): array {
		$columns['leaderspath_slug']     = 'leaderspath_slug';
		$columns['leaderspath_duration'] = 'leaderspath_duration';
		return $columns;
	}

	// -------------------------------------------------------------------------
	// Lesson Columns
	// -------------------------------------------------------------------------

	/**
	 * Define custom columns for Lessons.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string> Modified columns.
	 */
	public function lesson_columns( array $columns ): array {
		$new_columns = [];

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			// Insert custom columns after title.
			if ( 'title' === $key ) {
				$new_columns['leaderspath_slug']            = __( 'Slug', 'leaderspath' );
				$new_columns['leaderspath_activity_count']  = __( 'Activities', 'leaderspath' );
				$new_columns['leaderspath_total_duration'] = __( 'Total Duration', 'leaderspath' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render content for custom Lesson columns.
	 *
	 * @since 0.1.0
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function lesson_column_content( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'leaderspath_slug':
				$this->render_slug( $post_id );
				break;

			case 'leaderspath_activity_count':
				$this->render_lesson_activity_count( $post_id );
				break;

			case 'leaderspath_total_duration':
				$this->render_lesson_total_duration( $post_id );
				break;
		}
	}

	/**
	 * Define sortable columns for Lessons.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, string> $columns Sortable columns.
	 * @return array<string, string> Modified sortable columns.
	 */
	public function lesson_sortable_columns( array $columns ): array {
		$columns['leaderspath_slug']           = 'leaderspath_slug';
		$columns['leaderspath_activity_count'] = 'leaderspath_activity_count';
		return $columns;
	}

	// -------------------------------------------------------------------------
	// Course Columns
	// -------------------------------------------------------------------------

	/**
	 * Define custom columns for Courses.
	 *
	 * @since 0.5.0
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string> Modified columns.
	 */
	public function course_columns( array $columns ): array {
		$new_columns = [];

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( 'title' === $key ) {
				$new_columns['leaderspath_slug']           = __( 'Slug', 'leaderspath' );
				$new_columns['leaderspath_lesson_count']   = __( 'Lessons', 'leaderspath' );
				$new_columns['leaderspath_prerequisites'] = __( 'Prerequisites', 'leaderspath' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render content for custom Course columns.
	 *
	 * @since 0.5.0
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function course_column_content( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'leaderspath_slug':
				$this->render_slug( $post_id );
				break;

			case 'leaderspath_lesson_count':
				$this->render_course_lesson_count( $post_id );
				break;

			case 'leaderspath_prerequisites':
				$this->render_course_prerequisites( $post_id );
				break;
		}
	}

	/**
	 * Define sortable columns for Courses.
	 *
	 * @since 0.5.0
	 *
	 * @param array<string, string> $columns Sortable columns.
	 * @return array<string, string> Modified sortable columns.
	 */
	public function course_sortable_columns( array $columns ): array {
		$columns['leaderspath_slug'] = 'leaderspath_slug';
		return $columns;
	}

	// -------------------------------------------------------------------------
	// Context File Columns
	// -------------------------------------------------------------------------

	/**
	 * Define custom columns for Context Files.
	 *
	 * @since 0.5.0
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string> Modified columns.
	 */
	public function context_columns( array $columns ): array {
		$new_columns = [];

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( 'title' === $key ) {
				$new_columns['leaderspath_slug'] = __( 'Slug', 'leaderspath' );
			}
		}

		// Cohort goes at the end — it's the field editors scan for when
		// auditing which files are public versus organization-scoped.
		$new_columns['leaderspath_context_cohort'] = __( 'Cohort', 'leaderspath' );

		return $new_columns;
	}

	/**
	 * Render content for custom Context File columns.
	 *
	 * @since 0.5.0
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function context_column_content( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'leaderspath_slug':
				$this->render_slug( $post_id );
				break;

			case 'leaderspath_context_cohort':
				$this->render_context_cohort( $post_id );
				break;
		}
	}

	/**
	 * Define sortable columns for Context Files.
	 *
	 * @since 0.5.0
	 *
	 * @param array<string, string> $columns Sortable columns.
	 * @return array<string, string> Modified sortable columns.
	 */
	public function context_sortable_columns( array $columns ): array {
		$columns['leaderspath_slug']           = 'leaderspath_slug';
		$columns['leaderspath_context_cohort'] = 'leaderspath_context_cohort';
		return $columns;
	}

	/**
	 * Render the cohort a context file is scoped to, linked to its edit screen.
	 *
	 * Empty means shared/public curriculum content — shown as a dash, not
	 * blank, so it reads as a deliberate state rather than a missing value.
	 *
	 * @since 0.7.0
	 *
	 * @param int $post_id Context file post ID.
	 */
	private function render_context_cohort( int $post_id ): void {
		$cohort_id = get_field( 'context_cohort', $post_id );

		if ( empty( $cohort_id ) ) {
			echo '<span class="dashicons dashicons-admin-site-alt3" aria-hidden="true" title="' . esc_attr__( 'Public — shared across all cohorts', 'leaderspath' ) . '"></span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'Public', 'leaderspath' ) . '</span>';
			return;
		}

		printf(
			'<a href="%s">%s</a>',
			esc_url( (string) get_edit_post_link( (int) $cohort_id ) ),
			esc_html( get_the_title( (int) $cohort_id ) )
		);
	}

	/**
	 * Render the "Filter by cohort" dropdown above the Context Files list.
	 *
	 * Facilitators only see their own cohort(s) in the dropdown — matching
	 * what filter_context_files_by_cohort() will actually let them query.
	 *
	 * @since 0.7.0
	 *
	 * @param string $post_type Current screen's post type.
	 */
	public function context_cohort_filter_dropdown( string $post_type ): void {
		if ( 'leaderspath_context' !== $post_type ) {
			return;
		}

		$is_privileged = current_user_can( 'manage_options' ) || current_user_can( 'edit_others_posts' );

		$cohort_ids = $is_privileged
			? get_posts( [
				'post_type'      => 'leaderspath_cohort',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			] )
			: ( class_exists( 'LeadersPath\Includes\WooCommerce' )
				? \LeadersPath\Includes\Enrollment::get_facilitator_cohorts( get_current_user_id() )
				: [] );

		if ( empty( $cohort_ids ) ) {
			return;
		}

		$selected = isset( $_GET['leaderspath_context_cohort'] ) ? sanitize_text_field( wp_unslash( $_GET['leaderspath_context_cohort'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter, no state change.

		echo '<select name="leaderspath_context_cohort">';
		echo '<option value="">' . esc_html__( 'All cohorts', 'leaderspath' ) . '</option>';
		echo '<option value="public"' . selected( $selected, 'public', false ) . '>' . esc_html__( 'Public only', 'leaderspath' ) . '</option>';

		foreach ( $cohort_ids as $cohort_id ) {
			printf(
				'<option value="%d"%s>%s</option>',
				(int) $cohort_id,
				selected( $selected, (string) $cohort_id, false ),
				esc_html( get_the_title( (int) $cohort_id ) )
			);
		}

		echo '</select>';
	}

	/**
	 * Apply the cohort filter dropdown's selection to the Context Files query.
	 *
	 * @since 0.7.0
	 *
	 * @param \WP_Query $query The query object.
	 */
	public function filter_context_files_by_cohort( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || 'leaderspath_context' !== $query->get( 'post_type' ) ) {
			return;
		}

		$selected = isset( $_GET['leaderspath_context_cohort'] ) ? sanitize_text_field( wp_unslash( $_GET['leaderspath_context_cohort'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter, no state change.

		if ( '' !== $selected ) {
			if ( 'public' === $selected ) {
				$query->set( 'meta_query', [
					[
						'key'     => 'context_cohort',
						'compare' => 'NOT EXISTS',
					],
				] );
			} elseif ( is_numeric( $selected ) ) {
				$query->set( 'meta_query', [
					[
						'key'   => 'context_cohort',
						'value' => (int) $selected,
					],
				] );
			}
		}

		// Facilitators (non-admin/editor): restrict to their own cohort(s) plus
		// public content, regardless of the dropdown — this is the actual
		// security boundary, the dropdown above is just convenience for staff.
		if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		if ( ! class_exists( 'LeadersPath\Includes\WooCommerce' ) ) {
			return;
		}

		$facilitator_cohorts = \LeadersPath\Includes\Enrollment::get_facilitator_cohorts( get_current_user_id() );

		$scope_query = [ 'relation' => 'OR' ];
		if ( ! empty( $facilitator_cohorts ) ) {
			$scope_query[] = [
				'key'     => 'context_cohort',
				'value'   => $facilitator_cohorts,
				'compare' => 'IN',
			];
		}
		$scope_query[] = [
			'key'     => 'context_cohort',
			'compare' => 'NOT EXISTS',
		];

		$existing_meta_query = $query->get( 'meta_query' );
		$query->set( 'meta_query', [
			'relation' => 'AND',
			$scope_query,
			$existing_meta_query ?: [],
		] );
	}

	// -------------------------------------------------------------------------
	// Skill Columns
	// -------------------------------------------------------------------------

	/**
	 * Define custom columns for Skills.
	 *
	 * @since 0.5.0
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string> Modified columns.
	 */
	public function skill_columns( array $columns ): array {
		$new_columns = [];

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( 'title' === $key ) {
				$new_columns['leaderspath_slug'] = __( 'Slug', 'leaderspath' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render content for custom Skill columns.
	 *
	 * @since 0.5.0
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function skill_column_content( string $column, int $post_id ): void {
		if ( 'leaderspath_slug' === $column ) {
			$this->render_slug( $post_id );
		}
	}

	/**
	 * Define sortable columns for Skills.
	 *
	 * @since 0.5.0
	 *
	 * @param array<string, string> $columns Sortable columns.
	 * @return array<string, string> Modified sortable columns.
	 */
	public function skill_sortable_columns( array $columns ): array {
		$columns['leaderspath_slug'] = 'leaderspath_slug';
		return $columns;
	}

	// -------------------------------------------------------------------------
	// Sorting
	// -------------------------------------------------------------------------

	/**
	 * Handle custom column sorting.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_Query $query The query object.
	 */
	public function handle_sorting( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'leaderspath_slug' === $orderby ) {
			$query->set( 'orderby', 'name' );
		}

		if ( 'leaderspath_duration' === $orderby ) {
			$query->set( 'meta_key', 'activity_duration' );
			$query->set( 'orderby', 'meta_value_num' );
		}

		// Note: activity_count sorting would require a custom query/subquery.
		// For now, we'll sort by the stored total_duration instead.
		if ( 'leaderspath_activity_count' === $orderby ) {
			$query->set( 'meta_key', 'lesson_total_duration' );
			$query->set( 'orderby', 'meta_value_num' );
		}

		if ( 'leaderspath_context_cohort' === $orderby ) {
			$query->set( 'meta_key', 'context_cohort' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	// -------------------------------------------------------------------------
	// Activity Column Renderers
	// -------------------------------------------------------------------------

	/**
	 * Render the activity duration.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id Activity post ID.
	 */
	private function render_activity_duration( int $post_id ): void {
		$duration = get_field( 'activity_duration', $post_id );

		// Hidden value for quick edit.
		echo '<span class="leaderspath-duration-value hidden">' . esc_html( (string) $duration ) . '</span>';

		if ( empty( $duration ) ) {
			echo '<span class="dashicons dashicons-minus" aria-hidden="true"></span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'Not set', 'leaderspath' ) . '</span>';
			return;
		}

		printf(
			/* translators: %d: number of minutes */
			esc_html( _n( '%d min', '%d mins', (int) $duration, 'leaderspath' ) ),
			(int) $duration
		);
	}

	// -------------------------------------------------------------------------
	// Lesson Column Renderers
	// -------------------------------------------------------------------------

	/**
	 * Render the activity count for a lesson.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id Lesson post ID.
	 */
	private function render_lesson_activity_count( int $post_id ): void {
		$activities = get_field( 'lesson_activities', $post_id );
		$count      = is_array( $activities ) ? count( $activities ) : 0;

		if ( 0 === $count ) {
			echo '<span class="dashicons dashicons-minus" aria-hidden="true"></span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'No activities', 'leaderspath' ) . '</span>';
			return;
		}

		printf(
			/* translators: %d: number of activities */
			esc_html( _n( '%d activity', '%d activities', $count, 'leaderspath' ) ),
			$count
		);
	}

	/**
	 * Render the total duration for a lesson.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id Lesson post ID.
	 */
	private function render_lesson_total_duration( int $post_id ): void {
		// Calculate total duration from activities.
		$activities     = get_field( 'lesson_activities', $post_id );
		$total_duration = 0;

		if ( is_array( $activities ) ) {
			foreach ( $activities as $activity_id ) {
				$duration = get_field( 'activity_duration', $activity_id );
				if ( ! empty( $duration ) ) {
					$total_duration += (int) $duration;
				}
			}
		}

		if ( 0 === $total_duration ) {
			echo '<span class="dashicons dashicons-minus" aria-hidden="true"></span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'Not set', 'leaderspath' ) . '</span>';
			return;
		}

		// Display in hours and minutes if over 60 minutes.
		if ( $total_duration >= 60 ) {
			$hours   = floor( $total_duration / 60 );
			$minutes = $total_duration % 60;

			if ( $minutes > 0 ) {
				printf(
					/* translators: 1: hours, 2: minutes */
					esc_html__( '%1$dh %2$dm', 'leaderspath' ),
					$hours,
					$minutes
				);
			} else {
				printf(
					/* translators: %d: number of hours */
					esc_html( _n( '%d hour', '%d hours', (int) $hours, 'leaderspath' ) ),
					(int) $hours
				);
			}
		} else {
			printf(
				/* translators: %d: number of minutes */
				esc_html( _n( '%d min', '%d mins', $total_duration, 'leaderspath' ) ),
				$total_duration
			);
		}
	}

	// -------------------------------------------------------------------------
	// Course Column Renderers
	// -------------------------------------------------------------------------

	/**
	 * Render the lesson count for a course.
	 *
	 * @since 0.5.0
	 *
	 * @param int $post_id Course post ID.
	 */
	private function render_course_lesson_count( int $post_id ): void {
		$lessons = get_field( 'course_lessons', $post_id );
		$count   = is_array( $lessons ) ? count( $lessons ) : 0;

		if ( 0 === $count ) {
			echo '<span class="dashicons dashicons-minus" aria-hidden="true"></span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'No lessons', 'leaderspath' ) . '</span>';
			return;
		}

		printf(
			/* translators: %d: number of lessons */
			esc_html( _n( '%d lesson', '%d lessons', $count, 'leaderspath' ) ),
			$count
		);
	}

	/**
	 * Render the prerequisites for a course.
	 *
	 * @since 0.5.0
	 *
	 * @param int $post_id Course post ID.
	 */
	private function render_course_prerequisites( int $post_id ): void {
		$prerequisites = get_field( 'course_prerequisites', $post_id );

		if ( empty( $prerequisites ) || ! is_array( $prerequisites ) ) {
			echo '<span class="dashicons dashicons-minus" aria-hidden="true"></span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'None', 'leaderspath' ) . '</span>';
			return;
		}

		$links = [];
		foreach ( $prerequisites as $prereq_id ) {
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_edit_post_link( (int) $prereq_id ) ),
				esc_html( get_the_title( (int) $prereq_id ) )
			);
		}

		echo implode( ', ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Links escaped above.
	}

	// -------------------------------------------------------------------------
	// Quick Edit
	// -------------------------------------------------------------------------

	/**
	 * Render quick edit fields.
	 *
	 * @since 0.1.0
	 *
	 * @param string $column_name Column name.
	 * @param string $post_type   Post type.
	 */
	public function quick_edit_fields( string $column_name, string $post_type ): void {
		// Activity quick edit fields.
		if ( 'leaderspath_activity' === $post_type && 'leaderspath_duration' === $column_name ) {
			?>
			<fieldset class="inline-edit-col-right">
				<div class="inline-edit-col">
					<label>
						<span class="title"><?php esc_html_e( 'Duration', 'leaderspath' ); ?></span>
						<span class="input-text-wrap">
							<input type="number" name="leaderspath_activity_duration" class="leaderspath-activity-duration" min="1" max="480" step="1" />
							<span class="description"><?php esc_html_e( 'minutes', 'leaderspath' ); ?></span>
						</span>
					</label>
				</div>
			</fieldset>
			<?php
		}
	}

	/**
	 * Save quick edit data.
	 *
	 * @since 0.1.0
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save_quick_edit_data( int $post_id, \WP_Post $post ): void {
		// Skip autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Verify this is a quick edit (inline-save action).
		if ( ! isset( $_POST['_inline_edit'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_inline_edit'] ) ), 'inlineeditnonce' ) ) {
			return;
		}

		// Check capabilities.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save activity fields.
		if ( 'leaderspath_activity' === $post->post_type ) {
			if ( isset( $_POST['leaderspath_activity_duration'] ) ) {
				$duration = absint( $_POST['leaderspath_activity_duration'] );
				if ( $duration > 0 && $duration <= 480 ) {
					update_field( 'activity_duration', $duration, $post_id );
				} elseif ( '' === $_POST['leaderspath_activity_duration'] ) {
					delete_field( 'activity_duration', $post_id );
				}
			}
		}
	}

	/**
	 * Enqueue quick edit JavaScript.
	 *
	 * @since 0.1.0
	 *
	 * @param string $hook_suffix Admin page hook suffix.
	 */
	public function enqueue_quick_edit_script( string $hook_suffix ): void {
		if ( 'edit.php' !== $hook_suffix ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'leaderspath_activity' !== $screen->post_type ) {
			return;
		}

		// Inline script to populate quick edit fields.
		$script = <<<'JS'
(function($) {
	var $inlineEdit = inlineEditPost.edit;

	inlineEditPost.edit = function(id) {
		$inlineEdit.apply(this, arguments);

		var postId = 0;
		if (typeof(id) === 'object') {
			postId = parseInt(this.getId(id));
		}

		if (postId > 0) {
			var $row = $('#post-' + postId);
			var $editRow = $('#edit-' + postId);

			// Activity duration.
			var duration = $row.find('.leaderspath-duration-value').text();
			if (duration) {
				$editRow.find('input.leaderspath-activity-duration').val(duration);
			}
		}
	};
})(jQuery);
JS;

		wp_add_inline_script( 'inline-edit-post', $script );
	}

	// -------------------------------------------------------------------------
	// Cohort Columns (purchase-time instance — leaderspath_cohort CPT)
	// -------------------------------------------------------------------------

	/**
	 * Add custom columns for the Cohort admin list.
	 *
	 * @since 0.7.0
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string> Modified columns.
	 */
	public function cohort_columns( array $columns ): array {
		$new_columns = [];

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			// Insert cohort columns after the title column.
			if ( 'title' === $key ) {
				$new_columns['leaderspath_cohort_courses']        = __( 'Courses', 'leaderspath' );
				$new_columns['leaderspath_cohort_phase']          = __( 'Phase', 'leaderspath' );
				$new_columns['leaderspath_cohort_enrollees']      = __( 'Enrollees', 'leaderspath' );
				$new_columns['leaderspath_cohort_dates']          = __( 'Dates', 'leaderspath' );
				$new_columns['leaderspath_cohort_owner']          = __( 'Owner', 'leaderspath' );
				$new_columns['leaderspath_cohort_payment_status'] = __( 'Payment', 'leaderspath' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render content for custom Cohort columns.
	 *
	 * @since 0.7.0
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function cohort_column_content( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'leaderspath_cohort_courses':
				$this->render_cohort_courses( $post_id );
				break;

			case 'leaderspath_cohort_phase':
				$this->render_cohort_phase( $post_id );
				break;

			case 'leaderspath_cohort_enrollees':
				$this->render_cohort_enrollees( $post_id );
				break;

			case 'leaderspath_cohort_dates':
				$this->render_cohort_dates( $post_id );
				break;

			case 'leaderspath_cohort_owner':
				$this->render_cohort_owner( $post_id );
				break;

			case 'leaderspath_cohort_payment_status':
				$this->render_cohort_payment_status( $post_id );
				break;
		}
	}

	/**
	 * Define sortable columns for Cohorts.
	 *
	 * @since 0.7.0
	 *
	 * @param array<string, string> $columns Sortable columns.
	 * @return array<string, string> Modified sortable columns.
	 */
	public function cohort_sortable_columns( array $columns ): array {
		$columns['leaderspath_cohort_payment_status'] = 'leaderspath_cohort_payment_status';
		return $columns;
	}

	/**
	 * Add a "Preview First Lesson" row action to the Cohorts list, in place
	 * of the native "View" action WordPress can't offer for a
	 * non-publicly-queryable CPT.
	 *
	 * @since 0.13.0
	 *
	 * @param array<string, string> $actions Existing row actions.
	 * @param \WP_Post              $post    The post being rendered.
	 * @return array<string, string> Modified row actions.
	 */
	public function cohort_row_actions( array $actions, \WP_Post $post ): array {
		if ( 'leaderspath_cohort' !== $post->post_type ) {
			return $actions;
		}

		$url = $this->get_cohort_preview_url( $post->ID );

		if ( null === $url ) {
			return $actions;
		}

		$actions['leaderspath_preview_lesson'] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( $url ),
			esc_html__( 'Preview First Lesson', 'leaderspath' )
		);

		return $actions;
	}

	/**
	 * Build the /learn/{cohort-slug}/lesson/{lesson-slug}/ URL for the first
	 * lesson this cohort can actually reach (Cohort → Course(s) → Lessons,
	 * same membership Cohort_Rewrite itself validates against — this never
	 * links to a lesson the cohort wouldn't legitimately resolve).
	 *
	 * @since 0.13.0
	 *
	 * @param int $cohort_id Cohort post ID.
	 * @return string|null The preview URL, or null if the cohort has no
	 *                      reachable lesson yet (no courses assigned, or its
	 *                      courses have no lessons).
	 */
	private function get_cohort_preview_url( int $cohort_id ): ?string {
		$cohort = get_post( $cohort_id );
		if ( ! $cohort || empty( $cohort->post_name ) ) {
			return null;
		}

		$lesson_ids = \LeadersPath\Includes\Enrollment::get_cohort_lessons( $cohort_id );
		if ( empty( $lesson_ids ) ) {
			return null;
		}

		$lesson = get_post( $lesson_ids[0] );
		if ( ! $lesson || empty( $lesson->post_name ) ) {
			return null;
		}

		return home_url( sprintf( '/learn/%s/lesson/%s/', $cohort->post_name, $lesson->post_name ) );
	}

	/**
	 * Render the courses linked to a cohort.
	 *
	 * @since 0.4.0
	 *
	 * @param int $post_id Cohort post ID.
	 */
	private function render_cohort_courses( int $post_id ): void {
		$courses = get_field( 'cohort_courses', $post_id );

		if ( empty( $courses ) || ! is_array( $courses ) ) {
			echo '<span class="dashicons dashicons-minus" aria-hidden="true"></span>';
			return;
		}

		$links = [];
		foreach ( $courses as $course_id ) {
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_edit_post_link( (int) $course_id ) ),
				esc_html( get_the_title( (int) $course_id ) )
			);
		}

		echo implode( ', ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Links escaped above.
	}

	/**
	 * Render the pedagogical phase for a cohort.
	 *
	 * Distinct from the Payment column — this is the curriculum timeline
	 * (upcoming/active/completed from dates), not commitment/billing state.
	 *
	 * @since 0.4.0
	 *
	 * @param int $post_id Cohort post ID.
	 */
	private function render_cohort_phase( int $post_id ): void {
		$phase = \LeadersPath\Includes\Enrollment::get_cohort_phase( $post_id );

		$labels = [
			'upcoming'  => __( 'Upcoming', 'leaderspath' ),
			'active'    => __( 'Active', 'leaderspath' ),
			'completed' => __( 'Completed', 'leaderspath' ),
		];

		$colors = [
			'upcoming'  => '#46b450',
			'active'    => '#2271b1',
			'completed' => '#888',
		];

		printf(
			'<span style="color: %s; font-weight: 500;">%s</span>',
			esc_attr( $colors[ $phase ] ?? '#666' ),
			esc_html( $labels[ $phase ] ?? $phase )
		);
	}

	/**
	 * Render the enrollee count for a cohort, against its purchased seat count.
	 *
	 * Reads `cohort_seats` (set at creation from the purchased
	 * product/variation — see Enrollment::create_cohort()) rather than WC
	 * stock, since a cohort instance may exist independent of any WC product
	 * at all.
	 *
	 * @since 0.4.0
	 *
	 * @param int $post_id Cohort post ID.
	 */
	private function render_cohort_enrollees( int $post_id ): void {
		$enrollees = \LeadersPath\Includes\Enrollment::get_cohort_enrollees( $post_id );
		$count     = count( $enrollees );
		$seats     = (int) get_field( 'cohort_seats', $post_id );

		if ( $seats > 0 ) {
			printf( '%d / %d', $count, $seats );
		} else {
			echo esc_html( (string) $count );
		}
	}

	/**
	 * Render the date range for a cohort.
	 *
	 * @since 0.4.0
	 *
	 * @param int $post_id Cohort post ID.
	 */
	private function render_cohort_dates( int $post_id ): void {
		$start = get_field( 'cohort_start_date', $post_id );
		$end   = get_field( 'cohort_end_date', $post_id );

		if ( empty( $start ) && empty( $end ) ) {
			echo '<span class="dashicons dashicons-minus" aria-hidden="true"></span>';
			return;
		}

		$format = get_option( 'date_format' );

		$start_display = $start ? date_i18n( $format, strtotime( $start ) ) : '—';
		$end_display   = $end ? date_i18n( $format, strtotime( $end ) ) : '—';

		printf( '%s – %s', esc_html( $start_display ), esc_html( $end_display ) );
	}

	/**
	 * Render the cohort's owner (roster-management authority).
	 *
	 * @since 0.7.0
	 *
	 * @param int $post_id Cohort post ID.
	 */
	private function render_cohort_owner( int $post_id ): void {
		$owner_id = (int) get_field( 'cohort_owner', $post_id );

		if ( ! $owner_id ) {
			echo '<span class="dashicons dashicons-minus" aria-hidden="true"></span>';
			return;
		}

		$user = get_userdata( $owner_id );
		echo esc_html( $user ? $user->display_name : sprintf( '#%d', $owner_id ) );
	}

	/**
	 * Render the cohort's payment status.
	 *
	 * Distinct from the Phase column — this is commitment/billing state
	 * (see docs/TASKS.md Phase 14, "Creation trigger and payment status"),
	 * not the curriculum timeline.
	 *
	 * @since 0.7.0
	 *
	 * @param int $post_id Cohort post ID.
	 */
	private function render_cohort_payment_status( int $post_id ): void {
		$status = get_field( 'cohort_payment_status', $post_id ) ?: 'pending_payment';

		$labels = [
			'pending_payment' => __( 'Pending Payment', 'leaderspath' ),
			'paid'            => __( 'Paid', 'leaderspath' ),
		];

		$colors = [
			'pending_payment' => '#dba617',
			'paid'            => '#2271b1',
		];

		printf(
			'<span style="color: %s; font-weight: 500;">%s</span>',
			esc_attr( $colors[ $status ] ?? '#666' ),
			esc_html( $labels[ $status ] ?? $status )
		);

		if ( get_field( 'cohort_access_closed', $post_id ) ) {
			printf(
				' <span class="dashicons dashicons-lock" style="color: #d63638;" title="%s"></span>',
				esc_attr__( 'Access manually closed', 'leaderspath' )
			);
		}
	}
}
