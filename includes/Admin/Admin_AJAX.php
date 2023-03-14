<?php
/**
 * Class WordPress\Plugin_Check\Admin\Admin_AJAX
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

use WP_Error;
use Exception;
use WordPress\Plugin_Check\Checker\Checks;
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
		$this->verify_nonce();

		$checks = filter_input( INPUT_POST, 'checks', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_STRING );

		// Attempt to get the plugin basename based on the request.
		try {
			$plugin_basename = Plugin_Request_Utility::get_plugin_basename_from_input( $plugin );
		} catch ( Exception $error ) {
			wp_send_json_error(
				new WP_Error( 'invalid-plugin', $error->getMessage() ),
				403
			);
		}

		$plugin_active = is_plugin_active( $plugin_basename );

		// Create the checks instance.
		$checks_instance = new Checks( WP_PLUGIN_DIR . '/' . $plugin_basename );
		$all_checks      = $checks_instance->get_checks();

		// Filter checks to run based on the request.
		$checks_to_run = empty( $checks ) ? $all_checks : array_intersect_key( $all_checks, array_flip( $checks ) );

		// If the plugin is inactive and there are runtime checks.
		if ( ! $plugin_active && $this->has_runtime_check( $checks_to_run ) ) {
			// If specific checks were requested, return an error.
			if ( ! empty( $checks ) ) {
				wp_send_json_error(
					new WP_Error(
						__( 'Runtime checks cannot be run against inactive plugins.', 'plugin-check' )
					)
				);
			}

			// If all checks are requested, filter out any runtime checks.
			$checks_to_run = array_filter(
				$checks_to_run,
				function ( $check ) {
					return ! $check instanceof Runtime_Check;
				}
			);
		}

		wp_send_json_success(
			array(
				'plugin' => $plugin,
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
		$this->verify_nonce();

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
	 */
	protected function verify_nonce() {
		$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_KEY ) ) {
			wp_send_json_error(
				new WP_Error( 'invalid-nonce', __( 'Invalid nonce', 'plugin-check' ) ),
				403
			);
		}
	}

	/**
	 * Check for a Runtime_Check in a list of checks
	 *
	 * @since n.e.x.t
	 *
	 * @param array $checks An array of Check instances.
	 * @return boolean True if a Runtime_Check exists in the array, false if not.
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
