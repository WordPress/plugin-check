<?php
/**
 * Class WordPress\Plugin_Check\Admin\Namer_Page
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

use WordPress\Plugin_Check\Traits\AI_Check_Names;
use WordPress\Plugin_Check\Traits\AI_Utils;
use WP_Error;

/**
 * Admin page for the Plugin Check Namer tool.
 *
 * @since 1.8.0
 */
final class Namer_Page {

	use AI_Check_Names;
	use AI_Utils;

	/**
	 * Menu slug.
	 *
	 * @since 1.8.0
	 * @var string
	 */
	const MENU_SLUG = 'plugin-check-namer';

	/**
	 * Admin-post action for analysis.
	 *
	 * @since 1.8.0
	 * @var string
	 */
	const ACTION_ANALYZE = 'plugin_check_namer_analyze';

	/**
	 * Hook suffix for the tools page.
	 *
	 * @since 1.8.0
	 * @var string
	 */
	protected $hook_suffix = '';

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 1.8.0
	 */
	public function add_hooks() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_post_' . self::ACTION_ANALYZE, array( $this, 'handle_analyze' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_plugin_check_namer_analyze', array( $this, 'ajax_analyze' ) );
	}

	/**
	 * Adds the tools page.
	 *
	 * @since 1.8.0
	 */
	public function add_page() {
		$this->hook_suffix = add_management_page(
			__( 'Plugin Check Namer', 'plugin-check' ),
			__( 'Plugin Check Namer', 'plugin-check' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueues scripts for the tools page.
	 *
	 * @since 1.8.0
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( $hook_suffix !== $this->hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'plugin-check-admin',
			plugins_url( 'assets/css/plugin-check-admin.css', WP_PLUGIN_CHECK_MAIN_FILE ),
			array(),
			WP_PLUGIN_CHECK_VERSION
		);

		wp_enqueue_script(
			'plugin-check-namer',
			plugins_url( 'assets/js/plugin-check-namer.js', WP_PLUGIN_CHECK_MAIN_FILE ),
			array(),
			WP_PLUGIN_CHECK_VERSION,
			true
		);

		wp_localize_script(
			'plugin-check-namer',
			'pluginCheckNamer',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'plugin_check_namer_ajax' ),
				'messages' => array(
					'missingName'  => __( 'Please enter a plugin name.', 'plugin-check' ),
					'genericError' => __( 'An unexpected error occurred.', 'plugin-check' ),
				),
			)
		);
	}

	/**
	 * AJAX handler to analyze a plugin name.
	 *
	 * @since 1.8.0
	 */
	public function ajax_analyze() {
		check_ajax_referer( 'plugin_check_namer_ajax', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'plugin-check' ) ) );
		}

		$name = $this->get_plugin_name_from_request();
		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a plugin name.', 'plugin-check' ) ) );
		}

		$author = $this->get_author_name_from_request();

		$model_preference = $this->get_model_preference_from_request();
		$ai_config        = $this->get_ai_config( $model_preference );
		if ( is_wp_error( $ai_config ) ) {
			wp_send_json_error( array( 'message' => $ai_config->get_error_message() ) );
		}

		$analysis = $this->run_name_analysis( $ai_config['model_preference'], $name, $author );
		if ( is_wp_error( $analysis ) ) {
			wp_send_json_error( array( 'message' => $analysis->get_error_message() ) );
		}

		$parsed   = $this->parse_analysis( $analysis );
		$response = $this->build_ajax_response( $parsed, $analysis, $ai_config );

		wp_send_json_success( $response );
	}

	/**
	 * Builds AJAX response from parsed analysis.
	 *
	 * @since 1.8.0
	 *
	 * @param array        $parsed    Parsed analysis.
	 * @param string|array $analysis  Raw analysis.
	 * @param array        $ai_config AI configuration with model preference info.
	 * @return array Response array.
	 */
	protected function build_ajax_response( $parsed, $analysis, $ai_config = array() ) {
		$raw_output = $this->get_raw_output( $parsed, $analysis );
		$raw_output = $this->format_json_output( $raw_output );

		$response = array(
			'verdict'     => $parsed['verdict'],
			'explanation' => $parsed['explanation'],
			'raw'         => $raw_output,
		);

		if ( ! empty( $parsed['confusion_existing_plugins'] ) ) {
			$response['confusion_existing_plugins'] = $parsed['confusion_existing_plugins'];
		}
		if ( ! empty( $parsed['confusion_existing_others'] ) ) {
			$response['confusion_existing_others'] = $parsed['confusion_existing_others'];
		}
		if ( ! empty( $parsed['token_usage'] ) ) {
			$response['token_usage'] = $parsed['token_usage'];
		}

		// Add AI model preference information.
		if ( ! empty( $ai_config['model_preference'] ) ) {
			$response['ai_info'] = array(
				'model' => $ai_config['model_preference'],
			);
		}

		return $response;
	}

	/**
	 * Gets plugin name from request.
	 *
	 * @since 1.8.0
	 *
	 * @return string Plugin name or empty string.
	 */
	protected function get_plugin_name_from_request() {
		$name = isset( $_POST['plugin_name'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_name'] ) ) : '';
		return trim( $name );
	}

	/**
	 * Gets author name from request.
	 *
	 * @since 1.8.0
	 *
	 * @return string Author name or empty string.
	 */
	protected function get_author_name_from_request() {
		$author = isset( $_POST['author_name'] ) ? sanitize_text_field( wp_unslash( $_POST['author_name'] ) ) : '';
		return trim( $author );
	}

	/**
	 * Renders the page.
	 *
	 * @since 1.8.0
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$ai_config = $this->get_ai_config();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Plugin Check Namer Tool', 'plugin-check' ); ?></h1>

			<?php
			if ( is_wp_error( $ai_config ) ) {
				?>
				<div class="notice notice-error">
					<p>
						<?php
						echo esc_html( $ai_config->get_error_message() );
						?>
					</p>
				</div>

				<?php
				if ( true === $this->check_ai_prerequisites() && is_wp_error( $this->check_ai_connectors() ) ) {
					?>
					<p>
						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'options-connectors.php' ) ); ?>">
							<?php esc_html_e( 'Configure AI Connectors', 'plugin-check' ); ?>
						</a>
					</p>
					<?php
				}
				return;
			}
			?>

			<form id="plugin-check-namer-form" method="post">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="plugin_check_namer_input"><?php echo esc_html__( 'Plugin name', 'plugin-check' ); ?></label>
							</th>
							<td>
								<input
									type="text"
									id="plugin_check_namer_input"
									name="plugin_check_namer_input"
									class="large-text"
									value=""
									required
								/>
								<p class="description">
									<?php echo esc_html__( 'Enter the plugin name you want to evaluate.', 'plugin-check' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="plugin_check_namer_author"><?php echo esc_html__( 'Author name', 'plugin-check' ); ?></label>
							</th>
							<td>
								<input
									type="text"
									id="plugin_check_namer_author"
									name="plugin_check_namer_author"
									class="regular-text"
									value=""
								/>
								<p class="description">
									<?php echo esc_html__( 'Optional: Enter the author or brand name if you own the trademark.', 'plugin-check' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="plugin_check_namer_model"><?php echo esc_html__( 'AI model', 'plugin-check' ); ?></label>
							</th>
							<td>
								<?php $model_groups = $this->get_available_model_preferences(); ?>
								<select
									id="plugin_check_namer_model"
									name="model_preference"
									class="regular-text"
								>
									<option value=""><?php echo esc_html__( 'Automatic (recommended)', 'plugin-check' ); ?></option>
									<?php foreach ( $model_groups as $group_label => $group_options ) : ?>
										<?php if ( ! empty( $group_label ) ) : ?>
											<optgroup label="<?php echo esc_attr( $group_label ); ?>">
										<?php endif; ?>
										<?php foreach ( $group_options as $model_option ) : ?>
											<option value="<?php echo esc_attr( $model_option['value'] ); ?>">
												<?php echo esc_html( $model_option['label'] ); ?>
											</option>
										<?php endforeach; ?>
										<?php if ( ! empty( $group_label ) ) : ?>
											</optgroup>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>
								<p class="description">
									<?php echo esc_html__( 'Choose a specific model from connected AI providers, or leave as automatic to let WordPress decide.', 'plugin-check' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="description">
					<strong><?php echo esc_html__( 'Note:', 'plugin-check' ); ?></strong>
					<br/>
					<?php echo esc_html__( 'This tool provides guidance only and is not definitive. It contains a prompt that is used to evaluate the similarity of a plugin name to other plugin names and ensure compliance with trademark regulations.', 'plugin-check' ); ?>
					<br/>
					<?php echo esc_html__( 'This analysis performs two AI checks for similarity and trademark conflicts, which may take a moment to complete.', 'plugin-check' ); ?>
				</p>
				<p class="submit">
					<button type="submit" class="button button-primary" id="plugin-check-namer-submit"><?php echo esc_html__( 'Evaluate name', 'plugin-check' ); ?></button>
					<span class="spinner plugin-check-namer-spinner" id="plugin-check-namer-spinner"></span>
				</p>
			</form>

			<div id="plugin-check-namer-error" class="notice notice-error plugin-check-namer-hidden"><p></p></div>

			<div id="plugin-check-namer-result" class="plugin-check-namer-hidden">
				<h2><?php echo esc_html__( 'Result', 'plugin-check' ); ?></h2>
				<div id="plugin-check-namer-verdict-container" class="plugin-check-namer-verdict-container plugin-check-namer-hidden">
					<p class="plugin-check-namer-verdict-item">
						<strong><?php echo esc_html__( 'Verdict:', 'plugin-check' ); ?></strong>
						<span id="plugin-check-namer-verdict"></span>
					</p>
					<p class="plugin-check-namer-verdict-item">
						<strong><?php echo esc_html__( 'Explanation:', 'plugin-check' ); ?></strong>
						<span id="plugin-check-namer-explanation"></span>
					</p>
				<p id="plugin-check-namer-timing" class="plugin-check-namer-meta plugin-check-namer-hidden">
					<strong><?php echo esc_html__( 'Analysis completed in:', 'plugin-check' ); ?></strong>
					<span id="plugin-check-namer-timing-value"></span>
				</p>
				<p id="plugin-check-namer-tokens" class="plugin-check-namer-meta plugin-check-namer-hidden">
					<strong><?php echo esc_html__( 'Tokens used:', 'plugin-check' ); ?></strong>
					<span id="plugin-check-namer-tokens-value"></span>
				</p>
			</div>
				<div id="plugin-check-namer-confusion-plugins" class="plugin-check-namer-confusion plugin-check-namer-hidden">
					<p><strong><?php echo esc_html__( 'Similar Existing Plugins', 'plugin-check' ); ?></strong></p>
					<div id="plugin-check-namer-confusion-plugins-list"></div>
				</div>

				<div id="plugin-check-namer-confusion-others" class="plugin-check-namer-confusion plugin-check-namer-hidden">
					<h3><?php echo esc_html__( 'Similar Existing Projects/Trademarks', 'plugin-check' ); ?></h3>
					<div id="plugin-check-namer-confusion-others-list"></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles the analysis form submission.
	 *
	 * @since 1.8.0
	 */
	public function handle_analyze() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'plugin-check' ) );
		}

		check_admin_referer( 'plugin_check_namer_analyze', 'plugin_check_namer_nonce' );

		$input = isset( $_POST['plugin_check_namer_input'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_check_namer_input'] ) ) : '';
		$input = trim( $input );

		$author = isset( $_POST['plugin_check_namer_author'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_check_namer_author'] ) ) : '';
		$author = trim( $author );

		$model_preference = $this->get_model_preference_from_request();
		$user_id          = get_current_user_id();

		if ( empty( $input ) ) {
			$this->handle_analyze_error( $user_id, '', new WP_Error( 'missing_input', __( 'Please enter a plugin name.', 'plugin-check' ) ) );
			return;
		}

		$ai_config = $this->get_ai_config( $model_preference );
		if ( is_wp_error( $ai_config ) ) {
			$this->handle_analyze_error( $user_id, $input, $ai_config );
			return;
		}

		$analysis = $this->run_name_analysis( $ai_config['model_preference'], $input, $author );

		if ( is_wp_error( $analysis ) ) {
			$this->handle_analyze_error( $user_id, $input, $analysis );
			return;
		}

		$this->store_result(
			$user_id,
			array(
				'input'    => $input,
				'analysis' => $analysis,
			)
		);
		wp_safe_redirect( $this->get_page_url() );
		exit;
	}

	/**
	 * Handles analyze error and redirects.
	 *
	 * @since 1.8.0
	 *
	 * @param int      $user_id User ID.
	 * @param string   $input   Input value.
	 * @param WP_Error $error   Error object.
	 */
	protected function handle_analyze_error( $user_id, $input, $error ) {
		$this->store_result(
			$user_id,
			array(
				'input' => $input,
				'error' => $error,
			)
		);
		wp_safe_redirect( $this->get_page_url() );
		exit;
	}

	/**
	 * Gets the page URL.
	 *
	 * @since 1.8.0
	 *
	 * @return string
	 */
	protected function get_page_url() {
		return add_query_arg( array( 'page' => self::MENU_SLUG ), admin_url( 'tools.php' ) );
	}
}
