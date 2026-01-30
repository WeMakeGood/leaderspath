<?php
/**
 * Test the LeadersPath chat system via WP-CLI.
 *
 * Usage: wp eval-file wp-content/plugins/leaderspath/bin/test-chat.php [lesson_id] [message]
 *
 * Examples:
 *   wp eval-file wp-content/plugins/leaderspath/bin/test-chat.php
 *   wp eval-file wp-content/plugins/leaderspath/bin/test-chat.php 98 "What skills are available?"
 *
 * @package LeadersPath
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Must be run via WP-CLI' );
}

// Get arguments.
$lesson_id = isset( $args[0] ) ? (int) $args[0] : 0;
$message   = isset( $args[1] ) ? $args[1] : '';

echo "\n=== LeadersPath Chat System Test ===\n\n";

// 1. Test API Connection.
echo "1. Testing API Connection...\n";
$claude = new LeadersPath\Includes\Claude_API();
$connection_result = $claude->test_connection();

if ( is_wp_error( $connection_result ) ) {
	echo "   ERROR: " . $connection_result->get_error_message() . "\n";
	exit( 1 );
}

echo "   SUCCESS: Connected to Claude API\n";
echo "   Available models: " . $connection_result['model_count'] . "\n";

// List model families available.
$families = [];
foreach ( $connection_result['models'] as $model ) {
	if ( preg_match( '/^(claude-[a-z0-9-]+)-\d{8}$/', $model['id'], $matches ) ) {
		$families[ $matches[1] ] = true;
	}
}
echo "   Model families: " . implode( ', ', array_keys( $families ) ) . "\n\n";

// 2. Find or use specified lesson.
echo "2. Finding test lesson...\n";

if ( $lesson_id ) {
	$lesson = get_post( $lesson_id );
	if ( ! $lesson || 'leaderspath_lesson' !== $lesson->post_type ) {
		echo "   ERROR: Lesson ID $lesson_id not found\n";
		exit( 1 );
	}
} else {
	// Find a lesson with chatbot enabled.
	$lessons = get_posts( [
		'post_type'      => 'leaderspath_lesson',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'meta_query'     => [
			[
				'key'     => 'chatbot_enabled',
				'value'   => '1',
				'compare' => '=',
			],
		],
	] );

	if ( empty( $lessons ) ) {
		echo "   ERROR: No lessons with chatbot enabled found\n";
		exit( 1 );
	}

	$lesson    = $lessons[0];
	$lesson_id = $lesson->ID;
}

echo "   Using lesson: {$lesson->post_title} (ID: {$lesson_id})\n";

// Check chatbot settings.
$chatbot_enabled = get_field( 'chatbot_enabled', $lesson_id );
$chatbot_model   = get_field( 'chatbot_model', $lesson_id ) ?: LeadersPath\Admin\Settings::get_default_model();
$system_prompt   = get_field( 'chatbot_system_prompt', $lesson_id );

echo "   Chatbot enabled: " . ( $chatbot_enabled ? 'Yes' : 'No' ) . "\n";
echo "   Model: $chatbot_model\n";
echo "   Has custom system prompt: " . ( ! empty( $system_prompt ) ? 'Yes (' . strlen( $system_prompt ) . ' chars)' : 'No (using default)' ) . "\n\n";

// 3. Show context files.
echo "3. Context Files...\n";
$context_files = get_field( 'chatbot_context_files', $lesson_id ) ?: [];

if ( empty( $context_files ) ) {
	echo "   No context files attached\n";
} else {
	foreach ( $context_files as $context_id ) {
		$post = get_post( $context_id );
		if ( $post ) {
			$content_length = strlen( $post->post_content );
			echo "   - {$post->post_title} (ID: {$context_id}, {$content_length} chars)\n";
		}
	}
}
echo "\n";

// 4. Show skills.
echo "4. Skills...\n";
$skills = get_field( 'chatbot_skills', $lesson_id ) ?: [];

if ( empty( $skills ) ) {
	echo "   No skills attached\n";
} else {
	foreach ( $skills as $skill_id ) {
		$skill_name = get_field( 'skill_name', $skill_id ) ?: get_the_title( $skill_id );
		$skill_desc = get_field( 'skill_description', $skill_id );
		echo "   - $skill_name (ID: $skill_id)\n";
		if ( $skill_desc ) {
			$truncated = strlen( $skill_desc ) > 100 ? substr( $skill_desc, 0, 100 ) . '...' : $skill_desc;
			echo "     Desc: $truncated\n";
		}
	}
}
echo "\n";

// 5. Test chat if message provided.
if ( empty( $message ) ) {
	$message = "Hello! Can you tell me what context and skills you have available for this conversation?";
	echo "5. Using default test message: \"$message\"\n\n";
} else {
	echo "5. Using provided message: \"$message\"\n\n";
}

echo "6. Sending chat message...\n";

// Enable debug mode temporarily to see the full request.
$options = get_option( 'leaderspath_options', [] );
$original_debug = $options['debug_mode'] ?? false;
$options['debug_mode'] = true;
update_option( 'leaderspath_options', $options );

$result = $claude->send_message( $lesson_id, $message, [], $chatbot_model );

// Restore debug mode.
$options['debug_mode'] = $original_debug;
update_option( 'leaderspath_options', $options );

if ( is_wp_error( $result ) ) {
	echo "   ERROR: " . $result->get_error_message() . "\n";
	$data = $result->get_error_data();
	if ( ! empty( $data['details'] ) ) {
		echo "   Details: " . $data['details'] . "\n";
	}
	exit( 1 );
}

echo "   SUCCESS!\n\n";
echo "=== Response ===\n";
echo "Model: {$result['model']}\n";
echo "Stop reason: {$result['stop_reason']}\n";
if ( ! empty( $result['usage'] ) ) {
	echo "Input tokens: {$result['usage']['input_tokens']}\n";
	echo "Output tokens: {$result['usage']['output_tokens']}\n";
}
echo "\n--- Content ---\n";
echo $result['content'] . "\n";
echo "--- End ---\n\n";

// 7. Test conversation with history.
echo "7. Testing conversation continuity...\n";

$history = [
	[
		'role'    => 'user',
		'content' => $message,
	],
	[
		'role'    => 'assistant',
		'content' => $result['content'],
	],
];

$follow_up = "Can you summarize what you just told me in one sentence?";
echo "   Sending follow-up: \"$follow_up\"\n\n";

$result2 = $claude->send_message( $lesson_id, $follow_up, $history, $chatbot_model );

if ( is_wp_error( $result2 ) ) {
	echo "   ERROR: " . $result2->get_error_message() . "\n";
	exit( 1 );
}

echo "   SUCCESS!\n\n";
echo "=== Follow-up Response ===\n";
echo $result2['content'] . "\n";
echo "--- End ---\n\n";

echo "=== All tests passed! ===\n\n";
