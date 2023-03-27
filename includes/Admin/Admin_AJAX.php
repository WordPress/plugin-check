<?php
/**
 * Class WordPress\Plugin_Check\Admin\Admin_AJAX
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

use WP_Error;
use Exception;
use WordPress\Plugin_Check\Checker\AJAX_Runner;
use WordPress\Plugin_Check\Checker\Runtime_Check;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;
/**
 * Class to handle the Admin AJAX requests.
 *
 * @since n.e.x.t
 */
class Admin_AJAX {

	/**
	 * Nonce key.
	 *
	 * @since n.e.x.t
	 * @var string
	 */
	const NONCE_KEY = 'plugin-check-run-checks';

	/**
	 * Registers WordPress hooks for the Admin AJAX.
	 *
	 * @since n.e.x.t
	 */
	public function add_hooks() {
		add_action( 'wp_ajax_plugin_check_get_checks_to_run', array( $this, 'get_checks_to_run' ) );
		add_action( 'wp_ajax_plugin_check_run_checks', array( $this, 'run_checks' ) );
	}

	/**
	 * Creates and returns the nonce.
	 *
	 * @since n.e.x.t
	 */
	public function get_nonce() {
		return wp_create_nonce( self::NONCE_KEY );
	}

	/**
	 * Handles the AJAX request that returns the checks to run.
	 *
	 * @since n.e.x.t
	 */
	public function get_checks_to_run() {
		// Verify the nonce before continuing.
		$valid_nonce = $this->verify_nonce( filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING ) );

		if ( is_wp_error( $valid_nonce ) ) {
			wp_send_json_error( $valid_nonce, 403 );
		}

		$checks = filter_input( INPUT_POST, 'checks', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_STRING );

		$runner = Plugin_Request_Utility::get_runner();

		if ( is_null( $runner ) ) {
			$runner = new AJAX_Runner();
		}

		// Make sure we are using the correct runner instance.
		if ( ! ( $runner instanceof AJAX_Runner ) ) {
			wp_send_json_error(
				new WP_Error( 'invalid-runner', __( 'AJAX Runner was not initialized correctly.', 'plugin-check' ) ),
				403
			);
		}

		try {
			$runner->set_check_slugs( $checks );
			$runner->set_plugin( $plugin );

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
	 * @since n.e.x.t
	 */
	public function run_checks() {
		// Verify the nonce before continuing.
		$valid_nonce = $this->verify_nonce( filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING ) );

		if ( is_wp_error( $valid_nonce ) ) {
			wp_send_json_error( $valid_nonce, 403 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Verified!', 'plugin-check' ),
			)
		);
	}

	/**
	 * Verify the nonce passed in the request.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $nonce The request nonce passed.
	 * @return bool|WP_Error True if the nonce is valid. WP_Error if invalid.
	 */
	protected function verify_nonce( $nonce ) {
		if ( ! wp_verify_nonce( $nonce, self::NONCE_KEY ) ) {
			new WP_Error( 'invalid-nonce', __( 'Invalid nonce', 'plugin-check' ) );
		}

		return true;
	}

	/**
	 * Check for a Runtime_Check in a list of checks
	 *
	 * @since n.e.x.t
	 *
	 * @param array $checks An array of Check instances.
	 * @return bool True if a Runtime_Check exists in the array, false if not.
	 */
	protected function has_runtime_check( array $checks ) {
		foreach ( $checks as $check ) {
			if ( $check instanceof Runtime_Check ) {
				return true;
			}
		}

		return false;
	}
}
