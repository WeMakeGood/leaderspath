<?php
/**
 * Skill Package Processor.
 *
 * Handles extraction and validation of skill packages (ZIP files containing SKILL.md).
 * Auto-populates ACF fields from YAML frontmatter when a skill package is uploaded.
 *
 * @package LeadersPath
 * @since   0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Includes;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use ZipArchive;

/**
 * Processes uploaded skill packages and extracts metadata.
 *
 * @since 0.1.0
 */
class Skill_Processor {

	/**
	 * Transient key prefix for storing validation errors.
	 *
	 * @var string
	 */
	private const TRANSIENT_KEY_PREFIX = 'leaderspath_skill_errors_';

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
	}
}
