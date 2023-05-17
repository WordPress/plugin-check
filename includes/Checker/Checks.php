<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use Exception;

/**
 * Class to run checks on a plugin.
 *
 * @since n.e.x.t
 */
final class Checks {

	/**
	 * Array of all available Checks.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	protected $checks;

	/**
	 * Runs checks against the plugin.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Context $context The check context for the plugin to be checked.
	 * @param array         $checks  An array of Check objects to run.
	 * @return Check_Result Object containing all check results.
	 *
	 * @throws Exception Thrown when check fails with critical error.
	 */
	public function run_checks( Check_Context $context, array $checks ) {
		$result = new Check_Result( $context );

		// Run the checks.
		array_walk(
			$checks,
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
	private function run_check_with_result( Check $check, Check_Result $result ) {
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
}
