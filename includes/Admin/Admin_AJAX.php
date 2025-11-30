<?php
/**
 * Class WordPress\Plugin_Check\Admin\Admin_AJAX
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

use Exception;
use InvalidArgumentException;
use WordPress\Plugin_Check\Checker\AJAX_Runner;
use WordPress\Plugin_Check\Checker\Runtime_Check;
use WordPress\Plugin_Check\Checker\Runtime_Environment_Setup;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;
use WordPress\Plugin_Check\Utilities\Results_Exporter;
use WP_Error;

/**
 * Class to handle the Admin AJAX requests.
 *
 * @since 1.0.0
 */
final class Admin_AJAX {

	/**
	 * Nonce key.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const NONCE_KEY = 'plugin-check-run-checks';

	/**
	 * Clean up Runtime Environment action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTION_CLEAN_UP_ENVIRONMENT = 'plugin_check_clean_up_environment';

	/**
	 * Set up Runtime Environment action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTION_SET_UP_ENVIRONMENT = 'plugin_check_set_up_environment';

	/**
	 * Get Checks to run action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTION_GET_CHECKS_TO_RUN = 'plugin_check_get_checks_to_run';

	/**
	 * Run Checks action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTION_RUN_CHECKS = 'plugin_check_run_checks';

	/**
	 * Export results action name.
	 *
	 * @since 1.8.0
	 * @var string
	 */
	const ACTION_EXPORT_RESULTS = 'plugin_check_export_results';

	/**
	 * Registers WordPress hooks for the Admin AJAX.
	 *
	 * @since 1.0.0
	 */
	public function add_hooks() {
		add_action( 'wp_ajax_' . self::ACTION_CLEAN_UP_ENVIRONMENT, array( $this, 'clean_up_environment' ) );
		add_action( 'wp_ajax_' . self::ACTION_SET_UP_ENVIRONMENT, array( $this, 'set_up_environment' ) );
		add_action( 'wp_ajax_' . self::ACTION_GET_CHECKS_TO_RUN, array( $this, 'get_checks_to_run' ) );
		add_action( 'wp_ajax_' . self::ACTION_RUN_CHECKS, array( $this, 'run_checks' ) );
		add_action( 'wp_ajax_' . self::ACTION_EXPORT_RESULTS, array( $this, 'export_results' ) );
	}

	/**
	 * Creates and returns the nonce.
	 *
	 * @since 1.0.0
	 */
	public function get_nonce() {
		return wp_create_nonce( self::NONCE_KEY );
	}

	/**
	 * Check if the request is valid.
	 *
	 * @since 1.8.0
	 */
	private function check_request_validity() {
		// Verify the nonce before continuing.
		$valid_request = $this->verify_request( filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );

		if ( is_wp_error( $valid_request ) ) {
			wp_send_json_error( $valid_request, 403 );
		}
	}

