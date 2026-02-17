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

		// Skill columns.
		add_filter( 'manage_leaderspath_skill_posts_columns', [ $this, 'skill_columns' ] );
		add_action( 'manage_leaderspath_skill_posts_custom_column', [ $this, 'skill_column_content' ], 10, 2 );
		add_filter( 'manage_edit-leaderspath_skill_sortable_columns', [ $this, 'skill_sortable_columns' ] );

		// Cohort product columns — deferred because WC may not be loaded yet.
		add_action( 'plugins_loaded', function (): void {
			if ( class_exists( 'WooCommerce' ) ) {
				add_filter( 'manage_product_posts_columns', [ $this, 'cohort_product_columns' ] );
				add_action( 'manage_product_posts_custom_column', [ $this, 'cohort_product_column_content' ], 10, 2 );
			}
		} );

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
		if ( 'leaderspath_slug' === $column ) {
			$this->render_slug( $post_id );
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
		$columns['leaderspath_slug'] = 'leaderspath_slug';
		return $columns;
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
	// Cohort Product Columns (WooCommerce)
	// -------------------------------------------------------------------------

	/**
	 * Add custom columns for cohort products in the WooCommerce product list.
	 *
	 * @since 0.4.0
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string> Modified columns.
	 */
	public function cohort_product_columns( array $columns ): array {
		$new_columns = [];

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			// Insert cohort columns after the product name column.
			if ( 'name' === $key ) {
				$new_columns['leaderspath_cohort_courses']   = __( 'Courses', 'leaderspath' );
				$new_columns['leaderspath_cohort_phase']     = __( 'Phase', 'leaderspath' );
				$new_columns['leaderspath_cohort_enrollees'] = __( 'Enrollees', 'leaderspath' );
				$new_columns['leaderspath_cohort_dates']     = __( 'Dates', 'leaderspath' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render content for custom cohort product columns.
	 *
	 * @since 0.4.0
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function cohort_product_column_content( string $column, int $post_id ): void {
		// Only render for cohort products.
		if ( ! \LeadersPath\Includes\WooCommerce::is_cohort_product( $post_id ) ) {
			return;
		}

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
		}
	}

	/**
	 * Render the courses linked to a cohort.
	 *
	 * @since 0.4.0
	 *
	 * @param int $post_id Product post ID.
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
	 * Render the phase for a cohort product.
	 *
	 * @since 0.4.0
	 *
	 * @param int $post_id Product post ID.
	 */
	private function render_cohort_phase( int $post_id ): void {
		$phase = \LeadersPath\Includes\WooCommerce::get_cohort_phase( $post_id );

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
	 * Render the enrollee count for a cohort.
	 *
	 * @since 0.4.0
	 *
	 * @param int $post_id Product post ID.
	 */
	private function render_cohort_enrollees( int $post_id ): void {
		if ( ! class_exists( 'LeadersPath\Includes\WooCommerce' ) ) {
			echo '0';
			return;
		}

		$enrollees = \LeadersPath\Includes\WooCommerce::get_cohort_enrollees( $post_id );
		$count     = count( $enrollees );
		$product   = wc_get_product( $post_id );

		if ( $product && $product->managing_stock() && $product->get_stock_quantity() > 0 ) {
			printf(
				'%d / %d',
				$count,
				$product->get_stock_quantity()
			);
		} else {
			echo esc_html( (string) $count );
		}
	}

	/**
	 * Render the date range for a cohort.
	 *
	 * @since 0.4.0
	 *
	 * @param int $post_id Product post ID.
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
}
