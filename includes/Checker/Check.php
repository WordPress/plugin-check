<?php
/**
 * Interface WordPress\Plugin_Check\Checker\Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use Exception;

/**
 * Interface for a single check.
 *
 * @since n.e.x.t
 */
interface Check {
	/**
	 * Flag for stable checks.
	 *
	 * @since n.e.x.t
	 * @var int
	 */
	const STABILITY_STABLE = 1;

	/**
	 * Flag for experimental checks.
	 *
	 * @since n.e.x.t
	 * @var int
	 */
	const STABILITY_EXPERIMENTAL = 2;

	/**
	 * Returns the checks stability.
	 *
	 * @since n.e.x.t
	 *
	 * @return int
	 */
	public function get_stability();

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
	public function run( Check_Result $result );
}
