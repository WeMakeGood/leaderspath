<?php
/**
 * REST API endpoints.
 *
 * @package LeadersPath
 * @since   0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

use League\CommonMark\GithubFlavoredMarkdownConverter;
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
		// Chat endpoint (synchronous).
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

		// Chat streaming endpoint (SSE).
		register_rest_route(
			self::NAMESPACE,
			'/chat/stream',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_stream_chat' ],
				'permission_callback' => [ $this, 'check_chat_permission' ],
				'args'                => $this->get_chat_args(),
			]
		);

		// Cache pre-warm endpoint. Fired when a learner focuses the chat (before
		// their first message) to write the prompt cache during idle time, so the
		// real first message is a cache hit instead of processing ~28K tokens of
		// context cold. Fire-and-forget from the client.
		register_rest_route(
			self::NAMESPACE,
			'/chat/warm',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_warm' ],
				'permission_callback' => [ $this, 'check_chat_permission' ],
				'args'                => $this->get_chat_args(),
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
	 * Supports both activity-level (activity_id) and lesson-level (lesson_id) chatbots.
	 * One of activity_id or lesson_id must be provided.
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
			'lesson_id' => [
				'description'       => __( 'The lesson ID for context (lesson Q&A chatbot).', 'leaderspath' ),
				'type'              => 'integer',
				'required'          => false,
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
			'container_id' => [
				'description'       => __( 'Container ID for session continuity.', 'leaderspath' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
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

		// Enrollment check — only when WooCommerce is active, skip for admins/editors.
		if ( class_exists( 'LeadersPath\Includes\WooCommerce' )
			&& ! current_user_can( 'manage_options' )
			&& ! current_user_can( 'edit_others_posts' )
		) {
			$activity_id = $request->get_param( 'activity_id' );
			$lesson_id   = $request->get_param( 'lesson_id' );

			if ( ! empty( $activity_id )
				&& ! WooCommerce::can_user_access_activity( get_current_user_id(), (int) $activity_id )
			) {
				return new WP_Error(
					'rest_forbidden',
					__( 'You are not enrolled in a cohort that includes this content.', 'leaderspath' ),
					[ 'status' => 403 ]
				);
			}

			if ( ! empty( $lesson_id )
				&& ! WooCommerce::can_user_access_lesson( get_current_user_id(), (int) $lesson_id )
			) {
				return new WP_Error(
					'rest_forbidden',
					__( 'You are not enrolled in a cohort that includes this content.', 'leaderspath' ),
					[ 'status' => 403 ]
				);
			}
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
	 * Validate lesson ID.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value Lesson ID.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_lesson_id( $value ) {
		// Allow null/empty for optional parameter.
		if ( empty( $value ) ) {
			return true;
		}

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
	 * Supports both activity-level (activity_id) and lesson-level (lesson_id) chatbots.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function handle_chat( WP_REST_Request $request ) {
		$activity_id = $request->get_param( 'activity_id' );
		$lesson_id   = $request->get_param( 'lesson_id' );
		$message     = $request->get_param( 'message' );
		$history     = $request->get_param( 'history' ) ?? [];
		$model       = $request->get_param( 'model' );

		// Must have either activity_id or lesson_id.
		if ( empty( $activity_id ) && empty( $lesson_id ) ) {
			return new WP_Error(
				'missing_context',
				__( 'Either activity_id (for activity sandbox) or lesson_id (for lesson Q&A) is required.', 'leaderspath' ),
				[ 'status' => 400 ]
			);
		}

		// Determine which chatbot mode we're in.
		if ( ! empty( $lesson_id ) ) {
			// Lesson Q&A chatbot mode.
			return $this->handle_lesson_chat( (int) $lesson_id, $message, $history, $model );
		}

		// Activity sandbox mode.
		return $this->handle_activity_chat( (int) $activity_id, $message, $history, $model );
	}

	/**
	 * Handle a cache pre-warm request.
	 *
	 * Fired on chat focus, before the first message. Writes the prompt cache for
	 * this activity/lesson (a max_tokens:0 request) so the real first message is
	 * a cache hit. Best-effort: any failure is swallowed — a cold first message
	 * still works, just slower.
	 *
	 * @since 0.12.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Always 200 with a small status body.
	 */
	public function handle_warm( WP_REST_Request $request ): WP_REST_Response {
		$activity_id = (int) $request->get_param( 'activity_id' );
		$lesson_id   = (int) $request->get_param( 'lesson_id' );

		if ( ! $activity_id && ! $lesson_id ) {
			return new WP_REST_Response( [ 'warmed' => false, 'reason' => 'no_context' ], 200 );
		}

		$claude = new Claude_API();
		$warmed = $claude->warm_cache( $activity_id, $lesson_id );

		return new WP_REST_Response( [ 'warmed' => $warmed ], 200 );
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

		// Convert markdown to HTML; keep raw text for conversation history.
		$response['content_raw'] = $response['content'];
		$response['content']     = self::markdown_to_html( $response['content'] );

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Handle lesson Q&A chat.
	 *
	 * @since 0.1.0
	 *
	 * @param int         $lesson_id Lesson ID.
	 * @param string      $message   User message.
	 * @param array       $history   Conversation history.
	 * @param string|null $model     Model override.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	private function handle_lesson_chat( int $lesson_id, string $message, array $history, ?string $model ) {
		// Check if Q&A chatbot is enabled for this lesson.
		$chatbot_enabled = get_field( 'lesson_chatbot_enabled', $lesson_id );
		if ( ! $chatbot_enabled ) {
			return new WP_Error(
				'chatbot_disabled',
				__( 'Q&A chatbot is not enabled for this lesson.', 'leaderspath' ),
				[ 'status' => 400 ]
			);
		}

		// Determine model to use (lesson chatbot doesn't support model switching).
		if ( empty( $model ) ) {
			$model = get_field( 'lesson_chatbot_model', $lesson_id ) ?: \LeadersPath\Admin\Settings::get_default_model();
		}

		// Get Claude API handler.
		$claude = new Claude_API();

		// Send message (lesson mode).
		$response = $claude->send_lesson_message( $lesson_id, $message, $history, $model );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Convert markdown to HTML; keep raw text for conversation history.
		$response['content_raw'] = $response['content'];
		$response['content']     = self::markdown_to_html( $response['content'] );

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Handle streaming chat request.
	 *
	 * Outputs SSE events directly, bypassing WP_REST_Response.
	 * Calls exit() when complete.
	 *
	 * @since 0.9.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Only returns on pre-stream errors.
	 */
	public function handle_stream_chat( WP_REST_Request $request ) {
		$activity_id = $request->get_param( 'activity_id' );
		$lesson_id   = $request->get_param( 'lesson_id' );
		$message     = $request->get_param( 'message' );
		$history     = $request->get_param( 'history' ) ?? [];
		$model       = $request->get_param( 'model' );

		// Must have either activity_id or lesson_id.
		if ( empty( $activity_id ) && empty( $lesson_id ) ) {
			return new WP_Error(
				'missing_context',
				__( 'Either activity_id or lesson_id is required.', 'leaderspath' ),
				[ 'status' => 400 ]
			);
		}

		// Validate and determine mode.
		if ( ! empty( $lesson_id ) ) {
			$error = $this->validate_stream_lesson( (int) $lesson_id, $model );
			if ( is_wp_error( $error ) ) {
				return $error;
			}
			$model = $error; // Returns resolved model string.

			$this->start_sse_output();

			$claude  = new Claude_API();
			$result  = $claude->stream_lesson_message( (int) $lesson_id, $message, $history, $model );
		} else {
			$error = $this->validate_stream_activity( (int) $activity_id, $model );
			if ( is_wp_error( $error ) ) {
				return $error;
			}
			$model        = $error['model'];
			$container_id = $request->get_param( 'container_id' );

			$this->start_sse_output();

			$claude = new Claude_API();
			$result = $claude->stream_message( (int) $activity_id, $message, $history, $model, $container_id );
		}

		// If stream_message returned a WP_Error, it means streaming never started.
		// Send an SSE error event so the client can handle it.
		if ( is_wp_error( $result ) ) {
			echo 'event: error' . "\n";
			echo 'data: ' . wp_json_encode( [ 'message' => $result->get_error_message() ] ) . "\n\n";
			flush();
		}

		exit;
	}

	/**
	 * Validate activity for streaming and resolve model.
	 *
	 * @since 0.9.0
	 *
	 * @param int         $activity_id Activity ID.
	 * @param string|null $model       Model override.
	 * @return array|WP_Error Array with 'model' key or WP_Error.
	 */
	private function validate_stream_activity( int $activity_id, ?string $model ) {
		$chatbot_enabled = get_field( 'chatbot_enabled', $activity_id );
		if ( ! $chatbot_enabled ) {
			return new WP_Error(
				'chatbot_disabled',
				__( 'AI sandbox is not enabled for this activity.', 'leaderspath' ),
				[ 'status' => 400 ]
			);
		}

		if ( empty( $model ) ) {
			$model = get_field( 'chatbot_model', $activity_id ) ?: \LeadersPath\Admin\Settings::get_default_model();
		} else {
			$allow_switch = get_field( 'chatbot_allow_model_switch', $activity_id );
			if ( ! $allow_switch ) {
				$model = get_field( 'chatbot_model', $activity_id ) ?: \LeadersPath\Admin\Settings::get_default_model();
			}
		}

		return [ 'model' => $model ];
	}

	/**
	 * Validate lesson for streaming and resolve model.
	 *
	 * @since 0.9.0
	 *
	 * @param int         $lesson_id Lesson ID.
	 * @param string|null $model     Model override (ignored for lessons).
	 * @return string|WP_Error Resolved model slug or WP_Error.
	 */
	private function validate_stream_lesson( int $lesson_id, ?string $model ) {
		$chatbot_enabled = get_field( 'lesson_chatbot_enabled', $lesson_id );
		if ( ! $chatbot_enabled ) {
			return new WP_Error(
				'chatbot_disabled',
				__( 'Q&A chatbot is not enabled for this lesson.', 'leaderspath' ),
				[ 'status' => 400 ]
			);
		}

		return get_field( 'lesson_chatbot_model', $lesson_id ) ?: \LeadersPath\Admin\Settings::get_default_model();
	}

	/**
	 * Set SSE headers and flush output buffers.
	 *
	 * @since 0.9.0
	 */
	private function start_sse_output(): void {
		// Prevent PHP from timing out during long streams.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@set_time_limit( 300 );

		// Keep PHP alive even if the downstream connection drops momentarily.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@ignore_user_abort( true );

		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'X-Accel-Buffering: no' );
		header( 'Connection: keep-alive' );

		// Flush all WordPress/plugin output buffers.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		while ( @ob_get_level() ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@ob_end_flush();
		}
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
	 * Convert markdown text to sanitized HTML.
	 *
	 * Uses league/commonmark with GFM extensions for fenced code blocks,
	 * tables, strikethrough, task lists, and autolinks.
	 *
	 * @since 0.6.0
	 *
	 * @param string $markdown Raw markdown text.
	 * @return string Sanitized HTML.
	 */
	private static function markdown_to_html( string $markdown ): string {
		static $converter = null;

		if ( null === $converter ) {
			$converter = new GithubFlavoredMarkdownConverter( [
				'html_input'         => 'strip',
				'allow_unsafe_links' => false,
			] );
		}

		return wp_kses_post( $converter->convert( $markdown )->getContent() );
	}

}
