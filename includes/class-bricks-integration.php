<?php
/**
 * Bricks Builder integration.
 *
 * Bricks owns all LeadersPath display; the plugin exposes its logic for Bricks
 * to call at render time. Bricks calls PHP two ways — the `{echo:}` dynamic tag
 * (by global function name) and Element Conditions (via filters) — neither of
 * which can reference a namespaced static method. This class therefore:
 *
 *   1. Registers a thin layer of global `lp_*` functions (defined at the bottom
 *      of this file) that wrap the plugin's namespaced logic.
 *   2. Whitelists those functions for the `{echo:}` tag.
 *   3. Registers a custom "user is enrolled" Element Condition.
 *
 * See docs/bricks-integration.md for the full contract and the two-level
 * security gate (Bricks > Settings > Custom code must enable code execution
 * before `{echo:}` runs at all).
 *
 * @package LeadersPath\Includes
 * @since   0.7.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

class Bricks_Integration {

	/**
	 * Condition key for the "user is enrolled in this cohort" Element Condition.
	 */
	private const COND_ENROLLED = 'lp_enrolled_current_cohort';

	public function __construct() {
		// {echo:} function whitelist.
		add_filter( 'bricks/code/echo_function_names', [ $this, 'whitelist_echo_functions' ] );

		// Custom Element Condition: register the group + option, resolve the result.
		add_filter( 'bricks/conditions/groups', [ $this, 'register_condition_group' ] );
		add_filter( 'bricks/conditions/options', [ $this, 'register_condition_options' ] );
		add_filter( 'bricks/conditions/result', [ $this, 'resolve_condition' ], 10, 3 );
	}

	/**
	 * Whitelist the plugin's `{echo:}`-callable functions.
	 *
	 * An explicit list (not a `@^lp_` pattern) keeps the callable surface
	 * enumerable and reviewable. Bricks passes either the current callback name
	 * (first pass) or an accumulating array; merge defensively.
	 *
	 * @param mixed $allowed Current allowed value (array|bool|string).
	 * @return array Allowed function names.
	 */
	public function whitelist_echo_functions( $allowed ): array {
		$lp = [
			'lp_is_last_activity',
			'lp_enrolled_cohorts',
		];

		return is_array( $allowed ) ? array_values( array_unique( array_merge( $allowed, $lp ) ) ) : $lp;
	}

	/**
	 * Add a "LeadersPath" group to the Element Conditions UI.
	 *
	 * @param array $groups Existing condition groups.
	 * @return array
	 */
	public function register_condition_group( array $groups ): array {
		$groups[] = [
			'name'  => 'leaderspath',
			'label' => 'LeadersPath',
		];

		return $groups;
	}

	/**
	 * Register the "User is enrolled in this cohort" condition option.
	 *
	 * @param array $options Existing condition options.
	 * @return array
	 */
	public function register_condition_options( array $options ): array {
		$options[] = [
			'key'     => self::COND_ENROLLED,
			'group'   => 'leaderspath',
			'label'   => esc_html__( 'User is enrolled in this cohort', 'leaderspath' ),
			'compare' => [
				'type'    => 'select',
				'options' => [
					'true'  => esc_html__( 'Yes', 'leaderspath' ),
					'false' => esc_html__( 'No', 'leaderspath' ),
				],
			],
		];

		return $options;
	}

	/**
	 * Resolve the custom enrollment condition.
	 *
	 * Gates strictly on our condition key so unrelated conditions pass through
	 * untouched. Compares actual enrollment against the editor's Yes/No choice.
	 *
	 * @param bool   $result    Bricks' computed result.
	 * @param string $key       The condition key.
	 * @param array  $condition The full condition row (key/compare/value).
	 * @return bool
	 */
	public function resolve_condition( bool $result, string $key, array $condition ): bool {
		if ( self::COND_ENROLLED !== ( $condition['key'] ?? '' ) ) {
			return $result;
		}

		$is_enrolled = lp_user_is_enrolled();

		// Editor picks Yes (default) or No via the compare value.
		$want = ( 'false' === ( $condition['value'] ?? 'true' ) ) ? false : true;

		return $is_enrolled === $want;
	}
}
