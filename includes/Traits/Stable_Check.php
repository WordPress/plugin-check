<?php
/**
 * Trait WordPress\Plugin_Check\Traits\Stable_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

/**
 * Trait for stable checks.
 *
 * @since n.e.x.t
 */
trait Stable_Check {
	/**
	 * Returns the checks stability.
	 *
	 * @return string
	 */
	public function get_stability() {
		return self::STABILITY_STABLE;
	}
}
