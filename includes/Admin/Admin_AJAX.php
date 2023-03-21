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
use WordPress\Plugin_Check\Checker\Checks;
use WordPress\Plugin_Check\Checker\Runtime_Check;
use WordPress\Plugin_Check\Checker\Runtime_Environment_Setup;
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
		add_action( 'wp_ajax_plugin_check_clean_up_environment', array( $this, 'clean_up_environment' ) );
		add_action( 'wp_ajax_plugin_check_set_up_environment', array( $this, 'set_up_environment' ) );
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
	 * Handles the AJAX request to setup the runtime environment if needed.
	 *
	 * @since n.e.x.t
	 */
	public function set_up_environment() {
		// Verify the nonce before continuing.
		$valid_nonce = $this->verify_nonce( filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING ) );

		if ( is_wp_error( $valid_nonce ) ) {
			wp_send_json_error( $valid_nonce, 403 );
		}
		$runner = Plugin_Request_Utility::get_runner();

		if ( is_null( $runner ) ) {
			$runner = new AJAX_Runner();
		}

		// Make sure we are using the correct runner instance.
		if ( ! ( $runner instanceof AJAX_Runner ) ) {
			wp_send_json_error(
				new WP_Error( 'invalid-runner', __( 'AJAX Runner was not initialized correctly.', 'plugin-check' ) ),
				500
			);
		}

		$checks = wp_parse_list( filter_input( INPUT_POST, 'checks', FILTER_SANITIZE_STRING ) );
		$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_STRING );

		try {
			$runner->set_check_slugs( $checks );
			$runner->set_plugin( $plugin );
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
			$runtime->setup();
			$message = __( 'Runtime environment setup successful.', 'plugin-check' );
		}

		wp_send_json_success(
			array(
				'message' => $message,
				'plugin'  => $plugin,
				'checks'  => $checks,
			)
		);
	}

	/**
	 * Handles the AJAX request to cleanup the runtime environment.
	 *
	 * @since n.e.x.t
	 */
	public function clean_up_environment() {
		global $wpdb;

		// Verify the nonce before continuing.
		$valid_nonce = $this->verify_nonce( filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING ) );

		if ( is_wp_error( $valid_nonce ) ) {
			wp_send_json_error( $valid_nonce, 403 );
		}

		// Set the new prefix.
		$old_prefix = $wpdb->set_prefix( 'wppc_' );

		$message = __( 'Runtime environment was not prepared, cleanup was not run.', 'plugin-check' );

		// Test if the runtime environment tables exist.
		if ( 'wppc_posts' === $wpdb->get_var( "SHOW TABLES LIKE 'wppc_posts'" ) ) {
			$runtime = new Runtime_Environment_Setup();
			$runtime->cleanup();
			$message = __( 'Runtime environment cleanup successful.', 'plugin-check' );
		}

		// Restore the old prefix.
		$wpdb->set_prefix( $old_prefix );

		wp_send_json_success(
			array(
				'message' => $message,
			)
		);
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

		$checks = wp_parse_list( filter_input( INPUT_POST, 'checks', FILTER_SANITIZE_STRING ) );
		$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_STRING );

		// Attempt to get the plugin basename based on the request.
		try {
			$plugin_basename = Plugin_Request_Utility::get_plugin_basename_from_input( $plugin );
		} catch ( Exception $error ) {
			wp_send_json_error(
				new WP_Error( 'invalid-plugin', $error->getMessage() ),
				400
			);
		}

		$plugin_active = is_plugin_active( $plugin_basename );

		// Create the checks instance.
		$checks_instance = new Checks( WP_PLUGIN_DIR . '/' . $plugin_basename );
		$all_checks      = $checks_instance->get_checks();

		// If specific checks are requested to run.
		if ( ! empty( $checks ) ) {
			// Get the check instances based on the requested checks.
			$checks_to_run = array_intersect_key( $all_checks, array_flip( $checks ) );

			// Return an error if at least 1 runtime check is requested to run against an inactive plugin.
			if ( ! $plugin_active && $this->has_runtime_check( $checks_to_run ) ) {
				wp_send_json_error(
					new WP_Error(
						'inactive-plugin',
						__( 'Runtime checks cannot be run against inactive plugins.', 'plugin-check' )
					),
					400
				);
			}
		} else {
			// Run all checks for the plugin.
			$checks_to_run = $all_checks;

			// Only run static checks if the plugin is inactive.
			if ( ! $plugin_active ) {
				$checks_to_run = array_filter(
					$checks_to_run,
					function ( $check ) {
						return ! $check instanceof Runtime_Check;
					}
				);
			}
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
