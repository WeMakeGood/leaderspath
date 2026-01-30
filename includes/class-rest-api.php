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

		// Get lesson context files.
		register_rest_route(
			self::NAMESPACE,
			'/lessons/(?P<id>\d+)/context',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_lesson_context' ],
				'permission_callback' => [ $this, 'check_read_permission' ],
				'args'                => [
					'id' => [
						'description'       => __( 'Lesson ID.', 'leaderspath' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => [ $this, 'validate_lesson_id' ],
					],
				],
			]
		);

		// Get lesson skills.
		register_rest_route(
			self::NAMESPACE,
			'/lessons/(?P<id>\d+)/skills',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_lesson_skills' ],
				'permission_callback' => [ $this, 'check_read_permission' ],
				'args'                => [
					'id' => [
						'description'       => __( 'Lesson ID.', 'leaderspath' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => [ $this, 'validate_lesson_id' ],
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
	}

	/**
	 * Get chat endpoint arguments.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, array<string, mixed>> Argument definitions.
	 */
	private function get_chat_args(): array {
		return [
			'lesson_id' => [
				'description'       => __( 'The lesson ID for context.', 'leaderspath' ),
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => [ $this, 'validate_lesson_id' ],
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
	 * Validate lesson ID.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value Lesson ID.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_lesson_id( $value ) {
		$post = get_post( (int) $value );

		if ( ! $post || 'leaderspath_lesson' !== $post->post_type ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Invalid lesson ID.', 'leaderspath' ),
				[ 'status' => 404 ]
			);
		}

		if ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $post->ID ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You cannot access this lesson.', 'leaderspath' ),
				[ 'status' => 403 ]
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
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function handle_chat( WP_REST_Request $request ) {
		$lesson_id = (int) $request->get_param( 'lesson_id' );
		$message   = $request->get_param( 'message' );
		$history   = $request->get_param( 'history' ) ?? [];
		$model     = $request->get_param( 'model' );

		// Check if chatbot is enabled for this lesson.
		$chatbot_enabled = get_field( 'chatbot_enabled', $lesson_id );
		if ( ! $chatbot_enabled ) {
			return new WP_Error(
				'chatbot_disabled',
				__( 'Chatbot is not enabled for this lesson.', 'leaderspath' ),
				[ 'status' => 400 ]
			);
		}

		// Determine model to use.
		if ( empty( $model ) ) {
			$model = get_field( 'chatbot_model', $lesson_id ) ?: \LeadersPath\Admin\Settings::get_default_model();
		} else {
			// Check if model switching is allowed.
			$allow_switch = get_field( 'chatbot_allow_model_switch', $lesson_id );
			if ( ! $allow_switch ) {
				$model = get_field( 'chatbot_model', $lesson_id ) ?: \LeadersPath\Admin\Settings::get_default_model();
			}
		}

		// Get Claude API handler.
		$claude = new Claude_API();

		// Send message.
		$response = $claude->send_message( $lesson_id, $message, $history, $model );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Get context files for a lesson.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_lesson_context( WP_REST_Request $request ) {
		$lesson_id     = (int) $request->get_param( 'id' );
		$context_files = get_field( 'chatbot_context_files', $lesson_id ) ?: [];

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
	 * Get skills for a lesson.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_lesson_skills( WP_REST_Request $request ) {
		$lesson_id = (int) $request->get_param( 'id' );
		$skills    = get_field( 'chatbot_skills', $lesson_id ) ?: [];

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
}
