<?php
/**
 * WooCommerce integration for LeadersPath.
 *
 * Handles the Cohort *product* — the reusable catalog offering (e.g. "Core
 * Cohort Package") sellable to any number of organizations — and translates
 * WooCommerce order events into calls to Enrollment::create_cohort() /
 * Enrollment::set_cohort_payment_status(). This class is one *caller* of
 * that operation, not its implementation — see class-enrollment.php and
 * docs/TASKS.md Phase 14 ("cohort creation as a first-class, multi-caller
 * operation") for the full rationale. A cohort *instance* (facilitator,
 * dates, roster, payment status) lives on the `leaderspath_cohort` CPT, not
 * here — see Post_Types::register_cohort().
 *
 * Cohort products are Simple products with a "_cohort" meta flag, following
 * the same pattern as WooCommerce's built-in "Virtual" and "Downloadable"
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

		// Cohort instance creation happens on real commitment (e.g. an
		// on-hold PO/invoice order), not on completion — see docs/TASKS.md
		// Phase 14, "Creation trigger and payment status." `on-hold` is
		// WooCommerce's own native "awaiting payment confirmation" status,
		// used by manual/PO/invoice payment methods; `processing` covers
		// gateways that skip on-hold entirely for immediate payment.
		add_action( 'woocommerce_order_status_on_hold', [ $this, 'handle_order_commitment' ] );
		add_action( 'woocommerce_order_status_processing', [ $this, 'handle_order_commitment' ] );

		// Ongoing payment-status sync as the order progresses — the
		// repeatable half of the two-event boundary. Deliberately does NOT
		// touch access/enrollment; see handle_order_refunded()'s docblock.
		add_action( 'woocommerce_order_status_changed', [ $this, 'handle_order_status_changed' ], 10, 4 );
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

	// -------------------------------------------------------------------------
	// Order → Cohort Instance Translation
	// -------------------------------------------------------------------------

	/**
	 * Handle an order reflecting real commitment — create the cohort instance.
	 *
	 * Fires on `on-hold` (WooCommerce's native "awaiting payment
	 * confirmation" status — how manual/PO/invoice payment methods hold an
	 * order) or `processing` (gateways that clear payment immediately, e.g.
	 * card/Stripe). Either way, this is "the org committed to this," not
	 * "payment cleared" — see docs/TASKS.md Phase 14, "Creation trigger and
	 * payment status." One cohort-package per order is enforced at the
	 * product level (WooCommerce's native "Sold Individually" checkbox — see
	 * docs/TASKS.md, no plugin code involved), so this only ever expects one
	 * cohort-package line item.
	 *
	 * Idempotent against being fired twice for the same order (both hooks
	 * could plausibly fire in sequence for one order in some gateway flows):
	 * checks whether a cohort already carries this order ID before creating
	 * another.
	 *
	 * @since 0.7.0
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function handle_order_commitment( int $order_id ): void {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		if ( self::get_cohort_for_order( $order_id ) ) {
			return; // Already created for this order.
		}

		$user_id = $order->get_customer_id();
		if ( 0 === $user_id ) {
			return; // Guest checkout — no owner to assign.
		}

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();

			if ( ! $product || ! self::is_cohort_product( $product->get_id() ) ) {
				continue;
			}

			// TODO: 'cohort_is_mixed_offering' doesn't exist yet — the
			// mixed-organization product tier isn't built (see
			// docs/TASKS.md Phase 14, side-benefit note). Reads as
			// false/single-org until that field is registered.
			$is_mixed = (bool) get_field( 'cohort_is_mixed_offering', $product->get_id() );

			Enrollment::create_cohort( [
				'offering_id'          => $product->get_id(),
				'owner_user_id'        => $is_mixed ? 0 : $user_id,
				'is_mixed'             => $is_mixed,
				'org_name'             => $order->get_billing_company() ?: $order->get_formatted_billing_full_name(),
				'seats'                => $item->get_quantity() > 1 ? 1 : $this->get_variation_seats( $product ),
				'requested_start_date' => get_field( 'cohort_requested_start_date', $product->get_id() ) ?: '',
				'source_record_id'     => $order_id,
				'source_url'           => $order->get_edit_order_url(),
				'source'               => 'woocommerce',
			] );

			// One cohort-package per order is enforced at checkout (product
			// level), so there's at most one relevant line item — stop here
			// rather than looping further.
			return;
		}
	}

	/**
	 * Sync a cohort's payment status as its order progresses.
	 *
	 * The ongoing half of the two-event boundary — deliberately separate
	 * from `handle_order_commitment()`, which only ever fires once per
	 * order. This fires on every status transition and just forwards the
	 * new status to whichever cohort was created from this order, via
	 * `Enrollment::set_cohort_payment_status()`. Does not create a cohort if
	 * one doesn't exist yet (a status change on an order with no committed
	 * cohort — e.g. still `pending` — has nothing to update).
	 *
	 * Deliberately does **not** call unenroll/access-closed logic on refund
	 * or cancellation — see docs/TASKS.md Phase 14, "Refund/cancellation
	 * must NOT auto-revoke access." That's a manual `cohort_access_closed`
	 * toggle, a separate admin/store-manager action, not an automatic side
	 * effect of a billing status change.
	 *
	 * @since 0.7.0
	 *
	 * @param int    $order_id   WooCommerce order ID.
	 * @param string $old_status Previous order status (no `wc-` prefix).
	 * @param string $new_status New order status (no `wc-` prefix).
	 * @param \WC_Order $order   The order object.
	 */
	public function handle_order_status_changed( int $order_id, string $old_status, string $new_status, \WC_Order $order ): void {
		$cohort_id = self::get_cohort_for_order( $order_id );

		if ( ! $cohort_id ) {
			return;
		}

		$payment_status = 'completed' === $new_status ? 'paid' : 'pending_payment';

		Enrollment::set_cohort_payment_status( $cohort_id, $payment_status );
	}

	/**
	 * Find the cohort instance created from a given order, if any.
	 *
	 * @since 0.7.0
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return int Cohort post ID, or 0 if none.
	 */
	private static function get_cohort_for_order( int $order_id ): int {
		$cohorts = get_posts( [
			'post_type'      => 'leaderspath_cohort',
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'meta_query'     => [
				[
					'key'   => 'cohort_source_record_id',
					'value' => $order_id,
				],
			],
			'fields'         => 'ids',
		] );

		return ! empty( $cohorts ) ? (int) $cohorts[0] : 0;
	}

	/**
	 * Get the seat count for a purchased product/variation.
	 *
	 * TODO: 'cohort_variation_seats' doesn't exist yet — the Variable
	 * Product/team-size migration isn't built (see docs/TASKS.md Phase 14,
	 * "Cohort seat count is a WC Variable Product attribute"). This method
	 * is the intended read point once it is: the team-size attribute's seat
	 * count, per purchased variation. Falls back to 1 for a plain Simple
	 * product with no variation, so cohort creation never fails outright for
	 * lack of a seat-count field existing yet — every cohort created before
	 * that migration lands will show 1 seat, which is wrong but not fatal.
	 *
	 * @since 0.7.0
	 *
	 * @param \WC_Product $product The purchased product (or variation).
	 * @return int Seat count, minimum 1.
	 */
	private function get_variation_seats( \WC_Product $product ): int {
		$seats = (int) get_field( 'cohort_variation_seats', $product->get_id() );

		return $seats > 0 ? $seats : 1;
	}
}
