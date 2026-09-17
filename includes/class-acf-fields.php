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

		// Scope the cohort "current lesson" picker to lessons in the cohort's
		// selected courses (cohort → cohort_courses → course_lessons), rather
		// than every lesson in the system.
		add_filter(
			'acf/fields/post_object/query/key=field_cohort_current_lesson',
			[ $this, 'filter_current_lesson_choices' ],
			10,
			3
		);

		// Scope a facilitator's Context File "Cohort" picker to the cohort(s)
		// they actually facilitate — they should never see, let alone assign,
		// a cohort they don't run. Admins/editors see every cohort product.
		add_filter(
			'acf/fields/post_object/query/key=field_context_cohort',
			[ $this, 'filter_context_cohort_choices' ],
			10,
			3
		);

		// Hard gate: a facilitator's context file must belong to a cohort they
		// facilitate. Runs regardless of what the picker showed, so a crafted
		// request can't slip an unscoped or another-cohort's value through.
		add_filter(
			'acf/validate_value/key=field_context_cohort',
			[ $this, 'validate_context_cohort' ],
			10,
			4
		);

		// Renders the Cohort "Source Record" field as a real button linking
		// to cohort_source_url post meta, rather than a plain readonly ACF
		// field value — see docs/TASKS.md Phase 14.
		add_action(
			'acf/render_field/key=field_cohort_instance_source_link',
			[ $this, 'render_cohort_source_link_button' ]
		);
	}

	/**
	 * Render the Cohort "Source Record" button.
	 *
	 * Reads `cohort_source_url` from plain post meta (set by whichever
	 * commerce-backend caller created the cohort — `WooCommerce` or
	 * `WS_Form_Integration` — via `update_post_meta()`, not ACF) and, if
	 * present, renders it as a real button-styled link. Renders nothing if
	 * no source URL was recorded (e.g. a cohort created via WP-CLI with no
	 * originating record).
	 *
	 * @since 0.7.0
	 *
	 * @param array $field The ACF field array being rendered.
	 */
	public function render_cohort_source_link_button( array $field ): void {
		// acf_get_form_data('post_id') is ACF's own real API for the post
		// being edited during a render_field call — the $field array
		// itself carries no post-ID key (confirmed against ACF Pro core;
		// no such key exists on it).
		$post_id = (int) acf_get_form_data( 'post_id' );
		$url     = $post_id ? get_post_meta( $post_id, 'cohort_source_url', true ) : '';

		if ( ! $url ) {
			echo '<p>' . esc_html__( 'No source record on file.', 'leaderspath' ) . '</p>';
			return;
		}

		printf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer" class="button">%2$s</a>',
			esc_url( $url ),
			esc_html__( 'View Source Record', 'leaderspath' )
		);
	}

	/**
	 * Restrict the cohort `current_lesson` picker to lessons belonging to the
	 * cohort's selected courses.
	 *
	 * If the cohort has no courses (or its courses have no lessons), returns an
	 * empty result set — the picker shows nothing, signalling "select courses
	 * first" rather than silently offering every lesson.
	 *
	 * @since 0.7.0
	 *
	 * @param array      $args    WP_Query args ACF will run.
	 * @param array      $field   The field being queried.
	 * @param int|string $post_id The post being edited (the leaderspath_cohort instance).
	 * @return array Modified query args.
	 */
	public function filter_current_lesson_choices( array $args, array $field, $post_id ): array {
		// $post_id is normally a plain int on a regular CPT; the non-numeric
		// fallback is defensive left-over from when this field lived on the
		// WC product (some WC/ACF contexts prefix it, e.g. "product_123").
		$cohort_id = is_numeric( $post_id ) ? (int) $post_id : (int) preg_replace( '/[^0-9]/', '', (string) $post_id );

		// Enrollment::get_cohort_lessons() reads ACF fields directly — no
		// commerce-backend dependency, consistent with cohorts being their
		// own CPT now.
		$lesson_ids = $cohort_id ? Enrollment::get_cohort_lessons( $cohort_id ) : [];

		// Empty set → force no matches (0 is never a valid post ID) so an
		// unconfigured cohort shows an empty picker instead of all lessons.
		$args['post__in'] = ! empty( $lesson_ids ) ? $lesson_ids : [ 0 ];

		return $args;
	}

	/**
	 * Restrict the Context File "Cohort" picker to cohort products only, and
	 * for facilitators, to only the cohort(s) they facilitate.
	 *
	 * Admins and editors (anyone who can edit others' posts) see every cohort
	 * product — they're the ones who create shared/public content and may
	 * need to reassign a file to any cohort. Facilitators only ever see their
	 * own, so the field can't even display a choice validate_context_cohort()
	 * would reject.
	 *
	 * @since 0.7.0
	 *
	 * @param array      $args  WP_Query args ACF will run.
	 * @param array      $field The field being queried.
	 * @param int|string $post_id The post being edited.
	 * @return array Modified query args.
	 */
	public function filter_context_cohort_choices( array $args, array $field, $post_id ): array {
		// Cohorts are their own CPT (a purchase-time instance), not the WC
		// product — every leaderspath_cohort post is a cohort by
		// construction, so no _cohort meta flag is needed here.
		$args['post_type'] = 'leaderspath_cohort';

		if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_others_posts' ) ) {
			return $args;
		}

		$cohort_ids = Enrollment::get_facilitator_cohorts( get_current_user_id() );

		// No assigned cohort → empty picker, not "every cohort" (0 never matches).
		$args['post__in'] = ! empty( $cohort_ids ) ? $cohort_ids : [ 0 ];

		return $args;
	}

	/**
	 * Enforce that a facilitator's context file is scoped to a cohort they
	 * actually facilitate — required, not optional, and not just any cohort.
	 *
	 * Only "public" content (an empty Cohort field) may skip this, and only
	 * admins/editors are allowed to leave it empty or assign across cohorts;
	 * facilitators can only create/edit cohort-owned files for their own
	 * cohort(s). Runs on every save regardless of what the field's own query
	 * filter showed, so this is the authoritative check, not the UI narrowing.
	 *
	 * @since 0.7.0
	 *
	 * @param bool|string $valid   Current validation result (true, or an error message).
	 * @param mixed       $value   Submitted field value (cohort post ID, or empty).
	 * @param array       $field   The field being validated.
	 * @param string      $input   The input's HTML name attribute.
	 * @return bool|string True if valid, or an error message string.
	 */
	public function validate_context_cohort( $valid, $value, array $field, string $input ) {
		// Already invalid for another reason — don't pile on.
		if ( true !== $valid ) {
			return $valid;
		}

		// Admins/editors may leave it empty (public content) or assign any cohort.
		if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_others_posts' ) ) {
			return $valid;
		}

		// Anyone else editing a Context File is treated as a facilitator: a
		// cohort is required, and it must be one they actually facilitate.
		if ( empty( $value ) ) {
			return __( 'A cohort is required. Only Make Good staff can create shared/public context files.', 'leaderspath' );
		}

		$cohort_ids = Enrollment::get_facilitator_cohorts( get_current_user_id() );

		if ( ! in_array( (int) $value, $cohort_ids, true ) ) {
			return __( 'You can only assign context files to a cohort you facilitate.', 'leaderspath' );
		}

		return $valid;
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
		$this->register_cohort_instance_settings();

		// Cohort *catalog* fields on WooCommerce products (only when WC is
		// active). Deliberately separate from register_cohort_instance_settings()
		// above — the product is the reusable offering, the CPT is the
		// purchase-time instance. See class-post-types.php register_cohort()
		// for the full "cohort is a purchase-time instance, not the product"
		// rationale.
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
					'key'           => 'field_activity_instructions',
					'label'         => __( 'Learner Instructions', 'leaderspath' ),
					'name'          => 'activity_instructions',
					'type'          => 'wysiwyg',
					// Learner-facing, not model-facing. The plugin renders this as the
					// opening message in the chat and the lesson stepper shows the same
					// text. It is never sent to the AI — that is the System Prompt below.
					'instructions'  => __( 'Shown to the learner as the opening chat message and in the lesson stepper. Tells the participant what to try in this sandbox. NOT sent to the AI — see System Prompt below for that.', 'leaderspath' ),
					'tabs'          => 'all',
					'toolbar'       => 'basic',
					'media_upload'  => 0,
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
					'instructions'  => __( 'Custom system prompt for this activity\'s AI sandbox. Defines the AI behavior learners will experience. Sent to the AI — not shown to the learner (that is Learner Instructions above).', 'leaderspath' ),
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
					'instructions'  => __( 'Per-response token ceiling. Responses stream and auto-continue if they hit this, so long outputs (e.g. reports) still finish — this is a budget, not a hard wall. Default 16384. Higher = fewer continuation round-trips.', 'leaderspath' ),
					'default_value' => 16384,
					'min'           => 256,
					'max'           => 64000,
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
					'instructions'  => __( 'Per-response token ceiling. Responses stream and auto-continue if they hit this, so long outputs still finish — this is a budget, not a hard wall. Default 16384.', 'leaderspath' ),
					'default_value' => 16384,
					'min'           => 256,
					'max'           => 64000,
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
				[
					'key'           => 'field_context_cohort',
					'label'         => __( 'Cohort', 'leaderspath' ),
					'name'          => 'context_cohort',
					'type'          => 'post_object',
					'instructions'  => __( 'Leave empty for shared curriculum content available to every cohort. Set a cohort to scope this file to that organization only — required for anything a facilitator creates or edits.', 'leaderspath' ),
					'required'      => 0,
					'post_type'     => [ 'leaderspath_cohort' ],
					'return_format' => 'id',
					'multiple'      => 0,
					'allow_null'    => 1,
					'ui'            => 1,
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
					'instructions'  => __( 'Courses this cohort package includes. Copied onto each purchased cohort instance at creation — see the Cohort post type for the instance-level copy, which stays independently editable per cohort afterward.', 'leaderspath' ),
					'required'      => 0,
					'post_type'     => [ 'leaderspath_course' ],
					'filters'       => [ 'search' ],
					'elements'      => [ 'featured_image' ],
					'min'           => 0,
					'max'           => 20,
					'return_format' => 'id',
				],
				[
					'key'           => 'field_cohort_requested_start_date',
					'label'         => __( 'Requested Start Date', 'leaderspath' ),
					'name'          => 'cohort_requested_start_date',
					'type'          => 'date_picker',
					// Checkout-time intake only — the org's stated intent when
					// buying, before a facilitator is even assigned. Seeds the
					// new cohort instance's own start/end dates at creation;
					// never written back to from the instance afterward. Real
					// scheduling lives on the Cohort CPT, not here.
					'instructions'  => __( 'Optional. Captured at checkout as the organization\'s intent — not authoritative scheduling. Seeds the new cohort\'s start date once created; adjust the actual dates on the Cohort itself afterward.', 'leaderspath' ),
					'required'      => 0,
					'display_format' => 'F j, Y',
					'return_format' => 'Y-m-d',
					'first_day'     => 0,
				],
				[
					'key'           => 'field_cohort_video',
					'label'         => __( 'Cohort Video', 'leaderspath' ),
					'name'          => 'cohort_video',
					'type'          => 'url',
					'instructions'  => __( 'Optional intro or welcome video for the cohort sales page (e.g. a YouTube URL). Shown when set.', 'leaderspath' ),
					'required'      => 0,
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

	/**
	 * Register Cohort (purchase-time instance) field groups.
	 *
	 * Lives on the `leaderspath_cohort` CPT, not the WC product — see
	 * Post_Types::register_cohort() for the full rationale. `cohort_courses`,
	 * `cohort_start_date`, `cohort_end_date`, `cohort_facilitator`,
	 * `current_lesson` moved here from the product's field group (0.7.0);
	 * `cohort_owner`, `cohort_payment_status`, `cohort_access_closed`, and
	 * `cohort_source_record_id`/`cohort_source_url` are new.
	 *
	 * Split into four groups by concern rather than one flat list, matching
	 * this plugin's existing convention (Activity separates Settings from
	 * Chatbot, Skill visually separates its sync fields) — grouped by what a
	 * facilitator is actually doing when they look at each: who's involved,
	 * when it runs, what curriculum it covers, and its current status. The
	 * status group sits in the sidebar (`position: side`) — glanceable
	 * state, same treatment WordPress gives the Publish/status boxes,
	 * deliberately separate from content a facilitator edits routinely.
	 *
	 * Not gated on `class_exists('WooCommerce')` — a cohort instance should
	 * be usable regardless of which commerce backend (if any) created it.
	 *
	 * @since 0.7.0
	 */
	private function register_cohort_instance_settings(): void {
		acf_add_local_field_group( [
			'key'      => 'group_cohort_instance_details',
			'title'    => __( 'Cohort Details', 'leaderspath' ),
			'fields'   => [
				[
					'key'           => 'field_cohort_instance_org_name',
					'label'         => __( 'Organization', 'leaderspath' ),
					'name'          => 'cohort_org_name',
					'type'          => 'text',
					'instructions'  => __( 'The organization this cohort is for. For a mixed-organization cohort, leave blank or describe the group instead.', 'leaderspath' ),
					'required'      => 0,
				],
				[
					'key'           => 'field_cohort_instance_owner',
					'label'         => __( 'Owner', 'leaderspath' ),
					'name'          => 'cohort_owner',
					'type'          => 'user',
					// Roster-management authority — who can invite/revoke seats.
					// Defaults at creation per product type (single-org: the
					// purchaser, who also fills a seat; mixed-org: the
					// facilitator, who does not) but is reassignable afterward,
					// e.g. if the buyer isn't the day-to-day contact.
					'instructions'  => __( 'Has authority to invite/revoke roster seats. Defaults to the purchaser (single-org cohorts) or the facilitator (mixed-organization cohorts) at creation; reassignable afterward.', 'leaderspath' ),
					'required'      => 0,
					'role'          => '', // Any role — a purchaser may hold no LeadersPath-specific role at all.
					'return_format' => 'id',
					'multiple'      => 0,
					'allow_null'    => 1,
				],
				[
					'key'           => 'field_cohort_instance_facilitator',
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

		acf_add_local_field_group( [
			'key'      => 'group_cohort_instance_scheduling',
			'title'    => __( 'Scheduling', 'leaderspath' ),
			'fields'   => [
				[
					'key'           => 'field_cohort_instance_start_date',
					'label'         => __( 'Start Date', 'leaderspath' ),
					'name'          => 'cohort_start_date',
					'type'          => 'date_picker',
					'instructions'  => __( 'When this cohort begins. Used to determine cohort phase (upcoming/active/completed). Seeded from the product\'s Requested Start Date at creation; adjust freely afterward for real scheduling.', 'leaderspath' ),
					'required'      => 0,
					'display_format' => 'F j, Y',
					'return_format' => 'Y-m-d',
					'first_day'     => 0,
				],
				[
					'key'           => 'field_cohort_instance_end_date',
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
					'key'           => 'field_cohort_instance_current_lesson',
					'label'         => __( 'Current Lesson (This Week)', 'leaderspath' ),
					'name'          => 'current_lesson',
					'type'          => 'post_object',
					// Facilitator-set pointer to the lesson currently in session. Drives
					// the "this week" badge on the dashboard and lesson page. Single value.
					// Grouped with Scheduling, not Curriculum: it's a week-to-week
					// facilitator action (like the dates), not a one-time content
					// selection (like the courses list).
					'instructions'  => __( 'The lesson currently in session. Drives the "this week" badge on the dashboard and lesson page. Set before each session.', 'leaderspath' ),
					'required'      => 0,
					'post_type'     => [ 'leaderspath_lesson' ],
					'return_format' => 'id',
					'multiple'      => 0,
					'allow_null'    => 1,
					'ui'            => 1,
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
			'menu_order'            => 1,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		] );

		acf_add_local_field_group( [
			'key'      => 'group_cohort_instance_curriculum',
			'title'    => __( 'Curriculum', 'leaderspath' ),
			'fields'   => [
				[
					'key'           => 'field_cohort_instance_courses',
					'label'         => __( 'Courses', 'leaderspath' ),
					'name'          => 'cohort_courses',
					'type'          => 'relationship',
					'instructions'  => __( 'Courses included in this cohort. Copied from the purchased product at creation; editable afterward for an organization-specific adjustment.', 'leaderspath' ),
					'required'      => 0,
					'post_type'     => [ 'leaderspath_course' ],
					'filters'       => [ 'search' ],
					'elements'      => [ 'featured_image' ],
					'min'           => 0,
					'max'           => 20,
					'return_format' => 'id',
				],
				[
					'key'           => 'field_cohort_instance_seats',
					'label'         => __( 'Seats', 'leaderspath' ),
					'name'          => 'cohort_seats',
					'type'          => 'number',
					'instructions'  => __( 'Total seats for this cohort, from the purchased product/variation. Caps roster invites — see the Roster box below.', 'leaderspath' ),
					'required'      => 0,
					'min'           => 1,
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
			'menu_order'            => 2,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		] );

		acf_add_local_field_group( [
			'key'      => 'group_cohort_instance_status',
			'title'    => __( 'Status', 'leaderspath' ),
			'fields'   => [
				[
					'key'           => 'field_cohort_instance_payment_status',
					'label'         => __( 'Payment Status', 'leaderspath' ),
					'name'          => 'cohort_payment_status',
					'type'          => 'select',
					// Separate axis from get_cohort_phase()'s pedagogical phase
					// (upcoming/active/completed) — this tracks commitment/
					// payment, not the curriculum timeline. Updated as the
					// underlying order progresses, not written once and left
					// stale. Gates chatbot/sandbox access (requires 'paid');
					// facilitator assignment, roster invites, and curriculum
					// visibility are allowed on 'pending_payment'.
					'instructions'  => __( 'Tracks the underlying order/PO, not the curriculum timeline. Prep work (facilitator, roster, curriculum preview) is allowed while pending; learner-facing sandbox access requires Paid.', 'leaderspath' ),
					'required'      => 0,
					'choices'       => [
						'pending_payment' => __( 'Pending Payment', 'leaderspath' ),
						'paid'             => __( 'Paid', 'leaderspath' ),
					],
					'default_value' => 'pending_payment',
					'return_format' => 'value',
				],
				[
					'key'           => 'field_cohort_instance_access_closed',
					'label'         => __( 'Access Closed', 'leaderspath' ),
					'name'          => 'cohort_access_closed',
					'type'          => 'true_false',
					// Manual, admin/store-manager-only override — deliberately
					// decoupled from refund/cancellation, which must NOT
					// auto-revoke access (docs/TASKS.md Phase 14, "Refund/
					// cancellation must NOT auto-revoke access" — a refund
					// issued after a cohort completed shouldn't silently cut
					// off a team that already went through the material).
					// Checked live ahead of the enrollment chain in every
					// can_user_access_* call; flipping it off fully and
					// instantly restores access since no enrollment record is
					// ever touched by this field.
					'instructions'  => __( 'Manually closes access for the whole cohort, independent of refund/billing status. Reversible — flipping this off instantly restores access. Does not affect enrollment records.', 'leaderspath' ),
					'required'      => 0,
					'default_value' => 0,
					'ui'            => 1,
				],
				[
					'key'           => 'field_cohort_instance_source_record_id',
					'label'         => __( 'Source Record ID', 'leaderspath' ),
					'name'          => 'cohort_source_record_id',
					'type'          => 'number',
					// Generic on purpose — the originating record could be a
					// WooCommerce order, a WS Form submission, or a future
					// commerce backend. Traceability only, matching the old
					// cohort_source_order_id field's role — never read by
					// access-control or roster-management logic (Owner/
					// Facilitator above are authoritative for that).
					'instructions'  => __( 'The order, submission, or other backend record that created this cohort. Traceability only.', 'leaderspath' ),
					'required'      => 0,
					'readonly'      => 1,
				],
				[
					'key'           => 'field_cohort_instance_source_link',
					'label'         => __( 'Source Record', 'leaderspath' ),
					'name'          => 'cohort_source_link_button',
					'type'          => 'message',
					// The URL itself lives in plain post meta
					// (`cohort_source_url`, set via update_post_meta() —
					// not an ACF field), not as its own field value — this
					// field exists only to render a button pointing at it.
					// Content built dynamically per-post in
					// render_cohort_source_link_button().
					'message'       => '',
					'new_lines'     => '',
					'esc_html'      => 0,
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
			'position'              => 'side',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		] );
	}
}
