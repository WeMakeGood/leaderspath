<?php
/**
 * Cohort-scoped lesson URLs.
 *
 * Registers /learn/{cohort-slug}/lesson/{lesson-slug}/ as an explicit,
 * cohort-aware entry point into a lesson. This coexists with the lesson
 * CPT's own canonical permalink (/lesson/{slug}/, used for admin editing
 * and Bricks builder preview) — it does not replace it.
 *
 * Why this exists: an Activity/Lesson is templated and reused across every
 * cohort that reaches it, but a cohort can now attach its own org-specific
 * Context Files (see ACF_Fields::filter_context_cohort_choices() and
 * Enrollment::can_user_access_context()). The lesson page pre-renders every
 * activity's chatbot config into the initial HTML in one server-side pass
 * (see docs/bricks-integration.md, "Lesson page: no AJAX for activity
 * content") — there is no per-activity request where a cohort ID could be
 * threaded in later. The cohort has to be known *before* that render pass
 * starts, which means it has to come from the URL, not from the chat
 * request or from enrollment inference (a facilitator or an admin
 * previewing/troubleshooting a specific cohort's lesson is not enrolled in
 * it at all).
 *
 * @package LeadersPath
 * @since   0.7.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Registers and resolves the /learn/{cohort}/lesson/{lesson}/ rewrite.
 *
 * @since 0.7.0
 */
class Cohort_Rewrite {

	/**
	 * Query var carrying the leaderspath_cohort instance's slug.
	 *
	 * Deliberately NOT named `leaderspath_cohort` — kept distinct from the
	 * CPT slug itself as a precaution, even though testing traced an
	 * earlier apparent failure here to an unrelated cause (a site-wide
	 * logged-out/"coming soon" interstitial intercepting the request before
	 * WordPress's own routing, not a query-var collision). Left renamed
	 * anyway rather than reverted, since colliding with the CPT slug has no
	 * upside and this was already verified working under the new name.
	 *
	 * @var string
	 */
	public const QUERY_VAR_COHORT = 'leaderspath_cohort_slug';

	/**
	 * Query var carrying the lesson's slug (distinct from WordPress's own
	 * `name`/`leaderspath_lesson` query var so this rule can't be confused
	 * with the CPT's default permalink parsing).
	 *
	 * @var string
	 */
	public const QUERY_VAR_LESSON = 'leaderspath_lesson_slug';

	/**
	 * Initialize the class.
	 *
	 * @since 0.7.0
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_rewrite_rule' ] );
		add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
		add_action( 'parse_request', [ $this, 'resolve_request' ] );
	}

	/**
	 * Register the /learn/{cohort}/lesson/{lesson}/ rewrite rule.
	 *
	 * Takes effect after the next rewrite flush (plugin activation already
	 * flushes; a site that had the plugin active before this rule existed
	 * needs one manual flush — e.g. re-saving Settings > Permalinks, or
	 * `wp rewrite flush`).
	 *
	 * @since 0.7.0
	 */
	public function register_rewrite_rule(): void {
		add_rewrite_rule(
			'^learn/([^/]+)/lesson/([^/]+)/?$',
			sprintf(
				'index.php?%s=$matches[1]&%s=$matches[2]',
				self::QUERY_VAR_COHORT,
				self::QUERY_VAR_LESSON
			),
			'top'
		);
	}

