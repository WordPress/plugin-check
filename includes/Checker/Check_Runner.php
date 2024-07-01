<?php
/**
 * Interface WordPress\Plugin_Check\Checker\Check_Runner
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use Exception;

/**
 * Interface for runner classes like AJAX runner or CLI runner.
 *
 * @since 1.0.0
 */
interface Check_Runner {

	/**
	 * Determines if the current request is intended for the plugin checker.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean Returns true if the check is for plugin else false.
	 */
	public static function is_plugin_check();

	/**
	 * Prepares the environment for running the requested checks.
	 *
	 * @since 1.0.0
	 *
	 * @return callable Cleanup function to revert any changes made here.
	 *
	 * @throws Exception Thrown exception when preparation fails.
	 */
	public function prepare();

	/**
	 * Runs the requested checks against the plugin context and returns the results.
	 *
	 * @since 1.0.0
	 *
	 * @return Check_Result Object containing all check results.
	 *
	 * @throws Exception Thrown exception if a check fails.
	 */
	public function run();
}
