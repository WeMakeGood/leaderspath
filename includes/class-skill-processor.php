<?php
/**
 * Skill Package Processor.
 *
 * Handles extraction and validation of skill packages (ZIP files containing SKILL.md).
 * Auto-populates ACF fields from YAML frontmatter when a skill package is uploaded.
 * Uploads skill packages to Anthropic Skills API for code execution support.
 *
 * @package LeadersPath
 * @since   0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

use LeadersPath\Admin\Settings;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use WP_Error;
use ZipArchive;

/**
 * Processes uploaded skill packages and extracts metadata.
 *
 * @since 0.1.0
 */
class Skill_Processor {

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
	 * Transient key prefix for storing validation errors.
	 *
	 * @var string
	 */
	private const TRANSIENT_KEY_PREFIX = 'leaderspath_skill_errors_';

	/**
	 * Transient key prefix for storing sync success messages.
	 *
	 * @var string
	 */
	private const TRANSIENT_SUCCESS_PREFIX = 'leaderspath_skill_success_';

	/**
	 * Reserved words that cannot appear in skill names.
	 *
	 * @var array<string>
	 */
	private const RESERVED_WORDS = [ 'anthropic', 'claude' ];

	/**
	 * Initialize the processor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'acf/save_post', [ $this, 'process_skill_package' ], 20 );
		add_action( 'admin_notices', [ $this, 'display_validation_errors' ] );
		add_action( 'admin_notices', [ $this, 'display_sync_status_notice' ] );
		add_action( 'wp_ajax_leaderspath_resync_skill', [ $this, 'ajax_resync_skill' ] );
		add_action( 'admin_footer', [ $this, 'render_resync_script' ] );
	}

	/**
	 * Process skill package on post save.
	 *
	 * Extracts SKILL.md from the uploaded ZIP, parses YAML frontmatter,
	 * validates the data, and updates ACF fields.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $post_id The post ID.
	 * @return void
	 */
	public function process_skill_package( $post_id ): void {
		// Convert to int and validate.
		$post_id = (int) $post_id;

		// Skip autosaves and revisions.
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Only process skill posts.
		if ( get_post_type( $post_id ) !== 'leaderspath_skill' ) {
			return;
		}

		// Get the skill package attachment ID.
		$package_id = get_field( 'skill_package', $post_id );

		if ( ! $package_id ) {
			// No package uploaded, clear the read-only fields.
			$this->clear_skill_fields( $post_id );
			return;
		}

		// Extract SKILL.md content from the ZIP.
		$skill_md_content = $this->extract_skill_md( (int) $package_id );

		if ( null === $skill_md_content ) {
			$this->store_error( $post_id, __( 'The uploaded ZIP file does not contain a SKILL.md file. Please ensure your skill package includes SKILL.md at the root or in a subdirectory.', 'leaderspath' ) );
			$this->clear_skill_fields( $post_id );
			return;
		}

		// Parse YAML frontmatter.
		$frontmatter = $this->parse_frontmatter( $skill_md_content );

		if ( null === $frontmatter ) {
			$this->store_error( $post_id, __( 'Could not parse YAML frontmatter from SKILL.md. Ensure the file starts with valid YAML between --- delimiters.', 'leaderspath' ) );
			$this->clear_skill_fields( $post_id );
			return;
		}

		// Validate the extracted data.
		$validation_errors = $this->validate_skill_data( $frontmatter );

		if ( ! empty( $validation_errors ) ) {
			$this->store_error( $post_id, implode( '<br>', $validation_errors ) );
			$this->clear_skill_fields( $post_id );
			return;
		}

		// Update ACF fields with validated data.
		update_field( 'skill_name', $frontmatter['name'], $post_id );
		update_field( 'skill_description', $frontmatter['description'], $post_id );
		update_field( 'skill_compatibility', $frontmatter['compatibility'] ?? '', $post_id );

		// Clear any previous errors.
		delete_transient( self::TRANSIENT_KEY_PREFIX . $post_id );

		// Upload to Anthropic Skills API.
		$this->upload_to_anthropic( $post_id, (int) $package_id, $frontmatter );
	}

