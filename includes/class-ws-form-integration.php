<?php
/**
 * WS Form cohort-purchase integration.
 *
 * WooCommerce was dropped as the cohort-purchase mechanism in favor of a
 * WS Form Pro + Stripe Elements form (see docs/TASKS.md Phase 14,
 * "WooCommerce dropped as the purchase mechanism"). This class is the
 * plugin-owned handler for WS Form's "Run WordPress Hook" action, which
 * calls `Enrollment::create_cohort()` directly — the same canonical
 * cohort-creation operation every other caller uses (WP-CLI, the former WC
 * order hook). WS Form is one caller among others, not a redesign of
 * `Enrollment` itself.
 *
 * Fields are resolved by **label**, not hardcoded field ID, via WS Form's
 * own first-party `wsf_field_get_objects()` — a form can be edited (fields
 * reordered, sections added) without breaking this integration, so long as
 * the expected labels stay the same. See docs/TASKS.md Phase 14,
 * "Integration mechanism resolved" for the full research trail.
 *
 * @package LeadersPath
 * @since   0.7.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

if ( ! function_exists( 'wsf_field_get_objects' ) ) {
	// WS Form Pro isn't active — this integration is a no-op, not a fatal.
	return;
}

/**
 * Handles the WS Form "Run WordPress Hook" action for cohort purchases.
 *
 * @since 0.7.0
 */
class WS_Form_Integration {

	/**
	 * Hook tag the plugin listens for.
	 *
	 * This is the plugin's own choice, per the same "registration lives in
	 * plugin code" discipline as the Bricks `lp_*` integration
	 * (docs/bricks-integration.md) — the WS Form form's "Run WordPress
	 * Hook" action must be configured with this exact tag to reach this
	 * handler. Configured today on form 7 ("LeadersPath Cohort Purchase")
	 * as a literal placeholder ("name_of_hook") awaiting this value.
	 *
	 * @var string
	 */
	public const HOOK_TAG = 'leaderspath_ws_form_cohort_submitted';

	/**
	 * Field labels this handler expects on the org-cohort purchase form.
	 *
	 * A label-lookup miss (form edited, label typo'd) fails loudly — see
	 * handle_submission() — rather than silently reading nothing.
	 */
	private const LABEL_FIRST_NAME  = 'First Name';
	private const LABEL_LAST_NAME   = 'Last Name';
	private const LABEL_EMAIL       = 'Email';
	private const LABEL_ORG_NAME    = 'Organization Name';
	private const LABEL_PACKAGE     = 'Cohort Package';
	private const LABEL_COURSE      = 'Course';
	private const LABEL_START_DATE  = 'Requested Start Date';
	private const LABEL_PAYMENT     = 'Payment Method';

	/**
	 * Payment Method option values, as configured on the real form.
	 *
	 * @see docs/TASKS.md Phase 14 — confirmed exact strings from form 7's
	 * own field configuration, not assumed.
	 */
	private const PAYMENT_CREDIT_CARD = 'Credit or Debit Card';
	private const PAYMENT_PURCHASE_ORDER = 'Purchase Order (invoiced separately)';

	/**
	 * Initialize the class.
	 *
	 * @since 0.7.0
	 */
	public function __construct() {
		add_action( self::HOOK_TAG, [ $this, 'handle_submission' ], 10, 2 );
	}

