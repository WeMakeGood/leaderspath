<?php
/**
 * Create test data for LeadersPath development.
 *
 * Run with: wp eval-file wp-content/plugins/leaderspath/bin/create-test-data.php
 *
 * @package LeadersPath
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create test data.
 */
function leaderspath_create_test_data(): void {
	echo "Creating LeadersPath test data...\n\n";

	// Create Context Files first (referenced by lessons).
	$context_ids = leaderspath_create_test_context_files();

	// Create Skills (referenced by lessons).
	$skill_ids = leaderspath_create_test_skills();

	// Create Lessons.
	$lesson_ids = leaderspath_create_test_lessons( $context_ids, $skill_ids );

	// Create Courses (reference lessons).
	$course_ids = leaderspath_create_test_courses( $lesson_ids );

	// Create a Cohort.
	leaderspath_create_test_cohort( $course_ids[0] ?? 0 );

	echo "\nTest data creation complete!\n";
}

/**
 * Create test context files.
 *
 * @return array<int> Created post IDs.
 */
function leaderspath_create_test_context_files(): array {
	echo "Creating Context Files...\n";

	$context_files = [
		[
			'title'       => 'AI Ethics Guidelines',
			'content'     => "# AI Ethics Guidelines\n\nThese guidelines help ensure responsible AI usage:\n\n## Core Principles\n\n1. **Transparency**: Be clear about AI limitations and capabilities\n2. **Fairness**: Avoid bias in AI applications\n3. **Privacy**: Protect user data and consent\n4. **Accountability**: Maintain human oversight\n5. **Safety**: Prevent harmful outputs\n\n## Best Practices\n\n- Always disclose when content is AI-generated\n- Review AI outputs before publishing\n- Consider diverse perspectives in training data\n- Implement feedback mechanisms",
			'description' => 'Core ethical principles for working with AI systems.',
			'file_type'   => 'knowledge_base',
			'version'     => '1.0.0',
		],
		[
			'title'       => 'Prompt Engineering Basics',
			'content'     => "# Prompt Engineering Basics\n\n## What is Prompt Engineering?\n\nPrompt engineering is the practice of designing effective inputs for AI language models to get desired outputs.\n\n## Key Techniques\n\n### 1. Be Specific\nInstead of: \"Write about dogs\"\nBetter: \"Write a 200-word article about the health benefits of owning a dog for seniors\"\n\n### 2. Provide Context\nGive the AI background information it needs to respond appropriately.\n\n### 3. Use Examples\nShow the AI what format or style you want through examples.\n\n### 4. Set Constraints\nDefine length, tone, format, and other parameters.\n\n### 5. Iterate\nRefine your prompts based on the outputs you receive.",
			'description' => 'Introduction to writing effective prompts for AI models.',
			'file_type'   => 'instructions',
			'version'     => '1.2.0',
		],
		[
			'title'       => 'Sample Conversation Flows',
			'content'     => "# Sample Conversation Flows\n\n## Example 1: Technical Explanation\n\n**User**: What is machine learning?\n\n**Assistant**: Machine learning is a subset of artificial intelligence where computers learn patterns from data without being explicitly programmed. Think of it like teaching a child to recognize cats - instead of listing every feature of a cat, you show them many pictures until they can identify cats on their own.\n\n## Example 2: Creative Task\n\n**User**: Help me brainstorm names for a coffee shop.\n\n**Assistant**: Here are some coffee shop name ideas:\n- The Daily Grind\n- Bean There, Done That\n- Espresso Yourself\n- The Perky Cup\n- Grounds for Celebration",
			'description' => 'Example conversations demonstrating good AI interaction patterns.',
			'file_type'   => 'examples',
			'version'     => '1.0.0',
		],
	];

	$ids = [];

	foreach ( $context_files as $data ) {
		$existing = get_page_by_title( $data['title'], OBJECT, 'leaderspath_context' );
		if ( $existing ) {
			echo "  - Skipping '{$data['title']}' (already exists)\n";
			$ids[] = $existing->ID;
			continue;
		}

		$post_id = wp_insert_post( [
			'post_title'   => $data['title'],
			'post_content' => $data['content'],
			'post_type'    => 'leaderspath_context',
			'post_status'  => 'publish',
		] );

		if ( is_wp_error( $post_id ) ) {
			echo "  - Error creating '{$data['title']}': {$post_id->get_error_message()}\n";
			continue;
		}

		// Set ACF fields.
		if ( function_exists( 'update_field' ) ) {
			update_field( 'context_description', $data['description'], $post_id );
			update_field( 'context_file_type', $data['file_type'], $post_id );
			update_field( 'context_version', $data['version'], $post_id );
		}

		echo "  - Created '{$data['title']}' (ID: {$post_id})\n";
		$ids[] = $post_id;
	}

	return $ids;
}

