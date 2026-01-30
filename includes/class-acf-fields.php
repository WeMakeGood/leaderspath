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

		$this->register_lesson_settings();
		$this->register_lesson_chatbot();
		$this->register_course_settings();
		$this->register_cohort_settings();
		$this->register_context_settings();
		$this->register_skill_settings();
	}

	/**
	 * Register Lesson Settings field group.
	 *
	 * @since 0.1.0
	 */
	private function register_lesson_settings(): void {
		acf_add_local_field_group( [
			'key'      => 'group_lesson_settings',
			'title'    => __( 'Lesson Settings', 'leaderspath' ),
			'fields'   => [
				[
					'key'           => 'field_lesson_duration',
					'label'         => __( 'Duration', 'leaderspath' ),
					'name'          => 'lesson_duration',
					'type'          => 'number',
					'instructions'  => __( 'Estimated duration in minutes.', 'leaderspath' ),
					'required'      => 0,
					'min'           => 1,
					'max'           => 480,
					'step'          => 1,
					'append'        => __( 'minutes', 'leaderspath' ),
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
					'key'           => 'field_lesson_prerequisites',
					'label'         => __( 'Prerequisites', 'leaderspath' ),
					'name'          => 'lesson_prerequisites',
					'type'          => 'relationship',
					'instructions'  => __( 'Lessons that should be completed before this one.', 'leaderspath' ),
					'required'      => 0,
					'post_type'     => [ 'leaderspath_lesson' ],
					'filters'       => [ 'search' ],
					'elements'      => [],
					'min'           => 0,
					'max'           => 10,
					'return_format' => 'id',
				],
				[
					'key'           => 'field_lesson_references',
					'label'         => __( 'References', 'leaderspath' ),
					'name'          => 'lesson_references',
					'type'          => 'repeater',
					'instructions'  => __( 'External resources and reading materials.', 'leaderspath' ),
					'required'      => 0,
					'min'           => 0,
					'max'           => 20,
					'layout'        => 'block',
					'button_label'  => __( 'Add Reference', 'leaderspath' ),
					'sub_fields'    => [
						[
							'key'      => 'field_reference_title',
							'label'    => __( 'Title', 'leaderspath' ),
							'name'     => 'title',
							'type'     => 'text',
							'required' => 1,
						],
						[
							'key'      => 'field_reference_url',
							'label'    => __( 'URL', 'leaderspath' ),
							'name'     => 'url',
							'type'     => 'url',
							'required' => 1,
						],
						[
							'key'   => 'field_reference_description',
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
	}

	/**
	 * Register Lesson Chatbot Configuration field group.
	 *
	 * @since 0.1.0
	 */
	private function register_lesson_chatbot(): void {
		acf_add_local_field_group( [
			'key'      => 'group_lesson_chatbot',
			'title'    => __( 'Chatbot Configuration', 'leaderspath' ),
			'fields'   => [
				[
					'key'           => 'field_chatbot_enabled',
					'label'         => __( 'Enable Chatbot', 'leaderspath' ),
					'name'          => 'chatbot_enabled',
					'type'          => 'true_false',
					'instructions'  => __( 'Enable the AI chatbot for this lesson.', 'leaderspath' ),
					'default_value' => 1,
					'ui'            => 1,
				],
				[
					'key'           => 'field_chatbot_model',
					'label'         => __( 'Claude Model', 'leaderspath' ),
					'name'          => 'chatbot_model',
					'type'          => 'select',
					'instructions'  => __( 'Select the Claude model for this lesson.', 'leaderspath' ),
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
					'instructions'  => __( 'Custom system prompt for this lesson. Leave blank to use default.', 'leaderspath' ),
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
	}

	/**
	 * Register Course Settings field group.
	 *
	 * @since 0.1.0
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
					'filters'       => [ 'search', 'taxonomy' ],
					'taxonomy'      => [ 'leaderspath_topic' ],
					'elements'      => [ 'featured_image' ],
					'min'           => 0,
					'max'           => 100,
					'return_format' => 'id',
				],
				[
					'key'           => 'field_course_difficulty',
					'label'         => __( 'Difficulty Level', 'leaderspath' ),
					'name'          => 'course_difficulty',
					'type'          => 'select',
					'instructions'  => __( 'Target skill level for this course.', 'leaderspath' ),
					'choices'       => [
						'beginner'     => __( 'Beginner', 'leaderspath' ),
						'intermediate' => __( 'Intermediate', 'leaderspath' ),
						'advanced'     => __( 'Advanced', 'leaderspath' ),
					],
					'default_value' => 'beginner',
					'return_format' => 'value',
				],
				[
					'key'          => 'field_course_total_duration',
					'label'        => __( 'Total Duration', 'leaderspath' ),
					'name'         => 'course_total_duration',
					'type'         => 'number',
					'instructions' => __( 'Total estimated duration in minutes. Auto-calculated from lessons.', 'leaderspath' ),
					'readonly'     => 1,
					'append'       => __( 'minutes', 'leaderspath' ),
				],
				[
					'key'           => 'field_course_access_roles',
					'label'         => __( 'Access Roles', 'leaderspath' ),
					'name'          => 'course_access_roles',
					'type'          => 'checkbox',
					'instructions'  => __( 'User roles that can access this course. Leave empty for public access.', 'leaderspath' ),
					'choices'       => [
						'leaderspath_student' => __( 'LeadersPath Student', 'leaderspath' ),
						'subscriber'          => __( 'Subscriber', 'leaderspath' ),
						'contributor'         => __( 'Contributor', 'leaderspath' ),
						'author'              => __( 'Author', 'leaderspath' ),
						'editor'              => __( 'Editor', 'leaderspath' ),
						'administrator'       => __( 'Administrator', 'leaderspath' ),
					],
					'return_format' => 'value',
					'layout'        => 'horizontal',
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
	 * Register Cohort Settings field group.
	 *
	 * @since 0.1.0
	 */
	private function register_cohort_settings(): void {
		acf_add_local_field_group( [
			'key'      => 'group_cohort_settings',
			'title'    => __( 'Cohort Settings', 'leaderspath' ),
			'fields'   => [
				[
					'key'           => 'field_cohort_course',
					'label'         => __( 'Course', 'leaderspath' ),
					'name'          => 'cohort_course',
					'type'          => 'post_object',
					'instructions'  => __( 'The course this cohort will complete.', 'leaderspath' ),
					'required'      => 1,
					'post_type'     => [ 'leaderspath_course' ],
					'return_format' => 'id',
					'ui'            => 1,
				],
				[
					'key'           => 'field_cohort_status',
					'label'         => __( 'Status', 'leaderspath' ),
					'name'          => 'cohort_status',
					'type'          => 'select',
					'instructions'  => __( 'Current status of this cohort.', 'leaderspath' ),
					'required'      => 1,
					'choices'       => [
						'upcoming'  => __( 'Upcoming', 'leaderspath' ),
						'active'    => __( 'Active', 'leaderspath' ),
						'completed' => __( 'Completed', 'leaderspath' ),
						'cancelled' => __( 'Cancelled', 'leaderspath' ),
					],
					'default_value' => 'upcoming',
					'return_format' => 'value',
				],
				[
					'key'           => 'field_cohort_start_date',
					'label'         => __( 'Start Date', 'leaderspath' ),
					'name'          => 'cohort_start_date',
					'type'          => 'date_picker',
					'instructions'  => __( 'When this cohort begins.', 'leaderspath' ),
					'required'      => 1,
					'display_format' => 'F j, Y',
					'return_format' => 'Y-m-d',
				],
				[
					'key'           => 'field_cohort_end_date',
					'label'         => __( 'End Date', 'leaderspath' ),
					'name'          => 'cohort_end_date',
					'type'          => 'date_picker',
					'instructions'  => __( 'When this cohort ends.', 'leaderspath' ),
					'required'      => 1,
					'display_format' => 'F j, Y',
					'return_format' => 'Y-m-d',
				],
				[
					'key'           => 'field_cohort_instructor',
					'label'         => __( 'Instructor', 'leaderspath' ),
					'name'          => 'cohort_instructor',
					'type'          => 'user',
					'instructions'  => __( 'The facilitator for this cohort.', 'leaderspath' ),
					'required'      => 0,
					'role'          => [ 'administrator', 'editor', 'author' ],
					'return_format' => 'id',
				],
				[
					'key'           => 'field_cohort_language',
					'label'         => __( 'Language', 'leaderspath' ),
					'name'          => 'cohort_language',
					'type'          => 'select',
					'instructions'  => __( 'Primary language for this cohort.', 'leaderspath' ),
					'choices'       => [
						'en' => __( 'English', 'leaderspath' ),
						'es' => __( 'Spanish', 'leaderspath' ),
						'fr' => __( 'French', 'leaderspath' ),
						'de' => __( 'German', 'leaderspath' ),
						'pt' => __( 'Portuguese', 'leaderspath' ),
					],
					'default_value' => 'en',
					'return_format' => 'value',
				],
				[
					'key'           => 'field_cohort_timezone',
					'label'         => __( 'Timezone', 'leaderspath' ),
					'name'          => 'cohort_timezone',
					'type'          => 'select',
					'instructions'  => __( 'Timezone for scheduling.', 'leaderspath' ),
					'choices'       => $this->get_timezone_choices(),
					'default_value' => 'America/New_York',
					'return_format' => 'value',
				],
				[
					'key'           => 'field_cohort_max_participants',
					'label'         => __( 'Max Participants', 'leaderspath' ),
					'name'          => 'cohort_max_participants',
					'type'          => 'number',
					'instructions'  => __( 'Maximum number of participants. Leave empty for unlimited.', 'leaderspath' ),
					'min'           => 1,
					'max'           => 1000,
				],
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'leaderspath_cohort',
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
					'key'           => 'field_context_file_type',
					'label'         => __( 'File Type', 'leaderspath' ),
					'name'          => 'context_file_type',
					'type'          => 'select',
					'instructions'  => __( 'What type of content this file contains.', 'leaderspath' ),
					'choices'       => [
						'system_prompt'   => __( 'System Prompt', 'leaderspath' ),
						'knowledge_base'  => __( 'Knowledge Base', 'leaderspath' ),
						'instructions'    => __( 'Instructions', 'leaderspath' ),
						'examples'        => __( 'Examples', 'leaderspath' ),
						'other'           => __( 'Other', 'leaderspath' ),
					],
					'default_value' => 'knowledge_base',
					'return_format' => 'value',
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
	 * Get timezone choices for select field.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, string> Timezone choices.
	 */
	private function get_timezone_choices(): array {
		return [
			'America/New_York'    => __( 'Eastern Time (US)', 'leaderspath' ),
			'America/Chicago'     => __( 'Central Time (US)', 'leaderspath' ),
			'America/Denver'      => __( 'Mountain Time (US)', 'leaderspath' ),
			'America/Los_Angeles' => __( 'Pacific Time (US)', 'leaderspath' ),
			'America/Anchorage'   => __( 'Alaska Time', 'leaderspath' ),
			'Pacific/Honolulu'    => __( 'Hawaii Time', 'leaderspath' ),
			'Europe/London'       => __( 'London (GMT)', 'leaderspath' ),
			'Europe/Paris'        => __( 'Central European', 'leaderspath' ),
			'Europe/Berlin'       => __( 'Berlin', 'leaderspath' ),
			'Asia/Tokyo'          => __( 'Tokyo', 'leaderspath' ),
			'Asia/Shanghai'       => __( 'China Standard', 'leaderspath' ),
			'Asia/Dubai'          => __( 'Dubai', 'leaderspath' ),
			'Australia/Sydney'    => __( 'Sydney', 'leaderspath' ),
			'UTC'                 => __( 'UTC', 'leaderspath' ),
		];
	}
}
