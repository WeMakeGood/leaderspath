<?php
/**
 * Claude API Handler.
 *
 * @package LeadersPath
 * @since   0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

use WP_Error;

/**
 * Handles communication with the Anthropic Claude API.
 *
 * Uses Container API with Skills and Code Execution for full skill support.
 * See docs/claude-api-integration.md for complete API documentation.
 *
 * @since 0.1.0
 */
class Claude_API {

	/**
	 * Anthropic API base URL.
	 *
	 * @var string
	 */
	private const API_URL = 'https://api.anthropic.com/v1';

	/**
	 * Anthropic API version header.
	 *
	 * @var string
	 */
	private const API_VERSION = '2023-06-01';

	/**
	 * Transient key for cached models.
	 *
	 * @var string
	 */
	private const MODELS_TRANSIENT = 'leaderspath_claude_models';

	/**
	 * Model family mapping for user-friendly selection.
	 *
	 * Maps our simple slugs to model family prefixes.
	 *
	 * @var array<string, string>
	 */
	private const MODEL_FAMILIES = [
		'sonnet' => 'claude-sonnet',
		'haiku'  => 'claude-haiku',
		'opus'   => 'claude-opus',
	];

	/**
	 * Maximum number of skills per request.
	 *
	 * @var int
	 */
	private const MAX_SKILLS_PER_REQUEST = 8;

