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
class Checks {

	/**
	 * Array of all available Checks.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	protected $checks;

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
	 * Returns the Check Context.
	 *
	 * @since n.e.x.t
	 *
	 * @return Check_Context The plugin context that is being checked.
	 */
	public function context() {
		return $this->check_context;
	}

	/**
	 * Runs checks against the plugin.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $checks An array of Check objects to run.
	 * @return Check_Result Object containing all check results.
	 *
	 * @throws Exception Thrown when check fails with critical error.
	 */
	public function run_checks( array $checks ) {
		$result     = new Check_Result( $this->check_context );
		$all_checks = $this->get_checks();

		// Create an array of Check objects to run based on the check names passed.
		$checks_to_run = array_filter(
			$checks,
			function( $check ) use ( $all_checks ) {
				return in_array( $check, $all_checks, true );
			}
		);

		// Run the checks.
		array_walk(
			$checks_to_run,
			function( Check $check ) use ( $result ) {
				$this->run_check_with_result( $check, $result );
			}
		);

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
	 * @return array An array map of check slugs to Check instances.
	 */
	public function get_checks() {
		if ( null === $this->checks ) {
			// TODO: Add checks once implemented.
			$checks = array(
				'i18n_usage'            => new Checks\I18n_Usage_Check(),
				'enqueued_scripts_size' => new Checks\Enqueued_Scripts_Size_Check(),
			);

			/**
			 * Filters the available plugin check classes.
			 *
			 * @since n.e.x.t
			 *
			 * @param array $checks An array map of check slugs to Check instances.
			 */
			$this->checks = apply_filters( 'wp_plugin_check_checks', $checks );
		}

		return $this->checks;
	}
}
