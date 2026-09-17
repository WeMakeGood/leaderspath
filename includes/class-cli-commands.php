<?php
/**
 * WP-CLI commands for LeadersPath.
 *
 * Direct callers of the same operations any other caller uses — no
 * separate logic path. `wp leaderspath cohort create` calls
 * Enrollment::create_cohort() directly (the multi-caller operation
 * designed for exactly this — see docs/TASKS.md Phase 14, "cohort
 * creation as a first-class, multi-caller operation"). The generic
 * `post` commands wrap core `wp_insert_post()`/`wp_update_post()` plus
 * ACF's `update_field()`/`get_field()`, so content work (including test
 * data) never again needs a bespoke `wp eval-file` script.
 *
 * @package LeadersPath
 * @since   0.7.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

if ( ! defined( 'WP_CLI' ) || ! \WP_CLI ) {
	return;
}

/**
 * Manage LeadersPath content and cohorts from the command line.
 *
 * @since 0.7.0
 */
class CLI_Commands {

	/**
	 * LeadersPath CPT slugs the generic `post` commands operate on.
	 *
	 * Deliberately a fixed allowlist, not "any post type" — these commands
	 * read/write ACF fields registered for LeadersPath content specifically.
	 */
	private const MANAGED_POST_TYPES = [
		'leaderspath_activity',
		'leaderspath_lesson',
		'leaderspath_course',
		'leaderspath_context',
		'leaderspath_skill',
		'leaderspath_cohort',
	];

	/**
	 * Create a cohort instance.
	 *
	 * Thin CLI wrapper over Enrollment::create_cohort() — the exact same
	 * operation the WooCommerce order hook calls. No separate validation or
	 * creation logic lives here.
	 *
	 * ## OPTIONS
	 *
	 * [--offering=<id>]
	 * : Product (or other catalog offering) post ID to copy courses/seed
	 * requested-start-date from. Ignored if --course is also given.
	 * Optional — omit for a cohort with no catalog-offering origin.
	 *
	 * [--course=<id>]
	 * : A single leaderspath_course post ID to seed cohort_courses with
	 * directly — takes precedence over --offering's copy-from-product
	 * behavior. Matches the real org-purchase form's single-course-choice
	 * model (see docs/TASKS.md Phase 14).
	 *
	 * --owner=<user-id>
	 * : WordPress user ID with roster-management authority. Required unless
	 * --mixed is set and --facilitator is given (facilitator becomes owner).
	 *
	 * [--facilitator=<user-id>]
	 * : WordPress user ID of the assigned facilitator.
	 *
	 * [--org=<name>]
	 * : Organization name (or group description for a mixed cohort).
	 *
	 * [--seats=<n>]
	 * : Total seats. Default 1.
	 *
	 * [--start-date=<yyyy-mm-dd>]
	 * : Requested start date, seeds the cohort's actual start date.
	 *
	 * [--mixed]
	 * : Flag this as a mixed-organization cohort (owner defaults to
	 * facilitator, not enrolled as a participant). Note: create_cohort()
	 * always tags the new cohort with the 'organization' Cohort Type term
	 * regardless of this flag — a genuinely standing mixed cohort isn't
	 * created per-purchase; this flag only affects owner-resolution/
	 * enrollment behavior for an ad hoc CLI-created cohort.
	 *
	 * [--porcelain]
	 * : Output just the new cohort's post ID, for scripting.
	 *
	 * ## EXAMPLES
	 *
	 *     wp leaderspath cohort create --owner=1 --org="River Valley Food Bank" --seats=6
	 *     wp leaderspath cohort create --course=326 --owner=1 --facilitator=1 --org="Second Harvest" --seats=5 --start-date=2026-11-02
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative (flag) arguments.
	 */
	public function cohort_create( array $args, array $assoc_args ): void {
		$assoc_args = wp_parse_args( $assoc_args, [
			'offering'    => 0,
			'course'      => 0,
			'owner'       => 0,
			'facilitator' => 0,
			'org'         => '',
			'seats'       => 1,
			'start-date'  => '',
		] );

		$result = Enrollment::create_cohort( [
			'offering_id'          => (int) $assoc_args['offering'],
			'course_id'            => (int) $assoc_args['course'],
			'owner_user_id'        => (int) $assoc_args['owner'],
			'facilitator_user_id'  => (int) $assoc_args['facilitator'],
			'org_name'             => (string) $assoc_args['org'],
			'seats'                => (int) $assoc_args['seats'],
			'requested_start_date' => (string) $assoc_args['start-date'],
			'is_mixed'             => isset( $assoc_args['mixed'] ),
			'source'               => 'wp_cli',
		] );

		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
			return;
		}

