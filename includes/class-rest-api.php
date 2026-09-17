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
	 * Allowed MIME types for chat file uploads, mapped to their canonical
	 * extension(s). Matches what Anthropic's Files API + code-execution
	 * container actually accept: plain text, markdown, CSV, JSON, common
	 * images, PDF, and common office documents.
	 *
	 * @var array<string, array<int, string>>
	 */
	private const ALLOWED_UPLOAD_MIME_TYPES = [
		'text/plain'                                                               => [ 'txt' ],
		'text/markdown'                                                            => [ 'md' ],
		'text/csv'                                                                 => [ 'csv' ],
		'application/json'                                                         => [ 'json' ],
		'image/png'                                                                => [ 'png' ],
		'image/jpeg'                                                               => [ 'jpg', 'jpeg' ],
		'image/gif'                                                                => [ 'gif' ],
		'image/webp'                                                               => [ 'webp' ],
		'application/pdf'                                                          => [ 'pdf' ],
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document'  => [ 'docx' ],
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'        => [ 'xlsx' ],
		'application/vnd.openxmlformats-officedocument.presentationml.presentation' => [ 'pptx' ],
	];

	/**
	 * Maximum upload size in bytes for chat file uploads.
	 *
	 * A deliberate app-level ceiling, not Anthropic's own limit — the Files
	 * API itself allows up to 500MB per file (confirmed against
	 * platform.claude.com/docs/en/build-with-claude/files, 2026-09-17). 30MB
	 * keeps a single learner upload from tying up a synchronous
	 * wp_remote_post() call/PHP memory for an unreasonably long time; raise
	 * it deliberately if a real activity needs larger files, not as a
	 * pass-through of Anthropic's ceiling.
	 *
	 * @var int
	 */
	private const MAX_UPLOAD_BYTES = 30 * 1024 * 1024;

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

		// Chat file upload endpoint (multipart). Uploads to Anthropic's Files
		// API and returns a file_id for the client to attach on its *next*
		// /chat or /chat/stream call — see docs/TASKS.md Phase 15, Story 4.
		register_rest_route(
			self::NAMESPACE,
			'/chat/upload',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_chat_upload' ],
				'permission_callback' => [ $this, 'check_chat_permission' ],
				'args'                => [
					'activity_id' => [
						'description'       => __( 'The activity ID this file is being uploaded for.', 'leaderspath' ),
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
			'attached_file_id' => [
				'description'       => __( 'Anthropic file ID (from /chat/upload) to attach to this turn.', 'leaderspath' ),
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

		// Enrollment check — skip for admins/editors. Not gated on WooCommerce
		// being active: Enrollment/leaderspath_cohort are commerce-agnostic
		// (see class-enrollment.php), so this applies regardless of which
		// commerce backend, if any, created the cohort.
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_others_posts' ) ) {
			$activity_id = $request->get_param( 'activity_id' );
			$lesson_id   = $request->get_param( 'lesson_id' );

			if ( ! empty( $activity_id )
				&& ! Enrollment::can_user_access_activity( get_current_user_id(), (int) $activity_id )
			) {
				return new WP_Error(
					'rest_forbidden',
					__( 'You are not enrolled in a cohort that includes this content.', 'leaderspath' ),
					[ 'status' => 403 ]
				);
			}

			if ( ! empty( $lesson_id )
				&& ! Enrollment::can_user_access_lesson( get_current_user_id(), (int) $lesson_id )
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
	 * Mirrors check_chat_permission(): logged in, capable, and — when
	 * WooCommerce is active — enrolled in a cohort that includes the
	 * activity or lesson this context file or skill is attached to.
	 * Context files and skills have no owning cohort of their own; access
	 * is resolved by walking up the same chain the chatbot uses.
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

		// Check capability — same gate as the chatbot itself.
		if ( ! current_user_can( 'leaderspath_access_chatbot' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this content.', 'leaderspath' ),
				[ 'status' => 403 ]
			);
		}

		// Enrollment check — skip for admins/editors. Not gated on WooCommerce
		// being active; see check_chat_permission()'s equivalent note.
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_others_posts' ) ) {
			$route     = $request->get_route();
			$id        = (int) $request->get_param( 'id' );
			$user_id   = get_current_user_id();
			$permitted = true;

			if ( false !== strpos( $route, '/context/' ) ) {
				$permitted = Enrollment::can_user_access_context( $user_id, $id );
			} elseif ( false !== strpos( $route, '/skills/' ) ) {
				$permitted = Enrollment::can_user_access_skill( $user_id, $id );
			}

			if ( ! $permitted ) {
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
			// Lesson Q&A chatbot mode. Lessons have no skills/container, so
			// file attachment never applies here.
			return $this->handle_lesson_chat( (int) $lesson_id, $message, $history, $model );
		}

		// Activity sandbox mode.
		$attached_file_id = $request->get_param( 'attached_file_id' );
		return $this->handle_activity_chat( (int) $activity_id, $message, $history, $model, $attached_file_id ?: null );
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
	 * Handle a chat file upload.
	 *
	 * Uploads the file to Anthropic's Files API and returns its file_id.
	 * The client attaches that file_id on its *next* /chat or /chat/stream
	 * call (attached_file_id param) — this endpoint never talks to the
	 * Messages API itself. Only valid for skills-enabled activities: a
	 * container_upload only makes sense when a code-execution container
	 * exists, and this plugin only provisions one when skills are
	 * configured (see Claude_API::get_skills_for_api()). A skill-less
	 * activity_id is rejected here even if the client bypasses the UI.
	 *
	 * @since 0.13.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response with file_id, or error.
	 */
	public function handle_chat_upload( WP_REST_Request $request ) {
		$activity_id = (int) $request->get_param( 'activity_id' );

		$claude = new Claude_API();
		if ( empty( $claude->get_skills_for_api( $activity_id ) ) ) {
			return new WP_Error(
				'uploads_not_supported',
				__( 'This activity does not support file uploads.', 'leaderspath' ),
				[ 'status' => 400 ]
			);
		}

		$files = $request->get_file_params();
		if ( empty( $files['file'] ) || ! is_array( $files['file'] ) ) {
			return new WP_Error(
				'missing_file',
				__( 'No file was uploaded.', 'leaderspath' ),
				[ 'status' => 400 ]
			);
		}

		$file = $files['file'];

		if ( UPLOAD_ERR_OK !== ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
			return new WP_Error(
				'upload_error',
				__( 'The file failed to upload.', 'leaderspath' ),
				[ 'status' => 400 ]
			);
		}

		if ( (int) $file['size'] > self::MAX_UPLOAD_BYTES ) {
			return new WP_Error(
				'file_too_large',
				__( 'The file is too large. The maximum size is 30MB.', 'leaderspath' ),
				[ 'status' => 400 ]
			);
		}

		$mime_check = $this->validate_upload_type( $file['tmp_name'], $file['name'] );
		if ( is_wp_error( $mime_check ) ) {
			return $mime_check;
		}

		$api_key = \LeadersPath\Admin\Settings::get_api_key();
		if ( empty( $api_key ) ) {
			return new WP_Error(
				'api_key_missing',
				__( 'Claude API key is not configured.', 'leaderspath' ),
				[ 'status' => 500 ]
			);
		}

		$result = $claude->upload_file( $file['tmp_name'], sanitize_file_name( $file['name'] ), $mime_check, $api_key );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( [ 'file_id' => $result['id'] ], 200 );
	}

	/**
	 * Validate an uploaded file's type against the chat-upload allowlist.
	 *
	 * Uses wp_check_filetype_and_ext() (checks real file content against
	 * its extension, not just the client-supplied MIME type) so this stays
	 * authoritative even though chatbot.js does the same check client-side
	 * for fast UX. WordPress's own default mime map doesn't recognize
	 * .md/.json (confirmed directly: wp_check_filetype_and_ext() returns
	 * type => false for both with no extra argument, even though they're
	 * legitimate types this endpoint allows) — passing our own
	 * extension-to-mime map as the third argument is the documented way to
	 * extend recognized types without a global `upload_mimes` filter, and
	 * keeps ALLOWED_UPLOAD_MIME_TYPES the single source of truth.
	 *
	 * @since 0.13.0
	 *
	 * @param string $tmp_path      Path to the uploaded temp file.
	 * @param string $original_name Original client-supplied filename.
	 * @return string|WP_Error The validated MIME type, or WP_Error if not allowed.
	 */
	private function validate_upload_type( string $tmp_path, string $original_name ) {
		$extension_mimes = [];
		foreach ( self::ALLOWED_UPLOAD_MIME_TYPES as $mime => $extensions ) {
			foreach ( $extensions as $extension ) {
				$extension_mimes[ $extension ] = $mime;
			}
		}

		$checked   = wp_check_filetype_and_ext( $tmp_path, $original_name, $extension_mimes );
		$mime_type = $checked['type'] ?: '';

		if ( empty( $mime_type ) || ! isset( self::ALLOWED_UPLOAD_MIME_TYPES[ $mime_type ] ) ) {
			return new WP_Error(
				'invalid_file_type',
				__( 'This file type is not supported. Allowed types: plain text, Markdown, CSV, JSON, PNG/JPG/GIF/WebP images, PDF, and common Office documents.', 'leaderspath' ),
				[ 'status' => 400 ]
			);
		}

		return $mime_type;
	}

	/**
	 * Handle activity sandbox chat.
	 *
	 * @since 0.1.0
	 *
	 * @param int         $activity_id      Activity ID.
	 * @param string      $message          User message.
	 * @param array       $history          Conversation history.
	 * @param string|null $model            Model override.
	 * @param string|null $attached_file_id Anthropic file ID (from /chat/upload) to attach to this turn.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	private function handle_activity_chat( int $activity_id, string $message, array $history, ?string $model, ?string $attached_file_id = null ) {
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
		$response = $claude->send_message( $activity_id, $message, $history, $model, null, $attached_file_id );

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
			$model            = $error['model'];
			$container_id     = $request->get_param( 'container_id' );
			$attached_file_id = $request->get_param( 'attached_file_id' ) ?: null;

			$this->start_sse_output();

			$claude = new Claude_API();
			$result = $claude->stream_message( (int) $activity_id, $message, $history, $model, $container_id, $attached_file_id );
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
