<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use WordPress\Plugin_Check\Checker\Check_Context;
use Exception;

/**
 * Class to run checks on a plugin.
 *
 * @since n.e.x.t
 */
class Checks implements Preparation {

	/**
	 * Context for the plugin to check.
	 *
	 * @since n.e.x.t
	 * @var Check_Context
	 */
	protected $check_context;

	/**
	 * Sets the main context and the main file of the plugin to check.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $plugin_main_file Absolute path to the plugin main file.
	 */
	public function __construct( $plugin_main_file ) {
		$this->check_context = new Check_Context( $plugin_main_file );
	}

	/**
	 * Runs all checks against the plugin.
	 *
	 * @since n.e.x.t
	 *
	 * @return Check_Result Object containing all check results.
	 *
	 * @throws Exception Thrown when checks fail with critical error.
	 */
	public function run_all_checks() {
		$result = new Check_Result( $this->main_context, $this->check_context );
		$checks = $this->get_checks();

		array_walk(
			$checks,
			function( Check $check ) use ( $result ) {
				$this->run_check_with_result( $check, $result );
			}
		);

		return $result;
	}

	/**
	 * Runs a single check against the plugin.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $check Check class name.
	 * @return Check_Result Object containing all check results.
	 *
	 * @throws Exception Thrown when check fails with critical error.
	 */
	public function run_single_check( $check ) {
		$result = new Check_Result( $this->main_context, $this->check_context );
		$checks = $this->get_checks();

		// Look up the check based on the $check variable.
		$check_index = array_search( $check, $checks, true );
		if ( false === $check_index ) {
			throw new Exception(
				sprintf(
					/* translators: %s: class name */
					__( 'Invalid check class name %s.', 'plugin-check' ),
					$check
				)
			);
		}

		$this->run_check_with_result( $checks[ $check_index ], $result );

		return $result;
	}

	/**
	 * Runs a given check with the given result object to amend.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check        $check  The check to run.
	 * @param Check_Result $result The result object to amend.
	 *
	 * @throws Exception Thrown when check fails with critical error.
	 */
	protected function run_check_with_result( Check $check, Check_Result $result ) {
		// If $check implements Preparation interface, ensure the preparation and clean up is run.
		if ( $check instanceof Preparation ) {
			$cleanup = $check->prepare();

			try {
				$check->run( $result );
			} catch ( Exception $e ) {
				// Run clean up in case of any exception thrown from check.
				$cleanup();
				throw $e;
			}

			$cleanup();
			return;
		}

		// Otherwise, just run the check.
		$check->run( $result );
	}

	/**
	 * Gets the available plugin check classes.
	 *
	 * @since n.e.x.t
	 *
	 * @return array List of plugin check class instances implementing the Check interface.
	 */
	public function get_checks() {
		// TODO: Add checks once implemented.
		$checks = array();

		/**
		 * Filters the available plugin check classes.
		 *
		 * @since n.e.x.t
		 *
		 * @param array $checks List of plugin check class instances implementing the Check interface.
		 */
		return apply_filters( 'wp_plugin_check_checks', $checks );
	}
}
