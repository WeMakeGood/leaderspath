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
	 * Default max output tokens when an activity/lesson has none configured.
	 *
	 * Responses stream, so we can afford a generous ceiling (streaming supports
	 * up to 128K). 16384 comfortably fits long outputs like meeting reports;
	 * the stream also auto-continues on a max_tokens cut, so this is a starting
	 * budget, not a hard wall.
	 *
	 * @var int
	 */
	private const DEFAULT_MAX_TOKENS = 16384;

	/**
	 * Maximum number of retry attempts for transient API errors.
	 *
	 * @var int
	 */
	private const MAX_RETRIES = 2;

	/**
	 * Backoff delays (in seconds) between retry attempts.
	 *
	 * @var array<int, int>
	 */
	private const RETRY_DELAYS = [ 1, 3 ];

	/**
	 * HTTP status codes that are safe to retry.
	 *
	 * @var array<int, int>
	 */
	private const RETRYABLE_HTTP_CODES = [ 500, 502, 503, 529 ];

	/**
	 * Curl error numbers that are safe to retry.
	 *
	 * 7  = CURLE_COULDNT_CONNECT
	 * 28 = CURLE_OPERATION_TIMEDOUT
	 * 35 = CURLE_SSL_CONNECT_ERROR
	 * 56 = CURLE_RECV_ERROR
	 *
	 * @var array<int, int>
	 */
	private const RETRYABLE_CURL_ERRORS = [ 7, 28, 35, 56 ];

	/**
	 * Model ID prefixes that don't support programmatic tool calling for
	 * web_search/web_fetch.
	 *
	 * Discovered directly against the real API (2026-09-17, while building
	 * the Settings "Test API Versions" check): Haiku 4.5 rejects a request
	 * with these tools with `"does not support programmatic tool calling"`.
	 * Sonnet 5 was confirmed to accept the identical request. See
	 * docs/claude-api-integration.md.
	 *
	 * @var array<int, string>
	 */
	private const NO_PROGRAMMATIC_TOOL_CALLING_PREFIXES = [ 'claude-haiku' ];

	/**
	 * Send a message to Claude.
	 *
	 * Uses Container API with Skills and Code Execution when skills are configured.
	 *
	 * @since 0.1.0
	 *
	 * @param int         $activity_id      Activity ID for context.
	 * @param string      $message          User message.
	 * @param array       $history          Previous conversation messages.
	 * @param string      $model            Model slug (sonnet, haiku, opus).
	 * @param string|null $container_id     Container ID for session continuity.
	 * @param string|null $attached_file_id Anthropic file ID to attach to this turn (container_upload block), if any.
	 * @return array|WP_Error Response data or error.
	 */
	public function send_message( int $activity_id, string $message, array $history = [], string $model = 'sonnet', ?string $container_id = null, ?string $attached_file_id = null ) {
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
		$messages = $this->build_messages( $message, $history, $attached_file_id );

		// Get model settings.
		$max_tokens = (int) ( get_field( 'chatbot_max_tokens', $activity_id ) ?: self::DEFAULT_MAX_TOKENS );

		// Get skills for this activity (with valid Anthropic IDs).
		$skills_for_api = $this->get_skills_for_api( $activity_id );

		// Build request body.
		$body = [
			'model'       => $model_id,
			'max_tokens'  => $max_tokens,
			'system'      => $this->cacheable_system( $system_prompt ),
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
			$body['tools']     = $this->build_skills_tools( $model_id );
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
			$headers['anthropic-beta'] = \LeadersPath\Admin\Settings::get_beta_headers( [ 'code_execution', 'skills', 'web_tools' ] );
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

		// Handle non-JSON responses (e.g., Cloudflare HTML error pages, proxy timeouts).
		if ( ! is_array( $data ) ) {
			$this->maybe_log( 'Error', [ 'message' => 'Non-JSON response', 'body_preview' => substr( $body_raw, 0, 500 ) ] );
			return new WP_Error(
				'api_invalid_response',
				__( 'The AI service returned an unexpected response. Please try again.', 'leaderspath' ),
				[ 'status' => 502 ]
			);
		}

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
	 * Pre-warm the prompt cache for an activity or lesson.
	 *
	 * Sends a max_tokens:0 request whose prefix (tools + cached system prompt)
	 * byte-matches what the real chat request will send, so the API writes the
	 * cache during idle time (chat focus). The learner's first real message then
	 * reads the ~28K-token context from cache instead of processing it cold.
	 *
	 * max_tokens:0 runs prefill only — it writes the cache but does NOT provision
	 * a container (no generation → no code execution), and bills zero output
	 * tokens. Non-streaming (max_tokens:0 is rejected with stream:true).
	 * Best-effort: returns false on any problem; a cold first message still works.
	 *
	 * @since 0.12.0
	 *
	 * @param int $activity_id Activity ID, or 0 for a lesson.
	 * @param int $lesson_id   Lesson ID, or 0 for an activity.
	 * @return bool True if the cache write was accepted.
	 */
	public function warm_cache( int $activity_id = 0, int $lesson_id = 0 ): bool {
		$api_key = \LeadersPath\Admin\Settings::get_api_key();
		if ( empty( $api_key ) ) {
			return false;
		}

		if ( $activity_id ) {
			$model         = (string) ( get_field( 'chatbot_model', $activity_id ) ?: \LeadersPath\Admin\Settings::get_default_model() );
			$system_prompt = $this->build_system_prompt( $activity_id );
			$skills        = $this->get_skills_for_api( $activity_id );
		} elseif ( $lesson_id ) {
			$model         = (string) ( get_field( 'lesson_chatbot_model', $lesson_id ) ?: \LeadersPath\Admin\Settings::get_default_model() );
			$system_prompt = $this->build_lesson_system_prompt( $lesson_id );
			$skills        = []; // Lesson Q&A has no skills/container.
		} else {
			return false;
		}

		$model_id = $this->resolve_model_id( $model );
		if ( is_wp_error( $model_id ) ) {
			return false;
		}

		// Prefix must byte-match the real request for the cache to be reused:
		// same tools + same cached system block, in the same order.
		$body = [
			'model'      => $model_id,
			'max_tokens' => 0,
			'system'     => $this->cacheable_system( $system_prompt ),
			'messages'   => [ [ 'role' => 'user', 'content' => 'warmup' ] ],
		];

		$headers = [
			'Content-Type'      => 'application/json',
			'x-api-key'         => $api_key,
			'anthropic-version' => self::API_VERSION,
		];

		if ( ! empty( $skills ) ) {
			$body['container'] = [ 'skills' => $skills ];
			$body['tools']     = $this->build_skills_tools( $model_id );

			$headers['anthropic-beta'] = \LeadersPath\Admin\Settings::get_beta_headers( [ 'code_execution', 'skills', 'web_tools' ] );
		}

		$response = wp_remote_post(
			self::API_URL . '/messages',
			[
				'timeout' => 30,
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			$this->maybe_log( 'Warm Error', [ 'message' => $response->get_error_message() ] );
			return false;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );

		$created = is_array( $data ) ? ( $data['usage']['cache_creation_input_tokens'] ?? 0 ) : 0;
		$read    = is_array( $data ) ? ( $data['usage']['cache_read_input_tokens'] ?? 0 ) : 0;
		$this->maybe_log( 'Warm', "status={$status} cache_created={$created} cache_read={$read}" );

		return $status < 400;
	}

	/**
	 * Send a message to Claude for lesson Q&A chatbot.
	 *
	 * Similar to send_message but configured for lesson-level Q&A assistance.
	 *
	 * @since 0.1.0
	 *
	 * @param int         $lesson_id    Lesson ID for context.
	 * @param string      $message      User message.
	 * @param array       $history      Previous conversation messages.
	 * @param string      $model        Model slug (sonnet, haiku, opus).
	 * @param string|null $container_id Container ID for session continuity.
	 * @return array|WP_Error Response data or error.
	 */
	public function send_lesson_message( int $lesson_id, string $message, array $history = [], string $model = 'sonnet', ?string $container_id = null ) {
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

		// Build system prompt with lesson context.
		$system_prompt = $this->build_lesson_system_prompt( $lesson_id );

		// Build messages array.
		$messages = $this->build_messages( $message, $history );

		// Get model settings from lesson.
		$max_tokens = (int) ( get_field( 'lesson_chatbot_max_tokens', $lesson_id ) ?: self::DEFAULT_MAX_TOKENS );

		// Build request body.
		$body = [
			'model'       => $model_id,
			'max_tokens'  => $max_tokens,
			'system'      => $this->cacheable_system( $system_prompt ),
			'messages'    => $messages,
		];

		// Log request if debug mode is enabled.
		$this->maybe_log( 'Lesson Q&A Request', $body );

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
		$this->maybe_log( 'Lesson Q&A Response', [ 'status' => $status_code, 'body' => $data ] );

		// Handle non-JSON responses (e.g., Cloudflare HTML error pages, proxy timeouts).
		if ( ! is_array( $data ) ) {
			$this->maybe_log( 'Error', [ 'message' => 'Non-JSON response', 'body_preview' => substr( $body_raw, 0, 500 ) ] );
			return new WP_Error(
				'api_invalid_response',
				__( 'The AI service returned an unexpected response. Please try again.', 'leaderspath' ),
				[ 'status' => 502 ]
			);
		}

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
		 * Fires after a successful lesson Q&A chat message.
		 *
		 * @since 0.1.0
		 *
		 * @param string $message   The user's message.
		 * @param array  $data      The API response data.
		 * @param int    $user_id   The current user ID.
		 * @param int    $lesson_id The lesson ID.
		 */
		do_action( 'leaderspath_lesson_chat_message_sent', $message, $data, get_current_user_id(), $lesson_id );

		return [
			'content'      => $content,
			'model'        => $data['model'],
			'usage'        => $data['usage'] ?? [],
			'stop_reason'  => $data['stop_reason'] ?? null,
		];
	}

	/**
	 * Stream a message to Claude for activity sandbox.
	 *
	 * Outputs SSE events directly to the browser. Must be called after
	 * SSE headers have been sent and output buffers flushed.
	 *
	 * @since 0.9.0
	 *
	 * @param int         $activity_id      Activity ID for context.
	 * @param string      $message          User message.
	 * @param array       $history          Previous conversation messages.
	 * @param string      $model            Model slug (sonnet, haiku, opus).
	 * @param string|null $container_id     Container ID for session continuity.
	 * @param string|null $attached_file_id Anthropic file ID to attach to this turn (container_upload block), if any.
	 * @return WP_Error|null Null on success, WP_Error on pre-stream failure.
	 */
	public function stream_message( int $activity_id, string $message, array $history = [], string $model = 'sonnet', ?string $container_id = null, ?string $attached_file_id = null ): ?WP_Error {
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
		$messages = $this->build_messages( $message, $history, $attached_file_id );

		// Get model settings.
		$max_tokens = (int) ( get_field( 'chatbot_max_tokens', $activity_id ) ?: self::DEFAULT_MAX_TOKENS );

		// Get skills for this activity.
		$skills_for_api = $this->get_skills_for_api( $activity_id );

		// Build request body.
		$body = [
			'model'      => $model_id,
			'max_tokens' => $max_tokens,
			'system'     => $this->cacheable_system( $system_prompt ),
			'messages'   => $messages,
			'stream'     => true,
		];

		// Add container with skills if any skills are configured.
		if ( ! empty( $skills_for_api ) ) {
			$container = [ 'skills' => $skills_for_api ];

			if ( $container_id ) {
				$container['id'] = $container_id;
			}

			$body['container'] = $container;

			// Container reuse visibility: a reused (warm) container skips the slow
			// provisioning + skill-load on the first message. Fresh = slow start.
			$this->maybe_log(
				'Container',
				$container_id ? "reusing {$container_id} (warm)" : 'provisioning fresh (cold start — expected on first message)'
			);

			$body['tools'] = $this->build_skills_tools( $model_id );
		}

		$this->maybe_log( 'Stream Request', $body );

		// Build headers with beta features if skills are used.
		$headers = [
			'Content-Type'      => 'application/json',
			'x-api-key'         => $api_key,
			'anthropic-version' => self::API_VERSION,
		];

		if ( ! empty( $skills_for_api ) ) {
			$headers['anthropic-beta'] = \LeadersPath\Admin\Settings::get_beta_headers( [ 'code_execution', 'skills', 'web_tools' ] );
		}

		$error = $this->execute_stream( $body, $headers, $messages, $model );

		if ( is_wp_error( $error ) ) {
			return $error;
		}

		do_action( 'leaderspath_chat_message_sent', $message, [], get_current_user_id(), $activity_id );

		return null;
	}

	/**
	 * Stream a message to Claude for lesson Q&A chatbot.
	 *
	 * Outputs SSE events directly to the browser. Must be called after
	 * SSE headers have been sent and output buffers flushed.
	 *
	 * @since 0.9.0
	 *
	 * @param int    $lesson_id Lesson ID for context.
	 * @param string $message   User message.
	 * @param array  $history   Previous conversation messages.
	 * @param string $model     Model slug (sonnet, haiku, opus).
	 * @return WP_Error|null Null on success, WP_Error on pre-stream failure.
	 */
	public function stream_lesson_message( int $lesson_id, string $message, array $history = [], string $model = 'sonnet' ): ?WP_Error {
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

		// Build system prompt with lesson context.
		$system_prompt = $this->build_lesson_system_prompt( $lesson_id );

		// Build messages array.
		$messages = $this->build_messages( $message, $history );

		// Get model settings from lesson.
		$max_tokens = (int) ( get_field( 'lesson_chatbot_max_tokens', $lesson_id ) ?: self::DEFAULT_MAX_TOKENS );

		// Build request body.
		$body = [
			'model'      => $model_id,
			'max_tokens' => $max_tokens,
			'system'     => $this->cacheable_system( $system_prompt ),
			'messages'   => $messages,
			'stream'     => true,
		];

		$this->maybe_log( 'Stream Lesson Request', $body );

		// Build headers.
		$headers = [
			'Content-Type'      => 'application/json',
			'x-api-key'         => $api_key,
			'anthropic-version' => self::API_VERSION,
		];

		$error = $this->execute_stream( $body, $headers, $messages, $model );

		if ( is_wp_error( $error ) ) {
			return $error;
		}

		do_action( 'leaderspath_lesson_chat_message_sent', $message, [], get_current_user_id(), $lesson_id );

		return null;
	}

	/**
	 * Execute a streaming request to the Anthropic API.
	 *
	 * Forwards SSE events from Anthropic directly to the browser output.
	 * Parses events to extract metadata and accumulate content for the
	 * final [DONE] event.
	 *
	 * Handles pause_turn by initiating continuation requests (max 5).
	 *
	 * @since 0.9.0
	 *
	 * @param array  $body     Request body (must include 'stream' => true).
	 * @param array  $headers  Request headers.
	 * @param array  $messages Conversation messages (for pause_turn continuation).
	 * @param string $model    Model slug (for pause_turn continuation).
	 * @return WP_Error|null Null on success, WP_Error on failure.
	 */
	private function execute_stream( array $body, array $headers, array $messages, string $model ): ?WP_Error {
		// State tracking across chunks.
		$state = [
			'container_id' => null,
			'model'        => null,
			'usage'        => [ 'input_tokens' => 0, 'output_tokens' => 0 ],
			'content_raw'  => '',
			'stop_reason'  => null,
			'buffer'       => '',
			'content'      => [], // Accumulated content blocks for pause_turn.
			'last_activity' => microtime( true ), // For keep-alive tracking.
		];

		// Flush all WordPress output buffers.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		while ( @ob_get_level() ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@ob_end_flush();
		}

		$max_continuations = 5;
		$continuation      = 0;
		$retry_attempt     = 0;
		$original_body     = $body; // Preserve for retries.

		do {
			$ch = curl_init();

			if ( false === $ch ) {
				return new WP_Error(
					'curl_init_failed',
					__( 'Failed to initialize streaming connection.', 'leaderspath' ),
					[ 'status' => 500 ]
				);
			}

			// Reset per-request state.
			$state['buffer']      = '';
			$state['stop_reason'] = null;
			unset( $state['http_error'] );
			$state['error_body']  = '';

			// Build curl headers array.
			$curl_headers = [];
			foreach ( $headers as $key => $value ) {
				$curl_headers[] = $key . ': ' . $value;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt_array
			curl_setopt_array( $ch, [
				CURLOPT_URL            => self::API_URL . '/messages',
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => wp_json_encode( $body ),
				CURLOPT_HTTPHEADER     => $curl_headers,
				CURLOPT_RETURNTRANSFER => false,
				CURLOPT_TIMEOUT        => 300,
				CURLOPT_CONNECTTIMEOUT => 30,
				// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
				CURLOPT_WRITEFUNCTION  => function ( $ch, $chunk ) use ( &$state ) {
					return $this->handle_stream_chunk( $chunk, $state );
				},
				CURLOPT_HEADERFUNCTION => function ( $ch, $header_line ) use ( &$state ) {
					// Check for HTTP error status codes before streaming starts.
					if ( preg_match( '/^HTTP\/\S+\s+(\d{3})/', $header_line, $matches ) ) {
						$status = (int) $matches[1];
						if ( $status >= 400 ) {
							$state['http_error'] = $status;
						}
					}
					return strlen( $header_line );
				},
				// Enable progress callbacks for SSE keep-alive.
				// During web_search/web_fetch, Anthropic may go 30+ seconds with no data.
				// Nginx kills idle FastCGI connections (default 60s). Sending periodic
				// SSE comments (": keepalive") keeps the Nginx → PHP-FPM connection alive.
				CURLOPT_NOPROGRESS     => false,
				// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
				CURLOPT_PROGRESSFUNCTION => function ( $ch, $dl_total, $dl_now, $ul_total, $ul_now ) use ( &$state ) {
					$now     = microtime( true );
					$elapsed = $now - $state['last_activity'];

					// Send keep-alive every 15 seconds of inactivity.
					if ( $elapsed >= 15.0 ) {
						echo ": keepalive\n\n";
						flush();
						$state['last_activity'] = $now;
					}

					return 0; // 0 = continue, non-zero = abort.
				},
			] );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec
			$result = curl_exec( $ch );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_errno
			$curl_errno = curl_errno( $ch );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_error
			$curl_error = curl_error( $ch );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_getinfo
			$http_code  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
			curl_close( $ch );

			if ( 0 !== $curl_errno ) {
				$this->maybe_log( 'Stream Error', [ 'curl_errno' => $curl_errno, 'curl_error' => $curl_error, 'attempt' => $retry_attempt + 1 ] );

				// Retry transient curl errors.
				if ( $this->is_retryable_error( 0, $curl_errno ) && $retry_attempt < self::MAX_RETRIES ) {
					$delay = self::RETRY_DELAYS[ $retry_attempt ] ?? 3;
					$retry_attempt++;

					$this->send_sse_event( 'retry', wp_json_encode( [
						'attempt' => $retry_attempt + 1,
						'max'     => self::MAX_RETRIES + 1,
						'delay'   => $delay,
					] ) );

					sleep( $delay );

					// Reset state for fresh attempt.
					$state['content_raw'] = '';
					$state['content']     = [];
					$body                 = $original_body;
					$continuation         = 0;
					continue;
				}

				$this->send_sse_error( __( 'Connection to AI service failed.', 'leaderspath' ) );
				break;
			}

			if ( $http_code >= 400 ) {
				// Extract error message for logging/display.
				$error_message = __( 'The AI service returned an error. Please try again.', 'leaderspath' );
				if ( ! empty( $state['error_body'] ) ) {
					$error_data = json_decode( $state['error_body'], true );
					if ( is_array( $error_data ) && ! empty( $error_data['error']['message'] ) ) {
						$error_message = $error_data['error']['message'];
					}
				}

				$this->maybe_log( 'Stream HTTP Error', [ 'status' => $http_code, 'body' => $state['error_body'] ?? '', 'attempt' => $retry_attempt + 1 ] );

				// Retry transient HTTP errors.
				if ( $this->is_retryable_error( $http_code, 0 ) && $retry_attempt < self::MAX_RETRIES ) {
					$delay = self::RETRY_DELAYS[ $retry_attempt ] ?? 3;
					$retry_attempt++;

					$this->send_sse_event( 'retry', wp_json_encode( [
						'attempt' => $retry_attempt + 1,
						'max'     => self::MAX_RETRIES + 1,
						'delay'   => $delay,
					] ) );

					sleep( $delay );

					// Reset state for fresh attempt.
					$state['content_raw'] = '';
					$state['content']     = [];
					$body                 = $original_body;
					$continuation         = 0;
					continue;
				}

				$this->send_sse_error( $error_message );
				break;
			}

			// Successful response — reset retry counter.
			$retry_attempt = 0;

			// Continue the response when it was cut short. Two cases:
			//   pause_turn  — the model paused for a long tool/code operation.
			//   max_tokens  — the model hit the output-token ceiling mid-response
			//                 (e.g. a long meeting report). Appending the partial
			//                 and continuing lets it finish seamlessly instead of
			//                 truncating. The container is reused (warm, skills
			//                 already loaded), so the continuation needs only
			//                 model/max_tokens/messages/stream + container id.
			$needs_continuation = in_array( $state['stop_reason'], [ 'pause_turn', 'max_tokens' ], true );

			// Only continue when there is partial assistant content to build on —
			// appending an empty assistant turn would be rejected by the API.
			$has_partial = ! empty( array_values( $state['content'] ) );

			if ( $needs_continuation && $has_partial && $continuation < $max_continuations ) {
				$continuation++;

				$event_name = ( 'max_tokens' === $state['stop_reason'] ) ? 'continue' : 'pause';
				$this->send_sse_event( $event_name, wp_json_encode( [ 'continuation' => $continuation ] ) );

				// Add assistant's partial response to messages for continuation.
				$messages[]       = [
					'role'    => 'assistant',
					'content' => array_values( $state['content'] ),
				];
				$state['content'] = [];

				// Build continuation body.
				$body = [
					'model'      => $body['model'],
					'max_tokens' => $body['max_tokens'],
					'messages'   => $messages,
					'stream'     => true,
				];

				if ( $state['container_id'] ) {
					$body['container'] = [ 'id' => $state['container_id'] ];
				}

				$this->maybe_log( 'Stream Continuation', [
					'attempt'     => $continuation,
					'stop_reason' => $state['stop_reason'],
				] );
				continue;
			}

			break;
		} while ( true );

		// Send final [DONE] event with metadata.
		$done_data = [
			'container_id' => $state['container_id'],
			'model'        => $state['model'],
			'usage'        => $state['usage'],
			'content_raw'  => $state['content_raw'],
			'stop_reason'  => $state['stop_reason'],
		];

		$this->send_sse_event( 'done', wp_json_encode( $done_data ) );

		return null;
	}

	/**
	 * Handle a chunk of SSE data from the Anthropic API.
	 *
	 * Forwards the raw chunk to the browser and parses events to
	 * extract metadata.
	 *
	 * @since 0.9.0
	 *
	 * @param string $chunk Raw SSE data chunk.
	 * @param array  $state Reference to shared state array.
	 * @return int Number of bytes handled (must match chunk length for curl).
	 */
	private function handle_stream_chunk( string $chunk, array &$state ): int {
		$length = strlen( $chunk );

		// If Anthropic returned an HTTP error, the body is raw JSON (not SSE).
		// Accumulate it for error reporting — do NOT forward to browser.
		if ( ! empty( $state['http_error'] ) ) {
			$state['error_body'] = ( $state['error_body'] ?? '' ) . $chunk;
			return $length;
		}

		// Forward raw SSE data to browser.
		echo $chunk;
		flush();

		// Track activity for keep-alive.
		$state['last_activity'] = microtime( true );

		// Parse SSE events from the chunk.
		$state['buffer'] .= $chunk;

		while ( ( $pos = strpos( $state['buffer'], "\n\n" ) ) !== false ) {
			$raw_event       = substr( $state['buffer'], 0, $pos );
			$state['buffer'] = substr( $state['buffer'], $pos + 2 );

			$this->parse_sse_event( $raw_event, $state );
		}

		return $length;
	}

	/**
	 * Parse a single SSE event and update state.
	 *
	 * @since 0.9.0
	 *
	 * @param string $raw_event Raw SSE event text.
	 * @param array  $state     Reference to shared state array.
	 */
	private function parse_sse_event( string $raw_event, array &$state ): void {
		$event_type = '';
		$data       = '';

		foreach ( explode( "\n", $raw_event ) as $line ) {
			if ( strpos( $line, 'event: ' ) === 0 ) {
				$event_type = substr( $line, 7 );
			} elseif ( strpos( $line, 'data: ' ) === 0 ) {
				$data = substr( $line, 6 );
			}
		}

		if ( '' === $data ) {
			return;
		}

		$parsed = json_decode( $data, true );
		if ( ! is_array( $parsed ) ) {
			return;
		}

		switch ( $event_type ) {
			case 'message_start':
				$msg = $parsed['message'] ?? [];
				$state['container_id'] = $msg['container']['id'] ?? $state['container_id'];
				$state['model']        = $msg['model'] ?? $state['model'];
				if ( isset( $msg['usage']['input_tokens'] ) ) {
					$state['usage']['input_tokens'] = $msg['usage']['input_tokens'];
				}
				// Prompt-cache visibility: cache_read > 0 means the context prefix
				// was served from cache (fast); cache_creation > 0 means this
				// request wrote it (the slow first message). Lets us measure the
				// caching win in debug mode.
				$cache_read     = $msg['usage']['cache_read_input_tokens'] ?? null;
				$cache_creation = $msg['usage']['cache_creation_input_tokens'] ?? null;
				if ( null !== $cache_read || null !== $cache_creation ) {
					$this->maybe_log( 'Cache', sprintf(
						'read=%d created=%d uncached_input=%d',
						(int) $cache_read,
						(int) $cache_creation,
						(int) ( $msg['usage']['input_tokens'] ?? 0 )
					) );
				}
				break;

			case 'content_block_start':
				// Track content block for pause_turn continuation.
				$block = $parsed['content_block'] ?? [];
				$index = $parsed['index'] ?? count( $state['content'] );
				if ( 'text' === ( $block['type'] ?? '' ) ) {
					$state['content'][ $index ] = [ 'type' => 'text', 'text' => '' ];
				} else {
					$state['content'][ $index ] = $block;
				}
				break;

			case 'content_block_delta':
				$delta = $parsed['delta'] ?? [];
				$index = $parsed['index'] ?? null;
				if ( 'text_delta' === ( $delta['type'] ?? '' ) && isset( $delta['text'] ) ) {
					$state['content_raw'] .= $delta['text'];
					// Accumulate into content block for pause_turn replay.
					if ( null !== $index && isset( $state['content'][ $index ] ) ) {
						$state['content'][ $index ]['text'] = ( $state['content'][ $index ]['text'] ?? '' ) . $delta['text'];
					}
				}
				break;

			case 'content_block_stop':
				// Content block finalized — no action needed.
				break;

			case 'message_delta':
				$delta = $parsed['delta'] ?? [];
				$state['stop_reason'] = $delta['stop_reason'] ?? $state['stop_reason'];
				if ( isset( $parsed['usage']['output_tokens'] ) ) {
					$state['usage']['output_tokens'] = $parsed['usage']['output_tokens'];
				}
				break;
		}
	}

	/**
	 * Send a custom SSE event to the browser.
	 *
	 * @since 0.9.0
	 *
	 * @param string $event Event name.
	 * @param string $data  JSON data.
	 */
	private function send_sse_event( string $event, string $data ): void {
		echo 'event: ' . $event . "\n";
		echo 'data: ' . $data . "\n\n";
		flush();
	}

	/**
	 * Send an SSE error event to the browser.
	 *
	 * @since 0.9.0
	 *
	 * @param string $message Error message.
	 */
	private function send_sse_error( string $message ): void {
		$this->send_sse_event( 'error', wp_json_encode( [ 'message' => $message ] ) );
	}

	/**
	 * Build the `tools` array for a skills-enabled request.
	 *
	 * Always includes `code_execution`. Excludes `web_search`/`web_fetch`
	 * when the resolved model doesn't support programmatic tool calling
	 * for them (see `NO_PROGRAMMATIC_TOOL_CALLING_PREFIXES`) — Anthropic
	 * rejects the entire request otherwise, not just the unsupported
	 * tools. No shipped activity configures Haiku with skills today, but
	 * `chatbot_model` allows any facilitator to pick Haiku for any
	 * activity, skills included, so this guards a real, reachable
	 * misconfiguration rather than a hypothetical one.
	 *
	 * @since 0.7.0
	 *
	 * @param string $model_id Resolved model ID (e.g. 'claude-sonnet-5').
	 * @return array<int, array<string, string>> Tools array for the request body.
	 */
	private function build_skills_tools( string $model_id ): array {
		$tool_type = \LeadersPath\Admin\Settings::get_code_execution_tool_type();

		$tools = [
			[ 'type' => $tool_type, 'name' => 'code_execution' ],
		];

		foreach ( self::NO_PROGRAMMATIC_TOOL_CALLING_PREFIXES as $prefix ) {
			if ( 0 === strpos( $model_id, $prefix ) ) {
				return $tools;
			}
		}

		$web_tools = \LeadersPath\Admin\Settings::get_web_tool_types();

		$tools[] = [ 'type' => $web_tools['web_search'], 'name' => 'web_search' ];
		$tools[] = [ 'type' => $web_tools['web_fetch'], 'name' => 'web_fetch' ];

		return $tools;
	}

	/**
	 * Resolve a model slug to an actual model ID.
	 *
	 * @since 0.1.0
	 *
	 * @param string $slug Model slug (e.g., 'sonnet', 'haiku', 'opus').
	 * @return string|WP_Error Model ID or error.
	 */
	private function resolve_model_id( string $slug ) {
		// Normalize the slug. Accept bare family slugs (sonnet/haiku/opus) and
		// tolerate any legacy version-suffixed form (e.g. opus-4.5) by reducing
		// to the family prefix.
		$slug   = strtolower( trim( $slug ) );
		$family = preg_replace( '/-[0-9.]+$/', '', $slug );

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
				'sonnet' => 'claude-sonnet-5',
				'haiku'  => 'claude-haiku-4-5',
				'opus'   => 'claude-opus-4-8',
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
				'sonnet' => 'claude-sonnet-5',
				'haiku'  => 'claude-haiku-4-5',
				'opus'   => 'claude-opus-4-8',
			];
			return $fallbacks[ $family ] ?? $fallbacks['sonnet'];
		}

		// Prefer a bare current alias (e.g. "claude-opus-4-8") over dated
		// snapshots (e.g. "claude-opus-4-5-20251101") when the API offers one —
		// bare IDs are the recommended, always-current aliases. Fall back to the
		// lexically-last dated ID only when no bare alias exists.
		$bare = array_values(
			array_filter(
				$matches,
				static fn( string $id ): bool => ! preg_match( '/-\d{8}$/', $id )
			)
		);
		if ( ! empty( $bare ) ) {
			sort( $bare );
			return end( $bare );
		}

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

		if ( ! is_array( $body ) ) {
			return new WP_Error(
				'api_invalid_response',
				__( 'The AI service returned an unexpected response.', 'leaderspath' )
			);
		}

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
	 * Wrap a system prompt string as a cached content-block array.
	 *
	 * The LeadersPath system prompt is stable per activity/lesson (custom prompt
	 * + context files + skill descriptions — no per-request/volatile content), so
	 * it is an ideal prompt-cache prefix. Caching it means the model re-processes
	 * the (often large) context only on the first request of a session; every
	 * later message and every auto-continuation reads it from cache (~10% cost,
	 * far faster to process). Render order is tools -> system -> messages, so a
	 * breakpoint here also caches the tools that precede it.
	 *
	 * @since 0.12.0
	 *
	 * @param string $system_prompt The assembled system prompt.
	 * @return array<int, array<string, mixed>> System content blocks with a cache breakpoint.
	 */
	private function cacheable_system( string $system_prompt ): array {
		return [
			[
				'type'          => 'text',
				'text'          => $system_prompt,
				'cache_control' => [ 'type' => 'ephemeral' ],
			],
		];
	}

	/**
	 * Build the system prompt for lesson Q&A chatbot.
	 *
	 * Unlike activity sandboxes (which demonstrate specific behaviors),
	 * the lesson Q&A bot is a helpful assistant for answering questions.
	 *
	 * @since 0.1.0
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return string System prompt.
	 */
	private function build_lesson_system_prompt( int $lesson_id ): string {
		$lesson = get_post( $lesson_id );
		$parts  = [];

		// Start with custom system prompt if set.
		$custom_prompt = get_field( 'lesson_chatbot_system_prompt', $lesson_id );
		if ( ! empty( $custom_prompt ) ) {
			$parts[] = $custom_prompt;
		} else {
			// Default Q&A assistant prompt.
			$parts[] = sprintf(
				/* translators: %s: lesson title */
				__( 'You are a helpful Q&A assistant for the lesson "%s". Your role is to answer questions about the lesson content, clarify concepts, and help learners understand the material. Be accurate, clear, and supportive.', 'leaderspath' ),
				$lesson->post_title
			);
		}

		// Add lesson learning objectives if available.
		$objectives = get_field( 'lesson_objectives', $lesson_id );
		if ( ! empty( $objectives ) ) {
			$parts[]    = "\n\n--- Lesson Learning Objectives ---";
			$obj_number = 1;
			foreach ( $objectives as $objective ) {
				if ( ! empty( $objective['objective'] ) ) {
					$parts[] = sprintf( "\n%d. %s", $obj_number++, $objective['objective'] );
				}
			}
		}

		// Add learner overview if available.
		$learner_overview = get_field( 'lesson_learner_overview', $lesson_id );
		if ( ! empty( $learner_overview ) ) {
			$parts[] = "\n\n--- Lesson Overview ---\n";
			$parts[] = wp_strip_all_tags( $learner_overview );
		}

		// Add context files.
		$context_files = get_field( 'lesson_chatbot_context_files', $lesson_id ) ?: [];
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
		 * Filter the lesson Q&A system prompt before sending to Claude.
		 *
		 * @since 0.1.0
		 *
		 * @param string $prompt    The assembled system prompt.
		 * @param int    $lesson_id The lesson ID.
		 */
		$system_prompt = apply_filters( 'leaderspath_lesson_chatbot_system_prompt', implode( '', $parts ), $lesson_id );

		return $system_prompt;
	}

	/**
	 * Build messages array for API request.
	 *
	 * When $attached_file_id is set, the current turn's content becomes a
	 * content-block array (text + container_upload) instead of a plain
	 * string, per Anthropic's Files API container-attachment contract —
	 * this only makes sense when a container exists, i.e. skills-enabled
	 * activities (see Claude_API::get_skills_for_api()).
	 *
	 * @since 0.1.0
	 *
	 * @param string      $message          User's current message.
	 * @param array       $history          Previous conversation.
	 * @param string|null $attached_file_id Anthropic file ID to attach to this turn, if any.
	 * @return array<int, array<string, mixed>> Messages array.
	 */
	private function build_messages( string $message, array $history, ?string $attached_file_id = null ): array {
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
		if ( $attached_file_id ) {
			$messages[] = [
				'role'    => 'user',
				'content' => [
					[
						'type' => 'text',
						'text' => $message,
					],
					[
						'type'    => 'container_upload',
						'file_id' => $attached_file_id,
					],
				],
			];
		} else {
			$messages[] = [
				'role'    => 'user',
				'content' => $message,
			];
		}

		return $messages;
	}

	/**
	 * Get skills for API request.
	 *
	 * Returns only skills that have been successfully synced to Anthropic.
	 *
	 * Public: also called by Chatbot_Renderer to decide whether an activity's
	 * chat widget should render the file-upload UI (skills-enabled activities
	 * only — see docs/TASKS.md Phase 15, Story 4).
	 *
	 * @since 0.1.0
	 *
	 * @param int $activity_id The lesson ID.
	 * @return array<int, array<string, string>> Skills array for container.
	 */
	public function get_skills_for_api( int $activity_id ): array {
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
				'max_tokens' => (int) ( get_field( 'chatbot_max_tokens', $activity_id ) ?: self::DEFAULT_MAX_TOKENS ),
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

			if ( ! is_array( $data ) ) {
				$this->maybe_log( 'Error', [ 'message' => 'Non-JSON continuation response', 'body_preview' => substr( $body_raw, 0, 500 ) ] );
				return new WP_Error(
					'api_invalid_response',
					__( 'The AI service returned an unexpected response during processing. Please try again.', 'leaderspath' ),
					[ 'status' => 502 ]
				);
			}

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
	 * Check if an error is transient and safe to retry.
	 *
	 * @since 0.10.0
	 *
	 * @param int $http_code  HTTP status code (0 if curl error).
	 * @param int $curl_errno Curl error number (0 if HTTP error).
	 * @return bool True if the error is retryable.
	 */
	private function is_retryable_error( int $http_code, int $curl_errno ): bool {
		if ( 0 !== $curl_errno ) {
			return in_array( $curl_errno, self::RETRYABLE_CURL_ERRORS, true );
		}

		return in_array( $http_code, self::RETRYABLE_HTTP_CODES, true );
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

		// Sanitize sensitive data. `system` may be a string (legacy) or a
		// cacheable content-block array (see cacheable_system()); truncate the
		// text either way so logs stay readable.
		if ( is_array( $data ) && isset( $data['system'] ) ) {
			if ( is_string( $data['system'] ) ) {
				$data['system'] = substr( $data['system'], 0, 500 ) . '...';
			} elseif ( is_array( $data['system'] ) ) {
				$text            = $data['system'][0]['text'] ?? '';
				$data['system']  = '[cached] ' . substr( (string) $text, 0, 500 ) . '...';
			}
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
	 * Test the configured code-execution/web-tools API version settings.
	 *
	 * A `max_tokens: 0` request (prefill only — no generation, no container
	 * provisioned, zero output tokens billed; same pattern as
	 * `warm_cache()`) exercising the `code_execution` tool plus
	 * `web_search`/`web_fetch` tool types and the `beta_code_execution`/
	 * `beta_web_tools` beta headers together, using whatever the admin has
	 * currently configured in Settings. A rejected tool type or beta header
	 * surfaces as a real `invalid_request_error` from Anthropic — this is
	 * a genuine test of "does this still work," not a guess.
	 *
	 * Deliberately does **not** cover `beta_skills`/`beta_files` — those
	 * only apply once a real skill_id is referenced in a `container`, and
	 * there's no cheap, side-effect-free way to exercise them without
	 * uploading/referencing a real skill. See docs/claude-api-integration.md
	 * and CLAUDE.md's "API Version Configuration" section: catching that
	 * those two have gone *stale* (still valid, but superseded by something
	 * newer/better-supported) isn't something any connectivity test can
	 * catch anyway — it takes checking Anthropic's changelog directly.
	 *
	 * @since 0.7.0
	 *
	 * @return array|WP_Error Array with 'status' on success, WP_Error with
	 *                        the raw Anthropic error message on failure.
	 */
	public function test_api_versions() {
		$api_key = \LeadersPath\Admin\Settings::get_api_key();

		if ( empty( $api_key ) ) {
			return new WP_Error(
				'api_key_missing',
				__( 'Claude API key is not configured.', 'leaderspath' )
			);
		}

		// Sonnet, not Haiku: Haiku 4.5 rejects web_search/web_fetch with
		// "does not support programmatic tool calling" — a real constraint
		// discovered while building this check (see docs/TASKS.md Phase 14
		// and the new "Model tool-calling support" note in
		// docs/claude-api-integration.md). No shipped activity currently
		// configures Haiku with skills enabled, so this is latent, not
		// active, but the test itself must use a model that's actually
		// expected to support these tools.
		$model_id = $this->resolve_model_id( 'sonnet' );

		if ( is_wp_error( $model_id ) ) {
			return $model_id;
		}

		$body = [
			'model'      => $model_id,
			'max_tokens' => 0,
			'messages'   => [ [ 'role' => 'user', 'content' => 'test' ] ],
			'tools'      => $this->build_skills_tools( $model_id ),
		];

		$headers = [
			'Content-Type'      => 'application/json',
			'x-api-key'         => $api_key,
			'anthropic-version' => self::API_VERSION,
			'anthropic-beta'    => \LeadersPath\Admin\Settings::get_beta_headers( [ 'code_execution', 'web_tools' ] ),
		];

		$response = wp_remote_post(
			self::API_URL . '/messages',
			[
				'timeout' => 30,
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_request_failed',
				__( 'Failed to connect to Claude API.', 'leaderspath' )
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$data        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code >= 400 ) {
			// Surface Anthropic's raw message directly (not the generic
			// handle_api_error() mapping) — an admin diagnosing a stale
			// tool type/beta header needs the actual rejection reason.
			$message = is_array( $data ) ? ( $data['error']['message'] ?? '' ) : '';

			return new WP_Error(
				'api_version_invalid',
				$message ?: __( 'The API rejected the configured tool types or beta headers.', 'leaderspath' )
			);
		}

		return [ 'status' => 'valid' ];
	}

	/**
	 * Clear the models cache.
	 *
	 * @since 0.1.0
	 */
	public function clear_models_cache(): void {
		delete_transient( self::MODELS_TRANSIENT );
	}

	/**
	 * Upload a file to Anthropic's Files API for use via container_upload.
	 *
	 * The returned file ID is attached to the *next* chat turn's content
	 * blocks (see build_messages()) so the code-execution container can
	 * read it. Only meaningful for skills-enabled activities — a container
	 * is only provisioned when skills are configured (see
	 * get_skills_for_api()); the REST layer is responsible for rejecting
	 * uploads against skill-less activities before this is ever called.
	 *
	 * Reuses the same hand-built multipart pattern as
	 * Skill_Processor::build_multipart_body() (boundary via
	 * wp_generate_password(), manual Content-Disposition), adapted for a
	 * single generic file field. Anthropic's Files API expects a `file`
	 * field (singular, not `files[]` like the Skills upload endpoint) and
	 * returns `{"id": "file_...", "type": "file", ...}` — both confirmed
	 * against platform.claude.com/docs/en/build-with-claude/files,
	 * 2026-09-17.
	 *
	 * @since 0.13.0
	 *
	 * @param string $file_path        Path to the temporary uploaded file on disk.
	 * @param string $original_filename Original client-side filename (for Content-Disposition).
	 * @param string $mime_type        The file's MIME type (for Content-Type).
	 * @param string $api_key          The Anthropic API key.
	 * @return array|WP_Error {@type string $id Anthropic file ID.} or error.
	 */
	public function upload_file( string $file_path, string $original_filename, string $mime_type, string $api_key ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$file_content = file_get_contents( $file_path );

		if ( false === $file_content ) {
			return new WP_Error(
				'file_read_error',
				__( 'Could not read the uploaded file.', 'leaderspath' ),
				[ 'status' => 500 ]
			);
		}

		$boundary = wp_generate_password( 24, false );

		$body  = "--{$boundary}\r\n";
		$body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$original_filename}\"\r\n";
		$body .= "Content-Type: {$mime_type}\r\n\r\n";
		$body .= $file_content . "\r\n";
		$body .= "--{$boundary}--\r\n";

		$response = wp_remote_post(
			self::API_URL . '/files',
			[
				'timeout' => 60,
				'headers' => [
					'Content-Type'      => 'multipart/form-data; boundary=' . $boundary,
					'x-api-key'         => $api_key,
					'anthropic-version' => self::API_VERSION,
					'anthropic-beta'    => \LeadersPath\Admin\Settings::get_beta_headers( [ 'files' ] ),
				],
				'body'    => $body,
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_request_failed',
				/* translators: %s: error message */
				sprintf( __( 'Failed to connect to Anthropic API: %s', 'leaderspath' ), $response->get_error_message() )
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$data        = json_decode( wp_remote_retrieve_body( $response ), true );

		$this->maybe_log( 'Upload Response', [ 'status' => $status_code, 'body' => $data ] );

		if ( $status_code >= 400 ) {
			$message = is_array( $data ) ? ( $data['error']['message'] ?? '' ) : '';

			return new WP_Error(
				'file_upload_failed',
				$message ?: __( 'The AI service rejected the file upload.', 'leaderspath' ),
				[ 'status' => $status_code ]
			);
		}

		if ( ! is_array( $data ) || empty( $data['id'] ) ) {
			return new WP_Error(
				'api_invalid_response',
				__( 'The AI service returned an unexpected response to the file upload.', 'leaderspath' ),
				[ 'status' => 502 ]
			);
		}

		return [ 'id' => $data['id'] ];
	}
}