/**
 * Create test skills.
 *
 * @return array<int> Created post IDs.
 */
function leaderspath_create_test_skills(): array {
	echo "\nCreating Skills...\n";

	$skills = [
		[
			'title'         => 'Code Review Assistant',
			'name'          => 'code-review',
			'description'   => 'Analyzes code for bugs, security issues, and style improvements. Provides actionable feedback with explanations.',
			'compatibility' => 'Works with Python, JavaScript, PHP, and other common languages.',
			'version'       => '1.0.0',
		],
		[
			'title'         => 'Writing Editor',
			'name'          => 'writing-editor',
			'description'   => 'Reviews and improves written content for clarity, grammar, tone, and engagement. Can adapt to different styles (formal, casual, technical).',
			'compatibility' => 'English language content.',
			'version'       => '1.1.0',
		],
	];

	$ids = [];

	foreach ( $skills as $data ) {
		$existing = get_page_by_title( $data['title'], OBJECT, 'leaderspath_skill' );
		if ( $existing ) {
			echo "  - Skipping '{$data['title']}' (already exists)\n";
			$ids[] = $existing->ID;
			continue;
		}

		$post_id = wp_insert_post( [
			'post_title'  => $data['title'],
			'post_type'   => 'leaderspath_skill',
			'post_status' => 'publish',
		] );

		if ( is_wp_error( $post_id ) ) {
			echo "  - Error creating '{$data['title']}': {$post_id->get_error_message()}\n";
			continue;
		}

		// Set ACF fields (normally these would be extracted from ZIP).
		if ( function_exists( 'update_field' ) ) {
			update_field( 'skill_name', $data['name'], $post_id );
			update_field( 'skill_description', $data['description'], $post_id );
			update_field( 'skill_compatibility', $data['compatibility'], $post_id );
			update_field( 'skill_version', $data['version'], $post_id );
		}

		echo "  - Created '{$data['title']}' (ID: {$post_id})\n";
		$ids[] = $post_id;
	}

	return $ids;
}

/**
 * Create test lessons.
 *
 * @param array<int> $context_ids Context file IDs.
 * @param array<int> $skill_ids   Skill IDs.
 * @return array<int> Created post IDs.
 */
