<?php
/**
 * Class WordPress\Plugin_Check\Admin\Admin_AJAX
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

use Exception;
use WordPress\Plugin_Check\Checker\AJAX_Runner;
use WordPress\Plugin_Check\Checker\Runtime_Check;
use WordPress\Plugin_Check\Checker\Runtime_Environment_Setup;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;
use WP_Error;

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

		$checks = filter_input( INPUT_POST, 'checks', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
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
		if ( 'wppc_posts' === $wpdb->get_var( "SHOW TABLES LIKE 'wppc_posts'" ) || defined( 'WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION' ) ) {
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

		$checks = filter_input( INPUT_POST, 'checks', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		$checks = is_null( $checks ) ? array() : $checks;
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
