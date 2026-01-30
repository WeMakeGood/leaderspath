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
	 * Send a message to Claude.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $lesson_id Lesson ID for context.
	 * @param string $message   User message.
	 * @param array  $history   Previous conversation messages.
	 * @param string $model     Model slug (sonnet, haiku, opus-4.5).
	 * @return array|WP_Error Response data or error.
	 */
	public function send_message( int $lesson_id, string $message, array $history = [], string $model = 'sonnet' ) {
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
		$system_prompt = $this->build_system_prompt( $lesson_id );

		// Build messages array.
		$messages = $this->build_messages( $message, $history );

		// Get model settings.
		$max_tokens  = (int) ( get_field( 'chatbot_max_tokens', $lesson_id ) ?: 4096 );
		$temperature = (float) ( get_field( 'chatbot_temperature', $lesson_id ) ?? 0.7 );

		// Build request body.
		$body = [
			'model'       => $model_id,
			'max_tokens'  => $max_tokens,
			'system'      => $system_prompt,
			'messages'    => $messages,
		];

		// Only include temperature if not using extended thinking (opus).
		// Extended thinking requires temperature to be 1.
		if ( strpos( $model_id, 'opus' ) === false ) {
			$body['temperature'] = $temperature;
		}

		// Log request if debug mode is enabled.
		$this->maybe_log( 'Request', $body );

		// Make API request.
		$response = wp_remote_post(
			self::API_URL . '/messages',
			[
				'timeout' => 120,
				'headers' => [
					'Content-Type'      => 'application/json',
					'x-api-key'         => $api_key,
					'anthropic-version' => self::API_VERSION,
				],
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

		// Extract response content.
		if ( ! isset( $data['content'][0]['text'] ) ) {
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
		 * @param int    $lesson_id The lesson ID.
		 */
		do_action( 'leaderspath_chat_message_sent', $message, $data, get_current_user_id(), $lesson_id );

		return [
			'content'      => $data['content'][0]['text'],
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
	 * Build the system prompt with lesson context.
	 *
	 * @since 0.1.0
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return string System prompt.
	 */
	private function build_system_prompt( int $lesson_id ): string {
		$lesson = get_post( $lesson_id );
		$parts  = [];

		// Start with custom system prompt if set.
		$custom_prompt = get_field( 'chatbot_system_prompt', $lesson_id );
		if ( ! empty( $custom_prompt ) ) {
			$parts[] = $custom_prompt;
		} else {
			// Default system prompt.
			$parts[] = sprintf(
				/* translators: %s: lesson title */
				__( 'You are a helpful AI assistant for the lesson "%s". Help the learner understand the material and answer their questions clearly and accurately.', 'leaderspath' ),
				$lesson->post_title
			);
		}

		// Add lesson content as context.
		if ( ! empty( $lesson->post_content ) ) {
			$parts[] = "\n\n--- Lesson Content ---\n" . wp_strip_all_tags( $lesson->post_content );
		}

		// Add context files.
		$context_files = get_field( 'chatbot_context_files', $lesson_id ) ?: [];
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
		$skills = get_field( 'chatbot_skills', $lesson_id ) ?: [];
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
		 * @param int    $lesson_id The lesson ID.
		 */
		$system_prompt = apply_filters( 'leaderspath_chatbot_system_prompt', implode( '', $parts ), $lesson_id );

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
