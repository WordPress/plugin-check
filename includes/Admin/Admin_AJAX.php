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
use WordPress\Plugin_Check\Checker\Check_Result;
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

		$checks = isset( $_REQUEST['checks'] ) ? array_filter( $_REQUEST['checks'] ) : array();
		$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_STRING );

		try {
			$runner->set_check_slugs( $checks );
			$runner->set_plugin( $plugin );
			$results = $runner->run();
		} catch ( Exception $error ) {
			wp_send_json_error(
				new WP_Error( 'invalid-request', $error->getMessage() ),
				400
			);
		}

		wp_send_json_success(
			array(
				'results' => $this->format_results( $results ),
				'checks'  => $checks,
				'message' => __( 'Checks run successfully', 'plugin-check' ),
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

	/**
	 * Format results for the AJAX response.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $results The Check Results containing errors and warnings.
	 *
	 * @return array An array of results.
	 */
	protected function format_results( Check_Result $results ) {
		// Get errors and warnings from the results.
		$errors      = $results->get_errors();
		$warnings    = $results->get_warnings();
		$all_results = array();

		// Go over all files with errors first and print them, combined with any warnings in the same file.
		foreach ( $errors as $file_name => $file_errors ) {
			$file_warnings = array();

			if ( isset( $warnings[ $file_name ] ) ) {
				$file_warnings = $warnings[ $file_name ];
				unset( $warnings[ $file_name ] );
			}

			$file_results = $this->flatten_file_results( $file_name, $file_errors, $file_warnings );
			$all_results  = array_merge( $all_results, $file_results );
		}

		// If there are any files left with only warnings, print those next.
		foreach ( $warnings as $file_name => $file_warnings ) {
			$file_results = $this->flatten_file_results( $file_name, array(), $file_warnings );
			$all_results  = array_merge( $all_results, $file_results );
		}

		return $all_results;
	}

	/**
	 * Flattens and combines the given associative array of file errors and file warnings into a two-dimensional array.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $file_name     The file name for the errors.
	 * @param array $file_errors   Errors from a Check_Result, for a the file.
	 * @param array $file_warnings Warnings from a Check_Result, for a the file.
	 * @return array Combined file results.
	 */
	protected function flatten_file_results( $file_name, $file_errors, $file_warnings ) {
		$file_results = array();

		foreach ( $file_errors as $line => $line_errors ) {
			foreach ( $line_errors as $column => $column_errors ) {
				foreach ( $column_errors as $column_error ) {
					$file_results[] = array(
						'code'    => $column_error['code'],
						'message' => $column_error['message'],
						'file'    => $file_name,
						'type'    => 'WARNING',
						'line'    => $line,
						'column'  => $column,
					);
				}
			}
		}

		foreach ( $file_warnings as $line => $line_warnings ) {
			foreach ( $line_warnings as $column => $column_warnings ) {
				foreach ( $column_warnings as $column_warning ) {

					$file_results[] = array(
						'code'    => $column_warning['code'],
						'message' => $column_warning['message'],
						'file'    => $file_name,
						'type'    => 'WARNING',
						'line'    => $line,
						'column'  => $column,
					);
				}
			}
		}

		usort(
			$file_results,
			function( $a, $b ) {
				if ( $a['line'] < $b['line'] ) {
					return -1;
				}
				if ( $a['line'] > $b['line'] ) {
					return 1;
				}
				if ( $a['column'] < $b['column'] ) {
					return -1;
				}
				if ( $a['column'] > $b['column'] ) {
					return 1;
				}
				return 0;
			}
		);

		return $file_results;
	}
}
