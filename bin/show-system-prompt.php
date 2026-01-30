<?php
/**
 * Show the assembled system prompt for a lesson.
 *
 * Usage: wp eval-file wp-content/plugins/leaderspath/bin/show-system-prompt.php [lesson_id]
 *
 * @package LeadersPath
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Must be run via WP-CLI' );
}

$lesson_id = isset( $args[0] ) ? (int) $args[0] : 98;

echo "\n=== System Prompt Assembly for Lesson $lesson_id ===\n\n";

$lesson = get_post( $lesson_id );
if ( ! $lesson || 'leaderspath_lesson' !== $lesson->post_type ) {
	echo "ERROR: Lesson ID $lesson_id not found\n";
	exit( 1 );
}

echo "Lesson: {$lesson->post_title}\n\n";

// Use reflection to access the private build_system_prompt method.
$claude = new LeadersPath\Includes\Claude_API();
$reflection = new ReflectionClass( $claude );
$method = $reflection->getMethod( 'build_system_prompt' );
$method->setAccessible( true );

$system_prompt = $method->invoke( $claude, $lesson_id );

echo "=== FULL SYSTEM PROMPT ===\n";
echo "Length: " . strlen( $system_prompt ) . " characters\n";
echo str_repeat( '-', 60 ) . "\n";
echo $system_prompt;
echo "\n" . str_repeat( '-', 60 ) . "\n";

// Count tokens estimate (rough: 1 token ~= 4 chars for English).
$token_estimate = (int) ( strlen( $system_prompt ) / 4 );
echo "\nEstimated tokens: ~$token_estimate\n";
