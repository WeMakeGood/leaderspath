<?php
/**
 * Show the assembled system prompt for an activity.
 *
 * Usage: wp eval-file wp-content/plugins/leaderspath/bin/show-system-prompt.php [activity_id]
 *
 * @package LeadersPath
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Must be run via WP-CLI' );
}

$activity_id = isset( $args[0] ) ? (int) $args[0] : 98;

echo "\n=== System Prompt Assembly for Activity $activity_id ===\n\n";

$activity = get_post( $activity_id );
if ( ! $activity || 'leaderspath_activity' !== $activity->post_type ) {
	echo "ERROR: Activity ID $activity_id not found\n";
	exit( 1 );
}

echo "Activity: {$activity->post_title}\n\n";

// Use reflection to access the private build_system_prompt method.
$claude = new LeadersPath\Includes\Claude_API();
$reflection = new ReflectionClass( $claude );
$method = $reflection->getMethod( 'build_system_prompt' );
$method->setAccessible( true );

$system_prompt = $method->invoke( $claude, $activity_id );

echo "=== FULL SYSTEM PROMPT ===\n";
echo "Length: " . strlen( $system_prompt ) . " characters\n";
echo str_repeat( '-', 60 ) . "\n";
echo $system_prompt;
echo "\n" . str_repeat( '-', 60 ) . "\n";

// Count tokens estimate (rough: 1 token ~= 4 chars for English).
$token_estimate = (int) ( strlen( $system_prompt ) / 4 );
echo "\nEstimated tokens: ~$token_estimate\n";
