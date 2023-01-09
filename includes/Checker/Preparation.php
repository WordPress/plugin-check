<?php
/**
 * Interface WordPress\Plugin_Check\Checker\Preparation
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use Exception;

/**
 * Interface for a single preparation step.
 *
 * @since n.e.x.t
 */
interface Preparation {

	/**
	 * Runs preparation step for the environment and returns a closure as a cleanup function.
	 *
	 * @since n.e.x.t
	 *
	 * @return callable Cleanup function to revert the changes made here.
	 *
	 * @throws Exception Thrown when preparation fails.
	 */
	public function prepare();
}
