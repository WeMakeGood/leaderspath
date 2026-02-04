<?php
/**
 * Test the skill sync pipeline to Anthropic Skills API.
 *
 * Usage: wp eval-file wp-content/plugins/leaderspath/bin/test-skill-sync.php [skill_id]
 *
 * @package LeadersPath
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Must be run via WP-CLI' );
}

$skill_id = isset( $args[0] ) ? (int) $args[0] : 125;

echo "\n=== Skill Sync Pipeline Test ===\n\n";

// 1. Check API key is configured.
echo "1. Checking API key configuration...\n";
$api_key = LeadersPath\Admin\Settings::get_api_key();
if ( empty( $api_key ) ) {
	echo "   ERROR: API key not configured. Please add it in Settings > LeadersPath.\n";
	exit( 1 );
}
echo "   OK: API key is configured\n\n";

// 2. Check skill exists.
echo "2. Checking skill post exists...\n";
$skill = get_post( $skill_id );
if ( ! $skill || 'leaderspath_skill' !== $skill->post_type ) {
	echo "   ERROR: Skill ID $skill_id not found\n";
	exit( 1 );
}
echo "   OK: Found skill: {$skill->post_title}\n\n";

// 3. Check skill package attachment.
echo "3. Checking skill package attachment...\n";
$package_id = get_field( 'skill_package', $skill_id );
if ( ! $package_id ) {
	echo "   ERROR: No skill package attached\n";
	exit( 1 );
}
$file_path = get_attached_file( $package_id );
if ( ! $file_path || ! file_exists( $file_path ) ) {
	echo "   ERROR: Attachment file not found\n";
	exit( 1 );
}
echo "   OK: Package file exists: " . basename( $file_path ) . "\n";
echo "   Size: " . size_format( filesize( $file_path ) ) . "\n\n";

// 4. Check local validation (frontmatter).
echo "4. Checking local validation (SKILL.md frontmatter)...\n";
$skill_name = get_field( 'skill_name', $skill_id );
$skill_desc = get_field( 'skill_description', $skill_id );
if ( empty( $skill_name ) || empty( $skill_desc ) ) {
	echo "   ERROR: skill_name or skill_description not populated (local validation may have failed)\n";
	exit( 1 );
}
echo "   OK: skill_name: $skill_name\n";
echo "   OK: skill_description: " . substr( $skill_desc, 0, 80 ) . "...\n\n";

// 5. Check current sync status.
echo "5. Current sync status...\n";
$sync_status   = get_field( 'skill_sync_status', $skill_id );
$anthropic_id  = get_field( 'skill_anthropic_id', $skill_id );
$sync_error    = get_field( 'skill_sync_error', $skill_id );
$last_synced   = get_field( 'skill_last_synced', $skill_id );
echo "   sync_status: " . ( $sync_status ?: '(empty)' ) . "\n";
echo "   anthropic_id: " . ( $anthropic_id ?: '(empty)' ) . "\n";
if ( $sync_error ) {
	echo "   sync_error: $sync_error\n";
}
if ( $last_synced ) {
	echo "   last_synced: $last_synced\n";
}
echo "\n";

// 6. Check beta headers configuration.
echo "6. Checking beta headers configuration...\n";
$beta_headers = LeadersPath\Admin\Settings::get_beta_headers( [ 'skills' ] );
$tool_type    = LeadersPath\Admin\Settings::get_code_execution_tool_type();
echo "   Beta headers: $beta_headers\n";
echo "   Tool type: $tool_type\n\n";

// 7. Ask to proceed with sync test.
if ( 'synced' === $sync_status && $anthropic_id ) {
	echo "7. Skill is already synced to Anthropic (ID: $anthropic_id)\n";
	echo "   To test re-sync, run: wp eval-file ... --resync\n\n";

	// Check if --resync flag was passed.
	if ( in_array( '--resync', $args, true ) ) {
		echo "   Re-syncing...\n";
	} else {
		echo "=== All checks passed! Skill is synced and ready. ===\n\n";
		exit( 0 );
	}
}

echo "7. Attempting to sync skill to Anthropic...\n";
echo "   This will upload the skill package to the Anthropic Skills API.\n\n";

// Create skill processor and attempt sync.
$processor = new LeadersPath\Includes\Skill_Processor();
$result    = $processor->resync_skill( $skill_id );

if ( is_wp_error( $result ) ) {
	echo "   ERROR: " . $result->get_error_message() . "\n";

	// Refresh sync error from database.
	$sync_error = get_field( 'skill_sync_error', $skill_id );
	if ( $sync_error ) {
		echo "   Details: $sync_error\n";
	}
	exit( 1 );
}

// Success - show updated fields.
echo "   SUCCESS!\n\n";
echo "8. Updated sync status...\n";
$sync_status  = get_field( 'skill_sync_status', $skill_id );
$anthropic_id = get_field( 'skill_anthropic_id', $skill_id );
$version      = get_field( 'skill_anthropic_version', $skill_id );
$last_synced  = get_field( 'skill_last_synced', $skill_id );

echo "   sync_status: $sync_status\n";
echo "   anthropic_id: $anthropic_id\n";
echo "   version: $version\n";
echo "   last_synced: $last_synced\n\n";

echo "=== Skill sync test completed successfully! ===\n\n";

// 9. Test that skill can be used in chat.
echo "9. Testing skill inclusion in chat request...\n";

// Get a lesson that uses this skill.
$lessons = get_posts( [
	'post_type'      => 'leaderspath_activity',
	'post_status'    => 'publish',
	'posts_per_page' => 1,
	'meta_query'     => [
		[
			'key'     => 'chatbot_skills',
			'value'   => $skill_id,
			'compare' => 'LIKE',
		],
	],
] );

if ( empty( $lessons ) ) {
	echo "   No lessons found using this skill. Skipping chat test.\n";
	echo "   To test: assign this skill to a lesson's Chatbot Configuration.\n\n";
} else {
	$lesson = $lessons[0];
	echo "   Found lesson using skill: {$lesson->post_title} (ID: {$lesson->ID})\n";

	// Build the skills array that would be sent.
	$claude = new LeadersPath\Includes\Claude_API();
	$reflection = new ReflectionClass( $claude );
	$method = $reflection->getMethod( 'get_skills_for_api' );
	$method->setAccessible( true );
	$skills_for_api = $method->invoke( $claude, $lesson->ID );

	echo "   Skills that would be included in API request:\n";
	foreach ( $skills_for_api as $skill_data ) {
		echo "     - skill_id: {$skill_data['skill_id']}, version: {$skill_data['version']}\n";
	}

	if ( empty( $skills_for_api ) ) {
		echo "     (none - skill may not be synced or not assigned)\n";
	}
	echo "\n";
}

echo "=== All tests completed! ===\n\n";