		if ( isset( $assoc_args['porcelain'] ) ) {
			\WP_CLI::line( (string) $result );
			return;
		}

		\WP_CLI::success( "Created cohort #{$result}: " . get_the_title( $result ) );
	}

	/**
	 * Invite someone to a cohort's roster.
	 *
	 * Thin wrapper over Enrollment::invite_to_cohort() — sends the real
	 * password-reset email. Not a dry-run command.
	 *
	 * ## OPTIONS
	 *
	 * <cohort-id>
	 * : The leaderspath_cohort post ID.
	 *
	 * <email>
	 * : Email address to invite.
	 *
	 * @when after_wp_load
	 *
	 * @param array $args Positional arguments: [cohort-id, email].
	 */
	public function cohort_invite( array $args ): void {
		[ $cohort_id, $email ] = $args;

		$result = Enrollment::invite_to_cohort( (int) $cohort_id, $email );

		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
			return;
		}

		\WP_CLI::success( "Invited {$email} to cohort #{$cohort_id} (user #{$result})." );
	}

	/**
	 * Create a LeadersPath post with content and ACF fields in one call.
	 *
	 * ## OPTIONS
	 *
	 * <post-type>
	 * : One of: activity, lesson, course, context, skill, cohort (the
	 * `leaderspath_` prefix is added automatically).
	 *
	 * --title=<title>
	 * : Post title.
	 *
	 * [--content=<content>]
	 * : Post content (post_content) — HTML allowed.
	 *
	 * [--excerpt=<excerpt>]
	 * : Post excerpt (post_excerpt).
	 *
	 * [--status=<status>]
	 * : Post status. Default 'publish'.
	 *
	 * [--field=<field-assignment>]
	 * : Set an ACF field. Repeatable — pass multiple --field flags for
	 * multiple fields. Array-type fields (relationship, repeater-of-IDs)
	 * take a comma-separated list of post IDs: --field=course_lessons=12,13,14
	 *
	 * [--porcelain]
	 * : Output just the new post ID, for scripting.
	 *
	 * ## EXAMPLES
	 *
	 *     wp leaderspath post create course --title="AI Fluency Foundations" --content="..." --excerpt="..." --field=course_lessons=318,319,320
	 *     wp leaderspath post create lesson --title="Session 1" --field=lesson_total_duration="90 minutes"
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments: [post-type].
	 * @param array $assoc_args Associative (flag) arguments.
	 */
	public function post_create( array $args, array $assoc_args ): void {
		$post_type = $this->resolve_post_type( $args[0] ?? '' );

		if ( ! $post_type ) {
			return;
		}

		if ( empty( $assoc_args['title'] ) ) {
			\WP_CLI::error( 'A --title is required.' );
			return;
		}

		$post_id = wp_insert_post( [
			'post_type'    => $post_type,
			'post_title'   => (string) $assoc_args['title'],
			'post_content' => (string) ( $assoc_args['content'] ?? '' ),
			'post_excerpt' => (string) ( $assoc_args['excerpt'] ?? '' ),
			'post_status'  => (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'status', 'publish' ),
		], true );

		if ( is_wp_error( $post_id ) ) {
			\WP_CLI::error( $post_id->get_error_message() );
			return;
		}

		$this->apply_field_flags( $post_id, $assoc_args );

		if ( isset( $assoc_args['porcelain'] ) ) {
			\WP_CLI::line( (string) $post_id );
			return;
		}

		\WP_CLI::success( "Created {$post_type} #{$post_id}: " . get_the_title( $post_id ) );
	}

	/**
	 * Update an existing LeadersPath post's content and/or ACF fields.
	 *
	 * ## OPTIONS
	 *
	 * <post-id>
	 * : The post ID to update. Must be a LeadersPath-managed post type.
	 *
	 * [--title=<title>]
	 * : New post title.
	 *
	 * [--content=<content>]
	 * : New post content.
	 *
	 * [--excerpt=<excerpt>]
	 * : New post excerpt.
	 *
	 * [--field=<field-assignment>]
	 * : Set an ACF field. Repeatable. See `post create` for array syntax.
	 *
	 * ## EXAMPLES
	 *
	 *     wp leaderspath post update 326 --excerpt="A six-session curriculum for one organization's team."
	 *     wp leaderspath post update 318 --field=lesson_total_duration="90 minutes"
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments: [post-id].
	 * @param array $assoc_args Associative (flag) arguments.
	 */
	public function post_update( array $args, array $assoc_args ): void {
		$post_id = (int) $args[0];

		if ( ! $this->assert_managed_post( $post_id ) ) {
			return;
		}

		$update = [ 'ID' => $post_id ];

		foreach ( [ 'title' => 'post_title', 'content' => 'post_content', 'excerpt' => 'post_excerpt' ] as $flag => $column ) {
			if ( isset( $assoc_args[ $flag ] ) ) {
				$update[ $column ] = (string) $assoc_args[ $flag ];
			}
		}

		if ( count( $update ) > 1 ) {
			$result = wp_update_post( $update, true );
			if ( is_wp_error( $result ) ) {
				\WP_CLI::error( $result->get_error_message() );
				return;
			}
		}

		$this->apply_field_flags( $post_id, $assoc_args );

		\WP_CLI::success( "Updated #{$post_id}: " . get_the_title( $post_id ) );
	}

	/**
	 * Show a LeadersPath post's content and every registered ACF field.
	 *
	 * The audit tool this command surface exists to replace one-off
	 * `wp eval` scripts for — "what's actually filled in on this post."
	 *
	 * ## OPTIONS
	 *
	 * <post-id>
	 * : The post ID to inspect. Must be a LeadersPath-managed post type.
	 *
	 * ## EXAMPLES
	 *
	 *     wp leaderspath post get 326
	 *
	 * @when after_wp_load
	 *
	 * @param array $args Positional arguments: [post-id].
	 */
	public function post_get( array $args ): void {
		$post_id = (int) $args[0];

		if ( ! $this->assert_managed_post( $post_id ) ) {
			return;
		}

		$post = get_post( $post_id );

		\WP_CLI::line( \WP_CLI::colorize( "%Y{$post->post_title}%n (#{$post_id}, {$post->post_type}, {$post->post_status})" ) );
		\WP_CLI::line( '' );
		\WP_CLI::line( 'post_content: ' . ( $post->post_content ?: '(empty)' ) );
		\WP_CLI::line( 'post_excerpt: ' . ( $post->post_excerpt ?: '(empty)' ) );
		\WP_CLI::line( '' );

		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return;
		}

		$groups = acf_get_field_groups( [ 'post_type' => $post->post_type ] );

		foreach ( $groups as $group ) {
			\WP_CLI::line( \WP_CLI::colorize( "%B{$group['title']}%n" ) );

			foreach ( acf_get_fields( $group ) as $field ) {
				$value = get_field( $field['name'], $post_id );
				\WP_CLI::line( '  ' . $this->format_field_line( $field, $value ) );
			}

			\WP_CLI::line( '' );
		}
	}

	/**
	 * List LeadersPath posts of a given type with their key relationship counts.
	 *
	 * ## OPTIONS
	 *
	 * <post-type>
	 * : One of: activity, lesson, course, context, skill, cohort.
	 *
	 * [--status=<status>]
	 * : Filter by post status. Default 'any'.
	 *
	 * ## EXAMPLES
	 *
	 *     wp leaderspath post list course
	 *     wp leaderspath post list cohort --status=publish
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments: [post-type].
	 * @param array $assoc_args Associative (flag) arguments.
	 */
	public function post_list( array $args, array $assoc_args ): void {
		$post_type = $this->resolve_post_type( $args[0] ?? '' );

		if ( ! $post_type ) {
			return;
		}

		$posts = get_posts( [
			'post_type'      => $post_type,
			'post_status'    => \WP_CLI\Utils\get_flag_value( $assoc_args, 'status', 'any' ),
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		] );

		$rows = [];
		foreach ( $posts as $post ) {
			$rows[] = [
				'ID'     => $post->ID,
				'title'  => $post->post_title,
				'status' => $post->post_status,
			];
		}

		\WP_CLI\Utils\format_items( 'table', $rows, [ 'ID', 'title', 'status' ] );
	}

	// -------------------------------------------------------------------------
	// Internal helpers
	// -------------------------------------------------------------------------

	/**
	 * Resolve a short post-type name (e.g. "course") to the full managed
	 * CPT slug (e.g. "leaderspath_course"), erroring if it isn't managed.
	 *
	 * @param string $short_name Short post-type name, with or without the
	 *                           leaderspath_ prefix.
	 * @return string The full post type slug, or '' on error (already
	 *                reported via WP_CLI::error()).
	 */
	private function resolve_post_type( string $short_name ): string {
		$full = 0 === strpos( $short_name, 'leaderspath_' ) ? $short_name : "leaderspath_{$short_name}";

		if ( ! in_array( $full, self::MANAGED_POST_TYPES, true ) ) {
			\WP_CLI::error( sprintf(
				'Unknown post type "%s". Expected one of: %s',
				$short_name,
				implode( ', ', array_map( fn( $pt ) => str_replace( 'leaderspath_', '', $pt ), self::MANAGED_POST_TYPES ) )
			) );
			return '';
		}

		return $full;
	}

	/**
	 * Confirm a post ID exists and is a LeadersPath-managed post type,
	 * erroring otherwise.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if valid.
	 */
	private function assert_managed_post( int $post_id ): bool {
		$post_type = get_post_type( $post_id );

		if ( ! $post_type || ! in_array( $post_type, self::MANAGED_POST_TYPES, true ) ) {
			\WP_CLI::error( "Post #{$post_id} is not a LeadersPath-managed post." );
			return false;
		}

		return true;
	}

	/**
	 * Apply every repeated --field=name=value flag to a post via ACF.
	 *
	 * Array-type values (comma-separated in the flag) are split and cast to
	 * ints, matching every LeadersPath relationship field's `return_format
	 * => 'id'` convention — this is a CLI-ergonomics assumption, not a
	 * general-purpose ACF value parser, and won't handle a repeater's
	 * nested sub-fields (e.g. lesson_objectives, lesson_references) in one
	 * flag; those still need a script or the admin UI.
	 *
	 * @param int   $post_id    Post to update.
	 * @param array $assoc_args CLI associative arguments, possibly containing
	 *                          one or more 'field' entries (WP-CLI collects
	 *                          repeated flags of the same name into an array).
	 */
	private function apply_field_flags( int $post_id, array $assoc_args ): void {
		if ( empty( $assoc_args['field'] ) ) {
			return;
		}

		$post_type       = (string) get_post_type( $post_id );
		$registered      = $this->get_registered_field_names( $post_type );

		// WP-CLI gives a single string for one --field=, an array for repeats.
		$field_flags = is_array( $assoc_args['field'] ) ? $assoc_args['field'] : [ $assoc_args['field'] ];

		foreach ( $field_flags as $flag ) {
			if ( ! str_contains( $flag, '=' ) ) {
				\WP_CLI::warning( "Skipping malformed --field value \"{$flag}\" (expected name=value)." );
				continue;
			}

			[ $name, $value ] = explode( '=', $flag, 2 );

			// Validate against the post type's actual registered ACF
			// fields before writing — update_field() has no such check
			// itself and will happily write raw postmeta for a field name
			// that isn't registered on this post type at all, which
			// silently produces garbage meta rather than an error. Found
			// by testing (not by inspection): a plain typo/wrong-CPT
			// --field name succeeds with no feedback otherwise.
			if ( ! in_array( $name, $registered, true ) ) {
				\WP_CLI::warning( "Skipping --field=\"{$name}\" — not a registered ACF field on {$post_type}. Known fields: " . implode( ', ', $registered ) );
				continue;
			}

			// Comma-separated → array of ints (relationship/post_object ID
			// lists). No comma → pass the scalar through as-is.
			if ( str_contains( $value, ',' ) ) {
				$value = array_map( 'intval', array_map( 'trim', explode( ',', $value ) ) );
			}

			update_field( $name, $value, $post_id );
		}
	}

	/**
	 * Get every ACF field name registered for a post type, across all its
	 * field groups.
	 *
	 * @param string $post_type CPT slug.
	 * @return array<string> Field names.
	 */
	private function get_registered_field_names( string $post_type ): array {
		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return [];
		}

		$names = [];

		foreach ( acf_get_field_groups( [ 'post_type' => $post_type ] ) as $group ) {
			foreach ( acf_get_fields( $group ) as $field ) {
				$names[] = $field['name'];
			}
		}

		return $names;
	}

	/**
	 * Format one field's value for `post get` output.
	 *
	 * @param array $field ACF field definition.
	 * @param mixed $value Current field value.
	 * @return string Formatted "name: value" line.
	 */
	private function format_field_line( array $field, $value ): string {
		if ( empty( $value ) && '0' !== $value && 0 !== $value ) {
			return "{$field['name']}: (empty)";
		}

		if ( is_array( $value ) ) {
			// Relationship/post_object arrays of IDs, or repeater rows.
			$is_id_list = array_reduce( $value, fn( $carry, $v ) => $carry && ( is_int( $v ) || is_numeric( $v ) ), true );

			if ( $is_id_list ) {
				$titles = array_map( fn( $id ) => get_the_title( (int) $id ) ?: "#{$id}", $value );
				return "{$field['name']}: " . implode( ', ', $titles ) . ' (' . count( $value ) . ')';
			}

			return "{$field['name']}: " . count( $value ) . ' item(s) — use the admin UI to inspect repeater rows';
		}

		$str = is_bool( $value ) ? ( $value ? 'true' : 'false' ) : (string) $value;
		$str = wp_strip_all_tags( $str );

		if ( strlen( $str ) > 100 ) {
			$str = substr( $str, 0, 97 ) . '...';
		}

		return "{$field['name']}: {$str}";
	}
}

\WP_CLI::add_command( 'leaderspath cohort create', [ new CLI_Commands(), 'cohort_create' ] );
\WP_CLI::add_command( 'leaderspath cohort invite', [ new CLI_Commands(), 'cohort_invite' ] );
\WP_CLI::add_command( 'leaderspath post create', [ new CLI_Commands(), 'post_create' ] );
\WP_CLI::add_command( 'leaderspath post update', [ new CLI_Commands(), 'post_update' ] );
\WP_CLI::add_command( 'leaderspath post get', [ new CLI_Commands(), 'post_get' ] );
\WP_CLI::add_command( 'leaderspath post list', [ new CLI_Commands(), 'post_list' ] );