function leaderspath_create_test_lessons( array $context_ids, array $skill_ids ): array {
	echo "\nCreating Lessons...\n";

	$lessons = [
		[
			'title'         => 'Introduction to AI Assistants',
			'content'       => "<h2>Welcome to AI Assistants</h2>\n\n<p>In this lesson, you'll learn the fundamentals of working with AI assistants like Claude. We'll cover what AI assistants can do, their limitations, and how to interact with them effectively.</p>\n\n<h3>Learning Objectives</h3>\n<ul>\n<li>Understand what AI assistants are and how they work</li>\n<li>Learn the difference between AI and traditional software</li>\n<li>Practice having a conversation with an AI</li>\n</ul>\n\n<h3>Key Concepts</h3>\n<p>AI assistants are trained on large amounts of text data and can generate human-like responses. They don't truly \"understand\" in the human sense, but can be remarkably helpful for many tasks.</p>",
			'excerpt'       => 'Learn the basics of AI assistants and how to interact with them effectively.',
			'duration'      => 15,
			'objectives'    => [
				'Understand what AI assistants are',
				'Know the difference between AI and traditional software',
				'Have your first AI conversation',
			],
			'chatbot'       => true,
			'model'         => 'sonnet',
			'system_prompt' => 'You are a friendly AI tutor helping someone learn about AI assistants for the first time. Be encouraging, use simple language, and provide concrete examples. Ask questions to check understanding.',
			'context_files' => [ 0 ], // Index into $context_ids.
			'skills'        => [],
		],
		[
			'title'         => 'Effective Prompt Writing',
			'content'       => "<h2>Writing Better Prompts</h2>\n\n<p>The quality of your AI interactions depends heavily on how you write your prompts. This lesson covers techniques for getting better results from AI assistants.</p>\n\n<h3>The CLEAR Framework</h3>\n<ul>\n<li><strong>C</strong>ontext - Provide background information</li>\n<li><strong>L</strong>ength - Specify desired output length</li>\n<li><strong>E</strong>xamples - Show what you want</li>\n<li><strong>A</strong>udience - Define who will read it</li>\n<li><strong>R</strong>ole - Give the AI a persona</li>\n</ul>\n\n<h3>Practice Exercise</h3>\n<p>Try rewriting this vague prompt: \"Write something about marketing\" using the CLEAR framework.</p>",
			'excerpt'       => 'Master the art of prompt engineering to get better AI responses.',
			'duration'      => 25,
			'objectives'    => [
				'Apply the CLEAR framework to prompts',
				'Recognize and fix vague prompts',
				'Iterate on prompts for better results',
			],
			'chatbot'       => true,
			'model'         => 'sonnet',
			'system_prompt' => 'You are a prompt engineering coach. Help the user practice writing effective prompts. When they submit a prompt, analyze it using the CLEAR framework and suggest improvements. Be constructive and specific.',
			'context_files' => [ 1 ], // Prompt Engineering Basics.
			'skills'        => [ 1 ], // Writing Editor.
		],
		[
			'title'         => 'AI Ethics and Responsibility',
			'content'       => "<h2>Using AI Responsibly</h2>\n\n<p>As AI becomes more powerful, understanding ethical considerations becomes crucial. This lesson explores the responsibilities that come with using AI tools.</p>\n\n<h3>Key Ethical Considerations</h3>\n<ul>\n<li>Transparency about AI usage</li>\n<li>Avoiding harmful applications</li>\n<li>Protecting privacy</li>\n<li>Ensuring fairness and avoiding bias</li>\n<li>Maintaining human oversight</li>\n</ul>\n\n<h3>Discussion Questions</h3>\n<p>Consider: When should you disclose that content was AI-generated? What are the risks of over-relying on AI?</p>",
			'excerpt'       => 'Explore the ethical dimensions of AI and learn to use it responsibly.',
			'duration'      => 20,
			'objectives'    => [
				'Identify key ethical considerations in AI use',
				'Evaluate scenarios for ethical concerns',
				'Develop personal guidelines for AI ethics',
			],
			'chatbot'       => true,
			'model'         => 'sonnet',
			'system_prompt' => 'You are an AI ethics educator. Help users think through ethical scenarios involving AI. Present multiple perspectives, ask probing questions, and encourage critical thinking. Avoid being preachy.',
			'context_files' => [ 0 ], // AI Ethics Guidelines.
			'skills'        => [],
		],
		[
			'title'         => 'Raw LLM vs Context-Enhanced AI',
			'content'       => "<h2>Understanding the Difference</h2>\n\n<p>This lesson demonstrates the dramatic difference between interacting with a raw language model versus one enhanced with context and skills.</p>\n\n<h3>What You'll Experience</h3>\n<p>You'll have two conversations:</p>\n<ol>\n<li>First with minimal context (raw LLM experience)</li>\n<li>Then with full context and skills enabled</li>\n</ol>\n\n<h3>Key Insight</h3>\n<p>Context-enhanced AI can provide more relevant, accurate, and useful responses because it has access to specific information and capabilities tailored to the task.</p>",
			'excerpt'       => 'Experience firsthand how context transforms AI capabilities.',
			'duration'      => 30,
			'objectives'    => [
				'Experience raw vs context-enhanced AI',
				'Understand why context matters',
				'Appreciate the value of AI customization',
			],
			'chatbot'       => true,
			'model'         => 'haiku',
			'system_prompt' => '', // Empty for raw experience.
			'context_files' => [],
			'skills'        => [],
		],
	];

	$ids = [];

	foreach ( $lessons as $data ) {
		$existing = get_page_by_title( $data['title'], OBJECT, 'leaderspath_lesson' );
		if ( $existing ) {
			echo "  - Skipping '{$data['title']}' (already exists)\n";
			$ids[] = $existing->ID;
			continue;
		}

		$post_id = wp_insert_post( [
			'post_title'   => $data['title'],
			'post_content' => $data['content'],
			'post_excerpt' => $data['excerpt'],
			'post_type'    => 'leaderspath_lesson',
			'post_status'  => 'publish',
		] );

		if ( is_wp_error( $post_id ) ) {
			echo "  - Error creating '{$data['title']}': {$post_id->get_error_message()}\n";
			continue;
		}

		// Set ACF fields.
		if ( function_exists( 'update_field' ) ) {
			update_field( 'lesson_duration', $data['duration'], $post_id );

			// Objectives (repeater).
			$objectives = [];
			foreach ( $data['objectives'] as $obj ) {
				$objectives[] = [ 'objective' => $obj ];
			}
			update_field( 'lesson_objectives', $objectives, $post_id );

			// Chatbot settings.
			update_field( 'chatbot_enabled', $data['chatbot'] ? 1 : 0, $post_id );
			update_field( 'chatbot_model', $data['model'], $post_id );
			update_field( 'chatbot_system_prompt', $data['system_prompt'], $post_id );
			update_field( 'chatbot_allow_model_switch', 1, $post_id );

			// Context files.
			$context_for_lesson = [];
			foreach ( $data['context_files'] as $idx ) {
				if ( isset( $context_ids[ $idx ] ) ) {
					$context_for_lesson[] = $context_ids[ $idx ];
				}
			}
			if ( ! empty( $context_for_lesson ) ) {
				update_field( 'chatbot_context_files', $context_for_lesson, $post_id );
			}

			// Skills.
			$skills_for_lesson = [];
			foreach ( $data['skills'] as $idx ) {
				if ( isset( $skill_ids[ $idx ] ) ) {
					$skills_for_lesson[] = $skill_ids[ $idx ];
				}
			}
			if ( ! empty( $skills_for_lesson ) ) {
				update_field( 'chatbot_skills', $skills_for_lesson, $post_id );
			}
		}

		echo "  - Created '{$data['title']}' (ID: {$post_id})\n";
		$ids[] = $post_id;
	}

	return $ids;
}

