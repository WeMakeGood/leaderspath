<?php
/**
 * Custom admin columns for LeadersPath CPTs.
 *
 * @package LeadersPath
 * @since   0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Admin;

/**
 * Manages custom admin columns for Activities and Lessons.
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

		// Handle sorting.
		add_action( 'pre_get_posts', [ $this, 'handle_sorting' ] );

		// Quick edit support.
		add_action( 'quick_edit_custom_box', [ $this, 'quick_edit_fields' ], 10, 2 );
		add_action( 'save_post', [ $this, 'save_quick_edit_data' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_quick_edit_script' ] );
	}

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
				$new_columns['leaderspath_lesson']   = __( 'Lesson', 'leaderspath' );
				$new_columns['leaderspath_duration'] = __( 'Duration', 'leaderspath' );
				$new_columns['leaderspath_chatbot']  = __( 'Chatbot', 'leaderspath' );
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
			case 'leaderspath_lesson':
				$this->render_activity_lesson( $post_id );
				break;

			case 'leaderspath_duration':
				$this->render_activity_duration( $post_id );
				break;

			case 'leaderspath_chatbot':
				$this->render_activity_chatbot_status( $post_id );
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
		$columns['leaderspath_duration'] = 'leaderspath_duration';
		return $columns;
	}

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
				$new_columns['leaderspath_activity_count'] = __( 'Activities', 'leaderspath' );
				$new_columns['leaderspath_difficulty']   = __( 'Difficulty', 'leaderspath' );
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
			case 'leaderspath_activity_count':
				$this->render_lesson_activity_count( $post_id );
				break;

			case 'leaderspath_difficulty':
				$this->render_lesson_difficulty( $post_id );
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
		$columns['leaderspath_activity_count'] = 'leaderspath_activity_count';
		return $columns;
	}

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

	/**
	 * Render the lesson(s) an activity belongs to.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id Activity post ID.
	 */
	private function render_activity_lesson( int $post_id ): void {
		// Find lessons that include this activity.
		$lessons = get_posts( [
			'post_type'      => 'leaderspath_lesson',
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'     => 'lesson_activities',
					'value'   => sprintf( '"%d"', $post_id ),
					'compare' => 'LIKE',
				],
			],
			'fields'         => 'ids',
		] );

		if ( empty( $lessons ) ) {
			echo '<span class="dashicons dashicons-minus" aria-hidden="true"></span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'No lesson', 'leaderspath' ) . '</span>';
			return;
		}

		$lesson_links = [];
		foreach ( $lessons as $lesson_id ) {
			$lesson_links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_edit_post_link( $lesson_id ) ),
				esc_html( get_the_title( $lesson_id ) )
			);
		}

		echo implode( ', ', $lesson_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Links escaped above.
	}

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

	/**
	 * Render the chatbot status for an activity.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id Activity post ID.
	 */
	private function render_activity_chatbot_status( int $post_id ): void {
		$enabled = get_field( 'chatbot_enabled', $post_id );
		$model   = get_field( 'chatbot_model', $post_id );

		// Hidden value for quick edit.
		echo '<span class="leaderspath-chatbot-value hidden">' . ( $enabled ? '1' : '0' ) . '</span>';

		if ( $enabled ) {
			$model_labels = [
				'sonnet'   => __( 'Sonnet', 'leaderspath' ),
				'haiku'    => __( 'Haiku', 'leaderspath' ),
				'opus-4.5' => __( 'Opus 4.5', 'leaderspath' ),
			];

			$model_label = $model_labels[ $model ] ?? $model;

			printf(
				'<span class="dashicons dashicons-yes-alt" style="color: #46b450;" aria-hidden="true"></span> %s',
				esc_html( $model_label )
			);
		} else {
			echo '<span class="dashicons dashicons-no-alt" style="color: #dc3232;" aria-hidden="true"></span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'Disabled', 'leaderspath' ) . '</span>';
		}
	}

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
	 * Render the difficulty level for a lesson.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id Lesson post ID.
	 */
	private function render_lesson_difficulty( int $post_id ): void {
		$difficulty = get_field( 'lesson_difficulty', $post_id );

		// Hidden value for quick edit.
		echo '<span class="leaderspath-difficulty-value hidden">' . esc_html( (string) $difficulty ) . '</span>';

		if ( empty( $difficulty ) ) {
			echo '<span class="dashicons dashicons-minus" aria-hidden="true"></span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'Not set', 'leaderspath' ) . '</span>';
			return;
		}

		$labels = [
			'beginner'     => __( 'Beginner', 'leaderspath' ),
			'intermediate' => __( 'Intermediate', 'leaderspath' ),
			'advanced'     => __( 'Advanced', 'leaderspath' ),
		];

		$colors = [
			'beginner'     => '#46b450',
			'intermediate' => '#ffb900',
			'advanced'     => '#dc3232',
		];

		$label = $labels[ $difficulty ] ?? $difficulty;
		$color = $colors[ $difficulty ] ?? '#666';

		printf(
			'<span style="color: %s; font-weight: 500;">%s</span>',
			esc_attr( $color ),
			esc_html( $label )
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
		if ( 'leaderspath_activity' === $post_type ) {
			if ( 'leaderspath_duration' === $column_name ) {
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

			if ( 'leaderspath_chatbot' === $column_name ) {
				?>
				<fieldset class="inline-edit-col-right">
					<div class="inline-edit-col">
						<label class="alignleft">
							<input type="checkbox" name="leaderspath_chatbot_enabled" class="leaderspath-chatbot-enabled" value="1" />
							<span class="checkbox-title"><?php esc_html_e( 'Enable Chatbot', 'leaderspath' ); ?></span>
						</label>
					</div>
				</fieldset>
				<?php
			}
		}

		// Lesson quick edit fields.
		if ( 'leaderspath_lesson' === $post_type && 'leaderspath_difficulty' === $column_name ) {
			?>
			<fieldset class="inline-edit-col-right">
				<div class="inline-edit-col">
					<label>
						<span class="title"><?php esc_html_e( 'Difficulty', 'leaderspath' ); ?></span>
						<select name="leaderspath_lesson_difficulty" class="leaderspath-lesson-difficulty">
							<option value="beginner"><?php esc_html_e( 'Beginner', 'leaderspath' ); ?></option>
							<option value="intermediate"><?php esc_html_e( 'Intermediate', 'leaderspath' ); ?></option>
							<option value="advanced"><?php esc_html_e( 'Advanced', 'leaderspath' ); ?></option>
						</select>
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
			// Duration.
			if ( isset( $_POST['leaderspath_activity_duration'] ) ) {
				$duration = absint( $_POST['leaderspath_activity_duration'] );
				if ( $duration > 0 && $duration <= 480 ) {
					update_field( 'activity_duration', $duration, $post_id );
				} elseif ( '' === $_POST['leaderspath_activity_duration'] ) {
					delete_field( 'activity_duration', $post_id );
				}
			}

			// Chatbot enabled - checkbox won't be sent if unchecked.
			$chatbot_enabled = isset( $_POST['leaderspath_chatbot_enabled'] ) ? 1 : 0;
			update_field( 'chatbot_enabled', $chatbot_enabled, $post_id );
		}

		// Save lesson fields.
		if ( 'leaderspath_lesson' === $post->post_type ) {
			if ( isset( $_POST['leaderspath_lesson_difficulty'] ) ) {
				$valid = [ 'beginner', 'intermediate', 'advanced' ];
				$difficulty = sanitize_text_field( wp_unslash( $_POST['leaderspath_lesson_difficulty'] ) );
				if ( in_array( $difficulty, $valid, true ) ) {
					update_field( 'lesson_difficulty', $difficulty, $post_id );
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
		if ( ! $screen || ! in_array( $screen->post_type, [ 'leaderspath_activity', 'leaderspath_lesson' ], true ) ) {
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

			// Chatbot enabled.
			var chatbotEnabled = $row.find('.leaderspath-chatbot-value').text();
			$editRow.find('input.leaderspath-chatbot-enabled').prop('checked', chatbotEnabled === '1');

			// Lesson difficulty.
			var difficulty = $row.find('.leaderspath-difficulty-value').text();
			if (difficulty) {
				$editRow.find('select.leaderspath-lesson-difficulty').val(difficulty);
			}
		}
	};
})(jQuery);
JS;

		wp_add_inline_script( 'inline-edit-post', $script );
	}
}
