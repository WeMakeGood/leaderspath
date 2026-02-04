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

	// Create Context Files first (referenced by activities).
	$context_ids = leaderspath_create_test_context_files();

	// Create Skills (referenced by activities).
	$skill_ids = leaderspath_create_test_skills();

	// Create Activities.
	$activity_ids = leaderspath_create_test_activities( $context_ids, $skill_ids );

	// Create Courses (reference activities and context files).
	$course_ids = leaderspath_create_test_courses( $activity_ids, $context_ids );

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
 * Create test activities.
 *
 * @param array<int> $context_ids Context file IDs.
 * @param array<int> $skill_ids   Skill IDs.
 * @return array<int> Created post IDs.
 */
function leaderspath_create_test_activities( array $context_ids, array $skill_ids ): array {
	echo "\nCreating Activities...\n";

	$activities = [
		[
			'title'         => 'Introduction to AI Assistants',
			'content'       => "<h2>Welcome to AI Assistants</h2>\n\n<p>In this activity, you'll explore the fundamentals of working with AI assistants like Claude. You'll discover what AI assistants can do, their limitations, and how to interact with them effectively.</p>\n\n<h3>What You'll Explore</h3>\n<ul>\n<li>Understand what AI assistants are and how they work</li>\n<li>Learn the difference between AI and traditional software</li>\n<li>Practice having a conversation with an AI</li>\n</ul>\n\n<h3>Key Concepts</h3>\n<p>AI assistants are trained on large amounts of text data and can generate human-like responses. They don't truly \"understand\" in the human sense, but can be remarkably helpful for many tasks.</p>",
			'excerpt'       => 'Explore the basics of AI assistants and how to interact with them effectively.',
			'duration'      => 15,
			'chatbot'       => true,
			'model'         => 'sonnet',
			'system_prompt' => 'You are a friendly AI tutor helping someone learn about AI assistants for the first time. Be encouraging, use simple language, and provide concrete examples. Ask questions to check understanding.',
			'context_files' => [ 0 ], // Index into $context_ids.
			'skills'        => [],
		],
		[
			'title'         => 'Effective Prompt Writing',
			'content'       => "<h2>Writing Better Prompts</h2>\n\n<p>The quality of your AI interactions depends heavily on how you write your prompts. This activity covers techniques for getting better results from AI assistants.</p>\n\n<h3>The CLEAR Framework</h3>\n<ul>\n<li><strong>C</strong>ontext - Provide background information</li>\n<li><strong>L</strong>ength - Specify desired output length</li>\n<li><strong>E</strong>xamples - Show what you want</li>\n<li><strong>A</strong>udience - Define who will read it</li>\n<li><strong>R</strong>ole - Give the AI a persona</li>\n</ul>\n\n<h3>Practice Exercise</h3>\n<p>Try rewriting this vague prompt: \"Write something about marketing\" using the CLEAR framework.</p>",
			'excerpt'       => 'Master the art of prompt engineering to get better AI responses.',
			'duration'      => 25,
			'chatbot'       => true,
			'model'         => 'sonnet',
			'system_prompt' => 'You are a prompt engineering coach. Help the user practice writing effective prompts. When they submit a prompt, analyze it using the CLEAR framework and suggest improvements. Be constructive and specific.',
			'context_files' => [ 1 ], // Prompt Engineering Basics.
			'skills'        => [ 1 ], // Writing Editor.
		],
		[
			'title'         => 'AI Ethics and Responsibility',
			'content'       => "<h2>Using AI Responsibly</h2>\n\n<p>As AI becomes more powerful, understanding ethical considerations becomes crucial. This activity explores the responsibilities that come with using AI tools.</p>\n\n<h3>Key Ethical Considerations</h3>\n<ul>\n<li>Transparency about AI usage</li>\n<li>Avoiding harmful applications</li>\n<li>Protecting privacy</li>\n<li>Ensuring fairness and avoiding bias</li>\n<li>Maintaining human oversight</li>\n</ul>\n\n<h3>Discussion Questions</h3>\n<p>Consider: When should you disclose that content was AI-generated? What are the risks of over-relying on AI?</p>",
			'excerpt'       => 'Explore the ethical dimensions of AI and learn to use it responsibly.',
			'duration'      => 20,
			'chatbot'       => true,
			'model'         => 'sonnet',
			'system_prompt' => 'You are an AI ethics educator. Help users think through ethical scenarios involving AI. Present multiple perspectives, ask probing questions, and encourage critical thinking. Avoid being preachy.',
			'context_files' => [ 0 ], // AI Ethics Guidelines.
			'skills'        => [],
		],
		[
			'title'         => 'Raw LLM vs Context-Enhanced AI',
			'content'       => "<h2>Understanding the Difference</h2>\n\n<p>This activity demonstrates the dramatic difference between interacting with a raw language model versus one enhanced with context and skills.</p>\n\n<h3>What You'll Experience</h3>\n<p>You'll have two conversations:</p>\n<ol>\n<li>First with minimal context (raw LLM experience)</li>\n<li>Then with full context and skills enabled</li>\n</ol>\n\n<h3>Key Insight</h3>\n<p>Context-enhanced AI can provide more relevant, accurate, and useful responses because it has access to specific information and capabilities tailored to the task.</p>",
			'excerpt'       => 'Experience firsthand how context transforms AI capabilities.',
			'duration'      => 30,
			'chatbot'       => true,
			'model'         => 'haiku',
			'system_prompt' => '', // Empty for raw experience.
			'context_files' => [],
			'skills'        => [],
		],
	];

	$ids = [];

	foreach ( $activities as $data ) {
		$existing = get_page_by_title( $data['title'], OBJECT, 'leaderspath_activity' );
		if ( $existing ) {
			echo "  - Skipping '{$data['title']}' (already exists)\n";
			$ids[] = $existing->ID;
			continue;
		}

		$post_id = wp_insert_post( [
			'post_title'   => $data['title'],
			'post_content' => $data['content'],
			'post_excerpt' => $data['excerpt'],
			'post_type'    => 'leaderspath_activity',
			'post_status'  => 'publish',
		] );

		if ( is_wp_error( $post_id ) ) {
			echo "  - Error creating '{$data['title']}': {$post_id->get_error_message()}\n";
			continue;
		}

		// Set ACF fields.
		if ( function_exists( 'update_field' ) ) {
			update_field( 'activity_duration', $data['duration'], $post_id );

			// Chatbot settings.
			update_field( 'chatbot_enabled', $data['chatbot'] ? 1 : 0, $post_id );
			update_field( 'chatbot_model', $data['model'], $post_id );
			update_field( 'chatbot_system_prompt', $data['system_prompt'], $post_id );
			update_field( 'chatbot_allow_model_switch', 1, $post_id );

			// Context files.
			$context_for_activity = [];
			foreach ( $data['context_files'] as $idx ) {
				if ( isset( $context_ids[ $idx ] ) ) {
					$context_for_activity[] = $context_ids[ $idx ];
				}
			}
			if ( ! empty( $context_for_activity ) ) {
				update_field( 'chatbot_context_files', $context_for_activity, $post_id );
			}

			// Skills.
			$skills_for_activity = [];
			foreach ( $data['skills'] as $idx ) {
				if ( isset( $skill_ids[ $idx ] ) ) {
					$skills_for_activity[] = $skill_ids[ $idx ];
				}
			}
			if ( ! empty( $skills_for_activity ) ) {
				update_field( 'chatbot_skills', $skills_for_activity, $post_id );
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
 * @param array<int> $activity_ids Activity IDs.
 * @param array<int> $context_ids  Context file IDs (for course Q&A chatbot).
 * @return array<int> Created post IDs.
 */
function leaderspath_create_test_courses( array $activity_ids, array $context_ids ): array {
	echo "\nCreating Courses...\n";

	$courses = [
		[
			'title'      => 'AI Fundamentals',
			'content'    => "<h2>Course Overview</h2>\n\n<p>This course introduces you to working with AI assistants. You'll learn the basics, practice prompt writing, and explore ethical considerations.</p>\n\n<p>By the end, you'll be confident in your ability to use AI tools effectively and responsibly.</p>",
			'excerpt'    => 'A comprehensive introduction to AI assistants for beginners.',
			'activities' => [ 0, 1, 2 ], // Indices into $activity_ids.
			'difficulty' => 'beginner',
			'total_duration' => '90 minutes',
			'objectives' => [
				'Understand what AI assistants are and how they differ from traditional software',
				'Write effective prompts using the CLEAR framework',
				'Identify ethical considerations when using AI tools',
				'Confidently interact with AI assistants for various tasks',
			],
			'facilitator_guide' => "<h2>Facilitator Guide: AI Fundamentals</h2>\n\n<h3>Before the Session (10 min prep)</h3>\n<ul>\n<li>Ensure all participants have access to the course</li>\n<li>Test the AI sandbox activities work correctly</li>\n<li>Review the context files attached to each activity</li>\n</ul>\n\n<h3>Session Flow</h3>\n\n<h4>1. Welcome & Introduction (10 min)</h4>\n<p>Welcome participants and set expectations. Explain that this is an interactive session where they'll experiment with AI firsthand.</p>\n<p><strong>Key talking points:</strong></p>\n<ul>\n<li>AI assistants are tools, not magic</li>\n<li>The quality of output depends on input quality</li>\n<li>We'll learn by doing, not just watching</li>\n</ul>\n\n<h4>2. Activity: Introduction to AI Assistants (20 min)</h4>\n<p>Guide participants to the first activity. Let them explore freely for 10 minutes, then facilitate a 10-minute discussion.</p>\n<p><strong>Discussion prompts:</strong></p>\n<ul>\n<li>What surprised you about the AI's responses?</li>\n<li>What limitations did you notice?</li>\n<li>How is this different from a search engine?</li>\n</ul>\n\n<h4>3. Activity: Effective Prompt Writing (25 min)</h4>\n<p>Introduce the CLEAR framework before participants start. Allow 15 minutes for hands-on practice, 10 minutes for sharing.</p>\n<p><strong>Facilitation tip:</strong> Have participants share their before/after prompts to illustrate improvement.</p>\n\n<h4>4. Activity: AI Ethics and Responsibility (20 min)</h4>\n<p>This activity works best with paired discussion. Have participants work through scenarios together.</p>\n<p><strong>Discussion prompts:</strong></p>\n<ul>\n<li>When would you disclose AI assistance?</li>\n<li>What tasks should NOT use AI?</li>\n<li>How do you verify AI outputs?</li>\n</ul>\n\n<h4>5. Wrap-up & Q&A (15 min)</h4>\n<p>Summarize key learnings and answer questions. Point participants to additional resources.</p>",
			'learner_overview' => "<h2>What You'll Experience</h2>\n\n<p>Welcome to AI Fundamentals! In this facilitated session, you'll go beyond reading about AI—you'll actually work with it.</p>\n\n<h3>Session Format</h3>\n<p>This is an interactive, hands-on course led by a facilitator. You'll:</p>\n<ul>\n<li>Experiment with AI assistants in guided sandbox activities</li>\n<li>Discuss your observations with fellow learners</li>\n<li>Practice techniques that improve AI interactions</li>\n<li>Explore real ethical scenarios together</li>\n</ul>\n\n<h3>What to Expect</h3>\n<p>Each activity gives you a safe space to explore AI behavior. The AI sandboxes are configured specifically for learning—try things, make mistakes, and discover what works.</p>\n\n<h3>No Prior Experience Needed</h3>\n<p>This course is designed for beginners. Come with curiosity and an open mind.</p>",
			'chatbot_enabled' => true,
			'chatbot_model' => 'sonnet',
			'chatbot_system_prompt' => 'You are a helpful Q&A assistant for the AI Fundamentals course. Answer questions about AI assistants, prompt engineering, and AI ethics based on the course content. Be encouraging to beginners and provide practical examples. If asked about topics outside the course scope, acknowledge the question and gently redirect to the course material.',
			'chatbot_context_files' => [ 0, 1 ], // AI Ethics Guidelines, Prompt Engineering Basics.
		],
		[
			'title'      => 'AI in Practice',
			'content'    => "<h2>Hands-On AI Experience</h2>\n\n<p>Move beyond theory and experience the practical differences between raw and context-enhanced AI. This short course provides direct comparison opportunities.</p>",
			'excerpt'    => 'Experience the difference context makes in AI interactions.',
			'activities' => [ 3 ], // Raw vs Enhanced activity.
			'difficulty' => 'intermediate',
			'total_duration' => '45 minutes',
			'objectives' => [
				'Experience the difference between raw and context-enhanced AI',
				'Understand why context improves AI responses',
				'Recognize the value of AI customization for specific use cases',
			],
			'facilitator_guide' => "<h2>Facilitator Guide: AI in Practice</h2>\n\n<h3>Session Overview</h3>\n<p>This short course demonstrates the power of context through direct comparison. Participants will interact with both raw and enhanced AI to see the difference firsthand.</p>\n\n<h3>Session Flow</h3>\n\n<h4>1. Introduction (5 min)</h4>\n<p>Explain that participants will have two different AI experiences and should note the differences.</p>\n\n<h4>2. Activity: Raw vs Context-Enhanced AI (30 min)</h4>\n<p>The activity guides participants through both experiences. Allow time for both phases.</p>\n<p><strong>Facilitation tip:</strong> Don't reveal the difference upfront—let them discover it.</p>\n\n<h4>3. Debrief Discussion (10 min)</h4>\n<p><strong>Discussion prompts:</strong></p>\n<ul>\n<li>What differences did you notice?</li>\n<li>Which responses were more useful? Why?</li>\n<li>How might you apply context-enhancement in your work?</li>\n</ul>",
			'learner_overview' => "<h2>See the Difference Context Makes</h2>\n\n<p>In this hands-on session, you'll experience something most AI users never see: the dramatic difference between a raw language model and one enhanced with specific context and capabilities.</p>\n\n<h3>What You'll Do</h3>\n<p>You'll have two conversations with AI—and the difference will surprise you. This isn't theory; it's direct experience that will change how you think about AI tools.</p>\n\n<h3>Prerequisites</h3>\n<p>Basic familiarity with AI assistants is helpful but not required. If you've completed AI Fundamentals, you're well prepared.</p>",
			'chatbot_enabled' => true,
			'chatbot_model' => 'sonnet',
			'chatbot_system_prompt' => 'You are a helpful Q&A assistant for the AI in Practice course. Help learners understand the concepts of context-enhanced AI versus raw language models. Explain how context, system prompts, and skills can transform AI capabilities. Use concrete examples to illustrate your points.',
			'chatbot_context_files' => [ 2 ], // Sample Conversation Flows.
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
			// Activities (relationship).
			$activities_for_course = [];
			foreach ( $data['activities'] as $idx ) {
				if ( isset( $activity_ids[ $idx ] ) ) {
					$activities_for_course[] = $activity_ids[ $idx ];
				}
			}
			if ( ! empty( $activities_for_course ) ) {
				update_field( 'course_activities', $activities_for_course, $post_id );
			}

			// Course Settings.
			update_field( 'course_difficulty', $data['difficulty'], $post_id );
			update_field( 'course_total_duration', $data['total_duration'], $post_id );

			// Learning Objectives (repeater).
			if ( ! empty( $data['objectives'] ) ) {
				$objectives_data = [];
				foreach ( $data['objectives'] as $objective ) {
					$objectives_data[] = [ 'objective' => $objective ];
				}
				update_field( 'course_objectives', $objectives_data, $post_id );
			}

			// Facilitator Content.
			if ( ! empty( $data['facilitator_guide'] ) ) {
				update_field( 'course_facilitator_guide', $data['facilitator_guide'], $post_id );
			}

			// Learner Content.
			if ( ! empty( $data['learner_overview'] ) ) {
				update_field( 'course_learner_overview', $data['learner_overview'], $post_id );
			}

			// Course Q&A Chatbot.
			if ( ! empty( $data['chatbot_enabled'] ) ) {
				update_field( 'course_chatbot_enabled', 1, $post_id );
				update_field( 'course_chatbot_model', $data['chatbot_model'] ?? 'sonnet', $post_id );
				update_field( 'course_chatbot_system_prompt', $data['chatbot_system_prompt'] ?? '', $post_id );

				// Context files for course chatbot.
				if ( ! empty( $data['chatbot_context_files'] ) ) {
					$context_for_course = [];
					foreach ( $data['chatbot_context_files'] as $idx ) {
						if ( isset( $context_ids[ $idx ] ) ) {
							$context_for_course[] = $context_ids[ $idx ];
						}
					}
					if ( ! empty( $context_for_course ) ) {
						update_field( 'course_chatbot_context_files', $context_for_course, $post_id );
					}
				}
			}
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