	/**
	 * Send a message to Claude.
	 *
	 * Uses Container API with Skills and Code Execution when skills are configured.
	 *
	 * @since 0.1.0
	 *
	 * @param int         $activity_id    Activity ID for context.
	 * @param string      $message      User message.
	 * @param array       $history      Previous conversation messages.
	 * @param string      $model        Model slug (sonnet, haiku, opus-4.5).
	 * @param string|null $container_id Container ID for session continuity.
	 * @return array|WP_Error Response data or error.
	 */
	public function send_message( int $activity_id, string $message, array $history = [], string $model = 'sonnet', ?string $container_id = null ) {
		// Get API key.
		$api_key = \LeadersPath\Admin\Settings::get_api_key();

		if ( empty( $api_key ) ) {
			return new WP_Error(
				'api_key_missing',
				__( 'Claude API key is not configured.', 'leaderspath' ),
				[ 'status' => 500 ]
			);
		}

		// Resolve model ID.
		$model_id = $this->resolve_model_id( $model );
		if ( is_wp_error( $model_id ) ) {
			return $model_id;
		}

		// Build system prompt with context.
		$system_prompt = $this->build_system_prompt( $activity_id );

		// Build messages array.
		$messages = $this->build_messages( $message, $history );

		// Get model settings.
		$max_tokens  = (int) ( get_field( 'chatbot_max_tokens', $activity_id ) ?: 4096 );
		$temperature = (float) ( get_field( 'chatbot_temperature', $activity_id ) ?? 0.7 );

		// Get skills for this activity (with valid Anthropic IDs).
		$skills_for_api = $this->get_skills_for_api( $activity_id );

		// Build request body.
		$body = [
			'model'       => $model_id,
			'max_tokens'  => $max_tokens,
			'system'      => $system_prompt,
			'messages'    => $messages,
		];

		// Add container with skills if any skills are configured.
		if ( ! empty( $skills_for_api ) ) {
			$container = [ 'skills' => $skills_for_api ];

			// Reuse existing container if provided.
			if ( $container_id ) {
				$container['id'] = $container_id;
			}

			$body['container'] = $container;

			// Add code execution tool when skills are present.
			$tool_type = \LeadersPath\Admin\Settings::get_code_execution_tool_type();
			$body['tools'] = [
				[
					'type' => $tool_type,
					'name' => 'code_execution',
				],
			];
		}

		// Only include temperature if not using extended thinking (opus).
		// Extended thinking requires temperature to be 1.
		if ( strpos( $model_id, 'opus' ) === false ) {
			$body['temperature'] = $temperature;
		}

		// Log request if debug mode is enabled.
		$this->maybe_log( 'Request', $body );

		// Build headers with beta features if skills are used.
		$headers = [
			'Content-Type'      => 'application/json',
			'x-api-key'         => $api_key,
			'anthropic-version' => self::API_VERSION,
		];

		if ( ! empty( $skills_for_api ) ) {
			$headers['anthropic-beta'] = \LeadersPath\Admin\Settings::get_beta_headers( [ 'code_execution', 'skills' ] );
		}

		// Make API request.
		$response = wp_remote_post(
			self::API_URL . '/messages',
			[
				'timeout' => 180, // Longer timeout for code execution.
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			$this->maybe_log( 'Error', [ 'message' => $response->get_error_message() ] );
			return new WP_Error(
				'api_request_failed',
				__( 'Failed to connect to Claude API.', 'leaderspath' ),
				[ 'status' => 500 ]
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body_raw    = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body_raw, true );

		// Log response if debug mode is enabled.
		$this->maybe_log( 'Response', [ 'status' => $status_code, 'body' => $data ] );

		// Handle error responses.
		if ( $status_code >= 400 ) {
			return $this->handle_api_error( $status_code, $data );
		}

		// Handle pause_turn for long-running operations.
		if ( isset( $data['stop_reason'] ) && 'pause_turn' === $data['stop_reason'] ) {
			return $this->handle_pause_turn( $activity_id, $data, $messages, $model, $api_key, $headers );
		}

		// Extract response content (may have multiple content blocks).
		$content = $this->extract_response_content( $data );

		if ( '' === $content ) {
			return new WP_Error(
				'api_invalid_response',
				__( 'Invalid response from Claude API.', 'leaderspath' ),
				[ 'status' => 500 ]
			);
		}

		/**
		 * Fires after a successful chat message.
		 *
		 * @since 0.1.0
		 *
		 * @param string $message   The user's message.
		 * @param array  $data      The API response data.
		 * @param int    $user_id   The current user ID.
		 * @param int    $activity_id The activity ID.
		 */
		do_action( 'leaderspath_chat_message_sent', $message, $data, get_current_user_id(), $activity_id );

		return [
			'content'      => $content,
			'model'        => $data['model'],
			'usage'        => $data['usage'] ?? [],
			'stop_reason'  => $data['stop_reason'] ?? null,
			'container_id' => $data['container']['id'] ?? null,
		];
	}

	/**
	 * Send a message to Claude for course Q&A chatbot.
	 *
	 * Similar to send_message but configured for course-level Q&A assistance.
	 *
	 * @since 0.1.0
	 *
	 * @param int         $course_id    Course ID for context.
	 * @param string      $message      User message.
	 * @param array       $history      Previous conversation messages.
	 * @param string      $model        Model slug (sonnet, haiku, opus-4.5).
	 * @param string|null $container_id Container ID for session continuity.
	 * @return array|WP_Error Response data or error.
	 */
	public function send_course_message( int $course_id, string $message, array $history = [], string $model = 'sonnet', ?string $container_id = null ) {
		// Get API key.
		$api_key = \LeadersPath\Admin\Settings::get_api_key();

		if ( empty( $api_key ) ) {
			return new WP_Error(
				'api_key_missing',
				__( 'Claude API key is not configured.', 'leaderspath' ),
				[ 'status' => 500 ]
			);
		}

		// Resolve model ID.
		$model_id = $this->resolve_model_id( $model );
		if ( is_wp_error( $model_id ) ) {
			return $model_id;
		}

		// Build system prompt with course context.
		$system_prompt = $this->build_course_system_prompt( $course_id );

		// Build messages array.
		$messages = $this->build_messages( $message, $history );

		// Get model settings from course.
		$max_tokens  = (int) ( get_field( 'course_chatbot_max_tokens', $course_id ) ?: 4096 );
		$temperature = (float) ( get_field( 'course_chatbot_temperature', $course_id ) ?? 0.7 );

		// Build request body.
		$body = [
			'model'       => $model_id,
			'max_tokens'  => $max_tokens,
			'system'      => $system_prompt,
			'messages'    => $messages,
		];

		// Only include temperature if not using extended thinking (opus).
		if ( strpos( $model_id, 'opus' ) === false ) {
			$body['temperature'] = $temperature;
		}

		// Log request if debug mode is enabled.
		$this->maybe_log( 'Course Q&A Request', $body );

		// Build headers.
		$headers = [
			'Content-Type'      => 'application/json',
			'x-api-key'         => $api_key,
			'anthropic-version' => self::API_VERSION,
		];

		// Make API request.
		$response = wp_remote_post(
			self::API_URL . '/messages',
			[
				'timeout' => 120,
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			$this->maybe_log( 'Error', [ 'message' => $response->get_error_message() ] );
			return new WP_Error(
				'api_request_failed',
				__( 'Failed to connect to Claude API.', 'leaderspath' ),
				[ 'status' => 500 ]
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body_raw    = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body_raw, true );

		// Log response if debug mode is enabled.
		$this->maybe_log( 'Course Q&A Response', [ 'status' => $status_code, 'body' => $data ] );

		// Handle error responses.
		if ( $status_code >= 400 ) {
			return $this->handle_api_error( $status_code, $data );
		}

		// Extract response content.
		$content = $this->extract_response_content( $data );

		if ( '' === $content ) {
			return new WP_Error(
				'api_invalid_response',
				__( 'Invalid response from Claude API.', 'leaderspath' ),
				[ 'status' => 500 ]
			);
		}

		/**
		 * Fires after a successful course Q&A chat message.
		 *
		 * @since 0.1.0
		 *
		 * @param string $message   The user's message.
		 * @param array  $data      The API response data.
		 * @param int    $user_id   The current user ID.
		 * @param int    $course_id The course ID.
		 */
		do_action( 'leaderspath_course_chat_message_sent', $message, $data, get_current_user_id(), $course_id );

		return [
			'content'      => $content,
			'model'        => $data['model'],
			'usage'        => $data['usage'] ?? [],
			'stop_reason'  => $data['stop_reason'] ?? null,
		];
	}

	/**
	 * Resolve a model slug to an actual model ID.
	 *
	 * @since 0.1.0
	 *
	 * @param string $slug Model slug (e.g., 'sonnet', 'haiku', 'opus-4.5').
	 * @return string|WP_Error Model ID or error.
	 */
	private function resolve_model_id( string $slug ) {
		// Normalize the slug.
		$slug = strtolower( trim( $slug ) );

		// Map opus-4.5 to opus.
		$family = str_replace( '-4.5', '', $slug );

		if ( ! isset( self::MODEL_FAMILIES[ $family ] ) ) {
			return new WP_Error(
				'invalid_model',
				/* translators: %s: model slug */
				sprintf( __( 'Invalid model: %s', 'leaderspath' ), $slug ),
				[ 'status' => 400 ]
			);
		}

		// Get available models.
		$models = $this->get_available_models();

		if ( is_wp_error( $models ) ) {
			// Fallback to known model IDs if we can't fetch the list.
			$fallbacks = [
				'sonnet' => 'claude-sonnet-4-20250514',
				'haiku'  => 'claude-haiku-4-20250514',
				'opus'   => 'claude-opus-4-5-20251101',
			];
			return $fallbacks[ $family ] ?? $fallbacks['sonnet'];
		}

		// Find the best match for this family.
		$family_prefix = self::MODEL_FAMILIES[ $family ];
		$matches       = [];

		foreach ( $models as $model ) {
			if ( strpos( $model['id'], $family_prefix ) === 0 ) {
				$matches[] = $model['id'];
			}
		}

		if ( empty( $matches ) ) {
			// No matches found, use fallback.
			$fallbacks = [
				'sonnet' => 'claude-sonnet-4-20250514',
				'haiku'  => 'claude-haiku-4-20250514',
				'opus'   => 'claude-opus-4-5-20251101',
			];
			return $fallbacks[ $family ] ?? $fallbacks['sonnet'];
		}

		// Return the most recent model (they're typically sorted or we can sort by date in ID).
		sort( $matches );
		return end( $matches );
	}

	/**
	 * Get available models from the API.
	 *
	 * Results are cached for 24 hours.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $force_refresh Force refresh the cache.
	 * @return array|WP_Error Array of models or error.
	 */
	public function get_available_models( bool $force_refresh = false ) {
		// Check cache first.
		if ( ! $force_refresh ) {
			$cached = get_transient( self::MODELS_TRANSIENT );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$api_key = \LeadersPath\Admin\Settings::get_api_key();

		if ( empty( $api_key ) ) {
			return new WP_Error(
				'api_key_missing',
				__( 'Claude API key is not configured.', 'leaderspath' )
			);
		}

		$response = wp_remote_get(
			self::API_URL . '/models',
			[
				'timeout' => 30,
				'headers' => [
					'x-api-key'         => $api_key,
					'anthropic-version' => self::API_VERSION,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code >= 400 ) {
			return $this->handle_api_error( $status_code, $body );
		}

		$models = $body['data'] ?? [];

		// Cache for 24 hours.
		set_transient( self::MODELS_TRANSIENT, $models, DAY_IN_SECONDS );

		return $models;
	}

	/**
	 * Get models grouped by family for settings UI.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, array<string, string>> Models grouped by family.
	 */
	public function get_models_for_settings(): array {
		$models = $this->get_available_models();

		// If we can't fetch models, return defaults.
		if ( is_wp_error( $models ) || empty( $models ) ) {
			return [
				'sonnet' => __( 'Sonnet (Recommended)', 'leaderspath' ),
				'haiku'  => __( 'Haiku (Faster, Lower Cost)', 'leaderspath' ),
				'opus'   => __( 'Opus (Most Capable)', 'leaderspath' ),
			];
		}

		$grouped = [
			'sonnet' => __( 'Sonnet (Recommended)', 'leaderspath' ),
			'haiku'  => __( 'Haiku (Faster, Lower Cost)', 'leaderspath' ),
			'opus'   => __( 'Opus (Most Capable)', 'leaderspath' ),
		];

		return $grouped;
	}

	/**
	 * Build the system prompt with activity context.
	 *
	 * @since 0.1.0
	 *
	 * @param int $activity_id Activity ID.
	 * @return string System prompt.
	 */
	private function build_system_prompt( int $activity_id ): string {
		$activity = get_post( $activity_id );
		$parts  = [];

		// Start with custom system prompt if set.
		$custom_prompt = get_field( 'chatbot_system_prompt', $activity_id );
		if ( ! empty( $custom_prompt ) ) {
			$parts[] = $custom_prompt;
		} else {
			// Default system prompt.
			$parts[] = sprintf(
				/* translators: %s: activity title */
				__( 'You are a helpful AI assistant for the activity "%s". Help the learner understand the material and answer their questions clearly and accurately.', 'leaderspath' ),
				$activity->post_title
			);
		}

		// Add context files.
		$context_files = get_field( 'chatbot_context_files', $activity_id ) ?: [];
		if ( ! empty( $context_files ) ) {
			$parts[] = "\n\n--- Reference Materials ---";

			foreach ( $context_files as $context_id ) {
				$context_post = get_post( $context_id );
				if ( ! $context_post ) {
					continue;
				}

				$parts[] = sprintf(
					"\n\n### %s\n%s",
					$context_post->post_title,
					$context_post->post_content
				);
			}
		}

		// Add skills definitions.
		$skills = get_field( 'chatbot_skills', $activity_id ) ?: [];
		if ( ! empty( $skills ) ) {
			$parts[] = "\n\n--- Available Skills ---";

			foreach ( $skills as $skill_id ) {
				$skill_name = get_field( 'skill_name', $skill_id ) ?: get_the_title( $skill_id );
				$skill_desc = get_field( 'skill_description', $skill_id );

				if ( $skill_name && $skill_desc ) {
					$parts[] = sprintf(
						"\n\n### %s\n%s",
						$skill_name,
						$skill_desc
					);
				}
			}
		}

		/**
		 * Filter the system prompt before sending to Claude.
		 *
		 * @since 0.1.0
		 *
		 * @param string $prompt    The assembled system prompt.
		 * @param int    $activity_id The activity ID.
		 */
		$system_prompt = apply_filters( 'leaderspath_chatbot_system_prompt', implode( '', $parts ), $activity_id );

		return $system_prompt;
	}

	/**
	 * Build the system prompt for course Q&A chatbot.
	 *
	 * Unlike activity sandboxes (which demonstrate specific behaviors),
	 * the course Q&A bot is a helpful assistant for answering questions.
	 *
	 * @since 0.1.0
	 *
	 * @param int $course_id Course ID.
	 * @return string System prompt.
	 */
	private function build_course_system_prompt( int $course_id ): string {
		$course = get_post( $course_id );
		$parts  = [];

		// Start with custom system prompt if set.
		$custom_prompt = get_field( 'course_chatbot_system_prompt', $course_id );
		if ( ! empty( $custom_prompt ) ) {
			$parts[] = $custom_prompt;
		} else {
			// Default Q&A assistant prompt.
			$parts[] = sprintf(
				/* translators: %s: course title */
				__( 'You are a helpful Q&A assistant for the course "%s". Your role is to answer questions about the course content, clarify concepts, and help learners understand the material. Be accurate, clear, and supportive.', 'leaderspath' ),
				$course->post_title
			);
		}

		// Add course learning objectives if available.
		$objectives = get_field( 'course_objectives', $course_id );
		if ( ! empty( $objectives ) ) {
			$parts[]    = "\n\n--- Course Learning Objectives ---";
			$obj_number = 1;
			foreach ( $objectives as $objective ) {
				if ( ! empty( $objective['objective'] ) ) {
					$parts[] = sprintf( "\n%d. %s", $obj_number++, $objective['objective'] );
				}
			}
		}

		// Add learner overview if available.
		$learner_overview = get_field( 'course_learner_overview', $course_id );
		if ( ! empty( $learner_overview ) ) {
			$parts[] = "\n\n--- Course Overview ---\n";
			$parts[] = wp_strip_all_tags( $learner_overview );
		}

		// Add context files.
		$context_files = get_field( 'course_chatbot_context_files', $course_id ) ?: [];
		if ( ! empty( $context_files ) ) {
			$parts[] = "\n\n--- Reference Materials ---";

			foreach ( $context_files as $context_id ) {
				$context_post = get_post( $context_id );
				if ( ! $context_post ) {
					continue;
				}

				$parts[] = sprintf(
					"\n\n### %s\n%s",
					$context_post->post_title,
					$context_post->post_content
				);
			}
		}

		/**
		 * Filter the course Q&A system prompt before sending to Claude.
		 *
		 * @since 0.1.0
		 *
		 * @param string $prompt    The assembled system prompt.
		 * @param int    $course_id The course ID.
		 */
		$system_prompt = apply_filters( 'leaderspath_course_chatbot_system_prompt', implode( '', $parts ), $course_id );

		return $system_prompt;
	}

	/**
	 * Build messages array for API request.
	 *
	 * @since 0.1.0
	 *
	 * @param string $message User's current message.
	 * @param array  $history Previous conversation.
	 * @return array<int, array<string, string>> Messages array.
	 */
	private function build_messages( string $message, array $history ): array {
		$messages = [];

		// Add history.
		foreach ( $history as $item ) {
			if ( isset( $item['role'], $item['content'] ) ) {
				$messages[] = [
					'role'    => sanitize_text_field( $item['role'] ),
					'content' => sanitize_textarea_field( $item['content'] ),
				];
			}
		}

		// Add current message.
		$messages[] = [
			'role'    => 'user',
			'content' => $message,
		];

		return $messages;
	}

	/**
	 * Get skills for API request.
	 *
	 * Returns only skills that have been successfully synced to Anthropic.
	 *
	 * @since 0.1.0
	 *
	 * @param int $activity_id The lesson ID.
	 * @return array<int, array<string, string>> Skills array for container.
	 */
	private function get_skills_for_api( int $activity_id ): array {
		$skills = get_field( 'chatbot_skills', $activity_id ) ?: [];

		if ( empty( $skills ) ) {
			return [];
		}

		$skills_for_api = [];

		foreach ( $skills as $skill_id ) {
			$anthropic_id = get_field( 'skill_anthropic_id', $skill_id );
			$sync_status  = get_field( 'skill_sync_status', $skill_id );

			// Only include skills that have been synced to Anthropic.
			if ( $anthropic_id && 'synced' === $sync_status ) {
				$skills_for_api[] = [
					'type'     => 'custom',
					'skill_id' => $anthropic_id,
					'version'  => 'latest',
				];
			}
		}

		// Limit to max skills per request.
		return array_slice( $skills_for_api, 0, self::MAX_SKILLS_PER_REQUEST );
	}

	/**
	 * Handle pause_turn response for long-running operations.
	 *
	 * When code execution takes a long time, the API may return with
	 * stop_reason: "pause_turn". We need to continue the conversation
	 * to get the final result.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $activity_id The activity ID.
	 * @param array  $data      The initial response data.
	 * @param array  $messages  The conversation messages.
	 * @param string $model     The model slug.
	 * @param string $api_key   The API key.
	 * @param array  $headers   The request headers.
	 * @return array|WP_Error The final response or error.
	 */
	private function handle_pause_turn( int $activity_id, array $data, array $messages, string $model, string $api_key, array $headers ) {
		$max_continuations = 5; // Prevent infinite loops.
		$continuation      = 0;
		$container_id      = $data['container']['id'] ?? null;

		while ( isset( $data['stop_reason'] ) && 'pause_turn' === $data['stop_reason'] && $continuation < $max_continuations ) {
			$continuation++;

			// Add assistant's partial response to messages.
			$messages[] = [
				'role'    => 'assistant',
				'content' => $data['content'],
			];

			// Continue the conversation.
			$body = [
				'model'     => $this->resolve_model_id( $model ),
				'max_tokens' => (int) ( get_field( 'chatbot_max_tokens', $activity_id ) ?: 4096 ),
				'messages'  => $messages,
			];

			// Include container to continue in same execution environment.
			if ( $container_id ) {
				$body['container'] = [ 'id' => $container_id ];
			}

			$this->maybe_log( 'Continuation Request', [ 'attempt' => $continuation, 'body' => $body ] );

			$response = wp_remote_post(
				self::API_URL . '/messages',
				[
					'timeout' => 180,
					'headers' => $headers,
					'body'    => wp_json_encode( $body ),
				]
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$status_code = wp_remote_retrieve_response_code( $response );
			$body_raw    = wp_remote_retrieve_body( $response );
			$data        = json_decode( $body_raw, true );

			$this->maybe_log( 'Continuation Response', [ 'status' => $status_code, 'body' => $data ] );

			if ( $status_code >= 400 ) {
				return $this->handle_api_error( $status_code, $data );
			}

			// Update container ID if changed.
			if ( isset( $data['container']['id'] ) ) {
				$container_id = $data['container']['id'];
			}
		}

		// Extract final content.
		$content = $this->extract_response_content( $data );

		return [
			'content'      => $content,
			'model'        => $data['model'],
			'usage'        => $data['usage'] ?? [],
			'stop_reason'  => $data['stop_reason'] ?? null,
			'container_id' => $container_id,
		];
	}

	/**
	 * Extract text content from API response.
	 *
	 * Handles responses with multiple content blocks (text, tool use, tool results).
	 *
	 * @since 0.1.0
	 *
	 * @param array $data The API response data.
	 * @return string The extracted text content.
	 */
	private function extract_response_content( array $data ): string {
		if ( ! isset( $data['content'] ) || ! is_array( $data['content'] ) ) {
			return '';
		}

		$text_parts = [];

		foreach ( $data['content'] as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			// Extract text content.
			if ( isset( $block['type'] ) && 'text' === $block['type'] && isset( $block['text'] ) ) {
				$text_parts[] = $block['text'];
			}

			// Include code execution results in output (for transparency).
			if ( isset( $block['type'] ) && 'bash_code_execution_tool_result' === $block['type'] ) {
				$result = $block['content'] ?? [];
				if ( isset( $result['stdout'] ) && ! empty( $result['stdout'] ) ) {
					$text_parts[] = "\n```\n" . $result['stdout'] . "\n```\n";
				}
			}
		}

		return implode( "\n", $text_parts );
	}

	/**
	 * Handle API error responses.
	 *
	 * @since 0.1.0
	 *
	 * @param int   $status_code HTTP status code.
	 * @param array $data        Response data.
	 * @return WP_Error Error object.
	 */
	private function handle_api_error( int $status_code, array $data ): WP_Error {
		$error_type    = $data['error']['type'] ?? 'unknown_error';
		$error_message = $data['error']['message'] ?? __( 'An unknown error occurred.', 'leaderspath' );

		// Map common errors to user-friendly messages.
		$user_messages = [
			'authentication_error' => __( 'API authentication failed. Please check the API key configuration.', 'leaderspath' ),
			'rate_limit_error'     => __( 'Too many requests. Please wait a moment and try again.', 'leaderspath' ),
			'overloaded_error'     => __( 'The AI service is currently busy. Please try again in a moment.', 'leaderspath' ),
			'invalid_request_error' => __( 'Invalid request. Please try again.', 'leaderspath' ),
		];

		$user_message = $user_messages[ $error_type ] ?? $error_message;

		return new WP_Error(
			'claude_api_error',
			$user_message,
			[
				'status'     => $status_code,
				'error_type' => $error_type,
				'details'    => $error_message,
			]
		);
	}

	/**
	 * Log message if debug mode is enabled.
	 *
	 * @since 0.1.0
	 *
	 * @param string $label Log label.
	 * @param mixed  $data  Data to log.
	 */
	private function maybe_log( string $label, $data ): void {
		if ( ! \LeadersPath\Admin\Settings::is_debug_mode() ) {
			return;
		}

		// Sanitize sensitive data.
		if ( is_array( $data ) && isset( $data['system'] ) ) {
			// Truncate system prompt in logs.
			$data['system'] = substr( $data['system'], 0, 500 ) . '...';
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( sprintf(
			'[LeadersPath Claude API] %s: %s',
			$label,
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			print_r( $data, true )
		) );
	}

	/**
	 * Test the API connection.
	 *
	 * @since 0.1.0
	 *
	 * @return array|WP_Error Array with connection info or error.
	 */
	public function test_connection() {
		$api_key = \LeadersPath\Admin\Settings::get_api_key();

		if ( empty( $api_key ) ) {
			return new WP_Error(
				'api_key_missing',
				__( 'Claude API key is not configured.', 'leaderspath' )
			);
		}

		// Try to fetch models as a connection test.
		$models = $this->get_available_models( true );

		if ( is_wp_error( $models ) ) {
			return $models;
		}

		return [
			'status'       => 'connected',
			'model_count'  => count( $models ),
			'models'       => $models,
		];
	}

	/**
	 * Clear the models cache.
	 *
	 * @since 0.1.0
	 */
	public function clear_models_cache(): void {
		delete_transient( self::MODELS_TRANSIENT );
	}
}
