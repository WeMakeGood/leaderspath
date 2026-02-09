<?php
/**
 * REST API endpoints.
 *
 * @package LeadersPath
 * @since   0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Registers and handles REST API endpoints for LeadersPath.
 *
 * @since 0.1.0
 */
class REST_API {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	private const NAMESPACE = 'leaderspath/v1';

	/**
	 * Initialize the class.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes(): void {
		// Chat endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/chat',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_chat' ],
				'permission_callback' => [ $this, 'check_chat_permission' ],
				'args'                => $this->get_chat_args(),
			]
		);

		// Get activity context files.
		register_rest_route(
			self::NAMESPACE,
			'/activities/(?P<id>\d+)/context',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_activity_context' ],
				'permission_callback' => [ $this, 'check_read_permission' ],
				'args'                => [
					'id' => [
						'description'       => __( 'Activity ID.', 'leaderspath' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => [ $this, 'validate_activity_id' ],
					],
				],
			]
		);

		// Get activity skills.
		register_rest_route(
			self::NAMESPACE,
			'/activities/(?P<id>\d+)/skills',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_activity_skills' ],
				'permission_callback' => [ $this, 'check_read_permission' ],
				'args'                => [
					'id' => [
						'description'       => __( 'Activity ID.', 'leaderspath' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => [ $this, 'validate_activity_id' ],
					],
				],
			]
		);

		// Download context file content.
		register_rest_route(
			self::NAMESPACE,
			'/context/(?P<id>\d+)/download',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'download_context' ],
				'permission_callback' => [ $this, 'check_read_permission' ],
				'args'                => [
					'id' => [
						'description'       => __( 'Context file ID.', 'leaderspath' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => [ $this, 'validate_context_id' ],
					],
				],
			]
		);

		// Download skill definition.
		register_rest_route(
			self::NAMESPACE,
			'/skills/(?P<id>\d+)/download',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'download_skill' ],
				'permission_callback' => [ $this, 'check_read_permission' ],
				'args'                => [
					'id' => [
						'description'       => __( 'Skill ID.', 'leaderspath' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => [ $this, 'validate_skill_id' ],
					],
				],
			]
		);

		// Course meta preview (for VB).
		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/meta',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_course_meta' ],
				'permission_callback' => [ $this, 'check_vb_permission' ],
				'args'                => [
					'id' => [
						'description'       => __( 'Course ID.', 'leaderspath' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => [ $this, 'validate_course_id_for_meta' ],
					],
				],
			]
		);

		// Course meta preview (fallback to first course).
		register_rest_route(
			self::NAMESPACE,
			'/courses/meta',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_first_course_meta' ],
				'permission_callback' => [ $this, 'check_vb_permission' ],
			]
		);

		// Activity meta preview (for VB).
		register_rest_route(
			self::NAMESPACE,
			'/activities/(?P<id>\d+)/meta',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_activity_meta_preview' ],
				'permission_callback' => [ $this, 'check_vb_permission' ],
				'args'                => [
					'id' => [
						'description'       => __( 'Activity ID.', 'leaderspath' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => [ $this, 'validate_activity_id' ],
					],
				],
			]
		);

		// Activity meta preview (fallback to first activity).
		register_rest_route(
			self::NAMESPACE,
			'/activities/meta',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_first_activity_meta' ],
				'permission_callback' => [ $this, 'check_vb_permission' ],
			]
		);

		// Context files preview (fallback to first activity).
		register_rest_route(
			self::NAMESPACE,
			'/activities/context',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_first_activity_context' ],
				'permission_callback' => [ $this, 'check_vb_permission' ],
			]
		);

		// Skills preview (fallback to first activity).
		register_rest_route(
			self::NAMESPACE,
			'/activities/skills',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_first_activity_skills' ],
				'permission_callback' => [ $this, 'check_vb_permission' ],
			]
		);

		// Course objectives preview (for VB).
		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/objectives',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_course_objectives' ],
				'permission_callback' => [ $this, 'check_vb_permission' ],
				'args'                => [
					'id' => [
						'description'       => __( 'Course ID.', 'leaderspath' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => [ $this, 'validate_course_id_for_meta' ],
					],
				],
			]
		);

		// Course objectives preview (fallback to first course).
		register_rest_route(
			self::NAMESPACE,
			'/courses/objectives',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_first_course_objectives' ],
				'permission_callback' => [ $this, 'check_vb_permission' ],
			]
		);

		// Course activities preview (for VB).
		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/activities',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_course_activities' ],
				'permission_callback' => [ $this, 'check_vb_permission' ],
				'args'                => [
					'id' => [
						'description'       => __( 'Course ID.', 'leaderspath' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => [ $this, 'validate_course_id_for_meta' ],
					],
				],
			]
		);

		// Course activities preview (fallback to first course).
		register_rest_route(
			self::NAMESPACE,
			'/courses/activities',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_first_course_activities' ],
				'permission_callback' => [ $this, 'check_vb_permission' ],
			]
		);
	}

	/**
	 * Get chat endpoint arguments.
	 *
	 * Supports both activity-level (activity_id) and course-level (course_id) chatbots.
	 * One of activity_id or course_id must be provided.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, array<string, mixed>> Argument definitions.
	 */
	private function get_chat_args(): array {
		return [
			'activity_id' => [
				'description'       => __( 'The activity ID for context (activity sandbox).', 'leaderspath' ),
				'type'              => 'integer',
				'required'          => false,
				'validate_callback' => [ $this, 'validate_activity_id' ],
			],
			'course_id' => [
				'description'       => __( 'The course ID for context (course Q&A chatbot).', 'leaderspath' ),
				'type'              => 'integer',
				'required'          => false,
				'validate_callback' => [ $this, 'validate_course_id' ],
			],
			'message'   => [
				'description'       => __( 'The user message to send to Claude.', 'leaderspath' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'validate_callback' => function ( $value ) {
					if ( empty( trim( $value ) ) ) {
						return new WP_Error(
							'rest_invalid_param',
							__( 'Message cannot be empty.', 'leaderspath' ),
							[ 'status' => 400 ]
						);
					}
					if ( strlen( $value ) > 32000 ) {
						return new WP_Error(
							'rest_invalid_param',
							__( 'Message is too long.', 'leaderspath' ),
							[ 'status' => 400 ]
						);
					}
					return true;
				},
			],
			'history'   => [
				'description'       => __( 'Previous conversation messages.', 'leaderspath' ),
				'type'              => 'array',
				'required'          => false,
				'default'           => [],
				'validate_callback' => [ $this, 'validate_history' ],
			],
			'model'     => [
				'description'       => __( 'Claude model to use.', 'leaderspath' ),
				'type'              => 'string',
				'required'          => false,
				'enum'              => [ 'sonnet', 'haiku', 'opus-4.5' ],
			],
		];
	}

	/**
	 * Check permission for chat endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if permitted, WP_Error otherwise.
	 */
	public function check_chat_permission( WP_REST_Request $request ) {
		// Must be logged in.
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You must be logged in to use the chatbot.', 'leaderspath' ),
				[ 'status' => 401 ]
			);
		}

