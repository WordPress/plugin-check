<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Abstract_Runtime_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use WordPress\Plugin_Check\Checker\Runtime_Check;
use WordPress\Plugin_Check\Checker\Preparation;
use WordPress\Plugin_Check\Checker\Check_Result;
use Exception;

/**
 * Check for running one or more PHP CodeSniffer sniffs.
 *
 * @since n.e.x.t
 */
abstract class Abstract_Runtime_Check implements Runtime_Check, Preparation {

	/**
	 * Runs preparation step for the environment and returns a closure as a cleanup function.
	 *
	 * @since n.e.x.t
	 *
	 * @return callable Cleanup function to revert the changes made here.
	 *
	 * @throws Exception Thrown when preparation fails.
	 */
	abstract public function prepare();

	/**
	 * Amends the given result by running the check on the associated plugin.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	abstract public function run( Check_Result $result );
}
