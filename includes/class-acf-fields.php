<?php
/**
 * ACF Field Groups registration.
 *
 * @package LeadersPath
 * @since   0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

/**
 * Registers all ACF field groups for LeadersPath CPTs.
 *
 * @since 0.1.0
 */
class ACF_Fields {

	/**
	 * Initialize the class.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'acf/include_fields', [ $this, 'register_field_groups' ] );
	}

	/**
	 * Register all ACF field groups.
	 *
	 * @since 0.1.0
	 */
	public function register_field_groups(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$this->register_activity_settings();
		$this->register_activity_chatbot();
		$this->register_lesson_settings();
		$this->register_course_settings();
		$this->register_context_settings();
		$this->register_skill_settings();

		// Cohort fields on WooCommerce products (only when WC is active).
		if ( class_exists( 'WooCommerce' ) ) {
			$this->register_cohort_settings();
		}
	}

	/**
	 * Register Activity Settings field group.
	 *
	 * @since 0.1.0
	 */
	private function register_activity_settings(): void {
		acf_add_local_field_group( [
			'key'      => 'group_activity_settings',
			'title'    => __( 'Activity Settings', 'leaderspath' ),
			'fields'   => [
				[
					'key'           => 'field_activity_duration',
					'label'         => __( 'Duration', 'leaderspath' ),
					'name'          => 'activity_duration',
					'type'          => 'number',
					'instructions'  => __( 'Estimated duration in minutes.', 'leaderspath' ),
					'required'      => 0,
					'min'           => 1,
					'max'           => 480,
					'step'          => 1,
					'append'        => __( 'minutes', 'leaderspath' ),
				],
				],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'leaderspath_activity',
					],
				],
			],
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		] );
	}

	/**
	 * Register Activity Chatbot Configuration field group.
	 *
	 * This configures the AI sandbox for the activity.
	 *
	 * @since 0.1.0
	 */
	private function register_activity_chatbot(): void {
		acf_add_local_field_group( [
			'key'      => 'group_activity_chatbot',
			'title'    => __( 'AI Sandbox Configuration', 'leaderspath' ),
			'fields'   => [
				[
					'key'           => 'field_chatbot_enabled',
					'label'         => __( 'Enable AI Sandbox', 'leaderspath' ),
					'name'          => 'chatbot_enabled',
					'type'          => 'true_false',
					'instructions'  => __( 'Enable the AI sandbox for this activity.', 'leaderspath' ),
					'default_value' => 1,
					'ui'            => 1,
				],
				[
					'key'           => 'field_chatbot_model',
					'label'         => __( 'Claude Model', 'leaderspath' ),
					'name'          => 'chatbot_model',
					'type'          => 'select',
					'instructions'  => __( 'Select the Claude model for this activity.', 'leaderspath' ),
					'required'      => 0,
					'choices'       => [
						'sonnet'    => __( 'Sonnet (Recommended)', 'leaderspath' ),
						'haiku'     => __( 'Haiku (Faster, Lower Cost)', 'leaderspath' ),
						'opus-4.5'  => __( 'Opus 4.5 (Most Capable)', 'leaderspath' ),
					],
					'default_value' => 'sonnet',
					'return_format' => 'value',
					'conditional_logic' => [
						[
							[
								'field'    => 'field_chatbot_enabled',
								'operator' => '==',
								'value'    => '1',
							],
						],
					],
				],
				[
					'key'           => 'field_chatbot_allow_model_switch',
					'label'         => __( 'Allow Model Switching', 'leaderspath' ),
					'name'          => 'chatbot_allow_model_switch',
					'type'          => 'true_false',
					'instructions'  => __( 'Allow learners to switch between Claude models.', 'leaderspath' ),
					'default_value' => 0,
					'ui'            => 1,
					'conditional_logic' => [
						[
							[
								'field'    => 'field_chatbot_enabled',
								'operator' => '==',
								'value'    => '1',
							],
						],
					],
				],
				[
					'key'           => 'field_chatbot_system_prompt',
					'label'         => __( 'System Prompt', 'leaderspath' ),
					'name'          => 'chatbot_system_prompt',
					'type'          => 'textarea',
					'instructions'  => __( 'Custom system prompt for this activity\'s AI sandbox. Defines the AI behavior learners will experience.', 'leaderspath' ),
					'rows'          => 6,
					'conditional_logic' => [
						[
							[
								'field'    => 'field_chatbot_enabled',
								'operator' => '==',
								'value'    => '1',
							],
						],
					],
				],
				[
					'key'           => 'field_chatbot_context_files',
					'label'         => __( 'Context Files', 'leaderspath' ),
					'name'          => 'chatbot_context_files',
					'type'          => 'relationship',
					'instructions'  => __( 'Select context files to include in the conversation.', 'leaderspath' ),
					'required'      => 0,
					'post_type'     => [ 'leaderspath_context' ],
					'filters'       => [ 'search' ],
					'elements'      => [ 'featured_image' ],
					'min'           => 0,
					'max'           => 20,
					'return_format' => 'id',
					'conditional_logic' => [
						[
							[
								'field'    => 'field_chatbot_enabled',
								'operator' => '==',
								'value'    => '1',
							],
						],
					],
				],
				[
					'key'           => 'field_chatbot_skills',
					'label'         => __( 'Skills', 'leaderspath' ),
					'name'          => 'chatbot_skills',
					'type'          => 'relationship',
					'instructions'  => __( 'Select skills available to the chatbot.', 'leaderspath' ),
					'required'      => 0,
					'post_type'     => [ 'leaderspath_skill' ],
					'filters'       => [ 'search' ],
					'elements'      => [],
					'min'           => 0,
					'max'           => 20,
					'return_format' => 'id',
					'conditional_logic' => [
						[
							[
								'field'    => 'field_chatbot_enabled',
								'operator' => '==',
								'value'    => '1',
							],
						],
					],
				],
				[
					'key'           => 'field_chatbot_max_tokens',
					'label'         => __( 'Max Response Tokens', 'leaderspath' ),
					'name'          => 'chatbot_max_tokens',
					'type'          => 'number',
					'instructions'  => __( 'Maximum tokens in Claude\'s response.', 'leaderspath' ),
					'default_value' => 4096,
					'min'           => 256,
					'max'           => 16384,
					'step'          => 256,
					'conditional_logic' => [
						[
							[
								'field'    => 'field_chatbot_enabled',
								'operator' => '==',
								'value'    => '1',
							],
						],
					],
				],
				[
					'key'           => 'field_chatbot_temperature',
					'label'         => __( 'Temperature', 'leaderspath' ),
					'name'          => 'chatbot_temperature',
					'type'          => 'number',
					'instructions'  => __( 'Controls randomness. Lower is more focused, higher is more creative.', 'leaderspath' ),
					'default_value' => 0.7,
					'min'           => 0,
					'max'           => 1,
					'step'          => 0.1,
					'conditional_logic' => [
						[
							[
								'field'    => 'field_chatbot_enabled',
								'operator' => '==',
								'value'    => '1',
							],
						],
					],
				],
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'leaderspath_activity',
					],
				],
			],
			'menu_order'            => 10,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		] );
	}

	/**
	 * Register Lesson Settings field group.
	 *
	 * Lessons are the atomic teaching unit in LeadersPath - taught as a cohesive
	 * whole by a facilitator with AI sandbox activities for hands-on experimentation.
	 *
	 * @since 0.3.0
	 */
	private function register_lesson_settings(): void {
		acf_add_local_field_group( [
			'key'      => 'group_lesson_settings',
			'title'    => __( 'Lesson Settings', 'leaderspath' ),
			'fields'   => [
				[
					'key'           => 'field_lesson_activities',
					'label'         => __( 'Activities', 'leaderspath' ),
					'name'          => 'lesson_activities',
					'type'          => 'relationship',
					'instructions'  => __( 'Select and order the activities in this lesson. Activities are AI sandbox experiments within the facilitated lesson.', 'leaderspath' ),
					'required'      => 0,
					'post_type'     => [ 'leaderspath_activity' ],
					'filters'       => [ 'search' ],
					'elements'      => [ 'featured_image' ],
					'min'           => 0,
					'max'           => 100,
					'return_format' => 'id',
				],
				[
					'key'          => 'field_lesson_total_duration',
					'label'        => __( 'Total Duration', 'leaderspath' ),
					'name'         => 'lesson_total_duration',
					'type'         => 'text',
					'instructions' => __( 'Total facilitation time (e.g., "90 minutes" or "2 hours").', 'leaderspath' ),
					'placeholder'  => __( '90 minutes', 'leaderspath' ),
				],
				[
					'key'           => 'field_lesson_objectives',
					'label'         => __( 'Learning Objectives', 'leaderspath' ),
					'name'          => 'lesson_objectives',
					'type'          => 'repeater',
					'instructions'  => __( 'What learners will achieve after completing this lesson.', 'leaderspath' ),
					'required'      => 0,
					'min'           => 0,
					'max'           => 10,
					'layout'        => 'table',
					'button_label'  => __( 'Add Objective', 'leaderspath' ),
					'sub_fields'    => [
						[
							'key'   => 'field_lesson_objective',
							'label' => __( 'Objective', 'leaderspath' ),
							'name'  => 'objective',
							'type'  => 'text',
						],
					],
				],
				[
					'key'           => 'field_lesson_references',
					'label'         => __( 'References', 'leaderspath' ),
					'name'          => 'lesson_references',
					'type'          => 'repeater',
					'instructions'  => __( 'External resources and reading materials for this lesson.', 'leaderspath' ),
					'required'      => 0,
					'min'           => 0,
					'max'           => 20,
					'layout'        => 'block',
					'button_label'  => __( 'Add Reference', 'leaderspath' ),
					'sub_fields'    => [
						[
							'key'      => 'field_lesson_reference_title',
							'label'    => __( 'Title', 'leaderspath' ),
							'name'     => 'title',
							'type'     => 'text',
							'required' => 1,
						],
						[
							'key'      => 'field_lesson_reference_url',
							'label'    => __( 'URL', 'leaderspath' ),
							'name'     => 'url',
							'type'     => 'url',
							'required' => 1,
						],
						[
							'key'   => 'field_lesson_reference_description',
							'label' => __( 'Description', 'leaderspath' ),
							'name'  => 'description',
							'type'  => 'textarea',
							'rows'  => 2,
						],
					],
				],
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'leaderspath_lesson',
					],
				],
			],
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		] );

		// Facilitator Content field group - for facilitator-only content.
		acf_add_local_field_group( [
			'key'      => 'group_lesson_facilitator',
			'title'    => __( 'Facilitator Content', 'leaderspath' ),
			'fields'   => [
				[
					'key'          => 'field_lesson_facilitator_guide',
					'label'        => __( 'Facilitator Guide', 'leaderspath' ),
					'name'         => 'lesson_facilitator_guide',
					'type'         => 'wysiwyg',
					'instructions' => __( 'Complete teaching script with timing, activity transitions, and discussion prompts. This is the primary teaching document for facilitators.', 'leaderspath' ),
					'tabs'         => 'all',
					'toolbar'      => 'full',
					'media_upload' => 1,
				],
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'leaderspath_lesson',
					],
				],
			],
			'menu_order'            => 5,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		] );

		// Learner Content field group - what learners see.
		acf_add_local_field_group( [
			'key'      => 'group_lesson_learner',
			'title'    => __( 'Learner Content', 'leaderspath' ),
			'fields'   => [
				[
					'key'          => 'field_lesson_learner_overview',
					'label'        => __( 'Learner Overview', 'leaderspath' ),
					'name'         => 'lesson_learner_overview',
					'type'         => 'wysiwyg',
					'instructions' => __( 'What learners will experience in this lesson. Context and framing, not teaching content (that comes from the facilitator).', 'leaderspath' ),
					'tabs'         => 'all',
					'toolbar'      => 'full',
					'media_upload' => 1,
				],
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'leaderspath_lesson',
					],
				],
			],
			'menu_order'            => 10,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		] );

		// Access Roles field group - sidebar metabox for access control.
		acf_add_local_field_group( [
			'key'      => 'group_lesson_access',
			'title'    => __( 'Access Roles', 'leaderspath' ),
			'fields'   => [
				[
					'key'           => 'field_lesson_access_roles',
					'label'         => __( 'Allowed Roles', 'leaderspath' ),
					'name'          => 'lesson_access_roles',
					'type'          => 'checkbox',
					'instructions'  => __( 'User roles that can access this lesson. Leave empty for public access.', 'leaderspath' ),
					'choices'       => [
						'leaderspath_student' => __( 'LeadersPath Student', 'leaderspath' ),
						'subscriber'          => __( 'Subscriber', 'leaderspath' ),
						'contributor'         => __( 'Contributor', 'leaderspath' ),
						'author'              => __( 'Author', 'leaderspath' ),
						'editor'              => __( 'Editor', 'leaderspath' ),
						'administrator'       => __( 'Administrator', 'leaderspath' ),
					],
					'return_format' => 'value',
					'layout'        => 'vertical',
				],
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'leaderspath_lesson',
					],
				],
			],
			'menu_order'            => 0,
			'position'              => 'side',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		] );

		// Lesson Q&A Chatbot Configuration - optional lesson-level Q&A assistant.
		acf_add_local_field_group( [
			'key'      => 'group_lesson_chatbot',
			'title'    => __( 'Lesson Q&A Chatbot', 'leaderspath' ),
			'fields'   => [
				[
					'key'           => 'field_lesson_chatbot_enabled',
					'label'         => __( 'Enable Q&A Chatbot', 'leaderspath' ),
					'name'          => 'lesson_chatbot_enabled',
					'type'          => 'true_false',
					'instructions'  => __( 'Enable a lesson-level Q&A chatbot for answering questions about lesson content. Unlike activity sandboxes (which demonstrate specific AI behaviors), this is a helpful assistant.', 'leaderspath' ),
					'default_value' => 0,
					'ui'            => 1,
				],
				[
					'key'           => 'field_lesson_chatbot_model',
					'label'         => __( 'Claude Model', 'leaderspath' ),
					'name'          => 'lesson_chatbot_model',
					'type'          => 'select',
					'instructions'  => __( 'Select the Claude model for the Q&A chatbot.', 'leaderspath' ),
					'required'      => 0,
					'choices'       => [
						'sonnet'    => __( 'Sonnet (Recommended)', 'leaderspath' ),
						'haiku'     => __( 'Haiku (Faster, Lower Cost)', 'leaderspath' ),
						'opus-4.5'  => __( 'Opus 4.5 (Most Capable)', 'leaderspath' ),
					],
					'default_value' => 'sonnet',
					'return_format' => 'value',
					'conditional_logic' => [
						[
							[
								'field'    => 'field_lesson_chatbot_enabled',
								'operator' => '==',
								'value'    => '1',
							],
						],
					],
				],
				[
					'key'           => 'field_lesson_chatbot_system_prompt',
					'label'         => __( 'System Prompt', 'leaderspath' ),
					'name'          => 'lesson_chatbot_system_prompt',
					'type'          => 'textarea',
					'instructions'  => __( 'Custom system prompt for the Q&A chatbot. Should be configured as a helpful, knowledgeable assistant.', 'leaderspath' ),
					'rows'          => 6,
					'conditional_logic' => [
						[
							[
								'field'    => 'field_lesson_chatbot_enabled',
								'operator' => '==',
								'value'    => '1',
							],
						],
					],
				],
				[
					'key'           => 'field_lesson_chatbot_context_files',
					'label'         => __( 'Context Files', 'leaderspath' ),
					'name'          => 'lesson_chatbot_context_files',
					'type'          => 'relationship',
					'instructions'  => __( 'Select context files for the Q&A chatbot. Typically includes all lesson content.', 'leaderspath' ),
					'required'      => 0,
					'post_type'     => [ 'leaderspath_context' ],
					'filters'       => [ 'search' ],
					'elements'      => [ 'featured_image' ],
					'min'           => 0,
					'max'           => 20,
					'return_format' => 'id',
					'conditional_logic' => [
						[
							[
								'field'    => 'field_lesson_chatbot_enabled',
								'operator' => '==',
								'value'    => '1',
							],
						],
					],
				],
				[
					'key'           => 'field_lesson_chatbot_max_tokens',
					'label'         => __( 'Max Response Tokens', 'leaderspath' ),
					'name'          => 'lesson_chatbot_max_tokens',
					'type'          => 'number',
					'instructions'  => __( 'Maximum tokens in Claude\'s response.', 'leaderspath' ),
					'default_value' => 4096,
					'min'           => 256,
					'max'           => 16384,
					'step'          => 256,
					'conditional_logic' => [
						[
							[
								'field'    => 'field_lesson_chatbot_enabled',
								'operator' => '==',
								'value'    => '1',
							],
						],
					],
				],
				[
					'key'           => 'field_lesson_chatbot_temperature',
					'label'         => __( 'Temperature', 'leaderspath' ),
					'name'          => 'lesson_chatbot_temperature',
					'type'          => 'number',
					'instructions'  => __( 'Controls randomness. Lower is more focused, higher is more creative.', 'leaderspath' ),
					'default_value' => 0.7,
					'min'           => 0,
					'max'           => 1,
					'step'          => 0.1,
					'conditional_logic' => [
						[
							[
								'field'    => 'field_lesson_chatbot_enabled',
								'operator' => '==',
								'value'    => '1',
							],
						],
					],
				],
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'leaderspath_lesson',
					],
				],
			],
			'menu_order'            => 15,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		] );
	}

	/**
	 * Register Course Settings field group.
	 *
	 * Courses are reusable curriculum structures containing Lessons.
	 *
	 * @since 0.3.0
	 */
	private function register_course_settings(): void {
		acf_add_local_field_group( [
			'key'      => 'group_course_settings',
			'title'    => __( 'Course Settings', 'leaderspath' ),
			'fields'   => [
				[
					'key'           => 'field_course_lessons',
					'label'         => __( 'Lessons', 'leaderspath' ),
					'name'          => 'course_lessons',
					'type'          => 'relationship',
					'instructions'  => __( 'Select and order the lessons in this course.', 'leaderspath' ),
					'required'      => 0,
					'post_type'     => [ 'leaderspath_lesson' ],
					'filters'       => [ 'search' ],
					'elements'      => [ 'featured_image' ],
					'min'           => 0,
					'max'           => 100,
					'return_format' => 'id',
				],
				[
					'key'           => 'field_course_prerequisites',
					'label'         => __( 'Prerequisites', 'leaderspath' ),
					'name'          => 'course_prerequisites',
					'type'          => 'relationship',
					'instructions'  => __( 'Courses that should be completed before this one.', 'leaderspath' ),
					'required'      => 0,
					'post_type'     => [ 'leaderspath_course' ],
					'filters'       => [ 'search' ],
					'elements'      => [ 'featured_image' ],
					'min'           => 0,
					'max'           => 10,
					'return_format' => 'id',
				],
				],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'leaderspath_course',
					],
				],
			],
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		] );
	}

	/**
	 * Register Context File Settings field group.
	 *
	 * @since 0.1.0
	 */
	private function register_context_settings(): void {
		acf_add_local_field_group( [
			'key'      => 'group_context_settings',
			'title'    => __( 'Context File Settings', 'leaderspath' ),
			'fields'   => [
				[
					'key'          => 'field_context_description',
					'label'        => __( 'Description', 'leaderspath' ),
					'name'         => 'context_description',
					'type'         => 'textarea',
					'instructions' => __( 'Purpose and usage notes for this context file.', 'leaderspath' ),
					'rows'         => 3,
				],
				[
					'key'          => 'field_context_version',
					'label'        => __( 'Version', 'leaderspath' ),
					'name'         => 'context_version',
					'type'         => 'text',
					'instructions' => __( 'Semantic version (e.g., 1.0.0).', 'leaderspath' ),
					'placeholder'  => '1.0.0',
				],
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'leaderspath_context',
					],
				],
			],
			'menu_order'            => 0,
			'position'              => 'side',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		] );
	}

	/**
	 * Register Skill Settings field group.
	 *
	 * Skills are ZIP packages containing SKILL.md, references/, scripts/, and assets/.
	 * The name and description are extracted from SKILL.md YAML frontmatter on upload.
	 *
	 * @since 0.1.0
	 */
	private function register_skill_settings(): void {
		acf_add_local_field_group( [
			'key'      => 'group_skill_settings',
			'title'    => __( 'Skill Package', 'leaderspath' ),
			'fields'   => [
				[
					'key'          => 'field_skill_package',
					'label'        => __( 'Skill Package (ZIP)', 'leaderspath' ),
					'name'         => 'skill_package',
					'type'         => 'file',
					'instructions' => __( 'Upload a skill package ZIP file. Must contain a SKILL.md file with YAML frontmatter (name, description). See Agent Skills spec for structure.', 'leaderspath' ),
					'required'     => 1,
					'return_format' => 'id',
					'library'      => 'all',
					'mime_types'   => 'zip',
				],
				[
					'key'          => 'field_skill_name',
					'label'        => __( 'Skill Name', 'leaderspath' ),
					'name'         => 'skill_name',
					'type'         => 'text',
					'instructions' => __( 'Extracted from SKILL.md frontmatter. Used for skill identification.', 'leaderspath' ),
					'readonly'     => 1,
				],
				[
					'key'          => 'field_skill_description',
					'label'        => __( 'Description', 'leaderspath' ),
					'name'         => 'skill_description',
					'type'         => 'textarea',
					'instructions' => __( 'Extracted from SKILL.md frontmatter. Describes what the skill does and when to use it.', 'leaderspath' ),
					'rows'         => 4,
					'readonly'     => 1,
				],
				[
					'key'          => 'field_skill_compatibility',
					'label'        => __( 'Compatibility', 'leaderspath' ),
					'name'         => 'skill_compatibility',
					'type'         => 'text',
					'instructions' => __( 'Environment prerequisites (e.g., "Requires Python 3.9+"). Extracted from frontmatter if present.', 'leaderspath' ),
					'readonly'     => 1,
				],
				[
					'key'          => 'field_skill_version',
					'label'        => __( 'Version', 'leaderspath' ),
					'name'         => 'skill_version',
					'type'         => 'text',
					'instructions' => __( 'Semantic version for this skill package (e.g., 1.0.0).', 'leaderspath' ),
					'placeholder'  => '1.0.0',
				],
				[
					'key'          => 'field_skill_notes',
					'label'        => __( 'Admin Notes', 'leaderspath' ),
					'name'         => 'skill_notes',
					'type'         => 'wysiwyg',
					'instructions' => __( 'Internal notes about this skill (not shown to learners).', 'leaderspath' ),
					'tabs'         => 'all',
					'toolbar'      => 'basic',
					'media_upload' => 0,
				],
				// Anthropic API Sync Fields.
				[
					'key'          => 'field_skill_anthropic_id',
					'label'        => __( 'Anthropic Skill ID', 'leaderspath' ),
					'name'         => 'skill_anthropic_id',
					'type'         => 'text',
					'instructions' => __( 'The skill_id returned from Anthropic Skills API. Auto-populated on upload.', 'leaderspath' ),
					'readonly'     => 1,
					'wrapper'      => [ 'class' => 'leaderspath-sync-field' ],
				],
				[
					'key'          => 'field_skill_anthropic_version',
					'label'        => __( 'Anthropic Version', 'leaderspath' ),
					'name'         => 'skill_anthropic_version',
					'type'         => 'text',
					'instructions' => __( 'The version timestamp from Anthropic. Auto-populated on upload.', 'leaderspath' ),
					'readonly'     => 1,
					'wrapper'      => [ 'class' => 'leaderspath-sync-field' ],
				],
				[
					'key'           => 'field_skill_sync_status',
					'label'         => __( 'Sync Status', 'leaderspath' ),
					'name'          => 'skill_sync_status',
					'type'          => 'select',
					'instructions'  => __( 'Current synchronization status with Anthropic Skills API.', 'leaderspath' ),
					'choices'       => [
						'pending' => __( 'Pending', 'leaderspath' ),
						'synced'  => __( 'Synced', 'leaderspath' ),
						'error'   => __( 'Error', 'leaderspath' ),
					],
					'default_value' => 'pending',
					'return_format' => 'value',
					'readonly'      => 1,
					'wrapper'       => [ 'class' => 'leaderspath-sync-field' ],
				],
				[
					'key'          => 'field_skill_sync_error',
					'label'        => __( 'Sync Error', 'leaderspath' ),
					'name'         => 'skill_sync_error',
					'type'         => 'textarea',
					'instructions' => __( 'Last error message from sync attempt (if any).', 'leaderspath' ),
					'rows'         => 2,
					'readonly'     => 1,
					'wrapper'      => [ 'class' => 'leaderspath-sync-field' ],
					'conditional_logic' => [
						[
							[
								'field'    => 'field_skill_sync_status',
								'operator' => '==',
								'value'    => 'error',
							],
						],
					],
				],
				[
					'key'          => 'field_skill_last_synced',
					'label'        => __( 'Last Synced', 'leaderspath' ),
					'name'         => 'skill_last_synced',
					'type'         => 'text',
					'instructions' => __( 'Timestamp of last successful sync to Anthropic.', 'leaderspath' ),
					'readonly'     => 1,
					'wrapper'      => [ 'class' => 'leaderspath-sync-field' ],
				],
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'leaderspath_skill',
					],
				],
			],
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		] );
	}

	/**
	 * Register Cohort Settings field group.
	 *
	 * Cohorts are WooCommerce products that grant learners access to Courses.
	 * Fields appear on the WooCommerce product edit screen.
	 *
	 * @since 0.4.0
	 */
	private function register_cohort_settings(): void {
		acf_add_local_field_group( [
			'key'      => 'group_cohort_settings',
			'title'    => __( 'Cohort Settings', 'leaderspath' ),
			'fields'   => [
				[
					'key'           => 'field_cohort_courses',
					'label'         => __( 'Courses', 'leaderspath' ),
					'name'          => 'cohort_courses',
					'type'          => 'relationship',
					'instructions'  => __( 'Select the courses included in this cohort. Enrolled learners will have access to all lessons and activities within these courses.', 'leaderspath' ),
					'required'      => 0,
					'post_type'     => [ 'leaderspath_course' ],
					'filters'       => [ 'search' ],
					'elements'      => [ 'featured_image' ],
					'min'           => 0,
					'max'           => 20,
					'return_format' => 'id',
				],
				[
					'key'           => 'field_cohort_start_date',
					'label'         => __( 'Start Date', 'leaderspath' ),
					'name'          => 'cohort_start_date',
					'type'          => 'date_picker',
					'instructions'  => __( 'When this cohort begins. Used to determine cohort phase (upcoming/active/completed).', 'leaderspath' ),
					'required'      => 0,
					'display_format' => 'F j, Y',
					'return_format' => 'Y-m-d',
					'first_day'     => 0,
				],
				[
					'key'           => 'field_cohort_end_date',
					'label'         => __( 'End Date', 'leaderspath' ),
					'name'          => 'cohort_end_date',
					'type'          => 'date_picker',
					'instructions'  => __( 'When this cohort ends. Enrolled learners retain access after completion for review.', 'leaderspath' ),
					'required'      => 0,
					'display_format' => 'F j, Y',
					'return_format' => 'Y-m-d',
					'first_day'     => 0,
				],
				[
					'key'           => 'field_cohort_facilitator',
					'label'         => __( 'Facilitator', 'leaderspath' ),
					'name'          => 'cohort_facilitator',
					'type'          => 'user',
					'instructions'  => __( 'The facilitator who will lead this cohort.', 'leaderspath' ),
					'required'      => 0,
					'role'          => [ 'leaderspath_facilitator', 'administrator' ],
					'return_format' => 'id',
					'multiple'      => 0,
					'allow_null'    => 1,
				],
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'product',
					],
				],
			],
			'menu_order'            => 50,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		] );
	}
}