/**
 * Create test courses.
 *
 * @param array<int> $lesson_ids Lesson IDs.
 * @return array<int> Created post IDs.
 */
function leaderspath_create_test_courses( array $lesson_ids ): array {
	echo "\nCreating Courses...\n";

	$courses = [
		[
			'title'      => 'AI Fundamentals',
			'content'    => "<h2>Course Overview</h2>\n\n<p>This course introduces you to working with AI assistants. You'll learn the basics, practice prompt writing, and explore ethical considerations.</p>\n\n<p>By the end, you'll be confident in your ability to use AI tools effectively and responsibly.</p>",
			'excerpt'    => 'A comprehensive introduction to AI assistants for beginners.',
			'lessons'    => [ 0, 1, 2 ], // Indices into $lesson_ids.
			'difficulty' => 'beginner',
		],
		[
			'title'      => 'AI in Practice',
			'content'    => "<h2>Hands-On AI Experience</h2>\n\n<p>Move beyond theory and experience the practical differences between raw and context-enhanced AI. This short course provides direct comparison opportunities.</p>",
			'excerpt'    => 'Experience the difference context makes in AI interactions.',
			'lessons'    => [ 3 ], // Raw vs Enhanced lesson.
			'difficulty' => 'intermediate',
		],
	];

	$ids = [];

	foreach ( $courses as $data ) {
		$existing = get_page_by_title( $data['title'], OBJECT, 'leaderspath_course' );
		if ( $existing ) {
			echo "  - Skipping '{$data['title']}' (already exists)\n";
			$ids[] = $existing->ID;
			continue;
		}

		$post_id = wp_insert_post( [
			'post_title'   => $data['title'],
			'post_content' => $data['content'],
			'post_excerpt' => $data['excerpt'],
			'post_type'    => 'leaderspath_course',
			'post_status'  => 'publish',
		] );

		if ( is_wp_error( $post_id ) ) {
			echo "  - Error creating '{$data['title']}': {$post_id->get_error_message()}\n";
			continue;
		}

		// Set ACF fields.
		if ( function_exists( 'update_field' ) ) {
			// Lessons (relationship).
			$lessons_for_course = [];
			foreach ( $data['lessons'] as $idx ) {
				if ( isset( $lesson_ids[ $idx ] ) ) {
					$lessons_for_course[] = $lesson_ids[ $idx ];
				}
			}
			if ( ! empty( $lessons_for_course ) ) {
				update_field( 'course_lessons', $lessons_for_course, $post_id );
			}

			update_field( 'course_difficulty', $data['difficulty'], $post_id );
		}

		echo "  - Created '{$data['title']}' (ID: {$post_id})\n";
		$ids[] = $post_id;
	}

	return $ids;
}

/**
 * Create a test cohort.
 *
 * @param int $course_id Course ID.
 */
function leaderspath_create_test_cohort( int $course_id ): void {
	echo "\nCreating Cohort...\n";

	if ( ! $course_id ) {
		echo "  - Skipping cohort (no course available)\n";
		return;
	}

	$title = 'Spring 2026 Cohort';

	$existing = get_page_by_title( $title, OBJECT, 'leaderspath_cohort' );
	if ( $existing ) {
		echo "  - Skipping '{$title}' (already exists)\n";
		return;
	}

	$post_id = wp_insert_post( [
		'post_title'  => $title,
		'post_type'   => 'leaderspath_cohort',
		'post_status' => 'publish',
	] );

	if ( is_wp_error( $post_id ) ) {
		echo "  - Error creating '{$title}': {$post_id->get_error_message()}\n";
		return;
	}

	// Set ACF fields.
	if ( function_exists( 'update_field' ) ) {
		update_field( 'cohort_course', $course_id, $post_id );
		update_field( 'cohort_status', 'upcoming', $post_id );
		update_field( 'cohort_start_date', '2026-03-01', $post_id );
		update_field( 'cohort_end_date', '2026-05-31', $post_id );
		update_field( 'cohort_language', 'en', $post_id );
		update_field( 'cohort_timezone', 'America/New_York', $post_id );
		update_field( 'cohort_max_participants', 25, $post_id );
	}

	echo "  - Created '{$title}' (ID: {$post_id})\n";
}

// Run the function.
leaderspath_create_test_data();
