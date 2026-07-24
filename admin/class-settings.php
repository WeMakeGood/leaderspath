<?php
/**
 * Plugin Settings page.
 *
 * @package LeadersPath
 * @since   0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Admin;

/**
 * Manages the LeadersPath settings page.
 *
 * @since 0.1.0
 */
class Settings {

	/**
	 * Option group name.
	 *
	 * @var string
	 */
	private const OPTION_GROUP = 'leaderspath_settings';

	/**
	 * Option name for storing settings.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'leaderspath_options';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	private const PAGE_SLUG = 'leaderspath-settings';

	/**
	 * Encryption method for API key.
	 *
	 * @var string
	 */
	private const ENCRYPTION_METHOD = 'aes-256-cbc';

	/**
	 * Initialize the class.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_notices', [ $this, 'display_api_key_notice' ] );
		add_action( 'wp_ajax_leaderspath_test_connection', [ $this, 'ajax_test_connection' ] );
	}

	/**
	 * Add the settings page to the admin menu.
	 *
	 * @since 0.1.0
	 */
	public function add_settings_page(): void {
		add_submenu_page(
			Admin_Menu::MENU_SLUG,
			__( 'Settings', 'leaderspath' ),
			__( 'Settings', 'leaderspath' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 0.1.0
	 */
	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_options' ],
				'default'           => $this->get_defaults(),
			]
		);

		// Claude API Section.
		add_settings_section(
			'leaderspath_api_section',
			__( 'Claude API Configuration', 'leaderspath' ),
			[ $this, 'render_api_section' ],
			self::PAGE_SLUG
		);

		add_settings_field(
			'api_key',
			__( 'API Key', 'leaderspath' ),
			[ $this, 'render_api_key_field' ],
			self::PAGE_SLUG,
			'leaderspath_api_section'
		);

		add_settings_field(
			'default_model',
			__( 'Default Model', 'leaderspath' ),
			[ $this, 'render_default_model_field' ],
			self::PAGE_SLUG,
			'leaderspath_api_section'
		);

		// API Versions Section (Advanced).
		add_settings_section(
			'leaderspath_api_versions_section',
			__( 'API Version Configuration', 'leaderspath' ),
			[ $this, 'render_api_versions_section' ],
			self::PAGE_SLUG
		);

		add_settings_field(
			'beta_code_execution',
			__( 'Code Execution Beta Header', 'leaderspath' ),
			[ $this, 'render_beta_code_execution_field' ],
			self::PAGE_SLUG,
			'leaderspath_api_versions_section'
		);

		add_settings_field(
			'beta_skills',
			__( 'Skills Beta Header', 'leaderspath' ),
			[ $this, 'render_beta_skills_field' ],
			self::PAGE_SLUG,
			'leaderspath_api_versions_section'
		);

		add_settings_field(
			'beta_files',
			__( 'Files API Beta Header', 'leaderspath' ),
			[ $this, 'render_beta_files_field' ],
			self::PAGE_SLUG,
			'leaderspath_api_versions_section'
		);

		add_settings_field(
			'tool_code_execution',
			__( 'Code Execution Tool Type', 'leaderspath' ),
			[ $this, 'render_tool_code_execution_field' ],
			self::PAGE_SLUG,
			'leaderspath_api_versions_section'
		);

		add_settings_field(
			'beta_web_tools',
			__( 'Web Tools Beta Header', 'leaderspath' ),
			[ $this, 'render_beta_web_tools_field' ],
			self::PAGE_SLUG,
			'leaderspath_api_versions_section'
		);

		add_settings_field(
			'tool_web_search',
			__( 'Web Search Tool Type', 'leaderspath' ),
			[ $this, 'render_tool_web_search_field' ],
			self::PAGE_SLUG,
			'leaderspath_api_versions_section'
		);

		add_settings_field(
			'tool_web_fetch',
			__( 'Web Fetch Tool Type', 'leaderspath' ),
			[ $this, 'render_tool_web_fetch_field' ],
			self::PAGE_SLUG,
			'leaderspath_api_versions_section'
		);

		// Debug Section.
		add_settings_section(
			'leaderspath_debug_section',
			__( 'Debugging', 'leaderspath' ),
			[ $this, 'render_debug_section' ],
			self::PAGE_SLUG
		);

		add_settings_field(
			'debug_mode',
			__( 'Debug Mode', 'leaderspath' ),
			[ $this, 'render_debug_mode_field' ],
			self::PAGE_SLUG,
			'leaderspath_debug_section'
		);
	}

