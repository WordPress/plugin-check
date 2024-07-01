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
 * @since 1.0.0
 */
interface Check {
	/**
	 * Stability value for stable checks.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const STABILITY_STABLE = 'STABLE';

	/**
	 * Stability value for experimental checks.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const STABILITY_EXPERIMENTAL = 'EXPERIMENTAL';

	/**
	 * Returns the check's stability.
	 *
	 * @since 1.0.0
	 *
	 * @return string One of the check stability constant values.
	 */
	public function get_stability();

	/**
	 * Amends the given result by running the check on the associated plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	public function run( Check_Result $result );

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.0.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories();
}
