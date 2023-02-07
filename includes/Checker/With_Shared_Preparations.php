<?php
/**
 * Interface WordPress\Plugin_Check\Checker\With_Shared_Preparations
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

/**
 * Interface for a check that relies on shared preparations.
 *
 * @since n.e.x.t
 */
interface With_Shared_Preparations {

	/**
	 * Gets the list of shared preparations.
	 *
	 * @since n.e.x.t
	 *
	 * @return array Returns a map of $class_name => $constructor_args pairs. If the class does not
	 *               need any constructor arguments, it would just be an empty array.
	 */
	public function get_shared_preparations();
}