	/**
	 * Configures the runner based on the current request.
	 *
	 * @since 1.8.0
	 *
	 * @param AJAX_Runner $runner The runner to configure.
	 * @return array The configuration used.
	 */
	private function configure_runner( $runner ) {
		$checks               = filter_input( INPUT_POST, 'checks', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		$checks               = is_null( $checks ) ? array() : $checks;
		$plugin               = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$include_experimental = 1 === filter_input( INPUT_POST, 'include-experimental', FILTER_VALIDATE_INT );

		$runner->set_experimental_flag( $include_experimental );
		$runner->set_check_slugs( $checks );
		$runner->set_plugin( $plugin );

		return array(
			'checks' => $checks,
			'plugin' => $plugin,
		);
	}

	/**
	 * Retrieves the AJAX Runner instance.
	 *
	 * @since 1.8.0
	 *
	 * @return AJAX_Runner|WP_Error The runner instance or WP_Error on failure.
	 */
	private function get_ajax_runner() {
		$runner = Plugin_Request_Utility::get_runner();

		if ( is_null( $runner ) ) {
			$runner = new AJAX_Runner();
		}

		if ( ! ( $runner instanceof AJAX_Runner ) ) {
			return new WP_Error( 'invalid-runner', __( 'AJAX Runner was not initialized correctly.', 'plugin-check' ) );
		}

		return $runner;
	}

	/**
	 * Handles the AJAX request to setup the runtime environment if needed.
	 *
	 * @since 1.0.0
	 */
	public function set_up_environment() {
		$this->check_request_validity();

		$runner = $this->get_ajax_runner();

		if ( is_wp_error( $runner ) ) {
			wp_send_json_error( $runner, 500 );
		}

		try {
			$config = $this->configure_runner( $runner );

			$checks_to_run = $runner->get_checks_to_run();
		} catch ( Exception $error ) {
			wp_send_json_error(
				new WP_Error( 'invalid-request', $error->getMessage() ),
				400
			);
		}

		$message = __( 'No runtime checks, runtime environment was not setup.', 'plugin-check' );

		if ( $this->has_runtime_check( $checks_to_run ) ) {
			$runtime = new Runtime_Environment_Setup();
			$runtime->set_up();
			$message = __( 'Runtime environment setup successful.', 'plugin-check' );
		}

		wp_send_json_success(
			array(
				'message' => $message,
				'plugin'  => $config['plugin'],
				'checks'  => $config['checks'],
			)
		);
	}

	/**
	 * Handles the AJAX request to cleanup the runtime environment.
	 *
	 * @since 1.0.0
	 */
	public function clean_up_environment() {
		$this->check_request_validity();

		// Test if the runtime environment is prepared (and thus needs cleanup).
		$runtime = new Runtime_Environment_Setup();
		if ( $runtime->is_set_up() ) {
			$runtime->clean_up();
			$message = __( 'Runtime environment cleanup successful.', 'plugin-check' );
		} else {
			$message = __( 'Runtime environment was not prepared, cleanup was not run.', 'plugin-check' );
		}

		wp_send_json_success(
			array(
				'message' => $message,
			)
		);
	}

	/**
	 * Handles the AJAX request that returns the checks to run.
	 *
	 * @since 1.0.0
	 */
	public function get_checks_to_run() {
		$this->check_request_validity();

		$categories = filter_input( INPUT_POST, 'categories', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		$categories = is_null( $categories ) ? array() : $categories;

		$runner = $this->get_ajax_runner();

		if ( is_wp_error( $runner ) ) {
			wp_send_json_error( $runner, 403 );
		}

		try {
			$this->configure_runner( $runner );
			$runner->set_categories( $categories );

			$plugin_basename = $runner->get_plugin_basename();
			$checks_to_run   = $runner->get_checks_to_run();
		} catch ( Exception $error ) {
			wp_send_json_error(
				new WP_Error( 'invalid-checks', $error->getMessage() ),
				403
			);
		}

		wp_send_json_success(
			array(
				'plugin' => $plugin_basename,
				'checks' => array_keys( $checks_to_run ),
			)
		);
	}

	/**
	 * Run checks.
	 *
	 * @since 1.0.0
	 */
	public function run_checks() {
		$this->check_request_validity();

		$runner = $this->get_ajax_runner();

		if ( is_wp_error( $runner ) ) {
			wp_send_json_error( $runner, 500 );
		}

		$types = filter_input( INPUT_POST, 'types', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		$types = is_null( $types ) ? array() : $types;

		try {
			$this->configure_runner( $runner );
			$results = $runner->run();
		} catch ( Exception $error ) {
			wp_send_json_error(
				new WP_Error( 'invalid-request', $error->getMessage() ),
				400
			);
		}

		$response_data = $this->prepare_results_response( $results, $types );

		wp_send_json_success( $response_data );
	}

	/**
	 * Prepare the results response based on requested types.
	 *
	 * @since 1.8.0
	 *
	 * @param object $results The check results object.
	 * @param array  $types   The types of results to include (error, warning).
	 * @return array The prepared response data.
	 */
	private function prepare_results_response( $results, array $types ) {
		$response = array(
			'message'  => __( 'Checks run successfully', 'plugin-check' ),
			'errors'   => array(),
			'warnings' => array(),
		);

		if ( in_array( 'error', $types, true ) ) {
			$response['errors'] = $results->get_errors();
		}

		if ( in_array( 'warning', $types, true ) ) {
			$response['warnings'] = $results->get_warnings();
		}

		return $response;
	}


	/**
	 * Handles exporting Plugin Check results.
	 *
	 * @since 1.8.0
	 */
	public function export_results() {
		$this->check_request_validity();

		try {
			$format          = $this->determine_export_format();
			$results_payload = $this->extract_results_payload();
			$export_metadata = $this->prepare_export_metadata();
			$payload         = $this->build_export_payload( $results_payload, $format, $export_metadata );
		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}

		wp_send_json_success( $payload );
	}

	/**
	 * Determines the requested export format.
	 *
	 * @since 1.8.0
	 *
	 * @return string Export format slug.
	 */
	private function determine_export_format() {
		$format = filter_input( INPUT_POST, 'format', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( empty( $format ) ) {
			return Results_Exporter::FORMAT_JSON;
		}

		return strtolower( $format );
	}

	/**
	 * Extracts errors and warnings payload from the request.
	 *
	 * @since 1.8.0
	 *
	 * @return array{
	 *     errors: array,
	 *     warnings: array,
	 * }
	 *
	 * @throws InvalidArgumentException When the payload is missing or malformed.
	 */
	private function extract_results_payload() {
		$raw_results = isset( $_POST['results'] ) ? wp_unslash( $_POST['results'] ) : '';
		if ( '' === $raw_results ) {
			throw new InvalidArgumentException( __( 'Invalid or empty results payload.', 'plugin-check' ) );
		}

		$decoded_results = json_decode( $raw_results, true );
		if ( null === $decoded_results || JSON_ERROR_NONE !== json_last_error() ) {
			throw new InvalidArgumentException( __( 'Malformed results payload.', 'plugin-check' ) );
		}

		return array(
			'errors'   => isset( $decoded_results['errors'] ) && is_array( $decoded_results['errors'] ) ? $decoded_results['errors'] : array(),
			'warnings' => isset( $decoded_results['warnings'] ) && is_array( $decoded_results['warnings'] ) ? $decoded_results['warnings'] : array(),
		);
	}

	/**
	 * Prepares metadata used for export filenames and headers.
	 *
	 * @since 1.8.0
	 *
	 * @return array Metadata values.
	 */
	private function prepare_export_metadata() {
		$plugin_slug  = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$plugin_label = isset( $_POST['plugin_label'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_label'] ) ) : '';
		if ( empty( $plugin_label ) ) {
			$plugin_label = $plugin_slug;
		}

		return array(
			'plugin'          => $plugin_label,
			'slug'            => $plugin_slug,
			'timestamp'       => current_time( 'Ymd-His' ),
			'timestamp_human' => current_time( 'mysql' ),
		);
	}

	/**
	 * Builds the export payload using the Results Exporter.
	 *
	 * @since 1.8.0
	 *
	 * @param array  $results_payload Payload containing errors and warnings.
	 * @param string $format          Export format slug.
	 * @param array  $metadata        Export metadata.
	 * @return array Export payload.
	 *
	 * @throws InvalidArgumentException If the payload cannot be generated.
	 */
	private function build_export_payload( array $results_payload, $format, array $metadata ) {
		return Results_Exporter::export(
			$results_payload['errors'],
			$results_payload['warnings'],
			$format,
			$metadata
		);
	}

	/**
	 * Verify the request.
	 *
	 * @since 1.0.0
	 *
	 * @param string $nonce The request nonce passed.
	 * @return bool|WP_Error True if the nonce is valid. WP_Error if invalid.
	 */
	private function verify_request( $nonce ) {
		if ( ! wp_verify_nonce( $nonce, self::NONCE_KEY ) ) {
			return new WP_Error( 'invalid-nonce', __( 'Invalid nonce', 'plugin-check' ) );
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return new WP_Error( 'invalid-permissions', __( 'Invalid user permissions, you are not allowed to perform this request.', 'plugin-check' ) );
		}

		return true;
	}

	/**
	 * Check for a Runtime_Check in a list of checks.
	 *
	 * @since 1.0.0
	 *
	 * @param array $checks An array of Check instances.
	 * @return bool True if a Runtime_Check exists in the array, false if not.
	 */
	private function has_runtime_check( array $checks ) {
		foreach ( $checks as $check ) {
			if ( $check instanceof Runtime_Check ) {
				return true;
			}
		}

		return false;
	}
}
