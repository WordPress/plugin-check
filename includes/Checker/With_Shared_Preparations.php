<?php
/**
 * Interface WordPress\Plugin_Check\Checker\With_Shared_Preparations
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

/**
 * Interface for the shared preparations.
 *
 * @since n.e.x.t
 */
interface With_Shared_Preparations {

	/**
	 * Get list of preparation classes.
	 *
	 * @since n.e.x.t
	 *
	 * @return array Returns an array of preparation class names.
	 */
	public function get_shared_preparations();
}
