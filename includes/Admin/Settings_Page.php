<?php
/**
 * Class WordPress\Plugin_Check\Admin\Settings_Page
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

use WordPress\Plugin_Check\Traits\AI_Utils;

/**
 * Class to handle the Settings page for Plugin Check.
 *
 * Provides AI model selection (from WordPress 7.0 core AI connectors)
 * and severity threshold configuration for AI false positive detection.
 *
 * @since 2.0.0
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class Settings_Page {

	use AI_Utils;

	/**
	 * Option group name.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const OPTION_GROUP = 'plugin_check_settings';

	/**
	 * Option name.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const OPTION_NAME = 'plugin_check_settings';

	/**
	 * Page slug.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const PAGE_SLUG = 'plugin-check-settings';

	/**
	 * Admin page hook suffix.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	protected $hook_suffix = '';

	/**
	 * Registers WordPress hooks for the settings page.
	 *
	 * @since 2.0.0
	 */
	public function add_hooks() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Adds the settings page under the Settings menu.
	 *
	 * @since 2.0.0
	 */
	public function add_page() {
		$this->hook_suffix = add_submenu_page(
			'options-general.php',
			__( 'Plugin Check', 'plugin-check' ),
			__( 'Plugin Check', 'plugin-check' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Registers settings and settings fields.
	 *
	 * @since 2.0.0
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(
					'ai_model_preference'  => '',
					'ai_severity_errors'   => 7,
					'ai_severity_warnings' => 6,
				),
			)
		);

		// AI Code Review section.
		add_settings_section(
			'ai_code_review_section',
			__( 'AI Code Review', 'plugin-check' ),
			array( $this, 'render_ai_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'ai_model_preference',
			__( 'AI Model', 'plugin-check' ),
			array( $this, 'render_model_preference_field' ),
			self::PAGE_SLUG,
			'ai_code_review_section',
			array(
				'label_for' => 'ai_model_preference',
			)
		);

		// Severity threshold section.
		add_settings_section(
			'ai_severity_section',
			__( 'Severity Threshold', 'plugin-check' ),
			array( $this, 'render_severity_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'ai_severity_errors',
			__( 'Errors', 'plugin-check' ),
			array( $this, 'render_severity_errors_field' ),
			self::PAGE_SLUG,
			'ai_severity_section',
			array(
				'label_for' => 'ai_severity_errors',
			)
		);

		add_settings_field(
			'ai_severity_warnings',
			__( 'Warnings', 'plugin-check' ),
			array( $this, 'render_severity_warnings_field' ),
			self::PAGE_SLUG,
			'ai_severity_section',
			array(
				'label_for' => 'ai_severity_warnings',
			)
		);
	}

	/**
	 * Renders the AI settings section description.
	 *
	 * @since 2.0.0
	 */
	public function render_ai_section_description() {
		$has_connectors = ! $this->has_no_active_ai_connectors();
		?>
		<p>
			<?php esc_html_e( 'Select the AI model to use for code review and false positive detection. Models are provided by the AI connectors configured in WordPress.', 'plugin-check' ); ?>
		</p>
		<?php if ( ! $has_connectors ) : ?>
			<div class="notice notice-warning inline">
				<p>
					<?php
					$configured_connector_message = sprintf(
						/* translators: %s: URL to WordPress AI settings. */
						__( 'No AI connectors are configured. Please <a href="%s">configure an AI connector</a> in WordPress settings first.', 'plugin-check' ),
						esc_url( admin_url( 'options-general.php' ) )
					);

					echo wp_kses(
						$configured_connector_message,
						array( 'a' => array( 'href' => array() ) )
					);
					?>
				</p>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders the severity section description.
	 *
	 * @since 2.0.0
	 */
	public function render_severity_section_description() {
		?>
		<p>
			<?php esc_html_e( 'Set the severity threshold (1-10). AI will analyze issues with severity BELOW this value. Low severity issues are more likely to be false positives.', 'plugin-check' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders the AI model preference field.
	 *
	 * Dynamically populated from WordPress 7.0 AI connectors.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Field arguments.
	 */
	public function render_model_preference_field( $args ) {
		$settings       = get_option( self::OPTION_NAME, array() );
		$value          = isset( $settings['ai_model_preference'] ) ? $settings['ai_model_preference'] : '';
		$grouped_models = $this->get_available_model_preferences();
		$has_models     = ! empty( $grouped_models );
		?>
		<select
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			name="<?php echo esc_attr( self::OPTION_NAME . '[ai_model_preference]' ); ?>"
			class="regular-text"
			<?php echo ! $has_models ? 'disabled' : ''; ?>
		>
			<option value=""><?php esc_html_e( '-- Default (auto) --', 'plugin-check' ); ?></option>
			<?php foreach ( $grouped_models as $group_label => $models ) : ?>
				<optgroup label="<?php echo esc_attr( $group_label ); ?>">
					<?php foreach ( $models as $model ) : ?>
						<option value="<?php echo esc_attr( $model['value'] ); ?>" <?php selected( $value, $model['value'] ); ?>>
							<?php echo esc_html( $model['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
		<?php if ( ! $has_models ) : ?>
			<p class="description" style="color: #d63638;">
				<?php esc_html_e( 'No AI models available. Please configure an AI connector in WordPress settings.', 'plugin-check' ); ?>
			</p>
		<?php else : ?>
			<p class="description">
				<?php esc_html_e( 'Select the AI model for code review. Code-optimized models (e.g., GPT-4o, Claude Sonnet) are recommended for best results.', 'plugin-check' ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders the severity threshold field for errors.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Field arguments.
	 */
	public function render_severity_errors_field( $args ) {
		$settings = get_option( self::OPTION_NAME, array() );
		$value    = isset( $settings['ai_severity_errors'] ) ? intval( $settings['ai_severity_errors'] ) : 7;
		?>
		<input
				type="number"
				id="<?php echo esc_attr( $args['label_for'] ); ?>"
				name="<?php echo esc_attr( self::OPTION_NAME . '[ai_severity_errors]' ); ?>"
				value="<?php echo esc_attr( (string) $value ); ?>"
				min="1"
				max="10"
				class="small-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Analyze errors with severity < this value (Default: 7)', 'plugin-check' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders the severity threshold field for warnings.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Field arguments.
	 */
	public function render_severity_warnings_field( $args ) {
		$settings = get_option( self::OPTION_NAME, array() );
		$value    = isset( $settings['ai_severity_warnings'] ) ? intval( $settings['ai_severity_warnings'] ) : 6;
		?>
		<input
				type="number"
				id="<?php echo esc_attr( $args['label_for'] ); ?>"
				name="<?php echo esc_attr( self::OPTION_NAME . '[ai_severity_warnings]' ); ?>"
				value="<?php echo esc_attr( (string) $value ); ?>"
				min="1"
				max="10"
				class="small-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Analyze warnings with severity < this value (Default: 6)', 'plugin-check' ); ?>
		</p>
		<?php
	}

	/**
	 * Sanitizes settings input.
	 *
	 * @since 2.0.0
	 *
	 * @param array $input Settings input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		if ( isset( $input['ai_model_preference'] ) ) {
			$sanitized['ai_model_preference'] = sanitize_text_field( $input['ai_model_preference'] );
		} else {
			$sanitized['ai_model_preference'] = '';
		}

		if ( isset( $input['ai_severity_errors'] ) ) {
			$value                           = intval( $input['ai_severity_errors'] );
			$sanitized['ai_severity_errors'] = ( $value >= 1 && $value <= 10 ) ? $value : 7;
		} else {
			$sanitized['ai_severity_errors'] = 7;
		}

		if ( isset( $input['ai_severity_warnings'] ) ) {
			$value                             = intval( $input['ai_severity_warnings'] );
			$sanitized['ai_severity_warnings'] = ( $value >= 1 && $value <= 10 ) ? $value : 6;
		} else {
			$sanitized['ai_severity_warnings'] = 6;
		}

		return $sanitized;
	}

	/**
	 * Gets the saved AI model preference.
	 *
	 * @since 2.0.0
	 *
	 * @return string AI model preference (e.g., 'openai::gpt-4o') or empty for auto.
	 */
	public static function get_model_preference() {
		$settings = get_option( self::OPTION_NAME, array() );
		return isset( $settings['ai_model_preference'] ) ? $settings['ai_model_preference'] : '';
	}

	/**
	 * Gets the AI severity threshold for errors.
	 *
	 * @since 2.0.0
	 *
	 * @return int AI severity threshold for errors.
	 */
	public static function get_severity_errors() {
		$settings = get_option( self::OPTION_NAME, array() );
		return isset( $settings['ai_severity_errors'] ) ? intval( $settings['ai_severity_errors'] ) : 7;
	}

	/**
	 * Gets the AI severity threshold for warnings.
	 *
	 * @since 2.0.0
	 *
	 * @return int AI severity threshold for warnings.
	 */
	public static function get_severity_warnings() {
		$settings = get_option( self::OPTION_NAME, array() );
		return isset( $settings['ai_severity_warnings'] ) ? intval( $settings['ai_severity_warnings'] ) : 6;
	}

	/**
	 * Renders the settings page.
	 *
	 * @since 2.0.0
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'plugin-check' ) );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Plugin Check Settings', 'plugin-check' ); ?></h1>

			<?php settings_errors( self::OPTION_NAME ); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
