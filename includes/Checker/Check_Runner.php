<?php
/**
 * Interface WordPress\Plugin_Check\Checker\Check_Runner
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

/**
 * Interface for runner classes like AJAX runner or CLI runner.
 *
 * @since n.e.x.t
 */
interface Check_Runner {

	/**
	 * Determine if the current request is intended for the plugin checker.
	 *
	 * @since n.e.x.t
	 *
	 * @return boolean Returns true if the check is for plugin else false.
	 */
	public function is_plugin_check();

	/**
	 * Handles running the universal preparations depending on the requested checks.
	 */
	public function prepare();

	/**
	 * Run the requested checks against the plugin context and return the results.
	 *
	 * @return Check_Result Object containing all check results.
	 */
	public function run();

}
