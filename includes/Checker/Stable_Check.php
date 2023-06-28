<?php
/**
 * Trait WordPress\Plugin_Check\Checker\Stable_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

/**
 * Trait for stable checks.
 *
 * @since n.e.x.t
 */
trait Stable_Check {
	/**
	 * Returns the checks stability
	 *
	 * @return int
	 */
	public function get_stability() {
		return self::STABILITY_STABLE;
	}
}
