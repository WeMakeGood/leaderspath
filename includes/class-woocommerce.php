<?php
/**
 * WooCommerce integration for LeadersPath.
 *
 * Handles Cohort product checkbox, enrollment management,
 * and access gating for the content chain:
 * Cohort (WC product) → Course(s) → Lessons → Activities.
 *
 * Cohorts are Simple products with a "_cohort" meta flag, following the
 * same pattern as WooCommerce's built-in "Virtual" and "Downloadable"
 * checkboxes and the wc-donation-platform's "Donation" checkbox.
 *
 * @package LeadersPath
 * @since   0.4.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

/**
 * WooCommerce integration class.
 *
 * @since 0.4.0
 */
class WooCommerce {

	/**
	 * User meta key for enrollment tracking.
	 *
	 * Stores an array of cohort product IDs the user is enrolled in.
	 *
	 * @var string
	 */
	public const ENROLLMENT_META_KEY = 'leaderspath_enrollments';

	/**
	 * Product meta key for cohort flag.
	 *
	 * @var string
	 */
	public const COHORT_META_KEY = '_cohort';

	/**
	 * Initialize the class.
	 *
	 * @since 0.4.0
	 */
	public function __construct() {
		// Cohort checkbox on product data panel.
		add_filter( 'product_type_options', [ $this, 'add_cohort_checkbox' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_cohort_meta' ] );

		// Admin UI: show/hide ACF panel based on checkbox.
		add_action( 'admin_footer', [ $this, 'cohort_admin_js' ] );

		// Rewrite stock availability text for cohort products.
		add_filter( 'woocommerce_get_availability_text', [ $this, 'cohort_availability_text' ], 10, 2 );

		// Enrollment on order status changes.
		add_action( 'woocommerce_order_status_completed', [ $this, 'handle_order_completed' ] );
		add_action( 'woocommerce_order_status_refunded', [ $this, 'handle_order_refunded' ] );
		add_action( 'woocommerce_order_status_cancelled', [ $this, 'handle_order_cancelled' ] );
	}

	// -------------------------------------------------------------------------
	// Cohort Checkbox (Product Data Panel)
	// -------------------------------------------------------------------------

	/**
	 * Add "Cohort" checkbox next to Virtual and Downloadable.
	 *
	 * @since 0.4.0
	 *
	 * @param array<string, array<string, string>> $options Product type options.
	 * @return array<string, array<string, string>> Modified options.
	 */
	public function add_cohort_checkbox( array $options ): array {
		$options['cohort'] = [
			'id'            => self::COHORT_META_KEY,
			'wrapper_class' => 'show_if_simple',
			'label'         => __( 'Cohort', 'leaderspath' ),
			'description'   => __( 'Cohort products enroll learners in courses for facilitated learning.', 'leaderspath' ),
			'default'       => 'no',
		];

		return $options;
	}

	/**
	 * Save the cohort checkbox value when product is saved.
	 *
	 * @since 0.4.0
	 *
	 * @param int $post_id Product post ID.
	 */
	public function save_cohort_meta( int $post_id ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the nonce.
		$is_cohort = isset( $_POST[ self::COHORT_META_KEY ] ) ? 'yes' : 'no';
		update_post_meta( $post_id, self::COHORT_META_KEY, $is_cohort );
	}

	/**
	 * Check if a product is a cohort product.
	 *
	 * @since 0.4.0
	 *
	 * @param int $product_id WooCommerce product ID.
	 * @return bool True if the product has the cohort flag.
	 */
	public static function is_cohort_product( int $product_id ): bool {
		return 'yes' === get_post_meta( $product_id, self::COHORT_META_KEY, true );
	}

	/**
	 * Rewrite WooCommerce stock availability text for cohort products.
	 *
	 * Replaces "X in stock" with "X seats remaining" for cohort products.
	 *
	 * @since 0.4.0
	 *
	 * @param string      $availability The default availability text.
	 * @param \WC_Product $product      The product object.
	 * @return string Modified availability text for cohorts.
	 */
	public function cohort_availability_text( string $availability, \WC_Product $product ): string {
		if ( ! self::is_cohort_product( $product->get_id() ) ) {
			return $availability;
		}

		if ( $product->is_in_stock() && $product->managing_stock() ) {
			$stock = $product->get_stock_quantity();

			/* translators: %d: number of seats remaining */
			return sprintf( _n( '%d seat remaining', '%d seats remaining', $stock, 'leaderspath' ), $stock );
		}

		if ( ! $product->is_in_stock() ) {
			return __( 'Cohort full', 'leaderspath' );
		}

		return $availability;
	}

	/**
	 * Output JavaScript to toggle ACF Cohort Settings panel visibility.
	 *
	 * Shows the panel when the Cohort checkbox is checked, hides it otherwise.
	 * Also auto-checks Virtual when Cohort is checked (cohorts have no shipping).
	 *
	 * @since 0.4.0
	 */
	public function cohort_admin_js(): void {
		$screen = get_current_screen();

		if ( ! $screen || 'product' !== $screen->id ) {
			return;
		}

		?>
		<script type="text/javascript">
		jQuery(function($) {
			var $cohortCheckbox = $('input#_cohort');
			var $cohortSettings = $('#acf-group_cohort_settings');

			function toggleCohortPanel() {
				var isCohort = $cohortCheckbox.is(':checked');

				// Show Cohort Settings ACF panel only when checkbox is checked.
				if ($cohortSettings.length) {
					$cohortSettings.toggle(isCohort);
				}

				// Cohort products are always virtual — auto-check when cohort is checked.
				if (isCohort) {
					$('#_virtual').prop('checked', true).trigger('change');
				}

				// Show Inventory tab for cohorts (stock = max participants).
				if (isCohort) {
					$('.inventory_options').show();
					$('.inventory_tab').show();
				}
			}

			$cohortCheckbox.on('change', toggleCohortPanel);

			// Set initial state on page load.
			toggleCohortPanel();
		});
		</script>
		<?php
	}

	/**
	 * Get the cohort phase based on start and end dates.
	 *
	 * @since 0.4.0
	 *
	 * @param int $product_id WooCommerce product ID.
	 * @return string One of 'upcoming', 'active', or 'completed'.
	 */
	public static function get_cohort_phase( int $product_id ): string {
		$start_date = get_field( 'cohort_start_date', $product_id );
		$end_date   = get_field( 'cohort_end_date', $product_id );
		$today      = current_time( 'Y-m-d' );

		if ( ! empty( $start_date ) && $today < $start_date ) {
			return 'upcoming';
		}

		if ( ! empty( $end_date ) && $today > $end_date ) {
			return 'completed';
		}

		return 'active';
	}

	// -------------------------------------------------------------------------
	// Enrollment System
	// -------------------------------------------------------------------------

	/**
	 * Handle order completion — enroll user in cohort(s).
	 *
	 * @since 0.4.0
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function handle_order_completed( int $order_id ): void {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$user_id = $order->get_customer_id();
		if ( 0 === $user_id ) {
			return; // Guest checkout — no enrollment.
		}

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( $product && self::is_cohort_product( $product->get_id() ) ) {
				self::enroll_user( $user_id, $product->get_id() );
			}
		}
	}

	/**
	 * Handle order refund — unenroll user from cohort(s).
	 *
	 * @since 0.4.0
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function handle_order_refunded( int $order_id ): void {
		$this->unenroll_order_cohorts( $order_id );
	}

	/**
	 * Handle order cancellation — unenroll user from cohort(s).
	 *
	 * @since 0.4.0
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function handle_order_cancelled( int $order_id ): void {
		$this->unenroll_order_cohorts( $order_id );
	}

	/**
	 * Unenroll a user from all cohorts in an order.
	 *
	 * @since 0.4.0
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	private function unenroll_order_cohorts( int $order_id ): void {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$user_id = $order->get_customer_id();
		if ( 0 === $user_id ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( $product && self::is_cohort_product( $product->get_id() ) ) {
				self::unenroll_user( $user_id, $product->get_id() );
			}
		}
	}

	/**
	 * Enroll a user in a cohort.
	 *
	 * @since 0.4.0
	 *
	 * @param int $user_id   WordPress user ID.
	 * @param int $cohort_id WooCommerce product (cohort) ID.
	 * @return bool True on success.
	 */
	public static function enroll_user( int $user_id, int $cohort_id ): bool {
		$enrollments = get_user_meta( $user_id, self::ENROLLMENT_META_KEY, true );

		if ( ! is_array( $enrollments ) ) {
			$enrollments = [];
		}

		// Already enrolled.
		if ( in_array( $cohort_id, $enrollments, true ) ) {
			return true;
		}

		$enrollments[] = $cohort_id;
		update_user_meta( $user_id, self::ENROLLMENT_META_KEY, $enrollments );
		update_user_meta( $user_id, "leaderspath_enrollment_{$cohort_id}_date", current_time( 'mysql' ) );

		// Grant student role if user doesn't have it.
		$user = get_userdata( $user_id );
		if ( $user && ! in_array( 'leaderspath_student', $user->roles, true ) ) {
			$user->add_role( 'leaderspath_student' );
		}

		/**
		 * Fires after a user is enrolled in a cohort.
		 *
		 * @since 0.4.0
		 *
		 * @param int $user_id   WordPress user ID.
		 * @param int $cohort_id WooCommerce product (cohort) ID.
		 */
		do_action( 'leaderspath_user_enrolled', $user_id, $cohort_id );

		return true;
	}

	/**
	 * Unenroll a user from a cohort.
	 *
	 * @since 0.4.0
	 *
	 * @param int $user_id   WordPress user ID.
	 * @param int $cohort_id WooCommerce product (cohort) ID.
	 * @return bool True on success.
	 */
	public static function unenroll_user( int $user_id, int $cohort_id ): bool {
		$enrollments = get_user_meta( $user_id, self::ENROLLMENT_META_KEY, true );

		if ( ! is_array( $enrollments ) ) {
			return true; // Nothing to remove.
		}

		$key = array_search( $cohort_id, $enrollments, true );
		if ( false === $key ) {
			return true; // Not enrolled.
		}

		unset( $enrollments[ $key ] );
		$enrollments = array_values( $enrollments ); // Re-index.

		if ( empty( $enrollments ) ) {
			delete_user_meta( $user_id, self::ENROLLMENT_META_KEY );
		} else {
			update_user_meta( $user_id, self::ENROLLMENT_META_KEY, $enrollments );
		}

		delete_user_meta( $user_id, "leaderspath_enrollment_{$cohort_id}_date" );

		/**
		 * Fires after a user is unenrolled from a cohort.
		 *
		 * @since 0.4.0
		 *
		 * @param int $user_id   WordPress user ID.
		 * @param int $cohort_id WooCommerce product (cohort) ID.
		 */
		do_action( 'leaderspath_user_unenrolled', $user_id, $cohort_id );

		return true;
	}

	/**
	 * Check if a user is enrolled in a specific cohort.
	 *
	 * @since 0.4.0
	 *
	 * @param int $user_id   WordPress user ID.
	 * @param int $cohort_id WooCommerce product (cohort) ID.
	 * @return bool True if enrolled.
	 */
	public static function is_user_enrolled( int $user_id, int $cohort_id ): bool {
		$enrollments = get_user_meta( $user_id, self::ENROLLMENT_META_KEY, true );

		if ( ! is_array( $enrollments ) ) {
			return false;
		}

		return in_array( $cohort_id, $enrollments, true );
	}

	/**
	 * Get all cohort IDs a user is enrolled in.
	 *
	 * @since 0.4.0
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array<int> Array of cohort product IDs.
	 */
	public static function get_user_enrollments( int $user_id ): array {
		$enrollments = get_user_meta( $user_id, self::ENROLLMENT_META_KEY, true );

		if ( ! is_array( $enrollments ) ) {
			return [];
		}

		return array_map( 'intval', $enrollments );
	}

	/**
	 * Get all users enrolled in a specific cohort.
	 *
	 * @since 0.4.0
	 *
	 * @param int $cohort_id WooCommerce product (cohort) ID.
	 * @return array<int> Array of user IDs.
	 */
	public static function get_cohort_enrollees( int $cohort_id ): array {
		$users = get_users( [
			'meta_key'     => self::ENROLLMENT_META_KEY,
			'meta_value'   => sprintf( '"%d"', $cohort_id ),
			'meta_compare' => 'LIKE',
			'fields'       => 'ID',
		] );

		return array_map( 'intval', $users );
	}

	// -------------------------------------------------------------------------
	// Prerequisite Resolution
	// -------------------------------------------------------------------------

	/**
	 * Get all prerequisite courses for a cohort.
	 *
	 * Aggregates the `course_prerequisites` from every course linked to
	 * the cohort, de-duplicates, and excludes courses that are already
	 * included in the cohort itself (a course can't be both required AND
	 * delivered by the same cohort).
	 *
	 * @since 0.5.0
	 *
	 * @param int $cohort_id WooCommerce product (cohort) ID.
	 * @return array<int> Unique prerequisite course post IDs.
	 */
	public static function get_cohort_prerequisites( int $cohort_id ): array {
		$cohort_courses = get_field( 'cohort_courses', $cohort_id );

		if ( ! is_array( $cohort_courses ) || empty( $cohort_courses ) ) {
			return [];
		}

		$prerequisites = [];

		foreach ( $cohort_courses as $course_id ) {
			$course_prereqs = get_field( 'course_prerequisites', $course_id );

			if ( is_array( $course_prereqs ) ) {
				foreach ( $course_prereqs as $prereq_id ) {
					$prerequisites[] = (int) $prereq_id;
				}
			}
		}

		// De-duplicate and exclude courses already in the cohort.
		$prerequisites = array_unique( $prerequisites );
		$prerequisites = array_values(
			array_diff( $prerequisites, array_map( 'intval', $cohort_courses ) )
		);

		return $prerequisites;
	}

	/**
	 * Get all lessons belonging to a cohort, via its linked courses.
	 *
	 * Walks the access chain one level: cohort → cohort_courses → each course's
	 * course_lessons. De-duplicated, order preserved (course order, then lesson
	 * order within each course). Used to scope the "current lesson" picker and
	 * by display surfaces that list a cohort's lessons.
	 *
	 * @since 0.7.0
	 *
	 * @param int $cohort_id WooCommerce product (cohort) ID.
	 * @return array<int> Unique lesson post IDs.
	 */
	public static function get_cohort_lessons( int $cohort_id ): array {
		$cohort_courses = get_field( 'cohort_courses', $cohort_id );

		if ( ! is_array( $cohort_courses ) || empty( $cohort_courses ) ) {
			return [];
		}

		$lessons = [];

		foreach ( $cohort_courses as $course_id ) {
			$course_lessons = get_field( 'course_lessons', $course_id );

			if ( is_array( $course_lessons ) ) {
				foreach ( $course_lessons as $lesson_id ) {
					$lessons[] = (int) $lesson_id;
				}
			}
		}

		return array_values( array_unique( $lessons ) );
	}

	// -------------------------------------------------------------------------
	// Access Chain Resolution
	// -------------------------------------------------------------------------

	/**
	 * Check if a user can access a specific course via cohort enrollment.
	 *
	 * Admin and editor roles bypass enrollment checks.
	 *
	 * @since 0.4.0
	 *
	 * @param int $user_id   WordPress user ID.
	 * @param int $course_id LeadersPath course post ID.
	 * @return bool True if the user can access the course.
	 */
	public static function can_user_access_course( int $user_id, int $course_id ): bool {
		// Admin/Editor bypass.
		if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'edit_others_posts' ) ) {
			return true;
		}

		$enrollments = self::get_user_enrollments( $user_id );
		if ( empty( $enrollments ) ) {
			return false;
		}

		// Find published cohort products that link to this course.
		$cohorts = get_posts( [
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'     => self::COHORT_META_KEY,
					'value'   => 'yes',
					'compare' => '=',
				],
				[
					'key'     => 'cohort_courses',
					'value'   => sprintf( '"%d"', $course_id ),
					'compare' => 'LIKE',
				],
			],
			'fields'         => 'ids',
		] );

		if ( empty( $cohorts ) ) {
			return false;
		}

		// Check if user is enrolled in any of these cohorts.
		foreach ( $cohorts as $cohort_id ) {
			if ( in_array( $cohort_id, $enrollments, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a user can access a specific lesson via cohort enrollment.
	 *
	 * Resolves the chain: Lesson → Course(s) → Cohort(s) → Enrollment.
	 *
	 * @since 0.4.0
	 *
	 * @param int $user_id   WordPress user ID.
	 * @param int $lesson_id LeadersPath lesson post ID.
	 * @return bool True if the user can access the lesson.
	 */
	public static function can_user_access_lesson( int $user_id, int $lesson_id ): bool {
		// Admin/Editor bypass.
		if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'edit_others_posts' ) ) {
			return true;
		}

		// Find courses that contain this lesson.
		$courses = get_posts( [
			'post_type'      => 'leaderspath_course',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'     => 'course_lessons',
					'value'   => sprintf( '"%d"', $lesson_id ),
					'compare' => 'LIKE',
				],
			],
			'fields'         => 'ids',
		] );

		if ( empty( $courses ) ) {
			return false;
		}

		// Check if user can access any of those courses.
		foreach ( $courses as $course_id ) {
			if ( self::can_user_access_course( $user_id, $course_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a user can access a specific activity via cohort enrollment.
	 *
	 * Resolves the chain: Activity → Lesson(s) → Course(s) → Cohort(s) → Enrollment.
	 *
	 * @since 0.4.0
	 *
	 * @param int $user_id     WordPress user ID.
	 * @param int $activity_id LeadersPath activity post ID.
	 * @return bool True if the user can access the activity.
	 */
	public static function can_user_access_activity( int $user_id, int $activity_id ): bool {
		// Admin/Editor bypass.
		if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'edit_others_posts' ) ) {
			return true;
		}

		// Find lessons that contain this activity.
		$lessons = get_posts( [
			'post_type'      => 'leaderspath_lesson',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'     => 'lesson_activities',
					'value'   => sprintf( '"%d"', $activity_id ),
					'compare' => 'LIKE',
				],
			],
			'fields'         => 'ids',
		] );

		if ( empty( $lessons ) ) {
			return false;
		}

		// Check if user can access any of those lessons.
		foreach ( $lessons as $lesson_id ) {
			if ( self::can_user_access_lesson( $user_id, $lesson_id ) ) {
				return true;
			}
		}

		return false;
	}
}