		// Check capability.
		if ( ! current_user_can( 'leaderspath_access_chatbot' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to use the chatbot.', 'leaderspath' ),
				[ 'status' => 403 ]
			);
		}

		// Verify nonce from header.
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Invalid security token.', 'leaderspath' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Check permission for read endpoints.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if permitted, WP_Error otherwise.
	 */
	public function check_read_permission( WP_REST_Request $request ) {
		// Must be logged in.
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You must be logged in to access this content.', 'leaderspath' ),
				[ 'status' => 401 ]
			);
		}

		return true;
	}

	/**
	 * Check permission for Visual Builder endpoints.
	 *
	 * Used for module preview data in the VB.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if permitted, WP_Error otherwise.
	 */
	public function check_vb_permission( WP_REST_Request $request ) {
		// Must be logged in and able to edit posts.
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You must be logged in with edit permissions to access this content.', 'leaderspath' ),
				[ 'status' => 401 ]
			);
		}

		return true;
	}

	/**
	 * Validate activity ID.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value Activity ID.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_activity_id( $value ) {
		// Allow null/empty for optional parameter.
		if ( empty( $value ) ) {
			return true;
		}

		$post = get_post( (int) $value );

		if ( ! $post || 'leaderspath_activity' !== $post->post_type ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Invalid activity ID.', 'leaderspath' ),
				[ 'status' => 404 ]
			);
		}

		if ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $post->ID ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You cannot access this activity.', 'leaderspath' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Validate course ID.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value Course ID.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_course_id( $value ) {
		// Allow null/empty for optional parameter.
		if ( empty( $value ) ) {
			return true;
		}

		$post = get_post( (int) $value );

		if ( ! $post || 'leaderspath_course' !== $post->post_type ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Invalid course ID.', 'leaderspath' ),
				[ 'status' => 404 ]
			);
		}

		if ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $post->ID ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You cannot access this course.', 'leaderspath' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Validate course ID for meta endpoint (required).
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value Course ID.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_course_id_for_meta( $value ) {
		$post = get_post( (int) $value );

		if ( ! $post || 'leaderspath_course' !== $post->post_type ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Invalid course ID.', 'leaderspath' ),
				[ 'status' => 404 ]
			);
		}

		return true;
	}

	/**
	 * Validate context file ID.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value Context ID.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_context_id( $value ) {
		$post = get_post( (int) $value );

		if ( ! $post || 'leaderspath_context' !== $post->post_type ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Invalid context file ID.', 'leaderspath' ),
				[ 'status' => 404 ]
			);
		}

		return true;
	}

	/**
	 * Validate skill ID.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value Skill ID.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_skill_id( $value ) {
		$post = get_post( (int) $value );

		if ( ! $post || 'leaderspath_skill' !== $post->post_type ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Invalid skill ID.', 'leaderspath' ),
				[ 'status' => 404 ]
			);
		}

		return true;
	}

	/**
	 * Validate conversation history.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value History array.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_history( $value ) {
		if ( ! is_array( $value ) ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'History must be an array.', 'leaderspath' ),
				[ 'status' => 400 ]
			);
		}

		foreach ( $value as $message ) {
			if ( ! is_array( $message ) ) {
				return new WP_Error(
					'rest_invalid_param',
					__( 'Each history item must be an object.', 'leaderspath' ),
					[ 'status' => 400 ]
				);
			}

			if ( ! isset( $message['role'] ) || ! in_array( $message['role'], [ 'user', 'assistant' ], true ) ) {
				return new WP_Error(
					'rest_invalid_param',
					__( 'Each history item must have a valid role (user or assistant).', 'leaderspath' ),
					[ 'status' => 400 ]
				);
			}

			if ( ! isset( $message['content'] ) || ! is_string( $message['content'] ) ) {
				return new WP_Error(
					'rest_invalid_param',
					__( 'Each history item must have content.', 'leaderspath' ),
					[ 'status' => 400 ]
				);
			}
		}

		return true;
	}

	/**
	 * Handle chat request.
	 *
	 * Supports both activity-level (activity_id) and course-level (course_id) chatbots.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function handle_chat( WP_REST_Request $request ) {
		$activity_id = $request->get_param( 'activity_id' );
		$course_id   = $request->get_param( 'course_id' );
		$message     = $request->get_param( 'message' );
		$history     = $request->get_param( 'history' ) ?? [];
		$model       = $request->get_param( 'model' );

		// Must have either activity_id or course_id.
		if ( empty( $activity_id ) && empty( $course_id ) ) {
			return new WP_Error(
				'missing_context',
				__( 'Either activity_id (for activity sandbox) or course_id (for course Q&A) is required.', 'leaderspath' ),
				[ 'status' => 400 ]
			);
		}

		// Determine which chatbot mode we're in.
		if ( ! empty( $course_id ) ) {
			// Course Q&A chatbot mode.
			return $this->handle_course_chat( (int) $course_id, $message, $history, $model );
		}

		// Activity sandbox mode.
		return $this->handle_activity_chat( (int) $activity_id, $message, $history, $model );
	}

	/**
	 * Handle activity sandbox chat.
	 *
	 * @since 0.1.0
	 *
	 * @param int         $activity_id Activity ID.
	 * @param string      $message     User message.
	 * @param array       $history     Conversation history.
	 * @param string|null $model       Model override.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	private function handle_activity_chat( int $activity_id, string $message, array $history, ?string $model ) {
		// Check if chatbot is enabled for this activity.
		$chatbot_enabled = get_field( 'chatbot_enabled', $activity_id );
		if ( ! $chatbot_enabled ) {
			return new WP_Error(
				'chatbot_disabled',
				__( 'AI sandbox is not enabled for this activity.', 'leaderspath' ),
				[ 'status' => 400 ]
			);
		}

		// Determine model to use.
		if ( empty( $model ) ) {
			$model = get_field( 'chatbot_model', $activity_id ) ?: \LeadersPath\Admin\Settings::get_default_model();
		} else {
			// Check if model switching is allowed.
			$allow_switch = get_field( 'chatbot_allow_model_switch', $activity_id );
			if ( ! $allow_switch ) {
				$model = get_field( 'chatbot_model', $activity_id ) ?: \LeadersPath\Admin\Settings::get_default_model();
			}
		}

		// Get Claude API handler.
		$claude = new Claude_API();

		// Send message (activity mode).
		$response = $claude->send_message( $activity_id, $message, $history, $model );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Handle course Q&A chat.
	 *
	 * @since 0.1.0
	 *
	 * @param int         $course_id Course ID.
	 * @param string      $message   User message.
	 * @param array       $history   Conversation history.
	 * @param string|null $model     Model override.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	private function handle_course_chat( int $course_id, string $message, array $history, ?string $model ) {
		// Check if Q&A chatbot is enabled for this course.
		$chatbot_enabled = get_field( 'course_chatbot_enabled', $course_id );
		if ( ! $chatbot_enabled ) {
			return new WP_Error(
				'chatbot_disabled',
				__( 'Q&A chatbot is not enabled for this course.', 'leaderspath' ),
				[ 'status' => 400 ]
			);
		}

		// Determine model to use (course chatbot doesn't support model switching).
		if ( empty( $model ) ) {
			$model = get_field( 'course_chatbot_model', $course_id ) ?: \LeadersPath\Admin\Settings::get_default_model();
		}

		// Get Claude API handler.
		$claude = new Claude_API();

		// Send message (course mode).
		$response = $claude->send_course_message( $course_id, $message, $history, $model );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Get context files for an activity.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_activity_context( WP_REST_Request $request ) {
		$activity_id   = (int) $request->get_param( 'id' );
		$context_files = get_field( 'chatbot_context_files', $activity_id ) ?: [];

		$data = [];

		foreach ( $context_files as $context_id ) {
			$post = get_post( $context_id );
			if ( ! $post ) {
				continue;
			}

			$data[] = [
				'id'          => $post->ID,
				'title'       => $post->post_title,
				'description' => get_field( 'context_description', $post->ID ) ?: '',
				'file_type'   => get_field( 'context_file_type', $post->ID ) ?: 'other',
				'version'     => get_field( 'context_version', $post->ID ) ?: '',
				'download'    => rest_url( self::NAMESPACE . '/context/' . $post->ID . '/download' ),
			];
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get skills for an activity.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_activity_skills( WP_REST_Request $request ) {
		$activity_id = (int) $request->get_param( 'id' );
		$skills      = get_field( 'chatbot_skills', $activity_id ) ?: [];

		$data = [];

		foreach ( $skills as $skill_id ) {
			$post = get_post( $skill_id );
			if ( ! $post ) {
				continue;
			}

			$data[] = [
				'id'            => $post->ID,
				'title'         => $post->post_title,
				'name'          => get_field( 'skill_name', $post->ID ) ?: $post->post_title,
				'description'   => get_field( 'skill_description', $post->ID ) ?: '',
				'compatibility' => get_field( 'skill_compatibility', $post->ID ) ?: '',
				'version'       => get_field( 'skill_version', $post->ID ) ?: '',
				'download'      => rest_url( self::NAMESPACE . '/skills/' . $post->ID . '/download' ),
			];
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Download context file content.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function download_context( WP_REST_Request $request ) {
		$context_id = (int) $request->get_param( 'id' );
		$post       = get_post( $context_id );

		if ( ! $post ) {
			return new WP_Error(
				'not_found',
				__( 'Context file not found.', 'leaderspath' ),
				[ 'status' => 404 ]
			);
		}

		$data = [
			'id'       => $post->ID,
			'title'    => $post->post_title,
			'content'  => $post->post_content,
			'metadata' => [
				'description' => get_field( 'context_description', $post->ID ) ?: '',
				'file_type'   => get_field( 'context_file_type', $post->ID ) ?: 'other',
				'version'     => get_field( 'context_version', $post->ID ) ?: '',
			],
		];

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Download skill definition.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function download_skill( WP_REST_Request $request ) {
		$skill_id = (int) $request->get_param( 'id' );
		$post     = get_post( $skill_id );

		if ( ! $post ) {
			return new WP_Error(
				'not_found',
				__( 'Skill not found.', 'leaderspath' ),
				[ 'status' => 404 ]
			);
		}

		// Get the skill package file.
		$package_id = get_field( 'skill_package', $skill_id );
		$file_url   = $package_id ? wp_get_attachment_url( $package_id ) : '';

		$data = [
			'id'       => $post->ID,
			'title'    => $post->post_title,
			'metadata' => [
				'name'          => get_field( 'skill_name', $post->ID ) ?: $post->post_title,
				'description'   => get_field( 'skill_description', $post->ID ) ?: '',
				'compatibility' => get_field( 'skill_compatibility', $post->ID ) ?: '',
				'version'       => get_field( 'skill_version', $post->ID ) ?: '',
			],
			'package_url' => $file_url,
		];

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get course meta data for VB preview.
	 *
	 * Returns course metadata (duration, difficulty, activity count) for rendering
	 * in the Visual Builder preview.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_course_meta( WP_REST_Request $request ) {
		$course_id = (int) $request->get_param( 'id' );

		return new WP_REST_Response( $this->build_course_meta_response( $course_id ), 200 );
	}

	/**
	 * Get first available course meta for VB fallback.
	 *
	 * Used when editing Theme Builder templates where no specific course context exists.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_first_course_meta() {
		$courses = get_posts(
			[
				'post_type'      => 'leaderspath_course',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		if ( empty( $courses ) ) {
			return new WP_REST_Response(
				[
					'course_id'      => 0,
					'course_title'   => '',
					'duration'       => '',
					'difficulty'     => '',
					'difficulty_name'=> '',
					'activity_count' => 0,
					'is_sample'      => true,
				],
				200
			);
		}

		$data              = $this->build_course_meta_response( $courses[0]->ID );
		$data['is_sample'] = true;

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Build course meta response data.
	 *
	 * @since 0.1.0
	 *
	 * @param int $course_id Course ID.
	 * @return array Course meta data.
	 */
	private function build_course_meta_response( int $course_id ): array {
		$activities = get_field( 'course_activities', $course_id );
		$difficulty = get_field( 'course_difficulty', $course_id ) ?: '';

		$difficulty_names = [
			'beginner'     => __( 'Beginner', 'leaderspath' ),
			'intermediate' => __( 'Intermediate', 'leaderspath' ),
			'advanced'     => __( 'Advanced', 'leaderspath' ),
		];

		return [
			'course_id'       => $course_id,
			'course_title'    => get_the_title( $course_id ),
			'duration'        => get_field( 'course_total_duration', $course_id ) ?: '',
			'difficulty'      => $difficulty,
			'difficulty_name' => $difficulty_names[ $difficulty ] ?? ucfirst( $difficulty ),
			'activity_count'  => is_array( $activities ) ? count( $activities ) : 0,
			'is_sample'       => false,
		];
	}

	/**
	 * Get activity meta data for VB preview.
	 *
	 * Returns activity metadata (duration, model) for rendering in the Visual Builder preview.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_activity_meta_preview( WP_REST_Request $request ) {
		$activity_id = (int) $request->get_param( 'id' );

		return new WP_REST_Response( $this->build_activity_meta_response( $activity_id ), 200 );
	}

	/**
	 * Get first available activity meta for VB fallback.
	 *
	 * Used when editing Theme Builder templates where no specific activity context exists.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_first_activity_meta() {
		$activities = get_posts(
			[
				'post_type'      => 'leaderspath_activity',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		if ( empty( $activities ) ) {
			return new WP_REST_Response(
				[
					'activity_id'     => 0,
					'activity_title'  => '',
					'duration'        => 0,
					'duration_text'   => '',
					'model'           => '',
					'model_name'      => '',
					'chatbot_enabled' => false,
				],
				200
			);
		}

		return new WP_REST_Response( $this->build_activity_meta_response( $activities[0]->ID ), 200 );
	}

	/**
	 * Build activity meta response data.
	 *
	 * @since 0.1.0
	 *
	 * @param int $activity_id Activity ID.
	 * @return array Activity meta data.
	 */
	private function build_activity_meta_response( int $activity_id ): array {
		$model           = get_field( 'chatbot_model', $activity_id ) ?: '';
		$chatbot_enabled = (bool) get_field( 'chatbot_enabled', $activity_id );
		$duration        = (int) ( get_field( 'activity_duration', $activity_id ) ?: 0 );

		$model_names = [
			'sonnet'   => __( 'Claude Sonnet', 'leaderspath' ),
			'haiku'    => __( 'Claude Haiku', 'leaderspath' ),
			'opus-4.5' => __( 'Claude Opus', 'leaderspath' ),
		];

		$duration_text = '';
		if ( $duration > 0 ) {
			$duration_text = sprintf(
				/* translators: %d: number of minutes */
				_n( '%d minute', '%d minutes', $duration, 'leaderspath' ),
				$duration
			);
		}

		return [
			'activity_id'     => $activity_id,
			'activity_title'  => get_the_title( $activity_id ),
			'duration'        => $duration,
			'duration_text'   => $duration_text,
			'model'           => $model,
			'model_name'      => $model_names[ $model ] ?? ucfirst( $model ),
			'chatbot_enabled' => $chatbot_enabled,
		];
	}

	/**
	 * Get context files for first activity (VB fallback).
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response Response.
	 */
	public function get_first_activity_context() {
		$activity_id = $this->get_first_activity_id();

		if ( ! $activity_id ) {
			return new WP_REST_Response( [], 200 );
		}

		return $this->build_context_response( $activity_id );
	}

	/**
	 * Get skills for first activity (VB fallback).
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response Response.
	 */
	public function get_first_activity_skills() {
		$activity_id = $this->get_first_activity_id();

		if ( ! $activity_id ) {
			return new WP_REST_Response( [], 200 );
		}

		return $this->build_skills_response( $activity_id );
	}

	/**
	 * Get the first activity ID for VB preview.
	 *
	 * @since 0.1.0
	 *
	 * @return int Activity ID or 0.
	 */
	private function get_first_activity_id(): int {
		$activities = get_posts(
			[
				'post_type'      => 'leaderspath_activity',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		return ! empty( $activities ) ? (int) $activities[0]->ID : 0;
	}

	/**
	 * Build context files response for an activity.
	 *
	 * @since 0.1.0
	 *
	 * @param int $activity_id Activity ID.
	 * @return WP_REST_Response Response.
	 */
	private function build_context_response( int $activity_id ): WP_REST_Response {
		$context_files = get_field( 'chatbot_context_files', $activity_id ) ?: [];

		$file_type_labels = [
			'system_prompt'  => __( 'System Prompt', 'leaderspath' ),
			'knowledge_base' => __( 'Knowledge Base', 'leaderspath' ),
			'instructions'   => __( 'Instructions', 'leaderspath' ),
			'other'          => __( 'Context', 'leaderspath' ),
		];

		$data = [];

		foreach ( $context_files as $context_id ) {
			$post = get_post( $context_id );
			if ( ! $post ) {
				continue;
			}

			$file_type = get_field( 'context_file_type', $post->ID ) ?: 'other';

			$data[] = [
				'id'              => $post->ID,
				'title'           => $post->post_title,
				'description'     => get_field( 'context_description', $post->ID ) ?: '',
				'file_type'       => $file_type,
				'file_type_label' => $file_type_labels[ $file_type ] ?? $file_type_labels['other'],
				'version'         => get_field( 'context_version', $post->ID ) ?: '',
				'download'        => rest_url( self::NAMESPACE . '/context/' . $post->ID . '/download' ),
			];
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Build skills response for an activity.
	 *
	 * @since 0.1.0
	 *
	 * @param int $activity_id Activity ID.
	 * @return WP_REST_Response Response.
	 */
	private function build_skills_response( int $activity_id ): WP_REST_Response {
		$skills = get_field( 'chatbot_skills', $activity_id ) ?: [];

		$data = [];

		foreach ( $skills as $skill_id ) {
			$post = get_post( $skill_id );
			if ( ! $post ) {
				continue;
			}

			$data[] = [
				'id'            => $post->ID,
				'title'         => $post->post_title,
				'name'          => get_field( 'skill_name', $post->ID ) ?: $post->post_title,
				'description'   => get_field( 'skill_description', $post->ID ) ?: '',
				'compatibility' => get_field( 'skill_compatibility', $post->ID ) ?: '',
				'version'       => get_field( 'skill_version', $post->ID ) ?: '',
				'download'      => rest_url( self::NAMESPACE . '/skills/' . $post->ID . '/download' ),
			];
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get course objectives for VB preview.
	 *
	 * Returns course objectives from the course_objectives repeater field
	 * for rendering in the Visual Builder preview.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_course_objectives( WP_REST_Request $request ) {
		$course_id = (int) $request->get_param( 'id' );

		return new WP_REST_Response( $this->build_course_objectives_response( $course_id ), 200 );
	}

	/**
	 * Get first available course objectives for VB fallback.
	 *
	 * Used when editing Theme Builder templates where no specific course context exists.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_first_course_objectives() {
		$courses = get_posts(
			[
				'post_type'      => 'leaderspath_course',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		if ( empty( $courses ) ) {
			return new WP_REST_Response(
				[
					'course_id'    => 0,
					'course_title' => '',
					'objectives'   => [],
				],
				200
			);
		}

		return new WP_REST_Response( $this->build_course_objectives_response( $courses[0]->ID ), 200 );
	}

	/**
	 * Build course objectives response data.
	 *
	 * @since 0.1.0
	 *
	 * @param int $course_id Course ID.
	 * @return array Course objectives data.
	 */
	private function build_course_objectives_response( int $course_id ): array {
		$objectives_repeater = get_field( 'course_objectives', $course_id );
		$objectives          = [];

		if ( is_array( $objectives_repeater ) ) {
			foreach ( $objectives_repeater as $row ) {
				if ( ! empty( $row['objective'] ) ) {
					$objectives[] = $row['objective'];
				}
			}
		}

		return [
			'course_id'    => $course_id,
			'course_title' => get_the_title( $course_id ),
			'objectives'   => $objectives,
		];
	}

	/**
	 * Get course activities for VB preview.
	 *
	 * Returns ordered list of activities from the course_activities relationship field
	 * for rendering in the Visual Builder preview.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_course_activities( WP_REST_Request $request ) {
		$course_id = (int) $request->get_param( 'id' );

		return new WP_REST_Response( $this->build_course_activities_response( $course_id ), 200 );
	}

	/**
	 * Get first available course activities for VB fallback.
	 *
	 * Used when editing Theme Builder templates where no specific course context exists.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_first_course_activities() {
		$courses = get_posts(
			[
				'post_type'      => 'leaderspath_course',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		if ( empty( $courses ) ) {
			return new WP_REST_Response(
				[
					'course_id'    => 0,
					'course_title' => '',
					'activities'   => [],
				],
				200
			);
		}

		return new WP_REST_Response( $this->build_course_activities_response( $courses[0]->ID ), 200 );
	}

	/**
	 * Build course activities response data.
	 *
	 * @since 0.1.0
	 *
	 * @param int $course_id Course ID.
	 * @return array Course activities data.
	 */
	private function build_course_activities_response( int $course_id ): array {
		$related_activities = get_field( 'course_activities', $course_id );
		$activities         = [];

		if ( is_array( $related_activities ) ) {
			foreach ( $related_activities as $activity ) {
				if ( ! $activity instanceof \WP_Post ) {
					continue;
				}

				$activities[] = [
					'id'    => $activity->ID,
					'title' => get_the_title( $activity ),
					'url'   => get_permalink( $activity ),
				];
			}
		}

		return [
			'course_id'    => $course_id,
			'course_title' => get_the_title( $course_id ),
			'activities'   => $activities,
		];
	}
}