	/**
	 * Extract SKILL.md content from a ZIP file.
	 *
	 * Searches for SKILL.md at the root level first, then in the first
	 * subdirectory (common when zipping a folder).
	 *
	 * @since 0.1.0
	 *
	 * @param int $attachment_id The attachment ID of the ZIP file.
	 * @return string|null The SKILL.md content, or null if not found.
	 */
	private function extract_skill_md( int $attachment_id ): ?string {
		$file_path = get_attached_file( $attachment_id );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return null;
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $file_path ) ) {
			return null;
		}

		// First, try to find SKILL.md at the root.
		$content = $zip->getFromName( 'SKILL.md' );

		if ( false !== $content ) {
			$zip->close();
			return $content;
		}

		// Search for SKILL.md in subdirectories.
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$filename = $zip->getNameIndex( $i );

			// Match SKILL.md in any single subdirectory (e.g., "skill-name/SKILL.md").
			if ( preg_match( '#^[^/]+/SKILL\.md$#', $filename ) ) {
				$content = $zip->getFromIndex( $i );
				$zip->close();
				return ( false !== $content ) ? $content : null;
			}
		}

		$zip->close();
		return null;
	}

	/**
	 * Parse YAML frontmatter from SKILL.md content.
	 *
	 * Extracts the YAML block between --- delimiters at the start of the file.
	 *
	 * @since 0.1.0
	 *
	 * @param string $content The SKILL.md file content.
	 * @return array<string, mixed>|null Parsed frontmatter, or null on failure.
	 */
	private function parse_frontmatter( string $content ): ?array {
		// Match YAML frontmatter between --- delimiters.
		if ( ! preg_match( '/^---\s*\n(.*?)\n---\s*\n/s', $content, $matches ) ) {
			return null;
		}

		$yaml_content = $matches[1];

		try {
			$parsed = Yaml::parse( $yaml_content );

			if ( ! is_array( $parsed ) ) {
				return null;
			}

			return $parsed;
		} catch ( ParseException $e ) {
			return null;
		}
	}

	/**
	 * Validate skill data from frontmatter.
	 *
	 * Validates according to the Agent Skills Specification:
	 * - name: 1-64 chars, lowercase alphanumeric + hyphens, no reserved words
	 * - description: 1-1024 chars, no angle brackets
	 * - compatibility: 1-500 chars (optional)
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $data The parsed frontmatter data.
	 * @return array<string> Array of validation error messages.
	 */
	private function validate_skill_data( array $data ): array {
		$errors = [];

		// Validate name (required).
		if ( empty( $data['name'] ) ) {
			$errors[] = __( 'Missing required field: name', 'leaderspath' );
		} else {
			$name = (string) $data['name'];

			if ( strlen( $name ) > 64 ) {
				$errors[] = __( 'Skill name must be 64 characters or less.', 'leaderspath' );
			}

			if ( ! preg_match( '/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $name ) ) {
				$errors[] = __( 'Skill name must contain only lowercase letters, numbers, and hyphens. Cannot start or end with a hyphen.', 'leaderspath' );
			}

			if ( preg_match( '/--/', $name ) ) {
				$errors[] = __( 'Skill name cannot contain consecutive hyphens (--).', 'leaderspath' );
			}

			foreach ( self::RESERVED_WORDS as $reserved ) {
				if ( stripos( $name, $reserved ) !== false ) {
					/* translators: %s: reserved word */
					$errors[] = sprintf( __( 'Skill name cannot contain the reserved word "%s".', 'leaderspath' ), $reserved );
				}
			}
		}

		// Validate description (required).
		if ( empty( $data['description'] ) ) {
			$errors[] = __( 'Missing required field: description', 'leaderspath' );
		} else {
			$description = (string) $data['description'];

			if ( strlen( $description ) > 1024 ) {
				$errors[] = __( 'Description must be 1024 characters or less.', 'leaderspath' );
			}

			if ( preg_match( '/[<>]/', $description ) ) {
				$errors[] = __( 'Description cannot contain angle brackets (< or >).', 'leaderspath' );
			}
		}

		// Validate compatibility (optional).
		if ( ! empty( $data['compatibility'] ) ) {
			$compatibility = (string) $data['compatibility'];

			if ( strlen( $compatibility ) > 500 ) {
				$errors[] = __( 'Compatibility field must be 500 characters or less.', 'leaderspath' );
			}
		}

		return $errors;
	}

	/**
	 * Clear skill metadata fields.
	 *
	 * Called when validation fails or no package is uploaded.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id The post ID.
	 * @return void
	 */
	private function clear_skill_fields( int $post_id ): void {
		update_field( 'skill_name', '', $post_id );
		update_field( 'skill_description', '', $post_id );
		update_field( 'skill_compatibility', '', $post_id );
	}

	/**
	 * Store a validation error for display.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $post_id The post ID.
	 * @param string $message The error message.
	 * @return void
	 */
	private function store_error( int $post_id, string $message ): void {
		set_transient(
			self::TRANSIENT_KEY_PREFIX . $post_id,
			$message,
			60 // 1 minute expiry.
		);
	}

	/**
	 * Display validation errors as admin notices.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function display_validation_errors(): void {
		$screen = get_current_screen();

		// Only show on skill edit screen.
		if ( ! $screen || 'leaderspath_skill' !== $screen->post_type ) {
			return;
		}

		// Get the post ID from the URL.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just reading post ID for display.
		$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;

		if ( ! $post_id ) {
			return;
		}

		$error = get_transient( self::TRANSIENT_KEY_PREFIX . $post_id );

		if ( $error ) {
			printf(
				'<div class="notice notice-error"><p><strong>%s</strong></p><p>%s</p></div>',
				esc_html__( 'Skill Package Validation Error', 'leaderspath' ),
				wp_kses_post( $error )
			);

			// Clear the transient after display.
			delete_transient( self::TRANSIENT_KEY_PREFIX . $post_id );
		}

		// Also display success messages.
		$success = get_transient( self::TRANSIENT_SUCCESS_PREFIX . $post_id );

		if ( $success ) {
			printf(
				'<div class="notice notice-success"><p><strong>%s</strong></p><p>%s</p></div>',
				esc_html__( 'Skill Synced to Anthropic', 'leaderspath' ),
				wp_kses_post( $success )
			);

			// Clear the transient after display.
			delete_transient( self::TRANSIENT_SUCCESS_PREFIX . $post_id );
		}
	}

	/**
	 * Upload skill package to Anthropic Skills API.
	 *
	 * Creates a new skill or updates an existing one with a new version.
	 *
	 * @since 0.1.0
	 *
	 * @param int   $post_id       The post ID.
	 * @param int   $attachment_id The attachment ID of the ZIP file.
	 * @param array $frontmatter   Parsed frontmatter data.
	 * @return void
	 */
	private function upload_to_anthropic( int $post_id, int $attachment_id, array $frontmatter ): void {
		// Check if API key is configured.
		$api_key = Settings::get_api_key();

		if ( empty( $api_key ) ) {
			update_field( 'skill_sync_status', 'error', $post_id );
			update_field( 'skill_sync_error', __( 'Claude API key is not configured. Please add your API key in LeadersPath settings.', 'leaderspath' ), $post_id );
			return;
		}

		// Get the ZIP file path.
		$file_path = get_attached_file( $attachment_id );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			update_field( 'skill_sync_status', 'error', $post_id );
			update_field( 'skill_sync_error', __( 'Could not locate the skill package file.', 'leaderspath' ), $post_id );
			return;
		}

		// Mark as pending during upload.
		update_field( 'skill_sync_status', 'pending', $post_id );
		update_field( 'skill_sync_error', '', $post_id );

		// Check if this skill already has an Anthropic ID (update vs create).
		$existing_skill_id = get_field( 'skill_anthropic_id', $post_id );

		if ( $existing_skill_id ) {
			// Create a new version for existing skill.
			$result = $this->create_skill_version( $existing_skill_id, $file_path, $api_key );
		} else {
			// Create a new skill.
			$result = $this->create_skill( $frontmatter, $file_path, $api_key );
		}

		if ( is_wp_error( $result ) ) {
			update_field( 'skill_sync_status', 'error', $post_id );
			update_field( 'skill_sync_error', $result->get_error_message(), $post_id );
			$this->store_error( $post_id, $result->get_error_message() );
			return;
		}

		// Update ACF fields with Anthropic response data.
		update_field( 'skill_anthropic_id', $result['skill_id'], $post_id );
		update_field( 'skill_anthropic_version', $result['version'], $post_id );
		update_field( 'skill_sync_status', 'synced', $post_id );
		update_field( 'skill_sync_error', '', $post_id );
		update_field( 'skill_last_synced', current_time( 'mysql' ), $post_id );

		// Store success message.
		$message = sprintf(
			/* translators: 1: skill ID, 2: version */
			__( 'Skill uploaded successfully. ID: %1$s, Version: %2$s', 'leaderspath' ),
			$result['skill_id'],
			$result['version']
		);
		set_transient( self::TRANSIENT_SUCCESS_PREFIX . $post_id, $message, 60 );
	}

	/**
	 * Create a new skill on Anthropic Skills API.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $frontmatter Parsed frontmatter data.
	 * @param string $file_path   Path to the ZIP file.
	 * @param string $api_key     The API key.
	 * @return array|WP_Error Response data or error.
	 */
	private function create_skill( array $frontmatter, string $file_path, string $api_key ) {
		$display_title = $frontmatter['name'] ?? basename( $file_path, '.zip' );

		// Build request with multipart form data.
		$boundary = wp_generate_password( 24, false );

		// Read file content.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$file_content = file_get_contents( $file_path );

		if ( false === $file_content ) {
			return new WP_Error( 'file_read_error', __( 'Could not read the skill package file.', 'leaderspath' ) );
		}

		// Build multipart body.
		$body = $this->build_multipart_body( $boundary, $display_title, $file_path, $file_content );

		$response = wp_remote_post(
			self::API_URL . '/skills',
			[
				'timeout' => 60,
				'headers' => [
					'Content-Type'      => 'multipart/form-data; boundary=' . $boundary,
					'x-api-key'         => $api_key,
					'anthropic-version' => self::API_VERSION,
					'anthropic-beta'    => Settings::get_beta_headers( [ 'skills' ] ),
				],
				'body'    => $body,
			]
		);

		return $this->handle_api_response( $response, 'create' );
	}

	/**
	 * Create a new version for an existing skill.
	 *
	 * @since 0.1.0
	 *
	 * @param string $skill_id  The Anthropic skill ID.
	 * @param string $file_path Path to the ZIP file.
	 * @param string $api_key   The API key.
	 * @return array|WP_Error Response data or error.
	 */
	private function create_skill_version( string $skill_id, string $file_path, string $api_key ) {
		$boundary = wp_generate_password( 24, false );

		// Read file content.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$file_content = file_get_contents( $file_path );

		if ( false === $file_content ) {
			return new WP_Error( 'file_read_error', __( 'Could not read the skill package file.', 'leaderspath' ) );
		}

		// Build multipart body (without display_title for version update).
		$body = $this->build_multipart_body( $boundary, null, $file_path, $file_content );

		$response = wp_remote_post(
			self::API_URL . '/skills/' . $skill_id . '/versions',
			[
				'timeout' => 60,
				'headers' => [
					'Content-Type'      => 'multipart/form-data; boundary=' . $boundary,
					'x-api-key'         => $api_key,
					'anthropic-version' => self::API_VERSION,
					'anthropic-beta'    => Settings::get_beta_headers( [ 'skills' ] ),
				],
				'body'    => $body,
			]
		);

		return $this->handle_api_response( $response, 'version', $skill_id );
	}

	/**
	 * Build multipart form data body.
	 *
	 * @since 0.1.0
	 *
	 * @param string      $boundary      The multipart boundary.
	 * @param string|null $display_title Optional display title for new skills.
	 * @param string      $file_path     Path to the file.
	 * @param string      $file_content  File content.
	 * @return string The multipart body.
	 */
	private function build_multipart_body( string $boundary, ?string $display_title, string $file_path, string $file_content ): string {
		$body = '';

		// Add display_title field if provided.
		if ( null !== $display_title ) {
			$body .= "--{$boundary}\r\n";
			$body .= "Content-Disposition: form-data; name=\"display_title\"\r\n\r\n";
			$body .= "{$display_title}\r\n";
		}

		// Add file field - API expects 'files[]' for array notation.
		$filename = basename( $file_path );
		$body    .= "--{$boundary}\r\n";
		$body    .= "Content-Disposition: form-data; name=\"files[]\"; filename=\"{$filename}\"\r\n";
		$body    .= "Content-Type: application/zip\r\n\r\n";
		$body    .= $file_content . "\r\n";
		$body    .= "--{$boundary}--\r\n";

		return $body;
	}

	/**
	 * Handle API response from Anthropic.
	 *
	 * @since 0.1.0
	 *
	 * @param array|WP_Error $response   The API response.
	 * @param string         $operation  The operation type (create, version).
	 * @param string|null    $skill_id   The skill ID (for version operations).
	 * @return array|WP_Error Parsed response data or error.
	 */
	private function handle_api_response( $response, string $operation, ?string $skill_id = null ) {
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_request_failed',
				/* translators: %s: error message */
				sprintf( __( 'Failed to connect to Anthropic API: %s', 'leaderspath' ), $response->get_error_message() )
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body_raw    = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body_raw, true );

		// Log if debug mode is enabled.
		if ( Settings::is_debug_mode() ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
			error_log( sprintf( '[LeadersPath Skills API] %s response (%d): %s', $operation, $status_code, print_r( $data, true ) ) );
		}

		// Handle error responses.
		if ( $status_code >= 400 ) {
			$error_message = $data['error']['message'] ?? __( 'Unknown error occurred.', 'leaderspath' );
			$error_type    = $data['error']['type'] ?? 'api_error';

			return new WP_Error(
				'anthropic_api_error',
				/* translators: 1: error type, 2: error message */
				sprintf( __( 'Anthropic API error (%1$s): %2$s', 'leaderspath' ), $error_type, $error_message )
			);
		}

		// Parse successful response.
		if ( 'create' === $operation ) {
			if ( ! isset( $data['id'] ) ) {
				return new WP_Error( 'invalid_response', __( 'Invalid response from Anthropic API: missing skill ID.', 'leaderspath' ) );
			}

			return [
				'skill_id' => $data['id'],
				'version'  => $data['latest_version'] ?? 'latest',
			];
		}

		// Version operation.
		return [
			'skill_id' => $skill_id,
			'version'  => $data['version'] ?? $data['latest_version'] ?? 'latest',
		];
	}

	/**
	 * Delete a skill from Anthropic when the WordPress post is deleted.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id The post ID being deleted.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function delete_from_anthropic( int $post_id ) {
		$skill_id = get_field( 'skill_anthropic_id', $post_id );

		if ( empty( $skill_id ) ) {
			return true; // Nothing to delete.
		}

		$api_key = Settings::get_api_key();

		if ( empty( $api_key ) ) {
			return new WP_Error( 'api_key_missing', __( 'API key not configured.', 'leaderspath' ) );
		}

		$response = wp_remote_request(
			self::API_URL . '/skills/' . $skill_id,
			[
				'method'  => 'DELETE',
				'timeout' => 30,
				'headers' => [
					'x-api-key'         => $api_key,
					'anthropic-version' => self::API_VERSION,
					'anthropic-beta'    => Settings::get_beta_headers( [ 'skills' ] ),
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		// 200 = deleted, 404 = already gone (both are acceptable).
		if ( $status_code === 200 || $status_code === 404 ) {
			return true;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$error_message = $body['error']['message'] ?? __( 'Failed to delete skill from Anthropic.', 'leaderspath' );

		return new WP_Error( 'delete_failed', $error_message );
	}

	/**
	 * Re-sync a skill to Anthropic.
	 *
	 * This is called when the user clicks the "Re-sync" button for a failed upload.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id The post ID.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function resync_skill( int $post_id ) {
		// Verify this is a skill post.
		if ( get_post_type( $post_id ) !== 'leaderspath_skill' ) {
			return new WP_Error( 'invalid_post', __( 'Not a skill post.', 'leaderspath' ) );
		}

		// Get the package attachment ID.
		$package_id = get_field( 'skill_package', $post_id );

		if ( ! $package_id ) {
			return new WP_Error( 'no_package', __( 'No skill package uploaded.', 'leaderspath' ) );
		}

		// Extract and validate SKILL.md.
		$skill_md_content = $this->extract_skill_md( (int) $package_id );

		if ( null === $skill_md_content ) {
			return new WP_Error( 'invalid_package', __( 'The skill package does not contain a valid SKILL.md file.', 'leaderspath' ) );
		}

		$frontmatter = $this->parse_frontmatter( $skill_md_content );

		if ( null === $frontmatter ) {
			return new WP_Error( 'invalid_frontmatter', __( 'Could not parse SKILL.md frontmatter.', 'leaderspath' ) );
		}

		$validation_errors = $this->validate_skill_data( $frontmatter );

		if ( ! empty( $validation_errors ) ) {
			return new WP_Error( 'validation_failed', implode( ' ', $validation_errors ) );
		}

		// Attempt upload to Anthropic.
		$this->upload_to_anthropic( $post_id, (int) $package_id, $frontmatter );

		// Check if it succeeded.
		$sync_status = get_field( 'skill_sync_status', $post_id );

		if ( 'synced' === $sync_status ) {
			return true;
		}

		$sync_error = get_field( 'skill_sync_error', $post_id );
		return new WP_Error( 'sync_failed', $sync_error ?: __( 'Sync failed for unknown reason.', 'leaderspath' ) );
	}

	/**
	 * Display sync status notice on skill edit screen.
	 *
	 * Shows the current sync status and a re-sync button for failed uploads.
	 *
	 * @since 0.1.0
	 */
	public function display_sync_status_notice(): void {
		$screen = get_current_screen();

		// Only show on skill edit screen.
		if ( ! $screen || 'leaderspath_skill' !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}

		// Get the post ID from the URL.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just reading post ID for display.
		$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;

		if ( ! $post_id ) {
			return;
		}

		$sync_status = get_field( 'skill_sync_status', $post_id );
		$sync_error  = get_field( 'skill_sync_error', $post_id );
		$anthropic_id = get_field( 'skill_anthropic_id', $post_id );
		$last_synced = get_field( 'skill_last_synced', $post_id );

		// Don't show notice if no package uploaded yet.
		$package_id = get_field( 'skill_package', $post_id );
		if ( ! $package_id ) {
			return;
		}

		// Display based on status.
		if ( 'synced' === $sync_status && $anthropic_id ) {
			$last_synced_display = $last_synced ? sprintf(
				/* translators: %s: timestamp */
				__( 'Last synced: %s', 'leaderspath' ),
				$last_synced
			) : '';
			?>
			<div class="notice notice-success">
				<p>
					<strong><?php esc_html_e( 'Anthropic Sync:', 'leaderspath' ); ?></strong>
					<?php esc_html_e( 'Skill is synced and ready for use.', 'leaderspath' ); ?>
					<?php if ( $last_synced_display ) : ?>
						<br><small><?php echo esc_html( $last_synced_display ); ?></small>
					<?php endif; ?>
				</p>
			</div>
			<?php
		} elseif ( 'error' === $sync_status ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'Anthropic Sync Failed:', 'leaderspath' ); ?></strong>
					<?php echo esc_html( $sync_error ); ?>
				</p>
				<p>
					<button type="button" class="button button-secondary leaderspath-resync-skill" data-post-id="<?php echo esc_attr( (string) $post_id ); ?>">
						<?php esc_html_e( 'Retry Sync', 'leaderspath' ); ?>
					</button>
					<span class="leaderspath-resync-status"></span>
				</p>
			</div>
			<?php
		} elseif ( 'pending' === $sync_status ) {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'Anthropic Sync:', 'leaderspath' ); ?></strong>
					<?php esc_html_e( 'Skill sync is pending. Save the skill to trigger upload.', 'leaderspath' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * AJAX handler for re-syncing a skill.
	 *
	 * @since 0.1.0
	 */
	public function ajax_resync_skill(): void {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'leaderspath_resync_skill' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'leaderspath' ) ] );
		}

		// Check capability.
		if ( ! current_user_can( 'edit_leaderspath_skills' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'leaderspath' ) ] );
		}

		// Get post ID.
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;

		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post ID.', 'leaderspath' ) ] );
		}

		// Attempt resync.
		$result = $this->resync_skill( $post_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$anthropic_id = get_field( 'skill_anthropic_id', $post_id );
		$version      = get_field( 'skill_anthropic_version', $post_id );

		wp_send_json_success( [
			'message' => sprintf(
				/* translators: 1: skill ID, 2: version */
				__( 'Skill synced successfully! ID: %1$s, Version: %2$s', 'leaderspath' ),
				$anthropic_id,
				$version
			),
		] );
	}

	/**
	 * Render the JavaScript for the re-sync button.
	 *
	 * @since 0.1.0
	 */
	public function render_resync_script(): void {
		$screen = get_current_screen();

		// Only on skill edit screen.
		if ( ! $screen || 'leaderspath_skill' !== $screen->post_type ) {
			return;
		}
		?>
		<script>
		jQuery(document).ready(function($) {
			$('.leaderspath-resync-skill').on('click', function() {
				var $button = $(this);
				var $status = $button.siblings('.leaderspath-resync-status');
				var postId = $button.data('post-id');

				$button.prop('disabled', true).text('<?php echo esc_js( __( 'Syncing...', 'leaderspath' ) ); ?>');
				$status.html('');

				$.post(ajaxurl, {
					action: 'leaderspath_resync_skill',
					nonce: '<?php echo esc_js( wp_create_nonce( 'leaderspath_resync_skill' ) ); ?>',
					post_id: postId
				}, function(response) {
					if (response.success) {
						$status.html('<span style="color: #46b450; margin-left: 10px;"><span class="dashicons dashicons-yes-alt"></span> ' + response.data.message + '</span>');
						$button.text('<?php echo esc_js( __( 'Synced!', 'leaderspath' ) ); ?>');
						// Reload page to update all fields.
						setTimeout(function() {
							window.location.reload();
						}, 1500);
					} else {
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Retry Sync', 'leaderspath' ) ); ?>');
						$status.html('<span style="color: #dc3232; margin-left: 10px;"><span class="dashicons dashicons-warning"></span> ' + response.data.message + '</span>');
					}
				}).fail(function() {
					$button.prop('disabled', false).text('<?php echo esc_js( __( 'Retry Sync', 'leaderspath' ) ); ?>');
					$status.html('<span style="color: #dc3232; margin-left: 10px;"><span class="dashicons dashicons-warning"></span> <?php echo esc_js( __( 'Request failed', 'leaderspath' ) ); ?></span>');
				});
			});
		});
		</script>
		<?php
	}
}