	/**
	 * Get default option values.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed> Default values.
	 */
	private function get_defaults(): array {
		return [
			'api_key'                   => '',
			'default_model'             => 'sonnet',
			'debug_mode'                => false,
			// Beta header versions (configurable for API updates).
			'beta_code_execution'       => 'code-execution-2025-08-25',
			'beta_skills'               => 'skills-2025-10-02',
			'beta_files'                => 'files-api-2025-04-14',
			'beta_web_tools'            => 'code-execution-web-tools-2026-02-09',
			'tool_code_execution'       => 'code_execution_20260521',
			'tool_web_search'           => 'web_search_20260209',
			'tool_web_fetch'            => 'web_fetch_20260209',
		];
	}

	/**
	 * Sanitize options before saving.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $input Raw input values.
	 * @return array<string, mixed> Sanitized values.
	 */
	public function sanitize_options( array $input ): array {
		$sanitized = [];
		$current   = get_option( self::OPTION_NAME, $this->get_defaults() );

		// API Key - encrypt if changed.
		if ( isset( $input['api_key'] ) ) {
			$new_key = sanitize_text_field( $input['api_key'] );
			// Only re-encrypt if the key has changed (not the placeholder).
			if ( $new_key !== '' && $new_key !== '••••••••••••••••' ) {
				$sanitized['api_key'] = $this->encrypt_api_key( $new_key );
			} else {
				// Keep existing encrypted key.
				$sanitized['api_key'] = $current['api_key'] ?? '';
			}
		}

		// Default model.
		$valid_models = [ 'sonnet', 'haiku', 'opus' ];
		if ( isset( $input['default_model'] ) && in_array( $input['default_model'], $valid_models, true ) ) {
			$sanitized['default_model'] = $input['default_model'];
		} else {
			$sanitized['default_model'] = 'sonnet';
		}

		// Debug mode.
		$sanitized['debug_mode'] = ! empty( $input['debug_mode'] );

		// Beta header versions (sanitize as text, validate format).
		$beta_fields = [ 'beta_code_execution', 'beta_skills', 'beta_files', 'beta_web_tools', 'tool_code_execution', 'tool_web_search', 'tool_web_fetch' ];
		$defaults    = $this->get_defaults();

		foreach ( $beta_fields as $field ) {
			if ( isset( $input[ $field ] ) && ! empty( $input[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $input[ $field ] );
			} else {
				$sanitized[ $field ] = $defaults[ $field ];
			}
		}

		return $sanitized;
	}

	/**
	 * Render the settings page.
	 *
	 * @since 0.1.0
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the API section description.
	 *
	 * @since 0.1.0
	 */
	public function render_api_section(): void {
		echo '<p>' . esc_html__( 'Configure your Anthropic API credentials for Claude integration.', 'leaderspath' ) . '</p>';
	}

	/**
	 * Render the API key field.
	 *
	 * @since 0.1.0
	 */
	public function render_api_key_field(): void {
		$options        = get_option( self::OPTION_NAME, $this->get_defaults() );
		$has_db_key     = ! empty( $options['api_key'] );
		$has_constant   = self::is_api_key_constant();
		$has_key        = $has_constant || $has_db_key;

		?>
		<?php if ( $has_constant ) : ?>
			<input
				type="password"
				id="leaderspath_api_key"
				value="••••••••••••••••"
				class="regular-text"
				disabled="disabled"
			/>
			<button type="button" id="leaderspath-test-connection" class="button button-secondary">
				<?php esc_html_e( 'Test Connection', 'leaderspath' ); ?>
			</button>
			<span id="leaderspath-connection-status"></span>
			<p class="description" style="color: #46b450;">
				<span class="dashicons dashicons-lock"></span>
				<?php esc_html_e( 'API key is defined in wp-config.php via the LEADERSPATH_API_KEY constant.', 'leaderspath' ); ?>
			</p>
		<?php else : ?>
			<input
				type="password"
				id="leaderspath_api_key"
				name="<?php echo esc_attr( self::OPTION_NAME ); ?>[api_key]"
				value="<?php echo $has_db_key ? '••••••••••••••••' : ''; ?>"
				class="regular-text"
				autocomplete="off"
			/>
			<?php if ( $has_db_key ) : ?>
				<button type="button" id="leaderspath-test-connection" class="button button-secondary">
					<?php esc_html_e( 'Test Connection', 'leaderspath' ); ?>
				</button>
				<span id="leaderspath-connection-status"></span>
			<?php endif; ?>
			<p class="description">
				<?php
				printf(
					/* translators: %s: Anthropic console URL */
					esc_html__( 'Enter your Anthropic API key. Get one from %s.', 'leaderspath' ),
					'<a href="https://console.anthropic.com/" target="_blank" rel="noopener noreferrer">console.anthropic.com</a>'
				);
				?>
			</p>
			<p class="description">
				<?php
				printf(
					/* translators: %s: constant name */
					esc_html__( 'Alternatively, define %s in wp-config.php for server-level configuration.', 'leaderspath' ),
					'<code>LEADERSPATH_API_KEY</code>'
				);
				?>
			</p>
			<?php if ( $has_db_key ) : ?>
				<p class="description" style="color: #46b450;">
					<span class="dashicons dashicons-yes-alt"></span>
					<?php esc_html_e( 'API key is configured and encrypted.', 'leaderspath' ); ?>
				</p>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( $has_key ) : ?>
		<script>
		jQuery(document).ready(function($) {
			$('#leaderspath-test-connection').on('click', function() {
				var $button = $(this);
				var $status = $('#leaderspath-connection-status');

				$button.prop('disabled', true).text('<?php echo esc_js( __( 'Testing...', 'leaderspath' ) ); ?>');
				$status.html('');

				$.post(ajaxurl, {
					action: 'leaderspath_test_connection',
					nonce: '<?php echo esc_js( wp_create_nonce( 'leaderspath_test_connection' ) ); ?>'
				}, function(response) {
					$button.prop('disabled', false).text('<?php echo esc_js( __( 'Test Connection', 'leaderspath' ) ); ?>');

					if (response.success) {
						$status.html('<span style="color: #46b450; margin-left: 10px;"><span class="dashicons dashicons-yes-alt"></span> ' + response.data.message + '</span>');
					} else {
						$status.html('<span style="color: #dc3232; margin-left: 10px;"><span class="dashicons dashicons-warning"></span> ' + response.data.message + '</span>');
					}
				}).fail(function() {
					$button.prop('disabled', false).text('<?php echo esc_js( __( 'Test Connection', 'leaderspath' ) ); ?>');
					$status.html('<span style="color: #dc3232; margin-left: 10px;"><span class="dashicons dashicons-warning"></span> <?php echo esc_js( __( 'Request failed', 'leaderspath' ) ); ?></span>');
				});
			});
		});
		</script>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render the default model field.
	 *
	 * @since 0.1.0
	 */
	public function render_default_model_field(): void {
		$options       = get_option( self::OPTION_NAME, $this->get_defaults() );
		$current_model = $options['default_model'] ?? 'sonnet';

		$models = [
			'sonnet' => __( 'Sonnet (Recommended - balanced speed and capability)', 'leaderspath' ),
			'haiku'  => __( 'Haiku (Fastest, lower cost)', 'leaderspath' ),
			'opus'   => __( 'Opus (Most capable, higher cost)', 'leaderspath' ),
		];

		?>
		<select
			id="leaderspath_default_model"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_model]"
		>
			<?php foreach ( $models as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_model, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Default Claude model for new lessons. Can be overridden per lesson.', 'leaderspath' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the debug section description.
	 *
	 * @since 0.1.0
	 */
	public function render_debug_section(): void {
		echo '<p>' . esc_html__( 'Enable debug features for troubleshooting. Disable in production.', 'leaderspath' ) . '</p>';
	}

	/**
	 * Render the debug mode field.
	 *
	 * @since 0.1.0
	 */
	public function render_debug_mode_field(): void {
		$options    = get_option( self::OPTION_NAME, $this->get_defaults() );
		$debug_mode = $options['debug_mode'] ?? false;

		?>
		<label for="leaderspath_debug_mode">
			<input
				type="checkbox"
				id="leaderspath_debug_mode"
				name="<?php echo esc_attr( self::OPTION_NAME ); ?>[debug_mode]"
				value="1"
				<?php checked( $debug_mode, true ); ?>
			/>
			<?php esc_html_e( 'Enable debug logging', 'leaderspath' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When enabled, API requests and responses are logged to the debug log.', 'leaderspath' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the API versions section description.
	 *
	 * @since 0.1.0
	 */
	public function render_api_versions_section(): void {
		?>
		<p>
			<?php esc_html_e( 'Advanced: Configure Anthropic API beta header versions. Only change these if Anthropic updates their API.', 'leaderspath' ); ?>
		</p>
		<p>
			<a href="https://docs.anthropic.com/en/api/beta-headers" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'View Anthropic Beta Headers Documentation', 'leaderspath' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Render the code execution beta header field.
	 *
	 * @since 0.1.0
	 */
	public function render_beta_code_execution_field(): void {
		$options = get_option( self::OPTION_NAME, $this->get_defaults() );
		$value   = $options['beta_code_execution'] ?? $this->get_defaults()['beta_code_execution'];

		?>
		<input
			type="text"
			id="leaderspath_beta_code_execution"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[beta_code_execution]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Beta header for code execution. Format: code-execution-YYYY-MM-DD', 'leaderspath' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the skills beta header field.
	 *
	 * @since 0.1.0
	 */
	public function render_beta_skills_field(): void {
		$options = get_option( self::OPTION_NAME, $this->get_defaults() );
		$value   = $options['beta_skills'] ?? $this->get_defaults()['beta_skills'];

		?>
		<input
			type="text"
			id="leaderspath_beta_skills"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[beta_skills]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Beta header for skills API. Format: skills-YYYY-MM-DD', 'leaderspath' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the files API beta header field.
	 *
	 * @since 0.1.0
	 */
	public function render_beta_files_field(): void {
		$options = get_option( self::OPTION_NAME, $this->get_defaults() );
		$value   = $options['beta_files'] ?? $this->get_defaults()['beta_files'];

		?>
		<input
			type="text"
			id="leaderspath_beta_files"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[beta_files]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Beta header for files API. Format: files-api-YYYY-MM-DD', 'leaderspath' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the code execution tool type field.
	 *
	 * @since 0.1.0
	 */
	public function render_tool_code_execution_field(): void {
		$options = get_option( self::OPTION_NAME, $this->get_defaults() );
		$value   = $options['tool_code_execution'] ?? $this->get_defaults()['tool_code_execution'];

		?>
		<input
			type="text"
			id="leaderspath_tool_code_execution"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[tool_code_execution]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Tool type for code execution. Format: code_execution_YYYYMMDD', 'leaderspath' ); ?>
		</p>
		<?php
	}

	/**
	 * Display admin notice if API key is not configured.
	 *
	 * @since 0.1.0
	 */
	public function display_api_key_notice(): void {
		// Only show on LeadersPath-related admin pages.
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$show_on_screens = [
			'leaderspath_activity',
			'leaderspath_lesson',
			'leaderspath_course',
			'leaderspath_context',
			'leaderspath_skill',
			'edit-leaderspath_activity',
			'edit-leaderspath_lesson',
			'edit-leaderspath_course',
			'edit-leaderspath_context',
			'edit-leaderspath_skill',
			'leaderspath_page_' . self::PAGE_SLUG,
		];

		if ( ! in_array( $screen->id, $show_on_screens, true ) ) {
			return;
		}

		// No notice needed if the API key is defined in wp-config.php.
		if ( self::is_api_key_constant() ) {
			return;
		}

		$options = get_option( self::OPTION_NAME, $this->get_defaults() );

		if ( empty( $options['api_key'] ) ) {
			?>
			<div class="notice notice-warning">
				<p>
					<?php
					printf(
						/* translators: %1$s: Settings page URL, %2$s: constant name */
						esc_html__( 'LeadersPath: Claude API key is not configured. Chatbot features will not work until you %1$s or define %2$s in wp-config.php.', 'leaderspath' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ) . '">' . esc_html__( 'add your API key', 'leaderspath' ) . '</a>',
						'<code>LEADERSPATH_API_KEY</code>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Encrypt the API key for storage.
	 *
	 * @since 0.1.0
	 *
	 * @param string $api_key The plain text API key.
	 * @return string The encrypted API key.
	 */
	private function encrypt_api_key( string $api_key ): string {
		$key = $this->get_encryption_key();
		$iv  = openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::ENCRYPTION_METHOD ) );

		$encrypted = openssl_encrypt( $api_key, self::ENCRYPTION_METHOD, $key, 0, $iv );

		if ( false === $encrypted ) {
			return '';
		}

		// Store IV with encrypted data.
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt the API key for use.
	 *
	 * @since 0.1.0
	 *
	 * @param string $encrypted_key The encrypted API key.
	 * @return string The decrypted API key.
	 */
	public function decrypt_api_key( string $encrypted_key ): string {
		if ( empty( $encrypted_key ) ) {
			return '';
		}

		$key  = $this->get_encryption_key();
		$data = base64_decode( $encrypted_key );

		if ( false === $data ) {
			return '';
		}

		$iv_length = openssl_cipher_iv_length( self::ENCRYPTION_METHOD );
		$iv        = substr( $data, 0, $iv_length );
		$encrypted = substr( $data, $iv_length );

		$decrypted = openssl_decrypt( $encrypted, self::ENCRYPTION_METHOD, $key, 0, $iv );

		return false === $decrypted ? '' : $decrypted;
	}

	/**
	 * Get the encryption key.
	 *
	 * Uses AUTH_KEY from wp-config.php as the base, ensuring uniqueness per site.
	 *
	 * @since 0.1.0
	 *
	 * @return string The encryption key.
	 */
	private function get_encryption_key(): string {
		// Use WordPress AUTH_KEY as base for encryption.
		$base_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'leaderspath-default-key';
		return hash( 'sha256', $base_key . 'leaderspath_api_encryption' );
	}

	/**
	 * Check if the API key is defined as a constant in wp-config.php.
	 *
	 * @since 0.4.0
	 *
	 * @return bool True if LEADERSPATH_API_KEY constant is defined and non-empty.
	 */
	public static function is_api_key_constant(): bool {
		return defined( 'LEADERSPATH_API_KEY' ) && ! empty( LEADERSPATH_API_KEY );
	}

	/**
	 * Get the decrypted API key.
	 *
	 * Checks for a LEADERSPATH_API_KEY constant in wp-config.php first,
	 * then falls back to the encrypted value stored in the database.
	 *
	 * @since 0.1.0
	 *
	 * @return string The API key, or empty string if not set.
	 */
	public static function get_api_key(): string {
		// Prefer wp-config.php constant if defined.
		if ( self::is_api_key_constant() ) {
			return LEADERSPATH_API_KEY;
		}

		$options = get_option( self::OPTION_NAME, [] );

		if ( empty( $options['api_key'] ) ) {
			return '';
		}

		$instance = new self();
		return $instance->decrypt_api_key( $options['api_key'] );
	}

	/**
	 * Get the default model setting.
	 *
	 * @since 0.1.0
	 *
	 * @return string The default model slug.
	 */
	public static function get_default_model(): string {
		$options = get_option( self::OPTION_NAME, [] );
		return $options['default_model'] ?? 'sonnet';
	}

	/**
	 * Check if debug mode is enabled.
	 *
	 * @since 0.1.0
	 *
	 * @return bool Whether debug mode is enabled.
	 */
	public static function is_debug_mode(): bool {
		$options = get_option( self::OPTION_NAME, [] );
		return ! empty( $options['debug_mode'] );
	}

	/**
	 * Get the beta headers for API requests.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string> $features Features to include (code_execution, skills, files).
	 * @return string Comma-separated beta headers.
	 */
	public static function get_beta_headers( array $features = [] ): string {
		$instance = new self();
		$defaults = $instance->get_defaults();
		$options  = get_option( self::OPTION_NAME, $defaults );

		$headers = [];

		if ( empty( $features ) || in_array( 'code_execution', $features, true ) ) {
			$headers[] = $options['beta_code_execution'] ?? $defaults['beta_code_execution'];
		}

		if ( empty( $features ) || in_array( 'skills', $features, true ) ) {
			$headers[] = $options['beta_skills'] ?? $defaults['beta_skills'];
		}

		if ( in_array( 'files', $features, true ) ) {
			$headers[] = $options['beta_files'] ?? $defaults['beta_files'];
		}

		if ( in_array( 'web_tools', $features, true ) ) {
			$headers[] = $options['beta_web_tools'] ?? $defaults['beta_web_tools'];
		}

		return implode( ',', $headers );
	}

	/**
	 * Get the code execution tool type.
	 *
	 * @since 0.1.0
	 *
	 * @return string The tool type identifier.
	 */
	public static function get_code_execution_tool_type(): string {
		$instance = new self();
		$defaults = $instance->get_defaults();
		$options  = get_option( self::OPTION_NAME, $defaults );

		return $options['tool_code_execution'] ?? $defaults['tool_code_execution'];
	}

	/**
	 * Get the web search and web fetch tool types.
	 *
	 * @since 0.11.0
	 *
	 * @return array{web_search: string, web_fetch: string} Tool type identifiers.
	 */
	public static function get_web_tool_types(): array {
		$instance = new self();
		$defaults = $instance->get_defaults();
		$options  = get_option( self::OPTION_NAME, $defaults );

		return [
			'web_search' => $options['tool_web_search'] ?? $defaults['tool_web_search'],
			'web_fetch'  => $options['tool_web_fetch'] ?? $defaults['tool_web_fetch'],
		];
	}

	/**
	 * Render the web tools beta header field.
	 *
	 * @since 0.11.0
	 */
	public function render_beta_web_tools_field(): void {
		$options = get_option( self::OPTION_NAME, $this->get_defaults() );
		$value   = $options['beta_web_tools'] ?? $this->get_defaults()['beta_web_tools'];

		?>
		<input
			type="text"
			id="leaderspath_beta_web_tools"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[beta_web_tools]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Beta header for web search/fetch with dynamic filtering. Format: code-execution-web-tools-YYYY-MM-DD', 'leaderspath' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the web search tool type field.
	 *
	 * @since 0.11.0
	 */
	public function render_tool_web_search_field(): void {
		$options = get_option( self::OPTION_NAME, $this->get_defaults() );
		$value   = $options['tool_web_search'] ?? $this->get_defaults()['tool_web_search'];

		?>
		<input
			type="text"
			id="leaderspath_tool_web_search"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[tool_web_search]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Tool type for web search. Format: web_search_YYYYMMDD', 'leaderspath' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the web fetch tool type field.
	 *
	 * @since 0.11.0
	 */
	public function render_tool_web_fetch_field(): void {
		$options = get_option( self::OPTION_NAME, $this->get_defaults() );
		$value   = $options['tool_web_fetch'] ?? $this->get_defaults()['tool_web_fetch'];

		?>
		<input
			type="text"
			id="leaderspath_tool_web_fetch"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[tool_web_fetch]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Tool type for web fetch. Format: web_fetch_YYYYMMDD', 'leaderspath' ); ?>
		</p>
		<?php
	}

	/**
	 * AJAX handler for testing API connection.
	 *
	 * @since 0.1.0
	 */
	public function ajax_test_connection(): void {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'leaderspath_test_connection' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'leaderspath' ) ] );
		}

		// Check capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'leaderspath' ) ] );
		}

		// Test connection.
		$claude = new \LeadersPath\Includes\Claude_API();
		$result = $claude->test_connection();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$model_count = $result['model_count'] ?? 0;

		wp_send_json_success( [
			'message' => sprintf(
				/* translators: %d: number of models */
				_n(
					'Connected! %d model available.',
					'Connected! %d models available.',
					$model_count,
					'leaderspath'
				),
				$model_count
			),
			'models'  => $result['models'] ?? [],
		] );
	}
}