	/**
	 * Whitelist the two custom query vars.
	 *
	 * @since 0.7.0
	 *
	 * @param array<string> $vars Existing public query vars.
	 * @return array<string> Modified query vars.
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = self::QUERY_VAR_COHORT;
		$vars[] = self::QUERY_VAR_LESSON;
		return $vars;
	}

	/**
	 * Resolve the cohort + lesson slugs into the actual lesson query.
	 *
	 * Rewrites the request into a normal `leaderspath_lesson` single-post
	 * query (so the existing Bricks "Single Lesson" template renders exactly
	 * as it does today) while leaving the resolved cohort ID as a query var
	 * for the rest of the request lifecycle — Post_Id_Helper and the render
	 * pass read it from there, not by re-parsing the URL.
	 *
	 * Fails closed: an unresolvable cohort or lesson slug, or a lesson not
	 * actually reachable by that cohort (via Cohort → Course(s) → Lessons),
	 * 404s rather than falling through to a guessed/sample post. This is a
	 * confidentiality boundary (which org's context loads), not a display
	 * nicety — never guess its subject.
	 *
	 * @since 0.7.0
	 *
	 * @param \WP $wp The main WP request object.
	 */
	public function resolve_request( \WP $wp ): void {
		if ( empty( $wp->query_vars[ self::QUERY_VAR_COHORT ] ) || empty( $wp->query_vars[ self::QUERY_VAR_LESSON ] ) ) {
			return;
		}

		$cohort_slug = sanitize_title( (string) $wp->query_vars[ self::QUERY_VAR_COHORT ] );
		$lesson_slug = sanitize_title( (string) $wp->query_vars[ self::QUERY_VAR_LESSON ] );

		// Cohorts are their own CPT (a purchase-time instance), not the WC
		// product — see Post_Types::register_cohort(). No is_cohort_product()
		// check needed: every leaderspath_cohort post is a cohort by
		// construction, and access-closed/payment-status are checked
		// downstream via can_user_access_* rather than here.
		$cohort = get_page_by_path( $cohort_slug, OBJECT, 'leaderspath_cohort' );
		$lesson = get_page_by_path( $lesson_slug, OBJECT, 'leaderspath_lesson' );

		if ( ! $cohort || ! $lesson ) {
			$this->force_404( $wp );
			return;
		}

		// The lesson must actually belong to this cohort (Cohort → Course(s)
		// → Lessons) — a valid cohort and a valid lesson slug that have
		// nothing to do with each other is still a 404, not "close enough."
		if ( ! in_array( $lesson->ID, Enrollment::get_cohort_lessons( $cohort->ID ), true ) ) {
			$this->force_404( $wp );
			return;
		}

		// Overwrite the query vars WordPress will actually query on: a plain
		// leaderspath_lesson single-post lookup, identical to what
		// /lesson/{slug}/ produces, so the same Bricks template renders.
		$wp->query_vars['leaderspath_lesson'] = $lesson_slug;
		$wp->query_vars['post_type']          = 'leaderspath_lesson';
		unset( $wp->query_vars[ self::QUERY_VAR_LESSON ] );

		// Keep the resolved cohort ID (not the raw slug) as the query var for
		// the rest of the request — Post_Id_Helper and the cohort-context
		// resolution both read this rather than re-parsing the URL.
		$wp->query_vars[ self::QUERY_VAR_COHORT ] = $cohort->ID;
	}

	/**
	 * Force the current request to 404 without querying a fallback post.
	 *
	 * Setting `query_vars['error'] = '404'` on `parse_request` is the
	 * documented, sufficient mechanism — WP::main() picks it up when it runs
	 * the main query immediately afterward and produces a real 404
	 * (correct status code and template), no `set_404()`/`status_header()`
	 * call needed. An earlier version of this method added exactly that,
	 * on a deferred `wp` action — verified by testing (not just inspection)
	 * that it was actively wrong: by the time `wp` fires, WP_Query has
	 * already run against the emptied query_vars and can resolve to
	 * something else (the front page, in testing), giving a 200 with 404
	 * *content* — the wrong status code with the right-looking page, which
	 * is worse than an obviously broken page for anything checking status
	 * codes programmatically.
	 *
	 * @since 0.7.0
	 *
	 * @param \WP $wp The main WP request object.
	 */
	private function force_404( \WP $wp ): void {
		$wp->query_vars    = [ 'error' => '404' ];
		$wp->matched_query = 'error=404';
	}

	/**
	 * Get the cohort ID for the current request, if resolved via this rewrite.
	 *
	 * The one read path other code should use — never re-parse the URL.
	 * Returns 0 for any request that didn't come through
	 * /learn/{cohort}/lesson/{lesson}/, including the lesson's own canonical
	 * /lesson/{slug}/ permalink (admin editing, Bricks builder preview).
	 *
	 * @since 0.7.0
	 *
	 * @return int leaderspath_cohort post ID, or 0 if not a cohort-scoped request.
	 */
	public static function get_current_cohort_id(): int {
		$cohort_id = get_query_var( self::QUERY_VAR_COHORT );
		return $cohort_id ? (int) $cohort_id : 0;
	}
}
