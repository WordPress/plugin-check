<?php
/**
 * Trait WordPress\Plugin_Check\Checker\Experimental_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

/**
 * Trait for experimental checks.
 *
 * @since n.e.x.t
 */
trait Experimental_Check {
	/**
	 * Returns the checks stability.
	 *
	 * @since n.e.x.t
	 *
	 * @return string One of the check stability constant values.
	 */
	public function get_stability() {
		return self::STABILITY_EXPERIMENTAL;
	}
}