	/**
	 * Handle a WS Form cohort-purchase submission.
	 *
	 * Reads the fields this handler expects by label, resolves or creates
	 * the owner's WP account, decides `pending_payment` vs. `paid` from the
	 * selected payment method (never from Stripe webhook/async state — see
	 * docs/TASKS.md Phase 14, "Async-payment caveat resolved": the
	 * Purchase Order path never triggers a Stripe charge through this form
	 * at all, so there is nothing asynchronous to wait on), and calls
	 * `Enrollment::create_cohort()`.
	 *
	 * @since 0.7.0
	 *
	 * @param object $form   WS_Form_Form object for the submitted form.
	 * @param object $submit WS_Form_Submit object for this submission.
	 */
	public function handle_submission( $form, $submit ): void {
		try {
			// "Cohort Package" is a price_select field configured to submit
			// its Seats column value directly (a real number: "3", "5",
			// "7"), not the option label — confirmed from the field's own
			// `data_grid_select_price` config (`select_price_field_value`
			// points at the Seats column). The "More than 7 seats —
			// Contact Us" row has a deliberately empty Seats value, which
			// is how that row is detected here — not by string-matching
			// its label — since the field no longer submits a label at all.
			$seats = (int) $this->get_field_value( $form, $submit, self::LABEL_PACKAGE );

			// Contact-us lead, not a purchase — do nothing on the
			// Enrollment side. Send Email/FluentCRM (already configured on
			// the form) already handle notifying the team and capturing
			// the lead; there is no seat count to create a cohort with.
			if ( $seats < 1 ) {
				return;
			}

			$email = sanitize_email( $this->get_field_value( $form, $submit, self::LABEL_EMAIL ) );

			if ( ! is_email( $email ) ) {
				$this->log_error( 'Submission had no valid Email value — cannot resolve an owner account.' );
				return;
			}

			$owner_id = Enrollment::find_or_create_user_by_email( $email );

			if ( is_wp_error( $owner_id ) ) {
				$this->log_error( 'Could not create/find owner account: ' . $owner_id->get_error_message() );
				return;
			}

			$org_name = $this->get_field_value( $form, $submit, self::LABEL_ORG_NAME );

			// First/Last Name aren't passed to create_cohort() directly —
			// Enrollment has no field for a person's name (the owner's
			// display name comes from wp_insert_user()'s own defaults, or
			// can be set here if that's ever needed) — but resolving them
			// confirms the labels still exist and match the form, giving
			// an early, loud failure if the form was edited incompatibly
			// rather than a silent partial submission.
			$this->get_field_value( $form, $submit, self::LABEL_FIRST_NAME );
			$this->get_field_value( $form, $submit, self::LABEL_LAST_NAME );

			// "Course" is a select field submitting a leaderspath_course
			// post ID directly (e.g. "326") — the org-cohort form's real
			// curriculum-selection mechanism, confirmed against cohort #346.
			$course_id = (int) $this->get_field_value( $form, $submit, self::LABEL_COURSE );

			$start_date = $this->get_field_value( $form, $submit, self::LABEL_START_DATE );

			$payment_method = $this->get_field_value( $form, $submit, self::LABEL_PAYMENT );
			$payment_status = self::PAYMENT_CREDIT_CARD === $payment_method ? 'paid' : 'pending_payment';

			$result = Enrollment::create_cohort( [
				'owner_user_id'        => $owner_id,
				'org_name'             => $org_name,
				'seats'                => $seats,
				'course_id'            => $course_id,
				'requested_start_date' => $start_date,
				'is_mixed'             => false, // Form 7 is the org-cohort form only — see docs/TASKS.md Phase 14.
				'source_record_id'     => $submit->id,
				'source_url'           => $this->get_submission_edit_url( $form, $submit ),
				'source'               => 'ws_form',
			] );

			if ( is_wp_error( $result ) ) {
				$this->log_error( 'Enrollment::create_cohort() failed: ' . $result->get_error_message() );
				return;
			}

			Enrollment::set_cohort_payment_status( $result, $payment_status );

		} catch ( \Exception $e ) {
			// wsf_field_get_objects()/wsf_form_check() throw on an invalid
			// form/submit object or a label that no longer matches any
			// field — fail loudly (logged), never silently.
			$this->log_error( $e->getMessage() );
		}
	}

	/**
	 * Build a direct admin link to a WS Form submission.
	 *
	 * URL shape confirmed from WS Form Pro's own source
	 * (`WS_Form_Common`'s submission-notification email builder):
	 * `admin.php?page=ws-form-submit&id={form_id}#{submit_id}` — form ID as
	 * a query arg, submission ID as a URL fragment, not a second query arg.
	 *
	 * @since 0.7.0
	 *
	 * @param object $form   WS_Form_Form object.
	 * @param object $submit WS_Form_Submit object.
	 * @return string Admin URL to the submission's entry screen.
	 */
	private function get_submission_edit_url( $form, $submit ): string {
		return get_admin_url( null, 'admin.php?page=ws-form-submit&id=' . $form->id . '#' . $submit->id );
	}

	/**
	 * Get a submitted field's value by its label.
	 *
	 * Throws (via WS Form's own `wsf_field_get_objects()`) if no field with
	 * this label exists on the form — deliberate: a form edit that removes
	 * or renames an expected field should surface as a loud, logged
	 * failure, not a silently-missing value flowing into `create_cohort()`.
	 *
	 * **Choice-type fields (select, price_select, checkbox, etc.) submit as
	 * a PHP array of selected label(s), not a scalar** — confirmed from
	 * WS Form Pro's own `WS_Form_Submit::db_get_submit_meta()`, which
	 * `maybe_unserialize()`s the stored value before wrapping it in
	 * `['value' => $value, ...]`; `wsf_submit_get_value()` only unwraps that
	 * outer `value` key, not the choice array itself. This was the actual
	 * cause of cohort #345's wrong seat count — the array flowed through
	 * a `(string)` cast as the literal string "Array" rather than "3
	 * seats". Normalized here (first element) so every caller of this
	 * method gets a plain scalar, matching genuinely single-value fields
	 * (text, datetime) which never had this problem.
	 *
	 * @since 0.7.0
	 *
	 * @param object $form  WS_Form_Form object.
	 * @param object $submit WS_Form_Submit object.
	 * @param string $label  Exact field label to look up.
	 * @return string Submitted value, or '' if the field exists but wasn't filled in.
	 *
	 * @throws \Exception If no field with this label exists on the form.
	 */
	private function get_field_value( $form, $submit, string $label ): string {
		$fields = wsf_field_get_objects( $form, false, $label );

		if ( empty( $fields ) || ! is_array( $fields ) || ! isset( $fields[0]->id ) ) {
			throw new \Exception( sprintf( 'No field labeled "%s" found on this form.', $label ) );
		}

		$value = wsf_submit_get_value( $submit, sprintf( 'field_%d', $fields[0]->id ) );

		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		return (string) $value;
	}

	/**
	 * Log an integration failure.
	 *
	 * @since 0.7.0
	 *
	 * @param string $message Error message.
	 */
	private function log_error( string $message ): void {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Deliberate: surfaces in the site's PHP error log for an admin to notice a broken cohort-purchase integration.
		error_log( '[LeadersPath WS Form] ' . $message );
	}
}
